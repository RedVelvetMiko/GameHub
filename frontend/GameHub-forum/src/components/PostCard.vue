<template>
  <!-- 帖子卡片：展示标题、作者、发布时间、浏览量和点赞数 -->
  <Card class="post-card" :bordered="true" :dis-hover="false">
    <!-- 帖子标题：点击跳转到帖子详情页 -->
    <div class="post-title">
      <router-link :to="`/post/${post.id}`">{{ post.title }}</router-link>
    </div>

    <!-- 帖子元信息：作者、发布时间、浏览量、点赞数 -->
    <div class="post-meta">
      <span class="author">作者：{{ post.author_name }}</span>
      <span>{{ formatDate(post.created_at) }}</span>
      <span>浏览 {{ post.views }}</span>
      <span>点赞 {{ post.likes_count }}</span>
    </div>
  </Card>
</template>

<script setup>
// 接收父组件传入的帖子对象
defineProps({
  post: {
    type: Object,
    required: true
  }
})

// 将日期字符串格式化为 YYYY-MM-DD HH:mm
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}
</script>

<style scoped lang="scss">
/* 卡片底部留出间距，避免相邻卡片紧贴 */
.post-card {
  margin-bottom: $spacing-sm;
}

/* 帖子标题：中等字重，链接默认使用正文色，悬停变主题色 */
.post-title {
  font-size: $font-size-base;
  font-weight: 500;
  margin-bottom: $spacing-xs;

  a {
    color: $text-color;

    &:hover {
      color: $primary-color;
    }
  }
}

/* 元信息：小号次要文字，横向排列并保持间距 */
.post-meta {
  font-size: $font-size-sm;
  color: $text-secondary;
  display: flex;
  gap: $spacing-md;
}
</style>