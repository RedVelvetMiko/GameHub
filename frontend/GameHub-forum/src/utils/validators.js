// 校验规则常量，与后端 utils/validate.php 保持一致，避免前后端校验结果不一致

// ========== 用户名 ==========
// 允许 2-16 位：中文、字母、数字、下划线、连字符
export const USERNAME_RE = /^[\u4e00-\u9fa5A-Za-z0-9_-]{2,16}$/
// 用户名校验失败提示文案
export const USERNAME_MSG = '用户名需为 2-16 位中文、字母、数字或下划线'

// ========== 密码 ==========
// 6-30 位非空白字符，即不允许包含空格（含首尾空格）
export const PASSWORD_RE = /^\S{6,30}$/
// 密码校验失败提示文案
export const PASSWORD_MSG = '密码需为 6-30 位，且不能包含首尾空格'

// ========== 登录页输入长度上限 ==========
// 登录场景放宽上限，仅做长度保护，与数据库字段 varchar(50) 对齐
export const LOGIN_USERNAME_MAX = 50
export const LOGIN_PASSWORD_MAX = 100