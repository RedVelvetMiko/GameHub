<template>
  <div class="category-list-page">
    <h1 class="page-title">游戏版块</h1>

    <!-- 版块卡片网格：点击卡片进入对应版块的帖子列表 -->
    <div class="category-grid">
      <Card
        v-for="cat in categories"
        :key="cat.id"
        class="category-card"
        @click="goToCategory(cat.id)"
      >
        <div class="card-content">
          <Icon :type="getIconType(cat.icon)" size="40" color="#2d8cf0" />
          <h3>{{ cat.name }}</h3>
          <p>{{ cat.description }}</p>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCategories } from '@/api/category'

const router = useRouter()
// 版块列表数据
const categories = ref([])

// 组件挂载后拉取版块列表
onMounted(async () => {
  try {
    categories.value = await getCategories()
    console.log('版块列表数据:', categories.value)
  } catch (err) {
    console.error('获取版块列表失败', err)
  }
})

// 跳转到指定版块的详情页
function goToCategory(id) {
  router.push(`/category/${id}`)
}

// 将后端返回的 icon 名称映射为 iView 图标类型，未匹配时使用默认图标
function getIconType(iconName) {
  const map = {
    'phone-portrait': 'md-phone-portrait',
    'desktop': 'md-desktop',
    'game-controller': 'md-game-controller-b',
    'bulb': 'md-bulb',
    'chatbubbles': 'md-chatbubbles'
  }
  return map[iconName] || 'md-apps'
}
</script>

<style scoped lang="scss">
/* 页面容器：限制最大宽度并水平居中 */
.category-list-page {
  max-width: 900px;
  margin: 0 auto;
  padding: $spacing-lg;
}

/* 页面标题：居中显示 */
.page-title {
  text-align: center;
  margin-bottom: $spacing-lg;
  color: $text-color;
}

/* 版块网格：自适应列数，每列最小 250px，窗口变窄时自动换行 */
.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: $spacing-md;
}

/* 版块卡片：悬停时上浮并加阴影，提示可点击 */
.category-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

/* 卡片内容：图标、版块名、描述纵向居中排列 */
.card-content {
  text-align: center;
  padding: $spacing-md;

  /* 版块名：使用主题色 */
  h3 {
    margin: $spacing-sm 0;
    color: $primary-color;
  }

  /* 版块描述：次要文字色、小号字 */
  p {
    color: $text-secondary;
    font-size: $font-size-sm;
  }
}
</style>