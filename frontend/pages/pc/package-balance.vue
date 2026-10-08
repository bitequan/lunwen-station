<template>
  <div class="balance-page">
    <!-- 简洁页头：标题 + 说明 -->
    <header class="page-header">
      <h1 class="page-header__title">套餐余额</h1>
      <p class="page-header__desc">已购套餐剩余额度一览 · 使用后实时扣减</p>
    </header>

    <!-- 账户余额条 -->
    <section class="money-bar">
      <div class="money-bar__label">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span>账户余额</span>
      </div>
      <div class="money-bar__amount">¥ {{ moneyText }}</div>
      <NuxtLink to="/pc/user?tab=recharge" class="money-bar__link">去充值 →</NuxtLink>
    </section>

    <!-- 剩余套餐汇总 -->
    <section class="balance-body">
      <!-- 加载中 -->
      <div v-if="loading" class="state-box">
        <div class="spinner"></div>
        <p>加载套餐余额...</p>
      </div>

      <!-- 数据 -->
      <div v-else class="balance-grid">
        <div
          v-for="item in items"
          :key="item.key"
          class="balance-card"
          :class="{ 'is-empty': !item.active }"
        >
          <div class="balance-card__top">
            <span class="balance-card__name">{{ item.name }}</span>
            <span class="balance-card__tag" :class="tagClass(item)">{{ tagText(item) }}</span>
          </div>
          <div class="balance-card__num" :class="{ 'is-empty': !item.active && !item.expired }">
            {{ item.text }}
          </div>
          <div class="balance-card__sub">
            {{ item.sub }}
            <span v-if="item.expire_text" class="balance-card__expire" :class="{ 'is-expired': item.expired }">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              {{ item.expire_text }}
            </span>
          </div>
        </div>
      </div>

      <!-- 空状态兜底 -->
      <div v-if="!loading && items.length === 0" class="state-box">
        <div class="empty-icon">
          <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <p class="empty-title">暂未开通任何套餐</p>
        <p class="empty-desc">可到「套餐商城」选购降重降AI / AI检测资源包</p>
        <button class="empty-btn" @click="goShop">去购买套餐</button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

definePageMeta({ layout: 'console' })
useSeoMeta({
  title: '套餐余额 - AI写作助手',
  description: '查看已购套餐剩余额度与账户余额。',
})

const api = useApi()
const auth = useAuth()
const toast = useToast()

const loading = ref(false)
const money = ref(0)
const items = ref([])

const moneyText = computed(() => {
  return Number(money.value).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

// 余额条目标签：可用 / 已到期 / 空
function tagText(item) {
  if (item.active) return '可用'
  if (item.expired) return '已到期'
  return '空'
}
function tagClass(item) {
  if (item.active) return 'is-on'
  if (item.expired) return 'is-danger'
  return 'is-off'
}

async function loadBalance() {
  if (!auth.isLoggedIn.value) {
    useLoginModal().open(() => navigateTo('/pc/package-balance'))
    return
  }
  loading.value = true
  try {
    const res = await api.get('/api/package/balance')
    if (res.ok && res.data) {
      money.value = res.data.user_money || 0
      items.value = Array.isArray(res.data.items) ? res.data.items : []
    } else {
      throw new Error(res.msg || '加载失败')
    }
  } catch (e) {
    toast.error(e.message || '网络错误，请稍后重试')
  } finally {
    loading.value = false
  }
}

const onRefresh = () => { loadBalance() }

function goShop() {
  navigateTo('/pc/package-shop')
}

onMounted(() => {
  loadBalance()
  // 支持 console 布局顶栏刷新事件（如充值后同步刷新余额）
  window.addEventListener('console-refresh', onRefresh)
})
onBeforeUnmount(() => {
  window.removeEventListener('console-refresh', onRefresh)
})
</script>

<style scoped>
.balance-page {
  min-height: 100%;
  padding-bottom: 24px;
}

/* ===== 页头 ===== */
.page-header {
  display: flex;
  align-items: baseline;
  gap: 12px;
  flex-wrap: wrap;
  padding: 4px 2px 16px;
}
.page-header__title {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  color: var(--text, #1f2937);
}
.page-header__desc {
  margin: 0;
  font-size: 13px;
  color: var(--muted, #9ca3af);
}

/* ===== 账户余额条 ===== */
.money-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(13, 148, 136, 0.12), rgba(13, 148, 136, 0.04));
  border: 1px solid rgba(13, 148, 136, 0.22);
}
.money-bar__label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--muted, #64748b);
}
.money-bar__amount {
  font-size: 26px;
  font-weight: 800;
  color: var(--primary, #0d9488);
  letter-spacing: 0.2px;
}
.money-bar__link {
  margin-left: auto;
  font-size: 13px;
  font-weight: 600;
  color: var(--primary, #0d9488);
  white-space: nowrap;
}
.money-bar__link:hover {
  opacity: 0.8;
}

/* ===== 剩余套餐网格 ===== */
.balance-body {
  margin-top: 20px;
}
.balance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 14px;
}
.balance-card {
  padding: 18px 18px 16px;
  border-radius: 12px;
  background: var(--card, #fff);
  border: 1px solid var(--border, #eef0f3);
  transition: box-shadow 0.2s ease;
}
.balance-card:hover {
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.balance-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.balance-card__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #1f2937);
}
.balance-card__tag {
  font-size: 11px;
  line-height: 1;
  padding: 4px 8px;
  border-radius: 999px;
  font-weight: 600;
}
.balance-card__tag.is-on {
  color: #0d9488;
  background: rgba(13, 148, 136, 0.12);
}
.balance-card__tag.is-off {
  color: #94a3b8;
  background: rgba(148, 163, 184, 0.14);
}
.balance-card__tag.is-danger {
  color: #d97706;
  background: rgba(217, 119, 6, 0.14);
}
.balance-card__num {
  margin-top: 14px;
  font-size: 28px;
  font-weight: 800;
  color: var(--primary, #0d9488);
  line-height: 1.2;
}
.balance-card__num.is-empty {
  color: #cbd5e1;
}
.balance-card__sub {
  margin-top: 6px;
  font-size: 12px;
  color: var(--muted, #94a3b8);
  line-height: 1.5;
}
.balance-card__expire {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--primary, #0d9488);
}
.balance-card__expire.is-expired {
  color: #d97706;
}

/* ===== 状态通用 ===== */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 60px 0;
  color: var(--muted, #9ca3af);
}
.spinner {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 3px solid rgba(13, 148, 136, 0.15);
  border-top-color: var(--primary, #0d9488);
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-icon {
  color: #cbd5e1;
}
.empty-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text, #334155);
}
.empty-desc {
  font-size: 13px;
  color: var(--muted, #94a3b8);
}
.empty-btn {
  margin-top: 6px;
  padding: 9px 22px;
  border: none;
  border-radius: 8px;
  background: var(--primary, #0d9488);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
.empty-btn:hover {
  opacity: 0.9;
}
</style>