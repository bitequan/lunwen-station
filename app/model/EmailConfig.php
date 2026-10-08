<?php

namespace app\model;

use think\Model;

/**
 * 邮件配置模型
 */
class EmailConfig extends Model
{
    // 设置完整表名
    protected $name = 'email_config';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'smtp_port' => 'integer',
        'status' => 'integer',
    ];
}

