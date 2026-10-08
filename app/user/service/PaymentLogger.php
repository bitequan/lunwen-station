<?php

namespace app\user\service;


/**
 * 支付日志服务类（本地日志文件）
 */
class PaymentLogger
{
    /**
     * 记录支付日志
     * @param string $orderNo 订单号
     * @param string $paymentMethod 支付方式
     * @param string $logType 日志类型：create-创建支付，callback-回调，query-查询，error-错误，success-成功
     * @param string $logContent 日志内容
     * @param array $extraData 额外数据
     */
    public static function log($orderNo, $paymentMethod, $logType, $logContent, $extraData = [])
    {
        try {
            $logData = [
                'order_no' => $orderNo,
                'payment_method' => $paymentMethod,
                'log_type' => $logType,
                'log_content' => $logContent,
                'extra_data' => $extraData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // 使用ThinkPHP的日志系统记录
            $logMessage = sprintf(
                "[支付日志] 订单号: %s | 支付方式: %s | 日志类型: %s | 内容: %s",
                $orderNo,
                $paymentMethod,
                $logType,
                is_array($logContent) ? json_encode($logContent, JSON_UNESCAPED_UNICODE) : $logContent
            );
            
            // 根据日志类型选择不同的日志级别
            switch ($logType) {
                case 'error':
                    break;
                case 'warning':
                    break;
                case 'success':
                    break;
                default:
                    break;
            }
            
            // 同时写入专门的支付日志文件
            self::writeToPaymentLogFile($logData);
            
        } catch (\Exception $e) {
            // 如果日志记录失败，至少记录到系统日志
        }
    }
    
    /**
     * 写入专门的支付日志文件
     * @param array $logData 日志数据
     */
    private static function writeToPaymentLogFile($logData)
    {
        try {
            $logDir = runtime_path() . 'payment/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . 'payment_' . date('Y-m-d') . '.log';
            
            $logLine = sprintf(
                "[%s] %s | 订单号: %s | 支付方式: %s | 类型: %s | IP: %s | 数据: %s\n",
                date('Y-m-d H:i:s'),
                $logData['log_content'],
                $logData['order_no'],
                $logData['payment_method'],
                $logData['log_type'],
                $logData['ip_address'],
                json_encode($logData['extra_data'] ?? [], JSON_UNESCAPED_UNICODE)
            );
            
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (\Exception $e) {
            // 如果写入文件失败，只记录到系统日志
        }
    }
    
    /**
     * 记录支付创建日志
     */
    public static function logCreate($orderNo, $paymentMethod, $paymentData)
    {
        self::log($orderNo, $paymentMethod, 'create', '创建支付请求', $paymentData);
    }
    
    /**
     * 记录支付回调日志
     */
    public static function logCallback($orderNo, $paymentMethod, $callbackData)
    {
        self::log($orderNo, $paymentMethod, 'callback', '支付回调通知', $callbackData);
    }
    
    /**
     * 记录支付查询日志
     */
    public static function logQuery($orderNo, $paymentMethod, $queryData)
    {
        self::log($orderNo, $paymentMethod, 'query', '查询支付状态', $queryData);
    }
    
    /**
     * 记录支付错误日志
     */
    public static function logError($orderNo, $paymentMethod, $errorMessage, $errorData = [])
    {
        self::log($orderNo, $paymentMethod, 'error', $errorMessage, $errorData);
    }
    
    /**
     * 记录支付成功日志
     */
    public static function logSuccess($orderNo, $paymentMethod, $successData)
    {
        self::log($orderNo, $paymentMethod, 'success', '支付成功', $successData);
    }
    
    /**
     * 记录支付警告日志
     */
    public static function logWarning($orderNo, $paymentMethod, $warningMessage, $warningData = [])
    {
        self::log($orderNo, $paymentMethod, 'warning', $warningMessage, $warningData);
    }
    
    /**
     * 获取支付日志文件列表
     * @param string $date 日期，格式：Y-m-d
     * @return array
     */
    public static function getLogFiles($date = '')
    {
        try {
            $logDir = runtime_path() . 'payment/';
            if (!is_dir($logDir)) {
                return [];
            }
            
            $files = [];
            $pattern = $logDir . 'payment_*.log';
            
            foreach (glob($pattern) as $file) {
                $filename = basename($file);
                $fileDate = substr($filename, 8, 10); // 提取日期部分
                
                if (empty($date) || $fileDate === $date) {
                    $files[] = [
                        'filename' => $filename,
                        'path' => $file,
                        'date' => $fileDate,
                        'size' => filesize($file),
                        'modified' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            
            // 按日期倒序排序
            usort($files, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
            
            return $files;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 读取支付日志文件内容
     * @param string $date 日期，格式：Y-m-d
     * @param int $limit 限制行数
     * @return array
     */
    public static function readLogFile($date, $limit = 1000)
    {
        try {
            $logFile = runtime_path() . 'payment/payment_' . $date . '.log';
            
            if (!file_exists($logFile)) {
                return ['lines' => [], 'total' => 0];
            }
            
            // 读取文件内容
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            // 过滤空行
            $lines = array_filter($lines, function($line) {
                return !empty(trim($line));
            });
            
            // 限制行数
            $total = count($lines);
            $lines = array_slice($lines, -$limit);
            
            // 反转数组，使最新的日志在前面
            $lines = array_reverse($lines);
            
            return [
                'lines' => $lines,
                'total' => $total,
                'date' => $date,
                'file' => $logFile
            ];
            
        } catch (\Exception $e) {
            return ['lines' => [], 'total' => 0];
        }
    }
}
