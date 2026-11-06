<?php
/**
 * 校验工具：与前端 utils/validators.js 保持同一套规则
 * 注意：用户名按“字符”算，不按字节算，必须加 u 修饰符
 */

/**
 * 用户名：2-16 位中文、字母、数字、下划线、连字符
 * 与前端 /^[\u4e00-\u9fa5A-Za-z0-9_-]{2,16}$/ 对齐
 */
function isValidUsername($username): bool {
    if (!is_string($username)) {
        return false;
    }
    return preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_-]{2,16}$/u', $username) === 1;
}

/**
 * 密码：6-30 位非空白字符（任何位置都不允许空格）
 * 与前端 /^\S{6,30}$/ 对齐
 */
function isValidPassword($password): bool {
    if (!is_string($password)) {
        return false;
    }
    return preg_match('/^\S{6,30}$/', $password) === 1;
}