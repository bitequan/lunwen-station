<?php

declare(strict_types=1);

namespace app\user\middleware;

use app\common\service\UserSingleSessionService;

class UserSingleSessionCheck
{
    public function handle($request, \Closure $next)
    {
        UserSingleSessionService::verifyRequest();

        return $next($request);
    }
}
