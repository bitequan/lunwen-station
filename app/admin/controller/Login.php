<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\model\Admins;
use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;

class Login extends BaseController
{
    /**
     * 登录页面
     */                                                 
    public function index()
    {
        // 如果已登录，跳转到后台首页
        if (Session::get('admin_id')) {
            return redirect('/admin/');
        }
        // 使用admin应用内的视图文件
        return View::fetch('login/index');
    }

    /**
     * 执行登录
     */
    public function doLogin()
    {
        $username = Request::post('username');
        $password = Request::post('password');
        $remember = Request::post('remember', 0);

        if (empty($username) || empty($password)) {
            return json(['code' => 0, 'msg' => '用户名和密码不能为空']);
        }

        // 防爆破：同一用户名+IP 10 分钟内失败超过 5 次锁定
        $lockKey = 'admin_login_att_' . md5((string) $username . '|' . Request::ip());
        if ((int) \think\facade\Cache::get($lockKey, 0) >= 5) {
            return json(['code' => 0, 'msg' => '失败次数过多，请10分钟后再试']);
        }

        // 查找管理员
        $admin = Admins::where('username', $username)->find();

        if (!$admin) {
            \think\facade\Cache::set($lockKey, (int) \think\facade\Cache::get($lockKey, 0) + 1, 600);
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }

        // 验证密码
        $realPassword = $admin->getData('password');
        if (!password_verify($password, $realPassword)) {
            \think\facade\Cache::set($lockKey, (int) \think\facade\Cache::get($lockKey, 0) + 1, 600);
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }

        // 登录成功，清除失败计数
        \think\facade\Cache::delete($lockKey);

        // 检查状态
        if ($admin->status != 1) {
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }

        // 更新登录信息
        $admin->last_login_time = date('Y-m-d H:i:s');
        $admin->last_login_ip = Request::ip();
        $admin->save();

        // 设置Session
        Session::set('admin_id', $admin->id);
        Session::set('admin_username', $admin->username);
        Session::set('admin_nickname', $admin->nickname);

        // 如果选择了记住我，设置一个7天有效期的cookie作为标记
        if ($remember == 1) {
            Cookie::set('remember_me', '1', [
                'expire' => 7 * 24 * 60 * 60, // 7天
                'path' => '/',
                'httponly' => true
            ]);
        }

        return json(['code' => 1, 'msg' => '登录成功', 'url' => '/admin/']);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        // 强制清理管理员登录态（先删关键字段，再清空并销毁会话）
        Session::delete('admin_id');
        Session::delete('admin_username');
        Session::delete('admin_nickname');
        Session::clear();
        Session::destroy();

        // 再次兜底清理原生会话，避免个别环境下 Session::destroy 未完全失效
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?: '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
        }
        @session_destroy();
        
        // 清除记住我cookie
        Cookie::delete('remember_me');
        Cookie::delete('PHPSESSID');
        
        // 如果是AJAX请求，返回JSON
        if (Request::isAjax()) {
            return json(['code' => 1, 'msg' => '退出成功', 'url' => '/admin/login']);
        }
        
        // 否则跳转到登录页
        return redirect('/admin/login');
    }
}