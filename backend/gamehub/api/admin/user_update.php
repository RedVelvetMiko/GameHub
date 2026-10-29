<?php
// 管理后台接口：修改指定用户的角色（user / admin）

require_once '../../config/database.php';
require_once '../../utils/auth.php';
require_once '../../utils/response.php';

$db = getDb();

// ---------- 权限校验 ----------
// 校验 token，未登录返回 401
$userId = get_user_id_from_token($db);
if ($userId <= 0) {
    error('请先登录', 401);
}

// 查询用户角色，非管理员返回 403
$stmt = $db->prepare('SELECT role FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    error('无权限', 403);
}

// ---------- 参数解析 ----------
// 目标用户 ID 来自 query 参数，必须为正整数
$targetUserId = intval($_GET['id'] ?? 0);
if ($targetUserId <= 0) {
    error('无效的用户ID');
}

$input = json_decode(file_get_contents('php://input'), true);

// 本接口只允许更新 role 字段，缺失则报错
if (!isset($input['role'])) {
    error('没有要更新的字段');
}

// 角色白名单校验，防止写入非法值
$allowedRoles = ['user', 'admin'];
if (!in_array($input['role'], $allowedRoles)) {
    error('无效的角色');
}

// ---------- 执行更新 ----------
$sql = "UPDATE users SET role = :role WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->bindValue(':role', $input['role']);
$stmt->bindValue(':id', $targetUserId, PDO::PARAM_INT);
$stmt->execute();

// 根据受影响行数判断是否真正发生变化（角色相同时 rowCount 为 0）
if ($stmt->rowCount() > 0) {
    success(null, '更新成功');
} else {
    success(null, '未作更改');
}