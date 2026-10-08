<template>
  <Teleport to="body">
    <Transition name="ssa-fade">
      <div v-if="modelValue" class="ssa-mask" @click.self="close">
        <Transition name="ssa-pop">
          <div v-if="modelValue" class="ssa-card">
            <!-- 头部横幅：主题色渐变 + 装饰圆环 -->
            <div class="ssa-head">
              <span class="ssa-head-orb ssa-head-orb-a"></span>
              <span class="ssa-head-orb ssa-head-orb-b"></span>
              <span class="ssa-head-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              </span>
              <div class="ssa-head-text">
                <h3 class="ssa-title">分站公告</h3>
                <p class="ssa-sub">新鲜动态 · 及时送达</p>
              </div>
              <button class="ssa-close" @click="close" aria-label="关闭">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <!-- 正文 -->
            <div class="ssa-body">
              <div v-if="title" class="ssa-msg-title">{{ title }}</div>
              <div v-if="timeText" class="ssa-time">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                发布于 {{ timeText }}
              </div>
              <div class="ssa-msg-content" :class="{ 'only-content': !title }">
                <p v-if="content">{{ content }}</p>
                <p v-else class="ssa-empty">暂无更多公告内容</p>
              </div>
            </div>

            <!-- 底部 -->
            <div class="ssa-foot">
              <button class="ssa-ok" @click="close">我知道了</button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  content: { type: String, default: '' },
  // 公告发布时间（Unix 时间戳，秒）
  time: { type: [Number, String], default: 0 },
})
const emit = defineEmits(['update:modelValue'])

// 发布时间展示：仅展示日期与时间（YYYY-MM-DD HH:mm），无效时间不展示
const timeText = computed(() => {
  const t = Number(props.time)
  if (!Number.isFinite(t) || t <= 0) return ''
  const d = new Date(t * 1000)
  if (isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
})

function close() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
/* ===== 固定配色（teal 青绿，弹窗不随 header 公告配色循环变化） ===== */
.ssa-card {
  --ac1: #14b8a6;
  --ac2: #0d9488;
  --ac-soft: #e6f8f5;
}

/* 遮罩 */
.ssa-mask {
  position: fixed;
  inset: 0;
  z-index: 3000;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* 卡片 */
.ssa-card {
  width: 460px;
  max-width: 100%;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 30px 70px -24px rgba(15, 23, 42, 0.5);
  overflow: hidden;
  border: 1px solid #eef2f6;
  display: flex;
  flex-direction: column;
  max-height: min(82vh, 640px);
}

/* 头部横幅 */
.ssa-head {
  position: relative;
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 22px 24px 20px;
  background: linear-gradient(135deg, var(--ac1), var(--ac2));
  color: #fff;
  overflow: hidden;
}
.ssa-head-orb {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.14);
}
.ssa-head-orb-a { width: 130px; height: 130px; right: -36px; top: -52px; }
.ssa-head-orb-b { width: 70px; height: 70px; right: 78px; bottom: -38px; }
.ssa-head-icon {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(2px);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow: 0 8px 18px -8px rgba(0, 0, 0, 0.35);
  flex-shrink: 0;
}
.ssa-head-text {
  position: relative;
  flex: 1;
  min-width: 0;
}
.ssa-title {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.ssa-sub {
  margin: 3px 0 0;
  font-size: 12px;
  opacity: 0.85;
}
.ssa-close {
  position: relative;
  appearance: none;
  border: none;
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.18s ease;
}
.ssa-close:hover { background: rgba(255, 255, 255, 0.32); transform: rotate(90deg); }

/* 正文 */
.ssa-body {
  padding: 22px 26px 8px;
  overflow-y: auto;
  min-height: 0;
}
.ssa-msg-title {
  font-size: 16.5px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.5;
  padding-bottom: 12px;
  margin-bottom: 14px;
  border-bottom: 1px dashed #e2e8f0;
  position: relative;
}
.ssa-msg-title::before {
  content: '';
  position: absolute;
  left: 0;
  bottom: -1.5px;
  width: 34px;
  height: 3px;
  border-radius: 3px;
  background: linear-gradient(90deg, var(--ac1), var(--ac2));
}
/* 发布时间 */
.ssa-time {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin: -4px 0 14px;
  font-size: 12px;
  color: #94a3b8;
}
.ssa-time svg { color: var(--ac2); }
.ssa-msg-content {
  font-size: 14px;
  line-height: 1.85;
  color: #334155;
  white-space: pre-wrap;
  word-break: break-word;
}
.ssa-msg-content p { margin: 0; }
.ssa-msg-content.only-content { padding-top: 6px; }
.ssa-empty { color: #94a3b8; }

/* 底部 */
.ssa-foot {
  padding: 14px 26px 22px;
}
.ssa-ok {
  appearance: none;
  border: none;
  width: 100%;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--ac1), var(--ac2));
  color: #fff;
  font-family: inherit;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 1px;
  cursor: pointer;
  box-shadow: 0 10px 22px -10px var(--ac2);
  transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
}
.ssa-ok:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 26px -10px var(--ac2);
}
.ssa-ok:active { transform: translateY(0); opacity: 0.92; }

/* 过渡动画 */
.ssa-fade-enter-active { transition: opacity 0.25s ease; }
.ssa-fade-leave-active { transition: opacity 0.2s ease; }
.ssa-fade-enter-from, .ssa-fade-leave-to { opacity: 0; }
.ssa-pop-enter-active { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease; }
.ssa-pop-leave-active { transition: transform 0.2s ease, opacity 0.2s ease; }
.ssa-pop-enter-from, .ssa-pop-leave-to { transform: translateY(18px) scale(0.95); opacity: 0; }

@media (max-width: 480px) {
  .ssa-card { width: 100%; }
  .ssa-head { padding: 18px 18px 16px; }
  .ssa-body { padding: 18px 20px 6px; }
  .ssa-foot { padding: 12px 20px 18px; }
}
</style>
