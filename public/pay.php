<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\App;

// [ pay应用入口文件 ]

// 首次部署检查：未完成则跳转到安装向导
if (PHP_SAPI !== 'cli') {
    $uriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    $baseName = basename($uriPath);
    if ($baseName !== 'install.php') {
        $projectRoot = dirname(__DIR__);
        $installBlockFile = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'install.block';
        if (!is_file($installBlockFile)) {
            header('Location: /install.php', true, 302);
            exit;
        }
    }
}

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

// 设置应用为pay
$http->name('pay');

$response = $http->run();

$response->send();

$http->end($response);
