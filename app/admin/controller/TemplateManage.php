<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\service\ApiClientService;
use app\common\service\OutlineTemplateService;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\App;

class TemplateManage extends BaseController
{
    private OutlineTemplateService $outlineTemplateService;
    private ApiClientService $apiClientService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->outlineTemplateService = new OutlineTemplateService();
        $this->apiClientService = new ApiClientService();
    }

    private function isAdminLoggedIn(): bool
    {
        return (bool)Session::get('admin_id');
    }

    private function jsonBody(): array
    {
        $data = Request::post();
        if (!empty($data)) {
            return $data;
        }
        $raw = Request::getContent();
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function outlineTemplates()
    {
        if (!$this->isAdminLoggedIn()) {
            return redirect('/admin/login');
        }
        return View::fetch('template_manage/outline_templates');
    }

    public function paperTemplates()
    {
        if (!$this->isAdminLoggedIn()) {
            return redirect('/admin/login');
        }
        return View::fetch('template_manage/paper_templates');
    }

    public function getOutlineTemplates()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $params = [
            'page' => (int)Request::get('page', 1),
            'page_size' => (int)Request::get('page_size', 20),
        ];
        $keyword = trim((string)Request::get('keyword', ''));
        if ($keyword !== '') {
            $params['keyword'] = $keyword;
        }

        // 仅获取自有模板：不传 agent_id
        $result = $this->outlineTemplateService->getTemplateList($params);
        return json($result);
    }

    public function getOutlineTemplateDetail()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $id = (int)Request::get('id', 0);
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => 'id 不能为空']);
        }

        // 仅获取自有模板：不传 agent_id
        $result = $this->outlineTemplateService->getTemplateDetail($id, null);
        return json($result);
    }

    public function saveOutlineTemplate()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        if (empty($data['template_name']) || empty($data['template_content'])) {
            return json(['code' => 0, 'msg' => '模板名称和模板内容不能为空']);
        }

        if (!empty($data['id'])) {
            $result = $this->outlineTemplateService->editTemplate($data);
        } else {
            $result = $this->outlineTemplateService->addTemplate($data);
        }
        return json($result);
    }

    public function deleteOutlineTemplate()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        if (empty($data['id'])) {
            return json(['code' => 0, 'msg' => 'id 不能为空']);
        }

        $result = $this->outlineTemplateService->deleteTemplate(['id' => (int)$data['id']]);
        return json($result);
    }

    public function updateOutlineTemplateShare()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0 || !isset($data['enable_subordinate'])) {
            return json(['code' => 0, 'msg' => 'id 和 enable_subordinate 不能为空']);
        }

        $enableSubordinate = (int)$data['enable_subordinate'] === 1 ? 1 : 0;
        $result = $this->outlineTemplateService->editTemplate([
            'id' => $id,
            'enable_subordinate' => $enableSubordinate,
        ]);
        return json($result);
    }

    public function getPaperTemplates()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $type = trim((string)Request::get('type', 'private'));
        if ($type !== 'public' && $type !== 'private') {
            $type = 'private';
        }
        $page = max(1, (int)Request::get('page', 1));
        $pageSize = max(1, (int)Request::get('page_size', 30));
        $keyword = trim((string)Request::get('keyword', ''));

        $params = [
            'type' => $type,
            'page' => $page,
            'page_size' => $pageSize,
            // 按最新约定：不传 agent_id，只传空值 agent_id_is_empty
            'agent_id_is_empty' => '1',
        ];

        if ($keyword !== '') {
            $params['keyword'] = $keyword;
        }

        // 新论文模板列表接口：/tokenapi/paper/templates
        $result = $this->apiClientService->get('/tokenapi/paper/templates', $params);
        return json($result);
    }

    public function updatePaperTemplateShare()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0 || !isset($data['enable_subordinate'])) {
            return json(['code' => 0, 'msg' => 'id 和 enable_subordinate 不能为空']);
        }

        $enableSubordinate = (int)$data['enable_subordinate'] === 1 ? 1 : 0;

        // 按接口约定：POST /tokenapi/template/uplwtemp，至少传一个可更新字段
        $payload = [
            'id' => $id,
            'enable_subordinate' => $enableSubordinate,
        ];

        $result = $this->outlineTemplateService->uploadLwTemplate($payload);
        return json($result);
    }

    public function changePaperTemplateStatus()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        if (empty($data['tid']) || !isset($data['status'])) {
            return json(['code' => 0, 'msg' => 'tid 和 status 不能为空']);
        }

        $payload = [
            'tid' => (int)$data['tid'],
            'status' => (int)$data['status'],
        ];

        $result = $this->outlineTemplateService->changeLwTemplate($payload);
        return json($result);
    }

    public function deletePaperTemplate()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $data = $this->jsonBody();
        if (empty($data['tid'])) {
            return json(['code' => 0, 'msg' => 'tid 不能为空']);
        }

        $result = $this->outlineTemplateService->deleteLwTemplate(['tid' => (int)$data['tid']]);
        return json($result);
    }
}
