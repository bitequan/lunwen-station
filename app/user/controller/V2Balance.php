<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use think\facade\Cache;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * 用户端余额/工作台接口（api-doc「账号与余额」组）
 *
 * 接口路径：
 *   /api/pc/dashboard        工作台汇总（余额/累计充值/累计消费/近7日趋势）
 *   /api/pc/userBalance      当前余额（写作/大纲页扣费前查询）
 *   /api/pc/accountLogs      余额变动流水
 *   /api/package/balance     套餐余额页
 * 统一返回格式：{ code, show, msg, data }；均需请求头 token
 *
 * 数据源：ad_users.balance（余额）、ad_balance_logs（流水）
 */
class V2Balance
{
    /** 当前登录用户 ID；未登录返回 0 */
    private function uid(): int
    {
        return UserTokenService::getUserId(UserTokenService::readRequestToken());
    }

    /**
     * 工作台汇总（user.vue /stats + 近7日趋势）
     */
    public function dashboard(): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        $user = Db::name('users')->where('id', $userId)->find();

        // 汇总统计：SQL 聚合代替全表拉取，流水增长不再拖慢接口
        $agg = Db::name('balance_logs')
            ->where('user_id', $userId)
            ->fieldRaw("SUM(CASE WHEN change_amount > 0 AND LOWER(change_type) LIKE '%recharge%' THEN change_amount ELSE 0 END) AS total_recharge,
                SUM(CASE WHEN change_amount > 0 AND LOWER(change_type) LIKE '%recharge%' THEN 1 ELSE 0 END) AS recharge_count,
                SUM(CASE WHEN change_amount > 0 AND LOWER(change_type) LIKE '%recharge%' THEN IFNULL(bonus_amount, 0) ELSE 0 END) AS total_gift,
                SUM(CASE WHEN change_amount < 0 THEN -change_amount ELSE 0 END) AS total_consume,
                SUM(CASE WHEN change_amount < 0 THEN 1 ELSE 0 END) AS consume_count")
            ->find() ?: [];

        $totalRecharge = (float) ($agg['total_recharge'] ?? 0);
        $rechargeCount = (int) ($agg['recharge_count'] ?? 0);
        $totalConsume  = (float) ($agg['total_consume'] ?? 0);
        $consumeCount  = (int) ($agg['consume_count'] ?? 0);
        $totalGift     = (float) ($agg['total_gift'] ?? 0);

        // 近 7 日趋势（充值 + 消费），按日期分组：仅取近 7 天流水
        $logs = Db::name('balance_logs')
            ->where('user_id', $userId)
            ->where('create_time', '>=', date('Y-m-d 00:00:00', strtotime('-6 day')))
            ->field('change_amount,change_type,create_time')
            ->select()
            ->toArray();
        $trendMap = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('m-d', strtotime("-$i day"));
            $trendMap[$d] = ['date' => $d, 'recharge' => 0, 'consume' => 0];
        }
        foreach ($logs as $log) {
            $d       = date('m-d', strtotime((string) $log['create_time']));
            $amount  = (float) $log['change_amount'];
            $type    = strtolower((string) ($log['change_type'] ?? ''));
            if (!isset($trendMap[$d])) continue;
            if ($amount > 0 && strpos($type, 'recharge') !== false) {
                $trendMap[$d]['recharge'] += $amount;
            } elseif ($amount < 0) {
                $trendMap[$d]['consume'] += abs($amount);
            }
        }
        ksort($trendMap);

        // 我的产出统计（总览页统计卡，口径与订单列表一致）
        // 已支付订单数：product_code 落库口径为业务类型（'paper'），gjlw 为计价商品 code（历史数据兼容）
        $paperOrderCount = Db::name('orders')
            ->where('user_id', $userId)
            ->whereIn('product_code', ['paper', 'gjlw'])
            ->where('pay_status', 1)
            ->count();
        $pptOrderCount = Db::name('orders')
            ->where('user_id', $userId)
            ->where('product_code', 'ppt')
            ->where('pay_status', 1)
            ->count();
        // 私有模板数：主站 /openapi/template/list（agent_str 隔离），仅取 total
        // 60 秒短缓存：上游接口 TTFB≈0.7s，避免每次进工作台都串行等待；失败不阻塞
        $templateCount = 0;
        $tplCacheKey = 'pkg_tpl_count_' . AgentIdHelper::build($userId);
        $cachedCount = Cache::get($tplCacheKey);
        if ($cachedCount === null) {
            try {
                $tr = (new ApiClientService())->get('/openapi/template/list', [
                    'page'      => 1,
                    'page_size' => 1,
                    'agent_str' => (string) AgentIdHelper::build($userId),
                ]);
                if (($tr['code'] ?? 0) === 1) {
                    $templateCount = (int) ($tr['data']['total'] ?? 0);
                }
            } catch (\Throwable $e) {
            }
            Cache::set($tplCacheKey, $templateCount, 60);
        } else {
            $templateCount = (int) $cachedCount;
        }

        return JsonService::data([
            'user_money'      => round((float) ($user['balance'] ?? 0), 2),
            'total_recharge'  => round($totalRecharge, 2),
            'recharge_count'  => $rechargeCount,
            'total_consume'   => round($totalConsume, 2),
            'consume_count'   => $consumeCount,
            'total_gift'      => round($totalGift, 2),
            'trend_7d'        => array_values($trendMap),
            'consume_dist_30d'=> [],
            'my_paper_order_count' => (int) $paperOrderCount,
            'my_ppt_order_count'   => (int) $pptOrderCount,
            'my_template_count'    => $templateCount,
            'my_fav_count'         => 0, // 收藏功能已移除，字段保留兼容前端
        ]);
    }

    /**
     * 当前余额（OutlineEditor 等扣费前查询）
     */
    public function userBalance(): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        $balance = (float) Db::name('users')->where('id', $userId)->value('balance', 0);
        return JsonService::data(['user_money' => round($balance, 2)]);
    }

    /**
     * 套餐余额页
     * 余额 = 本站用户自有余额（ad_users.balance，主站预充严禁透传）；
     * items = 该用户 agent_str 独立套餐余额（/openapi/jiangchong/agentBalance 单次上游调用，
     *         含降重时长包 / AI检测时长包 / 篇数包），与主站 token 主人套餐互不占用。
     */
    public function packageBalance(): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        $balance = (float) Db::name('users')->where('id', $userId)->value('balance', 0);

        // 单次上游调用拉取 agent 维度套餐余额（15 秒短缓存加速重复访问；失败降级为空列表，不阻塞余额展示）
        $cacheKey = 'pkg_balance_' . AgentIdHelper::build($userId);
        $bal = Cache::get($cacheKey);
        if (!is_array($bal)) {
            try {
                $r = (new ApiClientService())->get('/openapi/jiangchong/agentBalance', [
                    'agent_str' => (string) AgentIdHelper::build($userId),
                ]);
                $bal = (($r['code'] ?? 0) === 1 && is_array($r['data'] ?? null)) ? $r['data'] : [];
            } catch (\Throwable $e) {
                $bal = [];
            }
            Cache::set($cacheKey, $bal, 15);
        }

        $items = [];
        if ($bal) {
            // 降重降AI资源包（时长包，通用列 time_expire_time）
            $items[] = $this->timeItem('jc', '降重降AI资源包',
                !empty($bal['time_active']),
                (int) ($bal['time_expire_time'] ?? 0),
                (int) ($bal['time_balance_sec'] ?? 0),
                '时长包 · 有效期内降重/降AI不限次');
            // AI检测资源包（独立时长包列 ai_check_time_expire_time）
            $items[] = $this->timeItem('aicheck', 'AI检测资源包',
                !empty($bal['ai_check_time_active']),
                (int) ($bal['ai_check_time_expire_time'] ?? 0),
                (int) ($bal['ai_check_time_balance_sec'] ?? 0),
                '时长包 · 有效期内AI检测不限次');
            // 篇数包（本站不售卖，仅当名下有剩余时展示）
            $remain = (int) ($bal['jc_article_remain'] ?? 0);
            if ($remain > 0) {
                $items[] = [
                    'key'         => 'jc_article',
                    'name'        => '降重篇数包',
                    'active'      => true,
                    'expired'     => false,
                    'text'        => $remain . ' 篇',
                    'sub'         => (string) ($bal['jc_article_max_chars_text'] ?? '不限'),
                    'expire_text' => '',
                ];
            }
        }

        return JsonService::data([
            'user_money' => round($balance, 2),
            'items'      => $items,
        ]);
    }

    /** 时长包余额卡片数据（可用/已到期/未开通 三态） */
    private function timeItem(string $key, string $name, bool $active, int $expireTs, int $remainSec, string $sub): array
    {
        $expired = !$active && $expireTs > 0;
        return [
            'key'         => $key,
            'name'        => $name,
            'active'      => $active,
            'expired'     => $expired,
            'text'        => $active ? ('剩余 ' . $this->fmtDuration($remainSec)) : ($expired ? '已到期' : '未开通'),
            'sub'         => $sub,
            'expire_text' => $expireTs > 0 ? (($expired ? '已于 ' : '到期 ') . date('Y-m-d H:i', $expireTs)) : '',
        ];
    }

    /** 秒数 → 「X天X小时X分钟」 */
    private function fmtDuration(int $sec): string
    {
        if ($sec <= 0) {
            return '0分钟';
        }
        $d = intdiv($sec, 86400);
        $h = intdiv($sec % 86400, 3600);
        $m = intdiv($sec % 3600, 60);
        $out = '';
        if ($d > 0) {
            $out .= $d . '天';
        }
        if ($h > 0) {
            $out .= $h . '小时';
        }
        if ($m > 0 || $out === '') {
            $out .= max($m, 1) . '分钟';
        }
        return $out;
    }

    /**
     * 余额变动流水（user.vue 资金明细）
     * filter: all|income|expense|recharge|consume
     */
    public function accountLogs(): \think\response\Json
    {
        $userId = $this->uid();
        if (!$userId) {
            return JsonService::authExpired();
        }
        $pageNo   = max(1, (int) input('page_no', 1));
        $pageSize = min(50, max(1, (int) input('page_size', 10)));
        $filter   = (string) input('filter', 'all');

        $query = Db::name('balance_logs')->where('user_id', $userId);
        if ($filter === 'income') {
            $query->where('change_amount', '>', 0);
        } elseif ($filter === 'expense') {
            $query->where('change_amount', '<', 0);
        } elseif ($filter === 'recharge') {
            $query->whereLike('change_type', '%recharge%');
        } elseif ($filter === 'consume') {
            // 消费类：通用 consume 前缀 + 各业务扣费（*_deduct）均视为本站消费
            $query->where(function ($q) {
                $q->whereLike('change_type', '%consume%')
                  ->whereOr('change_type', 'like', '%deduct%');
            });
        }

        $total = (clone $query)->count();
        $rows  = (clone $query)
            ->order('id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $amount = (float) $row['change_amount'];
            $ct     = (string) ($row['change_type'] ?? '');
            $time   = $row['create_time'] ?? '';
            $list[] = [
                'id'          => (int) $row['id'],
                'direction'   => $amount >= 0 ? 'income' : 'expense',
                'amount'      => round(abs($amount), 2),
                'before'      => round((float) ($row['before_balance'] ?? 0), 2),
                'after'       => round((float) ($row['after_balance'] ?? 0), 2),
                'change_type' => $ct,
                'type'        => $this->logTypeName($ct),
                'sn'          => (string) ($row['related_id'] ?? ''),
                'time'        => $this->fmtTime($time),
                'remark'      => $row['remark'] ?? '',
                'status'      => '成功',
                'create_time' => $this->fmtTime($time),
                'ts'          => $time ? strtotime((string) $time) : 0,
            ];
        }

        return JsonService::data([
            'list'       => $list,
            'total'      => $total,
            'count'      => count($list),
            'page_no'    => $pageNo,
            'page_size'  => $pageSize,
            'page_total' => max(1, (int) ceil($total / max(1, $pageSize))),
        ]);
    }

    /**
     * 把本站流水 change_type 映射为用户友好的中文类型名
     */
    private function logTypeName(string $ct): string
    {
        $c = strtolower($ct);
        if (strpos($c, 'recharge') !== false || $c === 'charge') {
            return '充值';
        }
        if (strpos($c, 'refund') !== false || strpos($c, 'withdraw') !== false || strpos($c, 'cash') !== false) {
            return '退款/提现';
        }
        if (strpos($c, 'jiangchong') !== false || strpos($c, 'reduceweight') !== false) {
            return '段落降重';
        }
        if (strpos($c, 'check_deduct') !== false || strpos($c, 'doc_deduct') !== false) {
            return '文档降重';
        }
        if (strpos($c, 'ppt_download') !== false) {
            return 'PPT下载';
        }
        if (strpos($c, 'ppt') !== false) {
            return 'PPT生成';
        }
        if (strpos($c, 'write') !== false || strpos($c, 'kaiti') !== false) {
            return 'AI写作';
        }
        if (strpos($c, 'paper') !== false) {
            return 'AI论文';
        }
        if (strpos($c, 'autodoc') !== false || strpos($c, 'format') !== false || strpos($c, 'layout') !== false || strpos($c, 'paiban') !== false) {
            return '格式重排';
        }
        if (strpos($c, 'bonus') !== false || strpos($c, 'gift') !== false || strpos($c, 'income') !== false || strpos($c, 'reback') !== false) {
            return '赠送/收益';
        }
        if (strpos($c, 'adjust') !== false || strpos($c, 'manual') !== false || strpos($c, 'admin') !== false) {
            return '后台调整';
        }
        if (strpos($c, 'consume') !== false || strpos($c, 'deduct') !== false || strpos($c, 'pay') !== false) {
            return '消费';
        }
        return $ct === '' ? '余额变动' : $ct;
    }

    /**
     * @param mixed $t datetime 或 unix 时间戳，统一输出 'Y-m-d H:i:s'
     */
    private function fmtTime($t): string
    {
        if (!$t) return '';
        $c = (string) $t;
        if (ctype_digit($c)) return date('Y-m-d H:i:s', (int) $c);
        return date('Y-m-d H:i:s', strtotime($c));
    }
}