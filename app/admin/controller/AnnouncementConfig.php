<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use app\model\Announcement;

class AnnouncementConfig extends BaseController
{
    /**
     * 公告设置页面
     */
    public function index()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        return View::fetch('announcement_config/index');
    }
    
    /**
     * 获取公告信息
     */
    public function getAnnouncement()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        try {
            // 获取公告（只需要一条）
            $announcement = Announcement::find(1);
            
            if (!$announcement) {
                // 如果没有公告，创建一个默认的
                $announcement = new Announcement();
                $announcement->id = 1;
                $announcement->title = '欢迎使用对接端管理系统';
                $announcement->content = '欢迎使用对接端管理系统，祝您使用愉快！';
                $announcement->status = 1;
                $announcement->create_time = date('Y-m-d H:i:s');
                $announcement->update_time = date('Y-m-d H:i:s');
                $announcement->save();
            }
            
            return json([
                'code' => 1,
                'data' => $announcement->toArray()
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取公告失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存公告信息
     */
    public function saveAnnouncement()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        try {
            $data = Request::post();
            
            // 验证数据
            if (empty($data['title'])) {
                return json(['code' => 0, 'msg' => '公告标题不能为空']);
            }
            
            if (empty($data['content'])) {
                return json(['code' => 0, 'msg' => '公告内容不能为空']);
            }
            
            // 获取或创建公告
            $announcement = Announcement::find(1);
            
            if (!$announcement) {
                $announcement = new Announcement();
                $announcement->id = 1;
                $announcement->create_time = date('Y-m-d H:i:s');
            }
            
            // 更新公告信息
            $announcement->title = trim($data['title']);
            $announcement->content = trim($data['content']);
            $announcement->status = isset($data['status']) ? intval($data['status']) : 1;
            $announcement->update_time = date('Y-m-d H:i:s');
            
            $result = $announcement->save();
            
            if ($result) {
                return json(['code' => 1, 'msg' => '保存成功']);
            } else {
                return json(['code' => 0, 'msg' => '保存失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
}
