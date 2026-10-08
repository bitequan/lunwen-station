<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\user\service\PaymentService;

/**
 * 充值接口（规划阶段1·充值写侧）
 *
 * 接口路径：/api/recharge/{action}
 * 统一返回格式：{ code, show, msg, data }
 *
 * 设计说明：复用后端已有充值/支付体系——
 *   - 支付渠道启停：ad_payment_methods.enabled（管理后台可启停），配置完整性由 PaymentService 统一过滤
 *   - 充值订单：ad_recharge_orders（赠送规则 RechargeBonusRule::calculateBonus）
 *   - 支付发起：复用微信/支付宝/易支付服务生成 PC 收银台（二维码）
 *   - 支付回调入账：PaymentService::updateOrderPayment 已完备，无需改动
 */
class V2Recharge
{
    private static $jsonBody = null;

    /**
     * 充值配置（含可用支付渠道；渠道启停由后端 ad_payment_methods.enabled 控制）
     * GET /api/recharge/config
     */
    public function config(): \think\response\Json
    {
        // 渠道过滤：enabled=1 且配置完整（PaymentService 统一处理，易支付自动拆分为独立渠道）
        $methods = PaymentService::getAvailablePaymentMethods();
        $payWays = [];
        foreach ($methods as $m) {
            $code = (string) ($m['code'] ?? '');
            if ($code === 'balance') {
                continue; // 充值不展示余额支付
            }
            $payWays[] = [
                'pay_type'  => $code,
                'pay_name'  => $m['display_name'] ?? $m['name'] ?? $code,
                'icon_text' => $m['icon_text'] ?? '',
            ];
        }

        return JsonService::data([
            'pay_ways'      => $payWays,
            'recharge_rate' => 1.0,   // 主站到账倍率 1
            'is_agent_pay'  => false, // 主站无代理支付
            'site_pay'      => null,
            'agent_info'    => null,
        ]);
    }

    /**
     * 创建充值订单并发起支付
     * POST /api/recharge/recharge
     * 入参：money, pay_type(wechat|alipay|epay_alipay|epay_wxpay)
     * 返回：{ from:'recharge', order_no, pay:{ config } }
     */
    public function recharge(): \think\response\Json
    {
        $userId = UserTokenService::getUserId(UserTokenService::readRequestToken());
        if (!$userId) {
            return JsonService::authExpired();
        }

        $money = (float) $this->param('money', 0);
        $payType = trim((string) $this->param('pay_type', ''));

        if ($money < 0.01) {
            return JsonService::fail('充值金额不能低于 0.01 元');
        }
        if ($money > 50000) {
            return JsonService::fail('单笔充值金额不能超过 50000 元');
        }
        if ($payType === '') {
            return JsonService::fail('请选择支付方式');
        }

        // 渠道校验：必须已启用且配置完整（后端启停生效）；余额支付不适用于充值
        $payMethod = null;
        foreach (PaymentService::getAvailablePaymentMethods() as $m) {
            if ((string) ($m['code'] ?? '') === 'balance') {
                continue;
            }
            if ((string) ($m['code'] ?? '') === $payType) {
                $payMethod = $m;
                break;
            }
        }
        if (!$payMethod) {
            return JsonService::fail('该支付渠道未启用或配置不完整，请联系客服');
        }

        // 计算充值赠送（后端充值赠送规则）
        $bonusResult = \app\model\RechargeBonusRule::calculateBonus($money);
        $bonusAmount = (float) ($bonusResult['bonus_amount'] ?? 0.00);
        $totalAmount = (float) ($bonusResult['total_amount'] ?? $money);

        // 创建充值订单（ad_recharge_orders）
        $orderNo = date('YmdHis') . mt_rand(100000, 999999);
        $orderId = Db::name('recharge_orders')->insertGetId([
            'order_no'           => $orderNo,
            'user_id'            => $userId,
            'amount'             => $money,
            'bonus_amount'       => $bonusAmount,
            'total_amount'       => $totalAmount,
            'bonus_rule_id'      => $bonusResult['rule_id'] ?? null,
            'bonus_rule_name'    => $bonusResult['rule_name'] ?? '',
            'payment_method'     => $payType,
            'payment_method_name'=> $payMethod['display_name'] ?? $payMethod['name'] ?? $payType,
            'status'             => 0,
            'pay_status'         => 0,
            'ip_address'         => request()->ip(),
            'user_agent'         => substr((string) request()->header('user-agent', ''), 0, 500),
            'expire_time'        => date('Y-m-d H:i:s', time() + 1800),
            'create_time'        => date('Y-m-d H:i:s'),
            'update_time'        => date('Y-m-d H:i:s'),
        ]);
        if (!$orderId) {
            return JsonService::fail('创建充值订单失败，请稍后重试');
        }

        // 发起支付：生成 PC 收银台（二维码）
        $payConfig = $this->buildPayConfig($orderNo, $payType);
        if ($payConfig === null) {
            return JsonService::fail('支付发起失败，请稍后重试或更换支付方式');
        }

        return JsonService::data([
            'from'     => 'recharge',
            'order_no' => $orderNo,
            'pay'      => [
                'config' => $payConfig,
            ],
        ]);
    }

    /**
     * 生成收银台内容（二维码图片 URL；微信/支付宝/易支付 PC 统一）
     */
    private function buildPayConfig(string $orderNo, string $payType): ?string
    {
        $orderInfo = PaymentService::getOrderInfo($orderNo);
        if (!$orderInfo) {
            return null;
        }
        try {
            switch ($payType) {
                case 'wechat':
                    $result = (new \app\user\service\WeChatPayService())->createNativePayment($orderInfo);
                    $codeUrl = $result['code_url'] ?? '';
                    return $codeUrl === '' ? null : $this->qrImageUrl($codeUrl);

                case 'alipay':
                    $result = (new \app\user\service\AliPayService())->createQrCodePayment($orderInfo);
                    $qrCode = $result['qr_code'] ?? '';
                    return $qrCode === '' ? null : $this->qrImageUrl($qrCode);

                case 'epay_alipay':
                case 'epay_wxpay':
                    $subType = $payType === 'epay_alipay' ? 'alipay' : 'wxpay';
                    $result = (new \app\user\service\EPayService())->createPayment($orderInfo, $subType, 'pc');
                    if (!empty($result['qr_code_image'])) {
                        return $result['qr_code_image'];
                    }
                    if (!empty($result['qr_code'])) {
                        return $this->qrImageUrl($result['qr_code']);
                    }
                    if (!empty($result['pay_url'])) {
                        return $result['pay_url'];
                    }
                    return null;

                default:
                    return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** 生成二维码图片地址（与旧收银台一致） */
    private function qrImageUrl(string $data): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
    }

    /**
     * 读取请求参数：兼容表单（application/x-www-form-urlencoded）与 JSON body。
     */
    private function param(string $key, $default = '')
    {
        $form = input($key);
        if ($form !== '' && $form !== null) {
            return $form;
        }
        if (self::$jsonBody === null) {
            self::$jsonBody = [];
            $raw = (string) file_get_contents('php://input');
            if ($raw !== '' && strpos(ltrim($raw), '{') === 0) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    self::$jsonBody = $decoded;
                }
            }
        }
        return self::$jsonBody[$key] ?? $default;
    }
}
