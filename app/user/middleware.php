<?php
// user应用中间件定义文件
return [
    \app\user\middleware\AuthCheck::class,
    \app\user\middleware\UserSingleSessionCheck::class,
];
