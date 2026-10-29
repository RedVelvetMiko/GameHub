<?php
/**
 * 统一 JSON 响应函数
 * 约定响应结构：{ code, msg, data? }
 * 两个函数都会输出响应后立即 exit，调用处无需再 return
 */

/**
 * 成功响应
 *
 * @param mixed  $data 返回的数据体，默认空数组
 * @param string $msg  提示信息
 */
function success($data = [], $msg = '操作成功') {
    header('Content-Type: application/json; charset=utf-8');
    // JSON_UNESCAPED_UNICODE：中文不转义为 \uXXXX，便于调试阅读
    echo json_encode(['code' => 200, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 错误响应
 *
 * @param string $msg  错误提示
 * @param int    $code HTTP 状态码，同时作为响应体的 code 字段
 */
function error($msg = '操作失败', $code = 400) {
    header('Content-Type: application/json; charset=utf-8');

    // 返回 401 时必须带上 WWW-Authenticate 头：
    // 在 CGI/FastCGI 模式下，若缺少该头，Apache 会用自己的 HTML 错误页
    // 替换掉响应体，导致前端拿不到 JSON 内容
    if ($code === 401) {
        header('WWW-Authenticate: Bearer realm="api"');
    }

    http_response_code($code);
    echo json_encode(['code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}