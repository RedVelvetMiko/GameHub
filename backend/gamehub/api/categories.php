<?php
/**
 * 版块列表接口
 * GET /api/categories
 * 返回全部版块，按排序字段升序排列
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

// 仅允许 GET 请求，其他方法返回 405
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('仅支持 GET 请求', 405);
}

// 查询所有版块，按 sort 升序排列以控制前端展示顺序
$stmt = $pdo->query('SELECT id, name, icon, description, sort FROM categories ORDER BY sort ASC');
$categories = $stmt->fetchAll();

// 将 id 统一转为整数，避免前端拿到字符串类型的 ID
foreach ($categories as &$cat) {
    $cat['id'] = (int)$cat['id'];
}

// 返回版块列表
success($categories, '获取版块列表成功');