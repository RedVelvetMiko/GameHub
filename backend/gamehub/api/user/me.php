<?php
/**
 * 获取当前登录用户信息
 * GET /api/user/me
 * 需要在请求头中携带 Authorization: Bearer <token>
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// 仅允许 GET 请求，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('仅支持 GET 请求', 405);
}

// 解析 token 获取用户 ID，无效则返回 401
$userId = get_user_id_from_token($pdo);
if (!$userId) {
    error('请先登录', 401);
}

// 查询用户信息，明确排除 password 字段，避免敏感信息泄露
$stmt = $pdo->prepare('SELECT id, username, avatar, signature, role, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// token 有效但用户已被删除时返回 404
if (!$user) {
    error('用户不存在', 404);
}

// 字段类型规范化：id 转为整型，created_at 缺失时置为空字符串
$user['id'] = (int)$user['id'];
$user['created_at'] = $user['created_at'] ?? '';

// 返回用户信息
success($user, '获取用户信息成功');