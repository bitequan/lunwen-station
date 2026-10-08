<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * 用户端前台 session 写入（登录态）
 */
class FrontendUserSession
{
    /**
     * @param array<string,mixed> $user users 表行
     */
    public static function loginWithUserRow(array $user): void
    {
        $now = date('Y-m-d H:i:s');
        Db::name('users')
            ->where('id', (int) $user['id'])
            ->update([
                'last_login_time' => $now,
                'last_login_ip' => request()->ip(),
                'update_time' => $now,
            ]);

        session('user_id', $user['id']);
        session('user_info', [
            'id' => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'] ?: $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
        ]);
        session('remember_me', null);

        UserSingleSessionService::onLogin((int) $user['id']);
    }
}
