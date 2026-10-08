<?php
namespace app\common\utils;

/**
 * 价格计算工具类
 * 处理各种价格计算逻辑
 */
class PriceCalculator
{
    /**
     * 计算显示价格
     * 
     * @param string $key 价格配置的key，如 "adppt", "adlw" 等
     * @param array $config 价格配置数组
     * @param array $userInfo 用户信息数组，包含 addprice 字段
     * @param int $computing 计算类型：0=不使用代理费率，1=乘法计算，2=加法计算
     * @return float 计算后的价格
     */
    public static function getDisplayPrice($key, $config, $userInfo, $computing)
    {
        $price = 0;
        
        // 从配置中查找对应key的价格
        foreach ($config as $item) {
            if ($item['key'] === $key) {
                $price = floatval($item['price'] ?? 0);
                break;
            }
        }
        
        // 根据计算类型应用代理费率
        if ($computing == 1) {
            // 乘法计算
            $price *= floatval($userInfo['addprice'] ?? 1);
        } elseif ($computing == 2) {
            // 加法计算
            $price += floatval($userInfo['addprice'] ?? 0);
        }
        
        return round($price, 2);
    }
    
    /**
     * 从JSON配置文件中获取价格配置
     * 
     * @param string $configPath 配置文件路径
     * @return array 价格配置数组
     */
    public static function getPriceConfig($configPath = '')
    {
        if (empty($configPath)) {
            $configPath = root_path() . 'config_price.json';
        }
        
        if (!file_exists($configPath)) {
            return [];
        }
        
        $configContent = file_get_contents($configPath);
        $config = json_decode($configContent, true);
        
        return $config ?: [];
    }
    
    /**
     * 计算订单总价
     * 
     * @param float $basePrice 基础价格
     * @param array $services 服务列表
     * @param array $servicePrices 服务价格配置
     * @return float 总价
     */
    public static function calculateOrderTotal($basePrice, $services, $servicePrices)
    {
        $total = $basePrice;
        
        if (!empty($services) && is_array($services)) {
            foreach ($services as $service) {
                if (isset($servicePrices[$service])) {
                    $total += floatval($servicePrices[$service]);
                }
            }
        }
        
        return round($total, 2);
    }
    
    /**
     * 格式化价格显示
     * 
     * @param float $price 价格
     * @param int $decimals 小数位数
     * @return string 格式化后的价格
     */
    public static function formatPrice($price, $decimals = 2)
    {
        return number_format($price, $decimals, '.', '');
    }
    
    /**
     * 计算折扣价格
     * 
     * @param float $originalPrice 原价
     * @param float $discountRate 折扣率（0-1之间）
     * @return float 折扣后价格
     */
    public static function calculateDiscountPrice($originalPrice, $discountRate)
    {
        if ($discountRate <= 0 || $discountRate > 1) {
            return $originalPrice;
        }
        
        $discountedPrice = $originalPrice * $discountRate;
        return round($discountedPrice, 2);
    }
}
