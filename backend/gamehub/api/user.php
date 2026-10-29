<?php
/**
 * 用户接口
 * GET  /api/user?id=1   获取指定用户信息
 * POST /api/user        更新用户信息（签名、头像等）
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET：获取用户信息 ====================
if ($method === 'GET') {
    // 用户 ID 来自 query 参数，必须为正整数
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$userId) {
        error('缺少 id 参数');
    }

    // 查询用户公开信息，明确排除 password 字段
    $stmt = $pdo->prepare('SELECT id, username, avatar, signature, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    // 用户不存在时返回 404
    if (!$user) {
        error('用户不存在', 404);
    }

    // 字段类型规范化：id 转为整型
    $user['id'] = (int)$user['id'];
    success($user, '获取用户信息成功');
}

// ==================== POST：更新用户信息 ====================
if ($method === 'POST') {
    // 解析 JSON 请求体
    $input = json_decode(file_get_contents('php://input'), true);

    // 目标用户 ID 及待更新字段；字段为空字符串视为「不更新」
    $userId = (int)($input['id'] ?? 0);
    $signature = trim($input['signature'] ?? '');
    $avatar = trim($input['avatar'] ?? '');

    if (!$userId) {
        error('缺少用户 ID');
    }

    // 按传入的非空字段动态拼接 SET 子句，实现部分更新
    $updates = [];
    $params = [':id' => $userId];

    if ($signature !== '') {
        $updates[] = 'signature = :signature';
        $params[':signature'] = $signature;
    }
    if ($avatar !== '') {
        $updates[] = 'avatar = :avatar';
        $params[':avatar'] = $avatar;
    }

    // 所有字段都为空时无需执行更新
    if (empty($updates)) {
        error('没有要更新的字段');
    }

    // 执行更新。注意：当前未校验请求者身份，任何人都可更新任意用户信息，
    // 正式环境应改为基于 token 校验并限制为本人或管理员操作
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    success([], '更新成功');
}