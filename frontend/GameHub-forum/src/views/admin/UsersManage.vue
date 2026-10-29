<template>
	<div class="users-manage">
		<h2 class="page-title">用户管理</h2>

		<!-- 工具栏：按用户名搜索用户 -->
		<div class="toolbar">
			<Input v-model="keyword" placeholder="搜索用户名..." search clearable style="width: 280px"
				@on-search="loadUsers(1)" @on-clear="loadUsers(1)" />
		</div>

		<!-- 用户表格 -->
		<Table :columns="columns" :data="userList" :loading="loading" stripe border>
			<!-- 头像列：无头像时显示默认空头像 -->
			<template #avatar="{ row }">
				<Avatar :src="row.avatar || ''" size="small" />
			</template>

			<!-- 角色列：管理员红色 Tag，普通用户蓝色 Tag -->
			<template #role="{ row }">
				<Tag :color="row.role === 'admin' ? 'red' : 'blue'">{{ row.role }}</Tag>
			</template>

			<!-- 注册时间列：日期与时间分两行显示（插槽名用短横线以匹配列配置） -->
			<template #created-at="{ row }">
				<div class="time-cell">
					<div>{{ fmtDate(row.created_at) }}</div>
					<div class="time">{{ fmtTime(row.created_at) }}</div>
				</div>
			</template>

			<!-- 操作列：打开修改角色弹窗 -->
			<template #action="{ row }">
				<Button size="small" type="warning" @click="editRole(row)">修改权限</Button>
			</template>
		</Table>

		<!-- 分页：切换页码时按新页码加载数据 -->
		<Page :total="total" :current="page" :page-size="limit" show-total show-elevator @on-change="loadUsers"
			style="margin-top: 16px; text-align: right;" />

		<!-- 修改角色弹窗：单选管理员或普通用户 -->
		<Modal v-model="showRoleModal" title="修改角色" :mask-closable="false" width="380">
			<RadioGroup v-model="editForm.role" vertical>
				<Radio label="admin">
					<span class="txt-admin">管理员</span>
				</Radio>
				<Radio label="user">
					<span class="txt-user">普通用户</span>
				</Radio>
			</RadioGroup>
			<template #footer>
				<Button @click="showRoleModal = false">取消</Button>
				<Button type="primary" :loading="saving" @click="confirmEditRole">确定</Button>
			</template>
		</Modal>
	</div>
</template>

<script setup>
	import {
		ref,
		onMounted
	} from 'vue'
	import {
		Message
	} from 'view-ui-plus'
	import request from '@/api/request'
	import { useRouter } from 'vue-router'
	import { useUserStore } from '@/stores/user'

	const router = useRouter()
	const userStore = useUserStore()

	// 表格加载状态
	const loading = ref(false)
	// 当前页用户列表
	const userList = ref([])
	// 总条数
	const total = ref(0)
	// 当前页码
	const page = ref(1)
	// 每页条数
	const limit = ref(20)
	// 搜索关键词
	const keyword = ref('')

	// 从日期时间字符串中取出日期部分，缺失时返回 '-'
	function fmtDate(v) {
		return (v || '').split(' ')[0] || '-'
	}

	// 从日期时间字符串中取出时间部分，缺失时返回空字符串
	function fmtTime(v) {
		return (v || '').split(' ')[1] || ''
	}

	// 表格列配置：注册时间插槽名需与模板中的 #created-at 对应
	const columns = [{
			title: 'ID',
			key: 'id',
			width: 78
		},
		{
			title: '头像',
			slot: 'avatar',
			width: 84,
			align: 'center'
		},
		{
			title: '用户名',
			key: 'username',
			minWidth: 128
		},
		{
			title: '签名',
			key: 'signature',
			ellipsis: true,
			minWidth: 152
		},
		{
			title: '角色',
			slot: 'role',
			width: 96
		},
		{
			title: '注册时间',
			slot: 'created-at',
			width: 146,
			align: 'center'
		},
		{
			title: '操作',
			slot: 'action',
			width: 104,
			fixed: 'right'
		}
	]

	// 加载用户列表：传入页码则更新当前页，随后按页码、每页条数和关键词请求数据
	async function loadUsers(p) {
		if (p) page.value = p
		loading.value = true
		try {
			const data = await request.get('/admin/users', {
				params: {
					page: page.value,
					limit: limit.value,
					keyword: keyword.value
				}
			})
			// 响应拦截器已返回后端的 data 字段，这里直接使用
			userList.value = data.list
			total.value = data.total
		} catch (err) {
			Message.error('网络错误')
		} finally {
			loading.value = false
		}
	}

	// 修改角色弹窗的显示状态
	const showRoleModal = ref(false)
	// 弹窗表单：当前操作用户 ID 与目标角色
	const editForm = ref({
		userId: null,
		role: 'user'
	})
	// 提交保存中的状态，用于按钮 loading
	const saving = ref(false)

	// 打开角色修改弹窗，回填当前行用户 ID 与角色
	function editRole(row) {
		editForm.value = {
			userId: row.id,
			role: row.role
		}
		showRoleModal.value = true
	}

	// 提交角色修改
	async function confirmEditRole() {
		saving.value = true
		try {
			await request.put(`/admin/user_update?id=${editForm.value.userId}`, {
				role: editForm.value.role
			})
			Message.success('角色已更新')
			showRoleModal.value = false

			// 若修改的是当前登录用户自己：同步本地 store 的角色，
			// 并跳回首页，由路由守卫按新角色拦截后续后台访问
			if (editForm.value.userId === userStore.userInfo?.id) {
				userStore.userInfo.role = editForm.value.role
				router.push('/')
			} else {
				// 修改的是其他用户，刷新当前页列表即可
				loadUsers(page.value)
			}
		} catch (err) {
			Message.error('操作失败')
		} finally {
			saving.value = false
		}
	}

	// 组件挂载后加载第一页数据
	onMounted(() => loadUsers(1))
</script>

<style scoped lang="scss">
	/* 页面容器：白底、圆角 */
	.users-manage {
		background: #fff;
		padding: 18px;
		border-radius: 4px;
	}

	/* 页面标题 */
	.page-title {
		font-size: 18px;
		margin-bottom: 16px;
		font-weight: 600;
	}

	/* 工具栏与表格之间留出间距 */
	.toolbar {
		margin-bottom: 14px;
	}

	/* 注册时间单元格：日期居中、时间用更浅的小号字 */
	.time-cell {
		text-align: center;

		>div {
			white-space: nowrap;
		}

		.time {
			color: #808695;
			font-size: 11px;
		}
	}

	/* 角色单选文字配色：普通用户蓝色，管理员红色 */
	.txt-user  { color: #2d8cf0; }
	.txt-admin { color: #ed4014; }
</style>