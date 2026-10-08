<?php

namespace app\user\service;

use app\common\utils\AgentIdHelper;
use app\model\PaymentMethod;
use app\model\Orders;
use app\model\Products;
use think\facade\Db;
use Exception;

/**
 * 支付服务类
 */
class PaymentService
{
    /**
     * 获取可用的支付方式
     * @return array
     */
    public static function getAvailablePaymentMethods()
    {
        try {
            // 获取所有启用的支付方式
            $methods = PaymentMethod::getEnabledMethods();
            
            $availableMethods = [];
            foreach ($methods as $method) {
                // 检查配置是否完整
                if (self::validatePaymentConfig($method)) {
                    // 如果是易支付，需要拆分为独立的支付渠道
                    if ($method['code'] === 'epay') {
                        $epayMethods = self::splitEpayMethods($method);
                        $availableMethods = array_merge($availableMethods, $epayMethods);
                    } else {
                        $availableMethods[] = [
                            'id' => $method['id'],
                            'code' => $method['code'],
                            'name' => $method['name'],
                            'display_name' => $method['display_name'] ?? $method['name'],
                            'icon' => $method['icon'] ?? '',
                            'icon_text' => $method['icon_text'] ?? '',
                            'sort_order' => $method['sort_order'] ?? 0
                            // 前端不需要配置参数，已删除config字段
                        ];
                    }
                }
            }
            
            // 按排序字段排序
            usort($availableMethods, function($a, $b) {
                return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
            });
            
            return $availableMethods;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    
    /**
     * 将易支付拆分为独立的支付渠道
     * @param array $epayMethod 易支付配置
     * @return array
     */
    private static function splitEpayMethods($epayMethod)
    {
        $methods = [];
        $config = $epayMethod['config'] ?? [];
        
        // 检查启用的支付宝渠道
        $enableAlipay = $config['enable_alipay'] ?? 0;
        if ($enableAlipay == 1) {
            $methods[] = [
                'id' => $epayMethod['id'] . '_alipay',
                'code' => 'epay_alipay',
                'name' => '支付宝',
                'display_name' => $config['alipay_display_name'] ?? '支付宝',
                'icon' => 'alipay',
                'icon_text' => $config['alipay_icon_text'] ?? '支',
                'sort_order' => $epayMethod['sort_order'] ?? 0,
                'epay_type' => 'alipay'
                // 前端不需要配置参数，已删除config字段
            ];
        }
        
        // 检查启用的微信支付渠道
        $enableWechat = $config['enable_wechat'] ?? 0;
        if ($enableWechat == 1) {
            $methods[] = [
                'id' => $epayMethod['id'] . '_wxpay',
                'code' => 'epay_wxpay',
                'name' => '微信支付',
                'display_name' => $config['wxpay_display_name'] ?? '微信支付',
                'icon' => 'wechat',
                'icon_text' => $config['wxpay_icon_text'] ?? '微',
                'sort_order' => ($epayMethod['sort_order'] ?? 0) + 1,
                'epay_type' => 'wxpay'
                // 前端不需要配置参数，已删除config字段
            ];
        }
        
        return $methods;
    }
    
    /**
     * 验证支付配置是否完整
     * @param array $method
     * @return bool
     */
    private static function validatePaymentConfig($method)
    {
        $config = $method['config'] ?? [];
        
        switch ($method['code']) {
            case 'wechat':
                // 微信支付需要商户号、AppID、API密钥和证书
                return !empty($config['mchid']) && 
                       !empty($config['appid']) && 
                       !empty($config['paySignKey']) &&
                       !empty($config['apiclient_cert']) &&
                       !empty($config['apiclient_key']);
                
            case 'alipay':
                // 支付宝需要AppID、私钥和公钥
                return !empty($config['app_id']) && 
                       !empty($config['private_key']) && 
                       !empty($config['public_key']);
                
            case 'epay':
                // 易支付需要商户ID、密钥和API地址
                return !empty($config['pid']) && 
                       !empty($config['key']) && 
                       !empty($config['api_url']);
                
            case 'epay_alipay':
                // 易支付-支付宝需要商户ID、密钥、API地址，并且支付宝渠道已启用
                return !empty($config['pid']) && 
                       !empty($config['key']) && 
                       !empty($config['api_url']) &&
                       ($config['enable_alipay'] ?? 0) == 1;
                
            case 'epay_wxpay':
                // 易支付-微信支付需要商户ID、密钥、API地址，并且微信支付渠道已启用
                return !empty($config['pid']) && 
                       !empty($config['key']) && 
                       !empty($config['api_url']) &&
                       ($config['enable_wechat'] ?? 0) == 1;
                
            case 'balance':
                // 余额支付无需配置
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * 创建订单
     * @param array $orderData
     * @return array
     */
    public static function createOrder($orderData)
    {
        try {
            // 验证必要参数
            $requiredFields = ['product_code', 'product_name', 'quantity', 'unit_price', 'total_amount'];
            foreach ($requiredFields as $field) {
                if (empty($orderData[$field])) {
                    throw new Exception("缺少必要参数: {$field}");
                }
            }
            
            // 生成订单号
            $orderNo = self::generateOrderNo();
            
            // 获取商品信息
            $product = Products::where('code', $orderData['product_code'])->find();
            $productId = $product ? $product->id : 0;
            
            // 创建订单数据
            $order = new Orders();
            $order->order_no = $orderNo;
            $order->user_id = $orderData['user_id'] ?? 0;
            $order->guest_token = $orderData['guest_token'] ?? null;
            $order->product_id = $productId;
            $order->product_code = $orderData['product_code'];
            $order->product_name = $orderData['product_name'];
            $order->quantity = $orderData['quantity'];
            $order->unit_price = $orderData['unit_price'];
            $order->total_amount = $orderData['total_amount'];
            $order->payment_method = $orderData['payment_method'] ?? '';
            $order->payment_method_name = $orderData['payment_method_name'] ?? '';
            $order->status = 0; // 待支付
            $order->pay_status = 0; // 未支付
            $order->contact_name = $orderData['contact_name'] ?? '';
            $order->contact_email = $orderData['contact_email'] ?? '';
            $order->contact_phone = $orderData['contact_phone'] ?? '';
            $order->remark = $orderData['remark'] ?? '';
            $order->ip_address = request()->ip();
            $order->user_agent = request()->header('user-agent');
            $order->expire_time = date('Y-m-d H:i:s', time() + 1800); // 30分钟后过期
            
            if ($order->save()) {
                return [
                    'code' => 1,
                    'msg' => '订单创建成功',
                    'data' => [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                        'total_amount' => $order->total_amount,
                        'expire_time' => $order->expire_time
                    ]
                ];
            } else {
                throw new Exception('订单保存失败');
            }
            
        } catch (Exception $e) {
            return [
                'code' => 0,
                'msg' => '订单创建失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成订单号
     * @return string
     */
    private static function generateOrderNo()
    {
        return date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * 获取订单信息（支持普通订单和充值订单）
     * @param string $orderNo
     * @return array|null
     */
    public static function getOrderInfo($orderNo)
    {
        try {
            // 先尝试查询普通订单
            $order = Orders::where('order_no', $orderNo)->find();
            
            if ($order) {
                return [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                    'user_id' => $order->user_id,
                    'product_id' => $order->product_id,
                    'product_code' => $order->product_code,
                    'product_name' => $order->product_name,
                    'quantity' => $order->quantity,
                    'unit_price' => $order->unit_price,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'payment_method_name' => $order->payment_method_name,
                    'status' => $order->status,
                    'pay_status' => $order->pay_status,
                    'pay_time' => $order->pay_time,
                    'create_time' => $order->create_time,
                    'expire_time' => $order->expire_time,
                    'status_text' => $order->status_text,
                    'pay_status_text' => $order->pay_status_text,
                    'order_type' => 'normal' // 普通订单
                ];
            }
            
            // 如果不是普通订单，尝试查询充值订单
            $rechargeOrder = Db::name('recharge_orders')
                ->where('order_no', $orderNo)
                ->find();
            
            if ($rechargeOrder) {
                return [
                    'id' => $rechargeOrder['id'],
                    'order_no' => $rechargeOrder['order_no'],
                    'product_id' => 0,
                    'product_code' => 'recharge',
                    'product_name' => '账户充值',
                    'quantity' => 1,
                    'unit_price' => $rechargeOrder['amount'],
                    'total_amount' => $rechargeOrder['amount'],
                    'payment_method' => $rechargeOrder['payment_method'] ?? '',
                    'payment_method_name' => $rechargeOrder['payment_method_name'] ?? '',
                    'status' => $rechargeOrder['status'],
                    'pay_status' => $rechargeOrder['pay_status'],
                    'pay_time' => $rechargeOrder['pay_time'],
                    'create_time' => $rechargeOrder['create_time'],
                    'expire_time' => $rechargeOrder['expire_time'],
                    'status_text' => self::getOrderStatusText($rechargeOrder['status']),
                    'pay_status_text' => self::getPayStatusText($rechargeOrder['pay_status']),
                    'order_type' => 'recharge', // 充值订单
                    'user_id' => $rechargeOrder['user_id']
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取订单状态文本
     */
    private static function getOrderStatusText($status)
    {
        $statusMap = [
            0 => '待支付',
            1 => '已支付',
            2 => '处理中',
            3 => '已完成',
            4 => '已取消',
            5 => '已退款'
        ];
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取支付状态文本
     */
    private static function getPayStatusText($payStatus)
    {
        $statusMap = [
            0 => '未支付',
            1 => '已支付',
            2 => '支付失败'
        ];
        return $statusMap[$payStatus] ?? '未知';
    }
    
    /**
     * 更新订单支付状态（支持普通订单和充值订单）
     * @param string $orderNo
     * @param string $paymentMethod
     * @param string $transactionId
     * @param array $paymentData 支付数据，用于日志记录
     * @return bool
     */
    public static function updateOrderPayment($orderNo, $paymentMethod, $transactionId = '', $paymentData = [])
    {
        try {
            
            Db::startTrans();
            
            // 先尝试查询普通订单
            $order = Orders::where('order_no', $orderNo)->lock(true)->find();
            $isRechargeOrder = false;
            
            // 如果不是普通订单，查询充值订单
            if (!$order) {
                $rechargeOrder = Db::name('recharge_orders')
                    ->where('order_no', $orderNo)
                    ->lock(true)
                    ->find();
                
                if ($rechargeOrder) {
                    $isRechargeOrder = true;
                    
                    // 检查是否已支付
                    if ($rechargeOrder['pay_status'] == 1) {
                        
                        // 即使订单已支付，也要检查并确保余额已增加
                        // 防止第一次回调时余额更新失败的情况
                        $userId = $rechargeOrder['user_id'];
                        $rechargeAmount = floatval($rechargeOrder['amount']);
                        
                        // 获取当前余额
                        $user = Db::name('users')
                            ->where('id', $userId)
                            ->lock(true)
                            ->find();
                        
                        if ($user) {
                            $currentBalance = floatval($user['balance'] ?? 0);
                            $expectedBalance = $currentBalance;
                            
                            // 检查余额日志，确认是否已经记录过这笔充值
                            $balanceLog = Db::name('balance_logs')
                                ->where('user_id', $userId)
                                ->where('related_id', $orderNo)
                                ->where('change_type', 'recharge')
                                ->find();
                            
                            if (!$balanceLog) {
                                
                                // 获取赠送金额
                                $bonusAmount = floatval($rechargeOrder['bonus_amount'] ?? 0.00);
                                $totalAmount = floatval($rechargeOrder['total_amount'] ?? $rechargeAmount);
                                
                                // 增加用户余额（包含赠送金额）
                                $beforeBalance = $currentBalance;
                                $afterBalance = $beforeBalance + $totalAmount;
                                
                                // 更新用户余额
                                Db::name('users')
                                    ->where('id', $userId)
                                    ->update([
                                        'balance' => $afterBalance,
                                        'update_time' => date('Y-m-d H:i:s')
                                    ]);
                                
                                
                                // 记录余额变动日志（包含赠送金额）
                                $remark = '账户充值（补充）';
                                if ($bonusAmount > 0) {
                                    $remark .= "（赠送 {$bonusAmount} 元）";
                                }
                                
                                Db::name('balance_logs')->insert([
                                    'user_id' => $userId,
                                    'change_type' => 'recharge',
                                    'change_amount' => $rechargeAmount,
                                    'bonus_amount' => $bonusAmount,
                                    'total_change_amount' => $totalAmount,
                                    'before_balance' => $beforeBalance,
                                    'after_balance' => $afterBalance,
                                    'related_id' => $orderNo,
                                    'related_type' => 'recharge',
                                    'remark' => $remark,
                                    'operator_type' => 'system',
                                    'ip_address' => $rechargeOrder['ip_address'] ?? '',
                                    'create_time' => date('Y-m-d H:i:s')
                                ]);
                                
                            } else {
                            }
                        }
                        
                        Db::commit();
                        return true;
                    }
                    
                    // 更新充值订单状态
                    $updateData = [
                        'payment_method' => $paymentMethod,
                        'pay_status' => 1,
                        'status' => 1,
                        'pay_time' => date('Y-m-d H:i:s'),
                        'pay_transaction_id' => $transactionId,
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    
                    $updateResult = Db::name('recharge_orders')
                        ->where('order_no', $orderNo)
                        ->update($updateData);
                    
                    if (!$updateResult) {
                        throw new Exception('更新充值订单状态失败');
                    }
                    
                    
                    // 增加用户余额（包含赠送金额）
                    $userId = $rechargeOrder['user_id'];
                    $rechargeAmount = floatval($rechargeOrder['amount']);
                    $bonusAmount = floatval($rechargeOrder['bonus_amount'] ?? 0.00);
                    $totalAmount = floatval($rechargeOrder['total_amount'] ?? $rechargeAmount);
                    
                    // 获取当前余额
                    $user = Db::name('users')
                        ->where('id', $userId)
                        ->lock(true)
                        ->find();
                    
                    if (!$user) {
                        throw new Exception('用户不存在');
                    }
                    
                    $beforeBalance = floatval($user['balance'] ?? 0);
                    $afterBalance = $beforeBalance + $totalAmount;
                    
                    // 更新用户余额
                    Db::name('users')
                        ->where('id', $userId)
                        ->update([
                            'balance' => $afterBalance,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                    
                    
                    // 记录余额变动日志（包含赠送金额）
                    $remark = '账户充值';
                    if ($bonusAmount > 0) {
                        $remark .= "（赠送 {$bonusAmount} 元）";
                    }
                    
                    Db::name('balance_logs')->insert([
                        'user_id' => $userId,
                        'change_type' => 'recharge',
                        'change_amount' => $rechargeAmount,
                        'bonus_amount' => $bonusAmount,
                        'total_change_amount' => $totalAmount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'related_id' => $orderNo,
                        'related_type' => 'recharge',
                        'remark' => $remark,
                        'operator_type' => 'system',
                        'ip_address' => $rechargeOrder['ip_address'] ?? '',
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                    
                    
                    // 记录支付成功日志
                    \app\user\service\PaymentLogger::logSuccess($orderNo, $paymentMethod, [
                        'transaction_id' => $transactionId,
                        'pay_time' => $updateData['pay_time'],
                        'total_amount' => $rechargeAmount,
                        'payment_data' => $paymentData,
                        'order_type' => 'recharge'
                    ]);
                    
                    Db::commit();
                    return true;
                } else {
                    throw new Exception('订单不存在');
                }
            }
            
            // 处理普通订单
            
            if ($order->pay_status == 1) {
                // 订单已支付，记录重复支付日志
                \app\user\service\PaymentLogger::logWarning($orderNo, $paymentMethod, '重复支付回调，订单已支付', [
                    'transaction_id' => $transactionId,
                    'original_pay_time' => $order->pay_time
                ]);
                
                Db::commit();
                return true;
            }
            
            // 更新订单状态
            $order->payment_method = $paymentMethod;
            $order->pay_status = 1; // 已支付
            $order->status = 1; // 已支付
            $order->pay_time = date('Y-m-d H:i:s');
            $order->pay_transaction_id = $transactionId;
            
            
            if (!$order->save()) {
                throw new Exception('更新订单状态失败');
            }
            
            
            // 记录支付成功日志
            \app\user\service\PaymentLogger::logSuccess($orderNo, $paymentMethod, [
                'transaction_id' => $transactionId,
                'pay_time' => $order->pay_time,
                'total_amount' => $order->total_amount,
                'payment_data' => $paymentData
            ]);
            
            Db::commit();
            
            
            // 支付成功后，根据商品类型执行后续动作

            // 1. 对接 TokenAPI 的写作 / 高级论文 / PPT 等老商品
            if (in_array($order->product_code, ['kaiti', 'rws', 'sx', 'sxrz', 'gjlw', 'ppt'])) {
                try {
                    $tokenApiResult = self::submitOrderToTokenAPI($orderNo);
                    
                    if ($tokenApiResult) {
                    } else {
                    }
                } catch (Exception $tokenApiException) {
                }
            }

            return true;
            
        } catch (Exception $e) {
            
            Db::rollback();
            
            // 记录错误日志
            \app\user\service\PaymentLogger::logError($orderNo, $paymentMethod, '更新订单支付状态失败: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'error_data' => $paymentData
            ]);
            
            return false;
        }
    }
    
    /**
     * 处理支付回调
     * @param string $paymentMethod
     * @param array $callbackData
     * @return array
     */
    public static function handlePaymentCallback($paymentMethod, $callbackData)
    {
        try {
            $orderNo = $callbackData['out_trade_no'] ?? '';
            
            if (empty($orderNo)) {
                throw new Exception('订单号不能为空');
            }
            
            // 根据支付方式处理回调
            switch ($paymentMethod) {
                case 'wechat':
                    return self::handleWechatCallback($orderNo, $callbackData);
                    
                case 'alipay':
                    return self::handleAlipayCallback($orderNo, $callbackData);
                    
                case 'epay':
                    return self::handleEpayCallback($orderNo, $callbackData);
                    
                default:
                    throw new Exception('不支持的支付方式');
            }
            
        } catch (Exception $e) {
            return [
                'code' => 0,
                'msg' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 处理微信支付回调
     * @param string $orderNo
     * @param array $callbackData
     * @return array
     */
    private static function handleWechatCallback($orderNo, $callbackData)
    {
        try {
            
            // 验证回调数据
            if (empty($callbackData['transaction_id'])) {
                return [
                    'code' => 0,
                    'msg' => '回调数据不完整'
                ];
            }
            
            $transactionId = $callbackData['transaction_id'];
            
            // 检查交易状态（借鉴支付借鉴中的逻辑）
            $tradeState = $callbackData['trade_state'] ?? '';
            if ($tradeState !== 'SUCCESS') {
                return [
                    'code' => 0,
                    'msg' => '交易未成功'
                ];
            }
            
            // 验证支付金额（如果回调数据中有金额信息）
            if (isset($callbackData['amount']['total'])) {
                $totalFee = $callbackData['amount']['total']; // 单位为分
                
                // 获取订单信息
                $order = Orders::where('order_no', $orderNo)->find();
                if ($order) {
                    $orderAmountYuan = floatval($order->total_amount);
                    $callbackAmountYuan = floatval($totalFee) / 100;
                    
                    if (abs($orderAmountYuan - $callbackAmountYuan) > 0.01) {
                        return [
                            'code' => 0,
                            'msg' => '金额不匹配'
                        ];
                    }
                }
            }
            
            // 更新订单支付状态
            if (self::updateOrderPayment($orderNo, 'wechat', $transactionId, $callbackData)) {
                return [
                    'code' => 1,
                    'msg' => '支付成功'
                ];
            } else {
                return [
                    'code' => 0,
                    'msg' => '更新订单状态失败'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'code' => 0,
                'msg' => '处理异常: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 处理支付宝回调
     * @param string $orderNo
     * @param array $callbackData
     * @return array
     */
    private static function handleAlipayCallback($orderNo, $callbackData)
    {
        // 安全校验：必须先通过支付宝官方验签，未验签一律拒绝
        if (!self::verifyAlipayNotifySign($callbackData)) {
            return [
                'code' => 0,
                'msg' => '支付宝回调验签失败'
            ];
        }

        $transactionId = $callbackData['trade_no'] ?? '';

        if (self::updateOrderPayment($orderNo, 'alipay', $transactionId)) {
            return [
                'code' => 1,
                'msg' => '支付成功'
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '更新订单状态失败'
            ];
        }
    }

    /**
     * 支付宝回调验签（EasySDK verifyNotify，与 pay 应用回调一致）
     */
    private static function verifyAlipayNotifySign($callbackData): bool
    {
        try {
            $paymentMethod = \app\model\PaymentMethod::getByCode('alipay');
            if (!$paymentMethod || empty($paymentMethod['config']) || $paymentMethod['enabled'] != 1) {
                return false;
            }
            $config = $paymentMethod['config'];

            $options = new \Alipay\EasySDK\Kernel\Config();
            $options->protocol = 'https';
            $options->gatewayHost = 'openapi.alipay.com';
            $options->signType = 'RSA2';
            $options->appId = $config['app_id'] ?? '';
            $options->merchantPrivateKey = $config['private_key'] ?? '';
            $options->alipayPublicKey = $config['public_key'] ?? '';
            $options->notifyUrl = config('payment.alipay.notify_url', '');
            $options->ignoreSSL = true;

            \Alipay\EasySDK\Kernel\Factory::setOptions($options);
            $alipay = \Alipay\EasySDK\Kernel\Factory::payment();

            return (bool) $alipay->common()->verifyNotify($callbackData);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * 处理易支付回调
     * @param string $orderNo
     * @param array $callbackData
     * @return array
     */
    private static function handleEpayCallback($orderNo, $callbackData)
    {
        try {
            // 使用易支付服务类处理回调
            $epayService = new EPayService();
            $result = $epayService->handleNotify($callbackData);
            
            if ($result) {
                return [
                    'code' => 1,
                    'msg' => '支付成功'
                ];
            } else {
                return [
                    'code' => 0,
                    'msg' => '易支付回调处理失败'
                ];
            }
        } catch (Exception $e) {
            return [
                'code' => 0,
                'msg' => '易支付回调处理异常: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 处理微信支付回调通知
     * @param string $orderNo 订单号
     * @param string $transactionId 微信交易号
     * @param float $totalFee 支付金额（分）
     * @param array $callbackData 回调数据
     * @return bool
     */
    public static function handleWechatNotify($orderNo, $transactionId, $totalFee, $callbackData)
    {
        try {
            
            // 验证订单是否存在
            $order = Orders::where('order_no', $orderNo)->find();
            if (!$order) {
                return false;
            }
            
            
            // 检查订单是否已支付
            if ($order->pay_status == 1) {
                return true;
            }
            
            // 验证支付金额（微信支付金额单位为分，需要转换为元）
            $orderAmountYuan = floatval($order->total_amount);
            $callbackAmountYuan = floatval($totalFee) / 100;
            
            
            if (abs($orderAmountYuan - $callbackAmountYuan) > 0.01) {
                return false;
            }
            
            // 更新订单支付状态
            $result = self::updateOrderPayment($orderNo, 'wechat', $transactionId, $callbackData);
            
            if ($result) {
                return true;
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 验证易支付签名
     * @param array $data
     * @param string $key
     * @return bool
     */
    private static function verifyEpaySign($data, $key)
    {
        $sign = $data['sign'] ?? '';
        unset($data['sign'], $data['sign_type']);
        
        // 移除空值
        $data = array_filter($data, function($value) {
            return $value !== '' && $value !== null;
        });
        
        // 按键名排序
        ksort($data);
        
        // 拼接签名字符串
        $signString = '';
        foreach ($data as $k => $v) {
            $signString .= $k . '=' . $v . '&';
        }
        $signString .= $key;
        
        // 计算MD5签名
        $calculatedSign = strtolower(md5($signString));
        
        return $sign === $calculatedSign;
    }
    
    /**
     * 获取支付配置
     * @param string $paymentCode
     * @return array|null
     */
    public static function getPaymentConfig($paymentCode)
    {
        try {
            // 处理易支付渠道的特殊情况
            if (strpos($paymentCode, 'epay_') === 0) {
                // 易支付渠道，使用 epay 作为code获取配置
                $method = PaymentMethod::getByCode('epay');
            } else {
                $method = PaymentMethod::getByCode($paymentCode);
            }
            
            if (!$method || empty($method['config'])) {
                return null;
            }
            
            return $method['config'];
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * 在支付前提交订单到TokenAPI（用于余额支付，确保TokenAPI提交成功后再扣除余额）
     * @param string $orderNo 订单号
     * @return array ['success' => bool, 'isBalanceInsufficient' => bool]
     */
    public static function submitOrderToTokenAPIBeforePayment($orderNo)
    {
        try {
            
            // 获取订单信息
            $order = Orders::where('order_no', $orderNo)->find();
            
            if (!$order) {
                return ['success' => false, 'isBalanceInsufficient' => false];
            }
            
            
            // 注意：这里不检查支付状态，因为是在支付前调用
            
            // 检查订单是否已提交到TokenAPI
            if ($order->tokenapi_submitted == 1) {
                return ['success' => true, 'isBalanceInsufficient' => false];
            }
            
            // 检查订单是否已经在TokenAPI创建（通过 createPaperOrder/createKaitiOrder 等接口）
            // 如果 tokenapi_response 中有 order_sn，说明订单已经在TokenAPI创建，只需要调用支付确认接口
            $tokenApiOrderSn = null;
            if (!empty($order->tokenapi_response)) {
                $responseData = json_decode($order->tokenapi_response, true);
                if (is_array($responseData)) {
                    // 检查是否有 order_sn（可能是直接返回的，或者在 data 中）
                    $tokenApiOrderSn = $responseData['order_sn'] ?? $responseData['data']['order_sn'] ?? null;
                    if ($tokenApiOrderSn) {
                    }
                }
            }
            
            $productCode = $order->product_code;
            $apiPath = '';
            $apiData = [];
            
            // 如果订单未在TokenAPI创建，需要先创建订单，然后确认支付
            
            // 解析 product_data（JSON格式）
            $productData = [];
            if (!empty($order->product_data)) {
                $productData = json_decode($order->product_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $productData = [];
                } else {
                }
            } else {
            }

            // 对于 PPT 历史订单：如果 product_data 中已经包含 ppt_order_sn，说明 TokenAPI 侧订单已存在，
            // 这里直接走支付确认逻辑，而不再尝试重新创建 PPT 订单。
            if ($productCode === 'ppt' && !$tokenApiOrderSn && !empty($productData['ppt_order_sn'])) {
                $tokenApiOrderSn = $productData['ppt_order_sn'];
            }

            // 统一的“已存在 TokenAPI 订单”分支：无论是从 tokenapi_response 还是 product_data 得到的 order_sn
            if ($tokenApiOrderSn) {
                
                $paymentConfirmed = self::confirmTokenAPIPayment($productCode, $tokenApiOrderSn, $order);
                if ($paymentConfirmed) {
                    $order->tokenapi_submitted = 1;
                    $order->save();
                    return ['success' => true, 'isBalanceInsufficient' => false];
                } else {
                    $order->refresh();
                    $isBalanceInsufficient = false;
                    if ($order->status == 6) {
                        $isBalanceInsufficient = true;
                    }
                    return ['success' => false, 'isBalanceInsufficient' => $isBalanceInsufficient];
                }
            }

            switch($productCode) {
                case 'kaiti':
                    $apiPath = '/tokenapi/write/kaitiOrder';
                    $apiData = [
                        'title' => $productData['title'] ?? '',
                        'template_id' => isset($productData['template_id']) ? intval($productData['template_id']) : (isset($productData['templateId']) ? intval($productData['templateId']) : 0),
                        'outline' => $productData['outline'] ?? $productData['outlines'] ?? [],
                    ];
                    if (isset($productData['wxtype'])) {
                        $apiData['literature_area'] = $productData['wxtype'];
                    }
                    if (isset($productData['wxnum'])) {
                        $apiData['literature_count'] = intval($productData['wxnum']);
                    }
                    if (isset($productData['wenxianlist']) && is_array($productData['wenxianlist']) && !empty($productData['wenxianlist'])) {
                        $apiData['literature_list'] = $productData['wenxianlist'];
                    } elseif (isset($productData['wxlist']) && !empty($productData['wxlist'])) {
                        $apiData['literature_list'] = is_array($productData['wxlist']) ? $productData['wxlist'] : explode("\n", $productData['wxlist']);
                    }
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return ['success' => false, 'isBalanceInsufficient' => false];
                    }
                    break;
                    
                case 'gjlw':
                    $apiPath = '/tokenapi/paper/order';
                    $apiData = [
                        'record_id' => isset($productData['record_id']) ? intval($productData['record_id']) : 0,
                        'outline' => $productData['outlines'] ?? $productData['outline'] ?? [],
                        'template_id' => isset($productData['template']) ? intval($productData['template']) : (isset($productData['template_id']) ? intval($productData['template_id']) : 0),
                        'selftemp' => isset($productData['selftemp']) ? intval($productData['selftemp']) : 0,
                        'agent_amount' => floatval($order->total_amount ?? 0),
                        'agent_id' => $order->user_id ? AgentIdHelper::build((int)$order->user_id) : ''
                    ];
                    if ($apiData['record_id'] <= 0 || $apiData['template_id'] <= 0) {
                        return ['success' => false, 'isBalanceInsufficient' => false];
                    }
                    break;
                    
                case 'ppt':
                    $apiPath = '/tokenapi/ppt/createOrder';
                    $apiData = [
                        'title' => trim($productData['pp_ttitle'] ?? $productData['title'] ?? ''),
                        'author' => trim($productData['author'] ?? ''),
                        'chapter_count' => isset($productData['chapter_count']) ? intval($productData['chapter_count']) : 6,
                        'section_range' => isset($productData['section_range']) ? $productData['section_range'] : '3-6'
                    ];
                    if (!empty($productData['outline_content'])) {
                        $apiData['outline_content'] = trim($productData['outline_content']);
                    } elseif (!empty($productData['content'])) {
                        $apiData['outline_content'] = trim($productData['content']);
                    }
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData['title'])) {
                        return ['success' => false, 'isBalanceInsufficient' => false];
                    }
                    break;
                    
                case 'rws':
                    $apiPath = '/tokenapi/write/rwsOrder';
                    $template_id = 0;
                    if (isset($productData['template_id']) && !empty($productData['template_id'])) {
                        $template_id = intval($productData['template_id']);
                    } elseif (isset($productData['templateId']) && !empty($productData['templateId'])) {
                        $template_id = intval($productData['templateId']);
                    }
                    $apiData = [
                        'title' => trim($productData['title'] ?? ''),
                        'template_id' => $template_id,
                        'outline' => $productData['outline'] ?? $productData['outlines'] ?? [],
                    ];
                    if (isset($productData['wxtype']) && !empty($productData['wxtype'])) {
                        $wxtype = $productData['wxtype'];
                        if ($wxtype === '全部' || $wxtype === '全部文献') {
                            $apiData['literature_area'] = 'all';
                        } elseif ($wxtype === '中文' || $wxtype === '中文文献') {
                            $apiData['literature_area'] = 'zh';
                        } elseif ($wxtype === '英文' || $wxtype === '英文文献') {
                            $apiData['literature_area'] = 'en';
                        } else {
                            $apiData['literature_area'] = $wxtype;
                        }
                    }
                    if (isset($productData['wxnum'])) {
                        $apiData['literature_count'] = intval($productData['wxnum']);
                    }
                    if (isset($productData['wxlist']) && !empty($productData['wxlist'])) {
                        $apiData['literature_list'] = is_array($productData['wxlist']) ? $productData['wxlist'] : explode("\n", $productData['wxlist']);
                    }
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return ['success' => false, 'isBalanceInsufficient' => false];
                    }
                    break;
                    
                case 'sx':
                    $apiPath = '/tokenapi/write/sxOrder';
                    $template_id = 0;
                    if (isset($productData['template_id']) && !empty($productData['template_id'])) {
                        $template_id = intval($productData['template_id']);
                    } elseif (isset($productData['templateId']) && !empty($productData['templateId'])) {
                        $template_id = intval($productData['templateId']);
                    }
                    $apiData = [
                        'title' => trim($productData['title'] ?? ''),
                        'template_id' => $template_id,
                    ];
                    if (isset($productData['unit']) && !empty($productData['unit'])) {
                        $apiData['unit'] = trim($productData['unit']);
                    }
                    if (isset($productData['unit_text']) && !empty($productData['unit_text'])) {
                        $apiData['unit_text'] = trim($productData['unit_text']);
                    }
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return ['success' => false, 'isBalanceInsufficient' => false];
                    }
                    break;
                    
                case 'sxrz':
                    $apiPath = '/tokenapi/write/sxrzOrder';
                    $apiData = $productData;
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData)) {
                        return false;
                    }
                    break;
                    
                default:
                    return false;
            }
            
            
            // 获取TokenAPI配置
            $apiUrl = config('docking.api_url');
            $apiToken = config('docking.api_token');
            
            if (empty($apiUrl) || empty($apiToken)) {
                return ['success' => false, 'isBalanceInsufficient' => false];
            }
            
            // 构建完整URL
            $url = rtrim($apiUrl, '/') . $apiPath;
            
            
            // 调用TokenAPI
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            
            if ($error) {
                $order->tokenapi_response = json_encode([
                    'error' => 'curl请求失败',
                    'error_message' => $error,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                $order->save();
                return ['success' => false, 'isBalanceInsufficient' => false];
            }
            
            if ($httpCode !== 200) {
                $order->tokenapi_response = json_encode([
                    'error' => 'HTTP状态码错误',
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                $order->save();
                return ['success' => false, 'isBalanceInsufficient' => false];
            }
            
            
            $result = json_decode($response, true);
            
            // 保存响应数据到数据库
            $order->tokenapi_response = json_encode([
                'http_code' => $httpCode,
                'response' => $result,
                'raw_response' => $response
            ]);
            
            if (!$result || !isset($result['code']) || $result['code'] != 1) {
                $errorMsg = $result['msg'] ?? '未知错误';
                
                // 检测是否为余额不足
                $isBalanceInsufficient = false;
                $errorCode = $result['error_code'] ?? null;
                $code = $result['code'] ?? null;
                $msg = $errorMsg;
                
                // 方式1：通过 error_code 判断
                if ($errorCode !== null) {
                    $isBalanceInsufficient = ($errorCode == 1001 || $errorCode == 1002 || $errorCode == -1001);
                }
                
                // 方式2：通过 code 判断
                if (!$isBalanceInsufficient && $code !== null && $code != 1) {
                    $isBalanceInsufficient = ($code == -1 || $code == 1001);
                }
                
                // 方式3：通过 msg 判断（最可靠的方式）
                if (!$isBalanceInsufficient && $msg) {
                    $msgLower = strtolower($msg);
                    $isBalanceInsufficient = (strpos($msgLower, '余额不足') !== false || 
                                            (strpos($msgLower, '余额') !== false && strpos($msgLower, '不足') !== false));
                }
                
                $order->save();
                return ['success' => false, 'isBalanceInsufficient' => $isBalanceInsufficient];
            }
            
            // TokenAPI创建订单成功，保存返回的 order_sn（如果有）
            $tokenApiOrderSn = $result['data']['order_sn'] ?? null;
            if ($tokenApiOrderSn) {
                $responseData = json_decode($order->tokenapi_response, true);
                if (is_array($responseData)) {
                    $responseData['tokenapi_order_sn'] = $tokenApiOrderSn;
                    $order->tokenapi_response = json_encode($responseData);
                }
            }
            
            // 更新订单的TokenAPI提交状态（但不更新支付状态，因为还没支付）
            $order->tokenapi_submitted = 1;
            $order->save();
            
            if ($tokenApiOrderSn) {
            }
            
            // 调用TokenAPI支付确认接口
            if ($tokenApiOrderSn) {
                $paymentConfirmed = self::confirmTokenAPIPayment($productCode, $tokenApiOrderSn, $order);
                if ($paymentConfirmed) {
                } else {
                    // 检查订单状态是否已更新为 6（支付异常），如果是余额不足，confirmTokenAPIPayment 已经更新了状态
                    $order->refresh();
                    $isBalanceInsufficient = false;
                    if ($order->status == 6) {
                        $isBalanceInsufficient = true;
                    }
                    return ['success' => false, 'isBalanceInsufficient' => $isBalanceInsufficient];
                }
            } else {
                return ['success' => false, 'isBalanceInsufficient' => false];
            }
            
            return ['success' => true, 'isBalanceInsufficient' => false];
            
        } catch (Exception $e) {
            return ['success' => false, 'isBalanceInsufficient' => false];
        }
    }
    
    /**
     * 提交订单到TokenAPI
     * @param string $orderNo 订单号
     * @return bool
     */
    private static function submitOrderToTokenAPI($orderNo)
    {
        try {
            
            // 获取订单信息
            $order = Orders::where('order_no', $orderNo)->find();
            
            if (!$order) {
                return false;
            }
            
            
            // 检查订单是否已支付
            if ($order->pay_status != 1) {
                return false;
            }
            
            // 检查订单是否已提交到TokenAPI
            if ($order->tokenapi_submitted == 1) {
                return true;
            }
            
            // 检查订单是否已经在TokenAPI创建（通过 createPaperOrder/createKaitiOrder 等接口）
            // 如果 tokenapi_response 中有 order_sn，说明订单已经在TokenAPI创建，只需要调用支付确认接口
            $tokenApiOrderSn = null;
            if (!empty($order->tokenapi_response)) {
                $responseData = json_decode($order->tokenapi_response, true);
                if (is_array($responseData)) {
                    // 检查是否有 order_sn（可能是直接返回的，或者在 data 中）
                    $tokenApiOrderSn = $responseData['order_sn'] ?? $responseData['data']['order_sn'] ?? null;
                    if ($tokenApiOrderSn) {
                    }
                }
            }
            
            $productCode = $order->product_code;
            $apiPath = '';
            $apiData = [];
            
            // 如果订单已经在TokenAPI创建，直接调用支付确认接口
            if ($tokenApiOrderSn) {
                
                // 调用支付确认接口
                $paymentConfirmed = self::confirmTokenAPIPayment($productCode, $tokenApiOrderSn, $order);
                if ($paymentConfirmed) {
                    // 更新订单的TokenAPI提交状态
                    $order->tokenapi_submitted = 1;
                    $order->save();
                    return true;
                } else {
                    return false;
                }
            }
            
            // 如果订单未在TokenAPI创建，需要先创建订单，然后确认支付
            
            // 解析 product_data（JSON格式）
            $productData = [];
            if (!empty($order->product_data)) {
                $productData = json_decode($order->product_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $productData = [];
                } else {
                }
            } else {
            }
            
            switch($productCode) {
                case 'kaiti':
                    // 开题报告：使用 /tokenapi/write/kaitiOrder 创建订单
                    $apiPath = '/tokenapi/write/kaitiOrder';
                    // 清理和映射字段，只保留 TokenAPI 需要的字段（参考 api.php 的 ktsave 逻辑）
                    $apiData = [
                        'title' => $productData['title'] ?? '',
                        'template_id' => isset($productData['template_id']) ? intval($productData['template_id']) : (isset($productData['templateId']) ? intval($productData['templateId']) : 0),
                        'outline' => $productData['outline'] ?? $productData['outlines'] ?? [],
                    ];
                    
                    // 可选字段映射
                    if (isset($productData['wxtype'])) {
                        $apiData['literature_area'] = $productData['wxtype'];
                    }
                    if (isset($productData['wxnum'])) {
                        $apiData['literature_count'] = intval($productData['wxnum']);
                    }
                    if (isset($productData['wenxianlist']) && is_array($productData['wenxianlist']) && !empty($productData['wenxianlist'])) {
                        $apiData['literature_list'] = $productData['wenxianlist'];
                    } elseif (isset($productData['wxlist']) && !empty($productData['wxlist'])) {
                        // 如果 wxlist 是字符串，转换为数组
                        $apiData['literature_list'] = is_array($productData['wxlist']) ? $productData['wxlist'] : explode("\n", $productData['wxlist']);
                    }
                    
                    // 代理相关字段（如果没有用户ID，传0；agent_amount 使用订单金额）
                    // 老版 api.php 直接用 uid，这里改为「可配置前缀 + user_id」，便于在 TokenAPI 侧区分来源
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    
                    // 验证必填字段
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return false;
                    }
                    break;
                    
                case 'gjlw':
                    // 高级论文：使用 /tokenapi/paper/order 创建订单
                    $apiPath = '/tokenapi/paper/order';
                    // 转换数据格式以匹配 tokenapi 的期望格式（参考 api.php 的 place 逻辑）
                    $apiData = [
                        'record_id' => isset($productData['record_id']) ? intval($productData['record_id']) : 0,
                        'outline' => $productData['outlines'] ?? $productData['outline'] ?? [],
                        'template_id' => isset($productData['template']) ? intval($productData['template']) : (isset($productData['template_id']) ? intval($productData['template_id']) : 0),
                        'selftemp' => isset($productData['selftemp']) ? intval($productData['selftemp']) : 0,
                        'agent_amount' => floatval($order->total_amount ?? 0),
                        // 高级论文 agent_id 与站点配置的 agent_id_prefix 规则一致
                        'agent_id' => $order->user_id ? AgentIdHelper::build((int)$order->user_id) : ''
                    ];
                    // 验证必填字段
                    if ($apiData['record_id'] <= 0 || $apiData['template_id'] <= 0) {
                        return false;
                    }
                    break;
                    
                case 'ppt':
                    // PPT：使用 /tokenapi/ppt/createOrder 创建订单
                    $apiPath = '/tokenapi/ppt/createOrder';
                    // 参数映射：将前端参数映射为 tokenapi 需要的格式（参考 api.php 的 create_pptorder 逻辑）
                    $apiData = [
                        'title' => trim($productData['pp_ttitle'] ?? $productData['title'] ?? ''),
                        'author' => trim($productData['author'] ?? ''),
                        'chapter_count' => isset($productData['chapter_count']) ? intval($productData['chapter_count']) : 6,
                        'section_range' => isset($productData['section_range']) ? $productData['section_range'] : '3-6'
                    ];
                    // 优先使用 outline_content 参数
                    if (!empty($productData['outline_content'])) {
                        $apiData['outline_content'] = trim($productData['outline_content']);
                    } elseif (!empty($productData['content'])) {
                        $apiData['outline_content'] = trim($productData['content']);
                    }
                    // 代理相关字段
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    // 验证必填字段
                    if (empty($apiData['title'])) {
                        return false;
                    }
                    break;
                    
                case 'rws':
                    // 任务书：使用 /tokenapi/write/rwsOrder 创建订单
                    $apiPath = '/tokenapi/write/rwsOrder';
                    // 提取 template_id
                    $template_id = 0;
                    if (isset($productData['template_id']) && !empty($productData['template_id'])) {
                        $template_id = intval($productData['template_id']);
                    } elseif (isset($productData['templateId']) && !empty($productData['templateId'])) {
                        $template_id = intval($productData['templateId']);
                    }
                    // 清理和映射字段
                    $apiData = [
                        'title' => trim($productData['title'] ?? ''),
                        'template_id' => $template_id,
                        'outline' => $productData['outline'] ?? $productData['outlines'] ?? [],
                    ];
                    // 可选字段映射
                    if (isset($productData['wxtype']) && !empty($productData['wxtype'])) {
                        $wxtype = $productData['wxtype'];
                        if ($wxtype === '全部' || $wxtype === '全部文献') {
                            $apiData['literature_area'] = 'all';
                        } elseif ($wxtype === '中文' || $wxtype === '中文文献') {
                            $apiData['literature_area'] = 'zh';
                        } elseif ($wxtype === '英文' || $wxtype === '英文文献') {
                            $apiData['literature_area'] = 'en';
                        } else {
                            $apiData['literature_area'] = $wxtype;
                        }
                    }
                    if (isset($productData['wxnum'])) {
                        $apiData['literature_count'] = intval($productData['wxnum']);
                    }
                    if (isset($productData['wxlist']) && !empty($productData['wxlist'])) {
                        $apiData['literature_list'] = is_array($productData['wxlist']) ? $productData['wxlist'] : explode("\n", $productData['wxlist']);
                    }
                    // 代理相关字段
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    // 验证必填字段
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return false;
                    }
                    break;
                    
                case 'sx':
                    // 实习报告：使用 /tokenapi/write/sxOrder 创建订单
                    $apiPath = '/tokenapi/write/sxOrder';
                    // 提取 template_id
                    $template_id = 0;
                    if (isset($productData['template_id']) && !empty($productData['template_id'])) {
                        $template_id = intval($productData['template_id']);
                    } elseif (isset($productData['templateId']) && !empty($productData['templateId'])) {
                        $template_id = intval($productData['templateId']);
                    }
                    // 清理和映射字段
                    $apiData = [
                        'title' => trim($productData['title'] ?? ''),
                        'template_id' => $template_id,
                    ];
                    // 可选字段映射
                    if (isset($productData['unit']) && !empty($productData['unit'])) {
                        $apiData['unit'] = trim($productData['unit']);
                    }
                    if (isset($productData['unit_text']) && !empty($productData['unit_text'])) {
                        $apiData['unit_text'] = trim($productData['unit_text']);
                    }
                    // 代理相关字段
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    // 验证必填字段
                    if (empty($apiData['title']) || $apiData['template_id'] <= 0) {
                        return false;
                    }
                    break;
                    
                case 'sxrz':
                    // 实习日志：使用 /tokenapi/write/sxrzOrder 创建订单
                    $apiPath = '/tokenapi/write/sxrzOrder';
                    // 实习日志的数据格式可能与实习报告类似，需要根据实际情况调整
                    $apiData = $productData;
                    // 添加代理相关字段
                    $apiData['agent_id'] = $order->user_id ? AgentIdHelper::build((int)$order->user_id) : '';
                    $apiData['agent_amount'] = floatval($order->total_amount ?? 0);
                    if (empty($apiData)) {
                        return false;
                    }
                    break;
                    
                default:
                    return false;
            }
            
            
            // 获取TokenAPI配置 - 从 docking 配置读取
            $apiUrl = config('docking.api_url');
            $apiToken = config('docking.api_token');
            
            if (empty($apiUrl) || empty($apiToken)) {
                return false;
            }
            
            // 构建完整URL
            $url = rtrim($apiUrl, '/') . $apiPath;
            
            
            // 调用TokenAPI
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            
            if ($error) {
                // 保存错误响应到数据库
                $order->tokenapi_response = json_encode([
                    'error' => 'curl请求失败',
                    'error_message' => $error,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                $order->save();
                return false;
            }
            
            if ($httpCode !== 200) {
                // 保存错误响应到数据库
                $order->tokenapi_response = json_encode([
                    'error' => 'HTTP状态码错误',
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                $order->save();
                return false;
            }
            
            
            $result = json_decode($response, true);
            
            // 无论成功还是失败，都保存响应数据到数据库
            $order->tokenapi_response = json_encode([
                'http_code' => $httpCode,
                'response' => $result,
                'raw_response' => $response
            ]);
            
            if (!$result || !isset($result['code']) || $result['code'] != 1) {
                $errorMsg = $result['msg'] ?? '未知错误';
                $order->save();
                return false;
            }
            
            // TokenAPI创建订单成功，保存返回的 order_sn（如果有）
            $tokenApiOrderSn = $result['data']['order_sn'] ?? null;
            if ($tokenApiOrderSn) {
                // 可以将TokenAPI的订单号保存到响应数据中，方便后续查询
                $responseData = json_decode($order->tokenapi_response, true);
                if (is_array($responseData)) {
                    $responseData['tokenapi_order_sn'] = $tokenApiOrderSn;
                    $order->tokenapi_response = json_encode($responseData);
                }
            }
            
            // 更新订单的TokenAPI提交状态
            $order->tokenapi_submitted = 1;
            $order->save();
            
            if ($tokenApiOrderSn) {
            }
            
            // 调用TokenAPI支付确认接口
            if ($tokenApiOrderSn) {
                $paymentConfirmed = self::confirmTokenAPIPayment($productCode, $tokenApiOrderSn, $order);
                if ($paymentConfirmed) {
                } else {
                    // 检查订单状态是否已更新为 6（支付异常），如果是余额不足，confirmTokenAPIPayment 已经更新了状态
                    $order->refresh();
                    if ($order->status == 6) {
                    }
                }
            } else {
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 确认TokenAPI支付
     * @param string $productCode 商品代码
     * @param string $tokenApiOrderSn TokenAPI订单号
     * @param object $order 本地订单对象
     * @return bool
     */
    private static function confirmTokenAPIPayment($productCode, $tokenApiOrderSn, $order)
    {
        try {
            if (empty($tokenApiOrderSn)) {
                return false;
            }
            
            
            // 获取TokenAPI配置
            $apiUrl = config('docking.api_url');
            $apiToken = config('docking.api_token');
            
            if (empty($apiUrl) || empty($apiToken)) {
                return false;
            }
            
            // 根据商品类型确定支付确认接口
            $apiPath = '';
            $apiData = [];
            
            switch($productCode) {
                case 'kaiti':
                case 'rws':
                case 'sx':
                case 'sxrz':
                    // 写作订单：使用 /tokenapi/pay/balanceWriting
                    $apiPath = '/tokenapi/pay/balanceWriting';
                    $apiData = ['order_sn' => $tokenApiOrderSn];
                    break;
                    
                case 'gjlw':
                    // 高级论文：使用 /tokenapi/pay/balancePaper
                    $apiPath = '/tokenapi/pay/balancePaper';
                    $apiData = ['order_sn' => $tokenApiOrderSn];
                    break;
                    
                case 'ppt':
                    // PPT订单：使用 /tokenapi/pay/balancePpt
                    $apiPath = '/tokenapi/pay/balancePpt';
                    $apiData = ['order_sn' => $tokenApiOrderSn];
                    break;

                case 'tools_aigcreduceweight':
                case 'file_aigcreduceweight':
                    // 文档降重（TokenAPI: POST /tokenapi/check/create_jcorder）
                    // 余额确认：与 check 模块对接（若对接方路由不同，请按实际 TokenAPI 文档调整）
                    $apiPath = '/tokenapi/pay/balanceCheck';
                    $apiData = ['order_sn' => $tokenApiOrderSn];
                    break;
                    
                default:
                    return false;
            }
            
            // 构建完整URL
            $url = rtrim($apiUrl, '/') . $apiPath;
            
            
            // 调用TokenAPI支付确认接口
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            
            // 预先组装一份用于落库的响应信息
            $decoded = null;
            if ($response !== false) {
                $decoded = json_decode($response, true);
            }
            $summary = [
                'stage'      => 'confirm_payment',
                'api_path'   => $apiPath,
                'request'    => $apiData,
                'http_code'  => $httpCode,
                'raw'        => $response,
                'parsed'     => $decoded,
                'curl_error' => $error,
            ];
            // 写入到 tokenapi_response，方便排查
            try {
                $order->tokenapi_response = json_encode($summary, JSON_UNESCAPED_UNICODE);
                $order->save();
            } catch (Exception $e) {
            }
            
            if ($error) {
                return false;
            }
            
            if ($httpCode !== 200) {
                return false;
            }
            
            $result = $decoded;
            
            if (!$result || !isset($result['code']) || $result['code'] != 1) {
                $errorMsg = $result['msg'] ?? '未知错误';
                
                // 检测是否为余额不足
                // 先检查 error_code/code，再兜底通过 msg 关键词判断
                $errorCode = $result['error_code'] ?? null;
                $code = $result['code'] ?? null;
                $msg = $errorMsg;
                
                // 如果 error_code 存在且为余额不足的错误码，或者 code 为特定值表示余额不足
                $isBalanceInsufficient = false;
                if ($errorCode !== null) {
                    // 通过 error_code 判断（需要根据实际 TokenAPI 的错误码定义）
                    // 常见的余额不足错误码可能是 1001, 1002, -1001 等，需要根据实际 API 文档调整
                    $isBalanceInsufficient = ($errorCode == 1001 || $errorCode == 1002 || $errorCode == -1001);
                } elseif ($code !== null && $code != 1) {
                    // 如果 code 为特定值表示余额不足（需要根据实际 TokenAPI 的定义）
                    // 这里假设 code 为 -1 或特定值表示余额不足，需要根据实际 API 文档调整
                    $isBalanceInsufficient = ($code == -1 || $code == 1001);
                }

                // 兜底：部分接口只返回 code=0 + msg=余额不足
                if (!$isBalanceInsufficient && $msg) {
                    $msgLower = strtolower($msg);
                    $isBalanceInsufficient = (strpos($msgLower, '余额不足') !== false ||
                                            (strpos($msgLower, '余额') !== false && strpos($msgLower, '不足') !== false));
                }
                
                if ($isBalanceInsufficient) {
                    
                    // 更新订单状态为 6（支付异常）
                    try {
                        $order->status = 6; // 支付异常
                        $order->save();
                    } catch (Exception $e) {
                    }
                }
                
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
