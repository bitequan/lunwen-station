<?php
declare(strict_types=1);

namespace app\common\service;

use app\model\SystemConfig;
use think\facade\Cache;
use think\facade\Request;

/**
 * 聚合登录（OAuth）对接：配置读取与上游 connect.php 请求
 *
 * 存储键：聚合 <-> social_login_config；微信开放平台扫码 <-> social_wx_open_config；
 * 微信服务号：带参二维码关注/扫码 + 服务器事件 <-> social_wx_mp_config（互不混写）
 */
class SocialLoginService
{
    public const CONFIG_KEY_AGGREGATE = 'social_login_config';

    public const CONFIG_KEY_WX_OPEN = 'social_wx_open_config';

    public const CONFIG_KEY_WX_MP = 'social_wx_mp_config';

    /** 服务号跨端活码：Cache 键前缀（oauth 行、state→ticket、轮询） */
    public const CACHE_WXMP_OAUTH = 'social_wxmp_oauth_';

    public const CACHE_WXMP_STATE = 'social_wxmp_state_';

    public const CACHE_WXMP_POLL = 'social_wxmp_poll_';

    /** 公众号 access_token（cgi-bin）缓存键 */
    public const CACHE_WXMP_ACCESS_TOKEN = 'social_wxmp_cgi_access_token';

    public const WXMP_TICKET_TTL = 600;

    public const PROVIDERS = ['qq' => 'QQ', 'wx' => '微信', 'alipay' => '支付宝', 'sina' => '微博'];

    /**
     * 聚合登录完整配置（含密钥，仅后台与服务端使用；不含官方微信字段）
     */
    public static function loadConfig(): array
    {
        $defaults = [
            'enabled' => 0,
            'connect_url' => '',
            'appid' => '',
            'appkey' => '',
            'types' => [
                'qq' => 0,
                'wx' => 0,
                'alipay' => 0,
                'sina' => 0,
            ],
        ];
        $raw = SystemConfig::getValue(self::CONFIG_KEY_AGGREGATE);
        if (!$raw) {
            return $defaults;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        unset($decoded['wx_open']);
        $merged = array_replace_recursive($defaults, $decoded);
        if (!is_array($merged['types'])) {
            $merged['types'] = $defaults['types'];
        }
        foreach (array_keys(self::PROVIDERS) as $p) {
            $merged['types'][$p] = !empty($merged['types'][$p]) ? 1 : 0;
        }

        return $merged;
    }

    /**
     * 微信开放平台 · 网站应用扫码（独立配置键；若旧数据曾写在聚合 JSON 的 wx_open 内，读取时兼容）
     *
     * @return array{enabled:int,appid:string,appsecret:string}
     */
    public static function loadWxOpenConfig(): array
    {
        $defaults = [
            'enabled' => 0,
            'appid' => '',
            'appsecret' => '',
        ];
        $raw = SystemConfig::getValue(self::CONFIG_KEY_WX_OPEN);
        if ($raw) {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $merged = array_replace($defaults, $decoded);
                $merged['enabled'] = !empty($merged['enabled']) ? 1 : 0;

                return $merged;
            }
        }
        $legacy = SystemConfig::getValue(self::CONFIG_KEY_AGGREGATE);
        if ($legacy) {
            $agg = json_decode((string) $legacy, true);
            if (is_array($agg) && !empty($agg['wx_open']) && is_array($agg['wx_open'])) {
                $merged = array_replace($defaults, $agg['wx_open']);
                $merged['enabled'] = !empty($merged['enabled']) ? 1 : 0;

                return $merged;
            }
        }

        return $defaults;
    }

    public static function isWxOpenEnabled(): bool
    {
        $w = self::loadWxOpenConfig();

        return (int) ($w['enabled'] ?? 0) === 1
            && trim((string) ($w['appid'] ?? '')) !== ''
            && trim((string) ($w['appsecret'] ?? '')) !== '';
    }

    /**
     * 微信公众平台 · 服务号关注/带参扫码登录（独立配置键）
     *
     * @return array{enabled:int,appid:string,appsecret:string,mp_token:string,encoding_aes_key:string,login_success_reply:string}
     */
    public static function loadWxMpConfig(): array
    {
        $defaults = [
            'enabled' => 0,
            'appid' => '',
            'appsecret' => '',
            /** 公众平台「服务器配置」Token，用于校验 URL 与接收事件 */
            'mp_token' => '',
            /** 43 位 EncodingAESKey；空则仅支持明文模式 */
            'encoding_aes_key' => '',
            /** 关注/扫码登录成功后，被动回复给用户的文案；空则使用内置默认 */
            'login_success_reply' => '',
        ];
        $raw = SystemConfig::getValue(self::CONFIG_KEY_WX_MP);
        if (!$raw) {
            return $defaults;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        $merged = array_replace($defaults, $decoded);
        $merged['enabled'] = !empty($merged['enabled']) ? 1 : 0;
        $merged['mp_token'] = trim((string) ($merged['mp_token'] ?? ''));
        $merged['encoding_aes_key'] = trim((string) ($merged['encoding_aes_key'] ?? ''));
        $merged['login_success_reply'] = trim((string) ($merged['login_success_reply'] ?? ''));

        return $merged;
    }

    /** 扫码/关注登录成功后，公众号内被动回复的文案（未配置则用默认） */
    public static function wxMpLoginSuccessReplyText(): string
    {
        $w = self::loadWxMpConfig();
        $t = trim((string) ($w['login_success_reply'] ?? ''));

        return $t !== '' ? $t : '登录成功，您已在网站完成账号登录。';
    }

    public static function isWxMpEnabled(): bool
    {
        $w = self::loadWxMpConfig();

        return (int) ($w['enabled'] ?? 0) === 1
            && trim((string) ($w['appid'] ?? '')) !== ''
            && trim((string) ($w['appsecret'] ?? '')) !== '';
    }

    public static function wxMpAppId(): string
    {
        $w = self::loadWxMpConfig();

        return trim((string) ($w['appid'] ?? ''));
    }

    /** 消息服务器 Token；未配置则无法接收关注/扫码事件 */
    public static function wxMpToken(): string
    {
        $w = self::loadWxMpConfig();

        return trim((string) ($w['mp_token'] ?? ''));
    }

    /** 43 位 EncodingAESKey；空表示仅按明文处理消息体（公众平台须选明文模式） */
    public static function wxMpEncodingAesKey(): string
    {
        $w = self::loadWxMpConfig();

        return trim((string) ($w['encoding_aes_key'] ?? ''));
    }

    /**
     * 校验微信公众平台 URL / 消息签名
     */
    public static function verifyMpSignature(string $token, string $signature, string $timestamp, string $nonce): bool
    {
        if ($token === '' || $signature === '') {
            return false;
        }
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));

        return hash_equals($tmpStr, $signature);
    }

    /**
     * IP 白名单类错误时附加简短说明
     */
    private static function enrichWeChatMpApiError(string $rawMsg, string $fallback = ''): string
    {
        $rawMsg = trim($rawMsg);
        if ($rawMsg === '') {
            return $fallback !== '' ? $fallback : '微信接口返回错误';
        }
        $low = strtolower($rawMsg);
        if (!str_contains($low, 'whitelist') && !str_contains($low, 'invalid ip')) {
            return $rawMsg;
        }
        $ipv4 = '';
        if (preg_match('/\b(\d{1,3}(?:\.\d{1,3}){3})\b/', $rawMsg, $m)) {
            $ipv4 = $m[1];
        }
        $hint = '请在公众平台「开发 - 基本配置 - IP 白名单」中加入本机出口 IP。';
        if ($ipv4 !== '') {
            $hint .= ' 本次：' . $ipv4;
        }

        return $rawMsg . ' ' . $hint;
    }

    /**
     * 获取公众号全局 access_token（带缓存）
     */
    public static function getMpAccessToken(): string
    {
        $cached = Cache::get(self::CACHE_WXMP_ACCESS_TOKEN);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        $w = self::loadWxMpConfig();
        $appid = trim((string) ($w['appid'] ?? ''));
        $secret = trim((string) ($w['appsecret'] ?? ''));
        if ($appid === '' || $secret === '') {
            throw new \RuntimeException('微信服务号 AppID/AppSecret 未配置');
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?' . http_build_query([
            'grant_type' => 'client_credential',
            'appid' => $appid,
            'secret' => $secret,
        ]);
        $json = self::httpGetJsonWechat($url);
        if (isset($json['errcode']) && (int) $json['errcode'] !== 0) {
            throw new \RuntimeException(self::enrichWeChatMpApiError((string) ($json['errmsg'] ?? ''), '获取 access_token 失败'));
        }
        $at = (string) ($json['access_token'] ?? '');
        if ($at === '') {
            throw new \RuntimeException('微信未返回 access_token');
        }
        $expiresIn = (int) ($json['expires_in'] ?? 7200);
        $ttl = max(120, $expiresIn - 180);
        Cache::set(self::CACHE_WXMP_ACCESS_TOKEN, $at, $ttl);

        return $at;
    }

    public static function clearMpAccessTokenCache(): void
    {
        Cache::delete(self::CACHE_WXMP_ACCESS_TOKEN);
    }

    /**
     * 创建临时带参二维码（场景值字符串，最长 64），返回微信 showqrcode 所需 ticket
     *
     * @return array{ticket:string,expire_seconds:int}
     */
    public static function createWxMpStrSceneQr(string $sceneStr, int $expireSeconds = 600): array
    {
        $len = strlen($sceneStr);
        if ($len < 1 || $len > 64) {
            throw new \RuntimeException('二维码场景值长度须为 1～64');
        }
        $accessToken = self::getMpAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . rawurlencode($accessToken);
        $payload = [
            'expire_seconds' => $expireSeconds,
            'action_name' => 'QR_STR_SCENE',
            'action_info' => ['scene' => ['scene_str' => $sceneStr]],
        ];
        $json = self::httpPostJsonWechat($url, $payload);
        if (isset($json['errcode']) && (int) $json['errcode'] !== 0) {
            if ((int) $json['errcode'] === 40001) {
                self::clearMpAccessTokenCache();

                return self::createWxMpStrSceneQr($sceneStr, $expireSeconds);
            }
            throw new \RuntimeException(self::enrichWeChatMpApiError((string) ($json['errmsg'] ?? ''), '创建二维码失败'));
        }
        $ticket = (string) ($json['ticket'] ?? '');
        if ($ticket === '') {
            throw new \RuntimeException('微信未返回二维码 ticket');
        }

        return [
            'ticket' => $ticket,
            'expire_seconds' => (int) ($json['expire_seconds'] ?? $expireSeconds),
        ];
    }

    /**
     * 公众号用户基本信息（需用户已关注），用于绑定网站账号
     *
     * @return array{social_uid:string,nickname:string,faceimg:string}
     */
    public static function fetchMpSubscriberProfile(string $openid): array
    {
        $openid = trim($openid);
        if ($openid === '') {
            throw new \RuntimeException('openid 无效');
        }
        $accessToken = self::getMpAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?' . http_build_query([
            'access_token' => $accessToken,
            'openid' => $openid,
            'lang' => 'zh_CN',
        ]);
        $j = self::httpGetJsonWechat($url);
        if (isset($j['errcode']) && (int) $j['errcode'] !== 0) {
            if ((int) $j['errcode'] === 40001) {
                self::clearMpAccessTokenCache();

                return self::fetchMpSubscriberProfile($openid);
            }
            throw new \RuntimeException(self::enrichWeChatMpApiError((string) ($j['errmsg'] ?? ''), '获取用户信息失败'));
        }
        $unionId = isset($j['unionid']) ? (string) $j['unionid'] : '';
        $socialUid = $unionId !== '' ? $unionId : $openid;

        return [
            'social_uid' => $socialUid,
            'nickname' => (string) ($j['nickname'] ?? ''),
            'faceimg' => (string) ($j['headimgurl'] ?? ''),
        ];
    }

    /**
     * 客服接口下发文本消息（登录成功后自动回复）
     */
    public static function sendMpKfText(string $openid, string $content): void
    {
        $openid = trim($openid);
        $content = trim($content);
        if ($openid === '' || $content === '') {
            return;
        }
        $accessToken = self::getMpAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . rawurlencode($accessToken);
        $payload = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => ['content' => $content],
        ];
        $json = self::httpPostJsonWechat($url, $payload);
        if (isset($json['errcode']) && (int) $json['errcode'] !== 0) {
            if ((int) $json['errcode'] === 40001) {
                self::clearMpAccessTokenCache();
                self::sendMpKfText($openid, $content);

                return;
            }
            // 非致命：用户未发消息时 48h 限制等，忽略
        }
    }

    /**
     * 微信接口 POST JSON
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function httpPostJsonWechat(string $url, array $data): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl 初始化失败');
        }
        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Accept: application/json']);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false || $resp === '') {
            throw new \RuntimeException($err !== '' ? $err : '微信接口无响应');
        }
        if ($code >= 400) {
            throw new \RuntimeException('微信接口 HTTP ' . $code);
        }
        $parsed = json_decode($resp, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException('微信接口返回非 JSON');
        }

        return $parsed;
    }

    public static function wxOpenAppId(): string
    {
        $w = self::loadWxOpenConfig();

        return trim((string) ($w['appid'] ?? ''));
    }

    /**
     * 微信控制台填写的授权回调 URI（需与此字符串完全一致，含 https、路径，一般不含查询串）
     */
    public static function wxOpenRedirectUri(): string
    {
        return self::callbackUrl();
    }

    /**
     * 使用 code 换取用户（微信开放平台网站应用 OAuth2）
     *
     * @return array{social_uid:string,nickname:string,faceimg:string}
     */
    public static function exchangeWxOpenCode(string $code): array
    {
        $w = self::loadWxOpenConfig();
        $appid = trim((string) ($w['appid'] ?? ''));
        $secret = trim((string) ($w['appsecret'] ?? ''));
        if ($appid === '' || $secret === '') {
            throw new \RuntimeException('微信开放平台未配置完整');
        }

        return self::exchangeWeChatOAuth2Code($appid, $secret, $code);
    }

    /**
     * 服务号网页授权 code 换用户信息（与开放平台网站应用共用 sns/oauth2 接口）
     *
     * @return array{social_uid:string,nickname:string,faceimg:string}
     */
    public static function exchangeWxMpCode(string $code): array
    {
        $w = self::loadWxMpConfig();
        $appid = trim((string) ($w['appid'] ?? ''));
        $secret = trim((string) ($w['appsecret'] ?? ''));
        if ($appid === '' || $secret === '') {
            throw new \RuntimeException('微信服务号未配置完整');
        }

        return self::exchangeWeChatOAuth2Code($appid, $secret, $code);
    }

    /**
     * @return array{social_uid:string,nickname:string,faceimg:string}
     */
    private static function exchangeWeChatOAuth2Code(string $appid, string $secret, string $code): array
    {
        $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?'
            . http_build_query([
                'appid' => $appid,
                'secret' => $secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

        $tokenJson = self::httpGetJsonWechat($tokenUrl);
        if (isset($tokenJson['errcode']) && (int) $tokenJson['errcode'] !== 0) {
            throw new \RuntimeException((string) ($tokenJson['errmsg'] ?? '换取 access_token 失败'));
        }
        $accessToken = (string) ($tokenJson['access_token'] ?? '');
        $openid = (string) ($tokenJson['openid'] ?? '');
        if ($accessToken === '' || $openid === '') {
            throw new \RuntimeException('微信未返回有效的授权信息');
        }

        $infoUrl = 'https://api.weixin.qq.com/sns/userinfo?'
            . http_build_query([
                'access_token' => $accessToken,
                'openid' => $openid,
                'lang' => 'zh_CN',
            ]);
        $userJson = self::httpGetJsonWechat($infoUrl);
        if (isset($userJson['errcode']) && (int) $userJson['errcode'] !== 0) {
            throw new \RuntimeException((string) ($userJson['errmsg'] ?? '获取用户信息失败'));
        }

        $unionId = isset($userJson['unionid']) ? (string) $userJson['unionid'] : '';
        $socialUid = $unionId !== '' ? $unionId : $openid;

        return [
            'social_uid' => $socialUid,
            'nickname' => (string) ($userJson['nickname'] ?? ''),
            'faceimg' => (string) ($userJson['headimgurl'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function httpGetJsonWechat(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl 初始化失败');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException($err !== '' ? $err : '微信接口无响应');
        }
        if ($code >= 400) {
            throw new \RuntimeException('微信接口 HTTP ' . $code);
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('微信接口返回非 JSON');
        }

        return $data;
    }

    /**
     * 前台可暴露的配置（不含 appkey）
     */
    public static function publicConfig(): array
    {
        $c = self::loadConfig();
        $enabledTypes = [];
        foreach (self::PROVIDERS as $code => $label) {
            if (!empty($c['types'][$code])) {
                $enabledTypes[] = ['type' => $code, 'name' => $label];
            }
        }
        $aggregateOn = (int) ($c['enabled'] ?? 0) === 1
            && $c['appid'] !== ''
            && $c['appkey'] !== ''
            && count($enabledTypes) > 0;

        $wxOpenOn = self::isWxOpenEnabled();
        $wxMpOn = self::isWxMpEnabled();

        // 聚合总开关关闭时不向前台下发渠道列表，避免仅勾选渠道却仍展示聚合按钮
        $typesForPublic = $aggregateOn ? $enabledTypes : [];

        return [
            'enabled' => $aggregateOn || $wxOpenOn || $wxMpOn,
            'types' => $typesForPublic,
            'wx_open' => [
                'enabled' => $wxOpenOn,
                'appid' => $wxOpenOn ? self::wxOpenAppId() : '',
            ],
            'wx_mp' => [
                'enabled' => $wxMpOn,
                'appid' => $wxMpOn ? self::wxMpAppId() : '',
            ],
        ];
    }

    public static function isProviderEnabled(string $type): bool
    {
        $c = self::loadConfig();
        if ((int) ($c['enabled'] ?? 0) !== 1) {
            return false;
        }
        if ($c['appid'] === '' || $c['appkey'] === '') {
            return false;
        }
        return !empty($c['types'][$type]) && isset(self::PROVIDERS[$type]);
    }

    /**
     * OAuth 回调完整 URL（微信 redirect_uri 须与公众平台配置一致）
     */
    public static function callbackUrl(): string
    {
        return rtrim(self::publicOAuthBaseUrl(), '/') . '/social/oauth/callback';
    }

    /**
     * 对外 OAuth / 活码使用的一级 URL 前缀（无尾部斜杠）
     * 优先 site_config.site_url；若只配了「域名根」而应用在子路径（如 /user），自动补上当前 Request::rootUrl，避免 redirect_uri 少一段路径导致 10003
     */
    public static function publicOAuthBaseUrl(): string
    {
        $canon = self::canonicalSiteBaseUrl();
        $reqBase = rtrim(Request::domain() . Request::rootUrl(), '/');

        if ($canon === '') {
            return $reqBase;
        }

        $parts = parse_url($canon);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $reqBase;
        }

        $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
        $root = trim((string) Request::rootUrl(), '/');

        if ($path === '' && $root !== '') {
            return rtrim($canon, '/') . '/' . $root;
        }

        return $canon;
    }

    /**
     * 来自 site_config.site_url 的站点根（无尾部斜杠），未配置时返回空字符串
     */
    public static function canonicalSiteBaseUrl(): string
    {
        $raw = SystemConfig::getValue('site_config');
        if (!$raw) {
            return '';
        }
        $cfg = json_decode((string) $raw, true);
        if (!is_array($cfg)) {
            return '';
        }
        $u = trim((string) ($cfg['site_url'] ?? ''));
        if ($u === '') {
            return '';
        }
        $parts = parse_url($u);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $scheme = strtolower((string) $parts['scheme']);
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? (int) $parts['port'] : 0;
        $base = $scheme . '://' . $host;
        if ($port > 0 && ! (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $base .= ':' . $port;
        }
        if (!empty($parts['path'])) {
            $path = rtrim((string) $parts['path'], '/');
            if ($path !== '' && $path !== '/') {
                $base .= $path;
            }
        }

        return rtrim($base, '/');
    }

    /**
     * 请求 act=login，返回跳转 URL
     */
    public static function fetchOAuthRedirectUrl(string $type, string $redirectUri): string
    {
        $c = self::loadConfig();
        $q = [
            'act' => 'login',
            'appid' => $c['appid'],
            'appkey' => $c['appkey'],
            'type' => $type,
            'redirect_uri' => $redirectUri,
        ];

        $json = self::requestConnect($c, $q);
        if (($json['code'] ?? -1) !== 0) {
            $msg = (string) ($json['msg'] ?? '获取登录地址失败');
            throw new \RuntimeException($msg);
        }
        $jump = (string) ($json['url'] ?? '');
        if ($jump === '') {
            throw new \RuntimeException('聚合接口未返回跳转地址');
        }
        return $jump;
    }

    /**
     * act=callback 用 code 换用户信息
     *
     * @return array{social_uid:string,nickname:string,faceimg:string,access_token?:string}
     */
    public static function exchangeCode(string $type, string $code): array
    {
        $c = self::loadConfig();
        $q = [
            'act' => 'callback',
            'appid' => $c['appid'],
            'appkey' => $c['appkey'],
            'type' => $type,
            'code' => $code,
        ];

        $json = self::requestConnect($c, $q);
        if (($json['code'] ?? -1) !== 0) {
            $msg = (string) ($json['msg'] ?? '授权失败');
            throw new \RuntimeException($msg);
        }
        $uid = (string) ($json['social_uid'] ?? '');
        if ($uid === '') {
            throw new \RuntimeException('未获取到第三方用户标识');
        }
        return [
            'social_uid' => $uid,
            'nickname' => (string) ($json['nickname'] ?? ''),
            'faceimg' => (string) ($json['faceimg'] ?? ''),
            'access_token' => (string) ($json['access_token'] ?? ''),
        ];
    }

    /**
     * 调用聚合 connect.php：先按配置的 URL（多为 https），若本地 curl 或上游 JSON 报 SSL 链错误，则自动改用 http 重试一次。
     * 说明：部分环境本地 curl 已关闭校验仍看到该文案，实为上游接口在 JSON.msg 中返回了其服务端的 SSL 错误。
     *
     * @param array<string,mixed> $c loadConfig 结果
     * @param array<string,string> $q 查询参数
     * @return array<string,mixed>
     */
    private static function requestConnect(array $c, array $q): array
    {
        $base = rtrim((string) ($c['connect_url'] ?? ''), '/');
        if ($base === '') {
            throw new \RuntimeException('未配置聚合接口地址');
        }
        $sep = str_contains($base, '?') ? '&' : '?';
        $url = $base . $sep . http_build_query($q);

        $json = null;
        try {
            $json = self::httpGetJson($url);
        } catch (\RuntimeException $e) {
            if (!self::isSslRelatedMessage($e->getMessage())) {
                throw $e;
            }
            $httpUrl = self::httpsToHttpUrl($url);
            if ($httpUrl === null) {
                throw $e;
            }
            $json = self::httpGetJson($httpUrl);
        }

        if (($json['code'] ?? -1) !== 0) {
            $msg = (string) ($json['msg'] ?? '');
            if ($msg !== '' && self::isSslRelatedMessage($msg)) {
                $httpUrl = self::httpsToHttpUrl($url);
                if ($httpUrl !== null) {
                    $json = self::httpGetJson($httpUrl);
                }
            }
        }

        return $json;
    }

    private static function isSslRelatedMessage(string $text): bool
    {
        $t = strtolower($text);
        return str_contains($t, 'ssl certificate problem')
            || str_contains($t, 'unable to get local issuer certificate')
            || str_contains($t, 'certificate verify failed');
    }

    private static function httpsToHttpUrl(string $url): ?string
    {
        if (str_starts_with(strtolower($url), 'https://')) {
            return (string) preg_replace('#^https://#i', 'http://', $url, 1);
        }
        return null;
    }

    private static function httpGetJson(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl 初始化失败');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        // 忽略 SSL 证书校验：用于解决环境缺少 CA 根证书导致的
        // SSL certificate problem: unable to get local issuer certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if (defined('CURLSSLOPT_NO_REVOKE')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NO_REVOKE);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException($err !== '' ? $err : '聚合接口无响应');
        }

        if ($code >= 400) {
            throw new \RuntimeException('聚合接口 HTTP ' . $code);
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('聚合接口返回非 JSON');
        }
        return $data;
    }
}
