<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Request;
use think\facade\Db;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * AIGC 降重（段落降重 jiangchong + 文档降重 check）openapi 桥接控制器
 *
 * 前端 aigcreduceweight.vue 走本站 /api/jiangchong/* 与 /api/check/*（+ token Header），
 * 本控制器桥接到主站 /openapi/jiangchong 与 /openapi/check（Bearer 出站，docking.api_url/api_token）。
 *
 * 计费模型：降重为「直连透传」——主站按套餐钱包/余额（pay_method=balance/char/doc/time）在调用时扣费，
 * 本站不额外扣取用户余额（避免双重计费；与 PPT/自动排版的后付费双余额模型不同）。
 * agent_str 传本站用户对接标识（AgentIdHelper 前缀格式，如 adweb_12），主站按下游用户隔离归属（改写/建单/订单列表/记录/配额均携带）。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2JiangchongAi
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

    /** 读取请求参数：兼容表单、JSON body 与 GET 查询串 */
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
        $get = Request::get();
        if (!is_array($get)) {
            $get = [];
        }
        return array_merge($get, $post, self::$jsonBody);
    }

    /** 出站请求主站 openapi */
    private function call(string $path, string $method = 'GET', array $params = []): array
    {
        $c = new ApiClientService();
        return $method === 'GET' ? $c->get($path, $params) : $c->post($path, $params);
    }

    /** 统一失败返回 */
    private function fail($msg): \think\response\Json
    {
        return JsonService::fail($msg ?: '操作失败，请稍后重试', [], 0, 1);
    }

    /** 主站预充余额（对接 token 主人在主站的 user_money） */
    private function mainPrepaid(): float
    {
        $r = $this->call('/openapi/user/package', 'GET', []);
        return (float) ($r['data']['user_money'] ?? 0);
    }

    /** 下游用户独立套餐余额（agent_str 维度：时长包/篇数包与 token 主人互不占用） */
    private function agentBalance(): array
    {
        $uid = $this->uid();
        if (!$uid) {
            return [];
        }
        $r = $this->call('/openapi/jiangchong/agentBalance', 'GET', ['agent_str' => $this->agentStr($uid)]);
        if (($r['code'] ?? 0) === 1 && is_array($r['data'] ?? null)) {
            return $r['data'];
        }
        return [];
    }

    /** 降重商品每千字加价金额（ad_products.code=tools_aigcreduceweight 的 markup，元/千字） */
    private function reduceMarkup(): float
    {
        try {
            $m = Db::name('products')
                ->where('code', 'tools_aigcreduceweight')
                ->value('markup', 0);
        } catch (\Throwable $e) {
            $m = 0;
        }
        return (float) ($m ?? 0);
    }

    /**
     * 本站加价后的千字符单价：主站基础单价 + 降重商品加价金额（元/千字）。
     * 复用商品管理统一的定价模式（用户单价 = 对接底价 + 该商品加价）。
     * 基础单价≤0（免计费模式）时不加价，保持 0。
     */
    private function markedUpCharPrice(float $base): float
    {
        if ($base <= 0) {
            return 0.0;
        }
        return round($base + $this->reduceMarkup(), 2);
    }

    /** 降重千字符单价（本站加价后；0=免计费模式） */
    private function charPrice(): float
    {
        $r = $this->call('/openapi/jiangchong/wallet', 'GET', []);
        if (($r['code'] ?? 0) === 1 && isset($r['data']['char_price'])) {
            return $this->markedUpCharPrice((float) $r['data']['char_price']);
        }
        return 0.0;
    }

    /** 主站基础千字符单价（不加价）。仅用于判断主站预充是否足以覆盖主站成本 */
    private function baseCharPrice(): float
    {
        $r = $this->call('/openapi/jiangchong/wallet', 'GET', []);
        if (($r['code'] ?? 0) === 1 && isset($r['data']['char_price'])) {
            return (float) $r['data']['char_price'];
        }
        return 0.0;
    }

    /** 与主站 calcAmount 一致的金额估算：ceil(word/1000) × 千字单价 */
    private function estimateAmount(int $wordCount, float $charPrice): float
    {
        if ($wordCount <= 0 || $charPrice <= 0) {
            return 0.0;
        }
        $units  = (int) ceil($wordCount / 1000);
        $amount = bcmul((string) $units, (string) $charPrice, 2);
        return round((float) $amount, 2);
    }

    /**
     * 降重统一「双余额扣费」：本站扣用户余额 + 主站扣 token 预充(user_money)。
     * 流程：主站预充 guard → 本站余额 guard+预扣 → 调主站（balance 方式扣预充）→
     *       成功按主站实际扣费金额多退少补并记流水，失败回滚本站余额。
     * 免计费模式（char_price=0）不扣任何费用。
     */
    private function dualCharge(string $path, int $wordCount, string $scene, array $payload): \think\response\Json
    {
        $uid = $this->uid();
        $price     = $this->charPrice();                       // 本站加价后单价（应收）
        $estimated = $this->estimateAmount($wordCount, $price); // 本站应收估算（含加价）
        $baseEst   = $this->estimateAmount($wordCount, $this->baseCharPrice()); // 主站成本估算（不加价）

        // 主站预充 guard：只需覆盖主站成本（基础单价），加价部分不占用主站预充
        if ($baseEst > 0 && $this->mainPrepaid() + 0.001 < $baseEst) {
            return $this->fail('对接方预充值余额不足，暂无法降重，请联系平台充值');
        }

        // 本站余额 guard + 预扣
        $before = null;
        $after  = null;
        if ($estimated > 0) {
            $user = Db::name('users')->where('id', $uid)->find();
            if (!$user) {
                return $this->fail('用户不存在');
            }
            $bal = (float) ($user['balance'] ?? 0);
            if ($bal + 0.001 < $estimated) {
                return $this->fail('余额不足，请先充值');
            }
            $before = $bal;
            $after  = round($bal - $estimated, 2);
            Db::name('users')->where('id', $uid)->update([
                'balance'     => $after,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 调主站（balance 方式 → 扣 token 预充 user_money）
        $r = $this->call($path, 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            if ($before !== null) {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $before,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            return $this->fail($r['msg'] ?? '降重失败，请稍后重试');
        }

        // 结算：本站余额按「本站加价后的估算金额」$estimated 收取（预扣即最终扣款）。
        // 主站按成本价（billing.amount=基础单价）扣其 token 预充，不再回退本站到成本价，
        // 加价差（estimated - 主站实扣）即本站收益。
        $d = $r['data'] ?? [];
        if ($before !== null) {
            $final = round($before - $estimated, 2);
            if (abs($after - $final) >= 0.01) {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $final,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            $after = $final;
            if ((int) ($d['billing']['charged'] ?? 1) === 1 && $estimated > 0) {
                try {
                    Db::name('balance_logs')->insert([
                        'user_id'             => $uid,
                        'change_type'         => $scene === 'check' ? 'check_deduct' : 'jiangchong_deduct',
                        'change_amount'       => -$estimated,
                        'bonus_amount'        => 0,
                        'total_change_amount' => -$estimated,
                        'before_balance'      => $before,
                        'after_balance'       => $after,
                        'related_id'          => (string) ($d['order_sn'] ?? ''),
                        'related_type'        => $scene,
                        'remark'              => ($scene === 'check' ? '文档降重' : '段落降重') . '双余额扣费（' . ($d['order_sn'] ?? '') . '）',
                        'operator_type'       => 'system',
                        'ip_address'          => Request::ip(),
                        'create_time'         => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                }
            }
        }

        // 交付安全：剥离上游响应中的主站预充余额（left_money 统一改为本站余额口径）
        $d = $this->sanitizeUpstreamData(is_array($d) ? $d : [], $before !== null ? $after : null);

        return JsonService::data($d);
    }

    /**
     * 剥离上游（主站）响应中的敏感字段：主站预充余额不得透出给用户端。
     * $localLeft 非空时，billing.left_money 改写为本站扣费后余额；否则移除该字段。
     */
    private function sanitizeUpstreamData(array $d, ?float $localLeft): array
    {
        unset($d['user_money'], $d['main_money']);
        if (isset($d['billing']) && is_array($d['billing'])) {
            if ($localLeft !== null) {
                $d['billing']['left_money'] = round($localLeft, 2);
            } else {
                unset($d['billing']['left_money']);
            }
        }
        return $d;
    }

    // ==================== 段落降重（jiangchong） ====================

    /** 降重配置（前端读 data.languages） */
    public function config(): \think\response\Json
    {
        $r = $this->call('/openapi/jiangchong/config', 'GET');
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取降重配置失败');
        }
        return JsonService::data($r['data'] ?? []);
    }

    /** 套餐钱包（对账：账户余额=本站用户自有余额；千字单价=主站基础价+本站加价；时长包/篇数包=该用户 agent_str 独立余额） */
    public function wallet(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $r = $this->call('/openapi/jiangchong/wallet', 'GET');
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取钱包失败');
        }
        $d = $r['data'] ?? [];
        if (is_array($d)) {
            // 「账户余额」展示本站用户自有余额（ad_users.balance），而非对接方（主站 token 主人）的预充值
            $siteBal = (float) Db::name('users')->where('id', $uid)->value('balance', 0);
            $d['user_money']   = round($siteBal, 2);
            $d['site_balance'] = round($siteBal, 2);
            // 「每千字单价」展示本站加价后的单价（主站基础单价 + 降重商品加价金额/千字）
            if (isset($d['char_price'])) {
                $d['char_price'] = $this->markedUpCharPrice((float) $d['char_price']);
            }
            // 时长包/篇数包按该用户 agent_str 独立余额展示（主站 wallet 只返回 token 主人自身套餐，
            // 本站用户购买的资源包写入 jiangchong_agent_balance 行，必须改读 agent 口径）
            $bal = $this->agentBalance();
            if ($bal) {
                $d['time_active']      = !empty($bal['time_active']);
                $d['time_balance_sec'] = (int) ($bal['time_balance_sec'] ?? 0);
                $d['time_expire_time'] = (int) ($bal['time_expire_time'] ?? 0);
                // agent 行无小时窗口限额（购包时已置 0），展示「不限」
                $d['time_hour_limit']      = 0;
                $d['time_hour_used_chars'] = 0;
                $d['time_hour_reset_in']   = 0;
                // 篇数包余额同样按 agent 行展示
                $d['jc_article_remain']         = (int) ($bal['jc_article_remain'] ?? 0);
                $d['jc_article_max_chars']      = (int) ($bal['jc_article_max_chars'] ?? 0);
                $d['jc_article_max_chars_text'] = (string) ($bal['jc_article_max_chars_text'] ?? '不限');
            }
        }
        return JsonService::data($d);
    }

    /** 套餐额度明细（前端读 data 数组） */
    public function quota(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $r = $this->call('/openapi/jiangchong/quota', 'GET', [
            'agent_str' => $this->agentStr($uid),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取套餐额度失败');
        }
        $d = $r['data'] ?? [];
        return JsonService::data(is_array($d) ? $d : []);
    }

    /** 官方更新/公告（主站未暴露，返回空列表，保持前端宣传位不报错） */
    public function updates(): \think\response\Json
    {
        return JsonService::data([
            'list' => [],
            'count' => 0,
        ]);
    }

    /** 段落降重（实时改写）。余额=双余额扣费；时长包=主站套餐免金额扣费，本站不双扣 */
    public function adjc(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p   = $this->body();
        $txt = trim((string) ($p['sentence'] ?? ($p['content'] ?? '')));
        if ($txt === '') {
            return $this->fail('请填写需要降重的文本');
        }
        $method  = (string) ($p['pay_method'] ?? '');
        $payload = [
            'sentence'     => $txt,
            'rewrite_type' => (string) ($p['rewrite_type'] ?? 'aigc'),
            'platform'     => (string) ($p['platform'] ?? 'PaperPass'),
            'language'     => (string) ($p['language'] ?? 'zh'),
            'family'       => (string) ($p['family'] ?? ($p['strength'] ?? 'A')),
            'pay_method'   => ($method === 'time') ? 'time' : 'balance',
            'agent_str'    => $this->agentStr($uid),
        ];
        if ($method === 'time') {
            $r = $this->call('/openapi/jiangchong/adjc', 'POST', $payload);
            if (($r['code'] ?? 0) !== 1) {
                return $this->fail($r['msg'] ?? '降重失败，请稍后重试');
            }
            $data = is_array($r['data'] ?? null) ? $r['data'] : [];
            return JsonService::data($this->sanitizeUpstreamData($data, null));
        }
        return $this->dualCharge('/openapi/jiangchong/adjc', mb_strlen($txt), 'jiangchong', $payload);
    }

    /** 降重记录列表（前端读 data.lists 与 data.count） */
    public function records(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p      = $this->body();
        $page   = max(1, (int) ($p['pageNo'] ?? $p['page'] ?? 1));
        $size   = max(1, min(50, (int) ($p['pageSize'] ?? $p['page_size'] ?? 10)));
        $r = $this->call('/openapi/jiangchong/records', 'GET', [
            'page'      => $page,
            'page_size' => $size,
            'agent_str' => $this->agentStr($uid),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取降重记录失败');
        }
        $d    = $r['data'] ?? [];
        $list = $d['lists'] ?? $d['data'] ?? $d['list'] ?? [];
        $count = (int) ($d['count'] ?? $d['total'] ?? count($list));
        // 交付安全：主站记录 order_amount 为对接成本口径，改写为本站实付口径后再下发
        $list = $this->localizeRecordAmounts($uid, is_array($list) ? $list : []);
        // 主站不下发 *_preview / answer_word_count / process_time_text，前端折叠态依赖这些字段，此处统一补齐
        foreach ($list as &$it) {
            if (!is_array($it)) {
                continue;
            }
            $content = (string) ($it['content'] ?? '');
            $answer  = (string) ($it['answer'] ?? '');
            $it['content_preview']   = mb_strlen($content) > 120 ? mb_substr($content, 0, 120) . '…' : $content;
            $it['answer_preview']    = mb_strlen($answer) > 120 ? mb_substr($answer, 0, 120) . '…' : $answer;
            $it['answer_word_count'] = mb_strlen($answer);
            $ct = is_numeric($it['create_time'] ?? 0) ? (int) $it['create_time'] : (int) strtotime((string) ($it['create_time'] ?? ''));
            $ut = is_numeric($it['update_time'] ?? 0) ? (int) $it['update_time'] : (int) strtotime((string) ($it['update_time'] ?? ''));
            $sec = ($ut > 0 && $ct > 0 && $ut >= $ct) ? $ut - $ct : 0;
            $it['process_time_text'] = $sec >= 60
                ? sprintf('%d分%02d秒', intdiv($sec, 60), $sec % 60)
                : ($sec . '秒');
        }
        unset($it);
        return JsonService::data([
            'lists' => $list,
            'count' => $count,
        ]);
    }

    /**
     * 将主站记录条目的 order_amount（对接成本口径）改写为本站用户实付口径：
     * 1) 优先取本站流水（related_id=order_sn 的 *_deduct 扣费）实扣金额；
     * 2) 无流水的历史记录按「本站千字单价 / 主站千字单价」比例换算；
     * 3) 仍无法确定时置 0（前端不展示金额），宁可少展示也不泄露成本价。
     */
    private function localizeRecordAmounts(int $uid, array $list): array
    {
        if (!$list) {
            return $list;
        }
        $sns = [];
        foreach ($list as $it) {
            if (is_array($it) && (string) ($it['order_sn'] ?? '') !== '') {
                $sns[] = (string) $it['order_sn'];
            }
        }
        $paidMap = [];
        if ($sns) {
            try {
                $logs = Db::name('balance_logs')
                    ->where('user_id', $uid)
                    ->whereIn('related_id', $sns)
                    ->where('change_amount', '<', 0)
                    ->whereIn('change_type', ['jiangchong_deduct', 'check_deduct'])
                    ->order('id', 'asc')
                    ->field('related_id,change_amount')
                    ->select()
                    ->toArray();
                foreach ($logs as $lg) {
                    $rid = (string) ($lg['related_id'] ?? '');
                    if ($rid !== '' && !isset($paidMap[$rid])) {
                        $paidMap[$rid] = round(abs((float) $lg['change_amount']), 2);
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        $localPrice = $this->charPrice();      // 本站加价后千字单价
        $basePrice  = $this->baseCharPrice();  // 主站千字单价（成本口径）
        foreach ($list as &$it) {
            if (!is_array($it)) {
                continue;
            }
            $sn = (string) ($it['order_sn'] ?? '');
            if ($sn !== '' && isset($paidMap[$sn])) {
                $it['order_amount'] = $paidMap[$sn];
                continue;
            }
            $mainAmt = (float) ($it['order_amount'] ?? 0);
            if ($mainAmt <= 0) {
                continue;
            }
            if ($localPrice > 0 && $basePrice > 0) {
                $it['order_amount'] = round($mainAmt * $localPrice / $basePrice, 2);
            } else {
                $it['order_amount'] = 0;
            }
        }
        unset($it);
        return $list;
    }

    // ==================== 文档降重（check） ====================

    /** 获取 OSS 直传签名（前端用 put_url / key / content_type） */
    public function presign(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();
        $r = $this->call('/openapi/check/presign', 'GET', [
            'ext' => (string) ($p['ext'] ?? 'docx'),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取直传凭证失败');
        }
        $d = $r['data'] ?? [];
        return JsonService::data([
            'put_url'       => (string) ($d['put_url'] ?? ''),
            'upload_method' => (string) ($d['upload_method'] ?? 'PUT'),
            'content_type'  => (string) ($d['content_type'] ?? 'application/octet-stream'),
            'download_url'  => (string) ($d['download_url'] ?? ''),
            'key'           => (string) ($d['key'] ?? ''),
            'expires_at'    => (int) ($d['expires_at'] ?? 0),
        ]);
    }

    /** 字数统计（对已上传文档统计） */
    public function wordcount(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p      = $this->body();
        $ossKey = trim((string) ($p['oss_key'] ?? ''));
        if ($ossKey === '') {
            return $this->fail('缺少文件标识');
        }
        $r = $this->call('/openapi/check/wordcount', 'POST', ['oss_key' => $ossKey]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '字数统计失败');
        }
        $d = $r['data'] ?? [];
        return JsonService::data([
            'wordcount' => (int) ($d['wordcount'] ?? $d['wordCount'] ?? 0),
        ]);
    }

    /** 文档降重订单列表（orders/jiangchong.vue，agent_str 隔离；剥离内部渠道字段） */
    public function orderList(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();
        $r = $this->call('/openapi/check/orderList', 'GET', [
            'status'    => (string) ($p['status'] ?? 'all'),
            'keyword'   => (string) ($p['keyword'] ?? ''),
            'page'      => max(1, (int) ($p['page'] ?? 1)),
            'page_size' => max(1, min(50, (int) ($p['page_size'] ?? 10))),
            'agent_str' => $this->agentStr($uid),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取订单失败');
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        if (!empty($d['list']) && is_array($d['list'])) {
            foreach ($d['list'] as &$row) {
                unset($row['agent_str'], $row['agent_amount']);
            }
            unset($row);
        }
        return JsonService::data($d);
    }

    /** 创建文档降重订单（映射主站 /openapi/check/create，双余额扣费） */
    public function createJcorder(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p      = $this->body();
        $ossKey = trim((string) ($p['oss_key'] ?? ''));
        $wc     = (int) ($p['word_count'] ?? 0);
        if ($ossKey === '' || $wc <= 0) {
            return $this->fail('请先上传文档并完成字数统计');
        }
        $method  = (string) ($p['pay_method'] ?? '');
        $payload = [
            'oss_key'    => $ossKey,
            'word_count' => $wc,
            'type'       => (string) ($p['type'] ?? 'aigc'),
            'platform'   => (string) ($p['platform'] ?? 'PaperPass'),
            'family'     => (string) ($p['family'] ?? ($p['strength'] ?? 'A')),
            'title'      => (string) ($p['title'] ?? '论文降重'),
            'pay_method' => ($method === 'time') ? 'time' : 'balance',
            'agent_str'  => $this->agentStr($uid),
        ];
        if ($method === 'time') {
            $r = $this->call('/openapi/check/create', 'POST', $payload);
            if (($r['code'] ?? 0) !== 1) {
                return $this->fail($r['msg'] ?? '建单失败，请稍后重试');
            }
            $data = is_array($r['data'] ?? null) ? $r['data'] : [];
            // 时长包抵扣路径：本站实收 0（余额未扣），仍记录订单行供后台订单列表使用
            $this->insertCheckOrder($uid, (string)($data['order_sn'] ?? ''), $payload, 0.00, 'time', $data);
            return JsonService::data($this->sanitizeUpstreamData($data, null));
        }
        $resp = $this->dualCharge('/openapi/check/create', $wc, 'check', $payload);
        // 余额路径：建单成功后记录订单行（本站实扣金额从流水反查）
        try {
            $arr = $resp->getData();
            $sn = (string)($arr['data']['order_sn'] ?? '');
            if ($sn !== '') {
                $paid = 0.0;
                $log = Db::name('balance_logs')
                    ->where('user_id', $uid)
                    ->where('related_id', $sn)
                    ->where('change_type', 'check_deduct')
                    ->where('change_amount', '<', 0)
                    ->order('id', 'asc')
                    ->find();
                if ($log) {
                    $paid = round(abs((float)$log['change_amount']), 2);
                }
                $this->insertCheckOrder($uid, $sn, $payload, $paid, 'balance', is_array($arr['data'] ?? null) ? $arr['data'] : []);
            }
        } catch (\Throwable $e) {
            // 写单失败不影响主流程
        }
        return $resp;
    }

    /** 文档降重订单写入本站订单表（后台订单列表数据源），重复幂等 */
    private function insertCheckOrder(int $uid, string $orderSn, array $p, float $amount, string $payMethod, array $d): void
    {
        if ($orderSn === '') {
            return;
        }
        try {
            $exist = Db::name('orders')->where('order_no', $orderSn)->find();
            if ($exist) {
                return;
            }
            Db::name('orders')->insert([
                'order_no'           => $orderSn,
                'product_id'         => 0,
                'product_code'       => 'check',
                'product_name'       => '文档降重（AIGC降重）',
                'product_data'       => json_encode([
                    'type'        => 'check',
                    'platform'    => (string)($p['platform'] ?? ''),
                    'family'      => (string)($p['family'] ?? ''),
                    'word_count'  => (int)($p['word_count'] ?? 0),
                    'title'       => (string)($p['title'] ?? '论文降重'),
                    'pay_method'  => $payMethod,
                    'main_amount' => (float)($d['billing']['amount'] ?? 0),
                ], JSON_UNESCAPED_UNICODE),
                'quantity'           => 1,
                'unit_price'         => $amount,
                'total_amount'       => $amount,
                'remark'             => '文档降重（openapi）',
                'status'             => 0,
                'pay_status'         => 1,
                'pay_time'           => date('Y-m-d H:i:s'),
                'user_id'            => $uid,
                'tokenapi_submitted' => 1,
                'tokenapi_response'  => json_encode($d, JSON_UNESCAPED_UNICODE),
                'ip_address'         => Request::ip(),
                'user_agent'         => Request::header('user-agent', ''),
                'create_time'        => date('Y-m-d H:i:s'),
                'update_time'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}