<?php
// 应用公共文件

/**
 * 返回带当前库表前缀的完整表名（用于原生 SQL）。
 */
function db_table(string $nameWithoutPrefix): string
{
    $p = (string) config('database.connections.mysql.prefix', '');

    return $p . $nameWithoutPrefix;
}
