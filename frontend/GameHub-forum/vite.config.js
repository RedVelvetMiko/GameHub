import {
	defineConfig
} from 'vite'
import vue from '@vitejs/plugin-vue'
import {
	resolve
} from 'path'

export default defineConfig({
	// 使用 Vue 单文件组件支持
	plugins: [vue()],

	resolve: {
		alias: {
			// 路径别名：@ 指向 src 目录，基于当前配置文件所在目录解析
			'@': resolve(import.meta.dirname, 'src')
		}
	},

	css: {
		preprocessorOptions: {
			scss: {
				// 全局注入 SCSS 变量与 mixin，各组件样式可直接使用，无需重复 @use
				additionalData: `
        @use "@/styles/_variables.scss" as *;
        @use "@/styles/_mixins.scss" as *;
      `
			}
		}
	},

	server: {
		// 开发服务器端口
		port: 5173,
		proxy: {
			// 代理 /api 请求到后端，避免开发环境跨域
			'/api': {
				target: 'http://localhost:8080',
				changeOrigin: true,
				// 路径重写规则：
				//   /api/posts        -> /gamehub-api/api/posts.php
				//   /api/posts?id=1   -> /gamehub-api/api/posts.php?id=1
				// 即：去掉 /api 前缀、加上 /gamehub-api/api 前缀、补 .php 后缀，query 原样保留
				rewrite: (path) => {
					const [pathPart, queryPart] = path.split('?')
					const newPath = pathPart.replace(/^\/api/, '/gamehub-api/api') + '.php'
					return queryPart ? `${newPath}?${queryPart}` : newPath
				}
			}
		}
	}
})