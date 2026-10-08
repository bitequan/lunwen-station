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

// 支付回调路由（不需要登录鉴权）
// 参考user和admin应用的路由配置方式
// 注意：在多应用模式下，访问 /pay/xxx 时，xxx部分会在pay应用内进行路由匹配

// 微信支付回调
Route::any('wechat/notify', 'notify/wechat');
// 兼容路径重复的情况：/pay/pay/wechat/notify -> pay应用内的 pay/wechat/notify
Route::any('pay/wechat/notify', 'notify/wechat');

// 支付宝支付回调
Route::any('alipay/notify', 'notify/alipay');
Route::any('pay/alipay/notify', 'notify/alipay');

// 易支付回调
Route::any('epay/notify', 'notify/epay');
Route::any('pay/epay/notify', 'notify/epay');

// 易支付返回
Route::any('epay/return', 'notify/epayReturn');
Route::any('pay/epay/return', 'notify/epayReturn');
