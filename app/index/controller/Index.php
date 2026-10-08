<?php
declare (strict_types = 1);

namespace app\index\controller;

class Index
{
    public function index()
    {
        // 旧根落地页已废弃：主站统一走 Nuxt 前端（写作中心工作台）
        // 根路径按 UA 自适应分流：移动端 → /m/，桌面端 → /pc/
        // （iPad 新 UA 伪装 Mac 的情况由前端 m-redirect 中间件按 maxTouchPoints 兜底）
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $isMobile = (bool) preg_match('/Android|iPhone|iPad|iPod|IEMobile|Opera Mini|Mobile/i', $ua);
        return redirect($isMobile ? '/m/' : '/pc/');
    }

    public function hello($name = 'ThinkPHP8')
    {
        return 'hello,' . $name;
    }
}
