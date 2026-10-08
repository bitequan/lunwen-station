<?php

namespace app\pay\controller;

use app\pay\BaseController;
use app\user\service\PaymentService;
use think\facade\Request;

/**
 * 支付回调控制器
 * 注意：这个控制器不需要登录鉴权，所有方法都是公开的
 */
class Notify extends BaseController
{
    /**
     * 微信支付回调
     */
    public function wechat()
    {
        try {
            
            // 获取原始POST数据
            $input = file_get_contents('php://input');
            
            // 记录所有请求头
            $headers = Request::header();
            
            // 同时记录GET和POST参数，检查是否是支付宝回调
            $getParams = Request::get();
            $postParams = Request::post();
            
            // 检查是否是支付宝回调（支付宝回调通常有out_trade_no、trade_no、total_amount等参数）
            $alipayParams = array_merge($getParams, $postParams);
            if (!empty($alipayParams['out_trade_no']) || !empty($alipayParams['trade_no']) || !empty($alipayParams['total_amount'])) {
                // 如果是支付宝回调，调用支付宝回调方法
                return $this->alipay();
            }
            
            // 检查是否是易支付回调（易支付回调通常有pid、trade_no、money等参数）
            if (!empty($alipayParams['pid']) || (!empty($alipayParams['trade_no']) && !empty($alipayParams['money']))) {
                // 如果是易支付回调，调用易支付回调方法
                return $this->epay();
            }
            
            // 解析JSON数据
            $data = json_decode($input, true);
            if (!$data) {
                return response('fail', 200);
            }
            
            
            // 验证必要字段
            if (empty($data['resource']['ciphertext'])) {
                return response('fail', 200);
            }
            
            // 解密数据（这里需要微信支付V3的API密钥）
            $config = $this->getWechatConfig();
            if (!$config) {
                return response('fail', 200);
            }
            
            // 使用EasyWeChat处理回调（借鉴支付借鉴中的逻辑）
            $wechatPay = $this->getWechatPayInstance($config);
            if (!$wechatPay) {
                return response('fail', 200);
            }
            
            $server = $wechatPay->getServer();
            
            // 处理支付成功回调
            $server->handlePaid(function ($message) {
                if ($message['trade_state'] === 'SUCCESS') {
                    $transactionId = $message['transaction_id'] ?? '';
                    $outTradeNo = $message['out_trade_no'] ?? '';
                    $totalFee = $message['amount']['total'] ?? 0;
                    
                    if (empty($outTradeNo)) {
                        return false;
                    }
                    
                    // 处理订单
                    $paymentService = new PaymentService();
                    $result = $paymentService->handleWechatNotify($outTradeNo, $transactionId, $totalFee, $message);
                    
                    if ($result) {
                        return true;
                    } else {
                        return false;
                    }
                }
                
                return true;
            });
            
            // 处理退款回调（可选）
            $server->handleRefunded(function ($message) {
                return true;
            });
            
            // 返回响应
            $response = $server->serve();
            
            return $response;
            
        } catch (\Exception $e) {
            return response('fail', 200);
        }
    }
    
    /**
     * 支付宝支付回调
     */
    public function alipay()
    {
        try {
            // 获取所有参数
            $params = Request::param();
            
            // 验证必要字段
            if (empty($params['out_trade_no'])) {
                return 'fail';
            }
            
            // 获取配置
            $config = $this->getAlipayConfig();
            if (!$config) {
                return 'fail';
            }
            
            // 使用Alipay EasySDK验证签名
            $alipay = $this->getAlipayInstance($config);
            if (!$alipay) {
                return 'fail';
            }
            
            // 验证签名
            try {
                $verifyResult = $alipay->common()->verifyNotify($params);
                if (!$verifyResult) {
                    return 'fail';
                }
            } catch (\Exception $e) {
                return 'fail';
            }
            
            // 获取订单信息
            $outTradeNo = $params['out_trade_no'];
            $tradeNo = $params['trade_no'] ?? '';
            $totalAmount = $params['total_amount'] ?? 0;
            $tradeStatus = $params['trade_status'] ?? '';
            
            // 只处理支付成功的回调
            if ($tradeStatus !== 'TRADE_SUCCESS' && $tradeStatus !== 'TRADE_FINISHED') {
                return 'success';
            }
            
            // 处理订单 - 使用支付宝服务类处理回调
            try {
                $aliPayService = new \app\user\service\AliPayService();
            } catch (\Exception $e) {
                return 'fail';
            }
            
            $result = $aliPayService->handleNotify($params);
            
            if ($result) {
                return 'success';
            } else {
                return 'fail';
            }
            
        } catch (\Exception $e) {
            return 'fail';
        }
    }
    
    /**
     * 易支付回调
     */
    public function epay()
    {
        try {
            // 获取所有参数
            $params = Request::param();
            
            // 验证必要字段
            if (empty($params['out_trade_no'])) {
                return 'fail';
            }
            
            // 获取配置
            $config = $this->getEpayConfig();
            if (!$config) {
                return 'fail';
            }
            
            // 验证签名
            $verifyResult = $this->verifyEpaySign($params, $config);
            if (!$verifyResult) {
                return 'fail';
            }
            
            // 获取订单信息
            $outTradeNo = $params['out_trade_no'];
            $tradeNo = $params['trade_no'] ?? '';
            $money = $params['money'] ?? 0;
            $tradeStatus = $params['trade_status'] ?? '';
            
            // 只处理支付成功的回调
            if ($tradeStatus !== 'TRADE_SUCCESS') {
                return 'success';
            }
            
            // 处理订单 - 使用EPayService的handleNotify方法
            try {
                $epayService = new \app\user\service\EPayService();
                $result = $epayService->handleNotify($params);
                
                if ($result) {
                    return 'success';
                } else {
                    return 'fail';
                }
            } catch (\Exception $e) {
                return 'fail';
            }
            
        } catch (\Exception $e) {
            return 'fail';
        }
    }
    
    /**
     * 易支付返回页面
     */
    public function epayReturn()
    {
        try {
            // 获取所有参数
            $params = Request::param();
            
            // 验证必要字段
            if (empty($params['out_trade_no'])) {
                return redirect('/payment/fail');
            }
            
            // 获取配置
            $config = $this->getEpayConfig();
            if (!$config) {
                return redirect('/payment/fail');
            }
            
            // 验证签名
            $verifyResult = $this->verifyEpaySign($params, $config);
            if (!$verifyResult) {
                return redirect('/payment/fail');
            }
            
            // 获取订单信息
            $outTradeNo = $params['out_trade_no'];
            $tradeStatus = $params['trade_status'] ?? '';
            
            // 检查订单状态
            if ($tradeStatus === 'TRADE_SUCCESS') {
                // 跳转到支付成功页面
                return redirect('/payment/success?order_no=' . $outTradeNo);
            } else {
                // 跳转到支付失败页面
                return redirect('/payment/fail?order_no=' . $outTradeNo);
            }
            
        } catch (\Exception $e) {
            return redirect('/payment/fail');
        }
    }
    
    /**
     * 获取微信支付配置
     */
    private function getWechatConfig()
    {
        try {
            // 使用模型获取微信支付配置
            $paymentMethod = \app\model\PaymentMethod::getByCode('wechat');
            
            if (!$paymentMethod) {
                return null;
            }
            
            if ($paymentMethod['enabled'] != 1) {
                return null;
            }
            
            if (empty($paymentMethod['config'])) {
                return null;
            }
            
            // 配置已经是数组格式（模型自动转换）
            $config = $paymentMethod['config'];
            
            // 验证必要配置字段
            $requiredFields = ['mchid', 'appid', 'paySignKey', 'apiclient_cert', 'apiclient_key'];
            foreach ($requiredFields as $field) {
                if (empty($config[$field])) {
                    return null;
                }
            }
            
            // 创建证书目录
            $certDir = runtime_path('cert');
            if (!is_dir($certDir)) {
                mkdir($certDir, 0755, true);
            }
            
            // 写入证书文件
            $certPath = $certDir . '/apiclient_cert.pem';
            $keyPath = $certDir . '/apiclient_key.pem';
            
            file_put_contents($certPath, $config['apiclient_cert']);
            file_put_contents($keyPath, $config['apiclient_key']);
            
            // 返回EasyWeChat V3需要的配置格式（参考WeChatPayService.php）
            $wechatConfig = [
                'mch_id' => $config['mchid'],
                'private_key' => $keyPath,
                'certificate' => $certPath,
                'secret_key' => $config['paySignKey'],
                'http' => [
                    'throw' => true,
                    'timeout' => 5.0,
                ]
            ];
            
            return $wechatConfig;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取微信支付实例
     */
    private function getWechatPayInstance($config)
    {
        try {
            // 使用EasyWeChat V3创建微信支付实例
            $app = new \EasyWeChat\Pay\Application($config);
            return $app;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取支付宝配置
     */
    private function getAlipayConfig()
    {
        try {
            // 使用模型获取支付宝支付配置
            $paymentMethod = \app\model\PaymentMethod::getByCode('alipay');
            
            if (!$paymentMethod || empty($paymentMethod['config']) || $paymentMethod['enabled'] != 1) {
                return null;
            }
            
            // 配置已经是数组格式（模型自动转换）
            $config = $paymentMethod['config'];
            
            // 返回支付宝支付需要的配置格式
            return [
                'app_id' => $config['app_id'] ?? '',
                'ali_public_key' => $config['public_key'] ?? '',
                'private_key' => $config['private_key'] ?? '',
                'notify_url' => config('payment.alipay.notify_url', ''),
                'return_url' => config('payment.alipay.return_url', ''),
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取支付宝实例
     */
    private function getAlipayInstance($config)
    {
        try {
            // 使用Alipay EasySDK创建支付宝实例
            $options = new \Alipay\EasySDK\Kernel\Config();
            $options->protocol = 'https';
            $options->gatewayHost = 'openapi.alipay.com';
            $options->signType = 'RSA2';
            $options->appId = $config['app_id'] ?? '';
            $options->merchantPrivateKey = $config['private_key'] ?? '';
            $options->alipayPublicKey = $config['ali_public_key'] ?? '';
            $options->notifyUrl = $config['notify_url'] ?? '';
            
            // 忽略SSL证书验证（开发环境使用）
            $options->ignoreSSL = true;
            
            \Alipay\EasySDK\Kernel\Factory::setOptions($options);
            $alipay = \Alipay\EasySDK\Kernel\Factory::payment();
            
            return $alipay;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取易支付配置
     */
    private function getEpayConfig()
    {
        try {
            // 使用模型获取易支付配置
            $paymentMethod = \app\model\PaymentMethod::getByCode('epay');
            
            if (!$paymentMethod || empty($paymentMethod['config']) || $paymentMethod['enabled'] != 1) {
                return null;
            }
            
            // 配置已经是数组格式（模型自动转换）
            $config = $paymentMethod['config'];
            
            // 返回易支付需要的配置格式
            return [
                'pid' => $config['pid'] ?? '',
                'key' => $config['key'] ?? '',
                'api_url' => $config['api_url'] ?? '',
                'notify_url' => config('payment.epay.notify_url', ''),
                'return_url' => config('payment.epay.return_url', ''),
                'enable_alipay' => $config['enable_alipay'] ?? 0,
                'enable_wechat' => $config['enable_wechat'] ?? 0,
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取证书文件路径（将证书字符串保存为临时文件）
     */
    private function getCertPath($certContent)
    {
        if (empty($certContent)) {
            return '';
        }
        
        // 创建临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'wechat_cert_');
        file_put_contents($tempFile, $certContent);
        
        return $tempFile;
    }
    
    /**
     * 获取密钥文件路径（将密钥字符串保存为临时文件）
     */
    private function getKeyPath($keyContent)
    {
        if (empty($keyContent)) {
            return '';
        }
        
        // 创建临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'wechat_key_');
        file_put_contents($tempFile, $keyContent);
        
        return $tempFile;
    }
    
    /**
     * 验证易支付签名
     */
    private function verifyEpaySign($params, $config)
    {
        // 获取签名
        $sign = $params['sign'] ?? '';
        $signType = $params['sign_type'] ?? 'MD5';
        
        if (empty($sign)) {
            return false;
        }
        
        // 移除签名参数
        unset($params['sign']);
        unset($params['sign_type']);
        
        // 参数排序
        ksort($params);
        reset($params);
        
        // 拼接参数
        $signStr = '';
        foreach ($params as $key => $val) {
            if ($val !== '' && $val !== null && $key !== 'sign' && $key !== 'sign_type') {
                $signStr .= $key . '=' . $val . '&';
            }
        }
        
        $signStr = rtrim($signStr, '&');
        $signStr .= $config['key'];
        
        // 计算签名
        $calculatedSign = '';
        if ($signType === 'MD5') {
            $calculatedSign = md5($signStr);
        } elseif ($signType === 'SHA256') {
            $calculatedSign = hash('sha256', $signStr);
        }
        
        // 验证签名
        return strtoupper($calculatedSign) === strtoupper($sign);
    }
}
