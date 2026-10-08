<template>
  <Teleport to="body">
    <TransitionGroup name="toast" tag="div" class="toast-container">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="toast-item"
          :class="`toast-${t.type}`"
          @click="remove(t.id)"
        >
          <span class="toast-icon">
            <!-- 成功 -->
            <svg v-if="t.type === 'success'" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <!-- 错误 -->
            <svg v-else-if="t.type === 'error'" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
            <!-- 警告 -->
            <svg v-else-if="t.type === 'warning'" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            <!-- 信息 -->
            <svg v-else viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          </span>
          <span class="toast-message">{{ t.message }}</span>
        </div>
      </TransitionGroup>
    </Teleport>
</template>

<script setup>
const { toasts, remove } = useToast()
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 84px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  pointer-events: none;
}

.toast-item {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 240px;
  max-width: 460px;
  padding: 12px 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(15, 23, 42, 0.06);
  border: 1px solid var(--gray-100);
  font-size: 14px;
  color: var(--gray-600);
  cursor: pointer;
  pointer-events: auto;
  backdrop-filter: blur(8px);
}

.toast-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
}

.toast-message {
  flex: 1;
  line-height: 1.5;
  word-break: break-word;
}

/* 类型样式 */
.toast-success .toast-icon {
  background: #d1fae5;
  color: #059669;
}

.toast-error .toast-icon {
  background: #fee2e2;
  color: #dc2626;
}

.toast-warning .toast-icon {
  background: #fef3c7;
  color: #d97706;
}

.toast-info .toast-icon {
  background: #ccfbf1;
  color: var(--primary-600);
}

.toast-success {
  border-left: 3px solid #059669;
}

.toast-error {
  border-left: 3px solid #dc2626;
}

.toast-warning {
  border-left: 3px solid #d97706;
}

.toast-info {
  border-left: 3px solid var(--primary-500);
}

/* 动画 */
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
  transition: all 0.25s ease-in;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

.toast-move {
  transition: transform 0.25s ease;
}
</style>
