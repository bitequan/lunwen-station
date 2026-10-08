<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\service\DatabaseMigrationService;
use think\facade\Request;
use think\facade\View;

class Database extends BaseController
{
    /**
     * 执行管理员后台数据库迁移
     */
    public function migrateAdmin()
    {
        $service = new DatabaseMigrationService();
        $sqlFile = root_path() . 'database/admin_tables.sql';
        
        $result = $service->executeSqlFile($sqlFile);
        
        if (Request::isAjax() || Request::isPost()) {
            return json($result);
        }
        
        View::assign('result', $result);
        return View::fetch('database/migrate_result');
    }
}
