<?php

namespace app\user\service;

use app\model\PaymentMethod;
use Exception;

/**
 * 易支付服务类
 */
class EPayService
{
    /**
     * @var array 支付配置
     */
    protected $config;
    
    /**
     * @var string 支付回调URL
     */
    protected $notifyUrl;
    
    /**
     * @var string 支付返回URL
     */
    protected $returnUrl;
    
    /**
     * 构造函数
     * @throws Exception
     */
    public function __construct()
    {
        $this->initConfig();
    }
    
    /**
     * 初始化易支付配置
     * @throws Exception
     */
    protected function initConfig()
    {
        // 从数据库获取易支付配置
        $method = PaymentMethod::getByCode('epay');
        
        if (!$method || empty($method['config'])) {
            throw new Exception('易支付配置不存在');
        }
        
        $config = $method['config'];
        
        // 验证必要配置
        $requiredFields = ['pid', 'key', 'api_url'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new Exception("易支付配置不完整，缺少字段: {$field}");
            }
        }
        
        $this->config = $config;
        
        // 回调URL - 统一使用带/pay/前缀的路径，与支付宝保持一致
        $domain = request()->domain();
        $this->notifyUrl = $domain . '/pay/epay/notify';
        $this->returnUrl = $domain . '/pay/epay/return';
    }
    
    /**
     * 创建支付
     * @param array $orderInfo 订单信息
     * @param string $payType 支付类型：alipay-支付宝，wxpay-微信支付
     * @param string $paymentType 支付方式：pc-PC支付，h5-H5支付
     * @return array
     * @throws Exception
     */
    public function createPayment($orderInfo, $payType = 'alipay', $paymentType = 'pc')
    {
        try {
            // 验证支付类型是否启用
            if (!$this->isPayTypeEnabled($payType)) {
                throw new Exception('该支付类型未启用');
            }
            
            // H5支付使用页面跳转接口（submit.php），PC支付使用API接口（mapi.php）
            if ($paymentType === 'h5') {
                return $this->createH5Payment($orderInfo, $payType);
            } else {
                return $this->createPCPayment($orderInfo, $payType);
            }
            
        } catch (Exception $e) {
            throw new Exception('易支付创建失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建PC支付（使用API接口）
     * @param array $orderInfo 订单信息
     * @param string $payType 支付类型
     * @return array
     * @throws Exception
     */
    protected function createPCPayment($orderInfo, $payType)
    {
        // 构建支付参数
        $params = [
            'pid' => $this->config['pid'],
            'type' => $payType,
            'out_trade_no' => $orderInfo['order_no'],
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl . '?order_no=' . $orderInfo['order_no'],
            'name' => $this->getPaymentDescription($orderInfo),
            'money' => number_format($orderInfo['total_amount'], 2, '.', ''),
            'param' => 'order',
            'sign_type' => 'MD5',
            'clientip' => request()->ip(),
            'device' => 'pc',
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        
        // 调用易支付API接口（mapi.php）
        $result = $this->callApi($params);
        
        if ($result['code'] == 1) {
            $qrCode = $result['qrcode'] ?? '';
            
            if (empty($qrCode)) {
                throw new Exception('易支付PC支付未返回二维码链接');
            }
            
            
            // 生成二维码图片URL
            $qrCodeImageUrl = $this->generateQRCodeImage($qrCode);
            
            return [
                'pay_url' => '',
                'qr_code' => $qrCode,
                'qr_code_image' => $qrCodeImageUrl,
                'urlscheme' => '',
                'trade_no' => $result['trade_no'] ?? '',
                'expire_time' => date('Y-m-d H:i:s', time() + 900),
            ];
        } else {
            throw new Exception('易支付创建失败: ' . ($result['msg'] ?? '未知错误'));
        }
    }
    
    /**
     * 创建H5支付（优先使用API接口，如果返回收银台则使用submit.php）
     * @param array $orderInfo 订单信息
     * @param string $payType 支付类型
     * @return array
     * @throws Exception
     */
    protected function createH5Payment($orderInfo, $payType)
    {
        // 先尝试使用API接口，设置device='jump'仅返回支付跳转url
        $params = [
            'pid' => $this->config['pid'],
            'type' => $payType,
            'out_trade_no' => $orderInfo['order_no'],
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl . '?order_no=' . $orderInfo['order_no'],
            'name' => $this->getPaymentDescription($orderInfo),
            'money' => number_format($orderInfo['total_amount'], 2, '.', ''),
            'param' => 'order',
            'sign_type' => 'MD5',
            'clientip' => request()->ip(),
            'device' => 'jump', // 使用jump仅返回支付跳转url
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        
        // 调用易支付API接口（mapi.php）
        $result = $this->callApi($params);
        
        if ($result['code'] == 1) {
            $payUrl = $result['payurl'] ?? '';
            $urlScheme = $result['urlscheme'] ?? '';
            
            // 优先使用payurl
            $finalPayUrl = $payUrl ?: $urlScheme;
            
            // 检查返回的URL是否是易支付的收银台页面
            // 如果是易支付的域名，说明返回的是收银台，需要使用submit.php
            $apiUrlHost = parse_url($this->config['api_url'], PHP_URL_HOST);
            $payUrlHost = parse_url($finalPayUrl, PHP_URL_HOST);
            
            if (!empty($finalPayUrl) && $payUrlHost === $apiUrlHost) {
                // 返回的是易支付的收银台，改用submit.php直接跳转
                $submitUrl = rtrim($this->config['api_url'], '/') . '/submit.php';
                $finalPayUrl = $submitUrl . '?' . http_build_query($params);
            }
            
            if (empty($finalPayUrl)) {
                // 如果API接口没有返回payurl，使用submit.php作为备用
                $submitUrl = rtrim($this->config['api_url'], '/') . '/submit.php';
                $finalPayUrl = $submitUrl . '?' . http_build_query($params);
            }
            
            
            return [
                'pay_url' => $finalPayUrl,
                'qr_code' => '',
                'qr_code_image' => '',
                'urlscheme' => $urlScheme,
                'trade_no' => $result['trade_no'] ?? '',
                'expire_time' => date('Y-m-d H:i:s', time() + 900),
            ];
        } else {
            // API接口失败，使用submit.php作为备用
            $submitUrl = rtrim($this->config['api_url'], '/') . '/submit.php';
            $payUrl = $submitUrl . '?' . http_build_query($params);
            
            return [
                'pay_url' => $payUrl,
                'qr_code' => '',
                'qr_code_image' => '',
                'urlscheme' => '',
                'trade_no' => '',
                'expire_time' => date('Y-m-d H:i:s', time() + 900),
            ];
        }
    }
    
    /**
     * 获取设备类型
     * @param string $paymentType 支付方式：pc 或 h5
     * @return string
     */
    protected function getDeviceType($paymentType = 'pc')
    {
        if ($paymentType === 'h5') {
            // H5支付，根据文档使用'jump'可以仅返回支付跳转url
            // 或者根据User-Agent判断设备类型
            $userAgent = request()->header('user-agent', '');
            $userAgent = strtolower($userAgent);
            
            if (strpos($userAgent, 'micromessenger') !== false) {
                // 微信内浏览器
                return 'wechat';
            } elseif (strpos($userAgent, 'alipay') !== false) {
                // 支付宝客户端
                return 'alipay';
            } elseif (strpos($userAgent, 'qq/') !== false || strpos($userAgent, 'mqqbrowser') !== false) {
                // 手机QQ内浏览器
                return 'qq';
            } elseif (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
                // 手机浏览器
                return 'mobile';
            } else {
                // 默认使用jump，仅返回支付跳转url
                return 'jump';
            }
        } else {
            // PC支付
            return 'pc';
        }
    }
    
    /**
     * 生成二维码图片URL
     * @param string $qrCodeData 二维码数据（支付宝支付链接）
     * @return string 二维码图片URL
     */
    protected function generateQRCodeImage($qrCodeData)
    {
        try {
            // 使用第三方二维码生成服务
            // 这里使用 goqr.me 的API，你也可以使用其他服务如 qrserver.com
            $encodedUrl = urlencode($qrCodeData);
            return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encodedUrl}";
            
        } catch (Exception $e) {
            // 如果生成失败，返回原始链接
            return $qrCodeData;
        }
    }
    
    /**
     * 检查支付类型是否启用
     * @param string $payType 支付类型
     * @return bool
     */
    public function isPayTypeEnabled($payType)
    {
        // 从数据库配置中读取启用的支付类型
        $enableAlipay = $this->config['enable_alipay'] ?? 0;
        $enableWechat = $this->config['enable_wechat'] ?? 0;
        
        if ($payType === 'alipay') {
            return $enableAlipay == 1;
        } elseif ($payType === 'wxpay') {
            return $enableWechat == 1;
        }
        
        return false;
    }
    
    /**
     * 生成MD5签名
     * @param array $params
     * @return string
     */
    protected function generateSign(array $params): string
    {
        // 移除sign、sign_type和空值
        unset($params['sign'], $params['sign_type']);
        $params = array_filter($params, function($value) {
            return $value !== '' && $value !== null;
        });
        
        // 按照参数名ASCII码从小到大排序
        ksort($params);
        
        // 手动拼接成URL键值对格式（不进行URL编码）
        $signArray = [];
        foreach ($params as $key => $value) {
            $signArray[] = $key . '=' . $value;
        }
        $signString = implode('&', $signArray);
        
        // 拼接密钥并MD5加密
        $signString .= $this->config['key'];
        return strtolower(md5($signString));
    }
    
    /**
     * 调用易支付API
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function callApi(array $params): array
    {
        $apiUrl = rtrim($this->config['api_url'], '/') . '/mapi.php';
        
        // 使用curl发送POST请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        
        if ($error) {
            throw new Exception('易支付请求失败: ' . $error);
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('易支付返回数据格式错误: ' . $response);
        }
        
        return $result;
    }
    
    /**
     * 查询支付状态
     * @param string $orderNo 订单号
     * @return array
     * @throws Exception
     */
    public function queryPayment($orderNo)
    {
        try {
            $apiUrl = rtrim($this->config['api_url'], '/') . '/api.php';
            $url = $apiUrl . '?act=order&pid=' . $this->config['pid'] . '&key=' . $this->config['key'] . '&out_trade_no=' . urlencode($orderNo);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('易支付查询订单失败: ' . $error);
            }
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('易支付返回数据格式错误');
            }
            
            
            return [
                'trade_status' => $result['status'] ?? '',
                'trade_no' => $result['trade_no'] ?? '',
                'money' => $result['money'] ?? '',
            ];
            
        } catch (Exception $e) {
            throw new Exception('易支付查询失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 处理支付回调
     * @param array $params 回调参数
     * @return bool
     */
    public function handleNotify($params)
    {
        try {
            
            // 验证签名
            $sign = $params['sign'] ?? '';
            $signType = $params['sign_type'] ?? 'MD5';
            
            // 保存原始参数用于签名验证
            $originalParams = $params;
            unset($params['sign'], $params['sign_type']);
            $params = array_filter($params, function($value) {
                return $value !== '' && $value !== null;
            });
            ksort($params);
            
            
            // 手动拼接签名字符串（不进行URL编码）
            $signArray = [];
            foreach ($params as $key => $value) {
                $signArray[] = $key . '=' . $value;
            }
            $signString = implode('&', $signArray);
            $signString .= $this->config['key'];
            
            
            $calculatedSign = strtolower(md5($signString));
            
            if ($sign !== $calculatedSign) {
                return false;
            }
            
            
            // 检查支付状态
            $tradeStatus = $originalParams['trade_status'] ?? '';
            
            if ($tradeStatus !== 'TRADE_SUCCESS') {
                return true; // 返回true表示已处理，但不是成功状态
            }
            
            $orderNo = $originalParams['out_trade_no'] ?? '';
            $transactionId = $originalParams['trade_no'] ?? '';
            $money = $originalParams['money'] ?? '';
            
            
            if (empty($orderNo)) {
                return false;
            }
            
            // 更新订单支付状态
            $result = PaymentService::updateOrderPayment($orderNo, 'epay', $transactionId, $originalParams);
            
            if ($result) {
            } else {
            }
            
            return $result;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取支付描述
     * @param array $orderInfo
     * @return string
     */
    protected function getPaymentDescription($orderInfo)
    {
        $productName = $orderInfo['product_name'] ?? '商品';
        $desc = mb_substr($productName, 0, 32); // 易支付描述最多32个字符
        return $desc ?: '商品购买';
    }
    
    /**
     * 获取启用的支付类型
     * @return array
     */
    public function getEnabledTypes()
    {
        $enabledTypes = [];
        
        // 从数据库配置中读取启用的支付类型
        $enableAlipay = $this->config['enable_alipay'] ?? 0;
        $enableWechat = $this->config['enable_wechat'] ?? 0;
        
        if ($enableAlipay == 1) {
            $enabledTypes[] = 'alipay';
        }
        
        if ($enableWechat == 1) {
            $enabledTypes[] = 'wxpay';
        }
        
        return $enabledTypes;
    }
    
    /**
     * 测试支付
     * @param float $amount 测试金额
     * @param string $payType 支付类型
     * @return array
     * @throws Exception
     */
    public function testPay($amount = 0.1, $payType = 'alipay')
    {
        try {
            // 生成测试订单号
            $testOrderNo = 'TEST' . date('YmdHis') . rand(1000, 9999);
            
            // 构建测试支付参数
            $params = [
                'pid' => $this->config['pid'],
                'type' => $payType,
                'out_trade_no' => $testOrderNo,
                'notify_url' => $this->notifyUrl,
                'return_url' => $this->returnUrl,
                'name' => '支付测试',
                'money' => number_format($amount, 2, '.', ''),
                'param' => 'test',
                'sign_type' => 'MD5',
                'clientip' => request()->ip(),
                'device' => 'pc',
            ];
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 调用易支付API接口
            $result = $this->callApi($params);
            
            // 在返回结果中添加订单号，方便前端查询状态
            if (is_array($result) && $result['code'] == 1) {
                $result['out_trade_no'] = $testOrderNo;
            }
            
            return $result;
            
        } catch (Exception $e) {
            throw new Exception('易支付测试失败: ' . $e->getMessage());
        }
    }
}
