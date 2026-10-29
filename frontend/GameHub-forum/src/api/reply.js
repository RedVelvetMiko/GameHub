import request from './request'

export function getReplies(postId, page = 1, limit = 10) {
  return request.get('/replies', { params: { post_id: postId, page, limit } })
}

export function addReply(data) {
  return request.post('/replies', data)
}