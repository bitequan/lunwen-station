<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 常见问题模型
 */
class Faq extends Model
{
    protected $name = 'faq';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取状态文本
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            1 => '显示',
            0 => '隐藏'
        ];
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 验证数据
     */
    public static function validateData($data)
    {
        $errors = [];
        
        if (empty($data['question'])) {
            $errors[] = '问题标题不能为空';
        } elseif (mb_strlen($data['question']) > 255) {
            $errors[] = '问题标题不能超过255个字符';
        }
        
        if (empty($data['answer'])) {
            $errors[] = '问题答案不能为空';
        }
        
        if (isset($data['sort_order']) && !is_numeric($data['sort_order'])) {
            $errors[] = '排序顺序必须是数字';
        }
        
        return $errors;
    }
}

