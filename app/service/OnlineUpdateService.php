<?php
declare(strict_types=1);

namespace app\service;

use app\model\SystemConfig;
use Exception;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use ZipArchive;

class OnlineUpdateService
{
    private const LOCK_KEY = 'online_update_global_lock';
    private const FINAL_STATUSES = ['success', 'failed', 'rolled_back'];
    private const CRITICAL_PROTECTED_FILES = [
        'app/AppService.php',
        'app/service.php',
        'public/index.php',
    ];
    
    // 在线更新服务端配置：避免在 config/version.php 暴露敏感参数
    private const ONLINE_UPDATE_APP_ID = 1;
    private const ONLINE_UPDATE_BASE_URL = 'https://www.bitewu.com';
    private const ONLINE_UPDATE_CHECK_ENDPOINT = '/api/check/update';
    public function checkOnline(): array
    {
        if (!Config::get('version.online_update.enabled', false)) {
            return ['code' => 0, 'msg' => '在线更新已关闭'];
        }
        $this->initTables();
        $cfg = $this->getServerConfig();
        if ($cfg['error'] !== '') {
            return ['code' => 0, 'msg' => $cfg['error']];
        }

        $payload = [
            'app' => $cfg['app_id'],
            'authcode' => $cfg['authcode'],
            'domain' => $cfg['domain'],
            'version' => (new VersionService())->getCurrentVersion(),
            'dbversion' => (new DatabaseMigrationService())->getDatabaseVersion(),
        ];
        $requestResult = $this->httpPostJson($cfg['check_url'], $payload);
        if (!$requestResult['success']) {
            return ['code' => 0, 'msg' => $requestResult['message']];
        }

        $raw = $requestResult['data'];
        $remoteCode = (int)($raw['code'] ?? 0);
        $hasUpdate = in_array($remoteCode, [1, 2], true);
        return [
            'code' => 1,
            'msg' => (string)($raw['msg'] ?? ($hasUpdate ? '发现新版本' : '当前已是最新版本')),
            'data' => [
                'has_update' => $hasUpdate,
                'current_version' => $payload['version'],
                'latest_version' => (string)($raw['version'] ?? ''),
                'raw' => $raw,
            ],
        ];
    }

    public function createTask(string $toVersion, int $adminId, array $checkData, bool $forceUpdate, string $idempotencyKey): array
    {
        $this->initTables();
        if ($toVersion === '') {
            return ['code' => 0, 'msg' => 'to_version 不能为空'];
        }
        if ($idempotencyKey !== '') {
            $old = Db::name(VersionSchema::T_TASK)->where('idem', $idempotencyKey)->find();
            if ($old) {
                return ['code' => 1, 'msg' => '已存在同幂等任务', 'data' => ['task_no' => $old['tno']]];
            }
        }

        $taskNo = 'UPD' . date('YmdHis') . strtoupper(substr(md5((string)mt_rand()), 0, 8));
        $now = date('Y-m-d H:i:s');
        $taskId = (int)Db::name(VersionSchema::T_TASK)->insertGetId([
            'tno' => $taskNo,
            'fver' => (new VersionService())->getCurrentVersion(),
            'tver' => $toVersion,
            'status' => 'pending',
            'step' => 'pending',
            'progress' => 0,
            'op_id' => $adminId,
            'idem' => $idempotencyKey,
            'ecode' => '',
            'emsg' => '',
            'ctx' => json_encode(['check_data' => $checkData, 'force_update' => $forceUpdate], JSON_UNESCAPED_UNICODE),
            'c_at' => $now,
            'u_at' => $now,
        ]);
        $this->logTask($taskId, 'info', '任务创建成功', ['task_no' => $taskNo]);
        return ['code' => 1, 'msg' => '任务创建成功', 'data' => ['task_no' => $taskNo, 'task_id' => $taskId]];
    }

    public function startTask(string $taskNo): array
    {
        $this->initTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        if (in_array((string)$task['status'], self::FINAL_STATUSES, true) && (string)$task['status'] === 'success') {
            return ['code' => 0, 'msg' => '任务已成功，无需重复执行'];
        }
        if (!$this->acquireLock()) {
            return ['code' => 0, 'msg' => '已有升级任务执行中'];
        }

        try {
            return $this->runStateMachine($task);
        } finally {
            $this->releaseLock();
        }
    }

    public function taskStatus(string $taskNo): array
    {
        $this->initTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        return ['code' => 1, 'msg' => '获取成功', 'data' => VersionSchema::taskRowToApi($task)];
    }

    public function taskLogs(string $taskNo, int $limit = 200): array
    {
        $this->initTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        $logs = Db::name(VersionSchema::T_LOG)->where('tid', (int)$task['id'])->order('id', 'desc')->limit(max(1, min($limit, 1000)))->select()->toArray();
        $out = [];
        foreach ($logs as $row) {
            $out[] = VersionSchema::logRowToApi($row);
        }
        return ['code' => 1, 'msg' => '获取成功', 'data' => $out];
    }

    public function retryTask(string $taskNo): array
    {
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        if (!in_array((string)$task['status'], ['failed', 'rolled_back'], true)) {
            return ['code' => 0, 'msg' => '仅失败或已回滚任务支持重试'];
        }
        Db::name(VersionSchema::T_TASK)->where('id', (int)$task['id'])->update([
            'status' => 'pending', 'step' => 'pending', 'progress' => 0, 'ecode' => '', 'emsg' => '', 'u_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logTask((int)$task['id'], 'warning', '任务已重置，准备重试');
        return $this->startTask($taskNo);
    }

    public function rollbackTask(string $taskNo): array
    {
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        $taskId = (int)$task['id'];
        $ctx = $this->decodeJson((string)($task['ctx'] ?? ''));
        $res = $this->rollbackByContext($ctx);
        Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
            'status' => 'rolled_back', 'step' => 'rolled_back', 'u_at' => date('Y-m-d H:i:s'),
            'ecode' => $res['success'] ? '' : 'ROLLBACK_PARTIAL', 'emsg' => $res['success'] ? '' : $res['message'],
        ]);
        $this->logTask($taskId, $res['success'] ? 'warning' : 'error', '手动回滚执行完成', $res);
        return ['code' => $res['success'] ? 1 : 0, 'msg' => $res['success'] ? '回滚成功' : ('回滚失败: ' . $res['message'])];
    }

    private function runStateMachine(array $task): array
    {
        $taskId = (int)$task['id'];
        $ctx = $this->decodeJson((string)($task['ctx'] ?? ''));
        try {
            $this->markStep($taskId, 'checking', 5);
            $check = $this->checkOnline();
            if (($check['code'] ?? 0) !== 1 || empty($check['data']['has_update'])) {
                throw new Exception((string)($check['msg'] ?? '未发现可升级版本'));
            }
            $ctx['check_data'] = $check['data']['raw'] ?? [];

            $this->markStep($taskId, 'precheck', 12);
            $this->precheckOrFail($ctx);

            $this->markStep($taskId, 'downloaded', 25);
            $ctx = array_merge($ctx, $this->downloadArtifacts($task['tno'], $ctx['check_data']));

            $this->markStep($taskId, 'verified', 40);
            $this->verifyArtifactOrFail($ctx);

            $this->markStep($taskId, 'backed_up', 55);
            $ctx = array_merge($ctx, $this->createBackupSnapshot($task['tno']));

            if (Config::get('version.strategy.maintenance_mode', false)) {
                $this->setMaintenanceMode(1);
                $ctx['maintenance_opened'] = true;
            }

            $this->markStep($taskId, 'applying_files', 70);
            $this->applyPackageFiles($ctx, (string)$task['tver']);

            $this->markStep($taskId, 'migrating_db', 82);
            $this->applyDatabaseMigrations($ctx);

            $this->markStep($taskId, 'finalizing', 95);
            $this->finalHealthCheck();

            Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
                'status' => 'success', 'step' => 'success', 'progress' => 100,
                'ctx' => json_encode($ctx, JSON_UNESCAPED_UNICODE),
                'ecode' => '', 'emsg' => '', 'u_at' => date('Y-m-d H:i:s'),
            ]);
            if (!empty($ctx['maintenance_opened'])) {
                $this->setMaintenanceMode(0);
            }
            $this->logTask($taskId, 'info', '升级成功完成');
            return ['code' => 1, 'msg' => '升级成功', 'data' => ['task_no' => $task['tno']]];
        } catch (Exception $e) {
            $rb = $this->rollbackByContext($ctx);
            if (!empty($ctx['maintenance_opened'])) {
                $this->setMaintenanceMode(0);
            }
            Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
                'status' => $rb['success'] ? 'rolled_back' : 'failed',
                'step' => $rb['success'] ? 'rolled_back' : ((string)Db::name(VersionSchema::T_TASK)->where('id', $taskId)->value('step') ?: 'failed'),
                'ecode' => 'ONLINE_UPDATE_FAILED',
                'emsg' => $e->getMessage(),
                'ctx' => json_encode($ctx, JSON_UNESCAPED_UNICODE),
                'u_at' => date('Y-m-d H:i:s'),
            ]);
            $this->logTask($taskId, 'error', '升级失败', ['error' => $e->getMessage(), 'rollback' => $rb]);
            return ['code' => 0, 'msg' => '升级失败: ' . $e->getMessage()];
        }
    }

    private function markStep(int $taskId, string $step, int $progress): void
    {
        Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
            'status' => $step, 'step' => $step, 'progress' => max(0, min($progress, 100)), 'u_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logTask($taskId, 'info', '状态推进: ' . $step, ['progress' => $progress]);
    }

    private function precheckOrFail(array $ctx): void
    {
        $mustWritable = [root_path() . 'runtime', root_path() . 'config', root_path() . 'app'];
        foreach ($mustWritable as $dir) {
            if (!is_dir($dir) || !is_writable($dir)) {
                throw new Exception('目录不可写: ' . $dir);
            }
        }
        if (!extension_loaded('zip')) {
            throw new Exception('缺少 zip 扩展');
        }
        $free = @disk_free_space(root_path());
        if ($free !== false && $free < 200 * 1024 * 1024) {
            throw new Exception('磁盘剩余空间不足 200MB');
        }
        if (!empty($ctx['check_data']['link']) && !$this->isAllowedHost((string)$ctx['check_data']['link'])) {
            throw new Exception('下载地址不在白名单内');
        }
    }

    private function downloadArtifacts(string $taskNo, array $checkData): array
    {
        $taskDir = rtrim((string)Config::get('version.update_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tasks' . DIRECTORY_SEPARATOR . $taskNo . DIRECTORY_SEPARATOR;
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
        }
        $packageUrl = (string)($checkData['link'] ?? '');
        $sqlUrl = (string)($checkData['sql'] ?? '');
        $ret = ['task_dir' => $taskDir, 'package_path' => '', 'sql_path' => ''];
        if ($packageUrl !== '') {
            $ret['package_path'] = $taskDir . 'update.zip';
            $bytes = @file_put_contents($ret['package_path'], $this->curlGet($packageUrl));
            if ($bytes === false || $bytes <= 0) {
                throw new Exception('下载更新包失败');
            }
        }
        if ($sqlUrl !== '') {
            if (!$this->isAllowedHost($sqlUrl)) {
                throw new Exception('SQL 下载地址不在白名单内');
            }
            $ret['sql_path'] = $taskDir . 'remote.sql';
            $bytes = @file_put_contents($ret['sql_path'], $this->curlGet($sqlUrl));
            if ($bytes === false || $bytes <= 0) {
                throw new Exception('下载远程 SQL 失败');
            }
        }
        return $ret;
    }

    private function verifyArtifactOrFail(array $ctx): void
    {
        $packagePath = (string)($ctx['package_path'] ?? '');
        if ($packagePath === '') {
            return;
        }
        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new Exception('更新包无法打开');
        }
        $json = $zip->getFromName('update_info.json');
        if ($json === false) {
            $zip->close();
            throw new Exception('更新包缺少 update_info.json');
        }
        $info = json_decode((string)$json, true);
        if (!is_array($info)) {
            $zip->close();
            throw new Exception('update_info.json 格式非法');
        }
        if (Config::get('version.strategy.require_signature', false) && empty($info['signature'])) {
            $zip->close();
            throw new Exception('策略要求验签，但未提供 signature');
        }
        if (!empty($info['signature'])) {
            if (!function_exists('openssl_verify')) {
                $zip->close();
                throw new Exception('当前环境不支持 openssl_verify，无法验签');
            }
            $pub = trim((string)Config::get('version.security.public_key', ''));
            if ($pub === '') {
                $zip->close();
                throw new Exception('更新包包含 signature 但未配置 public_key');
            }
            $data = (string)($info['version'] ?? '') . '|' . (string)($info['min_upgradable_version'] ?? '');
            $ok = openssl_verify($data, base64_decode((string)$info['signature']), $pub, OPENSSL_ALGO_SHA256);
            if ($ok !== 1) {
                $zip->close();
                throw new Exception('更新包签名验证失败');
            }
        }
        $zip->close();
    }

    private function createBackupSnapshot(string $taskNo): array
    {
        $src = root_path();
        $backupRoot = rtrim((string)Config::get('version.backup_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($backupRoot)) {
            mkdir($backupRoot, 0755, true);
        }
        $backupDir = $backupRoot . 'task_' . $taskNo . '_' . date('YmdHis') . DIRECTORY_SEPARATOR;
        mkdir($backupDir, 0755, true);
        $targets = ['app', 'config', 'route', 'public', 'extend'];
        foreach ($targets as $t) {
            $from = $src . $t;
            $to = $backupDir . $t;
            if (is_dir($from)) {
                $this->copyDir($from, $to);
            }
        }
        return ['backup_dir' => $backupDir];
    }

    private function applyPackageFiles(array $ctx, string $toVersion): void
    {
        $packagePath = (string)($ctx['package_path'] ?? '');
        if ($packagePath === '') {
            return;
        }
        $extractPath = (string)($ctx['task_dir'] ?? '') . 'extract' . DIRECTORY_SEPARATOR;
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new Exception('更新包打开失败');
        }
        $zip->extractTo($extractPath);
        $zip->close();

        $protected = array_values(array_unique(array_merge(
            (array)Config::get('version.strategy.protected_files', ['.env', 'config/site.php']),
            self::CRITICAL_PROTECTED_FILES
        )));

        // 升级前先做权限探测：发现任何不可写目标，直接终止，避免“写到一半失败”。
        $this->assertWritableForExtractedPackage($extractPath, $protected);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $rel = str_replace('\\', '/', str_replace($extractPath, '', $item->getPathname()));
            if ($rel === '' || strpos($rel, '../') !== false || preg_match('/^[A-Za-z]:\//', $rel)) {
                continue;
            }
            if (in_array($rel, ['update_info.json'], true) || strpos($rel, 'sql/') === 0) {
                continue;
            }
            if (in_array($rel, $protected, true)) {
                continue;
            }
            $dst = root_path() . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if ($item->isDir()) {
                if (!is_dir($dst)) {
                    if (!mkdir($dst, 0755, true) && !is_dir($dst)) {
                        throw new Exception('创建目录失败，请检查权限: ' . $dst);
                    }
                }
            } else {
                $this->safeCopyFile($item->getPathname(), $dst);
            }
        }

        $infoPath = $extractPath . 'update_info.json';
        if (is_file($infoPath)) {
            $info = json_decode((string)file_get_contents($infoPath), true);
            if (is_array($info) && !empty($info['delete_list']) && is_array($info['delete_list'])) {
                foreach ($info['delete_list'] as $del) {
                    $del = str_replace('\\', '/', (string)$del);
                    if ($del === '' || strpos($del, '../') !== false || preg_match('/^[A-Za-z]:\//', $del) || in_array($del, $protected, true)) {
                        continue;
                    }
                    $target = root_path() . str_replace('/', DIRECTORY_SEPARATOR, $del);
                    if (is_file($target)) {
                        @unlink($target);
                    }
                }
            }
        }

        if ($toVersion !== '') {
            (new VersionService())->setCurrentVersion($toVersion);
            (new DatabaseMigrationService())->setDatabaseVersion($toVersion);
        }
    }

    private function applyRemoteSql(array $ctx): void
    {
        $sqlPath = (string)($ctx['sql_path'] ?? '');
        if ($sqlPath === '' || !is_file($sqlPath)) {
            return;
        }
        $this->executeSqlScriptAndRecord($sqlPath);
    }

    /**
     * 执行更新包中的 SQL（sql/*.sql）。
     * 优先读取 update_info.json 的 sql_files；若未配置则自动扫描 sql 目录。
     */
    private function applyPackageSql(array $ctx): void
    {
        $taskDir = (string)($ctx['task_dir'] ?? '');
        if ($taskDir === '') {
            return;
        }
        $extractPath = rtrim($taskDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'extract' . DIRECTORY_SEPARATOR;
        if (!is_dir($extractPath)) {
            return;
        }

        $sqlDir = $extractPath . 'sql' . DIRECTORY_SEPARATOR;
        if (!is_dir($sqlDir)) {
            return;
        }

        $sqlFiles = [];
        $infoPath = $extractPath . 'update_info.json';
        if (is_file($infoPath)) {
            $info = json_decode((string)file_get_contents($infoPath), true);
            if (is_array($info) && !empty($info['sql_files']) && is_array($info['sql_files'])) {
                foreach ($info['sql_files'] as $name) {
                    $name = (string)$name;
                    if ($name !== '' && substr($name, -4) === '.sql') {
                        $sqlFiles[] = $name;
                    }
                }
            }
        }

        if (empty($sqlFiles)) {
            foreach ((array)glob($sqlDir . '*.sql') as $path) {
                if (is_file($path)) {
                    $sqlFiles[] = basename($path);
                }
            }
        }

        $sqlFiles = array_values(array_unique($sqlFiles));
        sort($sqlFiles);
        foreach ($sqlFiles as $sqlFile) {
            $sqlPath = $sqlDir . $sqlFile;
            if (!is_file($sqlPath)) {
                throw new Exception('更新包 SQL 文件不存在: ' . $sqlFile);
            }
            $this->executeSqlScriptAndRecord($sqlPath);
        }
    }

    private function executeSqlScriptAndRecord(string $sqlPath): void
    {
        $sql = (string)file_get_contents($sqlPath);
        if ($sql === '') {
            return;
        }
        $sql = $this->rewriteSqlTablePrefix($sql);
        $res = (new DatabaseMigrationService())->executeSql($sql);
        Db::name(VersionSchema::T_MIG)->insert([
            'ver' => (new VersionService())->getCurrentVersion(),
            'sfile' => basename($sqlPath),
            'csum' => hash((string)Config::get('version.security.hash_algo', 'sha256'), $sql),
            'run_at' => date('Y-m-d H:i:s'),
            'status' => $res['success'] ? 'success' : 'failed',
        ]);
        if (!$res['success']) {
            throw new Exception((string)$res['message']);
        }
    }

    private function applyDatabaseMigrations(array $ctx): void
    {
        // 兼容旧行为：优先执行远程 SQL。
        $this->applyRemoteSql($ctx);
        // 新增：执行更新包内 sql/*.sql。
        $this->applyPackageSql($ctx);
    }

    private function finalHealthCheck(): void
    {
        $base = Request::domain();
        if ($base === '') {
            return;
        }
        $checks = ['/', '/admin/login'];
        foreach ($checks as $path) {
            $url = rtrim($base, '/') . $path;
            $out = $this->curlGet($url);
            if ($out === '') {
                throw new Exception('升级后健康检查失败: ' . $path);
            }
        }
    }

    private function rollbackByContext(array $ctx): array
    {
        $backup = (string)($ctx['backup_dir'] ?? '');
        if ($backup === '' || !is_dir($backup)) {
            return ['success' => false, 'message' => '无可用备份目录'];
        }
        try {
            foreach (['app', 'config', 'route', 'public', 'extend'] as $dir) {
                $src = $backup . $dir;
                if (!is_dir($src)) {
                    continue;
                }
                $dst = root_path() . $dir;
                $this->removeDir($dst);
                $this->copyDir($src, $dst);
            }
            return ['success' => true, 'message' => '文件回滚成功'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function initTables(): void
    {
        Db::execute("CREATE TABLE IF NOT EXISTS `" . $this->table(VersionSchema::T_TASK) . "` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `tno` varchar(50) NOT NULL,
            `fver` varchar(32) NOT NULL DEFAULT '',
            `tver` varchar(32) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `step` varchar(32) NOT NULL DEFAULT 'pending',
            `progress` tinyint unsigned NOT NULL DEFAULT 0,
            `op_id` int unsigned NOT NULL DEFAULT 0,
            `idem` varchar(80) NOT NULL DEFAULT '',
            `ecode` varchar(64) NOT NULL DEFAULT '',
            `emsg` text,
            `ctx` longtext,
            `c_at` datetime NOT NULL,
            `u_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_tno` (`tno`),
            KEY `i_st` (`status`),
            KEY `i_idem` (`idem`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新任务'");
        Db::execute("CREATE TABLE IF NOT EXISTS `" . $this->table(VersionSchema::T_LOG) . "` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `tid` bigint unsigned NOT NULL,
            `level` varchar(16) NOT NULL DEFAULT 'info',
            `message` varchar(255) NOT NULL DEFAULT '',
            `ctx` longtext,
            `c_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `i_tid` (`tid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新日志'");
        Db::execute("CREATE TABLE IF NOT EXISTS `" . $this->table(VersionSchema::T_MIG) . "` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `ver` varchar(32) NOT NULL DEFAULT '',
            `sfile` varchar(255) NOT NULL DEFAULT '',
            `csum` varchar(128) NOT NULL DEFAULT '',
            `run_at` datetime NOT NULL,
            `status` varchar(16) NOT NULL DEFAULT 'success',
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_vs` (`ver`,`sfile`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SQL迁移记录'");
        Db::execute("CREATE TABLE IF NOT EXISTS `" . $this->table(VersionSchema::T_LOCK) . "` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `lkey` varchar(64) NOT NULL,
            `exp_at` datetime NOT NULL,
            `c_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_lkey` (`lkey`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新锁'");
    }

    private function acquireLock(): bool
    {
        try {
            Db::name(VersionSchema::T_LOCK)->where('exp_at', '<', date('Y-m-d H:i:s'))->delete();
            Db::name(VersionSchema::T_LOCK)->insert([
                'lkey' => self::LOCK_KEY,
                'exp_at' => date('Y-m-d H:i:s', time() + 1800),
                'c_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function releaseLock(): void
    {
        Db::name(VersionSchema::T_LOCK)->where('lkey', self::LOCK_KEY)->delete();
    }

    private function logTask(int $taskId, string $level, string $message, array $context = []): void
    {
        Db::name(VersionSchema::T_LOG)->insert([
            'tid' => $taskId,
            'level' => $level,
            'message' => $message,
            'ctx' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'c_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function getServerConfig(): array
    {
        $siteCfg = is_file(config_path() . 'site.php') ? (array)require config_path() . 'site.php' : [];
        $authcode = (string)($siteCfg['authcode'] ?? '');
        $base = rtrim(self::ONLINE_UPDATE_BASE_URL, '/');
        $check = self::ONLINE_UPDATE_CHECK_ENDPOINT;
        $appId = self::ONLINE_UPDATE_APP_ID;
        $domain = preg_replace('/:\d+$/', '', (string)Request::host());

        if ($authcode === '') {
            return ['error' => 'config/site.php 中 authcode 未配置'];
        }
        if ($base === '') {
            return ['error' => '在线更新服务端 base_url 未配置（代码常量）'];
        }
        return [
            'error' => '',
            'app_id' => $appId > 0 ? $appId : 1,
            'authcode' => $authcode,
            'domain' => $domain,
            'check_url' => $base . '/' . ltrim($check, '/'),
        ];
    }

    private function httpPostJson(string $url, array $payload): array
    {
        $retry = max(0, (int)Config::get('version.http.retry_times', 2));
        $timeout = max(1, (int)Config::get('version.http.timeout', 30));
        $connect = max(1, (int)Config::get('version.http.connect_timeout', 8));
        $lastErr = '请求失败';
        for ($i = 0; $i <= $retry; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                $lastErr = $err !== '' ? $err : '网络错误';
                continue;
            }
            $data = json_decode((string)$raw, true);
            if (!is_array($data)) {
                $lastErr = '响应不是 JSON';
                continue;
            }
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => $lastErr];
    }

    private function isAllowedHost(string $url): bool
    {
        $allowed = (array)Config::get('version.security.allowed_hosts', []);
        if (empty($allowed)) {
            return true;
        }
        $host = (string)parse_url($url, PHP_URL_HOST);
        return in_array($host, $allowed, true);
    }

    private function curlGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, max(1, (int)Config::get('version.http.timeout', 30)));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $out = curl_exec($ch);
        curl_close($ch);
        return $out !== false ? (string)$out : '';
    }

    private function copyDir(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $dir = dirname($target);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($item->getPathname(), $target);
            }
        }
    }

    private function assertWritableForExtractedPackage(string $extractPath, array $protected): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $rel = str_replace('\\', '/', str_replace($extractPath, '', $item->getPathname()));
            if ($rel === '' || strpos($rel, '../') !== false || preg_match('/^[A-Za-z]:\//', $rel)) {
                continue;
            }
            if (in_array($rel, ['update_info.json'], true) || strpos($rel, 'sql/') === 0) {
                continue;
            }
            if (in_array($rel, $protected, true)) {
                continue;
            }

            $dst = root_path() . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if ($item->isDir()) {
                if (is_dir($dst)) {
                    if (!is_writable($dst)) {
                        throw new Exception('权限不足: 目录不可写 ' . $dst);
                    }
                    continue;
                }

                $parent = dirname($dst);
                if (!is_dir($parent) || !is_writable($parent)) {
                    throw new Exception('权限不足: 无法创建目录 ' . $dst);
                }
                continue;
            }

            $dir = dirname($dst);
            if (!is_dir($dir) || !is_writable($dir)) {
                throw new Exception('权限不足: 目标目录不可写 ' . $dir);
            }
            if (is_file($dst) && !is_writable($dst)) {
                throw new Exception('权限不足: 目标文件不可写 ' . $dst);
            }
        }
    }

    private function safeCopyFile(string $source, string $destination): void
    {
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new Exception('创建目录失败，请检查权限: ' . $dir);
            }
        }
        if (!is_writable($dir)) {
            throw new Exception('目标目录无写权限: ' . $dir);
        }

        if (is_file($destination) && !is_writable($destination)) {
            throw new Exception('目标文件无写权限: ' . $destination);
        }

        if (!copy($source, $destination)) {
            throw new Exception('写入文件失败，请检查目录/文件权限: ' . $destination);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    private function decodeJson(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 将更新包 SQL 中固定前缀（默认 ad_）替换为当前站点前缀。
     * 仅替换反引号包裹的表名，避免误改普通字符串内容。
     */
    private function rewriteSqlTablePrefix(string $sql): string
    {
        $sourcePrefix = VersionSchema::SQL_SOURCE_PREFIX;
        $targetPrefix = (string)config('database.connections.mysql.prefix', '');
        if ($sourcePrefix === '' || $sourcePrefix === $targetPrefix) {
            return $sql;
        }

        $pattern = '/`' . preg_quote($sourcePrefix, '/') . '([A-Za-z0-9_]+)`/';
        return (string)preg_replace($pattern, '`' . $targetPrefix . '$1`', $sql);
    }

    private function table(string $name): string
    {
        $prefix = (string)config('database.connections.mysql.prefix', '');
        return $prefix . $name;
    }

    private function setMaintenanceMode(int $enabled): void
    {
        SystemConfig::setValue('system_maintenance_mode', (string)$enabled, '维护模式', 'number');
    }
}

