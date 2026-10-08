<template>
  <div class="m-root">
    <!-- 顶部 Header：同步 console 顶栏 H5 设计（白毛玻璃 sticky 52px） -->
    <header class="m-header">
      <div class="m-header-left">
        <NuxtLink to="/m" class="m-header-brand">
          <img class="m-header-logo" :src="site.logo.value || '/pc/logo.png'" alt="logo" />
          <span class="m-header-name">{{ site.siteName.value || 'AI写作助手' }}</span>
        </NuxtLink>
        <!-- 二级页回退按钮（一级 tab 页不显示），置于站名右侧 -->
        <button v-if="isSubPage" class="m-header-back" type="button" aria-label="返回" @click="goBack">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
      </div>
      <div class="m-header-right">
        <template v-if="mounted && isLoggedIn">
          <NuxtLink to="/m/user" class="m-header-user">
            <span v-if="avatarUrl" class="m-header-avatar" :style="{ backgroundImage: 'url(' + avatarUrl + ')' }"></span>
            <span v-else class="m-header-avatar">{{ displayNameInitial }}</span>
            <span class="m-header-username">{{ displayName }}</span>
          </NuxtLink>
        </template>
        <button v-else class="m-header-login" type="button" @click="openLogin">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          登录
        </button>
      </div>
    </header>

    <!-- 页面内容 -->
    <main class="m-main">
      <slot />
    </main>

    <!-- 底部导航 -->
    <nav class="m-tabbar">
      <NuxtLink
        v-for="t in tabs"
        :key="t.to"
        :to="t.to"
        class="m-tab"
        :class="{ active: isActive(t) }"
      >
        <span class="m-tab-icon" v-html="t.icon"></span>
        <span class="m-tab-label">{{ t.label }}</span>
      </NuxtLink>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const route = useRoute()
const auth = useAuth()
const site = useSite()

// 登录弹窗：与 console 顶栏同走全局弹窗，不跳独立 /login 页
const login = useLoginModal()
const openLogin = () => {
  login.open(() => {
    if (import.meta.client) window.location.reload()
  })
}

const mounted = ref(false)
onMounted(() => { mounted.value = true })

const isLoggedIn = computed(() => !!auth.isLoggedIn.value)
const displayName = computed(() => auth.user.value?.nickname || '用户')
const displayNameInitial = computed(() => displayName.value.charAt(0).toUpperCase())
const avatarUrl = computed(() => auth.user.value?.avatar || '')

const I = (d) =>
  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

// 底部导航：首页 / 创作 / 工具 / 订单 / 我的
const tabs = [
  { to: '/m', label: '首页', icon: I('<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>') },
  { to: '/m/create', label: '创作', icon: I('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>') },
  { to: '/m/tools', label: '工具', icon: I('<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>') },
  { to: '/m/orders', label: '订单', icon: I('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/>') },
  { to: '/m/user', label: '我的', icon: I('<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>') },
]

function isActive(t) {
  // 归一化尾斜杠（静态托管 301 补斜杠，/m/autodoc/ 须与 /m/autodoc 同判）
  const path = route.path !== '/' ? route.path.replace(/\/+$/, '') : route.path
  if (t.to === '/m') return path === '/m'
  // 工具类页面（含各工具 /m 薄壳页）从工具箱进入，高亮「工具」tab
  if (t.to === '/m/tools') return M_TOOL_PATHS.includes(path)
  return path === t.to || path.startsWith(t.to + '/')
}

// 工具箱及全部工具薄壳页路径（新工具建 /m 页后在此登记）
const M_TOOL_PATHS = [
  '/m/tools', '/m/autodoc', '/m/aippt', '/m/ai-check', '/m/aigcreduceweight',
  '/m/paperweight', '/m/rewrite', '/m/createtitle', '/m/createoutline',
  '/m/wxlist', '/m/illustration', '/m/createchart', '/m/writing',
  '/m/proposal', '/m/task', '/m/internship', '/m/internshipdiary',
]

// ===== 二级页回退 =====
// 一级页白名单（底部 5 个 tab 根路径），其余（工具功能页/余额/充值/套餐等）均为二级页
const M_ROOT_PATHS = ['/m', '/m/create', '/m/tools', '/m/orders', '/m/user']
const isSubPage = computed(() => {
  const path = route.path !== '/' ? route.path.replace(/\/+$/, '') : route.path
  return !M_ROOT_PATHS.includes(path)
})
// 返回：有浏览历史则 back，直开落地页时按归属回对应一级 tab
function goBack() {
  const path = route.path.replace(/\/+$/, '')
  if (window.history.state?.back) {
    useRouter().back()
    return
  }
  let fallback = '/m'
  if (M_TOOL_PATHS.includes(path)) fallback = '/m/tools'
  else if (path.startsWith('/m/user') || ['/m/recharge', '/m/balance', '/m/packages'].includes(path)) fallback = '/m/user'
  useRouter().replace(fallback)
}
</script>
