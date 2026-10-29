<template>
  <!-- 页脚：深色背景，居中显示版权信息 -->
  <div class="footer">
    <div class="footer-container">
      <p>&copy; 2026 M1_K0 GameHub. All rights reserved.</p>
    </div>
  </div>
</template>

<style scoped lang="scss">
.footer {
  background: #333;
  color: #ccc;
  text-align: center;
  padding: $spacing-md 0;
  margin-top: $spacing-xl; /* 与上方内容留出间距 */
}

/* 内容容器：限制最大宽度并水平居中 */
.footer-container {
  max-width: 1200px;
  margin: 0 auto;
}
</style>