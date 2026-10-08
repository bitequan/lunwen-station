<?php
declare (strict_types = 1);

namespace app\common;

use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 邮件服务类
 * 用于发送各种类型的邮件
 */
class MailService
{
    /**
     * 邮件配置缓存
     * @var array|null
     */
    private static $emailConfig = null;
    
    /**
     * 获取邮件配置
     * @return array|null
     */
    private static function getEmailConfig()
    {
        if (self::$emailConfig === null) {
            self::$emailConfig = Db::name('email_config')
                ->where('id', 1)
                ->find();
        }
        
        return self::$emailConfig;
    }
    
    /**
     * 检查邮件服务是否可用
     * @return bool
     */
    public static function isAvailable(): bool
    {
        $config = self::getEmailConfig();
        return $config && $config['status'] == 1;
    }
    
    /**
     * 发送验证码邮件
     * @param string $to 收件人邮箱
     * @param string $verifyCode 验证码
     * @param string $type 验证码类型：register注册, reset_password重置密码
     * @return bool 发送结果
     */
    public static function sendVerifyCode(string $to, string $verifyCode, string $type = 'register'): bool
    {
        try {
            $config = self::getEmailConfig();
            
            if (!$config || $config['status'] != 1) {
                // 如果没有配置邮件服务器，记录日志并返回成功（测试环境）
                return true;
            }
            
            // 根据类型设置邮件主题
            $subjectMap = [
                'register'       => '注册验证码',
                'reset_password' => '重置密码验证码',
                // 本页面绑定 / 修改邮箱统一使用“邮箱绑定验证”
                'change_email'   => '邮箱绑定验证',
            ];
            
            $siteName = self::getSiteName();
            $subject = ($subjectMap[$type] ?? '验证码') . ' - ' . $siteName;
            
            // 创建邮件内容
            $content = self::createVerifyCodeContent($verifyCode, $type, $config['from_name']);
            
            // 异步发送邮件（不阻塞用户请求）
            return self::sendAsync($to, $subject, $content, $verifyCode, $type);
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 异步发送邮件（不阻塞用户请求）
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $content 邮件内容
     * @param string $verifyCode 验证码（用于日志）
     * @param string $type 邮件类型
     * @return bool 立即返回true，实际发送在后台进行
     */
    private static function sendAsync(string $to, string $subject, string $content, string $verifyCode, string $type): bool
    {
        // 记录开始发送
        
        // 使用后台进程发送邮件
        if (function_exists('shell_exec') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Linux/Unix系统使用后台进程
            $scriptPath = dirname(__DIR__) . '/../public/index.php';
            $command = sprintf(
                'php %s /user/api/sendEmailBackground > /dev/null 2>&1 &',
                escapeshellarg($scriptPath)
            );
            
            shell_exec($command);
        } else {
            // Windows系统或无法使用shell_exec时，使用快速同步发送
            try {
                // 设置更短的超时时间
                $originalTimeout = ini_get('max_execution_time');
                set_time_limit(10); // 10秒超时
                
                $result = self::send($to, $subject, $content);
                
                // 恢复原始超时设置
                if ($originalTimeout > 0) {
                    set_time_limit((int)$originalTimeout);
                }
                
                if (!$result) {
                }
            } catch (\Exception $e) {
            }
        }
        
        // 立即返回成功，不等待邮件发送完成
        return true;
    }
    
    /**
     * 发送通用邮件
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $content 邮件内容（HTML格式）
     * @param string $altContent 纯文本内容（可选）
     * @return bool 发送结果
     */
    public static function send(string $to, string $subject, string $content, string $altContent = ''): bool
    {
        try {
            $config = self::getEmailConfig();
            
            if (!$config || $config['status'] != 1) {
                return false;
            }
            
            $mail = new PHPMailer(true);
            
            // 服务器设置
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            
            // 自动检测加密方式
            $port = (int)$config['smtp_port'];
            $encryption = self::autoDetectEncryption($port, $config['smtp_encryption']);
            $mail->SMTPSecure = self::getEncryption($encryption);
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';
            
            // 设置超时时间（防止请求卡住）
            $mail->Timeout    = 30; // 增加超时时间到30秒
            $mail->SMTPKeepAlive = false; // 不保持连接
            
            // 设置连接超时
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // 调试模式（始终记录详细日志以便排查问题）
            $mail->SMTPDebug = 2;
            $debugMessages = [];
            $mail->Debugoutput = function($str, $level) use (&$debugMessages) {
                $debugMessages[] = $str;
            };
            
            // 发件人
            $mail->setFrom($config['from_email'], $config['from_name']);
            
            // 收件人
            $mail->addAddress($to);
            
            // 内容
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content;
            $mail->AltBody = $altContent ?: strip_tags($content);
            
            // 记录发送前的配置信息（用于排查问题）
            
            // 发送邮件
            $startTime = microtime(true);
            $result = $mail->send();
            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result) {
                return $result;
            } else {
                $errorInfo = $mail->ErrorInfo;
                return false;
            }
            
        } catch (Exception $e) {
            $errorInfo = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            if (isset($debugMessages) && !empty($debugMessages)) {
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取加密方式
     * @param string $encryption 加密方式字符串
     * @return string PHPMailer加密方式
     */
    private static function getEncryption(string $encryption): string
    {
        $map = [
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            'tls' => PHPMailer::ENCRYPTION_STARTTLS,
            'none' => ''
        ];
        
        return $map[$encryption] ?? '';
    }
    
    /**
     * 根据端口自动检测加密方式
     * @param int $port SMTP端口
     * @param string $encryption 配置的加密方式
     * @return string 修正后的加密方式
     */
    private static function autoDetectEncryption(int $port, string $encryption): string
    {
        // 如果已经指定了加密方式，使用指定的
        if ($encryption && $encryption !== 'none') {
            return $encryption;
        }
        
        // 根据端口自动检测
        if ($port == 465) {
            return 'ssl';
        } elseif ($port == 587) {
            return 'tls';
        }
        
        return $encryption;
    }
    
    /**
     * 创建验证码邮件内容
     * @param string $verifyCode 验证码
     * @param string $type 验证码类型
     * @param string $siteName 站点名称
     * @return string HTML邮件内容
     */
    private static function createVerifyCodeContent(string $verifyCode, string $type, string $siteName = '用户中心'): string
    {
        // 获取网站配置中的站点名称
        $siteName = self::getSiteName();
        
        $typeText = [
            'register' => '注册',
            'reset_password' => '重置密码'
        ][$type] ?? '验证';
        
        $expireMinutes = self::getVerifyCodeExpireMinutes();
        $currentYear = date('Y');
        
        // 读取模板文件
        $templatePath = dirname(__DIR__) . '/common/templates/email_verify_code.html';
        
        if (file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
            
            // 替换模板变量
            $replacements = [
                '{$typeText}' => $typeText,
                '{$siteName}' => $siteName,
                '{$verifyCode}' => $verifyCode,
                '{$expireMinutes}' => $expireMinutes,
                '{$currentYear}' => $currentYear
            ];
            
            return str_replace(array_keys($replacements), array_values($replacements), $template);
        } else {
            // 如果模板文件不存在，使用内置模板（兼容性）
            return self::createBuiltInVerifyCodeContent($verifyCode, $type, $siteName, $typeText, $expireMinutes);
        }
    }
    
    /**
     * 创建内置验证码邮件内容（兼容性）
     * @param string $verifyCode 验证码
     * @param string $type 验证码类型
     * @param string $siteName 站点名称
     * @param string $typeText 类型文本
     * @param int $expireMinutes 过期时间（分钟）
     * @return string HTML邮件内容
     */
    private static function createBuiltInVerifyCodeContent(string $verifyCode, string $type, string $siteName, string $typeText, int $expireMinutes): string
    {
        $currentYear = date('Y');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$typeText}验证码</title>
    <style>
        body { 
            font-family: 'Microsoft YaHei', 'Segoe UI', Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px; 
            text-align: center; 
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .content { 
            background: #fff; 
            padding: 40px; 
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .code-container {
            text-align: center;
            margin: 30px 0;
        }
        .code { 
            display: inline-block;
            font-size: 36px; 
            font-weight: bold; 
            color: #1890ff; 
            padding: 20px 40px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            border-radius: 10px;
            letter-spacing: 10px;
            border: 2px dashed #1890ff;
            box-shadow: 0 4px 15px rgba(24, 144, 255, 0.1);
        }
        .tips {
            background: #f8f9fa;
            border-left: 4px solid #1890ff;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .tips h3 {
            margin-top: 0;
            color: #1890ff;
        }
        .tips ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .tips li {
            margin-bottom: 5px;
        }
        .footer { 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 1px solid #e8e8e8; 
            color: #999; 
            font-size: 12px; 
            text-align: center; 
        }
        .btn {
            display: inline-block;
            background: #1890ff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .btn:hover {
            background: #40a9ff;
        }
        .expire-notice {
            color: #ff4d4f;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            <h2>亲爱的用户：</h2>
            <p>感谢您使用{$siteName}！</p>
            <p>您正在进行{$typeText}操作，验证码为：</p>
            
            <div class="code-container">
                <div class="code">{$verifyCode}</div>
            </div>
            
            <div class="expire-notice">
                ⏰ 请在{$expireMinutes}分钟内完成验证
            </div>
            
            <div class="tips">
                <h3>🔒 安全提示：</h3>
                <ul>
                    <li>请勿将验证码告知他人，包括{$siteName}客服</li>
                    <li>验证码仅限本次操作使用，有效期为{$expireMinutes}分钟</li>
                    <li>如非本人操作，请立即修改账户密码</li>
                    <li>请妥善保管您的账户信息</li>
                </ul>
            </div>
            
            <p>如果这不是您的操作，请忽略此邮件。</p>
            <p>如有任何问题，请联系我们的客服。</p>
            
            <p style="margin-top: 30px;">祝您使用愉快！</p>
            <p><strong>{$siteName}团队</strong></p>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>© {$currentYear} {$siteName} 版权所有</p>
            <p style="font-size: 11px; color: #ccc;">为了保障您的账户安全，请勿泄露此邮件内容</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * 获取网站配置
     * @return array 网站配置
     */
    private static function getSiteConfig(): array
    {
        try {
            // 从 ad_system_config 表获取JSON格式配置
            $config = Db::name('system_config')
                ->where('config_key', 'site_config')
                ->field('config_value')
                ->find();
            
            if ($config && !empty($config['config_value'])) {
                $siteConfig = json_decode($config['config_value'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($siteConfig)) {
                    return $siteConfig;
                }
            }
            
            return [];
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 获取网站名称
     * @return string 网站名称
     */
    private static function getSiteName(): string
    {
        $siteConfig = self::getSiteConfig();
        
        if (!empty($siteConfig['site_name'])) {
            return $siteConfig['site_name'];
        }
        
        // 默认返回邮件配置中的发件人名称
        $emailConfig = self::getEmailConfig();
        if ($emailConfig && !empty($emailConfig['from_name'])) {
            return $emailConfig['from_name'];
        }
        
        return '用户中心';
    }
    
    /**
     * 获取验证码有效期（分钟）
     * @return int
     */
    private static function getVerifyCodeExpireMinutes(): int
    {
        try {
            // 从 system_config 表获取安全配置
            $config = Db::name('system_config')
                ->where('config_key', 'security_config')
                ->field('config_value')
                ->find();
            
            if ($config && !empty($config['config_value'])) {
                $securityConfig = json_decode($config['config_value'], true);
                if (isset($securityConfig['email_verify_expire'])) {
                    return (int)$securityConfig['email_verify_expire'];
                }
            }
            
            // 默认10分钟
            return 10;
            
        } catch (\Exception $e) {
            return 10;
        }
    }
    
    /**
     * 测试邮件配置
     * @param string $testEmail 测试邮箱地址
     * @return array 测试结果
     */
    public static function testConfig(string $testEmail): array
    {
        try {
            $config = self::getEmailConfig();
            
            if (!$config) {
                return [
                    'success' => false,
                    'message' => '邮件配置不存在'
                ];
            }
            
            if ($config['status'] != 1) {
                return [
                    'success' => false,
                    'message' => '邮件服务未启用'
                ];
            }
            
            // 测试连接
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = self::getEncryption($config['smtp_encryption']);
            $mail->Port       = (int)$config['smtp_port'];
            $mail->Timeout    = 10;
            
            // 测试连接
            if (!$mail->smtpConnect()) {
                return [
                    'success' => false,
                    'message' => 'SMTP连接失败: ' . $mail->ErrorInfo
                ];
            }
            
            $mail->smtpClose();
            
            // 发送测试邮件
            $siteName = self::getSiteName();
            $subject = '邮件配置测试 - ' . $siteName;
            $content = '<h1>邮件配置测试</h1><p>这是一封测试邮件，用于验证邮件配置是否正确。</p><p>如果收到此邮件，说明邮件配置成功！</p>';
            
            $result = self::send($testEmail, $subject, $content);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => '测试邮件发送成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '测试邮件发送失败'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'PHPMailer异常: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '测试异常: ' . $e->getMessage()
            ];
        }
    }
}
