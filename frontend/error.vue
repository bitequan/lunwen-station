<template>
  <div class="err-page">
    <div class="err-card">
      <div class="err-icon">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9" />
          <line x1="9" y1="9" x2="15" y2="15" />
          <line x1="15" y1="9" x2="9" y2="15" />
        </svg>
      </div>
      <h1 class="err-title">{{ is404 ? '页面不存在' : '服务开小差了' }}</h1>
      <p class="err-desc">
        {{ is404 ? '您访问的地址不存在或已被移除，请返回首页继续浏览' : '访问出错，请稍后重试或返回首页' }}
      </p>
      <p v-if="is404 && seconds > 0" class="err-countdown">
        {{ seconds }} 秒后自动返回首页
      </p>
      <div class="err-actions">
        <button class="err-btn" @click="goHome">立即返回首页</button>
      </div>
      <div v-if="error?.statusCode" class="err-code">{{ error.statusCode }}</div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ error: Object })

const is404 = props.error?.statusCode === 404

// 仅 404 自动倒计时跳转首页，其他错误只提供手动跳转
const seconds = ref(is404 ? 5 : 0)
let timer = null
let redirectTimer = null

function goHome() {
  if (redirectTimer) clearTimeout(redirectTimer)
  if (timer) clearInterval(timer)
  // 根路径分流页会按设备自动进入 /pc 或 /m
  if (typeof window !== 'undefined') {
    window.location.href = '/'
  } else {
    navigateTo('/')
  }
}

onMounted(() => {
  if (!is404) return
  timer = setInterval(() => {
    seconds.value -= 1
    if (seconds.value <= 0) {
      clearInterval(timer)
      redirectTimer = setTimeout(goHome, 200)
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (redirectTimer) clearTimeout(redirectTimer)
})
</script>

<style scoped>
.err-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: linear-gradient(160deg, #f0fdfa 0%, #f8fafc 55%, #f1f5f9 100%);
}
.err-card {
  position: relative;
  width: 100%;
  max-width: 420px;
  text-align: center;
  background: #fff;
  border: 1px solid var(--gray-100, #f1f5f9);
  border-radius: 20px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  padding: 44px 36px 36px;
}
.err-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 88px;
  height: 88px;
  margin: 0 auto 20px;
  border-radius: 50%;
  color: var(--primary-500, #14b8a6);
  background: rgba(20, 184, 166, 0.1);
}
.err-title {
  margin: 0 0 10px;
  font-size: 20px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  line-height: 1.4;
}
.err-desc {
  margin: 0 auto 6px;
  max-width: 300px;
  font-size: 13px;
  line-height: 1.7;
  color: var(--gray-500, #64748b);
}
.err-countdown {
  margin: 14px 0 0;
  font-size: 12px;
  color: var(--gray-400, #94a3b8);
}
.err-actions {
  margin-top: 24px;
}
.err-btn {
  min-width: 150px;
  padding: 11px 28px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6) 0%, var(--primary-600, #0d9488) 100%);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.15s;
}
.err-btn:hover {
  opacity: 0.92;
  transform: translateY(-1px);
}
.err-code {
  position: absolute;
  top: 14px;
  right: 18px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--gray-100, #f1f5f9);
}
</style>
