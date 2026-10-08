<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use app\model\Faq;

class FaqConfig extends BaseController
{
    /**
     * 常见问题配置页面
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
        
        return View::fetch('faq_config/index');
    }
    
    /**
     * 获取常见问题列表
     */
    public function getFaqList()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        try {
            // 获取所有常见问题
            $faqs = Faq::order('sort_order', 'asc')
                ->order('id', 'desc')
                ->select()
                ->toArray();
            
            // 格式化数据
            $formattedFaqs = [];
            foreach ($faqs as $faq) {
                $formattedFaqs[] = [
                    'id' => $faq['id'],
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                    'sort_order' => $faq['sort_order'],
                    'status' => $faq['status'],
                    'status_text' => Faq::getStatusText($faq['status']),
                    'create_time' => $faq['create_time'],
                    'update_time' => $faq['update_time']
                ];
            }
            
            return json(['code' => 1, 'data' => $formattedFaqs]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取常见问题列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取单个常见问题
     */
    public function getFaq()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::get('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => '常见问题ID不能为空']);
        }
        
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return json(['code' => 0, 'msg' => '常见问题不存在']);
            }
            
            return json(['code' => 1, 'data' => $faq->toArray()]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取常见问题失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存常见问题
     */
    public function saveFaq()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = Request::post();
        
        try {
            // 验证数据
            $errors = Faq::validateData($data);
            if (!empty($errors)) {
                return json(['code' => 0, 'msg' => implode('；', $errors)]);
            }
            
            // 处理排序顺序
            if (!isset($data['sort_order']) || $data['sort_order'] === '') {
                $data['sort_order'] = 0;
            }
            
            // 处理状态
            if (!isset($data['status'])) {
                $data['status'] = 1;
            }
            
            // 保存数据
            if (!empty($data['id'])) {
                // 更新
                $faq = Faq::find($data['id']);
                if (!$faq) {
                    return json(['code' => 0, 'msg' => '常见问题不存在']);
                }
                $faq->save($data);
            } else {
                // 新增
                $faq = new Faq();
                $faq->save($data);
            }
            
            return json(['code' => 1, 'msg' => '保存成功', 'data' => ['id' => $faq->id]]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新常见问题状态
     */
    public function updateFaqStatus()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::post('id', 0);
        $status = Request::post('status', 1);
        
        if (!$id) {
            return json(['code' => 0, 'msg' => '常见问题ID不能为空']);
        }
        
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return json(['code' => 0, 'msg' => '常见问题不存在']);
            }
            
            $faq->status = $status;
            $faq->save();
            
            return json(['code' => 1, 'msg' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新常见问题排序
     */
    public function updateFaqSort()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::post('id', 0);
        $sortOrder = Request::post('sort_order', 0);
        
        if (!$id) {
            return json(['code' => 0, 'msg' => '常见问题ID不能为空']);
        }
        
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return json(['code' => 0, 'msg' => '常见问题不存在']);
            }
            
            $faq->sort_order = $sortOrder;
            $faq->save();
            
            return json(['code' => 1, 'msg' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除常见问题
     */
    public function deleteFaq()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::post('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => '常见问题ID不能为空']);
        }
        
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return json(['code' => 0, 'msg' => '常见问题不存在']);
            }
            
            $faq->delete();
            
            return json(['code' => 1, 'msg' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
}

