<?php
// 管理后台用户列表接口：校验管理员身份后，返回分页用户列表，支持按用户名搜索

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
// 页码最小为 1；每页条数限制在 1-100 之间，默认 20
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

// ---------- 搜索条件 ----------
// 仅按用户名模糊搜索；无关键词时不加 WHERE 条件
$keyword = $_GET['keyword'] ?? '';
$where = '';
$params = [];
if ($keyword) {
    $where = "WHERE username LIKE :keyword";
    $params[':keyword'] = "%{$keyword}%";
}

// ---------- 查询总数 ----------
// 与列表使用相同筛选条件，保证分页总数准确
$countSql = "SELECT COUNT(*) FROM users {$where}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// ---------- 查询列表 ----------
// 只返回管理页需要的字段（不含 email、status），按注册时间倒序分页
$sql = "SELECT id, username, avatar, signature, role, created_at 
        FROM users {$where} 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
// 先绑定搜索参数（字符串），再绑定分页参数（整型，需指定 PDO::PARAM_INT）
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 返回分页结构与总条数
success([
    'list' => $users,
    'total' => intval($total),
    'page' => $page,
    'limit' => $limit
]);