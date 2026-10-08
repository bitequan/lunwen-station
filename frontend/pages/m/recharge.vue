<template>
  <div class="m-recharge">
    <!-- 余额展示 -->
    <section class="m-card m-bal-card">
      <div class="m-bal-label">当前余额（元）</div>
      <div class="m-bal-num"><span class="m-price"><span class="m-price-symbol">¥</span>{{ moneyText }}</span></div>
    </section>

    <!-- 金额 -->
    <section class="m-card">
      <div class="m-field">
        <div class="m-field-label">充值金额</div>
        <div class="m-chips">
          <button
            v-for="a in quickAmounts"
            :key="a"
            class="m-chip"
            :class="{ active: Number(money) === a }"
            @click="money = a"
          >¥{{ a }}</button>
        </div>
        <input
          v-model="money"
          type="number"
          inputmode="decimal"
          class="m-input"
          style="margin-top:10px"
          placeholder="自定义金额（0.01 - 50000）"
          min="0.01"
          max="50000"
        />
      </div>

      <div class="m-field">
        <div class="m-field-label">支付方式</div>
        <div v-if="payWays.length" class="m-chips">
          <button
            v-for="w in payWays"
            :key="w.pay_type"
            class="m-chip"
            :class="{ active: payType === w.pay_type }"
            @click="payType = w.pay_type"
          >{{ w.pay_name || w.pay_type }}</button>
        </div>
        <div v-else class="m-pay-empty">{{ configLoaded ? '暂无可用支付方式，请联系客服' : '支付方式加载中...' }}</div>
      </div>
    </section>

    <button class="m-btn m-btn-primary m-btn-block" style="margin-top:14px" :disabled="submitting" @click="submit">
      {{ submitting ? '创建订单中...' : '立即充值' }}
    </button>
    <p class="m-recharge-tip">充值成功后余额实时到账，可用于全部 AI 服务扣费。</p>

    <!-- 收银台弹层：二维码 / 等待支付 -->
    <Transition name="m-fade">
      <div v-if="qrPay" class="m-sheet-mask" @click.self="closeQr">
        <div class="m-sheet m-qr-sheet">
          <div class="m-sheet-head">
            <span class="m-sheet-title">{{ qrPay.payName }} · 扫码支付</span>
            <button class="m-sheet-close" @click="closeQr">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="m-qr-amount">¥ {{ Number(qrPay.amount).toFixed(2) }}</div>
          <div class="m-qr-box">
            <img v-if="qrPay.img" :src="qrPay.img" alt="支付二维码" class="m-qr-img" />
            <div v-else class="m-loading" style="padding:20px 0"><div class="m-spinner"></div></div>
          </div>
          <p class="m-qr-status" :class="{ 'is-paid': qrPay.status === 'paid' }">
            {{ qrPay.status === 'paid' ? '支付成功，余额已到账' : '等待支付中...（长按识别或截图扫码）' }}
          </p>
          <div class="m-qr-order" @click="copySn(qrPay.orderNo)">订单号：{{ qrPay.orderNo }}</div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '余额充值' })

const api = useApi()
const auth = useAuth()
const toast = useToast()
const login = useLoginModal()

const quickAmounts = [30, 50, 100, 200, 500, 1000]
const money = ref(100)
const payType = ref('')
const payWays = ref([])
const configLoaded = ref(false)
const submitting = ref(false)
const qrPay = ref(null)

let pollTimer = null

const moneyText = computed(() => Number(auth.user.value?.user_money || 0).toFixed(2))

async function loadConfig() {
  try {
    const res = await api.get('/api/recharge/config')
    if (res.ok && res.data) {
      const ways = Array.isArray(res.data.pay_ways) ? res.data.pay_ways.slice() : []
      // 支付宝第一、微信第二（与 PC 端排序一致）
      const idx = (kw) => ways.findIndex(w => String(w.pay_type).toLowerCase().includes(kw))
      const moveTop = (i, rank) => {
        if (i < 0) return
        const [item] = ways.splice(i, 1)
        ways.splice(Math.min(rank, ways.length), 0, item)
      }
      moveTop(idx('wechat'), 1)
      moveTop(ways.findIndex(w => String(w.pay_type).toLowerCase().includes('alipay')), 0)
      payWays.value = ways
      if (ways.length && !ways.find(w => w.pay_type === payType.value)) {
        payType.value = ways[0].pay_type
      }
    }
  } catch (e) {} finally {
    configLoaded.value = true
  }
}

async function submit() {
  if (submitting.value) return
  if (!auth.isLoggedIn.value) {
    toast.warning('请先登录')
    login.open()
    return
  }
  const m = Number(money.value)
  if (!m || m <= 0) { toast.error('请输入有效充值金额'); return }
  if (m < 0.01) { toast.error('充值金额不能低于 0.01 元'); return }
  if (m > 50000) { toast.error('单笔充值金额不能超过 50000 元'); return }
  if (!payType.value) { toast.error('请选择支付方式'); return }

  submitting.value = true
  try {
    const res = await api.post('/api/recharge/recharge', { money: m, pay_type: payType.value })
    if (res.ok && res.data) {
      const pay = res.data.pay
      const config = pay?.config
      if (typeof config === 'string' && /<[a-z!]/i.test(config)) {
        // HTML 收银台（如支付宝 PC 网页支付）：新窗口打开
        openPayGateway(config)
      } else if (typeof config === 'string' && config) {
        // 二维码：弹层展示 + 轮询到账
        const w = payWays.value.find(x => x.pay_type === payType.value)
        qrPay.value = {
          orderNo: res.data.order_no,
          amount: m,
          payName: w?.pay_name || '在线支付',
          img: config,
          status: 'pending',
        }
        startPoll(res.data.order_no)
      } else {
        openPayGateway(pay?.config || pay)
      }
    } else if (res.msg) {
      toast.error(res.msg)
    }
  } catch (e) {} finally {
    submitting.value = false
  }
}

// HTML 收银台：写文档自动提交（与 PC 端 openPayGateway 一致）
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

// 轮询充值订单是否到账（3 秒一次，最长 5 分钟）
function startPoll(orderNo) {
  stopPoll()
  const started = Date.now()
  pollTimer = setInterval(async () => {
    if (document.hidden) return
    if (Date.now() - started > 5 * 60 * 1000) { stopPoll(); return }
    try {
      const res = await api.post('/api/order/rechargeList', { page: 1, page_size: 1, keyword: orderNo })
      const row = res.ok && Array.isArray(res.data?.list) ? res.data.list[0] : null
      if (row && (Number(row.pay_status) === 1 || row.status_text === '已支付' || row.status_text === '已到账')) {
        if (qrPay.value && qrPay.value.orderNo === orderNo) qrPay.value.status = 'paid'
        stopPoll()
        toast.success('充值成功')
        auth.fetchUser()
      }
    } catch (e) {}
  }, 3000)
}
function stopPoll() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null }
}

function closeQr() {
  stopPoll()
  qrPay.value = null
}

async function copySn(sn) {
  if (!sn) return
  try {
    await navigator.clipboard.writeText(sn)
    toast.success('订单号已复制')
  } catch (e) {}
}

onMounted(async () => {
  auth.restore()
  if (auth.isLoggedIn.value) auth.fetchUser()
  else login.openIfNeeded()
  loadConfig()
})
onUnmounted(stopPoll)
</script>

<style scoped>
.m-bal-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px 16px;
  background: linear-gradient(150deg, rgba(20, 184, 166, 0.1), #fff 55%);
}
.m-bal-label { font-size: 12.5px; color: #94a3b8; }
.m-bal-num { margin-top: 4px; font-size: 30px; }

.m-pay-empty {
  padding: 10px 2px;
  font-size: 13px;
  color: #94a3b8;
}
.m-recharge-tip {
  margin: 12px 4px 0;
  font-size: 12px;
  color: #94a3b8;
  text-align: center;
}

.m-qr-sheet { text-align: center; }
.m-qr-amount {
  font-size: 30px;
  font-weight: 800;
  color: #f97316;
  font-variant-numeric: tabular-nums;
  margin-bottom: 12px;
}
.m-qr-box {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
}
.m-qr-img {
  width: 200px;
  height: 200px;
  object-fit: contain;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}
.m-qr-status {
  font-size: 13px;
  color: #94a3b8;
  margin: 8px 0 0;
}
.m-qr-status.is-paid {
  color: #16a34a;
  font-weight: 600;
}
.m-qr-order {
  margin-top: 10px;
  font-size: 12px;
  color: #94a3b8;
  font-family: 'SF Mono', Consolas, monospace;
  word-break: break-all;
}
</style>
