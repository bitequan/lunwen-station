<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use app\model\Announcement as AnnouncementModel;

class Announcement extends BaseController
{
    /**
     * 获取公告信息
     */
    public function getAnnouncement()
    {
        try {
            // 获取公告（只需要一条）
            $announcement = AnnouncementModel::find(1);
            
            if (!$announcement) {
                // 如果没有公告，返回空数据
                return json([
                    'code' => 1,
                    'data' => [
                        'id' => 0,
                        'title' => '暂无公告',
                        'content' => '当前没有系统公告',
                        'status' => 0,
                        'create_time' => null,
                        'update_time' => null
                    ]
                ]);
            }
            
            // 只返回必要的信息
            $data = [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'status' => $announcement->status,
                'create_time' => $announcement->create_time,
                'update_time' => $announcement->update_time
            ];
            
            return json([
                'code' => 1,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取公告失败',
                'data' => [
                    'id' => 0,
                    'title' => '系统错误',
                    'content' => '获取公告信息时发生错误',
                    'status' => 0
                ]
            ]);
        }
    }
    
    /**
     * 测试公告页面（用于调试）
     */
    public function test()
    {
        try {
            $announcement = AnnouncementModel::find(1);
            
            if (!$announcement) {
                $announcement = new \stdClass();
                $announcement->id = 0;
                $announcement->title = '测试公告';
                $announcement->content = '这是一个测试公告内容，用于调试公告功能。';
                $announcement->status = 1;
                $announcement->create_time = date('Y-m-d H:i:s');
                $announcement->update_time = date('Y-m-d H:i:s');
            }
            
            View::assign('announcement', $announcement);
            return View::fetch('announcement/test');
            
        } catch (\Exception $e) {
            return '公告测试页面加载失败：' . $e->getMessage();
        }
    }
}
