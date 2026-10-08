<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\View;

/**
 * 订单管理控制器
 */
class Orders extends BaseController
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 订单列表页面
     */
    public function index()
    {
        // 获取当前用户ID
        $userId = $this->getUserId();

        // 传递用户ID到视图
        return view('user/orders', [
            'user_id' => $userId
        ]);
    }

    /**
     * 获取用户ID
     *
     * @return int
     */
    private function getUserId()
    {
        // 从session中获取用户ID
        $userId = session('user_id');
        return $userId ? intval($userId) : 0;
    }
}
