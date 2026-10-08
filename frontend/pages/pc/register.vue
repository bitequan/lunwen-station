<template>
  <div class="login-page">
    <div class="login-bg">
      <div class="bg-shape shape-1"></div>
      <div class="bg-shape shape-2"></div>
    </div>

    <NuxtLink to="/pc" class="brand">
      <img :src="'/pc/logo.png'" alt="AI写作助手" />
      <span>AI写作助手</span>
    </NuxtLink>

    <div class="login-card">
      <div class="modal-header">
        <img class="modal-logo" :src="'/pc/logo.png'" alt="AI写作助手" />
        <h2 class="modal-title">完成注册</h2>
        <p class="modal-desc">设置用户名，完成账号注册</p>
      </div>

      <!-- 提示：验证码已发送至 -->
      <div v-if="accountDisplay" class="send-tip">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
        <span>验证码已发送至 <strong>{{ accountDisplay }}</strong></span>
      </div>

      <form class="account-form" @submit.prevent="handleComplete">
        <!-- 验证码 + 重发按钮 -->
        <div class="field">
          <label>验证码</label>
          <div class="code-row">
            <input
              v-model="form.code"
              type="text"
              placeholder="请输入验证码"
              required
              maxlength="8"
              :disabled="loading"
              autocomplete="one-time-code"
            />
            <button
              type="button"
              class="resend-btn"
              :disabled="countdown > 0 || sending"
              @click="resendCode"
            >
              {{ sending ? '发送中...' : (countdown > 0 ? `${countdown}s 后重发` : '获取验证码') }}
            </button>
          </div>
        </div>

        <!-- 用户名 -->
        <div class="field">
          <label>用户名（登录账号）</label>
          <input
            v-model="form.username"
            type="text"
            placeholder="4-20 位字母或数字"
            required
            maxlength="20"
            :disabled="loading"
            @blur="checkUsername"
          />
          <p v-if="usernameHint" class="hint" :class="usernameOk ? 'ok' : 'err'">{{ usernameHint }}</p>
          <p v-else class="hint muted">用户名将作为登录账号，仅支持 4-20 位字母或数字</p>
        </div>

        <div v-if="errorMsg" class="form-error">{{ errorMsg }}</div>

        <button type="submit" class="btn btn-primary submit-btn" :disabled="loading || !usernameOk">
          <span v-if="loading" class="btn-spinner"></span>
          {{ loading ? '注册中...' : '完成注册' }}
        </button>
      </form>

      <div class="modal-footer">
        <p>
          <a href="javascript:void(0)" @click="goBack">返回上一步</a>
        </p>
      </div>
    </div>

    <footer class="login-footer">
      <p>© 2024–2026 郑州比特泉网络科技有限公司 版权所有</p>
      <p><a href="https://beian.miit.gov.cn" target="_blank" rel="noopener">豫ICP备2024046993号-4</a></p>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

definePageMeta({
  layout: 'blank',
})

useSeoMeta({
  title: '完成注册 - AI写作助手',
  description: '设置用户名，完成AI写作助手账号注册。',
})

const route = useRoute()
const auth = useAuth()
const toast = useToast()

const registerToken = ref('')
const ctx = ref({ account_type: '', account: '' })

const form = ref({ code: '', username: '' })
const loading = ref(false)
const sending = ref(false)
const errorMsg = ref('')

// 倒计时
const countdown = ref(0)
let countdownTimer = null

// 用户名实时校验
const usernameOk = ref(false)
const usernameHint = ref('')
let usernameCheckTimer = null

// 显示账号（脱敏：邮箱部分隐藏，手机号中间隐藏）
const accountDisplay = computed(() => {
  const acc = ctx.value.account
  if (!acc) return ''
  if (ctx.value.account_type === 'email') {
    const [name, domain] = acc.split('@')
    if (!domain) return acc
    const maskedName = name.length > 2 ? name.slice(0, 2) + '***' : name + '***'
    return maskedName + '@' + domain
  }
  if (ctx.value.account_type === 'mobile') {
    if (acc.length === 11) return acc.slice(0, 3) + '****' + acc.slice(7)
    return acc
  }
  return acc
})

function startCountdown(sec = 60) {
  countdown.value = sec
  if (countdownTimer) clearInterval(countdownTimer)
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }, 1000)
}

// 发送/重发验证码
async function resendCode() {
  if (sending.value || countdown.value > 0) return
  if (!ctx.value.account_type || !ctx.value.account) {
    errorMsg.value = '注册上下文丢失，请返回重新注册'
    return
  }
  sending.value = true
  errorMsg.value = ''
  try {
    const res = await auth.registerSendCode({
      account_type: ctx.value.account_type,
      account: ctx.value.account,
    })
    if (res.ok) {
      toast.success('验证码已发送')
      startCountdown(60)
    } else {
      errorMsg.value = res.msg || '发送失败，请稍后重试'
    }
  } catch (e) {
    errorMsg.value = e?.message || '发送失败，请稍后重试'
  } finally {
    sending.value = false
  }
}

// 用户名校验（异步，返回 Promise<boolean>）
async function checkUsername() {
  const u = form.value.username?.trim()
  if (!u) {
    usernameOk.value = false
    usernameHint.value = ''
    return false
  }
  if (!/^[a-zA-Z0-9]{4,20}$/.test(u)) {
    usernameOk.value = false
    usernameHint.value = '用户名仅支持 4-20 位字母或数字'
    return false
  }
  try {
    const res = await auth.checkUsername(u)
    if (res.ok && res.data) {
      usernameOk.value = !!res.data.available
      usernameHint.value = res.data.msg || (res.data.available ? '用户名可用' : '用户名已被使用')
      return usernameOk.value
    }
    usernameOk.value = false
    usernameHint.value = '校验失败，请稍后重试'
    return false
  } catch (e) {
    usernameOk.value = false
    usernameHint.value = '校验失败，请稍后重试'
    return false
  }
}

// 用户名输入时实时校验（防抖）
watch(() => form.value.username, () => {
  usernameOk.value = false
  if (usernameCheckTimer) clearTimeout(usernameCheckTimer)
  const u = form.value.username?.trim()
  if (!u) {
    usernameHint.value = ''
    return
  }
  if (!/^[a-zA-Z0-9]{4,20}$/.test(u)) {
    usernameHint.value = '用户名仅支持 4-20 位字母或数字'
    return
  }
  usernameCheckTimer = setTimeout(() => {
    checkUsername()
  }, 500)
})

// 完成注册
async function handleComplete() {
  if (loading.value) return
  errorMsg.value = ''
  if (!form.value.code?.trim()) {
    errorMsg.value = '请输入验证码'
    return
  }
  if (!form.value.username?.trim()) {
    errorMsg.value = '请输入用户名'
    return
  }
  if (!/^[a-zA-Z0-9]{4,20}$/.test(form.value.username.trim())) {
    errorMsg.value = '用户名仅支持 4-20 位字母或数字'
    return
  }
  // 提交时若未校验通过，同步校验一次
  if (!usernameOk.value) {
    const ok = await checkUsername()
    if (!ok) {
      errorMsg.value = usernameHint.value || '用户名不可用'
      return
    }
  }

  loading.value = true
  try {
    const res = await auth.registerComplete({
      register_token: registerToken.value,
      code: form.value.code,
      username: form.value.username,
    })
    if (res.ok) {
      toast.success('注册成功，已自动登录')
      if (import.meta.client) {
        sessionStorage.removeItem('register_context')
      }
      window.location.href = '/pc/user'
    } else {
      errorMsg.value = res.msg || '注册失败'
    }
  } catch (e) {
    errorMsg.value = e?.message || '注册失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

function goBack() {
  if (import.meta.client) {
    sessionStorage.removeItem('register_context')
    useLoginModal().open()
  }
}

onMounted(() => {
  const token = route.query.register_token
  if (!token) {
    toast.error('注册链接无效')
    if (import.meta.client) {
      useLoginModal().open()
    }
    return
  }
  registerToken.value = String(token)

  // 从 sessionStorage 读上下文
  if (import.meta.client) {
    try {
      const raw = sessionStorage.getItem('register_context')
      if (raw) {
        ctx.value = JSON.parse(raw)
      }
    } catch (e) {}
  }

  // 自动发送一次验证码
  if (ctx.value.account_type && ctx.value.account) {
    resendCode()
  } else {
    // 上下文丢失，提示用户
    errorMsg.value = '注册上下文丢失，请返回重新注册'
  }
})

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
  if (usernameCheckTimer) clearTimeout(usernameCheckTimer)
})
</script>

<style scoped>
.login-page {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  background: #f8fafc;
  overflow: hidden;
}

.login-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.45;
}

.shape-1 {
  width: 480px;
  height: 480px;
  top: -120px;
  right: -120px;
  background: rgba(20, 184, 166, 0.25);
}

.shape-2 {
  width: 400px;
  height: 400px;
  bottom: -80px;
  left: -80px;
  background: rgba(249, 115, 22, 0.18);
}

.brand {
  position: absolute;
  top: 28px;
  left: 32px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-800);
}

.brand img {
  width: 38px;
  height: 38px;
}

.login-card {
  position: relative;
  width: 100%;
  max-width: 420px;
  background: var(--white);
  border-radius: var(--radius-xl);
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
  padding: 40px;
  z-index: 1;
}

.modal-header {
  text-align: center;
  margin-bottom: 24px;
}

.modal-logo {
  width: 56px;
  height: 56px;
  margin: 0 auto 14px;
}

.modal-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 6px;
}

.modal-desc {
  font-size: 14px;
  color: var(--gray-500);
}

.send-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  margin-bottom: 20px;
  border-radius: var(--radius-md);
  background: rgba(20, 184, 166, 0.06);
  border: 1px solid rgba(20, 184, 166, 0.2);
  color: var(--primary-600);
  font-size: 13px;
  line-height: 1.5;
}

.send-tip strong {
  font-weight: 700;
  color: var(--dark-800);
}

.account-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
}

.field input {
  padding: 12px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-200);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.field input:focus {
  border-color: var(--primary-500);
}

/* 验证码 + 重发按钮 */
.code-row {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.code-row input {
  flex: 1;
}

.resend-btn {
  flex-shrink: 0;
  padding: 0 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--primary-500);
  background: var(--primary-50, #ecfdf5);
  color: var(--primary-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.resend-btn:hover:not(:disabled) {
  background: var(--primary-500);
  color: var(--white);
}

.resend-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.hint {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 1.5;
}

.hint.muted {
  color: var(--gray-400);
}

.hint.ok {
  color: #059669;
}

.hint.err {
  color: #dc2626;
}

.submit-btn {
  width: 100%;
  margin-top: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  border-top-color: #fff;
  border-radius: 50%;
  animation: btn-spin 0.6s linear infinite;
}

@keyframes btn-spin {
  to { transform: rotate(360deg); }
}

.form-error {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-size: 13px;
  line-height: 1.5;
}

.modal-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 13px;
  color: var(--gray-500);
}

.modal-footer a {
  color: var(--primary-600);
  font-weight: 600;
  cursor: pointer;
}

.login-footer {
  position: absolute;
  bottom: 24px;
  text-align: center;
  font-size: 12px;
  color: var(--gray-400);
  line-height: 1.8;
}

.login-footer a {
  color: var(--gray-400);
}

.modal-footer a:hover {
  color: var(--primary-600);
}

@media (max-width: 480px) {
  .login-card {
    padding: 28px 24px;
  }

  .brand {
    left: 24px;
    font-size: 18px;
  }

  .brand img {
    width: 32px;
    height: 32px;
  }
}
</style>
