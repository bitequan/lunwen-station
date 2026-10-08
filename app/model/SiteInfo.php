<?php

namespace app\model;

use think\Model;

/**
 * 站点信息模型
 */
class SiteInfo extends Model
{
    // 设置完整表名
    protected $name = 'site_info';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
    ];
}

