<?php
declare(strict_types=1);

namespace app\user\controller;

use app\model\SystemConfig;
use app\model\Products;
use app\common\service\JsonService;

/**
 * 站点配置新版接口
 *
 * 前端 useSite() 会调用 GET /api/agent/site 获取当前站点配置，
 * 用于展示站点名/logo、应用多配色主题、功能开关与售价倍率。
 * 本项目为主站（非代理分站），返回主站默认配置，theme 默认 teal。
 */
class V2Site
{
    private const DEFAULT_THEME = 'teal';

    /**
     * 当前站点配置
     */
    public function site(): \think\response\Json
    {
        $siteConfig = $this->readSiteConfig();

        $theme = $siteConfig['theme'] ?? '';
        if (!in_array($theme, ['teal', 'orange', 'blue', 'purple'], true)) {
            $theme = self::DEFAULT_THEME;
        }

        return JsonService::data([
            'is_sub_site'  => false,
            'domain'       => request()->host(),
            'template_key' => 'default',
            'theme'        => $theme,
            'site_name'    => trim((string) ($siteConfig['site_name'] ?? '')) ?: 'AI写作助手',
            'logo'         => (string) ($siteConfig['site_logo'] ?? ''),
            'favicon'      => (string) (($siteConfig['site_icon'] ?? '') ?: ($siteConfig['site_logo'] ?? '')),
            'announcement_title'   => '',
            'announcement_content' => '',
            'announcement_color'   => 0,
            'announcement_time'    => 0,
            'pay_enabled'  => 0,
            'pay_wechat'   => '',
            'pay_alipay'   => '',
            'pay_note'     => '',
            'pay_config'   => ['wechat' => [], 'alipay' => []],
            'service_wechat' => '',
            'service_phone'  => (string) ($siteConfig['service_phone'] ?? ''),
            'service_time'   => '',
            'feature_map'  => $this->defaultFeatureMap(),
            'menu_products'=> $this->menuProducts(),
            'price_rate'   => 1.00,
            'price_rates'  => [],
            'status'       => 1,
            'copyright'    => '',
            'customer_service' => [
                'qr_code' => '',
                'wechat'  => (string) ($siteConfig['service_wechat'] ?? ''),
                'phone'   => (string) ($siteConfig['service_phone'] ?? ''),
                'service_time' => '',
            ],
            'seo' => [
                'title'       => (string) ($siteConfig['seo_title'] ?? ''),
                'keywords'    => (string) ($siteConfig['seo_keywords'] ?? ''),
                'description' => (string) ($siteConfig['seo_description'] ?? ''),
            ],
            'stat_code' => '',
        ]);
    }

    /**
     * 从 ad_system_config 读取 site_config JSON
     */
    private function readSiteConfig(): array
    {
        try {
            $raw = SystemConfig::getValue('site_config', '');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            // 配置缺失时返回空
        }
        return [];
    }

    /**
     * 启用中的商品列表，供前端侧栏菜单按 is_default + sort_order 排序
     * （商品管理「设为默认/首页默认」即 is_default；sort_order 为菜单顺序）
     *
     * @return array<int, array{code:string,int:is_default,is_default:int,sort_order:int}>
     */
    private function menuProducts(): array
    {
        try {
            $list = Products::where('enabled', 1)
                ->order('sort_order', 'asc')
                ->field(['code', 'is_default', 'sort_order'])
                ->select()
                ->toArray();
            return array_map(function ($p) {
                return [
                    'code'       => (string) ($p['code'] ?? ''),
                    'is_default' => (int) ($p['is_default'] ?? 0),
                    'sort_order' => (int) ($p['sort_order'] ?? 0),
                ];
            }, $list);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 功能开关默认全开（与前端 FEATURE_PAGES 一致）
     */
    private function defaultFeatureMap(): array
    {
        return [
            'create' => true,
            'writing' => true,
            'autodoc' => true,
            'aippt' => true,
            'ai_check' => true,
            'reduce_weight' => true,
            'tools' => true,
            'orders' => true,
            'templates' => true,
            'package_shop' => true,
        ];
    }
}