<?php
/**
 * 数据库连接
 * 连接参数从 database.local.php 读取（该文件不提交到 Git），
 * 仓库里只保留 database.example.php 模板，避免密码进入版本历史。
 * 使用 PDO + 原生预处理，防止 SQL 注入。
 */

// ---------- 1. 加载本地配置 ----------
// 真实密码只存在本地文件里；仓库中的 database.php 可安全提交
$localConfig = __DIR__ . '/database.local.php';

// 缺失本地配置时明确报错，而不是用默认值硬连——
// 避免别人 clone 后误以为"能连上"，也防止默认密码被写回代码
if (!file_exists($localConfig)) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'msg' => '缺少 config/database.local.php，请复制 database.example.php 并填写数据库信息']);
    exit;
}

// require 一个 return 数组的文件，直接得到配置数组
$cfg = require $localConfig;

// ---------- 2. 建立连接 ----------
// DSN 字符串：指定主机、端口、库名与字符集
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";

try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // 出错抛异常，便于统一捕获
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,             // 默认返回关联数组
        PDO::ATTR_EMULATE_PREPARES   => false,                        // 禁用模拟预处理，使用数据库原生预处理，更安全
    ]);
} catch (PDOException $e) {
    // 连接失败：返回 500 与统一提示
    // 注意：不把 $e->getMessage() 拼进响应，避免泄露主机、用户名等细节；
    // 详细错误应写日志（error_log($e->getMessage())），而不是回显给前端
    http_response_code(500);
    echo json_encode(['code' => 500, 'msg' => '数据库连接失败']);
    exit;
}

/**
 * 获取全局 PDO 实例
 * 供各接口文件通过 getDb() 取得同一个数据库连接，
 * 避免每个接口重复建立连接。
 */
function getDb(): PDO {
    global $pdo;
    return $pdo;
}