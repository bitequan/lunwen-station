<?php
namespace app\common\service;

use think\facade\Config;
use think\facade\Db;

/**
 * 论文服务类
 * 处理论文相关的API对接
 */
class PaperService extends ApiClientService
{
    /**
     * 获取论文表单配置
     * 
     * @param int $tid 模板ID
     * @return array
     */
    public function getFormFields($tid = 1)
    {
        if ($tid == 1) {
            $url = '/tokenapi/paper/formFields';
        } else {
            // 其他业务类型的配置接口映射
            switch ($tid) {
                case 3: // 开题报告
                    $url = '/tokenapi/write/kaitiConfig';
                    break;
                case 4: // 任务书
                    $url = '/tokenapi/write/rwsConfig';
                    break;
                case 16: // 实习报告
                    $url = '/tokenapi/write/sxConfig';
                    break;
                case 5: // 实习日志
                    $url = '/tokenapi/write/sxrzConfig';
                    break;
                default:
                    return [
                        'code' => 0,
                        'msg' => '不支持的配置类型ID：' . $tid
                    ];
            }
        }
        
        return $this->get($url);
    }
    
    /**
     * 生成论文大纲
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function generateOutline($data)
    {
        $url = '/tokenapi/paper/outline';
        // 生成大纲需要较长的处理时间，设置300秒超时
        $result = $this->postWithTimeout($url, $data, 300);
        
        // 转换格式：TokenAPI 返回 outline（单数），前端期望 outlines（复数）
        if ($result && isset($result['code']) && $result['code'] == 1 && isset($result['data'])) {
            if (isset($result['data']['outline']) && !isset($result['data']['outlines'])) {
                $result['data']['outlines'] = $result['data']['outline'];
                unset($result['data']['outline']);
            }
        }
        
        return $result;
    }

    /**
     * 根据用户输入的大纲文本，提取并整理结构化论文大纲
     * POST /tokenapi/paper/extracts
     *
     * @param array $data 请求数据（前端表单结构）
     * @return array
     */
    public function extractOutline($data)
    {
        $url = '/tokenapi/paper/extracts';

        $forms = $data['forms'] ?? [];
        if (!is_array($forms)) {
            $forms = [];
        }

        $title = '';
        if (isset($data['title']) && is_string($data['title'])) {
            $title = $data['title'];
        } elseif (isset($forms['title']) && is_string($forms['title'])) {
            // 兼容旧前端：title 可能也在 forms 里
            $title = $forms['title'];
        }

        $content = '';
        if (isset($data['content']) && is_string($data['content'])) {
            $content = $data['content'];
        }
        if (isset($forms['content']) && is_string($forms['content'])) {
            // 兼容当前前端：自定义大纲内容放在 forms.content
            $content = $forms['content'];
        }

        // form_params：与 /tokenapi/paper/outline 中一致，排除 content
        $formParams = $forms;
        unset($formParams['content']);

        $payload = [
            'title' => $title,
            'content' => $content,
        ];

        if (!empty($formParams)) {
            $payload['form_params'] = $formParams;
        }

        // 可选字段透传
        if (isset($data['selected_literatures'])) {
            $payload['selected_literatures'] = $data['selected_literatures'];
        }
        if (isset($data['gallery_resources'])) {
            $payload['gallery_resources'] = $data['gallery_resources'];
        }
        if (isset($data['literature_folders'])) {
            $payload['literature_folders'] = $data['literature_folders'];
        }
        if (isset($data['network_literature'])) {
            $payload['network_literature'] = $data['network_literature'];
        }

        // 提取大纲可能耗时较长
        $result = $this->postWithTimeout($url, $payload, 300);

        // 转换格式：TokenAPI 返回 outline（单数），前端期望 outlines（复数）
        if ($result && isset($result['code']) && $result['code'] == 1 && isset($result['data'])) {
            if (isset($result['data']['outline']) && !isset($result['data']['outlines'])) {
                $result['data']['outlines'] = $result['data']['outline'];
                unset($result['data']['outline']);
            }
        }

        return $result;
    }
    
    /**
     * 获取论文模板列表
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @return array
     */
    public function getTemplateList($page = 1, $keyword = '')
    {
        $url = '/tokenapi/paper/templates';
        $params = ['page' => $page];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        
        $result = $this->get($url, $params);
        
        // 转换数据格式以匹配前端期望
        return $this->transformTemplateResponse($result);
    }
    
    /**
     * 获取论文服务列表
     * 
     * @return array
     */
    public function getServices()
    {
        $url = '/tokenapi/paper/services';
        return $this->get($url);
    }
    
    /**
     * 创建论文订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createOrder($data)
    {
        $url = '/tokenapi/paper/order';
        
        // 转换数据格式以匹配TokenAPI期望的格式
        $apiData = $this->transformOrderData($data);
        
        // TokenAPI 需要 record_id 参数
        // 如果前端已经传递了 record_id，直接使用（前端应该已经调用过大纲生成接口）
        // 如果没有传递 record_id，才需要调用大纲生成接口获取
        if (empty($apiData['record_id']) || $apiData['record_id'] == 0) {
            // 如果没有 record_id，需要先调用大纲生成接口获取
            // 注意：这会导致创建订单变慢，所以前端应该在调用 createPaperOrder 之前先调用 generateOutline
            $title = $data['paper_title'] ?? $data['title'] ?? '';
            if (empty($title)) {
                return [
                    'code' => 0,
                    'msg' => '创建订单失败：title 不能为空',
                    'data' => []
                ];
            }
            
            // 构建 form_params 对象（从原始数据中提取表单参数）
            $formParams = [];
            if (isset($data['lengthnum'])) {
                $formParams['lengthnum'] = $data['lengthnum'];
            }
            if (isset($data['wxnum'])) {
                $formParams['wxnum'] = $data['wxnum'];
            }
            if (isset($data['codetype'])) {
                $formParams['codetype'] = $data['codetype'];
            }
            if (isset($data['wxquote'])) {
                $formParams['wxquote'] = $data['wxquote'];
            }
            if (isset($data['language'])) {
                $formParams['language'] = $data['language'];
            }
            
            // 构建大纲生成接口的请求数据
            $outlineData = [
                'title' => $title,
                'form_params' => $formParams,
            ];
            
            // 添加可选字段
            if (isset($data['aboutmsg']) || isset($data['about_msg'])) {
                $outlineData['about_msg'] = $data['aboutmsg'] ?? $data['about_msg'] ?? '';
            }
            if (isset($data['three_level'])) {
                $outlineData['three_level'] = $data['three_level'];
            }
            if (isset($data['literatures']) || isset($data['literature_list'])) {
                $outlineData['literatures'] = $data['literatures'] ?? $data['literature_list'] ?? [];
            }
            
            // 调用大纲生成接口，获取新的 record_id
            $outlineResult = $this->generateOutline($outlineData);
            
            // 如果大纲生成成功，使用新的 record_id
            if ($outlineResult && isset($outlineResult['code']) && $outlineResult['code'] == 1) {
                if (isset($outlineResult['data']['record_id'])) {
                    $apiData['record_id'] = $outlineResult['data']['record_id'];
                } else {
                    return [
                        'code' => 0,
                        'msg' => '大纲生成接口未返回 record_id',
                        'data' => []
                    ];
                }
            } else {
                $errorMsg = isset($outlineResult['msg']) ? $outlineResult['msg'] : '大纲生成失败';
                return [
                    'code' => 0,
                    'msg' => '创建订单失败：' . $errorMsg,
                    'data' => []
                ];
            }
        }
        
        // 直接调用 TokenAPI 创建订单（不调用大纲生成接口，使用前端传递的 record_id）
        return $this->post($url, $apiData);
    }
    
    /**
     * 获取论文订单列表
     * 
     * @param int $pageNo 页码
     * @param int $pageSize 每页数量
     * @param int $agentId 代理ID
     * @param string $keyword 关键词
     * @return array
     */
    public function getOrderList(
        $pageNo = 1,
        $pageSize = 20,
        $agentId = 0,
        $keyword = '',
        ?int $agentIdIsEmpty = null,
        string $agentIdPrefix = ''
    )
    {
        $url = '/tokenapi/paper/orderList';
        $params = [
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'keyword' => $keyword
        ];
        if (!empty($agentId)) {
            $params['agent_id'] = $agentId;
        }
        // 新增过滤能力：仅拉取指定 agent_str 前缀（如 adweb_）的订单
        if ($agentIdIsEmpty !== null) {
            $params['agent_id_is_empty'] = (int)$agentIdIsEmpty;
        }
        if ($agentIdPrefix !== '') {
            $params['agent_id_prefix'] = $agentIdPrefix;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 检查支付状态
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function checkPayStatus($orderSn)
    {
        $url = '/tokenapi/paper/checkPayStatus';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 获取下载链接
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function getDownloadUrl($orderSn)
    {
        $url = '/tokenapi/paper/getDownloadUrl';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 获取订单详情
     * 
     * @param string $orderSn 订单号
     * @param int $agentId 代理ID
     * @return array
     */
    public function getOrderDetail($orderSn, $agentId = 0)
    {
        $url = '/tokenapi/paper/orderDetail';
        $params = [
            'order_sn' => $orderSn,
        ];
        if (!empty($agentId)) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 余额支付
     * 
     * @param array $data 支付数据
     * @return array
     */
    public function balancePayment($data)
    {
        $url = '/tokenapi/pay/balancePaper';
        return $this->post($url, $data);
    }
    
    // ==================== 高级论文专用接口 ====================
    
    /**
     * 获取图表资源文件夹列表（高级论文专用）
     * 
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getGalleryFolders($agentId = null)
    {
        $url = '/tokenapi/paper/galleryFolders';
        $params = [];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取图表资源图片列表（高级论文专用）
     * 
     * @param int $folderId 文件夹ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getGalleryImages($folderId, $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/paper/galleryImages';
        $params = [
            'folder_id' => $folderId,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取文献资源文件夹列表（高级论文专用）
     * 
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getLiteratureFolders($agentId = null)
    {
        $url = '/tokenapi/paper/literatureFolders';
        $params = [];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取文献资源文档列表（高级论文专用）
     * 
     * @param int $folderId 文件夹ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getLiteratureDocuments($folderId, $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/paper/literatureDocuments';
        $params = [
            'folder_id' => $folderId,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 在线文献搜索（高级论文专用）
     * 
     * @param array $data 搜索数据
     * @return array
     */
    public function searchOnlineLiterature($data)
    {
        // 检查必要的参数
        if (!isset($data['title']) || empty(trim($data['title']))) {
            return [
                'code' => 0,
                'msg' => '缺少检索关键词'
            ];
        }
        
        // 使用TokenAPI的在线文献搜索接口
        $url = '/tokenapi/write/onlineLiterature';
        return $this->post($url, $data);
    }
    
    /**
     * 获取私有模板列表（高级论文专用）
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getPrivateTemplates($page = 1, $keyword = '', $agentId = null)
    {
        $url = '/tokenapi/paper/templates';
        $params = [
            'type' => 'private',
            'page' => $page
        ];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        
        $result = $this->get($url, $params);
        
        // 转换数据格式以匹配前端期望
        return $this->transformTemplateResponse($result);
    }
    
    /**
     * 获取公共模板列表（高级论文专用）
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getPublicTemplates($page = 1, $keyword = '', $agentId = null)
    {
        $url = '/tokenapi/paper/templates';
        $params = [
            'type' => 'public',
            'page' => $page
        ];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        
        $result = $this->get($url, $params);
        
        // 转换数据格式以匹配前端期望
        return $this->transformTemplateResponse($result);
    }
    
    /**
     * 转换模板响应数据格式
     * TokenAPI 返回格式：{code: 1, data: {list: [], total: 0, page: 1, page_size: 30}}
     * 前端期望格式：{code: 1, data: {list: [], total: 0}}
     * 
     * @param array $result API响应结果
     * @return array 转换后的结果
     */
    private function transformTemplateResponse($result)
    {
        if ($result && isset($result['code']) && $result['code'] == 1 && isset($result['data'])) {
            // 确保返回格式与前端期望一致
            if (isset($result['data']['list'])) {
                // 转换字段名：TokenAPI 返回 name，前端可能期望 title
                foreach ($result['data']['list'] as &$item) {
                    if (isset($item['name']) && !isset($item['title'])) {
                        $item['title'] = $item['name'];
                    }
                    // 处理图片路径
                    if (isset($item['cover']) && $item['cover']) {
                        // 如果已经是完整URL，保持不变
                        if (preg_match('/^https?:\/\//', $item['cover'])) {
                            continue;
                        }
                        
                        // 确保路径以 / 开头
                        if (strpos($item['cover'], '/') !== 0) {
                            $item['cover'] = '/' . $item['cover'];
                        }
                        
                        // 临时方案：直接返回TokenAPI的完整URL
                        // 这样可以先确保图片能显示，同时排查路由问题
                        // 使用基类的 baseUrl 属性，避免重复调用 Config
                        $item['cover'] = $this->baseUrl . $item['cover'];
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 获取题目推荐（高级论文专用）
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function getTitleSuggestions($data)
    {
        $url = '/tokenapi/tools/createtitle';
        // 题目推荐需要更长的处理时间，设置60秒超时
        return $this->postWithTimeout($url, $data, 60);
    }
    
    /**
     * 转换订单数据格式以匹配TokenAPI期望的格式
     * 
     * @param array $data 前端发送的数据
     * @return array 转换后的API数据
     */
    private function transformOrderData($data)
    {
        $apiData = $data;
        
        // 1. 将 outlines（复数）转换为 outline（单数）
        if (isset($apiData['outlines']) && !isset($apiData['outline'])) {
            $apiData['outline'] = $apiData['outlines'];
            unset($apiData['outlines']);
        }
        
        // 2. 将 template 转换为 template_id
        if (isset($apiData['template']) && !isset($apiData['template_id'])) {
            $apiData['template_id'] = $apiData['template'];
            unset($apiData['template']);
        }
        
        // 3. 转换 outline 结构以匹配 TokenAPI 期望的格式
        // TokenAPI 期望的格式：sections 中使用 "section" 字段，而不是 "name"
        if (isset($apiData['outline']) && is_array($apiData['outline'])) {
            foreach ($apiData['outline'] as &$chapter) {
                if (isset($chapter['sections']) && is_array($chapter['sections'])) {
                    foreach ($chapter['sections'] as &$section) {
                        // TokenAPI 期望使用 "section" 字段，而不是 "name"
                        if (isset($section['name']) && !isset($section['section'])) {
                            $section['section'] = $section['name'];
                            // 保留 name 字段，但优先使用 section
                        }
                        
                        // 确保 chart_type 字段存在（TokenAPI 需要）
                        if (!isset($section['chart_type'])) {
                            $section['chart_type'] = '';
                        }
                        
                        // 移除 TokenAPI 不需要的字段（可选，但为了数据清晰）
                        // 注意：不要移除 abstract，因为可能在其他地方需要
                    }
                }
            }
        }
        
        // 4. agent_id 格式保持原样：文档虽然写的是 int，但实际支持 "adweb_123" 字符串格式
        // 不需要转换，直接使用前端传递的格式

        // 6. 移除 TokenAPI 不需要的字段，只保留文档中要求的字段
        // agent_amount：本系统用户实际计费金额，与 tokenapi 返回的成本金额不同，创建订单时需传给 tokenapi
        $allowedFields = ['record_id', 'outline', 'template_id', 'selftemp', 'agent_id', 'agent_amount'];
        $filteredData = [];
        foreach ($allowedFields as $field) {
            if (isset($apiData[$field])) {
                $filteredData[$field] = $apiData[$field];
            }
        }
        
        // 记录发送到 TokenAPI 的数据，便于调试
        
        return $filteredData;
    }
    
}
