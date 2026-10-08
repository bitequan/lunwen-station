<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\View;
use think\Response;

/**
 * 论文模板配置（autodoc）控制器
 * 写作配置 - 论文模板配置页面
 */
class Autodoc extends BaseController
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 模板列表页面
     * 对应路由：GET /user/autodoc/index
     */
    public function index(): Response
    {
        $content = View::fetch('autodoc/index.html');
        $html    = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>模板列表 - 用户中心</title></head><body>' . $content . '</body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 论文模板配置 - 新建/配置模板页面
     * 对应路由：GET /user/autodoc/create
     * 包装为完整 HTML 以便在用户中心 iframe 中正常显示
     */
    public function create(): Response
    {
        $content = View::fetch('autodoc/createautodoc.html');
        $html    = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>论文模板配置 - 用户中心</title></head><body>' . $content . '</body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 论文模板配置 - 编辑模板页面
     * 对应路由：GET /user/autodoc/edit?tid=xxx
     * 包装为完整 HTML 以便在用户中心 iframe 中正常显示
     */
    public function edit(): Response
    {
        $content = View::fetch('autodoc/editautodoc.html');
        $html    = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>编辑论文模板 - 用户中心</title></head><body>' . $content . '</body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 论文编辑系统（订单完成后的在线编辑）
     *
     * 对应路由：
     * GET /user/view/autodoc/lwedit.html?order_sn=xxx
     *
     * 注意：前端页面本身会从 URL 查询参数读取 order_sn。
     */
    public function lwedit(): Response
    {
        // 该 view 文件是完整 HTML 文档（包含 <html> 标签），直接返回即可。
        return response(View::fetch('autodoc/lwedit.html'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
