<?php

declare(strict_types=1);

namespace app\admin\middleware;

use think\facade\Session;

/**
 * 后台登录校验（需已存在 admin_id 会话）
 */
class AdminAuth
{
    public function handle($request, \Closure $next)
    {
        if (!Session::get('admin_id')) {
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }

            return redirect('/admin/login');
        }

        return $next($request);
    }
}
