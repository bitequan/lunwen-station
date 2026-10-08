<template>
  <div class="m-user">
    <!-- 个人卡 -->
    <section class="m-card m-user-card">
      <div class="m-user-head">
        <span class="m-avatar m-avatar--lg">
          <img v-if="user?.avatar" :src="user.avatar" alt="" />
          <template v-else>{{ avatarChar }}</template>
        </span>
        <div class="m-user-info">
          <div class="m-user-name">{{ user?.nickname || '未登录' }}</div>
          <div class="m-user-sub">{{ user?.create_time ? '注册于 ' + user.create_time : '登录后体验全部功能' }}</div>
        </div>
        <button v-if="!loggedIn" class="m-btn m-btn-primary m-btn-sm" @click="doLogin">登录</button>
      </div>
      <div class="m-user-money">
        <div class="m-user-money-label">账户余额（元）</div>
        <div class="m-user-money-row">
          <span class="m-price m-user-money-num"><span class="m-price-symbol">¥</span>{{ moneyText }}</span>
          <NuxtLink to="/m/recharge" class="m-btn m-btn-warn m-btn-sm">充值</NuxtLink>
        </div>
      </div>
    </section>

    <!-- 套餐余额 -->
    <div class="m-section-title">
      套餐余额
      <NuxtLink to="/m/packages" class="m-section-more">去购买
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </NuxtLink>
    </div>
    <section class="m-card">
      <div v-if="pkgLoading" class="m-loading" style="padding:24px 0">
        <div class="m-spinner"></div>
      </div>
      <template v-else-if="pkgItems.length">
        <div v-for="item in pkgItems" :key="item.key" class="m-pkg-row">
          <div class="m-pkg-row-top">
            <span class="m-pkg-name">{{ item.name }}</span>
            <span class="m-status" :class="item.active ? 'completed' : (item.expired ? 'failed' : 'neutral')">
              {{ item.active ? '可用' : (item.expired ? '已到期' : '空') }}
            </span>
          </div>
          <div class="m-pkg-num" :class="{ 'is-off': !item.active }">{{ item.text }}</div>
          <div class="m-pkg-sub">
            {{ item.sub }}
            <span v-if="item.expire_text" class="m-pkg-expire">{{ item.expire_text }}</span>
          </div>
        </div>
      </template>
      <div v-else class="m-pkg-empty">暂未开通套餐，可到「套餐商城」选购</div>
    </section>

    <!-- 菜单 -->
    <div class="m-section-title">常用服务</div>
    <section class="m-card" style="padding:4px 16px">
      <NuxtLink v-for="c in cells" :key="c.to" :to="c.to" class="m-cell">
        <span class="m-cell-icon" :style="{ background: c.bg }" v-html="c.icon"></span>
        <span class="m-cell-body">
          <span class="m-cell-title">{{ c.label }}</span>
          <span v-if="c.desc" class="m-cell-desc">{{ c.desc }}</span>
        </span>
        <span class="m-cell-arrow">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
      </NuxtLink>
    </section>

    <button v-if="loggedIn" class="m-btn m-btn-plain m-btn-block m-logout" @click="doLogout">退出登录</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '个人中心' })

const api = useApi()
const auth = useAuth()
const toast = useToast()
const login = useLoginModal()
const router = useRouter()

const loggedIn = computed(() => auth.isLoggedIn.value)
const user = computed(() => auth.user.value)
const avatarChar = computed(() => (user.value?.nickname || '用').slice(0, 1))
const moneyText = computed(() => Number(user.value?.user_money || 0).toFixed(2))

const pkgLoading = ref(false)
const pkgItems = ref([])

const IC = (d) =>
  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

const cells = [
  { label: '我的订单', desc: '论文 / PPT / 写作 / 降重 / 排版 / 充值', to: '/m/orders', bg: 'linear-gradient(135deg,#14b8a6,#0d9488)', icon: IC('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/>') },
  { label: '资金明细', desc: '余额变动记录', to: '/m/balance', bg: 'linear-gradient(135deg,#f59e0b,#d97706)', icon: IC('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>') },
  { label: '套餐商城', desc: '降重降AI / AI检测资源包', to: '/m/packages', bg: 'linear-gradient(135deg,#ec4899,#db2777)', icon: IC('<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>') },
  { label: '余额充值', desc: '支付宝 / 微信', to: '/m/recharge', bg: 'linear-gradient(135deg,#3b82f6,#2563eb)', icon: IC('<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>') },
  { label: '我的模板', desc: '排版私有模板管理', to: '/pc/user?tab=templates&pc=1', bg: 'linear-gradient(135deg,#0ea5e9,#0284c7)', icon: IC('<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>') },
]

function doLogin() {
  login.open(() => {
    loadPkgBalance()
  })
}

async function doLogout() {
  await auth.logout()
  toast.success('已退出登录')
  pkgItems.value = []
}

async function loadPkgBalance() {
  if (!auth.isLoggedIn.value) return
  pkgLoading.value = true
  try {
    const res = await api.get('/api/package/balance')
    if (res.ok && res.data) {
      pkgItems.value = Array.isArray(res.data.items) ? res.data.items : []
    }
  } catch (e) {} finally {
    pkgLoading.value = false
  }
}

onMounted(async () => {
  auth.restore()
  if (auth.isLoggedIn.value) {
    await auth.fetchUser()
    loadPkgBalance()
  }
})
</script>

<style scoped>
.m-user-card { padding: 0; overflow: hidden; }
.m-user-head {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 16px 14px;
}
.m-avatar--lg { width: 52px; height: 52px; font-size: 20px; }
.m-user-info { flex: 1; min-width: 0; }
.m-user-name {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.m-user-sub { margin-top: 2px; font-size: 12px; color: #94a3b8; }

.m-user-money {
  padding: 12px 16px 16px;
  border-top: 1px solid #f1f5f9;
  background: linear-gradient(180deg, #fafcfc, #fff);
}
.m-user-money-label { font-size: 12px; color: #94a3b8; }
.m-user-money-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 4px;
}
.m-user-money-num { font-size: 28px; }

/* 套餐余额行 */
.m-pkg-row {
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}
.m-pkg-row:last-child { border-bottom: none; padding-bottom: 4px; }
.m-pkg-row:first-child { padding-top: 2px; }
.m-pkg-row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.m-pkg-name { font-size: 14px; font-weight: 600; color: #0f172a; }
.m-pkg-num {
  margin-top: 4px;
  font-size: 21px;
  font-weight: 800;
  color: var(--primary-600, #0d9488);
  font-variant-numeric: tabular-nums;
}
.m-pkg-num.is-off { color: #94a3b8; }
.m-pkg-sub {
  margin-top: 2px;
  font-size: 12px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 8px;
}
.m-pkg-expire { color: #c2410c; }
.m-pkg-empty {
  padding: 18px 0;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}

.m-logout { margin-top: 16px; color: #b91c1c; border-color: #fee2e2; }
</style>
