<?php
// +----------------------------------------------------------------------
// | 系统版本配置
// +----------------------------------------------------------------------

return [
    // 当前系统版本号
    'current_version' => '1.0.4',

    // 后台首页展示的授权说明（授权类型、客户名称、到期日等，留空则首页显示「—」）
    'license_notice' => '',
    
    // 版本更新服务器地址（兼容旧逻辑）
    'update_server' => '',

    // 在线更新总开关（交付站关闭：不连接授权端，避免本地定制被更新包覆盖）
    'online_update' => [
        'enabled' => false,
    ],

    // 传输层策略
    'http' => [
        'timeout' => 30,
        'connect_timeout' => 8,
        'retry_times' => 2,
    ],

    // 安全策略
    'security' => [
        // 签名验签公钥（预留）
        'public_key' => '',
        'hash_algo' => 'sha256',
        // 下载地址白名单（只允许这些 host）
        'allowed_hosts' => ['www.bitewu.com'],
    ],

    // 执行策略
    'strategy' => [
        'require_signature' => false,
        'allow_force_update' => false,
        'maintenance_mode' => false,
        // 是否要求启动升级时二次密码确认（建议生产开启）
        'require_admin_password' => false,
        // 默认不覆盖敏感文件
        'protected_files' => ['.env', 'config/site.php'],
    ],
    
    // 更新包存储目录
    'update_path' => root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR,
    
    // 备份目录
    'backup_path' => root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR,
    
    // 是否启用自动备份
    'auto_backup' => true,
    
    // 更新日志文件
    'log_file' => root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'update.log',
];

