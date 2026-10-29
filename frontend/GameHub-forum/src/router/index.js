import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { Message } from 'view-ui-plus'
// 管理后台布局组件（含侧栏），作为后台子路由的父级容器
import AdminLayout from '@/components/AdminLayout.vue'

const routes = [
  // 首页
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue')
  },
  // 登录页
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue')
  },
  // 注册页
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue')
  },
  // 版块列表
  {
    path: '/categories',
    name: 'Categories',
    component: () => import('@/views/CategoryList.vue')
  },
  // 版块详情：某版块下的帖子列表
  {
    path: '/category/:id',
    name: 'CategoryDetail',
    component: () => import('@/views/CategoryPosts.vue')
  },
  // 帖子详情
  {
    path: '/post/:id',
    name: 'PostDetail',
    component: () => import('@/views/PostDetail.vue')
  },
  // 发帖页：需登录
  {
    path: '/post/create',
    name: 'CreatePost',
    component: () => import('@/views/CreatePost.vue'),
    meta: { requiresAuth: true }
  },
  // 个人中心
  {
    path: '/user/:id',
    name: 'UserCenter',
    component: () => import('@/views/UserCenter.vue'),
    meta: { title: '个人中心' }
  },
  // 搜索结果页
  {
    path: '/search',
    name: 'SearchResult',
    component: () => import('@/views/SearchResult.vue')
  },
  // 管理后台：需登录且为管理员，使用 AdminLayout 作为侧栏布局
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      // 访问 /admin 时默认重定向到仪表盘
      { path: '', redirect: { name: 'AdminDashboard' } },
      // 仪表盘
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
        meta: { title: '仪表盘' }
      },
      // 用户管理
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/UsersManage.vue'),
        meta: { title: '用户管理' }
      },
      // 帖子管理
      {
        path: 'posts',
        name: 'AdminPosts',
        component: () => import('@/views/admin/PostsManage.vue'),
        meta: { title: '帖子管理' }
      }
    ]
  }
]

const router = createRouter({
  // 使用 HTML5 History 模式，基路径为子目录 /gamehub/
  history: createWebHistory('/gamehub/'),
  routes
})

// 全局前置守卫：处理登录校验与角色权限
router.beforeEach((to, from, next) => {
  const userStore = useUserStore()

  // 需要登录但未登录：跳转登录页，并记录原目标地址以便登录后回跳
  if (to.meta.requiresAuth && !userStore.token) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  // 需要特定角色但当前用户角色不匹配：提示无权限并返回首页
  if (to.meta.role && userStore.userInfo?.role !== to.meta.role) {
    Message.error('无权限访问')
    next({ name: 'Home' })
    return
  }

  next()
})

export default router