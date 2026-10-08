<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\utils\AgentIdHelper;
use think\App as ThinkApp;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;

class Index extends BaseController
{
    /**
     * 管理员后台首页
     */
    public function index()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);

        $siteInfoView = $this->getSiteInfoForView();
        $siteName = trim($siteInfoView['site_name']) !== ''
            ? $siteInfoView['site_name']
            : '对接端管理系统';
        View::assign('site_name', $siteName);
        View::assign('site_logo', trim((string) ($siteInfoView['site_logo'] ?? '')));
        View::assign('site_icon', trim((string) ($siteInfoView['site_icon'] ?? '')));

        // 使用相对路径，ThinkPHP会在app/admin/view/index目录下查找index.html
        return View::fetch('index');
    }

    /**
     * 首页数据统计
     */
    public function home()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 准备统计数据（包含：接口余额）
        $dockingConfig = config('docking') ?? [];
        if (!is_array($dockingConfig) || empty($dockingConfig)) {
            // 兼容：有些环境下 config('docking') 可能取不到，直接 include 配置文件
            $configFile = config_path() . 'docking.php';
            if (file_exists($configFile)) {
                $dockingConfig = include $configFile;
            }
            if (!is_array($dockingConfig)) {
                $dockingConfig = [];
            }
        }

        $apiBalance = $this->fetchWritingBalance(
            (string)($dockingConfig['api_url'] ?? ''),
            (string)($dockingConfig['api_token'] ?? '')
        );

        // 统计类数据（不包含接口余额：接口余额保持现有逻辑不动）
        $userCount = 0;
        $orderCount = 0;
        $todayOrderCount = 0;
        $monthOrderCount = 0;
        $chartData = $this->getHomeChartData(30);

        try {
            // 统计只看“正常用户”与“已支付订单”，更符合首页展示语义
            $userCount = (int)Db::name('users')->where('status', 1)->count();
            $orderCount = (int)Db::name('orders')->where('pay_status', 1)->count();
            $todayOrderCount = (int)Db::name('orders')->where('pay_status', 1)->whereTime('pay_time', 'today')->count();
            $monthOrderCount = (int)Db::name('orders')->where('pay_status', 1)->whereTime('pay_time', 'month')->count();
        } catch (\Exception $e) {
            // 保底：统计失败则保持 0（页面仍可正常打开）
        }

        $versionCfg = config('version');
        if (!is_array($versionCfg)) {
            $versionCfg = [];
        }

        $data = [
            'user_count' => $userCount,
            'order_count' => $orderCount,
            'today_order_count' => $todayOrderCount,
            'month_order_count' => $monthOrderCount,
            // 当前数据源未提供“api_consumption”单独表，这里保持为 0
            'api_consumption' => 0,
            'api_balance' => $apiBalance,
            'chart_data' => $chartData,
            'system_version' => (string)($versionCfg['current_version'] ?? '1.0.0'),
            'php_version' => PHP_VERSION,
            'thinkphp_version' => ThinkApp::VERSION,
            'license_notice' => trim((string)($versionCfg['license_notice'] ?? '')),
        ];
        
        View::assign('data', $data);
        
        // 使用相对路径，ThinkPHP会在app/admin/view/index目录下查找home.html
        return View::fetch('index/home');
    }

    /**
     * 首页图表数据（最近N天：用户增长 + 各类订单趋势）
     * 说明：
     * - 订单趋势按已支付订单（pay_status=1）并使用 pay_time 分日统计
     * - 写作中心订单 code 参考 /user/writing_center: kaiti/rws/sx/sxrz
     * - 论文订单：gjlw
     * - 查重订单：reduce
     * - ppt订单：ppt
     */
    private function getHomeChartData(int $days = 30): array
    {
        $labels = [];
        $users = [];
        $paperOrders = [];
        $writingCenterOrders = [];
        $checkOrders = [];
        $pptOrders = [];

        // 先生成日期维度，确保失败时也能返回固定长度数组
        $dayKeys = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $dayKeys[] = $day;
            $labels[] = date('m/d', strtotime($day));
            $users[$day] = 0;
            $paperOrders[$day] = 0;
            $writingCenterOrders[$day] = 0;
            $checkOrders[$day] = 0;
            $pptOrders[$day] = 0;
        }

        $startDate = $dayKeys[0] ?? date('Y-m-d', strtotime('-29 days'));
        $endDate = end($dayKeys) ?: date('Y-m-d');
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        // 分类 code 映射
        $writingCenterCodes = ['kaiti', 'rws', 'sx', 'sxrz'];
        $paperCodes = ['gjlw'];
        $checkCodes = ['reduce'];
        $pptCodes = ['ppt'];

        try {
            $tUsers = db_table('users');
            $tOrders = db_table('orders');
            // 用户增长：正常用户按 create_time 分日
            $userRows = Db::query(
                "SELECT DATE_FORMAT(create_time, '%Y-%m-%d') AS day, COUNT(*) AS cnt
                 FROM `{$tUsers}`
                 WHERE status = 1 AND create_time BETWEEN ? AND ?
                 GROUP BY day",
                [$startDateTime, $endDateTime]
            );
            foreach ($userRows as $row) {
                $d = (string)($row['day'] ?? '');
                if ($d !== '' && array_key_exists($d, $users)) {
                    $users[$d] = (int)($row['cnt'] ?? 0);
                }
            }

            // 订单趋势（分类型）：各类已支付订单数按 pay_time 分日统计
            $inWriting = implode(',', array_fill(0, count($writingCenterCodes), '?'));
            $inPaper = implode(',', array_fill(0, count($paperCodes), '?'));
            $inCheck = implode(',', array_fill(0, count($checkCodes), '?'));
            $inPpt = implode(',', array_fill(0, count($pptCodes), '?'));

            $paperRows = Db::query(
                "SELECT DATE_FORMAT(pay_time, '%Y-%m-%d') AS day, COUNT(*) AS cnt
                 FROM `{$tOrders}`
                 WHERE pay_status = 1 AND pay_time BETWEEN ? AND ?
                   AND product_code IN ($inPaper)
                 GROUP BY day",
                array_merge([$startDateTime, $endDateTime], $paperCodes)
            );
            foreach ($paperRows as $row) {
                $d = (string)($row['day'] ?? '');
                if ($d !== '' && array_key_exists($d, $paperOrders)) {
                    $paperOrders[$d] = (int)($row['cnt'] ?? 0);
                }
            }

            $writingRows = Db::query(
                "SELECT DATE_FORMAT(pay_time, '%Y-%m-%d') AS day, COUNT(*) AS cnt
                 FROM `{$tOrders}`
                 WHERE pay_status = 1 AND pay_time BETWEEN ? AND ?
                   AND product_code IN ($inWriting)
                 GROUP BY day",
                array_merge([$startDateTime, $endDateTime], $writingCenterCodes)
            );
            foreach ($writingRows as $row) {
                $d = (string)($row['day'] ?? '');
                if ($d !== '' && array_key_exists($d, $writingCenterOrders)) {
                    $writingCenterOrders[$d] = (int)($row['cnt'] ?? 0);
                }
            }

            $checkRows = Db::query(
                "SELECT DATE_FORMAT(pay_time, '%Y-%m-%d') AS day, COUNT(*) AS cnt
                 FROM `{$tOrders}`
                 WHERE pay_status = 1 AND pay_time BETWEEN ? AND ?
                   AND product_code IN ($inCheck)
                 GROUP BY day",
                array_merge([$startDateTime, $endDateTime], $checkCodes)
            );
            foreach ($checkRows as $row) {
                $d = (string)($row['day'] ?? '');
                if ($d !== '' && array_key_exists($d, $checkOrders)) {
                    $checkOrders[$d] = (int)($row['cnt'] ?? 0);
                }
            }

            $pptRows = Db::query(
                "SELECT DATE_FORMAT(pay_time, '%Y-%m-%d') AS day, COUNT(*) AS cnt
                 FROM `{$tOrders}`
                 WHERE pay_status = 1 AND pay_time BETWEEN ? AND ?
                   AND product_code IN ($inPpt)
                 GROUP BY day",
                array_merge([$startDateTime, $endDateTime], $pptCodes)
            );
            foreach ($pptRows as $row) {
                $d = (string)($row['day'] ?? '');
                if ($d !== '' && array_key_exists($d, $pptOrders)) {
                    $pptOrders[$d] = (int)($row['cnt'] ?? 0);
                }
            }
        } catch (\Exception $e) {
        }

        // 按 labels 顺序输出数值数组
        $usersArr = [];
        $paperOrdersArr = [];
        $writingCenterOrdersArr = [];
        $checkOrdersArr = [];
        $pptOrdersArr = [];

        foreach ($dayKeys as $day) {
            $usersArr[] = (int)($users[$day] ?? 0);
            $paperOrdersArr[] = (int)($paperOrders[$day] ?? 0);
            $writingCenterOrdersArr[] = (int)($writingCenterOrders[$day] ?? 0);
            $checkOrdersArr[] = (int)($checkOrders[$day] ?? 0);
            $pptOrdersArr[] = (int)($pptOrders[$day] ?? 0);
        }

        return [
            'labels' => $labels,
            'users' => $usersArr,
            'paper_orders' => $paperOrdersArr,
            'writing_center_orders' => $writingCenterOrdersArr,
            'check_orders' => $checkOrdersArr,
            'ppt_orders' => $pptOrdersArr,
        ];
    }

    /**
     * 爱点写作接口：查询账户余额
     * GET /openapi/jiangchong/wallet → data.user_money（主站预充余额）
     * 60 秒缓存，避免控制台每次刷新都打上游
     */
    private function fetchWritingBalance(string $apiUrl, string $apiToken): float
    {
        if (trim($apiUrl) === '' || trim($apiToken) === '') {
            return 0.0;
        }

        $cached = Cache::get('admin_api_balance');
        if (is_numeric($cached)) {
            return (float)$cached;
        }

        $baseUrl = rtrim($apiUrl, '/');
        $url = $baseUrl . '/openapi/jiangchong/wallet';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return 0.0;
        }
        if ($httpCode !== 200) {
            return 0.0;
        }

        $result = json_decode((string)$response, true);
        $code = $result['code'] ?? 0;
        // 主站接口成功 code 兼容 200 与 1
        if (!is_array($result) || !in_array($code, [1, 200], true)) {
            return 0.0;
        }

        $balance = (float)($result['data']['user_money'] ?? 0);
        if ($balance > 0) {
            Cache::set('admin_api_balance', $balance, 60);
        }
        return $balance;
    }

    /**
     * 网站配置主页面
     */
    public function siteConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        return View::fetch('site_config/basic');
    }

    /**
     * 基本信息配置
     */
    public function siteConfigBasic()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据 - ad_system_config.config_key = site_config（JSON）
        $config = $this->loadSiteConfigArray();
        
        View::assign('config', $config);
        View::assign('siteInfo', (object)$config); // 保持向后兼容
        
        return View::fetch('site_config/basic');
    }

    /**
     * 邮件设置
     */
    public function siteConfigEmail()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据
        $emailConfig = \app\model\EmailConfig::find(1);
        if ($emailConfig) {
            View::assign('config', $emailConfig->toArray());
        } else {
            // 默认配置
            View::assign('config', [
                'smtp_host' => '',
                'smtp_port' => 25,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'none',
                'from_email' => '',
                'from_name' => '',
                'status' => 0
            ]);
        }
        
        return View::fetch('site_config/email');
    }

    /**
     * 安全设置
     */
    public function siteConfigSecurity()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据 - 从系统配置表获取JSON格式配置
        $config = [];
        
        // 尝试从JSON格式配置读取
        $jsonConfig = \app\model\SystemConfig::getValue('security_config');
        
        // 调试：记录读取的JSON配置
        
        if ($jsonConfig) {
            try {
                $config = json_decode($jsonConfig, true);
                if (!is_array($config)) {
                    $config = [];
                }
                
                // 调试：记录解析后的配置
                
                // 确保JSON配置中的password_min_length字段存在
                if (!isset($config['password_min_length'])) {
                    // 尝试从旧的单独键读取
                    $passwordMinLength = \app\model\SystemConfig::getValue('security_password_min_length');
                    if ($passwordMinLength !== null) {
                        $config['password_min_length'] = $passwordMinLength;
                    }
                }
            } catch (\Exception $e) {
                $config = [];
            }
        }
        
        // 如果JSON配置不存在或解析失败，尝试从旧的单独键读取（兼容性）
        if (empty($config)) {
            // 安全配置键名列表（旧格式）
            $securityKeys = [
                'password_min_length' => 'security_password_min_length',
                'password_require_uppercase' => 'security_password_require_uppercase',
                'password_require_lowercase' => 'security_password_require_lowercase',
                'password_require_number' => 'security_password_require_number',
                'password_require_special' => 'security_password_require_special',
                'password_expire_days' => 'security_password_expire_days',
                'login_max_attempts' => 'security_login_max_attempts',
                'login_lockout_time' => 'security_login_lockout_time',
                'enable_captcha' => 'security_enable_captcha',
                'enable_email_verify' => 'security_enable_email_verify',
                'email_verify_expire' => 'security_email_verify_expire',
                'enable_2fa' => 'security_enable_2fa',
                'session_timeout' => 'security_session_timeout'
            ];
            
            // 从系统配置表获取所有安全配置
            foreach ($securityKeys as $key => $configKey) {
                $configValue = \app\model\SystemConfig::getValue($configKey);
                if ($configValue !== null) {
                    $config[$key] = $configValue;
                }
            }
        }
        
        // 设置默认值（如果配置不存在）
        $defaultConfig = [
            'password_min_length' => 6,
            'password_require_uppercase' => 0,
            'password_require_lowercase' => 0,
            'password_require_number' => 1,
            'password_require_special' => 0,
            'password_expire_days' => 0,
            'login_max_attempts' => 5,
            'login_lockout_time' => 30,
            'enable_captcha' => 0,
            'enable_email_verify' => 0,
            'email_verify_expire' => 10,
            'enable_2fa' => 0,
            'session_timeout' => 1440,
            'disable_self_register' => 0,
            'user_single_session' => 1,
        ];
        
        // 合并配置，确保所有键都有值
        foreach ($defaultConfig as $key => $defaultValue) {
            if (!isset($config[$key])) {
                $config[$key] = $defaultValue;
            }
        }
        
        // 确保所有配置值都有正确的类型
        $config = $this->ensureConfigTypes($config);
        
        // 调试：记录最终配置
        
        View::assign('config', $config);
        
        return View::fetch('site_config/security');
    }

    /**
     * 系统设置
     */
    public function siteConfigSystem()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据
        $systemConfigs = \app\model\SystemConfig::getAll();
        $config = [];
        
        // 系统配置键名列表
        $systemKeys = [
            'maintenance_mode',
            'maintenance_message',
            'page_size',
            'debug_mode',
            'api_rate_limit',
            'api_rate_period',
            'file_upload_max_size',
            'allowed_file_types',
            'backup_enabled',
            'backup_frequency',
            'log_retention_days',
            'cache_enabled',
            'cache_ttl'
        ];
        
        foreach ($systemKeys as $key) {
            $fullKey = 'system_' . $key;
            if (isset($systemConfigs[$fullKey])) {
                $config[$key] = $systemConfigs[$fullKey]['value'];
            } else {
                // 默认值
                $defaults = [
                    'maintenance_mode' => 0,
                    'maintenance_message' => '网站正在维护中，请稍后再访问。',
                    'page_size' => 15,
                    'debug_mode' => 0,
                    'api_rate_limit' => 100,
                    'api_rate_period' => 60,
                    'file_upload_max_size' => 10,
                    'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
                    'backup_enabled' => 0,
                    'backup_frequency' => 'daily',
                    'log_retention_days' => 30,
                    'cache_enabled' => 1,
                    'cache_ttl' => 3600
                ];
                $config[$key] = $defaults[$key] ?? '';
            }
        }
        
        View::assign('config', $config);
        
        return View::fetch('site_config/system');
    }

    /**
     * 支付配置
     */
    public function siteConfigPayment()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据
        $systemConfigs = \app\model\SystemConfig::getAll();
        $config = [];
        
        // 支付配置键名列表
        $paymentKeys = [
            'payment_enabled',
            'default_payment_method',
            'currency',
            'currency_symbol',
            'min_recharge_amount',
            'max_recharge_amount',
            'auto_confirm_timeout',
            'refund_enabled',
            'refund_days_limit'
        ];
        
        foreach ($paymentKeys as $key) {
            $fullKey = 'payment_' . $key;
            if (isset($systemConfigs[$fullKey])) {
                $config[$key] = $systemConfigs[$fullKey]['value'];
            } else {
                // 默认值
                $defaults = [
                    'payment_enabled' => 1,
                    'default_payment_method' => 'balance',
                    'currency' => 'CNY',
                    'currency_symbol' => '¥',
                    'min_recharge_amount' => 10,
                    'max_recharge_amount' => 10000,
                    'auto_confirm_timeout' => 30,
                    'refund_enabled' => 0,
                    'refund_days_limit' => 7
                ];
                $config[$key] = $defaults[$key] ?? '';
            }
        }
        
        View::assign('config', $config);
        
        return View::fetch('site_config/payment');
    }

    /**
     * 客服配置
     */
    public function siteConfigCustomerService()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从数据库加载配置数据
        $systemConfigs = \app\model\SystemConfig::getAll();
        $config = [];
        
        // 首先尝试从JSON配置中读取
        if (isset($systemConfigs['customer_service_config'])) {
            $jsonConfig = json_decode($systemConfigs['customer_service_config']['value'], true);
            if ($jsonConfig && is_array($jsonConfig)) {
                $config = $jsonConfig;
            }
        }
        
        // 如果JSON配置不存在或解析失败，尝试从单独的配置项读取（向后兼容）
        if (empty($config)) {
            // 客服配置键名列表
            $customerServiceKeys = [
                'enabled',
                'email',
                'phone',
                'qq',
                'wechat',
                'working_hours',
                'show_float_button'
            ];
            
            foreach ($customerServiceKeys as $key) {
                $fullKey = 'customer_service_' . $key;
                if (isset($systemConfigs[$fullKey])) {
                    $config[$key] = $systemConfigs[$fullKey]['value'];
                }
            }
        }
        
        // 设置默认值
        $defaults = [
            'enabled' => 1,
            'email' => 'support@example.com',
            'phone' => '400-123-4567',
            'qq' => '123456789',
            'wechat' => 'wechat123',
            'working_hours' => '工作时间：周一至周五 9:00-18:00',
            'show_float_button' => 1
        ];
        
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($config[$key]) || $config[$key] === '') {
                $config[$key] = $defaultValue;
            }
        }
        
        View::assign('config', $config);
        
        return View::fetch('site_config/customer_service');
    }

    /**
     * 用户端主题配色配置页
     */
    public function userThemeConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // 从系统配置表加载 JSON 配置
        $config = [];
        $jsonConfig = \app\model\SystemConfig::getValue('user_theme_config');
        if ($jsonConfig) {
            try {
                $decoded = json_decode($jsonConfig, true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            } catch (\Exception $e) {
                $config = [];
            }
        }
        
        // 默认值（与 base.css 保持一致）
        $defaults = [
            'primary_color' => '#6366F1',
            'primary_hover' => '#4F46E5',
            'primary_light' => '#EEF2FF',
            'primary_dark' => '#4338CA',
        ];
        
        $config = array_merge($defaults, $config);
        View::assign('config', $config);
        
        return View::fetch('site_config/user_theme');
    }

    /**
     * 聚合快捷登录对接配置
     */
    public function siteConfigSocialLogin()
    {
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }

        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);

        $defaults = [
            'enabled' => 0,
            'connect_url' => 'https://v.910627.xyz/connect.php',
            'appid' => '',
            'appkey' => '',
            'types' => [
                'qq' => 0,
                'wx' => 0,
                'alipay' => 0,
                'sina' => 0,
            ],
        ];
        $config = $defaults;
        $jsonConfig = \app\model\SystemConfig::getValue(\app\common\service\SocialLoginService::CONFIG_KEY_AGGREGATE);
        if ($jsonConfig) {
            $decoded = json_decode((string) $jsonConfig, true);
            if (is_array($decoded)) {
                unset($decoded['wx_open']);
                $config = array_replace_recursive($defaults, $decoded);
            }
        }

        $wxOpenConfig = \app\common\service\SocialLoginService::loadWxOpenConfig();
        $wxMpConfig = \app\common\service\SocialLoginService::loadWxMpConfig();
        $hasWxMpAesKey = trim((string) ($wxMpConfig['encoding_aes_key'] ?? '')) !== '';

        $siteUrl = '';
        $siteJson = \app\model\SystemConfig::getValue('site_config');
        if ($siteJson) {
            $siteArr = json_decode((string) $siteJson, true);
            if (is_array($siteArr) && !empty($siteArr['site_url'])) {
                $siteUrl = rtrim((string) $siteArr['site_url'], '/');
            }
        }
        $callbackExample = $siteUrl !== ''
            ? $siteUrl . '/social/oauth/callback'
            : 'https://您的域名/social/oauth/callback';

        $publicOAuthBase = rtrim(\app\common\service\SocialLoginService::publicOAuthBaseUrl(), '/');
        $wechatMpServerUrl = $publicOAuthBase . '/social/wechat/mp';
        $wxOpenOauthStartUrl = $publicOAuthBase !== ''
            ? $publicOAuthBase . '/social/oauth/start?type=wx_open'
            : '';

        View::assign('config', $config);
        View::assign('wxOpenConfig', $wxOpenConfig);
        View::assign('wxMpConfig', $wxMpConfig);
        View::assign('has_wx_mp_aes_key', $hasWxMpAesKey);
        View::assign('callback_example', $callbackExample);
        View::assign('wechat_mp_server_url', $wechatMpServerUrl);
        View::assign('wx_open_oauth_start_url', $wxOpenOauthStartUrl);
        View::assign('site_url_configured', $siteUrl !== '');

        return View::fetch('site_config/social_login');
    }

    /**
     * 保存网站配置
     */
    public function saveSiteConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        $type = $data['type'] ?? '';
        
        if (!$type) {
            return json(['code' => 0, 'msg' => '配置类型不能为空']);
        }
        
        try {
            // 根据配置类型处理不同的数据
            switch ($type) {
                case 'basic':
                    // 基本信息配置
                    $this->saveBasicConfig($data);
                    break;
                    
                case 'email':
                    // 邮件配置
                    $this->saveEmailConfig($data);
                    break;
                    
                case 'security':
                    // 安全配置
                    $this->saveSecurityConfig($data);
                    break;
                    
                case 'system':
                    // 系统配置
                    $this->saveSystemConfig($data);
                    break;
                    
                case 'payment':
                    // 支付配置
                    $this->saveSystemPaymentConfig($data);
                    break;
                    
                case 'customer_service':
                    // 客服配置
                    $this->saveCustomerServiceConfig($data);
                    break;

                case 'user_theme':
                    // 用户端主题配色
                    $this->saveUserThemeConfig($data);
                    break;

                case 'social_login':
                    $this->saveSocialLoginConfig($data);
                    break;

                case 'social_wx_open':
                    $this->saveWxOpenLoginConfig($data);
                    break;

                case 'social_wx_mp':
                    $this->saveWxMpLoginConfig($data);
                    break;
                    
                default:
                    return json(['code' => 0, 'msg' => '未知的配置类型']);
            }
            
            return json(['code' => 1, 'msg' => '保存成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存基本信息配置
     */
    private function saveBasicConfig($data)
    {
        // 构建网站配置数据（包含Logo和Icon）
        $siteConfig = [
            'site_name' => $data['site_name'] ?? '',
            'site_url' => $data['site_url'] ?? '',
            'site_keywords' => $data['site_keywords'] ?? '',
            'site_description' => $data['site_description'] ?? '',
            'site_copyright' => $data['site_copyright'] ?? '',
            'site_icp' => $data['site_icp'] ?? '',
            'site_address' => $data['site_address'] ?? '',
            'site_phone' => $data['site_phone'] ?? '',
            'site_email' => $data['site_email'] ?? '',
            'default_language' => $data['default_language'] ?? 'zh-CN',
            'timezone' => $data['timezone'] ?? 'Asia/Shanghai',
            'date_format' => $data['date_format'] ?? 'Y-m-d',
            'time_format' => $data['time_format'] ?? 'H:i:s',
            'site_logo' => $data['site_logo'] ?? '', // Logo URL
            'site_icon' => $data['site_icon'] ?? ''  // Favicon URL
        ];
        
        // 保存到 ad_system_config 表，使用JSON格式
        \app\model\SystemConfig::setValue(
            'site_config',
            json_encode($siteConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '网站基本配置',
            'json'
        );

        // 同步替换前端静态 Logo/Favicon：/pc/logo.png、/pc/favicon.ico 直接使用上传图片，
        // 前端首屏与 loading 无需等待站点配置接口即显示正确的品牌图
        $this->syncFrontendBrandAssets((string) $siteConfig['site_logo'], (string) $siteConfig['site_icon']);
    }

    /**
     * 将管理后台上传的 Logo/Favicon 同步写入前端静态资源位：
     *  - servers/public/pc/logo.png、/pc/favicon.ico —— 当前部署产物，保存后立即生效
     *  - source/public/logo.png、favicon.ico —— 前端源码默认图，下次 npm run generate 重建后仍保留
     * 仅处理本站 /uploads/ 路径；外链 URL 跳过（前端仍走站点配置接口动态指向）
     */
    private function syncFrontendBrandAssets(string $logoUrl, string $iconUrl): void
    {
        try {
            $serverRoot = rtrim(app()->getRootPath(), '/\\');                       // .../servers
            $serverPublic = $serverRoot . '/public';                                // servers/public
            $sourcePublic = rtrim(dirname($serverRoot), '/\\') . '/frontend/public'; // frontend/public

            if ($logoUrl !== '' && ($path = $this->resolveUploadPath($logoUrl, $serverPublic)) !== null) {
                if (is_dir($serverPublic . '/pc')) {
                    @copy($path, $serverPublic . '/pc/logo.png');
                }
                if (is_dir($sourcePublic)) {
                    @copy($path, $sourcePublic . '/logo.png');
                }
            }
            if ($iconUrl !== '' && ($path = $this->resolveUploadPath($iconUrl, $serverPublic)) !== null) {
                if (is_dir($serverPublic . '/pc')) {
                    @copy($path, $serverPublic . '/pc/favicon.ico');
                }
                if (is_dir($sourcePublic)) {
                    @copy($path, $sourcePublic . '/favicon.ico');
                }
            }
        } catch (\Throwable $e) {
            // 静态资源同步失败不影响配置保存
        }
    }

    /**
     * 把 /uploads/... 的站点 URL 解析为 servers/public 内的绝对路径
     * 非本站 uploads 路径、含目录穿越字符或文件不存在时返回 null
     */
    private function resolveUploadPath(string $url, string $serverPublic): ?string
    {
        $pos = strpos($url, '/uploads/');
        if ($pos === false) {
            return null;
        }
        $rel = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, substr($url, $pos + 1));
        if (preg_match('#(\.\.|:)#', $rel)) {
            return null;
        }
        $full = $serverPublic . DIRECTORY_SEPARATOR . $rel;
        return is_file($full) ? $full : null;
    }
    
    /**
     * 保存邮件配置
     */
    private function saveEmailConfig($data)
    {
        $configData = [
            'smtp_host' => $data['smtp_host'] ?? '',
            'smtp_port' => $data['smtp_port'] ?? 25,
            'smtp_username' => $data['smtp_username'] ?? '',
            'smtp_password' => $data['smtp_password'] ?? '',
            'smtp_encryption' => $data['smtp_encryption'] ?? 'none',
            'from_email' => $data['from_email'] ?? '',
            'from_name' => $data['from_name'] ?? '',
            'status' => isset($data['status']) && $data['status'] == '1' ? 1 : 0
        ];
        
        // 保存到 ad_email_config 表
        $emailConfig = \app\model\EmailConfig::find(1);
        if (!$emailConfig) {
            $emailConfig = new \app\model\EmailConfig();
            $emailConfig->id = 1;
        }
        
        foreach ($configData as $key => $value) {
            $emailConfig->$key = $value;
        }
        
        $emailConfig->save();
        
        // 保存到 ad_system_config 表作为备份
        foreach ($configData as $key => $value) {
            \app\model\SystemConfig::setValue(
                'email_' . $key,
                $value,
                '邮件' . $this->getConfigDesc($key),
                is_numeric($value) ? 'number' : 'string'
            );
        }
    }
    
    /**
     * 保存安全配置
     */
    private function saveSecurityConfig($data)
    {
        // 确保所有值都有正确的类型
        $configData = [
            'password_min_length' => (int)($data['password_min_length'] ?? 6),
            'password_require_uppercase' => isset($data['password_require_uppercase']) && $data['password_require_uppercase'] == '1' ? 1 : 0,
            'password_require_lowercase' => isset($data['password_require_lowercase']) && $data['password_require_lowercase'] == '1' ? 1 : 0,
            'password_require_number' => isset($data['password_require_number']) && $data['password_require_number'] == '1' ? 1 : 0,
            'password_require_special' => isset($data['password_require_special']) && $data['password_require_special'] == '1' ? 1 : 0,
            'password_expire_days' => (int)($data['password_expire_days'] ?? 0),
            'login_max_attempts' => (int)($data['login_max_attempts'] ?? 5),
            'login_lockout_time' => (int)($data['login_lockout_time'] ?? 30),
            'enable_captcha' => isset($data['enable_captcha']) && $data['enable_captcha'] == '1' ? 1 : 0,
            'enable_email_verify' => isset($data['enable_email_verify']) && $data['enable_email_verify'] == '1' ? 1 : 0,
            'email_verify_expire' => (int)($data['email_verify_expire'] ?? 10),
            'enable_2fa' => isset($data['enable_2fa']) && $data['enable_2fa'] == '1' ? 1 : 0,
            'session_timeout' => (int)($data['session_timeout'] ?? 1440),
            'disable_self_register' => isset($data['disable_self_register']) && $data['disable_self_register'] == '1' ? 1 : 0,
            'user_single_session' => isset($data['user_single_session']) && $data['user_single_session'] == '1' ? 1 : 0,
        ];
        
        // 调试：记录保存的数据
        
        // 将配置数据转换为JSON格式
        $jsonConfig = json_encode($configData, JSON_UNESCAPED_UNICODE);
        
        // 调试：记录JSON数据
        
        // 保存到 ad_system_config 表，使用JSON格式
        $result = \app\model\SystemConfig::setValue(
            'security_config',
            $jsonConfig,
            '安全配置参数',
            'json'
        );
        
        // 调试：记录保存结果
        
        // 清除opcache缓存，确保配置立即生效
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // 清除ThinkPHP的模型缓存，确保下次读取时从数据库获取最新数据
        \think\facade\Cache::clear();
    }
    
    /**
     * 保存系统配置
     */
    private function saveSystemConfig($data)
    {
        $configData = [
            'maintenance_mode' => isset($data['maintenance_mode']) && $data['maintenance_mode'] == '1' ? 1 : 0,
            'maintenance_message' => $data['maintenance_message'] ?? '网站正在维护中，请稍后再访问。',
            'maintenance_end_time' => $data['maintenance_end_time'] ?? '',
            'page_size' => $data['page_size'] ?? 15,
            'debug_mode' => isset($data['debug_mode']) && $data['debug_mode'] == '1' ? 1 : 0,
            'api_rate_limit' => $data['api_rate_limit'] ?? 100,
            'api_rate_period' => $data['api_rate_period'] ?? 60,
            'file_upload_max_size' => $data['file_upload_max_size'] ?? 10,
            'allowed_file_types' => $data['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx',
            'backup_enabled' => isset($data['backup_enabled']) && $data['backup_enabled'] == '1' ? 1 : 0,
            'backup_frequency' => $data['backup_frequency'] ?? 'daily',
            'log_retention_days' => $data['log_retention_days'] ?? 30,
            'cache_enabled' => isset($data['cache_enabled']) && $data['cache_enabled'] == '1' ? 1 : 0,
            'cache_ttl' => $data['cache_ttl'] ?? 3600
        ];
        
        // 直接保存到 ad_system_config 表
        foreach ($configData as $key => $value) {
            \app\model\SystemConfig::setValue(
                'system_' . $key,
                $value,
                '系统' . $this->getConfigDesc($key),
                is_numeric($value) ? 'number' : 'string'
            );
        }
    }
    
    /**
     * 保存系统支付配置（到ad_system_config表）
     */
    private function saveSystemPaymentConfig($data)
    {
        $configData = [
            'payment_enabled' => isset($data['payment_enabled']) && $data['payment_enabled'] == '1' ? 1 : 0,
            'default_payment_method' => $data['default_payment_method'] ?? 'balance',
            'currency' => $data['currency'] ?? 'CNY',
            'currency_symbol' => $data['currency_symbol'] ?? '¥',
            'min_recharge_amount' => $data['min_recharge_amount'] ?? 10,
            'max_recharge_amount' => $data['max_recharge_amount'] ?? 10000,
            'auto_confirm_timeout' => $data['auto_confirm_timeout'] ?? 30,
            'refund_enabled' => isset($data['refund_enabled']) && $data['refund_enabled'] == '1' ? 1 : 0,
            'refund_days_limit' => $data['refund_days_limit'] ?? 7
        ];
        
        // 直接保存到 ad_system_config 表
        foreach ($configData as $key => $value) {
            \app\model\SystemConfig::setValue(
                'payment_' . $key,
                $value,
                '支付' . $this->getConfigDesc($key),
                is_numeric($value) ? 'number' : 'string'
            );
        }
    }
    
    /**
     * 获取配置描述
     */
    private function getConfigDesc($key)
    {
        $descriptions = [
            // 基本信息
            'site_name' => '名称',
            'site_url' => 'URL',
            'site_keywords' => '关键词',
            'site_description' => '描述',
            'site_copyright' => '版权信息',
            'site_icp' => 'ICP备案号',
            'site_address' => '地址',
            'site_phone' => '电话',
            'site_email' => '邮箱',
            'default_language' => '默认语言',
            'timezone' => '时区',
            'date_format' => '日期格式',
            'time_format' => '时间格式',
            
            // 邮件配置
            'smtp_host' => 'SMTP服务器',
            'smtp_port' => 'SMTP端口',
            'smtp_username' => 'SMTP用户名',
            'smtp_password' => 'SMTP密码',
            'smtp_encryption' => '加密方式',
            'from_email' => '发件人邮箱',
            'from_name' => '发件人名称',
            'status' => '状态',
            
            // 安全配置
            'password_min_length' => '密码最小长度',
            'password_require_uppercase' => '要求大写字母',
            'password_require_lowercase' => '要求小写字母',
            'password_require_number' => '要求数字',
            'password_require_special' => '要求特殊字符',
            'password_expire_days' => '密码过期天数',
            'login_max_attempts' => '最大登录尝试次数',
            'login_lockout_time' => '登录锁定时间',
            'enable_captcha' => '启用验证码',
            'enable_email_verify' => '启用邮箱验证码',
            'email_verify_expire' => '邮箱验证码有效期',
            'enable_2fa' => '启用双因素认证',
            'session_timeout' => '会话超时时间',
            
            // 系统配置
            'maintenance_mode' => '维护模式',
            'maintenance_message' => '维护模式提示信息',
            'page_size' => '列表每页显示数量',
            'debug_mode' => '调试模式',
            'api_rate_limit' => 'API速率限制',
            'api_rate_period' => 'API速率周期',
            'file_upload_max_size' => '文件上传最大大小',
            'allowed_file_types' => '允许的文件类型',
            'backup_enabled' => '启用备份',
            'backup_frequency' => '备份频率',
            'log_retention_days' => '日志保留天数',
            'cache_enabled' => '启用缓存',
            'cache_ttl' => '缓存TTL',
            
            // 支付配置
            'payment_enabled' => '启用支付',
            'default_payment_method' => '默认支付方式',
            'currency' => '货币',
            'currency_symbol' => '货币符号',
            'min_recharge_amount' => '最小充值金额',
            'max_recharge_amount' => '最大充值金额',
            'auto_confirm_timeout' => '自动确认超时',
            'refund_enabled' => '启用退款',
            'refund_days_limit' => '退款天数限制'
        ];
        
        return $descriptions[$key] ?? $key;
    }
    
    /**
     * 确保配置值有正确的类型
     * @param array $config 配置数组
     * @return array 类型正确的配置数组
     */
    private function ensureConfigTypes($config)
    {
        // 定义每个字段的期望类型
        $typeMap = [
            'password_min_length' => 'int',
            'password_require_uppercase' => 'int',
            'password_require_lowercase' => 'int',
            'password_require_number' => 'int',
            'password_require_special' => 'int',
            'password_expire_days' => 'int',
            'login_max_attempts' => 'int',
            'login_lockout_time' => 'int',
            'enable_captcha' => 'int',
            'enable_email_verify' => 'int',
            'email_verify_expire' => 'int',
            'enable_2fa' => 'int',
            'session_timeout' => 'int',
            'disable_self_register' => 'int',
            'user_single_session' => 'int',
        ];
        
        foreach ($typeMap as $key => $type) {
            if (isset($config[$key])) {
                switch ($type) {
                    case 'int':
                        $config[$key] = (int)$config[$key];
                        break;
                    case 'bool':
                        $config[$key] = (bool)$config[$key];
                        break;
                    case 'string':
                        $config[$key] = (string)$config[$key];
                        break;
                }
            }
        }
        
        return $config;
    }

    /**
     * 上传图片
     */
    public function uploadImage()
    {
        try {
            // 检查是否已登录
            $adminId = Session::get('admin_id');
            if (!$adminId) {
                return json(['code' => 0, 'msg' => '未登录']);
            }
            
            // 获取上传的文件
            $file = Request::file('file');
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择要上传的图片']);
            }
            
            // 获取上传类型（logo或icon）
            $type = Request::param('type', 'logo');
            
            // 验证文件类型（favicon 仅允许 ICO/PNG，logo 允许常见位图）
            $fileExt = strtolower($file->getOriginalExtension());

            if ($type === 'icon') {
                $allowedTypes = ['ico', 'png'];
                $extMsg = 'Favicon 仅支持 ICO、PNG 格式';
            } else {
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $extMsg = '不支持的文件类型，请上传 JPG、PNG、GIF、WEBP 格式的图片';
            }

            if (!in_array($fileExt, $allowedTypes)) {
                return json(['code' => 0, 'msg' => $extMsg]);
            }
            
            // 验证文件大小（最大5MB）
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file->getSize() > $maxSize) {
                return json(['code' => 0, 'msg' => '图片大小不能超过5MB']);
            }
            
            // 创建上传目录
            $uploadPath = 'uploads/' . date('Y/m/d');
            $savePath = app()->getRootPath() . 'public/' . $uploadPath;
            
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            
            // 生成唯一文件名
            $fileName = $type . '_' . time() . '_' . uniqid() . '.' . $fileExt;
            
            // 移动文件到上传目录
            $file->move($savePath, $fileName);
            
            // 返回上传成功的URL
            $fileUrl = '/' . $uploadPath . '/' . $fileName;
            
            return json([
                'code' => 1,
                'msg' => '上传成功',
                'data' => [
                    'url' => $fileUrl,
                    'path' => $uploadPath . '/' . $fileName
                ]
            ]);
            
        } catch (\Exception $e) {
            // 记录错误日志
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }
    }

    /**
     * API配置页面
     */
    public function apiConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // api_config.html 在 app/admin/view/index/ 目录下
        return View::fetch('index/api_config');
    }

    /**
     * 获取API配置
     */
    public function getApiConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $key = request()->get('key', '');
        
        // 从 config/docking.php 读取配置
        $configFile = config_path() . 'docking.php';
        $configs = [];

        if (file_exists($configFile)) {
            $configs = include $configFile;
        } else {
            // 如果配置文件不存在，使用默认配置
            $configs = [
                'agent_id_prefix' => AgentIdHelper::DEFAULT_PREFIX,
                'api_url' => 'http://e.com/',
                'api_token' => 'your_api_token_here',
            ];
        }

        // 转换为前端期望的格式（统一来自 docking.php，不走数据库）
        $formattedConfigs = [
            'agent_id_prefix' => [
                'id' => 5,
                'config_key' => 'agent_id_prefix',
                'config_value' => $configs['agent_id_prefix'] ?? AgentIdHelper::DEFAULT_PREFIX,
                'config_desc' => 'agent_str 前缀',
                'config_type' => 'string',
                'sort' => 0,
                'status' => 1,
            ],
            'api_url' => [
                'id' => 1,
                'config_key' => 'api_url',
                'config_value' => $configs['api_url'] ?? 'http://e.com/',
                'config_desc' => '对接域名',
                'config_type' => 'string',
                'sort' => 1,
                'status' => 1
            ],
            'api_token' => [
                'id' => 2,
                'config_key' => 'api_token',
                'config_value' => $configs['api_token'] ?? 'your_api_token_here',
                'config_desc' => '对接Token',
                'config_type' => 'string',
                'sort' => 2,
                'status' => 1
            ],
        ];
        
        if ($key && isset($formattedConfigs[$key])) {
            return json(['code' => 1, 'data' => $formattedConfigs[$key]]);
        }
        
        // 如果没有指定key，返回所有配置
        return json(['code' => 1, 'data' => array_values($formattedConfigs)]);
    }

    /**
     * 保存API配置
     */
    public function saveApiConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        $key = $data['config_key'] ?? '';
        $value = $data['config_value'] ?? '';
        
        if (!$key) {
            return json(['code' => 0, 'msg' => '参数不完整']);
        }

        if ($key === 'agent_id_prefix') {
            $value = AgentIdHelper::normalizePrefixForSave((string)$value);
        } elseif ($value === '' || $value === null) {
            return json(['code' => 0, 'msg' => '参数不完整']);
        }
        
        // 读取现有的配置
        $configFile = config_path() . 'docking.php';
        $configs = [];
        
        if (file_exists($configFile)) {
            $configs = include $configFile;
        }
        if (!is_array($configs)) {
            $configs = [];
        }
        if (!array_key_exists('agent_id_prefix', $configs) || trim((string)($configs['agent_id_prefix'] ?? '')) === '') {
            $configs['agent_id_prefix'] = AgentIdHelper::DEFAULT_PREFIX;
        }
        
        // 更新配置
        $configs[$key] = $value;
        
        // 保存到配置文件
        $configContent = "<?php\n// 对接配置\nreturn [\n";
        
        foreach ($configs as $configKey => $configValue) {
            $escapedValue = str_replace("'", "\\'", (string)$configValue);
            $comment = '';
            
            if ($configKey === 'agent_id_prefix') {
                $comment = '    // 本站用户 agent_str 前缀（完整含下划线）';
            } elseif ($configKey === 'api_url') {
                $comment = '    // 爱点写作 - 对接域名';
            } elseif ($configKey === 'api_token') {
                $comment = '    // 爱点写作 - 对接Token';
            }
            
            $configContent .= "{$comment}\n    '{$configKey}' => '{$escapedValue}',\n";
        }
        
        $configContent .= "];\n";
        
        // 写入文件
        if (file_put_contents($configFile, $configContent) === false) {
            return json(['code' => 0, 'msg' => '保存配置文件失败']);
        }
        
        // 清除配置缓存，确保立即生效
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($configFile, true);
        }
        
        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 商品管理页面
     */
    public function products()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // products/index.html 在 app/admin/view/products/ 目录下
        return View::fetch('products/index');
    }

    /**
     * 获取商品列表
     */
    public function getProducts()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        // 从数据库获取商品数据
        try {
            $products = \app\model\Products::order('sort_order', 'asc')->select();
            
            // 格式化数据
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'icon' => $product->icon,
                    'cost_price' => $product->cost_price,
                    'follow_price' => $product->follow_price,
                    'markup' => $product->markup,
                    'enabled' => $product->enabled,
                    'is_default' => $product->is_default ?? 0,
                    'sort_order' => $product->sort_order,
                    'page_url' => $product->page_url ?? '',
                    'notice_text' => $product->notice_text,
                    'guide_enabled' => $product->guide_enabled ?? 0,
                    'guide_image' => $product->guide_image ?? '',
                    'create_time' => $product->create_time,
                    'update_time' => $product->update_time
                ];
            }
            
            return json(['code' => 1, 'data' => $formattedProducts]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取商品数据失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取单个商品
     */
    public function getProduct()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = request()->get('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => '商品ID不能为空']);
        }
        
        try {
            // 从数据库获取商品数据
            $product = \app\model\Products::find($id);
            
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            
            // 格式化数据
            $formattedProduct = [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'icon' => $product->icon,
                'cost_price' => $product->cost_price,
                'follow_price' => $product->follow_price,
                'markup' => $product->markup,
                'enabled' => $product->enabled,
                'is_default' => $product->is_default ?? 0,
                'sort_order' => $product->sort_order,
                'page_url' => $product->page_url ?? '',
                'notice_text' => $product->notice_text,
                'guide_enabled' => $product->guide_enabled ?? 0,
                'guide_image' => $product->guide_image ?? '',
                'create_time' => $product->create_time,
                'update_time' => $product->update_time
            ];
            
            return json(['code' => 1, 'data' => $formattedProduct]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取商品数据失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取商品价格
     */
    public function fetchProductPrice()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $productCode = request()->get('code', '');
        if (!$productCode) {
            return json(['code' => 0, 'msg' => '商品代码不能为空']);
        }
        
        // ============ 新 openapi 对接计价（有明确静态单价的商品） ============
        // 对接底价取自新 openapi 端口（docking.api_url，Bearer 鉴权），加价仍由本商品 markup 决定
        $openapiPrc = $this->fetchOpenapiBasePrice($productCode);
        if ($openapiPrc !== null) {
            $product = \app\model\Products::where('code', $productCode)->find();
            $base    = (float) $openapiPrc['price'];
            $final   = $base;
            if ($product && (int) $product->follow_price === 1) {
                $final += floatval($product->markup);
            }

            $basePayload = [
                'price'          => $base,
                'final_price'    => $final,
                'cost_price'     => $openapiPrc['cost_price'],
                'price_per_1000' => $openapiPrc['price_per_1000'] !== null ? $openapiPrc['price_per_1000'] : $base,
                'price_per_hour' => $openapiPrc['price_per_hour'] ?? '',
                'currency'       => 'CNY',
            ];
            // 梯度/双版本计价透传：gjlw=阶梯（models），ppt=双版本（versions）
            if (!empty($openapiPrc['price_mode'])) {
                $basePayload['price_mode'] = $openapiPrc['price_mode'];
            }
            if (!empty($openapiPrc['models'])) {
                $basePayload['models'] = $openapiPrc['models'];
            }
            if (!empty($openapiPrc['versions'])) {
                $basePayload['versions'] = $openapiPrc['versions'];
            }
            if ($product) {
                $basePayload['product_code'] = $product->code;
                $basePayload['product_name'] = $product->name;
                $basePayload['follow_price'] = $product->follow_price;
                $basePayload['markup']       = $product->markup;
            } else {
                $basePayload['product_code'] = $productCode;
            }
            return json(['code' => 1, 'data' => $basePayload, 'msg' => '获取成功']);
        }

        // 其他商品仍然走原有写作 TokenAPI 价格接口
        // 获取对接配置
        $dockingConfig = config('docking');
        $apiUrl = $dockingConfig['api_url'] ?? '';
        $apiToken = $dockingConfig['api_token'] ?? '';
        
        if (empty($apiUrl) || empty($apiToken)) {
            return json(['code' => 0, 'msg' => '请先配置对接域名和Token']);
        }
        
        try {
            // 根据商品代码确定API接口和参数 - 统一使用 /tokenapi/billing/price 接口
            $apiPath = '/tokenapi/billing/price';
            $queryParams = [];
            
            // 判断商品类型并构建相应的参数
            if (in_array($productCode, ['kaiti', 'rws', 'sx', 'sxrz', 'gjlw', 'ppt', 'reduce'])) {
                // 普通商品：直接使用商品代码作为 biz_type
                $queryParams['biz_type'] = $productCode;
            } elseif (in_array($productCode, ['tools_aigcreduceweight'], true)) {
                // AIGC 降重 · 统一对接计价：仅此处走 AI率降重套餐单价（biz_type=billing），返回 price_per_1000 / price_per_hour
                $queryParams['biz_type'] = 'billing';
            } elseif (strpos($productCode, 'tools_') === 0) {
                // 工具类商品：使用 tool 作为 biz_type，提取工具类型
                $queryParams['biz_type'] = 'tool';
                $toolType = str_replace('tools_', '', $productCode);
                $queryParams['tool_type'] = $toolType;
            } else {
                // 其他未知商品类型，尝试使用商品代码作为 biz_type
                $queryParams['biz_type'] = $productCode;
            }
            
            // 构建完整URL
            $url = rtrim($apiUrl, '/') . $apiPath;
            
            // 添加查询参数
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            
            // 发送请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return json(['code' => 0, 'msg' => '请求失败：' . $error]);
            }
            
            if ($httpCode !== 200) {
                return json(['code' => 0, 'msg' => 'API返回错误，状态码：' . $httpCode]);
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['code']) || $result['code'] != 1) {
                return json(['code' => 0, 'msg' => 'API返回错误：' . ($result['msg'] ?? '未知错误')]);
            }

            // AIGC 降重：billing 返回双单价，与其它 biz_type 的 amount 结构不同
            if (in_array($productCode, ['tools_aigcreduceweight'], true)) {
                $d = $result['data'] ?? [];
                $pricePer1000 = floatval($d['price_per_1000'] ?? $d['billing_price_per_1000'] ?? 0);
                $pricePerHour  = floatval($d['price_per_hour'] ?? $d['billing_price_per_hour'] ?? 0);
                $price          = $pricePer1000;

                $product = \app\model\Products::where('code', $productCode)->find();
                $finalPrice = $price;
                if ($product && (int) $product->follow_price === 1) {
                    $finalPrice += floatval($product->markup);
                }

                $payload = [
                    'price'          => $price,
                    'final_price'    => $finalPrice,
                    'cost_price'     => $pricePer1000,
                    'price_per_1000' => $pricePer1000,
                    'price_per_hour' => $pricePerHour,
                    'currency'       => 'CNY',
                ];
                if ($product) {
                    $payload['product_code']  = $product->code;
                    $payload['product_name']  = $product->name;
                    $payload['follow_price']  = $product->follow_price;
                    $payload['markup']        = $product->markup;
                } else {
                    $payload['product_code'] = $productCode;
                }

                return json(['code' => 1, 'data' => $payload, 'msg' => '获取成功']);
            }
            
            // 解析价格（根据实际API返回格式调整）
            // API返回格式：{"code":1,"data":{"biz_type":"sx","scene":"order","amount":2}}
            $price = 0.00;
            if (isset($result['data']['amount'])) {
                $price = floatval($result['data']['amount']);
            } elseif (isset($result['data']['price'])) {
                $price = floatval($result['data']['price']);
            } elseif (isset($result['data'][$productCode])) {
                $price = floatval($result['data'][$productCode]);
            } elseif (isset($result['data'][0]['price'])) {
                $price = floatval($result['data'][0]['price']);
            }
            
            // 从数据库获取商品信息，计算最终价格
            $product = \app\model\Products::where('code', $productCode)->find();
            $finalPrice = $price;
            
            if ($product) {
                // 如果开启了跟价，在API价格基础上加价
                if ($product->follow_price == 1) {
                    $finalPrice += $product->markup;
                }
                
                return json([
                    'code' => 1, 
                    'data' => [
                        'price' => $price,
                        'final_price' => $finalPrice,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'cost_price' => $price, // API返回的价格作为成本价
                        'follow_price' => $product->follow_price,
                        'markup' => $product->markup,
                        'currency' => 'CNY'
                    ], 
                    'msg' => '获取成功'
                ]);
            } else {
                return json([
                    'code' => 1, 
                    'data' => [
                        'price' => $price,
                        'final_price' => $finalPrice,
                        'product_code' => $productCode,
                        'currency' => 'CNY'
                    ], 
                    'msg' => '获取成功'
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取价格失败：' . $e->getMessage()]);
        }
    }

    /**
     * 从新 openapi 对接端口（docking.api_url，Bearer 鉴权）获取商品对接底价。
     * 仅支持有明确静态单价的商品；不适用返回 null（上层回退旧 TokenAPI）。
     *
     * @return array|null 形如 ['price'=>float,'cost_price'=>float,'price_per_1000'=>?float,'price_per_hour'=>?float]
     */
    private function fetchOpenapiBasePrice(string $code): ?array
    {
        try {
            $client = new \app\common\service\ApiClientService();
        } catch (\Throwable $e) {
            return null;
        }

        // AIGC 降重：/openapi/jiangchong/wallet → char_price（每千字基础单价）
        if (in_array($code, ['tools_aigcreduceweight'], true)) {
            $r = $client->get('/openapi/jiangchong/wallet');
            if (($r['code'] ?? 0) === 1) {
                $d    = $r['data'] ?? [];
                $p1000 = (float) ($d['char_price'] ?? 0);
                $ph    = isset($d['price_per_hour']) ? (float) $d['price_per_hour'] : null;
                if ($p1000 > 0) {
                    return ['price' => $p1000, 'cost_price' => $p1000, 'price_per_1000' => $p1000, 'price_per_hour' => $ph];
                }
            }
            return null;
        }

        // PPT：双版本计价（标准版/高级版）→ /openapi/ppt/price?is_advanced=0|1
        if ($code === 'ppt') {
            $versions = [];
            $stdCost  = 0.0;
            foreach ([0 => '标准版', 1 => '高级版'] as $adv => $vname) {
                $r = $client->get('/openapi/ppt/price', ['is_advanced' => $adv]);
                if (($r['code'] ?? 0) !== 1) {
                    continue;
                }
                $d    = $r['data'] ?? [];
                $cost = (float) ($d['cost_price'] ?? ($d['base_price'] ?? 0));
                if ($cost <= 0) {
                    continue;
                }
                if ($adv === 0) {
                    $stdCost = $cost;
                }
                $versions[] = [
                    'version'    => $adv ? 'advanced' : 'standard',
                    'name'       => $vname,
                    'model_name' => (string) ($d['model_name'] ?? ''),
                    'base_price' => (string) ($d['base_price'] ?? '0'),
                    'cost_price' => $cost,
                ];
            }
            if ($versions) {
                $head = $stdCost > 0 ? $stdCost : (float) $versions[0]['cost_price'];
                return [
                    'price'          => $head,
                    'cost_price'     => $head,
                    'price_per_1000' => null,
                    'price_per_hour' => null,
                    'price_mode'     => 'versions',
                    'versions'       => $versions,
                ];
            }
            return null;
        }

        // 自动排版：/openapi/autodoc/price → cost_price/base_price
        if ($code === 'autodoc') {
            $r = $client->get('/openapi/autodoc/price');
            if (($r['code'] ?? 0) === 1) {
                $d = $r['data'] ?? [];
                $p = (float) ($d['cost_price'] ?? ($d['base_price'] ?? 0));
                if ($p > 0) {
                    return ['price' => $p, 'cost_price' => $p, 'price_per_1000' => null, 'price_per_hour' => null];
                }
            }
            return null;
        }

        // 文档AI检测：/openapi/ai_check/wallet → char_price（每千字符基础单价）
        if ($code === 'aicheck') {
            $r = $client->get('/openapi/ai_check/wallet');
            if (($r['code'] ?? 0) === 1) {
                $d = $r['data'] ?? [];
                $p = (float) ($d['char_price'] ?? 0);
                if ($p > 0) {
                    return ['price' => $p, 'cost_price' => $p, 'price_per_1000' => $p, 'price_per_hour' => null];
                }
            }
            return null;
        }

        // AI 论文：阶梯计价（字数档 × 模型）→ /openapi/price/costList（type_code=paper）
        if ($code === 'gjlw') {
            $r = $client->get('/openapi/price/costList', []);
            if (($r['code'] ?? 0) === 1 && is_array($r['data']['list'] ?? null)) {
                $models   = [];
                $allCosts = [];
                foreach ($r['data']['list'] as $item) {
                    if (!is_array($item) || ($item['type_code'] ?? '') !== 'paper') {
                        continue;
                    }
                    $tiers = [];
                    foreach ((array) ($item['tiers'] ?? []) as $t) {
                        if (!is_array($t)) {
                            continue;
                        }
                        $cost = (float) ($t['cost_price'] ?? 0);
                        if ($cost > 0) {
                            $allCosts[] = $cost;
                        }
                        $tiers[] = [
                            'max_words'  => (int) ($t['max_words'] ?? 0),
                            'base_price' => (string) ($t['base_price'] ?? '0'),
                            'cost_price' => (string) ($t['cost_price'] ?? '0'),
                        ];
                    }
                    if (!$tiers) {
                        continue;
                    }
                    $models[] = [
                        'code'        => (string) ($item['model_code'] ?? ''),
                        'name'        => (string) ($item['model_name'] ?? ''),
                        'tag'         => (string) ($item['model_tag'] ?? ''),
                        'is_advanced' => (int) ($item['is_advanced'] ?? 0),
                        'tiers'       => $tiers,
                    ];
                }
                if ($models) {
                    $min = $allCosts ? min($allCosts) : 0.0;
                    return [
                        'price'          => $min,
                        'cost_price'     => $min,
                        'price_per_1000' => null,
                        'price_per_hour' => null,
                        'price_mode'     => 'tiered',
                        'models'         => $models,
                    ];
                }
            }
            return null;
        }

        return null;
    }

    /**
     * 更新商品状态
     */
    public function updateProductStatus()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        // 验证必要字段
        if (empty($data['id']) || !isset($data['enabled'])) {
            return json(['code' => 0, 'msg' => '商品ID和状态不能为空']);
        }
        
        try {
            // 更新商品状态
            $product = \app\model\Products::find($data['id']);
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            
            $product->enabled = $data['enabled'] ? 1 : 0;
            $product->save();
            
            return json(['code' => 1, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新商品状态失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存商品
     */
    public function saveProduct()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        // 验证必要字段
        if (empty($data['code']) || empty($data['name'])) {
            return json(['code' => 0, 'msg' => '商品代码和名称不能为空']);
        }
        
        try {
            // 检查商品代码是否已存在（如果是新增）
            if (empty($data['id'])) {
                $exists = \app\model\Products::where('code', $data['code'])->find();
                if ($exists) {
                    return json(['code' => 0, 'msg' => '商品代码已存在']);
                }
            }
            
            // 处理数据
            $productData = [
                'code' => $data['code'],
                'name' => $data['name'],
                'icon' => $data['icon'] ?? '',
                'cost_price' => $data['cost_price'] ?? 0.00,
                'follow_price' => isset($data['follow_price']) ? ($data['follow_price'] ? 1 : 0) : 0,
                'markup' => $data['markup'] ?? 0.00,
                'enabled' => isset($data['enabled']) ? ($data['enabled'] ? 1 : 0) : 1,
                'sort_order' => $data['sort_order'] ?? 0,
                'page_url' => $data['page_url'] ?? '',
                'notice_text' => $data['notice_text'] ?? '',
            ];

            // 仅当前端显式传入 product_type 时才更新，避免“保存设置”误改商品类型
            if (array_key_exists('product_type', $data)) {
                $productData['product_type'] = intval($data['product_type']);
            } elseif (empty($data['id'])) {
                // 新增商品且未传入类型时，根据 code 兼容推断默认类型
                $productData['product_type'] = (strpos($data['code'], 'tools_') === 0) ? 2 : 1;
            }

            // 仅当前端启用了对应输入项时才更新引导图配置，避免覆盖其它商品已有配置
            if (array_key_exists('guide_enabled', $data)) {
                $productData['guide_enabled'] = $data['guide_enabled'] ? 1 : 0;
            }
            if (array_key_exists('guide_image', $data)) {
                $productData['guide_image'] = $data['guide_image'] ?? '';
            }
            
            // 保存数据
            if (!empty($data['id'])) {
                // 更新
                $product = \app\model\Products::find($data['id']);
                if (!$product) {
                    return json(['code' => 0, 'msg' => '商品不存在']);
                }
                $product->save($productData);

                // AIGC 降重已合并为单商品（tools_aigcreduceweight），历史 file_aigcreduceweight 订单行保留兼容读取
                if ($data['code'] === 'file_aigcreduceweight') {
                    // 旧商品已下架：仅保存自身，不镜像
                } elseif ($data['code'] === 'tools_aigcreduceweight') {
                    $legacy = \app\model\Products::where('code', 'file_aigcreduceweight')->find();
                    if ($legacy) {
                        $legacy->cost_price   = $productData['cost_price'];
                        $legacy->follow_price = $productData['follow_price'];
                        $legacy->markup       = $productData['markup'];
                        $legacy->save();
                    }
                }
            } else {
                // 新增
                $product = new \app\model\Products();
                $product->save($productData);
            }
            
            return json(['code' => 1, 'msg' => '保存成功', 'data' => ['id' => $product->id]]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存商品失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新商品首页默认项
     */
    public function updateProductDefault()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        // 验证必要字段
        if (empty($data['id']) || !isset($data['is_default'])) {
            return json(['code' => 0, 'msg' => '商品ID和默认状态不能为空']);
        }
        
        try {
            // 如果设置为默认，先取消其他商品的默认状态
            if ($data['is_default'] == 1) {
                \app\model\Products::where('is_default', 1)->update(['is_default' => 0]);
            }
            
            // 更新当前商品的默认状态
            $product = \app\model\Products::find($data['id']);
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            
            $product->is_default = $data['is_default'] ? 1 : 0;
            $product->save();
            
            return json(['code' => 1, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新商品默认状态失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新商品排序
     */
    public function updateProductSort()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        // 验证必要字段
        if (empty($data['id']) || !isset($data['sort_order'])) {
            return json(['code' => 0, 'msg' => '商品ID和排序值不能为空']);
        }
        
        try {
            // 更新商品排序
            $product = \app\model\Products::find($data['id']);
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            
            $product->sort_order = intval($data['sort_order']);
            $product->save();
            
            return json(['code' => 1, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新商品排序失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 修改当前登录管理员密码
     */
    public function changeMyPassword()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        $oldPassword = trim((string)($data['old_password'] ?? ''));
        $newPassword = trim((string)($data['new_password'] ?? ''));
        $confirmPassword = trim((string)($data['confirm_password'] ?? ''));

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return json(['code' => 0, 'msg' => '请完整填写密码信息']);
        }
        if (mb_strlen($newPassword) < 6) {
            return json(['code' => 0, 'msg' => '新密码长度不能少于6位']);
        }
        if ($newPassword !== $confirmPassword) {
            return json(['code' => 0, 'msg' => '两次输入的新密码不一致']);
        }

        $admin = \app\model\Admins::find((int)$adminId);
        if (!$admin) {
            return json(['code' => 0, 'msg' => '管理员不存在']);
        }
        if (!$admin->verifyPassword($oldPassword)) {
            return json(['code' => 0, 'msg' => '原密码错误']);
        }
        if ($admin->verifyPassword($newPassword)) {
            return json(['code' => 0, 'msg' => '新密码不能与原密码相同']);
        }

        $admin->password = $newPassword;
        $admin->save();

        return json(['code' => 1, 'msg' => '修改成功']);
    }

    /**
     * 站点信息页面
     */
    public function siteInfo()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        View::assign('info', $this->getSiteInfoForView());

        // site_info/index.html 在 app/admin/view/site_info/ 目录下
        return View::fetch('site_info/index');
    }

    /**
     * 保存站点信息
     */
    public function saveSiteInfo()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $raw = Request::getContent();
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            $data = request()->post();
        }
        if (!is_array($data)) {
            $data = [];
        }

        $allowed = [
            'site_name', 'site_logo', 'site_icon', 'site_keywords', 'site_description',
            'site_copyright', 'site_icp', 'site_address', 'site_phone', 'site_email',
        ];
        $payload = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            $payload[$key] = is_string($v) ? trim($v) : (string) $v;
        }

        try {
            $siteConfig = $this->loadSiteConfigArray();
            foreach ($payload as $k => $v) {
                $siteConfig[$k] = $v;
            }
            \app\model\SystemConfig::setValue(
                'site_config',
                json_encode($siteConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                '网站基本配置',
                'json'
            );

            return json(['code' => 1, 'msg' => '保存成功']);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取支付方式
     */
    public function getPaymentMethods()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        try {
            // 从数据库获取支付方式
            $methods = \app\model\PaymentMethod::getAllMethods();
            
            // 格式化返回数据
            $formattedMethods = [];
            foreach ($methods as $method) {
                $formattedMethods[] = [
                    'id' => $method['id'],
                    'code' => $method['code'],
                    'name' => $method['name'],
                    'display_name' => $method['display_name'] ?? $method['name'],
                    'icon' => $method['icon'] ?? '',
                    'icon_text' => $method['icon_text'] ?? '',
                    'sort_order' => $method['sort_order'] ?? 0,
                    'enabled' => $method['enabled'] == 1,
                    'config' => $method['config'] ?? []
                ];
            }
            
            return json(['code' => 1, 'data' => $formattedMethods]);
            
        } catch (\Exception $e) {
            // 如果出错，返回模拟数据
            $methods = [
                ['id' => 1, 'code' => 'alipay', 'name' => '支付宝', 'display_name' => '支付宝支付', 'icon' => 'alipay', 'icon_text' => '支', 'sort_order' => 1, 'enabled' => true, 'config' => []],
                ['id' => 2, 'code' => 'wechat', 'name' => '微信支付', 'display_name' => '微信支付', 'icon' => 'wechat', 'icon_text' => '✓', 'sort_order' => 2, 'enabled' => true, 'config' => []],
                ['id' => 3, 'code' => 'balance', 'name' => '余额支付', 'display_name' => '余额支付', 'icon' => 'balance', 'icon_text' => '¥', 'sort_order' => 0, 'enabled' => true, 'config' => []],
                ['id' => 4, 'code' => 'epay', 'name' => '易支付', 'display_name' => '易支付', 'icon' => 'epay', 'icon_text' => '易', 'sort_order' => 3, 'enabled' => true, 'config' => []]
            ];
            
            return json(['code' => 1, 'data' => $methods]);
        }
    }

    /**
     * 获取单个支付方式
     */
    public function getPaymentMethod()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = request()->get('id', 0);
        $code = request()->get('code', '');
        
        try {
            if ($id) {
                $method = \app\model\PaymentMethod::find($id);
            } elseif ($code) {
                $method = \app\model\PaymentMethod::getByCode($code);
            } else {
                return json(['code' => 0, 'msg' => '参数错误']);
            }
            
            if (!$method) {
                return json(['code' => 0, 'msg' => '支付方式不存在']);
            }
            
            // 格式化返回数据
            $formattedMethod = [
                'id' => $method['id'],
                'code' => $method['code'],
                'name' => $method['name'],
                'display_name' => $method['display_name'] ?? $method['name'],
                'icon' => $method['icon'] ?? '',
                'icon_text' => $method['icon_text'] ?? '',
                'sort_order' => $method['sort_order'] ?? 0,
                'enabled' => $method['enabled'] == 1,
                'config' => $method['config'] ?? []
            ];
            
            return json(['code' => 1, 'data' => $formattedMethod]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新支付状态
     */
    public function updatePaymentStatus()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        try {
            // 验证必填字段
            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => 'ID不能为空']);
            }
            
            if (!isset($data['enabled'])) {
                return json(['code' => 0, 'msg' => '状态不能为空']);
            }
            
            // 查找支付方式
            $method = \app\model\PaymentMethod::find($data['id']);
            
            if (!$method) {
                return json(['code' => 0, 'msg' => '支付方式不存在']);
            }
            
            // 更新状态
            $method->enabled = $data['enabled'] ? 1 : 0;
            
            // 保存到数据库
            if ($method->save()) {
                return json(['code' => 1, 'msg' => '状态更新成功']);
            } else {
                return json(['code' => 0, 'msg' => '状态更新失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存支付配置
     */
    public function savePaymentConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        try {
            // 验证必填字段
            if (empty($data['code'])) {
                return json(['code' => 0, 'msg' => '支付方式代码不能为空']);
            }
            
            // 查找或创建支付方式
            $method = \app\model\PaymentMethod::where('code', $data['code'])->find();
            
            if (!$method) {
                $method = new \app\model\PaymentMethod();
                $method->code = $data['code'];
                $method->name = $data['name'] ?? $data['code'];
            }
            
            // 更新基本信息
            if (isset($data['display_name'])) {
                $method->display_name = $data['display_name'];
            }
            
            if (isset($data['icon'])) {
                $method->icon = $data['icon'];
            }
            
            if (isset($data['icon_text'])) {
                $method->icon_text = $data['icon_text'];
            }
            
            if (isset($data['sort_order'])) {
                $method->sort_order = intval($data['sort_order']);
            }
            
            // 更新配置参数
            if (isset($data['config'])) {
                $config = is_string($data['config']) ? json_decode($data['config'], true) : $data['config'];
                $method->config = $config;
            }
            
            // 保存到数据库
            if ($method->save()) {
                return json(['code' => 1, 'msg' => '保存成功']);
            } else {
                return json(['code' => 0, 'msg' => '保存失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存客服配置
     * @param array $data 配置数据
     */
    private function saveCustomerServiceConfig($data)
    {
        // 构建客服配置数组
        $customerServiceConfig = [
            'enabled' => isset($data['enabled']) && $data['enabled'] ? 1 : 0,
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'qq' => $data['qq'] ?? '',
            'wechat' => $data['wechat'] ?? '',
            'working_hours' => $data['working_hours'] ?? '工作时间：周一至周五 9:00-18:00',
            'show_float_button' => isset($data['show_float_button']) && $data['show_float_button'] ? 1 : 0
        ];
        
        // 将配置保存为JSON格式
        \app\model\SystemConfig::setValue(
            'customer_service_config',
            json_encode($customerServiceConfig, JSON_UNESCAPED_UNICODE),
            '客服配置参数',
            'json'
        );
        
        return true;
    }

    /**
     * 保存用户端主题配色（ad_system_config:user_theme_config）
     */
    private function saveUserThemeConfig($data)
    {
        $defaults = [
            'primary_color' => '#6366F1',
            'primary_hover' => '#4F46E5',
            'primary_light' => '#EEF2FF',
            'primary_dark' => '#4338CA',
        ];
        
        $themeConfig = [
            'primary_color' => $data['primary_color'] ?? ($defaults['primary_color']),
            'primary_hover' => $data['primary_hover'] ?? ($defaults['primary_hover']),
            'primary_light' => $data['primary_light'] ?? ($defaults['primary_light']),
            'primary_dark' => $data['primary_dark'] ?? ($defaults['primary_dark']),
        ];
        
        \app\model\SystemConfig::setValue(
            'user_theme_config',
            json_encode($themeConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '用户端主题配色',
            'json'
        );
        
        // 清除缓存，确保前台立即生效
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        \think\facade\Cache::clear();
        
        return true;
    }

    /**
     * 若旧版把 wx_open 写在聚合 JSON 内，在保存聚合前迁到独立键（独立键尚无有效 AppID/Secret 时才写入），避免丢失
     */
    private function migrateLegacyWxOpenFromAggregateIfNeeded(): void
    {
        $raw = \app\model\SystemConfig::getValue(\app\common\service\SocialLoginService::CONFIG_KEY_AGGREGATE);
        if (!$raw) {
            return;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || empty($decoded['wx_open']) || !is_array($decoded['wx_open'])) {
            return;
        }
        $sepRaw = \app\model\SystemConfig::getValue(\app\common\service\SocialLoginService::CONFIG_KEY_WX_OPEN);
        if ($sepRaw) {
            $sep = json_decode((string) $sepRaw, true);
            if (is_array($sep)) {
                $has = trim((string) ($sep['appid'] ?? '')) !== '' || trim((string) ($sep['appsecret'] ?? '')) !== '';
                if ($has) {
                    return;
                }
            }
        }
        \app\model\SystemConfig::setValue(
            \app\common\service\SocialLoginService::CONFIG_KEY_WX_OPEN,
            json_encode($decoded['wx_open'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '微信开放平台扫码登录',
            'json'
        );
    }

    /**
     * 保存聚合快捷登录配置（仅 ad_system_config:social_login_config，不含官方微信）
     *
     * @param array<string,mixed> $data
     */
    private function saveSocialLoginConfig(array $data): void
    {
        $this->migrateLegacyWxOpenFromAggregateIfNeeded();

        $types = [];
        foreach (array_keys(\app\common\service\SocialLoginService::PROVIDERS) as $code) {
            $key = 'type_' . $code;
            $types[$code] = isset($data[$key]) && (string) $data[$key] === '1' ? 1 : 0;
        }

        $connect = trim((string) ($data['connect_url'] ?? ''));
        if ($connect === '') {
            $connect = 'https://v.910627.xyz/connect.php';
        }

        $existing = [];
        $raw = \app\model\SystemConfig::getValue(\app\common\service\SocialLoginService::CONFIG_KEY_AGGREGATE);
        if ($raw) {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $appkeyIn = trim((string) ($data['appkey'] ?? ''));
        $appkey = $appkeyIn !== '' ? $appkeyIn : (string) ($existing['appkey'] ?? '');

        $config = [
            'enabled' => isset($data['enabled']) && (string) $data['enabled'] === '1' ? 1 : 0,
            'connect_url' => $connect,
            'appid' => trim((string) ($data['appid'] ?? '')),
            'appkey' => $appkey,
            'types' => $types,
        ];

        \app\model\SystemConfig::setValue(
            \app\common\service\SocialLoginService::CONFIG_KEY_AGGREGATE,
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '聚合快捷登录对接',
            'json'
        );
    }

    /**
     * 保存微信开放平台扫码登录（仅 ad_system_config:social_wx_open_config）
     *
     * @param array<string,mixed> $data
     */
    private function saveWxOpenLoginConfig(array $data): void
    {
        $existing = \app\common\service\SocialLoginService::loadWxOpenConfig();
        $secretIn = trim((string) ($data['wx_open_appsecret'] ?? ''));
        $secret = $secretIn !== '' ? $secretIn : (string) ($existing['appsecret'] ?? '');

        $wx = [
            'enabled' => isset($data['wx_open_enabled']) && (string) $data['wx_open_enabled'] === '1' ? 1 : 0,
            'appid' => trim((string) ($data['wx_open_appid'] ?? '')),
            'appsecret' => $secret,
        ];

        \app\model\SystemConfig::setValue(
            \app\common\service\SocialLoginService::CONFIG_KEY_WX_OPEN,
            json_encode($wx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '微信开放平台扫码登录',
            'json'
        );
    }

    /**
     * 保存微信服务号关注/扫码登录（仅 ad_system_config:social_wx_mp_config）
     *
     * @param array<string,mixed> $data
     */
    private function saveWxMpLoginConfig(array $data): void
    {
        $existing = \app\common\service\SocialLoginService::loadWxMpConfig();
        $secretIn = trim((string) ($data['wx_mp_appsecret'] ?? ''));
        $secret = $secretIn !== '' ? $secretIn : (string) ($existing['appsecret'] ?? '');
        $tokenIn = trim((string) ($data['wx_mp_token'] ?? ''));
        $mpToken = $tokenIn !== '' ? $tokenIn : (string) ($existing['mp_token'] ?? '');
        $aesIn = trim((string) ($data['wx_mp_encoding_aes_key'] ?? ''));
        $clearAes = !empty($data['wx_mp_encoding_aes_key_clear']) && (string) $data['wx_mp_encoding_aes_key_clear'] === '1';
        $encodingAesKey = $clearAes ? '' : ($aesIn !== '' ? $aesIn : (string) ($existing['encoding_aes_key'] ?? ''));
        if ($encodingAesKey !== '' && strlen($encodingAesKey) !== 43) {
            throw new \InvalidArgumentException('EncodingAESKey 须为 43 位，与公众平台「基本配置」中一致');
        }

        $welcomeRaw = (string) ($data['wx_mp_login_success_reply'] ?? '');
        $welcomeTrim = trim($welcomeRaw);
        if (mb_strlen($welcomeTrim) > 600) {
            throw new \InvalidArgumentException('扫码登录成功回复（欢迎词）不超过 600 字');
        }

        $wx = [
            'enabled' => isset($data['wx_mp_enabled']) && (string) $data['wx_mp_enabled'] === '1' ? 1 : 0,
            'appid' => trim((string) ($data['wx_mp_appid'] ?? '')),
            'appsecret' => $secret,
            'mp_token' => $mpToken,
            'encoding_aes_key' => $encodingAesKey,
            'login_success_reply' => $welcomeTrim,
        ];

        \app\model\SystemConfig::setValue(
            \app\common\service\SocialLoginService::CONFIG_KEY_WX_MP,
            json_encode($wx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            '微信服务号关注登录',
            'json'
        );
    }
    
    /**
     * 获取客服配置键名描述
     * @param string $key 配置键名
     * @return string 描述
     */
    private function getCustomerServiceKeyDesc($key)
    {
        $descriptions = [
            'enabled' => '启用客服功能',
            'email' => '客服邮箱',
            'phone' => '客服电话',
            'qq' => '客服QQ',
            'wechat' => '客服微信',
            'working_hours' => '工作时间',
            'show_float_button' => '显示悬浮按钮'
        ];
        
        return $descriptions[$key] ?? $key;
    }
    
    /**
     * 获取客服配置数据（API接口）
     * GET /admin/getCustomerServiceConfig
     */
    public function getCustomerServiceConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        // 从数据库加载配置数据
        $systemConfigs = \app\model\SystemConfig::getAll();
        $config = [];
        
        // 首先尝试从JSON配置中读取
        if (isset($systemConfigs['customer_service_config'])) {
            $jsonConfig = json_decode($systemConfigs['customer_service_config']['value'], true);
            if ($jsonConfig && is_array($jsonConfig)) {
                $config = $jsonConfig;
            }
        }
        
        // 如果JSON配置不存在或解析失败，尝试从单独的配置项读取（向后兼容）
        if (empty($config)) {
            // 客服配置键名列表
            $customerServiceKeys = [
                'enabled',
                'email',
                'phone',
                'qq',
                'wechat',
                'working_hours',
                'show_float_button'
            ];
            
            foreach ($customerServiceKeys as $key) {
                $fullKey = 'customer_service_' . $key;
                if (isset($systemConfigs[$fullKey])) {
                    $config[$key] = $systemConfigs[$fullKey]['value'];
                }
            }
        }
        
        // 设置默认值
        $defaults = [
            'enabled' => 1,
            'email' => '',
            'phone' => '',
            'qq' => '',
            'wechat' => '',
            'working_hours' => '工作时间：周一至周五 9:00-18:00',
            'show_float_button' => 1
        ];
        
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($config[$key]) || $config[$key] === '') {
                $config[$key] = $defaultValue;
            }
        }
        
        return json(['code' => 1, 'data' => $config]);
    }
    
    /**
     * 保存客服配置（API接口）
     * POST /admin/saveCustomerServiceConfig
     */
    public function saveCustomerServiceConfigApi()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = request()->post();
        
        try {
            // 调用保存客服配置的方法
            $this->saveCustomerServiceConfig($data);
            
            return json(['code' => 1, 'msg' => '保存成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 测试邮件配置
     * POST /admin/testEmailConfig
     */
    public function testEmailConfig()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $email = request()->post('email');
        
        if (empty($email)) {
            return json(['code' => 0, 'msg' => '测试邮箱地址不能为空']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '邮箱地址格式不正确']);
        }
        
        try {
            // 生成测试验证码
            $testCode = '123456'; // 固定测试验证码
            
            // 发送测试邮件
            $sendResult = \app\common\MailService::sendVerifyCode($email, $testCode, 'register');
            
            if ($sendResult) {
                return json([
                    'code' => 1, 
                    'msg' => '测试邮件发送成功！请检查您的邮箱（包括垃圾邮件箱）'
                ]);
            } else {
                return json([
                    'code' => 0, 
                    'msg' => '测试邮件发送失败，请检查邮件配置是否正确'
                ]);
            }
            
        } catch (\Exception $e) {
            return json([
                'code' => 0, 
                'msg' => '测试失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 站点信息页展示字段（与「基础信息」同源：ad_system_config.site_config JSON）
     *
     * @return array<string, string>
     */
    private function getSiteInfoForView(): array
    {
        $keys = [
            'site_name', 'site_logo', 'site_icon', 'site_keywords', 'site_description',
            'site_copyright', 'site_icp', 'site_address', 'site_phone', 'site_email',
        ];
        $out = array_fill_keys($keys, '');
        $full = $this->loadSiteConfigArray();
        foreach ($keys as $k) {
            if (\array_key_exists($k, $full) && $full[$k] !== null) {
                $out[$k] = (string) $full[$k];
            }
        }

        return $out;
    }

    /**
     * 读取网站基本配置（config_key = site_config）
     *
     * @return array<string, mixed>
     */
    private function loadSiteConfigArray(): array
    {
        $defaultConfig = [
            'site_name' => '对接端管理系统',
            'site_url' => '',
            'site_keywords' => '',
            'site_description' => 'TokenAPI 对接端管理系统',
            'site_copyright' => '',
            'site_icp' => '',
            'site_address' => '',
            'site_phone' => '',
            'site_email' => '',
            'default_language' => 'zh-CN',
            'timezone' => 'Asia/Shanghai',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'site_logo' => '',
            'site_icon' => '',
        ];

        $jsonConfig = \app\model\SystemConfig::getValue('site_config');
        if ($jsonConfig === null || $jsonConfig === '') {
            return $defaultConfig;
        }
        try {
            $config = json_decode((string) $jsonConfig, true);
            if (!\is_array($config) || json_last_error() !== JSON_ERROR_NONE) {
                return $defaultConfig;
            }
        } catch (\Throwable $e) {
            return $defaultConfig;
        }

        return array_merge($defaultConfig, $config);
    }

}
