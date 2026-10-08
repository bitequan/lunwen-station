<?php

namespace app\model;

use think\Model;

/**
 * 安全配置模型
 */
class SecurityConfig extends Model
{
    // 设置完整表名
    protected $name = 'security_config';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'password_min_length' => 'integer',
        'password_require_uppercase' => 'integer',
        'password_require_lowercase' => 'integer',
        'password_require_number' => 'integer',
        'password_require_special' => 'integer',
        'password_expire_days' => 'integer',
        'login_max_attempts' => 'integer',
        'login_lockout_time' => 'integer',
        'enable_captcha' => 'integer',
        'enable_2fa' => 'integer',
        'session_timeout' => 'integer',
    ];
}

