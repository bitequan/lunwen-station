<?php
namespace app\common\service;

/**
 * 工具服务类
 * 处理降重、改写、配图、参考文献等工具相关的API对接
 */
class ToolService extends ApiClientService
{
    // ==================== 题目/大纲/图表工具 ====================
    
    /**
     * 题目生成
     *
     * 对接 TokenAPI: /tokenapi/tools/createtitle
     *
     * @param array $data
     * @return array
     */
    public function createTitle(array $data)
    {
        $url = '/tokenapi/tools/createtitle';
        return $this->post($url, $data);
    }
    
    /**
     * 大纲生成
     *
     * 对接 TokenAPI: /tokenapi/tools/createoutline
     *
     * @param array $data
     * @return array
     */
    public function createOutline(array $data)
    {
        $url = '/tokenapi/tools/createoutline';
        return $this->post($url, $data);
    }
    
    /**
     * 图表生成
     *
     * 对接 TokenAPI: /tokenapi/tools/chart
     *
     * @param array $data
     * @return array
     */
    public function createChart(array $data)
    {
        $url = '/tokenapi/tools/chart';
        return $this->post($url, $data);
    }
    
    // ==================== 参考文献工具 ====================
    
    /**
     * 获取参考文献列表
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function getReferences($data)
    {
        $url = '/tokenapi/tools/wxlist';
        return $this->post($url, $data);
    }
    
    // ==================== 段落改写工具 ====================
    
    /**
     * 段落改写
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function rewriteParagraph($data)
    {
        $url = '/tokenapi/tools/rewrite';
        return $this->post($url, $data);
    }
    
    // ==================== 段落配图工具 ====================
    
    /**
     * 段落配图
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function illustration($data)
    {
        $url = '/tokenapi/tools/illustration';
        return $this->post($url, $data);
    }
    
    // ==================== AIGC降重工具 ====================
    
    /**
     * 计算降重金额
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function calculateReduceWeightMoney($data)
    {
        $url = '/tokenapi/tools/aigcreduceweightMoney';
        return $this->post($url, $data);
    }
    
    /**
     * 创建降重订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createReduceWeightOrder($data)
    {
        $url = '/tokenapi/tools/aigcreduceweightOrder';
        return $this->post($url, $data);
    }
    
    /**
     * 获取降重配置
     * 
     * @return array
     */
    public function getReduceWeightConfig()
    {
        $url = '/tokenapi/tools/aigcreduceweightConfig';
        return $this->get($url);
    }
    
    /**
     * 计算文档字数
     * 
     * @param array $file 文件数据
     * @return array
     */
    public function calculateWordCount($file)
    {
        // 对齐文档：使用 /tokenapi/check/wordcount 计算字数
        $url = '/tokenapi/check/wordcount';
        return $this->upload($url, [], ['file' => $file]);
    }
    
    /**
     * 获取降重订单列表
     * 
     * @param int $pageNo 页码
     * @param int $pageSize 每页数量
     * @param int $agentId 代理ID
     * @param string $keyword 关键词
     * @return array
     */
    public function getReduceWeightOrderList($pageNo = 1, $pageSize = 20, $agentId = 0, $keyword = '')
    {
        $url = '/tokenapi/tools/aigcreduceweightOrderList';
        $params = [
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'agent_id' => $agentId,
            'keyword' => $keyword
        ];
        return $this->get($url, $params);
    }
    
    /**
     * 检查降重支付状态
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function checkReduceWeightPayStatus($orderSn)
    {
        $url = '/tokenapi/tools/checkAigcreduceweightPayStatus';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 获取降重下载链接
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function getReduceWeightDownloadUrl($orderSn)
    {
        $url = '/tokenapi/tools/getAigcreduceweightDownloadUrl';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    // ==================== 其他工具 ====================
    
    /**
     * 获取工具价格列表
     * 
     * @return array
     */
    public function getToolPrices()
    {
        $url = '/tokenapi/tools/prices';
        return $this->get($url);
    }
    
    /**
     * 演示功能
     *
     * @param array $data 请求数据
     * @return array
     */
    public function demonstrate($data)
    {
        $url = '/tokenapi/tools/demonstrate';
        return $this->post($url, $data);
    }
}
