<?php
/**
 * 更新当前用户资料
 * PUT /api/user/profile
 * 请求体 JSON：{ "signature": "新签名" }
 * 需要在请求头中携带 Authorization: Bearer <token>
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// 仅允许 PUT 请求，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    error('仅支持 PUT 请求', 405);
}

// 解析 token 获取用户 ID，无效则返回 401
$userId = get_user_id_from_token($pdo);
if (!$userId) {
    error('请先登录', 401);
}

// 解析 JSON 请求体，取出签名并去除首尾空白
$input = json_decode(file_get_contents('php://input'), true);
$signature = trim($input['signature'] ?? '');

// 签名长度上限 255 字符，与数据库字段保持一致
if (mb_strlen($signature) > 255) {
    error('签名不能超过255个字符');
}

// 更新当前用户的签名
$stmt = $pdo->prepare('UPDATE users SET signature = :signature WHERE id = :id');
$stmt->execute([
    ':signature' => $signature,
    ':id' => $userId
]);

// 重新查询用户信息（排除 password），返回最新数据供前端同步
$stmt = $pdo->prepare('SELECT id, username, avatar, signature, role, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// 字段类型规范化：id 转为整型
$user['id'] = (int)$user['id'];

// 返回更新后的用户信息
success($user, '资料更新成功');