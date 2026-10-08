<?php
// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    // session name
    'name'           => 'PHPSESSID',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => 'file',
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间（单位：秒）
    // 设置为7天，这样即使关闭浏览器也能保持登录
    'expire'         => 604800, // 7天 = 7 * 24 * 60 * 60
    // 前缀
    'prefix'         => '',
    // Cookie 安全：禁止 JS 读取会话 Cookie，防 XSS 窃取
    'httponly'       => true,
    // SameSite 防 CSRF
    'same_site'      => 'Lax',
];
