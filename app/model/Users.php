<?php

namespace app\model;

use think\Model;

/**
 * 用户管理模型
 */
class Users extends Model
{
    protected $name = 'users';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 注意：不隐藏password字段，因为验证时需要访问
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 密码修改器
     */
    public function setPasswordAttr($value)
    {
        // 如果已经是哈希值（以$开头），直接返回
        if (empty($value) || strpos($value, '$') === 0) {
            return $value;
        }
        return password_hash($value, PASSWORD_DEFAULT);
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword($password)
    {
        // 需要获取原始密码（未隐藏的）
        $realPassword = $this->getData('password');
        return password_verify($password, $realPassword);
    }
}

