<template>
	<div class="user-center">
		<!-- 用户信息卡片：数据就绪后显示，否则显示加载提示 -->
		<Card v-if="user" class="profile-card">
			<div class="profile-header">
				<Avatar icon="md-person" size="large" />
				<div class="profile-info">
					<h2>{{ user.username }}</h2>
					<!-- 签名：未填写时显示占位文案 -->
					<p class="signature">{{ user.signature || '这个人很懒，什么都没留下' }}</p>
				</div>
			</div>
		</Card>
		<div v-else class="loading">加载中...</div>

		<!-- Tab 切换：我的帖子 / 我的回复 / 资料修改 -->
		<Tabs value="posts" @on-click="handleTabChange">
			<!-- 我的帖子 -->
			<TabPane label="我的帖子" name="posts">
				<div v-if="myPosts.length > 0">
					<PostCard v-for="post in myPosts" :key="post.id" :post="post" />
					<!-- 分页：总条数超过一页时才显示 -->
					<Page v-if="totalPosts > limit" :total="totalPosts" :current="page" :page-size="limit"
						@on-change="changePage" show-elevator style="margin-top: 16px; text-align: center" />
				</div>
				<div v-else class="empty">还没有发布过帖子</div>
			</TabPane>

			<!-- 我的回复 -->
			<TabPane label="我的回复" name="replies">
				<div v-if="myReplies.length > 0">
					<div v-for="reply in myReplies" :key="reply.id" class="reply-item">
						<div class="reply-content">{{ reply.content }}</div>
						<!-- 回复元信息：回复时间与所回复帖子的链接 -->
						<div class="reply-meta">
							于 {{ formatDate(reply.created_at) }} 回复了帖子
							<router-link
								:to="`/post/${reply.post_id}`">{{ reply.post_title || '#' + reply.post_id }}</router-link>
						</div>
					</div>
				</div>
				<div v-else class="empty">还没有回复过帖子</div>
			</TabPane>

			<!-- 资料修改：目前仅支持修改签名 -->
			<TabPane label="资料修改" name="settings">
				<Form :model="formData" label-position="top">
					<FormItem label="签名">
						<Input v-model="formData.signature" placeholder="一句话介绍自己" />
					</FormItem>
					<FormItem>
						<Button type="primary" @click="updateProfile" :loading="updating">保存修改</Button>
					</FormItem>
				</Form>
			</TabPane>
		</Tabs>
	</div>
</template>

<script setup>
	import {
		ref,
		onMounted
	} from 'vue'
	import {
		useRouter
	} from 'vue-router'
	import {
		useUserStore
	} from '@/stores/user'
	import request from '@/api/request'
	import {
		Message
	} from 'view-ui-plus'
	import PostCard from '@/components/PostCard.vue'

	const router = useRouter()
	const userStore = useUserStore()

	// 当前用户信息
	const user = ref(null)
	// 我的帖子列表
	const myPosts = ref([])
	// 我的回复列表
	const myReplies = ref([])
	// 资料修改表单，目前仅含签名
	const formData = ref({
		signature: ''
	})
	// 资料保存中状态
	const updating = ref(false)

	// 帖子分页状态
	const page = ref(1)
	// 帖子每页条数
	const limit = 10
	// 帖子总条数
	const totalPosts = ref(0)

	// ---------- 初始化 ----------
	onMounted(async () => {
		// 1. 未登录则跳转登录页
		if (!userStore.token) {
			Message.warning('请先登录')
			router.push('/login')
			return
		}

		// 2. 优先使用 store 中已有的用户信息；缺失时再请求接口
		if (userStore.userInfo) {
			user.value = userStore.userInfo
			formData.value.signature = userStore.userInfo.signature || ''
		} else {
			await fetchCurrentUser()
		}

		// 3. 默认加载「我的帖子」列表
		await fetchMyPosts()
	})

	// 获取当前用户信息：成功后同步到 store 与签名表单
	async function fetchCurrentUser() {
		try {
			const res = await request.get('/user/me')
			// 响应拦截器已解包，res 即用户数据
			user.value = res
			userStore.setUserInfo(res)
			formData.value.signature = res.signature || ''
		} catch (err) {
			Message.error('获取用户信息失败')
			router.push('/login')
		}
	}

	// 获取我的帖子：按用户 ID 分页查询，按最新排序
	async function fetchMyPosts(p = 1) {
		try {
			const res = await request.get('/posts', {
				params: {
					user_id: user.value.id,
					page: p,
					limit,
					sort: 'latest'
				}
			})
			// 后端返回 { list, total, page, limit }
			myPosts.value = res.list || []
			totalPosts.value = res.total || 0
			page.value = res.page || p
		} catch (err) {
			Message.error('加载帖子失败')
			myPosts.value = []
		}
	}

	// 获取我的回复：暂不分页，一次性拉取较多数据
	async function fetchMyReplies() {
		try {
			const res = await request.get('/replies', {
				params: {
					user_id: user.value.id,
					page: 1,
					limit: 100
				}
			})
			myReplies.value = res.list || []
		} catch (err) {
			Message.error('加载回复失败')
			myReplies.value = []
		}
	}

	// Tab 切换：对应数据为空时才按需加载，避免重复请求
	function handleTabChange(name) {
		if (name === 'posts' && myPosts.value.length === 0) fetchMyPosts()
		if (name === 'replies' && myReplies.value.length === 0) fetchMyReplies()
	}

	// 帖子分页切换
	function changePage(newPage) {
		fetchMyPosts(newPage)
	}

	// 保存资料修改：成功后同步更新本地 user 与 store 中的签名
	async function updateProfile() {
		updating.value = true
		try {
			const res = await request.put('/user/profile', {
				signature: formData.value.signature
			})
			Message.success('修改成功')
			user.value.signature = formData.value.signature
			userStore.setUserInfo({
				...userStore.userInfo,
				signature: formData.value.signature
			})
		} catch (err) {
			Message.error('修改失败')
		} finally {
			updating.value = false
		}
	}

	// 将日期字符串格式化为 YYYY-MM-DD HH:mm
	function formatDate(dateStr) {
		if (!dateStr) return ''
		const d = new Date(dateStr)
		return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
	}
</script>

<style scoped lang="scss">
	/* 页面容器：限制最大宽度并水平居中 */
	.user-center {
		max-width: 900px;
		margin: 0 auto;
		padding: $spacing-lg;
	}

	/* 用户信息卡片与下方 Tabs 留出间距 */
	.profile-card {
		margin-bottom: $spacing-lg;
	}

	/* 头像与用户信息横向排列并垂直居中 */
	.profile-header {
		display: flex;
		align-items: center;
		gap: $spacing-md;
	}

	.profile-info {
		/* 用户名 */
		h2 {
			margin: 0;
			color: $text-color;
		}

		/* 签名：次要文字色、小号字 */
		.signature {
			color: $text-secondary;
			font-size: $font-size-sm;
			margin-top: $spacing-xs;
		}
	}

	/* 单条回复：底部细线分隔，最后一条去掉 */
	.reply-item {
		padding: $spacing-sm 0;
		border-bottom: 1px solid $border-color;

		&:last-child {
			border-bottom: none;
		}

		/* 回复正文 */
		.reply-content {
			margin-bottom: $spacing-xs;
			color: $text-color;
		}

		/* 回复元信息：时间与帖子链接 */
		.reply-meta {
			font-size: $font-size-sm;
			color: $text-secondary;

			a {
				color: $primary-color;
			}
		}
	}

	/* 列表空状态提示 */
	.empty {
		text-align: center;
		padding: $spacing-xl;
		color: $text-secondary;
	}

	/* 用户信息加载中的占位提示 */
	.loading {
		text-align: center;
		padding: $spacing-xl;
		color: $text-secondary;
	}
</style>