<template>
  <section id="contact" class="contact-section">
    <div class="container">
      <div class="section-head">
        <h2 class="section-title">平台客服</h2>
        <p class="section-subtitle">邀请码 · 套餐咨询 · 订单协助 · 工作日 7×12 小时在线</p>
      </div>

      <div class="contact-card" @click="openQr">
        <div class="contact-qr-wrap">
          <img src="/qrcode-service.jpg" alt="平台客服微信二维码" class="contact-qr" />
          <span class="qr-scan-tip">扫码咨询</span>
        </div>

        <div class="contact-body">
          <div class="contact-header">
            <h3 class="contact-name">官方客服微信</h3>
            <span class="contact-status">
              <span class="status-dot"></span>
              在线
            </span>
          </div>
          <p class="contact-desc">
            扫码添加企业微信客服，获得专业支持、专属邀请码与一对一服务。
            合作咨询、套餐选择、订单进度随时为您解答。
          </p>

          <div class="contact-tags">
            <span class="contact-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              邀请码咨询
            </span>
            <span class="contact-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              套餐推荐
            </span>
            <span class="contact-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              售后支持
            </span>
            <span class="contact-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              分站合作
            </span>
          </div>

          <button class="contact-cta" type="button" @click.stop="openQr">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            立即添加客服
            <svg class="cta-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- 客服二维码放大弹窗 -->
    <Teleport to="body">
      <Transition name="qr-fade">
        <div v-if="showQr" class="qr-modal" @click.self="showQr = false">
          <div class="qr-modal-content">
            <img src="/qrcode-service.jpg" alt="平台客服微信二维码" class="qr-modal-img" />
            <p class="qr-modal-tip">长按或扫描二维码添加官方客服微信</p>
            <p class="qr-modal-sub">邀请码 · 套餐咨询 · 订单协助</p>
            <button class="qr-modal-close" @click="showQr = false" aria-label="关闭">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const showQr = ref(false)

function openQr() {
  showQr.value = true
}
</script>

<style scoped>
.contact-section {
  padding: 80px 0 100px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  position: relative;
}

.section-head {
  text-align: center;
  margin-bottom: 40px;
}

.section-title {
  margin: 0 0 12px;
  font-size: 32px;
  font-weight: 800;
  color: var(--dark-900, #0f172a);
  letter-spacing: -0.01em;
}

.section-subtitle {
  margin: 0;
  font-size: 15px;
  color: var(--gray-500, #6b7280);
  font-weight: 500;
}

.contact-card {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 48px;
  align-items: center;
  max-width: 880px;
  margin: 0 auto;
  padding: 36px 44px;
  background: var(--white, #fff);
  border: 1px solid rgba(20, 184, 166, 0.18);
  border-radius: 24px;
  box-shadow: 0 12px 40px rgba(13, 148, 136, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04);
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  position: relative;
  overflow: hidden;
}

.contact-card::before {
  content: '';
  position: absolute;
  top: -80px;
  right: -80px;
  width: 240px;
  height: 240px;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.1) 0%, transparent 70%);
  pointer-events: none;
}

.contact-card:hover {
  transform: translateY(-2px);
  border-color: rgba(20, 184, 166, 0.4);
  box-shadow: 0 18px 50px rgba(13, 148, 136, 0.12), 0 4px 10px rgba(15, 23, 42, 0.05);
}

/* 二维码区 */
.contact-qr-wrap {
  position: relative;
  width: 240px;
  height: 240px;
  padding: 12px;
  background: #fff;
  border: 2px solid #f0fdfa;
  border-radius: 20px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
}

.contact-qr {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  border-radius: 8px;
}

.qr-scan-tip {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  padding: 4px 14px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  border-radius: 999px;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
  white-space: nowrap;
}

/* 内容区 */
.contact-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 1;
}

.contact-header {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.contact-name {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  color: var(--dark-900, #0f172a);
  line-height: 1.2;
}

.contact-status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  background: rgba(34, 197, 94, 0.12);
  color: #16a34a;
  font-size: 12px;
  font-weight: 700;
  border-radius: 999px;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #16a34a;
  box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6);
  animation: pulse 1.8s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6);
  }
  50% {
    box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
  }
}

.contact-desc {
  margin: 0;
  font-size: 14.5px;
  color: var(--gray-600, #4b5563);
  line-height: 1.7;
  font-weight: 500;
}

.contact-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.contact-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  background: rgba(20, 184, 166, 0.08);
  color: #0d9488;
  font-size: 13px;
  font-weight: 600;
  border-radius: 999px;
  border: 1px solid rgba(20, 184, 166, 0.15);
  line-height: 1.4;
  white-space: nowrap;
}

.contact-tag svg {
  flex-shrink: 0;
}

.contact-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  align-self: flex-start;
  padding: 12px 24px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  font-size: 14.5px;
  font-weight: 700;
  border: none;
  border-radius: 999px;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(20, 184, 166, 0.3);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  margin-top: 4px;
}

.contact-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(20, 184, 166, 0.4);
}

.cta-arrow {
  transition: transform 0.2s ease;
}

.contact-cta:hover .cta-arrow {
  transform: translateX(3px);
}

/* ===== 客服二维码弹窗 ===== */
.qr-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  padding: 20px;
}

.qr-modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 32px;
  background: var(--white, #fff);
  border-radius: 24px;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
  max-width: 92vw;
}

.qr-modal-img {
  width: 320px;
  height: 320px;
  max-width: 70vw;
  max-height: 50vh;
  object-fit: contain;
  border-radius: 12px;
  background: #fff;
  padding: 8px;
  border: 1px solid var(--gray-100, #f3f4f6);
}

.qr-modal-tip {
  font-size: 15px;
  font-weight: 600;
  color: var(--dark-800, #1e293b);
  margin: 0;
  margin-top: 4px;
}

.qr-modal-sub {
  font-size: 13px;
  color: var(--gray-500, #6b7280);
  margin: 0;
}

.qr-modal-close {
  position: absolute;
  top: -14px;
  right: -14px;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: none;
  background: #fff;
  color: var(--dark-700, #334155);
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
  transition: transform 0.2s ease, background 0.2s ease;
}

.qr-modal-close:hover {
  background: #f1f5f9;
  transform: rotate(90deg);
}

.qr-fade-enter-active,
.qr-fade-leave-active {
  transition: opacity 0.25s ease;
}

.qr-fade-enter-from,
.qr-fade-leave-to {
  opacity: 0;
}

/* 响应式 */
@media (max-width: 900px) {
  .contact-card {
    grid-template-columns: 1fr;
    gap: 28px;
    padding: 28px 24px;
    text-align: center;
  }

  .contact-qr-wrap {
    margin: 0 auto;
    width: 200px;
    height: 200px;
  }

  .contact-header {
    justify-content: center;
  }

  .contact-tags {
    justify-content: center;
  }

  .contact-cta {
    align-self: center;
  }
}

@media (max-width: 768px) {
  .contact-section {
    padding: 60px 0 80px;
  }

  .section-title {
    font-size: 26px;
  }

  .section-subtitle {
    font-size: 14px;
  }

  .contact-card {
    padding: 24px 20px;
    border-radius: 20px;
  }

  .contact-qr-wrap {
    width: 180px;
    height: 180px;
    padding: 10px;
  }

  .contact-name {
    font-size: 20px;
  }

  .contact-desc {
    font-size: 13.5px;
  }

  .contact-cta {
    width: 100%;
    padding: 14px 20px;
  }

  .qr-modal-img {
    width: 260px;
    height: 260px;
  }
}

@media (max-width: 480px) {
  .contact-tags {
    gap: 6px;
  }

  .contact-tag {
    font-size: 12px;
    padding: 5px 10px;
  }
}
</style>
