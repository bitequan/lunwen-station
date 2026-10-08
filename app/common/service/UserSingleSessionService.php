<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Cookie;

/**
 * 用户端（user 应用）单点登录：同一账号仅保留最后一次登录产生的会话有效。
 */
final class UserSingleSessionService
{
    private const CACHE_PREFIX = 'user_sso_sig_v1_';

    private const CACHE_TTL = 31536000;

    private const REMEMBER_COOKIE = 'user_remember';

    public static function isEnabled(): bool
    {
        $raw = \app\model\SystemConfig::getValue('security_config');
        if ($raw === null || $raw === '') {
            return true;
        }
        $cfg = json_decode((string) $raw, true);
        if (!is_array($cfg)) {
            return true;
        }
        if (!array_key_exists('user_single_session', $cfg)) {
            return true;
        }

        return (int) $cfg['user_single_session'] === 1;
    }

    public static function cacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId;
    }

    /**
     * 登录成功或会话绑定时调用：写入新令牌并使其它端会话失效。
     */
    public static function onLogin(int $userId): void
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return;
        }
        if (!self::isEnabled()) {
            session('user_sso_token', null);

            return;
        }
        $token = bin2hex(random_bytes(16));
        Cache::set(self::cacheKey($userId), $token, self::CACHE_TTL);
        session('user_sso_token', $token);
    }

    public static function onLogout(int $userId): void
    {
        $userId = (int) $userId;
        if ($userId > 0) {
            Cache::delete(self::cacheKey($userId));
        }
        session('user_sso_token', null);
    }

    public static function verifyRequest(): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $uid = (int) (session('user_id') ?? 0);
        if ($uid <= 0) {
            return;
        }

        $sessTok = (string) (session('user_sso_token') ?? '');
        $cacheTok = (string) (Cache::get(self::cacheKey($uid)) ?? '');

        if ($sessTok === '') {
            if ($cacheTok === '') {
                self::assignFreshToken($uid);
            } else {
                self::forceLogout();
            }

            return;
        }

        if ($cacheTok === '' || !hash_equals($cacheTok, $sessTok)) {
            self::forceLogout();
        }
    }

    private static function assignFreshToken(int $userId): void
    {
        $token = bin2hex(random_bytes(16));
        Cache::set(self::cacheKey($userId), $token, self::CACHE_TTL);
        session('user_sso_token', $token);
    }

    private static function forceLogout(): void
    {
        session('user_id', null);
        session('user_info', null);
        session('user_sso_token', null);
        session('remember_me', null);
        Cookie::delete(self::REMEMBER_COOKIE);
    }
}
