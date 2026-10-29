<template>
	<div class="create-post-page">
		<Card style="max-width: 800px; margin: 40px auto;">
			<h2 class="page-title">发布新帖</h2>

			<Form ref="formRef" :model="formData" :rules="rules">
				<!-- 版块选择 -->
				<FormItem label="选择版块" prop="category_id">
					<Select v-model="formData.category_id" placeholder="请选择版块">
						<Option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</Option>
					</Select>
				</FormItem>

				<!-- 标题输入 -->
				<FormItem label="标题" prop="title">
					<Input v-model="formData.title" placeholder="请输入帖子标题" />
				</FormItem>

				<!-- 富文本内容编辑 -->
				<FormItem label="内容" prop="content">
					<RichEditor v-model="formData.content" />
				</FormItem>

				<!-- 操作区：左侧灰色取消文字，右侧主按钮发布 -->
				<FormItem>
					<div ref="publishAreaRef" class="publish-area">
						<span class="cancel-link" @click="cancel">取消</span>
						<Button type="primary" @click="handleSubmit" :loading="loading">
							<Icon type="md-send" style="margin-right: 4px;" />
							发布
						</Button>
					</div>
				</FormItem>
			</Form>
		</Card>

		<!-- 跳至发布悬浮按钮：发布按钮不在视口内时显示，点击平滑滚动到操作区 -->
		<transition name="fade">
			<div v-if="showJumpBtn" class="jump-to-publish" @click="scrollToPublish" title="跳至发布">
				<Icon type="md-arrow-down" />
				<span class="jump-text">发布</span>
			</div>
		</transition>
	</div>
</template>

<script setup>
	import {
		reactive,
		ref,
		onMounted,
		onBeforeUnmount
	} from 'vue'
	import {
		useRouter,
		useRoute
	} from 'vue-router'
	import {
		useUserStore
	} from '@/stores/user'
	import {
		getCategories
	} from '@/api/category'
	import {
		createPost
	} from '@/api/post'
	import {
		Message
	} from 'view-ui-plus'
	import RichEditor from '@/components/RichEditor.vue'

	const router = useRouter()
	const route = useRoute()
	const userStore = useUserStore()
	// 表单引用，用于调用 validate
	const formRef = ref(null)
	// 提交中状态，控制发布按钮 loading
	const loading = ref(false)
	// 版块列表
	const categories = ref([])

	// 发布操作区引用，用于判断其是否进入视口
	const publishAreaRef = ref(null)
	// 是否显示“跳至发布”悬浮按钮
	const showJumpBtn = ref(false)
	// 观察发布操作区可见性的 IntersectionObserver 实例
	let observer = null

	onMounted(async () => {
		try {
			categories.value = await getCategories()
		} catch (err) {
			console.error('获取版块列表失败', err)
		}

		// 监听发布操作区是否在视口内：不可见时显示悬浮按钮。
		// 阈值设为 0.1，露出 10% 即视为可见，避免边缘来回闪烁。
		observer = new IntersectionObserver(([entry]) => {
			showJumpBtn.value = !entry.isIntersecting
		}, {
			threshold: 0.1
		})
		if (publishAreaRef.value) {
			observer.observe(publishAreaRef.value)
		}
	})

	onBeforeUnmount(() => {
		// 组件卸载时断开观察器，防止内存泄漏
		if (observer) {
			observer.disconnect()
			observer = null
		}
	})

	// 平滑滚动到发布操作区：将其定位到视口中间偏下位置
	function scrollToPublish() {
		if (publishAreaRef.value) {
			const rect = publishAreaRef.value.getBoundingClientRect()
			const targetY = window.scrollY + rect.top - (window.innerHeight / 2) + rect.height
			window.scrollTo({
				top: targetY,
				behavior: 'smooth'
			})
		}
	}

	// 表单数据
	const formData = reactive({
		category_id: '',
		title: '',
		content: ''
	})

	// 表单校验规则：版块必选、标题 2-50 字、内容必填
	const rules = {
		category_id: [{
			required: true,
			type: 'number',
			message: '请选择版块',
			trigger: 'change'
		}],
		title: [{
				required: true,
				message: '请输入标题',
				trigger: 'blur'
			},
			{
				min: 2,
				max: 50,
				message: '标题长度2-50字',
				trigger: 'blur'
			}
		],
		content: [{
			required: true,
			message: '请输入内容',
			trigger: 'blur'
		}]
	}

	// 提交发帖：先校验表单，再检查登录状态，最后调用创建接口
	async function handleSubmit() {
		const valid = await formRef.value.validate().catch(() => false)
		if (!valid) return

		// 未登录时跳转登录页，并携带当前路径以便登录后回跳
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

		loading.value = true
		try {
			await createPost({
				category_id: formData.category_id,
				title: formData.title,
				content: formData.content
			})
			Message.success('发帖成功')
			router.push('/')
		} catch (err) {
			Message.error(err.message || '发帖失败')
		} finally {
			loading.value = false
		}
	}

	// 取消：返回上一页
	function cancel() {
		router.back()
	}
</script>

<style scoped lang="scss">
	@use 'sass:color';

	/* 页面外层内边距 */
	.create-post-page {
		padding: $spacing-lg;
	}

	/* 页面标题 */
	.page-title {
		margin-bottom: $spacing-md;
		color: $text-color;
	}

	/* 发布操作区：整组右对齐到卡片右下角 */
	.publish-area {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		gap: $spacing-md;
	}

	/* 取消文字：次要文字色，悬停加深提示可点击 */
	.cancel-link {
		color: $text-secondary;
		font-size: $font-size-sm;
		cursor: pointer;
		user-select: none;
		transition: color 0.2s;

		&:hover {
			color: $text-color;
		}
	}

	/* 跳至发布悬浮按钮：固定在右下角，风格与 BackTop 一致 */
	.jump-to-publish {
		position: fixed;
		right: 30px;
		bottom: 50px;
		z-index: 900;
		height: 40px;
		min-width: 40px;
		padding: 0 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 4px;
		border-radius: 4px;
		background-color: $primary-color;
		color: #fff;
		font-size: 14px;
		cursor: pointer;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
		transition: background-color 0.3s;

		/* 悬停时加深主色 */
		&:hover {
			background-color: color.adjust($primary-color, $lightness: -10%);
		}

		.jump-text {
			font-size: 13px;
		}
	}

	/* 悬浮按钮淡入淡出过渡，带轻微上移效果 */
	.fade-enter-active,
	.fade-leave-active {
		transition: opacity 0.25s ease, transform 0.25s ease;
	}

	.fade-enter-from,
	.fade-leave-to {
		opacity: 0;
		transform: translateY(10px);
	}
</style>