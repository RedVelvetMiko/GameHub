<?php
/**
 * 帖子接口
 * GET  /api/posts?category_id=&page=&sort=&keyword=&limit=&user_id=  帖子列表
 * GET  /api/posts?id=1                                              单个帖子（含 is_liked）
 * POST /api/posts                                                   创建帖子（user_id 从 token 解析）
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET：获取单个帖子或帖子列表 ====================
if ($method === 'GET') {
    // ---------- 传了 id 参数：返回单个帖子详情 ----------
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        // 先自增浏览量，再查询，保证返回的 views 是自增后的值
        $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :id')
            ->execute([':id' => $id]);

        // 查询帖子本体，联表取作者名
        $stmt = $pdo->prepare('
            SELECT p.*, u.username AS author_name
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();

        if (!$post) {
            error('帖子不存在', 404);
        }

        // 字段类型规范化：数字转整型，布尔语义字段转 bool
        $post['id'] = (int)$post['id'];
        $post['user_id'] = (int)$post['user_id'];
        $post['category_id'] = (int)$post['category_id'];
        $post['views'] = (int)$post['views'];
        $post['likes_count'] = (int)$post['likes_count'];
        $post['is_top'] = (bool)$post['is_top'];
        $post['status'] = (bool)$post['status'];

        // 若请求携带有效 token，则补充当前用户是否已点赞该帖
        $post['is_liked'] = false;
        $uid = get_user_id_from_token($pdo);
        if ($uid) {
            $likeStmt = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = :uid AND post_id = :pid LIMIT 1');
            $likeStmt->execute([':uid' => $uid, ':pid' => $id]);
            $post['is_liked'] = (bool)$likeStmt->fetch();
        }

        success($post, '获取帖子成功');
    }

    // ---------- 未传 id：返回帖子列表（分页 + 排序 + 搜索 + 过滤） ----------
    // 分页参数：页码最小 1，每页条数限制在 1-50 之间，默认 10
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // 排序方式：hottest 最热 / latest 最新（默认）
    $sort = $_GET['sort'] ?? 'latest';
    // 搜索关键词（同时匹配标题与内容）
    $keyword = trim($_GET['keyword'] ?? '');
    // 按版块过滤
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    // 按用户过滤，用于个人中心「我的帖子」
    $userIdFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    // 动态拼接 WHERE 条件与对应参数
    $where = [];
    $params = [];

    // 列表默认只显示正常状态（status = 1）的帖子
    $where[] = 'p.status = 1';

    // 按用户过滤
    if ($userIdFilter) {
        $where[] = 'p.user_id = :user_id';
        $params[':user_id'] = $userIdFilter;
    }

    // 按版块过滤
    if ($categoryId) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    // 关键词搜索：标题或内容任一命中即可。
    // LIKE 占位符不能复用，故用 :keyword 与 :keyword2 两个同名参数
    if ($keyword) {
        $where[] = '(p.title LIKE :keyword OR p.content LIKE :keyword2)';
        $params[':keyword'] = "%$keyword%";
        $params[':keyword2'] = "%$keyword%";
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // 排序规则：最热按点赞、浏览降序；最新按置顶优先、发布时间降序
    $orderBy = match ($sort) {
        'hottest' => 'ORDER BY p.likes_count DESC, p.views DESC',
        default   => 'ORDER BY p.is_top DESC, p.created_at DESC',
    };

    // 先查总数，供前端分页使用
    $countSql = "SELECT COUNT(*) FROM posts p $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // 再查当前页数据，联表取作者名
    $sql = "
        SELECT p.*, u.username AS author_name
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        $whereClause
        $orderBy
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    // 先绑定筛选参数（字符串），再绑定分页参数（整型，需指定 PDO::PARAM_INT）
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // 逐条规范化字段类型
    foreach ($posts as &$post) {
        $post['id'] = (int)$post['id'];
        $post['user_id'] = (int)$post['user_id'];
        $post['category_id'] = (int)$post['category_id'];
        $post['views'] = (int)$post['views'];
        $post['likes_count'] = (int)$post['likes_count'];
        $post['is_top'] = (bool)$post['is_top'];
        $post['status'] = (bool)$post['status'];
        // author_name 本身为字符串，无需转换
    }

    // 返回分页结构
    success([
        'list' => $posts,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ], '获取帖子列表成功');
}

// ==================== POST：创建帖子 ====================
if ($method === 'POST') {
    // 解析 JSON 请求体
    $input = json_decode(file_get_contents('php://input'), true);

    // 发帖必须登录，用户身份从 token 解析，不接收前端传入的 user_id
    $userId = get_user_id_from_token($pdo);
    if (!$userId) {
        error('请先登录', 401);
    }

    // 版块 ID、标题、内容均必填
    $categoryId = (int)($input['category_id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');

    if (!$categoryId || empty($title) || empty($content)) {
        error('参数不完整');
    }

    // 插入帖子，创建时间取当前时间
    $stmt = $pdo->prepare('
        INSERT INTO posts (user_id, category_id, title, content, created_at)
        VALUES (:user_id, :category_id, :title, :content, NOW())
    ');
    $stmt->execute([
        ':user_id' => $userId,
        ':category_id' => $categoryId,
        ':title' => $title,
        ':content' => $content
    ]);

    // 返回新帖 ID，供前端跳转详情页
    $postId = (int)$pdo->lastInsertId();

    success(['id' => $postId], '发帖成功');
}