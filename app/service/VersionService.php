<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use app\service\DatabaseMigrationService;
use ZipArchive;
use Exception;

/**
 * 版本管理服务类
 */
class VersionService
{
    private const UPDATE_LOCK_KEY = 'online_update_lock';
    
    // 在线更新服务端配置：避免在 config/version.php 暴露敏感参数
    private const ONLINE_UPDATE_APP_ID = 1;
    private const ONLINE_UPDATE_BASE_URL = 'https://www.bitewu.com';
    private const ONLINE_UPDATE_CHECK_ENDPOINT = '/api/check/update';

    /**
     * 获取当前版本号
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return Config::get('version.current_version', '1.0.0');
    }

    /**
     * 设置当前版本号
     * @param string $version
     * @return bool
     */
    public function setCurrentVersion(string $version): bool
    {
        $configFile = config_path() . 'version.php';
        $content = file_get_contents($configFile);
        $content = preg_replace(
            "/'current_version'\s*=>\s*['\"](.*?)['\"]/",
            "'current_version' => '{$version}'",
            $content
        );
        return file_put_contents($configFile, $content) !== false;
    }

    /**
     * 检查是否有新版本
     * @param string $remoteVersion 远程版本号
     * @return array
     */
    public function checkUpdate(string $remoteVersion = ''): array
    {
        $currentVersion = $this->getCurrentVersion();
        
        // 如果没有提供远程版本号，尝试从服务器获取
        if (empty($remoteVersion)) {
            $updateServer = Config::get('version.update_server', '');
            if (!empty($updateServer)) {
                $remoteVersion = $this->getRemoteVersion($updateServer);
            }
        }
        
        if (empty($remoteVersion)) {
            return [
                'has_update' => false,
                'current_version' => $currentVersion,
                'message' => '无法获取远程版本信息'
            ];
        }
        
        $hasUpdate = version_compare($remoteVersion, $currentVersion, '>');
        
        return [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $remoteVersion,
            'message' => $hasUpdate ? '发现新版本' : '当前已是最新版本'
        ];
    }

    /**
     * 在线检查更新（对接 YNova_Auth 检查接口）
     */
    public function checkOnlineUpdate(): array
    {
        if (!Config::get('version.online_update.enabled', false)) {
            return ['code' => 0, 'msg' => '在线更新已关闭'];
        }

        $this->initUpdateTables();
        $cfg = $this->buildCheckConfig();
        if ($cfg['error'] !== '') {
            return ['code' => 0, 'msg' => $cfg['error']];
        }

        $payload = [
            'app' => self::ONLINE_UPDATE_APP_ID,
            'authcode' => $cfg['authcode'],
            'version' => $this->getCurrentVersion(),
            'dbversion' => (new DatabaseMigrationService())->getDatabaseVersion(),
            'domain' => $cfg['domain'],
        ];

        $result = $this->requestWithRetry($cfg['url'], $payload);
        if (!$result['success']) {
            return ['code' => 0, 'msg' => $result['message']];
        }

        $remote = $result['data'];
        $remoteCode = (int)($remote['code'] ?? 0);
        $hasUpdate = in_array($remoteCode, [1, 2], true);
        $latestVersion = (string)($remote['version'] ?? '');

        return [
            'code' => 1,
            'msg' => (string)($remote['msg'] ?? ($hasUpdate ? '发现新版本' : '当前已是最新版本')),
            'data' => [
                'has_update' => $hasUpdate,
                'current_version' => $payload['version'],
                'latest_version' => $latestVersion,
                'raw' => $remote,
            ],
        ];
    }

    /**
     * 创建在线更新任务
     */
    public function createUpdateTask(string $toVersion, int $adminId, array $checkData = []): array
    {
        if (!Config::get('version.online_update.enabled', false)) {
            return ['code' => 0, 'msg' => '在线更新已关闭'];
        }
        if ($toVersion === '') {
            return ['code' => 0, 'msg' => 'to_version 不能为空'];
        }

        $this->initUpdateTables();
        $taskNo = 'UPD' . date('YmdHis') . strtoupper(substr(md5((string)mt_rand()), 0, 6));
        $now = date('Y-m-d H:i:s');

        $taskId = (int)Db::name(VersionSchema::T_TASK)->insertGetId([
            'tno' => $taskNo,
            'fver' => $this->getCurrentVersion(),
            'tver' => $toVersion,
            'status' => 'pending',
            'step' => 'pending',
            'progress' => 0,
            'op_id' => $adminId,
            'idem' => '',
            'ecode' => '',
            'emsg' => '',
            'ctx' => json_encode(['check_data' => $checkData], JSON_UNESCAPED_UNICODE),
            'c_at' => $now,
            'u_at' => $now,
        ]);
        $this->addTaskLog($taskId, 'info', '升级任务已创建', ['task_no' => $taskNo, 'to_version' => $toVersion]);

        return ['code' => 1, 'msg' => '任务创建成功', 'data' => ['task_no' => $taskNo, 'task_id' => $taskId]];
    }

    /**
     * 启动在线更新任务（串行执行）
     */
    public function startUpdateTask(string $taskNo): array
    {
        $this->initUpdateTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        if (!$this->acquireUpdateLock()) {
            return ['code' => 0, 'msg' => '当前已有升级任务在执行，请稍后重试'];
        }

        try {
            $taskId = (int)$task['id'];
            $context = $this->decodeJsonArray((string)($task['ctx'] ?? ''));

            $this->updateTaskState($taskId, 'checking', 10);
            $check = $this->checkOnlineUpdate();
            if (($check['code'] ?? 0) !== 1 || empty($check['data']['has_update'])) {
                throw new Exception((string)($check['msg'] ?? '未发现可用更新'));
            }
            $raw = $check['data']['raw'] ?? [];
            $context['check_data'] = $raw;

            $this->updateTaskState($taskId, 'downloaded', 30);
            $download = $this->downloadUpdatePackage($taskNo, $raw);
            if (!$download['success']) {
                throw new Exception($download['message']);
            }
            $context = array_merge($context, $download['context']);

            $this->updateTaskState($taskId, 'verified', 45);
            $verify = $this->verifyDownloadedPackage($context);
            if (!$verify['success']) {
                throw new Exception($verify['message']);
            }

            $this->updateTaskState($taskId, 'backed_up', 60);
            if (!$this->backupSystem()) {
                throw new Exception('系统备份失败，已终止升级');
            }

            $this->updateTaskState($taskId, 'applying_files', 75);
            $applyResult = $this->applyUpdate((string)$context['package_path'], (string)($task['tver'] ?? ''), 'incremental');
            if (!$applyResult['success']) {
                throw new Exception((string)$applyResult['message']);
            }

            $this->updateTaskState($taskId, 'migrating_db', 90);
            $this->applyRemoteSqlIfNeeded($taskId, $context);

            $this->updateTaskState($taskId, 'finalizing', 98);
            Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
                'status' => 'success',
                'step' => 'success',
                'progress' => 100,
                'ecode' => '',
                'emsg' => '',
                'ctx' => json_encode($context, JSON_UNESCAPED_UNICODE),
                'u_at' => date('Y-m-d H:i:s'),
            ]);
            $this->addTaskLog($taskId, 'info', '升级完成', ['to_version' => (string)$task['tver']]);

            return ['code' => 1, 'msg' => '升级完成', 'data' => ['task_no' => $taskNo]];
        } catch (Exception $e) {
            $taskId = (int)$task['id'];
            Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
                'status' => 'failed',
                'step' => (string)($this->getTaskStep($taskId) ?: 'failed'),
                'ecode' => 'ONLINE_UPDATE_FAILED',
                'emsg' => $e->getMessage(),
                'u_at' => date('Y-m-d H:i:s'),
            ]);
            $this->addTaskLog($taskId, 'error', '升级失败', ['error' => $e->getMessage()]);
            return ['code' => 0, 'msg' => '升级失败: ' . $e->getMessage()];
        } finally {
            $this->releaseUpdateLock();
        }
    }

    public function getTaskStatus(string $taskNo): array
    {
        $this->initUpdateTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        return ['code' => 1, 'msg' => '获取成功', 'data' => VersionSchema::taskRowToApi($task)];
    }

    public function getTaskLogs(string $taskNo, int $limit = 100): array
    {
        $this->initUpdateTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        $logs = Db::name(VersionSchema::T_LOG)
            ->where('tid', (int)$task['id'])
            ->order('id', 'desc')
            ->limit(max(1, min($limit, 500)))
            ->select()
            ->toArray();
        $out = [];
        foreach ($logs as $row) {
            $out[] = VersionSchema::logRowToApi($row);
        }
        return ['code' => 1, 'msg' => '获取成功', 'data' => $out];
    }

    public function retryTask(string $taskNo): array
    {
        $this->initUpdateTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }
        if (!in_array((string)$task['status'], ['failed', 'rolled_back'], true)) {
            return ['code' => 0, 'msg' => '仅失败或已回滚任务可重试'];
        }

        Db::name(VersionSchema::T_TASK)->where('id', (int)$task['id'])->update([
            'status' => 'pending',
            'step' => 'pending',
            'progress' => 0,
            'ecode' => '',
            'emsg' => '',
            'u_at' => date('Y-m-d H:i:s'),
        ]);
        $this->addTaskLog((int)$task['id'], 'info', '任务已重置为待执行，准备重试');

        return $this->startUpdateTask($taskNo);
    }

    public function rollbackTask(string $taskNo): array
    {
        $this->initUpdateTables();
        $task = Db::name(VersionSchema::T_TASK)->where('tno', $taskNo)->find();
        if (!$task) {
            return ['code' => 0, 'msg' => '任务不存在'];
        }

        Db::name(VersionSchema::T_TASK)->where('id', (int)$task['id'])->update([
            'status' => 'rolled_back',
            'step' => 'rolled_back',
            'u_at' => date('Y-m-d H:i:s'),
        ]);
        $this->addTaskLog((int)$task['id'], 'warning', '任务已标记为手动回滚');

        return ['code' => 1, 'msg' => '任务已标记回滚'];
    }

    /**
     * 从远程服务器获取版本号
     * @param string $serverUrl
     * @return string
     */
    private function getRemoteVersion(string $serverUrl): string
    {
        try {
            $url = rtrim($serverUrl, '/') . '/api/version/check';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                return $data['version'] ?? '';
            }
        } catch (Exception $e) {
        }
        return '';
    }

    /**
     * 备份当前系统
     * @return bool
     */
    public function backupSystem(): bool
    {
        if (!Config::get('version.auto_backup', true)) {
            return true;
        }

        try {
            $backupPath = Config::get('version.backup_path');
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $backupDir = $backupPath . date('YmdHis') . '_' . $this->getCurrentVersion() . DIRECTORY_SEPARATOR;
            mkdir($backupDir, 0755, true);

            // 备份应用目录
            $this->copyDirectory(app_path(), $backupDir . 'app');
            
            // 备份配置文件
            $this->copyDirectory(config_path(), $backupDir . 'config');
            
            // 备份路由文件
            if (is_dir(route_path())) {
                $this->copyDirectory(route_path(), $backupDir . 'route');
            }

            $this->writeLog('系统备份完成: ' . $backupDir);
            return true;
        } catch (Exception $e) {
            $this->writeLog('系统备份失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 应用更新包
     * @param string $updatePackagePath 更新包路径
     * @param string $newVersion 新版本号
     * @param string $updateMode 更新模式: 'full'全包更新, 'incremental'新增更新
     * @return array
     */
    public function applyUpdate(string $updatePackagePath, string $newVersion = '', string $updateMode = 'incremental'): array
    {
        try {
            // 检查更新包是否存在
            if (!file_exists($updatePackagePath)) {
                return [
                    'success' => false,
                    'message' => '更新包文件不存在'
                ];
            }

            // 备份系统
            if (!$this->backupSystem()) {
                return [
                    'success' => false,
                    'message' => '系统备份失败，更新已取消'
                ];
            }

            // 解压更新包
            $extractPath = Config::get('version.update_path') . 'extract' . DIRECTORY_SEPARATOR;
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($updatePackagePath) !== true) {
                return [
                    'success' => false,
                    'message' => '无法打开更新包文件'
                ];
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // 读取更新包信息
            $updateInfo = $this->readUpdateInfo($extractPath);
            
            // 应用数据库更新
            $dbResult = $this->applyDatabaseUpdate($extractPath, $updateInfo);
            
            // 应用文件更新（根据更新模式）
            $this->applyUpdateFiles($extractPath, $updateMode);

            // 更新版本号
            if (!empty($newVersion)) {
                $this->setCurrentVersion($newVersion);
            } elseif (!empty($updateInfo['version'])) {
                $this->setCurrentVersion($updateInfo['version']);
            }

            // 记录数据库版本
            if (!empty($updateInfo['version'])) {
                $dbMigration = new DatabaseMigrationService();
                $dbMigration->setDatabaseVersion($updateInfo['version']);
            }

            // 清理临时文件
            $this->deleteDirectory($extractPath);

            $message = '更新成功';
            if (!$dbResult['success']) {
                $message .= '，但数据库更新有警告: ' . $dbResult['message'];
            }

            $this->writeLog('更新应用成功: ' . $updatePackagePath);
            return [
                'success' => true,
                'message' => $message,
                'db_result' => $dbResult
            ];
        } catch (Exception $e) {
            $this->writeLog('更新应用失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '更新失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 读取更新包信息
     * @param string $extractPath
     * @return array
     */
    private function readUpdateInfo(string $extractPath): array
    {
        $infoFile = $extractPath . 'update_info.json';
        $info = [
            'version' => '',
            'description' => '',
            'sql_files' => []
        ];

        if (file_exists($infoFile)) {
            $content = file_get_contents($infoFile);
            $data = json_decode($content, true);
            if ($data) {
                $info = array_merge($info, $data);
            }
        }

        // 自动查找SQL文件
        $sqlDir = $extractPath . 'sql' . DIRECTORY_SEPARATOR;
        if (is_dir($sqlDir)) {
            $files = glob($sqlDir . '*.sql');
            foreach ($files as $file) {
                $info['sql_files'][] = basename($file);
            }
        }

        return $info;
    }

    /**
     * 应用数据库更新
     * @param string $extractPath
     * @param array $updateInfo
     * @return array
     */
    private function applyDatabaseUpdate(string $extractPath, array $updateInfo): array
    {
        if (empty($updateInfo['sql_files'])) {
            return [
                'success' => true,
                'message' => '无需更新数据库'
            ];
        }

        $dbMigration = new DatabaseMigrationService();
        $results = [];
        $hasError = false;

        foreach ($updateInfo['sql_files'] as $sqlFile) {
            $sqlPath = $extractPath . 'sql' . DIRECTORY_SEPARATOR . $sqlFile;
            if (file_exists($sqlPath)) {
                $result = $dbMigration->executeSqlFile($sqlPath);
                $results[] = [
                    'file' => $sqlFile,
                    'result' => $result
                ];
                if (!$result['success']) {
                    $hasError = true;
                }
            }
        }

        return [
            'success' => !$hasError,
            'message' => $hasError ? '部分SQL文件执行失败' : '数据库更新成功',
            'details' => $results
        ];
    }

    /**
     * 应用更新文件
     * @param string $extractPath 解压路径
     * @param string $updateMode 更新模式: 'full'全包更新, 'incremental'新增更新
     * @return void
     */
    private function applyUpdateFiles(string $extractPath, string $updateMode = 'incremental'): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        // 需要排除的文件和目录
        $excludePaths = [
            'update_info.json',
            'sql' . DIRECTORY_SEPARATOR
        ];

        // 全包更新：先清理目标目录（如果更新包中有删除标记）
        if ($updateMode === 'full') {
            $this->prepareFullUpdate($extractPath);
        }

        $updatedFiles = [];
        $createdDirs = [];

        foreach ($iterator as $item) {
            $relativePath = str_replace($extractPath, '', $item->getPathname());
            
            // 跳过排除的文件
            $shouldExclude = false;
            foreach ($excludePaths as $exclude) {
                if (strpos($relativePath, $exclude) !== false) {
                    $shouldExclude = true;
                    break;
                }
            }
            if ($shouldExclude) {
                continue;
            }
            
            $targetPath = root_path() . $relativePath;
            
            if ($item->isDir()) {
                // 创建目录
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                    $createdDirs[] = $targetPath;
                }
            } else {
                // 新增更新模式：只更新存在的文件或新增文件
                if ($updateMode === 'incremental') {
                    // 如果目标文件不存在，或者源文件更新，则更新
                    if (!file_exists($targetPath) || filemtime($item->getPathname()) > filemtime($targetPath)) {
                        $targetDir = dirname($targetPath);
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                            $createdDirs[] = $targetDir;
                        }
                        copy($item->getPathname(), $targetPath);
                        $updatedFiles[] = $relativePath;
                    }
                } else {
                    // 全包更新模式：直接覆盖所有文件
                    $targetDir = dirname($targetPath);
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                        $createdDirs[] = $targetDir;
                    }
                    copy($item->getPathname(), $targetPath);
                    $updatedFiles[] = $relativePath;
                }
            }
        }

        // 记录更新信息
        $this->writeLog(sprintf(
            '文件更新完成 - 模式: %s, 更新文件数: %d, 创建目录数: %d',
            $updateMode === 'full' ? '全包更新' : '新增更新',
            count($updatedFiles),
            count($createdDirs)
        ));
    }

    /**
     * 准备全包更新（处理删除标记文件）
     * @param string $extractPath
     * @return void
     */
    private function prepareFullUpdate(string $extractPath): void
    {
        // 读取删除列表文件（如果存在）
        $deleteListFile = $extractPath . 'delete_list.txt';
        if (file_exists($deleteListFile)) {
            $deleteList = file($deleteListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($deleteList as $fileToDelete) {
                $fileToDelete = trim($fileToDelete);
                if (empty($fileToDelete) || strpos($fileToDelete, '..') !== false) {
                    continue; // 跳过空行和危险路径
                }
                
                $targetPath = root_path() . $fileToDelete;
                if (file_exists($targetPath) && is_file($targetPath)) {
                    unlink($targetPath);
                    $this->writeLog('全包更新删除文件: ' . $fileToDelete);
                }
            }
        }
    }

    /**
     * 上传更新包
     * @param \think\File $file
     * @return array
     */
    public function uploadUpdatePackage($file): array
    {
        try {
            // 验证文件类型
            $allowedTypes = ['application/zip', 'application/x-zip-compressed'];
            if (!in_array($file->getMime(), $allowedTypes) && $file->getOriginalExtension() !== 'zip') {
                return [
                    'success' => false,
                    'message' => '只允许上传ZIP格式的更新包'
                ];
            }

            $updatePath = Config::get('version.update_path');
            if (!is_dir($updatePath)) {
                mkdir($updatePath, 0755, true);
            }

            $fileName = 'update_' . date('YmdHis') . '_' . uniqid() . '.zip';
            $filePath = $updatePath . $fileName;

            $file->move($updatePath, $fileName);

            $this->writeLog('更新包上传成功: ' . $fileName);
            return [
                'success' => true,
                'message' => '上传成功',
                'file_path' => $filePath,
                'file_name' => $fileName
            ];
        } catch (Exception $e) {
            $this->writeLog('更新包上传失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '上传失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取更新历史记录
     * @param int $limit
     * @return array
     */
    public function getUpdateHistory(int $limit = 10): array
    {
        $logFile = Config::get('version.log_file');
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile);
        $history = [];
        $count = 0;

        for ($i = count($lines) - 1; $i >= 0 && $count < $limit; $i--) {
            $line = trim($lines[$i]);
            if (!empty($line)) {
                $history[] = [
                    'time' => substr($line, 0, 19),
                    'message' => substr($line, 20)
                ];
                $count++;
            }
        }

        return $history;
    }

    /**
     * 复制目录
     * @param string $source
     * @param string $destination
     * @return void
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * 删除目录
     * @param string $dir
     * @return void
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * 写入更新日志
     * @param string $message
     * @return void
     */
    private function writeLog(string $message): void
    {
        $logFile = Config::get('version.log_file');
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logMessage = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    private function buildCheckConfig(): array
    {
        $siteCfg = is_file(config_path() . 'site.php') ? (array)require config_path() . 'site.php' : [];
        $authcode = (string)($siteCfg['authcode'] ?? '');
        $baseUrl = rtrim(self::ONLINE_UPDATE_BASE_URL, '/');
        $endpoint = self::ONLINE_UPDATE_CHECK_ENDPOINT;
        $domain = preg_replace('/:\d+$/', '', (string)Request::host());
        $url = $baseUrl . '/' . ltrim($endpoint, '/');

        if ($authcode === '') {
            return ['error' => 'config/site.php 中 authcode 未配置'];
        }
        if ($baseUrl === '') {
            return ['error' => '在线更新服务端 base_url 未配置（代码常量）'];
        }
        return ['error' => '', 'authcode' => $authcode, 'domain' => $domain, 'url' => $url];
    }

    private function requestWithRetry(string $url, array $data): array
    {
        $retryTimes = max(0, (int)Config::get('version.http.retry_times', 2));
        $timeout = max(1, (int)Config::get('version.http.timeout', 30));
        $connectTimeout = max(1, (int)Config::get('version.http.connect_timeout', 8));
        $lastError = '请求失败';

        for ($i = 0; $i <= $retryTimes; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($raw !== false) {
                $json = json_decode((string)$raw, true);
                if (is_array($json)) {
                    return ['success' => true, 'data' => $json];
                }
                $lastError = '响应非 JSON 格式';
            } else {
                $lastError = $err !== '' ? $err : '网络请求失败';
            }
        }
        return ['success' => false, 'message' => $lastError];
    }

    private function initUpdateTables(): void
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

    private function addTaskLog(int $taskId, string $level, string $message, array $context = []): void
    {
        Db::name(VersionSchema::T_LOG)->insert([
            'tid' => $taskId,
            'level' => $level,
            'message' => $message,
            'ctx' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'c_at' => date('Y-m-d H:i:s'),
        ]);
        $this->writeLog("[Task#{$taskId}] {$message}");
    }

    private function updateTaskState(int $taskId, string $step, int $progress): void
    {
        Db::name(VersionSchema::T_TASK)->where('id', $taskId)->update([
            'status' => $step,
            'step' => $step,
            'progress' => $progress,
            'u_at' => date('Y-m-d H:i:s'),
        ]);
        $this->addTaskLog($taskId, 'info', '状态推进: ' . $step, ['progress' => $progress]);
    }

    private function acquireUpdateLock(): bool
    {
        try {
            Db::name(VersionSchema::T_LOCK)->where('exp_at', '<', date('Y-m-d H:i:s'))->delete();
            Db::name(VersionSchema::T_LOCK)->insert([
                'lkey' => self::UPDATE_LOCK_KEY,
                'exp_at' => date('Y-m-d H:i:s', time() + 1800),
                'c_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function releaseUpdateLock(): void
    {
        Db::name(VersionSchema::T_LOCK)->where('lkey', self::UPDATE_LOCK_KEY)->delete();
    }

    private function getTaskStep(int $taskId): string
    {
        $step = Db::name(VersionSchema::T_TASK)->where('id', $taskId)->value('step');
        return is_string($step) ? $step : '';
    }

    private function downloadUpdatePackage(string $taskNo, array $checkRaw): array
    {
        $downloadUrl = (string)($checkRaw['link'] ?? $checkRaw['download_url'] ?? '');
        $sqlUrl = (string)($checkRaw['sql'] ?? $checkRaw['sql_url'] ?? '');
        if ($downloadUrl === '' && $sqlUrl === '') {
            return ['success' => false, 'message' => '检查结果不包含下载地址'];
        }
        if ($downloadUrl !== '' && !$this->isAllowedDownloadUrl($downloadUrl)) {
            return ['success' => false, 'message' => '下载地址不在允许的白名单域名内'];
        }

        $taskDir = rtrim((string)Config::get('version.update_path'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'tasks' . DIRECTORY_SEPARATOR . $taskNo . DIRECTORY_SEPARATOR;
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
        }

        $context = ['task_dir' => $taskDir, 'package_path' => '', 'sql_path' => ''];
        if ($downloadUrl !== '') {
            $packagePath = $taskDir . 'update.zip';
            $ok = @file_put_contents($packagePath, $this->curlGet($downloadUrl));
            if ($ok === false || !file_exists($packagePath) || filesize($packagePath) <= 0) {
                return ['success' => false, 'message' => '更新包下载失败'];
            }
            $context['package_path'] = $packagePath;
        }

        if ($sqlUrl !== '') {
            if (!$this->isAllowedDownloadUrl($sqlUrl)) {
                return ['success' => false, 'message' => 'SQL 地址不在允许的白名单域名内'];
            }
            $sqlPath = $taskDir . 'remote_update.sql';
            $ok = @file_put_contents($sqlPath, $this->curlGet($sqlUrl));
            if ($ok !== false && file_exists($sqlPath) && filesize($sqlPath) > 0) {
                $context['sql_path'] = $sqlPath;
            }
        }

        return ['success' => true, 'context' => $context];
    }

    private function verifyDownloadedPackage(array $context): array
    {
        $packagePath = (string)($context['package_path'] ?? '');
        if ($packagePath === '') {
            return ['success' => true, 'message' => '无文件包，仅数据库更新'];
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            return ['success' => false, 'message' => '更新包损坏或非 ZIP 文件'];
        }
        $zip->close();

        return ['success' => true, 'message' => '更新包校验通过'];
    }

    private function applyRemoteSqlIfNeeded(int $taskId, array $context): void
    {
        $sqlPath = (string)($context['sql_path'] ?? '');
        if ($sqlPath === '' || !is_file($sqlPath)) {
            return;
        }

        $sql = (string)@file_get_contents($sqlPath);
        if ($sql === '') {
            return;
        }

        $migration = new DatabaseMigrationService();
        $res = $migration->executeSql($sql);
        Db::name(VersionSchema::T_MIG)->insert([
            'ver' => $this->getCurrentVersion(),
            'sfile' => basename($sqlPath),
            'csum' => hash((string)Config::get('version.security.hash_algo', 'sha256'), $sql),
            'run_at' => date('Y-m-d H:i:s'),
            'status' => $res['success'] ? 'success' : 'failed',
        ]);

        if (!$res['success']) {
            $this->addTaskLog($taskId, 'error', '远程 SQL 执行失败', ['message' => $res['message'] ?? '']);
            throw new Exception((string)($res['message'] ?? '远程 SQL 执行失败'));
        }
        $this->addTaskLog($taskId, 'info', '远程 SQL 执行完成');
    }

    private function isAllowedDownloadUrl(string $url): bool
    {
        $allowedHosts = (array)Config::get('version.security.allowed_hosts', []);
        if (empty($allowedHosts)) {
            return true;
        }
        $host = (string)parse_url($url, PHP_URL_HOST);
        return $host !== '' && in_array($host, $allowedHosts, true);
    }

    private function curlGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)Config::get('version.http.timeout', 30));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output !== false ? (string)$output : '';
    }

    private function decodeJsonArray(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    private function table(string $name): string
    {
        $prefix = (string)config('database.connections.mysql.prefix', '');
        return $prefix . $name;
    }
}

