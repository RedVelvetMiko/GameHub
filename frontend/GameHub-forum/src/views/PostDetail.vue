<template>
	<div class="post-detail-page">
		<!-- 帖子内容卡片：数据就绪后显示，否则显示加载提示 -->
		<Card class="post-card" v-if="post">
			<div class="post-header">
				<h1>{{ post.title }}</h1>

				<!-- 元信息：作者、发布时间、浏览量、点赞按钮 -->
				<div class="post-meta">
					<span>作者：{{ post.author_name }}</span>
					<span>{{ formatDate(post.created_at) }}</span>
					<span>浏览 {{ post.views }}</span>
					<span>
						<Button :type="isLiked ? 'primary' : 'default'" size="small" @click="toggleLike"
							:loading="likeLoading">
							<Icon :type="isLiked ? 'md-heart' : 'ios-heart-outline'" />
							{{ post.likes_count }}
						</Button>
					</span>
				</div>
			</div>

			<!-- 帖子正文：富文本 HTML 渲染 -->
			<div class="post-body" v-html="post.content"></div>
		</Card>
		<div v-else class="loading">加载中...</div>

		<!-- 回复区域 -->
		<div class="replies-section">
			<h2>回复 ({{ replyTree.length }})</h2>

			<!-- 回复输入框 -->
			<div class="reply-form">
				<!-- 回复某条评论时显示目标提示，可取消 -->
				<div v-if="replyTo" class="reply-target">
					正在回复 @{{ replyTo.username }}：
					<Button size="small" @click="cancelReply">取消</Button>
				</div>
				<Input v-model="replyContent" type="textarea" :rows="3" placeholder="写下你的回复..." />
				<div class="reply-actions">
					<Button type="primary" @click="submitReply" :loading="submitting">发表回复</Button>
				</div>
			</div>

			<!-- 回复列表：递归组件渲染树形结构 -->
			<div class="reply-list">
				<div v-if="replyTree.length === 0" class="empty-replies">
					暂无回复，来抢沙发吧！
				</div>
				<ReplyItem v-for="reply in replyTree" :key="reply.id" :reply="reply" @reply-to="setReplyTo" />
			</div>
		</div>

		<!-- 回到顶部按钮：滚动超过 400px 后出现 -->
		<BackTop :height="400" :bottom="50" :right="30">
			<div class="back-top-inner">
				<Icon type="md-arrow-up" />
			</div>
		</BackTop>
	</div>
</template>

<script setup>
	import {
		ref,
		computed,
		onMounted
	} from 'vue'
	import {
		useRoute,
		useRouter
	} from 'vue-router'
	import {
		useUserStore
	} from '@/stores/user'
	import request from '@/api/request'
	import {
		Message
	} from 'view-ui-plus'
	import ReplyItem from '@/components/ReplyItem.vue'

	const route = useRoute()
	const router = useRouter()
	const userStore = useUserStore()

	// 当前帖子 ID
	const postId = ref(null)
	// 帖子详情数据
	const post = ref(null)
	// 平铺的回复列表
	const replies = ref([])
	// 回复输入框内容
	const replyContent = ref('')
	// 回复提交中状态
	const submitting = ref(false)
	// 点赞请求中状态
	const likeLoading = ref(false)
	// 当前用户是否已点赞
	const isLiked = ref(false)
	// 当前回复目标 { id, username }，为 null 表示发表顶层回复
	const replyTo = ref(null)

	// 将平铺回复列表构建为树：parent_id 为 0/null 视为顶层，
	// 其余按 parent_id 挂到对应父节点的 children 下
	const replyTree = computed(() => {
		const map = {}
		const tree = []
		// 先为每条回复创建带 children 的节点副本
		replies.value.forEach(r => {
			map[r.id] = {
				...r,
				children: []
			}
		})
		// 再按 parent_id 组装父子关系
		replies.value.forEach(r => {
			if (r.parent_id === 0 || r.parent_id === null) {
				tree.push(map[r.id])
			} else if (map[r.parent_id]) {
				map[r.parent_id].children.push(map[r.id])
			}
		})
		return tree
	})

	// 获取帖子详情：成功则记录点赞状态并继续拉取回复，失败则返回首页
	async function fetchPost() {
		try {
			const res = await request.get('/posts', {
				params: {
					id: postId.value
				}
			})
			// 响应拦截器已拆包，res 即帖子对象本身
			if (res && res.id) {
				post.value = res
				isLiked.value = !!res.is_liked
				await fetchReplies()
			} else {
				Message.error('帖子不存在')
				router.push('/')
			}
		} catch (err) {
			Message.error('加载失败')
			router.push('/')
		}
	}

	// 获取该帖子的回复列表，接口返回 { list, total, page, limit }，此处仅取 list
	async function fetchReplies() {
		try {
			const res = await request.get('/replies', {
				params: {
					post_id: postId.value
				}
			})
			replies.value = (res && res.list) || []
		} catch (err) {
			console.error('获取回复失败', err)
			replies.value = []
		}
	}

	// 点赞/取消点赞：未登录时跳转登录并携带回跳地址；
	// 成功后按后端返回的 liked 与 likes_count 更新状态
	async function toggleLike() {
		if (!userStore.token) {
			Message.warning('请先登录')
			router.push({
				name: 'Login',
				query: {
					redirect: route.fullPath
				}
			})
			return
		}
		likeLoading.value = true
		try {
			const res = await request.post('/likes', {
				post_id: postId.value
			})
			isLiked.value = res.liked
			post.value.likes_count = res.likes_count
		} catch (err) {
			Message.error('操作失败')
		} finally {
			likeLoading.value = false
		}
	}

	// 设置回复目标：记录被回复评论的 ID 与作者名
	function setReplyTo(reply) {
		replyTo.value = {
			id: reply.id,
			username: reply.author_name || `用户${reply.author_name}`
		}
	}

	// 取消回复目标，恢复为发表顶层回复
	function cancelReply() {
		replyTo.value = null
	}

	// 提交回复：内容非空且已登录才发送；
	// parent_id 取回复目标 ID，无目标则为 0（顶层回复）；
	// 用户身份由 token 决定，无需传 user_id
	async function submitReply() {
		if (!replyContent.value.trim()) {
			Message.warning('请输入回复内容')
			return
		}
		if (!userStore.token) {
			Message.warning('请先登录')
			router.push({
				name: 'Login',
				query: {
					redirect: route.fullPath
				}
			})
			return
		}
		submitting.value = true
		try {
			await request.post('/replies', {
				post_id: postId.value,
				content: replyContent.value.trim(),
				parent_id: replyTo.value ? replyTo.value.id : 0
			})

			Message.success('回复成功')
			// 成功后清空输入框与回复目标，并重新拉取回复列表
			replyContent.value = ''
			replyTo.value = null
			await fetchReplies()
		} catch (err) {
			Message.error('回复失败')
		} finally {
			submitting.value = false
		}
	}

	// 将日期字符串格式化为 YYYY-MM-DD HH:mm
	function formatDate(dateStr) {
		if (!dateStr) return ''
		const d = new Date(dateStr)
		return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
	}

	// 组件挂载：从路由参数取帖子 ID 并加载详情
	onMounted(async () => {
		postId.value = Number(route.params.id)
		await fetchPost()
	})
</script>

<style scoped lang="scss">
	@use 'sass:color';

	/* 页面容器：限制最大宽度并水平居中 */
	.post-detail-page {
		max-width: 800px;
		margin: 0 auto;
		padding: $spacing-lg;
	}

	/* 帖子卡片与下方回复区域留出间距 */
	.post-card {
		margin-bottom: $spacing-lg;
	}

	.post-header {
		/* 帖子标题 */
		h1 {
			margin: 0 0 $spacing-sm;
			color: $text-color;
		}

		/* 元信息：横向排列，次要文字色、小号字 */
		.post-meta {
			display: flex;
			gap: $spacing-md;
			color: $text-secondary;
			font-size: $font-size-sm;
			align-items: center;
		}
	}

	/* 帖子正文：富文本 HTML 渲染区域 */
	.post-body {
		margin-top: $spacing-md;
		line-height: 1.8;
		color: $text-color;
		word-break: break-word;

		/* 富文本由 v-html 渲染，scoped 样式选不中内部节点，需用 :deep() */
		:deep(img) {
			max-width: 100%;
			height: auto;
		}

		/* 超长链接强制换行，避免撑破容器 */
		:deep(a) {
			word-break: break-all;
		}

		/* 代码块：容器内横向滚动，超长行自动折行 */
		:deep(pre) {
			background: #f5f7fa;
			border: 1px solid #e8eaec;
			border-radius: 4px;
			padding: 12px;
			margin: 12px 0;
			overflow-x: auto;
			max-width: 100%;

			code {
				background: transparent;
				padding: 0;
				/* 用 pre-wrap + break-all，连续无空格长串也能折行 */
				white-space: pre-wrap;
				word-break: break-all;
				font-family: Consolas, Monaco, 'Courier New', monospace;
				font-size: 13px;
			}
		}

		/* 行内代码 */
		:deep(code) {
			background: #f5f7fa;
			border-radius: 3px;
			padding: 2px 5px;
			font-family: Consolas, Monaco, 'Courier New', monospace;
			font-size: 90%;
		}

		/* 引用块：左侧竖线 + 浅灰背景 */
		:deep(blockquote) {
			margin: 12px 0;
			padding: 8px 16px;
			border-left: 4px solid #dcdee2;
			background: #f8f8f8;
			color: #808695;
		}

		/* 表格：宽表格改为块级并横向滚动，避免溢出 */
		:deep(table) {
			border-collapse: collapse;
			max-width: 100%;
			display: block;
			overflow-x: auto;

			th,
			td {
				border: 1px solid #e8eaec;
				padding: 6px 12px;
			}

			th {
				background: #f5f7fa;
			}
		}
	}

	.replies-section {
		/* 回复区标题 */
		h2 {
			margin-bottom: $spacing-md;
		}

		/* 回复表单区域 */
		.reply-form {
			margin-bottom: $spacing-lg;

			/* 回复目标提示：主题色小字 */
			.reply-target {
				margin-bottom: $spacing-sm;
				color: $primary-color;
				font-size: $font-size-sm;
			}

			/* 提交按钮右对齐 */
			.reply-actions {
				margin-top: $spacing-sm;
				text-align: right;
			}
		}
	}

	/* 帖子加载中的占位提示 */
	.loading {
		text-align: center;
		padding: $spacing-xl;
		color: $text-secondary;
	}

	/* 回到顶部按钮：主色方块，悬停加深 */
	.back-top-inner {
		height: 40px;
		width: 40px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 4px;
		background-color: $primary-color;
		color: #fff;
		font-size: 20px;
		cursor: pointer;
		transition: background-color 0.3s;

		&:hover {
			background-color: color.adjust($primary-color, $lightness: -10%);
		}
	}

	/* 无回复时的空状态提示 */
	.empty-replies {
		text-align: center;
		padding: $spacing-lg;
		color: $text-secondary;
		font-size: $font-size-sm;
	}
</style>