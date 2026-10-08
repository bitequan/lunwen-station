<?php
namespace app\common\service;

/**
 * PPT服务类
 * 处理PPT相关的API对接
 */
class PptService extends ApiClientService
{
    /**
     * 获取PPT价格
     * 
     * @return array
     */
    public function getPrice()
    {
        $url = '/tokenapi/ppt/price';
        return $this->get($url);
    }
    
    /**
     * 生成PPT大纲
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function generateOutline($data)
    {
        $url = '/tokenapi/ppt/generateOutline';
        return $this->post($url, $data);
    }
    
    /**
     * 获取PPT模板分类
     * 
     * @return array
     */
    public function getTemplateCategories()
    {
        $url = '/tokenapi/ppt/templateCategories';
        return $this->get($url);
    }
    
    /**
     * 获取PPT模板列表
     * 
     * @param int|null $categoryId 分类ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public function getTemplates($categoryId = null, $page = 1, $pageSize = 50)
    {
        // 按对接文档使用 /tokenapi/ppt/templateList
        $url = '/tokenapi/ppt/templateList';
        $params = [
            'page' => $page,
            'limit' => $pageSize,
            // 默认公共模板，可根据需要增加 is_private 参数
        ];
        if ($categoryId !== null) {
            $params['category_id'] = $categoryId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 创建PPT订单
     *
     * @param array $data 订单数据（应包含 title、outline_content 等；agent_amount 为本系统用户实际计费金额，需由调用方传入）
     * @return array
     */
    public function createOrder($data)
    {
        $url = '/tokenapi/ppt/createOrder';
        return $this->post($url, $data);
    }
    
    /**
     * 获取PPT订单列表
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
        $url = '/tokenapi/ppt/orderList';
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
     * 检查PPT支付状态
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function checkPayStatus($orderSn)
    {
        $url = '/tokenapi/ppt/checkPayStatus';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 获取PPT预览链接
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function getPreviewUrl($orderSn)
    {
        $url = '/tokenapi/ppt/getPreviewUrl';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 获取PPT下载链接
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function getDownloadUrl($orderSn)
    {
        $url = '/tokenapi/ppt/getDownloadUrl';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * PPT余额支付
     * 
     * @param array $data 支付数据
     * @return array
     */
    public function balancePayment($data)
    {
        $url = '/tokenapi/pay/balancePpt';
        return $this->post($url, $data);
    }
    
    /**
     * 更新PPT模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function updateTemplate($data)
    {
        $url = '/tokenapi/ppt/updateTemplate';
        return $this->post($url, $data);
    }
}
