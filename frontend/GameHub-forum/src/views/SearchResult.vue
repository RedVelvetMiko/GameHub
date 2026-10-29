<template>
  <div class="search-result-page">
    <!-- 搜索标题区：展示关键词与结果数量 -->
    <div class="search-header">
      <h2>搜索结果：<span class="keyword">{{ keyword }}</span></h2>
      <p v-if="total > 0">共找到 {{ total }} 条结果</p>
      <p v-else>没有找到相关帖子</p>
    </div>

    <!-- 排序工具栏：最新 / 最热 -->
    <div class="toolbar">
      <RadioGroup v-model="sort" type="button" @on-change="handleSortChange">
        <Radio label="latest">最新</Radio>
        <Radio label="hottest">最热</Radio>
      </RadioGroup>
    </div>

    <!-- 帖子列表：加载中时显示 Spin，加载完成后显示表格 -->
    <div class="table-wrapper">
      <Table
        v-if="!loading"
        :columns="columns"
        :data="posts"
        stripe
        :no-data-text="noDataText"
      >
        <!-- 标题列：点击跳转帖子详情 -->
        <template #title="{ row }">
          <router-link :to="`/post/${row.id}`" class="post-title">{{ row.title }}</router-link>
        </template>

        <!-- 作者列：缺省时用「用户+ID」兜底 -->
        <template #author="{ row }">
          <span>{{ row.author_name || '用户' + row.user_id }}</span>
        </template>

        <!-- 发布时间列 -->
        <template #created_at="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </Table>
      <div v-else class="table-loading">
        <Spin fix>
          <Icon type="ios-loading" size="18" class="spin-icon"></Icon>
          <div>加载中...</div>
        </Spin>
      </div>
    </div>

    <!-- 分页：每页固定 10 条，切换页码写入路由 -->
    <Page
      v-if="total > 0"
      :total="total"
      :current="page"
      :page-size="pageSize"
      show-elevator
      show-total
      @on-change="changePage"
      class="pagination"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getPosts } from '@/api/post'
import { Message } from 'view-ui-plus'

const route = useRoute()
const router = useRouter()

// 当前搜索关键词
const keyword = ref('')
// 当前页帖子列表
const posts = ref([])
// 结果总数
const total = ref(0)
// 当前页码
const page = ref(1)
// 每页条数，固定 10 条，不提供切换
const pageSize = 10
// 排序方式：latest 最新 / hottest 最热
const sort = ref('latest')
// 加载状态，控制表格与 Spin 的切换
const loading = ref(false)

// 空数据提示：有关键词时提示无匹配结果，翻页后才空说明没有更多，其余为暂无数据
const noDataText = computed(() => {
  if (keyword.value) return `没有找到与 "${keyword.value}" 相关的帖子`
  if (page.value > 1) return '没有更多帖子了'
  return '暂无数据'
})

// 表格列配置（与版块页保持一致）
const columns = [
  { title: '标题', slot: 'title', key: 'title', minWidth: 200 },
  { title: '作者', slot: 'author', width: 100 },
  { title: '浏览', key: 'views', width: 70 },
  { title: '点赞', key: 'likes_count', width: 70 },
  { title: '发布时间', slot: 'created_at', width: 160 }
]

// 监听路由 query 中的关键词、页码、排序，同步到本地状态后重新搜索
watch(
  [() => route.query.q, () => route.query.page, () => route.query.sort],
  ([newQ, newPage, newSort]) => {
    keyword.value = newQ || ''
    page.value = Number(newPage) || 1
    sort.value = newSort || 'latest'
    fetchPosts()
  },
  { immediate: true }
)

// 拉取搜索结果：无关键词时直接清空，避免无意义请求
async function fetchPosts() {
  if (!keyword.value) {
    posts.value = []
    total.value = 0
    return
  }
  loading.value = true
  try {
    const res = await getPosts({
      keyword: keyword.value,
      page: page.value,
      limit: pageSize,
      sort: sort.value
    })

    // 翻页后返回空列表：视为已到末页，清空列表并提示
    if (res.list && res.list.length === 0 && page.value > 1) {
      posts.value = []
      total.value = (page.value - 1) * pageSize
      Message.info('没有更多帖子了')
      return
    }

    // 后端返回结构为 { list, total, page, limit }
    posts.value = res.list || []
    total.value = res.total || 0
  } catch (err) {
    console.error('搜索失败', err)
    Message.error('搜索失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

// 切换排序：写入路由并重置回第 1 页
function handleSortChange(val) {
  router.replace({
    query: { ...route.query, sort: val, page: 1 }
  })
}

// 切换页码：保留其他 query 参数，仅更新 page
function changePage(newPage) {
  router.replace({
    query: { ...route.query, page: newPage }
  })
}

// 将日期字符串格式化为 YYYY-MM-DD HH:mm
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>

<style scoped lang="scss">
/* 页面容器：限制最大宽度并水平居中 */
.search-result-page {
  max-width: 900px;
  margin: 0 auto;
  padding: $spacing-lg;
}

/* 搜索标题区：关键词高亮为主题色 */
.search-header {
  margin-bottom: $spacing-md;

  .keyword {
    color: $primary-color;
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

/* 表格外层：作为 Spin 的定位容器，并预留最小高度避免布局跳动 */
.table-wrapper {
  position: relative;
  min-height: 200px;
}

/* 加载中容器：同样保留最小高度 */
.table-loading {
  position: relative;
  min-height: 200px;
}

/* 加载图标：持续旋转动画 */
.spin-icon {
  animation: ani-spin 1s linear infinite;
}

@keyframes ani-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 分页：居中显示并与上方表格留出间距 */
.pagination {
  margin-top: 16px;
  text-align: center;
}
</style>