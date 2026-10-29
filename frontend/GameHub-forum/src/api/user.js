import request from './request'

export function getUserInfo(id) {
  return request.get('/user', { params: { id } })
}

export function updateUser(data) {
  return request.post('/user', data)
}