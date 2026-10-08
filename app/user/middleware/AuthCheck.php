<?php

declare(strict_types=1);

namespace app\user\middleware;

class AuthCheck
{
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }
}
