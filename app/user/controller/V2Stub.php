<?php
declare(strict_types=1);

namespace app\user\controller;

use app\common\service\JsonService;

/**
 * 占位控制器（api-doc 范围内、本库暂未接入真实数据源的业务组）
 *
 * 由 route 通配规则将各文档分组未实现的功能页接口收敛到本控制器，保证前端页面可渲染不报错：
 *   - 读类接口（list/categories/templates/outline/config/price/quota/wallet/records/models/...）
 *     → 返回 code=1 + 空结构 data（占位后接真实数据源）；
 *   - 写/长任务类接口（create/pay/download/generate/save/delete/upload/...）
 *     → 返回 code=0 + show=1 + 文案「功能暂未接入」，页面提示不崩溃。
 */
class V2Stub
{
    /** 写 / 长任务类动作关键词 → 返回"未接入"提示 */
    private const WRITE_ACTION =
        'create,pay,prepay,download,generate,generateOutline,save,update,delete,upload,uploadCover,uploadLogo,' .
        'enhance,adjc,rewrite,submit,import,bind,unbind,toggle,recharge,buy,publish,build,deduct,demonstrate,' .
        'generateAdvanced,generatePpt,generateOutlineSync,importFanwenStream,create_jcorder,createAdvancedOrder,' .
        'createOrder,createPptOrder,createAdvancedOrders,updateOutline,refresh';

    public function stub(): \think\response\Json
    {
        $group  = (string) input('group', '');
        $action = (string) input('action', '');

        $kw = array_filter(array_map('trim', explode(',', self::WRITE_ACTION)));
        foreach ($kw as $w) {
            // 动作关键词语义匹配：接口名含该词即视为写/长任务
            if ($w !== '' && strpos($action, $w) !== false) {
                return JsonService::fail('该功能暂未接入，请联系客服', [], 0, 1);
            }
        }

        // 读类 → 返回空结构，保证页面装配渲染
        return JsonService::data([
            'list' => [],
        ]);
    }
}