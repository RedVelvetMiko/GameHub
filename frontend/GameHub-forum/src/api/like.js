// src/api/likes.js
import request from './request'

// 点赞/取消点赞（toggle）
// 身份由拦截器自动带的 token 决定，前端只需传 post_id
export function toggleLike(postId) {
	return request.post('/likes', { post_id: postId })
}
