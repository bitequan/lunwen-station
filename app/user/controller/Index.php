<?php
declare (strict_types = 1);

namespace app\user\controller;

use think\facade\View;
use app\model\Products;
use think\facade\Db;

class Index
{
    public function index()
    {
        // 旧根首页("用户中心" PHP 模板)已废弃：主站统一走 Nuxt 前端（写作中心工作台）
        // 根路径按 UA 自适应分流：移动端 → /m/，桌面端 → /pc/
        // （iPad 新 UA 伪装 Mac 的情况由前端 m-redirect 中间件按 maxTouchPoints 兜底）
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $isMobile = (bool) preg_match('/Android|iPhone|iPad|iPod|IEMobile|Opera Mini|Mobile/i', $ua);
        return redirect($isMobile ? '/m/' : '/pc/');
        
        // 以下旧逻辑不再执行（原：维护模式检查 + 商品列表拼装 HTML）
        // 首先检查维护模式
        $maintenanceCheck = $this->checkMaintenanceForPage();
        if ($maintenanceCheck['maintenance_mode'] === 1) {
            // 维护模式开启，显示维护页面
            return $this->showMaintenancePage($maintenanceCheck['maintenance_message']);
        }
        
        // 获取所有启用的商品，按sort_order排序
        $products = Products::where('enabled', 1)
            ->order('sort_order', 'asc')
            ->select();
        
        // 查找默认商品
        $defaultProduct = Products::where('enabled', 1)
            ->where('is_default', 1)
            ->find();
        
        // 如果没有设置默认商品，则使用第一个商品
        if (!$defaultProduct && count($products) > 0) {
            $defaultProduct = $products[0];
        }
        
        // 处理商品URL，使用统一的商品页面路由
        $productsArray = $products->toArray();
        foreach ($productsArray as &$product) {
            // 所有商品都使用统一的商品页面，通过code参数区分
            $product['page_url'] = '/user/product?code=' . urlencode($product['code']);
        }
        unset($product); // 解除引用
        
        // 处理默认商品的URL
        if ($defaultProduct) {
            $defaultProduct['page_url'] = '/user/product?code=' . urlencode($defaultProduct['code']);
        }
        
        // 获取网站名称
        $siteName = $this->getSiteName();
        
        // 获取网站Logo
        $siteLogo = $this->getSiteLogo();
        
        // 生成完整的HTML，不使用模板变量
        $html = $this->generateFullHtml($productsArray, $defaultProduct, $siteName, $siteLogo);
        
        // 输出HTML
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
    
    /**
     * 生成完整的HTML页面
     */
    private function generateFullHtml($products, $defaultProduct, $siteName = '用户中心', $siteLogo = '')
    {
        // 读取基础模板
        $templatePath = dirname(__DIR__) . '/view/index/index.html';
        if (!file_exists($templatePath)) {
            // 尝试其他路径
            $templatePath = 'app/user/view/index/index.html';
        }
        $templateContent = file_get_contents($templatePath);
        
        // 生成菜单HTML
        $menuHtml = $this->generateMenuHtml($products, $defaultProduct);
        
        // 生成隐藏字段HTML
        $hiddenFields = $this->generateHiddenFields($defaultProduct);
        
        // 替换菜单部分
        $menuStart = '            <nav class="sidebar-nav">';
        $menuEnd = '            </nav>';
        $newMenuContent = $menuStart . "\n                " . $menuHtml . "\n            " . $menuEnd;
        
        // 查找并替换菜单部分
        $pattern = '/' . preg_quote($menuStart, '/') . '.*?' . preg_quote($menuEnd, '/') . '/s';
        $templateContent = preg_replace($pattern, $newMenuContent, $templateContent);
        
        // 替换隐藏字段部分
        $hiddenFieldsMarker = '        {$hiddenFields|raw}';
        $templateContent = str_replace($hiddenFieldsMarker, $hiddenFields, $templateContent);
        
        // 替换网站名称
        $templateContent = $this->replaceSiteName($templateContent, $siteName);
        
        // 替换Logo
        $templateContent = $this->replaceLogo($templateContent, $siteLogo, $siteName);
        
        // 替换SEO信息（描述和关键词）
        $templateContent = $this->replaceSeoInfo($templateContent);

        // 注入备案/版权等站点底部信息
        $templateContent = $this->replaceIcpInfo($templateContent);

        // 注入用户端主题变量（覆盖 base.css 的 --primary-*）
        $themeCssVars = $this->buildUserThemeCssVars();
        $templateContent = str_replace(
            '/* USER_THEME_VARS_PLACEHOLDER */',
            $themeCssVars,
            $templateContent
        );
        
        return $templateContent;
    }
    
    /**
     * 生成菜单HTML
     */
    private function generateMenuHtml($products, $defaultProduct)
    {
        $menuHtml = '';
        
        if (!empty($products)) {
            // 分离主要商品、写作中心商品和工具类商品
            $mainProducts = [];
            $toolProducts = [];
            $writingCenterProducts = [];
            
            // 写作中心包含的商品 code 列表（开题报告、任务书、实习报告、实习日志）
            $writingCenterCodes = ['kaiti', 'rws', 'sx', 'sxrz'];
            
            foreach ($products as $product) {
                // 统一通过 code 识别增值服务商品（无论 product_type 字段是否存在）
                $productCode = $product['code'] ?? '';
                
                // 写作中心内的商品单独收集，不在主菜单单独展示
                if (in_array($productCode, $writingCenterCodes, true)) {
                    $writingCenterProducts[] = $product;
                    continue;
                }
                
                // 获取商品类型（优先使用 product_type 字段，兼容旧数据）
                $productType = 1; // 默认主要商品
                if (isset($product['product_type'])) {
                    $productType = (int)$product['product_type'];
                } else {
                    // 兼容旧数据：通过 code 判断工具类
                    if (strpos($productCode, 'tools_') === 0) {
                        $productType = 2; // 工具类商品
                    }
                }
                // 根据商品类型分类
                if ($productType == 2) {
                    $toolProducts[] = $product;
                } elseif ($productType == 1) {
                    // 只添加主要商品，不添加增值服务（type=3）
                    $mainProducts[] = $product;
                }
                // 增值服务（type=3）不添加到任何菜单中
            }
            
            // 主要商品区域
            if (!empty($mainProducts)) {
                $menuHtml .= '<div class="nav-section">';
                
                foreach ($mainProducts as $product) {
                    $isActive = ($defaultProduct && $product['id'] == $defaultProduct['id']) ? 'active' : '';
                    $icon = $this->generateIconHtml($product);
                    
                    $menuHtml .= '<a href="#" class="nav-item ' . $isActive . '" 
                                   data-url="' . htmlspecialchars($product['page_url']) . '" 
                                   data-tooltip="' . htmlspecialchars($product['name']) . '">
                                    <div class="nav-icon">' . $icon . '</div>
                                    <span class="nav-text">' . htmlspecialchars($product['name']) . '</span>
                                </a>';
                }
                
                $menuHtml .= '</div>';
            }
            
            // 写作中心统一入口（包含：开题报告、任务书、实习报告、实习日志）
            if (!empty($writingCenterProducts)) {
                $menuHtml .= '<div class="nav-section">
                                <a href="#" class="nav-item" 
                                   data-url="/user/writing_center" 
                                   data-tooltip="写作中心">
                                    <div class="nav-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 20h16"></path>
                                            <path d="M6 18l10-10 2 2L8 20H6v-2z"></path>
                                            <path d="M14 4l2-2 4 4-2 2-4-4z"></path>
                                        </svg>
                                    </div>
                                    <span class="nav-text">写作中心</span>
                                </a>
                              </div>';
            }
            
            // 工具类统一入口
            if (!empty($toolProducts)) {
                $menuHtml .= '<div class="nav-section">
                                <a href="#" class="nav-item" 
                                   data-url="/user/product?code=tools_center" 
                                   data-tooltip="小工具中心">
                                    <div class="nav-icon">
                                        <span style="font-size: 20px;">🧰</span>
                                    </div>
                                    <span class="nav-text">小工具</span>
                                </a>
                              </div>';
            }
        } else {
            // 如果没有商品，显示默认菜单
            $menuHtml .= '<div class="nav-section">
                            <a href="#" class="nav-item active" data-url="/gjlw" data-tooltip="高级论文">
                                <div class="nav-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                </div>
                                <span class="nav-text">高级论文</span>
                            </a>
                        </div>';
        }
        
        return $menuHtml;
    }
    
    /**
     * 生成图标HTML
     */
    private function generateIconHtml($product)
    {
        if (!empty($product['icon'])) {
            $iconPath = trim($product['icon']);
            // 判断是否为路径（以 / 开头或以图片扩展名结尾）
            if (strpos($iconPath, '/') === 0 || preg_match('/\.(svg|png|jpg|jpeg|gif|webp)$/i', $iconPath)) {
                // 如果是相对路径，确保以 / 开头
                if (strpos($iconPath, '/') !== 0) {
                    $iconPath = '/' . $iconPath;
                }
                return '<img src="' . htmlspecialchars($iconPath) . '" alt="' . htmlspecialchars($product['name']) . '" style="width: 100%; height: 100%; object-fit: contain;">';
            } else {
                // 否则作为文本/emoji显示
                return '<span style="font-size: 20px;">' . htmlspecialchars($iconPath) . '</span>';
            }
        } else {
            // 默认图标
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>';
        }
    }
    
    /**
     * 生成隐藏字段HTML
     */
    private function generateHiddenFields($defaultProduct)
    {
        if (!$defaultProduct) {
            return '';
        }
        
        $defaultUrl = htmlspecialchars($defaultProduct['page_url']);
        $defaultName = htmlspecialchars($defaultProduct['name']);
        
        return '
            <!-- 隐藏字段，用于传递默认商品信息 -->
            <input type="hidden" id="defaultProductUrl" value="' . $defaultUrl . '">
            <input type="hidden" id="defaultProductName" value="' . $defaultName . '">';
    }
    
    /**
     * 获取网站配置
     * @return array 网站配置
     */
    private function getSiteConfig(): array
    {
        try {
            // 从 ad_system_config 表获取JSON格式配置
            $config = Db::name('system_config')
                ->where('config_key', 'site_config')
                ->field('config_value')
                ->find();
            
            if ($config && !empty($config['config_value'])) {
                $siteConfig = json_decode($config['config_value'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($siteConfig)) {
                    return $siteConfig;
                }
            }
            
            return [];
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取用户端主题配置（后台配置 user_theme_config）
     * @return array{primary_color:string,primary_hover:string,primary_light:string,primary_dark:string}
     */
    private function getUserThemeConfig(): array
    {
        $defaults = [
            'primary_color' => '#6366F1',
            'primary_hover' => '#4F46E5',
            'primary_light' => '#EEF2FF',
            'primary_dark' => '#4338CA',
        ];

        try {
            $row = Db::name('system_config')
                ->where('config_key', 'user_theme_config')
                ->field('config_value')
                ->find();

            $jsonValue = $row['config_value'] ?? '';
            if (!empty($jsonValue)) {
                $decoded = json_decode((string)$jsonValue, true);
                if (is_array($decoded)) {
                    return array_merge($defaults, $decoded);
                }
            }
        } catch (\Exception $e) {
        }

        return $defaults;
    }

    /**
     * 将 16 进制颜色转换为 rgb 列表（用于 rgba(var(--primary-rgb), alpha)）
     * @return string "r, g, b"
     */
    private function hexToRgb(string $hex): string
    {
        $hex = trim($hex);
        if ($hex === '') {
            return '99, 102, 241';
        }
        if (strpos($hex, '#') === 0) {
            $hex = substr($hex, 1);
        }
        $hex = strtolower($hex);

        // 支持 #rgb
        if (strlen($hex) === 3 && preg_match('/^[0-9a-f]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-f]{6}$/', $hex)) {
            return '99, 102, 241';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return $r . ', ' . $g . ', ' . $b;
    }

    /**
     * 生成用于覆盖 :root 的主题变量 CSS
     */
    private function buildUserThemeCssVars(): string
    {
        $config = $this->getUserThemeConfig();

        $primaryColor = (string)($config['primary_color'] ?? '#6366F1');
        $primaryHover = (string)($config['primary_hover'] ?? '#4F46E5');
        $primaryLight = (string)($config['primary_light'] ?? '#EEF2FF');
        $primaryDark = (string)($config['primary_dark'] ?? '#4338CA');

        // 归一化：确保颜色带 #
        $primaryColor = (strpos($primaryColor, '#') === 0) ? $primaryColor : ('#' . $primaryColor);
        $primaryHover = (strpos($primaryHover, '#') === 0) ? $primaryHover : ('#' . $primaryHover);
        $primaryLight = (strpos($primaryLight, '#') === 0) ? $primaryLight : ('#' . $primaryLight);
        $primaryDark = (strpos($primaryDark, '#') === 0) ? $primaryDark : ('#' . $primaryDark);

        $primaryRgb = $this->hexToRgb($primaryColor);
        $primaryHoverRgb = $this->hexToRgb($primaryHover);
        $primaryDarkRgb = $this->hexToRgb($primaryDark);

        return <<<CSS
:root {
    --primary-color: {$primaryColor};
    --primary-hover: {$primaryHover};
    --primary-light: {$primaryLight};
    --primary-dark: {$primaryDark};

    --primary-rgb: {$primaryRgb};
    --primary-hover-rgb: {$primaryHoverRgb};
    --primary-dark-rgb: {$primaryDarkRgb};
}
CSS;
    }
    
    /**
     * 获取网站名称
     * @return string 网站名称
     */
    private function getSiteName(): string
    {
        $siteConfig = $this->getSiteConfig();
        return $siteConfig['site_name'] ?? '用户中心';
    }
    
    /**
     * 获取网站Logo路径
     * @return string Logo路径，如果没有设置则返回空字符串
     */
    private function getSiteLogo(): string
    {
        $siteConfig = $this->getSiteConfig();
        $logoPath = $siteConfig['site_logo'] ?? '';
        
        if (!empty($logoPath)) {
            return $this->sanitizeLogoPath($logoPath);
        }
        
        return '';
    }
    
    /**
     * 清理Logo路径
     * @param string $logoPath Logo路径
     * @return string 清理后的Logo路径
     */
    private function sanitizeLogoPath(string $logoPath): string
    {
        if (empty($logoPath)) {
            return '';
        }
        
        // 如果已经是完整的URL，直接返回
        if (strpos($logoPath, 'http://') === 0 || strpos($logoPath, 'https://') === 0) {
            return $logoPath;
        }
        
        // 如果是相对路径，确保以 / 开头
        if (strpos($logoPath, '/') !== 0) {
            $logoPath = '/' . $logoPath;
        }
        
        return $logoPath;
    }
    
    /**
     * 替换HTML中的网站名称
     * @param string $html HTML内容
     * @param string $siteName 网站名称
     * @return string 替换后的HTML
     */
    private function replaceSiteName(string $html, string $siteName): string
    {
        // 替换页面标题
        $html = str_replace('<title>用户中心</title>', '<title>' . htmlspecialchars($siteName) . ' - 用户中心</title>', $html);
        
        // 替换Logo文本
        $html = str_replace('<div class="logo-text">用户中心</div>', '<div class="logo-text">' . htmlspecialchars($siteName) . '</div>', $html);
        
        // 替换页面标题
        $html = str_replace('<h1 class="header-title" id="pageTitle">用户中心</h1>', '<h1 class="header-title" id="pageTitle">' . htmlspecialchars($siteName) . ' - 用户中心</h1>', $html);
        
        return $html;
    }
    
    /**
     * 替换HTML中的Logo
     * @param string $html HTML内容
     * @param string $logoPath Logo路径
     * @param string $siteName 网站名称
     * @return string 替换后的HTML
     */
    private function replaceLogo(string $html, string $logoPath, string $siteName): string
    {
        // 获取网站名称的首字母（大写）
        $firstLetter = $this->getFirstLetter($siteName);
        
        if (!empty($logoPath)) {
            // 如果有Logo，显示Logo图片
            $logoHtml = '<img src="' . htmlspecialchars($logoPath) . '" alt="' . htmlspecialchars($siteName) . '" class="logo-image">';
        } else {
            // 如果没有Logo，显示首字母
            $logoHtml = '<div class="logo-letter">' . htmlspecialchars($firstLetter) . '</div>';
        }
        
        // 替换Logo部分
        $html = str_replace('<div class="logo">U</div>', '<div class="logo">' . $logoHtml . '</div>', $html);
        
        return $html;
    }
    
    /**
     * 获取网站名称的首字母（大写）
     * @param string $siteName 网站名称
     * @return string 首字母
     */
    private function getFirstLetter(string $siteName): string
    {
        if (empty($siteName)) {
            return 'U';
        }
        
        // 获取第一个字符
        $firstChar = mb_substr($siteName, 0, 1, 'UTF-8');
        
        // 转换为大写
        $firstLetter = mb_strtoupper($firstChar, 'UTF-8');
        
        // 如果是中文字符，尝试获取拼音首字母
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $firstChar)) {
            // 简单的中文转拼音首字母映射（常见字符）
            $pinyinMap = [
                '对' => 'D', '接' => 'J', '端' => 'D', '管' => 'G', '理' => 'L', '系' => 'X', '统' => 'T',
                '用' => 'Y', '户' => 'H', '中' => 'Z', '心' => 'X',
                // 可以添加更多中文字符映射
            ];
            
            if (isset($pinyinMap[$firstChar])) {
                return $pinyinMap[$firstChar];
            }
            
            // 如果没有映射，返回原字符
            return $firstChar;
        }
        
        // 如果是英文字母，直接返回大写
        if (preg_match('/[A-Za-z]/', $firstLetter)) {
            return $firstLetter;
        }
        
        // 其他字符返回默认值
        return 'U';
    }

    /**
     * 替换HTML中的备案/版权信息
     * @param string $html HTML内容
     * @return string
     */
    private function replaceIcpInfo(string $html): string
    {
        // 获取网站配置
        $siteConfig = $this->getSiteConfig();

        $icp      = trim($siteConfig['site_icp'] ?? '');
        $copyright = trim($siteConfig['site_copyright'] ?? '');

        // 生成备案信息HTML（行业通用：版权与备案分项展示）
        $segments = [];
        if ($copyright !== '') {
            $year = date('Y');
            $segments[] = '<span class="icp-item icp-copyright">Copyright © ' . $year . ' ' . htmlspecialchars($copyright) . '</span>';
        }
        if ($icp !== '') {
            $icpHtml = '<a class="icp-link" href="https://beian.miit.gov.cn/" target="_blank" rel="noreferrer noopener">' . htmlspecialchars($icp) . '</a>';
            $segments[] = '<span class="icp-item icp-record">' . $icpHtml . '</span>';
        }

        if (empty($segments)) {
            // 两项都未配置时，移除整个页脚区域，不预留任何空间
            $pattern = '/\s*<footer class="site-footer">\s*<!-- ICP_INFO_PLACEHOLDER -->\s*<\/footer>\s*/';
            return preg_replace($pattern, '', $html, 1) ?? $html;
        }

        $replaceHtml = '<div class="icp-info">' . implode('', $segments) . '</div>';
        return str_replace('<!-- ICP_INFO_PLACEHOLDER -->', $replaceHtml, $html);
    }
    
    /**
     * 替换HTML中的SEO信息（描述和关键词）
     * @param string $html HTML内容
     * @return string 替换后的HTML
     */
    private function replaceSeoInfo(string $html): string
    {
        // 获取网站配置
        $siteConfig = $this->getSiteConfig();
        
        // 获取网站描述
        $siteDescription = $siteConfig['site_description'] ?? '专业的AI写作平台';
        
        // 获取网站关键词
        $siteKeywords = $siteConfig['site_keywords'] ?? 'AI写作,论文范文生成,毕业论文';
        
        // 替换描述meta标签
        $descriptionMeta = '<meta name="description" content="' . htmlspecialchars($siteDescription) . '">';
        $html = $this->addOrReplaceMetaTag($html, 'description', $descriptionMeta);
        
        // 替换关键词meta标签
        $keywordsMeta = '<meta name="keywords" content="' . htmlspecialchars($siteKeywords) . '">';
        $html = $this->addOrReplaceMetaTag($html, 'keywords', $keywordsMeta);
        
        // 替换Favicon
        $html = $this->replaceFavicon($html, $siteConfig);
        
        return $html;
    }
    
    /**
     * 添加或替换meta标签
     * @param string $html HTML内容
     * @param string $name meta标签的name属性
     * @param string $newMetaTag 新的meta标签
     * @return string 替换后的HTML
     */
    private function addOrReplaceMetaTag(string $html, string $name, string $newMetaTag): string
    {
        // 查找现有的meta标签
        $pattern = '/<meta\s+name=["\']' . preg_quote($name, '/') . '["\'][^>]*>/i';
        
        if (preg_match($pattern, $html)) {
            // 如果存在，替换它
            $html = preg_replace($pattern, $newMetaTag, $html);
        } else {
            // 如果不存在，在title标签后添加
            $titlePattern = '/<title>[^<]*<\/title>/i';
            if (preg_match($titlePattern, $html, $matches)) {
                $html = str_replace($matches[0], $matches[0] . "\n    " . $newMetaTag, $html);
            }
        }
        
        return $html;
    }
    
    /**
     * 替换HTML中的Favicon
     * @param string $html HTML内容
     * @param array $siteConfig 网站配置
     * @return string 替换后的HTML
     */
    private function replaceFavicon(string $html, array $siteConfig): string
    {
        // 获取Favicon路径
        $faviconPath = $siteConfig['site_icon'] ?? '';
        
        if (empty($faviconPath)) {
            // 如果没有设置Favicon，使用默认的Favicon
            $faviconPath = '/favicon.ico';
        } else {
            // 清理Favicon路径
            $faviconPath = $this->sanitizeLogoPath($faviconPath);
        }
        
        // 生成Favicon链接标签
        $faviconTag = '<link rel="icon" href="' . htmlspecialchars($faviconPath) . '" type="image/x-icon">';
        
        // 查找现有的Favicon链接
        $pattern = '/<link\s+rel=["\'](?:icon|shortcut icon)["\'][^>]*>/i';
        
        if (preg_match($pattern, $html)) {
            // 如果存在，替换它
            $html = preg_replace($pattern, $faviconTag, $html);
        } else {
            // 如果不存在，在title标签后添加
            $titlePattern = '/<title>[^<]*<\/title>/i';
            if (preg_match($titlePattern, $html, $matches)) {
                // 在title标签后添加Favicon
                $html = str_replace($matches[0], $matches[0] . "\n    " . $faviconTag, $html);
            } else {
                // 如果找不到title标签，在head标签末尾添加
                $headPattern = '/<\/head>/i';
                if (preg_match($headPattern, $html)) {
                    $html = preg_replace($headPattern, "    " . $faviconTag . "\n</head>", $html);
                }
            }
        }
        
        return $html;
    }
    
    /**
     * 检查维护模式（用于页面渲染）
     * @return array
     */
    private function checkMaintenanceForPage(): array
    {
        try {
            // 从数据库获取维护模式配置
            $maintenanceConfig = Db::name('system_config')
                ->where('config_key', 'system_maintenance_mode')
                ->field('config_value')
                ->find();
            
            $maintenanceMode = $maintenanceConfig['config_value'] ?? '0';
            
            // 获取维护消息
            $messageConfig = Db::name('system_config')
                ->where('config_key', 'system_maintenance_message')
                ->field('config_value')
                ->find();
            
            $maintenanceMessage = $messageConfig['config_value'] ?? '网站正在维护中，请稍后再访问。';
            
            return [
                'maintenance_mode' => (int)$maintenanceMode,
                'maintenance_message' => $maintenanceMessage
            ];
            
        } catch (\Exception $e) {
            
            // 如果数据库查询失败，返回默认值
            return [
                'maintenance_mode' => 0,
                'maintenance_message' => '网站正在维护中，请稍后再访问。'
            ];
        }
    }
    
    /**
     * 显示维护页面
     * @param string $message 维护消息
     * @return \think\Response
     */
    private function showMaintenancePage(string $message)
    {
        // 读取维护页面模板
        $templatePath = dirname(__DIR__) . '/view/maintenance.html';
        if (!file_exists($templatePath)) {
            // 如果维护页面不存在，返回简单的维护提示
            $html = '<!DOCTYPE html>
            <html lang="zh-CN">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>系统维护中</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                        color: #374151;
                    }
                    .maintenance-container {
                        background: white;
                        border-radius: 20px;
                        padding: 40px;
                        max-width: 500px;
                        width: 100%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        text-align: center;
                    }
                    .maintenance-icon {
                        font-size: 64px;
                        margin-bottom: 20px;
                        color: #F59E0B;
                    }
                    .maintenance-title {
                        font-size: 24px;
                        font-weight: 700;
                        color: #374151;
                        margin-bottom: 12px;
                    }
                    .maintenance-message {
                        font-size: 16px;
                        color: #6B7280;
                        line-height: 1.6;
                        margin-bottom: 24px;
                    }
                </style>
            </head>
            <body>
                <div class="maintenance-container">
                    <div class="maintenance-icon">🔧</div>
                    <h1 class="maintenance-title">系统维护中</h1>
                    <p class="maintenance-message">' . htmlspecialchars($message) . '</p>
                </div>
            </body>
            </html>';
            
            return response($html, 200, ['Content-Type' => 'text/html']);
        }
        
        $templateContent = file_get_contents($templatePath);
        
        // 替换维护消息
        $templateContent = str_replace(
            '网站正在维护中，请稍后再访问。给您带来的不便，敬请谅解。',
            htmlspecialchars($message),
            $templateContent
        );
        
        // 设置HTTP状态码为200（正常显示维护页面）
        return response($templateContent, 200, ['Content-Type' => 'text/html']);
    }
    
    /**
     * 检查维护模式（API接口）
     * @return \think\Response
     */
    public function checkMaintenance()
    {
        try {
            // 从数据库获取维护模式配置
            $maintenanceConfig = Db::name('system_config')
                ->where('config_key', 'system_maintenance_mode')
                ->field('config_value')
                ->find();
            
            $maintenanceMode = $maintenanceConfig['config_value'] ?? '0';
            
            // 获取维护消息
            $messageConfig = Db::name('system_config')
                ->where('config_key', 'system_maintenance_message')
                ->field('config_value')
                ->find();
            
            $maintenanceMessage = $messageConfig['config_value'] ?? '网站正在维护中，请稍后再访问。';
            
            // 获取维护结束时间
            $endTimeConfig = Db::name('system_config')
                ->where('config_key', 'system_maintenance_end_time')
                ->field('config_value')
                ->find();
            
            $maintenanceEndTime = $endTimeConfig['config_value'] ?? '';
            
            // 获取页面大小配置（用于其他用途）
            $pageSizeConfig = Db::name('system_config')
                ->where('config_key', 'system_page_size')
                ->field('config_value')
                ->find();
            
            $pageSize = $pageSizeConfig['config_value'] ?? '15';
            
            // 获取客服配置
            $customerServiceConfig = [];
            try {
                $jsonConfig = Db::name('system_config')
                    ->where('config_key', 'customer_service_config')
                    ->value('config_value');
                
                if ($jsonConfig) {
                    $customerServiceConfig = json_decode($jsonConfig, true);
                    if (!is_array($customerServiceConfig)) {
                        $customerServiceConfig = [];
                    }
                }
                
                // 如果JSON配置不存在或解析失败，尝试从单独的配置项读取
                if (empty($customerServiceConfig)) {
                    $systemConfigs = Db::name('system_config')
                        ->where('config_key', 'like', 'customer_service_%')
                        ->column('config_value', 'config_key');
                    
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
                            $customerServiceConfig[$key] = $systemConfigs[$fullKey];
                        }
                    }
                }
            } catch (\Exception $e) {
                $customerServiceConfig = [];
            }
            
            $data = [
                'maintenance_mode' => (int)$maintenanceMode,
                'maintenance_message' => $maintenanceMessage,
                'maintenance_end_time' => $maintenanceEndTime,
                'page_size' => (int)$pageSize,
                'estimated_time' => $maintenanceEndTime ? $maintenanceEndTime : '维护时间待定',
                'start_time' => date('Y-m-d H:i:s'),
                'customer_service' => $customerServiceConfig
            ];
            
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            
            // 如果数据库查询失败，返回默认值
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'maintenance_mode' => 0,
                    'maintenance_message' => '网站正在维护中，请稍后再访问。',
                    'maintenance_end_time' => '',
                    'page_size' => 15,
                    'estimated_time' => '维护时间待定',
                    'start_time' => date('Y-m-d H:i:s'),
                    'customer_service' => []
                ]
            ]);
        }
    }
}
