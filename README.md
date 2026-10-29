# GameHub 游戏论坛

> 基于 Vue 3 + PHP + MySQL 的前后端分离游戏社区论坛，个人独立完成的「前端 + 后端 + 数据库」全链路项目。
> 采用 Mock 先行模式：先用 JSON-Server 验证前端交互，再切换 PHP + MySQL 真实后端，最终在 phpStudy（Apache + MySQL）环境完整运行并通过测试。

| 项目 | 说明 |
| --- | --- |
| 技术栈 | Vue 3 · Vite · Vue Router 4 · Pinia · Axios · Sass · View UI Plus · wangEditor 5 ｜ PHP · MySQL（PDO） |
| 部署 | phpStudy（Apache 8080 + MySQL），前后端同源，.htaccess 重写 |
| 开发周期 | 2026.10 起 |
| 代码规模 | 约 3000+ 行，注释完整 |

## 功能特性

**用户端**
- 注册 / 登录 / 退出：Token 鉴权，刷新保持登录状态，401 自动跳登录页
- 首页：轮播图（点击跳转版块、hover 暂停）+ 版块标签入口 + 热帖排行榜（按浏览量排序）
- 版块浏览：版块卡片列表、版块内帖子列表（分页 / 排序 / 搜索）
- 帖子：富文本发帖（wangEditor）、正文展示、回复 / 楼中楼、点赞 / 取消点赞、浏览量统计
- 个人中心：我的帖子 / 我的回复 / 资料修改（签名）
- 全局搜索：关键词模糊搜索，结果页 Table 展示 + 分页

**管理员后台**
- 用户管理：角色权限修改（普通用户 / 管理员）
- 帖子管理：置顶 / 取消置顶、软删除（status 字段）
- 权限变更后自动更新本地角色并跳转；/admin 子路由守卫二次保护

## 技术要点

**前端架构**
- 组合式 API + `<script setup>`，目录分层（api / stores / router / views / components / styles）
- Pinia + pinia-plugin-persistedstate：token 持久化，刷新不丢失
- Axios 统一封装：baseURL + 请求拦截器自动携带 token + 响应拦截器统一错误处理（401 跳登录）
- Vue Router 懒加载 + 全局 beforeEach 守卫（meta.requiresAuth），登录后按 redirect 回跳原页
- Sass `@use` 模块系统 + 设计令牌（`_variables.scss` 统一颜色 / 字号 / 间距）
- wangEditor 5 封装 RichEditor 组件：v-model 双向绑定，onBeforeUnmount 销毁实例

**后端设计**
- PHP + PDO 预处理防 SQL 注入，统一 JSON 返回格式 `{code, msg, data}`
- Token 鉴权：登录后生成随机 token 存库，后续请求校验
- 软删除：`posts.status`（1 正常 / 0 删除），列表查询只取 status=1
- 置顶：`is_top` 字段，列表按 is_top DESC, created_at DESC 排序

**部署与跨域**
- 开发期 Vite proxy 解决跨域；生产期前后端同源部署，无需跨域配置
- .htaccess 双规则缺一不可：API 重写（`/api/xxx → api/xxx.php`）+ Vue History 模式兜底（index.html）
- 初期子目录部署导致资源与 API 路径反复加前缀，最终站点根目录直指 gamehub，路径回归简洁

## 快速开始

**环境要求**：Node.js（开发环境 v24.21.0 / npm 11.19.0）、phpStudy（Apache + MySQL，Apache 端口 8080）

    # 1. 前端
    npm install
    npm run dev          # http://localhost:5173

    # 2. Mock 模式（可选）
    npm run mock         # JSON-Server 运行于 3000 端口，Vite proxy 将 /api 转发过去

**真实后端（PHP + MySQL）**
1. phpStudy 启动 Apache + MySQL，导入数据库［待补：SQL 文件路径］
2. 站点根目录直接指向 gamehub（勿用子目录方式，否则资源与 API 路径需加前缀）
3. 确认 .htaccess 双规则生效（API 重写 + History 兜底）

## 项目结构

    GameHub-forum/
    ├─ src/                  # 前端（Vue 3）
    │  ├─ api/               # request.js（Axios 拦截器）、auth.js、category.js、post.js
    │  ├─ stores/            # user.js（token 持久化）
    │  ├─ router/            # 路由表 + 全局守卫
    │  ├─ views/             # Home / CategoryList / PostDetail / CreatePost / UserCenter /
    │  │                     # SearchResult / Login / Register / AdminLayout / UsersManage / PostsManage
    │  ├─ components/        # NavBar / Footer / PostCard / RichEditor
    │  └─ styles/            # _variables.scss 设计令牌、global.scss
    ├─ api/                  # PHP 后端接口：posts.php、post_delete.php 等
    ├─ db.json               # JSON-Server 模拟数据（Mock 阶段）
    ├─ .htaccess             # API 重写 + History 兜底
    └─ vite.config.js        # Sass additionalData 注入、/api 代理

## 开发历程

环境准备 → 前端骨架 → Mock 数据 → 核心交互 → 真实后端与管理后台（→ 收尾部署）。每阶段目标明确，先跑通再优化。

| 阶段 | 主要工作 | 状态 |
| --- | --- | --- |
| 0 环境准备 | Node / npm / phpStudy（Apache 8080）工具链 | ✅ |
| 1 前端骨架 | Vite + Vue3、目录分层、Router / Pinia / Axios 封装、全局布局、Sass 变量 | ✅ |
| 2 Mock 开发 | JSON-Server + db.json 模拟数据、Vite proxy；登录注册 / 版块 / 首页 / 发帖详情 / 个人中心 / 搜索 | ✅ |
| 3 核心交互 | 路由守卫补全、发帖入口、搜索页 Table 重构、wangEditor 集成、首页优化、代码质量统一 | ✅ |
| 4 真实后端 | PHP + MySQL 接管（PDO / Token / 软删除 / 置顶）、管理员后台、.htaccess 部署、体验迭代 | ✅（至 2026-10-20） |
| 5 收尾部署 | 全面测试、部署上线、密码哈希、Token 过期机制 | ⏳ 计划中 |

### 踩坑与修复

<details>
<summary>展开查看开发中定位与解决的 10 个典型问题</summary>

| 问题 | 原因 → 修复 | 知识点 |
| --- | --- | --- |
| Sass @import 弃用警告 | `additionalData` 与 global.scss 改用 `@use ... as *`，删除组件内重复 @import | Sass 新模块系统，避免全局污染 |
| 注册“失败”但 POST /users 201 | 响应拦截器假定 `{code,msg,data}`，JSON-Server 无 code 字段 → 先判 code 再按统一格式处理 | 前后端接口约定 / Mock 兼容 |
| 路由 /categories 警告 | NavBar 有链接但路由表缺失 → 补路由与占位页 | 路由表与导航一致性 |
| 下拉菜单“默认展开” | Vue2 `slot="list"` 被忽略，内容落入默认插槽 → `<template #list>`；`.native` 移除 | Vue3 具名插槽 / 修饰符变更 |
| Sass 变量未定义 | `_variables.scss` 缺字号梯度 → 补全 xs–xxxl 与 $text-secondary | SCSS 先定义后使用 / 设计令牌 |
| 守卫读 token 与存储不一致 | localStorage 取不到 Pinia 持久化的 token → 守卫内 `useUserStore().token`；登录后读 `route.query.redirect` 回跳 | Pinia 持久化 / 守卫时机 |
| useRoute 未导入 | 只导入了 useRouter → 补 `import { useRoute }` | Vue Router 组合式 API |
| 子目录部署路径错乱 | /gamehub/ 子目录致资源与 API 需加前缀 → 站点根目录直指 gamehub | Apache 部署 / .htaccess |
| 深度选择器报错 | `::v-deep` 弃用 → 升级 `:deep()` 语法 | Vue3 SFC 样式规范 |
| 版块列表显示已软删帖 | api/posts.php 列表查询缺条件 → 增加 status = 1 | 软删除查询完整性 |

</details>

## 已知问题与 TODO

开发中已知的问题（回复作者显示、点赞数回退、分页总数、楼中楼嵌套等）多数已在阶段 4 后端解决，详见开发日志。

- [ ] 管理员后台功能扩展
- [ ] 全面测试 + 部署上线
- [ ] 密码哈希改造（password_hash / password_verify）
- [ ] Token 过期机制（expires_at 或 JWT）
- [ ] HTTPS 部署（Let's Encrypt）
- [ ] 消息通知（回复 / 点赞提醒）
- [ ] 性能优化（图片懒加载、组件异步加载、索引优化）
- [ ] 单元测试与 CI/CD

## 截图

［待补：首页 / 帖子详情（楼中楼、点赞）/ 富文本发帖 / 个人中心 / 管理后台截图，建议存 docs/screenshots/ 后在此引用］
