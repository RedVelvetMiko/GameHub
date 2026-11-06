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
		const response = error.response
		const data = response?.data

		// HTTP 401：只有业务 code 也是 401 才认为登录过期
		if (response && response.status === 401) {
			if (data && data.code === 401) {
				redirectToLogin()
				return Promise.reject(new Error(data.msg || '请先登录'))
			}
			// 其他 401（如参数错误），只提示不登出，也要带后端 msg
			console.error(data?.msg || '请求失败')
			return Promise.reject(new Error(data?.msg || error.message || '请求失败'))
		}

		// 其他 HTTP 错误（400 / 405 / 409 / 500 ...）：优先用后端 msg
		if (response) {
			console.error(data?.msg || '请求失败')
			return Promise.reject(new Error(data?.msg || error.message || '请求失败'))
		}

		// 网络层错误（无 response）：保留 axios 原始文案
		console.error('网络错误:', error.message)
		return Promise.reject(error)
	}
)

export default request