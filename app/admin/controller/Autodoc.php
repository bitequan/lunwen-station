<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\service\MaterialService;
use app\common\service\OutlineTemplateService;
use app\common\service\ToolService;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Autodoc extends BaseController
{
    private OutlineTemplateService $outlineTemplateService;
    private ToolService $toolService;
    private MaterialService $materialService;

    protected function initialize()
    {
        parent::initialize();
        $this->outlineTemplateService = new OutlineTemplateService();
        $this->toolService = new ToolService();
        $this->materialService = new MaterialService();
    }

    private function isAdminLoggedIn(): bool
    {
        return (bool)Session::get('admin_id');
    }

    private function requireLogin()
    {
        if (!$this->isAdminLoggedIn()) {
            return redirect('/admin/login');
        }
        return null;
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

    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        // 管理端不再使用 autodoc/index.html，默认跳转到新建页
        return redirect('/admin/autodoc/create');
    }

    public function create()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        return View::fetch('autodoc/createautodoc');
    }

    public function edit()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        return View::fetch('autodoc/editautodoc');
    }

    public function lwedit()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        return View::fetch('autodoc/lwedit');
    }

    public function lwtemplates()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $page = (int)Request::get('page', 1);
        $keyword = (string)Request::get('keyword', '');
        $result = $this->outlineTemplateService->getLwTemplateList($page, $keyword, null);
        return json($result);
    }

    public function lwchange()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $data = $this->jsonBody();
        if (empty($data['tid'])) {
            return json(['code' => 0, 'msg' => 'tid 不能为空']);
        }
        return json($this->outlineTemplateService->changeLwTemplate($data));
    }

    public function lwdelete()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $data = $this->jsonBody();
        if (empty($data['tid'])) {
            return json(['code' => 0, 'msg' => 'tid 不能为空']);
        }
        return json($this->outlineTemplateService->deleteLwTemplate($data));
    }

    public function lwjson()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $tid = (int)Request::get('tid', 0);
        if ($tid <= 0) {
            return json(['code' => 0, 'msg' => 'tid 不能为空']);
        }
        // 管理端不传真实 agent_id，使用 0 兼容 service 方法签名
        return json($this->outlineTemplateService->getLwTemplateJson($tid, 0));
    }

    public function uplwtemp()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $data = $this->jsonBody();
        return json($this->outlineTemplateService->uploadLwTemplate($data));
    }

    public function savelwtemp()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $data = $this->jsonBody();
        // 注意：新增模板时不传 agent_id（按管理端要求）
        if (isset($data['agent_id'])) {
            unset($data['agent_id']);
        }
        return json($this->outlineTemplateService->saveLwTemplate($data));
    }

    public function uploadTemplateImage()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $file = Request::file('file');
        if (!$file) {
            $file = Request::file('image');
        }
        if (!$file) {
            $allFiles = Request::file();
            if (is_array($allFiles) && !empty($allFiles)) {
                $file = reset($allFiles);
            }
        }
        if (!$file) {
            return json(['code' => 0, 'msg' => '请上传图片文件']);
        }
        $fileData = [
            'tmp_name' => $file->getPathname(),
            'type' => $file->getMime(),
            'name' => $file->getOriginalName(),
        ];
        $result = $this->outlineTemplateService->upload('/tokenapi/template/uploadImage', [], ['image' => $fileData]);
        return json($result);
    }

    public function createdemo()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $data = $this->jsonBody();
        if (empty($data['jsondata'])) {
            return json(['code' => 0, 'msg' => 'jsondata 不能为空']);
        }
        return json($this->toolService->demonstrate($data));
    }

    public function uploadDocument()
    {
        if (!$this->isAdminLoggedIn()) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        $file = Request::file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => '请上传文档文件']);
        }
        $fileData = [
            'tmp_name' => $file->getPathname(),
            'type' => $file->getMime(),
            'name' => $file->getOriginalName(),
        ];
        return json($this->materialService->uploadDocument($fileData));
    }
}
