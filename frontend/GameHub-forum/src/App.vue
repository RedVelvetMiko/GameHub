<template>
  <div class="app-wrapper">
    <!-- 顶部导航栏 -->
    <NavBar />

    <!-- 主内容区：各路由页面在此渲染 -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- 底部页脚 -->
    <Footer />
  </div>
</template>

<script setup>
import NavBar from '@/components/NavBar.vue'
import Footer from '@/components/Footer.vue'
</script>

<style scoped lang="scss">
/* 应用外壳：纵向排列导航栏、主内容、页脚，并撑满整个视口 */
.app-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  // 页面统一灰色背景，集中在此处设置
  background-color: $bg-color;
}

/* 主内容区：占据剩余高度，限制最大宽度并水平居中 */
.main-content {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: $spacing-md;
}
</style>