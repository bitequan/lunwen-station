<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 兼容旧退出地址，避免 /login/logout 返回 404
Route::get('/login/logout', function () {
    return redirect('/admin/login/logout');
});

// 旧版 H5 曾挂在 /pc/m 下的历史路径兼容：统一 301 到 /m/（剩余路径与查询参数原样保留，剔除 nginx rewrite 注入的内部 s 参数）
Route::get('/pc/m', function () {
    $rest = preg_replace('#^pc/m/?#', '', (string) \think\facade\Request::pathinfo());
    $q = $_GET;
    unset($q['s']);
    $qs = http_build_query($q);
    return redirect('/m/' . $rest . ($qs !== '' ? '?' . $qs : ''), 301);
});
Route::get('/pc/m/:rest', function () {
    $rest = preg_replace('#^pc/m/?#', '', (string) \think\facade\Request::pathinfo());
    $q = $_GET;
    unset($q['s']);
    $qs = http_build_query($q);
    return redirect('/m/' . $rest . ($qs !== '' ? '?' . $qs : ''), 301);
});

Route::auto();