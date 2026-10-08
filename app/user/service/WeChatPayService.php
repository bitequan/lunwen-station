<?php

namespace app\user\service;

use app\model\PaymentMethod;
use EasyWeChat\Pay\Application;
use Exception;

/**
 * 微信支付服务类
 */
class WeChatPayService
{
    /**
     * @var Application EasyWeChat支付应用实例
     */
    protected $app;
    
    /**
     * @var array 支付配置
     */
    protected $config;
    
    /**
     * @var string 支付回调URL
     */
    protected $notifyUrl;
    
    /**
     * 构造函数
     * @throws Exception
     */
    public function __construct()
    {
        $this->initConfig();
        $this->initApp();
    }
    
    /**
     * 初始化微信支付配置
     * @throws Exception
     */
    protected function initConfig()
    {
        // 从数据库获取微信支付配置
        $method = PaymentMethod::getByCode('wechat');
        
        if (!$method || empty($method['config'])) {
            throw new Exception('微信支付配置不存在');
        }
        
        $config = $method['config'];
        
        // 验证必要配置
        $requiredFields = ['mchid', 'appid', 'paySignKey', 'apiclient_cert', 'apiclient_key'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new Exception("微信支付配置不完整，缺少字段: {$field}");
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
        
        // 构建EasyWeChat配置
        $this->config = [
            'mch_id' => $config['mchid'],
            'private_key' => $keyPath,
            'certificate' => $certPath,
            'secret_key' => $config['paySignKey'],
            'http' => [
                'throw' => true,
                'timeout' => 5.0,
            ]
        ];
        
        // 回调URL - 统一使用带/pay/前缀的路径，与支付宝保持一致
        $domain = request()->domain();
        $this->notifyUrl = $domain . '/pay/wechat/notify';
    }
    
    /**
     * 初始化EasyWeChat应用
     */
    protected function initApp()
    {
        $this->app = new Application($this->config);
    }
    
    /**
     * 创建Native支付（二维码支付）
     * @param array $orderInfo 订单信息
     * @return array
     * @throws Exception
     */
    public function createNativePayment($orderInfo)
    {
        try {
            // 构建支付参数
            $params = [
                'appid' => $this->getAppId(),
                'mchid' => $this->config['mch_id'],
                'description' => $this->getPaymentDescription($orderInfo),
                'out_trade_no' => $orderInfo['order_no'],
                'notify_url' => $this->notifyUrl,
                'amount' => [
                    'total' => intval($orderInfo['total_amount'] * 100), // 转换为分
                ],
                'attach' => 'order',
            ];
            
            
            // 调用微信支付API
            $response = $this->app->getClient()->postJson('v3/pay/transactions/native', $params);
            $result = $response->toArray(false);
            
            
            // 检查错误
            if (!empty($result['code']) || !empty($result['message'])) {
                throw new Exception('微信支付错误: ' . ($result['message'] ?? '未知错误'));
            }
            
            if (empty($result['code_url'])) {
                throw new Exception('微信支付返回的二维码URL为空');
            }
            
            return [
                'code_url' => $result['code_url'],
                'prepay_id' => $result['prepay_id'] ?? '',
                'expire_time' => date('Y-m-d H:i:s', time() + 900), // 15分钟后过期
            ];
            
        } catch (Exception $e) {
            throw new Exception('微信支付创建失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建H5支付（手机网站支付）
     * @param array $orderInfo 订单信息
     * @param string $clientIp 客户端IP
     * @return array
     * @throws Exception
     */
    public function createH5Payment($orderInfo, $clientIp = '')
    {
        try {
            // 获取客户端IP
            if (empty($clientIp)) {
                $clientIp = request()->ip();
            }
            
            // 构建支付参数
            $params = [
                'appid' => $this->getAppId(),
                'mchid' => $this->config['mch_id'],
                'description' => $this->getPaymentDescription($orderInfo),
                'out_trade_no' => $orderInfo['order_no'],
                'notify_url' => $this->notifyUrl,
                'amount' => [
                    'total' => intval($orderInfo['total_amount'] * 100), // 转换为分
                ],
                'scene_info' => [
                    'payer_client_ip' => $clientIp,
                    'h5_info' => [
                        'type' => 'Wap',
                    ],
                ],
                'attach' => 'order',
            ];
            
            
            // 调用微信支付H5 API
            $response = $this->app->getClient()->postJson('v3/pay/transactions/h5', $params);
            $result = $response->toArray(false);
            
            
            // 检查错误
            if (!empty($result['code']) || !empty($result['message'])) {
                throw new Exception('微信H5支付错误: ' . ($result['message'] ?? '未知错误'));
            }
            
            if (empty($result['h5_url'])) {
                throw new Exception('微信H5支付返回的支付链接为空');
            }
            
            return [
                'pay_url' => $result['h5_url'],
                'prepay_id' => $result['prepay_id'] ?? '',
                'expire_time' => date('Y-m-d H:i:s', time() + 900), // 15分钟后过期
            ];
            
        } catch (Exception $e) {
            throw new Exception('微信H5支付创建失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取AppID
     * @return string
     * @throws Exception
     */
    protected function getAppId()
    {
        $method = PaymentMethod::getByCode('wechat');
        if (!$method || empty($method['config']['appid'])) {
            throw new Exception('微信支付AppID未配置');
        }
        return $method['config']['appid'];
    }
    
    /**
     * 获取支付描述
     * @param array $orderInfo
     * @return string
     */
    protected function getPaymentDescription($orderInfo)
    {
        $productName = $orderInfo['product_name'] ?? '商品';
        $desc = mb_substr($productName, 0, 32); // 微信支付描述最多32个字符
        return $desc ?: '商品购买';
    }
    
    /**
     * 获取EasyWeChat应用实例
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }
    
    /**
     * 处理支付回调
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleNotify()
    {
        $server = $this->app->getServer();
        
        $server->handlePaid(function ($message) {
            
            if ($message['trade_state'] === 'SUCCESS') {
                $orderNo = $message['out_trade_no'];
                $transactionId = $message['transaction_id'];
                
                // 更新订单支付状态
                $result = PaymentService::updateOrderPayment($orderNo, 'wechat', $transactionId);
                
                if ($result) {
                } else {
                }
            }
            
            return true;
        });
        
        return $server->serve();
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
            $response = $this->app->getClient()->get("v3/pay/transactions/out-trade-no/{$orderNo}?mchid={$this->config['mch_id']}");
            $result = $response->toArray(false);
            
            return [
                'trade_state' => $result['trade_state'] ?? '',
                'transaction_id' => $result['transaction_id'] ?? '',
                'success_time' => $result['success_time'] ?? '',
            ];
            
        } catch (Exception $e) {
            throw new Exception('微信支付查询失败: ' . $e->getMessage());
        }
    }
}
