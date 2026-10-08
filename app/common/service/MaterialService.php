<?php
namespace app\common\service;

/**
 * 素材库服务类
 * 处理图片、文档、文献等素材相关的API对接
 */
class MaterialService extends ApiClientService
{
    // ==================== 图片素材库 ====================
    
    /**
     * 获取图片文件夹列表
     * 
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getGalleryFolders($agentId = null)
    {
        $url = '/tokenapi/material/galleryFolders';
        $params = [];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取图片列表
     * 
     * @param int $folderId 文件夹ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getGalleryImages($folderId, $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/material/galleryImages';
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
    
    // ==================== 文献素材库 ====================
    
    /**
     * 获取文献文件夹列表
     * 
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getLiteratureFolders($agentId = null)
    {
        $url = '/tokenapi/material/literatureFolders';
        $params = [];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取文献列表
     * 
     * @param int $folderId 文件夹ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getLiteratureDocuments($folderId, $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/material/literatureDocuments';
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
     * 在线文献搜索
     * 
     * @param array $data 搜索数据
     * @return array
     */
    public function searchOnlineLiterature($data)
    {
        $url = '/tokenapi/material/onlineLiterature';
        return $this->post($url, $data);
    }
    
    // ==================== 通用素材库 ====================
    
    /**
     * 获取素材文件夹列表
     * 
     * @param int $parentId 父文件夹ID
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getMaterialFolders($parentId = 0, $keyword = '', $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/material/folders';
        $params = [
            'parent_id' => $parentId,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 获取素材文件夹树形结构
     * 
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getMaterialFolderTree($agentId = null)
    {
        $url = '/tokenapi/material/folderTree';
        $params = [];
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 创建素材文件夹
     * 
     * @param array $data 文件夹数据
     * @return array
     */
    public function createMaterialFolder($data)
    {
        $url = '/tokenapi/material/createFolder';
        return $this->post($url, $data);
    }
    
    /**
     * 更新素材文件夹
     * 
     * @param int $id 文件夹ID
     * @param array $data 文件夹数据
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function updateMaterialFolder($id, $data, $agentId = null)
    {
        $url = '/tokenapi/material/updateFolder';
        $params = array_merge(['id' => $id], $data);
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->post($url, $params);
    }
    
    /**
     * 删除素材文件夹
     * 
     * @param int $id 文件夹ID
     * @return array
     */
    public function deleteMaterialFolder($id)
    {
        $url = '/tokenapi/material/deleteFolder';
        $params = ['id' => $id];
        return $this->post($url, $params);
    }
    
    /**
     * 获取文章列表
     * 
     * @param int $parentId 父文件夹ID
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getArticles($parentId = -1, $keyword = '', $page = 1, $pageSize = 10, $agentId = null)
    {
        $url = '/tokenapi/material/articles';
        $params = [
            'parent_id' => $parentId,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 删除文章
     * 
     * @param int $id 文章ID
     * @return array
     */
    public function deleteArticle($id)
    {
        $url = '/tokenapi/material/deleteArticle';
        $params = ['id' => $id];
        return $this->post($url, $params);
    }
    
    /**
     * 获取图片列表
     * 
     * @param int $parentId 父文件夹ID
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function getImages($parentId = -1, $keyword = '', $page = 1, $pageSize = 12, $agentId = null)
    {
        $url = '/tokenapi/material/images';
        $params = [
            'parent_id' => $parentId,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($keyword) {
            $params['keyword'] = $keyword;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 上传图片
     * 
     * @param array $formData 表单数据
     * @return array
     */
    public function uploadImage($formData)
    {
        $url = '/tokenapi/material/uploadImage';
        return $this->upload($url, $formData, ['file' => $formData['file'] ?? null]);
    }
    
    /**
     * 更新图片信息
     * 
     * @param int $id 图片ID
     * @param array $data 图片数据
     * @return array
     */
    public function updateImage($id, $data)
    {
        $url = '/tokenapi/material/updateImage';
        $params = array_merge(['id' => $id], $data);
        return $this->post($url, $params);
    }
    
    /**
     * 删除图片
     * 
     * @param int $segmentId 图片段ID
     * @return array
     */
    public function deleteImage($segmentId)
    {
        $url = '/tokenapi/material/deleteImage';
        $params = ['segment_id' => $segmentId];
        return $this->post($url, $params);
    }
    
    /**
     * 素材搜索
     * 
     * @param string $keyword 关键词
     * @param string $type 类型
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $agentId 代理ID
     * @return array
     */
    public function search($keyword, $type = '', $page = 1, $pageSize = 20, $agentId = null)
    {
        $url = '/tokenapi/material/search';
        $params = [
            'keyword' => $keyword,
            'page' => $page,
            'page_size' => $pageSize
        ];
        if ($type) {
            $params['type'] = $type;
        }
        if ($agentId !== null) {
            $params['agent_id'] = $agentId;
        }
        return $this->get($url, $params);
    }
    
    /**
     * 根据哈希ID获取文章
     * 
     * @param string $hashId 哈希ID
     * @return array
     */
    public function getArticleByHashId($hashId)
    {
        $url = '/tokenapi/material/getArticleByHashId';
        $params = ['hash_id' => $hashId];
        return $this->get($url, $params);
    }
    
    /**
     * 获取完整段落
     * 
     * @param string $hashId 哈希ID
     * @return array
     */
    public function getFullSegments($hashId)
    {
        $url = '/tokenapi/material/getFullSegments';
        $params = ['hash_id' => $hashId];
        return $this->get($url, $params);
    }
    
    /**
     * 编辑段落
     * 
     * @param int $segmentId 段落ID
     * @param array $data 段落数据
     * @return array
     */
    public function editSegment($segmentId, $data)
    {
        $url = '/tokenapi/material/editSegment';
        $params = array_merge(['segment_id' => $segmentId], $data);
        return $this->post($url, $params);
    }
    
    /**
     * 删除段落
     * 
     * @param int $segmentId 段落ID
     * @return array
     */
    public function deleteSegment($segmentId)
    {
        $url = '/tokenapi/material/deleteSegment';
        $params = ['segment_id' => $segmentId];
        return $this->post($url, $params);
    }
    
    /**
     * 添加段落
     * 
     * @param array $data 段落数据
     * @return array
     */
    public function addSegment($data)
    {
        $url = '/tokenapi/material/addSegment';
        return $this->post($url, $data);
    }
    
    /**
     * 上传文档
     * 
     * @param array $formData 表单数据
     * @return array
     */
    public function uploadDocument($formData)
    {
        $url = '/tokenapi/material/uploadDocument';
        return $this->upload($url, $formData, ['file' => $formData['file'] ?? null]);
    }
    
    /**
     * 获取文档处理进度
     * 
     * @param string $taskId 任务ID
     * @param string $segmentType 段落类型
     * @param int $segmentLength 段落长度
     * @return array
     */
    public function getMinerUProgress($taskId, $segmentType = 'paragraph', $segmentLength = 500)
    {
        $url = '/tokenapi/material/getMinerUProgress';
        $params = [
            'task_id' => $taskId,
            'segment_type' => $segmentType,
            'segment_length' => $segmentLength
        ];
        return $this->get($url, $params);
    }
    
    /**
     * 确认段落
     * 
     * @param array $data 确认数据
     * @return array
     */
    public function confirmSegments($data)
    {
        $url = '/tokenapi/material/confirmSegments';
        return $this->post($url, $data);
    }
}
