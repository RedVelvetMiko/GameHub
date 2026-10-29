<?php
// 管理后台仪表盘接口：校验管理员身份后，返回各类统计汇总与最近帖子

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();

// ---------- 权限校验 ----------
// 解析 token 获取用户 ID，无效则返回 401
$userId = get_user_id_from_token($pdo);
if (!$userId) {
    error('未登录或 token 无效', 401);
}

// 查询用户角色，非管理员则返回 403
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    error('无权限访问', 403);
}

// ---------- 统计查询 ----------
// 用户总数
$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
// 帖子总数
$total_posts   = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
// 今日发帖数：按创建日期与当天对比
$today_posts   = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
// 回复总数
$total_replies = $pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();

// 全站点赞总数与浏览总数：用 COALESCE 处理空表时的 NULL，保证返回 0
$total_likes = $pdo->query("SELECT COALESCE(SUM(likes_count), 0) FROM posts")->fetchColumn();
$total_views = $pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();

// 最近 10 条帖子：联表取作者名，按发布时间倒序
$stmt = $pdo->prepare("
    SELECT p.id, p.title, u.username AS author, p.created_at
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_posts = $stmt->fetchAll();

// 统一返回统计结果，计数字段转为整型
success([
    'total_users'   => (int)$total_users,
    'total_posts'   => (int)$total_posts,
    'today_posts'   => (int)$today_posts,
    'total_replies' => (int)$total_replies,
    'total_likes'   => (int)$total_likes,
    'total_views'   => (int)$total_views,
    'recent_posts'  => $recent_posts
]);