import {
	defineStore
} from 'pinia'
import {
	ref
} from 'vue'

// 用户状态仓库：保存登录令牌与用户信息
export const useUserStore = defineStore('user', () => {
	// 登录令牌，空字符串表示未登录
	const token = ref('')
	// 当前登录用户信息，未登录时为 null
	const userInfo = ref(null)

	// 设置登录令牌
	function setToken(newToken) {
		token.value = newToken
	}

	// 设置用户信息
	function setUserInfo(info) {
		userInfo.value = info
	}

	// 退出登录：清空令牌与用户信息
	function logout() {
		token.value = ''
		userInfo.value = null
	}

	return {
		token,
		userInfo,
		setToken,
		setUserInfo,
		logout
	}
}, {
	// 开启持久化，自动保存到 localStorage，刷新页面后登录状态不丢失
	persist: true
})