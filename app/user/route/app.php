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

// ============================================================
// 新版用户端接口（V2 · 对齐规划阶段1）—— 放在最前，确保优先于旧 /api/login 等前缀规则匹配
// 统一返回格式 { code, show, msg, data }；登录态走 Header token
// 控制器：app\user\controller\V2Login / V2User / V2Site
// ============================================================
// —— 登录 / 注册 ——
Route::post('/api/login/account', 'v2Login/account');                 // 账号密码/邮箱验证码登录
Route::post('/api/login/register', 'v2Login/register');               // 一步注册（账号密码）
Route::post('/api/login/logout', 'v2Login/logout');                   // 退出登录
Route::get('/api/login/registerConfig', 'v2Login/registerConfig');    // 注册/登录配置
Route::get('/api/wechat/scanLoginUrl', 'v2Login/scanWxLoginUrl');      // 微信扫码登录二维码配置
Route::get('/api/login/checkUsername', 'v2Login/checkUsername');      // 用户名/账号可用性
Route::post('/api/login/registerPreCheck', 'v2Login/registerPreCheck');   // 注册第一步：预校验+令牌
Route::post('/api/login/registerSendCode', 'v2Login/registerSendCode');   // 注册第二步：发验证码
Route::post('/api/login/registerComplete', 'v2Login/registerComplete');   // 注册完成：创建账号
Route::post('/api/login/sendEmailCode', 'v2Login/sendEmailCode');     // 发送邮箱登录验证码
// —— 用户中心 ——
Route::get('/api/user/info', 'v2User/info');                          // 当前用户信息（需 token）
Route::post('/api/user/changePwd', 'v2User/changePwd');               // 修改登录密码（token）
Route::post('/api/user/update', 'v2User/update');                     // 修改资料/昵称（token）
Route::get('/api/user/loginLog', 'v2User/loginLog');                  // 登录历史（token，分页）
// —— 站点配置（前端 useSite）——
Route::get('/api/agent/site', 'v2Site/site');                         // 站点配置
// —— 公告（前端 useAnnouncement）——
Route::get('/api/announcement/popup', 'v2Announcement/popup');        // 弹窗公告（最新一条启用）
Route::get('/api/announcement/lists', 'v2Announcement/lists');        // 公告列表
Route::get('/api/announcement/detail', 'v2Announcement/detail');      // 公告详情
// —— 邀请码校验（注册基础闭环依赖；本部署无代理体系，任意非空码有效）——
Route::post('/api/agent/validateCode', 'v2Agent/validateCode');        // 邀请码校验
// —— 余额 / 工作台（api-doc「账号与余额」组）——
Route::get('/api/pc/dashboard', 'v2Balance/dashboard');               // 工作台汇总
Route::get('/api/pc/userBalance', 'v2Balance/userBalance');           // 当前余额
Route::get('/api/pc/accountLogs', 'v2Balance/accountLogs');           // 余额流水
Route::get('/api/package/balance', 'v2Balance/packageBalance');       // 套餐余额页
// —— 订单查询（api-doc「订单查询」组）——
Route::post('/api/order/rechargeList', 'v2Order/rechargeList');       // 充值订单
Route::post('/api/order/paperList', 'v2Order/paperList');             // 论文订单
Route::post('/api/order/pptList', 'v2Order/pptList');                 // PPT 订单
Route::post('/api/order/writeList', 'v2Order/writeList');             // 写作订单
Route::post('/api/order/autodocList', 'v2Order/autodocList');         // 自动排版订单
// —— 充值（写侧）——
Route::get('/api/recharge/config', 'v2Recharge/config');              // 充值配置（渠道启停生效）
Route::post('/api/recharge/recharge', 'v2Recharge/recharge');         // 创建充值订单并发起支付
// —— 高级论文（create/OutlineEditor，桥接主站 openapi，双余额扣费）——
Route::get('/api/pc/searchTemplates', 'v2PaperAi/searchTemplates');   // 公共模板
Route::get('/api/pc/userTemplates', 'v2PaperAi/userTemplates');       // 用户/私有模板
Route::post('/api/pc/createOrder', 'v2PaperAi/createOrder');          // 双余额下单
Route::get('/api/ai/models', 'v2PaperAi/models');                     // 论文模型列表
Route::get('/api/ai/outlineDetail', 'v2PaperAi/outlineDetail');       // 大纲详情
Route::post('/api/ai/generateOutline', 'v2PaperAi/generateOutline');  // 大纲生成（SSE）
Route::post('/api/ai/generateOutlineSync', 'v2PaperAi/generateOutlineSync'); // 大纲生成（同步）
Route::post('/api/ai/enhanceOutline', 'v2PaperAi/enhanceOutline');    // 大纲增强（SSE）
Route::post('/api/ai/updateOutline', 'v2PaperAi/updateOutline');      // 大纲回写
Route::post('/api/ai/outlineList', 'v2PaperAi/outlineList');          // 我的大纲列表
Route::post('/api/write/onlineLiterature', 'v2PaperAi/onlineLiterature'); // 在线文献检索（主站代理）
// —— 写作中心（writing/proposal|task|internship|internshipdiary，桥接主站 openapi，双余额后付费）——
Route::get('/api/write/detail', 'v2WriteAi/detail');                  // 类目详情（价格+动态表单）
Route::post('/api/write/ktoutline', 'v2WriteAi/ktoutline');           // 开题报告大纲
Route::post('/api/write/rwsoutline', 'v2WriteAi/rwsoutline');         // 任务书大纲
Route::get('/api/write/kttemplist', 'v2WriteAi/kttemplist');          // 开题模板
Route::get('/api/write/rwstemplist', 'v2WriteAi/rwstemplist');        // 任务书模板
Route::get('/api/write/sxtemplist', 'v2WriteAi/sxtemplist');          // 实习报告模板
Route::post('/api/write/uploadDocument', 'v2WriteAi/uploadDocument'); // 参考文档上传签名
Route::post('/api/write/ktsave', 'v2WriteAi/ktsave');                 // 开题下单（后付费第一步）
Route::post('/api/write/rwssave', 'v2WriteAi/rwssave');               // 任务书下单
Route::post('/api/write/sxsave', 'v2WriteAi/sxsave');                 // 实习报告下单
Route::post('/api/write/sxrzsave', 'v2WriteAi/sxrzsave');             // 实习日志下单
Route::post('/api/write/pay', 'v2WriteAi/pay');                       // 写作支付（双余额扣费）
// —— AI PPT（aippt.vue，桥接主站 openapi，双余额）——
Route::get('/api/ppt/getPriceConfig', 'v2PptAi/getPriceConfig');       // 价格（普通/高级）
Route::get('/api/ppt/templateCategories', 'v2PptAi/templateCategories');     // 普通版分类
Route::get('/api/ppt/getTemplateCategories', 'v2PptAi/getTemplateCategories'); // 高级版分类
Route::post('/api/ppt/generatePptOutline', 'v2PptAi/generatePptOutline');    // 普通版大纲
Route::post('/api/ppt/generatePptOutlineStream', 'v2PptAi/generatePptOutlineStream'); // 普通版大纲 SSE
Route::post('/api/ppt/generateAdvancedOutline', 'v2PptAi/generateAdvancedOutline'); // 高级版大纲
Route::post('/api/ppt/generateAdvancedOutlineStream', 'v2PptAi/generateAdvancedOutlineStream'); // 高级版大纲 SSE
Route::post('/api/ppt/templateList', 'v2PptAi/templateList');          // 普通版模板
Route::post('/api/ppt/getAdvancedTemplates', 'v2PptAi/getAdvancedTemplates'); // 高级版模板
Route::get('/api/ppt/getAdvancedTemplateCategories', 'v2PptAi/getAdvancedTemplateCategories'); // 高级版模板分类（含数量）
Route::post('/api/ppt/createPptOrder', 'v2PptAi/createPptOrder');      // 普通版下单（双余额）
Route::post('/api/ppt/createAdvancedOrder', 'v2PptAi/createAdvancedOrder'); // 高级版下单（双余额）
Route::post('/api/ppt/download', 'v2PptAi/downloadPpt');               // 下载（后付费双余额扣费，兼容 aippt 内部）
Route::post('/api/ppt/downloadPpt', 'v2PptAi/downloadPpt');            // 下载（订单中心下载按钮）
// —— 自动排版（autodoc.vue 格式重排，桥接主站 openapi，后付费下载扣费）——
Route::post('/api/autodoc/templateList', 'v2AutodocAi/templateList');       // 排版模板
Route::post('/api/autodoc/templateConfig', 'v2AutodocAi/templateConfig');   // 模板预览配置
Route::post('/api/autodoc/getUploadSignature', 'v2AutodocAi/getUploadSignature'); // 上传签名
Route::post('/api/autodoc/uploadDoc', 'v2AutodocAi/uploadDoc');             // 文档解析入口
Route::post('/api/autodoc/generate', 'v2AutodocAi/generate');               // 生成/下单（后付费只建单不扣费）
Route::post('/api/autodoc/downloadDoc', 'v2AutodocAi/downloadDoc');         // 下载（首次双余额扣费）
// —— 写作小工具（createtitle/rewrite/illustration/createchart/paperweight/wxlist，
//    桥接主站 openapi /openapi/tools/*，免费直通不扣本站余额）——
Route::post('/api/tools/createtitle', 'v2ToolsAi/createtitle');            // 题目生成
Route::post('/api/tools/rewrite', 'v2ToolsAi/rewrite');                    // 段落改写
Route::post('/api/tools/illustration', 'v2ToolsAi/illustration');          // 段落配图
Route::post('/api/tools/createchart', 'v2ToolsAi/createchart');            // 图表生成
Route::post('/api/paper_weight/rewrite', 'v2ToolsAi/paperweightRewrite');  // 论文增重（mode→targetWordCount）
Route::post('/api/other_api/wxlist_relevance', 'v2ToolsAi/wxlistRelevance'); // 在线文献（相关度包装）
// —— 论文模板管理（template.vue 模板制作 / template-fanwen.vue 范文导入 / user.vue 我的模板，
//    桥接主站 openapi /openapi/template/*，免费；agent_str=uid 隔离归属）——
Route::get('/api/template/list', 'v2TemplateAi/list');                  // 我的模板列表
Route::get('/api/template/detail', 'v2TemplateAi/detail');              // 模板详情
Route::post('/api/template/save', 'v2TemplateAi/save');                 // 保存模板
Route::post('/api/template/update', 'v2TemplateAi/update');             // 更新模板
Route::post('/api/template/delete', 'v2TemplateAi/delete');             // 删除模板
Route::post('/api/template/uploadLogo', 'v2TemplateAi/uploadLogo');     // logo 直传签名（响应换算 put_url）
Route::post('/api/template/uploadCover', 'v2TemplateAi/uploadCover');   // 封面直传签名
Route::post('/api/template/demonstrate', 'v2TemplateAi/demonstrate');   // 排版演示（jsondata→conjson）
Route::post('/api/template/importFanwenStream', 'v2TemplateAi/importFanwenStream'); // 范文导入（SSE 透传+done 帧归一化）
// —— 套餐商城（package-shop.vue，桥接主站 openapi /openapi/package/*，仅售时长包，双余额扣费）——
Route::get('/api/package/lists', 'v2PackageAi/lists');                 // 可售套餐列表
Route::post('/api/package/createOrder', 'v2PackageAi/createOrder');    // 购买（双扣费）
// —— 文档 AI 检测（ai-check.vue，桥接主站 openapi /openapi/ai_check/*，时长包优先/余额双扣）——
Route::get('/api/ai_check/platforms', 'v2AiCheckAi/platforms');            // 检测平台列表（补 report 对照）
Route::get('/api/ai_check/wallet', 'v2AiCheckAi/wallet');                  // 计费钱包（本站口径）
Route::post('/api/ai_check/claimFree', 'v2AiCheckAi/claimFree');           // 免费额度领取（未开放）
Route::post('/api/ai_check/getUploadSignature', 'v2AiCheckAi/getUploadSignature'); // OSS 直传签名
Route::post('/api/ai_check/detect', 'v2AiCheckAi/detect');                 // 提交检测（同步，双余额扣费）
// ================= 占位通配（api-doc 范围内暂未接入的文档业务组）=================
// 置于所有准确实体路由之后；命中上述 V2 已实现接口的仍走实体路由（路由按注册顺序优先匹配）。
// 归一到 V2Stub：读类→code1空结构，写/长任务类→「功能暂未接入」。
// —— 论文/大纲/模板搜索（pc 已有并守恒时实体路由优先）——
Route::rule('/api/pc/:action', 'v2Stub/stub')->append(['group' => 'pc']);
Route::rule('/api/ai/:action', 'v2Stub/stub')->append(['group' => 'ai']);
// —— 论文模板（前端 /api/template、/api/paper_weight）——
Route::rule('/api/template/:action', 'v2Stub/stub')->append(['group' => 'template']);
Route::rule('/api/paper_weight/:action', 'v2Stub/stub')->append(['group' => 'paper_weight']);
// —— PPT ——
Route::rule('/api/ppt/:action', 'v2Stub/stub')->append(['group' => 'ppt']);
// —— 自动排版 ——
Route::rule('/api/autodoc/:action', 'v2Stub/stub')->append(['group' => 'autodoc']);
// —— AIGC 降重（aigcreduceweight.vue，桥接主站 openapi，直连透传计费）——
Route::get('/api/jiangchong/config', 'v2JiangchongAi/config');               // 降重配置
Route::get('/api/jiangchong/wallet', 'v2JiangchongAi/wallet');               // 套餐钱包
Route::get('/api/jiangchong/quota', 'v2JiangchongAi/quota');                 // 套餐额度明细
Route::get('/api/jiangchong/updates', 'v2JiangchongAi/updates');             // 官方更新（占位空列表）
Route::post('/api/jiangchong/adjc', 'v2JiangchongAi/adjc');                  // 段落降重
Route::get('/api/jiangchong/records', 'v2JiangchongAi/records');             // 降重记录
Route::post('/api/check/presign', 'v2JiangchongAi/presign');                 // OSS 直传签名
Route::post('/api/check/wordcount', 'v2JiangchongAi/wordcount');             // 字数统计
Route::post('/api/check/create_jcorder', 'v2JiangchongAi/createJcorder');    // 创建文档降重订单
Route::get('/api/check/orderList', 'v2JiangchongAi/orderList');              // 文档降重订单列表（agent_str 隔离）
// —— 文档降重 / 段落降重（check / jiangchong 其余动作走 stub）——
Route::rule('/api/check/:action', 'v2Stub/stub')->append(['group' => 'check']);
Route::rule('/api/jiangchong/:action', 'v2Stub/stub')->append(['group' => 'jiangchong']);
// —— 写作中心 / 写作小工具 ——
Route::rule('/api/write/:action', 'v2Stub/stub')->append(['group' => 'write']);
Route::rule('/api/tools/:action', 'v2Stub/stub')->append(['group' => 'tools']);
// —— 套餐商城 / 充值 / 其它辅助 ——
Route::rule('/api/package/:action', 'v2Stub/stub')->append(['group' => 'package']);
Route::rule('/api/recharge/:action', 'v2Stub/stub')->append(['group' => 'recharge']);
Route::rule('/api/other_api/:action', 'v2Stub/stub')->append(['group' => 'other_api']);

// 用户端路由（旧版 PHP 页面已剔除，统一走 Nuxt /pc/）
Route::get('/', 'index/index');
Route::get('/index', 'index/index');
Route::get('/query', function () { return redirect('/pc/user'); });
Route::get('/order', function () { return redirect('/pc/user'); });

// 维护模式检查接口
Route::get('/checkMaintenance', 'index/checkMaintenance');

// 聚合快捷登录 OAuth（需在后台启用并配置）
Route::get('/social/oauth/start', 'social/oauthStart');
Route::get('/social/oauth/callback', 'social/oauthCallback');
Route::get('/social/oauth/wx_mp_bridge', 'social/wxMpBridge');
Route::any('/social/wechat/mp', 'social/wechatMpServer');

// 余额相关路由 - 放在前面确保API路由优先匹配
Route::group('balance', function () {
    // 获取余额
    Route::get('/getBalance', 'balance/getBalance');
    
    // 计算赠送金额
    Route::post('/calculateBonus', 'balance/calculateBonus');
    
    // 创建充值订单
    Route::post('/createRechargeOrder', 'balance/createRechargeOrder');
    
    // 获取交易记录
    Route::get('/getHistory', 'balance/getHistory');
    
    // 获取充值赠送规则
    Route::get('/getRechargeBonusRules', 'balance/getRechargeBonusRules');
});

// 公告相关路由
Route::get('/announcement/getAnnouncement', 'announcement/getAnnouncement');

// 个人中心相关路由（旧版 PHP 页面已剔除 → Nuxt /pc/user）
Route::get('/profile', function () { return redirect('/pc/user'); });
Route::get('/security', function () { return redirect('/pc/user'); });
Route::get('/orders$', function () { return redirect('/pc/user'); }); // 原 orders/index（页面）
Route::get('/settings', function () { return redirect('/pc/user'); });
// 论文大纲模板管理页面（旧版已剔除 → Nuxt /pc/outline）
Route::get('/outline_templates', function () { return redirect('/pc/outline'); });
Route::get('/balance$', function () { return redirect('/pc/user'); });
Route::get('/balance/records', function () { return redirect('/pc/user'); });
// 论文模板配置（写作配置 - 论文模板）（旧版已剔除 → Nuxt /pc/autodoc）
Route::get('/autodoc/create', function () { return redirect('/pc/autodoc'); });
// 商品类型页面（旧版已剔除 → Nuxt）
Route::get('/gjlw', function () { return redirect('/pc/create'); });
Route::get('/ppt', function () { return redirect('/pc/aippt'); });
// 通用商品页面（旧版已剔除 → Nuxt 首页）
Route::get('/product', function () { return redirect('/pc/'); });

// 旧版 H5 曾挂在 /pc/m 下的历史路径兼容：统一 301 到 /m/（剩余路径与查询参数原样保留，剔除 nginx rewrite 注入的内部 s 参数）
Route::get('/pc/m', function () {
    $rest = preg_replace('#^pc/m/?#', '', (string) \think\facade\Request::pathinfo());
    $q = $_GET;
    unset($q['s']);
    $qs = http_build_query($q);
    return redirect('/m/' . $rest . ($qs !== '' ? '?' . $qs : ''), 301);
});
Route::get('/pc/m/:rest', function () {
    $rest = preg_replace('#^pc/m/?#', '', (string) \think\facade\Request::pathinfo());
    $q = $_GET;
    unset($q['s']);
    $qs = http_build_query($q);
    return redirect('/m/' . $rest . ($qs !== '' ? '?' . $qs : ''), 301);
});

// 支付页面路由（旧版已剔除 → Nuxt 个人中心/充值；/payment/success|fail 由支付回调使用，保留）
// 注：裸 /service/* 会被同名 service 应用目录拦截（历史 404），仅 /view/service/* 生效
Route::get('/view/service/payment', function () { return redirect('/pc/user'); });
Route::get('/view/service/payment.html', function () { return redirect('/pc/user'); });

// 论文在线编辑页面（Autodoc）（旧版已剔除 → Nuxt /pc/autodoc）
Route::get('/view/autodoc/lwedit.html', function () { return redirect('/pc/autodoc'); });
Route::get('/view/autodoc/lwedit', function () { return redirect('/pc/autodoc'); });

// 小工具中心页面路由（旧版已剔除 → Nuxt /pc/tools）
Route::get('/view/service/tools_center', function () { return redirect('/pc/tools'); });
Route::get('/view/service/tools_center.html', function () { return redirect('/pc/tools'); });

// 其余旧版页面 auto-route 兜底（模板已剔除，防 500）
Route::get('/autodoc', function () { return redirect('/pc/autodoc'); });
Route::get('/autodoc/edit', function () { return redirect('/pc/autodoc'); });
Route::get('/writing_center', function () { return redirect('/pc/writing'); });
Route::get('/announcement/test', function () { return redirect('/pc/'); });

// 用户端其他API路由（Api控制器中的方法）
Route::group('api', function () {
    Route::get('/getProducts', 'api/getProducts');
    Route::get('/getToolsProducts', 'api/getToolsProducts');
    Route::get('/getProduct', 'api/getProduct');
    Route::get('/getProductNotice', 'api/getProductNotice');
    
    // 邮箱验证码相关
    Route::get('/checkEmailVerify', 'api/checkEmailVerify');
    Route::post('/sendRegisterCode', 'api/sendRegisterCode');
    Route::post('/register', 'api/register');
    
    // 用户登录相关
    Route::post('/login', 'api/login');
    Route::post('/logout', 'api/logout');
    Route::get('/getUserInfo', 'api/getUserInfo');
    Route::get('/socialLoginConfig', 'api/socialLoginConfig');
    Route::get('/socialWxOpenPrepare', 'api/socialWxOpenPrepare');
    Route::get('/socialWxMpPrepare', 'api/socialWxMpPrepare');
    Route::get('/socialWxMpPoll', 'api/socialWxMpPoll');
    Route::post('/socialWxMpConfirm', 'api/socialWxMpConfirm');
    
    // 忘记密码相关
    Route::post('/sendResetPasswordCode', 'api/sendResetPasswordCode');
    Route::post('/resetPassword', 'api/resetPassword');
    
    // 后台邮件发送（内部使用）
    Route::get('/sendEmailBackground', 'api/sendEmailBackground');
    
    // 常见问题相关
    Route::get('/getFaqList', 'api/getFaqList');
});

// 支付相关路由
Route::group('payment', function () {
    // 支付页面（旧版已剔除 → Nuxt 个人中心/充值）
    Route::get('/index', function () { return redirect('/pc/user'); });
    
    // 创建订单
    Route::post('/createOrder', 'payment/createOrder');
    
    // 获取支付方式
    Route::get('/getPaymentMethods', 'payment/getPaymentMethods');
    
    // 发起支付
    Route::post('/pay', 'payment/pay');
    
    // 支付成功页面
    Route::get('/success', 'payment/success');
    
    // 支付失败页面
    Route::get('/fail', 'payment/fail');
    
    // 检查支付状态
    Route::get('/checkStatus', 'payment/checkStatus');
    
    // 支付宝H5支付中间页面
    Route::get('/alipay/h5', 'payment/alipayH5');
    
    // 支付回调通知
    Route::any('/epayNotify', 'payment/epayNotify');
    Route::any('/alipayNotify', 'payment/alipayNotify');
    Route::any('/wechatNotify', 'payment/wechatNotify');
});
