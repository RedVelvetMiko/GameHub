<?php
// utils/auth.php
// 认证工具：从请求头 Authorization: Bearer <token> 中解析出对应的 user_id

/**
 * 根据请求头中的 token 反查用户 ID
 *
 * @param PDO $pdo 数据库连接
 * @return int 有效 token 返回对应用户 ID；缺失或无效则返回 0
 */
function get_user_id_from_token(PDO $pdo): int {
    // 读取请求头：部分服务器（如 Apache + 重写规则）会把 Authorization 转到 REDIRECT_HTTP_AUTHORIZATION
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    // 按 Bearer <token> 格式提取 token，格式不符直接视为未登录
    if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return 0;
    }

    // 用 token 在 users 表中查找对应记录（token 在登录时写入）
    $stmt = $pdo->prepare('SELECT id FROM users WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $m[1]]);
    $user = $stmt->fetch();

    // 命中则返回整型用户 ID，否则返回 0 表示无效
    return $user ? (int)$user['id'] : 0;
}