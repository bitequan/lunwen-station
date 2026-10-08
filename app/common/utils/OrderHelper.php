<?php
namespace app\common\utils;

/**
 * 订单工具类
 * 处理订单相关辅助功能
 */
class OrderHelper
{
    /**
     * 生成订单号
     * 
     * @param string $prefix 订单前缀
     * @param int $userId 用户ID
     * @return string 订单号
     */
    public static function generateOrderSn($prefix = 'ORD', $userId = 0)
    {
        $time = date('YmdHis');
        $random = mt_rand(1000, 9999);
        $userIdPart = $userId > 0 ? str_pad($userId, 6, '0', STR_PAD_LEFT) : '000000';
        
        return $prefix . $time . $userIdPart . $random;
    }
    
    /**
     * 生成论文订单号
     * 
     * @param int $userId 用户ID
     * @return string 订单号
     */
    public static function generatePaperOrderSn($userId = 0)
    {
        return self::generateOrderSn('PAPER', $userId);
    }
    
    /**
     * 生成PPT订单号
     * 
     * @param int $userId 用户ID
     * @return string 订单号
     */
    public static function generatePptOrderSn($userId = 0)
    {
        return self::generateOrderSn('PPT', $userId);
    }
    
    /**
     * 生成写作订单号
     * 
     * @param int $userId 用户ID
     * @return string 订单号
     */
    public static function generateWriteOrderSn($userId = 0)
    {
        return self::generateOrderSn('WRITE', $userId);
    }
    
    /**
     * 生成降重订单号
     * 
     * @param int $userId 用户ID
     * @return string 订单号
     */
    public static function generateReduceWeightOrderSn($userId = 0)
    {
        return self::generateOrderSn('REDUCE', $userId);
    }
    
    /**
     * 验证订单数据
     * 
     * @param array $orderData 订单数据
     * @param string $orderType 订单类型：paper|ppt|write|reduce_weight
     * @return array 验证结果
     */
    public static function validateOrderData($orderData, $orderType = 'paper')
    {
        $errors = [];
        
        // 通用验证
        if (empty($orderData['title'] ?? '')) {
            $errors[] = '标题不能为空';
        }
        
        if (empty($orderData['user_id'] ?? 0)) {
            $errors[] = '用户ID不能为空';
        }
        
        // 类型特定验证
        switch ($orderType) {
            case 'paper':
                if (empty($orderData['paper_type'] ?? '')) {
                    $errors[] = '论文类型不能为空';
                }
                if (empty($orderData['word_count'] ?? 0)) {
                    $errors[] = '字数不能为空';
                }
                break;
                
            case 'ppt':
                if (empty($orderData['template_id'] ?? 0)) {
                    $errors[] = 'PPT模板不能为空';
                }
                break;
                
            case 'write':
                if (empty($orderData['write_type'] ?? '')) {
                    $errors[] = '写作类型不能为空';
                }
                break;
                
            case 'reduce_weight':
                if (empty($orderData['original_file'] ?? '')) {
                    $errors[] = '原文件不能为空';
                }
                break;
        }
        
        if (empty($errors)) {
            return [
                'success' => true,
                'code' => 1,
                'msg' => '验证通过'
            ];
        } else {
            return [
                'success' => false,
                'code' => 0,
                'msg' => implode('；', $errors)
            ];
        }
    }
    
    /**
     * 构建订单数据
     * 
     * @param array $formData 表单数据
     * @param string $orderType 订单类型
     * @return array 订单数据
     */
    public static function buildOrderData($formData, $orderType = 'paper')
    {
        $orderData = [
            'title' => $formData['title'] ?? '',
            'user_id' => $formData['user_id'] ?? 0,
            'create_time' => time(),
            'update_time' => time(),
            'status' => 0, // 待处理
            'price' => $formData['price'] ?? 0,
            'pay_status' => -1, // 未支付
            'remark' => $formData['remark'] ?? ''
        ];
        
        // 添加类型特定字段
        switch ($orderType) {
            case 'paper':
                $orderData['paper_type'] = $formData['paper_type'] ?? '';
                $orderData['word_count'] = $formData['word_count'] ?? 0;
                $orderData['paper_style'] = $formData['paper_style'] ?? '';
                $orderData['services'] = $formData['services'] ?? [];
                break;
                
            case 'ppt':
                $orderData['template_id'] = $formData['template_id'] ?? 0;
                $orderData['chapter_count'] = $formData['chapter_count'] ?? 6;
                $orderData['section_range'] = $formData['section_range'] ?? '3-6';
                $orderData['author'] = $formData['author'] ?? '';
                break;
                
            case 'write':
                $orderData['write_type'] = $formData['write_type'] ?? '';
                $orderData['write_data'] = $formData['write_data'] ?? [];
                break;
                
            case 'reduce_weight':
                $orderData['original_file'] = $formData['original_file'] ?? '';
                $orderData['target_similarity'] = $formData['target_similarity'] ?? 10;
                $orderData['word_count'] = $formData['word_count'] ?? 0;
                break;
        }
        
        return $orderData;
    }
    
    /**
     * 计算订单超时时间
     * 
     * @param string $orderType 订单类型
     * @return int 超时时间（秒）
     */
    public static function getOrderTimeout($orderType = 'paper')
    {
        $timeouts = [
            'paper' => 24 * 3600, // 24小时
            'ppt' => 12 * 3600,   // 12小时
            'write' => 48 * 3600, // 48小时
            'reduce_weight' => 6 * 3600, // 6小时
        ];
        
        return $timeouts[$orderType] ?? 24 * 3600;
    }
    
    /**
     * 格式化订单状态
     * 
     * @param int $status 状态码
     * @param string $orderType 订单类型
     * @return string 状态描述
     */
    public static function formatOrderStatus($status, $orderType = 'paper')
    {
        $statusMap = [
            -2 => '已取消',
            -1 => '待支付',
            0 => '待处理',
            1 => '处理中',
            2 => '已完成',
            3 => '已退款'
        ];
        
        return $statusMap[$status] ?? '未知状态';
    }
    
    /**
     * 格式化支付状态
     * 
     * @param int $payStatus 支付状态码
     * @return string 支付状态描述
     */
    public static function formatPayStatus($payStatus)
    {
        $statusMap = [
            -1 => '未支付',
            0 => '支付中',
            1 => '已支付',
            2 => '支付失败',
            3 => '已退款'
        ];
        
        return $statusMap[$payStatus] ?? '未知状态';
    }
}
