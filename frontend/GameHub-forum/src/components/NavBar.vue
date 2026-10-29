<template>
	<div class="navbar">
		<div class="navbar-container">
			<!-- 站点 Logo，点击返回首页 -->
			<router-link to="/" class="navbar-logo">
				<span class="logo-text">M1_K0 GameHub</span>
			</router-link>

			<!-- 主导航菜单 -->
			<div class="navbar-menu">
				<router-link to="/" class="menu-item">首页</router-link>
				<router-link to="/categories" class="menu-item">版块</router-link>
			</div>

			<!-- 右侧操作区：搜索、登录状态相关入口 -->
			<div class="navbar-right">
				<!-- 搜索框：回车或点击搜索图标均触发搜索 -->
				<Input v-model="searchKeyword" placeholder="搜索帖子..." size="small" search clearable
					style="width: 180px; margin-right: 10px;" @on-search="handleSearch" @on-enter="handleSearch" />

				<!-- 未登录状态：显示登录/注册按钮 -->
				<template v-if="!userStore.token">
					<router-link to="/login">
						<Button size="small">登录</Button>
					</router-link>
					<router-link to="/register" style="margin-left: 8px;">
						<Button type="primary" size="small">注册</Button>
					</router-link>
				</template>

				<!-- 已登录状态：发帖按钮 + 用户下拉菜单 -->
				<template v-else>
					<Button type="primary" size="small" @click="$router.push('/post/create')"
						style="margin-right: 8px;">
						<Icon type="md-create" /> 发帖
					</Button>

					<!-- 用户下拉菜单：管理后台（仅管理员）、个人中心、退出登录 -->
					<Dropdown trigger="click" placement="bottom-end" transfer>
						<a href="javascript:void(0)" class="user-trigger">
							{{ userStore.userInfo?.username || '用户' }}
							<Icon type="ios-arrow-down" />
						</a>
						<template #list>
							<DropdownMenu>
								<!-- 仅管理员可见，下方带分割线 -->
								<DropdownItem v-if="userStore.userInfo?.role === 'admin'" @click="goToAdmin"
									class="admin-divider">
									<Icon type="md-settings" /> 管理后台
								</DropdownItem>
								<DropdownItem @click="goToUserCenter">
									<Icon type="md-person" /> 个人中心
								</DropdownItem>
								<DropdownItem @click="handleLogout">
									<Icon type="md-log-out" /> 退出登录
								</DropdownItem>
							</DropdownMenu>
						</template>
					</Dropdown>
				</template>
			</div>
		</div>
	</div>
</template>

<script setup>
	import {
		ref
	} from 'vue'
	import {
		useUserStore
	} from '@/stores/user'
	import {
		useRouter
	} from 'vue-router'
	import {
		Input,
		Button,
		Icon,
		Dropdown,
		DropdownMenu,
		DropdownItem
	} from 'view-ui-plus'

	const userStore = useUserStore()
	const router = useRouter()

	// 搜索关键词
	const searchKeyword = ref('')

	// 执行搜索：关键词非空时跳转到搜索页，并通过 query 传递关键词
	function handleSearch() {
		const keyword = searchKeyword.value
		if (!keyword || !keyword.trim()) return
		router.push({
			path: '/search',
			query: {
				q: keyword.trim()
			}
		})
	}

	// 跳转到当前登录用户的个人中心
	function goToUserCenter() {
		if (userStore.userInfo?.id) {
			router.push(`/user/${userStore.userInfo.id}`)
		}
	}

	// 退出登录并返回首页
	function handleLogout() {
		userStore.logout()
		router.push('/')
	}

	// 跳转到管理后台
	function goToAdmin() {
		router.push('/admin')
	}
</script>

<style scoped lang="scss">
	/* 导航栏：白底、吸顶固定，滚动时保持可见 */
	.navbar {
		background: #fff;
		border-bottom: 1px solid $border-color;
		position: sticky;
		top: 0;
		z-index: 100;
	}

	/* 导航栏内容容器：限制最大宽度并水平居中 */
	.navbar-container {
		max-width: 1200px;
		margin: 0 auto;
		display: flex;
		align-items: center;
		height: 60px;
		padding: 0 $spacing-md;
	}

	/* Logo 文字样式 */
	.navbar-logo {
		font-size: $font-size-xl;
		font-weight: bold;
		color: $primary-color;
		margin-right: $spacing-lg;
	}

	/* 主导航菜单：占据中间弹性空间 */
	.navbar-menu {
		flex: 1;
		display: flex;
		gap: $spacing-md;
	}

	/* 单个菜单项：悬停变主题色 */
	.menu-item {
		color: $text-color;

		&:hover {
			color: $primary-color;
		}
	}

	/* 右侧操作区：横向排列并保持垂直居中 */
	.navbar-right {
		display: flex;
		align-items: center;
		gap: $spacing-sm;
	}

	/* 用户名触发器：超长省略，悬停变主题色 */
	.user-trigger {
		max-width: 160px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		display: inline-flex;
		align-items: center;
		gap: 4px;
		cursor: pointer;
		color: $text-color;

		&:hover {
			color: $primary-color;
		}
	}

	/* 管理后台菜单项下方的分割线，用于与普通项区分 */
	:deep(.admin-divider) {
		border-bottom: 1px solid #e8eaec;
	}
</style>