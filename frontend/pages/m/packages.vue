<template>
  <div class="m-packages">
    <div v-if="loading" class="m-loading">
      <div class="m-spinner"></div>
      <span>加载中...</span>
    </div>

    <template v-else-if="groups.length">
      <section v-for="g in groups" :key="g.type_code" class="m-pkg-group">
        <div class="m-section-title">{{ g.label }}</div>
        <div class="m-pkg-list">
          <article
            v-for="p in g.packages"
            :key="p.id"
            class="m-card m-pkg-card"
            :class="{ 'is-hot': isHot(g, p) }"
            @click="openConfirm(p)"
          >
            <span v-if="isHot(g, p)" class="m-pkg-hot">荐</span>
            <div class="m-pkg-card-top">
              <span class="m-pkg-card-name">{{ p.name }}</span>
              <span v-if="p.model_type_text" class="m-tag" :class="{ gold: Number(p.model_type) === 1 }">{{ p.model_type_text }}</span>
            </div>
            <div class="m-pkg-card-price">
              <span class="m-price"><span class="m-price-symbol">¥</span>{{ fmtMoney(payPrice(p)) }}</span>
              <span v-if="Number(p.original_price) > Number(p.price)" class="m-pkg-original">¥{{ fmtMoney(p.original_price) }}</span>
            </div>
            <div class="m-pkg-card-specs">
              <span v-if="p.duration_text" class="m-pkg-spec">{{ p.duration_text }}</span>
              <span v-if="p.unit_text" class="m-pkg-spec">{{ p.unit_text }}</span>
              <span v-if="p.hour_char_limit_text" class="m-pkg-spec">{{ p.hour_char_limit_text }}</span>
            </div>
            <button class="m-btn m-btn-primary m-btn-sm m-pkg-buy">立即购买</button>
          </article>
        </div>
      </section>
    </template>

    <section v-else class="m-card">
      <div class="m-empty">
        <div class="m-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <p class="m-empty-title">暂无可购套餐</p>
        <p class="m-empty-desc">套餐上架后将在此展示</p>
      </div>
    </section>

    <!-- 购买确认 -->
    <Transition name="m-fade">
      <div v-if="currentPkg" class="m-sheet-mask" @click.self="closeConfirm">
        <div class="m-sheet">
          <div class="m-sheet-head">
            <span class="m-sheet-title">确认购买</span>
            <button class="m-sheet-close" @click="closeConfirm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="m-confirm-pkg">
            <div class="m-confirm-pkg-name">{{ currentPkg.name }}</div>
            <div class="m-confirm-row"><span>应付金额</span><b class="m-price"><span class="m-price-symbol">¥</span>{{ payPrice(currentPkg).toFixed(2) }}</b></div>
            <div class="m-confirm-row"><span>账户余额</span><b>¥{{ Number(user?.user_money || 0).toFixed(2) }}</b></div>
          </div>
          <p v-if="insufficient" class="m-confirm-warn">余额不足，请先充值后再购买</p>
          <div class="m-confirm-actions">
            <NuxtLink to="/m/recharge" class="m-btn m-btn-plain" style="flex:1" @click="closeConfirm">去充值</NuxtLink>
            <button class="m-btn m-btn-primary" style="flex:1.6" :disabled="submitting || insufficient" @click="submitOrder">
              {{ submitting ? '支付中...' : '确认支付' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 购买成功 -->
    <Transition name="m-fade">
      <div v-if="successData" class="m-sheet-mask" @click.self="successData = null">
        <div class="m-sheet" style="text-align:center">
          <div class="m-success-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="m-success-title">购买成功，已实时到账</div>
          <div class="m-confirm-pkg" style="text-align:left">
            <div class="m-confirm-row"><span>套餐</span><b>{{ successData.name || (currentPkg && currentPkg.name) }}</b></div>
            <div class="m-confirm-row"><span>实付</span><b class="m-price"><span class="m-price-symbol">¥</span>{{ Number(successData.price || 0).toFixed(2) }}</b></div>
            <div class="m-confirm-row"><span>订单号</span><b class="m-success-sn" @click="copySn(successData.order_no)">{{ successData.order_no }}</b></div>
          </div>
          <button class="m-btn m-btn-primary m-btn-block" @click="successData = null">完成</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '套餐商城' })

const api = useApi()
const auth = useAuth()
const toast = useToast()
const login = useLoginModal()

const loading = ref(false)
const groups = ref([])
const currentPkg = ref(null)
const submitting = ref(false)
const successData = ref(null)

const user = computed(() => auth.user.value)
const insufficient = computed(() => {
  if (!currentPkg.value) return false
  return Number(user.value?.user_money || 0) < payPrice(currentPkg.value)
})

// 本站无代理分级，统一标准价
function payPrice(pkg) {
  return Number(pkg?.price || 0)
}

// 分组标题
const TYPE_LABELS = {
  paper: '论文套餐',
  jiangchong: '降重降AI资源包',
  ai_check: 'AI检测资源包',
  jc_package: '降重降AI资源包',
  aicheck_package: 'AI检测资源包',
  time: '时长套餐',
}

function isHot(group, pkg) {
  const recommended = (group.packages || []).find(p => Number(p.is_recommend) === 1)
  if (recommended) return pkg.id === recommended.id
  if (!group.packages || group.packages.length < 2) return false
  const hasDiscount = group.packages.some(p => Number(p.original_price) > Number(p.price))
  if (!hasDiscount) return false
  let maxSave = 0
  let hotId = null
  group.packages.forEach(p => {
    const save = Number(p.original_price) - Number(p.price)
    if (save > maxSave) { maxSave = save; hotId = p.id }
  })
  return pkg.id === hotId
}

function trimZero(s) {
  return String(s).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1')
}
function fmtMoney(v) {
  const n = Number(v) || 0
  if (Math.abs(n) >= 100000000) return trimZero((n / 100000000).toFixed(2)) + '亿'
  if (Math.abs(n) >= 10000) return trimZero((n / 10000).toFixed(2)) + '万'
  return Number.isInteger(n) ? String(n) : n.toFixed(2)
}

async function loadShop() {
  loading.value = true
  try {
    const res = await api.get('/api/package/lists')
    if (res.ok && res.data) {
      const list = Array.isArray(res.data.list) ? res.data.list : []
      groups.value = list.map(g => ({
        ...g,
        label: g.name || TYPE_LABELS[g.type_code] || g.type_code || '套餐',
      }))
    } else {
      toast.error(res.msg || '加载失败')
    }
  } catch (e) {} finally {
    loading.value = false
  }
}

function openConfirm(pkg) {
  if (!auth.isLoggedIn.value) {
    login.open(() => openConfirm(pkg))
    return
  }
  currentPkg.value = pkg
}

function closeConfirm() {
  if (submitting.value) return
  currentPkg.value = null
}

async function submitOrder() {
  if (!currentPkg.value || submitting.value || insufficient.value) return
  submitting.value = true
  try {
    const res = await api.post('/api/package/createOrder', { package_id: currentPkg.value.id })
    if (res.ok && res.data) {
      successData.value = { ...res.data, name: currentPkg.value.name }
      currentPkg.value = null
      auth.fetchUser()
    }
  } catch (e) {} finally {
    submitting.value = false
  }
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
  if (auth.isLoggedIn.value) await auth.fetchUser()
  loadShop()
})
</script>

<style scoped>
.m-pkg-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.m-pkg-card {
  position: relative;
  padding: 15px 16px;
}
.m-pkg-card.is-hot {
  border: 1px solid rgba(20, 184, 166, 0.35);
  box-shadow: 0 6px 22px rgba(20, 184, 166, 0.12);
}
.m-pkg-hot {
  position: absolute;
  top: 0;
  right: 0;
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border-radius: 0 16px 0 16px;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--primary-600, #0d9488));
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}
.m-pkg-card-top {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-right: 26px;
}
.m-pkg-card-name {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  flex: 1;
  min-width: 0;
}
.m-pkg-card-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-top: 8px;
}
.m-pkg-card-price .m-price { font-size: 22px; }
.m-pkg-original {
  font-size: 12px;
  color: #cbd5e1;
  text-decoration: line-through;
}
.m-pkg-card-specs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.m-pkg-spec {
  font-size: 11.5px;
  color: #64748b;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}
.m-pkg-buy {
  margin-top: 12px;
  width: 100%;
}

.m-confirm-pkg {
  background: #f8fafc;
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 14px;
}
.m-confirm-pkg-name {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: 1px dashed #e2e8f0;
}
.m-confirm-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13.5px;
  color: #64748b;
  padding: 4px 0;
}
.m-confirm-row b { color: #0f172a; font-weight: 700; }
.m-confirm-warn {
  margin: 0 0 12px;
  font-size: 12.5px;
  color: #b45309;
  background: rgba(245, 158, 11, 0.1);
  border-radius: 9px;
  padding: 8px 12px;
}
.m-confirm-actions {
  display: flex;
  gap: 10px;
}

.m-success-icon {
  width: 64px;
  height: 64px;
  margin: 6px auto 10px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}
.m-success-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 14px;
}
.m-success-sn {
  font-family: 'SF Mono', Consolas, monospace;
  font-size: 12.5px;
  word-break: break-all;
}
</style>
