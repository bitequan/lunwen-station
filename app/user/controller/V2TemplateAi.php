<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Request;
use think\facade\Config;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;
use app\common\utils\AgentIdHelper;

/**
 * 论文模板管理 openapi 桥接控制器
 *
 * 前端 template.vue（模板制作）、template-fanwen.vue（范文导入）、user.vue（我的模板）
 * 走本站 /api/template/*，本控制器桥接到主站 /openapi/template/*（Bearer 出站，docking.api_url/api_token）。
 *
 * 计费模型：模板管理免费（上游不扣费，本站不扣余额）。
 * agent_str 固定传本站用户对接标识（AgentIdHelper 前缀格式，如 adweb_12）：模板按下游用户隔离归属（列表/详情/改删均带 agent_str 校验）。
 *
 * 响应适配（本站前端契约 ≠ 上游原样结构，出站做变换）：
 *   - 预签名（uploadCover/uploadLogo）：上游 {upload_url,upload_headers,file_url} → 本站 {put_url,content_type,download_url}
 *   - 列表条目：补 title/cover 别名；剥离 agent_str（内部渠道标识不下发）
 *   - demonstrate：前端传 jsondata → 上游 conjson
 *   - importFanwenStream：SSE 透传上游 /template/extractStream，done 帧归一化为 {status,data:{template_id,...}}
 *
 * 返回统一结构 { code, show, msg, data }（importFanwenStream 为 text/event-stream）。
 */
class V2TemplateAi
{
    /** 当前登录用户 ID；未登录返回 0 */
    private function uid(): int
    {
        return UserTokenService::getUserId(UserTokenService::readRequestToken());
    }

    /** 本站用户对接标识（agent_str = 管理员配置前缀 + 用户ID，如 adweb_12），主站按其做下游用户隔离归属 */
    private function agentStr(int $uid): string
    {
        return (string) AgentIdHelper::build($uid);
    }

    private static $jsonBody = null;

    /** 读取请求参数：兼容表单、JSON body 与 GET 查询串 */
    private function body(): array
    {
        $post = Request::post();
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
        $get = Request::get();
        if (!is_array($get)) {
            $get = [];
        }
        return array_merge($get, $post, self::$jsonBody);
    }

    /** 出站请求主站 openapi */
    private function call(string $path, string $method = 'GET', array $params = []): array
    {
        $c = new ApiClientService();
        return $method === 'GET' ? $c->get($path, $params) : $c->post($path, $params);
    }

    /** 统一失败返回 */
    private function fail($msg): \think\response\Json
    {
        return JsonService::fail($msg ?: '操作失败，请稍后重试', [], 0, 1);
    }

    /** 登录守卫：未登录返回 authExpired 响应，已登录返回 null */
    private function guard()
    {
        if (!$this->uid()) {
            return JsonService::authExpired();
        }
        return null;
    }

    /** 剥离数组/对象中的 agent_str 键（内部渠道标识不下发，递归一层） */
    private function stripAgentStr(array &$arr): void
    {
        foreach ($arr as $k => $v) {
            if ($k === 'agent_str') {
                unset($arr[$k]);
            } elseif (is_array($v)) {
                $this->stripAgentStr($v);
            }
        }
    }

    /** 上游预签名结构 → 本站前端契约 {put_url, content_type, download_url} */
    private function presignData(array $up): array
    {
        return [
            'put_url'      => (string)($up['upload_url'] ?? ''),
            'content_type' => (string)($up['upload_headers']['Content-Type'] ?? ($up['upload_headers']['content-type'] ?? 'application/octet-stream')),
            'download_url' => (string)($up['file_url'] ?? ''),
            'expires_at'   => (int)($up['expires_at'] ?? 0),
        ];
    }

    // ==================== 模板列表 ====================

    /** GET /api/template/list?page=&page_size=&keyword= → {list, total, page, page_size, total_pages} */
    public function list(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $r = $this->call('/openapi/template/list', 'GET', [
            'page'      => max(1, (int)($p['page'] ?? 1)),
            'page_size' => max(1, min(100, (int)($p['page_size'] ?? 20))),
            'agent_str' => $this->agentStr($this->uid()),
            'keyword'   => trim((string)($p['keyword'] ?? '')),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取模板列表失败');
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        // 条目装饰：title/cover 别名（前端回退链兼容）；剥离 agent_str
        $rows = is_array($d['list'] ?? null) ? $d['list'] : [];
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }
            if (isset($row['name']) && !isset($row['title'])) {
                $row['title'] = $row['name'];
            }
            if (!empty($row['avt'])) {
                if (!isset($row['cover'])) {
                    $row['cover'] = $row['avt'];
                }
                if (!isset($row['image'])) {
                    $row['image'] = $row['avt'];
                }
            }
            $row['source_type'] = (int)($row['source_type'] ?? 0);
        }
        unset($row);
        $d['list'] = $rows;
        $this->stripAgentStr($d);
        return JsonService::data($d);
    }

    // ==================== 模板详情 ====================

    /** GET /api/template/detail?id= → 模板对象（含 conjson 字符串，前端自行 parse） */
    public function detail(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) {
            return $this->fail('模板 id 不能为空');
        }
        $r = $this->call('/openapi/template/detail', 'GET', [
            'id'        => $id,
            'agent_str' => $this->agentStr($this->uid()),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '模板不存在或无权查看');
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        $this->stripAgentStr($d);
        return JsonService::data($d);
    }

    // ==================== 保存 / 更新 / 删除 ====================

    /** POST /api/template/save {name, logo, conjson} → {id} */
    public function save(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $name = trim((string)($p['name'] ?? ''));
        if ($name === '') {
            return $this->fail('请输入模板名称');
        }
        $payload = [
            'name'      => $name,
            'logo'      => (string)($p['logo'] ?? ''),
            'agent_str' => $this->agentStr($this->uid()),
        ];
        if (isset($p['conjson']) && is_array($p['conjson'])) {
            $payload['conjson'] = $p['conjson'];
        }
        $r = $this->call('/openapi/template/save', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '模板保存失败，请稍后重试');
        }
        return JsonService::data($r['data'] ?? []);
    }

    /** POST /api/template/update {id, name, logo, conjson} → {id} */
    public function update(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) {
            return $this->fail('模板 id 不能为空');
        }
        $payload = [
            'id'        => $id,
            'name'      => trim((string)($p['name'] ?? '')),
            'logo'      => (string)($p['logo'] ?? ''),
            'agent_str' => $this->agentStr($this->uid()),
        ];
        if (isset($p['conjson']) && is_array($p['conjson'])) {
            $payload['conjson'] = $p['conjson'];
        }
        $r = $this->call('/openapi/template/update', 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '模板更新失败，请稍后重试');
        }
        return JsonService::data($r['data'] ?? []);
    }

    /** POST /api/template/delete {id} → {id}（上游软删除） */
    public function delete(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) {
            return $this->fail('模板 id 不能为空');
        }
        $r = $this->call('/openapi/template/delete', 'POST', [
            'id'        => $id,
            'agent_str' => $this->agentStr($this->uid()),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '模板删除失败，请稍后重试');
        }
        return JsonService::data($r['data'] ?? []);
    }

    // ==================== 直传预签名 ====================

    /** POST /api/template/uploadLogo {ext} → {put_url, content_type, download_url} */
    public function uploadLogo(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $r = $this->call('/openapi/template/uploadLogo', 'POST', [
            'ext' => (string)($p['ext'] ?? 'png'),
        ]);
        if (($r['code'] ?? 0) !== 1 || empty($r['data']['upload_url'])) {
            return $this->fail($r['msg'] ?? '获取上传签名失败，请稍后重试');
        }
        return JsonService::data($this->presignData($r['data']));
    }

    /** POST /api/template/uploadCover {ext} → {put_url, content_type, download_url} */
    public function uploadCover(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $r = $this->call('/openapi/template/uploadCover', 'POST', [
            'ext' => (string)($p['ext'] ?? 'docx'),
        ]);
        if (($r['code'] ?? 0) !== 1 || empty($r['data']['upload_url'])) {
            return $this->fail($r['msg'] ?? '获取上传签名失败，请稍后重试');
        }
        return JsonService::data($this->presignData($r['data']));
    }

    // ==================== 排版演示 ====================

    /** POST /api/template/demonstrate {jsondata} → {doc_url}（jsondata→上游 conjson） */
    public function demonstrate(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $conjson = $p['jsondata'] ?? ($p['conjson'] ?? null);
        if (empty($conjson) || !is_array($conjson)) {
            return $this->fail('模板配置数据不能为空');
        }
        $r = $this->call('/openapi/template/demonstrate', 'POST', ['conjson' => $conjson]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '文档生成失败，请稍后重试');
        }
        return JsonService::data($r['data'] ?? []);
    }

    // ==================== 范文导入（SSE 流式） ====================

    /**
     * POST /api/template/importFanwenStream {name, file_url, scope?, logo?}
     * SSE 透传上游 /openapi/template/extractStream，done 帧归一化：
     *   上游 {status:done, template_id} → 本站 {status:done, data:{template_id,...}}（前端从 ev.data 取值）
     * 参数校验失败返回普通 JSON {code:0,msg}（前端按 Content-Type 区分）。
     */
    public function importFanwenStream(): void
    {
        $uid = $this->uid();
        if (!$uid) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => -1, 'show' => 1, 'msg' => '请先登录', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $p = $this->body();
        $name = trim((string)($p['name'] ?? ''));
        $fileUrl = trim((string)($p['file_url'] ?? ''));
        $logo = trim((string)($p['logo'] ?? ''));
        if ($name === '') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'show' => 1, 'msg' => '模板名称不能为空', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (mb_strlen($name) > 100) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'show' => 1, 'msg' => '模板名称过长（最多 100 字符）', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!preg_match('#^https?://#i', $fileUrl)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'show' => 1, 'msg' => '请先上传范文文档', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (mb_strlen($logo) > 512) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'show' => 1, 'msg' => 'logo 地址过长（最多 512 字符）', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $config = Config::get('docking');
        $baseUrl = rtrim((string)($config['api_url'] ?? ''), '/');
        $apiKey = (string)($config['api_token'] ?? '');

        @set_time_limit(600);
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        $ch = curl_init($baseUrl . '/openapi/template/extractStream');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'file_url'  => $fileUrl,
                'name'      => $name,
                'logo'      => $logo,
                'agent_str' => $this->agentStr($uid),
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: text/event-stream',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 600,
            CURLOPT_CONNECTTIMEOUT => 5,
            // 逐帧透传：缓冲至完整 SSE 帧（\n\n 结尾），done 帧归一化后输出
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) {
                static $buf = '';
                $buf .= $chunk;
                while (($pos = strpos($buf, "\n\n")) !== false) {
                    $frame = substr($buf, 0, $pos);
                    $buf = substr($buf, $pos + 2);
                    echo $this->normalizeSseFrame($frame);
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                }
                return strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 0) {
            echo "data: " . json_encode(['status' => 'error', 'msg' => '范文解析服务不可达，请稍后重试'], JSON_UNESCAPED_UNICODE) . "\n\n";
        } elseif ($httpCode === 401) {
            echo "data: " . json_encode(['status' => 'error', 'msg' => '范文解析服务鉴权失败'], JSON_UNESCAPED_UNICODE) . "\n\n";
        } elseif ($httpCode >= 500) {
            echo "data: " . json_encode(['status' => 'error', 'msg' => '范文解析服务异常（HTTP ' . $httpCode . '）'], JSON_UNESCAPED_UNICODE) . "\n\n";
        }
    }

    /** SSE 帧归一化：data: 行 JSON 解析，done 帧顶层 template_id 收拢进 data */
    private function normalizeSseFrame(string $frame): string
    {
        $lines = explode("\n", $frame);
        foreach ($lines as $i => $line) {
            if (strpos($line, 'data:') !== 0) {
                continue;
            }
            $jsonStr = trim(substr($line, 5));
            if ($jsonStr === '') {
                continue;
            }
            $ev = json_decode($jsonStr, true);
            if (!is_array($ev)) {
                continue;
            }
            if (($ev['status'] ?? '') === 'done' && !isset($ev['data']) && isset($ev['template_id'])) {
                $data = ['template_id' => (int)$ev['template_id']];
                foreach (['boards', 'bodyLevels', 'name'] as $k) {
                    if (isset($ev[$k])) {
                        $data[$k] = $ev[$k];
                    }
                }
                $ev = ['status' => 'done', 'data' => $data];
                $lines[$i] = 'data: ' . json_encode($ev, JSON_UNESCAPED_UNICODE);
            } else {
                $lines[$i] = 'data: ' . json_encode($ev, JSON_UNESCAPED_UNICODE);
            }
        }
        return implode("\n", $lines) . "\n\n";
    }
}
