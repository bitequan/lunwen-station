<template>
  <div class="root-entry">
    <div class="root-entry__spinner"></div>
    <p>正在进入…</p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

// 根路径：按设备分流（移动端 → /m，桌面端 → /pc）
// 实际自适应切换由 middleware/m-redirect.global.js 统一处理，此处兜底直接访问 / 的情况
useHead({ title: 'AI写作助手' })

onMounted(() => {
  const ua = navigator.userAgent || ''
  const mobileUA = /Android|iPhone|iPad|iPod|IEMobile|Opera Mini|Mobile/i.test(ua)
  let touchWide = false
  try { touchWide = navigator.maxTouchPoints > 1 && Math.min(window.screen.width, window.screen.height) < 768 } catch (e) {}
  if (mobileUA || touchWide) {
    navigateTo('/m', { replace: true })
  } else {
    navigateTo('/pc', { replace: true })
  }
})
</script>

<style scoped>
.root-entry {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  color: #94a3b8;
  font-size: 14px;
}
.root-entry__spinner {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 3px solid rgba(20, 184, 166, 0.15);
  border-top-color: #14b8a6;
  animation: root-spin 0.8s linear infinite;
}
@keyframes root-spin {
  to { transform: rotate(360deg); }
}
</style>
