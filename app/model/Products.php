<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Products extends Model
{
    // 设置表名
    protected $name = 'products';
    
    // 设置主键
    protected $pk = 'id';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'        => 'string',
        'name'        => 'string',
        'icon'        => 'string',
        'product_type'=> 'int',
        'cost_price'  => 'float',
        'follow_price'=> 'int',
        'markup'      => 'float',
        'enabled'     => 'int',
        'is_default'  => 'int',
        'sort_order'  => 'int',
        'page_url'    => 'string',
        'notice_text' => 'string',
        'guide_enabled' => 'int',
        'guide_image'   => 'string',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 时间字段自动转换
    protected $type = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 字段默认值
    protected $default = [
        'cost_price'  => 0.00,
        'follow_price'=> 0,
        'markup'      => 0.00,
        'enabled'     => 1,
        'sort_order'  => 0,
        'guide_enabled' => 0,
        'guide_image' => '',
    ];
}
