<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use app\common\service\JsonService;
use app\common\service\UserTokenService;

/**
 * 用户中心新版接口（规划阶段1·基础闭环）
 * 接口路径：/api/user/{action}
 * 统一返回格式：{ code, show, msg, data }
 */
class V2User
{
    /**
     * 当前登录用户信息
     * 需要请求头 token；登录态失效返回 code=-1
     */
    public function info(): \think\response\Json
    {
        $userId = UserTokenService::getUserId(UserTokenService::readRequestToken());
        if (!$userId) {
            return JsonService::authExpired();
        }
        $user = Db::name('users')->where('id', $userId)->find();
        if (!$user) {
            UserTokenService::destroyToken(UserTokenService::readRequestToken());
            return JsonService::authExpired('账号不存在');
        }
        if ((int) $user['status'] !== 1) {
            return JsonService::fail('该账号已被禁用，请联系客服');
        }

        return JsonService::data($this->formatUser($user));
    }

    /**
     * 修改登录密码
     * POST /api/user/changePwd  入参：old_password, new_password（token 鉴权）
     */
    public function changePwd(): \think\response\Json
    {
        $userId = UserTokenService::getUserId(UserTokenService::readRequestToken());
        if (!$userId) {
            return JsonService::authExpired();
        }
        $body = $this->body();
        $oldPassword = trim((string) ($body['old_password'] ?? ''));
        $newPassword = trim((string) ($body['new_password'] ?? ''));
        if ($oldPassword === '' || $newPassword === '') {
            return JsonService::fail('原密码和新密码不能为空');
        }
        if (strlen($newPassword) < 6) {
            return JsonService::fail('新密码至少需要6位字符');
        }
        $user = Db::name('users')->where('id', $userId)->where('status', 1)->find();
        if (!$user) {
            return JsonService::fail('用户不存在或已被禁用');
        }
        if ($user['password'] !== '' && !password_verify($oldPassword, (string) $user['password'])) {
            return JsonService::fail('原密码不正确');
        }
        Db::name('users')->where('id', $userId)->update([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return JsonService::success('密码修改成功，请使用新密码重新登录');
    }

    /**
     * 修改用户资料（昵称）
     * POST /api/user/update  入参：nickname（token 鉴权）
     */
    public function update(): \think\response\Json
    {
        $userId = UserTokenService::getUserId(UserTokenService::readRequestToken());
        if (!$userId) {
            return JsonService::authExpired();
        }
        $body = $this->body();
        $nickname = trim((string) ($body['nickname'] ?? ''));
        $len = mb_strlen($nickname);
        if ($len < 2 || $len > 20) {
            return JsonService::fail('昵称长度需为 2-20 个字符');
        }
        Db::name('users')->where('id', $userId)->update([
            'nickname' => $nickname,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return JsonService::success('保存成功', ['nickname' => $nickname]);
    }

    /**
     * 登录历史（分页）
     * GET /api/user/loginLog?page=1&page_size=8（token 鉴权）
     * 返回 list 元素：{ id, ts, time_text, ip, ipIsV4, browser, os, device, way, terminal }
     */
    public function loginLog(): \think\response\Json
    {
        $userId = UserTokenService::getUserId(UserTokenService::readRequestToken());
        if (!$userId) {
            return JsonService::authExpired();
        }
        $page  = max(1, (int) input('page', 1));
        $size  = min(50, max(1, (int) input('page_size', 8)));

        $query = Db::name('user_login_log')->where('user_id', $userId);
        $total = (clone $query)->count();
        $rows  = (clone $query)->order('id', 'desc')->page($page, $size)->select()->toArray();

        $list = [];
        foreach ($rows as $row) {
            $ip = (string) ($row['login_ip'] ?? '');
            [$browser, $os, $device] = $this->parseUA((string) ($row['user_agent'] ?? ''));
            $list[] = [
                'id'        => (int) ($row['id'] ?? 0),
                'ts'        => (int) ($row['login_time'] ?? 0),
                'time_text' => date('Y-m-d H:i', (int) ($row['login_time'] ?? 0)),
                'ip'        => $ip,
                'ipIsV4'    => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
                'browser'   => $browser,
                'os'        => $os,
                'device'    => $device,
                'way'       => (string) ($row['login_way'] ?? ''),
                'terminal'  => (int) ($row['terminal'] ?? 0),
            ];
        }
        return JsonService::data([
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $size,
        ]);
    }

    /** 轻量 UA 解析：[browser, os, device] */
    private function parseUA(string $ua): array
    {
        $browser = '';
        $os = '';
        if (stripos($ua, 'Edg/') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Chrome/') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Safari/') !== false) {
            $browser = 'Safari';
        }
        if (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iOS';
        } elseif (stripos($ua, 'Mac OS') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }
        $device = 'pc';
        if (stripos($ua, 'iPad') !== false) {
            $device = 'tablet';
        } elseif (preg_match('/Mobile|Android|iPhone/', $ua)) {
            $device = 'mobile';
        }
        return [$browser, $os, $device];
    }

    /** 读取请求参数：兼容表单与 JSON body */
    private static $jsonBody = null;

    private function body(): array
    {
        $post = request()->post();
        if (!is_array($post)) {
            $post = [];
        }
        if (self::$jsonBody === null) {
            self::$jsonBody = [];
            $raw = (string) request()->getContent();
            if ($raw === '') {
                $raw = (string) file_get_contents('php://input');
            }
            $raw = trim((string) $raw, "\xEF\xBB\xBF \t\r\n");
            if ($raw !== '' && strpos($raw, '{') === 0) {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    self::$jsonBody = $dec;
                }
            }
        }
        return array_merge($post, self::$jsonBody);
    }

    /**
     * 组装 /api/user/info 返回结构（对齐新版契约，适配本项目 ad_users 字段）
     */
    private function formatUser(array $user): array
    {
        $lastLoginTime = $user['last_login_time'] ?? null;
        $lastLoginTs = $lastLoginTime ? strtotime((string) $lastLoginTime) : 0;

        return [
            'nickname'    => trim((string) ($user['nickname'] ?: $user['username'])),
            'real_name'   => '',
            'avatar'      => $user['avatar'] ?? '',
            'mobile'      => $user['phone'] ?? '',
            'email'       => $user['email'] ?? '',
            'has_password'=> $user['password'] !== '',
            'has_auth'    => false,
            'create_time' => isset($user['create_time']) ? strtotime((string) $user['create_time']) : time(),
            'user_money'  => (float) ($user['balance'] ?? 0),
            'level'       => 1,
            'level_text'  => '普通用户',
            'invite_code' => '',
            'last_login'  => $lastLoginTs ? [
                'ts' => $lastLoginTs,
                'time_text' => date('Y-m-d H:i', $lastLoginTs),
                'ip' => $user['last_login_ip'] ?? '',
                'ip_version' => filter_var($user['last_login_ip'] ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 : 4,
                'device' => 'pc',
                'os' => '',
                'browser' => '',
                'way' => 'account',
                'terminal' => 4,
            ] : null,
        ];
    }
}
