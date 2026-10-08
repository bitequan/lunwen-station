<?php

namespace app\model;

use think\Model;

/**
 * 订单模型
 */
class Orders extends Model
{
    // 设置完整表名
    protected $name = 'orders';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'status' => 'integer',
        'pay_status' => 'integer',
        'unit_price' => 'float',
        'total_amount' => 'float',
    ];
    
    /**
     * 订单状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            0 => '待支付',
            1 => '已支付',
            2 => '处理中',
            3 => '已完成',
            4 => '已取消',
            5 => '已退款'
        ];
        
        return $statusMap[$data['status']] ?? '未知';
    }
    
    /**
     * 支付状态文本
     */
    public function getPayStatusTextAttr($value, $data)
    {
        $statusMap = [
            0 => '未支付',
            1 => '已支付',
            2 => '支付失败'
        ];
        
        return $statusMap[$data['pay_status']] ?? '未知';
    }
    
    /**
     * 关联商品
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}

