<template>
  <div class="console-layout">
    <template v-if="mounted">
    <!-- 手机端遮罩层：抽屉打开时显示 -->
    <Transition name="drawer-fade">
      <div v-if="isMobile && drawerOpen" class="sidebar-mask" @click="closeDrawer"></div>
    </Transition>

    <aside class="sidebar" v-if="!embedMode" :class="{ 'drawer-open': isMobile && drawerOpen }">
      <div class="sidebar-bg-layer"></div>
      <div class="sidebar-orb sidebar-orb-teal"></div>
      <div class="sidebar-orb sidebar-orb-orange"></div>
      <div class="sidebar-grid-pattern"></div>

      <!-- 手机端抽屉关闭按钮：仅 ≤900px 且抽屉打开时显示 -->
      <button v-if="isMobile" class="drawer-close" :class="{ 'show': drawerOpen }" @click="closeDrawer" aria-label="关闭菜单">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>

      <NuxtLink to="/pc/create" class="brand" @click="closeDrawer">
        <div class="brand-logo-wrap">
          <img class="brand-logo" :src="site.logo.value || defaultLogo" :alt="site.siteName.value || 'AI写作助手'" />
          <div class="brand-logo-glow"></div>
        </div>
        <span class="brand-name">{{ site.siteName.value || 'AI写作助手' }}</span>
      </NuxtLink>

      <nav class="side-menu">
        <template v-for="item in filteredMenuItems" :key="item.to || item.id">
          <!-- 多级菜单（有 children 时） -->
          <div v-if="item.children && item.children.length" class="menu-group">
            <button
              class="group-title"
              :class="{ open: openGroups.includes(item.id), 'has-active-child': item.children.some(child => isActive(child)) }"
              @click="toggleGroup(item.id)"
            >
              <span class="menu-icon-chip">
                <svg class="group-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="item.icon"></svg>
              </span>
              <span class="group-label">{{ item.label }}</span>
              <svg class="group-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div v-show="openGroups.includes(item.id)" class="group-children">
              <template v-for="child in item.children" :key="child.to">
                <NuxtLink
                  v-if="!child.onlyLevel || (auth.user.value?.level || 0) === child.onlyLevel"
                  :to="child.to"
                  class="menu-item"
                  :class="{ active: isActive(child) }"
                  @click="closeDrawer"
                >
                  <span class="menu-dot"></span>
                  <span class="menu-label">{{ child.label }}</span>
                </NuxtLink>
              </template>
            </div>
          </div>

          <!-- 外链菜单（新窗口打开） -->
          <a
            v-else-if="item.external && (!item.onlyLevel || (auth.user.value?.level || 0) === item.onlyLevel)"
            :href="item.external"
            target="_blank"
            rel="noopener noreferrer"
            class="menu-item solo"
            @click="closeDrawer"
          >
            <span class="menu-icon-chip">
              <svg class="menu-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="item.icon"></svg>
            </span>
            <span class="menu-label">{{ item.label }}</span>
            <span v-if="item.verifyTag" class="menu-verify">{{ item.verifyTag }}</span>
          </a>

          <!-- 单级菜单（其他） -->
          <NuxtLink
            v-else-if="!item.onlyLevel || (auth.user.value?.level || 0) === item.onlyLevel"
            :to="item.to"
            class="menu-item solo"
            :class="{ active: isActive(item), 'menu-item--highlight': item.highlight }"
            @click="closeDrawer"
          >
            <span class="menu-icon-chip" :class="{ 'menu-icon-chip--highlight': item.highlight }">
              <svg class="menu-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="item.icon"></svg>
            </span>
            <span class="menu-label">{{ item.label }}</span>
            <span v-if="item.badge" class="menu-badge">{{ item.badge }}</span>
          </NuxtLink>
        </template>
      </nav>

      <div class="side-user-card">
        <NuxtLink v-if="isLoggedIn" to="/pc/user" class="user-card-main" @click="closeDrawer">
          <span class="user-card-avatar-wrap">
            <span v-if="avatarUrl" class="user-card-avatar" :style="{ backgroundImage: 'url(' + avatarUrl + ')' }"></span>
            <span v-else class="user-card-avatar fallback">{{ displayNameInitial }}</span>
            <span class="user-card-status"></span>
          </span>
          <div class="user-card-info">
            <span class="user-card-name">{{ displayName }}</span>
            <span class="user-card-meta">{{ accountText || '—' }}</span>
          </div>
        </NuxtLink>
        <button v-else class="user-card-main" type="button" @click="openLogin">
          <span class="user-card-avatar-wrap">
            <span class="user-card-avatar fallback">{{ displayNameInitial }}</span>
            <span class="user-card-status"></span>
          </span>
          <div class="user-card-info">
            <span class="user-card-name">{{ displayName }}</span>
            <span class="user-card-meta">点击登录</span>
          </div>
        </button>
        <button v-if="!isLoggedIn" class="user-card-action" title="登录" @click="openLogin">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        </button>
        <button v-else class="user-card-action" @click="handleLogout" title="退出登录">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </aside>

    <main class="main" :class="{ 'main--embed': embedMode }">
      <header class="topbar" v-if="!embedMode">
        <div class="topbar-left">
          <!-- 手机端汉堡菜单按钮：仅 ≤900px 显示 -->
          <button v-if="isMobile" class="topbar-hamburger" :class="{ active: drawerOpen }" @click="toggleDrawer" aria-label="切换菜单">
            <span class="hamburger-box">
              <span class="hamburger-inner"></span>
            </span>
          </button>
          <!-- 页面图标 + 名称（在前） -->
          <div class="topbar-page-icon-wrap">
            <svg class="topbar-page-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="currentPage.icon"></svg>
          </div>
          <span class="topbar-page-name">{{ currentPage.label }}</span>
          <!-- 二级页面回退按钮：仅在子路由页面显示（在后） -->
          <span v-if="parentRoute" class="topbar-back-divider"></span>
          <button v-if="parentRoute" class="topbar-back" @click="goToParent" :title="`返回${parentRoute.label}`">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          </button>
        </div>
        <div class="topbar-right" v-if="mounted">
            <button class="topbar-refresh" :class="{ spinning: refreshing }" @click="handleRefresh" title="刷新当前页数据">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              <span class="refresh-text">刷新</span>
            </button>
            <div class="user-divider"></div>
            <button v-if="!isLoggedIn" class="topbar-login" type="button" @click="openLogin">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
              登录
            </button>
            <button v-else class="user-trigger" @click="toggleUserMenu">
              <span v-if="avatarUrl" class="user-avatar" :style="{ backgroundImage: 'url(' + avatarUrl + ')' }"></span>
              <span v-else class="user-avatar fallback">{{ displayNameInitial }}</span>
              <span class="user-name">{{ displayName }}</span>
              <svg class="user-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :class="{ open: userMenuOpen }"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <Transition name="dropdown">
              <div v-if="userMenuOpen" class="user-dropdown">
                <div class="dropdown-header">
                  <span class="dropdown-name">{{ displayName }}</span>
                  <span class="dropdown-account">{{ accountText }}</span>
                </div>
                <NuxtLink to="/pc/user" class="dropdown-item" @click="userMenuOpen = false">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  个人中心
                </NuxtLink>
                <NuxtLink to="/pc/user?tab=settings" class="dropdown-item" @click="userMenuOpen = false">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                  账号设置
                </NuxtLink>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item danger" @click="handleLogout">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  退出登录
                </button>
              </div>
            </Transition>
        </div>
        <span v-else class="user-name ssr-placeholder">用户</span>
      </header>

      <div class="console-content-wrap">
        <slot />
        <Transition name="page-refresh-fade">
          <div v-if="refreshing" class="page-refresh-overlay">
            <svg class="page-refresh-ring" viewBox="0 0 60 60" aria-hidden="true">
              <defs>
                <linearGradient id="ring-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#0d9488" stop-opacity="0.95"/>
                  <stop offset="55%" stop-color="#14b8a6" stop-opacity="0.55"/>
                  <stop offset="100%" stop-color="#14b8a6" stop-opacity="0.08"/>
                </linearGradient>
              </defs>
              <!-- 底层细环: 极淡，增加层次 -->
              <circle cx="30" cy="30" r="24" fill="none" stroke="#14b8a6" stroke-width="1.2" stroke-opacity="0.12"/>
              <!-- 主环: 渐变缺口圆环，旋转 -->
              <circle class="page-refresh-ring-track" cx="30" cy="30" r="24" fill="none" stroke="url(#ring-gradient)" stroke-width="4.2" stroke-linecap="round"/>
              <!-- 内环: 反向慢速，增加立体感 -->
              <circle class="page-refresh-ring-inner" cx="30" cy="30" r="16" fill="none" stroke="#0d9488" stroke-width="2.4" stroke-opacity="0.35" stroke-linecap="round"/>
            </svg>
            <p class="page-refresh-text">正在刷新</p>
          </div>
        </Transition>
      </div>
    </main>
    </template>
    <div v-else class="console-loading">
      <div class="loading-ring">
        <img class="loading-logo-img" :src="site.logo.value || defaultLogo" alt="AI写作助手" />
      </div>
      <p class="loading-text">正在加载工作台…</p>
    </div>
    <!-- 公共公告弹窗由 app.vue 全局挂载，此处仅触发检查逻辑 -->
    <!-- 登录弹窗由 app.vue 全局挂载（唯一实例，useLoginModal 单例控制） -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuth } from '~/composables/useAuth'
// 默认 logo 用 public 静态路径（管理员在后台上传 logo 后会直接替换该文件），
// 首屏/loading 无需等待站点配置接口即显示正确的 logo；source/public/logo.png 为默认底版
const defaultLogo = (useRuntimeConfig().app?.baseURL || '/') + 'logo.png'

const route = useRoute()
const router = useRouter()
const auth = useAuth()
// 分站站点配置：根据功能开关过滤侧边栏菜单
const site = useSite()
// 公告弹窗：登录后/刷新后检查一次 + 启动强制弹窗轮询
const announcement = useAnnouncement()
const toast = useToast()

// mounted 标记：SSR 阶段为 false，客户端 onMounted 后置 true
const mounted = ref(false)

// 手机端抽屉式侧边栏：isMobile 通过 matchMedia 检测，drawerOpen 控制开合
// PC 端(≥901px) 仍是固定 248px 侧边栏；手机端(≤900px) 改为可收起展开的抽屉
const isMobile = ref(false)
const drawerOpen = ref(false)
let mediaQuery = null

const toggleDrawer = () => {
  if (!isMobile.value) return
  drawerOpen.value = !drawerOpen.value
  // 打开时锁 body 滚动，避免背景滚动穿透
  if (typeof document !== 'undefined') {
    document.body.style.overflow = drawerOpen.value ? 'hidden' : ''
  }
}

const closeDrawer = () => {
  if (!drawerOpen.value) return
  drawerOpen.value = false
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
}

// 刷新触发器：layout 通过 window 自定义事件通知 page 重新加载数据
const refreshing = ref(false)

// 嵌入模式（分站工作台 iframe 内嵌）：?embed=1
// 隐藏侧边栏与 topbar、跳过登录跳转，仅渲染 slot 页面内容，供分站复用主站功能页
const embedMode = computed(() => {
  if (typeof window === 'undefined') return false
  const p = new URLSearchParams(window.location.search)
  return p.get('embed') === '1' || p.get('embed') === 'true'
})

const handleRefresh = () => {
  if (refreshing.value) return
  refreshing.value = true
  // 派发自定义事件，page 监听后重新加载
  window.dispatchEvent(new CustomEvent('console-refresh'))
  // loading 时长 1200ms，结束后恢复
  setTimeout(() => { refreshing.value = false }, 1200)
}

// 当前路径和 tab：用 ref 存储，onMounted 时从 window.location 直接读取
// 避免 computed 在 SSR 阶段缓存错误值
const currentPath = ref('/')
const currentTab = ref('overview')
const currentTemplateId = ref(0)  // 模板编辑模式 id,> 0 表示编辑模式

// 从浏览器实际 URL 同步路径和 tab（去掉 baseURL 前缀）
function syncUrlState() {
  if (typeof window === 'undefined') return
  const fullPath = window.location.pathname
  const base = '/pc'
  let path = fullPath.startsWith(base) ? (fullPath.slice(base.length) || '/') : fullPath
  // Nuxt 路由会自动追加 trailing slash(如 /create/),需去除末尾斜杠(根路径除外)
  // 否则 isActive 中 path === '/pc/create' 会因 path='/pc/create/' 而不匹配
  if (path.length > 1 && path.endsWith('/')) {
    path = path.slice(0, -1)
  }
  currentPath.value = path
  const params = new URLSearchParams(window.location.search)
  currentTab.value = params.get('tab') || 'overview'
  currentTemplateId.value = Number(params.get('id')) || 0
  // 通用逻辑:遍历所有分组,若当前页面命中某分组的子项,则自动展开该分组
  // 这样刷新任何页面都能保持对应分组展开,无需为每个分组硬编码条件
  menuItems.forEach(item => {
    if (item.children && item.children.some(child => isActive(child))) {
      if (!openGroups.value.includes(item.id)) {
        openGroups.value = [...openGroups.value, item.id]
      }
    }
  })
}

// 路由变化时（点击菜单导航或 router.replace）始终从 window.location 读取
// 不使用 route 对象，避免预渲染 payload 中的错误状态覆盖正确值
// 路由变化时同时收起手机端抽屉（点击菜单项已 closeDrawer，这里兜底处理浏览器前进后退）
watch(() => route.fullPath, () => {
  syncUrlState()
  closeDrawer()
})

// 菜单数据：单级菜单（无 children）或多级菜单（有 children）
// featureKey 对应后端功能开关 key（AgentSite::featureOptions），分站按开关过滤
const menuItems = [
  { to: '/pc/create', label: 'AI论文', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>', productCodes: ['gjlw'], featureKey: 'create' },
  { to: '/pc/writing', label: '写作中心', icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>', productCodes: ['kaiti', 'rws', 'sx', 'sxrz'], featureKey: 'writing' },
  { to: '/pc/autodoc', label: '格式重排', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M8 17h4"/><path d="M9 11V7l3 2-3 2z"/>', productCodes: ['autodoc'], featureKey: 'autodoc' },
  { to: '/pc/aippt', label: 'AIPPT生成', icon: '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>', productCodes: ['ppt'], featureKey: 'aippt' },
  { to: '/pc/ai-check', label: 'AI检测', icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>', productCodes: ['aicheck'], featureKey: 'ai_check' },
  { to: '/pc/tools/aigcreduceweight', label: 'AI降重', icon: '<path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/><path d="M8 12h8"/><path d="M12 8v8"/>', productCodes: ['tools_aigcreduceweight'], featureKey: 'reduce_weight' },
  { to: '/pc/tools', label: '小工具', icon: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', productCodes: ['tools_wxlist', 'tools_rewrite', 'tools_illustration', 'tools_chart', 'tools_createtitle', 'tools_createoutline'], featureKey: 'tools' },
  {
    id: 'package-shop',
    label: '套餐商城',
    icon: '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
    featureKey: 'package_shop',
    children: [
      { to: '/pc/package-balance', label: '套餐余额', icon: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
      { to: '/pc/package-shop', label: '购买套餐', icon: '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>' },
    ]
  },
  {
    id: 'orders',
    label: '订单中心',
    icon: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    featureKey: 'orders',
    children: [
      { to: '/pc/orders/paper', label: '论文订单', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>' },
      { to: '/pc/orders/ppt', label: 'PPT订单', icon: '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>' },
      { to: '/pc/orders/write', label: '写作订单', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>' },
      { to: '/pc/orders/autodoc', label: '格式重排订单', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M8 17h4"/>' },
      { to: '/pc/orders/jiangchong', label: '降重订单', icon: '<path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/><path d="M8 12h8"/><path d="M12 8v8"/>' },
      { to: '/pc/orders/recharge', label: '充值订单', icon: '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>' },
    ]
  },
  {
    id: 'my-templates',
    label: '我的模板',
    icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>',
    featureKey: 'templates',
    children: [
      { to: '/pc/user?tab=templates', label: '论文模板列表', icon: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>' },
      { to: '/pc/template-mode', label: '创建论文模板', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/>' },
    ]
  },

  
  {
    id: 'user-center',
    label: '个人中心',
    icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    children: [
      { to: '/pc/user', label: '账户总览', icon: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>' },
      { to: '/pc/user?tab=recharge', label: '余额充值', icon: '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/><circle cx="12" cy="15" r="2"/>' },
      { to: '/pc/user?tab=settings', label: '账号设置', icon: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>' },
      { to: '/pc/user?tab=balance-log', label: '金额变动', icon: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
      { to: '/pc/package-balance', label: '套餐余额', icon: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>' },
    ]
  },
]

// 按商品配置排序侧栏菜单：商品管理「设为默认/首页默认」商品对应菜单置顶，
// 其余按关联商品的最小 sort_order 升序；未关联商品的菜单项（格式重排/
// 套餐商城/订单中心/我的模板/个人中心）保持原顺序排在所有已有商品菜单之后。
const orderedMenuItems = computed(() => {
  const products = site.site.value?.menu_products || []
  const withProduct = []
  const withoutProduct = []
  menuItems.forEach((item, index) => {
    const codes = item.productCodes || []
    let hasProduct = false
    let isDefault = false
    let minSort = Infinity
    for (const code of codes) {
      const p = products.find(pp => pp.code === code)
      if (!p) continue
      hasProduct = true
      if (p.is_default) isDefault = true
      if (p.sort_order < minSort) minSort = p.sort_order
    }
    if (hasProduct) {
      withProduct.push({ item, index, isDefault, minSort })
    } else {
      withoutProduct.push(item)
    }
  })
  withProduct.sort((a, b) => {
    if (a.isDefault !== b.isDefault) return a.isDefault ? -1 : 1
    if (a.minSort !== b.minSort) return a.minSort - b.minSort
    return a.index - b.index
  })
  return [...withProduct.map(x => x.item), ...withoutProduct]
})

// 分站功能开关过滤：主站显示全部菜单；分站过滤掉未开启的功能
// 按 featureKey 匹配（featureKey 缺省或未定义时视为始终显示）
const filteredMenuItems = computed(() => {
  const base = orderedMenuItems.value
  if (!site.site.value?.is_sub_site) return base
  return base.filter(item => {
    if (!item.featureKey || !item.id) return true
    return site.featureEnabled(item.featureKey)
  })
})

// 展开的分组（默认不展开任何分组，点击时再展开）
const openGroups = ref([])

const toggleGroup = (id) => {
  if (openGroups.value.includes(id)) {
    openGroups.value = openGroups.value.filter(g => g !== id)
  } else {
    openGroups.value = [...openGroups.value, id]
  }
}

// 判断菜单项是否激活
function isActive(item) {
  const path = currentPath.value
  // /user?tab=xxx 类型的菜单项
  if (item.to.startsWith('/pc/user?tab=')) {
    const expectedTab = item.to.split('tab=')[1]
    if (path === '/pc/user' && currentTab.value === expectedTab) return true
    // 编辑模式(/template?id=xxx)是从「我的模板」入口进来的,高亮该项
    if (expectedTab === 'templates' && path === '/pc/template' && currentTemplateId.value > 0) return true
    return false
  }
  // 工作台（/user 不带 tab）：只在 tab 为 overview 或未指定时激活
  if (item.to === '/pc/user') {
    return path === '/pc/user' && currentTab.value === 'overview'
  }
  // 模板制作(/template)仅在创建模式(path=/template 且无 id)下高亮
  if (item.to === '/pc/template-mode') {
    // 模式选择页
    if (path === '/pc/template-mode') return true
    // 范文上传页
    if (path === '/pc/template-fanwen') return true
    // 参数填写页：仅在创建模式(path=/template 且无 id)下高亮，编辑模式高亮"论文模板列表"
    if (path === '/pc/template' && currentTemplateId.value === 0) return true
    return false
  }
  // 小工具：/tools 及其子路径都高亮该菜单项
  // 但 /tools/aigcreduceweight 已独立为一级菜单,访问它时不高亮"小工具"
  if (item.to === '/pc/tools' && path.startsWith('/pc/tools')) {
    if (path === '/pc/tools/aigcreduceweight' || path.startsWith('/pc/tools/aigcreduceweight/')) {
      return false
    }
    return true
  }
  // 写作中心：/writing 及其子生成器（/writing/proposal 等）都高亮该菜单项
  // 但 /create（AI论文）是独立功能,访问它时不高亮"写作中心"
  if (item.to === '/pc/writing') {
    if (path === '/pc/writing') return true
    return ['/pc/writing/proposal', '/pc/writing/task', '/pc/writing/internship', '/pc/writing/internshipdiary'].includes(path)
  }
  return path === item.to
}

// 当前页面信息
const allItems = computed(() => menuItems.flatMap(item => {
  return item.children ? item.children : [item]
}))
const currentPage = computed(() => {
  const active = allItems.value.find(item => isActive(item))
  if (!active) return allItems.value[0]
  return active
})

// 二级页面回退：当路径包含 2+ 段时（如 /tools/createchart），计算父级路由
// /tools/xxx → /tools（小工具）；/orders/xxx → /user（工作台，因无 /orders 页面）
// 例外：/tools/aigcreduceweight 已独立为一级菜单,不显示回退按钮
const parentRoute = computed(() => {
  const path = currentPath.value
  const segments = path.split('/').filter(Boolean)

  // 新建模板二级页（参数填写 / 上传范文）回退到模式选择页
  if (path === '/pc/template' || path === '/pc/template-fanwen') {
    // /template 处于编辑模式(id>0)时来自「我的模板」，回退到论文模板列表
    if (path === '/pc/template' && currentTemplateId.value > 0) {
      return { path: '/pc/user?tab=templates', label: '我的模板' }
    }
    return { path: '/pc/template-mode', label: '创建论文模板' }
  }

  if (segments.length < 2) return null

  // AI降重 是独立一级菜单,不需要"返回小工具"回退按钮
  if (path === '/pc/tools/aigcreduceweight') return null

  // 写作中心入口页自身是独立一级菜单,不显示回退按钮
  if (path === '/pc/writing') return null

  // 写作中心子生成器（/writing/proposal 等）回退到写作中心入口页
  if (['/pc/writing/proposal', '/pc/writing/task', '/pc/writing/internship', '/pc/writing/internshipdiary'].includes(path)) {
    return { path: '/pc/writing', label: '写作中心' }
  }

  const module = segments[0]
  const parentPath = '/' + module

  // /orders/* 无独立首页，回退到个人中心
  if (module === 'orders') {
    return { path: '/pc/user?tab=balance-log', label: '个人中心' }
  }

  // 查找父级菜单项以获取标签
  const parentItem = menuItems.find(item => item.to === parentPath)
  if (parentItem) {
    return { path: parentPath, label: parentItem.label }
  }

  return { path: parentPath, label: '返回' }
})

function goToParent() {
  if (parentRoute.value) {
    navigateTo(parentRoute.value.path)
  }
}

// 用户信息（已脱敏：不再使用 account/sn/id 等价字段作为显示名或账号行）
const isLoggedIn = computed(() => !!auth.isLoggedIn.value)
const displayName = computed(() => auth.user.value?.nickname || '用户')

// 登录弹窗（header/侧栏登录按钮统走全局弹窗，不跳独立 /login 页）
const login = useLoginModal()
const openLogin = () => {
  login.open(handleLoginSuccess)
}
// 登录成功：刷新当前页以加载登录态真实数据（侧栏/顶栏已是响应式，刷新保证页面数据一致）
const handleLoginSuccess = () => {
  if (import.meta.client) {
    window.location.reload()
  }
}
const accountText = computed(() => auth.user.value?.mobile || auth.user.value?.email || '')
const displayNameInitial = computed(() => {
  const n = displayName.value
  return n ? n.charAt(0).toUpperCase() : 'U'
})
const avatarUrl = computed(() => auth.user.value?.avatar || '')

// 用户下拉菜单
const userMenuOpen = ref(false)
const toggleUserMenu = (e) => {
  e.stopPropagation()
  userMenuOpen.value = !userMenuOpen.value
}
const onDocClick = () => { userMenuOpen.value = false }

// ESC 键关闭抽屉
const onKeydown = (e) => {
  if (e.key === 'Escape' && drawerOpen.value) {
    closeDrawer()
  }
}

// 屏幕尺寸变化处理：跨越断点时同步 isMobile，并复位抽屉状态
const onMediaChange = (e) => {
  isMobile.value = e.matches
  // 切回 PC 端时强制收起抽屉并恢复 body 滚动
  if (!isMobile.value && drawerOpen.value) {
    closeDrawer()
  }
}

onMounted(async () => {
  auth.restore()
  // 拉取站点配置（分站判断 + 功能开关 + 配色主题），失败不影响主站正常使用
  try { await site.fetchSite() } catch (e) {}
  // 从浏览器实际 URL 读取路径和 tab（解决预渲染 hydration 后 route 状态不正确的问题）
  syncUrlState()
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKeydown)

  // 初始化响应式断点检测：≤900px 视为手机端
  if (window.matchMedia) {
    mediaQuery = window.matchMedia('(max-width: 900px)')
    isMobile.value = mediaQuery.matches
    // 兼容 Safari < 14: addListener 已废弃但更广兼容
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', onMediaChange)
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(onMediaChange)
    }
  }

  // 本次部署不登录即渲染工作台供查看（"预览模式"）；顶部/侧栏提供登录入口再真正操作。
  // 已登录才拉取受鉴权保护的用户信息与登录后公告；未登录跳过，仅渲染页面壳。
  if (auth.isLoggedIn.value) {
    try { await auth.fetchUser() } catch (e) {}
    try { announcement.checkOnLogin() } catch (e) {}
  }

  mounted.value = true
})
onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKeydown)
  // 离开工作台时停止公告轮询，避免定时器泄漏
  announcement.stopPolling()
  if (mediaQuery) {
    if (mediaQuery.removeEventListener) {
      mediaQuery.removeEventListener('change', onMediaChange)
    } else if (mediaQuery.removeListener) {
      mediaQuery.removeListener(onMediaChange)
    }
    mediaQuery = null
  }
  // 兜底恢复 body 滚动
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})

// 登出
async function handleLogout() {
  userMenuOpen.value = false
  await auth.logout()
  toast.success('已退出登录')
  router.replace('/pc')
}
</script>

<style scoped>
.console-layout {
  min-height: 100vh;
  background: #f8fafc;
}

/* 加载占位屏：SSR + 初始 hydration 阶段显示，避免 hydration mismatch 500 闪烁 */
.console-loading {
  position: fixed;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 24px;
  background: #f8fafc;
  z-index: 9999;
}

/* logo + 环绕转圈 */
.loading-ring {
  position: relative;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}
/* 外圈：旋转的渐变弧 */
.loading-ring::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 3px solid #e2e8f0;
  border-top-color: #14b8a6;
  border-right-color: #14b8a6;
  animation: loading-spin 0.9s linear infinite;
}
/* 内圈：反向旋转的浅弧，增加层次 */
.loading-ring::after {
  content: '';
  position: absolute;
  inset: 6px;
  border-radius: 50%;
  border: 2px solid transparent;
  border-bottom-color: #5eead4;
  animation: loading-spin 1.4s linear infinite reverse;
}
.loading-logo-img {
  width: 38px;
  height: 38px;
  border-radius: 9px;
  animation: loading-pulse 1.8s ease-in-out infinite;
}
.loading-text {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  letter-spacing: 0.5px;
  animation: loading-fade 1.6s ease-in-out infinite;
}
@keyframes loading-spin { to { transform: rotate(360deg); } }
@keyframes loading-pulse { 0%,100% { opacity: 0.55; transform: scale(0.92); } 50% { opacity: 1; transform: scale(1); } }
@keyframes loading-fade { 0%,100% { opacity: 0.4; } 50% { opacity: 0.85; } }

/* ============ Sidebar（深色质感） ============ */
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 248px;
  background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
  border-right: 1px solid rgba(51, 65, 85, 0.5);
  display: flex;
  flex-direction: column;
  z-index: 100;
  overflow: hidden;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.25), 1px 0 0 rgba(20, 184, 166, 0.08);
}

/* 顶部渐变装饰线 */
.sidebar::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg,
    rgba(20, 184, 166, 0.6) 0%,
    rgba(94, 234, 212, 0.2) 30%,
    rgba(249, 115, 22, 0.2) 70%,
    rgba(249, 115, 22, 0.4) 100%);
  z-index: 2;
  pointer-events: none;
}

/* 多层径向渐变底色——深色背景下光晕更突出 */
.sidebar-bg-layer {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 30% 0%, rgba(20, 184, 166, 0.18) 0%, transparent 50%),
    radial-gradient(circle at 80% 100%, rgba(249, 115, 22, 0.12) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20, 184, 166, 0.05) 0%, transparent 60%);
  pointer-events: none;
  z-index: 0;
}

/* 模糊光球——深色背景下更通透 */
.sidebar-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  pointer-events: none;
  z-index: 0;
}

.sidebar-orb-teal {
  width: 220px;
  height: 220px;
  background: rgba(20, 184, 166, 0.22);
  top: -90px;
  left: -70px;
}

.sidebar-orb-orange {
  width: 200px;
  height: 200px;
  background: rgba(249, 115, 22, 0.15);
  bottom: -70px;
  right: -60px;
}

/* 细微网格纹理 */
.sidebar-grid-pattern {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
  background-size: 24px 24px;
  pointer-events: none;
  z-index: 0;
  opacity: 0.8;
}

/* ============ 品牌区 ============ */
.brand {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  height: 64px;
  padding: 0 20px;
  border-bottom: 1px solid rgba(51, 65, 85, 0.4);
  color: #f1f5f9;
  flex-shrink: 0;
}

.brand-logo-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-logo {
  width: 30px;
  height: 30px;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 2px 8px rgba(20, 184, 166, 0.4));
}

/* logo 光晕——深色下更醒目 */
.brand-logo-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.4) 0%, transparent 70%);
  filter: blur(6px);
}

.brand-name {
  font-size: 16px;
  font-weight: 800;
  background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.01em;
}

/* 侧栏菜单滚动容器：位于品牌区与用户卡片之间，超长时纵向滚动 */
.side-menu {
  position: relative;
  z-index: 1;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 12px 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  /* Firefox 滚动条 */
  scrollbar-width: thin;
  scrollbar-color: rgba(20, 184, 166, 0.35) transparent;
}

.side-menu::-webkit-scrollbar {
  width: 6px;
}
.side-menu::-webkit-scrollbar-track {
  background: transparent;
  margin: 4px 0;
}
.side-menu::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(20, 184, 166, 0.35) 0%, rgba(51, 65, 85, 0.4) 100%);
  border-radius: 3px;
  border: 1px solid transparent;
  background-clip: padding-box;
  transition: background 0.2s ease;
}
.side-menu::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, rgba(20, 184, 166, 0.6) 0%, rgba(20, 184, 166, 0.4) 100%);
  background-clip: padding-box;
}
.side-menu::-webkit-scrollbar-thumb:active {
  background: linear-gradient(180deg, rgba(20, 184, 166, 0.8) 0%, rgba(20, 184, 166, 0.5) 100%);
  background-clip: padding-box;
}

/* ============ 菜单分组 ============ */
.menu-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

/* 图标芯片——给图标加圆角背景，增加视觉重量 */
.menu-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.07);
  flex-shrink: 0;
  transition: all 0.2s ease;
}

/* 分组标题——与一级菜单同级，但用 chevron 表示可展开 */
.group-title {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: 1px solid transparent;
  background: transparent;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  color: #dbe4ee;
  cursor: pointer;
  transition: color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  text-align: left;
  letter-spacing: 0.2px;
}

.group-title:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

.group-title.open {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.04);
}

.group-title:hover .menu-icon-chip,
.group-title.open .menu-icon-chip {
  background: rgba(20, 184, 166, 0.12);
  border-color: rgba(20, 184, 166, 0.2);
}

.group-icon {
  color: #94a3b8;
  transition: color 0.18s ease;
}

.group-title:hover .group-icon,
.group-title.open .group-icon {
  color: #5eead4;
}

.group-label {
  flex: 1;
}

.group-chevron {
  flex-shrink: 0;
  transition: transform 0.25s ease, color 0.2s ease;
  color: #94a3b8;
}

.group-title:hover .group-chevron {
  color: #5eead4;
}

.group-title.open .group-chevron {
  transform: rotate(180deg);
  color: #5eead4;
}

/* 分组标题下有子项处于激活态时，分组标题也高亮 */
.group-title.has-active-child {
  color: #ffffff;
  background: linear-gradient(90deg, rgba(20, 184, 166, 0.14) 0%, rgba(20, 184, 166, 0.04) 100%);
  border-color: rgba(94, 234, 212, 0.14);
}
.group-title.has-active-child .group-icon {
  color: #5eead4;
}
.group-title.has-active-child .menu-icon-chip {
  background: rgba(20, 184, 166, 0.2);
  border-color: rgba(94, 234, 212, 0.3);
}
.group-title.has-active-child .group-chevron {
  color: #5eead4;
}

/* ============ 子菜单项 ============ */
.group-children {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 3px 0 6px 44px;
  position: relative;
}

/* 子菜单左侧引导线 */
.group-children::before {
  content: '';
  position: absolute;
  left: 24px;
  top: 10px;
  bottom: 12px;
  width: 1.5px;
  background: linear-gradient(180deg, rgba(20, 184, 166, 0.2) 0%, rgba(51, 65, 85, 0.25) 100%);
  border-radius: 1px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 12px;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 500;
  color: #94a3b8;
  background: transparent;
  text-decoration: none;
  transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
  position: relative;
  border: 1px solid transparent;
}

/* 单级菜单项（无父分组）——突出为一级主入口 */
.menu-item.solo {
  padding: 9px 10px;
  font-size: 13.5px;
  font-weight: 600;
  color: #dbe4ee;
  letter-spacing: 0.2px;
}

/* 一级菜单的图标芯片更大、背景更明显 */
.menu-item.solo .menu-icon-chip {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.07);
}

.menu-item.solo .menu-icon {
  color: #94a3b8;
}

/* 悬停：轻背景 + 右移 */
.menu-item:hover {
  color: #f1f5f9;
  background: rgba(255, 255, 255, 0.05);
  transform: translateX(2px);
}

.menu-item.solo:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

.menu-item:hover .menu-icon-chip {
  background: rgba(20, 184, 166, 0.12);
  border-color: rgba(20, 184, 166, 0.2);
}

/* 悬停左侧指示条 */
.menu-item.solo::after {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 0;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #5eead4 0%, #14b8a6 100%);
  transition: height 0.2s ease;
}

.menu-item.solo:hover::after {
  height: 55%;
}

.menu-item.solo.active::after {
  height: 0;
}

/* 激活态：左侧指示条 + 极淡背景 */
.menu-item.active {
  color: #ffffff;
  background: rgba(20, 184, 166, 0.08);
  font-weight: 600;
  border-color: transparent;
  box-shadow: none;
}

/* 一级菜单激活态——更强的高亮背景，凸显当前所在主功能 */
.menu-item.solo.active {
  background: linear-gradient(90deg, rgba(20, 184, 166, 0.18) 0%, rgba(20, 184, 166, 0.06) 100%);
  border-color: rgba(94, 234, 212, 0.18);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

/* 激活态图标芯片 */
.menu-item.active .menu-icon-chip {
  background: rgba(20, 184, 166, 0.18);
  border-color: rgba(94, 234, 212, 0.25);
}

.menu-item.solo.active .menu-icon-chip {
  background: rgba(20, 184, 166, 0.28);
  border-color: rgba(94, 234, 212, 0.4);
}

.menu-item.solo.active .menu-icon {
  color: #5eead4;
}

/* 激活态左侧渐变指示条 */
.menu-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  bottom: 8px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #5eead4 0%, #14b8a6 100%);
  box-shadow: 0 0 8px rgba(94, 234, 212, 0.5);
}

.menu-item.active:hover {
  color: #ffffff;
  background: rgba(20, 184, 166, 0.12);
  transform: translateX(2px);
}

.menu-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #475569;
  flex-shrink: 0;
  transition: all 0.18s ease;
  position: relative;
  z-index: 1;
}

.menu-item:hover .menu-dot {
  background: #5eead4;
}

.menu-item.active .menu-dot {
  background: #5eead4;
  box-shadow: 0 0 6px rgba(94, 234, 212, 0.6);
}

.menu-icon {
  color: #64748b;
  transition: color 0.18s ease;
}

.menu-item:hover .menu-icon {
  color: #5eead4;
}

.menu-item.active .menu-icon {
  color: #5eead4;
}

.menu-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.menu-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1.5px 6px;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.4px;
  color: #ffffff;
  background: linear-gradient(135deg, #fb923c 0%, #ef4444 100%);
  border-radius: 999px;
  box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
  flex-shrink: 0;
  animation: badge-blink 2.4s ease-in-out infinite;
}

/* 外链菜单"正版"等可信标签（静态小章，区别于闪烁的 badge） */
.menu-verify {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1.5px 7px;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #0d9488;
  background: rgba(94, 234, 212, 0.16);
  border: 1px solid rgba(94, 234, 212, 0.4);
  border-radius: 999px;
  flex-shrink: 0;
  box-shadow: inset 0 1px 0 rgba(94, 234, 212, 0.25);
}

.menu-item:hover .menu-verify {
  color: #5eead4;
  background: rgba(94, 234, 212, 0.22);
}

@keyframes badge-blink {
  0%, 100% { transform: scale(1); box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35); }
  50% { transform: scale(1.08); box-shadow: 0 3px 10px rgba(239, 68, 68, 0.55); }
}

/* ============ 用户迷你卡片 ============ */
.side-user-card {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 8px 12px 6px;
  padding: 8px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
  border: 1px solid rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.side-user-card:hover {
  border-color: rgba(20, 184, 166, 0.25);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 4px 12px rgba(20, 184, 166, 0.1);
}

.user-card-main {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
  text-decoration: none;
  color: inherit;
  /* 未登录时该元素渲染为 button，需重置浏览器默认样式 */
  border: none;
  background: none;
  margin: 0;
  padding: 0;
  text-align: left;
  cursor: pointer;
  font: inherit;
}

.user-card-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.user-card-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  background-color: #334155;
  display: block;
  border: 2px solid rgba(20, 184, 166, 0.2);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.user-card-avatar.fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(20, 184, 166, 0.3);
}

/* 在线状态点 */
.user-card-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #22c55e;
  border: 2px solid #1e293b;
  box-shadow: 0 0 6px rgba(34, 197, 94, 0.6);
  animation: status-pulse 2s ease-in-out infinite;
}

@keyframes status-pulse {
  0%, 100% { box-shadow: 0 0 6px rgba(34, 197, 94, 0.6); }
  50% { box-shadow: 0 0 10px rgba(34, 197, 94, 0.9); }
}

.user-card-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.user-card-name {
  font-size: 13px;
  font-weight: 700;
  color: #f1f5f9;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.3;
}

.user-card-meta {
  font-size: 11px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  line-height: 1.3;
}

.user-card-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.18s ease;
}

.user-card-action:hover {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

/* ============ Main ============ */
.main {
  margin-left: 248px;
  min-height: 100vh;
  padding: 20px 28px 32px;
  position: relative;
  display: flex;
  flex-direction: column;
}

/* ============ 嵌入模式（分站 iframe 内嵌）：无侧边栏留白，无内边距 ============ */
.main--embed {
  margin-left: 0 !important;
  padding: 0 !important;
}
.main--embed .console-content-wrap {
  min-height: 0;
}

/* ============ Topbar（呼应落地页 navbar 的毛玻璃风格） ============ */
.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  margin: -20px -28px 20px;
  padding: 0 28px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid #f1f5f9;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* 二级页面回退按钮 */
.topbar-back {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: rgba(20, 184, 166, 0.08);
  color: #0d9488;
  cursor: pointer;
  transition: all 0.18s ease;
  flex-shrink: 0;
}

.topbar-back:hover {
  background: rgba(20, 184, 166, 0.15);
  border-color: rgba(20, 184, 166, 0.25);
  transform: translateX(-2px);
}

.topbar-back-divider {
  width: 1px;
  height: 18px;
  background: #e2e8f0;
  flex-shrink: 0;
}

.topbar-page-icon-wrap {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: rgba(20, 184, 166, 0.1);
  color: #0d9488;
}

.topbar-page-icon {
  display: block;
}

.topbar-page-name {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
}

.topbar-login {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: #fff;
  text-decoration: none;
  padding: 6px 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.24);
  transition: all 0.15s ease;
  white-space: nowrap;
  /* button 元素需重置默认样式 */
  border: none;
  cursor: pointer;
  font-family: inherit;
}

.topbar-login:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(13, 148, 136, 0.32);
}

.topbar-refresh {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  transition: all 0.15s ease;
}

.topbar-refresh:hover {
  color: #0d9488;
  background: rgba(20, 184, 166, 0.08);
  border-color: rgba(20, 184, 166, 0.18);
}

.topbar-refresh.spinning {
  color: #0d9488;
  background: rgba(20, 184, 166, 0.08);
  border-color: rgba(20, 184, 166, 0.18);
  cursor: not-allowed;
  pointer-events: none;
}

.topbar-refresh.spinning svg {
  animation: topbar-spin 0.8s linear infinite;
}

@keyframes topbar-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 页面内容区刷新 loading overlay */
.console-content-wrap {
  position: relative;
  flex: 1;
  min-height: 0;
}

.page-refresh-overlay {
  position: fixed;
  top: 64px;
  left: 248px;
  right: 0;
  bottom: 0;
  background: rgba(248, 250, 252, 0.3);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
  z-index: 40;
}

.page-refresh-ring {
  width: 56px;
  height: 56px;
  transform-origin: center;
  filter: drop-shadow(0 2px 6px rgba(13, 148, 136, 0.28));
}

.page-refresh-ring-track {
  stroke-dasharray: 110 40;
  animation: page-refresh-ring-spin 1.2s linear infinite;
  transform-origin: center;
}

.page-refresh-ring-inner {
  stroke-dasharray: 55 30;
  animation: page-refresh-ring-spin-reverse 1.8s linear infinite;
  transform-origin: center;
}

@keyframes page-refresh-ring-spin {
  to { transform: rotate(360deg); }
}

@keyframes page-refresh-ring-spin-reverse {
  to { transform: rotate(-360deg); }
}

.page-refresh-text {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  color: #0f766e;
  letter-spacing: 1px;
}

.page-refresh-fade-enter-active,
.page-refresh-fade-leave-active {
  transition: opacity 0.2s ease;
}
.page-refresh-fade-enter-from,
.page-refresh-fade-leave-to {
  opacity: 0;
}

/* 响应式: 小屏侧边栏隐藏, topbar 56px */
@media (max-width: 900px) {
  .page-refresh-overlay {
    top: 56px;
    left: 0;
  }
}

.user-divider {
  width: 1px;
  height: 18px;
  background: #e2e8f0;
}

.user-trigger {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 999px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  transition: all 0.18s ease;
}

.user-trigger:hover {
  background: #f8fafc;
  border-color: #e2e8f0;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  background-color: #f1f5f9;
  flex-shrink: 0;
}

.user-avatar.fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-name.ssr-placeholder {
  color: #94a3b8;
  font-weight: 500;
}

.user-caret {
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.user-caret.open {
  transform: rotate(180deg);
}

/* ============ 用户下拉菜单 ============ */
.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  min-width: 230px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 16px 48px rgba(15, 23, 42, 0.12), 0 4px 12px rgba(15, 23, 42, 0.04);
  padding: 6px;
  z-index: 200;
  overflow: hidden;
}

.dropdown-header {
  padding: 12px 12px 10px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 4px;
}

.dropdown-name {
  display: block;
  font-size: 13.5px;
  font-weight: 700;
  color: #0f172a;
}

.dropdown-account {
  display: block;
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 2px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  background: transparent;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
}

.dropdown-item:hover {
  background: #f8fafc;
  color: #0d9488;
}

.dropdown-item.danger:hover {
  background: #fef2f2;
  color: #dc2626;
}

.dropdown-divider {
  height: 1px;
  background: #f1f5f9;
  margin: 4px 0;
}

/* ============ 下拉动画 ============ */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ============ 响应式 ============ */
/* 手机端(≤900px): 侧边栏改为抽屉式，默认隐藏，加 .drawer-open 滑入 */
@media (max-width: 900px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
    box-shadow: none;
    /* 防抽屉隐藏态装饰球(orbs)漏出屏幕左缘 */
    overflow: hidden;
  }

  .sidebar.drawer-open {
    transform: translateX(0);
    box-shadow: 6px 0 32px rgba(0, 0, 0, 0.4);
  }

  /* 抽屉打开时遮罩层 */
  .sidebar-mask {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 99;
    cursor: pointer;
  }

  .drawer-fade-enter-active,
  .drawer-fade-leave-active {
    transition: opacity 0.32s ease;
  }
  .drawer-fade-enter-from,
  .drawer-fade-leave-to {
    opacity: 0;
  }

  .main {
    margin-left: 0;
    padding: 16px;
  }

  .topbar {
    margin: -16px -16px 16px;
    padding: 0 16px;
    height: 56px;
  }

  .user-name {
    max-width: 80px;
  }

  .topbar-refresh .refresh-text {
    display: none;
  }

  /* 侧栏已收起为抽屉，刷新遮罩定位基准随之改变 */
  .page-refresh-overlay {
    top: 56px;
    left: 0;
  }
}

/* ============ 深度手机端(≤640px)：壳层 H5 化，去 PC 工作台感 ============ */
@media (max-width: 640px) {
  /* 内容区收紧，贴近原生 App 边距 */
  .main {
    padding: 12px;
  }

  .topbar {
    margin: -12px -12px 12px;
    padding: 0 12px;
    height: 52px;
  }

  /* 刷新是 PC 工作台操作习惯，手机端隐藏 */
  .topbar-refresh {
    display: none;
  }

  .topbar-login {
    padding: 5px 12px;
    font-size: 12px;
  }

  .user-name {
    max-width: 64px;
  }

  .page-refresh-overlay {
    top: 52px;
  }
}

/* ============ 汉堡菜单按钮（仅手机端显示） ============ */
.topbar-hamburger {
  display: none;
  width: 38px;
  height: 38px;
  padding: 0;
  margin-right: 4px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 10px;
  align-items: center;
  justify-content: center;
  transition: background 0.18s ease;
  flex-shrink: 0;
}

.topbar-hamburger:hover {
  background: rgba(20, 184, 166, 0.08);
}

.hamburger-box {
  position: relative;
  display: inline-block;
  width: 20px;
  height: 16px;
}

.hamburger-inner {
  position: absolute;
  top: 50%;
  left: 0;
  width: 100%;
  height: 2px;
  background: #0f172a;
  border-radius: 2px;
  transform: translateY(-50%);
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), background 0.18s ease;
}

.hamburger-inner::before,
.hamburger-inner::after {
  content: '';
  position: absolute;
  left: 0;
  width: 100%;
  height: 2px;
  background: #0f172a;
  border-radius: 2px;
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.hamburger-inner::before {
  top: -7px;
}

.hamburger-inner::after {
  top: 7px;
}

/* 激活态（X 形）：抽屉打开时汉堡按钮变成关闭图标 */
.topbar-hamburger.active .hamburger-inner {
  background: transparent;
}

.topbar-hamburger.active .hamburger-inner::before {
  top: 0;
  transform: rotate(45deg);
  background: #0d9488;
}

.topbar-hamburger.active .hamburger-inner::after {
  top: 0;
  transform: rotate(-45deg);
  background: #0d9488;
}

@media (max-width: 900px) {
  .topbar-hamburger {
    display: inline-flex;
  }
}

/* ============ 手机端抽屉关闭按钮 ============ */
.drawer-close {
  display: none;
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.06);
  color: #cbd5e1;
  border-radius: 9px;
  cursor: pointer;
  z-index: 3;
  align-items: center;
  justify-content: center;
  transition: all 0.18s ease;
  opacity: 0;
  pointer-events: none;
}

.drawer-close.show {
  opacity: 1;
  pointer-events: auto;
}

.drawer-close:hover {
  background: rgba(239, 68, 68, 0.18);
  border-color: rgba(239, 68, 68, 0.35);
  color: #fca5a5;
}

@media (max-width: 900px) {
  .drawer-close {
    display: inline-flex;
  }
}
</style>
