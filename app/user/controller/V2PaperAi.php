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
 * 高级论文（paper）openapi 桥接控制器
 *
 * 前端 create.vue / OutlineEditor.vue 走本站 /api/pc·/api/ai/* + token Header，
 * 本控制器桥接到主站 /openapi/paper·user（Bearer 出站，docking.api_url/api_token），
 * 并落地"双余额"扣费：
 *   1) 本站用户先扣本站余额（ad_users.balance）；
 *   2) 再调主站下单扣对接 token 主人在主站的预充余额；
 *   3) 主站预充余额不足 ⇒ 禁止下单。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2PaperAi
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

    /** 读取请求参数：兼容表单与 JSON body（TP 在部分环境不并入 $_POST，手动解析 php://input） */
    private static $jsonBody = null;

    private function body(): array
    {
        $post = Request::post();
        if (!is_array($post)) {
            $post = [];
        }
        if (self::$jsonBody === null) {
            self::$jsonBody = [];
            // 优先取框架已缓存的内容（部分环境 php://input 被提前消费，getContent 返回缓存）
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

    /** 出站请求主站 openapi；$path 形如 /openapi/paper/generateOutline */
    private function call(string $path, string $method = 'GET', array $params = []): array
    {
        $c = new ApiClientService();
        return $method === 'GET' ? $c->get($path, $params) : $c->post($path, $params);
    }

    /** 从「8000」/「1.5万」/「10000」这类文案解析字数 */
    private function wordsToCount($words): int
    {
        $raw = (string)$words;
        $digits = preg_replace('/[^0-9]/', '', $raw);
        $n = (int)$digits;
        if ($n > 0) {
            return $n;
        }
        if (mb_strpos($raw, '万') !== false || mb_strpos($raw, 'w') !== false) {
            $v = (float)preg_replace('/[^0-9.]/', '', $raw) * 10000;
            if ($v > 0) {
                return (int)$v;
            }
        }
        return 0;
    }

    private function floatStr($v): string
    {
        return number_format((float)$v, 2, '.', '');
    }

    // ==================== 模板 ====================

    /** 公共模板（主站与网站 /pc/create 同源随机推荐；无归属问题，不需要 agent_str） */
    public function searchTemplates(): \think\response\Json
    {
        $kw = (string)Request::get('keyword', '');
        $r  = $this->call('/openapi/paper/templateList', 'GET', ['tab' => 'public', 'keyword' => $kw]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取模板失败', [], 0, 1);
        }
        $d    = is_array($r['data'] ?? null) ? $r['data'] : [];
        $list = is_array($d['list'] ?? null) ? $d['list'] : [];
        foreach ($list as &$it) {
            if (is_array($it)) {
                // 公共模板：id = template_no（主站隐藏自增 id）
                $it['id'] = (string)($it['template_no'] ?? '');
            }
        }
        unset($it);
        return JsonService::data([
            'list'     => $list,
            'total'    => $d['total'] ?? 0,
            'db_total' => $d['db_total'] ?? 0,
        ]);
    }

    // ==================== 模板 ====================

    /** 私有模板：改走 /openapi/template/list（与 paper 私有模板同表 TemplistAuto），agent_str 服务端隔离归属；条目 id 即 paper/create 的 template_id */
    public function userTemplates(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::notLogin();
        }
        $r = $this->call('/openapi/template/list', 'GET', [
            'page'      => 1,
            'page_size' => 100,
            'agent_str' => $this->agentStr($uid),
            'keyword'   => trim((string)Request::get('keyword', '')),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '获取私有模板失败', [], 0, 1);
        }
        $d    = is_array($r['data'] ?? null) ? $r['data'] : [];
        $list = is_array($d['list'] ?? null) ? $d['list'] : [];
        foreach ($list as &$it) {
            if (!is_array($it)) {
                continue;
            }
            $it['id'] = (int)($it['id'] ?? 0);
            unset($it['agent_str']); // 内部渠道标识不下发前端
        }
        unset($it);
        return JsonService::data([
            'list'  => $list,
            'total' => (int)($d['total'] ?? count($list)),
        ]);
    }

    // ==================== 模型 ====================

    /** 本站商品加价（元/单）：ad_products.markup；paper 类型对应商品 code=gjlw */
    private function productMarkupByCode(string $code): float
    {
        try {
            return (float) Db::name('products')->where('code', $code)->value('markup', 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function models(): \think\response\Json
    {
        $type = trim((string)Request::get('type', 'paper'));
        // 主站成本价列表（GET /openapi/price/costList，含模型与字数档），按 type_code 过滤映射为前端契约：
        // { code, name, tag, is_advanced, price_config(JSON:[{max_words,price}]) }
        // 展示价与实扣同源：每档用户价 = 进价(cost>0 兜底标价) + 后台加价（paper→gjlw / autodoc→autodoc）
        $markup = $this->productMarkupByCode($type === 'paper' ? 'gjlw' : $type);
        $c = new ApiClientService();
        $r = $c->get('/openapi/price/costList', []);
        if (($r['code'] ?? 0) === 1 && is_array($r['data']['list'] ?? null)) {
            $models = [];
            foreach ($r['data']['list'] as $item) {
                if (!is_array($item) || ($item['type_code'] ?? '') !== $type) {
                    continue;
                }
                $tiers = [];
                foreach ((array)($item['tiers'] ?? []) as $tier) {
                    if (!is_array($tier)) {
                        continue;
                    }
                    $cost = (float)($tier['cost_price'] ?? 0);
                    $in   = $cost > 0 ? $cost : (float)($tier['base_price'] ?? 0);
                    $tiers[] = [
                        'max_words' => (int)($tier['max_words'] ?? 0),
                        'price'     => (string) round($in + $markup, 2),
                    ];
                }
                $models[] = [
                    'code'         => (string)($item['model_code'] ?? ''),
                    'name'         => (string)($item['model_name'] ?? ''),
                    'tag'          => (string)($item['model_tag'] ?? ''),
                    'is_advanced'  => (int)($item['is_advanced'] ?? 0),
                    'price_config' => json_encode($tiers, JSON_UNESCAPED_UNICODE),
                ];
            }
            if ($models) {
                return JsonService::data($models);
            }
        }
        // 回退：costList 不可用时的论文模型保底清单（type 非论文时返回空列表）
        if ($type !== 'paper') {
            return JsonService::data([]);
        }
        return JsonService::data([
            ['code' => 'standard', 'name' => '标准版', 'tag' => '普通', 'is_advanced' => 0],
            ['code' => 'pro',      'name' => '专业版', 'tag' => '高级', 'is_advanced' => 1],
            ['code' => 'deepseek', 'name' => 'DeepSeek高级版', 'tag' => '高级', 'is_advanced' => 1],
        ]);
    }

    // ==================== 大纲 ====================

    public function outlineDetail(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::notLogin();
        }
        $no = trim((string)Request::get('outline_no', ''));
        if ($no === '') {
            return JsonService::fail('缺少大纲编号', [], 0, 1);
        }
        $r = $this->call('/openapi/paper/outlineDetail', 'GET', ['outline_no' => $no]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '大纲不存在', [], 0, 1);
        }
        return JsonService::data($r['data'] ?? []);
    }

    public function updateOutline(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::notLogin();
        }
        $no      = trim((string)($this->body()['outline_no'] ?? ''));
        $content = (string)($this->body()['content'] ?? '');
        if ($no === '' || $content === '') {
            return JsonService::fail('参数不能为空', [], 0, 1);
        }
        $r = $this->call('/openapi/paper/updateOutline', 'POST', ['outline_no' => $no, 'content' => $content]);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '保存失败', [], 0, 1);
        }
        return JsonService::data(['outline_no' => $no, 'updated' => true]);
    }

    // ==================== 在线文献 ====================

    /** 在线文献检索（主站 /tokenapi/write/onlineLiterature 代理，data 为文献数组） */
    public function onlineLiterature(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $title = trim((string)($this->body()['title'] ?? ''));
        if ($title === '') {
            return JsonService::fail('缺少检索关键词', [], 0, 1);
        }
        $r = $this->call('/tokenapi/write/onlineLiterature', 'POST', ['title' => $title]);
        if (($r['code'] ?? 0) !== 1 || !is_array($r['data'] ?? null)) {
            return JsonService::fail($r['msg'] ?? '检索失败，请稍后重试', [], 0, 1);
        }
        return JsonService::data($r['data']);
    }

    // ==================== 我的大纲 ====================

    /**
     * 我的大纲列表（分页）
     * POST /api/ai/outlineList  入参：page, limit
     * 数据源：本站 ad_paper_outlines（生成大纲成功时落库，下单成功置 is_ordered=1）
     */
    public function outlineList(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $page  = max(1, (int) input('page', 1));
        $limit = min(50, max(1, (int) input('limit', 10)));

        $query = Db::name('paper_outlines')->where('user_id', $uid);
        $total = (clone $query)->count();
        $rows  = (clone $query)->order('id', 'desc')->page($page, $limit)->select()->toArray();

        $modelNames = ['standard' => '标准版', 'pro' => '专业版', 'deepseek' => 'DeepSeek高级版'];
        $list = [];
        foreach ($rows as $r) {
            $model = (string) ($r['model'] ?? '');
            $list[] = [
                'outline_no' => (string) ($r['outline_no'] ?? ''),
                'title'      => (string) ($r['title'] ?? ''),
                'degree'     => (string) ($r['degree'] ?? ''),
                'profession' => (string) ($r['profession'] ?? ''),
                'words'      => (string) ($r['words'] ?? ''),
                'model'      => $model,
                'model_name' => $modelNames[$model] ?? '',
                'is_ordered' => (int) ($r['is_ordered'] ?? 0),
                'create_time' => (string) ($r['create_time'] ?? ''),
            ];
        }
        return JsonService::data(['list' => $list, 'total' => $total]);
    }

    /** 大纲生成成功后落本站库（同用户同 outline_no 幂等） */
    private function upsertOutline(int $uid, string $outlineNo, array $p): void
    {
        if ($outlineNo === '') {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $exist = Db::name('paper_outlines')
            ->where('user_id', $uid)
            ->where('outline_no', $outlineNo)
            ->find();
        if ($exist) {
            return;
        }
        Db::name('paper_outlines')->insert([
            'user_id'     => $uid,
            'outline_no'  => $outlineNo,
            'title'       => mb_substr(trim((string)($p['title'] ?? '')), 0, 250),
            'degree'      => mb_substr((string)($p['degree'] ?? ''), 0, 45),
            'profession'  => mb_substr((string)($p['profession'] ?? ''), 0, 95),
            'words'       => mb_substr((string)($p['words'] ?? ''), 0, 15),
            'model'       => mb_substr((string)($p['model'] ?? ''), 0, 25),
            'is_ordered'  => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    /** 下单成功后标记大纲已下单 */
    private function markOutlineOrdered(int $uid, string $outlineNo): void
    {
        if ($outlineNo === '') {
            return;
        }
        Db::name('paper_outlines')
            ->where('user_id', $uid)
            ->where('outline_no', $outlineNo)
            ->update(['is_ordered' => 1, 'update_time' => date('Y-m-d H:i:s')]);
    }

    // ==================== 大纲生成（SSE / 同步） ====================

    private function buildOutlinePayload(array $p): array
    {
        return [
            'title'         => trim((string)($p['title'] ?? '')),
            'word_count'    => $this->wordsToCount($p['words'] ?? ''),
            'model'         => (string)($p['model'] ?? 'standard'),
            'outline_level' => (string)($p['outline_level'] ?? 'two'),
            'language'      => (string)($p['language'] ?? '中文'),
            'degree'        => (string)($p['degree'] ?? '本科'),
            'profession'    => (string)($p['profession'] ?? ''),
            'major'         => (string)($p['major'] ?? ''),
            'words'         => (string)($p['words'] ?? ''),
        ];
    }

    public function generateOutline()
    {
        return $this->streamOutline('generate');
    }

    public function enhanceOutline()
    {
        return $this->streamOutline('enhance');
    }

    private function streamOutline(string $mode)
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p     = $this->body();
        $title = trim((string)($p['title'] ?? ''));
        if ($title === '') {
            return JsonService::fail('论文标题不能为空', [], 0, 1);
        }
        $payload = $this->buildOutlinePayload($p);
        if ($payload['word_count'] <= 0) {
            return JsonService::fail('请填写字数要求（如 8000）', [], 0, 1);
        }

        // 增强：主站暂未提供独立增强接口，将用户粘贴的自定义大纲原样回传以便继续编辑
        if ($mode === 'enhance' && !empty($p['custom_outline'])) {
            $this->emitStreamFromContent((string)$p['custom_outline'], null);
            return;
        }

        // 真流式：逐帧透传主站 /openapi/paper/generateOutlineStream，
        // 旁路捕获 done 帧 outline_no 用于本站大纲落库（失败不阻塞流输出）
        (new ApiClientService())->passthroughSse(
            '/openapi/paper/generateOutlineStream',
            $payload,
            function (array $frame) use ($uid, $p) {
                if (!empty($frame['done']) && !empty($frame['outline_no'])) {
                    try {
                        $this->upsertOutline($uid, (string)$frame['outline_no'], $p);
                    } catch (\Throwable $e) {
                    }
                }
            }
        );
    }

    private function emitStreamFromContent(string $content, ?string $outlineNo): void
    {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $len  = mb_strlen($content);
        $step = (int)min(200, max(50, ceil($len / 60)));
        $pos  = 0;
        while ($pos < $len) {
            $chunk = mb_substr($content, $pos, $step);
            $pos  += $step;
            echo 'data: ' . json_encode(['content' => $chunk], JSON_UNESCAPED_UNICODE) . "\n\n";
            echo str_repeat(' ', 256) . "\n";
            @ob_flush();
            flush();
            usleep(12000);
        }
        $done = ['done' => true];
        if ($outlineNo !== null) {
            $done['outline_no'] = $outlineNo;
        }
        echo 'data: ' . json_encode($done, JSON_UNESCAPED_UNICODE) . "\n\n";
        @ob_flush();
        flush();
        exit;
    }

    public function generateOutlineSync(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p     = $this->body();
        $title = trim((string)($p['title'] ?? ''));
        if ($title === '') {
            return JsonService::fail('论文标题不能为空', [], 0, 1);
        }
        $payload = $this->buildOutlinePayload($p);
        if ($payload['word_count'] <= 0) {
            return JsonService::fail('请填写字数要求（如 8000）', [], 0, 1);
        }
        $r = $this->call('/openapi/paper/generateOutline', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return JsonService::fail($r['msg'] ?? '大纲生成失败', [], 0, 1);
        }
        // 生成成功：落本站大纲列表（失败不阻塞返回）
        try {
            $this->upsertOutline($uid, (string)($r['data']['outline_no'] ?? ''), $p);
        } catch (\Throwable $e) {
        }
        return JsonService::data([
            'outline_no' => $r['data']['outline_no'] ?? '',
            'content'    => $r['data']['content'] ?? '',
            'length'     => (int)($r['data']['length'] ?? 0),
        ]);
    }

    // ==================== 双余额下单 ====================

    public function createOrder(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p     = $this->body();
        $title = trim((string)($p['title'] ?? ''));
        $wc    = (int)($p['word_count'] ?? 0);
        if ($title === '') {
            return JsonService::fail('论文标题不能为空', [], 0, 1);
        }
        if ($wc <= 0) {
            return JsonService::fail('字数要求无效', [], 0, 1);
        }
        $model     = (string)($p['model'] ?? 'standard');
        $needLower = (int)($p['need_lower_ai'] ?? 0);
        if ((string)($p['pay_way'] ?? 'balance') !== 'balance') {
            return JsonService::fail('当前仅支持余额支付', [], 0, 1);
        }

        // 1) 计价（本站用户应付 = 进价 + 后台加价，梯度每档同源）
        $pr = $this->call('/openapi/paper/price', 'GET', [
            'model'       => $model,
            'word_count'  => $wc,
            'need_lower_ai' => $needLower,
        ]);
        if (($pr['code'] ?? 0) !== 1) {
            return JsonService::fail($pr['msg'] ?? '计价失败', [], 0, 1);
        }
        $cost = (float)($pr['data']['cost_price'] ?? 0);
        $base = (float)($pr['data']['base_price'] ?? 0);
        // 进价 = 主站代理成本（cost>0 兜底标价；降AI时主站已按 ×1.3 缩放）
        $inPrice = $cost > 0 ? $cost : $base;
        if ($inPrice <= 0) {
            return JsonService::fail('计价金额异常', [], 0, 1);
        }
        // 用户应收：降AI 的 30% 上浮作用于「进价+加价」整体（与前端下单弹窗 lowerAiFee=档位价×30% 同源），
        // 即降AI时加价部分同样 ×1.3：用户价 = 进价 + 加价×1.3（数学恒等于 (未缩放进价+加价)×1.3）
        $markup = $this->productMarkupByCode('gjlw');
        $quote  = round($inPrice + $markup * ($needLower ? 1.3 : 1.0), 2);

        // 2) 主站预充余额 guard（只须覆盖进价，加价部分不占主站预充；不足禁止下单）
        $pk        = $this->call('/openapi/user/package', 'GET', []);
        $mainMoney = (float)($pk['data']['user_money'] ?? 0);
        if ($inPrice > $mainMoney + 0.001) {
            return JsonService::fail('对接方预充值余额不足，暂无法下单，请联系平台充值', [], 0, 1);
        }

        // 3) 校验并预扣本站用户余额
        $user = Db::name('users')->where('id', $uid)->find();
        if (!$user) {
            return JsonService::fail('用户不存在', [], 0, 1);
        }
        $bal    = (float)($user['balance'] ?? 0);
        if ($bal + 0.001 < $quote) {
            return JsonService::fail('余额不足，请先充值', [], 0, 1);
        }
        $before = $bal;
        $after  = round($bal - $quote, 2);
        Db::name('users')->where('id', $uid)->update([
            'balance'    => $after,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        // 4) 调主站下单（扣主站预充，真实生成论文）
        $payload = $this->buildPaperCreatePayload($p, $wc);
        $payload['pay_way']    = 'balance';
        $payload['agent_str']  = $this->agentStr($uid);
        $r = $this->call('/openapi/paper/create', 'POST', $payload);

        if (($r['code'] ?? 0) !== 1) {
            // 主站失败：回滚本站用户余额
            Db::name('users')->where('id', $uid)->update([
                'balance'    => $before,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            return JsonService::fail($r['msg'] ?? '下单失败，已退回本站余额', [], 0, 1);
        }

        $d       = $r['data'] ?? [];
        $orderSn = (string)($d['order_sn'] ?? '');

        // 4.5) 以主站实际成交进价为准，多退少补（上游计价接口与下单可能存在字数区间差异）。
        //      用户实付 = 主站成交进价 + 加价（降AI时加价同样 ×1.3）：加价部分固定不参与退补，防止成本价泄露。
        $actualIn = (float)($d['order_amount'] ?? 0);
        if ($actualIn <= 0) {
            $actualIn = $inPrice;
        }
        $actual = round($actualIn + $markup * ($needLower ? 1.3 : 1.0), 2);
        $finalAfter = $after;
        $diff       = round($quote - $actual, 2);
        if (abs($diff) >= 0.01) {
            if ($diff > 0) {
                // 预扣多于实际成交：退差额
                $finalAfter = round($after + $diff, 2);
                Db::name('users')->where('id', $uid)->update([
                    'balance'    => $finalAfter,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                try {
                    Db::name('balance_logs')->insert([
                        'user_id'             => $uid,
                        'change_type'         => 'paper_refund',
                        'change_amount'       => $diff,
                        'bonus_amount'        => 0,
                        'total_change_amount' => $diff,
                        'before_balance'      => $after,
                        'after_balance'       => $finalAfter,
                        'related_id'          => $orderSn,
                        'related_type'        => 'paper',
                        'remark'              => 'AI论文下单差额退回（计价 ' . $this->floatStr($quote) . ' 元，实付 ' . $this->floatStr($actual) . ' 元）',
                        'operator_type'       => 'system',
                        'ip_address'          => Request::ip(),
                        'create_time'         => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                }
            } else {
                // 预扣少于实际成交：补收差额（最多扣至 0，避免负余额）
                $deduct     = min(-$diff, $after);
                $finalAfter = round($after - $deduct, 2);
                if ($deduct >= 0.01) {
                    Db::name('users')->where('id', $uid)->update([
                        'balance'    => $finalAfter,
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                    try {
                        Db::name('balance_logs')->insert([
                            'user_id'             => $uid,
                            'change_type'         => 'paper_order',
                            'change_amount'       => -$deduct,
                            'bonus_amount'        => 0,
                            'total_change_amount' => -$deduct,
                            'before_balance'      => $after,
                            'after_balance'       => $finalAfter,
                            'related_id'          => $orderSn,
                            'related_type'        => 'paper',
                            'remark'              => 'AI论文下单差额补收（计价 ' . $this->floatStr($quote) . ' 元，实付 ' . $this->floatStr($actual) . ' 元）',
                            'operator_type'       => 'system',
                            'ip_address'          => Request::ip(),
                            'create_time'         => date('Y-m-d H:i:s'),
                        ]);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        // 5) 本站余额流水 + 订单落库（非阻塞；失败不影响下单结果）
        try {
            Db::name('balance_logs')->insert([
                'user_id'           => $uid,
                'change_type'       => 'paper_order',
                'change_amount'     => -$quote,
                'bonus_amount'      => 0,
                'total_change_amount' => -$quote,
                'before_balance'    => $before,
                'after_balance'     => $after,
                'related_id'        => $orderSn,
                'related_type'      => 'paper',
                'remark'            => 'AI论文下单（' . $model . '，' . $wc . ' 字）',
                'operator_type'     => 'system',
                'ip_address'        => Request::ip(),
                'create_time'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
        try {
            $this->insertLocalOrder($uid, $orderSn, $p, $actual, $model, $wc, $d);
        } catch (\Throwable $e) {
        }
        try {
            $this->markOutlineOrdered($uid, trim((string)($p['outline_no'] ?? '')));
        } catch (\Throwable $e) {
        }

        return JsonService::data([
            'order_id'     => $d['order_id'] ?? 0,
            'order_sn'     => $orderSn,
            'order_amount' => $this->floatStr($actual),
            'range'        => $d['range'] ?? '',
            'pay_status'   => $d['pay_status'] ?? 1,
            'pay_way'      => 'balance',
            'left_money'   => $this->floatStr($finalAfter),
            'left_quota'   => $d['left_quota'] ?? '',
        ]);
    }

    private function buildPaperCreatePayload(array $p, int $wc): array
    {
        $source = trim((string)($p['template_source'] ?? 'public'));
        $payload = [
            'title'              => trim((string)($p['title'] ?? '')),
            'word_count'         => $wc,
            'model'              => (string)($p['model'] ?? 'standard'),
            'need_lower_ai'      => (int)($p['need_lower_ai'] ?? 0),
            'outline_level'      => (string)($p['outline_level'] ?? 'two'),
            'language'           => (string)($p['language'] ?? '中文'),
            'degree'             => (string)($p['degree'] ?? '本科'),
            'profession'         => (string)($p['profession'] ?? ''),
            'major'              => (string)($p['major'] ?? ''),
            'outline_no'         => trim((string)($p['outline_no'] ?? '')),
            'outline_data'       => (string)($p['outline_data'] ?? ''),
            'use_custom_outline' => (int)($p['use_custom_outline'] ?? 0),
            'words'              => (string)($p['words'] ?? ''),
        ];
        // 模板字段：仅在前端传了有效模板 id 时才下发（0/空 = 用户未选模板，不传避免上游按 0 号模板查询报「模板不存在或已下线」）
        $tid = trim((string)($p['template_id'] ?? ''));
        if ($source === 'private') {
            if ($tid !== '' && (int)$tid > 0) {
                $payload['template_type'] = 'private';
                $payload['template_id']   = (int)$tid;
            }
        } elseif ($tid !== '' && $tid !== '0') {
            $payload['template_type'] = 'public';
            $payload['template_no']   = $tid;
        }
        return $payload;
    }

    private function insertLocalOrder(int $uid, string $orderSn, array $p, float $amount, string $model, int $wc, array $d): void
    {
        $exist = Db::name('orders')->where('order_no', $orderSn)->find();
        if ($exist) {
            return;
        }
        Db::name('orders')->insert([
            'order_no'           => $orderSn,
            'product_id'         => 0,
            'product_code'       => 'paper',
            'product_name'       => 'AI 论文',
            'product_data'       => json_encode([
                'type'        => 'paper',
                'model'       => $model,
                'word_count'  => $wc,
                'title'       => trim((string)($p['title'] ?? '')),
                'outline_no'  => trim((string)($p['outline_no'] ?? '')),
            ], JSON_UNESCAPED_UNICODE),
            'quantity'           => 1,
            'unit_price'         => $amount,
            'total_amount'       => $amount,
            'remark'             => '高级论文（openapi）',
            'status'             => 0,
            'pay_status'         => 1,
            'user_id'            => $uid,
            'tokenapi_submitted' => 1,
            'tokenapi_response'  => json_encode($d, JSON_UNESCAPED_UNICODE),
            'ip_address'         => Request::ip(),
            'user_agent'         => Request::header('user-agent', ''),
            'create_time'        => date('Y-m-d H:i:s'),
            'update_time'        => date('Y-m-d H:i:s'),
        ]);
    }
}