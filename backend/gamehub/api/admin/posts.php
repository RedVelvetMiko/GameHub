<?php
// 管理后台帖子列表接口：校验管理员身份后，返回分页帖子列表，支持按标题搜索

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

// ---------- 分页参数 ----------
// 页码最小为 1；每页条数限制在 1-100 之间，默认 15
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 15;
$offset = ($page - 1) * $limit;

// ---------- 搜索条件 ----------
// 按标题模糊搜索；无关键词时不加 WHERE 条件
$keyword = $_GET['keyword'] ?? '';
$where = '';
$params = [];
if ($keyword) {
    $where = "WHERE p.title LIKE :keyword";
    $params[':keyword'] = "%{$keyword}%";
}

// ---------- 查询总数 ----------
// 与列表使用相同筛选条件，保证分页总数准确
$countSql = "SELECT COUNT(*) FROM posts p {$where}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// ---------- 查询列表 ----------
// 联表取作者名与版块名，按发布时间倒序分页
$sql = "SELECT p.id, p.title, p.views, p.likes_count, p.is_top, p.status, p.created_at,
               u.username AS author_name, c.name AS category_name
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        {$where}
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
// 先绑定搜索参数（字符串），再绑定分页参数（整型，需指定 PDO::PARAM_INT）
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 返回分页结构与总条数
success([
    'list' => $posts,
    'total' => intval($total),
    'page' => $page,
    'limit' => $limit
]);