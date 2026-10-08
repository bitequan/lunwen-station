<template>
  <div class="shop-page">
    <!-- 简洁页头：仅标题 + 说明（对标 autodoc .header 语言） -->
    <header class="page-header">
      <h1 class="page-header__title">套餐商城</h1>
      <p class="page-header__desc">批量购买 · 单价更优 · 立即到账</p>
    </header>

    <!-- 商城列表 -->
    <div class="shop-body">
      <!-- 加载中 -->
      <div v-if="loading" class="state-box">
        <div class="spinner"></div>
        <p>加载套餐中...</p>
      </div>

      <!-- 空状态 -->
      <div v-else-if="shopList.length === 0" class="state-box">
        <div class="empty-icon">
          <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <p class="empty-title">暂无可购买的套餐</p>
        <p class="empty-desc">请稍后再来，或联系客服</p>
      </div>

      <!-- 套餐分组 -->
      <template v-else>
        <section
          v-for="group in shopList"
          :key="group.type_id"
          class="pkg-group"
        >
          <!-- 分组标题：简洁文字标签，不做重型容器 -->
          <div class="pkg-group__head">
            <h2 class="pkg-group__title">{{ group.type_name }}</h2>
            <span v-if="group.type_desc" class="pkg-group__desc">{{ group.type_desc }}</span>
          </div>

          <!-- 套餐卡片网格：价格视觉中心 -->
          <div class="pkg-grid">
            <div
              v-for="pkg in group.packages"
              :key="pkg.id"
              class="pkg-card"
              :class="{ 'pkg-card--hot': isHotPkg(group, pkg) }"
            >
              <span v-if="isHotPkg(group, pkg)" class="pkg-card__tag">超值</span>

              <!-- 头部：名称 + 模型标签同行，压缩卡片高度 -->
              <div class="pkg-card__head">
                <div class="pkg-card__name">{{ pkg.name }}</div>
                <span v-if="group.type_code === 'paper' && pkg.model_type_text" class="pkg-card__model-tag" :class="{ 'is-advanced': pkg.model_type === 1 }">
                  <svg v-if="pkg.model_type === 1" class="model-tag__icon" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.26L21 9.27l-4.5 4.38 1.06 6.23L12 16.9 6.44 19.88 7.5 13.65 3 9.27l6.1-1.01z"/></svg>
                  <svg v-else class="model-tag__icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>
                  {{ pkg.model_type_text }}
                </span>
              </div>

              <!-- 价格区：视觉中心 -->
              <div class="pkg-card__price-area">
                <!-- 主价 -->
                <div class="pkg-card__price-row">
                  <span class="pkg-card__price">{{ fmtMoney(payPrice(pkg)) }}</span>
                  <span class="pkg-card__price-label">标准价</span>
                </div>

                <!-- 营销原价划线（若有） -->
                <div v-if="hasOriginalPrice(pkg)" class="pkg-card__price-ladder">
                  <span class="pkg-card__ladder-item">
                    <span class="pkg-card__ladder-label">原价</span>
                    <span class="pkg-card__ladder-price is-strike">{{ fmtMoney(pkg.original_price) }}</span>
                  </span>
                </div>
              </div>

              <!-- 核心数量说明：购买后获得的单位 -->
              <div class="pkg-card__quantity">
                <template v-if="pkg.unit === 'time'">
                  {{ pkg.duration_text || formatDuration(pkg.quantity) }}<span class="pkg-card__unit">时长包</span>
                </template>
                <template v-else>
                  {{ formatQuantity(pkg.quantity) }}<span class="pkg-card__unit">{{ pkg.unit_text }}</span>
                </template>
              </div>

              <!-- 论文同类在期 → 锁定提示 -->
              <div v-if="pkg.locked" class="pkg-card__locked">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                {{ pkg.lock_reason }}
              </div>

              <!-- 行动区 -->
              <button
                class="pkg-card__btn"
                :class="{ 'is-locked': pkg.locked }"
                :disabled="pkg.locked"
                :title="pkg.locked ? pkg.lock_reason : ''"
                @click="openConfirm(pkg, group)"
              >
                <svg v-if="pkg.locked" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <template v-if="pkg.locked">已有在期套餐</template>
                <template v-else>立即购买</template>
              </button>
            </div>
          </div>
        </section>
      </template>

      <!-- 底部说明 -->
      <p class="shop-tip">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        套餐购买后立即到账；论文同类篇数套餐在剩余或未到期前不可重复购买。可到 <NuxtLink to="/pc/package-balance" class="shop-tip__link">套餐余额</NuxtLink> 查看剩余与到期时间
      </p>
    </div>

    <!-- 购买确认弹窗 -->
    <Transition name="sheet">
      <div v-if="confirmVisible" class="sheet-mask" @click.self="closeConfirm">
        <div class="sheet">
          <div class="sheet-header">
            <h3>确认购买</h3>
            <button class="sheet-close" @click="closeConfirm">×</button>
          </div>
          <div class="sheet-body">
            <div v-if="currentPkg" class="confirm-content">
                <div class="confirm-pkg-card">
                  <div class="confirm-pkg-head">
                    <div class="confirm-pkg-name">{{ currentPkg.name }}</div>
                    <span v-if="currentGroup?.type_code === 'paper' && currentPkg.model_type_text" class="confirm-pkg-model-tag" :class="{ 'is-advanced': currentPkg.model_type === 1 }">
                      <svg v-if="currentPkg.model_type === 1" class="model-tag__icon" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.26L21 9.27l-4.5 4.38 1.06 6.23L12 16.9 6.44 19.88 7.5 13.65 3 9.27l6.1-1.01z"/></svg>
                      <svg v-else class="model-tag__icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>
                      {{ currentPkg.model_type_text }}
                    </span>
                  </div>

                  <!-- 套餐详情：数值字段两列合并排布，减少弹窗高度 -->
                  <div class="confirm-pkg-detail">
                    <div class="cd-grid">
                      <div class="cd-col">
                        <span class="cd-label">套餐规格</span>
                        <span class="cd-value">
                          <template v-if="currentPkg.unit === 'time'">
                            {{ currentPkg.duration_text || formatDuration(currentPkg.quantity) }} 时长包
                          </template>
                          <template v-else>
                            {{ formatQuantity(currentPkg.quantity) }} {{ currentPkg.unit_text }}
                          </template>
                        </span>
                      </div>
                      <div v-if="currentPkg.max_chars_text" class="cd-col">
                        <span class="cd-label">单篇字符上限</span>
                        <span class="cd-value">{{ currentPkg.max_chars_text }}</span>
                      </div>
                      <div v-if="currentPkg.hour_char_limit_text" class="cd-col">
                        <span class="cd-label">每小时用量</span>
                        <span class="cd-value">{{ currentPkg.hour_char_limit_text }}</span>
                      </div>
                      <div v-if="currentPkg.validity_text" class="cd-col">
                        <span class="cd-label">有效期</span>
                        <span class="cd-value">{{ currentPkg.validity_text }}</span>
                      </div>
                    </div>
                    <div v-if="currentPkg.intro" class="cd-intro">
                      <span class="cd-label">套餐说明</span>
                      <span class="cd-value">{{ currentPkg.intro }}</span>
                    </div>
                  </div>
                </div>

              <div class="confirm-amount-box">
                <div class="amount-row">
                  <span class="amount-label">应付金额</span>
                  <span class="amount-value highlight">¥{{ payPrice(currentPkg).toFixed(2) }}</span>
                </div>
                <div class="amount-row">
                  <span class="amount-label">当前余额</span>
                  <span class="amount-value">¥{{ userMoneyText }}</span>
                </div>
                <div class="amount-row">
                  <span class="amount-label">支付后余额</span>
                  <span class="amount-value" :class="{ 'insufficient': insufficient }">
                    ¥{{ afterPayMoneyText }}
                  </span>
                </div>
              </div>

              <div class="pay-section">
                <span class="pay-section-title">支付方式</span>
                <div class="payway-list">
                  <label class="payway-item active">
                    <input type="radio" checked disabled />
                    <div class="payway-icon">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    <span>账户余额</span>
                    <span class="payway-tag">立即到账</span>
                  </label>
                </div>
              </div>

              <div v-if="insufficient" class="insufficient-tip">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>余额不足，请先充值</span>
                <button class="go-recharge-btn" @click="goRecharge">去充值</button>
              </div>

              <button
                class="submit-btn"
                :disabled="submitting || insufficient"
                @click="submitOrder"
              >
                <span v-if="submitting" class="btn-spinner"></span>
                <span v-else>确认支付 ¥{{ payPrice(currentPkg).toFixed(2) }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 购买成功弹窗 -->
    <Transition name="fade">
      <div v-if="successVisible" class="success-mask" @click="closeSuccess">
        <div class="success-card" @click.stop>
          <div class="success-icon">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <h3 class="success-title">购买成功</h3>
          <p class="success-desc">{{ successData.name }}</p>
          <div class="success-detail">
            <div class="detail-row">
              <span>数量</span>
              <b>
                <template v-if="successData.unit === 'time'">
                  {{ successData.duration_text || formatDuration(successData.quantity) }} 时长包
                </template>
                <template v-else>
                  {{ formatQuantity(successData.quantity) }} {{ successData.unit_text }}
                </template>
              </b>
            </div>
            <div class="detail-row">
              <span>实付</span>
              <b>¥{{ Number(successData.price).toFixed(2) }}</b>
            </div>
            <div class="detail-row">
              <span>订单号</span>
              <b class="order-no" @click="copyOrderNo(successData.order_no)">{{ successData.order_no }}</b>
            </div>
          </div>
          <button class="success-btn" @click="closeSuccess">完成</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({ layout: 'console' })
useSeoMeta({
  title: '套餐商城 - AI写作助手',
  description: '批量购买AI商品套餐，单价更优，立即到账。',
})

const auth = useAuth()
const toast = useToast()
const api = useApi()

// ===== 商城数据 =====
const shopList = ref([])
const loading = ref(false)

// 应付价（本站无代理分级，统一标准价）
function payPrice(pkg) {
  if (!pkg) return 0
  return Number(pkg.price)
}

function isHotPkg(group, pkg) {
  // 优先使用后台指定的推荐套餐（is_recommend=1）
  if (group.packages && group.packages.length) {
    const recommended = group.packages.find(p => Number(p.is_recommend) === 1)
    if (recommended) return pkg.id === recommended.id
  }
  // 未指定推荐时，回退到旧逻辑：有原价差价的组里，选差价最大的套餐
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

// 是否有营销原价（原价 > 标准价时才展示）
function hasOriginalPrice(pkg) {
  return Number(pkg.original_price) > Number(pkg.price)
}

function formatQuantity(q) {
  const n = Number(q) || 0
  if (n >= 10000) {
    return (n / 10000).toFixed(n % 10000 === 0 ? 0 : 1) + '万'
  }
  return String(n)
}

// 金额紧凑展示：万元及以上用「万 / 亿」缩写，避免卡片内溢出边界
function trimZero(s) {
  return String(s).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1')
}
function fmtMoney(v) {
  const n = Number(v) || 0
  const abs = Math.abs(n)
  if (abs >= 100000000) return '¥' + trimZero((n / 100000000).toFixed(2)) + '亿'
  if (abs >= 10000) return '¥' + trimZero((n / 10000).toFixed(2)) + '万'
  return '¥' + n.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 时长展示（秒数 → "1小时" / "1天" / "1月" / "1年"）
function formatDuration(seconds) {
  const s = Number(seconds) || 0
  if (s <= 0) return '0小时'
  const day = 86400
  const hour = 3600
  if (s % day === 0) {
    const days = s / day
    if (days === 30) return '1月'
    if (days === 90) return '3月'
    if (days === 365) return '1年'
    return days + '天'
  }
  if (s % hour === 0) {
    return (s / hour) + '小时'
  }
  return s + '秒'
}

const userMoneyText = computed(() => {
  const m = Number(auth.user.value?.user_money || 0)
  return m.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

const confirmVisible = ref(false)
const currentPkg = ref(null)
const currentGroup = ref(null)
const submitting = ref(false)

const afterPayMoneyText = computed(() => {
  if (!currentPkg.value) return '0.00'
  const after = Number(auth.user.value?.user_money || 0) - payPrice(currentPkg.value)
  return (after < 0 ? 0 : after).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})
const insufficient = computed(() => {
  if (!currentPkg.value) return false
  return Number(auth.user.value?.user_money || 0) < payPrice(currentPkg.value)
})

function openConfirm(pkg, group) {
  if (!auth.isLoggedIn.value) {
    toast.error('请先登录')
    useLoginModal().open(() => navigateTo('/pc/package-shop'))
    return
  }
  currentPkg.value = pkg
  currentGroup.value = group
  confirmVisible.value = true
}
function closeConfirm() {
  if (submitting.value) return
  confirmVisible.value = false
}

async function submitOrder() {
  if (!currentPkg.value || submitting.value || insufficient.value) return
  submitting.value = true
  try {
    const res = await api.post('/api/package/createOrder', {
      package_id: currentPkg.value.id,
    })
    if (res.ok && res.data) {
      confirmVisible.value = false
      // 合并套餐展示字段，便于成功弹窗显示 duration_text / hour_char_limit_text
      successData.value = {
        ...res.data,
        unit_text: currentPkg.value.unit_text,
        duration_text: currentPkg.value.duration_text,
        hour_char_limit_text: currentPkg.value.hour_char_limit_text,
      }
      successVisible.value = true
      auth.fetchUser()
    }
  } catch (e) {
    // useApi 已处理网络错误 toast
  } finally {
    submitting.value = false
  }
}

const successVisible = ref(false)
const successData = ref({})

function closeSuccess() {
  successVisible.value = false
}

async function copyOrderNo(sn) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(sn)
    } else {
      const ta = document.createElement('textarea')
      ta.value = sn
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
    toast.success('订单号已复制')
  } catch (e) {
    toast.error('复制失败')
  }
}

function goRecharge() {
  navigateTo('/pc/user')
}

async function loadShopList() {
  loading.value = true
  try {
    const res = await api.get('/api/package/lists')
    if (res.ok && res.data) {
      shopList.value = res.data.list || []
    } else {
      toast.error(res.msg || '加载失败')
    }
  } catch (e) {
    toast.error('网络错误，请稍后重试')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  auth.restore()
  if (auth.isLoggedIn.value) {
    await auth.fetchUser()
  }
  loadShopList()
})
</script>

<style scoped>
/* ============ 页面容器 ============ */
.shop-page {
  min-height: calc(100vh - 64px);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* ============ 简洁页头（对标 autodoc .header） ============ */
.page-header {
  padding: 4px 0 0;
}
.page-header__title {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  letter-spacing: -0.01em;
  line-height: 1.3;
}
.page-header__desc {
  margin: 6px 0 0;
  font-size: 13px;
  color: var(--gray-500, #64748b);
}

/* ============ 状态盒（加载/空） ============ */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 20px;
  text-align: center;
  background: var(--white, #fff);
  border: 1px solid var(--gray-100, #f1f5f9);
  border-radius: var(--radius-md, 12px);
}
.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--gray-200, #e2e8f0);
  border-top-color: var(--primary-500, #14b8a6);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-bottom: 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }
.state-box p {
  margin: 0;
  font-size: 13px;
  color: var(--gray-400, #94a3b8);
}
.empty-icon {
  color: var(--gray-300, #cbd5e1);
  margin-bottom: 12px;
}
.empty-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--dark-700, #334155);
  margin: 0 0 4px;
}
.empty-desc {
  font-size: 13px;
  color: var(--gray-400, #94a3b8);
  margin: 0;
}

/* ============ 套餐分组 ============ */
.pkg-group {
  margin-bottom: 8px;
}
.pkg-group__head {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 12px;
  padding: 0 2px;
}
.pkg-group__title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800, #1e293b);
}
.pkg-group__desc {
  font-size: 12px;
  color: var(--gray-400, #94a3b8);
}

/* ============ 套餐卡片网格（紧凑布局：套餐多时占用更少空间） ============ */
.pkg-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(172px, 1fr));
  gap: 12px;
}
.pkg-card {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 14px 14px 12px;
  background: var(--white, #fff);
  border: 1px solid var(--gray-200, #e2e8f0);
  border-radius: var(--radius-md, 12px);
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.pkg-card:hover {
  border-color: rgba(20, 184, 166, 0.4);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.pkg-card--hot {
  border-color: #fdba74;
  background: #fffaf3;
}
.pkg-card--hot:hover {
  border-color: #fb923c;
}
.pkg-card__tag {
  position: absolute;
  top: -1px;
  right: 10px;
  transform: translateY(-50%);
  font-size: 10px;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 999px;
  background: #f97316;
  color: #fff;
  line-height: 1.4;
}
/* 头部：名称 + 模型标签同行 */
.pkg-card__head {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  justify-content: space-between;
  margin-bottom: 8px;
  min-width: 0;
}
.pkg-card__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-700, #334155);
  overflow: visible;
  white-space: normal;
  overflow-wrap: break-word;
  line-height: 1.4;
  flex: 1;
  min-width: 0;
}
.pkg-card__model-tag {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  height: 20px;
  padding: 0 8px;
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 600;
  line-height: 1;
  border-radius: 5px;
  background: #eef2ff;
  color: #4f46e5;
  border: 1px solid #c7d2fe;
}
.pkg-card__model-tag.is-advanced {
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
}
.model-tag__icon {
  flex-shrink: 0;
}

/* 价格区：视觉中心 */
.pkg-card__price-area {
  margin-bottom: 8px;
}
.pkg-card__price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  flex-wrap: nowrap;
  min-width: 0;
}
.pkg-card__price {
  font-size: 24px;
  font-weight: 800;
  color: var(--dark-900, #0f172a);
  line-height: 1;
  letter-spacing: -0.02em;
  flex-shrink: 0;
}
.pkg-card__price-label {
  font-size: 10px;
  font-weight: 700;
  color: var(--primary-600, #0d9488);
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(20, 184, 166, 0.1);
  border: 1px solid rgba(20, 184, 166, 0.22);
  white-space: nowrap;
  flex-shrink: 0;
  transform: translateY(-3px);
}
/* 价格梯度：原价 / 标准价划线行，突出二次折扣力度 */
.pkg-card__price-ladder {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 3px 8px;
  margin-top: 3px;
  font-size: 11px;
}
.pkg-card__ladder-item {
  display: inline-flex;
  align-items: baseline;
  gap: 3px;
}
.pkg-card__ladder-label {
  color: var(--gray-400, #94a3b8);
  white-space: nowrap;
}
.pkg-card__ladder-price {
  font-size: 11px;
  color: var(--gray-500, #64748b);
  white-space: nowrap;
}
.pkg-card__ladder-price.is-strike {
  text-decoration: line-through;
  text-decoration-thickness: 1px;
  color: var(--gray-400, #94a3b8);
}
.pkg-card__save-amt {
  font-weight: 600;
  color: #ea580c;
  padding: 0 5px;
  border-radius: 4px;
  background: rgba(249, 115, 22, 0.1);
  white-space: nowrap;
}
/* 核心数量说明：仅一行，简洁 */
.pkg-card__quantity {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-700, #334155);
}
.pkg-card__unit {
  font-size: 10px;
  font-weight: 500;
  color: var(--gray-400, #94a3b8);
  margin-left: 2px;
}

/* 行动区 */
.pkg-card__btn {
  width: 100%;
  padding: 8px 0;
  margin-top: auto;
  border-radius: 8px;
  border: 1.5px solid var(--primary-500, #14b8a6);
  background: var(--white, #fff);
  color: var(--primary-600, #0d9488);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.pkg-card__btn:hover {
  background: var(--primary-500, #14b8a6);
  color: #fff;
}
.pkg-card--hot .pkg-card__btn {
  border-color: #f97316;
  color: #ea580c;
}
.pkg-card--hot .pkg-card__btn:hover {
  background: #f97316;
  color: #fff;
}

/* 论文同类在期锁定态 */
.pkg-card__locked {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0 0 10px;
  font-size: 11px;
  line-height: 1.4;
  color: #d97706;
  background: rgba(217, 119, 6, 0.1);
  padding: 6px 9px;
  border-radius: 8px;
}
.pkg-card__btn.is-locked {
  cursor: not-allowed;
  border-color: var(--gray-300, #d1d5db);
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-400, #9ca3af);
}
.pkg-card__btn.is-locked:hover {
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-400, #9ca3af);
}
.pkg-card--hot .pkg-card__btn.is-locked {
  border-color: var(--gray-300, #d1d5db);
  color: var(--gray-400, #9ca3af);
}
.pkg-card--hot .pkg-card__btn.is-locked:hover {
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-400, #9ca3af);
}

/* ============ 底部说明 ============ */
.shop-tip {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  color: var(--gray-400, #94a3b8);
  padding: 8px 0 4px;
  margin: 0;
}
.shop-tip svg {
  flex-shrink: 0;
}
.shop-tip__link {
  color: var(--primary-600, #0d9488);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}
.shop-tip__link:hover {
  text-decoration: underline;
}

/* ============ 购买确认弹窗（居中模态框） ============ */
.sheet-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.sheet {
  width: 100%;
  max-width: 480px;
  background: var(--white, #fff);
  border-radius: 20px;
  max-height: 90vh;
  max-height: 90dvh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.15);
  overflow: hidden;
}
.sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 16px;
  border-bottom: 1px solid var(--gray-100, #f1f5f9);
}
.sheet-header h3 {
  font-size: 17px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  margin: 0;
  letter-spacing: -0.01em;
}
.sheet-close {
  font-size: 22px;
  color: var(--gray-400, #94a3b8);
  width: 32px;
  height: 32px;
  line-height: 1;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.15s, color 0.15s;
  display: grid;
  place-items: center;
}
.sheet-close:hover {
  background: var(--gray-100, #f1f5f9);
  color: var(--gray-600, #475569);
}
.sheet-body {
  padding: 20px;
  overflow-y: auto;
}
.confirm-content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.confirm-pkg-card {
  background: linear-gradient(180deg, var(--gray-50, #f8fafc) 0%, var(--white, #fff) 100%);
  border: 1px solid var(--gray-100, #f1f5f9);
  border-radius: 14px;
  padding: 16px 18px;
}
.confirm-pkg-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}
.confirm-pkg-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  overflow: visible;
  white-space: normal;
  overflow-wrap: break-word;
  line-height: 1.4;
  flex: 1;
  min-width: 0;
}
.confirm-pkg-model-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 22px;
  padding: 0 9px;
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 600;
  line-height: 1;
  border-radius: 5px;
  background: #eef2ff;
  color: #4f46e5;
  border: 1px solid #c7d2fe;
}
.confirm-pkg-model-tag.is-advanced {
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
}

/* 套餐详情：数值字段两列合并排布 */
.confirm-pkg-detail {
  display: flex;
  flex-direction: column;
}
.cd-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 16px;
  border-top: 1px dashed var(--gray-100, #f1f5f9);
  padding-top: 10px;
}
.cd-col {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.cd-label {
  font-size: 11px;
  color: var(--gray-400, #94a3b8);
}
.cd-value {
  font-size: 12px;
  font-weight: 600;
  color: var(--dark-700, #334155);
  line-height: 1.4;
  overflow-wrap: break-word;
}
.cd-intro {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed var(--gray-100, #f1f5f9);
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.cd-intro .cd-value {
  font-weight: 500;
  color: var(--gray-500, #64748b);
  line-height: 1.5;
}
.confirm-amount-box {
  background: var(--gray-50, #f8fafc);
  border-radius: 14px;
  padding: 6px 16px;
  border: 1px solid var(--gray-100, #f1f5f9);
}
.amount-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--gray-100, #f1f5f9);
}
.amount-row:last-child {
  border-bottom: none;
}
.amount-label {
  font-size: 13px;
  color: var(--gray-500, #64748b);
}
.amount-value {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800, #1e293b);
}
.amount-value.highlight {
  font-size: 18px;
  color: var(--primary-600, #0d9488);
}
.amount-value.insufficient {
  color: #dc2626;
}
.pay-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pay-section-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600, #475569);
}
.payway-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.payway-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border: 1.5px solid var(--gray-200, #e2e8f0);
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: var(--dark-800, #1e293b);
  transition: border-color 0.15s, background 0.15s;
}
.payway-item:hover {
  border-color: var(--gray-300, #cbd5e1);
}
.payway-item.active {
  border-color: var(--primary-500, #14b8a6);
  background: rgba(20, 184, 166, 0.04);
}
.payway-item input {
  accent-color: var(--primary-500, #14b8a6);
}
.payway-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600, #0d9488);
  display: grid;
  place-items: center;
}
.payway-tag {
  margin-left: auto;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600, #0d9488);
}
.insufficient-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(239, 68, 68, 0.05);
  border: 1px solid rgba(239, 68, 68, 0.15);
  color: #dc2626;
  font-size: 13px;
}
.insufficient-tip svg {
  flex-shrink: 0;
}
.go-recharge-btn {
  margin-left: auto;
  padding: 6px 14px;
  border-radius: 8px;
  border: none;
  background: #dc2626;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.go-recharge-btn:hover {
  background: #b91c1c;
}
.submit-btn {
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  border: none;
  background: var(--primary-500, #14b8a6);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s, box-shadow 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
}
.submit-btn:hover:not(:disabled) {
  background: var(--primary-600, #0d9488);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.28);
}
.submit-btn:active:not(:disabled) {
  transform: scale(0.98);
}
.submit-btn:disabled {
  background: var(--gray-200, #e2e8f0);
  color: var(--gray-400, #94a3b8);
  cursor: not-allowed;
  box-shadow: none;
}
.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

/* sheet 动画 - 居中弹窗缩放淡入 */
.sheet-enter-active, .sheet-leave-active {
  transition: opacity 0.25s ease;
}
.sheet-enter-active .sheet, .sheet-leave-active .sheet {
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.25s ease;
}
.sheet-enter-from, .sheet-leave-to {
  opacity: 0;
}
.sheet-enter-from .sheet, .sheet-leave-to .sheet {
  transform: scale(0.95);
  opacity: 0;
}

/* ============ 成功弹窗 ============ */
.success-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.success-card {
  width: 100%;
  max-width: 380px;
  background: var(--white, #fff);
  border-radius: 20px;
  padding: 28px 24px 24px;
  text-align: center;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.15);
}
.success-icon {
  width: 68px;
  height: 68px;
  margin: 0 auto 16px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  display: grid;
  place-items: center;
}
.success-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}
.success-desc {
  font-size: 13px;
  color: var(--gray-500, #64748b);
  margin: 0 0 16px;
}
.success-detail {
  background: var(--gray-50, #f8fafc);
  border-radius: 14px;
  padding: 6px 16px;
  margin-bottom: 18px;
  text-align: left;
  border: 1px solid var(--gray-100, #f1f5f9);
}
.detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--gray-100, #f1f5f9);
  font-size: 13px;
}
.detail-row:last-child {
  border-bottom: none;
}
.detail-row span {
  color: var(--gray-500, #64748b);
}
.detail-row b {
  color: var(--dark-800, #1e293b);
  font-weight: 600;
}
.detail-row .order-no {
  font-family: monospace;
  font-size: 11px;
  cursor: pointer;
  transition: color 0.15s;
}
.detail-row .order-no:hover {
  color: var(--primary-600, #0d9488);
}
.success-btn {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: none;
  background: var(--primary-500, #14b8a6);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, box-shadow 0.15s;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
}
.success-btn:hover {
  background: var(--primary-600, #0d9488);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.28);
}

/* fade 动画 */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* ============ 响应式 ============ */
@media (max-width: 640px) {
  .pkg-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .pkg-card {
    padding: 12px 11px 10px;
  }
  .pkg-card__price {
    font-size: 21px;
  }
}
</style>
