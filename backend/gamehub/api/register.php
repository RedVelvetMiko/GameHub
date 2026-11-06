<?php
/**
 * 注册接口
 * POST /api/register
 * 请求体 JSON：{ "username": "...", "password": "..." }
 *
 * 流程：校验请求方法 → 解析参数 → 类型保护 → 基础校验 → 用户名唯一性检查 → 写入用户表 → 返回新用户信息
 */

// ========== 1. 引入依赖 ==========

// 数据库连接（内部创建全局 PDO 对象 $pdo）
require_once __DIR__ . '/../config/database.php';

// 统一响应工具，提供 success() / error()
require_once __DIR__ . '/../utils/response.php';

// 校验工具，与前端 utils/validators.js 同一套规则
require_once __DIR__ . '/../utils/validate.php';


// ========== 2. 校验请求方法 ==========

// 仅允许 POST，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('仅支持 POST 请求', 405);
}


// ========== 3. 解析请求参数 ==========

// php://input 读取原始请求体，json_decode 转为关联数组
$input = json_decode(file_get_contents('php://input'), true);

// 类型保护：确保请求体是对象，且 username / password 都是字符串
// 避免 trim(array) 在 PHP 8 抛 TypeError 导致 500
if (!is_array($input)
    || !isset($input['username']) || !is_string($input['username'])
    || !isset($input['password']) || !is_string($input['password'])) {
    error('请求参数格式错误');
}

// 用户名去除首尾空白；密码不做 trim，避免误删用户有意输入的首尾空格
$username = trim($input['username']);
$password = $input['password'];


// ========== 4. 基础参数校验 ==========

// 用户名或密码为空直接拒绝（用 === '' 而非 empty，避免 "0" 被误判）
if ($username === '' || $password === '') {
    error('用户名和密码不能为空');
}

// 用户名规则：2-16 位中文、字母、数字、下划线、连字符（按字符算，与前端一致）
if (!isValidUsername($username)) {
    error('用户名需为 2-16 位中文、字母、数字、下划线或连字符');
}

// 密码规则：6-30 位非空白字符（任何位置都不允许空格）
if (!isValidPassword($password)) {
    error('密码需为 6-30 位，且不能包含空格');
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