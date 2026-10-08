<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\model\Admins;
use app\service\OnlineUpdateService;
use app\service\VersionService;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\response\Json;

/**
 * 版本更新控制器
 */
class Version extends BaseController
{
    protected VersionService $versionService;
    protected OnlineUpdateService $onlineUpdateService;

    protected function initialize()
    {
        parent::initialize();
        $this->versionService = new VersionService();
        $this->onlineUpdateService = new OnlineUpdateService();
    }

    /**
     * 在线更新硬闸：总开关关闭时拒绝所有在线更新动作（交付站防护，防止更新包覆盖本地定制）
     */
    private function guardOnlineDisabled(): ?Json
    {
        if (!config('version.online_update.enabled', false)) {
            return json(['code' => 0, 'msg' => '在线更新已关闭']);
        }
        return null;
    }

    public function index()
    {
        return View::fetch('version/index');
    }

    /**
     * 获取当前版本信息
     * @return Json
     */
    public function current(): Json
    {
        $version = $this->versionService->getCurrentVersion();
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => [
                'version' => $version,
                'update_time' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * 检查更新
     * @return Json
     */
    public function check(): Json
    {
        $remoteVersion = Request::param('remote_version', '');
        $result = $this->versionService->checkUpdate($remoteVersion);
        
        return json([
            'code' => 1,
            'msg' => $result['message'],
            'data' => $result
        ]);
    }

    /**
     * 在线检查更新（调用授权端）
     * @return Json
     */
    public function checkOnline(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $result = $this->onlineUpdateService->checkOnline();
        return json($result);
    }

    /**
     * 创建在线升级任务
     * @return Json
     */
    public function createTask(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $toVersion = Request::param('to_version', '');
        $checkData = Request::param('check_data/a', []);
        $forceUpdate = Request::param('force_update', 0, 'intval') === 1;
        $idempotencyKey = Request::param('idempotency_key', '');
        $adminId = (int) Session::get('admin_id', 0);

        $result = $this->onlineUpdateService->createTask($toVersion, $adminId, $checkData, $forceUpdate, $idempotencyKey);
        return json($result);
    }

    /**
     * 启动在线升级任务
     * @return Json
     */
    public function startTask(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $taskNo = Request::param('task_no', '');
        if ($taskNo === '') {
            return json(['code' => 0, 'msg' => '请提供任务号']);
        }
        if (config('version.strategy.require_admin_password', false)) {
            $confirmPassword = Request::param('confirm_password', '');
            $adminId = (int) Session::get('admin_id', 0);
            $admin = $adminId > 0 ? Admins::find($adminId) : null;
            if (!$admin || $confirmPassword === '' || !$admin->verifyPassword($confirmPassword)) {
                return json(['code' => 0, 'msg' => '二次密码校验失败']);
            }
        }

        $result = $this->onlineUpdateService->startTask($taskNo);
        return json($result);
    }

    /**
     * 查询任务状态
     * @return Json
     */
    public function taskStatus(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $taskNo = Request::param('task_no', '');
        if ($taskNo === '') {
            return json(['code' => 0, 'msg' => '请提供任务号']);
        }

        $result = $this->onlineUpdateService->taskStatus($taskNo);
        return json($result);
    }

    /**
     * 查询任务日志
     * @return Json
     */
    public function taskLogs(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $taskNo = Request::param('task_no', '');
        $limit = Request::param('limit', 100, 'intval');
        if ($taskNo === '') {
            return json(['code' => 0, 'msg' => '请提供任务号']);
        }

        $result = $this->onlineUpdateService->taskLogs($taskNo, $limit);
        return json($result);
    }

    /**
     * 重试失败任务
     * @return Json
     */
    public function retryTask(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $taskNo = Request::param('task_no', '');
        if ($taskNo === '') {
            return json(['code' => 0, 'msg' => '请提供任务号']);
        }

        $result = $this->onlineUpdateService->retryTask($taskNo);
        return json($result);
    }

    /**
     * 手动回滚任务（仅恢复状态，不做数据库逆向）
     * @return Json
     */
    public function rollbackTask(): Json
    {
        if ($resp = $this->guardOnlineDisabled()) {
            return $resp;
        }
        $taskNo = Request::param('task_no', '');
        if ($taskNo === '') {
            return json(['code' => 0, 'msg' => '请提供任务号']);
        }

        $result = $this->onlineUpdateService->rollbackTask($taskNo);
        return json($result);
    }

    /**
     * 上传更新包
     * @return Json
     */
    public function upload(): Json
    {
        $file = Request::file('update_package');
        
        if (!$file) {
            return json([
                'code' => 0,
                'msg' => '请选择要上传的更新包文件'
            ]);
        }

        $result = $this->versionService->uploadUpdatePackage($file);
        
        if ($result['success']) {
            return json([
                'code' => 1,
                'msg' => $result['message'],
                'data' => [
                    'file_path' => $result['file_path'],
                    'file_name' => $result['file_name']
                ]
            ]);
        } else {
            return json([
                'code' => 0,
                'msg' => $result['message']
            ]);
        }
    }

    /**
     * 应用更新
     * @return Json
     */
    public function apply(): Json
    {
        $filePath = Request::param('file_path', '');
        
        if (empty($filePath)) {
            return json([
                'code' => 0,
                'msg' => '请提供更新包路径'
            ]);
        }

        // 验证文件路径安全性
        $updatePath = config('version.update_path');
        if (strpos(realpath($filePath), realpath($updatePath)) !== 0) {
            return json([
                'code' => 0,
                'msg' => '无效的更新包路径'
            ]);
        }

        $newVersion = Request::param('new_version', '');
        $updateMode = Request::param('update_mode', 'incremental'); // 'full' 或 'incremental'
        
        // 验证更新模式
        if (!in_array($updateMode, ['full', 'incremental'])) {
            $updateMode = 'incremental';
        }
        
        $result = $this->versionService->applyUpdate($filePath, $newVersion, $updateMode);
        
        if ($result['success']) {
            $response = [
                'code' => 1,
                'msg' => $result['message']
            ];
            
            // 包含数据库更新结果
            if (isset($result['db_result'])) {
                $response['data'] = [
                    'db_result' => $result['db_result']
                ];
            }
            
            return json($response);
        } else {
            return json([
                'code' => 0,
                'msg' => $result['message']
            ]);
        }
    }

    /**
     * 获取更新历史
     * @return Json
     */
    public function history(): Json
    {
        $limit = Request::param('limit', 10, 'intval');
        $history = $this->versionService->getUpdateHistory($limit);
        
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $history
        ]);
    }

    /**
     * 设置版本号
     * @return Json
     */
    public function setVersion(): Json
    {
        $version = Request::param('version', '');
        
        if (empty($version)) {
            return json([
                'code' => 0,
                'msg' => '请提供版本号'
            ]);
        }

        // 验证版本号格式
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            return json([
                'code' => 0,
                'msg' => '版本号格式不正确，应为 x.x.x 格式'
            ]);
        }

        $result = $this->versionService->setCurrentVersion($version);
        
        if ($result) {
            return json([
                'code' => 1,
                'msg' => '版本号设置成功',
                'data' => [
                    'version' => $version
                ]
            ]);
        } else {
            return json([
                'code' => 0,
                'msg' => '版本号设置失败'
            ]);
        }
    }
}
