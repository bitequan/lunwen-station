<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\Db;
use think\facade\View;
use think\Response;

/**
 * 写作中心页面控制器
 * 入口：/user/writing_center
 */
class WritingCenter extends BaseController
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 写作中心首页
     * 对应路由：GET /user/writing_center
     * 在用户中心 iframe 中以完整 HTML 方式展示
     */
    public function index(): Response
    {
        $codeList = ['kaiti', 'rws', 'sx', 'sxrz'];

        // 从商品表读取各工具/商品的公告简介（notice_text）
        $rows = Db::name('products')
            ->whereIn('code', $codeList)
            ->where('enabled', 1)
            ->field('code, notice_text')
            ->select();

        $noticeMap = [];
        foreach (($rows ?? []) as $row) {
            $code = $row['code'] ?? '';
            $noticeMap[$code] = trim($row['notice_text'] ?? '');
        }

        $html = View::fetch('writing_center/index.html', [
            'kaiti_notice' => $noticeMap['kaiti'] ?? '',
            'rws_notice'   => $noticeMap['rws'] ?? '',
            'sx_notice'    => $noticeMap['sx'] ?? '',
            'sxrz_notice'  => $noticeMap['sxrz'] ?? '',
            'kaiti_enabled' => isset($noticeMap['kaiti']),
            'rws_enabled'   => isset($noticeMap['rws']),
            'sx_enabled'    => isset($noticeMap['sx']),
            'sxrz_enabled'  => isset($noticeMap['sxrz']),
        ]);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}

