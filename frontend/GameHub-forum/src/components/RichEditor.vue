<template>
	<div class="rich-editor" :class="{ 'is-fullscreen': isFullscreen }">
		<!-- 独立顶行：高度固定，切换源码/全屏时按钮位置保持不变 -->
		<div class="editor-header">
			<div class="header-title">内容编辑</div>
			<div class="quick-actions">
				<button type="button" class="qa-btn" :class="{ active: sourceMode }" @click="toggleSourceMode"
					title="切换 HTML 源码模式">
					&lt;/&gt; {{ sourceMode ? '返回编辑器' : 'HTML 源码' }}
				</button>
				<button type="button" class="qa-btn fullscreen-btn" :class="{ 'exit-btn': isFullscreen }"
					@click="toggleFullscreen" title="全屏（Esc 退出）">
					{{ isFullscreen ? '退出全屏' : '全屏' }}
				</button>
			</div>
		</div>

		<!-- 工具栏行：源码模式下替换为提示条，不影响上方固定顶行 -->
		<Toolbar v-show="!sourceMode" :editor="editorRef" :defaultConfig="toolbarConfig" mode="default"
			class="editor-toolbar" />
		<div v-show="sourceMode" class="source-banner">
			HTML 源码模式 — 粘贴/编辑 HTML 源码，点右上角「返回编辑器」生效
		</div>

		<!-- 编辑区舞台：编辑器常驻，源码 textarea 作为覆盖层叠加其上 -->
		<div class="editor-stage">
			<Editor :defaultConfig="editorConfig" mode="default" style="height: 500px; overflow-y: hidden;"
				@onCreated="handleCreated" @onChange="handleChange" />
			<textarea v-if="sourceMode" v-model="htmlSource" class="source-area" spellcheck="false"
				placeholder="在这里粘贴 HTML 源码..."></textarea>
		</div>
	</div>
</template>

<script setup>
	import {
		shallowRef,
		ref,
		onBeforeUnmount,
		onMounted,
		watch,
		nextTick
	} from 'vue'
	import {
		Editor,
		Toolbar
	} from '@wangeditor/editor-for-vue'

	const props = defineProps({
		modelValue: {
			type: String,
			default: ''
		}
	})
	const emit = defineEmits(['update:modelValue'])

	// 编辑器实例，使用 shallowRef 避免对实例做深层响应式代理
	const editorRef = shallowRef(null)
	// 是否处于全屏状态
	const isFullscreen = ref(false)
	// 是否处于 HTML 源码模式
	const sourceMode = ref(false)
	// 源码模式下的 HTML 文本
	const htmlSource = ref('')

	// 工具栏配置：排除全屏与视频分组，全屏由自定义按钮接管
	const toolbarConfig = {
		excludeKeys: ['fullScreen', 'group-video']
	}

	const editorConfig = {
		placeholder: '请输入内容...',
		MENU_CONF: {}
	}

	// 编辑器创建完成：保存实例并回填初始内容
	function handleCreated(editor) {
		editorRef.value = editor
		if (props.modelValue) {
			editor.setHtml(props.modelValue)
		}
	}

	// 编辑器内容变化：非源码模式下同步给父组件
	function handleChange(editor) {
		if (!sourceMode.value) {
			emit('update:modelValue', editor.getHtml())
		}
	}

	// 切换源码模式：编辑器始终不销毁，仅通过覆盖层遮盖/揭开
	function toggleSourceMode() {
		if (!sourceMode.value) {
			// 进入源码模式：取当前编辑器内容填入 textarea
			htmlSource.value = editorRef.value ? editorRef.value.getHtml() : props.modelValue
			sourceMode.value = true
		} else {
			// 退出源码模式：将 textarea 内容写回编辑器
			sourceMode.value = false
			if (editorRef.value) {
				editorRef.value.setHtml(htmlSource.value || '<p><br></p>')
				emit('update:modelValue', editorRef.value.getHtml())
			}
		}
	}

	// 源码模式下输入实时同步给父组件
	watch(htmlSource, (val) => {
		if (sourceMode.value) {
			emit('update:modelValue', val)
		}
	})

	// 切换全屏：等待 DOM 更新后派发 resize，让编辑器重新计算尺寸
	async function toggleFullscreen() {
		isFullscreen.value = !isFullscreen.value
		await nextTick()
		window.dispatchEvent(new Event('resize'))
	}

	// ESC 退出全屏（textarea 内按 ESC 不触发，避免影响源码编辑）
	function handleKeydown(e) {
		if (e.key === 'Escape' && isFullscreen.value && !(e.target instanceof HTMLTextAreaElement)) {
			isFullscreen.value = false
		}
	}

	onMounted(() => {
		document.addEventListener('keydown', handleKeydown)
	})

	onBeforeUnmount(() => {
		document.removeEventListener('keydown', handleKeydown)
		if (editorRef.value) {
			editorRef.value.destroy()
		}
	})

	// 外部 modelValue 变化时同步到编辑器或源码文本
	watch(
		() => props.modelValue,
		(newVal) => {
			if (sourceMode.value) {
				if (newVal !== htmlSource.value) htmlSource.value = newVal || ''
			} else if (editorRef.value && newVal !== editorRef.value.getHtml()) {
				editorRef.value.setHtml(newVal || '<p><br></p>')
			}
		}
	)
</script>

<style src="@wangeditor/editor/dist/css/style.css"></style>

<style scoped lang="scss">
	.rich-editor {
		border: 1px solid #dcdee2;
		border-radius: 4px;
		z-index: 0;
		display: flex;
		flex-direction: column;

		// 全屏状态：固定铺满视口
		&.is-fullscreen {
			position: fixed;
			inset: 0;
			z-index: 1000;
			background: #fff;
			padding: 8px;

			.editor-stage {
				flex: 1;

				> :first-child {
					height: auto !important;
					flex: 1;
					min-height: 300px;
				}
			}
		}
	}

	// 独立顶行：固定高度，按钮位置与下方内容无关
	.editor-header {
		flex-shrink: 0;
		height: 34px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 10px;
		border-bottom: 1px solid #e8eaec;
		background: #f8f8f9;
		border-radius: 3px 3px 0 0;

		.header-title {
			font-size: 12px;
			color: #808695;
			user-select: none;
		}
	}

	// 快捷按钮组：位于顶行右侧
	.quick-actions {
		display: flex;
		align-items: center;
		gap: 6px;
	}

	.qa-btn {
		border: 1px solid #dcdee2;
		border-radius: 3px;
		background: #fff;
		color: #515a6e;
		padding: 1px 8px;
		font-size: 12px;
		line-height: 18px;
		height: 22px;
		cursor: pointer;
		white-space: nowrap;
		box-sizing: border-box;
		transition: color 0.2s, border-color 0.2s, background-color 0.2s;

		&:hover {
			color: #2d8cf0;
			border-color: #2d8cf0;
		}

		// 源码按钮激活态：使用警告橙，与蓝色全屏按钮区分
		&.active {
			color: #fff;
			background: #ff9900;
			border-color: #ff9900;

			&:hover {
				background: #f90;
				border-color: #f90;
			}
		}

		// 全屏按钮：常态使用主蓝色，形成视觉引导
		&.fullscreen-btn {
			color: #fff;
			background: #2d8cf0;
			border-color: #2d8cf0;

			&:hover {
				background: #47b1ff;
				border-color: #47b1ff;
			}
		}

		// 退出全屏态：使用错误红提示
		&.exit-btn {
			background: #ed4014;
			border-color: #ed4014;

			&:hover {
				background: #f16643;
				border-color: #f16643;
			}
		}
	}

	.editor-toolbar {
		flex-shrink: 0;
		position: relative;
		z-index: 20;
		border-bottom: 1px solid #ccc;

		// 窄窗口下工具栏自动换行，避免滚动、裁剪或遮挡
		:deep(.w-e-bar-show) {
			flex-wrap: wrap;
		}
	}

	// 源码模式提示条：占据工具栏的位置
	.source-banner {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		padding: 8px 12px;
		font-size: 13px;
		color: #ed4014;
		background: #fef0f0;
		border-bottom: 1px solid #fde2e2;
	}

	// 编辑区舞台：作为覆盖层的定位参照
	.editor-stage {
		position: relative;
		flex-shrink: 0;
	}

	// 源码 textarea：绝对定位覆盖在编辑器之上
	.source-area {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		padding: 12px;
		border: none;
		outline: none;
		resize: none;
		box-sizing: border-box;
		background: #fafafa;
		color: #2c3e50;
		font-family: Consolas, Monaco, 'Courier New', monospace;
		font-size: 13px;
		line-height: 1.6;
	}
</style>

<style lang="scss">
	.rich-editor {
		.w-e-text-container {
			min-height: 300px;
		}

		// 编辑器内部弹出层需高于工具栏的 z-index: 20，避免被遮挡：
		//   w-e-select-list —— 代码块语言选择下拉
		//   w-e-drop-panel  —— 其他下拉面板
		//   w-e-modal       —— 图片/链接编辑弹窗
		//   w-e-hover-bar   —— 选中文字后的悬浮工具条
		.w-e-select-list,
		.w-e-drop-panel,
		.w-e-modal,
		.w-e-hover-bar {
			z-index: 30;
		}

		&.is-fullscreen {
			.w-e-text-container {
				height: 100% !important;
			}

			// 全屏容器层级为 1000，弹出层需高于它
			.w-e-select-list,
			.w-e-drop-panel,
			.w-e-modal,
			.w-e-hover-bar {
				z-index: 1130;
			}
		}
	}
</style>