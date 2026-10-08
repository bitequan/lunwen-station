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
 * 文档 AI 检测（ai-check.vue）openapi 桥接控制器
 *
 * 前端走本站 /api/ai_check/*（+ token Header），本控制器桥接到主站
 * /openapi/ai_check/*（Bearer 出站，docking.api_url/api_token）。
 *
 * 计费模型（对齐参考站口径）：
 *  - 时长包优先：agent_str 独立 ai_check 时长包有效 → 上游 pay_method=time 抵扣，本站不扣余额；
 *  - 余额路径：双余额扣费——主站预充 guard（基础单价估算）+ 本站余额预扣（加价后单价），
 *    上游成功后按实际送检字数（total_chars）多退少补，封顶扣前余额，写本站流水。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2AiCheckAi
{
    /** 平台效果报告对照（开放测试统计，静态参考数据；上游 openapi 不下发 report 字段） */
    private const PLATFORM_REPORTS = [
        'paperpass' => ['sample' => '系统开放测试时，与平台判定比对 803 段',
            'rows' => [
                ['label' => 'AI → AI',     'n' => 696, 'total' => 703, 'pct' => 99.0],
                ['label' => 'AI → 人工',   'n' => 7,   'total' => 703, 'pct' => 1.0],
                ['label' => '人工 → 人工', 'n' => 84,  'total' => 100, 'pct' => 84.0],
                ['label' => '人工 → AI',   'n' => 16,  'total' => 100, 'pct' => 16.0],
            ],
            'total' => ['n' => 780, 'total' => 803, 'pct' => 97.14],
        ],
        'weipu' => ['sample' => '系统开放测试时，与平台判定比对 42,159 句',
            'rows' => [
                ['label' => 'AI → AI',     'n' => 10620, 'total' => 12772, 'pct' => 83.2],
                ['label' => 'AI → 人工',   'n' => 2152,  'total' => 12772, 'pct' => 16.8],
                ['label' => '人工 → 人工', 'n' => 27190, 'total' => 29387, 'pct' => 92.5],
                ['label' => '人工 → AI',   'n' => 2197,  'total' => 29387, 'pct' => 7.5],
            ],
            'total' => ['n' => 37810, 'total' => 42159, 'pct' => 89.68],
        ],
        'daya' => ['sample' => '系统开放测试时，与平台判定比对 746 段',
            'rows' => [
                ['label' => 'AI → AI',     'n' => 615, 'total' => 629, 'pct' => 97.8],
                ['label' => 'AI → 人工',   'n' => 14,  'total' => 629, 'pct' => 2.2],
                ['label' => '人工 → 人工', 'n' => 93,  'total' => 117, 'pct' => 79.5],
                ['label' => '人工 → AI',   'n' => 24,  'total' => 117, 'pct' => 20.5],
            ],
            'total' => ['n' => 708, 'total' => 746, 'pct' => 94.91],
        ],
        'gzd' => ['sample' => '系统开放测试时，与平台判定比对 991 段',
            'rows' => [
                ['label' => 'AI → AI',     'n' => 697, 'total' => 716, 'pct' => 97.4],
                ['label' => 'AI → 人工',   'n' => 19,  'total' => 716, 'pct' => 2.7],
                ['label' => '人工 → 人工', 'n' => 235, 'total' => 275, 'pct' => 85.5],
                ['label' => '人工 → AI',   'n' => 40,  'total' => 275, 'pct' => 14.5],
            ],
            'total' => ['n' => 932, 'total' => 991, 'pct' => 94.05],
        ],
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

    /** AI 检测商品每千字加价金额（ad_products.code=aicheck 的 markup，元/千字） */
    private function markup(): float
    {
        try {
            $m = Db::name('products')
                ->where('code', 'aicheck')
                ->value('markup', 0);
        } catch (\Throwable $e) {
            $m = 0;
        }
        return (float) ($m ?? 0);
    }

    /**
     * 本站加价后的千字符单价：主站基础单价 + AI 检测商品加价金额（元/千字）。
     * 基础单价≤0（免计费模式）时不加价，保持 0。
     */
    private function markedUpCharPrice(float $base): float
    {
        if ($base <= 0) {
            return 0.0;
        }
        return round($base + $this->markup(), 2);
    }

    /** 主站基础千字符单价（不加价；0=免计费模式）。来源 /openapi/ai_check/wallet */
    private function baseCharPrice(): float
    {
        $r = $this->call('/openapi/ai_check/wallet', 'GET', []);
        if (($r['code'] ?? 0) === 1 && isset($r['data']['char_price'])) {
            return (float) $r['data']['char_price'];
        }
        return 0.0;
    }

    /** 与主站计费口径一致：ceil(chars/1000) × 千字符单价 */
    private function estimateAmount(int $chars, float $charPrice): float
    {
        if ($chars <= 0 || $charPrice <= 0) {
            return 0.0;
        }
        $units  = (int) ceil($chars / 1000);
        $amount = bcmul((string) $units, (string) $charPrice, 2);
        return round((float) $amount, 2);
    }

    /**
     * agent_str 独立套餐余额（AI 检测口径：ai_check_time_* 分列，与降重时长包互不影响）。
     * 注意：上游 openapi action 必须驼峰形式（agent_balance 下划线形式 404）。
     */
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

    // ==================== 接口 ====================

    /** 检测平台列表（上游直通 + 本站补齐效果报告对照 report，前端「效果报告」表依赖渲染） */
    public function platforms(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $r = $this->call('/openapi/ai_check/platforms', 'GET');
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取检测平台失败');
        }
        $list = is_array($r['data']['platforms'] ?? null) ? $r['data']['platforms'] : [];
        foreach ($list as &$p) {
            $k = (string) ($p['key'] ?? '');
            if (isset(self::PLATFORM_REPORTS[$k])) {
                $p['report'] = self::PLATFORM_REPORTS[$k];
            }
        }
        unset($p);
        return JsonService::data(['platforms' => $list]);
    }

    /**
     * 计费钱包（本站口径）：
     * 账户余额=本站用户余额（本地扣费）/ 千字价=本站加价后单价（主站基础价+aicheck 加价）；
     * 时长包=agent_str 独立余额（ai_check_time_*，与降重时长包互不影响，套餐中心购买）。
     */
    public function wallet(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $base  = $this->baseCharPrice();
        $price = $this->markedUpCharPrice($base);
        $bal   = $this->agentBalance();
        $siteBal = (float) Db::name('users')->where('id', $uid)->value('balance', 0);
        return JsonService::data([
            'mode'                 => $price > 0 ? 'paid' : 'free',
            'display_text'         => $price > 0 ? '按单价计费' : '免计费模式',
            'user_money'           => round($siteBal, 2),
            'site_balance'         => round($siteBal, 2),
            'char_price'           => $price,
            'char_balance'         => 0,
            'time_active'          => !empty($bal['ai_check_time_active']),
            'time_balance_sec'     => (int) ($bal['ai_check_time_balance_sec'] ?? 0),
            'time_expire_time'     => (int) ($bal['ai_check_time_expire_time'] ?? 0),
            'time_hour_limit'      => 0,
            'time_hour_used_chars' => 0,
            'time_hour_reset_in'   => 0,
            'free_claim'           => null, // 本站未开放免费额度活动
        ]);
    }

    /** 免费额度领取（本站未开放该活动） */
    public function claimFree(): \think\response\Json
    {
        return $this->fail('免费额度活动未开启');
    }

    /** OSS 直传签名（上游 presign 直通；put_url 由浏览器直传阿里云 OSS，不经本站服务器） */
    public function getUploadSignature(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p   = $this->body();
        $ext = (string) ($p['ext'] ?? 'docx');
        if (!in_array($ext, ['docx', 'doc', 'txt'], true)) {
            $ext = 'docx';
        }
        $r = $this->call('/openapi/ai_check/presign', 'POST', ['ext' => $ext]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取上传签名失败');
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        return JsonService::data([
            'put_url'       => (string) ($d['put_url'] ?? ''),
            'upload_method' => (string) ($d['upload_method'] ?? 'PUT'),
            'content_type'  => (string) ($d['content_type'] ?? 'application/octet-stream'),
            'download_url'  => (string) ($d['download_url'] ?? ''),
            'key'           => (string) ($d['key'] ?? ''),
            'expires_at'    => (int) ($d['expires_at'] ?? 0),
        ]);
    }

    /**
     * 提交 AI 检测（同步返回整体 AI 率与逐段明细）。
     * 支付方式尊重用户显式选择（前端三态 time/char/balance）：
     *   time=时长包抵扣（无效时回退）、char=字符包抵扣（agent 口径，本站不扣余额）、balance=余额双扣；
     * 未传时保持时长包优先的历史行为。
     * 抵扣路径：主站预充 guard → 上游成功即返回，本站不扣余额；
     * 余额路径：主站预充 guard → 本站预扣 → 上游成功按实际送检字数多退少补 → 记流水。
     */
    public function detect(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p        = $this->body();
        $text     = trim((string) ($p['text'] ?? ''));
        $fileUrl  = trim((string) ($p['file_url'] ?? ''));
        if ($text === '' && $fileUrl === '') {
            return $this->fail('请上传文档或粘贴待检测文本');
        }

        $before = (float) Db::name('users')->where('id', $uid)->value('balance', 0);

        // 支付方式解析：用户显式选择优先，缺省时长包优先（与降重时长包互不影响）
        $bal        = $this->agentBalance();
        $timeActive = !empty($bal['ai_check_time_active']);
        $requested  = trim((string) ($p['pay_method'] ?? ''));
        if ($requested === 'time') {
            $useTime = $timeActive; // 选时长包但已失效 → 回退余额路径
        } elseif ($requested === 'balance' || $requested === 'char') {
            $useTime = false;
        } else {
            $useTime = $timeActive; // 未传/未知值：历史行为
        }
        // 字符包路径：agent 口径字符余额由主站抵扣，本站不扣余额
        $useChar = !$useTime && $requested === 'char';

        $upstreamPay = $useTime ? 'time' : ($useChar ? 'char' : 'balance');
        $payload = [
            'platform_keys' => (string) ($p['platform_keys'] ?? ''),
            'pay_method'    => $upstreamPay,
            'agent_str'     => $this->agentStr($uid),
        ];
        if ($text !== '') {
            $payload['text'] = $text;
        } else {
            $payload['file_url'] = $fileUrl;
        }

        if ($useTime || $useChar) {
            $r = $this->call('/openapi/ai_check/detect', 'POST', $payload);
            if (($r['code'] ?? 0) !== 1) {
                return $this->fail($r['msg'] ?? '检测失败，请稍后重试');
            }
            $d = is_array($r['data'] ?? null) ? $r['data'] : [];
            // 抵扣路径：不扣本站余额，billing 标注时长包/字符包抵扣
            $d['billing'] = [
                'charged'     => false,
                'amount'      => 0,
                'pay_method'  => $upstreamPay,
                'left_money'  => round($before, 2),
            ];
            return JsonService::data($d);
        }

        // ===== 余额路径：双余额扣费 =====
        // 字数预估：文本模式取全文长度；文档模式本地不解析正文按最低计费单位预检，实际以上游送检字数为准
        $estChars  = $text !== '' ? mb_strlen($text) : 1000;
        $price     = $this->markedUpCharPrice($this->baseCharPrice()); // 本站加价后单价（应收）
        $estimated = $this->estimateAmount($estChars, $price);
        $baseEst   = $this->estimateAmount($estChars, $this->baseCharPrice()); // 主站成本估算（不加价）

        // 主站预充 guard：只需覆盖主站成本（基础单价），加价部分不占用主站预充
        if ($baseEst > 0 && $this->mainPrepaid() + 0.001 < $baseEst) {
            return $this->fail('对接方预充值余额不足，暂无法检测，请联系平台充值');
        }

        // 本站余额 guard + 预扣
        $after = null;
        if ($estimated > 0) {
            if ($before + 0.001 < $estimated) {
                return $this->fail('余额不足，请先充值');
            }
            $after = round($before - $estimated, 2);
            Db::name('users')->where('id', $uid)->update([
                'balance'     => $after,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 调主站（balance 方式 → 扣 token 预充 user_money）
        $r = $this->call('/openapi/ai_check/detect', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            if ($after !== null) {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $before,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            return $this->fail($r['msg'] ?? '检测失败，请稍后重试');
        }

        $d = is_array($r['data'] ?? null) ? $r['data'] : [];

        // 结算：实际扣费字数以送检字数为准（上游分段后仅送检段计费，过短段跳过），
        // 文档模式实际送检字数可能远超预检预估 → 封顶扣前余额，避免扣成负数
        $charge = 0.0;
        if ($estimated > 0) {
            $billChars = (int) ($d['total_chars'] ?? 0);
            if ($billChars <= 0) {
                $billChars = $estChars;
            }
            $charge = $this->estimateAmount($billChars, $price);
            if ($charge > $before) {
                $charge = $before;
            }
            $final = round($before - $charge, 2);
            if (abs((float) $after - $final) >= 0.01) {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $final,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($charge > 0) {
                try {
                    Db::name('balance_logs')->insert([
                        'user_id'             => $uid,
                        'change_type'         => 'aicheck_deduct',
                        'change_amount'       => -$charge,
                        'bonus_amount'        => 0,
                        'total_change_amount' => -$charge,
                        'before_balance'      => $before,
                        'after_balance'       => $final,
                        'related_id'          => '',
                        'related_type'        => 'ai_check',
                        'remark'              => 'AI检测扣费（送检 ' . $billChars . ' 字）',
                        'operator_type'       => 'system',
                        'ip_address'          => Request::ip(),
                        'create_time'         => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                }
            }
        }

        // 本地计费口径覆盖上游成本计费（不透传上游成本价）
        $d['billing'] = [
            'charged'     => $charge > 0,
            'amount'      => round($charge, 2),
            'pay_method'  => 'balance',
            'left_money'  => round($before - $charge, 2),
        ];
        return JsonService::data($d);
    }
}
