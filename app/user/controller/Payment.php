<?php

namespace app\user\controller;

use app\user\BaseController;
use app\user\service\PaymentService;
use think\facade\View;
use think\facade\Request;

/**
 * 支付控制器
 */
class Payment extends BaseController
{
    /**
     * 支付页面
     */
    public function index()
    {
        $orderNo = Request::get('order_no', '');
        
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }
        
        // 获取订单信息
        $orderInfo = PaymentService::getOrderInfo($orderNo);
        
        if (!$orderInfo) {
            return $this->error('订单不存在');
        }
        
        // 获取可用的支付方式
        $paymentMethods = PaymentService::getAvailablePaymentMethods();
        
        View::assign([
            'order' => $orderInfo,
            'payment_methods' => $paymentMethods,
            'order_no' => $orderNo
        ]);
        
        return View::fetch('payment/index');
    }
    
    /**
     * 创建订单
     */
    public function createOrder()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        
        $data = Request::post();
        
        // 验证必要参数
        $requiredFields = ['product_code', 'product_name', 'quantity', 'unit_price', 'total_amount'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return json(['code' => 0, 'msg' => "缺少必要参数: {$field}"]);
            }
        }
        
        // 添加用户信息
        $data['user_id'] = $this->getUserId();
        $data['guest_token'] = $this->getGuestToken();
        
        $result = PaymentService::createOrder($data);
        
        if ($result['code'] == 1) {
            return json([
                'code' => 1,
                'msg' => '订单创建成功',
                'data' => [
                    'order_no' => $result['data']['order_no'],
                    'redirect_url' => '/user/view/service/payment?order_no=' . urlencode($result['data']['order_no'])
                ]
            ]);
        } else {
            return json($result);
        }
    }
    
    /**
     * 获取支付方式
     */
    public function getPaymentMethods()
    {
        $paymentMethods = PaymentService::getAvailablePaymentMethods();
        
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $paymentMethods
        ]);
    }
    
    /**
     * 发起支付
     */
    public function pay()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        
        $orderNo = Request::post('order_no', '');
        $paymentCode = Request::post('payment_code', '');
        $paymentType = Request::post('payment_type', 'pc'); // pc 或 h5
        
        if (empty($orderNo) || empty($paymentCode)) {
            return json(['code' => 0, 'msg' => '订单号和支付方式不能为空']);
        }
        
        // 获取订单信息
        $orderInfo = PaymentService::getOrderInfo($orderNo);
        
        if (!$orderInfo) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        
        // 记录订单信息用于调试
        
        // 记录支付创建日志
        \app\user\service\PaymentLogger::logCreate($orderNo, $paymentCode, [
            'order_info' => $orderInfo,
            'payment_type' => $paymentType
        ]);
        
        if ($orderInfo['pay_status'] == 1) {
            return json(['code' => 0, 'msg' => '订单已支付']);
        }
        
        // 根据支付方式和支付类型处理支付
        switch ($paymentCode) {
            case 'balance':
                // 余额支付不需要配置
                return $this->balancePay($orderInfo);
                
            case 'wechat':
            case 'alipay':
            case 'epay_alipay':
            case 'epay_wxpay':
                // 其他支付方式需要配置
                $paymentConfig = PaymentService::getPaymentConfig($paymentCode);
                
                if (!$paymentConfig) {
                    return json(['code' => 0, 'msg' => '支付配置不存在']);
                }
                
                switch ($paymentCode) {
                    case 'wechat':
                        return $this->wechatPay($orderInfo, $paymentConfig, $paymentType);
                        
                    case 'alipay':
                        return $this->alipayPay($orderInfo, $paymentConfig, $paymentType);
                        
                    case 'epay_alipay':
                        return $this->epayPay($orderInfo, $paymentConfig, 'alipay', $paymentType);
                        
                    case 'epay_wxpay':
                        return $this->epayPay($orderInfo, $paymentConfig, 'wxpay', $paymentType);
                }
                break;
                
            default:
                return json(['code' => 0, 'msg' => '不支持的支付方式']);
        }
    }
    
    /**
     * 微信支付
     * @param array $orderInfo 订单信息
     * @param array $config 支付配置
     * @param string $paymentType 支付类型：pc 或 h5
     */
    private function wechatPay($orderInfo, $config, $paymentType = 'pc')
    {
        try {
            // 验证订单信息
            if (empty($orderInfo)) {
                return json(['code' => 0, 'msg' => '订单信息为空']);
            }
            
            // 验证必要字段
            $requiredFields = ['order_no', 'total_amount', 'product_name'];
            foreach ($requiredFields as $field) {
                if (empty($orderInfo[$field])) {
                    return json(['code' => 0, 'msg' => "订单信息不完整，缺少字段: {$field}"]);
                }
            }
            
            // 使用真实的微信支付API
            $wechatPayService = new \app\user\service\WeChatPayService();
            
            if ($paymentType === 'h5') {
                // H5支付：返回支付链接
                $paymentResult = $wechatPayService->createH5Payment($orderInfo);
                
                return json([
                    'code' => 1,
                    'msg' => 'H5支付链接已生成',
                    'data' => [
                        'payment_method' => 'wechat',
                        'order_no' => $orderInfo['order_no'],
                        'total_amount' => $orderInfo['total_amount'],
                        'product_name' => $orderInfo['product_name'],
                        'pay_url' => $paymentResult['pay_url'],
                        'expire_time' => $paymentResult['expire_time']
                    ]
                ]);
            } else {
                // PC支付：返回二维码
                $paymentResult = $wechatPayService->createNativePayment($orderInfo);
                
                // 生成二维码URL
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . 
                            urlencode($paymentResult['code_url']);
                
                return json([
                    'code' => 1,
                    'msg' => '支付二维码已生成',
                    'data' => [
                        'payment_method' => 'wechat',
                        'order_no' => $orderInfo['order_no'],
                        'total_amount' => $orderInfo['total_amount'],
                        'product_name' => $orderInfo['product_name'],
                        'qr_code_data' => [
                            'qr_code_url' => $qrCodeUrl,
                            'code_url' => $paymentResult['code_url'],
                            'payment_url' => $paymentResult['code_url']
                        ],
                        'expire_time' => $paymentResult['expire_time']
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '微信支付失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 支付宝支付
     * @param array $orderInfo 订单信息
     * @param array $config 支付配置
     * @param string $paymentType 支付类型：pc 或 h5
     */
    private function alipayPay($orderInfo, $config, $paymentType = 'pc')
    {
        try {
            // 验证订单信息
            if (empty($orderInfo)) {
                return json(['code' => 0, 'msg' => '订单信息为空']);
            }
            
            // 验证必要字段
            $requiredFields = ['order_no', 'total_amount', 'product_name'];
            foreach ($requiredFields as $field) {
                if (empty($orderInfo[$field])) {
                    return json(['code' => 0, 'msg' => "订单信息不完整，缺少字段: {$field}"]);
                }
            }
            
            // 使用真实的支付宝支付API
            $aliPayService = new \app\user\service\AliPayService();
            
            if ($paymentType === 'h5') {
                // H5支付：返回支付链接
                $returnUrl = request()->domain() . '/user/payment/success?order_no=' . $orderInfo['order_no'];
                $paymentResult = $aliPayService->createH5Payment($orderInfo, $returnUrl);
                
                return json([
                    'code' => 1,
                    'msg' => 'H5支付链接已生成',
                    'data' => [
                        'payment_method' => 'alipay',
                        'order_no' => $orderInfo['order_no'],
                        'total_amount' => $orderInfo['total_amount'],
                        'product_name' => $orderInfo['product_name'],
                        'pay_url' => $paymentResult['pay_url'],
                        'expire_time' => $paymentResult['expire_time']
                    ]
                ]);
            } else {
                // PC支付：返回二维码
                $paymentResult = $aliPayService->createQrCodePayment($orderInfo);
                
                // 生成二维码URL
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . 
                            urlencode($paymentResult['qr_code']);
                
                // 记录支付创建成功日志
                \app\user\service\PaymentLogger::log($orderInfo['order_no'], 'alipay', 'create_success', '支付宝支付二维码创建成功', [
                    'qr_code' => $paymentResult['qr_code'],
                    'expire_time' => $paymentResult['expire_time']
                ]);
                
                return json([
                    'code' => 1,
                    'msg' => '支付宝支付二维码已生成',
                    'data' => [
                        'payment_method' => 'alipay',
                        'order_no' => $orderInfo['order_no'],
                        'total_amount' => $orderInfo['total_amount'],
                        'product_name' => $orderInfo['product_name'],
                        'qr_code_data' => [
                            'qr_code_url' => $qrCodeUrl,
                            'qr_code' => $paymentResult['qr_code'],
                            'payment_url' => $paymentResult['qr_code']
                        ],
                        'expire_time' => $paymentResult['expire_time']
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '支付宝支付失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 易支付
     * @param array $orderInfo 订单信息
     * @param array $config 支付配置
     * @param string $payType 支付类型：alipay-支付宝，wxpay-微信支付
     * @param string $paymentType 支付方式：pc 或 h5
     * @return \think\response\Json
     */
    private function epayPay($orderInfo, $config, $payType = 'alipay', $paymentType = 'pc')
    {
        try {
            // 使用易支付服务类
            $epayService = new \app\user\service\EPayService();
            
            // 验证支付类型是否启用
            if (!$epayService->isPayTypeEnabled($payType)) {
                return json(['code' => 0, 'msg' => '该支付渠道未启用']);
            }
            
            // 创建支付（传递paymentType参数）
            $paymentType = $paymentType ?? 'pc'; // 默认为PC支付
            $paymentResult = $epayService->createPayment($orderInfo, $payType, $paymentType);
            
            // 根据易支付API返回的数据处理
            // API接口返回：payurl（支付跳转url）、qrcode（二维码链接）、urlscheme（小程序跳转url）
            $payUrl = $paymentResult['pay_url'] ?? '';
            $qrCode = $paymentResult['qr_code'] ?? '';
            $qrCodeImageUrl = $paymentResult['qr_code_image'] ?? '';
            $urlScheme = $paymentResult['urlscheme'] ?? '';
            
            // 优先使用payurl（H5支付），如果没有则使用qrcode（PC支付）
            if ($paymentType === 'h5') {
                // H5支付：优先返回payurl，如果没有则使用urlscheme
                $finalPayUrl = $payUrl ?: $urlScheme;
                
                if (empty($finalPayUrl)) {
                    // 如果H5支付没有返回payurl，尝试使用qrcode作为备用
                    $finalPayUrl = $qrCode;
                }
                
                if (empty($finalPayUrl)) {
                    throw new \Exception('易支付H5支付未返回支付链接');
                }
                
                $paymentMethodName = $payType === 'alipay' ? 'epay_alipay' : 'epay_wxpay';
                $msg = $payType === 'alipay' ? '易支付-支付宝H5支付链接已生成' : '易支付-微信支付H5支付链接已生成';
                
                return json([
                    'code' => 1,
                    'msg' => $msg,
                    'data' => [
                        'payment_method' => $paymentMethodName,
                        'order_no' => $orderInfo['order_no'],
                        'total_amount' => $orderInfo['total_amount'],
                        'product_name' => $orderInfo['product_name'],
                        'pay_url' => $finalPayUrl,
                        'expire_time' => $paymentResult['expire_time'] ?? date('Y-m-d H:i:s', time() + 900)
                    ]
                ]);
            } else {
                // PC支付：返回二维码
                if (empty($qrCode) && !empty($payUrl)) {
                    // 如果返回的是payurl而不是qrcode，说明需要跳转，不适合PC支付
                    throw new \Exception('易支付PC支付应返回二维码，但返回了跳转链接');
                }
                
                if (empty($qrCodeImageUrl) && !empty($qrCode)) {
                    // 如果没有二维码图片，生成一个
                    $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCode);
                }
                
                if ($payType === 'alipay') {
                    return json([
                        'code' => 1,
                        'msg' => '易支付-支付宝二维码已生成',
                        'data' => [
                            'payment_method' => 'epay_alipay',
                            'order_no' => $orderInfo['order_no'],
                            'total_amount' => $orderInfo['total_amount'],
                            'product_name' => $orderInfo['product_name'],
                            'qr_code_data' => [
                                'qr_code_url' => $qrCodeImageUrl,
                                'qr_code' => $qrCode,
                                'payment_url' => $qrCode,
                                'qr_code_image' => $qrCodeImageUrl
                            ],
                            'expire_time' => $paymentResult['expire_time'] ?? date('Y-m-d H:i:s', time() + 900)
                        ]
                    ]);
                } elseif ($payType === 'wxpay') {
                    return json([
                        'code' => 1,
                        'msg' => '易支付-微信支付二维码已生成',
                        'data' => [
                            'payment_method' => 'epay_wxpay',
                            'order_no' => $orderInfo['order_no'],
                            'total_amount' => $orderInfo['total_amount'],
                            'product_name' => $orderInfo['product_name'],
                            'qr_code_data' => [
                                'qr_code_url' => $qrCodeImageUrl,
                                'code_url' => $qrCode,
                                'payment_url' => $qrCode,
                                'qr_code_image' => $qrCodeImageUrl
                            ],
                            'expire_time' => $paymentResult['expire_time'] ?? date('Y-m-d H:i:s', time() + 900)
                        ]
                    ]);
                } else {
                    return json(['code' => 0, 'msg' => '不支持的支付类型']);
                }
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '易支付失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 余额支付
     */
    private function balancePay($orderInfo)
    {
        try {
            // 获取当前用户ID
            $userId = $this->getUserId();

            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录后再使用余额支付']);
            }

            // 检查订单是否属于当前用户
            // 如果订单还没有绑定用户（user_id 为空或为 0），优先绑定给当前登录用户
            if (empty($orderInfo['user_id'])) {
                \think\facade\Db::name('orders')
                    ->where('id', $orderInfo['id'])
                    ->update([
                        'user_id' => $userId,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                // 同步更新内存中的订单信息
                $orderInfo['user_id'] = $userId;
            } elseif ($orderInfo['user_id'] != $userId) {
                // 已经绑定了其他用户，禁止使用当前账户余额支付
                return json(['code' => 0, 'msg' => '订单不属于当前用户']);
            }
            
            // 获取用户余额
            $user = \think\facade\Db::name('users')
                ->where('id', $userId)
                ->field('balance')
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            
            $userBalance = floatval($user['balance']);
            $orderAmount = floatval($orderInfo['total_amount']);
            
            // 检查余额是否足够
            if ($userBalance < $orderAmount) {
                return json([
                    'code' => 0, 
                    'msg' => '余额不足，当前余额：¥' . number_format($userBalance, 2) . '，订单金额：¥' . number_format($orderAmount, 2)
                ]);
            }

            $productCode = $orderInfo['product_code'] ?? '';
            
            // 先提交订单到TokenAPI，确保提交成功后再扣除余额
            // 对于走 TokenAPI 的商品（写作/PPT/高级论文等）需要提交；
            // 对于本地纯业务商品则跳过，直接视为成功。

            $needTokenApi = in_array($productCode, ['kaiti', 'rws', 'sx', 'sxrz', 'gjlw', 'ppt']);

            if ($needTokenApi) {
                $tokenApiResult = PaymentService::submitOrderToTokenAPIBeforePayment($orderInfo['order_no']);
            } else {
                $tokenApiResult = ['success' => true, 'isBalanceInsufficient' => false];
            }
            

            if (!$tokenApiResult['success']) {
                // TokenAPI提交失败，不扣除余额
                
                // 检查是否是余额不足
                if ($tokenApiResult['isBalanceInsufficient']) {
                    return json([
                        'code' => 0,
                        'msg' => '后端模型余额不足,请联系客服处理！'
                    ]);
                }
                
                // 检查订单状态是否为 6（支付异常，可能是余额不足）
                $order = \app\model\Orders::where('order_no', $orderInfo['order_no'])->find();
                if ($order && $order->status == 6) {
                    return json([
                        'code' => 0,
                        'msg' => '后端模型余额不足,请联系客服处理！'
                    ]);
                }
                
                return json([
                    'code' => 0,
                    'msg' => '提交订单失败请重试或联系客服处理！'
                ]);
            }
            
            
            // 开始事务处理
            \think\facade\Db::startTrans();
            
            try {
                // 扣除用户余额
                $newBalance = $userBalance - $orderAmount;
                \think\facade\Db::name('users')
                    ->where('id', $userId)
                    ->update([
                        'balance' => $newBalance,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                
                // 记录余额变动日志（消费）
                \think\facade\Db::name('balance_logs')->insert([
                    'user_id' => $userId,
                    'change_type' => 'consume',
                    'change_amount' => -$orderAmount,                // 消费为负数
                    'before_balance' => $userBalance,
                    'after_balance' => $newBalance,
                    'related_id' => $orderInfo['order_no'],
                    'related_type' => 'order',
                    'remark' => '订单支付：' . ($orderInfo['product_name'] ?? '商品'),
                    'operator_id' => $userId,
                    'operator_type' => 'user',
                    'ip_address' => request()->ip(),
                    'create_time' => date('Y-m-d H:i:s'),
                    'bonus_amount' => 0,
                    'total_change_amount' => -$orderAmount
                ]);
                
                // 更新订单支付状态（TokenAPI已提交成功，这里只需要更新订单状态）
                // 注意：在事务中直接更新订单状态，确保与余额扣除在同一事务中
                $orderUpdateResult = \think\facade\Db::name('orders')
                    ->where('order_no', $orderInfo['order_no'])
                    ->update([
                        'payment_method' => 'balance',
                        'pay_status' => 1, // 已支付
                        'status' => 1, // 已支付
                        'pay_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                
                if (!$orderUpdateResult) {
                    // 如果更新订单状态失败，回滚余额扣除
                    \think\facade\Db::rollback();
                    return json([
                        'code' => 0,
                        'msg' => '更新订单状态失败，请稍后重试'
                    ]);
                }
                
                
                // 提交余额扣除和订单状态更新的事务
                \think\facade\Db::commit();
                
                // 记录支付成功日志
                \app\user\service\PaymentLogger::logSuccess($orderInfo['order_no'], 'balance', [
                    'user_id' => $userId,
                    'order_amount' => $orderAmount,
                    'balance_before' => $userBalance,
                    'balance_after' => $newBalance
                ]);

                $productCode = $orderInfo['product_code'] ?? 'gjlw';
                return json([
                    'code' => 1,
                    'msg' => '支付成功',
                    'data' => [
                        // 余额支付成功后直接回到对应商品的下单页
                        'redirect_url' => '/user/product?code=' . urlencode($productCode),
                    ]
                ]);
                
            } catch (\Exception $e) {
                \think\facade\Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '余额支付失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 生成易支付签名
     */
    private function generateEpaySign($params, $key)
    {
        unset($params['sign'], $params['sign_type']);
        
        // 移除空值
        $params = array_filter($params, function($value) {
            return $value !== '' && $value !== null;
        });
        
        // 按键名排序
        ksort($params);
        
        // 拼接签名字符串
        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= $k . '=' . $v . '&';
        }
        $signString .= $key;
        
        // 计算MD5签名
        return strtolower(md5($signString));
    }
    
    /**
     * 支付宝H5支付中间页面（自动提交表单）
     */
    public function alipayH5()
    {
        $orderNo = Request::get('order_no', '');
        $returnUrl = Request::get('return_url', '');
        
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }
        
        // 从session中获取保存的支付宝表单HTML
        $formHtml = session('alipay_h5_form_' . $orderNo);
        
        if (empty($formHtml)) {
            return $this->error('支付表单不存在或已过期，请重新发起支付');
        }
        
        // 确保是字符串类型
        if (!is_string($formHtml)) {
            return $this->error('支付表单格式错误，请重新发起支付');
        }
        
        // 检测是否在iframe中，如果在iframe中，需要先跳转到顶层窗口
        // 在HTML中添加JavaScript来检测和处理iframe情况
        $iframeScript = '
        <script>
        (function() {
            // 检测是否在iframe中
            function isInIframe() {
                try {
                    return window.self !== window.top;
                } catch (e) {
                    return true; // 跨域情况下假设在iframe中
                }
            }
            
            // 如果在iframe中，先跳转到顶层窗口
            if (isInIframe()) {
                try {
                    // 跳转到顶层窗口的当前URL
                    window.top.location.href = window.location.href;
                    return; // 停止执行，等待跳转
                } catch (e) {
                    // 如果跨域无法访问top，尝试parent
                    try {
                        window.parent.location.href = window.location.href;
                        return;
                    } catch (e2) {
                        // 如果都失败，继续在当前窗口执行
                        console.warn("无法跳出iframe，可能跨域限制");
                    }
                }
            }
            
            // 不在iframe中或无法跳出，自动提交表单
            // 查找表单并自动提交
            window.onload = function() {
                var form = document.querySelector("form");
                if (form) {
                    // 延迟一点提交，确保页面完全加载
                    setTimeout(function() {
                        form.submit();
                    }, 100);
                }
            };
        })();
        </script>';
        
        // 将iframe检测脚本插入到HTML的head部分
        if (strpos($formHtml, '</head>') !== false) {
            $formHtml = str_replace('</head>', $iframeScript . '</head>', $formHtml);
        } elseif (strpos($formHtml, '<body') !== false) {
            // 如果没有head标签，在body前插入
            $formHtml = str_replace('<body', $iframeScript . '<body', $formHtml);
        } else {
            // 如果都没有，在开头插入
            $formHtml = $iframeScript . $formHtml;
        }
        
        // 清除session中的表单数据
        session('alipay_h5_form_' . $orderNo, null);
        
        // 直接输出表单HTML，浏览器会自动提交
        return response($formHtml, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
    
    /**
     * 支付成功页面
     */
    public function success()
    {
        $orderNo = Request::get('order_no', '');
        
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }
        
        // 获取订单信息
        $orderInfo = PaymentService::getOrderInfo($orderNo);
        
        if (!$orderInfo) {
            return $this->error('订单不存在');
        }
        
        View::assign([
            'order' => $orderInfo,
            'order_no' => $orderNo
        ]);
        
        return View::fetch('payment/success');
    }
    
    /**
     * 支付失败页面
     */
    public function fail()
    {
        $orderNo = Request::get('order_no', '');
        $errorMsg = Request::get('error', '支付失败');
        
        View::assign([
            'order_no' => $orderNo,
            'error_msg' => $errorMsg
        ]);
        
        return View::fetch('payment/fail');
    }
    
    /**
     * 易支付回调通知
     */
    public function epayNotify()
    {
        $params = Request::get();
        
        // 记录回调数据
        
        // 处理回调
        $result = PaymentService::handlePaymentCallback('epay', $params);
        
        if ($result['code'] == 1) {
            echo 'success';
        } else {
            echo 'fail';
        }
    }
    
    /**
     * 支付宝回调通知
     */
    public function alipayNotify()
    {
        $params = Request::post();
        
        // 记录回调数据
        
        // 处理回调
        $result = PaymentService::handlePaymentCallback('alipay', $params);
        
        if ($result['code'] == 1) {
            echo 'success';
        } else {
            echo 'fail';
        }
    }
    
    /**
     * 微信支付回调通知
     */
    public function wechatNotify()
    {
        try {
            $wechatPayService = new \app\user\service\WeChatPayService();
            return $wechatPayService->handleNotify();
        } catch (\Exception $e) {
            return response('fail', 200);
        }
    }
    
    /**
     * 检查支付状态
     */
    public function checkStatus()
    {
        $orderNo = Request::get('order_no', '');
        
        if (empty($orderNo)) {
            return json(['code' => 0, 'msg' => '订单号不能为空']);
        }
        
        $orderInfo = PaymentService::getOrderInfo($orderNo);
        
        if (!$orderInfo) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        
        // 记录支付状态查询日志
        \app\user\service\PaymentLogger::logQuery($orderNo, $orderInfo['payment_method'] ?? 'unknown', [
            'pay_status' => $orderInfo['pay_status'],
            'status_text' => $orderInfo['pay_status_text'],
            'pay_time' => $orderInfo['pay_time']
        ]);
        
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => [
                'pay_status' => $orderInfo['pay_status'],
                'status_text' => $orderInfo['pay_status_text'],
                'pay_time' => $orderInfo['pay_time']
            ]
        ]);
    }
    
    /**
     * 获取用户ID
     */
    private function getUserId()
    {
        // 从session中获取用户ID
        $userId = session('user_id');
        return $userId ? intval($userId) : 0;
    }
    
    /**
     * 获取游客token
     */
    private function getGuestToken()
    {
        // 生成或获取游客token
        $token = session('guest_token');
        
        if (!$token) {
            $token = md5(uniqid() . time());
            session('guest_token', $token);
        }
        
        return $token;
    }
    
    /**
     * 获取用户余额
     */
    public function getUserBalance()
    {
        try {
            // 获取当前用户ID
            $userId = $this->getUserId();
            
            if (!$userId) {
                return json(['code' => 0, 'msg' => '用户未登录']);
            }
            
            // 获取用户余额
            $user = \think\facade\Db::name('users')
                ->where('id', $userId)
                ->field('balance')
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            
            $balance = floatval($user['balance']);
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'balance' => $balance,
                    'balance_formatted' => '¥' . number_format($balance, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取余额失败']);
        }
    }
    
    /**
     * 错误跳转
     */
    private function error($msg)
    {
        View::assign('error_msg', $msg);
        return View::fetch('payment/error');
    }
}
