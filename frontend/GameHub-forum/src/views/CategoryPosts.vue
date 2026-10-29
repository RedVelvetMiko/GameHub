<template>
  <div class="category-posts-page">
    <!-- 版块信息头部：数据未就绪时显示加载动画 -->
    <div class="category-header" v-if="category">
      <h1>{{ category.name }}</h1>
      <p>{{ category.description }}</p>
    </div>
    <div class="category-header" v-else>
      <Spin fix>
        <Icon type="ios-loading" size="18" class="spin-icon"></Icon>
        <div>加载中...</div>
      </Spin>
    </div>

    <!-- 排序工具栏：最新 / 最热 -->
    <div class="toolbar">
      <RadioGroup v-model="sortBy" type="button" @on-change="handleSortChange">
        <Radio label="latest">最新</Radio>
        <Radio label="hottest">最热</Radio>
      </RadioGroup>
    </div>

    <!-- 帖子列表 -->
    <Table
      :columns="columns"
      :data="posts"
      stripe
      :no-data-text="noDataText"
    >
      <!-- 标题列：点击跳转到帖子详情 -->
      <template #title="{ row }">
        <a @click="goToPost(row.id)" class="post-title">{{ row.title }}</a>
      </template>

      <!-- 作者列 -->
      <template #author="{ row }">
        <span>{{ row.author_name }}</span>
      </template>

      <!-- 发布时间列 -->
      <template #created_at="{ row }">
        {{ formatDate(row.created_at) }}
      </template>
    </Table>

    <!-- 分页：仅有数据时显示 -->
    <Page
      v-if="total > 0"
      :total="total"
      :current="page"
      :page-size="pageSize"
      show-elevator
      @on-change="changePage"
      style="margin-top: 16px; text-align: center;"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import request from '@/api/request'
import { Message } from 'view-ui-plus'

const route = useRoute()
const router = useRouter()

// 当前版块 ID
const categoryId = ref(null)
// 当前版块信息（名称、描述等）
const category = ref(null)
// 当前页帖子列表
const posts = ref([])
// 帖子总数
const total = ref(0)
// 当前页码
const page = ref(1)
// 每页条数
const pageSize = 10
// 排序方式：latest 最新 / hottest 最热
const sortBy = ref('latest')

// 空数据提示：翻页后才空说明没有更多，否则提示首发
const noDataText = computed(() => {
  if (page.value > 1) return '没有更多帖子了'
  return '该版块暂无帖子，快来发布第一篇吧！'
})

// 表格列配置
const columns = [
  { title: '标题', slot: 'title', key: 'title', minWidth: 200 },
  { title: '作者', slot: 'author', width: 100 },
  { title: '浏览', key: 'views', width: 70 },
  { title: '点赞', key: 'likes_count', width: 70 },
  { title: '发布时间', slot: 'created_at', width: 160 }
]

// 监听路由中版块 ID、页码、排序方式的变化，同步状态并重新拉取数据
watch(
  [() => route.params.id, () => route.query.page, () => route.query.sort],
  async ([newId]) => {
    if (newId) {
      categoryId.value = Number(newId)
      page.value = Number(route.query.page) || 1
      sortBy.value = route.query.sort || 'latest'
      await fetchData()
    }
  },
  { immediate: true }
)

// 拉取版块信息与帖子列表
async function fetchData() {
  try {
    // 并行请求：全部分类列表 + 当前版块的帖子列表
    const [allCategories, postsRes] = await Promise.all([
      request.get('/categories'),
      request.get('/posts', {
        params: {
          category_id: categoryId.value,
          page: page.value,
          limit: pageSize,
          sort: sortBy.value
        }
      })
    ])

    // 从全部分类中定位当前版块
    category.value = allCategories.find(c => c.id === categoryId.value) || null

    // 翻页后返回空列表：视为已到末页，清空列表并提示
    if (postsRes.list && postsRes.list.length === 0 && page.value > 1) {
      posts.value = []
      total.value = (page.value - 1) * pageSize
      Message.info('没有更多帖子了')
      return
    }

    // 后端返回结构为 { list, total, page, limit }
    posts.value = postsRes.list || []
    total.value = postsRes.total || 0
  } catch (err) {
    // 版块不存在时提示并返回首页，其余错误统一提示加载失败
    if (err.response && err.response.status === 404) {
      Message.error('该版块不存在')
      router.push('/')
    } else {
      Message.error('加载失败，请稍后重试')
    }
  }
}

// 将日期字符串格式化为 YYYY-MM-DD HH:mm
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

// 跳转到帖子详情页
function goToPost(id) {
  router.push(`/post/${id}`)
}

// 切换排序方式：重置回第一页
function handleSortChange(val) {
  router.push({
    query: { sort: val, page: 1 }
  })
}

// 切换页码：保留其他查询参数，仅更新 page
function changePage(newPage) {
  router.push({
    query: { ...route.query, page: newPage }
  })
}
</script>

<style scoped lang="scss">
/* 页面容器：限制最大宽度并水平居中 */
.category-posts-page {
  max-width: 900px;
  margin: 0 auto;
  padding: $spacing-lg;
}

/* 版块信息头部：版块名用正文色，描述用次要文字色 */
.category-header {
  margin-bottom: $spacing-md;

  h1 {
    color: $text-color;
    margin: 0;
  }

  p {
    color: $text-secondary;
    margin: 4px 0 0;
  }
}

/* 排序工具栏与表格之间留出间距 */
.toolbar {
  margin-bottom: $spacing-md;
}

/* 帖子标题链接：悬停时加下划线 */
.post-title {
  color: $primary-color;

  &:hover {
    text-decoration: underline;
  }
}

/* 加载图标：持续旋转动画 */
.spin-icon {
  animation: ani-spin 1s linear infinite;
}

@keyframes ani-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>