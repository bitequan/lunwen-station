<template>
  <Teleport to="body">
    <Transition name="ssrc-fade">
      <div v-if="modelValue" class="ssrc-mask" @click.self="close">
        <Transition name="ssrc-scale">
          <div v-if="modelValue" class="ssrc-card">
            <!-- 头部 -->
            <div class="ssrc-head">
              <div class="ssrc-head-left">
                <span class="ssrc-head-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </span>
                <div>
                  <h3 class="ssrc-title">充值中心</h3>
                  <p class="ssrc-sub">选择充值金额 · 在线支付 · 即时到账</p>
                </div>
              </div>
              <button class="ssrc-close" @click="close" aria-label="关闭">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div class="ssrc-body">
              <!-- 分站：未开启在线支付 -->
              <div v-if="isSiteMode && !sitePayEnabled" class="ssrc-unavailable">
                <div class="ssrc-unavailable-icon">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <h4 class="ssrc-unavailable-title">暂不支持在线充值</h4>
                <p class="ssrc-unavailable-desc">本分站未开启在线支付，请添加人工客服微信或致电客服，由客服为您人工充值到账。</p>
                <div v-if="siteContactText" class="ssrc-unavailable-agent">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>{{ siteContactText }}</span>
                </div>
                <p v-else class="ssrc-unavailable-agent">请联系人工客服办理人工充值</p>
                <p class="ssrc-unavailable-note">平台不代收充值金额，分站开启在线支付后即可在站内自助充值。</p>
                <button type="button" class="ssrc-btn ssrc-btn-outline" @click="close">我知道了</button>
              </div>

              <!-- 分站：站点余额不足 -->
              <div v-else-if="isSiteMode && !siteBalanceEnough" class="ssrc-unavailable">
                <div class="ssrc-unavailable-icon warn">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h4 class="ssrc-unavailable-title">站点余额不足</h4>
                <p class="ssrc-unavailable-desc">分站账户余额不足，暂时无法充值，请联系分站客服处理。</p>
                <div v-if="siteContactText" class="ssrc-unavailable-agent">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>{{ siteContactText }}</span>
                </div>
                <p v-else class="ssrc-unavailable-agent">请先联系分站客服处理</p>
                <button type="button" class="ssrc-btn ssrc-btn-outline" @click="close">我知道了</button>
              </div>

              <!-- 无法在线充值 -->
              <div v-else-if="isAgentPayUnavailable" class="ssrc-unavailable">
                <div class="ssrc-unavailable-icon">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><path d="M12 15v3"/></svg>
                </div>
                <h4 class="ssrc-unavailable-title">暂不支持在线充值</h4>
                <p class="ssrc-unavailable-desc">您的上级代理尚未配置在线支付接口，当前无法使用在线充值。</p>
                <div v-if="rechargeConfig.agent_info" class="ssrc-unavailable-agent">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>请先联系上级代理「<b>{{ rechargeConfig.agent_info.nickname || '上级代理' }}</b>」开通在线充值</span>
                </div>
                <p v-else class="ssrc-unavailable-agent">请先联系您的上级代理开通在线充值</p>
                <p class="ssrc-unavailable-note">二三级用户充值由上级代理收款，平台不代收。上级代理开通支付后即可在线充值。</p>
                <button type="button" class="ssrc-btn ssrc-btn-outline" @click="close">我知道了</button>
              </div>

              <!-- 充值表单（简化：选金额 → 选支付方式 → 确认充值） -->
              <div v-else class="ssrc-form">
                <!-- 当前余额 -->
                <div class="ssrc-balance">
                  <span class="ssrc-balance-label">当前余额</span>
                  <b class="ssrc-balance-value">¥ {{ userMoneyText }}</b>
                </div>

                <!-- 分站在线支付信息：收款方为分站拥有者商户号 -->
                <div v-if="isSiteMode" class="ssrc-site-pay">
                  <div class="ssrc-site-pay-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    分站在线支付 · 支付后自动到账余额
                  </div>
                  <div v-if="sitePay.note" class="ssrc-site-pay-note">{{ sitePay.note }}</div>
                </div>

                <!-- 充值金额 -->
                <div class="ssrc-section">
                  <div class="ssrc-section-head">
                    <h3 class="ssrc-section-title">充值金额</h3>
                    <span v-if="hasRechargeGift" class="ssrc-section-hint">含 {{ rechargeRateText }} 到账倍率</span>
                  </div>
                  <div class="ssrc-amount-grid">
                    <button
                      v-for="amt in presetAmounts"
                      :key="amt"
                      type="button"
                      class="ssrc-amount-opt"
                      :class="{ active: Number(rechargeForm.money) === amt }"
                      @click="rechargeForm.money = amt"
                    >
                      <div class="ssrc-amount-opt-num">
                        <span class="ssrc-amount-opt-yen">¥</span>{{ amt }}
                      </div>
                    </button>
                  </div>
                  <div class="ssrc-amount-custom">
                    <span class="ssrc-amount-custom-prefix">¥</span>
                    <input
                      v-model="rechargeForm.money"
                      type="number"
                      min="1"
                      step="0.01"
                      placeholder="输入自定义金额"
                    />
                  </div>
                  <div v-if="hasRechargeGift" class="ssrc-amount-custom-gift">
                    实到 <b>¥{{ arrivedAmountText }}</b> · 赠送 <b class="ssrc-gift-accent">+¥{{ giftAmountText }}</b>
                  </div>
                </div>

                <!-- 支付方式 -->
                <div v-if="rechargeConfig.pay_ways?.length" class="ssrc-section">
                  <div class="ssrc-section-head">
                    <h3 class="ssrc-section-title">支付方式</h3>
                  </div>
                  <div class="ssrc-payway-list">
                    <label
                      v-for="w in rechargeConfig.pay_ways"
                      :key="w.pay_type"
                      class="ssrc-payway-opt"
                      :class="{ active: rechargeForm.pay_type === w.pay_type }"
                    >
                      <input type="radio" v-model="rechargeForm.pay_type" :value="w.pay_type" />
                      <span class="ssrc-payway-opt-icon" :class="w.pay_type">
                        <svg v-if="w.pay_type === 'wechat'" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8.7 13.4c-.3 0-.6-.2-.7-.5l-.4-1.2c-.1-.2 0-.5.2-.6.3-.2.7-.1.9.2l.5 1.1c.1.3 0 .7-.2.8-.1.1-.2.2-.3.2zm3.4 0c-.3 0-.6-.2-.7-.5l-.4-1.2c-.1-.2 0-.5.2-.6.3-.2.7-.1.9.2l.5 1.1c.1.3 0 .7-.2.8-.1.1-.2.2-.3.2z"/><path d="M9.3 4C5.3 4 2 6.8 2 10.3c0 2 1.1 3.7 2.9 4.9.2.1.3.3.2.5l-.4 1.4c0 .2 0 .4.2.5.1 0 .2.1.3.1.1 0 .2 0 .3-.1l1.5-.9c.2-.1.4-.1.6-.1.4.1.8.1 1.2.1h.5c-.1-.4-.2-.8-.2-1.2 0-2.9 2.8-5.2 6.2-5.2h.5C16.7 6 13.4 4 9.3 4zm5.4 6c-3 0-5.4 2-5.4 4.5 0 .5.1 1 .3 1.4.1.2 0 .5-.2.6l-1.1.6c-.2.1-.4.1-.6 0-.2-.1-.3-.3-.2-.5l.3-1c.1-.2 0-.4-.2-.5-1.4-.9-2.2-2.2-2.2-3.6 0-2.5 2.4-4.5 5.4-4.5s5.4 2 5.4 4.5-2.4 4.5-5.4 4.5z"/></svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 13.5c-.3 0-.6.2-.7.5l-.4 1.2c-.1.2 0 .5.2.6.1.1.2.1.3.1.3 0 .6-.2.7-.5l.4-1.2c.1-.2 0-.5-.2-.6-.1 0-.2-.1-.3-.1zm-3.4 0c-.3 0-.6.2-.7.5l-.4 1.2c-.1.2 0 .5.2.6.1.1.2.1.3.1.3 0 .6-.2.7-.5l.4-1.2c.1-.2 0-.5-.2-.6-.1 0-.2-.1-.3-.1z"/><path d="M21 7h-9c-1.7 0-3 1.3-3 3v6c0 1.7 1.3 3 3 3h9c1.7 0 3-1.3 3-3v-6c0-1.7-1.3-3-3-3zm-4.5 9.5c-2.5 0-4.5-1.8-4.5-4s2-4 4.5-4 4.5 1.8 4.5 4-2 4-4.5 4z"/></svg>
                      </span>
                      <span class="ssrc-payway-opt-name">{{ w.pay_name }}</span>
                      <span class="ssrc-payway-opt-desc">{{ w.pay_type === 'wechat' ? '扫码支付' : '网页端支付' }}</span>
                      <span class="ssrc-payway-opt-check">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      </span>
                    </label>
                  </div>
                  <div v-if="rechargeConfig.is_agent_pay" class="ssrc-notice">
                    <span class="ssrc-notice-icon">!</span>
                    <span>当前为代理充值模式，付款将直接到上级代理账户，平台不代收。付款完成后请等待代理确认到账。</span>
                  </div>
                </div>

                <div v-if="rechargeError" class="ssrc-error">{{ rechargeError }}</div>

                <button
                  type="button"
                  class="ssrc-pay-btn"
                  :disabled="rechargeLoading || !rechargeForm.money || !rechargeForm.pay_type"
                  @click="submitRecharge"
                >
                  <span v-if="rechargeLoading" class="ssrc-btn-spinner"></span>
                  <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="m9 15 2 2 4-4"/></svg>
                  <span v-if="isSiteMode">去支付 · ¥{{ Number(rechargeForm.money || 0).toFixed(2) }}</span>
                  <span v-else-if="hasRechargeGift">确认充值 · 实到 ¥{{ arrivedAmountText }}</span>
                  <span v-else>确认充值</span>
                </button>

                <p class="ssrc-foot-note">
                  <span v-if="isSiteMode">分站在线支付 · 收款方为分站商户号，支付后自动到账余额</span>
                  <span v-else-if="rechargeConfig.agent_info">收款方：{{ rechargeConfig.agent_info.nickname || '代理账户' }}</span>
                  <span v-else>平台收款 · 即时到账</span>
                </p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps(['modelValue'])
const emit = defineEmits(['update:modelValue', 'success'])

const api = useApi()
const toast = useToast()
const auth = useAuth()
const site = useSite()

const rechargeLoading = ref(false)
const rechargeError = ref('')
const rechargeConfig = ref({})
const rechargeForm = ref({ money: 100, pay_type: 'alipay' })
const presetAmounts = [50, 100, 200, 500, 1000, 2000]

// ===== 分站充值模式：系统平台不代收，从分站拥有者余额划扣 =====
const sitePay = computed(() => rechargeConfig.value?.site_pay || null)
const isSiteMode = computed(() => !!sitePay.value)
const sitePayEnabled = computed(() => !!sitePay.value?.enabled)
const siteBalanceEnough = computed(() => !!sitePay.value?.balance_enough)
// 分站客服联系方式（提示联系客服充值用）
const siteContactText = computed(() => {
  const cs = site.site.value?.customer_service || {}
  const parts = []
  if (cs.wechat) parts.push('客服微信：' + cs.wechat)
  if (cs.phone) parts.push('客服电话：' + cs.phone)
  return parts.join(' · ')
})

// 当前用户余额
const userMoneyText = computed(() => {
  const m = Number(auth.user.value?.user_money || 0)
  return m.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

// 到账倍率 / 赠送 / 实到（分站充值 1:1 到账，不使用倍率）
const rechargeRate = computed(() => {
  if (isSiteMode.value) return 1
  const rate = Number(rechargeConfig.value?.recharge_rate ?? 1)
  if (!rate || rate < 1) return 1
  return rate
})
const hasRechargeGift = computed(() => rechargeRate.value > 1)
const isAgentPayUnavailable = computed(() => {
  return !!(rechargeConfig.value?.is_agent_pay && !rechargeConfig.value?.pay_ways?.length)
})
const giftAmount = computed(() => {
  if (!hasRechargeGift.value) return 0
  const m = Number(rechargeForm.value?.money || 0)
  return Math.round(m * (rechargeRate.value - 1) * 100) / 100
})
const giftAmountText = computed(() => giftAmount.value.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
const arrivedAmount = computed(() => {
  const m = Number(rechargeForm.value?.money || 0)
  return Math.round(m * rechargeRate.value * 100) / 100
})
const arrivedAmountText = computed(() => arrivedAmount.value.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
const rechargeRateText = computed(() => Number(rechargeRate.value).toFixed(Number(rechargeRate.value) % 1 === 0 ? 0 : 1) + ' 倍')
function getPayWayName(payType) {
  return rechargeConfig.value.pay_ways?.find(x => x.pay_type === payType)?.pay_name || ''
}

// 支付渠道排序：支付宝第1、微信第2
function sortPayWays(ways) {
  if (!Array.isArray(ways) || ways.length <= 1) return ways
  const result = ways.slice()
  const moveToTop = (idx, rank) => {
    if (idx < 0) return
    const [item] = result.splice(idx, 1)
    result.splice(Math.min(rank, result.length), 0, item)
  }
  moveToTop(result.findIndex(w => String(w.pay_type).toLowerCase().includes('wechat')), 1)
  moveToTop(result.findIndex(w => String(w.pay_type).toLowerCase().includes('alipay')), 0)
  return result
}

// 打开时拉取充值配置
async function loadConfig() {
  rechargeError.value = ''
  const res = await api.get('/api/recharge/config')
  if (res.ok && res.data) {
    const cfg = { ...res.data }
    if (Array.isArray(cfg.pay_ways)) cfg.pay_ways = sortPayWays(cfg.pay_ways)
    rechargeConfig.value = cfg
    const ways = cfg.pay_ways || []
    if (ways.length && !ways.find(w => w.pay_type === rechargeForm.value.pay_type)) {
      const alipay = ways.find(w => String(w.pay_type).toLowerCase().includes('alipay'))
      rechargeForm.value.pay_type = alipay ? alipay.pay_type : ways[0].pay_type
    }
  } else {
    rechargeConfig.value = {}
  }
  if (!rechargeForm.value.pay_type) rechargeForm.value.pay_type = 'alipay'
}

watch(() => props.modelValue, (v) => {
  if (v) {
    if (!rechargeForm.value.money) rechargeForm.value.money = 100
    loadConfig()
  }
})

// 打开支付宝/微信收银台
function openPayGateway(config) {
  if (typeof config === 'string' && /<[a-z!]/i.test(config)) {
    const w = window.open('', '_blank')
    if (w) {
      w.document.write(config)
      w.document.close()
      return
    }
    const holder = document.createElement('div')
    holder.style.display = 'none'
    holder.innerHTML = config
    document.body.appendChild(holder)
    const form = holder.querySelector('form')
    if (form) form.submit()
    return
  }
  window.open(config, '_blank')
}

// 提交充值
async function submitRecharge() {
  if (rechargeLoading.value) return
  rechargeError.value = ''
  const money = Number(rechargeForm.value.money)
  if (!money || money <= 0) {
    rechargeError.value = '请输入有效充值金额'
    return
  }
  if (money < 1) {
    rechargeError.value = '充值金额不能低于 1 元'
    return
  }
  if (money > 50000) {
    rechargeError.value = '单笔充值金额不能超过 50000 元'
    return
  }
  if (!isSiteMode.value && !rechargeForm.value.pay_type) {
    rechargeError.value = '请选择支付方式'
    return
  }
  rechargeLoading.value = true
  try {
    const res = await api.post('/api/recharge/recharge', {
      money,
      pay_type: rechargeForm.value.pay_type,
    })
    if (res.ok && res.data) {
      if (res.data.from === 'recharge') {
        const pay = res.data.pay
        if (pay?.config) {
          openPayGateway(pay.config)
          close()
        } else {
          toast.success('订单已创建')
          close()
        }
      } else {
        toast.success('订单已创建')
        close()
      }
    } else {
      rechargeError.value = res.msg || '充值失败'
    }
  } catch (e) {
    rechargeError.value = e?.message || '充值失败，请稍后重试'
  } finally {
    rechargeLoading.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.ssrc-mask {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
}
.ssrc-card {
  width: 100%;
  max-width: 460px;
  max-height: calc(100vh - 48px);
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
  overflow: hidden;
}

/* 头部 */
.ssrc-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid #f1f5f9;
  flex: 0 0 auto;
}
.ssrc-head-left { display: flex; align-items: center; gap: 12px; }
.ssrc-head-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px; height: 38px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary-14, #e6f8f5), var(--primary-08, #f1fbf9));
  border: 1px solid var(--primary-300, #5eead4);
  color: var(--primary-600, #0d9488);
}
.ssrc-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; }
.ssrc-sub { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }
.ssrc-close {
  width: 30px; height: 30px;
  display: grid; place-items: center;
  border: none; border-radius: 50%;
  background: #f1f5f9; color: #64748b;
  cursor: pointer;
  transition: all .2s;
}
.ssrc-close:hover { background: #e2e8f0; color: #0f172a; }

/* 主体：固定 max-height 保证内容超长时可滚动（与个人中心充值弹窗一致） */
.ssrc-body {
  flex: 1;
  min-height: 0;
  max-height: 70vh;
  overflow-y: auto;
  padding: 16px 20px 20px;
}

/* 余额信息条 */
.ssrc-balance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
  margin-bottom: 16px;
}
.ssrc-balance-label { font-size: 12px; color: #94a3b8; }
.ssrc-balance-value { font-size: 15px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }

/* 分站线下充值信息卡 */
.ssrc-site-pay {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
}
.ssrc-site-pay-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 700;
  color: #92400e;
  margin-bottom: 8px;
}
.ssrc-site-pay-title svg { color: #f59e0b; flex-shrink: 0; }
.ssrc-site-pay-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  padding: 4px 0;
  font-size: 12.5px;
}
.ssrc-site-pay-row span { color: #a16207; flex-shrink: 0; }
.ssrc-site-pay-row b { color: #78350f; font-weight: 700; word-break: break-all; }
.ssrc-site-pay-note {
  margin-top: 6px;
  padding-top: 8px;
  border-top: 1px dashed #fcd34d;
  font-size: 12px;
  line-height: 1.6;
  color: #92400e;
  white-space: pre-wrap;
}

/* 区块 */
.ssrc-section { margin-bottom: 14px; }
.ssrc-section-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.ssrc-section-title { font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0; }
.ssrc-section-hint { margin-left: auto; font-size: 11px; color: #ea580c; }
.ssrc-section-hint em { font-style: normal; font-weight: 700; }

/* 金额选择 */
.ssrc-amount-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.ssrc-amount-opt {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  height: 58px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-family: inherit;
  transition: all .18s ease;
}
.ssrc-amount-opt:hover { border-color: var(--primary-500, #14b8a6); background: var(--primary-08, #f1fbf9); }
.ssrc-amount-opt.active {
  border-color: var(--primary-500, #14b8a6);
  background: linear-gradient(135deg, var(--primary-14, #e6f8f5), #fff 60%);
  box-shadow: 0 0 0 1px var(--primary-500, #14b8a6), 0 6px 16px -8px var(--primary-600, #0d9488);
}
.ssrc-amount-opt-num { font-size: 16px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.ssrc-amount-opt.active .ssrc-amount-opt-num { color: var(--primary-600, #0d9488); }
.ssrc-amount-opt-yen { font-size: 11px; color: #94a3b8; margin-right: 1px; }

/* 自定义金额 */
.ssrc-amount-custom {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  padding: 0 14px;
  height: 42px;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  transition: all .18s ease;
}
.ssrc-amount-custom:focus-within {
  border-color: var(--primary-500, #14b8a6);
  background: #fff;
  box-shadow: 0 0 0 3px var(--primary-14, #e6f8f5);
}
.ssrc-amount-custom-prefix { font-size: 15px; font-weight: 700; color: var(--primary-600, #0d9488); }
.ssrc-amount-custom input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  font-family: inherit;
}
.ssrc-amount-custom input::placeholder { color: #94a3b8; font-weight: 400; }
.ssrc-amount-custom-gift { margin-top: 6px; font-size: 11.5px; color: #c2410c; }
.ssrc-amount-custom-gift b { color: #ea580c; }

/* 支付方式 */
.ssrc-payway-list { display: flex; flex-direction: column; gap: 8px; }
.ssrc-payway-opt {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  transition: all .18s ease;
}
.ssrc-payway-opt:hover { border-color: #cbd5e1; }
.ssrc-payway-opt.active {
  border-color: var(--primary-500, #14b8a6);
  background: var(--primary-08, #f1fbf9);
  box-shadow: 0 0 0 1px var(--primary-500, #14b8a6);
}
.ssrc-payway-opt input { position: absolute; opacity: 0; pointer-events: none; }
.ssrc-payway-opt-icon {
  width: 34px; height: 34px;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 10px;
  flex-shrink: 0;
}
.ssrc-payway-opt-icon.wechat { background: rgba(7, 193, 96, 0.12); color: #07c160; }
.ssrc-payway-opt-icon.alipay { background: rgba(22, 119, 255, 0.1); color: #1677ff; }
.ssrc-payway-opt-name { font-size: 13.5px; font-weight: 700; color: #0f172a; }
.ssrc-payway-opt-desc { font-size: 12px; color: #94a3b8; }
.ssrc-payway-opt-check {
  margin-left: auto;
  width: 20px; height: 20px;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 50%;
  border: 1.5px solid #cbd5e1;
  color: transparent;
  transition: all .18s ease;
  flex-shrink: 0;
}
.ssrc-payway-opt.active .ssrc-payway-opt-check {
  background: var(--primary-500, #14b8a6);
  border-color: var(--primary-500, #14b8a6);
  color: #fff;
}
.ssrc-notice {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 8px;
  padding: 9px 12px;
  border-radius: 10px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  font-size: 12px;
  line-height: 1.6;
  color: #92400e;
}
.ssrc-notice-icon {
  width: 16px; height: 16px;
  flex-shrink: 0;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 50%;
  background: #f59e0b; color: #fff;
  font-size: 11px; font-weight: 800;
  margin-top: 1px;
}

/* 错误 */
.ssrc-error {
  margin-top: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-size: 12.5px;
}

/* 提交按钮 */
.ssrc-pay-btn {
  width: 100%;
  height: 44px;
  margin-top: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--primary-600, #0d9488));
  color: #fff;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 20px -8px var(--primary-600, #0d9488);
  transition: all .18s ease;
}
.ssrc-pay-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 12px 26px -8px var(--primary-600, #0d9488); }
.ssrc-pay-btn:disabled { opacity: .55; cursor: not-allowed; }
.ssrc-btn-spinner {
  width: 15px; height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  border-top-color: #fff;
  border-radius: 50%;
  animation: ssrc-spin .6s linear infinite;
}
@keyframes ssrc-spin { to { transform: rotate(360deg); } }

.ssrc-foot-note { margin: 10px 0 0; text-align: center; font-size: 11.5px; color: #94a3b8; }

/* 按钮通用 */
.ssrc-btn {
  height: 38px;
  padding: 0 18px;
  border-radius: 10px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all .18s ease;
}
.ssrc-btn-primary {
  border: none;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--primary-600, #0d9488));
  color: #fff;
}
.ssrc-btn-primary:hover { transform: translateY(-1px); }
.ssrc-btn-outline {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
}
.ssrc-btn-outline:hover { border-color: var(--primary-500, #14b8a6); color: var(--primary-600, #0d9488); }

/* 等待代理付款视图 */
.ssrc-pending { text-align: center; padding: 8px 4px; }
.ssrc-pending-icon {
  width: 76px; height: 76px;
  margin: 0 auto 12px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
}
.ssrc-pending-icon.pending { background: #fffbeb; color: #f59e0b; border: 1px solid #fde68a; }
.ssrc-pending-icon.paid { background: var(--primary-08, #f1fbf9); color: var(--primary-600, #0d9488); border: 1px solid var(--primary-14, #e6f8f5); }
.ssrc-pending-icon.timeout { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
.ssrc-pending-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px; }
.ssrc-pending-desc { font-size: 13px; color: #64748b; margin: 0 auto 14px; max-width: 300px; line-height: 1.6; }
.ssrc-pending-card {
  text-align: left;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 14px;
}
.ssrc-pending-amount {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 12px;
}
.ssrc-pending-amount-label { font-size: 12px; color: #94a3b8; }
.ssrc-pending-amount-value { font-size: 20px; font-weight: 800; color: #0f172a; }
.ssrc-pending-amount-value em { font-style: normal; font-size: 13px; color: #94a3b8; margin-right: 1px; }
.ssrc-pending-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ssrc-pending-cell { display: flex; flex-direction: column; gap: 2px; }
.ssrc-pending-cell-label { font-size: 11px; color: #94a3b8; }
.ssrc-pending-cell-value { font-size: 12.5px; color: #0f172a; font-weight: 600; }
.ssrc-pending-cell-value.mono { font-family: 'SF Mono', 'Monaco', 'Consolas', monospace; }
.ssrc-pending-agent {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 8px 10px;
  border-radius: 10px;
  background: var(--primary-08, #f1fbf9);
  border: 1px solid var(--primary-14, #e6f8f5);
  color: #0f766e;
  font-size: 12.5px;
}
.ssrc-pending-agent b { margin-left: auto; color: #0f172a; }
.ssrc-pending-actions { display: flex; gap: 10px; justify-content: center; }

/* 分站在线支付 - 微信二维码 */
.ssrc-qr {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin: 0 auto 14px;
  padding: 14px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  max-width: 270px;
}
.ssrc-qr img {
  width: 220px;
  height: 220px;
  display: block;
  border-radius: 8px;
}
.ssrc-qr-tip {
  font-size: 12px;
  color: #64748b;
}

/* 无法在线充值 */
.ssrc-unavailable { text-align: center; padding: 16px 6px; }
.ssrc-unavailable-icon {
  width: 80px; height: 80px;
  margin: 0 auto 14px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 24px;
  background: linear-gradient(135deg, #fef2f2, #fff 60%);
  border: 1px solid #fecaca;
  color: #ef4444;
}
.ssrc-unavailable-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px; }
.ssrc-unavailable-desc { font-size: 13px; color: #64748b; margin: 0 0 10px; }
.ssrc-unavailable-agent {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0 0 8px;
  padding: 8px 12px;
  border-radius: 10px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #c2410c;
  font-size: 12.5px;
}
.ssrc-unavailable-agent b { color: #ea580c; }
.ssrc-unavailable-note { font-size: 11.5px; color: #94a3b8; margin: 0 0 14px; line-height: 1.6; }

/* 过渡动画 */
.ssrc-fade-enter-active, .ssrc-fade-leave-active { transition: opacity .22s ease; }
.ssrc-fade-enter-from, .ssrc-fade-leave-to { opacity: 0; }
.ssrc-scale-enter-active, .ssrc-scale-leave-active { transition: all .22s ease; }
.ssrc-scale-enter-from, .ssrc-scale-leave-to { opacity: 0; transform: scale(.96); }

/* 窄屏适配 */
@media (max-width: 480px) {
  .ssrc-mask { padding: 12px; }
  .ssrc-card { max-height: calc(100vh - 24px); }
  .ssrc-body { max-height: calc(100vh - 110px); }
  .ssrc-amount-grid { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .ssrc-amount-opt { height: 54px; }
}
</style>
