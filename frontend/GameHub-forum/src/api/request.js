// src/api/request.js
import axios from 'axios'
import { useUserStore } from '@/stores/user'

const request = axios.create({
	baseURL: '/api',
	timeout: 10000
})

function redirectToLogin() {
	const userStore = useUserStore()
	userStore.logout()
	const redirect = encodeURIComponent(window.location.pathname + window.location.search)
	window.location.href = `/login?redirect=${redirect}`
}

// 请求拦截器
request.interceptors.request.use(
	config => {
		const userStore = useUserStore()
		if (userStore.token) {
			config.headers.Authorization = `Bearer ${userStore.token}`
		}
		return config
	},
	error => Promise.reject(error)
)

// 响应拦截器
request.interceptors.response.use(
	response => {
		const res = response.data
		if (res && typeof res.code !== 'undefined') {
			if (res.code === 401) {
				redirectToLogin()
				return Promise.reject(new Error(res.msg || '请先登录'))
			}
			if (res.code !== 200) {
				console.error(res.msg || '请求失败')
				return Promise.reject(new Error(res.msg || '请求失败'))
			}
			return res.data
		}
		return res
	},
	error => {
		// ✅ 修改点：HTTP 401 不一定代表登录过期，还要看响应体的 code
		if (error.response && error.response.status === 401) {
			const data = error.response.data
			// 只有业务 code 也是 401 时才认为是登录过期
			if (data && data.code === 401) {
				redirectToLogin()
				return Promise.reject(new Error(data.msg || '请先登录'))
			}
			// 其他情况（如参数错误）只提示，不登出
			console.error(data?.msg || '请求失败')
			return Promise.reject(error)
		}
		console.error('网络错误:', error.message)
		return Promise.reject(error)
	}
)

export default request