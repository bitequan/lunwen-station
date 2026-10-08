<template>
  <el-dialog
    :model-value="modelValue"
    title="确认支付"
    width="440px"
    custom-class="pay-modal"
    modal-class="pay-modal-mask"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    append-to-body
    @update:model-value="val => emit('update:modelValue', val)"
  >
    <div class="pay-body">
      <!-- 金额卡片 -->
      <div class="pay-amount-card">
        <div class="pay-amount-top">
          <span class="pay-amount-label">支付金额</span>
          <span class="pay-amount-tag">余额直扣</span>
        </div>
        <div class="pay-amount-main">
          <span class="pay-amount-symbol">￥</span>
          <span class="pay-amount-value">{{ formatPrice(price) }}</span>
        </div>
        <div class="pay-amount-line"></div>
      </div>

      <!-- 余额支付 -->
      <div class="pay-method-item active">
        <span class="pay-method-icon icon-1">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><circle cx="6" cy="15" r="1.5" fill="currentColor" stroke="none"/></svg>
        </span>
        <span class="pay-method-info">
          <span class="pay-method-name">余额支付</span>
          <span class="pay-method-tip">使用账户余额直接扣款</span>
        </span>
        <span class="pay-method-radio checked"></span>
      </div>

      <!-- 订单信息 -->
      <div class="pay-order">
        <div class="pay-order-row">
          <span class="pay-order-label">服务项目</span>
          <span class="pay-order-value">{{ title }}</span>
        </div>
        <div class="pay-order-row">
          <span class="pay-order-label">订单编号</span>
          <span class="pay-order-value mono">{{ orderId }}</span>
        </div>
      </div>

      <p class="pay-tip">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        支付成功后订单即刻进入生成队列，可在订单中心查看进度。
      </p>
    </div>

    <template #footer>
      <el-button class="pay-btn-cancel" @click="emit('update:modelValue', false)">取消</el-button>
      <el-button class="pay-btn-confirm" :loading="loading" @click="handlePay">确认支付</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  orderId: { type: [String, Number], default: '' },
  price: { type: [String, Number], default: 0 },
  redirect: { type: String, default: '/pc/create' },
  title: { type: String, default: '写作服务' },
})

const emit = defineEmits(['update:modelValue', 'success'])

const api = useApi()

const loading = ref(false)

watch(() => props.modelValue, (val) => {
  if (val) loading.value = false
})

function formatPrice(v) {
  const n = Number(v)
  if (Number.isNaN(n)) return v
  return Number.isInteger(n) ? String(n) : n.toFixed(2)
}

async function handlePay() {
  if (!props.orderId) {
    ElMessage.error('订单信息缺失，请重新提交')
    return
  }
  loading.value = true
  try {
    const res = await api.post('/api/write/pay', {
      order_sn: props.orderId,
    })
    if (!res.ok) {
      ElMessage.error(res.msg || '支付失败，请稍后重试')
      return
    }
    ElMessage.success('支付成功')
    emit('success', res.data || {})
    emit('update:modelValue', false)
  } catch (e) {
    ElMessage.error(e.message || '支付失败，请稍后重试')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.pay-body {
  padding: 2px 2px;
}

/* ============ 金额卡片 ============ */
.pay-amount-card {
  position: relative;
  overflow: hidden;
  padding: 18px 20px 16px;
  margin-bottom: 14px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 55%, #f0f9ff 100%);
  border: 1px solid rgba(20, 184, 166, 0.18);
}
.pay-amount-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.pay-amount-label {
  font-size: 13px;
  color: var(--gray-500, #64748b);
  font-weight: 500;
}
.pay-amount-tag {
  font-size: 11px;
  font-weight: 600;
  color: #0d9488;
  background: rgba(20, 184, 166, 0.12);
  padding: 3px 9px;
  border-radius: 999px;
}
.pay-amount-main {
  display: flex;
  align-items: baseline;
  gap: 4px;
}
.pay-amount-symbol {
  font-size: 18px;
  font-weight: 700;
  color: var(--accent-500, #f97316);
}
.pay-amount-value {
  font-size: 38px;
  font-weight: 800;
  color: var(--accent-500, #f97316);
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
}
.pay-amount-line {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 3px;
  background: linear-gradient(90deg, #14b8a6, #5eead4, #14b8a6);
  background-size: 200% 100%;
  animation: amount-flow 3s linear infinite;
  opacity: 0.7;
}
@keyframes amount-flow {
  0% { background-position: 0% 0; }
  100% { background-position: 200% 0; }
}

/* ============ 支付方式 ============ */
.pay-method-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 15px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  transition: all 0.2s ease;
}
.pay-method-item.active {
  border-color: #14b8a6;
  background: #f0fdfa;
  box-shadow: 0 2px 12px rgba(20, 184, 166, 0.12);
}
.pay-method-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  color: #fff;
  flex-shrink: 0;
}
.pay-method-icon.icon-1 {
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
}
.pay-method-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}
.pay-method-name {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.pay-method-tip {
  font-size: 12px;
  color: #94a3b8;
}
.pay-method-radio {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  flex-shrink: 0;
  position: relative;
  transition: all 0.2s ease;
}
.pay-method-radio.checked {
  border-color: #14b8a6;
}
.pay-method-radio.checked::after {
  content: '';
  position: absolute;
  inset: 3px;
  border-radius: 50%;
  background: #14b8a6;
}

/* ============ 订单信息 ============ */
.pay-order {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
  padding: 12px 15px;
  background: #f8fafc;
  border-radius: 12px;
}
.pay-order-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.pay-order-label {
  font-size: 12.5px;
  color: #94a3b8;
  flex-shrink: 0;
}
.pay-order-value {
  font-size: 12.5px;
  color: #334155;
  font-weight: 600;
  text-align: right;
  word-break: break-all;
}
.pay-order-value.mono {
  font-family: 'DIN Alternate', 'Helvetica Neue', sans-serif;
  color: #64748b;
}

/* ============ 提示 ============ */
.pay-tip {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin: 14px 2px 2px;
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.6;
}
.pay-tip svg {
  flex-shrink: 0;
  margin-top: 2px;
  color: #14b8a6;
}
</style>

<style>
/* ============ 支付弹窗全局样式（对齐 /pc/create 收费标准弹窗） ============ */
/* 遮罩：半透明 + 毛玻璃 + 垂直居中对齐（仅本弹窗，通过 modal-class 限定） */
.el-overlay.pay-modal-mask {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  background-color: rgba(15, 23, 42, 0.55);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

/* 弹窗主体：垂直居中，消除 Element Plus 默认 15vh 顶部偏移 */
.el-dialog.pay-modal {
  margin: 0;
  position: relative;
  flex-shrink: 0;
  border-radius: 24px;
  box-shadow: 0 28px 90px rgba(15, 23, 42, 0.28);
  overflow: hidden;
}

/* 头部：不再用边框分割，改为居中标题 + 右上悬浮关闭按钮，视觉更轻盈 */
.el-dialog.pay-modal .el-dialog__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px 4px;
  margin-right: 0;
  border-bottom: none;
}

.el-dialog.pay-modal .el-dialog__title {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
  line-height: 1;
  letter-spacing: 0.5px;
}

/* 关闭按钮：悬浮在右上角，底色更柔和 */
.el-dialog.pay-modal .el-dialog__headerbtn {
  position: absolute;
  top: 18px;
  right: 20px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--gray-100);
  transition: all 0.2s;
}

.el-dialog.pay-modal .el-dialog__headerbtn:hover {
  background: #fef2f2;
  color: var(--danger-500);
  transform: rotate(90deg);
}

.el-dialog.pay-modal .el-dialog__close {
  color: var(--gray-500);
  font-size: 15px;
}

/* 主体 */
.el-dialog.pay-modal .el-dialog__body {
  padding: 14px 24px 8px;
}

/* 底部操作区 */
.el-dialog.pay-modal .el-dialog__footer {
  padding: 14px 24px 22px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.el-dialog.pay-modal .el-dialog__footer .el-button {
  height: 44px;
  padding: 0 30px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
}

.el-dialog.pay-modal .pay-btn-cancel {
  background: #fff;
  color: var(--gray-600);
  border: 1px solid var(--gray-300);
}

.el-dialog.pay-modal .pay-btn-cancel:hover {
  border-color: var(--gray-400);
  background: var(--gray-50);
  color: var(--dark-800);
}

.el-dialog.pay-modal .pay-btn-confirm {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  border: none;
  color: #fff;
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
}

.el-dialog.pay-modal .pay-btn-confirm:hover {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  box-shadow: 0 6px 20px rgba(13, 148, 136, 0.38);
  transform: translateY(-1px);
}

/* 移动端适配 */
@media (max-width: 640px) {
  .el-overlay.pay-modal-mask { padding: 16px; }
  .el-dialog.pay-modal {
    width: 100% !important;
    border-radius: 18px;
  }
  .el-dialog.pay-modal .el-dialog__header { padding: 18px 16px 2px; }
  .el-dialog.pay-modal .el-dialog__body { padding: 12px 16px 6px; }
  .el-dialog.pay-modal .el-dialog__footer { padding: 10px 16px 16px; gap: 8px; }
  .el-dialog.pay-modal .el-dialog__footer .el-button {
    flex: 1; height: 44px; padding: 0; border-radius: 12px;
  }
}
</style>