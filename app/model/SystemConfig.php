<?php

namespace app\model;

use think\Model;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    // 设置完整表名
    protected $name = 'system_config';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
    ];
    
    /**
     * 获取配置值
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getValue($key, $default = null)
    {
        $config = self::where('config_key', $key)->find();
        if ($config) {
            return $config->config_value;
        }
        return $default;
    }
    
    /**
     * 设置配置值
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @param string $desc 配置说明
     * @param string $type 配置类型
     * @return bool
     */
    public static function setValue($key, $value, $desc = '', $type = 'string')
    {
        $config = self::where('config_key', $key)->find();
        
        if ($config) {
            // 更新现有配置
            $config->config_value = $value;
            $config->config_desc = $desc;
            $config->config_type = $type;
            return $config->save();
        } else {
            // 创建新配置
            $config = new self();
            $config->config_key = $key;
            $config->config_value = $value;
            $config->config_desc = $desc;
            $config->config_type = $type;
            return $config->save();
        }
    }
    
    /**
     * 批量获取配置
     * @param array $keys 配置键名数组
     * @return array
     */
    public static function getValues($keys)
    {
        $configs = self::whereIn('config_key', $keys)->select();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->config_key] = $config->config_value;
        }
        
        // 为不存在的键设置默认值
        foreach ($keys as $key) {
            if (!isset($result[$key])) {
                $result[$key] = null;
            }
        }
        
        return $result;
    }
    
    /**
     * 批量设置配置
     * @param array $data 配置数据数组 [key => value]
     * @return bool
     */
    public static function setValues($data)
    {
        foreach ($data as $key => $value) {
            self::setValue($key, $value);
        }
        return true;
    }
    
    /**
     * 删除配置
     * @param string $key 配置键名
     * @return bool
     */
    public static function deleteValue($key)
    {
        return self::where('config_key', $key)->delete();
    }
    
    /**
     * 获取所有配置
     * @return array
     */
    public static function getAll()
    {
        $configs = self::select();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->config_key] = [
                'value' => $config->config_value,
                'desc' => $config->config_desc,
                'type' => $config->config_type,
                'id' => $config->id
            ];
        }
        
        return $result;
    }
}

