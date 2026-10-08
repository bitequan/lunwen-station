<?php

namespace app\middleware;

use think\facade\Session;

/**
 * 管理员认证中间件
 */
class AdminAuth
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 检查是否登录
        if (!Session::has('admin_id')) {
            // 如果是AJAX请求，返回JSON
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            // 否则跳转到登录页
            return redirect('/login/');
        }

        return $next($request);
    }
}

