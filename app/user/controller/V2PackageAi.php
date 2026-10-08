<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * 套餐商城 openapi 桥接控制器（降重/AI检测时长包，双余额扣费）
 *
 * 前端 package-shop.vue 走本站 /api/package/lists 与 /api/package/createOrder，
 * 本控制器桥接到主站 /openapi/package/lists 与 /openapi/package/create（Bearer 出站）。
 *
 * 售卖范围：与参考站一致，仅售 jiangchong（降重）/ ai_check（AI检测）的时长包（unit=time）；
 * 篇数包本站无消费路径不下发（主站动态上下架自动跟随）。
 *
 * 计费模型（双扣费）：上游购买按套餐原价扣对接 token 预充（即时到账不可回滚）；
 * 本站按本地售价扣用户余额 = 上游套餐价 + 商品加价（ad_products.code 按业务线取
 * jc_package / aicheck_package 的 markup，未配置默认 0）。先本站余额预检 →
 * 上游购买成功后再扣本站余额并写流水。
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2PackageAi
{
    /** 可售业务线（type_code）→ 本站加价商品 code 映射 */
    private const TYPE_PRODUCT_MAP = [
        'jiangchong' => 'jc_package',
        'ai_check'   => 'aicheck_package',
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

    /** 登录守卫：未登录返回 authExpired 响应，已登录返回 null */
    private function guard()
    {
        if (!$this->uid()) {
            return JsonService::authExpired();
        }
        return null;
    }

    /** 主站预充余额（对接 token 主人在主站的 user_money） */
    private function mainPrepaid(): float
    {
        $r = $this->call('/openapi/user/package', 'GET', []);
        return (float) ($r['data']['user_money'] ?? 0);
    }

    /** 拉取主站套餐列表并过滤为可售时长包平铺列表（含本站售价） */
    private function sellableList(): array
    {
        $r = $this->call('/openapi/package/lists', 'GET', []);
        if (($r['code'] ?? 0) !== 1 || !is_array($r['data']['list'] ?? null)) {
            return [];
        }
        $sellable = [];
        foreach ($r['data']['list'] as $group) {
            if (!is_array($group)) {
                continue;
            }
            $typeCode = (string)($group['type_code'] ?? '');
            if (!isset(self::TYPE_PRODUCT_MAP[$typeCode])) {
                continue;
            }
            $markup = $this->productMarkup(self::TYPE_PRODUCT_MAP[$typeCode]);
            foreach ((array)($group['packages'] ?? []) as $pkg) {
                if (!is_array($pkg) || ($pkg['unit'] ?? '') !== 'time') {
                    continue;
                }
                $basePrice = (float)($pkg['price'] ?? 0);
                $sellable[] = [
                    'type_code'            => $typeCode,
                    'type_name'            => (string)($group['type_name'] ?? ''),
                    'package_id'           => (int)($pkg['id'] ?? 0),
                    'name'                 => (string)($pkg['name'] ?? ''),
                    'quantity'             => (int)($pkg['quantity'] ?? 0),
                    'unit'                 => 'time',
                    'duration_text'        => (string)($pkg['duration_text'] ?? ''),
                    'validity_text'        => (string)($pkg['validity_text'] ?? ''),
                    'max_chars_text'       => (string)($pkg['max_chars_text'] ?? ''),
                    'hour_char_limit_text' => (string)($pkg['hour_char_limit_text'] ?? ''),
                    'intro'                => (string)($pkg['intro'] ?? ''),
                    'is_recommend'         => (int)($pkg['is_recommend'] ?? 0),
                    'base_price'           => $basePrice, // 主站套餐原价（进价，主站预充 guard 口径）
                    'price'                => round($basePrice + $markup, 2),  // 本站售价（套餐价 + 商品加价）
                    'market_price'         => (float)($pkg['original_price'] ?? 0), // 市场参考价（划线展示）
                ];
            }
        }
        return $sellable;
    }

    /** 商品加价金额（ad_products.code 的 markup；未配置默认 0） */
    private function productMarkup(string $code): float
    {
        try {
            $m = Db::name('products')->where('code', $code)->value('markup', 0);
        } catch (\Throwable $e) {
            $m = 0;
        }
        return (float) ($m ?? 0);
    }

    // ==================== 套餐列表 ====================

    /** GET /api/package/lists → {list:[分组{type_id,type_name,type_desc,type_code,packages:[]}]}
     *  前端 package-shop.vue 模板为分组结构：pkg.id/price/original_price/unit_text/locked 等字段。
     *  本站无代理分级体系，不下发 my_agent/agent_price/tier_prices。 */
    public function lists(): \think\response\Json
    {
        $groups = [];
        foreach ($this->sellableList() as $item) {
            $typeCode = $item['type_code'];
            if (!isset($groups[$typeCode])) {
                $groups[$typeCode] = [
                    'type_id'   => $typeCode,
                    'type_code' => $typeCode,
                    'type_name' => $item['type_name'],
                    'type_desc' => '',
                    'packages'  => [],
                ];
            }
            $groups[$typeCode]['packages'][] = [
                'id'                   => $item['package_id'],
                'name'                 => $item['name'],
                'quantity'             => $item['quantity'],
                'unit'                 => 'time',
                'unit_text'            => '',
                'duration_text'        => $item['duration_text'],
                'validity_text'        => $item['validity_text'],
                'max_chars_text'       => $item['max_chars_text'],
                'hour_char_limit_text' => $item['hour_char_limit_text'],
                'intro'                => $item['intro'],
                'is_recommend'         => $item['is_recommend'],
                'price'                => $item['price'],           // 本站售价（标准价）
                'original_price'       => $item['market_price'],    // 市场参考价（划线）
                'model_type'           => 0,
                'model_type_text'      => '',
                'locked'               => false,
                'lock_reason'          => '',
            ];
        }
        return JsonService::data([
            'list' => array_values($groups),
        ]);
    }

    // ==================== 购买（双余额扣费） ====================

    /**
     * POST /api/package/createOrder {package_id}
     * 流程：实时列表定位套餐（不在列表内一律拒绝）→ 主站预充 guard → 本站余额预检 →
     *       上游购买（扣预充，agent_str 发放到独立余额行，即时到账）→ 成功后扣本站余额 + 流水。
     */
    public function createOrder(): \think\response\Json
    {
        $uid = $this->uid();
        if (!$uid) {
            return JsonService::authExpired();
        }
        $p = $this->body();
        $packageId = (int)($p['package_id'] ?? 0);
        if ($packageId <= 0) {
            return $this->fail('参数错误：package_id 不能为空');
        }

        // 1) 实时列表定位（保证与主站上下架/改价同步；不在可售列表内拒绝）
        $pkg = null;
        foreach ($this->sellableList() as $item) {
            if ($item['package_id'] === $packageId) {
                $pkg = $item;
                break;
            }
        }
        if ($pkg === null) {
            return $this->fail('套餐不存在或未开放购买');
        }
        $sellPrice = (float)$pkg['price'];
        $basePrice = (float)($pkg['base_price'] ?? 0);

        // 2) 主站预充 guard（上游余额支付即时到账；上游只扣套餐原价，加价部分不占主站预充）
        if ($basePrice > 0 && $this->mainPrepaid() + 0.001 < $basePrice) {
            return $this->fail('对接方预充值余额不足，暂无法购买，请联系平台充值');
        }

        // 3) 本站余额预检（上游成功后不可回滚，必须先确认本站用户付得起）
        $user = Db::name('users')->where('id', $uid)->find();
        if (!$user) {
            return $this->fail('用户不存在');
        }
        $bal = (float)($user['balance'] ?? 0);
        if ($sellPrice > 0 && $bal + 0.001 < $sellPrice) {
            return $this->fail('余额不足，请先充值');
        }

        // 4) 上游购买（成功后套餐发放到该用户 agent_str 独立余额行）
        $r = $this->call('/openapi/package/create', 'POST', [
            'package_id' => $packageId,
            'agent_str'  => $this->agentStr($uid),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '购买失败，请稍后重试');
        }
        // 清理套餐余额页的短缓存，确保购买后立即展示最新到账
        Cache::delete('pkg_balance_' . $this->agentStr($uid));

        // 5) 上游成功后扣本站余额 + 流水（非阻塞；失败不影响发放结果）
        $after = $bal;
        if ($sellPrice > 0) {
            $after = round($bal - $sellPrice, 2);
            try {
                Db::name('users')->where('id', $uid)->update([
                    'balance'     => $after,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                Db::name('balance_logs')->insert([
                    'user_id'             => $uid,
                    'change_type'         => 'package_deduct',
                    'change_amount'       => -$sellPrice,
                    'bonus_amount'        => 0,
                    'total_change_amount' => -$sellPrice,
                    'before_balance'      => $bal,
                    'after_balance'       => $after,
                    'related_id'          => (string)($r['data']['order_no'] ?? ''),
                    'related_type'        => 'package',
                    'remark'              => '套餐购买-' . (string)($pkg['name'] ?? ''),
                    'operator_type'       => 'system',
                    'ip_address'          => Request::ip(),
                    'create_time'         => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return JsonService::data([
            'order_no'   => (string)($r['data']['order_no'] ?? ''),
            'name'       => (string)($r['data']['name'] ?? ($pkg['name'] ?? '')),
            'price'      => $sellPrice,
            'left_money' => $after,
        ]);
    }
}
