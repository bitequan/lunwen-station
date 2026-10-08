<?php
declare(strict_types=1);

namespace app\common\utils;

use think\facade\Config;

/**
 * 对接 TokenAPI 等使用的 agent_id：{前缀}{用户ID}
 */
class AgentIdHelper
{
    public const DEFAULT_PREFIX = 'adweb_';

    /**
     * 从配置读取原始前缀字符串（未规范化）
     */
    private static function rawPrefixFromConfig(): string
    {
        $cfg = Config::get('docking');
        if (!is_array($cfg) || $cfg === []) {
            $file = config_path() . 'docking.php';
            if (is_file($file)) {
                $cfg = include $file;
            }
        }
        if (!is_array($cfg)) {
            return '';
        }

        return (string)($cfg['agent_id_prefix'] ?? '');
    }

    /**
     * 规范化前缀：默认 adweb_；若未以 _ 结尾则自动补全
     */
    public static function prefix(): string
    {
        $p = trim(self::rawPrefixFromConfig());
        if ($p === '') {
            return self::DEFAULT_PREFIX;
        }
        // 仅保留字母数字与下划线，避免注入或异常字符进入对接参数
        $p = (string)preg_replace('/[^a-zA-Z0-9_]/', '', $p);
        if ($p === '') {
            return self::DEFAULT_PREFIX;
        }
        if (substr($p, -1) !== '_') {
            $p .= '_';
        }

        return $p;
    }

    /**
     * 后台保存时规范化：空则回退默认
     */
    public static function normalizePrefixForSave(string $input): string
    {
        $p = trim($input);
        if ($p === '') {
            return self::DEFAULT_PREFIX;
        }
        $p = (string)preg_replace('/[^a-zA-Z0-9_]/', '', $p);
        if ($p === '') {
            return self::DEFAULT_PREFIX;
        }
        if (substr($p, -1) !== '_') {
            $p .= '_';
        }

        return $p;
    }

    /**
     * 生成 agent_id；无效用户返回 null
     */
    public static function build(?int $userId): ?string
    {
        $userId = (int)($userId ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return self::prefix() . $userId;
    }

    /**
     * 从 agent_id 解析本站用户 ID（须匹配当前配置前缀）
     */
    public static function parseUserId(string $agentId): ?int
    {
        $agentId = trim($agentId);
        $pref = self::prefix();
        if ($agentId === '' || strpos($agentId, $pref) !== 0) {
            return null;
        }
        $suffix = substr($agentId, strlen($pref));
        if ($suffix === '' || !ctype_digit($suffix)) {
            return null;
        }

        return (int)$suffix;
    }
}
