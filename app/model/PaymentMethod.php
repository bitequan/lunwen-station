<?php

namespace app\model;

use think\Model;

/**
 * 支付方式模型
 */
class PaymentMethod extends Model
{
    // 设置完整表名
    protected $name = 'payment_methods';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'enabled' => 'integer',
    ];
    
    /**
     * 获取配置参数
     * @param mixed $value
     * @return array
     */
    public function getConfigAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true);
    }
    
    /**
     * 设置配置参数
     * @param array $value
     * @return string
     */
    public function setConfigAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }
    
    /**
     * 获取启用的支付方式
     * @return array
     */
    public static function getEnabledMethods()
    {
        return self::where('enabled', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取所有支付方式
     * @return array
     */
    public static function getAllMethods()
    {
        return self::order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 根据代码获取支付方式
     * @param string $code
     * @return array|null
     */
    public static function getByCode($code)
    {
        $method = self::where('code', $code)->find();
        return $method ? $method->toArray() : null;
    }
}

