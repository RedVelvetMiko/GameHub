<template>
  <div class="login-page">
    <Card style="width: 400px; margin: 60px auto;">
      <h2 style="text-align:center; margin-bottom: 20px;">登录</h2>

      <Form ref="formRef" :model="formData" :rules="rules">
        <!-- 用户名：仅限制长度上限 -->
        <FormItem prop="username">
          <Input v-model="formData.username" placeholder="用户名" :maxlength="50" />
        </FormItem>

        <!-- 密码：仅限制长度上限 -->
        <FormItem prop="password">
          <Input v-model="formData.password" type="password" placeholder="密码" :maxlength="100" />
        </FormItem>

        <!-- 登录按钮 -->
        <FormItem>
          <Button type="primary" long @click="handleLogin" :loading="loading">登录</Button>
        </FormItem>
      </Form>

      <p style="text-align:center;">
        还没有账号？<router-link to="/register">立即注册</router-link>
      </p>
    </Card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { login } from '@/api/auth'
import { Message } from 'view-ui-plus'
import { LOGIN_USERNAME_MAX, LOGIN_PASSWORD_MAX } from '@/utils/validators'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
// 表单引用，用于调用 validate
const formRef = ref(null)
// 提交中状态，控制登录按钮 loading
const loading = ref(false)

// 登录表单数据
const formData = reactive({
  username: '',
  password: ''
})

// 登录页校验规则：只校验非空与长度上限，不做字符白名单，
// 避免用户因历史账号格式不符合注册规则而无法登录
const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { max: LOGIN_USERNAME_MAX, message: `用户名不超过 ${LOGIN_USERNAME_MAX} 位`, trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { max: LOGIN_PASSWORD_MAX, message: '密码长度异常', trigger: 'blur' }
  ]
}

// 提交登录：校验表单 → 请求登录接口 → 保存令牌与用户信息 → 跳转
async function handleLogin() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  loading.value = true
  try {
    const res = await login({
      username: formData.username.trim(),
      password: formData.password
    })

    // 写入令牌与用户信息到 store（持久化由 store 配置负责）
    userStore.setToken(res.token)
    userStore.setUserInfo(res.user || res)
    Message.success('登录成功')

    // 跳转目标：优先取 query.redirect；仅允许以单个 / 开头的站内路径，
    // 防止 ?redirect=https://xxx 或 //evil.com 之类的开放重定向
    const raw = route.query.redirect || '/'
    const redirect = /^\/(?!\/)/.test(raw) ? raw : '/'
    router.push(redirect)
  } catch (error) {
    Message.error(error.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
/* 登录页顶部留白，使卡片在页面中位置更舒适 */
.login-page {
  padding-top: $spacing-xl;
}
</style>