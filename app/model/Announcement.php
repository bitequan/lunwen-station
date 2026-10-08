<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 公告模型
 */
class Announcement extends Model
{
    protected $name = 'announcement';
    
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
     * 获取格式化后的创建时间
     */
    public function getCreateTimeTextAttr($value, $data)
    {
        return $data['create_time'] ? date('Y-m-d H:i:s', strtotime($data['create_time'])) : '';
    }
    
    /**
     * 获取格式化后的更新时间
     */
    public function getUpdateTimeTextAttr($value, $data)
    {
        return $data['update_time'] ? date('Y-m-d H:i:s', strtotime($data['update_time'])) : '';
    }
}
