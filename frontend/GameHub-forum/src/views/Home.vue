<template>
	<div class="home-page">
		<!-- 轮播图：鼠标悬停暂停自动播放，移开恢复 -->
		<Carousel
			v-model="carouselIndex"
			:autoplay="isAutoplay"
			:autoplay-speed="4000"
			loop
			class="home-carousel"
			@mouseenter="onCarouselEnter"
			@mouseleave="onCarouselLeave">
			<CarouselItem v-for="(item, index) in carouselItems" :key="index">
				<div class="carousel-slide"
					:style="{ background: getCategoryColor(item.categoryId), cursor: item.link ? 'pointer' : 'default' }"
					@click="handleSlideClick(item)">
					<h2>{{ item.title }}</h2>
					<p>{{ item.desc }}</p>
				</div>
			</CarouselItem>
		</Carousel>

		<!-- 版块入口：点击 Tag 进入对应版块 -->
		<section class="section">
			<h2 class="section-title">热门版块</h2>
			<div class="category-row">
				<Tag v-for="cat in categories" :key="cat.id" :color="getTagColor(cat.id)" style="cursor:pointer;"
					@click="goToCategory(cat.id)">
					{{ cat.name }}
				</Tag>
			</div>
		</section>

		<!-- 热帖排行榜：按浏览量取前 5 条 -->
		<section class="section">
			<h2 class="section-title">🔥 热帖排行</h2>
			<Table :columns="hotColumns" :data="hotPosts" stripe no-data-text="暂无热帖">
				<!-- 标题列：点击跳转帖子详情 -->
				<template #title="{ row }">
					<a @click="goToPost(row.id)">{{ row.title }}</a>
				</template>

				<!-- 作者列：缺省时用「用户+ID」兜底 -->
				<template #author="{ row }">
					<span>{{ row.author_name || '用户' + row.user_id }}</span>
				</template>

				<!-- 浏览量列 -->
				<template #views="{ row }">
					<span>{{ row.views }}</span>
				</template>

				<!-- 点赞数列 -->
				<template #likes="{ row }">
					<span>{{ row.likes_count }}</span>
				</template>

				<!-- 发布时间列 -->
				<template #created_at="{ row }">
					<span>{{ formatDate(row.created_at) }}</span>
				</template>
			</Table>
		</section>
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
		getCategories
	} from '@/api/category'
	import {
		getPosts
	} from '@/api/post'

	const router = useRouter()

	// 轮播是否自动播放：悬停时置为 false
	const isAutoplay = ref(true)
	// 当前轮播页索引，与 Carousel 双向绑定
	const carouselIndex = ref(0)

	// 鼠标进入轮播：暂停自动播放
	function onCarouselEnter() {
		isAutoplay.value = false
	}

	// 鼠标离开轮播：恢复自动播放
	function onCarouselLeave() {
		isAutoplay.value = true
	}

	// 版块配色表：轮播图背景与 Tag 颜色统一从此处取色，保证两处一致。
	// name 为 iView Tag 预设色名，value 为对应色值。
	// 蓝色保留给主站主题色（欢迎页），版块不使用蓝色。
	const CATEGORY_COLORS = [
		{ name: 'magenta', value: '#eb2f96' },  // 版块 1：手游专区
		{ name: 'green',   value: '#19be6b' },  // 版块 2：端游专区
		{ name: 'red',     value: '#ed4014' },  // 版块 3：主机游戏
		{ name: 'yellow',  value: '#ff9900' },  // 版块 4：攻略分享
		{ name: 'purple',  value: '#9a66e4' }   // 版块 5：灌水闲聊
	]

	// 按版块 ID 取 Tag 预设色名
	function getTagColor(id) {
		return CATEGORY_COLORS[(id - 1) % CATEGORY_COLORS.length].name
	}

	// 按版块 ID 取色值；欢迎页无版块 ID，回退为主站蓝色
	function getCategoryColor(id) {
		if (!id) return '#2d8cf0'
		return CATEGORY_COLORS[(id - 1) % CATEGORY_COLORS.length].value
	}

	// 轮播图数据：背景色统一由 getCategoryColor 计算
	const carouselItems = ref([{
			title: '欢迎来到 M1_K0 GameHub',
			desc: '发现好游戏，分享你的冒险'
			// 欢迎页不设 categoryId 与 link，点击不跳转，背景为主站蓝色
		},
		{
			title: '📱 手游专区',
			desc: '手机游戏讨论',
			categoryId: 1,
			link: '/category/1'
		},
		{
			title: '🖥️ 端游专区',
			desc: '电脑游戏讨论',
			categoryId: 2,
			link: '/category/2'
		},
		{
			title: '🎮 主机游戏',
			desc: 'PS/Xbox/Switch',
			categoryId: 3,
			link: '/category/3'
		},
		{
			title: '💡 攻略分享',
			desc: '游戏攻略心得',
			categoryId: 4,
			link: '/category/4'
		},
		{
			title: '💬 灌水闲聊',
			desc: '轻松交流',
			categoryId: 5,
			link: '/category/5'
		}
	])

	// 版块列表：兼容接口直接返回数组或包一层 data 的情况
	const categories = ref([])
	onMounted(async () => {
		try {
			const res = await getCategories()
			categories.value = res.data ?? res
		} catch (err) {
			console.error('获取版块失败', err)
		}
	})

	// 热帖表格列配置
	const hotColumns = [{
			title: '标题',
			slot: 'title',
			key: 'title'
		},
		{
			title: '作者',
			slot: 'author',
			key: 'author_name',
			width: 100
		},
		{
			title: '浏览',
			slot: 'views',
			width: 80
		},
		{
			title: '点赞',
			slot: 'likes',
			width: 80
		},
		{
			title: '发布时间',
			slot: 'created_at',
			width: 180
		}
	]

	// 热帖数据：按热度排序取前 5 条
	const hotPosts = ref([])
	onMounted(async () => {
		try {
			const res = await getPosts({
				sort: 'hottest',
				limit: 5
			})
			hotPosts.value = res.list
		} catch (err) {
			console.error('获取热帖失败', err)
		}
	})

	// 跳转到指定版块
	function goToCategory(id) {
		router.push(`/category/${id}`)
	}

	// 跳转到帖子详情
	function goToPost(id) {
		router.push(`/post/${id}`)
	}

	// 将日期字符串格式化为 YYYY-MM-DD HH:mm
	function formatDate(dateStr) {
		if (!dateStr) return ''
		const d = new Date(dateStr)
		return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
	}

	// 轮播项点击：有 link 才跳转
	function handleSlideClick(item) {
		if (item.link) {
			router.push(item.link)
		}
	}
</script>

<style scoped lang="scss">
	/* 页面容器：限制最大宽度并水平居中 */
	.home-page {
		max-width: 1100px;
		margin: 0 auto;
		padding: $spacing-md;
	}

	/* 轮播图与下方区块留出间距 */
	.home-carousel {
		margin-bottom: $spacing-lg;
	}

	/* 轮播单页：内容垂直水平居中，白字显示 */
	.carousel-slide {
		height: 240px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		color: #fff;

		h2 {
			font-size: $font-size-xxl;
			margin-bottom: $spacing-sm;
		}

		/* 描述文字：略降透明度以弱化层级 */
		p {
			font-size: $font-size-lg;
			opacity: 0.9;
		}
	}

	/* 页面区块：与上下内容留出间距 */
	.section {
		margin-bottom: $spacing-lg;
	}

	/* 区块标题 */
	.section-title {
		margin-bottom: $spacing-md;
		color: $text-color;
	}

	/* 版块 Tag 行：自动换行排列 */
	.category-row {
		display: flex;
		flex-wrap: wrap;
		gap: $spacing-sm;
	}
</style>