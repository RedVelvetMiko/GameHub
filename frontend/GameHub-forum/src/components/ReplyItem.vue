<template>
  <!-- 单条回复：左侧竖线标识层级，根据 depth 缩进形成嵌套结构 -->
  <div class="reply-item" :style="{ marginLeft: Math.min(depth, 3) * 20 + 'px' }">
    <!-- 回复头部：头像、用户名、时间、回复按钮 -->
    <div class="reply-header">
      <Avatar size="small" icon="md-person" />
      <span class="username">{{ reply.author_name || '匿名用户' }}</span>
      <span class="time">{{ formatDate(reply.created_at) }}</span>
      <Button size="small" type="text" @click="$emit('reply-to', reply)">回复</Button>
    </div>

    <!-- 回复正文 -->
    <div class="reply-content">{{ reply.content }}</div>

    <!-- 子回复：递归渲染，层级 +1，并向上透传 reply-to 事件 -->
    <div v-if="reply.children && reply.children.length" class="reply-children">
      <ReplyItem
        v-for="child in reply.children"
        :key="child.id"
        :reply="child"
        :depth="depth + 1"
        @reply-to="$emit('reply-to', $event)"
      />
    </div>
  </div>
</template>

<script setup>
// reply：当前回复数据；depth：嵌套层级，用于控制缩进
defineProps({
  reply: {
    type: Object,
    required: true
  },
  depth: {
    type: Number,
    default: 0
  }
})

// 向父组件抛出“回复某条评论”事件
defineEmits(['reply-to'])

// 将日期字符串格式化为 YYYY-MM-DD HH:mm
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}
</script>

<style scoped lang="scss">
/* 回复项：左侧竖线体现层级，底部细线分隔相邻回复 */
.reply-item {
  border-left: 2px solid $border-color;
  padding-left: $spacing-sm;
  margin-bottom: $spacing-sm;
  padding-bottom: $spacing-sm;
  border-bottom: 1px solid $border-color;
  /* 防止子回复溢出容器 */
  overflow: hidden;
}

/* 头部：头像、用户名、时间、按钮横向排列 */
.reply-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;

  /* 用户名：加粗，使用正文色 */
  .username {
    font-weight: bold;
    color: $text-color;
  }

  /* 时间：次要文字色、小号字 */
  .time {
    color: $text-secondary;
    font-size: $font-size-sm;
  }
}

/* 回复正文：正文色，行高保证可读性 */
.reply-content {
  color: $text-color;
  line-height: 1.6;
  margin-bottom: $spacing-sm;
}

/* 子回复容器：与正文留出间距 */
.reply-children {
  margin-top: $spacing-sm;
}

/* 最后一条回复去掉底部细线，避免与父容器边框重复 */
.reply-item:last-child {
  border-bottom: none;
}
</style>