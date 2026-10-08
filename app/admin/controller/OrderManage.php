<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\service\ApiClientService;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use think\facade\Db;

/**
 * 后台订单管理控制器
 */
class OrderManage extends BaseController
{
    private function getAdminId(): ?int
    {
        $adminId = Session::get('admin_id');
        return $adminId ? (int)$adminId : null;
    }

    private function ensureAdminForView()
    {
        if (!$this->getAdminId()) {
            return redirect('/admin/login');
        }
        return null;
    }

    private function orderTypeConfig(): array
    {
        return [
            'paper' => [
                'label' => '论文订单',
                'return_url' => '/admin/ordersPaper',
            ],
            'ppt' => [
                'label' => 'PPT订单',
                'return_url' => '/admin/ordersPpt',
            ],
            'write' => [
                'label' => '写作中心订单',
                'return_url' => '/admin/ordersWrite',
            ],
            'check' => [
                'label' => '降重订单',
                'return_url' => '/admin/ordersCheck',
            ],
            'autodoc' => [
                'label' => '格式重排订单',
                'return_url' => '/admin/ordersAutodoc',
            ],
        ];
    }

    private function mapPayStatusText(int $payStatus): string
    {
        $statusMap = [
            0 => '未支付',
            1 => '已支付',
            2 => '支付失败',
        ];
        return $statusMap[$payStatus] ?? '未知';
    }

    /**
     * 论文/写作类订单生成状态（与对接文档一致）
     */
    private function mapGenStatusText(int $genStatus): string
    {
        $map = [
            0 => '待生成',
            1 => '生成中',
            2 => '生成成功',
            3 => '生成失败',
            4 => '排版中',
            5 => '排版失败',
            6 => '待重新排版',
            7 => '重新排版中',
        ];
        return $map[$genStatus] ?? '未知';
    }

    /**
     * 默认入口：展示论文订单列表（使用独立页面，不走 tabs）
     */
    public function index()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        return redirect('/admin/ordersPaper');
    }

    public function paper()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        $adminId = $this->getAdminId();
        View::assign('admin_id', $adminId);
        return View::fetch('order_manage/paper_orders');
    }

    public function ppt()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        return View::fetch('order_manage/ppt_orders');
    }

    public function write()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        return View::fetch('order_manage/write_orders');
    }

    public function check()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        return View::fetch('order_manage/check_orders');
    }

    public function autodoc()
    {
        $redirect = $this->ensureAdminForView();
        if ($redirect) return $redirect;
        return View::fetch('order_manage/autodoc_orders');
    }

    /**
     * 后台：按订单类型拉取列表 —— 数据源为本站订单表（下单时写入，天然带用户归属）
     * - 状态保鲜：对当前页未完成行做一次上游批量同步（仅 1 次 openapi 调用，失败静默）
     */
    public function getOrders()
    {
        if (!$this->getAdminId()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $type = (string)Request::param('type', 'paper');
        $config = $this->orderTypeConfig();
        if (!isset($config[$type])) {
            $type = 'paper';
        }

        $keyword = trim((string)Request::param('keyword', ''));
        $page = max(1, (int)Request::param('page', 1));
        $pageSize = max(5, min(50, (int)Request::param('page_size', 15)));

        // 注意：leftJoin 数组别名写法不走表前缀自动补全，必须写全前缀表名 ad_users
        $query = Db::name('orders')->alias('o')
            ->leftJoin(['ad_users' => 'u'], 'u.id = o.user_id')
            ->field('o.id,o.order_no,o.product_code,o.product_name,o.total_amount,o.status,o.pay_status,o.pay_time,o.create_time,o.product_data,u.username')
            ->where('o.product_code', $type);
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($q) use ($like) {
                $q->whereLike('o.order_no', $like)
                  ->whereOr('u.username', 'like', $like)
                  ->whereOr('o.product_data', 'like', $like);
            });
        }

        $total = (clone $query)->count();
        $rows = $query->order('o.id', 'desc')->page($page, $pageSize)->select()->toArray();

        // 未完成行状态保鲜（单次上游调用）
        $this->refreshPendingFromUpstream($type, $rows);

        $list = [];
        foreach ($rows as $row) {
            $pd = json_decode((string)($row['product_data'] ?? ''), true);
            if (!is_array($pd)) {
                $pd = [];
            }
            $status = (int)($row['status'] ?? 0);
            $payStatus = (int)($row['pay_status'] ?? 0);
            $statusText = trim((string)($pd['status_text'] ?? ''));
            if ($statusText === '') {
                $statusText = $type === 'check' ? $this->mapCheckStatusText($status) : $this->mapGenStatusText($status);
            }

            $list[] = [
                'order_no' => (string)($row['order_no'] ?? ''),
                'title' => (string)($pd['title'] ?? $row['product_name'] ?? ''),
                'agent_amount' => $row['total_amount'],
                'pay_status' => $payStatus,
                'pay_status_text' => $this->mapPayStatusText($payStatus),
                'status' => $status,
                'status_text' => $statusText,
                'username' => (string)($row['username'] ?? ''),
                'create_time' => (string)($row['create_time'] ?? ''),
                'pay_time' => (string)($row['pay_time'] ?? ''),
                // 结果文件链接不入本地库，详情页实时拉主站获取
                'download_url' => '',
                'preview_url' => '',
                '_raw' => ['local' => true, 'product_data' => $pd],
            ];
        }

        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ],
        ]);
    }

    /**
     * 列表状态保鲜：对当前页未完成行按单号精准拉取主站（paper/ppt/write/autodoc → /openapi/order/detail?order_sn=，
     * check → /openapi/check/orderList?keyword=单号），不做全量列表拉取；上游不可用时静默保持本地值。
     */
    private function refreshPendingFromUpstream(string $type, array &$rows): void
    {
        $pending = [];
        foreach ($rows as $row) {
            $st = (int)($row['status'] ?? 0);
            $done = ($type === 'check') ? ($st === 2 || $st === 3) : ($st === 2 || $st === 3 || $st === 5);
            $sn = (string)($row['order_no'] ?? '');
            if (!$done && $sn !== '') {
                $pending[$sn] = true;
            }
        }
        if (empty($pending)) {
            return;
        }

        // 未完成单通常仅少数几条；逐单单号精准拉取，上限 5 次防串行调用拖慢列表
        $targets = array_slice(array_keys($pending), 0, 5);
        try {
            $api = new ApiClientService();
            $now = date('Y-m-d H:i:s');
            foreach ($targets as $sn) {
                $up = null;
                if ($type === 'check') {
                    $r = $api->get('/openapi/check/orderList', ['status' => 'all', 'keyword' => $sn, 'page' => 1, 'page_size' => 5]);
                    if (is_array($r) && (int)($r['code'] ?? 0) === 1) {
                        foreach (($r['data']['list'] ?? []) as $it) {
                            if (is_array($it) && (string)($it['order_sn'] ?? '') === $sn) {
                                $up = $it;
                                break;
                            }
                        }
                    }
                } elseif ($type === 'autodoc') {
                    // autodoc 走主站专用详情接口（/openapi/order/detail 的 autodoc 分支线上暂不可用）
                    $r = $api->get('/openapi/autodoc/detail', ['order_sn' => $sn]);
                    if (is_array($r) && (int)($r['code'] ?? 0) === 1 && is_array($r['data'] ?? null) && !empty($r['data'])) {
                        $up = $r['data'];
                    }
                } else {
                    $r = $api->get('/openapi/order/detail', ['type' => $type, 'order_sn' => $sn]);
                    if (is_array($r) && (int)($r['code'] ?? 0) === 1 && is_array($r['data'] ?? null) && !empty($r['data'])) {
                        $up = $r['data'];
                    }
                }
                if ($up === null) {
                    continue;
                }

                // 状态字段按类型取：ppt 详情为 ppt_status，paper/write/autodoc 为 gen_status，check 为 status
                if ($type === 'ppt') {
                    $newStatus = (int)($up['ppt_status'] ?? -1);
                } elseif ($type === 'check') {
                    $newStatus = (int)($up['status'] ?? -1);
                } else {
                    $newStatus = (int)($up['gen_status'] ?? -1);
                }
                if ($newStatus < 0) {
                    continue;
                }

                $update = ['status' => $newStatus, 'update_time' => $now];
                if ($type !== 'check' && isset($up['pay_status'])) {
                    $update['pay_status'] = (int)$up['pay_status'];
                }
                Db::name('orders')->where('order_no', $sn)->update($update);
                foreach ($rows as $k => $row) {
                    if ((string)($row['order_no'] ?? '') === $sn) {
                        $rows[$k]['status'] = $newStatus;
                        if (isset($update['pay_status'])) {
                            $rows[$k]['pay_status'] = $update['pay_status'];
                        }
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // 静默：上游不可用时列表仍按本地数据显示
        }
    }

    /**
     * 降重订单状态文案（check 语义：0 待处理 1 处理中 2 已完成 3 失败）
     */
    private function mapCheckStatusText(int $status): string
    {
        $map = [0 => '待处理', 1 => '处理中', 2 => '已完成', 3 => '失败'];
        return $map[$status] ?? '未知';
    }

    /**
     * 后台：订单详情 —— 全部按单号精准拉取主站实时数据（paper/ppt/write/autodoc 走 /openapi/order/detail?order_sn=，
     * 降重走 /openapi/check/orderList?keyword=单号），失败回退本地下单快照。
     */
    public function orderDetail()
    {
        if (!$this->getAdminId()) {
            return redirect('/admin/login');
        }

        $type = (string)Request::param('type', 'paper');
        $config = $this->orderTypeConfig();
        if (!isset($config[$type])) {
            $type = 'paper';
        }

        $returnUrl = $config[$type]['return_url'] ?? '/admin/ordersPaper';
        $orderNo = trim((string)Request::param('order_no', ''));
        if ($orderNo === '') {
            return View::fetch('order_manage/order_detail', [
                'error' => '订单号不能为空',
                'returnUrl' => $returnUrl,
            ]);
        }

        // 本地订单行（下单时写入，作为兜底快照）
        $local = null;
        try {
            $local = Db::name('orders')->where('order_no', $orderNo)->where('product_code', $type)->find();
        } catch (\Throwable $e) {
            $local = null;
        }

        // 降重订单：openapi 无单条详情接口，按单号 keyword 筛选主站列表精准命中，失败回退本地快照
        $checkUp = null;
        try {
            $api = new ApiClientService();
            $r = $api->get('/openapi/check/orderList', ['status' => 'all', 'keyword' => $orderNo, 'page' => 1, 'page_size' => 5]);
            if (is_array($r) && (int)($r['code'] ?? 0) === 1) {
                foreach (($r['data']['list'] ?? []) as $it) {
                    if (is_array($it) && (string)($it['order_sn'] ?? '') === $orderNo) {
                        $checkUp = $it;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            $checkUp = null;
        }

        if ($type === 'check') {
            if (!$local && !$checkUp) {
                return View::fetch('order_manage/order_detail', [
                    'error' => '本地无该订单记录（历史订单可能未回填）',
                    'returnUrl' => $returnUrl,
                ]);
            }
            $upStatus = $checkUp !== null ? (int)($checkUp['status'] ?? -1) : -1;
            $status = $upStatus >= 0 ? $upStatus : (int)($local['status'] ?? 0);
            $payStatus = (int)($local['pay_status'] ?? 0);
            $orderArr = [
                'order_no' => (string)($local['order_no'] ?? $checkUp['order_sn'] ?? $orderNo),
                'product_code' => $type,
                'product_name' => (string)($local['product_name'] ?? '降重订单'),
                'total_amount' => $local['total_amount'] ?? ($checkUp['agent_amount'] ?? 0),
                'status' => $status,
                'pay_status' => $payStatus,
                'status_text' => $this->mapCheckStatusText($status),
                'pay_status_text' => $this->mapPayStatusText($payStatus),
                'create_time' => (string)($local['create_time'] ?? ''),
                'pay_time' => (string)($local['pay_time'] ?? ''),
            ];
            $pd = json_decode((string)($local['product_data'] ?? ''), true);
            // 主站实时命中时展示实时行 JSON（含最新状态/下载地址），否则回退本地下单快照
            $tr = $checkUp ?? json_decode((string)($local['tokenapi_response'] ?? ''), true);

            return View::fetch('order_manage/order_detail', [
                'order' => $orderArr,
                'productDataPretty' => json_encode(is_array($pd) ? $pd : [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'tokenApiResponsePretty' => json_encode(is_array($tr) ? $tr : [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'returnUrl' => $returnUrl,
            ]);
        }

        // 其它类型：按单号精准拉主站实时详情，失败回退本地快照
        // （autodoc 走主站专用详情接口 /openapi/autodoc/detail，其余走统一 detail）
        $detailData = null;
        try {
            $api = new ApiClientService();
            if ($type === 'autodoc') {
                $r = $api->get('/openapi/autodoc/detail', ['order_sn' => $orderNo]);
                if (is_array($r) && (int)($r['code'] ?? 0) === 1 && is_array($r['data'] ?? null) && !empty($r['data'])) {
                    $detailData = $r['data'];
                }
            } else {
                $detail = $api->get('/openapi/order/detail', ['type' => $type, 'order_sn' => $orderNo]);
                if (is_array($detail) && (int)($detail['code'] ?? 0) === 1 && is_array($detail['data'] ?? null)) {
                    $detailData = $detail['data'];
                }
            }
        } catch (\Throwable $e) {
            $detailData = null;
        }

        if (is_array($detailData) && !empty($detailData)) {
            $payStatus = (int)($detailData['pay_status'] ?? 0);
            // ppt 的状态字段为 ppt_status，其余类型为 gen_status
            $genStatus = (int)($detailData['gen_status'] ?? ($type === 'ppt' ? ($detailData['ppt_status'] ?? 0) : 0));
            $orderArr = [
                'order_no' => (string)($detailData['order_sn'] ?? $orderNo),
                'product_code' => $type,
                'product_name' => $config[$type]['label'] ?? $type,
                'total_amount' => $detailData['amount'] ?? ($local['total_amount'] ?? 0),
                'status' => $genStatus,
                'pay_status' => $payStatus,
                'status_text' => (string)($detailData['gen_status_text'] ?? $this->mapGenStatusText($genStatus)),
                'pay_status_text' => $this->mapPayStatusText($payStatus),
                'create_time' => (string)($detailData['create_time'] ?? ($local['create_time'] ?? '')),
                'pay_time' => (string)($detailData['pay_time'] ?? ($local['pay_time'] ?? '')),
            ];
            $productDataPretty = json_encode($detailData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $tokenApiResponsePretty = $productDataPretty;

            return View::fetch('order_manage/order_detail', [
                'order' => $orderArr,
                'productDataPretty' => $productDataPretty,
                'tokenApiResponsePretty' => $tokenApiResponsePretty,
                'returnUrl' => $returnUrl,
            ]);
        }

        // 主站不可达 → 本地快照兜底
        if ($local) {
            $payStatus = (int)($local['pay_status'] ?? 0);
            $orderArr = [
                'order_no' => (string)($local['order_no'] ?? $orderNo),
                'product_code' => $type,
                'product_name' => (string)($local['product_name'] ?? ($config[$type]['label'] ?? $type)),
                'total_amount' => $local['total_amount'] ?? 0,
                'status' => (int)($local['status'] ?? 0),
                'pay_status' => $payStatus,
                'status_text' => $this->mapGenStatusText((int)($local['status'] ?? 0)),
                'pay_status_text' => $this->mapPayStatusText($payStatus),
                'create_time' => (string)($local['create_time'] ?? ''),
                'pay_time' => (string)($local['pay_time'] ?? ''),
            ];
            $pd = json_decode((string)($local['product_data'] ?? ''), true);
            $tr = json_decode((string)($local['tokenapi_response'] ?? ''), true);

            return View::fetch('order_manage/order_detail', [
                'order' => $orderArr,
                'productDataPretty' => json_encode(is_array($pd) ? $pd : [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'tokenApiResponsePretty' => json_encode(is_array($tr) ? $tr : [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'returnUrl' => $returnUrl,
            ]);
        }

        return View::fetch('order_manage/order_detail', [
            'error' => '获取订单详情失败（主站不可达且本地无快照）',
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * 后台：更新订单状态（可选，用于后续扩展按钮）
     */
    public function updateOrderStatus()
    {
        if (!$this->getAdminId()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = Request::post();
        $orderNo = trim((string)($data['order_no'] ?? ''));
        $status = isset($data['status']) ? (int)$data['status'] : null;
        $payStatus = isset($data['pay_status']) ? (int)$data['pay_status'] : null;

        if ($orderNo === '' || ($status === null && $payStatus === null)) {
            return json(['code' => 0, 'msg' => '参数不完整']);
        }

        $update = [];
        if ($status !== null) $update['status'] = $status;
        if ($payStatus !== null) $update['pay_status'] = $payStatus;

        $res = Db::name('orders')->where('order_no', $orderNo)->update($update);
        if ($res === false) {
            return json(['code' => 0, 'msg' => '更新失败']);
        }

        return json(['code' => 1, 'msg' => '更新成功']);
    }

    /**
     * 失败订单重新处理：openapi 通道未提供重新生成接口，保留路由作明确提示
     */
    public function orderRegenerate()
    {
        if (!$this->getAdminId()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        return json(['code' => 0, 'msg' => '对接通道未提供订单重新处理能力，请在上游平台重新发起']);
    }

    /**
     * 降重订单下载链接：openapi 通道未提供单独的下载接口
     * （降重列表行已直接下发 zipurl 结果包地址，前端从行数据直接下载，本路由仅作兼容提示）
     */
    public function checkDownloadUrl()
    {
        if (!$this->getAdminId()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        return json(['code' => 0, 'msg' => '请直接使用订单列表中的下载按钮（结果包地址随列表下发）']);
    }
}
