<?php

namespace app\user\service;

use app\model\PaymentMethod;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use Exception;

/**
 * 支付宝支付服务类
 */
class AliPayService
{
    /**
     * @var mixed 支付宝支付实例
     */
    protected $pay;
    
    /**
     * @var array 支付配置
     */
    protected $config;
    
    /**
     * 构造函数
     * @throws Exception
     */
    public function __construct()
    {
        $this->initConfig();
        $this->initPay();
    }
    
    /**
     * 初始化支付宝支付配置
     * @throws Exception
     */
    protected function initConfig()
    {
        // 从数据库获取支付宝支付配置
        $method = PaymentMethod::getByCode('alipay');
        
        if (!$method || empty($method['config'])) {
            throw new Exception('支付宝支付配置不存在');
        }
        
        $config = $method['config'];
        
        // 验证必要配置
        $requiredFields = ['app_id', 'private_key', 'public_key'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new Exception("支付宝支付配置不完整，缺少字段: {$field}");
            }
        }
        
        $this->config = $config;
    }
    
    /**
     * 初始化支付宝支付实例
     */
    protected function initPay()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        // $options->gatewayHost = 'openapi.alipaydev.com'; // 测试沙箱地址
        $options->signType = 'RSA2';
        $options->appId = $this->config['app_id'];
        $options->merchantPrivateKey = $this->config['private_key'];
        $options->alipayPublicKey = $this->config['public_key'];
        
        // 回调URL - 注意：支付宝回调应该指向pay应用的notify控制器
        $domain = request()->domain();
        $options->notifyUrl = $domain . '/pay/alipay/notify';
        
        // 忽略SSL证书验证（开发环境使用，生产环境建议配置正确的CA证书）
        $options->ignoreSSL = true;
        
        Factory::setOptions($options);
        $this->pay = Factory::payment();
    }
    
    /**
     * 创建当面付（扫码支付）二维码
     * @param array $orderInfo 订单信息
     * @return array
     * @throws Exception
     */
    public function createQrCodePayment($orderInfo)
    {
        try {
            // 构建商品描述
            $subject = $this->getPaymentDescription($orderInfo);
            
            // 调用支付宝当面付接口
            $result = $this->pay->FaceToFace()->optional('passback_params', 'order')->preCreate(
                $subject,
                $orderInfo['order_no'],
                $orderInfo['total_amount']
            );
            
            $body = json_decode($result->httpBody, true);
            
            
            // 检查错误
            if (isset($body['alipay_trade_precreate_response']['code']) && 
                $body['alipay_trade_precreate_response']['code'] != 10000) {
                throw new Exception('支付宝支付错误: ' . 
                    ($body['alipay_trade_precreate_response']['sub_msg'] ?? 
                     $body['alipay_trade_precreate_response']['msg'] ?? '未知错误'));
            }
            
            if (empty($body['alipay_trade_precreate_response']['qr_code'])) {
                throw new Exception('支付宝支付返回的二维码URL为空');
            }
            
            return [
                'qr_code' => $body['alipay_trade_precreate_response']['qr_code'],
                'out_trade_no' => $body['alipay_trade_precreate_response']['out_trade_no'],
                'expire_time' => date('Y-m-d H:i:s', time() + 900), // 15分钟后过期
            ];
            
        } catch (Exception $e) {
            throw new Exception('支付宝支付创建失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建手机网站支付（H5支付）
     * @param array $orderInfo 订单信息
     * @param string $returnUrl 支付完成后的跳转URL
     * @return array
     * @throws Exception
     */
    public function createH5Payment($orderInfo, $returnUrl = '')
    {
        try {
            // 构建商品描述
            $subject = $this->getPaymentDescription($orderInfo);
            
            // 如果没有提供返回URL，使用默认的成功页面
            if (empty($returnUrl)) {
                $domain = request()->domain();
                $returnUrl = $domain . '/user/payment/success?order_no=' . $orderInfo['order_no'];
            }
            
            // 调用支付宝手机网站支付接口
            // Page支付返回的是表单HTML，我们需要创建一个中间页面来处理
            $result = $this->pay->Page()->optional('passback_params', 'order')->pay(
                $subject,
                $orderInfo['order_no'],
                $orderInfo['total_amount'],
                $returnUrl
            );
            
            // 提取HTML内容
            // EasySDK的Page支付返回的是Response对象，需要提取body内容
            $formHtml = '';
            
            // 记录返回值的类型和结构，用于调试
            if (is_object($result)) {
            }
            
            if (is_object($result)) {
                // 如果是对象，尝试多种方式提取HTML
                // 方式1: 尝试httpBody属性（使用property_exists检查，可以访问私有属性）
                if (property_exists($result, 'httpBody')) {
                    try {
                        $reflection = new \ReflectionClass($result);
                        $property = $reflection->getProperty('httpBody');
                        $property->setAccessible(true);
                        $formHtml = $property->getValue($result);
                    } catch (\Exception $e) {
                        // 如果反射失败，尝试直接访问
                        if (isset($result->httpBody)) {
                            $formHtml = $result->httpBody;
                        }
                    }
                }
                // 方式2: 尝试body属性
                if (empty($formHtml) && (property_exists($result, 'body') || isset($result->body))) {
                    try {
                        $reflection = new \ReflectionClass($result);
                        $property = $reflection->getProperty('body');
                        $property->setAccessible(true);
                        $formHtml = $property->getValue($result);
                    } catch (\Exception $e) {
                        if (isset($result->body)) {
                            $formHtml = $result->body;
                        }
                    }
                }
                // 方式3: 尝试getBody()方法
                if (empty($formHtml) && method_exists($result, 'getBody')) {
                    $formHtml = $result->getBody();
                }
                // 方式4: 尝试__toString()方法
                if (empty($formHtml) && method_exists($result, '__toString')) {
                    $formHtml = (string)$result;
                }
                // 方式5: 尝试反射获取所有属性
                if (empty($formHtml)) {
                    try {
                        $reflection = new \ReflectionClass($result);
                        $properties = $reflection->getProperties();
                        foreach ($properties as $property) {
                            $property->setAccessible(true);
                            $value = $property->getValue($result);
                            if (is_string($value) && (strpos($value, '<form') !== false || strpos($value, 'alipay') !== false)) {
                                $formHtml = $value;
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
                // 方式6: 尝试转换为数组后提取
                if (empty($formHtml)) {
                    try {
                        $formArray = json_decode(json_encode($result), true);
                        if (isset($formArray['httpBody'])) {
                            $formHtml = $formArray['httpBody'];
                        } elseif (isset($formArray['body'])) {
                            $formHtml = $formArray['body'];
                        }
                    } catch (\Exception $e) {
                    }
                }
            } elseif (is_string($result)) {
                $formHtml = $result;
            } elseif (is_array($result)) {
                // 如果是数组，尝试提取body或httpBody
                if (isset($result['httpBody'])) {
                    $formHtml = $result['httpBody'];
                } elseif (isset($result['body'])) {
                    $formHtml = $result['body'];
                }
            }
            
            // 验证HTML内容
            if (empty($formHtml) || !is_string($formHtml)) {
                $errorMsg = '无法提取支付宝支付表单HTML，返回值类型: ' . gettype($result);
                if (is_object($result)) {
                    $errorMsg .= ', 类名: ' . get_class($result);
                    // 尝试获取对象的所有公共属性
                    try {
                        $vars = get_object_vars($result);
                        $errorMsg .= ', 公共属性: ' . json_encode(array_keys($vars), JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        // 忽略错误
                    }
                    // 尝试获取所有方法
                    try {
                        $methods = get_class_methods($result);
                        $errorMsg .= ', 方法: ' . json_encode($methods, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        // 忽略错误
                    }
                }
                throw new Exception($errorMsg);
            }
            
            // 验证是否包含表单标签
            if (strpos($formHtml, '<form') === false && strpos($formHtml, 'alipay') === false) {
                throw new Exception('支付宝支付表单HTML格式异常');
            }
            
            // Page支付返回的是表单HTML字符串，不是JSON
            // 我们需要将表单HTML保存到临时文件或返回给前端
            // 这里我们返回一个中间页面URL，该页面会自动提交表单
            $domain = request()->domain();
            $payUrl = $domain . '/user/payment/alipay/h5?order_no=' . $orderInfo['order_no'] . '&return_url=' . urlencode($returnUrl);
            
            // 将表单HTML保存到session，供中间页面使用
            session('alipay_h5_form_' . $orderInfo['order_no'], $formHtml);
            
            
            return [
                'pay_url' => $payUrl,
                'out_trade_no' => $orderInfo['order_no'],
                'expire_time' => date('Y-m-d H:i:s', time() + 900), // 15分钟后过期
            ];
            
        } catch (Exception $e) {
            throw new Exception('支付宝H5支付创建失败: ' . $e->getMessage());
        }
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
            $result = $this->pay->common()->query($orderNo);
            $body = json_decode($result->httpBody, true);
            
            return [
                'trade_status' => $body['alipay_trade_query_response']['trade_status'] ?? '',
                'trade_no' => $body['alipay_trade_query_response']['trade_no'] ?? '',
                'send_pay_date' => $body['alipay_trade_query_response']['send_pay_date'] ?? '',
            ];
            
        } catch (Exception $e) {
            throw new Exception('支付宝支付查询失败: ' . $e->getMessage());
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
            
            // 记录回调日志
            $orderNo = $params['out_trade_no'] ?? '';
            \app\user\service\PaymentLogger::logCallback($orderNo, 'alipay', $params);
            
            // 验证签名
            $verify = $this->pay->common()->verifyNotify($params);
            if (!$verify) {
                \app\user\service\PaymentLogger::logError($orderNo, 'alipay', '支付宝回调签名验证失败', $params);
                return false;
            }
            
            \app\user\service\PaymentLogger::log($orderNo, 'alipay', 'verify', '支付宝回调签名验证成功', $params);
            
            // 检查交易状态
            $tradeStatus = $params['trade_status'] ?? '';
            if (!in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
                \app\user\service\PaymentLogger::log($orderNo, 'alipay', 'status_check', '支付宝回调交易状态不正确: ' . $tradeStatus, $params);
                return true; // 返回true表示已处理，但不是成功状态
            }
            
            $transactionId = $params['trade_no'] ?? '';
            
            if (empty($orderNo)) {
                \app\user\service\PaymentLogger::logError('', 'alipay', '支付宝回调订单号为空', $params);
                return false;
            }
            
            
            // 更新订单支付状态
            $result = PaymentService::updateOrderPayment($orderNo, 'alipay', $transactionId, $params);
            
            if ($result) {
                \app\user\service\PaymentLogger::log($orderNo, 'alipay', 'complete', '支付宝回调处理成功', $params);
            } else {
                \app\user\service\PaymentLogger::logError($orderNo, 'alipay', '支付宝回调处理失败', $params);
            }
            
            return $result;
            
        } catch (Exception $e) {
            \app\user\service\PaymentLogger::logError($orderNo ?? '', 'alipay', '支付宝回调处理异常: ' . $e->getMessage(), [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $desc = mb_substr($productName, 0, 128); // 支付宝描述最多128个字符
        return $desc ?: '商品购买';
    }
}
