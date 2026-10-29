<template>
  <div class="posts-manage">
    <h2 class="page-title">帖子管理</h2>

    <!-- 工具栏：按标题搜索帖子 -->
    <div class="toolbar">
      <Input v-model="keyword" placeholder="搜索帖子标题..." search clearable style="width: 320px"
        @on-search="loadPosts(1)" @on-clear="loadPosts(1)" />
    </div>

    <Table :columns="columns" :data="postList" :loading="loading" stripe border>
      <!-- 作者列 -->
      <template #author="{ row }">
        {{ row.author_name }}
      </template>

      <!-- 分类列：以 Tag 展示分类名 -->
      <template #category="{ row }">
        <Tag>{{ row.category_name }}</Tag>
      </template>

      <!-- 状态列：status 为 1 表示正常，否则为已删除 -->
      <template #status="{ row }">
        <Tag :color="row.status === 1 ? 'success' : 'default'">
          {{ row.status === 1 ? '正常' : '已删除' }}
        </Tag>
      </template>

      <!-- 置顶列：is_top 为 1 表示置顶 -->
      <template #top="{ row }">
        <Tag :color="row.is_top === 1 ? 'blue' : 'default'">
          {{ row.is_top === 1 ? '是' : '否' }}
        </Tag>
      </template>

      <!-- 发布时间列：日期与时间分两行显示 -->
      <template #createdAt="{ row }">
        <div class="time-cell">
          <div>{{ fmtDate(row.created_at) }}</div>
          <div class="time">{{ fmtTime(row.created_at) }}</div>
        </div>
      </template>

      <!-- 操作列：置顶/取消置顶、删除/恢复 -->
      <template #action="{ row }">
        <Button size="small" :type="row.is_top === 1 ? 'default' : 'primary'"
                @click="toggleTop(row)" style="margin-right: 4px;">
          {{ row.is_top === 1 ? '取消置顶' : '置顶' }}
        </Button>
        <Button size="small" :type="row.status === 1 ? 'warning' : 'success'"
                @click="toggleDelete(row)">
          {{ row.status === 1 ? '删除' : '恢复' }}
        </Button>
      </template>
    </Table>

    <!-- 分页：切换页码时重新加载对应页数据 -->
    <Page :total="total" :current="page" :page-size="limit" show-total show-elevator
      @on-change="loadPosts" style="margin-top: 16px; text-align: right;" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Message } from 'view-ui-plus'
import request from '@/api/request'

// 表格加载状态
const loading = ref(false)
// 当前页帖子列表
const postList = ref([])
// 总条数
const total = ref(0)
// 当前页码
const page = ref(1)
// 每页条数
const limit = ref(15)
// 搜索关键词
const keyword = ref('')

// 表格列配置：状态与置顶紧跟在标题之后，操作列固定在右侧
const columns = [
  { title: 'ID', key: 'id', width: 56, className: 'compact-col' },
  { title: '标题', key: 'title', ellipsis: true, minWidth: 180, tooltip: true },
  { title: '状态', slot: 'status', width: 86, align: 'center', ellipsis: true, tooltip: true },
  { title: '置顶', slot: 'top', width: 72, align: 'center', ellipsis: true, tooltip: true },
  { title: '作者', slot: 'author', width: 82, className: 'compact-col' },
  { title: '分类', slot: 'category', width: 92, className: 'compact-col' },
  { title: '浏览', key: 'views', width: 72, sortable: true, align: 'center', className: 'compact-col' },
  { title: '点赞', key: 'likes_count', width: 78, sortable: true, align: 'center', className: 'compact-col' },
  { title: '发布时间', slot: 'createdAt', width: 112, align: 'center' },
  { title: '操作', slot: 'action', width: 156, fixed: 'right', align: 'center', className: 'action-col' }
]

// 加载帖子列表：传入页码则更新当前页，随后按页码、每页条数和关键词请求数据
async function loadPosts(p) {
  if (p) page.value = p
  loading.value = true
  try {
    const data = await request.get('/admin/posts', {
      params: {
        page: page.value,
        limit: limit.value,
        keyword: keyword.value
      }
    })
    postList.value = data.list
    total.value = data.total
  } catch (err) {
    Message.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 切换帖子删除/恢复状态：请求成功后直接更新当前行状态
async function toggleDelete(row) {
  const newStatus = row.status === 1 ? 0 : 1
  try {
    await request.post(`/admin/post_delete?id=${row.id}`, { status: newStatus })
    row.status = newStatus
    Message.success(newStatus === 1 ? '已恢复' : '已删除')
  } catch (err) {
    Message.error('操作失败')
  }
}

// 切换帖子置顶状态：请求成功后直接更新当前行状态
async function toggleTop(row) {
  const newTop = row.is_top === 1 ? 0 : 1
  try {
    await request.post(`/admin/post_delete?id=${row.id}`, { is_top: newTop })
    row.is_top = newTop
    Message.success(newTop === 1 ? '已置顶' : '已取消置顶')
  } catch (err) {
    Message.error('操作失败')
  }
}

// 从日期时间字符串中取出日期部分，缺失时返回 '-'
function fmtDate(v) {
  return (v || '').split(' ')[0] || '-'
}

// 从日期时间字符串中取出时间部分，缺失时返回空字符串
function fmtTime(v) {
  return (v || '').split(' ')[1] || ''
}

// 组件挂载后加载第一页数据
onMounted(() => loadPosts(1))
</script>

<style scoped lang="scss">
/* 页面容器：白底、圆角 */
.posts-manage {
  background: #fff;
  padding: 18px;
  border-radius: 4px;
}

/* 页面标题 */
.page-title {
  font-size: 18px;
  margin-bottom: 16px;
  font-weight: 600;
}

/* 工具栏与表格之间留出间距 */
.toolbar {
  margin-bottom: 14px;
}

/* 发布时间单元格：日期居中、时间用更浅的小号字 */
.time-cell {
  text-align: center;

  > div {
    white-space: nowrap;
  }

  .time {
    color: #808695;
    font-size: 12px;
  }
}

/* 操作列：单元格垂直居中，内容水平居中排列 */
:deep(.action-col) {
  vertical-align: middle;
}

:deep(.action-col .ivu-table-cell) {
  padding-left: 6px;
  padding-right: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 紧凑列（ID、作者、浏览、点赞）：收窄左右内边距以节省横向空间 */
:deep(.compact-col .ivu-table-cell) {
  padding-left: 6px;
  padding-right: 6px;
}

/* 状态列：单元格垂直居中，内容水平居中 */
:deep(.status-col) {
  vertical-align: middle;
}

:deep(.status-col .ivu-table-cell) {
  padding-left: 6px;
  padding-right: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 置顶列：单元格垂直居中，内容水平居中 */
:deep(.top-col) {
  vertical-align: middle;
}

:deep(.top-col .ivu-table-cell) {
  padding-left: 6px;
  padding-right: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>