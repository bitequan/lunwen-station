<?php
namespace app\common\service;

/**
 * 大纲模板服务类
 * 处理大纲模板相关的API对接
 */
class OutlineTemplateService extends ApiClientService
{
    /**
     * 获取大纲模板列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getTemplateList($params = [])
    {
        // 参考旧版 adlw/api.php，使用新的 TokenAPI 大纲模板接口路径
        $url = '/tokenapi/outline_template/lists';
        return $this->get($url, $params);
    }
    
    /**
     * 获取大纲模板详情
     * 
     * @param int $id 模板ID
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getTemplateDetail($id, $agentId = null)
    {
        $url = '/tokenapi/outline_template/detail';
        $params = ['id' => $id];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 添加大纲模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function addTemplate($data)
    {
        $url = '/tokenapi/outline_template/add';
        return $this->post($url, $data);
    }
    
    /**
     * 编辑大纲模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function editTemplate($data)
    {
        $url = '/tokenapi/outline_template/edit';
        return $this->post($url, $data);
    }
    
    /**
     * 删除大纲模板
     * 
     * @param array $data 删除数据
     * @return array
     */
    public function deleteTemplate($data)
    {
        $url = '/tokenapi/outline_template/delete';
        return $this->post($url, $data);
    }
    
    /**
     * 获取论文模板列表
     * 
     * @param int $page 页码
     * @param string $keyword 关键词
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getLwTemplateList($page = 1, $keyword = '', $agentId = null)
    {
        $url = '/tokenapi/template/lwtemplates';
        $params = ['page' => $page];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 修改论文模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function changeLwTemplate($data)
    {
        $url = '/tokenapi/template/lwchange';
        return $this->post($url, $data);
    }
    
    /**
     * 删除论文模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function deleteLwTemplate($data)
    {
        $url = '/tokenapi/template/lwdelete';
        return $this->post($url, $data);
    }
    
    /**
     * 获取论文模板JSON
     * 
     * @param int $tid 模板ID
     * @param int $agentId 代理ID
     * @return array
     */
    public function getLwTemplateJson($tid, $agentId)
    {
        $url = '/tokenapi/template/lwjson';
        $params = [
            'tid' => $tid,
            'agent_id' => $agentId
        ];
        return $this->get($url, $params);
    }
    
    /**
     * 上传论文模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function uploadLwTemplate($data)
    {
        $url = '/tokenapi/template/uplwtemp';
        return $this->post($url, $data);
    }
    
    /**
     * 保存论文模板
     * 
     * @param array $data 模板数据
     * @return array
     */
    public function saveLwTemplate($data)
    {
        $url = '/tokenapi/template/savelwtemp';
        return $this->post($url, $data);
    }
}
