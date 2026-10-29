import {
	createApp
} from 'vue'
import App from './App.vue'
import router from './router'
import {
	createPinia
} from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

// 全局基础样式（含变量、mixin、重置样式等）
import './styles/global.scss'

import ViewUIPlus from 'view-ui-plus'
// View UI Plus 组件库样式
import 'view-ui-plus/dist/styles/viewuiplus.css'

const app = createApp(App)

// 创建 Pinia 实例并启用持久化插件：
// store 中配置 persist 的状态会自动保存到 localStorage，刷新后不丢失
const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

// 注册全局插件：状态管理、路由、UI 组件库
app.use(pinia)
app.use(router)
app.use(ViewUIPlus)

// 挂载应用
app.mount('#app')