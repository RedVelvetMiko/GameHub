<template>
  <div class="admin-layout">
    <div class="admin-sidebar" :class="{ collapsed: isCollapsed }">
      <div class="sidebar-header">
        <!-- 左侧蓝色图标区：宽 64px，与收起状态宽度一致，点击切换侧栏 -->
        <div class="toggle-area" @click="toggleSidebar">
          <Icon :type="isCollapsed ? 'md-menu' : 'md-arrow-back'" class="toggle-icon" />
        </div>
        <!-- 右侧标题区：仅展开状态显示，不响应点击 -->
        <h3 class="sidebar-title" v-show="!isCollapsed">管理后台</h3>
      </div>
      <Menu
        :active-name="activeMenu"
        theme="light"
        width="auto"
        accordion
        @on-select="goTo"
      >
        <MenuItem name="dashboard">
          <Icon type="md-stats" />
          <span v-show="!isCollapsed">仪表盘</span>
        </MenuItem>
        <MenuItem name="users">
          <Icon type="md-people" />
          <span v-show="!isCollapsed">用户管理</span>
        </MenuItem>
        <MenuItem name="posts">
          <Icon type="md-document" />
          <span v-show="!isCollapsed">帖子管理</span>
        </MenuItem>
      </Menu>
    </div>
    <div class="admin-main">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from 'view-ui-plus'

const router = useRouter()
const route = useRoute()

// 侧栏是否收起
const isCollapsed = ref(false)

// 切换侧栏展开/收起状态
function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value
}

// 根据当前路由名称计算菜单激活项
const activeMenu = computed(() => {
  const name = route.name
  if (name === 'AdminDashboard') return 'dashboard'
  if (name === 'AdminUsers') return 'users'
  if (name === 'AdminPosts') return 'posts'
  return 'dashboard'
})

// 菜单选择后跳转到对应管理页面
function goTo(name) {
  if (name === 'dashboard') router.push({ name: 'AdminDashboard' })
  if (name === 'users') router.push({ name: 'AdminUsers' })
  if (name === 'posts') router.push({ name: 'AdminPosts' })
}
</script>

<style scoped lang="scss">
.admin-layout {
  display: flex;
  min-height: calc(100vh - 59px);
  background: #f5f7fa;
}

.admin-sidebar {
  width: 240px;
  background: #fff;
  border-right: 1px solid #e8eaec;
  flex-shrink: 0;
  transition: width 0.27s ease;
  overflow-x: hidden;
  position: relative;

  &.collapsed {
    width: 64px;
  }

  /*
   * 贯穿竖线：位于 x=64px 处，从头部底部延伸到底部。
   * 用于在展开状态下分隔图标列与文字区域。
   */
  &::after {
    content: '';
    position: absolute;
    left: 64px;
    top: 52px;
    bottom: 0;
    width: 1px;
    background: #e8eaec;
    z-index: 10;
    pointer-events: none;
  }

  /* 收起状态下隐藏贯穿竖线 */
  &.collapsed::after {
    display: none;
  }

  /* ===== 菜单项样式 ===== */
  :deep(.ivu-menu-item) {
    height: 52px;
    display: flex;
    align-items: center;
    padding: 0 !important;
    white-space: nowrap;
    position: relative;

    /* 移除 iView 默认右边框，选中指示条由自定义 ::after 实现 */
    border-right: none !important;

    /* 统一背景过渡，避免切换时出现视觉跳变 */
    transition: background 0.15s ease;
  }

  /* 非激活菜单项同样移除默认右边框 */
  :deep(.ivu-menu-item:not(.ivu-menu-item-active)) {
    border-right: none !important;
  }

  /* 图标列：占满左侧 64px，并水平垂直居中 */
  :deep(.ivu-menu-item .ivu-icon) {
    width: 64px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
  }

  /* 菜单文字：从图标列右侧开始，左侧留 16px 间距 */
  :deep(.ivu-menu-item > span) {
    padding-left: 16px;
  }

  /* 选中菜单项右侧蓝色指示条 */
  :deep(.ivu-menu-item-active)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    width: 3px;
    height: 100%;
    background: #2d8cf0;
    border-radius: 0;
  }
}

/* ===== 侧栏头部 ===== */
.sidebar-header {
  display: flex;
  align-items: stretch;
  height: 52px;
  white-space: nowrap;
}

/* 蓝色图标区：宽 64px、通高，可点击切换侧栏 */
.toggle-area {
  width: 64px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #2d8cf0;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: #2a7fd0;
  }

  &:active {
    background: #2471b8;
  }
}

.toggle-icon {
  font-size: 20px;
  color: #fff;
}

/* 标题区：白底深色文字，不响应鼠标事件 */
.sidebar-title {
  font-size: 16px;
  font-weight: 600;
  color: #17233d;
  margin: 0;
  padding-left: 16px;
  line-height: 30px;
  align-self: center;
  pointer-events: none;
  user-select: none;
}

/* 主内容区域：占据剩余空间，可纵向滚动 */
.admin-main {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}
</style>