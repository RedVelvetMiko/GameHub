<?php
/**
 * 注册接口
 * POST /api/register
 * 请求体 JSON：{ "username": "...", "password": "..." }
 *
 * 流程：校验请求方法 → 解析参数 → 基础校验 → 用户名唯一性检查 → 写入用户表 → 返回新用户信息
 */

// ========== 1. 引入依赖 ==========

// 数据库连接（内部创建全局 PDO 对象 $pdo）
require_once __DIR__ . '/../config/database.php';

// 统一响应工具，提供 success() / error()
require_once __DIR__ . '/../utils/response.php';


// ========== 2. 校验请求方法 ==========

// 仅允许 POST，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('仅支持 POST 请求', 405);
}


// ========== 3. 解析请求参数 ==========

// php://input 读取原始请求体，json_decode 转为关联数组
$input = json_decode(file_get_contents('php://input'), true);

// 用户名去除首尾空白；密码不做 trim，避免误删用户有意输入的首尾空格
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';


// ========== 4. 基础参数校验 ==========

// 用户名或密码为空直接拒绝
if (empty($username) || empty($password)) {
    error('用户名和密码不能为空');
}

// 用户名长度限制：2-16 个字符
if (strlen($username) < 2 || strlen($username) > 16) {
    error('用户名长度为2~16个字符');
}

// 密码最小长度：6 位
if (strlen($password) < 6) {
    error('密码至少6位');
}


// ========== 5. 用户名唯一性检查 ==========

// 用预处理语句查询是否已存在同名用户，防止 SQL 注入
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);

// 查到记录说明用户名已被占用，返回 409 冲突
if ($stmt->fetch()) {
    error('用户名已存在', 409);
}


// ========== 6. 写入新用户 ==========

// 插入新用户（当前密码为明文存储，生产环境应改用 password_hash 加密）
$stmt = $pdo->prepare('INSERT INTO users (username, password, created_at) VALUES (:username, :password, NOW())');
$stmt->execute([
    ':username' => $username,
    ':password' => $password
]);

// 获取自增主键作为新用户 ID
$userId = $pdo->lastInsertId();


// ========== 7. 返回结果 ==========

// 返回新用户 ID（强制整型）与用户名
success([
    'id'       => (int)$userId,
    'username' => $username
], '注册成功');