-- AI写作助手（对接站）初始安装数据
-- 仅包含表结构与最小种子数据，业务数据请在部署后通过管理后台配置。

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ad_admins
-- ----------------------------
DROP TABLE IF EXISTS `ad_admins`;
CREATE TABLE `ad_admins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `role` varchar(50) DEFAULT 'admin' COMMENT '角色',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态: 1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT '' COMMENT '最后登录IP',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- Table structure for ad_announcement
-- ----------------------------
DROP TABLE IF EXISTS `ad_announcement`;
CREATE TABLE `ad_announcement` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '公告标题',
  `content` text COMMENT '公告内容',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态: 1显示 0隐藏',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='系统公告表';

-- ----------------------------
-- Table structure for ad_api_config
-- ----------------------------
DROP TABLE IF EXISTS `ad_api_config`;
CREATE TABLE `ad_api_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_key` varchar(100) NOT NULL DEFAULT '' COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `config_desc` varchar(255) DEFAULT '' COMMENT '配置说明',
  `config_type` varchar(20) DEFAULT 'string' COMMENT '配置类型: string, number, boolean, json',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API配置参数表';

-- ----------------------------
-- Table structure for ad_app_ver
-- ----------------------------
DROP TABLE IF EXISTS `ad_app_ver`;
CREATE TABLE `ad_app_ver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ver` varchar(20) NOT NULL COMMENT '版本号',
  `utime` datetime NOT NULL COMMENT '更新时间',
  `memo` text COMMENT '更新说明',
  PRIMARY KEY (`id`),
  KEY `i_ver` (`ver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统版本记录表';

-- ----------------------------
-- Table structure for ad_balance_logs
-- ----------------------------
DROP TABLE IF EXISTS `ad_balance_logs`;
CREATE TABLE `ad_balance_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `change_type` varchar(20) NOT NULL DEFAULT '' COMMENT '变动类型：recharge充值, consume消费, refund退款, adjust调整',
  `change_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动金额（正数为增加，负数为减少）',
  `before_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动前余额',
  `after_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动后余额',
  `related_id` varchar(50) DEFAULT '' COMMENT '关联ID（如订单号、充值订单号等）',
  `related_type` varchar(50) DEFAULT '' COMMENT '关联类型：order订单, recharge充值, adjust调整',
  `remark` text COMMENT '变动说明',
  `operator_id` int(11) unsigned DEFAULT NULL COMMENT '操作人ID（NULL表示系统自动）',
  `operator_type` varchar(20) DEFAULT 'system' COMMENT '操作人类型：system系统, admin管理员, user用户',
  `ip_address` varchar(50) DEFAULT '' COMMENT '操作IP地址',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `bonus_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额（仅充值类型有效）',
  `total_change_amount` decimal(10,2) DEFAULT '0.00' COMMENT '总变动金额（变动金额+赠送金额）',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `change_type` (`change_type`),
  KEY `related_id` (`related_id`),
  KEY `create_time` (`create_time`),
  KEY `idx_uid_ctime` (`user_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COMMENT='余额变动日志表';

-- ----------------------------
-- Table structure for ad_email_config
-- ----------------------------
DROP TABLE IF EXISTS `ad_email_config`;
CREATE TABLE `ad_email_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `smtp_host` varchar(100) DEFAULT '' COMMENT 'SMTP服务器地址',
  `smtp_port` int(11) DEFAULT '25' COMMENT 'SMTP端口',
  `smtp_username` varchar(100) DEFAULT '' COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT '' COMMENT 'SMTP密码',
  `smtp_encryption` varchar(10) DEFAULT 'none' COMMENT '加密方式: none, ssl, tls',
  `from_email` varchar(100) DEFAULT '' COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT '' COMMENT '发件人名称',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态: 1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='邮件配置表';

-- ----------------------------
-- Table structure for ad_email_verify_codes
-- ----------------------------
DROP TABLE IF EXISTS `ad_email_verify_codes`;
CREATE TABLE `ad_email_verify_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `verify_code` varchar(10) NOT NULL DEFAULT '' COMMENT '验证码',
  `verify_type` varchar(20) DEFAULT 'register' COMMENT '验证类型: register注册, reset_password重置密码',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态: 0未使用 1已使用',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `verify_type` (`verify_type`),
  KEY `status` (`status`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='邮箱验证码表';

-- ----------------------------
-- Table structure for ad_faq
-- ----------------------------
DROP TABLE IF EXISTS `ad_faq`;
CREATE TABLE `ad_faq` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '常见问题ID',
  `question` varchar(255) NOT NULL DEFAULT '' COMMENT '问题标题',
  `answer` text COMMENT '问题答案',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序顺序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态: 1显示 0隐藏',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='常见问题表';

-- ----------------------------
-- Table structure for ad_orders
-- ----------------------------
DROP TABLE IF EXISTS `ad_orders`;
CREATE TABLE `ad_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号（唯一）',
  `user_id` int(11) unsigned DEFAULT NULL COMMENT '用户ID（NULL表示游客订单）',
  `guest_token` varchar(64) DEFAULT NULL COMMENT '游客标识（用于关联游客订单）',
  `product_id` int(11) unsigned NOT NULL COMMENT '商品ID',
  `product_code` varchar(50) NOT NULL DEFAULT '' COMMENT '商品代码',
  `product_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `quantity` int(11) DEFAULT '1' COMMENT '数量',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `payment_method` varchar(50) DEFAULT '' COMMENT '支付方式代码',
  `payment_method_name` varchar(100) DEFAULT '' COMMENT '支付方式名称',
  `status` tinyint(1) DEFAULT '0' COMMENT '订单状态：0待支付 1已支付 2处理中 3已完成 4已取消 5已退款 6支付异常',
  `pay_status` tinyint(1) DEFAULT '0' COMMENT '支付状态：0未支付 1已支付 2支付失败',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_transaction_id` varchar(100) DEFAULT '' COMMENT '支付交易号',
  `contact_name` varchar(50) DEFAULT '' COMMENT '联系人姓名（游客订单必填）',
  `contact_email` varchar(100) DEFAULT '' COMMENT '联系邮箱（游客订单必填）',
  `contact_phone` varchar(20) DEFAULT '' COMMENT '联系电话（游客订单必填）',
  `remark` text COMMENT '订单备注',
  `product_data` text COMMENT '商品特定数据（JSON格式，如开题报告的大纲、文献列表等）',
  `tokenapi_submitted` tinyint(1) DEFAULT '0' COMMENT '是否已提交到TokenAPI：0未提交 1已提交',
  `tokenapi_response` text COMMENT 'TokenAPI响应数据（JSON格式）',
  `ip_address` varchar(50) DEFAULT '' COMMENT '下单IP地址',
  `user_agent` varchar(255) DEFAULT '' COMMENT '用户代理',
  `expire_time` datetime DEFAULT NULL COMMENT '订单过期时间（未支付订单自动取消）',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `guest_token` (`guest_token`),
  KEY `status` (`status`),
  KEY `pay_status` (`pay_status`),
  KEY `create_time` (`create_time`),
  KEY `idx_uid_code_pay` (`user_id`,`product_code`,`pay_status`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ----------------------------
-- Table structure for ad_paper_outlines
-- ----------------------------
DROP TABLE IF EXISTS `ad_paper_outlines`;
CREATE TABLE `ad_paper_outlines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '本站用户ID',
  `outline_no` varchar(64) NOT NULL DEFAULT '' COMMENT '主站大纲编号',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '论文标题',
  `degree` varchar(50) NOT NULL DEFAULT '' COMMENT '学历层次',
  `profession` varchar(100) NOT NULL DEFAULT '' COMMENT '专业',
  `words` varchar(20) NOT NULL DEFAULT '' COMMENT '字数文案',
  `model` varchar(30) NOT NULL DEFAULT '' COMMENT '模型code',
  `is_ordered` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待下单 1已下单',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`id`),
  KEY `idx_outline` (`outline_no`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='论文大纲列表（生成成功落库，下单置已下单）';

-- ----------------------------
-- Table structure for ad_payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `ad_payment_methods`;
CREATE TABLE `ad_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `code` varchar(50) NOT NULL COMMENT '支付方式代码',
  `name` varchar(100) NOT NULL COMMENT '支付方式名称',
  `display_name` varchar(100) DEFAULT NULL COMMENT '显示名称',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标类型',
  `icon_text` varchar(10) DEFAULT NULL COMMENT '图标文字',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用 0-禁用 1-启用',
  `config` text COMMENT '配置参数(JSON格式)',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='支付方式表';

-- ----------------------------
-- Table structure for ad_products
-- ----------------------------
DROP TABLE IF EXISTS `ad_products`;
CREATE TABLE `ad_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '商品代码（如：kaiti, rws, sx, sxrz, gjlw, ppt, reduce）',
  `product_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '商品类型：1主要商品 2工具类商品 3增值服务 4查重降重 ',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '对接成本价',
  `follow_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT '跟价开关：1开启 0关闭（开启时在成本价基础上加价）',
  `markup` decimal(10,2) DEFAULT '0.00' COMMENT '加价金额',
  `enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用：1启用 0禁用',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '首页默认项：1是 0否（只能有一个商品为默认）',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `page_url` varchar(255) DEFAULT '' COMMENT '对应页面的网页路径（用户端点击商品时跳转的页面）',
  `notice_text` text COMMENT '商品滚动条通知（客户端展示）',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `guide_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '左侧下单引导图开关：1显示 0隐藏',
  `guide_image` varchar(255) NOT NULL DEFAULT '' COMMENT '左侧下单引导图图片路径',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COMMENT='商品管理表';

-- ----------------------------
-- Table structure for ad_recharge_bonus_rules
-- ----------------------------
DROP TABLE IF EXISTS `ad_recharge_bonus_rules`;
CREATE TABLE `ad_recharge_bonus_rules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '规则ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '规则类型：1按比例赠送 2按固定金额赠送',
  `min_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最小充值金额（包含）',
  `max_amount` decimal(10,2) DEFAULT NULL COMMENT '最大充值金额（不包含，NULL表示无上限）',
  `bonus_percent` decimal(5,2) DEFAULT '0.00' COMMENT '赠送比例（百分比，rule_type=1时有效）',
  `bonus_fixed` decimal(10,2) DEFAULT '0.00' COMMENT '固定赠送金额（rule_type=2时有效）',
  `description` varchar(255) DEFAULT '' COMMENT '规则描述',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_amount_range` (`min_amount`,`max_amount`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='充值赠送规则表';

-- ----------------------------
-- Table structure for ad_recharge_orders
-- ----------------------------
DROP TABLE IF EXISTS `ad_recharge_orders`;
CREATE TABLE `ad_recharge_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '充值订单ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '充值订单号（唯一）',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `payment_method` varchar(50) DEFAULT '' COMMENT '支付方式代码',
  `payment_method_name` varchar(100) DEFAULT '' COMMENT '支付方式名称',
  `status` tinyint(1) DEFAULT '0' COMMENT '订单状态：0待支付 1已支付 2支付失败 3已取消',
  `pay_status` tinyint(1) DEFAULT '0' COMMENT '支付状态：0未支付 1已支付 2支付失败',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_transaction_id` varchar(100) DEFAULT '' COMMENT '支付交易号',
  `remark` text COMMENT '订单备注',
  `ip_address` varchar(50) DEFAULT '' COMMENT '下单IP地址',
  `user_agent` varchar(255) DEFAULT '' COMMENT '用户代理',
  `expire_time` datetime DEFAULT NULL COMMENT '订单过期时间（未支付订单自动取消）',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `bonus_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实际到账总金额（充值金额+赠送金额）',
  `bonus_rule_id` int(11) unsigned DEFAULT NULL COMMENT '使用的赠送规则ID',
  `bonus_rule_name` varchar(100) DEFAULT '' COMMENT '使用的赠送规则名称',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `pay_status` (`pay_status`),
  KEY `create_time` (`create_time`),
  KEY `idx_uid_pay` (`user_id`,`pay_status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='充值订单表';

-- ----------------------------
-- Table structure for ad_site_info
-- ----------------------------
DROP TABLE IF EXISTS `ad_site_info`;
CREATE TABLE `ad_site_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `site_name` varchar(100) DEFAULT '' COMMENT '站点名称',
  `site_url` varchar(255) DEFAULT '' COMMENT '网站URL',
  `site_logo` varchar(255) DEFAULT '' COMMENT '站点Logo',
  `site_icon` varchar(255) DEFAULT '' COMMENT '站点图标',
  `site_keywords` varchar(255) DEFAULT '' COMMENT '站点关键词',
  `site_description` text COMMENT '站点描述',
  `site_copyright` varchar(255) DEFAULT '' COMMENT '版权信息',
  `site_icp` varchar(50) DEFAULT '' COMMENT 'ICP备案号',
  `site_address` varchar(255) DEFAULT '' COMMENT '站点地址',
  `site_phone` varchar(50) DEFAULT '' COMMENT '联系电话',
  `site_email` varchar(100) DEFAULT '' COMMENT '联系邮箱',
  `default_language` varchar(10) DEFAULT 'zh-CN' COMMENT '默认语言',
  `timezone` varchar(50) DEFAULT 'Asia/Shanghai' COMMENT '时区设置',
  `date_format` varchar(20) DEFAULT 'Y-m-d' COMMENT '日期格式',
  `time_format` varchar(20) DEFAULT 'H:i:s' COMMENT '时间格式',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='站点信息表';

-- ----------------------------
-- Table structure for ad_system_config
-- ----------------------------
DROP TABLE IF EXISTS `ad_system_config`;
CREATE TABLE `ad_system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_key` varchar(100) NOT NULL DEFAULT '' COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `config_desc` varchar(255) DEFAULT '' COMMENT '配置说明',
  `config_type` varchar(20) DEFAULT 'string' COMMENT '配置类型: string, number, boolean, json',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- Table structure for ad_upd_lock
-- ----------------------------
DROP TABLE IF EXISTS `ad_upd_lock`;
CREATE TABLE `ad_upd_lock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lkey` varchar(64) NOT NULL,
  `exp_at` datetime NOT NULL,
  `c_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_lkey` (`lkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新锁';

-- ----------------------------
-- Table structure for ad_upd_log
-- ----------------------------
DROP TABLE IF EXISTS `ad_upd_log`;
CREATE TABLE `ad_upd_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tid` bigint(20) unsigned NOT NULL,
  `level` varchar(16) NOT NULL DEFAULT 'info',
  `message` varchar(255) NOT NULL DEFAULT '',
  `ctx` longtext,
  `c_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `i_tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新日志';

-- ----------------------------
-- Table structure for ad_upd_mig
-- ----------------------------
DROP TABLE IF EXISTS `ad_upd_mig`;
CREATE TABLE `ad_upd_mig` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ver` varchar(32) NOT NULL DEFAULT '',
  `sfile` varchar(255) NOT NULL DEFAULT '',
  `csum` varchar(128) NOT NULL DEFAULT '',
  `run_at` datetime NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'success',
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_vs` (`ver`,`sfile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SQL迁移记录';

-- ----------------------------
-- Table structure for ad_upd_task
-- ----------------------------
DROP TABLE IF EXISTS `ad_upd_task`;
CREATE TABLE `ad_upd_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tno` varchar(50) NOT NULL,
  `fver` varchar(32) NOT NULL DEFAULT '',
  `tver` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `step` varchar(32) NOT NULL DEFAULT 'pending',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `op_id` int(10) unsigned NOT NULL DEFAULT '0',
  `idem` varchar(80) NOT NULL DEFAULT '',
  `ecode` varchar(64) NOT NULL DEFAULT '',
  `emsg` text,
  `ctx` longtext,
  `c_at` datetime NOT NULL,
  `u_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_tno` (`tno`),
  KEY `i_st` (`status`),
  KEY `i_idem` (`idem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线更新任务';

-- ----------------------------
-- Table structure for ad_user_social_bind
-- ----------------------------
DROP TABLE IF EXISTS `ad_user_social_bind`;
CREATE TABLE `ad_user_social_bind` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '本站用户ID',
  `provider` varchar(20) NOT NULL DEFAULT '' COMMENT 'qq/wx/alipay/sina',
  `social_uid` varchar(128) NOT NULL DEFAULT '' COMMENT '第三方UID',
  `nickname` varchar(100) DEFAULT '' COMMENT '同步昵称快照',
  `avatar` varchar(500) DEFAULT '' COMMENT '同步头像快照',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_social_uid` (`provider`,`social_uid`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COMMENT='用户第三方登录绑定';

-- ----------------------------
-- Table structure for ad_users
-- ----------------------------
DROP TABLE IF EXISTS `ad_users`;
CREATE TABLE `ad_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '账户余额',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态: 1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT '' COMMENT '最后登录IP',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COMMENT='用户管理表';

-- ----------------------------
-- Table structure for system_db_migrations
-- ----------------------------
DROP TABLE IF EXISTS `system_db_migrations`;
CREATE TABLE `system_db_migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(32) NOT NULL DEFAULT '',
  `script_name` varchar(255) NOT NULL DEFAULT '',
  `checksum` varchar(128) NOT NULL DEFAULT '',
  `executed_at` datetime NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'success',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_version_script` (`version`,`script_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Seeds for ad_admins
-- ----------------------------
INSERT INTO `ad_admins` (`id`,`username`,`password`,`nickname`,`email`,`phone`,`avatar`,`role`,`status`,`last_login_time`,`last_login_ip`,`create_time`,`update_time`) VALUES (1,'admin','$2y$10$RFih7DDQvtmL9IvO5s8PYeKp4cpCbAWDWRF1G15MgIsUoDaUaKCju','超级管理员','','','','admin',1,NULL,'','2026-01-01 00:00:00','2026-01-01 00:00:00');

-- ----------------------------
-- Seeds for ad_site_info
-- ----------------------------
INSERT INTO `ad_site_info` (`id`,`site_name`,`site_url`,`site_logo`,`site_icon`,`site_keywords`,`site_description`,`site_copyright`,`site_icp`,`site_address`,`site_phone`,`site_email`,`default_language`,`timezone`,`date_format`,`time_format`,`create_time`,`update_time`) VALUES (1,'AI写作助手','','','','','','','','','','','zh-CN','Asia/Shanghai','Y-m-d','H:i:s','2026-01-01 00:00:00',NULL);

-- ----------------------------
-- Seeds for ad_system_config
-- ----------------------------
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (1,'site_site_name','对接端管理系统1','站点名称','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (2,'site_site_url','','站点URL','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (3,'site_site_keywords','','站点关键词','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (4,'site_site_description','','站点描述','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (5,'site_site_copyright','','站点版权信息','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (6,'site_site_icp','','站点ICP备案号','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (7,'site_site_address','','站点地址','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (8,'site_site_phone','','站点电话','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (9,'site_site_email','','站点邮箱','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (10,'site_default_language','zh-CN','站点默认语言','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (11,'site_timezone','Asia/Shanghai','站点时区','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (12,'site_date_format','Y-m-d','站点日期格式','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (13,'site_time_format','H:i:s','站点时间格式','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (14,'security_password_min_length','6','密码最小长度','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (15,'security_password_require_uppercase','1','要求大写字母','boolean','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (16,'security_password_require_lowercase','1','要求小写字母','boolean','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (17,'security_password_require_number','1','要求数字','boolean','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (18,'security_password_require_special','1','要求特殊字符','boolean','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (19,'security_password_expire_days','0','密码过期天数(0为不过期)','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (20,'security_login_max_attempts','5','最大登录尝试次数','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (21,'security_login_lockout_time','30','登录锁定时间(分钟)','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (22,'security_enable_captcha','1','启用验证码','boolean','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (23,'security_enable_2fa','0','安全启用双因素认证','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (24,'security_session_timeout','1440','安全会话超时时间','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (25,'security_config','{"password_min_length":6,"password_require_uppercase":1,"password_require_lowercase":1,"password_require_number":1,"password_require_special":1,"password_expire_days":0,"login_max_attempts":5,"login_lockout_time":30,"enable_captcha":0,"enable_email_verify":1,"email_verify_expire":10,"enable_2fa":0,"session_timeout":1440,"disable_self_register":0,"user_single_session":0}','安全配置参数','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (26,'site_config','{"site_name":"AI写作助手","site_url":"","site_keywords":"AI写作,论文范文生成,毕业论文!","site_description":"AI写作助手系统","site_copyright":"","site_icp":"","site_address":"","site_phone":"","site_email":"","default_language":"zh-CN","timezone":"Asia/Shanghai","date_format":"Y-m-d","time_format":"H:i:s","site_logo":"","site_icon":""}','网站基本配置','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (27,'email_smtp_host','smtp.exmail.qq.com','邮件SMTP服务器','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (28,'email_smtp_port','465','邮件SMTP端口','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (29,'email_smtp_username','','邮件SMTP用户名','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (30,'email_smtp_password','','邮件SMTP密码','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (31,'email_smtp_encryption','none','邮件加密方式','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (32,'email_from_email','','邮件发件人邮箱','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (33,'email_from_name','站长','邮件发件人名称','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (34,'email_status','0','邮件状态','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (35,'customer_service_config','{"enabled":1,"email":"","phone":"","qq":"","wechat":"","working_hours":"工作时间：周一至周五 9:00-18:00","show_float_button":1}','客服配置参数','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (36,'system_maintenance_mode','0','系统维护模式','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (37,'system_debug_mode','0','系统调试模式','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (38,'system_api_rate_limit','100','系统API速率限制','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (39,'system_api_rate_period','60','系统API速率周期','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (40,'system_file_upload_max_size','10','系统文件上传最大大小','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (41,'system_allowed_file_types','jpg,jpeg,png,gif,pdf,doc,docx','系统允许的文件类型','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (42,'system_backup_enabled','0','系统启用备份','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (43,'system_backup_frequency','daily','系统备份频率','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (44,'system_log_retention_days','30','系统日志保留天数','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (45,'system_cache_enabled','0','系统启用缓存','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (46,'system_cache_ttl','3600','系统缓存TTL','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (47,'system_maintenance_message','网站正在维护中，请稍后再访问。','系统维护模式提示信息','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (48,'system_page_size','15','系统列表每页显示数量','number','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (49,'system_maintenance_end_time','','系统maintenance_end_time','string','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (50,'user_theme_config','{\n    "primary_color": "#6366F1",\n    "primary_hover": "#4F46E5",\n    "primary_light": "#EEF2FF",\n    "primary_dark": "#4338CA"\n}','用户端主题配色','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (51,'social_login_config','{"enabled":0,"connect_url":"","appid":"","appkey":"","types":{"qq":1,"wx":1,"alipay":0,"sina":1}}','聚合快捷登录对接','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (52,'social_wx_open_config','{"enabled":0,"appid":"","appsecret":""}','微信开放平台扫码登录','json','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_system_config` (`id`,`config_key`,`config_value`,`config_desc`,`config_type`,`create_time`,`update_time`) VALUES (53,'social_wx_mp_config','{"enabled":0,"appid":"","appsecret":"","mp_token":"","encoding_aes_key":"","login_success_reply":""}','微信服务号关注登录','json','2026-01-01 00:00:00','2026-01-01 00:00:00');

-- ----------------------------
-- Seeds for ad_payment_methods
-- ----------------------------
INSERT INTO `ad_payment_methods` (`id`,`code`,`name`,`display_name`,`icon`,`icon_text`,`sort_order`,`enabled`,`config`,`create_time`,`update_time`) VALUES (1,'balance','余额支付','余额支付','balance','¥',0,1,'{}','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_payment_methods` (`id`,`code`,`name`,`display_name`,`icon`,`icon_text`,`sort_order`,`enabled`,`config`,`create_time`,`update_time`) VALUES (2,'wechat','微信支付','微信支付','wechat','微',10,0,'{"mchid":"","appid":"","paySignKey":"","apiclient_cert":"","apiclient_key":"","app_id":"","private_key":"","public_key":"","pid":"","key":"","api_url":"","enable_alipay":0,"enable_wechat":0}','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_payment_methods` (`id`,`code`,`name`,`display_name`,`icon`,`icon_text`,`sort_order`,`enabled`,`config`,`create_time`,`update_time`) VALUES (3,'alipay','支付宝支付','支付宝支付','alipay','支',20,0,'{"mchid":"","appid":"","paySignKey":"","apiclient_cert":"","apiclient_key":"","app_id":"","private_key":"","public_key":"","pid":"","key":"","api_url":"","enable_alipay":0,"enable_wechat":0}','2026-01-01 00:00:00','2026-01-01 00:00:00');
INSERT INTO `ad_payment_methods` (`id`,`code`,`name`,`display_name`,`icon`,`icon_text`,`sort_order`,`enabled`,`config`,`create_time`,`update_time`) VALUES (4,'epay','易支付','易支付','epay','易',30,0,'{"mchid":"","appid":"","paySignKey":"","apiclient_cert":"","apiclient_key":"","app_id":"","private_key":"","public_key":"","pid":"","key":"","api_url":"","enable_alipay":0,"enable_wechat":0}','2026-01-01 00:00:00','2026-01-01 00:00:00');

-- ----------------------------
-- Seeds for ad_products
-- ----------------------------
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (1,'gjlw',1,'AI论文','','0.00',0,'0.00',1,1,1,'','采用Multimodal+Global Thought Chain语言模型生成连贯、一致和逻辑性强的长文本技术','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (2,'ppt',1,'PPT生成','','0.00',0,'0.00',1,0,2,'','告别加班熬夜！AI赋能，一键生成专业PPT，效率提升500%！','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (3,'kaiti',1,'开题报告','','0.00',1,'0.00',1,0,3,'','将按规范结构自动生成开题报告草稿，覆盖研究背景、研究目标、研究内容、进度安排等核心模块。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (4,'rws',1,'任务书','','0.00',0,'0.00',1,0,4,'','根据选题自动生成任务书要求、工作内容、进度安排等，帮助快速对齐指导教师与学院模板。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (5,'sx',1,'实习报告','','0.00',0,'0.00',1,0,5,'','根据实习单位、岗位内容与收获总结，自动生成结构完整的实习报告文档，可按需修改润色。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (6,'sxrz',1,'实习日志','','0.00',0,'0.00',1,0,6,'','支持按日期批量生成或补齐实习日志内容，统一风格、自动区分每天任务与心得体会。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (7,'reduce',1,'查重/降重','','0.00',0,'0.00',1,0,7,'','','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (8,'tools_wxlist',2,'参考文献获取','','0.00',0,'0.00',1,0,8,'','将智能检索论文相关的期刊、会议与参考文献，支持快速复制与整理。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (9,'tools_rewrite',2,'段落改写','','0.00',0,'0.00',1,0,9,'','句子、段落一键改写，提升表述准确性与可读性，降低重复率。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (10,'tools_illustration',2,'段落配图','','0.00',0,'0.00',1,0,10,'','根据段落内容自动生成匹配插图，丰富论文或 PPT 的视觉表现。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (11,'tools_chart',2,'图表生成','','0.00',0,'0.00',1,0,11,'','根据数据与描述快速生成图表结构，辅助论文与汇报展示。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (12,'tools_createtitle',2,'题目生成','','0.00',0,'0.00',1,0,12,'','根据研究方向与关键词智能生成高质量论文题目。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (13,'tools_createoutline',2,'大纲生成','','0.00',0,'0.00',1,0,13,'','一键生成论文逻辑大纲，理清章节、段落结构思路。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (21,'tools_aigcreduceweight',2,'AIGC降重工具','','0.00',0,'0.00',1,0,1,'','AIGC智能降重，支持段落/多段落/文档降重，一键降低AIGC率与重复率。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (24,'aicheck',1,'AI检测','','0.00',0,'0.00',1,0,9,'','','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (25,'autodoc',1,'格式重排','','0.00',0,'0.00',1,0,10,'','','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (26,'jc_package',5,'降重降AI资源包','','0.00',0,'0.00',1,0,1,'','购买降重降AI资源包，支付成功后即时到账。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');
INSERT INTO `ad_products` (`id`,`code`,`product_type`,`name`,`icon`,`cost_price`,`follow_price`,`markup`,`enabled`,`is_default`,`sort_order`,`page_url`,`notice_text`,`create_time`,`update_time`,`guide_enabled`,`guide_image`) VALUES (27,'aicheck_package',5,'AI检测资源包','','0.00',0,'0.00',1,0,2,'','购买AI检测资源包，支付成功后即时到账。','2026-01-01 00:00:00','2026-01-01 00:00:00',0,'');

-- ----------------------------
-- Seeds for ad_announcement
-- ----------------------------
INSERT INTO `ad_announcement` (`id`,`title`,`content`,`status`,`create_time`,`update_time`) VALUES (1,'欢迎使用 AI写作助手','欢迎使用 AI写作助手，祝您使用愉快！',1,'2026-01-01 00:00:00',NULL);

SET FOREIGN_KEY_CHECKS = 1;
