<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use think\facade\Request;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * 自动排版（格式重排，autodoc.vue）openapi 桥接控制器
 *
 * 前端 autodoc.vue 走本站 /api/autodoc/* + token Header，本控制器桥接到主站 /openapi/autodoc
 * （Bearer 出站，docking.api_url/api_token）。
 *
 * 与 PPT 一致采用"后付费 + 双余额"模型：
 *   下单（generate）只建单不扣费，订单落库 pay_status=0；
 *   正式下载（downloadDoc）时才扣费：先校验主站预充充足 → 预扣本站用户余额 →
 *   调主站下载（首次扣主站预充）→ 主站失败回滚本站余额；已支付订单重复下载免费。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2AutodocAi
{
    /** 当前登录用户 ID；未登录返回 0 */
    private function uid(): int
    {
        return UserTokenService::getUserId(UserTokenService::readRequestToken());
    }

    /** 本站用户对接标识（agent_str = 管理员配置前缀 + 用户ID，如 adweb_12），主站按其做下游用户隔离归属 */
    private function agentStr(int $uid): string
    {
        return (string) AgentIdHelper::build($uid);
    }

    private static $jsonBody = null;

    /** 读取请求参数：兼容表单与 JSON body */
    private function body(): array
    {
        $post = Request::post();
        if (!is_array($post)) {
            $post = [];
        }
        if (self::$jsonBody === null) {
            self::$jsonBody = [];
            $raw = (string) request()->getContent();
            if ($raw === '') {
                $raw = (string) file_get_contents('php://input');
            }
            $raw = trim((string) $raw, "\xEF\xBB\xBF \t\r\n");
            if ($raw !== '' && strpos($raw, '{') === 0) {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    self::$jsonBody = $dec;
                }
            }
        }
        return array_merge($post, self::$jsonBody);
    }

    /** 出站请求主站 openapi */
    private function call(string $path, string $method = 'GET', array $params = []): array
    {
        $c = new ApiClientService();
        return $method === 'GET' ? $c->get($path, $params) : $c->post($path, $params);
    }

    /** 主站预充余额（对接 token 主人在主站的 user_money） */
    private function mainPrepaid(): float
    {
        $r = $this->call('/openapi/user/package', 'GET', []);
        return (float) ($r['data']['user_money'] ?? 0);
    }

    private function floatStr($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    /** 本站商品加价（元/篇）：ad_products.code=autodoc 的 markup */
    private function productMarkup(): float
    {
        try {
            return (float) Db::name('products')->where('code', 'autodoc')->value('markup', 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    // ==================== 模板列表 ====================

    /** 排版模板列表：tab=public 走主站公共池；tab=private 走 /openapi/template/list + agent_str（TemplistAuto，按本站用户隔离；与 AI 论文私有模板同池） */
    public function templateList(): \think\response\Json
    {
        $uid = $this->uid();
        $p   = $this->body();
        $tab = (string) ($p['tab'] ?? 'public') === 'private' ? 'private' : 'public';

        if ($tab === 'private') {
            $r = $this->call('/openapi/template/list', 'GET', [
                'agent_str' => $this->agentStr($uid),
                'keyword'   => (string) ($p['keyword'] ?? ''),
                'page'      => 1,
                'page_size' => 100,
            ]);
            if (($r['code'] ?? 0) !== 1) {
                return JsonService::fail($r['msg'] ?? '获取私有模板失败', [], 0, 1);
            }
            $d   = $r['data'] ?? [];
            $out = [];
            foreach ((array) ($d['list'] ?? []) as $it) {
                if (!is_array($it)) {
                    continue;
                }
                // 私有模板以自增 id 引用（无 template_uid），补齐空 degree/profession/years/status 兼容公共卡片字段与前端过滤
                $out[] = [
                    'id'          => (int) ($it['id'] ?? 0),
                    'name'        => (string) ($it['name'] ?? ''),
                    'avt'         => (string) ($it['avt'] ?? ''),
                    'source_type' => (int) ($it['source_type'] ?? 0),
                    'create_time' => (string) ($it['create_time'] ?? ''),
                    'status'      => 1,
                    'degree'      => '',
                    'profession'  => '',
                    'years'       => '',
                ];
            }
            return JsonService::data([
                'list'     => $out,
                'db_total' => (int) ($d['total'] ?? count($out)),
                'total'    => (int) ($d['total'] ?? count($out)),
            ]);
        }

        $r = $this->call('/openapi/autodoc/templateList', 'GET', [
            'keyword' => (string) ($p['keyword'] ?? ''),
            'tab'     => 'public',
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取模板失败', [], 0, 1);
        }
        $d    = $r['data'] ?? [];
        $list = $d['list'] ?? [];
        $out  = [];
        foreach ($list as $it) {
            if (!is_array($it)) {
                continue;
            }
            // 主站隐藏自增 id/template_uid，只暴露 template_no；此处回填 template_uid 与 id 供前端使用
            $no = (string) ($it['template_no'] ?? '');
            $out[] = array_merge($it, [
                'template_uid' => $no,
                'id'           => $no,
            ]);
        }
        return JsonService::data([
            'list'      => $out,
            'db_total'  => $d['db_total'] ?? $d['total'] ?? count($out),
            'total'     => $d['total'] ?? count($out),
        ]);
    }

    // ==================== 模板预览（conjson 主站不暴露，仅前端弹层用，返回空配置） ====================

    public function templateConfig(): \think\response\Json
    {
        return JsonService::data(['config' => []]);
    }

    // ==================== 上传签名 ====================

    /** 获取 OSS 直传签名（前端用 sig.put_url / sig.content_type / sig.download_url） */
    public function getUploadSignature(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p  = $this->body();
        $r = $this->call('/openapi/autodoc/uploadSignature', 'GET', [
            'ext' => (string) ($p['ext'] ?? 'docx'),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取上传签名失败', [], 0, 1);
        }
        $d    = $r['data'] ?? [];
        $hdr  = $d['upload_headers'] ?? [];
        $ctype = 'application/octet-stream';
        if (is_array($hdr)) {
            if (isset($hdr['Content-Type'])) {
                $ctype = (string) $hdr['Content-Type'];
            } else {
                foreach ($hdr as $item) {
                    if (is_array($item) && isset($item['Content-Type'])) {
                        $ctype = (string) $item['Content-Type'];
                        break;
                    }
                }
            }
        }
        return JsonService::data([
            'put_url'      => (string) ($d['upload_url'] ?? ''),
            'upload_url'   => (string) ($d['upload_url'] ?? ''),
            'content_type' => $ctype,
            'download_url' => (string) ($d['file_url'] ?? ''),
            'expires_at'   => (int) ($d['expires_at'] ?? 0),
        ]);
    }

    // ==================== 文档解析（走主站 openapi parseDoc，与主站前端解析同源） ====================

    /** 解析入口：调主站解析返回扁平化结构；解析失败回退透传模式（不阻塞下单流程） */
    public function uploadDoc(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p   = $this->body();
        $url = trim((string) ($p['file_url'] ?? ''));
        if ($url === '') {
            return JsonService::fail('文件地址不能为空', [], 0, 1);
        }
        $fallback = [
            'file_url'           => $url,
            'format_payload_url' => $url,
            'summary'            => [],
        ];
        $r = $this->call('/openapi/autodoc/parseDoc', 'POST', ['file_url' => $url]);
        if (($r['code'] ?? 0) !== 1 || !is_array($r['data'] ?? null)) {
            return JsonService::data($fallback);
        }
        $d = $r['data'];
        return JsonService::data([
            'file_url'           => $url,
            'format_payload_url' => '',
            'title'              => (string) ($d['title'] ?? ''),
            'flattened_content'  => $d['flattened_content'] ?? [],
            'summary'            => $d['summary'] ?? [],
            'meta'               => $d['meta'] ?? [],
        ]);
    }

    // ==================== 生成/下单（后付费：只建单不扣费，下载时才扣） ====================

    public function generate(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();

        $fileUrl = trim((string) ($p['file_url'] ?? ''));
        if ($fileUrl === '') {
            $fileUrl = trim((string) ($p['format_payload_url'] ?? ''));
        }
        $fileName = trim((string) ($p['fileName'] ?? ''));
        if ($fileUrl === '' || $fileName === '') {
            return JsonService::fail('文件地址和文件名不能为空', [], 0, 1);
        }
        $mode = (string) ($p['mode'] ?? 'retype');
        if ($mode !== 'polish') {
            $mode = 'retype';
        }
        $templateNo = trim((string) ($p['template_no'] ?? ''));
        $templateType = (string) ($p['template_type'] ?? 'public') === 'private' ? 'private' : 'public';

        $payload = [
            'file_url'      => $fileUrl,
            'file_name'     => $fileName,
            'mode'          => $mode,
            'agent_str'     => $this->agentStr($uid),
        ];
        if ($templateType === 'private') {
            // 私有模板（TemplistAuto 自增 id，本站用户在主站的独立模板）：主站按 token 名下 user_id 校验归属
            $templateId = (int) ($p['templateId'] ?? ($p['template_id'] ?? 0));
            if ($templateId <= 0) {
                return JsonService::fail('私有模板不存在，请重新选择', [], 0, 1);
            }
            $payload['template_type'] = 'private';
            $payload['template_id']   = $templateId;
        } elseif ($templateNo !== '') {
            $payload['template_no'] = $templateNo;
            $payload['template_type'] = 'public';
        }

        // 计价（本站用户应付 = 进价 + 后台加价；用于下单金额展示 & 下载时扣费核对）
        $amount = 0.0;
        $rp = $this->call('/openapi/autodoc/price', 'GET', []);
        if (($rp['code'] ?? 0) === 1) {
            $cost = (float) ($rp['data']['cost_price'] ?? 0);
            $base = (float) ($rp['data']['base_price'] ?? 0);
            $in   = $cost > 0 ? $cost : $base;
            $amount = $in > 0 ? round($in + $this->productMarkup(), 2) : 0.0;
        }
        if ($amount <= 0) {
            return JsonService::fail('计价失败，请稍后重试', [], 0, 1);
        }

        // 调主站建单（后付费，仅建单不扣费）
        $r = $this->call('/openapi/autodoc/create', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '下单失败，请稍后重试', [], 0, 1);
        }
        $d = $r['data'] ?? [];
        $orderSn = (string) ($d['order_sn'] ?? '');
        if ($orderSn === '') {
            return JsonService::fail('下单失败：未获取到订单号', [], 0, 1);
        }

        $this->insertLocalOrder($uid, $orderSn, $payload, $amount, $d);

        return JsonService::data([
            'order_sn'    => $orderSn,
            'amount'      => round($amount, 2),
            'preview_url' => (string) ($d['preview_url'] ?? ''),
            'doc_url'     => '',
            'file_size'   => '',
            'pay_mode'    => 'postpaid',
            'pay_status'  => 0,
        ]);
    }

    // ==================== 本地订单落库 ====================

    private function insertLocalOrder(int $uid, string $orderSn, array $payload, float $amount, array $d): void
    {
        $exist = Db::name('orders')->where('order_no', $orderSn)->find();
        if ($exist) {
            return;
        }
        Db::name('orders')->insert([
            'order_no'           => $orderSn,
            'product_id'         => 0,
            'product_code'       => 'autodoc',
            'product_name'       => '自动排版',
            'product_data'       => json_encode([
                'type'      => 'autodoc',
                'title'     => (string) ($payload['file_name'] ?? ''),
                'mode'      => (string) ($payload['mode'] ?? 'retype'),
                'template_no' => (string) ($payload['template_no'] ?? ''),
            ], JSON_UNESCAPED_UNICODE),
            'quantity'           => 1,
            'unit_price'         => $amount,
            'total_amount'       => $amount,
            'remark'             => '自动排版（openapi · 后付费）',
            'status'             => 0,
            'pay_status'         => 0,
            'user_id'            => $uid,
            'tokenapi_submitted' => 1,
            'tokenapi_response'  => json_encode($d, JSON_UNESCAPED_UNICODE),
            'ip_address'         => Request::ip(),
            'user_agent'         => Request::header('user-agent', ''),
            'create_time'        => date('Y-m-d H:i:s'),
            'update_time'        => date('Y-m-d H:i:s'),
        ]);
    }

    // ==================== 下载（后付费：首次下载双余额扣费，下载后可重复免费） ====================

    public function downloadDoc(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $orderSn = trim((string) ($this->body()['order_sn'] ?? ''));
        if ($orderSn === '') {
            return JsonService::fail('订单号不能为空', [], 0, 1);
        }

        $order = Db::name('orders')->where('order_no', $orderSn)->where('user_id', $uid)->find();
        if (!$order) {
            return JsonService::fail('排版订单不存在', [], 0, 1);
        }
        $amount = (float) ($order['total_amount'] ?? 0);

        // 幂等：已支付（已扣费下载过）直接透传主站下载（不再次扣费）
        $alreadyPaid = (int) ($order['pay_status'] ?? 0) === 1;

        // 1) 主站预充 guard（不足禁止下载）：只须覆盖主站成本（进价 = 售价 - 加价），加价部分不占主站预充
        $guardCost = max(0.0, $amount - $this->productMarkup());
        if ($this->mainPrepaid() + 0.001 < $guardCost) {
            return JsonService::fail('对接方预充值余额不足，暂无法下载，请联系平台充值', [], 0, 1);
        }

        // 2) 未支付时校验并预扣本站用户余额
        $before = null;
        $after  = null;
        if (!$alreadyPaid) {
            $user = Db::name('users')->where('id', $uid)->find();
            if (!$user) {
                return JsonService::fail('用户不存在', [], 0, 1);
            }
            $bal = (float) ($user['balance'] ?? 0);
            if ($bal + 0.001 < $amount) {
                return JsonService::fail('余额不足，请先充值', [], 0, 1);
            }
            $before = $bal;
            $after  = round($bal - $amount, 2);
            Db::name('users')->where('id', $uid)->update([
                'balance'     => $after,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 3) 调主站下载（首次扣主站预充）
        $r = $this->call('/openapi/autodoc/download', 'POST', ['order_sn' => $orderSn]);
        if (($r['code'] ?? 0) !== 1) {
            // 主站失败：回滚本站余额（仅当本次预扣过）
            if (!$alreadyPaid && $before !== null) {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $before,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            return JsonService::fail($r['msg'] ?? '下载失败，已退回本站余额', [], 0, 1);
        }

        // 4) 本站流水 + 订单置为已支付
        if (!$alreadyPaid && $before !== null) {
            try {
                Db::name('balance_logs')->insert([
                    'user_id'           => $uid,
                    'change_type'       => 'autodoc_download',
                    'change_amount'     => -$amount,
                    'bonus_amount'      => 0,
                    'total_change_amount' => -$amount,
                    'before_balance'    => $before,
                    'after_balance'     => $after,
                    'related_id'        => $orderSn,
                    'related_type'      => 'autodoc',
                    'remark'            => '自动排版下载扣费（' . $orderSn . '）',
                    'operator_type'     => 'system',
                    'ip_address'        => Request::ip(),
                    'create_time'       => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
            }
            try {
                Db::name('orders')->where('order_no', $orderSn)->update([
                    'pay_status' => 1,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
            }
        }

        $docUrl = (string) ($r['data']['doc_url'] ?? '');
        // 主站付费后可能仍未就绪，回写本地供后续直接下载
        if ($docUrl !== '') {
            try {
                $local = Db::name('orders')->where('order_no', $orderSn)->find();
                if ($local) {
                    $pd = json_decode((string) $local['product_data'], true);
                    if (!is_array($pd)) {
                        $pd = [];
                    }
                    $pd['doc_url'] = $docUrl;
                    Db::name('orders')->where('order_no', $orderSn)->update([
                        'product_data' => json_encode($pd, JSON_UNESCAPED_UNICODE),
                        'update_time'  => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) {
            }
        }

        return JsonService::data([
            'url'        => $docUrl,
            'charged'    => (bool) ($r['data']['charged'] ?? (!$alreadyPaid)),
            'pay_status' => 1,
            'order_sn'   => $orderSn,
        ]);
    }
}