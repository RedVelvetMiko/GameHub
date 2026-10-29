<?php
/**
 * 登录接口
 * POST /api/login
 * 请求体 JSON：{ "username": "...", "password": "..." }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

// 仅允许 POST 请求，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('仅支持 POST 请求', 405);
}

// 解析 JSON 请求体：用户名去除首尾空白，密码保持原样
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// 基础参数校验：任一为空即拒绝
if (empty($username) || empty($password)) {
    error('用户名和密码不能为空');
}

// 按用户名查询用户（含 password 字段用于比对）
$stmt = $pdo->prepare('SELECT id, username, password, avatar, signature, role, created_at FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

// 用户不存在时同样返回「用户名或密码错误」，避免泄露账号是否存在
if (!$user) {
    error('用户名或密码错误', 401);
}

// 密码比对（当前为明文对比，后续可改为 password_hash / password_verify）
if ($password !== $user['password']) {
    error('用户名或密码错误', 401);
}

// 生成简易 token（生产环境建议改用 JWT）
$token = md5($user['id'] . $user['username'] . time());

// 将 token 写入 users 表：
// 后续接口通过 get_user_id_from_token() 用请求头中的 token 反查用户身份
$pdo->prepare('UPDATE users SET token = :token WHERE id = :id')
    ->execute([':token' => $token, ':id' => $user['id']]);

// 返回 token 与用户信息（不含 password），created_at 缺失时置空
success([
    'token' => $token,
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'avatar' => $user['avatar'],
        'signature' => $user['signature'],
        'role' => $user['role'],
        'created_at' => $user['created_at']
    ]
], '登录成功');