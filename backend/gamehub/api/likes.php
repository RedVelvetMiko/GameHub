<?php
/**
 * 点赞接口
 * POST /api/likes  — 点赞 / 取消点赞（toggle 切换）
 * 请求体 JSON：{ "post_id": 123 }
 * user_id 从 token 解析，不接收前端传入
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

// 仅允许 POST 请求，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('仅支持 POST 请求', 405);
}

// 解析 JSON 请求体
$input = json_decode(file_get_contents('php://input'), true);

// 解析 token 获取用户 ID，未登录返回 401
$userId = get_user_id_from_token($pdo);
if (!$userId) {
    error('请先登录', 401);
}

// 帖子 ID 必须存在，且转为整型
$postId = (int)($input['post_id'] ?? 0);
if (!$postId) {
    error('参数不完整');
}

// 查询当前用户是否已对该帖点赞
$stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id LIMIT 1');
$stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
$existing = $stmt->fetch();

if ($existing) {
    // ---------- 已点赞：执行取消点赞 ----------
    // 删除点赞记录
    $deleteStmt = $pdo->prepare('DELETE FROM likes WHERE id = :id');
    $deleteStmt->execute([':id' => $existing['id']]);

    // 帖子点赞数减 1，用 GREATEST 兜底防止出现负数
    $pdo->prepare('UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = :id')
       ->execute([':id' => $postId]);

    $liked = false;
} else {
    // ---------- 未点赞：执行点赞 ----------
    // 插入点赞记录
    $insertStmt = $pdo->prepare('INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)');
    $insertStmt->execute([':user_id' => $userId, ':post_id' => $postId]);

    // 帖子点赞数加 1
    $pdo->prepare('UPDATE posts SET likes_count = likes_count + 1 WHERE id = :id')
       ->execute([':id' => $postId]);

    $liked = true;
}

// 从数据库重新读取最新点赞数，避免并发下返回过期计数
$countStmt = $pdo->prepare('SELECT likes_count FROM posts WHERE id = :id');
$countStmt->execute([':id' => $postId]);
$likesCount = (int)$countStmt->fetchColumn();

// 返回操作结果与最新点赞数
success([
    'liked' => $liked,
    'likes_count' => $likesCount
], $liked ? '点赞成功' : '取消点赞成功');