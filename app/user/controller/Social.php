<?php
declare(strict_types=1);

namespace app\user\controller;

use app\common\service\FrontendUserSession;
use app\common\service\SocialLoginService;
use app\common\service\WechatMpBizMsgCrypt;
use app\user\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\response\Html;
use think\response\Redirect;

/**
 * 聚合登录 OAuth 跳转与回调（含微信开放平台网站扫码、微信服务号关注/扫码事件登录）
 */
class Social extends BaseController
{
    /**
     * 回到首页并带上快捷登录结果（供前端读 URL 参数提示，无需单独接口）
     */
    private function redirectHomeWithSocialResult(bool $ok, string $errMsg = ''): Redirect
    {
        $prefix = rtrim((string) request()->rootUrl(), '/');
        $base = $prefix === '' ? '/' : $prefix . '/';
        if ($ok) {
            return redirect($base . '?social_login=1');
        }
        $q = ['social_login' => '0'];
        if ($errMsg !== '') {
            $q['msg'] = mb_substr($errMsg, 0, 300);
        }

        return redirect($base . '?' . http_build_query($q));
    }

    /**
     * 手机扫码授权完成后提示页（不写 PC 登录态）
     */
    private function wxMpPhoneDonePage(bool $ok, string $msg = ''): Html
    {
        $title = $ok ? '登录成功' : '授权未完成';
        $line = $ok
            ? '请返回电脑浏览器，页面将自动完成登录。本页可关闭。'
            : htmlspecialchars($msg !== '' ? $msg : '请返回电脑重试', ENT_QUOTES, 'UTF-8');
        $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:sans-serif;padding:24px;text-align:center;">'
            . '<h2 style="color:' . ($ok ? '#07c160' : '#cf1322') . ';">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="color:#666;line-height:1.6;">' . $line . '</p></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * 跳转第三方授权（聚合接口或微信开放平台 qrconnect）
     */
    public function oauthStart(): Redirect
    {
        $type = (string) input('get.type', '');

        if ($type === 'wx_open') {
            if (!SocialLoginService::isWxOpenEnabled()) {
                return $this->redirectHomeWithSocialResult(false, '微信扫码登录未启用或未配置完整');
            }
            $state = bin2hex(random_bytes(16));
            session('social_oauth_type', 'wx_open');
            session('social_oauth_state', $state);
            $appid = SocialLoginService::wxOpenAppId();
            $cbEnc = rawurlencode(SocialLoginService::wxOpenRedirectUri());
            $jump = 'https://open.weixin.qq.com/connect/qrconnect?appid=' . rawurlencode($appid)
                . '&redirect_uri=' . $cbEnc
                . '&response_type=code&scope=snsapi_login&state=' . rawurlencode($state)
                . '#wechat_redirect';

            return redirect($jump);
        }

        if ($type === 'wx_mp') {
            return $this->redirectHomeWithSocialResult(false, '请在登录窗口打开「关注公众号」二维码，用微信扫一扫完成登录');
        }

        if (!SocialLoginService::isProviderEnabled($type)) {
            return $this->redirectHomeWithSocialResult(false, '该登录方式未启用或未配置');
        }

        $callback = SocialLoginService::callbackUrl();
        session('social_oauth_type', $type);
        session('social_oauth_state', null);

        try {
            $jump = SocialLoginService::fetchOAuthRedirectUrl($type, $callback);
        } catch (\Throwable $e) {
            session('social_oauth_type', null);

            return $this->redirectHomeWithSocialResult(false, $e->getMessage());
        }

        return redirect($jump);
    }

    /**
     * 活码中转（旧版网页授权）：已改为带参公众号二维码，此入口保留兼容，提示用户使用新流程
     */
    public function wxMpBridge(): Html|Redirect
    {
        return $this->wxMpPhoneDonePage(false, '请关闭本页，在电脑端登录窗口重新打开二维码，使用微信扫一扫关注公众号完成登录');
    }

    /**
     * 微信公众平台服务器配置 URL（GET 校验 / POST 接收关注、扫码事件）
     * 支持明文模式；填写 EncodingAESKey 后与兼容模式、安全模式一致（密文收发）
     */
    public function wechatMpServer()
    {
        $token = SocialLoginService::wxMpToken();
        if ($token === '') {
            return response('mp_token not configured', 503, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $timestamp = (string) input('get.timestamp', '');
        $nonce = (string) input('get.nonce', '');
        $msgSignature = (string) input('get.msg_signature', '');
        $signature = (string) input('get.signature', '');
        $aesKey = SocialLoginService::wxMpEncodingAesKey();
        $appId = SocialLoginService::wxMpAppId();

        if (request()->isGet()) {
            $echostr = (string) input('get.echostr', '');
            if ($echostr === '') {
                return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            if ($msgSignature !== '' && $aesKey !== '' && WechatMpBizMsgCrypt::isValidEncodingAesKey($aesKey)) {
                try {
                    $crypt = new WechatMpBizMsgCrypt($token, $aesKey, $appId);
                    $plainEcho = $crypt->verifyUrl($msgSignature, $timestamp, $nonce, $echostr);
                } catch (\Throwable) {
                    $plainEcho = null;
                }
                if ($plainEcho !== null && $plainEcho !== '') {
                    return response($plainEcho, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
                }

                return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            if (!SocialLoginService::verifyMpSignature($token, $signature, $timestamp, $nonce)) {
                return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            return response($echostr, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $raw = (string) request()->getContent();
        if ($raw === '') {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $replyEncrypted = false;
        if ($msgSignature !== '' && $aesKey !== '' && WechatMpBizMsgCrypt::isValidEncodingAesKey($aesKey)) {
            try {
                $crypt = new WechatMpBizMsgCrypt($token, $aesKey, $appId);
                $plain = $crypt->decryptMsg($msgSignature, $timestamp, $nonce, $raw);
            } catch (\Throwable) {
                $plain = null;
            }
            if ($plain === null || $plain === '') {
                return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $raw = $plain;
            $replyEncrypted = true;
        } elseif (!SocialLoginService::verifyMpSignature($token, $signature, $timestamp, $nonce)) {
            return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $msgType = (string) ($xml->MsgType ?? '');
        if ($msgType !== 'event') {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $event = (string) ($xml->Event ?? '');
        $eventKey = (string) ($xml->EventKey ?? '');
        $fromUser = (string) ($xml->FromUserName ?? '');
        if ($fromUser === '') {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $scene = '';
        if ($event === 'subscribe') {
            if (str_starts_with($eventKey, 'qrscene_')) {
                $scene = substr($eventKey, 8);
            }
        } elseif ($event === 'SCAN') {
            $scene = $eventKey;
        }
        if ($scene !== '') {
            $toGh = (string) ($xml->ToUserName ?? '');
            $ok = $this->completeWxMpSceneLogin(trim($scene), $fromUser);
            if ($ok && $toGh !== '') {
                $replyXml = $this->buildWxMpPassiveTextXml($fromUser, $toGh, SocialLoginService::wxMpLoginSuccessReplyText());
                if ($replyEncrypted && $aesKey !== '' && WechatMpBizMsgCrypt::isValidEncodingAesKey($aesKey)) {
                    try {
                        $crypt = new WechatMpBizMsgCrypt($token, $aesKey, $appId);
                        $replyXml = $crypt->encryptXml($replyXml);
                    } catch (\Throwable) {
                        return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
                    }
                }

                return response($replyXml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }
        }

        return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * 被动回复文本（关注/扫码事件响应内返回，用户在公众号会话内看到）
     */
    private function buildWxMpPassiveTextXml(string $toUserOpenId, string $fromGhId, string $text): string
    {
        $text = str_replace(']]>', ']] >', $text);
        $time = (string) time();

        return '<xml><ToUserName><![CDATA[' . $toUserOpenId . ']]></ToUserName>'
            . '<FromUserName><![CDATA[' . $fromGhId . ']]></FromUserName>'
            . '<CreateTime>' . $time . '</CreateTime>'
            . '<MsgType><![CDATA[text]]></MsgType>'
            . '<Content><![CDATA[' . $text . ']]></Content></xml>';
    }

    /**
     * 带参二维码场景值与 PC 端轮询 ticket 一致；成功时返回 true 以便外层下发被动回复
     */
    private function completeWxMpSceneLogin(string $sceneStr, string $openid): bool
    {
        if ($sceneStr === '' || !SocialLoginService::isWxMpEnabled()) {
            return false;
        }
        $pollKey = SocialLoginService::CACHE_WXMP_POLL . $sceneStr;
        $row = Cache::get($pollKey);
        if (!is_array($row)) {
            return false;
        }
        $status = (string) ($row['status'] ?? 'pending');
        if ($status === 'success') {
            return false;
        }
        if ($status !== 'pending') {
            return false;
        }

        try {
            $info = SocialLoginService::fetchMpSubscriberProfile($openid);
        } catch (\Throwable $e) {
            Cache::set($pollKey, [
                'status' => 'failed',
                'msg' => $e->getMessage(),
            ], SocialLoginService::WXMP_TICKET_TTL);

            return false;
        }

        [$user, $err] = $this->buildOrLoadSocialUser('wx_mp', $info);
        if ($err !== null || $user === null) {
            Cache::set($pollKey, [
                'status' => 'failed',
                'msg' => (string) $err,
            ], SocialLoginService::WXMP_TICKET_TTL);

            return false;
        }

        Cache::set($pollKey, [
            'status' => 'success',
            'user_id' => (int) $user['id'],
        ], SocialLoginService::WXMP_TICKET_TTL);

        return true;
    }

    /**
     * OAuth 回调：聚合平台带 type、code；微信开放平台带 code、state（无 type）
     */
    public function oauthCallback(): Html|Redirect
    {
        $code = (string) input('get.code', '');
        $stateIn = (string) input('get.state', '');
        $typeGet = (string) input('get.type', '');
        $sessionType = (string) (session('social_oauth_type') ?? '');
        $expectedState = (string) (session('social_oauth_state') ?? '');

        $type = $typeGet !== '' ? $typeGet : $sessionType;

        if ($code === '') {
            session('social_oauth_type', null);
            session('social_oauth_state', null);

            return $this->redirectHomeWithSocialResult(false, '授权参数无效或会话已过期，请重试');
        }

        if ($type === 'wx_open') {
            if ($expectedState === '' || $stateIn === '' || !hash_equals($expectedState, $stateIn)) {
                session('social_oauth_type', null);
                session('social_oauth_state', null);

                return $this->redirectHomeWithSocialResult(false, '授权验证失败，请重新扫码');
            }
            session('social_oauth_type', null);
            session('social_oauth_state', null);

            if (!SocialLoginService::isWxOpenEnabled()) {
                return $this->redirectHomeWithSocialResult(false, '该登录方式已关闭');
            }
            try {
                $info = SocialLoginService::exchangeWxOpenCode($code);
            } catch (\Throwable $e) {
                return $this->redirectHomeWithSocialResult(false, $e->getMessage());
            }

            return $this->finishSocialLogin('wx_open', $info);
        }

        $ticket = '';
        if ($stateIn !== '') {
            $fromState = Cache::get(SocialLoginService::CACHE_WXMP_STATE . $stateIn);
            if (is_string($fromState) && $fromState !== '') {
                $ticket = $fromState;
            }
        }
        $wxMpBySession = $sessionType === 'wx_mp' && $expectedState !== '' && $stateIn !== ''
            && hash_equals($expectedState, $stateIn);
        $isWxMpFlow = $ticket !== '' || $wxMpBySession;

        if ($isWxMpFlow) {
            $stateOk = false;
            if ($ticket !== '') {
                $oauthRow = Cache::get(SocialLoginService::CACHE_WXMP_OAUTH . $ticket);
                $stateOk = is_array($oauthRow)
                    && isset($oauthRow['state'])
                    && hash_equals((string) $oauthRow['state'], $stateIn);
            }
            if (!$stateOk && $wxMpBySession) {
                $stateOk = true;
            }

            if (!$stateOk) {
                session('social_oauth_type', null);
                session('social_oauth_state', null);
                if ($ticket !== '') {
                    Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, [
                        'status' => 'failed',
                        'msg' => '授权验证失败，请重新扫码',
                    ], SocialLoginService::WXMP_TICKET_TTL);
                }

                return $ticket !== ''
                    ? $this->wxMpPhoneDonePage(false, '授权验证失败，请重新扫码')
                    : $this->redirectHomeWithSocialResult(false, '授权验证失败，请重新扫码');
            }

            session('social_oauth_type', null);
            session('social_oauth_state', null);

            if (!SocialLoginService::isWxMpEnabled()) {
                if ($ticket !== '') {
                    Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, [
                        'status' => 'failed',
                        'msg' => '该登录方式已关闭',
                    ], SocialLoginService::WXMP_TICKET_TTL);
                }

                return $ticket !== ''
                    ? $this->wxMpPhoneDonePage(false, '该登录方式已关闭')
                    : $this->redirectHomeWithSocialResult(false, '该登录方式已关闭');
            }

            try {
                $info = SocialLoginService::exchangeWxMpCode($code);
            } catch (\Throwable $e) {
                if ($ticket !== '') {
                    Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, [
                        'status' => 'failed',
                        'msg' => $e->getMessage(),
                    ], SocialLoginService::WXMP_TICKET_TTL);
                }

                return $ticket !== ''
                    ? $this->wxMpPhoneDonePage(false, $e->getMessage())
                    : $this->redirectHomeWithSocialResult(false, $e->getMessage());
            }

            [$user, $err] = $this->buildOrLoadSocialUser('wx_mp', $info);
            if ($err !== null || $user === null) {
                if ($ticket !== '') {
                    Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, [
                        'status' => 'failed',
                        'msg' => $err ?? '登录失败',
                    ], SocialLoginService::WXMP_TICKET_TTL);
                }

                return $ticket !== ''
                    ? $this->wxMpPhoneDonePage(false, (string) $err)
                    : $this->redirectHomeWithSocialResult(false, (string) $err);
            }

            if ($ticket !== '') {
                Cache::set(SocialLoginService::CACHE_WXMP_POLL . $ticket, [
                    'status' => 'success',
                    'user_id' => (int) $user['id'],
                ], SocialLoginService::WXMP_TICKET_TTL);
                Cache::delete(SocialLoginService::CACHE_WXMP_OAUTH . $ticket);
                Cache::delete(SocialLoginService::CACHE_WXMP_STATE . $stateIn);

                return $this->wxMpPhoneDonePage(true);
            }

            FrontendUserSession::loginWithUserRow($user);

            return $this->redirectHomeWithSocialResult(true);
        }

        session('social_oauth_type', null);
        session('social_oauth_state', null);

        if ($type === '' || $sessionType === '' || $type !== $sessionType) {
            return $this->redirectHomeWithSocialResult(false, '授权参数无效或会话已过期，请重试');
        }

        if (!SocialLoginService::isProviderEnabled($type)) {
            return $this->redirectHomeWithSocialResult(false, '该登录方式已关闭');
        }

        try {
            $info = SocialLoginService::exchangeCode($type, $code);
        } catch (\Throwable $e) {
            return $this->redirectHomeWithSocialResult(false, $e->getMessage());
        }

        return $this->finishSocialLogin($type, $info);
    }

    /**
     * @param array{social_uid:string,nickname:string,faceimg:string} $info
     */
    private function finishSocialLogin(string $type, array $info): Redirect
    {
        [$user, $err] = $this->buildOrLoadSocialUser($type, $info);
        if ($err !== null || $user === null) {
            return $this->redirectHomeWithSocialResult(false, (string) $err);
        }
        FrontendUserSession::loginWithUserRow($user);

        return $this->redirectHomeWithSocialResult(true);
    }

    /**
     * @param array{social_uid:string,nickname:string,faceimg:string} $info
     * @return array{0: ?array, 1: ?string} [user row, error message]
     */
    private function buildOrLoadSocialUser(string $type, array $info): array
    {
        $bind = Db::name('user_social_bind')
            ->where('provider', $type)
            ->where('social_uid', $info['social_uid'])
            ->find();

        if ($bind) {
            $user = Db::name('users')->where('id', (int) $bind['user_id'])->find();
            if (!$user || (int) $user['status'] !== 1) {
                return [null, '账号不存在或已被禁用'];
            }
            $now = date('Y-m-d H:i:s');
            Db::name('user_social_bind')->where('id', (int) $bind['id'])->update([
                'nickname' => $info['nickname'],
                'avatar' => $info['faceimg'],
                'update_time' => $now,
            ]);

            if ($type === 'wx_mp') {
                $uid = (int) $user['id'];
                $newUsername = self::allocateUsernameForSocial($type, $info['social_uid'], (string) $info['nickname'], $uid);
                $newNickname = mb_substr($info['nickname'] !== '' ? $info['nickname'] : $newUsername, 0, 50);
                $userUpdate = [
                    'nickname' => $newNickname,
                    'avatar' => mb_substr((string) $info['faceimg'], 0, 255),
                    'update_time' => $now,
                ];
                if ($newUsername !== (string) $user['username']) {
                    $userUpdate['username'] = $newUsername;
                }
                Db::name('users')->where('id', $uid)->update($userUpdate);
                $user = Db::name('users')->where('id', $uid)->find();
            }

            return [$user, null];
        }

        $now = date('Y-m-d H:i:s');
        $username = self::allocateUsernameForSocial($type, $info['social_uid'], (string) $info['nickname'], null);
        $randomPass = bin2hex(random_bytes(16));

        Db::startTrans();
        try {
            $userId = Db::name('users')->insertGetId([
                'username' => $username,
                'password' => password_hash($randomPass, PASSWORD_DEFAULT),
                'nickname' => mb_substr($info['nickname'] !== '' ? $info['nickname'] : $username, 0, 50),
                'email' => '',
                'phone' => '',
                'avatar' => mb_substr($info['faceimg'], 0, 255),
                'balance' => '0.00',
                'status' => 1,
                'last_login_time' => $now,
                'last_login_ip' => request()->ip(),
                'create_time' => $now,
                'update_time' => $now,
            ]);

            Db::name('user_social_bind')->insert([
                'user_id' => $userId,
                'provider' => $type,
                'social_uid' => $info['social_uid'],
                'nickname' => mb_substr($info['nickname'], 0, 100),
                'avatar' => mb_substr($info['faceimg'], 0, 500),
                'create_time' => $now,
                'update_time' => $now,
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();

            return [null, '创建账号失败，请确认已执行数据库脚本 database/social_login_bind.sql'];
        }

        $user = Db::name('users')->where('id', $userId)->find();
        if (!$user) {
            return [null, '创建账号失败'];
        }

        return [$user, null];
    }

    /**
     * 微信服务号：优先用接口返回的昵称作为 username（50 字内、库内唯一）；昵称为空或极端冲突时用「用户」+六位随机数字。
     *
     * @param ?int $forUserId 已存在用户更新资料时传入，用于占用检测时排除自身
     */
    private static function allocateUsernameForSocial(string $type, string $socialUid, string $nickname, ?int $forUserId): string
    {
        if ($type !== 'wx_mp') {
            return self::makeUniqueUsername($type, $socialUid);
        }
        $nick = trim($nickname);
        if ($nick === '') {
            return self::makeWxMpNumericUsername();
        }

        $base = mb_substr($nick, 0, 50);
        $n = 0;
        while ($n < 10000) {
            if ($n === 0) {
                $candidate = $base;
            } else {
                $suffix = (string) $n;
                $maxLen = 50 - mb_strlen($suffix);
                if ($maxLen < 1) {
                    $maxLen = 1;
                }
                $candidate = mb_substr($base, 0, $maxLen) . $suffix;
            }
            $row = Db::name('users')->where('username', $candidate)->find();
            if (!$row) {
                return $candidate;
            }
            if ($forUserId !== null && (int) ($row['id'] ?? 0) === $forUserId) {
                return $candidate;
            }
            $n++;
        }

        return self::makeWxMpNumericUsername();
    }

    /**
     * 微信服务号备用：用户 + 六位数字（0–999999），避免 wx_mp_ 哈希形式
     */
    private static function makeWxMpNumericUsername(): string
    {
        for ($i = 0; $i < 800; $i++) {
            $candidate = '用户' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!Db::name('users')->where('username', $candidate)->find()) {
                return $candidate;
            }
        }
        // 极低概率仍冲突：加长随机后缀
        do {
            $candidate = '用户' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Db::name('users')->where('username', $candidate)->find());

        return mb_substr($candidate, 0, 50);
    }

    private static function makeUniqueUsername(string $type, string $socialUid): string
    {
        $base = $type . '_' . substr(md5($socialUid), 0, 12);
        $candidate = $base;
        $n = 0;
        while (Db::name('users')->where('username', $candidate)->find()) {
            $n++;
            $candidate = $base . $n;
            if (strlen($candidate) > 48) {
                $candidate = substr($base, 0, 40) . $n;
            }
        }

        return $candidate;
    }
}
