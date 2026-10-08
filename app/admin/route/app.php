<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 管理员后台路由（在多应用模式下，已经通过/admin前缀访问）
Route::group('/', function () {
    // 登录相关（不校验 admin 会话）
    Route::group(function () {
        // 由于 route_complete_match=false，需把更长路径放前面并使用 $ 结尾，避免被 login 路由提前匹配
        Route::get('login/logout$', 'login/logout');
        Route::post('login/doLogin$', 'login/doLogin');
        Route::get('login$', 'login/index');
    });

    Route::group(function () {
    // 首页
    Route::get('/', 'index/index');
    Route::get('home', 'index/home');
    
    
    // API配置
    Route::get('apiConfig', 'index/apiConfig');
    Route::get('getApiConfig', 'index/getApiConfig');
    Route::post('saveApiConfig', 'index/saveApiConfig');
    
    // 商品管理
    Route::get('products', 'index/products');
    Route::get('getProducts', 'index/getProducts');
    Route::get('getProduct', 'index/getProduct');
    Route::get('fetchProductPrice', 'index/fetchProductPrice');
    Route::post('updateProductStatus', 'index/updateProductStatus');
    Route::post('updateProductDefault', 'index/updateProductDefault');
    Route::post('updateProductSort', 'index/updateProductSort');
    Route::post('saveProduct', 'index/saveProduct');
    
    // 用户管理
    Route::get('users', 'user/index');
    Route::get('userForm', 'user/userForm');
    Route::post('saveUser', 'user/saveUser');
    Route::post('deleteUser', 'user/deleteUser');
    Route::post('changeUserPassword', 'user/changeUserPassword');
    Route::post('updateUser', 'user/updateUser');
    Route::post('setUserStatus', 'user/setUserStatus');
    Route::post('userBalance', 'user/userBalance');
    
    // 修改当前登录管理员密码
    Route::post('changeMyPassword', 'index/changeMyPassword');
    
    // 站点信息
    Route::get('siteInfo', 'index/siteInfo');
    Route::post('saveSiteInfo', 'index/saveSiteInfo');
    
    // 网站配置
    Route::get('siteConfig', 'index/siteConfig');
    Route::get('siteConfigBasic', 'index/siteConfigBasic');
    Route::get('siteConfigEmail', 'index/siteConfigEmail');
    Route::get('siteConfigSecurity', 'index/siteConfigSecurity');
    Route::get('siteConfigSystem', 'index/siteConfigSystem');
    Route::get('siteConfigPayment', 'index/siteConfigPayment');
    Route::get('siteConfigSocialLogin', 'index/siteConfigSocialLogin');
    Route::get('siteConfigCustomerService', 'index/siteConfigCustomerService');
    // 用户端主题配色
    Route::get('userThemeConfig', 'index/userThemeConfig');
    Route::get('getCustomerServiceConfig', 'index/getCustomerServiceConfig');
    Route::post('saveSiteConfig', 'index/saveSiteConfig');
    Route::post('uploadImage', 'index/uploadImage');
    
    // 支付配置
    Route::get('getPaymentMethods', 'index/getPaymentMethods');
    Route::get('getPaymentMethod', 'index/getPaymentMethod');
    Route::post('updatePaymentStatus', 'index/updatePaymentStatus');
    Route::post('savePaymentConfig', 'index/savePaymentConfig');
    
    // 订单管理
    // 注意：Linux 下大小写敏感，控制器名必须与文件/类名驼峰一致（本项目为 OrderManage）
    Route::get('orders', 'OrderManage/index');
    Route::get('ordersPaper', 'OrderManage/paper');
    Route::get('ordersPpt', 'OrderManage/ppt');
    Route::get('ordersWrite', 'OrderManage/write');
    Route::get('ordersCheck', 'OrderManage/check');
    Route::get('ordersAutodoc', 'OrderManage/autodoc');
    Route::get('getOrders', 'OrderManage/getOrders');
    Route::get('orderDetail', 'OrderManage/orderDetail');
    Route::post('updateOrderStatus', 'OrderManage/updateOrderStatus');
    Route::post('orderRegenerate', 'OrderManage/orderRegenerate');
    Route::post('checkDownloadUrl', 'OrderManage/checkDownloadUrl');
    
    // 模板管理
    Route::get('outlineTemplates', 'TemplateManage/outlineTemplates');
    Route::get('paperTemplates', 'TemplateManage/paperTemplates');
    Route::get('autodoc/create', 'Autodoc/create');
    Route::get('autodoc/edit', 'Autodoc/edit');
    Route::get('autodoc$', 'Autodoc/index');
    Route::get('view/autodoc/createautodoc', 'Autodoc/create');
    Route::get('view/autodoc/editautodoc', 'Autodoc/edit');
    Route::get('view/autodoc/createautodoc.html', 'Autodoc/create');
    Route::get('view/autodoc/editautodoc.html', 'Autodoc/edit');
    Route::get('/view/autodoc/createautodoc', 'Autodoc/create');
    Route::get('/view/autodoc/editautodoc', 'Autodoc/edit');
    Route::get('/view/autodoc/createautodoc.html', 'Autodoc/create');
    Route::get('/view/autodoc/editautodoc.html', 'Autodoc/edit');
    Route::get('autodoc/lwedit', 'Autodoc/lwedit');
    Route::get('getOutlineTemplates', 'TemplateManage/getOutlineTemplates');
    Route::get('getOutlineTemplateDetail', 'TemplateManage/getOutlineTemplateDetail');
    Route::post('saveOutlineTemplate', 'TemplateManage/saveOutlineTemplate');
    Route::post('deleteOutlineTemplate', 'TemplateManage/deleteOutlineTemplate');
    Route::post('updateOutlineTemplateShare', 'TemplateManage/updateOutlineTemplateShare');
    Route::get('getPaperTemplates', 'TemplateManage/getPaperTemplates');
    Route::post('updatePaperTemplateShare', 'TemplateManage/updatePaperTemplateShare');
    Route::post('changePaperTemplateStatus', 'TemplateManage/changePaperTemplateStatus');
    Route::post('deletePaperTemplate', 'TemplateManage/deletePaperTemplate');
    Route::group('api/docking', function () {
        Route::get('lwtemplates', 'Autodoc/lwtemplates');
        Route::post('lwchange', 'Autodoc/lwchange');
        Route::post('lwdelete', 'Autodoc/lwdelete');
        Route::get('lwjson', 'Autodoc/lwjson');
        Route::post('uplwtemp', 'Autodoc/uplwtemp');
        Route::post('savelwtemp', 'Autodoc/savelwtemp');
        Route::post('uploadTemplateImage', 'Autodoc/uploadTemplateImage');
        Route::post('createdemo', 'Autodoc/createdemo');
        Route::post('uploadDocument', 'Autodoc/uploadDocument');
    });
    
    // 充值赠送规则管理（控制器名需驼峰 RechargeBonus 才能正确解析）
    Route::get('rechargeBonus', 'RechargeBonus/index');
    Route::get('getRechargeBonusRules', 'RechargeBonus/getRules');
    Route::get('getRechargeBonusRule', 'RechargeBonus/getRule');
    Route::post('saveRechargeBonusRule', 'RechargeBonus/saveRule');
    Route::post('updateRechargeBonusRuleStatus', 'RechargeBonus/updateRuleStatus');
    Route::post('updateRechargeBonusRuleSort', 'RechargeBonus/updateRuleSort');
    Route::post('deleteRechargeBonusRule', 'RechargeBonus/deleteRule');
    Route::post('testRechargeBonusCalculate', 'RechargeBonus/testCalculate');
    
    // 常见问题配置管理（控制器名需驼峰 FaqConfig 才能正确解析）
    Route::get('faqConfig', 'FaqConfig/index');
    Route::get('getFaqList', 'FaqConfig/getFaqList');
    Route::get('getFaq', 'FaqConfig/getFaq');
    Route::post('saveFaq', 'FaqConfig/saveFaq');
    Route::post('updateFaqStatus', 'FaqConfig/updateFaqStatus');
    Route::post('updateFaqSort', 'FaqConfig/updateFaqSort');
    Route::post('deleteFaq', 'FaqConfig/deleteFaq');
    
    // 公告设置管理（控制器名需驼峰 AnnouncementConfig 才能正确解析）
    Route::get('announcementConfig', 'AnnouncementConfig/index');
    Route::get('getAnnouncement', 'AnnouncementConfig/getAnnouncement');
    Route::post('saveAnnouncement', 'AnnouncementConfig/saveAnnouncement');
    
    // 数据库迁移
    Route::group('database', function () {
        Route::get('migrateAdmin', 'database/migrateAdmin');
        Route::post('migrateAdmin', 'database/migrateAdmin');
    });
    
    // 版本更新
    Route::get('versionCenter', 'version/index');
    Route::group('version', function () {
        // 获取当前版本
        Route::get('current', 'version/current');
        // 检查更新
        Route::get('check', 'version/check');
        // 在线检查更新
        Route::get('checkOnline', 'version/checkOnline');
        // 创建在线更新任务
        Route::post('createTask', 'version/createTask');
        // 启动在线更新任务
        Route::post('startTask', 'version/startTask');
        // 查询在线更新任务状态
        Route::get('taskStatus', 'version/taskStatus');
        // 查询在线更新任务日志
        Route::get('taskLogs', 'version/taskLogs');
        // 重试在线更新任务
        Route::post('retryTask', 'version/retryTask');
        // 手动回滚在线更新任务
        Route::post('rollbackTask', 'version/rollbackTask');
        // 上传更新包
        Route::post('upload', 'version/upload');
        // 应用更新
        Route::post('apply', 'version/apply');
        // 获取更新历史
        Route::get('history', 'version/history');
        // 设置版本号
        Route::post('set', 'version/setVersion');
    });
    })->middleware(\app\admin\middleware\AdminAuth::class);

    Route::post('testEmailConfig', 'index/testEmailConfig')
        ->middleware(\app\admin\middleware\AdminAuth::class);
});
