import request from './request'

// 获取帖子列表（支持分页、排序、分类、搜索）
export function getPosts(params = {}) {
  const query = {}
  if (params.page) query.page = params.page
  if (params.limit) query.limit = params.limit
  if (params.category_id) query.category_id = params.category_id
  if (params.keyword) query.keyword = params.keyword
  // 排序：'latest' 或 'hottest'
  query.sort = params.sort || 'latest'
  return request.get('/posts', { params: query })
}

// 获取单个帖子详情
export function getPostDetail(id) {
  return request.get('/posts', { params: { id } })
}

// 创建帖子
export function createPost(data) {
  return request.post('/posts', data)
}