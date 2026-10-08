<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use Exception;

/**
 * 数据库迁移服务类
 */
class DatabaseMigrationService
{
    /**
     * 执行SQL文件
     * @param string $sqlFile SQL文件路径
     * @return array
     */
    public function executeSqlFile(string $sqlFile): array
    {
        try {
            if (!file_exists($sqlFile)) {
                return [
                    'success' => false,
                    'message' => 'SQL文件不存在: ' . $sqlFile
                ];
            }

            $sql = file_get_contents($sqlFile);
            if (empty($sql)) {
                return [
                    'success' => false,
                    'message' => 'SQL文件为空'
                ];
            }

            // 移除BOM标记
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            
            // 分割SQL语句（支持多语句）
            $statements = $this->splitSqlStatements($sql);
            
            $executed = 0;
            $errors = [];
            
            // 开始事务
            Db::startTrans();
            
            try {
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || $this->isComment($statement)) {
                        continue;
                    }
                    
                    try {
                        Db::execute($statement);
                        $executed++;
                    } catch (Exception $e) {
                        $errors[] = [
                            'sql' => substr($statement, 0, 100) . '...',
                            'error' => $e->getMessage()
                        ];
                        // 如果是严重错误，停止执行
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                    }
                }
                
                Db::commit();
                
                return [
                    'success' => true,
                    'message' => "成功执行 {$executed} 条SQL语句",
                    'executed' => $executed,
                    'errors' => $errors
                ];
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '执行SQL失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 执行SQL字符串
     * @param string $sql SQL语句
     * @return array
     */
    public function executeSql(string $sql): array
    {
        try {
            $statements = $this->splitSqlStatements($sql);
            $executed = 0;
            
            Db::startTrans();
            
            try {
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || $this->isComment($statement)) {
                        continue;
                    }
                    
                    Db::execute($statement);
                    $executed++;
                }
                
                Db::commit();
                
                return [
                    'success' => true,
                    'message' => "成功执行 {$executed} 条SQL语句",
                    'executed' => $executed
                ];
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '执行SQL失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 检查数据库表是否存在
     * @param string $tableName 表名
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $database = config('database.connections.mysql.database');
            $physical = $this->physicalTableName($tableName);
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ?";
            $result = Db::query($sql, [$database, $physical]);
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检查数据库字段是否存在
     * @param string $tableName 表名
     * @param string $columnName 字段名
     * @return bool
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $database = config('database.connections.mysql.database');
            $physical = $this->physicalTableName($tableName);
            $sql = "SELECT COUNT(*) as count FROM information_schema.columns 
                    WHERE table_schema = ? AND table_name = ? AND column_name = ?";
            $result = Db::query($sql, [$database, $physical, $columnName]);
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取数据库版本（从版本表）
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        try {
            if (!$this->tableExists(VersionSchema::T_VER)) {
                $this->createVersionTable();
                return '0.0.0';
            }

            $result = Db::name(VersionSchema::T_VER)
                ->order('id', 'desc')
                ->find();

            return $result ? (string)$result['ver'] : '0.0.0';
        } catch (Exception $e) {
            return '0.0.0';
        }
    }

    /**
     * 设置数据库版本
     * @param string $version
     * @return bool
     */
    public function setDatabaseVersion(string $version): bool
    {
        try {
            if (!$this->tableExists(VersionSchema::T_VER)) {
                $this->createVersionTable();
            }

            Db::name(VersionSchema::T_VER)->insert([
                'ver' => $version,
                'utime' => date('Y-m-d H:i:s'),
                'memo' => '系统更新',
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 创建版本表
     * @return void
     */
    private function createVersionTable(): void
    {
        $t = $this->physicalTableName(VersionSchema::T_VER);
        $sql = "CREATE TABLE IF NOT EXISTS `{$t}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ver` varchar(20) NOT NULL COMMENT '版本号',
            `utime` datetime NOT NULL COMMENT '更新时间',
            `memo` text COMMENT '更新说明',
            PRIMARY KEY (`id`),
            KEY `i_ver` (`ver`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统版本记录表'";
        Db::execute($sql);
    }

    /** 逻辑表名 -> 带 DB_PREFIX 的物理表名 */
    private function physicalTableName(string $logicalWithoutPrefix): string
    {
        return (string)config('database.connections.mysql.prefix', '') . $logicalWithoutPrefix;
    }

    /**
     * 分割SQL语句
     * @param string $sql
     * @return array
     */
    private function splitSqlStatements(string $sql): array
    {
        // 移除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // 按分号分割，但要注意字符串中的分号
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $current .= $char;
            
            if (!$inString && ($char === '"' || $char === "'" || $char === '`')) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && $sql[$i - 1] !== '\\') {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }
        
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }

    /**
     * 判断是否为注释
     * @param string $line
     * @return bool
     */
    private function isComment(string $line): bool
    {
        $line = trim($line);
        return empty($line) || 
               strpos($line, '--') === 0 || 
               strpos($line, '/*') === 0 ||
               strpos($line, '#') === 0;
    }
}

