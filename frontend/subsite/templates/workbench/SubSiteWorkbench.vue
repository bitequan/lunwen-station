<template>
  <div class="ssw" :class="`ssw-theme-${themeKey}`">
    <!-- ===== 左侧菜单：全高深色侧边栏（顶到页面顶部，主站 console 风格） ===== -->
    <aside class="ssw-side">
      <div class="ssw-side-bg-layer"></div>
      <div class="ssw-side-orb ssw-side-orb-teal"></div>
      <div class="ssw-side-orb ssw-side-orb-orange"></div>
      <div class="ssw-side-grid-pattern"></div>

      <div class="ssw-brand">
        <div class="ssw-brand-logo-wrap">
          <img :src="siteLogo" alt="" class="ssw-brand-logo" />
          <div class="ssw-brand-logo-glow"></div>
        </div>
        <span class="ssw-brand-name">{{ siteNameText }}</span>
        <span class="ssw-brand-tag">
          <span class="ssw-brand-tag-dot"></span>
          工作台
        </span>
      </div>

      <div class="ssw-menu-scroll">
        <nav class="ssw-menu">
          <button
            v-for="m in menuList"
            :key="m.key"
            class="ssw-menu-item"
            :class="{ active: activeKey === m.key }"
            @click="selectMenu(m)"
          >
            <span class="ssw-menu-icon-chip">
              <span class="ssw-menu-icon" v-html="m.icon"></span>
            </span>
            <span class="ssw-menu-label">{{ m.label }}</span>
          </button>
        </nav>

        <!-- 账户入口：个人中心 / 充值（复用主站 console 页面，未登录点击先弹登录） -->
        <div class="ssw-menu-divider"></div>
        <nav class="ssw-menu">
          <button
            v-for="m in accountMenu"
            :key="m.key"
            class="ssw-menu-item"
            :class="{ active: activeKey === m.key }"
            @click="selectMenu(m)"
          >
            <span class="ssw-menu-icon-chip">
              <span class="ssw-menu-icon" v-html="m.icon"></span>
            </span>
            <span class="ssw-menu-label">{{ m.label }}</span>
          </button>
        </nav>
      </div>

      <!-- 未登录：登录入口（弹窗） -->
      <div v-if="!isLoggedIn" class="ssw-side-login">
        <button type="button" class="ssw-side-login-btn" @click="openLogin">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          用户登录 / 注册
        </button>
      </div>
      <!-- 已登录：用户卡片 -->
      <div v-else class="ssw-side-user">
        <div class="ssw-side-avatar">{{ avatarText }}</div>
        <div class="ssw-side-userinfo">
          <span class="ssw-side-username">{{ nickname }}</span>
          <button type="button" class="ssw-side-link" @click="selectMenu(accountMenu[0])">个人中心 ›</button>
        </div>
        <button type="button" class="ssw-side-recharge" title="账户充值" @click="openRecharge">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <span>充值</span>
        </button>
        <button type="button" class="ssw-side-logout" title="退出登录" @click="handleLogout">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>

      <div class="ssw-side-foot">
        <span class="ssw-side-foot-ver">© {{ year }} {{ siteNameText }}</span>
        <!-- 备案信息（平台后台「网站设置 → 备案信息」配置） -->
        <a
          v-for="(c, i) in copyrightItems"
          :key="i"
          class="ssw-side-foot-icp"
          :href="c.value || undefined"
          :target="c.value ? '_blank' : undefined"
          rel="noopener"
        >{{ c.key }}</a>
        <!-- 联系客服入口在顶栏右侧（.ssw-top-service），此处不再放置，避免与备案/版权信息重叠 -->
      </div>
    </aside>

    <!-- ===== 右侧：顶栏 + 主站功能页 iframe 内容区 ===== -->
    <div class="ssw-right">
      <!-- 顶栏：毛玻璃 topbar（主站 console 风格） -->
      <header class="ssw-top">
        <div class="ssw-top-left">
          <span class="ssw-top-page-icon" v-html="currentFunc.icon"></span>
          <span class="ssw-top-page-name">{{ currentFunc.label }}</span>
          <!-- 二级页面回退按钮：iframe 内进入子页面时显示 -->
          <template v-if="canGoBack">
            <span class="ssw-top-back-divider"></span>
            <button type="button" class="ssw-top-back" @click="iframeBack" :title="`返回${backLabel}`">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </button>
          </template>
        </div>
        <!-- 分站公告滚动条：header 中滚动展示公告（替代原实时动态假订单），点击打开公告弹窗；无公告时留空不展示 -->
        <div v-if="hasAnnouncement" class="ssw-live" :class="`ann-color-${annColorIdx}`" @click="openAnnouncement" title="查看公告详情">
          <span class="ssw-live-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            公告
          </span>
          <div class="ssw-live-viewport">
            <div class="ssw-live-track">
              <div v-for="copy in 2" :key="copy" class="ssw-live-run">
                <span v-for="(it, i) in annFeed" :key="copy + '-' + i" class="ssw-live-item">
                  <span class="ssw-live-title">{{ it }}</span>
                  <span class="ssw-live-dot"></span>
                </span>
              </div>
            </div>
          </div>
          <span class="ssw-live-more">详情 ›</span>
        </div>
        <div class="ssw-top-right">
          <!-- 联系客服：弹出客服信息（分站展示分站自配联系方式，不使用平台联系方式） -->
          <button type="button" class="ssw-top-service" @click="openService" title="联系客服">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>客服</span>
          </button>
          <!-- 刷新当前加载页面的功能按钮（iframe 内） -->
          <button type="button" class="ssw-top-refresh" :class="{ spinning: refreshing }" @click="iframeRefresh" title="刷新当前页面">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          </button>
          <!-- 已登录：用户 chip（点击进入个人中心） -->
          <template v-if="isLoggedIn">
            <button type="button" class="ssw-top-user" @click="selectMenu(accountMenu[0])">
              <span class="ssw-top-user-avatar">{{ avatarText }}</span>
              <span class="ssw-top-user-name">{{ nickname }}</span>
            </button>
          </template>
          <!-- 未登录：登录按钮（弹窗） -->
          <button v-else type="button" class="ssw-top-btn login" @click="openLogin">登录 / 注册</button>
        </div>
      </header>

      <!-- 分站公告：header 滚动条点击打开公告弹窗（访客与登录用户均可见）；弹窗固定配色，不随 header 循环变色 -->
      <SubsiteAnnouncementModal
        v-model="showAnnouncement"
        :title="announcementTitle"
        :content="announcementContent"
        :time="announcementTime"
      />

      <!-- 中间：主站功能页 iframe 嵌入区（撑满剩余空间，页面完全铺开，与主站 console 页面加载一致） -->
      <main class="ssw-main">
        <iframe
          ref="frameRef"
          :src="embedSrc"
          class="ssw-frame"
          frameborder="0"
        ></iframe>
      </main>
    </div>

    <!-- 分站登录弹窗：复用主站 LoginModal，登录成功后刷新用户信息并跳转待访问账户页 -->
    <LoginModal v-model="showLogin" @success="onLoginSuccess" />

    <!-- 分站充值弹窗：选择金额 / 输入金额，在线支付 -->
    <RechargeModal v-model="showRecharge" @success="onRechargeSuccess" />

    <!-- 分站客服信息弹窗：二维码 / 微信 / 电话 / 服务时间；未开启在线支付时替代支付弹窗提示人工充值 -->
    <ServiceModal v-model="showService" :service="serviceInfo" :notice="serviceRechargeNotice" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import RechargeModal from '~/subsite/components/RechargeModal.vue'
import ServiceModal from '~/subsite/components/ServiceModal.vue'
import SubsiteAnnouncementModal from '~/subsite/components/SubsiteAnnouncementModal.vue'
// 默认 logo 用 public 静态路径：管理员上传后该文件被直接替换，无需等待站点配置接口
const defaultLogo = '/pc/logo.png'

const site = useSite()
const auth = useAuth()
const route = useRoute()
const { showLogin, openLogin, closeLogin } = useSubsiteLogin()

// 工作台自身内容固定 100vh：给 <html> 打标，去掉全局强制滚动条轨道（避免右侧残留空滚动槽）
if (typeof document !== 'undefined') {
  document.documentElement.classList.add('ssw-embed')
}

const themeKey = computed(() => site.site.value?.theme || 'teal')
const siteNameText = computed(() => site.site.value?.site_name || 'AI写作助手')
// 分站 Logo：有配置则展示，否则回退默认 Logo
const siteLogo = computed(() => site.site.value?.logo || defaultLogo)
const year = new Date().getFullYear()

// ===== 独立分站公告：header 滚动展示（访客与登录用户均可见），点击打开公告弹窗 =====
const announcementTitle = computed(() => site.site.value?.announcement_title || '')
const announcementContent = computed(() => site.site.value?.announcement_content || '')
// 公告发布时间（时间戳，秒；弹窗展示）
const announcementTime = computed(() => Number(site.site.value?.announcement_time || 0) || 0)
const hasAnnouncement = computed(() => !!(announcementTitle.value || announcementContent.value))
// 配色索引：后端每次更新公告时循环更换（0/1/2），header 公告区随之换色以吸引关注
const annColorIdx = computed(() => {
  const c = Number(site.site.value?.announcement_color ?? 0)
  return Number.isFinite(c) ? Math.abs(c) % 3 : 0
})
// header 滚动内容：标题 + 内容按行拆分，形成无缝滚动
const annFeed = computed(() => {
  const items = []
  if (announcementTitle.value) items.push(announcementTitle.value)
  const c = announcementContent.value
  if (c) {
    c.split(/\r?\n/).map(s => s.trim()).filter(Boolean).forEach(line => items.push(line))
  }
  return items
})
const showAnnouncement = ref(false)
function openAnnouncement() {
  if (!hasAnnouncement.value) return
  showAnnouncement.value = true
}

// ===== 备案信息（平台后台「网站设置 → 备案信息」配置 [{key, value}]） =====
const copyrightItems = computed(() => {
  const arr = site.site.value?.copyright
  if (!Array.isArray(arr)) return []
  return arr.filter(c => c && (c.key || c.value))
})

// ===== 客服信息（分站：分站自配微信/电话/服务时间，无二维码；主站：平台配置） =====
const serviceInfo = computed(() => site.site.value?.customer_service || {})
const showService = ref(false)
// 客服弹窗顶部提示：默认空；分站未开启在线支付时替代支付弹窗展示「人工充值」引导
const serviceRechargeNotice = ref('')
function openService() {
  serviceRechargeNotice.value = ''
  showService.value = true
}

const isLoggedIn = computed(() => !!auth.user.value)
const nickname = computed(() => auth.user.value?.nickname || auth.user.value?.account || '用户')
const avatarText = computed(() => (nickname.value || 'U').slice(0, 1).toUpperCase())

// ===== 左侧菜单：同步主站 console 布局的业务功能（featureKey 对应后端功能开关）=====
// route 指向主站功能页，中间区 iframe 复用主站页面
const rawMenu = [
  {
    key: 'create', label: 'AI论文',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    route: '/create',
    featureKey: 'create',
  },
  {
    key: 'writing', label: '写作中心',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
    route: '/writing',
    featureKey: 'writing',
  },
  {
    key: 'autodoc', label: '格式重排',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M8 17h4"/><path d="M9 11V7l3 2-3 2z"/></svg>',
    route: '/autodoc',
    featureKey: 'autodoc',
  },
  {
    key: 'aippt', label: 'AIPPT生成',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    route: '/aippt',
    featureKey: 'aippt',
  },
  {
    key: 'reduce-weight', label: 'AI降重',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>',
    route: '/tools/aigcreduceweight',
    featureKey: 'reduce_weight',
  },
  {
    key: 'tools', label: '小工具',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    route: '/tools',
    featureKey: 'tools',
  },
  // 套餐商城（package_shop）为平台一级用户专属，分站不开放，此处不列出
]

// 按开关过滤：feature_map 空则视为全部开放
const menuList = computed(() => {
  const map = site.site.value?.feature_map
  if (!map) return rawMenu
  return rawMenu.filter(m => {
    if (!m.featureKey) return true
    return map[m.featureKey] !== false
  })
})

// ===== 账户菜单（分站专属；个人中心/收支明细复用主站 console 页面，未登录点击先弹登录）=====
// 充值由侧边栏用户卡「充值」按钮弹出充值弹窗，不再占用菜单项
const accountMenu = [
  {
    key: 'account-user', label: '个人中心', account: true, route: '/user',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  },
  {
    key: 'account-balance', label: '收支明细', account: true, route: '/user?tab=balance-log',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
  },
]

// 当前选中菜单：刷新/重开页面时恢复上次打开的页面（localStorage 按分站域名天然隔离）
const ACTIVE_KEY_STORAGE = 'aidian_subsite_active_key_v1'
const savedActiveKey = typeof localStorage !== 'undefined' ? localStorage.getItem(ACTIVE_KEY_STORAGE) : null
const activeKey = ref(savedActiveKey || 'create')
const pendingKey = ref(null)

// 更新选中菜单并持久化；切换菜单 = iframe 重新加载初始页，重置会话内导航标志
function setActive(key) {
  activeKey.value = key
  iframeNavigated = false
  iframeInitialized = false
  try { localStorage.setItem(ACTIVE_KEY_STORAGE, key) } catch (e) { /* 忽略 */ }
}

// 充值弹窗状态（分站共享 RechargeModal：选择金额 / 输入金额 + 在线支付）
const showRecharge = ref(false)
const api = useApi()
// 打开充值：分站未开启在线支付时，用「联系人工客服充值」弹窗整体替代支付弹窗，不再弹出支付表单
async function openRecharge() {
  let needService = false
  try {
    const res = await api.get('/api/recharge/config')
    const sp = res?.data?.site_pay
    // 分站模式下返回了 site_pay 且未开启可用支付 → 用客服弹窗替代支付弹窗；
    // 否则打开正常支付弹窗（主站 / 分站已开启在线支付 / 分站主本人）
    needService = sp !== null && sp !== undefined && !sp.enabled
  } catch (e) { /* 接口异常时回退为打开支付弹窗，由弹窗内自行处理 */ }
  if (needService) {
    // 未开启在线支付：用客服弹窗整体替代支付弹窗，提示联系人工客服人工充值
    serviceRechargeNotice.value = '当前未开启在线支付，请添加下方人工客服办理余额充值到账。'
    showService.value = true
  } else {
    showRecharge.value = true
  }
}

// 当前页 = 业务菜单 + 账户菜单 合并查找（账户项始终可进入 iframe）
const currentFunc = computed(() => {
  return [...menuList.value, ...accountMenu].find(x => x.key === activeKey.value) || rawMenu[0]
})

function selectMenu(m) {
  // 账户项需登录：未登录先弹登录框，登录成功后进入对应功能
  if (m.account && !isLoggedIn.value) {
    pendingKey.value = m.key
    openLogin()
    return
  }
  setActive(m.key)
}

// 登录成功：刷新用户信息，并跳转到登录前想访问的账户功能；若分站存在公告则弹窗提示
async function onLoginSuccess() {
  await auth.fetchUser()
  // 刷新站点配置（保证公告等数据是最新），若存在分站公告则自动弹出提示
  try { await site.fetchSite(true) } catch (e) {}
  if (hasAnnouncement.value) {
    showAnnouncement.value = true
  }
  if (pendingKey.value) {
    setActive(pendingKey.value)
    pendingKey.value = null
  }
}

// 充值成功：刷新用户余额等信息
async function onRechargeSuccess() {
  try { await auth.fetchUser() } catch (e) {}
}

// 退出登录：回到业务首页（并持久化，刷新后同样落在首页）
async function handleLogout() {
  await auth.logout()
  setActive('create')
}

// ===== iframe：嵌入主站功能页（同源，?embed=1 走 console 布局嵌入模式）=====
// 刷新恢复：iframe 内二级页面路径也持久化，刷新后保持停在二级页（而非回到一级入口）
const IFRAME_PATH_STORAGE = 'aidian_subsite_iframe_path_v1'
const savedIframePath = typeof localStorage !== 'undefined' ? localStorage.getItem(IFRAME_PATH_STORAGE) : null

const embedSrc = computed(() => {
  const m = currentFunc.value
  const base = m.route || '/create'
  // 主站功能页在 app.baseURL(/pc/) 下：拼上前缀避免 302 重定向，直接加载主站已写好的功能页
  const baseURL = useRuntimeConfig().app.baseURL || '/pc/'
  const baseUrl = baseURL.endsWith('/') ? baseURL.slice(0, -1) : baseURL
  // 若保存的 iframe 路径属于当前功能（同首段、是 base 的子路径），则恢复该二级页面而非一级入口
  let path = base
  const savedSeg = (savedIframePath || '').split('?')[0]
  if (savedSeg && savedSeg !== base && savedSeg.startsWith(base.replace(/\/$/, '') + '/')) {
    path = savedSeg
  }
  const full = baseUrl + path
  const q = new URLSearchParams({ embed: '1' })
  // 预览模式参数透传：分站预览时 iframe 内站点配置/主题跟随
  if (route.query.preview === '1' || route.query.preview === 'true') {
    q.set('preview', '1')
    if (typeof route.query.theme === 'string') q.set('theme', route.query.theme)
    if (typeof route.query.name === 'string') q.set('name', route.query.name)
    if (typeof route.query.logo === 'string') q.set('logo', route.query.logo)
    if (typeof route.query.price_rate === 'string') q.set('price_rate', route.query.price_rate)
  }
  // 路由可能已带查询串（如 /user?tab=balance-log），需用 & 拼接而非再追加 ?
  const sep = full.includes('?') ? '&' : '?'
  return full + sep + q.toString()
})

// iframe 撑满剩余空间、内部滚动，底部 ICP 固定；无需高度自适应逻辑

// ===== 顶栏返回/刷新：操作 iframe 内加载的主站功能页 =====
const frameRef = ref(null)
const refreshing = ref(false)
// iframe 内当前路径（去掉 baseURL 前缀），用于判断是否处于二级页面
const iframePath = ref('')
// 本次会话内是否发生过 iframe 内部导航（区别于"刷新后直接恢复二级页"）
// 刷新后 iframe 的 session history 被清空（且主站 SPA 重定向会使 history.length 虚高），
// 此时 history.back() 会回到错误的中间/空白页，必须改用"跳到一级入口"的方式回退
let iframeNavigated = false
let iframeInitialized = false
let pathTimer = null

// 读取 iframe 当前 pathname（同源可直接访问 contentWindow；未加载/异常返回 ''）
function getIframePath() {
  try {
    const win = frameRef.value?.contentWindow
    if (!win || !win.location) return ''
    const baseURL = useRuntimeConfig().app.baseURL || '/pc/'
    const base = baseURL.endsWith('/') ? baseURL.slice(0, -1) : baseURL
    let p = win.location.pathname || ''
    if (p.startsWith(base)) p = p.slice(base.length) || '/'
    return p
  } catch (e) {
    return ''
  }
}

// 二级页面判断：与主站 console 布局一致，多段路径视为子页面
// 例外：/tools/aigcreduceweight 已独立为一级菜单，不显示回退按钮
const canGoBack = computed(() => {
  const p = iframePath.value
  const segments = p.split('/').filter(Boolean)
  if (segments.length < 2) return false
  if (p === '/tools/aigcreduceweight') return false
  return true
})
// 一级路径 → 中文名（用于返回按钮提示文案）
const backLabelMap = {
  create: 'AI论文',
  writing: '写作中心',
  autodoc: '格式重排',
  aippt: 'AIPPT生成',
  tools: '小工具',
  user: '个人中心',
}
const backLabel = computed(() => {
  const p = iframePath.value
  const first = p.split('/').filter(Boolean)[0] || ''
  return backLabelMap[first] || first || '上一页'
})

// 返回上一页：
//  - 会话内用户点过页面内链接（iframe 内部导航）→ 用 history.back() 精准回退
//  - 刷新后直接恢复二级页（无有效历史）→ 跳到当前功能的一级入口页，避免回到错误/空白页
function iframeBack() {
  const win = frameRef.value?.contentWindow
  if (!win) return
  try {
    const baseURL = useRuntimeConfig().app.baseURL || '/pc/'
    const base = baseURL.endsWith('/') ? baseURL.slice(0, -1) : baseURL
    const m = currentFunc.value
    const home = (m.route || '/create') + '?embed=1'
    if (iframeNavigated && win.history.length > 1) {
      win.history.back()
      return
    }
    win.location.href = base + home
  } catch (e) {
    /* 忽略 */
  }
}

// 刷新当前加载的页面（iframe 内，保留当前路由与 embed 参数）
function iframeRefresh() {
  if (refreshing.value) return
  refreshing.value = true
  try {
    frameRef.value?.contentWindow?.location.reload()
  } catch (e) {
    /* 忽略 */
  }
  setTimeout(() => { refreshing.value = false }, 900)
}

onMounted(() => {
  // 分站工作台：隐藏 default 布局注入的主站 Navbar
  if (process.client) {
    document.body.classList.add('ssw-mode')
    // 跟踪 iframe 内路由（SPA pushState 不触发 iframe load 事件，需轮询 pathname）
    // 用于决定顶栏返回按钮是否显示，并持久化当前路径供刷新恢复二级页面
    pathTimer = setInterval(() => {
      const p = getIframePath()
      if (!p) return
      if (!iframeInitialized) {
        // 首次拿到路径：这是 iframe 初始加载/刷新恢复的路径，不算会话内导航
        iframeInitialized = true
        iframePath.value = p
        return
      }
      if (p !== iframePath.value) {
        // 路径相对初始恢复路径发生变化 → 判定发生会话内导航（点击链接/页内跳转）
        iframePath.value = p
        iframeNavigated = true
        try { localStorage.setItem(IFRAME_PATH_STORAGE, p) } catch (e) { /* 忽略 */ }
      }
    }, 600)
  }
})
onBeforeUnmount(() => {
  if (process.client) {
    document.body.classList.remove('ssw-mode')
  }
  if (pathTimer) {
    clearInterval(pathTimer)
    pathTimer = null
  }
})
</script>

<style scoped>
.ssw {
  --primary-600: #0d9488;
  --primary-500: #14b8a6;
  --primary-300: #5eead4;
  --primary-14: #e6f8f5;
  --primary-08: #f1fbf9;
  --ink: #0f172a;
  --ink-soft: #475569;
  --ink-fade: #94a3b8;
  --line: #e2e8f0;
  --bg: #f6f7fb;
  --panel: #ffffff;
  color: var(--ink);
  background: var(--bg);
  height: 100vh;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;
  display: flex;
  align-items: stretch;
}
.ssw-theme-teal {
  --primary-600: #0d9488; --primary-500: #14b8a6; --primary-300: #5eead4;
  --primary-14: #e6f8f5; --primary-08: #f1fbf9;
  --right-grad-a: #3fc1a4; --right-grad-b: #5eead4;
}
.ssw-theme-orange {
  --primary-600: #ea580c; --primary-500: #f97316; --primary-300: #fdba74;
  --primary-14: #fff1e6; --primary-08: #fff7ee;
  --right-grad-a: #ff8a3d; --right-grad-b: #fdba74;
}
.ssw-theme-blue {
  --primary-600: #2563eb; --primary-500: #3b82f6; --primary-300: #93c5fd;
  --primary-14: #eaf1ff; --primary-08: #f2f6ff;
  --right-grad-a: #5286ff; --right-grad-b: #93c5fd;
}
.ssw-theme-purple {
  --primary-600: #7c3aed; --primary-500: #8b5cf6; --primary-300: #c4b5fd;
  --primary-14: #f1ecff; --primary-08: #f7f4ff;
  --right-grad-a: #956dff; --right-grad-b: #c4b5fd;
}

/* ===== 左侧菜单：全高深色侧边栏（顶到顶部） ===== */
.ssw-side {
  position: relative;
  height: 100vh;
  flex: 0 0 248px;
  background: linear-gradient(180deg, #0e1526 0%, #172032 45%, #1e293b 100%);
  border-right: 1px solid rgba(51, 65, 85, 0.5);
  box-shadow: 4px 0 28px rgba(0, 0, 0, 0.22), 1px 0 0 rgba(20, 184, 166, 0.08);
  padding: 0;
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

/* 顶部渐变装饰线 */
.ssw-side::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg,
    rgba(20, 184, 166, 0.65) 0%,
    rgba(94, 234, 212, 0.22) 30%,
    rgba(249, 115, 22, 0.22) 70%,
    rgba(249, 115, 22, 0.45) 100%);
  z-index: 2;
  pointer-events: none;
}

.ssw-side-bg-layer {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 30% 0%, rgba(20, 184, 166, 0.18) 0%, transparent 50%),
    radial-gradient(circle at 80% 100%, rgba(249, 115, 22, 0.12) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20, 184, 166, 0.05) 0%, transparent 60%);
  pointer-events: none;
  z-index: 0;
}
.ssw-side-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  pointer-events: none;
  z-index: 0;
}
.ssw-side-orb-teal {
  width: 220px; height: 220px;
  background: rgba(20, 184, 166, 0.22);
  top: -90px; left: -70px;
}
.ssw-side-orb-orange {
  width: 200px; height: 200px;
  background: rgba(249, 115, 22, 0.15);
  bottom: -70px; right: -60px;
}
.ssw-side-grid-pattern {
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

/* 品牌区 */
.ssw-brand {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  height: 64px;
  padding: 0 16px;
  border-bottom: 1px solid rgba(51, 65, 85, 0.4);
  color: #f1f5f9;
  flex-shrink: 0;
}
.ssw-brand-logo-wrap { position: relative; display: flex; align-items: center; justify-content: center; }
.ssw-brand-logo {
  width: 32px; height: 32px;
  position: relative;
  z-index: 1;
  border-radius: 9px;
  filter: drop-shadow(0 2px 8px rgba(20, 184, 166, 0.4));
}
.ssw-brand-logo-glow {
  position: absolute;
  inset: -5px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.45) 0%, transparent 70%);
  filter: blur(7px);
}
.ssw-brand-name {
  font-size: 15px;
  font-weight: 800;
  background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ssw-brand-tag {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  font-weight: 700;
  color: #5eead4;
  background: rgba(20, 184, 166, 0.15);
  padding: 3px 9px 3px 7px;
  border-radius: 999px;
  letter-spacing: 0.02em;
  border: 1px solid rgba(20, 184, 166, 0.25);
  flex-shrink: 0;
}
.ssw-brand-tag-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: #5eead4;
  box-shadow: 0 0 6px rgba(94, 234, 212, 0.8);
  animation: brand-pulse 2.4s ease-in-out infinite;
}
@keyframes brand-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* 菜单滚动区 */
.ssw-menu-scroll {
  position: relative;
  z-index: 1;
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  padding: 12px 10px 14px;
  scrollbar-width: thin;
  scrollbar-color: rgba(20, 184, 166, 0.35) transparent;
}
.ssw-menu-scroll::-webkit-scrollbar { width: 6px; }
.ssw-menu-scroll::-webkit-scrollbar-track { background: transparent; margin: 4px 0; }
.ssw-menu-scroll::-webkit-scrollbar-thumb { background: rgba(20, 184, 166, 0.35); border-radius: 6px; }

/* 业务菜单 与 账户菜单 的分隔线 */
.ssw-menu-divider {
  height: 1px;
  margin: 10px 14px;
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.28) 20%, rgba(148, 163, 184, 0.28) 80%, transparent);
}

.ssw-menu {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.ssw-menu-item {
  appearance: none;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  width: 100%;
  height: 44px;
  padding: 0 10px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 600;
  color: #dbe4ee;
  text-align: left;
  letter-spacing: 0.2px;
  position: relative;
  transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
}
.ssw-menu-item:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
  transform: translateX(2px);
}
.ssw-menu-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px; height: 30px;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.07);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.ssw-menu-icon { display: inline-flex; color: #94a3b8; transition: color 0.18s ease; }
/* v-html 注入的 SVG 不受 scoped 约束，用 :deep() 穿透强制设尺寸，保证图标正常显示 */
:deep(.ssw-menu-icon svg) { width: 18px; height: 18px; display: inline-block; }
.ssw-menu-item:hover .ssw-menu-icon-chip {
  background: rgba(20, 184, 166, 0.12);
  border-color: rgba(20, 184, 166, 0.2);
}
.ssw-menu-item:hover .ssw-menu-icon { color: #5eead4; }
.ssw-menu-item.active {
  color: #ffffff;
  background: linear-gradient(90deg, rgba(20, 184, 166, 0.18) 0%, rgba(20, 184, 166, 0.06) 100%);
  border-color: rgba(94, 234, 212, 0.18);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}
.ssw-menu-item.active::before {
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
.ssw-menu-item.active .ssw-menu-icon-chip {
  background: rgba(20, 184, 166, 0.28);
  border-color: rgba(94, 234, 212, 0.4);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 0 10px rgba(20, 184, 166, 0.18);
}
.ssw-menu-item.active .ssw-menu-icon { color: #5eead4; }
.ssw-menu-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* 未登录：登录入口 */
.ssw-side-login {
  position: relative;
  z-index: 1;
  margin: 8px 12px 6px;
  flex-shrink: 0;
}
.ssw-side-login-btn {
  display: inline-flex;
  width: 100%;
  box-sizing: border-box;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 38px;
  border: none;
  border-radius: 11px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  text-decoration: none;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 8px 16px -10px rgba(20, 184, 166, 0.7);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.ssw-side-login-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 22px -10px rgba(20, 184, 166, 0.85); }

/* 已登录：用户卡片 */
.ssw-side-user {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 8px 12px 6px;
  padding: 9px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
  border: 1px solid rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  flex-shrink: 0;
}
.ssw-side-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  border: 2px solid rgba(20, 184, 166, 0.2);
  box-shadow: 0 2px 8px rgba(20, 184, 166, 0.3);
  flex-shrink: 0;
}
.ssw-side-userinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.ssw-side-username {
  font-size: 13px;
  font-weight: 700;
  color: #f1f5f9;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ssw-side-link {
  background: none;
  border: none;
  padding: 0;
  font-family: inherit;
  font-size: 11px;
  color: #64748b;
  text-decoration: none;
  text-align: left;
  cursor: pointer;
  transition: color 0.2s ease;
}
.ssw-side-link:hover { color: #5eead4; }
.ssw-side-recharge {
  height: 28px;
  padding: 0 10px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  border: 1px solid rgba(20, 184, 166, 0.35);
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.18), rgba(20, 184, 166, 0.1));
  color: #5eead4;
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.ssw-side-recharge:hover {
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 12px -4px rgba(20, 184, 166, 0.6);
}
.ssw-side-logout {
  width: 26px; height: 26px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
  color: #94a3b8;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.2s ease, background 0.2s ease;
}
.ssw-side-logout:hover { color: #fca5a5; background: rgba(239, 68, 68, 0.12); }

.ssw-side-foot {
  position: relative;
  z-index: 1;
  padding: 13px 12px 12px;
  text-align: center;
  border-top: 1px solid rgba(51, 65, 85, 0.4);
  flex-shrink: 0;
}
.ssw-side-foot-ver { font-size: 10.5px; color: #64748b; }
.ssw-side-foot-icp {
  display: block;
  margin-top: 3px;
  font-size: 9.5px;
  color: #475569;
  text-decoration: none;
  transition: color 0.2s ease;
}
.ssw-side-foot-icp:hover { color: #5eead4; }

/* ===== 右侧：顶栏 + 内容 ===== */
.ssw-right {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* 顶栏：毛玻璃 topbar（主站 console 风格） */
.ssw-top {
  position: sticky;
  top: 0;
  z-index: 50;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 0 20px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid #eef2f7;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02), 0 8px 24px -20px rgba(15, 23, 42, 0.4);
  flex: 0 0 auto;
}
/* 顶栏底部渐变细线 */
.ssw-top::after {
  content: '';
  position: absolute;
  left: 20px;
  right: 20px;
  bottom: -1px;
  height: 1px;
  background: linear-gradient(90deg,
    rgba(20, 184, 166, 0.35) 0%,
    rgba(20, 184, 166, 0.08) 30%,
    rgba(249, 115, 22, 0.08) 70%,
    rgba(249, 115, 22, 0.3) 100%);
  pointer-events: none;
}

.ssw-top-left { display: inline-flex; align-items: center; gap: 10px; flex: 0 0 auto; }
.ssw-top-page-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  border-radius: 10px;
  background: rgba(20, 184, 166, 0.1);
  border: 1px solid rgba(20, 184, 166, 0.14);
  color: #0d9488;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
}
:deep(.ssw-top-page-icon svg) { width: 16px; height: 16px; display: inline-block; }
.ssw-top-page-name { font-size: 15px; font-weight: 700; color: #0f172a; letter-spacing: .2px; }

/* 二级页面回退按钮（顶栏左侧，仿主站 console 风格） */
.ssw-top-back {
  display: inline-flex;
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
.ssw-top-back:hover {
  background: rgba(20, 184, 166, 0.15);
  border-color: rgba(20, 184, 166, 0.25);
  transform: translateX(-2px);
}
.ssw-top-back-divider {
  width: 1px;
  height: 18px;
  background: #e2e8f0;
  flex-shrink: 0;
}

/* 刷新当前加载页按钮（顶栏右侧，仿主站 console 风格） */
.ssw-top-refresh {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 9px;
  border: 1px solid transparent;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.ssw-top-refresh:hover {
  color: #0d9488;
  background: rgba(20, 184, 166, 0.08);
  border-color: rgba(20, 184, 166, 0.18);
}
.ssw-top-refresh.spinning {
  color: #0d9488;
  background: rgba(20, 184, 166, 0.08);
  border-color: rgba(20, 184, 166, 0.18);
  cursor: not-allowed;
  pointer-events: none;
}
.ssw-top-refresh.spinning svg {
  animation: ssw-spin 0.8s linear infinite;
}
@keyframes ssw-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 联系客服按钮（顶栏右侧） */
.ssw-top-service {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 32px;
  padding: 0 12px;
  border-radius: 9px;
  border: 1px solid transparent;
  background: rgba(20, 184, 166, 0.08);
  color: #0d9488;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.ssw-top-service:hover {
  background: rgba(20, 184, 166, 0.14);
  border-color: rgba(20, 184, 166, 0.22);
}

/* 分站公告滚动条：配色循环主题（ann_color 0/1/2 每次更新公告更换，吸引关注） */
.ssw-live {
  --ann-c1: #14b8a6;
  --ann-c2: #0d9488;
  --ann-soft: #f1fbf9;
  --ann-line: #e6f8f5;
  flex: 1;
  min-width: 0;
  max-width: 760px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px 0 6px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--ann-soft), #ffffff 55%);
  border: 1px solid var(--ann-line);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
  color: #475569;
  font-size: 13px;
  overflow: hidden;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.ssw-live.ann-color-1 {
  --ann-c1: #3b82f6; --ann-c2: #2563eb;
  --ann-soft: #eff6ff; --ann-line: #dbeafe;
}
.ssw-live.ann-color-2 {
  --ann-c1: #f59e0b; --ann-c2: #ea580c;
  --ann-soft: #fffbeb; --ann-line: #fef3c7;
}
.ssw-live:hover {
  border-color: var(--ann-c1);
  box-shadow: 0 6px 16px -8px var(--ann-c2);
}
.ssw-live-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 11px 3px 9px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--ann-c1), var(--ann-c2));
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.3px;
  flex: 0 0 auto;
  box-shadow: 0 4px 10px -4px var(--ann-c2);
}
/* 无缝滚动：两段相同内容，translateX(-50%) 形成循环；滚动发生在独立裁剪区，不遮徽标、不越界 */
.ssw-live-viewport {
  flex: 1;
  min-width: 0;
  height: 100%;
  overflow: hidden;
  display: flex;
  mask-image: linear-gradient(90deg, transparent, #000 28px, #000 calc(100% - 28px), transparent);
  -webkit-mask-image: linear-gradient(90deg, transparent, #000 28px, #000 calc(100% - 28px), transparent);
}
.ssw-live-track {
  display: flex;
  flex-shrink: 0;
  width: max-content;
  animation: ssw-marquee 42s linear infinite;
  will-change: transform;
}
.ssw-live:hover .ssw-live-track { animation-play-state: paused; }
@keyframes ssw-marquee {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}
.ssw-live-run {
  display: flex;
  align-items: center;
  flex: none;
}
.ssw-live-item {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 16px 0 0;
  font-size: 12.5px;
  white-space: nowrap;
}
.ssw-live-title { color: var(--ann-c2); font-weight: 600; }
.ssw-live-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; flex: 0 0 auto; }
.ssw-live-more {
  flex: 0 0 auto;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--ann-c2);
  background: color-mix(in srgb, var(--ann-c1) 12%, transparent);
  padding: 2px 9px;
  border-radius: 999px;
  letter-spacing: 0.2px;
  transition: background 0.18s ease;
}
.ssw-live:hover .ssw-live-more { background: color-mix(in srgb, var(--ann-c1) 20%, transparent); }

.ssw-top-right { display: inline-flex; align-items: center; gap: 8px; flex: 0 0 auto; }
.ssw-top-btn {
  height: 34px; padding: 0 18px; border-radius: 999px;
  display: inline-flex; align-items: center;
  border: none;
  text-decoration: none;
  font-family: inherit;
  font-size: 13px; font-weight: 600;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
  color: #fff;
  box-shadow: 0 8px 18px -8px var(--primary-600);
  transition: transform .15s, box-shadow .15s;
}
.ssw-top-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px -8px var(--primary-600);
}

/* 顶栏已登录：用户 chip（点击进入个人中心） */
.ssw-top-user {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 34px;
  padding: 0 14px 0 4px;
  border-radius: 999px;
  border: 1px solid var(--line);
  background: #fff;
  cursor: pointer;
  font-family: inherit;
  transition: border-color .15s, box-shadow .15s;
}
.ssw-top-user:hover {
  border-color: var(--primary-300);
  box-shadow: 0 4px 12px -6px var(--primary-600);
}
.ssw-top-user-avatar {
  width: 26px; height: 26px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.ssw-top-user-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink);
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ===== 中间：主站功能页 iframe 嵌入区（撑满剩余空间，无内边距无外框，页面完全铺开） ===== */
.ssw-main {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  /* 内边距：iframe 内主站页面在 embed 模式下本身无留白，这里给四周留白，
     避免写作中心/小工具等内容区与顶栏/边缘紧贴（露出的淡灰底色形成卡片感） */
  padding: 16px 20px 24px;
}
.ssw-frame {
  flex: 1;
  min-height: 0;
  width: 100%;
  display: block;
  background: #fff;
  border: none;
}

/* ===== 响应式：小屏隐藏侧边栏，顶部横排菜单 ===== */
@media (max-width: 900px) {
  .ssw-side { display: none; }
  .ssw-right { min-width: 0; }
  .ssw-top { flex-wrap: wrap; height: auto; padding: 10px 14px; gap: 8px; }
  .ssw-live { order: 3; width: 100%; max-width: none; }
  .ssw-main { padding: 12px; }
}
</style>

<style>
/* 分站模式下隐藏 default 布局注入的主站 Navbar，使工作台全屏 */
body.ssw-mode .layout-default > header.navbar { display: none !important; }
body.ssw-mode .layout-default { background: transparent; min-height: auto; }
body.ssw-mode .layout-default > main { display: block; padding: 0; margin: 0; width: 100%; max-width: none; }
</style>
