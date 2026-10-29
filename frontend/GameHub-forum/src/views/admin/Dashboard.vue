<template>
  <div class="dashboard">
    <h2 class="page-title">数据概览</h2>

    <!-- 统计卡片行：用户、帖子、今日发帖、回复四项汇总数据 -->
    <Row :gutter="16" class="stats-row">
      <Col span="6">
        <Card :bordered="false" class="stat-card stat-users">
          <Icon type="md-people" size="28" class="card-icon" />
          <div class="stat-value">{{ stats.total_users }}</div>
          <div class="stat-label">用户总数</div>
        </Card>
      </Col>
      <Col span="6">
        <Card :bordered="false" class="stat-card stat-posts">
          <Icon type="md-document" size="28" class="card-icon" />
          <div class="stat-value">{{ stats.total_posts }}</div>
          <div class="stat-label">帖子总数</div>
        </Card>
      </Col>
      <Col span="6">
        <Card :bordered="false" class="stat-card stat-today">
          <Icon type="md-create" size="28" class="card-icon" />
          <div class="stat-value">{{ stats.today_posts }}</div>
          <div class="stat-label">今日发帖</div>
        </Card>
      </Col>
      <Col span="6">
        <Card :bordered="false" class="stat-card stat-replies">
          <Icon type="md-chatbubbles" size="28" class="card-icon" />
          <div class="stat-value">{{ stats.total_replies }}</div>
          <div class="stat-label">回复总数</div>
        </Card>
      </Col>
    </Row>

    <!-- 最近帖子表格 -->
    <Card title="最近帖子" :bordered="false" class="recent-card">
      <Table :columns="recentColumns" :data="stats.recent_posts" size="small" stripe border></Table>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Message, Icon } from 'view-ui-plus'
import request from '@/api/request'

// 仪表盘统计数据，接口返回前先以零值占位，避免渲染报错
const stats = ref({
  total_users: 0,
  total_posts: 0,
  today_posts: 0,
  total_replies: 0,
  recent_posts: []
})

// 最近帖子表格列配置
const recentColumns = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '标题', key: 'title', ellipsis: true, minWidth: 240 },  // 标题过长时省略显示
  { title: '作者', key: 'author', width: 110 },
  { title: '发布时间', key: 'created_at', width: 170 }
]

// 加载仪表盘数据：成功则替换统计数据，失败则提示并重置为空对象
async function loadDashboard() {
  try {
    const res = await request.get('/admin/dashboard')
    stats.value = res || {}
  } catch (err) {
    console.error('请求失败:', err)
    Message.error('获取仪表盘数据失败')
    stats.value = {}
  }
}

// 组件挂载后立即拉取数据
onMounted(() => {
  loadDashboard()
})
</script>

<style scoped lang="scss">
/* 页面整体内边距 */
.dashboard {
  padding: 18px;
}

/* 页面标题 */
.page-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
}

/* 统计卡片行与下方内容留出间距 */
.stats-row {
  margin-bottom: 24px;
}

/* 统计卡片：内容居中，图标、数值、标签纵向排列 */
.stat-card {
  text-align: center;
  border-radius: 6px;
  padding: 20px 0;

  /* 图标：块级显示并留出下方间距，降低透明度以突出数值 */
  .card-icon {
    display: block;
    margin-bottom: 6px;
    opacity: 0.75;
  }

  /* 数值：大号加粗、深色，作为卡片视觉焦点 */
  .stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #17233d;
  }

  /* 标签：小号次要文字 */
  .stat-label {
    font-size: 13px;
    color: #808695;
    margin-top: 6px;
  }
}

/* 四类统计卡片使用不同浅色背景，便于区分 */
.stat-users { background: #f0faff; }
.stat-posts { background: #f0faf0; }
.stat-today { background: #fff7e6; }
.stat-replies { background: #f5f0ff; }

/* 最近帖子卡片：白底圆角 */
.recent-card {
  background: #fff;
  border-radius: 6px;
}
</style>