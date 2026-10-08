<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 用户端登录 Token 管理
 *
 * 前端通过 HTTP Header `token` 传递登录态。
 * Token 由 `authcode + userId + 时间戳 + 随机盐` 经 md5 生成，存储于缓存。
 *
 * 存储键：
 *   token_userid_{userId}          → 该用户当前 token（用于登出/互踢）
 *   userid_token_{token}           → ['user_id'=>, 'token'=>, 'expire_time'=>int]（校验用）
 *
 * TTL 与续期窗口：
 *   expire_duration    28800（8 小时） 超过即失效，返回登录超时
 *   be_expire_duration  7200（2 小时） 临近到期自动续期
 */
class UserTokenService
{
    private const KEEP_TOKEN_DURATION = 28800;   // token 总有效期 8 小时
    private const CONTINUE_DURATION   = 7200;    // 到期前 2 小时内自动续期

    private static function terminalKey(int $userId, int $terminal): string
    {
        return 'token_userid_' . $userId . '_' . $terminal;
    }

    private static function tokenKey(string $token): string
    {
        return 'userid_token_' . $token;
    }

    /**
     * 生成登录 token（新登录会顶掉同终端的旧 token）
     */
    public static function createToken(int $userId, int $terminal = 4): string
    {
        $salt = (string) config('site.authcode', '');
        $token = md5($salt . $userId . $terminal . microtime(true) . mt_rand(100000, 999999));

        $now = time();
        $expireTime = $now + self::KEEP_TOKEN_DURATION;
        $value = [
            'user_id' => $userId,
            'token' => $token,
            'expire_time' => $expireTime,
        ];

        Cache::set(self::terminalKey($userId, $terminal), $token, self::KEEP_TOKEN_DURATION);
        Cache::set(self::tokenKey($token), $value, self::KEEP_TOKEN_DURATION + 60);
        return $token;
    }

    /**
     * 校验 token 并返回其归属的用户 ID；无效/过期返回 0。
     * 临近过期时自动续期（幂等）。
     */
    public static function getUserId(string $token): int
    {
        if ($token === '') {
            return 0;
        }
        $info = Cache::get(self::tokenKey($token));
        if (!is_array($info) || empty($info['user_id']) || empty($info['expire_time'])) {
            return 0;
        }
        if ((int) $info['expire_time'] < time()) {
            Cache::delete(self::tokenKey($token));
            return 0;
        }
        // 临近到期自动续期
        if (time() > ((int) $info['expire_time'] - self::CONTINUE_DURATION)) {
            $newExpire = time() + self::KEEP_TOKEN_DURATION;
            $info['expire_time'] = $newExpire;
            Cache::set(self::tokenKey($token), $info, self::KEEP_TOKEN_DURATION + 60);
            Cache::set(self::terminalKey((int) $info['user_id'], -1), $token, self::KEEP_TOKEN_DURATION);
        }
        return (int) $info['user_id'];
    }

    /**
     * 从请求头读取 token
     */
    public static function readRequestToken(): string
    {
        return trim((string) \think\facade\Request::header('token', ''));
    }

    /**
     * 登出：清除 token
     */
    public static function destroyToken(string $token): void
    {
        if ($token === '') {
            return;
        }
        $info = Cache::get(self::tokenKey($token));
        if (is_array($info) && !empty($info['user_id'])) {
            $uid = (int) $info['user_id'];
            // 清除可能存在的终端键（终端未知，遍历有限可能性）
            foreach ([1, 2, 3, 4, 5, 6] as $terminal) {
                if (Cache::get(self::terminalKey($uid, $terminal)) === $token) {
                    Cache::delete(self::terminalKey($uid, $terminal));
                }
            }
        }
        Cache::delete(self::tokenKey($token));
    }
}