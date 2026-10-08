<?php

namespace app\pay;

/**
 * 支付应用基础控制器
 */
class BaseController extends \app\BaseController
{
    /**
     * 错误跳转
     */
    protected function error($msg)
    {
        return json([
            'code' => 0,
            'msg' => $msg
        ]);
    }
    
    /**
     * 成功返回
     */
    protected function success($msg = '', $data = [])
    {
        return json([
            'code' => 1,
            'msg' => $msg,
            'data' => $data
        ]);
    }
}
