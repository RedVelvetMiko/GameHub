<template>
  <div class="register-page">
    <Card style="width: 400px; margin: 60px auto;">
      <h2 style="text-align:center; margin-bottom: 20px;">注册</h2>

      <Form ref="formRef" :model="formData" :rules="rules">
        <!-- 用户名：长度上限与后端字段一致 -->
        <FormItem prop="username">
          <Input v-model="formData.username" placeholder="用户名" :maxlength="16" />
        </FormItem>

        <!-- 密码 -->
        <FormItem prop="password">
          <Input v-model="formData.password" type="password" placeholder="密码" :maxlength="30" />
        </FormItem>

        <!-- 确认密码：需与上方密码一致 -->
        <FormItem prop="confirmPassword">
          <Input v-model="formData.confirmPassword" type="password" placeholder="确认密码" :maxlength="30" />
        </FormItem>

        <!-- 注册按钮 -->
        <FormItem>
          <Button type="primary" long @click="handleRegister" :loading="loading">注册</Button>
        </FormItem>
      </Form>

      <p style="text-align:center;">
        已有账号？<router-link to="/login">立即登录</router-link>
      </p>
    </Card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { register } from '@/api/auth'
import { Message } from 'view-ui-plus'
import { USERNAME_RE, USERNAME_MSG, PASSWORD_RE, PASSWORD_MSG } from '@/utils/validators'

const router = useRouter()
// 表单引用，用于调用 validate
const formRef = ref(null)
// 提交中状态，控制注册按钮 loading
const loading = ref(false)

// 注册表单数据
const formData = reactive({
  username: '',
  password: '',
  confirmPassword: ''
})

// 注册校验规则：
//   用户名 —— 必填、2-16 位、且符合 USERNAME_RE（中文/字母/数字/下划线/连字符）
//   密码   —— 必填、符合 PASSWORD_RE（6-30 位非空白字符）
//   确认密码 —— 必填、且与 password 完全一致
// 正则与提示文案统一来自 utils/validators，保证与后端校验一致
const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 2, max: 16, message: '用户名长度 2-16 位', trigger: 'blur' },
    { pattern: USERNAME_RE, message: USERNAME_MSG, trigger: ['blur', 'change'] }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { pattern: PASSWORD_RE, message: PASSWORD_MSG, trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, message: '请确认密码', trigger: 'blur' },
    // 自定义校验器：与 password 字段实时比对
    {
      validator: (rule, value, callback) => {
        if (value !== formData.password) {
          callback(new Error('两次密码不一致'))
        } else {
          callback()
        }
      },
      trigger: ['blur', 'change']
    }
  ]
}

// 提交注册：校验表单 → 调用注册接口 → 成功后跳转登录页
async function handleRegister() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  loading.value = true
  try {
    await register({
      username: formData.username.trim(),
      password: formData.password
    })
    Message.success('注册成功，请登录')
    router.push('/login')
  } catch (error) {
    Message.error(error.message || '注册失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
/* 注册页顶部留白，使卡片在页面中位置更舒适 */
.register-page {
  padding-top: $spacing-xl;
}
</style>