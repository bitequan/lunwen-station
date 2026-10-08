<template>
  <div class="tool-placeholder">
    <div class="placeholder-card">
      <!-- 顶部装饰渐变条 -->
      <div class="placeholder-top-bar" :class="theme"></div>

      <!-- 图标区 -->
      <div class="placeholder-icon-wrap">
        <div class="placeholder-icon-chip" :class="theme">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="icon"></svg>
        </div>
        <span class="placeholder-pulse-ring" :class="theme"></span>
      </div>

      <!-- 文本区 -->
      <h2 class="placeholder-title">{{ name }}</h2>
      <p class="placeholder-desc">{{ desc }}</p>

      <!-- 状态标签 -->
      <div class="placeholder-status">
        <span class="status-dot"></span>
        <span>功能开发中</span>
      </div>

      <p class="placeholder-hint">该工具正在紧锣密鼓地开发中，敬请期待</p>

      <!-- 返回按钮 -->
      <NuxtLink to="/pc/tools" class="placeholder-back">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>返回小工具</span>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
defineProps({
  name: { type: String, required: true },
  desc: { type: String, default: '' },
  icon: { type: String, default: '' },
  theme: { type: String, default: 'teal' },
})
</script>

<style scoped>
.tool-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 64px - 52px);
  padding: 20px 0;
}

.placeholder-card {
  position: relative;
  width: 100%;
  max-width: 440px;
  background: var(--white);
  border-radius: var(--radius-xl);
  border: 1px solid var(--gray-100);
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(15, 23, 42, 0.03);
  padding: 40px 36px 32px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  overflow: hidden;
}

/* 顶部渐变条 */
.placeholder-top-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.placeholder-top-bar.teal {
  background: linear-gradient(90deg, #14b8a6, #5eead4);
}

.placeholder-top-bar.orange {
  background: linear-gradient(90deg, #f97316, #fdba74);
}

.placeholder-top-bar.cyan {
  background: linear-gradient(90deg, #06b6d4, #67e8f9);
}

/* 图标区 + 脉冲环 */
.placeholder-icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 22px;
}

.placeholder-icon-chip {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 76px;
  height: 76px;
  border-radius: 22px;
  color: var(--white);
}

.placeholder-icon-chip.teal {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 8px 24px rgba(13, 148, 136, 0.3);
}

.placeholder-icon-chip.orange {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 8px 24px rgba(249, 115, 22, 0.3);
}

.placeholder-icon-chip.cyan {
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
}

.placeholder-pulse-ring {
  position: absolute;
  width: 76px;
  height: 76px;
  border-radius: 22px;
  opacity: 0;
  animation: pulse-ring 2.4s ease-out infinite;
}

.placeholder-pulse-ring.teal {
  border: 2px solid #14b8a6;
}

.placeholder-pulse-ring.orange {
  border: 2px solid #f97316;
}

.placeholder-pulse-ring.cyan {
  border: 2px solid #06b6d4;
}

@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 0.6;
  }
  100% {
    transform: scale(1.6);
    opacity: 0;
  }
}

/* 标题与描述 */
.placeholder-title {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 800;
  color: var(--dark-900);
  margin-bottom: 8px;
  letter-spacing: -0.01em;
}

.placeholder-desc {
  font-size: 13.5px;
  color: var(--gray-500);
  line-height: 1.5;
  margin-bottom: 20px;
}

/* 状态标签 */
.placeholder-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 600;
  color: #c2410c;
  background: rgba(249, 115, 22, 0.08);
  border: 1px solid rgba(249, 115, 22, 0.18);
  margin-bottom: 14px;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #f97316;
  box-shadow: 0 0 6px rgba(249, 115, 22, 0.6);
  animation: status-blink 2s ease-in-out infinite;
}

@keyframes status-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.placeholder-hint {
  font-size: 13px;
  color: var(--gray-400);
  margin-bottom: 24px;
}

/* 返回按钮 */
.placeholder-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 999px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--white);
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  box-shadow: 0 6px 18px rgba(13, 148, 136, 0.25);
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.placeholder-back:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(13, 148, 136, 0.35);
}

.placeholder-back svg {
  transition: transform 0.2s ease;
}

.placeholder-back:hover svg {
  transform: translateX(-2px);
}

/* 响应式 */
@media (max-width: 600px) {
  .placeholder-card {
    padding: 32px 24px 28px;
  }

  .placeholder-title {
    font-size: 20px;
  }
}
</style>
