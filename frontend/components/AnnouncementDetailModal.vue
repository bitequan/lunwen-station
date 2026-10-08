<template>
  <ClientOnly>
    <Teleport to="body">
      <Transition name="announcement-fade">
        <div v-if="modelValue" class="announcement-mask" @click.self="handleClose">
          <div class="announcement-modal" role="dialog" aria-modal="true">
            <button class="announcement-close" @click="handleClose" aria-label="关闭">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <div class="announcement-header">
              <div class="announcement-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              </div>
              <h3 class="announcement-title">{{ data.title || '系统公告' }}</h3>
            </div>

            <div class="announcement-meta" v-if="data.update_time">
              {{ formatTime(data.update_time) }}
            </div>

            <div class="announcement-body" @click="onBodyClick" v-html="data.content"></div>

            <div class="announcement-footer">
              <button class="announcement-btn" @click="handleClose">我知道了</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </ClientOnly>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  data: { type: Object, default: () => ({ title: '', content: '', update_time: 0 }) }
})
const emit = defineEmits(['update:modelValue'])

function handleClose() {
  emit('update:modelValue', false)
}

// 正文中的链接：统一用新窗口(新标签页)打开跳转（安全属性，避免 SPA 内整页跳走）
function onBodyClick(e) {
  const target = e.target
  const a = target && target.closest ? target.closest('a[href]') : null
  if (!a) return
  const href = a.getAttribute('href') || ''
  if (!href || href.startsWith('#') || /^javascript:/i.test(href)) return
  e.preventDefault()
  e.stopPropagation()
  const win = window.open(href, '_blank')
  if (!win) {
    // 浏览器拦截弹窗时降级为当前页跳转
    window.location.href = href
  } else {
    // 手动断开 opener(等价 noopener), 避免新窗口持有原页面引用
    try { win.opener = null } catch (e) {}
  }
}

function formatTime(ts) {
  if (!ts) return ''
  const d = new Date(Number(ts) * 1000)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>

<style scoped>
.announcement-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.announcement-modal {
  position: relative;
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24), 0 6px 16px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: announcement-pop 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes announcement-pop {
  from { opacity: 0; transform: translateY(12px) scale(0.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.announcement-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(15, 23, 42, 0.04);
  border-radius: 50%;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 2;
}
.announcement-close:hover {
  background: rgba(15, 23, 42, 0.08);
  color: #1e293b;
}
.announcement-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 22px 24px 12px;
  border-bottom: 1px solid #f1f5f9;
}
.announcement-icon-wrap {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: linear-gradient(135deg, #0d9488, #14b8a6);
  color: #fff;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.32);
}
.announcement-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.4;
  flex: 1;
  padding-right: 28px;
  word-break: break-word;
}
.announcement-meta {
  padding: 8px 24px 0;
  font-size: 12px;
  color: #94a3b8;
}
.announcement-body {
  padding: 14px 24px 18px;
  overflow-y: auto;
  font-size: 14px;
  line-height: 1.7;
  color: #334155;
  word-break: break-word;
}
.announcement-body :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}
.announcement-body :deep(a) {
  color: #0d9488;
  text-decoration: underline;
}
.announcement-body :deep(p) {
  margin: 0 0 10px;
}
.announcement-body :deep(ul),
.announcement-body :deep(ol) {
  margin: 0 0 10px;
  padding-left: 22px;
}
.announcement-footer {
  padding: 12px 24px 20px;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #f1f5f9;
}
.announcement-btn {
  padding: 9px 24px;
  background: linear-gradient(135deg, #0d9488, #14b8a6);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28);
}
.announcement-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(13, 148, 136, 0.36);
}
.announcement-fade-enter-active { transition: opacity 0.25s ease; }
.announcement-fade-leave-active { transition: opacity 0.2s ease; }
.announcement-fade-enter-from,
.announcement-fade-leave-to { opacity: 0; }

@media (max-width: 600px) {
  .announcement-modal {
    max-width: 100%;
    max-height: 88vh;
    border-radius: 14px;
  }
}
</style>
