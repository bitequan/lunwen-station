<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Request;

/**
 * 统一 JSON 返回封装
 *
 * 约定返回结构：{ code, show, msg, data }
 *   code:  1=成功 0=失败 -1=登录超时需重新登录
 *   show:  1=前端需弹窗提示 0=静默
 *   msg:   提示文案
 *   data:  业务数据
 *
 * 该封装供用户端新版接口（app\user\controller\V2*）统一使用，
 * 与规划文档《3.2 后端》的返回格式约定保持一致。
 */
class JsonService
{
    /**
     * 业务成功
     * @param string $msg  提示文案
     * @param array|object|\think\Collection|null $data  业务数据
     * @param int $show  1=前端弹窗提示
     */
    public static function success(string $msg = 'success', $data = [], int $show = 0): \think\response\Json
    {
        return self::response(1, $show, $msg, $data);
    }

    /**
     * 仅返回数据（success 的轻量版，不携带提示文案）
     */
    public static function data($data = []): \think\response\Json
    {
        return self::response(1, 0, '', $data);
    }

    /**
     * 业务失败
     * @param string $msg  失败原因（show=1 时前端弹窗展示）
     * @param array $data
     */
    public static function fail(string $msg = 'fail', $data = [], int $code = 0, int $show = 1): \think\response\Json
    {
        return self::response($code, $show, $msg, $data);
    }

    /**
     * 登录超时 / token 无效，需要重新登录
     */
    public static function authExpired(string $msg = '登录超时，请重新登录'): \think\response\Json
    {
        return self::response(-1, 1, $msg, []);
    }

    /**
     * 未登录（未携带 token）
     */
    public static function notLogin(string $msg = '未登录'): \think\response\Json
    {
        return self::response(0, 0, $msg, []);
    }

    /**
     * 组装并返回 JSON 响应
     */
    protected static function response(int $code, int $show, string $msg, $data): \think\response\Json
    {
        return json([
            'code' => $code,
            'show' => $show,
            'msg'  => $msg,
            'data' => $data ?? (object)[],
        ]);
    }
}