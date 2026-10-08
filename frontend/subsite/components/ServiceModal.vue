<template>
  <Teleport to="body">
    <Transition name="ssvc-fade">
      <div v-if="modelValue" class="ssvc-mask" @click.self="close">
        <Transition name="ssvc-scale">
          <div v-if="modelValue" class="ssvc-card">
            <!-- 头部 -->
            <div class="ssvc-head">
              <div class="ssvc-head-left">
                <span class="ssvc-head-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                <div>
                  <h3 class="ssvc-title">联系客服</h3>
                  <p class="ssvc-sub">专属客服为您解答使用问题</p>
                </div>
              </div>
              <button class="ssvc-close" @click="close" aria-label="关闭">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div class="ssvc-body">
              <!-- 人工充值提示（分站未开启在线支付、替代支付弹窗时展示） -->
              <div v-if="notice" class="ssvc-notice">{{ notice }}</div>

              <!-- 二维码（如有） -->
              <div v-if="service.qr_code" class="ssvc-qr">
                <img :src="service.qr_code" alt="客服二维码" />
                <span class="ssvc-qr-tip">扫码添加客服微信</span>
              </div>

              <!-- 联系方式 -->
              <div v-if="hasContact" class="ssvc-rows">
                <div v-if="service.wechat" class="ssvc-row">
                  <span class="ssvc-row-icon teal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                  </span>
                  <div class="ssvc-row-info">
                    <span class="ssvc-row-label">客服微信</span>
                    <span class="ssvc-row-value mono">{{ service.wechat }}</span>
                  </div>
                  <button type="button" class="ssvc-row-copy" @click="copyText(service.wechat)">复制</button>
                </div>
                <div v-if="service.phone" class="ssvc-row">
                  <span class="ssvc-row-icon orange">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <div class="ssvc-row-info">
                    <span class="ssvc-row-label">联系电话</span>
                    <span class="ssvc-row-value mono">{{ service.phone }}</span>
                  </div>
                  <a v-if="service.phone" class="ssvc-row-copy" :href="'tel:' + service.phone">拨打</a>
                </div>
                <div v-if="service.service_time" class="ssvc-row">
                  <span class="ssvc-row-icon purple">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  </span>
                  <div class="ssvc-row-info">
                    <span class="ssvc-row-label">服务时间</span>
                    <span class="ssvc-row-value">{{ service.service_time }}</span>
                  </div>
                </div>
              </div>

              <!-- 未配置联系方式 -->
              <div v-if="!hasContact" class="ssvc-empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/></svg>
                <p>暂未配置客服联系方式，请稍后再试</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  // 客服信息：{ qr_code, wechat, phone, service_time }
  service: { type: Object, default: () => ({}) },
  // 顶部提示文案（分站未开启在线支付、用本弹窗替代支付弹窗时展示人工充值引导）
  notice: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const hasContact = computed(() => {
  const s = props.service || {}
  return !!(s.qr_code || s.wechat || s.phone || s.service_time)
})

function close() {
  emit('update:modelValue', false)
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text)
  } catch (e) {
    // 降级：选中文本复制
    const ta = document.createElement('textarea')
    ta.value = text
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
}
</script>

<style scoped>
/* 遮罩 */
.ssvc-mask {
  position: fixed;
  inset: 0;
  z-index: 3000;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* 卡片 */
.ssvc-card {
  width: 380px;
  max-width: 100%;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 24px 60px -20px rgba(15, 23, 42, 0.45);
  overflow: hidden;
  border: 1px solid #eef2f6;
}

/* 头部 */
.ssvc-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px 12px;
  border-bottom: 1px solid #f1f5f9;
}
.ssvc-head-left {
  display: flex;
  align-items: center;
  gap: 11px;
}
.ssvc-head-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 11px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  box-shadow: 0 6px 16px -6px rgba(13, 148, 136, 0.6);
}
.ssvc-title {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}
.ssvc-sub {
  margin: 1px 0 0;
  font-size: 11.5px;
  color: #94a3b8;
}
.ssvc-close {
  appearance: none;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  width: 30px;
  height: 30px;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.18s ease;
}
.ssvc-close:hover { color: #dc2626; background: #fee2e2; }

/* 内容 */
.ssvc-body {
  padding: 16px 18px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* 人工充值提示 */
.ssvc-notice {
  padding: 9px 12px;
  border-radius: 10px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #c2410c;
  font-size: 12.5px;
  line-height: 1.5;
  text-align: left;
}

/* 二维码 */
.ssvc-qr {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  padding: 10px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #eef2f6;
}
.ssvc-qr img {
  width: 140px;
  height: 140px;
  border-radius: 8px;
  object-fit: contain;
  background: #fff;
}
.ssvc-qr-tip {
  font-size: 11.5px;
  color: #64748b;
}

/* 联系方式行 */
.ssvc-rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.ssvc-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid #eef2f6;
  background: #fbfcfe;
}
.ssvc-row-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 9px;
  flex-shrink: 0;
}
.ssvc-row-icon.teal { color: #0d9488; background: rgba(20, 184, 166, 0.1); }
.ssvc-row-icon.orange { color: #ea580c; background: rgba(249, 115, 22, 0.1); }
.ssvc-row-icon.purple { color: #7c3aed; background: rgba(139, 92, 246, 0.1); }
.ssvc-row-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.ssvc-row-label {
  font-size: 11.5px;
  color: #94a3b8;
}
.ssvc-row-value {
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ssvc-row-value.mono { font-family: Consolas, monospace; letter-spacing: 0.2px; }
.ssvc-row-copy {
  appearance: none;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  flex-shrink: 0;
  transition: all 0.18s ease;
}
.ssvc-row-copy:hover {
  color: #0d9488;
  border-color: rgba(20, 184, 166, 0.35);
  background: rgba(20, 184, 166, 0.06);
}

/* 空态 */
.ssvc-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 26px 0;
  color: #94a3b8;
}
.ssvc-empty p { margin: 0; font-size: 13px; }

/* 过渡动画 */
.ssvc-fade-enter-active { transition: opacity 0.25s ease; }
.ssvc-fade-leave-active { transition: opacity 0.2s ease; }
.ssvc-fade-enter-from, .ssvc-fade-leave-to { opacity: 0; }
.ssvc-scale-enter-active { transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.28s ease; }
.ssvc-scale-leave-active { transition: transform 0.2s ease, opacity 0.2s ease; }
.ssvc-scale-enter-from, .ssvc-scale-leave-to { transform: scale(0.92); opacity: 0; }

@media (max-width: 480px) {
  .ssvc-card { width: 100%; }
}
</style>
