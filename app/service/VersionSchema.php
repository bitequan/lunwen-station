<?php
declare(strict_types=1);

namespace app\service;

/**
 * 在线更新 / 版本相关表结构常量（逻辑表名不含前缀，由 Db::name() 自动加 database.connections.mysql.prefix / .env DB_PREFIX）。
 * 字段名刻意缩短，避免部分 MySQL/MariaDB 对标识符总长限制较严时报错。
 */
final class VersionSchema
{
    public const T_TASK = 'upd_task';
    public const T_LOG = 'upd_log';
    public const T_MIG = 'upd_mig';
    public const T_LOCK = 'upd_lock';
    public const T_VER = 'app_ver';

    /** 更新包 SQL 内嵌表名使用的默认前缀（与官方包一致），执行前会 rewrite 为当前站点前缀 */
    public const SQL_SOURCE_PREFIX = 'ad_';

    /** @param array<string,mixed> $row */
    public static function taskRowToApi(array $row): array
    {
        return [
            'id' => $row['id'] ?? 0,
            'task_no' => $row['tno'] ?? '',
            'from_version' => $row['fver'] ?? '',
            'to_version' => $row['tver'] ?? '',
            'status' => $row['status'] ?? '',
            'step' => $row['step'] ?? '',
            'progress' => $row['progress'] ?? 0,
            'operator_admin_id' => $row['op_id'] ?? 0,
            'idempotency_key' => $row['idem'] ?? '',
            'error_code' => $row['ecode'] ?? '',
            'error_msg' => $row['emsg'] ?? '',
            'context_json' => $row['ctx'] ?? '',
            'created_at' => $row['c_at'] ?? '',
            'updated_at' => $row['u_at'] ?? '',
        ];
    }

    /** @param array<string,mixed> $row */
    public static function logRowToApi(array $row): array
    {
        return [
            'id' => $row['id'] ?? 0,
            'task_id' => $row['tid'] ?? 0,
            'level' => $row['level'] ?? 'info',
            'message' => $row['message'] ?? '',
            'context_json' => $row['ctx'] ?? '',
            'created_at' => $row['c_at'] ?? '',
        ];
    }
}
