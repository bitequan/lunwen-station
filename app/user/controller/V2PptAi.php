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
 * AI PPT（aippt.vue）openapi 桥接控制器
 *
 * 前端 aippt.vue 走本站 /api/ppt/* + token Header，本控制器桥接到主站 /openapi/ppt
 * （Bearer 出站，docking.api_url/api_token）。
 *
 * 与写作/高级论文一致采用"双余额"模型：
 *   普通版 create / 高级版 createAdvanced：本站用户下单 → 立即扣本站余额（ad_users.balance），
 *   主站大前提仅建单不扣费（PPT 后付费，主站预充在下单后下载时扣除）；
 *   但本站仍需在主站预充（/openapi/user/package.user_money）充足时才放行下单，否则禁止。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2PptAi
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
        $r = $this->call('/openapi/user/package', 'GET', []);
        return (float) ($r['data']['user_money'] ?? 0);
    }

    private function floatStr($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    // ==================== 价格 ====================

    /** 普通版价格 */
    public function getPriceConfig(): \think\response\Json
    {
        $uid   = $this->uid();
        $st    = $this->priceOf(false, $uid);
        $adv   = $this->priceOf(true, $uid);
        return JsonService::data([
            'standard' => $st,
            'advanced' => $adv,
        ]);
    }

    private function priceOf(bool $isAdvanced, int $uid): array
    {
        $r = $this->call('/openapi/ppt/price', 'GET', ['is_advanced' => $isAdvanced ? 1 : 0]);
        $d = ($r['code'] ?? 0) === 1 ? ($r['data'] ?? []) : [];
        // price 与实扣 quoteFromMain 同源（进价+加价）；上游不可用时为 0，前端展示「--」并禁止下单
        $in    = (float) ($d['cost_price'] ?? 0) > 0 ? (float) $d['cost_price'] : (float) ($d['base_price'] ?? 0);
        $price = $in > 0 ? round($in + $this->productMarkup(), 2) : 0.0;
        return [
            'price'      => $this->floatStr($price),
            'model_name' => $d['model_name'] ?? ($isAdvanced ? 'PPT高级模型' : 'PPT默认模型'),
            'model_tag'  => $d['model_tag'] ?? '',
            'version'    => $isAdvanced ? 'advanced' : 'standard',
            'is_advanced' => $isAdvanced ? 1 : 0,
            'is_per_unit'=> $d['is_per_unit'] ?? true,
        ];
    }

    // ==================== 分类 ====================

    /** 普通版分类（前端读 res.data.categories） */
    public function templateCategories(): \think\response\Json
    {
        return JsonService::data(['categories' => $this->fetchCategories()]);
    }

    /** 高级版分类（前端读 res.data 数组） */
    public function getTemplateCategories(): \think\response\Json
    {
        return JsonService::data($this->fetchCategories());
    }

    private function fetchCategories(): array
    {
        $r = $this->call('/openapi/ppt/templateCategories', 'GET', []);
        if (($r['code'] ?? 0) !== 1) {
            return [];
        }
        $cats = $r['data']['categories'] ?? [];
        $list = [];
        foreach ($cats as $c) {
            if (!is_array($c)) {
                $list[] = ['name' => (string)$c, 'id' => 0];
                continue;
            }
            $list[] = [
                'id'      => (int) ($c['id'] ?? 0),
                'name'    => (string) ($c['category_name'] ?? ''),
                'code'    => (string) ($c['category_code'] ?? ''),
            ];
        }
        return $list;
    }

    // ==================== 大纲（同步 + 降级） ====================

    public function generatePptOutline(): \think\response\Json
    {
        return $this->outline(false);
    }

    public function generateAdvancedOutline(): \think\response\Json
    {
        return $this->outline(true);
    }

    /** 普通版大纲 SSE 流式 */
    public function generatePptOutlineStream()
    {
        return $this->streamOutline(false);
    }

    /** 高级版大纲 SSE 流式 */
    public function generateAdvancedOutlineStream()
    {
        return $this->streamOutline(true);
    }

    private function streamOutline(bool $advanced)
    {
        $uid = $this->uid();
        if (!$uid) {
            return $this->sseError('登录已过期，请重新登录', -1);
        }
        $p     = $this->body();
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return $this->sseError('PPT 标题不能为空', 0);
        }
        // 真流式：逐帧透传主站 SSE 端点（高级版仅 title，普通版含章节/小节参数）
        if ($advanced) {
            $path    = '/openapi/ppt/generateAdvancedOutlineStream';
            $payload = ['title' => $title];
        } else {
            $path    = '/openapi/ppt/generateOutlineStream';
            $payload = [
                'title'         => $title,
                'chapter_count' => (int) ($p['chapterCount'] ?? ($p['chapter_count'] ?? 6)),
                'section_range' => (string) ($p['sectionRange'] ?? ($p['section_range'] ?? '3-6')),
            ];
        }
        (new ApiClientService())->passthroughSse($path, $payload);
    }

    private function sseError(string $msg, int $code = 0): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function outline(bool $advanced): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p     = $this->body();
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return JsonService::fail('PPT 标题不能为空', [], 0, 1);
        }
        $r = $this->call('/openapi/ppt/generateOutline', 'POST', [
            'title'         => $title,
            'chapter_count' => (int) ($p['chapterCount'] ?? ($p['chapter_count'] ?? 6)),
            'section_range' => (string) ($p['sectionRange'] ?? ($p['section_range'] ?? '3-6')),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '大纲生成失败，请重试', [], 0, 1);
        }
        return JsonService::data([
            'outline' => $r['data']['outline'] ?? '',
            'length'  => (int) ($r['data']['length'] ?? 0),
        ]);
    }

    // ==================== 模板列表 ====================

    public function templateList(): \think\response\Json
    {
        $uid = $this->uid();
        $p   = $this->body();
        $r = $this->call('/openapi/ppt/templateList', 'GET', [
            'category_id' => (int) ($p['category_id'] ?? 0),
            'page'        => max(1, (int) ($p['page'] ?? 1)),
            'page_size'   => max(1, min(50, (int) ($p['limit'] ?? 20))),
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
            $out[] = [
                'id'            => $it['id'] ?? 0,
                'tpl_uid'       => $it['tpl_uid'] ?? '',
                'preview_url'   => $it['preview_url'] ?? '',
                'preview'       => $it['preview_url'] ?? '',
                'category'      => $it['category'] ?? '',
                'style'         => $it['style'] ?? '',
                'template_name' => $it['template_name'] ?? '',
            ];
        }
        return JsonService::data([
            'list'  => $out,
            'count' => $d['total'] ?? (int)($d['total_pages'] > 0 ? 0 : 0),
            'total' => $d['total'] ?? 0,
            'page'  => $d['page'] ?? 1,
        ]);
    }

    /** 高级版模板（前端用 templates/total/pages 字段；透传主站专用接口，服务端按分类过滤，total 准确） */
    public function getAdvancedTemplates(): \think\response\Json
    {
        $p = $this->body();
        // 「全部」非真实分类名（主站按 category_use_name 精确匹配），归一为空=不过滤
        $category = trim((string) ($p['category'] ?? ''));
        if ($category === '全部') {
            $category = '';
        }
        $r = $this->call('/openapi/ppt/advancedTemplateList', 'GET', [
            'category'  => $category,
            'keyword'   => (string) ($p['keyword'] ?? ''),
            'page'      => max(1, (int) ($p['page'] ?? 1)),
            'page_size' => max(1, min(50, (int) ($p['page_size'] ?? 20))),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取模板失败', [], 0, 1);
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        return JsonService::data([
            'templates' => is_array($d['templates'] ?? null) ? $d['templates'] : [],
            'total'     => (int) ($d['total'] ?? 0),
            'pages'     => max(1, (int) ($d['pages'] ?? 1)),
        ]);
    }

    /** 高级版模板分类（主站 category_use_name 聚合，含各分类模板数量） */
    public function getAdvancedTemplateCategories(): \think\response\Json
    {
        $r = $this->call('/openapi/ppt/advancedTemplateCategories', 'GET', []);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取分类失败', [], 0, 1);
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        return JsonService::data(is_array($d['categories'] ?? null) ? $d['categories'] : []);
    }

    // ==================== 普通版下单（后付费：只建单不扣费，下载时才扣） ====================

    public function createPptOrder(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();
        $title = trim((string) ($p['title'] ?? ''));
        $outline = (string) ($p['outline_content'] ?? '');
        if ($title === '' || $outline === '') {
            return JsonService::fail('标题和大纲内容不能为空', [], 0, 1);
        }

        // 1) 计价（用于下单金额展示 & 后续下载时扣费核对）
        $price = $this->quoteFromMain(false, $uid);
        if ($price <= 0) {
            return JsonService::fail('计价金额异常', [], 0, 1);
        }

        // 2) 调主站建单（后付费，仅建单不扣费）
        $payload = [
            'agent_str'       => $this->agentStr($uid),
            'title'           => $title,
            'outline_content' => $outline,
            'author'          => (string) ($p['author'] ?? ''),
            'chapter_count'   => (int) ($p['chapter_count'] ?? 6),
            'section_range'   => (string) ($p['section_range'] ?? '3-6'),
            'template_id'     => (int) ($p['template_id'] ?? 0),
        ];
        $r = $this->call('/openapi/ppt/create', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '创建订单失败，请重试', [], 0, 1);
        }
        $orderSn = (string) ($r['data']['order_sn'] ?? '');
        if ($orderSn === '') {
            return JsonService::fail('创建订单失败：未获取到订单号', [], 0, 1);
        }

        // 3) 订单落库（后付费，未支付，下载时再双余额扣费）
        $this->insertLocalOrder($uid, $orderSn, $payload, $price, 'ppt', 'AI PPT', $r['data'] ?? []);

        return JsonService::data([
            'order_id' => $r['data']['order_id'] ?? 0,
            'order_sn' => $orderSn,
            'amount'   => $this->floatStr($price),
            'outline_id' => $r['data']['outline_id'] ?? 0,
            'pay_mode' => 'postpaid',
            'pay_status' => 0,
            'amount_display' => $this->floatStr($price),
        ]);
    }

    // ==================== 高级版下单（后付费：只建单不扣费，下载时才扣） ====================

    public function createAdvancedOrder(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();
        $title = trim((string) ($p['title'] ?? ''));
        $tplUid = trim((string) ($p['tpl_uid'] ?? ''));
        if ($title === '' || $tplUid === '') {
            return JsonService::fail('标题和模板不能为空', [], 0, 1);
        }

        $price = $this->quoteFromMain(true, $uid);
        if ($price <= 0) {
            return JsonService::fail('计价金额异常', [], 0, 1);
        }

        $payload = [
            'agent_str'       => $this->agentStr($uid),
            'title'           => $title,
            'tpl_uid'         => $tplUid,
            'theme_id'        => (string) ($p['theme_id'] ?? ''),
            'outline_content' => (string) ($p['outline_content'] ?? ''),
            'author'          => (string) ($p['author'] ?? ''),
        ];
        $r = $this->call('/openapi/ppt/createAdvanced', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '创建订单失败，请重试', [], 0, 1);
        }
        $orderSn = (string) ($r['data']['order_sn'] ?? '');
        if ($orderSn === '') {
            return JsonService::fail('创建订单失败：未获取到订单号', [], 0, 1);
        }

        $this->insertLocalOrder($uid, $orderSn, $payload, $price, 'ppt', 'AI PPT 高级版', $r['data'] ?? []);

        return JsonService::data([
            'order_id' => $r['data']['order_id'] ?? 0,
            'order_sn' => $orderSn,
            'amount'   => $this->floatStr($price),
            'version'  => 'advanced',
            'pay_mode' => 'postpaid',
            'pay_status' => 0,
            'amount_display' => $this->floatStr($price),
        ]);
    }

    // ==================== 计价（本站用户应付 = 进价 + 后台加价） ====================

    /** 本站商品加价（元/篇）：ad_products.code=ppt 的 markup */
    private function productMarkup(): float
    {
        try {
            return (float) Db::name('products')->where('code', 'ppt')->value('markup', 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /** 主站进价（cost>0 兜底 base）；0=上游不可用 */
    private function inPriceFromMain(bool $advanced): float
    {
        $r = $this->call('/openapi/ppt/price', 'GET', ['is_advanced' => $advanced ? 1 : 0]);
        if (($r['code'] ?? 0) !== 1) {
            return 0.0;
        }
        $cost = (float) ($r['data']['cost_price'] ?? 0);
        $base = (float) ($r['data']['base_price'] ?? 0);
        return $cost > 0 ? $cost : $base;
    }

    /** 本站用户应付 = 进价 + 后台加价（元/篇） */
    private function quoteFromMain(bool $advanced, int $uid): float
    {
        $in = $this->inPriceFromMain($advanced);
        return $in > 0 ? round($in + $this->productMarkup(), 2) : 0.0;
    }

    // ==================== 本地流水 + 订单落库 ====================

    private function writeLogs(int $uid, string $orderSn, float $before, float $after, float $amount, string $type): void
    {
        try {
            Db::name('balance_logs')->insert([
                'user_id'           => $uid,
                'change_type'       => 'ppt_order',
                'change_amount'     => -$amount,
                'bonus_amount'      => 0,
                'total_change_amount' => -$amount,
                'before_balance'    => $before,
                'after_balance'     => $after,
                'related_id'        => $orderSn,
                'related_type'      => 'ppt',
                'remark'            => 'AI PPT 下单扣费（' . $orderSn . '）',
                'operator_type'     => 'system',
                'ip_address'        => Request::ip(),
                'create_time'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function insertLocalOrder(int $uid, string $orderSn, array $payload, float $amount, string $code, string $name, array $d): void
    {
        $exist = Db::name('orders')->where('order_no', $orderSn)->find();
        if ($exist) {
            return;
        }
        Db::name('orders')->insert([
            'order_no'           => $orderSn,
            'product_id'         => 0,
            'product_code'       => $code,
            'product_name'       => $name,
            'product_data'       => json_encode([
                'type'    => 'ppt',
                'title'   => (string) ($payload['title'] ?? ''),
                'version' => strpos($name, '高级') !== false ? 'advanced' : 'normal',
            ], JSON_UNESCAPED_UNICODE),
            'quantity'           => 1,
            'unit_price'         => $amount,
            'total_amount'       => $amount,
            'remark'             => 'AI PPT（openapi · 后付费）',
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

    public function downloadPpt(): \think\response\Json
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
            return JsonService::fail('PPT 订单不存在', [], 0, 1);
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

        // 3) 调主站下载（首次会扣主站预充）
        $r = $this->call('/openapi/ppt/download', 'POST', ['order_sn' => $orderSn]);
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
                    'change_type'       => 'ppt_download',
                    'change_amount'     => -$amount,
                    'bonus_amount'      => 0,
                    'total_change_amount' => -$amount,
                    'before_balance'    => $before,
                    'after_balance'     => $after,
                    'related_id'        => $orderSn,
                    'related_type'      => 'ppt',
                    'remark'            => 'AI PPT 下载扣费（' . $orderSn . '）',
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

        return JsonService::data([
            'url'        => $r['data']['url'] ?? '',
            'charged'    => $r['data']['charged'] ?? (!$alreadyPaid),
            'pay_status' => 1,
            'order_sn'   => $orderSn,
        ]);
    }
}