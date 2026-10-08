<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use think\facade\Cache;
use app\common\MailService;
use app\common\service\JsonService;
use app\common\service\SocialLoginService;
use app\common\service\UserTokenService;

/**
 * 用户端新版登录/注册接口（规划阶段1·基础闭环）
 *
 * 接口路径：/api/login/{action}
 * 统一返回格式：{ code, show, msg, data }（见 JsonService）
 * 登录成功 data 结构：{ nickname, mobile, avatar, token }
 */
class V2Login
{
    private const TERMINAL_PC = 4;

    /** 注册|登录 流程临时数据缓存前缀 */
    private const REG_TOK_PREFIX = 'v2_regtok_';
    private const CODE_PREFIX    = 'v2_vcode_';
    private const CODE_TTL       = 600; // 验证码有效期 10 分钟
    private const REG_TOK_TTL    = 1800; // 注册流程令牌有效期 30 分钟

    /**
     * 账号密码登录 / 邮箱验证码登录
     * 入参：account, password, scene(1=密码,4=邮箱验证码), code(验证码), terminal
     */
    public function account(): \think\response\Json
    {
        $account = trim((string) $this->param('account', ''));
        $scene = (int) $this->param('scene', 1);
        $terminal = (int) $this->param('terminal', self::TERMINAL_PC);
        if ($account === '') {
            return JsonService::fail('请输入账号');
        }

        // 邮箱验证码登录
        if ($scene === 4) {
            $code = trim((string) $this->param('code', ''));
            if ($code === '' || !$this->verifyCode('login', $account, $code)) {
                return JsonService::fail('验证码错误或已过期');
            }
            $user = $this->findUserByAccount($account);
            $this->consumeCode('login', $account);
        } else {
            $password = (string) $this->param('password', '');
            if ($password === '') {
                return JsonService::fail('请输入密码');
            }
            $user = $this->findUserByAccount($account);
            if (!$user) {
                return JsonService::fail('账号不存在');
            }
            if (!password_verify($password, (string) $user['password'])) {
                return JsonService::fail('账号或密码错误');
            }
        }

        if (!$user) {
            return JsonService::fail('账号不存在');
        }
        if ((int) $user['status'] !== 1) {
            return JsonService::fail('该账号已被禁用，请联系客服');
        }

        return $this->loginSuccess($user, $terminal);
    }

    /**
     * 一步注册（新用户，账号密码）
     * 入参：account, password, invite_code(可选), terminal
     */
    public function register(): \think\response\Json
    {
        $account = trim((string) $this->param('account', ''));
        $password = (string) $this->param('password', '');
        $terminal = (int) $this->param('terminal', self::TERMINAL_PC);

        if ($account === '') {
            return JsonService::fail('请输入账号');
        }
        if (strlen($password) < 6) {
            return JsonService::fail('密码不能少于6位');
        }
        if ($this->findUserByAccount($account, true)) {
            return JsonService::fail('该账号已被注册');
        }
        if (filter_var($account, FILTER_VALIDATE_EMAIL) === false && !preg_match('/^1\d{10}$/', $account) && preg_match('/[^a-zA-Z0-9_]/', $account)) {
            return JsonService::fail('账号格式不正确');
        }

        $now = date('Y-m-d H:i:s');
        $userData = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => '用户' . mt_rand(100000, 999999),
            'email'    => filter_var($account, FILTER_VALIDATE_EMAIL) !== false ? $account : '',
            'phone'    => preg_match('/^1\d{10}$/', $account) ? $account : '',
            'username' => filter_var($account, FILTER_VALIDATE_EMAIL) === false && !preg_match('/^1\d{10}$/', $account) ? $account : '',
            'status'   => 1,
            'create_time' => $now,
            'update_time' => $now,
        ];
        if ($userData['email'] === '' && $userData['username'] === '' && $userData['phone'] === '') {
            $userData['username'] = $account;
        }

        $userId = Db::name('users')->insertGetId($userData);
        if (!$userId) {
            return JsonService::fail('注册失败，请稍后重试');
        }
        $userData['id'] = $userId;
        return $this->loginSuccess($userData, $terminal);
    }

    /**
     * 获取注册/登录配置（登录页与注册页初始化）
     */
    public function registerConfig(): \think\response\Json
    {
        return JsonService::data([
            'login_way'        => ['password', 'email'],
            'coerce_mobile'    => 0,
            'coerce_email'     => 0,
            'login_agreement'  => 1,
            'third_auth'       => 0,
            // 微信开放平台扫码登录开关：随后台「快捷登录对接」动态返回，前端据此隐藏微信扫码 Tab
            'wechat_auth'      => SocialLoginService::isWxOpenEnabled() ? 1 : 0,
            'qq_auth'          => 0,
            'single_login'     => 1,
            'register_enabled' => 1,
            'default_active'   => 'account',
        ]);
    }

    /**
     * 微信开放平台扫码登录：给前端 WxLogin SDK 渲染二维码的配置
     * 返回 appid/scope/redirect_uri/state，前端据此拉起 qrconnect 内嵌二维码；
     * state 与现有 oauthStart 一致写入 session，供 /social/oauth/callback 校验。
     */
    public function scanWxLoginUrl(): \think\response\Json
    {
        if (!SocialLoginService::isWxOpenEnabled()) {
            return JsonService::fail('微信扫码登录未启用或未配置完整');
        }
        $state = bin2hex(random_bytes(16));
        session('social_oauth_type', 'wx_open');
        session('social_oauth_state', $state);

        return JsonService::data([
            'appid'        => SocialLoginService::wxOpenAppId(),
            'scope'        => 'snsapi_login',
            'redirect_uri' => SocialLoginService::wxOpenRedirectUri(),
            'state'        => $state,
        ]);
    }

    /**
     * 用户名可用性检查
     */
    public function checkUsername(): \think\response\Json
    {
        $username = trim((string) $this->param('username', ''));
        if ($username === '') {
            return JsonService::data(['available' => false, 'msg' => '请输入用户名']);
        }
        $exists = Db::name('users')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->whereOr('email', $username)->whereOr('phone', $username);
            })
            ->where('status', 1)
            ->find();
        return JsonService::data([
            'available' => !$exists,
            'msg' => $exists ? '该用户名已被占用' : '用户名可用',
        ]);
    }

    /**
     * 注册第一步：预校验并生成注册令牌
     * 入参：account_type(email|mobile), account, password, invite_code, terminal
     */
    public function registerPreCheck(): \think\response\Json
    {
        $accountType = (string) $this->param('account_type', 'email');
        $account = trim((string) $this->param('account', ''));
        $password = (string) $this->param('password', '');
        $terminal = (int) $this->param('terminal', self::TERMINAL_PC);

        if ($account === '') {
            return JsonService::fail('请输入账号');
        }
        if (strlen($password) < 6) {
            return JsonService::fail('密码不能少于6位');
        }
        if ($accountType === 'email' && filter_var($account, FILTER_VALIDATE_EMAIL) === false) {
            return JsonService::fail('邮箱格式不正确');
        }
        if ($accountType === 'mobile' && !preg_match('/^1\d{10}$/', $account)) {
            return JsonService::fail('手机号格式不正确');
        }
        if ($this->findUserByAccount($account, true)) {
            return JsonService::fail('该账号已被注册');
        }

        $registerToken = md5($account . $accountType . time() . mt_rand(100000, 999999));
        Cache::set(
            self::REG_TOK_PREFIX . $registerToken,
            ['account_type' => $accountType, 'account' => $account, 'password' => $password, 'terminal' => $terminal],
            self::REG_TOK_TTL
        );

        return JsonService::data([
            'register_token' => $registerToken,
            'account_type' => $accountType,
            'account' => $account,
            'expire_at' => date('Y-m-d H:i:s', time() + self::REG_TOK_TTL),
        ]);
    }

    /**
     * 注册第二步：发送验证码
     * 入参：account_type, account, scene
     */
    public function registerSendCode(): \think\response\Json
    {
        $accountType = (string) $this->param('account_type', 'email');
        $account = trim((string) $this->param('account', ''));

        if ($accountType === 'email') {
            return $this->sendMailCode('register', $account);
        }
        if ($accountType === 'mobile') {
            return JsonService::fail('短信验证码暂未开放，请使用邮箱验证码');
        }
        return JsonService::fail('不支持的验证方式');
    }

    /**
     * 注册完成：校验验证码并创建账号
     * 入参：register_token, code, username(可选)
     */
    public function registerComplete(): \think\response\Json
    {
        $registerToken = trim((string) $this->param('register_token', ''));
        $code = trim((string) $this->param('code', ''));
        $username = trim((string) $this->param('username', ''));
        if ($registerToken === '') {
            return JsonService::fail('注册令牌缺失，请重新开始');
        }

        $reg = Cache::get(self::REG_TOK_PREFIX . $registerToken);
        if (!is_array($reg) || empty($reg['account'])) {
            return JsonService::fail('注册流程已失效，请重新开始');
        }
        if ($code === '' || !$this->verifyCode('register', $reg['account'], $code)) {
            return JsonService::fail('验证码错误或已过期');
        }
        $this->consumeCode('register', $reg['account']);

        $account = $reg['account'];
        if ($this->findUserByAccount($account, true)) {
            return JsonService::fail('该账号已被注册');
        }
        $now = date('Y-m-d H:i:s');
        $email = $reg['account_type'] === 'email' ? $account : ($username && filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : '');
        $phone = $reg['account_type'] === 'mobile' ? $account : '';
        $userName = $username !== '' && $email === '' ? $username : '';

        $userData = [
            'password' => password_hash((string) $reg['password'], PASSWORD_DEFAULT),
            'nickname' => $username !== '' ? $username : ('用户' . mt_rand(100000, 999999)),
            'email'    => $email,
            'phone'    => $phone,
            'username' => $userName,
            'status'   => 1,
            'create_time' => $now,
            'update_time' => $now,
        ];
        $userId = Db::name('users')->insertGetId($userData);
        if (!$userId) {
            return JsonService::fail('注册失败，请稍后重试');
        }
        Cache::delete(self::REG_TOK_PREFIX . $registerToken);
        $userData['id'] = $userId;
        return $this->loginSuccess($userData, (int) ($reg['terminal'] ?? self::TERMINAL_PC));
    }

    /**
     * 发送邮箱登录验证码（已注册邮箱）
     */
    public function sendEmailCode(): \think\response\Json
    {
        $account = trim((string) $this->param('account', ''));
        return $this->sendMailCode('login', $account);
    }

    /**
     * 退出登录
     */
    public function logout(): \think\response\Json
    {
        UserTokenService::destroyToken(UserTokenService::readRequestToken());
        return JsonService::success('退出成功', []);
    }

    /* ==================== 内部工具 ==================== */

    /**
     * 读取请求参数：兼容表单（application/x-www-form-urlencoded）与 JSON body。
     * TP6 在部分环境下不会把 JSON 请求体自动并入 $this->param()，这里手动解析 php://input 兜底。
     */
    private static $jsonBody = null;

    private function param(string $key, $default = '')
    {
        $form = input($key);
        if ($form !== '' && $form !== null) {
            return $form;
        }
        if (self::$jsonBody === null) {
            self::$jsonBody = [];
            $raw = (string) file_get_contents('php://input');
            if ($raw !== '' && strpos(ltrim($raw), '{') === 0) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    self::$jsonBody = $decoded;
                }
            }
        }
        return self::$jsonBody[$key] ?? $default;
    }

    /**
     * 按用户名/邮箱/手机号查找用户；$force 时含被禁用账号（用于注册去重）
     */
    private function findUserByAccount(string $account, bool $force = false): ?array
    {
        $query = Db::name('users')
            ->where(function ($q) use ($account) {
                $q->where('username', $account)
                    ->whereOr('email', $account)
                    ->whereOr('phone', $account);
            });
        if (!$force) {
            $query->where('status', 1);
        }
        return $query->find();
    }

    /**
     * 登录成功统一处理：更新登录信息 + 生成 token + 返回标准结构
     */
    private function loginSuccess(array $user, int $terminal): \think\response\Json
    {
        $now = date('Y-m-d H:i:s');
        Db::name('users')->where('id', (int) $user['id'])->update([
            'last_login_time' => $now,
            'last_login_ip' => request()->ip(),
            'update_time' => $now,
        ]);
        $this->writeLoginLog((int) $user['id'], 'account', $terminal);

        $token = UserTokenService::createToken((int) $user['id'], $terminal);
        return JsonService::data([
            'nickname' => $user['nickname'] ?: $user['username'],
            'mobile'   => $user['phone'] ?? '',
            'avatar'   => $user['avatar'] ?? '',
            'token'    => $token,
        ]);
    }

    /**
     * 写入登录日志（表不存在时静默跳过）
     */
    private function writeLoginLog(int $userId, string $way, int $terminal): void
    {
        try {
            Db::name('user_login_log')->insert([
                'user_id' => $userId,
                'login_time' => time(),
                'login_ip' => request()->ip(),
                'login_way' => $way,
                'terminal' => $terminal,
                'status' => 1,
                'user_agent' => substr((string) request()->header('user-agent', ''), 0, 500),
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 表未创建时静默
        }
    }

    /**
     * 发送邮箱验证码
     */
    private function sendMailCode(string $scene, string $email): \think\response\Json
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return JsonService::fail('邮箱格式不正确');
        }
        // 限频：同一邮箱 60 秒内只允许发送一次，防邮件轰炸
        $rlKey = 'mail_rl_' . md5($scene . '_' . $email);
        if (Cache::get($rlKey)) {
            return JsonService::fail('验证码发送过于频繁，请稍后再试');
        }
        $type = $scene === 'login' ? 'reset_password' : 'register';
        $code = (string) mt_rand(100000, 999999);
        Cache::set(self::CODE_PREFIX . $scene . '_' . $email, $code, self::CODE_TTL);
        Cache::set($rlKey, 1, 60);
        MailService::sendVerifyCode($email, $code, $type);
        return JsonService::success('验证码已发送', []);
    }

    private function verifyCode(string $scene, string $account, string $code): bool
    {
        $saved = Cache::get(self::CODE_PREFIX . $scene . '_' . $account);
        return $saved !== null && ((string) $saved) === $code;
    }

    private function consumeCode(string $scene, string $account): void
    {
        Cache::delete(self::CODE_PREFIX . $scene . '_' . $account);
    }
}