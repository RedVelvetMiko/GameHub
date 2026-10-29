<?php
/**
 * 回复接口
 * GET  /api/replies?post_id=1&page=1&limit=10   某帖子的回复列表（按时间正序，用于帖子详情页）
 * GET  /api/replies?user_id=9&page=1&limit=100  某用户的回复列表（按时间倒序，用于用户中心）
 * POST /api/replies                             发表回复（user_id 从 token 解析）
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

// 临时调试日志：记录请求头中的 Authorization，便于排查 token 未收到的问题
file_put_contents(
    __DIR__ . '/debug.log',
    date('H:i:s') . ' AUTH=' . ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '【还是没收到】')) . "\n",
    FILE_APPEND
);

$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET：获取回复列表 ====================
if ($method === 'GET') {
    // 过滤条件与分页参数
    $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $userIdFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $page = max(1, (int)($_GET['page'] ?? 1));
    // 每页条数上限放宽到 100，供用户中心一次性拉取
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // post_id 与 user_id 至少传一个，否则无法定位数据
    if (!$postId && !$userIdFilter) {
        error('缺少 post_id 或 user_id 参数', 400);
    }

    // 动态拼接 WHERE 条件与参数
    $where = [];
    $params = [];

    if ($postId) {
        $where[] = 'r.post_id = :post_id';
        $params[':post_id'] = $postId;
    }

    if ($userIdFilter) {
        $where[] = 'r.user_id = :user_id';
        $params[':user_id'] = $userIdFilter;
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // 排序规则：
    //   按帖子查回复 —— 正序，楼层从上到下；
    //   按用户查回复 —— 倒序，最新回复在前。
    if ($userIdFilter && !$postId) {
        $orderBy = 'ORDER BY r.created_at DESC';
    } else {
        $orderBy = 'ORDER BY r.created_at ASC';
    }

    // 查询总数，供分页使用
    $countSql = "SELECT COUNT(*) FROM replies r $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // 查询回复列表：联表取作者名、头像，以及所属帖子标题（供用户中心展示）
    $sql = "
        SELECT r.*, u.username AS author_name, u.avatar AS author_avatar, p.title AS post_title
        FROM replies r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN posts p ON r.post_id = p.id
        $whereClause
        $orderBy
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    // 过滤参数均为整型
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $replies = $stmt->fetchAll();

    // 字段类型规范化：parent_id 为 0 时统一转为 null，便于前端判断是否为顶层回复
    foreach ($replies as &$reply) {
        $reply['id'] = (int)$reply['id'];
        $reply['post_id'] = (int)$reply['post_id'];
        $reply['user_id'] = (int)$reply['user_id'];
        $reply['parent_id'] = $reply['parent_id'] ? (int)$reply['parent_id'] : null;
    }

    // 返回分页结构
    success([
        'list' => $replies,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ], '获取回复列表成功');
}

// ==================== POST：发表回复 ====================
if ($method === 'POST') {
    // 解析 JSON 请求体
    $input = json_decode(file_get_contents('php://input'), true);

    // 发帖人身份从 token 解析，不接收前端传入的 user_id
    $userId = get_user_id_from_token($pdo);
    if (!$userId) {
        error('请先登录', 401);
    }

    // 必填参数：所属帖子 ID 与回复内容
    $postId = (int)($input['post_id'] ?? 0);
    $content = trim($input['content'] ?? '');

    // parent_id 为 0 或未传时统一置为 null，表示顶层回复
    $parentId = isset($input['parent_id']) ? (int)$input['parent_id'] : null;
    $parentId = $parentId ?: null;

    if (!$postId || empty($content)) {
        error('参数不完整', 400);
    }

    // 楼中楼校验：父回复必须存在且属于同一帖子，防止跨帖挂载
    if ($parentId) {
        $checkStmt = $pdo->prepare('SELECT id FROM replies WHERE id = :id AND post_id = :post_id');
        $checkStmt->execute([':id' => $parentId, ':post_id' => $postId]);
        if (!$checkStmt->fetch()) {
            error('父回复不存在', 404);
        }
    }

    // 插入回复记录，创建时间取当前时间
    $stmt = $pdo->prepare('
        INSERT INTO replies (post_id, user_id, parent_id, content, created_at)
        VALUES (:post_id, :user_id, :parent_id, :content, NOW())
    ');
    $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId,
        ':parent_id' => $parentId,
        ':content' => $content
    ]);

    // 返回新回复 ID
    $replyId = (int)$pdo->lastInsertId();

    success(['id' => $replyId], '回复成功');
}