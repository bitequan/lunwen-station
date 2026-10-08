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
 * 写作中心（kaiti 开题报告 / rws 任务书 / sx 实习报告 / sxrz 实习日志）openapi 桥接控制器
 *
 * 前端 writing/proposal|task|internship|internshipdiary.vue 走本站 /api/write/* + token Header，
 * 本控制器桥接到主站 /openapi/write·autodoc（Bearer 出站，docking.api_url/api_token）。
 *
 * 下单采用主站「后付费两段式」：
 *   save（create）：先建写作订单（主站、本站均未扣款），本站校验本地余额/主站预充足够，返回 record_id(=order_sn)+price；
 *   pay：先扣本站用户余额（ad_users.balance），再调主站 /openapi/write/pay 扣对接 token 主人在主站的预充余额，
 *        主站预充不足则禁止支付并回滚本站余额。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2WriteAi
{
    /** 各写作类型 → 主站 actiontype / 类目ID / 中文名 */
    private const TYPE_MAP = [
        'kaiti' => ['action' => 'kaiti', 'category' => 3,  'name' => '开题报告'],
        'rws'   => ['action' => 'rws',   'category' => 4,  'name' => '任务书'],
        'sx'    => ['action' => 'sx',    'category' => 16, 'name' => '实习报告'],
        'sxrz'  => ['action' => 'sxrz',  'category' => 5,  'name' => '实习日志'],
    ];

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

    /** 读取请求参数：兼容表单与 JSON body */
    private static $jsonBody = null;

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
        $r  = $this->call('/openapi/user/package', 'GET', []);
        return (float) ($r['data']['user_money'] ?? 0);
    }

    private function floatStr($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    // ==================== 类目详情（价格 + 动态表单） ====================

    public function detail(): \think\response\Json
    {
        $id = (int) Request::get('id', 0);
        if ($id <= 0) {
            return JsonService::fail('类目不能为空', [], 0, 1);
        }
        $r = $this->call('/openapi/write/detail', 'GET', ['id' => $id]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取类目详情失败', [], 0, 1);
        }
        return JsonService::data($r['data'] ?? []);
    }

    // ==================== 大纲生成（ktoutline / rwsoutline） ====================

    public function ktoutline(): \think\response\Json { return $this->outline('kaiti'); }
    public function rwsoutline(): \think\response\Json { return $this->outline('rws'); }

    private function outline(string $type): \think\response\Json
    {
        $typeDef = self::TYPE_MAP[$type] ?? null;
        if (!$typeDef) {
            return JsonService::fail('不支持的写作类型', [], 0, 1);
        }
        $p = $this->body();

        $payload = [
            'actiontype' => $typeDef['action'],
            'title'      => trim((string) ($p['title'] ?? '')),
            'wxnum'      => (int) ($p['wxnum'] ?? 25),
            'wxtype'     => (string) ($p['wxtype'] ?? '全部'),
        ];
        if (isset($p['wxlist'])) {
            $payload['wxlist'] = is_array($p['wxlist']) ? implode("\n", array_map('trim', $p['wxlist'])) : (string) $p['wxlist'];
        }
        $payload['document_urls']   = (string) ($p['documentUrls'] ?? '');
        $payload['assist_content']  = (string) ($p['assistContent'] ?? '');
        $payload['enable_assist']   = (int) (bool) ($p['enableAssist'] ?? !empty($p['assistContent']));
        $payload['enable_file_upload'] = (int) (bool) ($p['enableFileUpload'] ?? !empty($p['documentUrls']));

        $r = $this->call('/openapi/write/outline', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '大纲生成失败，请重试', [], 0, 1);
        }

        $data = $r['data'] ?? [];
        // rws 主站返回裸 outline 数组；统一包装为前端期望的 {outline, wenxianlist}
        if (isset($data['outline'])) {
            $outline = $data['outline'];
            $wx      = $data['wenxianlist'] ?? [];
        } else {
            $outline = is_array($data) && array_is_list($data) ? $data :
                (isset($data['list']) ? $data['list'] : []);
            $wx = [];
        }
        return JsonService::data([
            'outline'     => $outline,
            'wenxianlist' => $wx,
        ]);
    }

    // ==================== 模板列表（kttemplist / rwstemplist / sxtemplist） ====================
    // 数据源：主站 /openapi/write/templist（需主站侧暴露 Actiontemp 公共模板）

    public function kttemplist(): \think\response\Json { return $this->templist('kaiti'); }
    public function rwstemplist(): \think\response\Json { return $this->templist('rws'); }
    public function sxtemplist(): \think\response\Json { return $this->templist('sx'); }

    private function templist(string $type): \think\response\Json
    {
        $typeDef = self::TYPE_MAP[$type] ?? null;
        if (!$typeDef) {
            return JsonService::fail('不支持的写作类型', [], 0, 1);
        }
        $name = trim((string) Request::get('name', ''));
        $page = max(1, (int) Request::get('page', 1));
        $r = $this->call('/openapi/write/templist', 'GET', [
            'actiontype' => $typeDef['action'],
            'keyword'    => $name,
            'page'       => $page,
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取写作模板失败，请稍后重试', [], 0, 1);
        }
        $raw = $r['data'] ?? [];
        if (!is_array($raw)) {
            return JsonService::data([]);
        }
        $rows = isset($raw['list']) ? $raw['list'] : $raw;
        if (!is_array($rows)) {
            $rows = [];
        }
        $list = [];
        foreach ($rows as $it) {
            if (!is_array($it)) {
                continue;
            }
            $tid  = $it['tid'] ?? ($it['id'] ?? 0);
            $list[] = [
                'id'   => $tid,
                'tid'  => $tid,
                'name' => (string) ($it['name'] ?? ''),
                'avt'  => (string) ($it['avt'] ?? ''),
            ];
        }
        return JsonService::data($list);
    }

    // ==================== 参考文档上传签名 ====================

    public function uploadDocument(): \think\response\Json
    {
        $p   = $this->body();
        $ext = strtolower((string) ($p['ext'] ?? 'docx'));
        $r = $this->call('/openapi/autodoc/uploadSignature', 'GET', ['ext' => $ext]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取上传签名失败', [], 0, 1);
        }
        $d = $r['data'] ?? [];
        $contentTypeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt'  => 'text/plain',
        ];
        return JsonService::data([
            'put_url'      => $d['upload_url'] ?? ($d['put_url'] ?? ''),
            'download_url' => $d['file_url'] ?? ($d['download_url'] ?? ''),
            'content_type' => $contentTypeMap[$ext] ?? 'application/octet-stream',
        ]);
    }

    // ==================== 下单（后付费第一步 create） ====================

    public function ktsave(): \think\response\Json  { return $this->create('kaiti'); }
    public function rwssave(): \think\response\Json { return $this->create('rws'); }
    public function sxsave(): \think\response\Json  { return $this->create('sx'); }
    public function sxrzsave(): \think\response\Json { return $this->create('sxrz'); }

    private function create(string $type): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $typeDef = self::TYPE_MAP[$type] ?? null;
        if (!$typeDef) {
            return JsonService::fail('不支持的写作类型', [], 0, 1);
        }
        $p = $this->body();

        // 1) 调主站 create（后付费，只建单不扣款）拿 order_sn + amount
        $payload = [
            'actiontype' => $typeDef['action'],
            'agent_str'  => $this->agentStr($uid),
            'title'      => trim((string) ($p['title'] ?? '')),
        ];
        foreach (['unit', 'unittext', 'company', 'rztype', 'num', 'everynum', 'textnum', 'wxnum', 'wxtype'] as $k) {
            if (array_key_exists($k, $p)) {
                $payload[$k] = $p[$k];
            }
        }
        if (array_key_exists('templateId', $p)) {
            $payload['template_id'] = (int) $p['templateId'];
        }
        if (array_key_exists('documentUrls', $p)) {
            $payload['document_urls'] = (string) $p['documentUrls'];
        }
        if (array_key_exists('assistContent', $p)) {
            $payload['assist_content'] = (string) $p['assistContent'];
        }
        if (array_key_exists('enableAssist', $p)) {
            $payload['enable_assist'] = (int) (bool) $p['enableAssist'];
        }
        if (array_key_exists('enableFileUpload', $p)) {
            $payload['enable_file_upload'] = (int) (bool) $p['enableFileUpload'];
        }
        if (array_key_exists('outlines', $p)) {
            $payload['outlines'] = $p['outlines'];
        }
        if (array_key_exists('wenxianlist', $p) && $p['wenxianlist']) {
            $wl = is_array($p['wenxianlist'])
                ? implode("\n", array_map('trim', $p['wenxianlist']))
                : (string) $p['wenxianlist'];
            $payload['wxlist'] = $wl;
        }

        $r = $this->call('/openapi/write/create', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '下单失败，请重试', [], 0, 1);
        }
        $d       = $r['data'] ?? [];
        $orderSn = (string) ($d['order_sn'] ?? '');
        $amount  = (float) ($d['amount'] ?? 0);
        if ($orderSn === '' || $amount <= 0) {
            return JsonService::fail('下单异常：未获取到有效订单', [], 0, 1);
        }

        // 2) 双余额守卫（下单即禁止）：本站余额 与 主站预充 均须 ≥ 应付金额
        $user = Db::name('users')->where('id', $uid)->find();
        if (!$user) {
            return JsonService::fail('用户不存在', [], 0, 1);
        }
        if ((float) ($user['balance'] ?? 0) + 0.001 < $amount) {
            return JsonService::fail('余额不足，请先充值', [], 0, 1);
        }
        if ($this->mainPrepaid() + 0.001 < $amount) {
            return JsonService::fail('对接方预充值余额不足，暂无法下单，请联系平台充值', [], 0, 1);
        }

        // 3) 本地订单落库（待支付，实际扣费在 pay）
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            $title = $typeDef['name'];
        }
        $this->insertLocalOrder($uid, $orderSn, $payload, $amount, $type, $title);

        return JsonService::data([
            'record_id' => $orderSn,
            'order_sn'  => $orderSn,
            'price'     => $this->floatStr($amount),
            'amount'    => $this->floatStr($amount),
            'actiontype' => $typeDef['action'],
        ]);
    }

    // ==================== 支付（后付费第二步 pay · 双余额扣费） ====================

    public function pay(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p       = $this->body();
        $orderSn = trim((string) ($p['order_sn'] ?? ''));
        if ($orderSn === '') {
            return JsonService::fail('订单号不能为空', [], 0, 1);
        }

        $order = Db::name('orders')->where('order_no', $orderSn)->where('user_id', $uid)->find();
        if (!$order) {
            return JsonService::fail('写作订单不存在', [], 0, 1);
        }
        if ((int) ($order['pay_status'] ?? 0) === 1) {
            // 幂等：已支付直接返回
            return JsonService::data(['order_sn' => $orderSn, 'paid' => true]);
        }
        $amount = (float) ($order['total_amount'] ?? 0);

        // 1) 主站预充守卫
        if ($this->mainPrepaid() + 0.001 < $amount) {
            return JsonService::fail('对接方预充值余额不足，暂无法支付，请联系平台充值', [], 0, 1);
        }

        // 2) 校验并预扣本站用户余额
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

        // 3) 调主站 pay（扣主站预充）
        $r = $this->call('/openapi/write/pay', 'POST', ['order_sn' => $orderSn]);
        if (($r['code'] ?? 0) !== 1) {
            // 主站失败：回滚本站余额
            Db::name('users')->where('id', $uid)->update([
                'balance'     => $before,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            return JsonService::fail($r['msg'] ?? '支付失败，已退回本站余额', [], 0, 1);
        }

        // 4) 本站流水 + 订单置为已支付
        try {
            Db::name('balance_logs')->insert([
                'user_id'           => $uid,
                'change_type'       => 'write_order',
                'change_amount'     => -$amount,
                'bonus_amount'      => 0,
                'total_change_amount' => -$amount,
                'before_balance'    => $before,
                'after_balance'     => $after,
                'related_id'        => $orderSn,
                'related_type'      => 'write',
                'remark'            => '写作服务扣费（' . $orderSn . '）',
                'operator_type'     => 'system',
                'ip_address'        => Request::ip(),
                'create_time'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
        try {
            Db::name('orders')->where('order_no', $orderSn)->update([
                'pay_status' => 1,
                'status'     => 1,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }

        return JsonService::data([
            'order_sn'     => $orderSn,
            'paid'         => true,
            'order_amount' => $this->floatStr($amount),
            'left_money'   => $this->floatStr($after),
        ]);
    }

    // ==================== 本地订单落库 ====================

    private function insertLocalOrder(int $uid, string $orderSn, array $payload, float $amount, string $type, string $title): void
    {
        $exist = Db::name('orders')->where('order_no', $orderSn)->find();
        if ($exist) {
            return;
        }
        Db::name('orders')->insert([
            'order_no'           => $orderSn,
            'product_id'         => 0,
            'product_code'       => $type,
            'product_name'       => ($type === 'sxrz') ? '实习日志' : ($type === 'sx' ? '实习报告' : ($type === 'rws' ? '任务书' : '开题报告')),
            'product_data'       => json_encode([
                'type'       => 'write',
                'actiontype' => $payload['actiontype'] ?? $type,
                'title'      => $title,
                'template_id'=> (int) ($payload['template_id'] ?? 0),
            ], JSON_UNESCAPED_UNICODE),
            'quantity'           => 1,
            'unit_price'         => $amount,
            'total_amount'       => $amount,
            'remark'             => '写作服务（openapi · 后付费）',
            'status'             => 0,
            'pay_status'         => 0,
            'user_id'            => $uid,
            'tokenapi_submitted' => 1,
            'tokenapi_response'  => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip_address'         => Request::ip(),
            'user_agent'         => Request::header('user-agent', ''),
            'create_time'        => date('Y-m-d H:i:s'),
            'update_time'        => date('Y-m-d H:i:s'),
        ]);
    }
}