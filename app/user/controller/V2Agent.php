<?php
declare(strict_types=1);

namespace app\user\controller;

use app\common\service\JsonService;

/**
 * 邀请码校验（注册基础闭环的必要依赖）
 *
 * 前端 login.vue 注册表单的邀请码由 /api/agent/validateCode 校验。
 * 本部署（ad_ 库）无上游代理/邀请码体系（ad_users 无 invite_code/level 字段，无 agent 表），
 * 因此约定：任意非空邀请码即视为有效，且无邀请人、注册后归属一级（自注册主站模式）。
 * 前端契约：{ code1, data:{ valid, inviter|null, child_level } }
 */
class V2Agent
{
    public function validateCode(): \think\response\Json
    {
        $code = trim((string) input('code', ''));
        if ($code === '') {
            return JsonService::fail('请输入邀请码');
        }
        // 无代理体系 → 任何非空码有效；inviter 置空，注册者归属一级
        return JsonService::data([
            'valid'       => true,
            'inviter'     => null,
            'child_level' => 1,
            'msg'         => '邀请码有效',
        ]);
    }
}