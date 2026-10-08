<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * 用户端订单查询接口（api-doc「订单查询」组）
 *
 * 接口路径（POST，需请求头 token）：
 *   /api/order/rechargeList    充值订单列表
 *   /api/order/paperList       论文订单列表
 *   /api/order/pptList         PPT 订单列表
 *   /api/order/writeList       写作订单列表（开题/任务书/实习/实习日志）
 *   /api/order/autodocList     自动排版订单列表
 *
 * 数据源：ad_orders（业务订单，product_data 存明细）、ad_recharge_orders（充值订单）
 * 生成状态 gen_status：0 待生成 / 1 生成中 / 2 已完成 / 3 失败（依据 pay_status 与本库可判定信息映射）
 */
class V2Order
{
    private function uid(): int
    {
        return UserTokenService::getUserId(UserTokenService::readRequestToken());
    }

    /**
     * 产品 code → 业务订单分组
     * 注意：落库口径 product_code = 业务类型（V2PaperAi::insertLocalOrder 写 'paper'），
     * gjlw 仅为计价商品 code（ad_products.code），历史数据可能存在，两值都需匹配。
     */
    private function codeGroups(): array
    {
        return [
            'paper'   => ['paper', 'gjlw'],
            'ppt'     => ['ppt'],
            'write'   => ['kaiti', 'rws', 'sx', 'sxrz'],
            'autodoc' => ['autodoc'],
        ];
    }

    public function rechargeList(): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        return $this->rechargeListPage($userId);
    }

    public function paperList(): \think\response\Json   { return $this->orderListPage('paper'); }
    public function pptList(): \think\response\Json     { return $this->orderListPage('ppt'); }
    public function writeList(): \think\response\Json   { return $this->orderListPage('write'); }
    public function autodocList(): \think\response\Json { return $this->orderListPage('autodoc'); }

    /**
     * 充值订单列表（ad_recharge_orders）
     */
    private function rechargeListPage(int $userId): \think\response\Json
    {
        $status  = (string) input('status', 'all');
        $keyword = trim((string) input('keyword', ''));
        $page    = max(1, (int) input('page', 1));
        $size    = min(50, max(1, (int) input('page_size', 10)));

        $query = Db::name('recharge_orders')->where('user_id', $userId);
        if ($status === 'paid') {
            $query->where('pay_status', 1);
        } elseif ($status === 'pending') {
            $query->where('pay_status', 0);
        }
        if ($keyword !== '') {
            $query->whereLike('order_no', "%{$keyword}%");
        }

        $total = (clone $query)->count();
        $rows  = (clone $query)->order('id', 'desc')->page($page, $size)->select()->toArray();

        $list = [];
        foreach ($rows as $row) {
            $paid  = (int) ($row['pay_status'] ?? 0) === 1;
            $list[] = [
                'order_sn'        => (string) ($row['order_no'] ?? ''),
                'pay_way_text'    => (string) ($row['payment_method_name'] ?? ''),
                'amount'          => round((float) ($row['amount'] ?? 0), 2),
                'status_class'    => $paid ? 'completed' : 'pending',
                'gen_status_text' => $paid ? '已完成' : '待支付',
                'pay_status'      => $paid ? 1 : 0,
                'create_time'     => $this->fmtTime($row['create_time'] ?? ''),
                'pay_time'        => $this->fmtTime($row['pay_time'] ?? ''),
            ];
        }

        return JsonService::data([
            'list'        => $list,
            'total'       => $total,
            'page_total'  => max(1, (int) ceil($total / max(1, $size))),
            'total_pages' => max(1, (int) ceil($total / max(1, $size))),
        ]);
    }

    /**
     * 业务订单列表（ad_orders），按产品分组
     */
    private function orderListPage(string $group): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        $codes   = $this->codeGroups()[$group] ?? [];
        $status  = (string) input('status', 'all');
        $keyword = trim((string) input('keyword', ''));
        $page    = max(1, (int) input('page', 1));
        $size    = min(50, max(1, (int) input('page_size', 10)));

        $query = Db::name('orders')->where('user_id', $userId);
        if ($codes) {
            $query->whereIn('product_code', $codes);
        }
        if ($status === 'paid') {
            $query->where('pay_status', 1);
        } elseif ($status === 'pending') {
            $query->where('pay_status', 0);
        }
        if ($keyword !== '') {
            $query->whereLike('order_no', "%{$keyword}%");
        }

        $total = (clone $query)->count();
        $rows  = (clone $query)->order('id', 'desc')->page($page, $size)->select()->toArray();

        // 后付费业务（PPT / 自动排版）：主站生成状态与预览链接以主站为准，同步覆盖本地推断
        $syncMap = $this->syncMainDetail($group, $rows, $userId, $page, $size);

        $list = [];
        foreach ($rows as $row) {
            $data  = $this->decodeProductData($row['product_data'] ?? '');
            $name  = (string) (($data['title'] ?? '') ?: ($row['product_name'] ?? ''));
            $paid  = (int) ($row['pay_status'] ?? 0) === 1;
            $synced = $syncMap[$row['order_no'] ?? ''] ?? null;
            $docUrl = (string) ($data['doc_url'] ?? '');
            if ($synced) {
                $paid      = ((int) ($synced['pay_status'] ?? 0)) === 1;
                $genStatus = (int) ($synced['gen_status'] ?? 0);
                $preview   = (string) ($synced['preview_url'] ?? '');
                // 主站返回下载链接时覆盖本地（论文完成后本库无 doc_url，以主站为准）
                if (!empty($synced['doc_url'])) {
                    $docUrl = (string) $synced['doc_url'];
                }
            } else {
                // 本库无独立"生成完成"标记：已支付且有下载地址视为完成，否则待生成
                $genStatus = ($paid && !empty($data['doc_url'])) ? 2 : ($paid ? 1 : 0);
                $preview   = (string) ($data['preview_url'] ?? '');
            }
            $statusMap = [
                0 => ['pending',    '待生成'],
                1 => ['processing', '生成中'],
                2 => ['completed',  '已完成'],
                3 => ['failed',     '失败'],
            ];
            [$sClass, $sText] = $statusMap[$genStatus] ?? $statusMap[0];

            $list[] = [
                'order_sn'        => (string) ($row['order_no'] ?? ''),
                'product_name'    => (string) ($row['product_name'] ?? ''),
                'title'           => $name,
                'model_name'      => (string) (($data['model'] ?? '') ?: ($data['model_name'] ?? '-')),
                'word_count'      => (int) ($data['word_count'] ?? 0),
                'amount'          => round((float) ($row['total_amount'] ?? 0), 2),
                'gen_status'      => $genStatus,
                'gen_status_text' => $sText,
                'status_class'    => $sClass,
                'pay_status'      => $paid ? 1 : 0,
                'preview_url'     => $preview,
                'create_time'     => $this->fmtTime($row['create_time'] ?? ''),
                'doc_url'         => $docUrl,
            ];
        }

        return JsonService::data([
            'list'       => $list,
            'total'      => $total,
            'page_total' => max(1, (int) ceil($total / max(1, $size))),
            'total_pages'=> max(1, (int) ceil($total / max(1, $size))),
        ]);
    }

    /**
     * 同步主站订单（AI论文 / PPT / 自动排版）的生成状态与下载/预览链接
     * 单次批量调用 GET /openapi/order/list?type=...&agent_str=...&page=&page_size=，
     * 按 order_sn 建立映射，仅覆盖主站返回的订单，未命中回退本地推断。
     *
     * 历史教训：旧实现对本页每行订单逐个调 /openapi/order/detail（主站单次 TTFB≈0.7s），
     * 一页 50 条 = 35s 串行上游调用，触发 max_execution_time 致命错误 → 接口 500。
     * @return array order_no => ['pay_status','gen_status','preview_url','doc_url']
     */
    private function syncMainDetail(string $group, array $rows, int $userId, int $page, int $size): array
    {
        if (!in_array($group, ['paper', 'ppt', 'autodoc'], true) || !$rows) {
            return [];
        }
        try {
            $c = new ApiClientService();
        } catch (\Throwable $e) {
            return [];
        }
        try {
            $r = $c->get('/openapi/order/list', [
                'type'      => $group,
                'agent_str' => (string) AgentIdHelper::build($userId),
                'page'      => $page,
                'page_size' => $size,
            ]);
        } catch (\Throwable $e) {
            return [];
        }
        $map = [];
        if (($r['code'] ?? 0) === 1 && is_array($r['data']['list'] ?? null)) {
            foreach ($r['data']['list'] as $item) {
                $sn = (string) ($item['order_sn'] ?? '');
                if ($sn === '') {
                    continue;
                }
                $map[$sn] = [
                    'pay_status'  => (int) ($item['pay_status'] ?? 0),
                    'gen_status'  => (int) ($item['gen_status'] ?? 0),
                    'preview_url' => (string) ($item['preview_url'] ?? ''),
                    'doc_url'     => (string) ($item['doc_url'] ?? ''),
                ];
            }
        }
        // 主列表未命中的行回退逐单 detail：历史订单的 agent_str 格式不一（裸uid/空串），
        // agent_str 过滤列表查不到；detail 仅 paper|ppt 支持（autodoc 无历史单，跳过），
        // 上限 10 防御性控制串行耗时
        if ($group !== 'autodoc') {
            $missed = [];
            foreach ($rows as $row) {
                $sn = (string) ($row['order_no'] ?? '');
                if ($sn !== '' && !isset($map[$sn])) {
                    $missed[] = $sn;
                }
            }
            foreach (array_slice($missed, 0, 10) as $sn) {
                try {
                    $d = $c->get('/openapi/order/detail', ['type' => $group, 'order_sn' => $sn]);
                } catch (\Throwable $e) {
                    continue;
                }
                if (($d['code'] ?? 0) !== 1 || !is_array($d['data'] ?? null)) {
                    continue;
                }
                $dd = $d['data'];
                $map[$sn] = [
                    'pay_status'  => (int) ($dd['pay_status'] ?? 0),
                    'gen_status'  => $group === 'ppt' ? (int) ($dd['ppt_status'] ?? 0) : (int) ($dd['gen_status'] ?? 0),
                    'preview_url' => (string) ($dd['preview_url'] ?? ''),
                    'doc_url'     => (string) ($group === 'ppt' ? ($dd['ppt_url'] ?? '') : ($dd['doc_url'] ?? '')),
                ];
            }
        }
        return $map;
    }

    /**
     * 解析 product_data JSON（兼容非对象/空）
     */
    private function decodeProductData($raw): array
    {
        if (is_array($raw)) return $raw;
        $raw = (string) $raw;
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function fmtTime($t): string
    {
        if (!$t) return '';
        $c = (string) $t;
        if (ctype_digit($c)) return date('Y-m-d H:i:s', (int) $c);
        return date('Y-m-d H:i:s', strtotime($c));
    }
}