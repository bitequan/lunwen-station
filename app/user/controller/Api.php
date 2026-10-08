<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cookie;
use think\facade\Cache;
use app\common\service\SocialLoginService;
use app\common\service\FrontendUserSession;
use app\common\service\UserSingleSessionService;

/**
 * 用户端 API 控制器
 */
class Api extends BaseController
{
    /**
     * 记住我 Cookie 名称
     * @var string
     */
    private const REMEMBER_COOKIE = 'user_remember';

    /**
     * 记住我有效期（7天）
     * @var int
     */
    private const REMEMBER_EXPIRE = 604800;

    /**
     * 是否关闭自助注册（仅允许快捷登录等新用户入口）
     */
    private function isSelfRegisterDisabled(): bool
    {
        try {
            $row = Db::name('system_config')
                ->where('config_key', 'security_config')
                ->field('config_value')
                ->find();
            if (!$row || empty($row['config_value'])) {
                return false;
            }
            $c = json_decode((string) $row['config_value'], true);

            return is_array($c) && !empty($c['disable_self_register']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取启用的商品列表（用户端）
     * 只返回 enabled = 1 的商品
     */
    public function getProducts()
    {
        try {
            // 只获取启用的商品，按排序字段排序
            $list = Db::name('products')
                ->where('enabled', 1)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();
            
            $list = $this->attachFinalPrice($list);
            $list = $this->sanitizeProductResponse($list);
            
            return json([
                'code' => 1,
                'msg'  => 'success',
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg'  => '获取商品列表失败：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 获取启用的工具类商品列表（用户端）
     * 只返回 enabled = 1 且 product_type = 2 的商品
     */
    public function getToolsProducts()
    {
        try {
            $list = Db::name('products')
                ->where('enabled', 1)
                ->where('product_type', 2)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();

            $list = $this->attachFinalPrice($list);
            $list = $this->sanitizeProductResponse($list);

            return json([
                'code' => 1,
                'msg'  => 'success',
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg'  => '获取工具类商品失败：' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 获取单个商品详情（用户端）
     */
    public function getProduct()
    {
        $id   = Request::get('id');
        $code = Request::get('code');
        
        if (empty($id) && empty($code)) {
            return json(['code' => 0, 'msg' => '商品ID或代码不能为空']);
        }
        
        try {
            $where = [];
            if ($id) {
                $where['id'] = $id;
            }
            if ($code) {
                $where['code'] = $code;
            }
            $where['enabled'] = 1;
            
            $product = Db::name('products')->where($where)->find();
            
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在或已禁用']);
            }

            $productList = $this->attachFinalPrice([$product]);
            $productList = $this->sanitizeProductResponse($productList);
            $product     = $productList[0];
            
            return json(['code' => 1, 'data' => $product]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取商品信息失败：' . $e->getMessage()]);
        }
    }

    /**
     * 批量计算商品最终价格
     *
     * @param array $list
     * @return array
     */
    private function attachFinalPrice(array $list): array
    {
        foreach ($list as &$product) {
            $price = floatval($product['cost_price'] ?? 0) + floatval($product['markup'] ?? 0);

            $product['price']       = number_format($price, 2, '.', '');
            $product['final_price'] = $price;
        }

        return $list;
    }

    /**
     * 移除用户端不应暴露的商品成本字段
     *
     * @param array $list
     * @return array
     */
    private function sanitizeProductResponse(array $list): array
    {
        foreach ($list as &$product) {
            unset($product['cost_price'], $product['follow_price'], $product['markup']);
        }

        return $list;
    }
    
    /**
     * 获取商品通知
     * GET /api/getProductNotice?code=商品代码
     */
    public function getProductNotice()
    {
        $code = Request::get('code');
        
        if (empty($code)) {
            return json(['code' => 0, 'msg' => '商品代码不能为空']);
        }
        
        try {
            // 获取商品信息，包括notice_text字段
            $product = Db::name('products')
                ->where('code', $code)
                ->where('enabled', 1) // 只获取启用的商品
                ->field('id, code, name, notice_text')
                ->find();
            
            if (!$product) {
                return json(['code' => 0, 'msg' => '商品不存在或已禁用']);
            }
            
            // 返回商品通知信息
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'id' => $product['id'],
                    'code' => $product['code'],
                    'name' => $product['name'],
                    'notice_text' => $product['notice_text'] ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取商品通知失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 检查邮箱验证码是否启用
     * GET /api/checkEmailVerify
     */
    public function checkEmailVerify()
    {
        try {
            // 从 system_config 表获取安全配置
            $config = \think\facade\Db::name('system_config')
                ->where('config_key', 'security_config')
                ->field('config_value')
                ->find();
            
            $enabled = false;
            
            if ($config && !empty($config['config_value'])) {
                $securityConfig = json_decode($config['config_value'], true);
                $enabled = isset($securityConfig['enable_email_verify']) ? (bool)$securityConfig['enable_email_verify'] : false;
            } else {
                // 如果找不到配置，尝试从旧的 security_config 表读取（兼容性）
                $oldConfig = \think\facade\Db::name('security_config')
                    ->where('id', 1)
                    ->field('enable_email_verify')
                    ->find();
                
                $enabled = isset($oldConfig['enable_email_verify']) ? (bool)$oldConfig['enable_email_verify'] : false;
            }
            
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => ['enabled' => $enabled]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '检查邮箱验证设置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 发送注册验证码
     * POST /api/sendRegisterCode
     */
    public function sendRegisterCode()
    {
        $email = input('post.email');
        
        if (empty($email)) {
            return json(['code' => 0, 'msg' => '邮箱地址不能为空']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '邮箱地址格式不正确']);
        }

        if ($this->isSelfRegisterDisabled()) {
            return json(['code' => 0, 'msg' => '当前已关闭自助注册，请使用快捷登录']);
        }
        
        try {
            // 检查邮箱是否已被注册
            $existingUser = \think\facade\Db::name('users')
                ->where('email', $email)
                ->find();
            
            if ($existingUser) {
                return json(['code' => 0, 'msg' => '该邮箱已被注册']);
            }
            
            // 检查是否在短时间内重复发送
            $recentCode = \think\facade\Db::name('email_verify_codes')
                ->where('email', $email)
                ->where('verify_type', 'register')
                ->where('status', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->order('create_time', 'desc')
                ->find();
            
            if ($recentCode) {
                $remaining = strtotime($recentCode['expire_time']) - time();
                if ($remaining > 300) { // 5分钟内不能重复发送
                    return json(['code' => 0, 'msg' => '验证码已发送，请稍后再试']);
                }
            }
            
            // 生成6位数字验证码
            $verifyCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // 获取验证码有效期
            $expireMinutes = 10; // 默认10分钟
            
            // 从 system_config 表获取安全配置
            $config = \think\facade\Db::name('system_config')
                ->where('config_key', 'security_config')
                ->field('config_value')
                ->find();
            
            if ($config && !empty($config['config_value'])) {
                $securityConfig = json_decode($config['config_value'], true);
                if (isset($securityConfig['email_verify_expire'])) {
                    $expireMinutes = (int)$securityConfig['email_verify_expire'];
                }
            } else {
                // 如果找不到JSON配置，尝试从旧的单独键读取
                $oldConfig = \think\facade\Db::name('system_config')
                    ->where('config_key', 'security_email_verify_expire')
                    ->field('config_value')
                    ->find();
                
                if ($oldConfig && isset($oldConfig['config_value'])) {
                    $expireMinutes = (int)$oldConfig['config_value'];
                } else {
                    // 最后尝试从旧的 security_config 表读取（兼容性）
                    $legacyConfig = \think\facade\Db::name('security_config')
                        ->where('id', 1)
                        ->field('email_verify_expire')
                        ->find();
                    
                    if ($legacyConfig && isset($legacyConfig['email_verify_expire'])) {
                        $expireMinutes = (int)$legacyConfig['email_verify_expire'];
                    }
                }
            }
            $expireTime = date('Y-m-d H:i:s', time() + $expireMinutes * 60);
            
            // 保存验证码到数据库
            $data = [
                'email' => $email,
                'verify_code' => $verifyCode,
                'verify_type' => 'register',
                'status' => 0,
                'expire_time' => $expireTime,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            \think\facade\Db::name('email_verify_codes')->insert($data);
            
            // 发送邮件（使用优化后的异步发送）
            $startTime = microtime(true);
            
            // 直接调用邮件服务，它会处理异步发送
            $sendResult = \app\common\MailService::sendVerifyCode($email, $verifyCode, 'register');
            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($sendResult) {
                return json(['code' => 1, 'msg' => '验证码已发送到您的邮箱']);
            } else {
                // 即使发送失败，也返回成功，但记录验证码到日志
                return json(['code' => 1, 'msg' => '验证码已发送到您的邮箱（如未收到请检查垃圾邮件）']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '发送验证码失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 用户注册
     * POST /api/register
     */
    public function register()
    {
        $username = input('post.username');
        $email = input('post.email');
        $password = input('post.password');
        $verifyCode = input('post.verify_code');
        
        // 基本验证
        if (empty($username) || empty($email) || empty($password)) {
            return json(['code' => 0, 'msg' => '请填写完整的注册信息']);
        }
        
        if (strlen($username) < 3 || strlen($username) > 20) {
            return json(['code' => 0, 'msg' => '用户名必须是3-20位字符']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '邮箱地址格式不正确']);
        }
        
        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少需要6位']);
        }

        if ($this->isSelfRegisterDisabled()) {
            return json(['code' => 0, 'msg' => '当前已关闭自助注册，请使用快捷登录']);
        }
        
        try {
            // 检查用户名是否已存在
            $existingUsername = \think\facade\Db::name('users')
                ->where('username', $username)
                ->find();
            
            if ($existingUsername) {
                return json(['code' => 0, 'msg' => '用户名已存在']);
            }
            
            // 检查邮箱是否已存在
            $existingEmail = \think\facade\Db::name('users')
                ->where('email', $email)
                ->find();
            
            if ($existingEmail) {
                return json(['code' => 0, 'msg' => '该邮箱已被注册']);
            }
            
            // 检查是否需要邮箱验证码
            $needEmailVerify = false;
            
            // 从 system_config 表获取安全配置
            $config = \think\facade\Db::name('system_config')
                ->where('config_key', 'security_config')
                ->field('config_value')
                ->find();
            
            if ($config && !empty($config['config_value'])) {
                $securityConfig = json_decode($config['config_value'], true);
                $needEmailVerify = isset($securityConfig['enable_email_verify']) ? (bool)$securityConfig['enable_email_verify'] : false;
            } else {
                // 如果找不到JSON配置，尝试从旧的单独键读取
                $oldConfig = \think\facade\Db::name('system_config')
                    ->where('config_key', 'security_enable_email_verify')
                    ->field('config_value')
                    ->find();
                
                if ($oldConfig && isset($oldConfig['config_value'])) {
                    $needEmailVerify = (bool)$oldConfig['config_value'];
                } else {
                    // 最后尝试从旧的 security_config 表读取（兼容性）
                    $legacyConfig = \think\facade\Db::name('security_config')
                        ->where('id', 1)
                        ->field('enable_email_verify')
                        ->find();
                    
                    if ($legacyConfig && isset($legacyConfig['enable_email_verify'])) {
                        $needEmailVerify = (bool)$legacyConfig['enable_email_verify'];
                    }
                }
            }
            
            if ($needEmailVerify) {
                if (empty($verifyCode)) {
                    return json(['code' => 0, 'msg' => '请输入邮箱验证码']);
                }
                
                // 验证验证码
                $validCode = \think\facade\Db::name('email_verify_codes')
                    ->where('email', $email)
                    ->where('verify_code', $verifyCode)
                    ->where('verify_type', 'register')
                    ->where('status', 0)
                    ->where('expire_time', '>', date('Y-m-d H:i:s'))
                    ->order('create_time', 'desc')
                    ->find();
                
                if (!$validCode) {
                    return json(['code' => 0, 'msg' => '验证码无效或已过期']);
                }
                
                // 标记验证码为已使用
                \think\facade\Db::name('email_verify_codes')
                    ->where('id', $validCode['id'])
                    ->update(['status' => 1, 'update_time' => date('Y-m-d H:i:s')]);
            }
            
            // 创建用户
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'nickname' => $username,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            $userId = \think\facade\Db::name('users')->insertGetId($userData);
            
            if ($userId) {
                return json([
                    'code' => 1,
                    'msg' => '注册成功',
                    'data' => [
                        'user' => [
                            'id' => $userId,
                            'username' => $username,
                            'nickname' => $username,
                            'email' => $email
                        ]
                    ]
                ]);
            } else {
                return json(['code' => 0, 'msg' => '注册失败，请稍后重试']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '注册失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 用户登录
     * POST /api/login
     */
    public function login()
    {
        $username = input('post.username');
        $password = input('post.password');
        $remember = input('post.remember', false);
        
        // 基本验证
        if (empty($username) || empty($password)) {
            return json(['code' => 0, 'msg' => '请输入用户名/邮箱和密码']);
        }
        
        try {
            // 查找用户（支持用户名或邮箱登录）
            $user = \think\facade\Db::name('users')
                ->where(function($query) use ($username) {
                    $query->where('username', $username)
                          ->whereOr('email', $username);
                })
                ->where('status', 1) // 只查找状态正常的用户
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在或已被禁用']);
            }
            
            // 验证密码
            if (!password_verify($password, $user['password'])) {
                return json(['code' => 0, 'msg' => '密码错误']);
            }
            
            FrontendUserSession::loginWithUserRow($user);

            // 记住我：写入签名 Cookie；未勾选则清理历史 Cookie
            if ($remember) {
                session('remember_me', true);
                $token = $this->buildRememberToken((int)$user['id']);
                Cookie::set(self::REMEMBER_COOKIE, $token, [
                    'expire' => self::REMEMBER_EXPIRE,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                session('remember_me', null);
                Cookie::delete(self::REMEMBER_COOKIE);
            }
            
            return json([
                'code' => 1,
                'msg' => '登录成功',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'nickname' => $user['nickname'] ?: $user['username'],
                        'email' => $user['email'],
                        'avatar' => $user['avatar']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '登录失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 用户退出登录
     * POST /api/logout
     */
    public function logout()
    {
        try {
            $logoutUid = (int) (session('user_id') ?? 0);
            // 清除用户会话
            session('user_id', null);
            session('user_info', null);
            session('remember_me', null);
            Cookie::delete(self::REMEMBER_COOKIE);
            UserSingleSessionService::onLogout($logoutUid);
            
            return json([
                'code' => 1,
                'msg' => '退出登录成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '退出登录失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取当前登录用户信息
     * GET /api/getUserInfo
     */
    public function getUserInfo()
    {
        try {
            $userId = session('user_id');
            if (!$userId) {
                $rememberUserId = $this->resolveRememberUserId();
                if ($rememberUserId > 0) {
                    $userId = $rememberUserId;
                }
            }
            
            if (!$userId) {
                return json([
                    'code' => 0,
                    'msg' => '用户未登录',
                    'data' => null
                ]);
            }
            
            $user = \think\facade\Db::name('users')
                ->where('id', $userId)
                ->where('status', 1)
                ->find();
            
            if (!$user) {
                // 清除无效的会话
                session('user_id', null);
                session('user_info', null);
                
                return json([
                    'code' => 0,
                    'msg' => '用户不存在或已被禁用',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'nickname' => $user['nickname'] ?: $user['username'],
                        'email' => $user['email'],
                        'avatar' => $user['avatar']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取用户信息失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 发送重置密码验证码
     * POST /api/sendResetPasswordCode
     */
    public function sendResetPasswordCode()
    {
        $email = input('post.email');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '请输入有效的邮箱地址']);
        }
        
        try {
            // 检查邮箱是否已注册
            $user = \think\facade\Db::name('users')
                ->where('email', $email)
                ->where('status', 1)
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '该邮箱未注册或账户已被禁用']);
            }
            
            // 检查是否在短时间内重复发送
            $recentCode = \think\facade\Db::name('email_verify_codes')
                ->where('email', $email)
                ->where('verify_type', 'reset_password')
                ->where('status', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->order('create_time', 'desc')
                ->find();
            
            if ($recentCode) {
                $sendTime = strtotime($recentCode['create_time']);
                $currentTime = time();
                $timeDiff = $currentTime - $sendTime;
                
                if ($timeDiff < 60) { // 60秒内不能重复发送
                    $waitTime = 60 - $timeDiff;
                    return json(['code' => 0, 'msg' => '请等待' . $waitTime . '秒后再发送验证码']);
                }
            }
            
            // 生成6位数字验证码
            $verifyCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expireTime = date('Y-m-d H:i:s', time() + 600); // 10分钟过期
            
            // 保存验证码到数据库
            $data = [
                'email' => $email,
                'verify_code' => $verifyCode,
                'verify_type' => 'reset_password',
                'status' => 0,
                'expire_time' => $expireTime,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            
            \think\facade\Db::name('email_verify_codes')->insert($data);
            
            // 发送验证码邮件
            $sendResult = \app\common\MailService::sendVerifyCode($email, $verifyCode, 'reset_password');
            
            if ($sendResult) {
                return json([
                    'code' => 1,
                    'msg' => '验证码已发送到您的邮箱，请查收'
                ]);
            } else {
                return json(['code' => 0, 'msg' => '验证码发送失败，请稍后重试']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '发送验证码失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 重置密码
     * POST /api/resetPassword
     */
    public function resetPassword()
    {
        $email = input('post.email');
        $verifyCode = input('post.verify_code');
        $newPassword = input('post.new_password');
        
        // 基本验证
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '请输入有效的邮箱地址']);
        }
        
        if (empty($verifyCode)) {
            return json(['code' => 0, 'msg' => '请输入验证码']);
        }
        
        if (empty($newPassword) || strlen($newPassword) < 6) {
            return json(['code' => 0, 'msg' => '新密码至少需要6位']);
        }

        // 防爆破：10 分钟窗口内验证码错误超过 5 次则锁定
        $attemptKey = 'rp_att_' . md5((string) $email);
        if ((int) \think\facade\Cache::get($attemptKey, 0) >= 5) {
            return json(['code' => 0, 'msg' => '错误次数过多，请10分钟后再试']);
        }
        
        try {
            // 验证验证码
            $validCode = \think\facade\Db::name('email_verify_codes')
                ->where('email', $email)
                ->where('verify_code', $verifyCode)
                ->where('verify_type', 'reset_password')
                ->where('status', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->order('create_time', 'desc')
                ->find();
            
            if (!$validCode) {
                // 记录一次错误尝试（600 秒滑动窗口）
                \think\facade\Cache::set($attemptKey, (int) \think\facade\Cache::get($attemptKey, 0) + 1, 600);
                return json(['code' => 0, 'msg' => '验证码无效或已过期']);
            }
            
            // 检查用户是否存在
            $user = \think\facade\Db::name('users')
                ->where('email', $email)
                ->where('status', 1)
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在或已被禁用']);
            }
            
            // 更新密码
            $updateData = [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            $result = \think\facade\Db::name('users')
                ->where('id', $user['id'])
                ->update($updateData);
            
            if ($result) {
                // 标记验证码为已使用
                \think\facade\Db::name('email_verify_codes')
                    ->where('id', $validCode['id'])
                    ->update(['status' => 1, 'update_time' => date('Y-m-d H:i:s')]);

                // 重置成功，清除防爆破计数
                \think\facade\Cache::delete($attemptKey);

                return json([
                    'code' => 1,
                    'msg' => '密码重置成功，请使用新密码登录'
                ]);
            } else {
                return json(['code' => 0, 'msg' => '密码重置失败，请稍后重试']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '重置密码失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 发送邮件（带超时控制和详细错误处理）
     * @param string $email 邮箱地址
     * @param string $verifyCode 验证码
     * @return array ['success' => bool, 'error' => string]
     */
    private function sendEmailWithTimeout($email, $verifyCode)
    {
        try {
            // 检查邮件服务是否可用
            if (!\app\common\MailService::isAvailable()) {
                return [
                    'success' => false,
                    'error' => '邮件服务未配置或未启用，请联系管理员'
                ];
            }
            
            // 设置执行时间限制（30秒超时）
            $maxExecutionTime = 30;
            $originalTimeLimit = ini_get('max_execution_time');
            set_time_limit($maxExecutionTime);
            
            // 记录开始时间
            $startTime = microtime(true);
            
            // 尝试发送邮件
            $sendResult = \app\common\MailService::sendVerifyCode($email, $verifyCode, 'register');
            
            // 计算耗时
            $elapsedTime = microtime(true) - $startTime;
            
            // 恢复原始时间限制
            if ($originalTimeLimit > 0) {
                set_time_limit($originalTimeLimit);
            }
            
            if ($sendResult) {
                return ['success' => true, 'error' => ''];
            } else {
                // 检查是否超时
                if ($elapsedTime >= $maxExecutionTime - 1) {
                    return [
                        'success' => false,
                        'error' => '邮件发送超时，请检查邮件服务器配置或稍后重试'
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => '邮件发送失败，请检查邮件服务器配置'
                ];
            }
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return [
                'success' => false,
                'error' => '邮件发送失败：' . $this->formatPHPMailerError($e)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => '邮件发送异常：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 格式化PHPMailer错误信息
     * @param \PHPMailer\PHPMailer\Exception $e
     * @return string
     */
    private function formatPHPMailerError($e)
    {
        $errorMsg = $e->getMessage();
        
        // 常见错误信息映射
        $errorMap = [
            'SMTP connect() failed' => '无法连接到邮件服务器，请检查SMTP配置',
            'SMTP authentication failed' => 'SMTP认证失败，请检查用户名和密码',
            'Invalid address' => '邮箱地址格式不正确',
            'Could not instantiate mail function' => '邮件功能未启用',
            'Connection: Timeout' => '连接超时，请检查网络或SMTP服务器',
            'Connection: Could not connect' => '无法连接到SMTP服务器',
        ];
        
        foreach ($errorMap as $key => $message) {
            if (stripos($errorMsg, $key) !== false) {
                return $message;
            }
        }
        
        return $errorMsg;
    }
    
    /**
     * 后台发送邮件（用于异步发送）
     * GET /api/sendEmailBackground
     * 这个接口由系统内部调用，不对外公开
     */
    public function sendEmailBackground()
    {
        try {
            // 获取最新的待发送验证码
            $pendingCode = \think\facade\Db::name('email_verify_codes')
                ->where('status', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->order('create_time', 'desc')
                ->find();
            
            if (!$pendingCode) {
                return json(['code' => 0, 'msg' => '没有待发送的验证码']);
            }
            
            // 发送邮件
            $sendResult = \app\common\MailService::sendVerifyCode(
                $pendingCode['email'],
                $pendingCode['verify_code'],
                $pendingCode['verify_type']
            );
            
            if ($sendResult) {
                return json(['code' => 1, 'msg' => '邮件发送成功']);
            } else {
                return json(['code' => 0, 'msg' => '邮件发送失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '邮件发送异常']);
        }
    }
    
    /**
     * 获取客服配置（用户端）
     * GET /user/api/getCustomerServiceConfig
     */
    public function getCustomerServiceConfig()
    {
        try {
            // 首先尝试从JSON配置中读取
            $jsonConfig = Db::name('system_config')
                ->where('config_key', 'customer_service_config')
                ->value('config_value');
            
            $config = [];
            
            if ($jsonConfig) {
                $config = json_decode($jsonConfig, true);
                if (!is_array($config)) {
                    $config = [];
                }
            }
            
            // 如果JSON配置不存在或解析失败，尝试从单独的配置项读取（向后兼容）
            if (empty($config)) {
                // 从数据库加载配置数据
                $systemConfigs = Db::name('system_config')
                    ->where('config_key', 'like', 'customer_service_%')
                    ->column('config_value', 'config_key');
                
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
                        $config[$key] = $systemConfigs[$fullKey];
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
            
            // 如果客服功能被禁用，返回空数据
            if (isset($config['enabled']) && $config['enabled'] == 0) {
                $config = [
                    'enabled' => 0,
                    'email' => '',
                    'phone' => '',
                    'qq' => '',
                    'wechat' => '',
                    'working_hours' => '',
                    'show_float_button' => 0
                ];
            }
            
            return json(['code' => 1, 'data' => $config]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取客服配置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取常见问题列表（用户端）
     * 只返回 status = 1 的常见问题，按排序字段排序
     */
    public function getFaqList()
    {
        try {
            // 只获取启用的常见问题，按排序字段排序
            $list = Db::name('faq')
                ->where('status', 1)
                ->order('sort_order asc, id desc')
                ->select()
                ->toArray();
            
            return json([
                'code' => 1,
                'msg'  => 'success',
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg'  => '获取常见问题列表失败：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 修改登录密码
     * POST /user/api/changePassword
     */
    public function changePassword()
    {
        $userId = session('user_id');
        if (!$userId) {
            return json(['code' => 0, 'msg' => '请先登录后再操作']);
        }

        $oldPassword = input('post.old_password');
        $newPassword = input('post.new_password');

        if (empty($oldPassword) || empty($newPassword)) {
            return json(['code' => 0, 'msg' => '原密码和新密码不能为空']);
        }

        if (strlen($newPassword) < 6) {
            return json(['code' => 0, 'msg' => '新密码至少需要6位字符']);
        }

        try {
            $user = Db::name('users')
                ->where('id', $userId)
                ->where('status', 1)
                ->find();

            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在或已被禁用']);
            }

            if (!password_verify($oldPassword, $user['password'])) {
                return json(['code' => 0, 'msg' => '原密码不正确']);
            }

            $updateData = [
                'password'    => password_hash($newPassword, PASSWORD_DEFAULT),
                'update_time' => date('Y-m-d H:i:s'),
            ];

            Db::name('users')->where('id', $userId)->update($updateData);

            return json(['code' => 1, 'msg' => '密码修改成功，请使用新密码重新登录']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '修改密码失败：' . $e->getMessage()]);
        }
    }

    /**
     * 发送修改邮箱验证码
     * POST /user/api/sendChangeEmailCode
     */
    public function sendChangeEmailCode()
    {
        $userId = session('user_id');
        if (!$userId) {
            return json(['code' => 0, 'msg' => '请先登录后再操作']);
        }

        $email = input('post.email');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '请输入有效的新邮箱地址']);
        }

        try {
            // 检查邮箱是否已被其它账号占用
            $existing = Db::name('users')
                ->where('email', $email)
                ->where('status', 1)
                ->where('id', '<>', $userId)
                ->find();

            if ($existing) {
                return json(['code' => 0, 'msg' => '该邮箱已被其它账户使用']);
            }

            // 生成验证码
            $verifyCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // 有效期，默认10分钟，可复用安全配置
            $expireMinutes = 10;
            $config        = Db::name('system_config')
                ->where('config_key', 'security_config')
                ->value('config_value');

            if ($config) {
                $securityConfig = json_decode($config, true);
                if (isset($securityConfig['email_verify_expire'])) {
                    $expireMinutes = (int)$securityConfig['email_verify_expire'];
                }
            }

            $expireTime = date('Y-m-d H:i:s', time() + $expireMinutes * 60);

            Db::name('email_verify_codes')->insert([
                'email'       => $email,
                'verify_code' => $verifyCode,
                'verify_type' => 'change_email',
                'status'      => 0,
                'expire_time' => $expireTime,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            $sendResult = \app\common\MailService::sendVerifyCode($email, $verifyCode, 'change_email');

            if ($sendResult) {
                return json(['code' => 1, 'msg' => '验证码已发送到新邮箱，请查收']);
            }

            return json(['code' => 0, 'msg' => '验证码发送失败，请稍后重试']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '发送验证码失败：' . $e->getMessage()]);
        }
    }

    /**
     * 修改邮箱
     * POST /user/api/updateEmail
     */
    public function updateEmail()
    {
        $userId = session('user_id');
        if (!$userId) {
            return json(['code' => 0, 'msg' => '请先登录后再操作']);
        }

        $email      = input('post.email');
        $verifyCode = input('post.verify_code');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 0, 'msg' => '请输入有效的新邮箱地址']);
        }

        if (empty($verifyCode)) {
            return json(['code' => 0, 'msg' => '请输入邮箱验证码']);
        }

        try {
            // 检查验证码
            $codeRow = Db::name('email_verify_codes')
                ->where('email', $email)
                ->where('verify_code', $verifyCode)
                ->where('verify_type', 'change_email')
                ->where('status', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->order('create_time', 'desc')
                ->find();

            if (!$codeRow) {
                return json(['code' => 0, 'msg' => '验证码无效或已过期']);
            }

            // 再次确认邮箱未被占用
            $existing = Db::name('users')
                ->where('email', $email)
                ->where('status', 1)
                ->where('id', '<>', $userId)
                ->find();

            if ($existing) {
                return json(['code' => 0, 'msg' => '该邮箱已被其它账户使用']);
            }

            Db::name('users')
                ->where('id', $userId)
                ->update([
                    'email'       => $email,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 标记验证码已使用
            Db::name('email_verify_codes')
                ->where('id', $codeRow['id'])
                ->update([
                    'status'      => 1,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 更新会话中的用户邮箱信息
            $sessionUser = session('user_info');
            if (is_array($sessionUser)) {
                $sessionUser['email'] = $email;
                session('user_info', $sessionUser);
            }

            return json(['code' => 1, 'msg' => '邮箱修改成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '修改邮箱失败：' . $e->getMessage()]);
        }
    }

    /**
     * 修改手机号
     * POST /user/api/updatePhone
     */
    public function updatePhone()
    {
        $userId = session('user_id');
        if (!$userId) {
            return json(['code' => 0, 'msg' => '请先登录后再操作']);
        }

        $phone = input('post.phone');

        if (empty($phone)) {
            return json(['code' => 0, 'msg' => '请输入手机号']);
        }

        // 简单的手机号格式校验（大陆手机号码）
        if (!preg_match('/^1[3-9]\d{9}$/', (string)$phone)) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }

        try {
            Db::name('users')
                ->where('id', $userId)
                ->update([
                    'phone'       => $phone,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            return json(['code' => 1, 'msg' => '手机号修改成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '修改手机号失败：' . $e->getMessage()]);
        }
    }

    /**
     * 统一更新安全设置
     * POST /user/api/updateSecuritySettings
     */
    public function updateSecuritySettings()
    {
        $userId = session('user_id');
        if (!$userId) {
            return json(['code' => 0, 'msg' => '请先登录后再操作']);
        }

        $oldPassword = input('post.old_password');
        $newPassword = input('post.new_password');
        $email = input('post.email');
        $verifyCode = input('post.verify_code');
        $newUsername = trim((string) (input('post.new_username') ?? ''));

        // 检查是否有需要更新的内容
        $hasPasswordUpdate = !empty($oldPassword) && !empty($newPassword);
        $hasEmailUpdate = !empty($email) && !empty($verifyCode);
        $hasUsernameUpdate = $newUsername !== '';

        if (!$hasPasswordUpdate && !$hasEmailUpdate && !$hasUsernameUpdate) {
            return json(['code' => 0, 'msg' => '没有需要更新的设置']);
        }

        try {
            $user = Db::name('users')
                ->where('id', $userId)
                ->where('status', 1)
                ->find();

            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在或已被禁用']);
            }

            $updateData = [];
            $messages = [];

            // 处理密码更新
            if ($hasPasswordUpdate) {
                if (strlen($newPassword) < 6) {
                    return json(['code' => 0, 'msg' => '新密码至少需要6位字符']);
                }

                if (!password_verify($oldPassword, $user['password'])) {
                    return json(['code' => 0, 'msg' => '原密码不正确']);
                }

                if ($oldPassword === $newPassword) {
                    return json(['code' => 0, 'msg' => '新密码不能和当前密码相同']);
                }

                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $messages[] = '密码修改成功';
            }

            // 处理邮箱更新
            if ($hasEmailUpdate) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return json(['code' => 0, 'msg' => '请输入有效的新邮箱地址']);
                }

                // 检查验证码
                $codeRow = Db::name('email_verify_codes')
                    ->where('email', $email)
                    ->where('verify_code', $verifyCode)
                    ->where('verify_type', 'change_email')
                    ->where('status', 0)
                    ->where('expire_time', '>', date('Y-m-d H:i:s'))
                    ->order('create_time', 'desc')
                    ->find();

                if (!$codeRow) {
                    return json(['code' => 0, 'msg' => '验证码无效或已过期']);
                }

                // 检查邮箱是否已被其它账号占用
                $existing = Db::name('users')
                    ->where('email', $email)
                    ->where('status', 1)
                    ->where('id', '<>', $userId)
                    ->find();

                if ($existing) {
                    return json(['code' => 0, 'msg' => '该邮箱已被其它账户使用']);
                }

                $updateData['email'] = $email;
                $messages[] = '邮箱修改成功';

                // 标记验证码已使用
                Db::name('email_verify_codes')
                    ->where('id', $codeRow['id'])
                    ->update([
                        'status' => 1,
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);

                // 更新会话中的用户邮箱信息
                $sessionUser = session('user_info');
                if (is_array($sessionUser)) {
                    $sessionUser['email'] = $email;
                    session('user_info', $sessionUser);
                }
            }

            // 处理用户名修改（与注册规则一致：3-20 位字母或数字）
            if ($hasUsernameUpdate) {
                if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                    return json(['code' => 0, 'msg' => '用户名必须是3-20位字符']);
                }
                if (!preg_match('/^[a-zA-Z0-9]+$/', $newUsername)) {
                    return json(['code' => 0, 'msg' => '用户名只能包含字母和数字']);
                }
                if ($newUsername === (string) $user['username']) {
                    return json(['code' => 0, 'msg' => '新用户名与当前用户名相同']);
                }

                $nameTaken = Db::name('users')
                    ->where('username', $newUsername)
                    ->where('id', '<>', $userId)
                    ->find();
                if ($nameTaken) {
                    return json(['code' => 0, 'msg' => '该用户名已被占用']);
                }

                $updateData['username'] = $newUsername;
                $messages[] = '用户名修改成功';
            }

            // 如果有更新数据，执行更新
            if (!empty($updateData)) {
                $updateData['update_time'] = date('Y-m-d H:i:s');
                Db::name('users')->where('id', $userId)->update($updateData);

                $fresh = Db::name('users')
                    ->where('id', $userId)
                    ->where('status', 1)
                    ->find();

                if ($fresh) {
                    session('user_info', [
                        'id' => (int) $fresh['id'],
                        'username' => $fresh['username'],
                        'nickname' => $fresh['nickname'] ?: $fresh['username'],
                        'email' => $fresh['email'],
                        'avatar' => $fresh['avatar'] ?? '',
                    ]);
                }

                $response = [
                    'code' => 1,
                    'msg' => implode('，', $messages),
                    'data' => [],
                ];

                if ($hasEmailUpdate) {
                    $response['data']['email'] = $email;
                }
                if ($fresh && ($hasUsernameUpdate || $hasEmailUpdate || $hasPasswordUpdate)) {
                    $response['data']['user'] = [
                        'username' => $fresh['username'],
                        'nickname' => $fresh['nickname'] ?: $fresh['username'],
                        'email' => $fresh['email'],
                        'avatar' => $fresh['avatar'] ?? '',
                    ];
                }

                return json($response);
            }

            return json(['code' => 0, 'msg' => '没有需要更新的设置']);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新设置失败：' . $e->getMessage()]);
        }
    }

    /**
     * 快捷登录：前台可展示的渠道（不含密钥）
     * GET /api/socialLoginConfig
     */
    public function socialLoginConfig()
    {
        try {
            $data = SocialLoginService::publicConfig();
            $data['disable_self_register'] = $this->isSelfRegisterDisabled();

            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => ['enabled' => false, 'types' => [], 'wx_open' => ['enabled' => false, 'appid' => ''], 'wx_mp' => ['enabled' => false, 'appid' => ''], 'disable_self_register' => false]]);
        }
    }

    /**
     * 微信开放平台扫码（WxLogin）初始化：下发 appid、redirect_uri、state，并写入 session
     * GET /user/api/socialWxOpenPrepare
     */
    public function socialWxOpenPrepare()
    {
        try {
            if (!SocialLoginService::isWxOpenEnabled()) {
                return json(['code' => 0, 'msg' => '微信扫码登录未启用', 'data' => null]);
            }
            $state = bin2hex(random_bytes(16));
            session('social_oauth_type', 'wx_open');
            session('social_oauth_state', $state);

            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'appid' => SocialLoginService::wxOpenAppId(),
                    'redirect_uri' => SocialLoginService::wxOpenRedirectUri(),
                    'state' => $state,
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 微信服务号：带参临时二维码（扫码关注或已关注用户扫码）+ 服务器事件完成登录
     * GET /user/api/socialWxMpPrepare
     */
    public function socialWxMpPrepare()
    {
        try {
            if (!SocialLoginService::isWxMpEnabled()) {
                return json(['code' => 0, 'msg' => '微信服务号登录未启用', 'data' => null]);
            }
            if (SocialLoginService::wxMpToken() === '') {
                return json(['code' => 0, 'msg' => '请先在后台「快捷登录对接」填写服务号消息服务器 Token，并完成公众平台服务器配置', 'data' => null]);
            }
            $ticket = bin2hex(random_bytes(16));
            $ttl = SocialLoginService::WXMP_TICKET_TTL;
            Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, ['status' => 'pending'], $ttl);

            $wechatQr = SocialLoginService::createWxMpStrSceneQr($ticket, $ttl);
            $qrUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . rawurlencode($wechatQr['ticket']);

            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'ticket' => $ticket,
                    'qr_url' => $qrUrl,
                    'appid' => SocialLoginService::wxMpAppId(),
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * PC 端轮询：手机是否已完成授权
     * GET /user/api/socialWxMpPoll?ticket=
     */
    public function socialWxMpPoll()
    {
        try {
            $ticket = trim((string) input('get.ticket', ''));
            if ($ticket === '') {
                return json(['code' => 0, 'msg' => '参数无效', 'data' => null]);
            }
            $row = Cache::get(SocialLoginService::CACHE_WXMP_POLL . $ticket);
            if (!is_array($row)) {
                return json(['code' => 0, 'msg' => '会话已过期，请重新打开二维码', 'data' => null]);
            }
            $status = (string) ($row['status'] ?? 'pending');
            if ($status === 'success') {
                return json([
                    'code' => 1,
                    'msg' => 'success',
                    'data' => ['status' => 'success'],
                ]);
            }
            if ($status === 'failed') {
                return json([
                    'code' => 0,
                    'msg' => (string) ($row['msg'] ?? '授权失败'),
                    'data' => ['status' => 'failed'],
                ]);
            }

            return json(['code' => 1, 'msg' => 'success', 'data' => ['status' => 'pending']]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * PC 端确认登录：将手机端已完成的用户写入当前浏览器 session
     * POST /user/api/socialWxMpConfirm  body: {"ticket":"..."}
     */
    public function socialWxMpConfirm()
    {
        try {
            $body = json_decode((string) request()->getContent(), true);
            $ticket = '';
            if (is_array($body) && !empty($body['ticket'])) {
                $ticket = trim((string) $body['ticket']);
            }
            if ($ticket === '') {
                $ticket = trim((string) input('post.ticket', ''));
            }
            if ($ticket === '') {
                return json(['code' => 0, 'msg' => '参数无效', 'data' => null]);
            }
            $row = Cache::get(SocialLoginService::CACHE_WXMP_POLL . $ticket);
            if (!is_array($row) || ($row['status'] ?? '') !== 'success' || empty($row['user_id'])) {
                return json(['code' => 0, 'msg' => '登录未完成或已失效', 'data' => null]);
            }
            $userId = (int) $row['user_id'];
            $user = Db::name('users')->where('id', $userId)->find();
            if (!$user || (int) $user['status'] !== 1) {
                Cache::delete(SocialLoginService::CACHE_WXMP_POLL . $ticket);

                return json(['code' => 0, 'msg' => '账号不可用', 'data' => null]);
            }
            FrontendUserSession::loginWithUserRow($user);
            Cache::delete(SocialLoginService::CACHE_WXMP_POLL . $ticket);

            return json(['code' => 1, 'msg' => 'success', 'data' => null]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 构建记住我 token（userId|expire|sign）
     */
    private function buildRememberToken(int $userId): string
    {
        $expire = time() + self::REMEMBER_EXPIRE;
        $data = $userId . '|' . $expire;
        $sign = hash_hmac('sha256', $data, $this->getRememberSecret());
        return base64_encode($data . '|' . $sign);
    }

    /**
     * 从 remember cookie 解析用户ID，失败返回0
     */
    private function resolveRememberUserId(): int
    {
        $token = Cookie::get(self::REMEMBER_COOKIE);
        if (empty($token)) {
            return 0;
        }

        $decoded = base64_decode((string)$token, true);
        if ($decoded === false) {
            return 0;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return 0;
        }

        [$userIdRaw, $expireRaw, $sign] = $parts;
        $userId = (int)$userIdRaw;
        $expire = (int)$expireRaw;
        if ($userId <= 0 || $expire < time()) {
            return 0;
        }

        $data = $userId . '|' . $expire;
        $expectedSign = hash_hmac('sha256', $data, $this->getRememberSecret());
        if (!hash_equals($expectedSign, (string)$sign)) {
            return 0;
        }

        $user = Db::name('users')
            ->where('id', $userId)
            ->where('status', 1)
            ->find();

        if (!$user) {
            return 0;
        }

        // 自动恢复会话
        session('user_id', $user['id']);
        session('user_info', [
            'id' => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'] ?: $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar']
        ]);
        session('remember_me', true);

        // 刷新 cookie 过期时间（滑动续期）
        $newToken = $this->buildRememberToken((int)$user['id']);
        Cookie::set(self::REMEMBER_COOKIE, $newToken, [
            'expire' => self::REMEMBER_EXPIRE,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        UserSingleSessionService::onLogin((int) $user['id']);

        return (int)$user['id'];
    }

    /**
     * 记住我签名密钥
     */
    private function getRememberSecret(): string
    {
        return (string)(config('app.app_key') ?: config('app.key') ?: 'user-remember-secret');
    }
}
