<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\common\utils\AgentIdHelper;
use app\user\BaseController;
use think\facade\View;

/**
 * 用户端控制器
 */
class User extends BaseController
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 用户端首页 - 商品列表
     */
    public function index()
    {
        return View::fetch('index');
    }
    
    /**
     * 查询结果页面
     */
    public function query()
    {
        return View::fetch('query');
    }
    
    /**
     * 下单页面
     */
    public function order()
    {
        return View::fetch('order');
    }

    
    
    /**
     * 获取商品公告内容
     * @param string $productCode 商品代码
     * @return string 公告内容
     */
    private function getProductNotice($productCode)
    {
        // 初始化公告内容为空
        $notice_text = '';
        
        try {
            // 查询指定商品的notice_text
            $product = \think\facade\Db::name('products')
                ->where('code', $productCode)
                ->where('enabled', 1)
                ->field('notice_text')
                ->find();
            
            if ($product && !empty($product['notice_text'])) {
                $notice_text = trim($product['notice_text']);
            }
        } catch (\Exception $e) {
            // 数据库查询失败，保持为空
        }
        
        return $notice_text;
    }

    /**
     * 获取下单引导图配置
     * @param string $productCode 商品代码
     * @return array{left_guide_enabled:int,left_guide_image:string,product_name:string}
     */
    private function getProductLeftGuideConfig(string $productCode): array
    {
        $nameFallback = [
            'gjlw' => '高级论文',
            'ppt'  => 'PPT生成',
            'kaiti' => '开题报告',
            'rws' => '任务书',
            'sx' => '实习报告',
            'sxrz' => '实习日志',
            'weightreduction' => '论文降重助手',
        ];
        $defaultGuideImage = '/assets/images/more/bylwsenior_js.png';
        $config = [
            // 字段缺失时也尽量保持旧行为：gjlw 默认显示引导图；降重页与 gjlw 一致默认开（无商品行时仍可有引导）
            'left_guide_enabled' => in_array($productCode, ['gjlw', 'weightreduction'], true) ? 1 : 0,
            'left_guide_image' => $defaultGuideImage,
            'product_name' => $nameFallback[$productCode] ?? $productCode,
        ];

        try {
            $product = \think\facade\Db::name('products')
                ->where('code', $productCode)
                ->where('enabled', 1)
                ->field('guide_enabled, guide_image, name')
                ->find();

            if ($product) {
                $rawGuideImage = trim((string)($product['guide_image'] ?? ''));
                $config['left_guide_enabled'] = (int)($product['guide_enabled'] ?? 0);
                $config['left_guide_image'] = $rawGuideImage !== '' ? $rawGuideImage : $defaultGuideImage;

                // 已配置非默认引导图时视为需要展示（避免「只上传图未开开关」时网络有请求但页面 display:none）
                if (
                    $config['left_guide_enabled'] !== 1
                    && $rawGuideImage !== ''
                    && $rawGuideImage !== $defaultGuideImage
                ) {
                    $config['left_guide_enabled'] = 1;
                }

                $displayName = trim((string)($product['name'] ?? ''));
                if ($displayName !== '') {
                    $config['product_name'] = $displayName;
                }
            }
        } catch (\Exception $e) {
            // 兼容：如果数据库暂未新增 guide 字段，保持默认配置
        }

        return $config;
    }

    /**
     * 是否与 Api::attachFinalPrice 在「未开启跟价」时一致：标价（成本+加价）≤0 视为免费。
     * 开启跟价时不标免费角标（避免未请求上游价时误判）。
     */
    private function isReduceEntryFreeFromProductRow(array $row): bool
    {
        if ((int)($row['follow_price'] ?? 0) === 1) {
            return false;
        }
        $price = (float)($row['cost_price'] ?? 0) + (float)($row['markup'] ?? 0);

        return $price <= 0.00001;
    }

    /**
     * 获取降重入口启用状态及 ad_products 中的商品名称、notice_text
     * @return array<string, mixed>
     */
    private function getReduceEntryEnabledMap(): array
    {
        $queryCodeList = ['tools_aigcreduceweight'];
        $enabledMap = array_fill_keys($queryCodeList, false);
        $nameMap = array_fill_keys($queryCodeList, '');
        $noticeMap = array_fill_keys($queryCodeList, '');
        $freeMap = array_fill_keys($queryCodeList, false);

        try {
            $rows = \think\facade\Db::name('products')
                ->whereIn('code', $queryCodeList)
                ->where('enabled', 1)
                ->field('code,name,notice_text,cost_price,follow_price,markup')
                ->select();

            foreach (($rows ?? []) as $row) {
                $itemCode = isset($row['code']) ? (string)$row['code'] : '';
                if ($itemCode !== '' && isset($enabledMap[$itemCode])) {
                    $enabledMap[$itemCode] = true;
                    $nameMap[$itemCode] = trim((string)($row['name'] ?? ''));
                    $noticeMap[$itemCode] = trim((string)($row['notice_text'] ?? ''));
                    $freeMap[$itemCode] = $this->isReduceEntryFreeFromProductRow($row);
                }
            }
        } catch (\Exception $e) {
        }

        return [
            'weightreduction_enabled' => (bool)($enabledMap['tools_aigcreduceweight'] ?? false),
            'reduce_entry_weightreduction_name' => $nameMap['tools_aigcreduceweight'] ?? '',
            'reduce_entry_weightreduction_notice' => $noticeMap['tools_aigcreduceweight'] ?? '',
            'reduce_entry_weightreduction_free' => (bool)($freeMap['tools_aigcreduceweight'] ?? false),
        ];
    }
    
    /**
     * 通用商品页面
     * @param string $code 商品代码，可选参数
     */
    public function product($code = '')
    {
        // 如果未传递参数，从请求中获取
        if (empty($code)) {
            $code = input('code', 'gjlw');
        }
        
        // 根据商品代码加载对应的模板
        $templateMap = [
            'gjlw' => 'service/gjlw', // 高级论文有专用页面
            'ppt' => 'service/ppt',   // PPT生成有专用页面
            'kaiti' => 'service/kaiti', // 开题报告
            'rws' => 'service/rws',     // 任务书
            'sx' => 'service/sx',       // 实习报告
            'sxrz' => 'service/sxrz',   // 实习日志
            'reduce' => 'service/reduce', // 查重/降重（入口页，内有两个子入口）
            'weightreduction' => 'service/weightreduction', // 论文降重助手
            'file_aigcreduceweight' => 'service/weightreduction', // AIGC文档降重
            'tools_wxlist' => 'service/tools_wxlist', // 参考文献获取
            'tools_rewrite' => 'service/tools_rewrite', // 段落改写
            'tools_illustration' => 'service/tools_illustration', // 段落配图
            'tools_chart' => 'service/tools_chart', // 图表生成
            'tools_createtitle' => 'service/tools_createtitle', // 题目生成
            'tools_createoutline' => 'service/tools_createoutline', // 大纲生成
            'tools_aigcreduceweight' => 'service/tools_aigcreduceweight', // AIGC降重工具
            'tools_center' => 'service/tools_center', // 小工具中心
        ];
        
        // 获取对应的模板
        $template = $templateMap[$code] ?? 'service/product';
        
        // 获取商品公告内容
        $notice_text = $this->getProductNotice($code);

        // 获取左侧引导图配置（gjlw 页面会用到）
        $leftGuideConfig = $this->getProductLeftGuideConfig($code);
        
        $viewData = [
            'notice_text' => $notice_text,
            'left_guide_enabled' => $leftGuideConfig['left_guide_enabled'],
            'left_guide_image' => $leftGuideConfig['left_guide_image'],
            'product_name' => $leftGuideConfig['product_name'],
            'agent_id_prefix' => AgentIdHelper::prefix(),
        ];

        if ($code === 'reduce') {
            $viewData = array_merge($viewData, $this->getReduceEntryEnabledMap());
        }

        // 传递数据给视图
        return View::fetch($template, $viewData);
    }
    
    /**
     * 通用支付页面
     * GET /user/view/service/payment.html?order_no=订单号
     */
    public function payment()
    {
        return View::fetch('service/payment');
    }
    
    /**
     * 论文大纲模板管理页面
     * 对应路由：GET /user/outline_templates
     */
    public function outline_templates()
    {
        return View::fetch('service/outline_templates');
    }
    
    /**
     * 个人资料页面
     */
    public function profile()
    {
        return View::fetch('user/profile');
    }
    
    /**
     * 账户安全页面
     */
    public function security()
    {
        return View::fetch('user/security');
    }
    
    /**
     * 我的订单页面（列表）
     */
    public function orders()
    {
        return View::fetch('user/orders');
    }
    
    /**
     * 系统设置页面
     */
    public function settings()
    {
        return View::fetch('user/settings');
    }
    
    /**
     * 余额管理页面
     */
    public function balance()
    {
        // 获取当前用户ID
        $userId = session('user_id');
        $userBalance = '0.00';
        $updateTime = date('Y-m-d H:i:s');
        
        if ($userId) {
            try {
                // 直接查询数据库获取余额
                $user = \think\facade\Db::name('users')
                    ->where('id', $userId)
                    ->field('balance, update_time')
                    ->find();
                
                if ($user) {
                    $userBalance = $user['balance'] ?? '0.00';
                    $updateTime = $user['update_time'] ?? date('Y-m-d H:i:s');
                    
                    // 记录调试日志
                }
            } catch (\Exception $e) {
            }
        }
        
        // 传递余额数据到视图
        return View::fetch('user/balance', [
            'user_balance' => $userBalance,
            'update_time' => $updateTime,
            'user_id' => $userId
        ]);
    }
    
    
}
