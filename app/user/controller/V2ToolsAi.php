<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Request;
use app\common\service\JsonService;
use app\common\service\UserTokenService;
use app\common\service\ApiClientService;

/**
 * 写作小工具 openapi 桥接控制器
 *
 * 前端工具页走本站 /api/tools/*、/api/paper_weight/*、/api/other_api/*，
 * 本控制器桥接到主站 /openapi/tools/*（Bearer 出站，docking.api_url/api_token）。
 *
 * 计费模型：小工具对下游用户免费直通（与参考站配置一致，工具类价格全 0），
 * 上游成本由对接 token 预充承担，本站不扣用户余额。
 * 工具调用需登录（uid），与站内其他功能保持一致。
 *
 * 覆盖端点：
 *   POST /api/tools/createtitle        → /openapi/tools/createtitle（题目生成）
 *   POST /api/tools/rewrite            → /openapi/tools/rewrite（段落改写）
 *   POST /api/tools/illustration       → /openapi/tools/illustration（段落配图）
 *   POST /api/tools/createchart        → /openapi/tools/chart（图表生成）
 *   POST /api/paper_weight/rewrite     → /openapi/tools/paperweight（论文增重，mode→targetWordCount 换算）
 *   POST /api/other_api/wxlist_relevance → /openapi/tools/wenxianDetail（在线文献，包装相关度得分）
 *
 * 返回统一结构 { code, show, msg, data }。
 */
class V2ToolsAi
{
    /** 当前登录用户 ID；未登录返回 0 */
    private function uid(): int
    {
        return UserTokenService::getUserId(UserTokenService::readRequestToken());
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

    /** 直通转发：调主站 → 成功透传 data，失败转本站统一失败结构 */
    private function passthrough(string $path, array $payload, string $fallback): \think\response\Json
    {
        $r = $this->call($path, 'POST', $payload);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? $fallback);
        }
        return JsonService::data($r['data'] ?? []);
    }

    // ==================== 题目生成 ====================

    /** POST /api/tools/createtitle {researchText, titleType} → {titles:[{title,abstract}]} */
    public function createtitle(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $researchText = trim((string)($p['researchText'] ?? ''));
        if ($researchText === '') {
            return $this->fail('请输入研究方向或关键词');
        }
        return $this->passthrough('/openapi/tools/createtitle', [
            'researchText' => $researchText,
            'titleType'    => (string)($p['titleType'] ?? ''),
        ], '题目生成失败，请稍后重试');
    }

    // ==================== 段落改写 ====================

    /** POST /api/tools/rewrite {sourceText, rewriteType, condition, aigcMode, referenceMode, referenceType} → {resultText, wenxianlist} */
    public function rewrite(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $sourceText = trim((string)($p['sourceText'] ?? ''));
        if ($sourceText === '') {
            return $this->fail('请输入需要改写的文本');
        }
        return $this->passthrough('/openapi/tools/rewrite', [
            'sourceText'    => $sourceText,
            'rewriteType'   => (string)($p['rewriteType'] ?? 'synonym'),
            'condition'     => (string)($p['condition'] ?? ''),
            'aigcMode'      => !empty($p['aigcMode']),
            'referenceMode' => !empty($p['referenceMode']),
            'referenceType' => (string)($p['referenceType'] ?? 'all'),
        ], '改写失败，请稍后重试');
    }

    // ==================== 段落配图 ====================

    /** POST /api/tools/illustration {content, matchType} → [{url, title}] */
    public function illustration(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $content = trim((string)($p['content'] ?? ''));
        if ($content === '') {
            return $this->fail('请输入配图内容');
        }
        return $this->passthrough('/openapi/tools/illustration', [
            'content'   => $content,
            'matchType' => (string)($p['matchType'] ?? 'simple'),
        ], '配图获取失败，请稍后重试');
    }

    // ==================== 图表生成 ====================

    /** POST /api/tools/createchart {chartType, data, enableOnlineData, enableReferences} → {imgbase64, content, wenxianlist} */
    public function createchart(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $data = trim((string)($p['data'] ?? ''));
        if ($data === '') {
            return $this->fail('请输入图表数据描述');
        }
        return $this->passthrough('/openapi/tools/chart', [
            'chartType'        => (string)($p['chartType'] ?? ''),
            'data'             => $data,
            'enableOnlineData' => !empty($p['enableOnlineData']),
            'enableReferences' => !empty($p['enableReferences']),
        ], '图表生成失败，请稍后重试');
    }

    // ==================== 论文增重 ====================

    /**
     * POST /api/paper_weight/rewrite {sourceText, condition, mode} → {resultText, wenxianlist}
     * 前端传 mode（replace=篇幅与原文相近 / expand=篇幅膨胀更多），
     * 上游 openapi 只收 targetWordCount → 按主站同款公式换算：
     *   replace: max(ceil(len*0.7), 30)；expand: max(ceil(len*3), 300)
     */
    public function paperweightRewrite(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $sourceText = trim((string)($p['sourceText'] ?? ''));
        if ($sourceText === '') {
            return $this->fail('请输入需要增重的文本');
        }
        $mode = (string)($p['mode'] ?? 'expand');
        if (!in_array($mode, ['replace', 'expand'], true)) {
            $mode = 'expand';
        }
        $len = mb_strlen($sourceText, 'UTF-8');
        $targetWordCount = $mode === 'replace'
            ? max((int)ceil($len * 0.7), 30)
            : max((int)ceil($len * 3), 300);

        return $this->passthrough('/openapi/tools/paperweight', [
            'sourceText'      => $sourceText,
            'condition'       => (string)($p['condition'] ?? ''),
            'targetWordCount' => $targetWordCount,
        ], '改写失败，请稍后重试');
    }

    // ==================== 在线文献（相关度模式） ====================

    /**
     * POST /api/other_api/wxlist_relevance {keywords, type, relevance_method} → [{quote, relevance_score}]
     * 上游 /openapi/tools/wenxianDetail 返回引用字符串数组；
     * 前端期望对象数组（quote + relevance_score）→ 包装并按「关键词命中 + 文本相似度」混合计算相关度（仅展示用）。
     */
    public function wxlistRelevance(): \think\response\Json
    {
        $g = $this->guard();
        if ($g !== null) {
            return $g;
        }
        $p = $this->body();
        $keywords = trim((string)($p['keywords'] ?? ''));
        if ($keywords === '') {
            return $this->fail('请输入关键词');
        }
        $r = $this->call('/openapi/tools/wenxianDetail', 'POST', [
            'keywords' => $keywords,
            'type'     => (string)($p['type'] ?? '全部'),
        ]);
        if (($r['code'] ?? 0) !== 1) {
            return $this->fail($r['msg'] ?? '获取文献失败，请稍后重试');
        }
        $rows = [];
        foreach ((array)($r['data'] ?? []) as $item) {
            if (is_array($item)) {
                $quote = trim((string)($item['quote'] ?? $item['title'] ?? ''));
            } else {
                $quote = trim((string)$item);
            }
            if ($quote === '' || $quote === 'null') {
                continue;
            }
            $rows[] = [
                'quote'           => $quote,
                'relevance_score' => $this->relevanceScore($keywords, $quote),
            ];
        }
        return JsonService::data($rows);
    }

    /**
     * 混合相关度得分（0~1，仅展示用）：
     * 60% 关键词命中率（按标点/空白切词，统计命中比例）+ 40% similar_text 文本相似度
     */
    private function relevanceScore(string $keywords, string $quote): float
    {
        $kws = preg_split('/[\s,，、;；]+/u', $keywords, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hit = 0;
        $n   = 0;
        foreach ($kws as $kw) {
            if (mb_strlen($kw) < 2) {
                continue;
            }
            $n++;
            if (mb_stripos($quote, $kw) !== false) {
                $hit++;
            }
        }
        $kwScore  = $n > 0 ? $hit / $n : 0.0;
        $simRatio = 0.0;
        if ($keywords !== '' && similar_text($keywords, $quote, $sim) >= 0) {
            $simRatio = max(0.0, min(1.0, $sim / 100));
        }
        return round(0.6 * $kwScore + 0.4 * $simRatio, 4);
    }
}
