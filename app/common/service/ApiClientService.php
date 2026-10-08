<?php
namespace app\common\service;

use think\facade\Config;

/**
 * API客户端服务类
 * 对接TokenAPI的统一接口服务
 */
class ApiClientService
{
    /**
     * @var string API基础URL
     */
    protected $baseUrl;
    
    /**
     * @var string API Token
     */
    protected $apiKey;
    
    /**
     * @var int 请求超时时间（秒）
     */
    protected $timeout = 300;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $config = Config::get('docking');
        $this->baseUrl = $config['api_url'] ?? '';
        $this->apiKey = $config['api_token'] ?? '';
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $url 请求URL
     * @param string $method 请求方法 GET|POST
     * @param array $data 请求数据
     * @param array $files 文件数据
     * @return array 响应数据
     */
    public function sendRequest($url, $method = 'GET', $data = null, $files = [])
    {
        $fullUrl = $this->baseUrl . $url;
        // 初始化cURL
        $ch = curl_init();
        
        // 设置请求头
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        
        // 如果是文件上传请求
        if (!empty($files)) {
            $headers = [
                'Authorization: Bearer ' . $this->apiKey,
            ];
            
            $postData = [];
            foreach ($data as $key => $value) {
                $postData[$key] = $value;
            }
            
            foreach ($files as $key => $file) {
                if (is_array($file) && isset($file['tmp_name'])) {
                    $postData[$key] = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
                }
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } elseif ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        } elseif ($method === 'GET' && $data) {
            $fullUrl .= '?' . http_build_query($data);
        }
        
        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // 执行请求
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'code' => 0,
                'msg' => '请求失败: ' . $error,
                'data' => null
            ];
        }
        
        // 解析响应
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'code' => 0,
                'msg' => '响应解析失败: ' . json_last_error_msg(),
                'data' => $response
            ];
        }
        
        return $result;
    }
    
    /**
     * 发送GET请求
     * 
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @return array 响应数据
     */
    public function get($url, $params = [])
    {
        return $this->sendRequest($url, 'GET', $params);
    }
    
    /**
     * 发送POST请求
     * 
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @return array 响应数据
     */
    public function post($url, $data = [])
    {
        return $this->sendRequest($url, 'POST', $data);
    }
    
    /**
     * 发送带自定义超时时间的POST请求
     * 
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param int $timeout 超时时间（秒）
     * @return array 响应数据
     */
    public function postWithTimeout($url, $data = [], $timeout = null)
    {
        $originalTimeout = $this->timeout;
        if ($timeout !== null) {
            $this->timeout = $timeout;
        }
        
        try {
            $result = $this->sendRequest($url, 'POST', $data);
        } finally {
            // 恢复原始超时时间
            $this->timeout = $originalTimeout;
        }
        
        return $result;
    }
    
    /**
     * 上传文件
     *
     * @param string $url 请求URL
     * @param string $data 表单数据
     * @param array $files 文件数据
     * @return array 响应数据
     */
    public function upload($url, $data = [], $files = [])
    {
        return $this->sendRequest($url, 'POST', $data, $files);
    }

    /**
     * 真流式 SSE 透传：调用上游流式 openapi，逐帧原样转发给当前 HTTP 客户端。
     * 用于大纲生成等 SSE 接口（前端所见即上游真实生成进度，非"攒完整结果再回放"的假流式）。
     *
     * 帧约定：data: {"content":"增量"} ... data: {"done":true[, "outline_no":"OL..."]}
     * 上游返回 JSON（参数校验错误等非流式响应）或连接失败时，包装为 SSE 错误帧：
     *   data: {"error":"msg"} + data: {"done":true,"failed":true}
     *
     * @param string $path 上游路径（含 /openapi 前缀，如 /openapi/paper/generateOutlineStream）
     * @param array $payload POST JSON 载荷
     * @param callable|null $onFrame 旁路回调 function(array $frameData)，每解析到一个完整 data 帧触发一次（供调用方捕获 done/outline_no 等；在帧透传前同步调用）
     */
    public function passthroughSse(string $path, array $payload = [], ?callable $onFrame = null): void
    {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // 关闭 Nginx 缓冲
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @set_time_limit(300);

        $jsonBuf = null; // 非 null：上游返回的是 JSON（非 SSE 响应），聚合后包装为错误帧
        $frameBuf = '';  // SSE 帧解析缓冲（旁路捕获用，帧以 \n\n 分隔）
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . $path,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: text/event-stream',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$jsonBuf, &$frameBuf, $onFrame) {
                if ($jsonBuf !== null) {
                    $jsonBuf .= $chunk;
                    return strlen($chunk);
                }
                $first = ltrim(substr($chunk, 0, 1));
                if ($first === '{') {
                    $jsonBuf = $chunk; // 上游 JSON 错误响应（参数校验等）→ 聚合后包装为 SSE 错误帧
                    return strlen($chunk);
                }
                if ($onFrame !== null) {
                    $frameBuf .= $chunk;
                    while (($i = strpos($frameBuf, "\n\n")) !== false) {
                        $frame = substr($frameBuf, 0, $i);
                        $frameBuf = substr($frameBuf, $i + 2);
                        foreach (explode("\n", $frame) as $line) {
                            if (strpos($line, 'data:') !== 0) {
                                continue;
                            }
                            $d = json_decode(trim(substr($line, 5)), true);
                            if (is_array($d)) {
                                $onFrame($d);
                            }
                        }
                    }
                }
                echo $chunk; // SSE 帧原样透传
                @ob_flush();
                @flush();
                return strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($jsonBuf !== null) {
            $j   = json_decode($jsonBuf, true);
            $msg = is_array($j) ? trim((string)($j['msg'] ?? '')) : '';
            if ($msg === '') {
                $msg = '上游接口返回异常';
            }
            echo 'data: ' . json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE) . "\n\n";
            echo 'data: ' . json_encode(['done' => true, 'failed' => true], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush();
            flush();
            exit;
        }
        if ($curlErr !== '' || ($httpCode > 0 && ($httpCode < 200 || $httpCode >= 300))) {
            // 连接失败/超时/非 2xx：可能已透传部分内容帧，补发错误帧告知前端终止
            $err = $curlErr !== '' ? $curlErr : ('HTTP ' . $httpCode);
            echo 'data: ' . json_encode(['error' => '连接上游失败：' . $err], JSON_UNESCAPED_UNICODE) . "\n\n";
            echo 'data: ' . json_encode(['done' => true, 'failed' => true], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush();
            flush();
            exit;
        }
        // 流正常结束：done 帧已由上游透传，无需附加输出
        exit;
    }
}
