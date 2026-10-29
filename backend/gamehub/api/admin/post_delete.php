<?php
// 管理员操作帖子的接口：支持「置顶/取消置顶」与「删除/恢复」两种操作

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
// 帖子 ID 来自 query 参数，必须为正整数
$postId = intval($_GET['id'] ?? 0);
if ($postId <= 0) {
    error('无效的帖子ID');
}

// 请求体为 JSON，根据字段区分具体操作
$input = json_decode(file_get_contents('php://input'), true);

// ---------- 分支一：置顶 / 取消置顶 ----------
// 请求体含 is_top 时走置顶逻辑，1 置顶、0 取消置顶
if (isset($input['is_top'])) {
    $newTop = intval($input['is_top']);
    if (!in_array($newTop, [0, 1])) {
        error('无效的置顶值');
    }
    $sql = "UPDATE posts SET is_top = :is_top WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':is_top', $newTop, PDO::PARAM_INT);
    $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
    $stmt->execute();
    success(null, $newTop == 1 ? '已置顶' : '已取消置顶');
}

// ---------- 分支二：删除 / 恢复 ----------
// 请求体含 status 时走状态变更逻辑：0 表示删除，1 表示恢复
$newStatus = isset($input['status']) ? intval($input['status']) : 0;
if (!in_array($newStatus, [0, 1])) {
    error('无效的状态值');
}

$sql = "UPDATE posts SET status = :status WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->bindValue(':status', $newStatus, PDO::PARAM_INT);
$stmt->bindValue(':id', $postId, PDO::PARAM_INT);
$stmt->execute();

// 根据受影响行数判断状态是否真正发生变化
if ($stmt->rowCount() > 0) {
    success(null, $newStatus == 0 ? '已删除' : '已恢复');
} else {
    success(null, '未作更改');
}