<template>
  <ClientOnly>
    <Teleport to="body">
      <Transition name="announcement-fade">
        <div v-if="visible" class="announcement-mask" @click.self="handleClose">
          <div class="announcement-modal" role="dialog" aria-modal="true">
            <button class="announcement-close" @click="handleClose" aria-label="关闭">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <header class="announcement-header">
              <div class="announcement-head-inner">
                <div class="announcement-eyebrow">
                  <span class="announcement-bell">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                  </span>
                  <span>最新公告</span>
                </div>
                <h3 class="announcement-title">{{ current.title || '系统公告' }}</h3>
              </div>
            </header>

            <div class="announcement-body" @click="onBodyClick" v-html="current.content"></div>

            <footer class="announcement-footer">
              <span v-if="displayDate" class="announcement-date">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                更新于 {{ displayDate }}
              </span>
              <span v-else></span>
              <button class="announcement-btn" @click="handleClose">我知道了</button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>
  </ClientOnly>
</template>

<script setup>
const { visible, current, close } = useAnnouncement()

// 版本号即 update_time（秒级时间戳），作为"更新于"展示
const displayDate = computed(() => {
  const v = Number(current.value.version)
  if (!v) return ''
  const d = new Date(v * 1000)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
})

function handleClose() {
  close()
}

// 正文中的链接：新标签页打开并跳转（安全属性，避免 SPA 内整页跳走）
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
</script>

<style scoped>
.announcement-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(5px);
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
  max-height: 82vh;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28), 0 8px 20px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: announcement-pop 0.34s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes announcement-pop {
  from { opacity: 0; transform: translateY(14px) scale(0.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.announcement-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(255, 255, 255, 0.18);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.92);
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 2;
}
.announcement-close:hover {
  background: rgba(255, 255, 255, 0.3);
  color: #fff;
}

/* 顶部渐变横幅 */
.announcement-header {
  position: relative;
  overflow: hidden;
  padding: 26px 30px 22px;
  background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #14b8a6 100%);
  color: #fff;
}
.announcement-header::before,
.announcement-header::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  pointer-events: none;
}
.announcement-header::before {
  width: 220px;
  height: 220px;
  top: -120px;
  right: -60px;
}
.announcement-header::after {
  width: 130px;
  height: 130px;
  bottom: -80px;
  left: -40px;
}

.announcement-head-inner {
  position: relative;
  z-index: 1;
}

.announcement-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 4px 12px 4px 6px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.95);
}

.announcement-bell {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
}

.announcement-title {
  margin: 14px 0 0;
  font-size: 19px;
  font-weight: 700;
  color: #fff;
  line-height: 1.45;
  padding-right: 34px;
  word-break: break-word;
}

/* 正文 */
.announcement-body {
  padding: 22px 30px;
  overflow-y: auto;
  font-size: 14.5px;
  line-height: 1.8;
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
  font-weight: 500;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
  transition: color 0.15s ease;
}
.announcement-body :deep(a:hover) {
  color: #0f766e;
}
.announcement-body :deep(p) {
  margin: 0 0 12px;
}
.announcement-body :deep(ul),
.announcement-body :deep(ol) {
  margin: 0 0 12px;
  padding-left: 22px;
}
.announcement-body :deep(h1),
.announcement-body :deep(h2),
.announcement-body :deep(h3),
.announcement-body :deep(h4) {
  margin: 16px 0 8px;
  font-weight: 600;
  color: #0f172a;
}

/* 底部 */
.announcement-footer {
  padding: 14px 30px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border-top: 1px solid #f1f5f9;
  background: #fcfdfd;
}

.announcement-date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  color: #94a3b8;
}

.announcement-btn {
  padding: 10px 28px;
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
.announcement-btn:active {
  transform: translateY(0);
}

.announcement-fade-enter-active {
  transition: opacity 0.25s ease;
}
.announcement-fade-leave-active {
  transition: opacity 0.2s ease;
}
.announcement-fade-enter-from,
.announcement-fade-leave-to {
  opacity: 0;
}

@media (max-width: 600px) {
  .announcement-modal {
    max-width: 100%;
    max-height: 88vh;
    border-radius: 16px;
  }
  .announcement-header {
    padding: 22px 20px 18px;
  }
  .announcement-body {
    padding: 16px 20px;
  }
  .announcement-footer {
    padding: 12px 20px 18px;
  }
}
</style>
