<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 充值赠送规则模型
 * @package app\model
 */
class RechargeBonusRule extends Model
{
    // 设置表名
    protected $name = 'recharge_bonus_rules';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 定义字段类型
    protected $type = [
        'id'            => 'integer',
        'rule_type'     => 'integer',
        'min_amount'    => 'float',
        'max_amount'    => 'float',
        'bonus_percent' => 'float',
        'bonus_fixed'   => 'float',
        'sort_order'    => 'integer',
        'status'        => 'integer'
    ];
    
    // 规则类型常量
    const RULE_TYPE_PERCENT = 1; // 按比例赠送
    const RULE_TYPE_FIXED   = 2; // 按固定金额赠送
    
    // 状态常量
    const STATUS_ENABLED  = 1; // 启用
    const STATUS_DISABLED = 0; // 禁用
    
    /**
     * 获取所有启用的规则（按排序和金额范围排序）
     * @return array
     */
    public static function getEnabledRules(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->order('sort_order', 'asc')
            ->order('min_amount', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 根据充值金额获取适用的赠送规则
     * @param float $amount 充值金额
     * @return array|null 匹配的规则，没有则返回null
     */
    public static function getApplicableRule(float $amount): ?array
    {
        $rules = self::getEnabledRules();
        
        foreach ($rules as $rule) {
            // 左开右闭区间：(min_amount, max_amount]
            // 不包括最小金额，包括最大金额
            if ($amount > $rule['min_amount']) {
                // 如果max_amount为null，表示无上限
                if ($rule['max_amount'] === null || $amount <= $rule['max_amount']) {
                    return $rule;
                }
            }
        }
        
        return null;
    }
    
    /**
     * 计算赠送金额
     * @param float $amount 充值金额
     * @return array 包含赠送金额和规则信息的数组
     */
    public static function calculateBonus(float $amount): array
    {
        $rule = self::getApplicableRule($amount);
        
        if (!$rule) {
            return [
                'bonus_amount' => 0.00,
                'total_amount' => $amount,
                'rule_id' => null,
                'rule_name' => '',
                'rule_type' => null
            ];
        }
        
        $bonusAmount = 0.00;
        
        if ($rule['rule_type'] == self::RULE_TYPE_PERCENT) {
            // 按比例计算
            $bonusAmount = round($amount * $rule['bonus_percent'] / 100, 2);
        } elseif ($rule['rule_type'] == self::RULE_TYPE_FIXED) {
            // 固定金额
            $bonusAmount = $rule['bonus_fixed'];
        }
        
        $totalAmount = $amount + $bonusAmount;
        
        return [
            'bonus_amount' => $bonusAmount,
            'total_amount' => $totalAmount,
            'rule_id' => $rule['id'],
            'rule_name' => $rule['name'],
            'rule_type' => $rule['rule_type'],
            'rule_data' => $rule
        ];
    }
    
    /**
     * 获取规则类型文本
     * @param int $type 规则类型
     * @return string
     */
    public static function getRuleTypeText(int $type): string
    {
        $types = [
            self::RULE_TYPE_PERCENT => '按比例赠送',
            self::RULE_TYPE_FIXED   => '按固定金额赠送'
        ];
        
        return $types[$type] ?? '未知类型';
    }
    
    /**
     * 获取状态文本
     * @param int $status 状态
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statuses = [
            self::STATUS_ENABLED  => '启用',
            self::STATUS_DISABLED => '禁用'
        ];
        
        return $statuses[$status] ?? '未知状态';
    }
    
    /**
     * 获取金额范围描述
     * @param float $min 最小金额
     * @param float|null $max 最大金额
     * @return string
     */
    public static function getAmountRangeText(float $min, ?float $max): string
    {
        if ($max === null) {
            return sprintf('> %.2f 元', $min);
        }
        
        return sprintf('%.2f 元 < 金额 ≤ %.2f 元', $min, $max);
    }
    
    /**
     * 获取赠送金额描述
     * @param array $rule 规则数据
     * @return string
     */
    public static function getBonusText(array $rule): string
    {
        if ($rule['rule_type'] == self::RULE_TYPE_PERCENT) {
            return sprintf('赠送 %.2f%%', $rule['bonus_percent']);
        } elseif ($rule['rule_type'] == self::RULE_TYPE_FIXED) {
            return sprintf('赠送 %.2f 元', $rule['bonus_fixed']);
        }
        
        return '无赠送';
    }
    
    /**
     * 验证规则数据
     * @param array $data 规则数据
     * @return array 验证结果
     */
    public static function validateRuleData(array $data): array
    {
        $errors = [];
        
        // 验证规则名称
        if (empty($data['name'])) {
            $errors[] = '规则名称不能为空';
        }
        
        // 验证规则类型
        if (!in_array($data['rule_type'], [self::RULE_TYPE_PERCENT, self::RULE_TYPE_FIXED])) {
            $errors[] = '无效的规则类型';
        }
        
        // 验证最小金额
        if (!isset($data['min_amount']) || $data['min_amount'] <= 0) {
            $errors[] = '最小充值金额必须大于0';
        }
        
        // 验证最大金额
        if (isset($data['max_amount']) && $data['max_amount'] !== '' && $data['max_amount'] !== null) {
            if ($data['max_amount'] <= $data['min_amount']) {
                $errors[] = '最大充值金额必须大于最小充值金额';
            }
        }
        
        // 验证赠送金额
        if ($data['rule_type'] == self::RULE_TYPE_PERCENT) {
            if (!isset($data['bonus_percent']) || $data['bonus_percent'] < 0) {
                $errors[] = '赠送比例不能为负数';
            }
        } elseif ($data['rule_type'] == self::RULE_TYPE_FIXED) {
            if (!isset($data['bonus_fixed']) || $data['bonus_fixed'] < 0) {
                $errors[] = '赠送金额不能为负数';
            }
        }
        
        return $errors;
    }
    
    /**
     * 检查金额范围是否重叠
     * @param array $data 规则数据
     * @param int|null $excludeId 排除的规则ID（用于更新时）
     * @return bool 是否重叠
     */
    public static function checkAmountRangeOverlap(array $data, ?int $excludeId = null): bool
    {
        $minAmount = (float)$data['min_amount'];
        $maxAmount = isset($data['max_amount']) && $data['max_amount'] !== '' && $data['max_amount'] !== null ? (float)$data['max_amount'] : null;
        
        $query = self::where('status', self::STATUS_ENABLED);
        
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        
        $rules = $query->select();
        
        foreach ($rules as $rule) {
            $ruleMin = (float)$rule['min_amount'];
            $ruleMax = $rule['max_amount'] !== null ? (float)$rule['max_amount'] : null;
            
            // 检查范围是否重叠（左开右闭区间）
            // 区间A: (min1, max1]
            // 区间B: (min2, max2]
            // 重叠条件：两个区间有交集
            
            if ($maxAmount === null && $ruleMax === null) {
                // 两个规则都无上限
                // 重叠条件：最小值相同（因为都是开区间）
                if ($minAmount == $ruleMin) {
                    return true;
                }
            } elseif ($maxAmount === null) {
                // 当前规则无上限，其他规则有上限
                // 重叠条件：当前规则的最小值 < 其他规则的最大值
                // 注意：如果 min1 = max2，不重叠，因为 min1 是开区间，max2 是闭区间
                if ($minAmount < $ruleMax) {
                    return true;
                }
            } elseif ($ruleMax === null) {
                // 其他规则无上限，当前规则有上限
                // 重叠条件：其他规则的最小值 < 当前规则的最大值
                if ($ruleMin < $maxAmount) {
                    return true;
                }
            } else {
                // 两个规则都有上限
                // 重叠条件：min1 < max2 && min2 < max1
                // 注意：使用 < 而不是 <=，因为都是左开区间
                if ($minAmount < $ruleMax && $ruleMin < $maxAmount) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
