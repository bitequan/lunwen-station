<?php
declare(strict_types=1);

// 首次部署安装向导（写入 .env / config/site.php / config/cache.php，并导入 data/adlw.sql）

$projectRoot = dirname(__DIR__);
$installBlockFile = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'install.block';
$envFile = $projectRoot . DIRECTORY_SEPARATOR . '.env';
$siteCfgFile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'site.php';
$sqlFile = $projectRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'adlw.sql';

ini_set('display_errors', 'On');
error_reporting(E_ALL);

function httpRedirect(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}

function parseEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)\s*$/', $line, $m)) {
            $key = (string) $m[1];
            $val = (string) $m[2];
            // 去掉可选的引号
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            $out[$key] = $val;
        }
    }
    return $out;
}

function upsertEnv(string $path, array $kv): bool
{
    $content = is_file($path) ? (string) @file_get_contents($path) : '';
    if ($content === '') {
        $content = '';
    }
    // 若不存在则创建一个简易文件
    $lines = $content === '' ? [] : preg_split("/\r\n|\n|\r/", $content);
    if (!is_array($lines)) {
        $lines = [];
    }

    // 先把现有 key 行替换掉
    $keys = array_keys($kv);
    $replaced = array_fill_keys($keys, false);

    $newLines = [];
    foreach ($lines as $line) {
        $handled = false;
        foreach ($kv as $key => $value) {
            $pattern = '/^' . preg_quote((string) $key, '/') . '\s*=\s*.*$/';
            if (preg_match($pattern, (string) $line)) {
                $newLines[] = $key . ' = ' . (string) $value;
                $replaced[$key] = true;
                $handled = true;
                break;
            }
        }
        if (!$handled && trim((string) $line) !== '') {
            $newLines[] = $line;
        } elseif (!$handled && trim((string) $line) === '') {
            // 忽略空行，避免无限增长
        }
    }

    foreach ($kv as $key => $value) {
        if (!($replaced[$key] ?? false)) {
            $newLines[] = $key . ' = ' . (string) $value;
        }
    }

    $final = implode(PHP_EOL, $newLines) . PHP_EOL;
    return @file_put_contents($path, $final) !== false;
}

function writeSiteAuthcode(string $path, string $authcode): bool
{
    $authcode = str_replace(["\\", "\r", "\n", "'"], ['\\\\', '', '', "\\'"], $authcode);
    $tpl = "<?php\n\n// | 站点级业务参数，按部署环境修改\n// | 勿将含真实密钥的文件提交到公开仓库\n\nreturn [\n    'authcode' => '{$authcode}',\n];\n";
    return @file_put_contents($path, $tpl) !== false;
}

function splitSqlStatements(string $sql): array
{
    // 移除注释（尽量保持与 DatabaseMigrationService 的行为一致）
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/--.*$/m', '', (string) $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', (string) $sql);

    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen((string) $sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $current .= $char;

        if (!$inString && ($char === '"' || $char === "'" || $char === '`')) {
            $inString = true;
            $stringChar = $char;
            continue;
        }

        if ($inString && $char === $stringChar) {
            // 计算前面连续反斜杠数量，判断引号是否被转义
            $backslashCount = 0;
            $j = $i - 1;
            while ($j >= 0 && $sql[$j] === '\\') {
                $backslashCount++;
                $j--;
            }
            if ($backslashCount % 2 === 0) {
                $inString = false;
                $stringChar = '';
            }
            continue;
        }

        if (!$inString && $char === ';') {
            $stmt = trim($current);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }

    $stmt = trim($current);
    if ($stmt !== '') {
        $statements[] = $stmt;
    }

    return $statements;
}

function isAlreadyExistsError(string $errMsg, string $statement, int $errNo = 0): bool
{
    $err = strtolower($errMsg);
    $stmtLower = strtolower($statement);
    // MySQL 常见“对象已存在/重复定义”错误码
    if (in_array($errNo, [1050, 1060, 1061, 1062, 1068, 1091], true)) {
        return true;
    }
    if (strpos($err, 'duplicate') !== false) {
        return true;
    }
    if (strpos($err, 'already exists') !== false) {
        return true;
    }
    if (strpos($stmtLower, 'create table') !== false && strpos($err, 'exists') !== false) {
        return true;
    }
    // 重复执行安装时，可能重复添加主键/索引；这类可视为已存在
    if (strpos($err, 'multiple primary key defined') !== false) {
        return true;
    }
    if (strpos($err, 'duplicate key name') !== false) {
        return true;
    }
    return false;
}

function normalizeDbPrefix(string $prefix): string
{
    $prefix = trim($prefix);
    if ($prefix === '') {
        return 'ad_';
    }
    $prefix = preg_replace('/[^A-Za-z0-9_]/', '', $prefix);
    if ($prefix === null || $prefix === '') {
        return 'ad_';
    }
    return (string) $prefix;
}

function rewriteInstallSqlTablePrefix(string $sql, string $sourcePrefix, string $targetPrefix): string
{
    if ($sourcePrefix === '' || $sourcePrefix === $targetPrefix) {
        return $sql;
    }
    $pattern = '/`' . preg_quote($sourcePrefix, '/') . '([A-Za-z0-9_]+)`/';
    return (string) preg_replace($pattern, '`' . $targetPrefix . '$1`', $sql);
}

// 已安装：禁止访问安装页（不输出任何路径、账号等敏感信息）
if (is_file($installBlockFile)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow');
    echo '系统已安装，安装入口已关闭，如需重新安装请先删除 public\install\install.block 文件。';
    exit;
}

$error = '';
$ok = '';
$dbEnv = parseEnvFile($envFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (string) ($_POST['ajax'] ?? '') === '1';
    $ajaxAction = trim((string) ($_POST['action'] ?? 'install'));
    $dbHost = trim((string) ($_POST['db_host'] ?? ''));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $dbCharset = trim((string) ($_POST['db_charset'] ?? 'utf8'));
    $dbPrefix = normalizeDbPrefix((string) ($_POST['db_prefix'] ?? ((string) ($dbEnv['DB_PREFIX'] ?? 'ad_'))));

    $confirm = (string) ($_POST['confirm'] ?? '');

    if ($isAjax && $ajaxAction === 'check_db') {
        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '请先填写数据库地址、库名、账号'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
        if ($mysqli === false || $mysqli->connect_errno) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '数据库连接失败：' . (string) ($mysqli ? $mysqli->connect_error : '未知错误')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($dbCharset !== '') {
            @$mysqli->set_charset($dbCharset);
        }
        $safeDbName = str_replace('`', '', $dbName);
        $safeCharset = $dbCharset !== '' ? $dbCharset : 'utf8';
        @$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET {$safeCharset}");
        $okSelect = @$mysqli->select_db($dbName);
        @$mysqli->close();

        header('Content-Type: application/json; charset=utf-8');
        if (!$okSelect) {
            echo json_encode(['code' => 0, 'msg' => '数据库可连接，但无法选择库，请检查权限'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['code' => 1, 'msg' => '数据库检测通过'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($confirm !== 'I_CONFIRM_INSTALL') {
        $error = '请勾选确认后再进行安装。';
    } elseif ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $error = '请填写必填项：数据库主机/库名/账号。';
    } elseif (!is_file($sqlFile)) {
        $error = '找不到 SQL 初始文件：data/adlw.sql';
    } else {
        // 1) 写入 .env（给 config/database.php 的 env() 使用）
        $okParts = [];
        $envKv = [
            'DB_TYPE' => 'mysql',
            'DB_HOST' => $dbHost,
            'DB_PORT' => (string) $dbPort,
            'DB_NAME' => $dbName,
            'DB_USER' => $dbUser,
            'DB_PASS' => $dbPass,
            'DB_CHARSET' => $dbCharset,
            'DB_PREFIX' => $dbPrefix,
        ];
        if (!upsertEnv($envFile, $envKv)) {
            $error = '写入 `.env` 失败，请检查文件权限。';
        } else {
            $okParts[] = '写入 .env';

            // 2) 写入 config/site.php（站点安全码，自动生成随机值：用户登录令牌的站点级加密盐）
            $authcode = bin2hex(random_bytes(16));
            if (!writeSiteAuthcode($siteCfgFile, $authcode)) {
                $error = '写入 `config/site.php` 失败，请检查文件权限。';
            } else {
                $okParts[] = '写入 config/site.php';
            }
        }

        // 3) 导入 data/adlw.sql
        if ($error === '') {
            $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
            if ($mysqli === false || $mysqli->connect_errno) {
                $error = '数据库连接失败：' . (string) ($mysqli ? $mysqli->connect_error : '未知错误');
            } else {
                // 设置连接字符集（只影响当前会话）
                if ($dbCharset !== '') {
                    $mysqli->set_charset($dbCharset);
                }

                // 尝试创建数据库（如果账号权限不足会失败并进入错误）
                $safeDbName = str_replace('`', '', $dbName);
                $safeCharset = $dbCharset !== '' ? $dbCharset : 'utf8';
                $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET {$safeCharset}";
                $mysqli->query($createDbSql);

                if (!$mysqli->select_db($dbName)) {
                    $error = '选择数据库失败：' . $mysqli->error;
                } else {
                    $mysqli->query("SET NAMES {$safeCharset}");

                    $sql = (string) @file_get_contents($sqlFile);
                    if ($sql === '') {
                        $error = 'SQL 文件读取为空';
                    } else {
                        $sql = rewriteInstallSqlTablePrefix($sql, 'ad_', $dbPrefix);
                        $statements = splitSqlStatements($sql);
                        $executed = 0;
                        $errors = [];

                        foreach ($statements as $stmt) {
                            $stmt = trim((string) $stmt);
                            // 兼容部分 SQL 文件中被拆分出的空片段（如单独 ";"）
                            if ($stmt === '' || $stmt === ';') {
                                continue;
                            }
                            // 跳过 DELIMITER 指令（mysqli::query 不支持）
                            if (preg_match('/^\s*DELIMITER\s+/i', $stmt) === 1) {
                                continue;
                            }

                            // 移除末尾分号，避免仅剩分号时触发 "Query was empty"
                            $stmtToExec = rtrim($stmt, " \t\n\r\0\x0B;");
                            if ($stmtToExec === '') {
                                continue;
                            }

                            $res = @$mysqli->query($stmtToExec);
                            if ($res === false) {
                                $errMsg = (string) $mysqli->error;
                                $errNo = (int) $mysqli->errno;
                                if (isAlreadyExistsError($errMsg, $stmtToExec, $errNo)) {
                                    continue;
                                }
                                $errors[] = [
                                    'error' => $errMsg,
                                    'sql' => substr(trim($stmtToExec), 0, 120) . '...'
                                ];
                                break;
                            }
                            $executed++;
                        }

                        if (!empty($errors)) {
                            $error = '导入 SQL 失败：' . $errors[0]['error'];
                        } else {
                            // 写入安装完成标记（按你的要求：public/install/install.block）
                            $installDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install';
                            if (!is_dir($installDir)) {
                                @mkdir($installDir, 0755, true);
                            }
                            @file_put_contents($installBlockFile, date('c') . PHP_EOL);

                            $ok = '安装完成，成功执行 SQL 语句数：' . (string) $executed;
                            if (!$isAjax) {
                                httpRedirect('/');
                            }
                        }
                    }
                }
                @$mysqli->close();
            }
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        if ($error !== '') {
            echo json_encode([
                'code' => 0,
                'msg' => $error,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'code' => 1,
            'msg' => $ok !== '' ? $ok : '安装已完成',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$phpVersion = PHP_VERSION;
$phpVersionOk = (bool) preg_match('/^8\.0\./', $phpVersion);
$fileinfoLoaded = extension_loaded('fileinfo');
$envStep1Ok = $phpVersionOk && $fileinfoLoaded;
// 安装向导：环境检测（用于页面展示；扩展与版本同属 PHP，单独分组避免与 PHP 并列误解）
$phpEnvChecks = [
    [
        'label' => 'PHP 版本',
        'ok' => $phpVersionOk,
        'desc' => '须为 PHP 8.0.x（当前：' . $phpVersion . '）',
        'kind' => 'version',
    ],
    [
        'label' => 'fileinfo',
        'ok' => $fileinfoLoaded,
        'desc' => '当前 PHP 的扩展，用于上传与文件 MIME 等',
        'kind' => 'ext',
    ],
];
$extOkBadge = '<span class="layui-badge" style="background:#52C41A;"><i class="layui-icon layui-icon-ok-circle"></i> 已就绪</span>';
$extNoBadge = '<span class="layui-badge" style="background:#ff4d4f;"><i class="layui-icon layui-icon-close-fill"></i> 未就绪</span>';

?>
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI写作助手 - 首次部署安装向导</title>
    <link rel="stylesheet" href="/static/libs/layui/css/layui.css"/>
    <style>
        :root{
            --primary:#2d8cf0;
            --success:#52C41A;
            --danger:#ff4d4f;
            --bg1:#eaf3ff;
            --bg2:#f7f9fc;
            --card-border:#e8eef7;
            --text:#1f2d3d;
            --muted:#6b7a90;
        }
        body{
            font-family:"Segoe UI",Tahoma,Arial,sans-serif;
            background:linear-gradient(135deg,var(--bg1),var(--bg2) 55%, #fff);
            color:var(--text);
        }
        .install-topbar{
            background:linear-gradient(90deg,#2d8cf0,#19a7ff);
            height:72px;
            display:flex;
            align-items:center;
            box-shadow:0 14px 30px rgba(45,140,240,.25);
            border-radius:0 0 18px 18px;
        }
        .install-topbar .inner{
            width:100%;
            max-width:980px;
            margin:0 auto;
            padding:0 16px;
        }
        .install-title{
            display:flex;
            align-items:baseline;
            gap:12px;
            color:#fff;
        }
        .install-title span:first-child{
            font-size:26px;
            font-weight:900;
            letter-spacing:.4px;
        }
        .install-title span:last-child{
            font-size:13px;
            opacity:.95;
            font-weight:600;
        }
        .install-container{
            max-width:980px;
            margin:18px auto 40px;
            padding:0 16px;
        }
        .install-card{
            background:rgba(255,255,255,.95);
            border:1px solid var(--card-border);
            border-radius:16px;
            box-shadow:0 10px 30px rgba(16,24,40,.06);
            overflow:hidden;
        }
        .install-card-header{
            padding:18px 20px;
            border-bottom:1px solid var(--card-border);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .install-card-header h2{
            margin:0;
            font-size:18px;
            font-weight:900;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .install-card-body{ padding:20px; }
        .install-stepbar{
            display:flex;
            gap:14px;
            margin-bottom:14px;
            flex-wrap:wrap;
        }
        .install-step{
            flex:1;
            min-width:220px;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .install-step-circle{
            width:34px;height:34px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-weight:900;
            background:#e8f3ff;
            color:#1b6fd6;
        }
        .install-step.active .install-step-circle{
            background:var(--primary);
            color:#fff;
        }
        .install-step .install-step-label{
            font-size:14px;
            font-weight:800;
            color:#516173;
        }
        .install-step.active .install-step-label{ color:#1a2b3a; }
        .ext-card{
            border:1px solid var(--card-border);
            border-radius:14px;
            padding:14px 14px 10px;
            background:#fff;
        }
        .ext-title{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom:10px;
        }
        .ext-title h3{
            margin:0;
            font-size:15px;
            font-weight:900;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .section-card{
            border:1px solid var(--card-border);
            border-radius:14px;
            background:#fff;
            margin-top:14px;
            overflow:hidden;
        }
        .section-header{
            padding:12px 14px;
            background:linear-gradient(90deg, rgba(45,140,240,.08), rgba(25,167,255,.05));
            border-bottom:1px solid var(--card-border);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .section-header .section-title{
            margin:0;
            font-size:15px;
            font-weight:900;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .section-body{
            padding:12px 14px 6px;
        }
        .hint-line{
            color:var(--muted);
            font-size:13px;
            line-height:1.6;
            margin:12px 0 0;
        }
        .env-php-tip{
            background:rgba(45,140,240,.06);
            border:1px solid rgba(45,140,240,.2);
            border-radius:12px;
            padding:12px 14px;
            margin-bottom:12px;
            font-size:13px;
            line-height:1.65;
            color:var(--text);
        }
        .env-php-tip p{ margin:0 0 8px; }
        .env-php-tip p:last-child{ margin-bottom:0; }
        .env-group-head td{
            background:linear-gradient(90deg, rgba(45,140,240,.1), rgba(25,167,255,.04));
            font-weight:800;
            font-size:13px;
            color:#1a2b3a;
            padding:10px 12px !important;
            border-bottom:1px solid var(--card-border);
            vertical-align:top;
        }
        .env-group-sub{
            display:block;
            font-weight:600;
            color:var(--muted);
            font-size:12px;
            margin-top:6px;
            line-height:1.55;
        }
        tr.env-ext-row td:first-child{
            padding-left:28px !important;
            position:relative;
        }
        tr.env-ext-row td:first-child::before{
            content:'└';
            position:absolute;
            left:12px;
            color:var(--muted);
            font-family:Consolas,monospace;
            font-size:12px;
        }
        .env-ext-badge{
            display:inline-block;
            margin-left:6px;
            font-size:11px;
            font-weight:700;
            color:var(--muted);
            vertical-align:1px;
        }
        .layui-form-label{ width: 140px; }
        .form-actions{
            display:flex;
            gap:12px;
            justify-content:flex-end;
            align-items:center;
            flex-wrap:wrap;
            margin-top:16px;
        }
        .result-panel{
            border:1px solid var(--card-border);
            border-radius:14px;
            background:#fff;
            padding:20px;
            text-align:center;
            margin-top:14px;
        }
        .result-panel .icon{
            font-size:52px;
            margin-bottom:10px;
            display:block;
        }
        .guide-btn{
            border-radius:8px;
            font-weight:700;
            box-shadow:0 8px 18px rgba(255,77,79,.2);
        }
    </style>
</head>
<body>
    <div class="install-topbar">
        <div class="inner">
            <div class="install-title">
                <span>AI写作助手 在线安装</span>
                <span>首次部署安装向导</span>
            </div>
        </div>
    </div>

    <div class="install-container">
        <div class="install-card">
            <div class="install-card-header">
                <h2>
                    <i class="layui-icon layui-icon-survey" style="color:var(--primary);font-size:18px;"></i>
                    安装配置
                </h2>
                <div class="layui-text" style="color:var(--muted);font-size:13px;">
                    页面只做展示与交互；安装执行仍以后端为准
                </div>
            </div>

            <div class="install-card-body">
                <div class="install-stepbar">
                    <div class="install-step active" id="step-indicator-1">
                        <div class="install-step-circle">1</div>
                        <div class="install-step-label">环境检测</div>
                    </div>
                    <div class="install-step" id="step-indicator-2">
                        <div class="install-step-circle">2</div>
                        <div class="install-step-label">填写配置</div>
                    </div>
                    <div class="install-step" id="step-indicator-3">
                        <div class="install-step-circle">3</div>
                        <div class="install-step-label">安装结果</div>
                    </div>
                </div>

                <div class="ext-card" id="step-panel-1">
                    <div class="ext-title">
                        <h3>
                            <i class="layui-icon layui-icon-read" style="color:var(--primary);"></i>
                            环境检测
                        </h3>
                    </div>
                    <div class="env-php-tip">
                        <p><strong>说明：</strong>fileinfo 是<strong>当前 PHP 已加载的扩展</strong>，与 PHP 版本一起在「PHP 管理」里配置即可，不要理解成与 PHP 平级的独立软件。</p>
                        <p><strong>宝塔面板：</strong><strong>软件商店</strong> → <strong>已安装</strong> → 找到 <strong>PHP 8.0</strong> → <strong>设置</strong> → <strong>安装扩展</strong>，勾选 <code>fileinfo</code> 安装完成后，在同一 PHP 管理界面点击<strong>重启</strong> PHP，再回到本页刷新。</p>
                        <p style="color:var(--muted);font-size:12px;">非宝塔（自行安装 PHP）：编辑当前站点使用的 <code>php.ini</code> 启用对应扩展，修改后重启 PHP-FPM / CGI / 内置 Web 服务。</p>
                    </div>
                    <table class="layui-table" lay-even lay-skin="nob" style="margin-bottom:0;">
                        <colgroup>
                            <col width="180">
                            <col width="220">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>检测项</th>
                                <th>当前状态</th>
                                <th>说明</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr class="env-group-head">
                            <td colspan="3">
                                一、PHP 运行环境
                                <span class="env-group-sub">下列版本与扩展属于同一套 PHP；扩展行在结构上从属于「PHP 版本」，并非并列的第四种「组件」。</span>
                            </td>
                        </tr>
                        <?php foreach ($phpEnvChecks as $row): ?>
                            <tr class="<?php echo ($row['kind'] ?? '') === 'ext' ? 'env-ext-row' : ''; ?>">
                                <td>
                                    <?php echo htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (($row['kind'] ?? '') === 'ext'): ?>
                                        <span class="env-ext-badge">（PHP 扩展）</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['ok'] ? $extOkBadge : $extNoBadge; ?></td>
                                <td><?php echo htmlspecialchars((string) $row['desc'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="hint-line">
                        <?php if (!$phpVersionOk): ?>
                            <div style="color:var(--danger);font-weight:700;margin-bottom:10px;">
                                当前 PHP 版本为 <?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?>，请切换到 PHP 8.0 后再继续安装。
                            </div>
                        <?php endif; ?>
                        <?php if (!$fileinfoLoaded): ?>
                            <?php
                            $missingPhpExt = array_filter([
                                !$fileinfoLoaded ? 'fileinfo' : null,
                            ]);
                            $missingPhpExtStr = implode('、', $missingPhpExt);
                            ?>
                            <div style="color:var(--danger);font-weight:700;margin-bottom:10px;">
                                未检测到必需的 PHP 扩展：<?php echo htmlspecialchars($missingPhpExtStr, ENT_QUOTES, 'UTF-8'); ?>。请按本页上方<strong>宝塔面板</strong>或<strong>非宝塔</strong>说明安装后<strong>重启 PHP</strong>，再刷新本页。
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions" style="margin-top:14px;">
                        <button type="button" id="btn-to-step2" class="layui-btn" <?php echo $envStep1Ok ? '' : 'disabled'; ?>>下一步：填写配置</button>
                    </div>
                </div>

                <div style="margin:16px 0;">
                    <?php if ($error !== ''): ?>
                        <div class="layui-alert layui-alert-danger" style="border-radius:12px;">
                            <div><b>错误：</b><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($ok !== ''): ?>
                        <div class="layui-alert layui-alert-success" style="border-radius:12px;">
                            <div><b>提示：</b><?php echo htmlspecialchars($ok); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <form class="layui-form" method="post" id="install-form" style="display:none;">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="action" value="install">
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="layui-icon layui-icon-template-1" style="color:var(--primary);"></i>
                                数据库参数
                            </div>
                            <div class="layui-text" style="color:var(--muted);font-size:13px;">用于导入初始化 SQL</div>
                        </div>
                        <div class="section-body">
                            <div class="layui-row layui-col-space16">
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">数据库地址</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：localhost" name="db_host" type="text"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_HOST'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input" lay-verify="required" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">数据库端口</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：3306" name="db_port" type="number"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_PORT'] ?? '3306'), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-row layui-col-space16">
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">数据库名称</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：adlw" name="db_name" type="text"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_NAME'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input" lay-verify="required" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">数据库账号</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：root" name="db_user" type="text"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_USER'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input" lay-verify="required" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-row layui-col-space16">
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">数据库密码</label>
                                        <div class="layui-input-block">
                                            <input placeholder="请输入数据库密码" name="db_pass" type="password"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_PASS'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input">
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">字符集</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：utf8" name="db_charset" type="text"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_CHARSET'] ?? 'utf8'), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input">
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">表前缀</label>
                                        <div class="layui-input-block">
                                            <input placeholder="例如：ad_" name="db_prefix" type="text"
                                                   value="<?php echo htmlspecialchars((string) ($dbEnv['DB_PREFIX'] ?? 'ad_'), ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="layui-input" pattern="[A-Za-z0-9_]+" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="layui-icon layui-icon-ok-circle" style="color:var(--primary);"></i>
                                确认安装
                            </div>
                            <div class="layui-text" style="color:var(--muted);font-size:13px;">不可逆的初始化操作</div>
                        </div>
                        <div class="section-body">
                            <div class="layui-form-item" style="margin-bottom:0;">
                                <label class="layui-form-label" style="line-height:20px;">确认安装</label>
                                <div class="layui-input-block">
                                    <div class="hint-line" style="margin:0 0 10px;">
                                        将进行数据库初始化，请确保数据库账号权限足够。
                                    </div>
                                    <input type="checkbox" name="confirm" value="I_CONFIRM_INSTALL" title="我已确认要执行首次安装" lay-skin="primary" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="layui-btn layui-btn-primary" type="button" id="btn-back-step1">上一步</button>
                        <button class="layui-btn layui-btn-fluid" id="btn-start-install" type="submit" style="max-width:320px;">
                            下一步：开始安装
                        </button>
                    </div>
                </form>

                <div id="step-panel-3" class="result-panel" style="display:none;">
                    <i id="result-icon" class="layui-icon icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="color:var(--primary);"></i>
                    <h3 id="result-title" style="margin:0 0 8px;">正在安装中...</h3>
                    <div id="result-msg" class="hint-line" style="margin:0;">请稍候，正在写入配置并初始化数据。</div>
                    <div id="result-admin-creds" class="hint-line" style="display:none;margin:14px 0 0;text-align:center;line-height:1.8;">
                        <div style="display:inline-block;text-align:left;padding:12px 16px;background:#f6f8fc;border-radius:10px;border:1px solid var(--card-border);">
                            <strong>默认后台管理员</strong><br/>
                            账号：<code>admin</code>　密码：<code>123456</code><br/>
                            <span style="font-size:13px;color:var(--muted);">首次登录后请及时修改密码。</span>
                        </div>
                    </div>
                    <div class="form-actions" style="justify-content:center;margin-top:16px;">
                        <a href="/admin" id="result-admin-btn" class="layui-btn" style="display:none;">进入后台</a>
                        <a href="/" id="result-home-btn" class="layui-btn layui-btn-primary" style="display:none;">访问首页</a>
                        <button type="button" id="result-back-btn" class="layui-btn layui-btn-primary" style="display:none;">返回上一步</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="max-width:980px;margin:18px auto 0;color:var(--muted);font-size:13px;line-height:1.8;">
            <div style="padding:0 2px;">
                安装说明：在线安装是为用户提供便利的安装方式。
                如果遇到问题，可联系系统技术支持协助解决。
            </div>
        </div>
    </div>

    <script type="text/javascript" src="/static/libs/layui/layui.js"></script>
    <script>
        layui.use(['form', 'layer'], function () {
            var form = layui.form;
            var layer = layui.layer;

            var step1 = document.getElementById('step-panel-1');
            var step2 = document.getElementById('install-form');
            var step3 = document.getElementById('step-panel-3');
            var indicators = [
                document.getElementById('step-indicator-1'),
                document.getElementById('step-indicator-2'),
                document.getElementById('step-indicator-3')
            ];

            var btnToStep2 = document.getElementById('btn-to-step2');
            var btnBackStep1 = document.getElementById('btn-back-step1');
            var formEl = document.getElementById('install-form');
            var resultTitle = document.getElementById('result-title');
            var resultMsg = document.getElementById('result-msg');
            var resultIcon = document.getElementById('result-icon');
            var resultAdminBtn = document.getElementById('result-admin-btn');
            var resultAdminCreds = document.getElementById('result-admin-creds');
            var resultHomeBtn = document.getElementById('result-home-btn');
            var resultBackBtn = document.getElementById('result-back-btn');

            function switchStep(step) {
                step1.style.display = step === 1 ? 'block' : 'none';
                step2.style.display = step === 2 ? 'block' : 'none';
                step3.style.display = step === 3 ? 'block' : 'none';
                indicators.forEach(function (item, index) {
                    if (!item) return;
                    item.classList.toggle('active', index + 1 === step);
                });
            }

            function setInstalling() {
                resultTitle.textContent = '正在安装中...';
                resultMsg.textContent = '请稍候，正在写入配置并初始化数据。';
                resultIcon.className = 'layui-icon icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop';
                resultIcon.style.color = 'var(--primary)';
                resultAdminBtn.style.display = 'none';
                if (resultAdminCreds) resultAdminCreds.style.display = 'none';
                resultHomeBtn.style.display = 'none';
                resultBackBtn.style.display = 'none';
            }

            function postCheck(action) {
                var fd = new FormData(formEl);
                fd.set('ajax', '1');
                fd.set('action', action);
                return fetch(window.location.pathname, { method: 'POST', body: fd })
                    .then(function (res) { return res.json(); });
            }

            btnToStep2.addEventListener('click', function () {
                switchStep(2);
            });
            btnBackStep1.addEventListener('click', function () {
                switchStep(1);
            });
            resultBackBtn.addEventListener('click', function () {
                switchStep(2);
            });

            formEl.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!formEl.checkValidity()) {
                    formEl.reportValidity();
                    return;
                }
                layer.msg('正在校验数据库配置...', {icon: 16, time: 1200});

                postCheck('check_db')
                    .then(function (data) {
                        if (Number(data.code) !== 1) throw new Error('数据库配置错误：' + (data.msg || '请检查后重试'));

                        setInstalling();
                        switchStep(3);

                        var formData = new FormData(formEl);
                        formData.set('action', 'install');
                        return fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        }).then(function (res) { return res.json(); });
                    })
                    .then(function (data) {
                        if (Number(data.code) === 1) {
                            resultTitle.textContent = '安装成功';
                            resultMsg.textContent = data.msg || '安装已完成';
                            resultIcon.className = 'layui-icon icon layui-icon-ok-circle';
                            resultIcon.style.color = '#52C41A';
                            if (resultAdminCreds) resultAdminCreds.style.display = 'block';
                            resultAdminBtn.style.display = 'inline-block';
                            resultHomeBtn.style.display = 'inline-block';
                        } else {
                            throw new Error(data.msg || '安装失败，请检查配置后重试。');
                        }
                    })
                    .catch(function (err) {
                        // 校验阶段失败：停留在第2步给出错误
                        if (step2.style.display !== 'none') {
                            layer.msg(err.message || '配置错误，请检查后重试', {icon: 5, time: 2600});
                            return;
                        }
                        // 安装阶段失败：显示结果页错误
                        resultTitle.textContent = '安装失败';
                        resultMsg.textContent = err.message || '请求异常，请检查网络或 PHP 运行日志。';
                        resultIcon.className = 'layui-icon icon layui-icon-close-fill';
                        resultIcon.style.color = '#ff4d4f';
                        resultBackBtn.style.display = 'inline-block';
                        layer.msg(resultMsg.textContent, {icon: 5, time: 2600});
                    });
            });

            form.render();
            switchStep(1);
        });
    </script>
</body>
</html>