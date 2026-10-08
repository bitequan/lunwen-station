<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 微信公众平台消息加解密（明文 / 兼容 / 安全模式共用算法）
 * 参考官方 WXBizMsgCrypt 示例实现。
 */
final class WechatMpBizMsgCrypt
{
    private const PKCS7_BLOCK = 32;

    private string $aesKey;

    private string $iv;

    public function __construct(
        private string $token,
        string $encodingAesKey43,
        private string $appId
    ) {
        if (strlen($encodingAesKey43) !== 43) {
            throw new \InvalidArgumentException('EncodingAESKey 须为 43 位');
        }
        $decoded = base64_decode($encodingAesKey43 . '=', true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new \InvalidArgumentException('EncodingAESKey 无效');
        }
        $this->aesKey = $decoded;
        $this->iv = substr($this->aesKey, 0, 16);
    }

    public static function isValidEncodingAesKey(string $key): bool
    {
        if (strlen($key) !== 43) {
            return false;
        }
        $decoded = base64_decode($key . '=', true);

        return $decoded !== false && strlen($decoded) === 32;
    }

    /**
     * URL 校验（GET）：解密 echostr，成功返回明文，失败返回 null
     */
    public function verifyUrl(string $msgSignature, string $timestamp, string $nonce, string $echoStr): ?string
    {
        $calc = $this->sha1Signature($this->token, $timestamp, $nonce, $echoStr);
        if (!hash_equals($calc, $msgSignature)) {
            return null;
        }

        return $this->decryptCipherToPlain($echoStr);
    }

    /**
     * 解密 POST 体，成功返回明文 XML
     */
    public function decryptMsg(string $msgSignature, string $timestamp, string $nonce, string $postXml): ?string
    {
        $encrypt = $this->extractEncryptFromXml($postXml);
        if ($encrypt === null) {
            return null;
        }
        $calc = $this->sha1Signature($this->token, $timestamp, $nonce, $encrypt);
        if (!hash_equals($calc, $msgSignature)) {
            return null;
        }

        return $this->decryptCipherToPlain($encrypt);
    }

    /**
     * 将被动回复明文 XML 加密为公众平台要求的格式
     */
    public function encryptXml(string $plainXml): string
    {
        $cipher = $this->encryptPlainToCipher($plainXml);
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(8));
        $sig = $this->sha1Signature($this->token, $timestamp, $nonce, $cipher);

        return '<xml>'
            . '<Encrypt><![CDATA[' . $cipher . ']]></Encrypt>'
            . '<MsgSignature><![CDATA[' . $sig . ']]></MsgSignature>'
            . '<TimeStamp>' . $timestamp . '</TimeStamp>'
            . '<Nonce><![CDATA[' . $nonce . ']]></Nonce>'
            . '</xml>';
    }

    private function sha1Signature(string $token, string $timestamp, string $nonce, string $encrypt): string
    {
        $arr = [$token, $timestamp, $nonce, $encrypt];
        sort($arr, SORT_STRING);

        return sha1(implode($arr));
    }

    private function extractEncryptFromXml(string $xml): ?string
    {
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($sx === false) {
            return null;
        }
        $e = (string) ($sx->Encrypt ?? '');

        return $e !== '' ? $e : null;
    }

    private function decryptCipherToPlain(string $encryptBase64): ?string
    {
        $decrypted = openssl_decrypt($encryptBase64, 'AES-256-CBC', $this->aesKey, OPENSSL_ZERO_PADDING, $this->iv);
        if ($decrypted === false) {
            return null;
        }
        $result = $this->pkcs7Decode($decrypted);
        if ($result === null || strlen($result) < 20) {
            return null;
        }
        $content = substr($result, 16);
        $lenArr = unpack('N', substr($content, 0, 4));
        if ($lenArr === false) {
            return null;
        }
        $xmlLen = (int) $lenArr[1];
        if ($xmlLen < 0 || 4 + $xmlLen > strlen($content)) {
            return null;
        }
        $xmlContent = substr($content, 4, $xmlLen);
        $fromId = substr($content, 4 + $xmlLen);
        if (!hash_equals($fromId, $this->appId)) {
            return null;
        }

        return $xmlContent;
    }

    private function encryptPlainToCipher(string $plainXml): string
    {
        $random = $this->randomBytes(16);
        $packed = $random . pack('N', strlen($plainXml)) . $plainXml . $this->appId;
        $padded = $this->pkcs7Encode($packed);
        $enc = openssl_encrypt($padded, 'AES-256-CBC', $this->aesKey, OPENSSL_ZERO_PADDING, $this->iv);
        if ($enc === false) {
            throw new \RuntimeException('微信消息加密失败');
        }

        return $enc;
    }

    private function randomBytes(int $n): string
    {
        $s = '';
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $max = strlen($pool) - 1;
        for ($i = 0; $i < $n; $i++) {
            $s .= $pool[random_int(0, $max)];
        }

        return $s;
    }

    private function pkcs7Encode(string $text): string
    {
        $block = self::PKCS7_BLOCK;
        $pad = $block - (strlen($text) % $block);
        if ($pad === 0) {
            $pad = $block;
        }

        return $text . str_repeat(chr($pad), $pad);
    }

    private function pkcs7Decode(string $text): ?string
    {
        if ($text === '') {
            return null;
        }
        $pad = ord($text[strlen($text) - 1]);
        if ($pad < 1 || $pad > self::PKCS7_BLOCK) {
            return null;
        }
        if (strlen($text) < $pad) {
            return null;
        }

        return substr($text, 0, -$pad);
    }
}
