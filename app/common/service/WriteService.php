<?php
namespace app\common\service;

/**
 * 写作服务类
 * 处理开题报告、任务书、实习报告等写作相关的API对接
 */
class WriteService extends ApiClientService
{
    // ==================== 开题报告相关 ====================
    
    /**
     * 获取开题报告模板列表
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @return array
     */
    public function getKaitiTemplateList($page = 1, $keyword = '')
    {
        $url = '/tokenapi/write_type/kaitiTemplates';
        $params = ['page' => $page];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 生成开题报告大纲
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function generateKaitiOutline($data)
    {
        $url = '/tokenapi/write/kaitiOutline';
        return $this->post($url, $data);
    }
    
    /**
     * 创建开题报告订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createKaitiOrder($data)
    {
        $url = '/tokenapi/write/kaitiOrder';
        return $this->post($url, $data);
    }
    
    // ==================== 任务书相关 ====================
    
    /**
     * 获取任务书模板列表
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @return array
     */
    public function getRwsTemplateList($page = 1, $keyword = '')
    {
        $url = '/tokenapi/write_type/rwsTemplates';
        $params = ['page' => $page];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 生成任务书大纲
     * 
     * @param array $data 请求数据
     * @return array
     */
    public function generateRwsOutline($data)
    {
        $url = '/tokenapi/write/rwsOutline';
        return $this->post($url, $data);
    }
    
    /**
     * 创建任务书订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createRwsOrder($data)
    {
        $url = '/tokenapi/write/rwsOrder';
        return $this->post($url, $data);
    }
    
    // ==================== 实习报告相关 ====================
    
    /**
     * 获取实习报告模板列表
     * 
     * @param int $page 页码
     * @param string $name 名称
     * @return array
     */
    public function getSxTemplateList($page = 1, $name = '')
    {
        $url = '/tokenapi/write_type/sxTemplates';
        $params = ['page' => $page];
        if ($name) {
            $params['name'] = $name;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 创建实习报告订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createSxOrder($data)
    {
        $url = '/tokenapi/write/sxOrder';
        return $this->post($url, $data);
    }
    
    /**
     * 创建实习日志订单
     * 
     * @param array $data 订单数据
     * @return array
     */
    public function createSxrzOrder($data)
    {
        $url = '/tokenapi/write/sxrzOrder';
        return $this->post($url, $data);
    }
    
    /**
     * 保存实习日志数据（兼容旧接口）
     * 
     * @param array $data 表单数据
     * @return array
     */
    public function saveSxrzData($data)
    {
        // 验证必填字段
        $requiredFields = ['company', 'unit', 'rztype', 'num', 'everynum', 'textnum', 'outlines'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'code' => 0,
                    'msg' => '缺少必要参数：' . $field,
                    'data' => []
                ];
            }
        }
        
        // 清理和映射字段（tokenapi/write/sxrzOrder 需要的格式）
        // 注意：tokenapi 期望的参数是 outline（单数），不是 outlines（复数）
        $cleanData = [
            'company' => trim($data['company']),
            'unit' => trim($data['unit']),
            'rztype' => is_array($data['rztype']) ? implode(',', $data['rztype']) : $data['rztype'],
            'num' => intval($data['num']),
            'everynum' => intval($data['everynum']),
            'textnum' => intval($data['textnum']),
            'outline' => is_array($data['outlines']) ? $data['outlines'] : (is_string($data['outlines']) ? explode("\n", $data['outlines']) : [])
        ];
        
        // 添加代理相关字段（这里需要从session或数据库中获取用户信息）
        // 暂时留空，由控制器层补充
        // $cleanData['agent_id'] = $userrow['uid'];
        // $cleanData['agent_amount'] = $price;
        
        // 调用创建订单接口
        return $this->createSxrzOrder($cleanData);
    }
    
    // ==================== 写作订单管理 ====================
    
    /**
     * 获取写作订单列表
     * 
     * @param int $pageNo 页码
     * @param int $pageSize 每页数量
     * @param int $agentId 代理ID
     * @param string $keyword 关键词
     * @param string $actionType 操作类型
     * @return array
     */
    public function getOrderList(
        $pageNo = 1,
        $pageSize = 20,
        $agentId = 0,
        $keyword = '',
        $actionType = '',
        ?int $agentIdIsEmpty = null,
        string $agentIdPrefix = ''
    )
    {
        $url = '/tokenapi/write/orderList';
        $params = [
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'keyword' => $keyword
        ];
        if (!empty($agentId)) {
            $params['agent_id'] = $agentId;
        }
        if ($actionType) {
            $params['actiontype'] = $actionType;
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
     * 获取写作订单详情
     * 
     * @param string $orderSn 订单号
     * @return array
     */
    public function getOrderDetail($orderSn)
    {
        $url = '/tokenapi/write/orderDetail';
        $params = ['order_sn' => $orderSn];
        return $this->get($url, $params);
    }
    
    /**
     * 写作订单余额支付
     * 
     * @param array $data 支付数据
     * @return array
     */
    public function balancePayment($data)
    {
        $url = '/tokenapi/pay/balanceWriting';
        return $this->post($url, $data);
    }
}
