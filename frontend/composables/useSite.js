// 站点配置组合式函数
// 通过公共接口 /api/agent/site 获取当前请求所属站点配置：
//   - 主站域名   → is_sub_site=false（平台站默认配置）
//   - 代理分站域名 → is_sub_site=true（返回分站模板/配色/站点名/logo/功能开关/售价倍率）
//
// 前端职责：
//   1. index.vue 判断 is_sub_site：分站域名根直接展示「功能广场」，不展示落地页
//   2. 根据 theme 应用多配色（通过 CSS 变量）
//   3. console 布局按 feature_map 过滤侧边栏菜单（功能开关）
//   4. 页面展示分站站点名 / logo / 售价（price_rate 展示价）

// 功能开关 key → 页面路由（与后端 AgentSite::featureOptions 一一对应）
// route 为登录后工作台内的入口路径
const FEATURE_PAGES = {
  create:        { route: '/pc/create',        label: 'AI论文' },
  writing:       { route: '/pc/writing',       label: '写作中心' },
  autodoc:       { route: '/pc/autodoc',       label: '自动排版' },
  aippt:         { route: '/pc/aippt',         label: 'AIPPT生成' },
  ai_check:      { route: '/pc/ai-check',      label: 'AI检测' },
  reduce_weight: { route: '/pc/tools/aigcreduceweight', label: 'AI降重' },
  tools:         { route: '/pc/tools',         label: '小工具' },
  orders:        { route: '/pc/orders/paper',  label: '订单中心' },
  templates:     { route: '/pc/user?tab=templates', label: '我的模板' },
  package_shop:  { route: '/pc/package-shop',  label: '套餐商城' },
}

// 商品 code → 落地页路由：商品管理「设为默认/首页默认」的商品（is_default=1）
// 对应到前端首页落地页，决定用户进入 `/pc/` 时落到哪个业务页。
// 未在此列出的商品即使被设为默认，也回退到默认落地 /create。
const PRODUCT_ROUTE = {
  gjlw:                    '/pc/create',
  ppt:                     '/pc/aippt',
  autodoc:                 '/pc/autodoc',
  aicheck:                 '/pc/ai-check',
  tools_aigcreduceweight:  '/pc/tools/aigcreduceweight',
  kaiti:                   '/pc/writing/proposal',
  rws:                     '/pc/writing/task',
  sx:                      '/pc/writing/internship',
  sxrz:                    '/pc/writing/internshipdiary',
  reduce:                  '/pc/tools/aigcreduceweight',
  tools_wxlist:            '/pc/tools/wxlist',
  tools_rewrite:           '/pc/tools/rewrite',
  tools_illustration:      '/pc/tools/illustration',
  tools_chart:             '/pc/tools/createchart',
  tools_createtitle:       '/pc/tools/createtitle',
  tools_createoutline:     '/pc/tools/createoutline',
}

// 配色主题 → CSS 变量组（多配色方案）
// teal 为默认主题（与主站一致），其余为可选的扩展配色
const THEMES = {
  teal: {
    name: '青绿',
    primary500: '#14b8a6',
    primary600: '#0d9488',
    primary300: '#5eead4',
    accent500: '#f97316',
    accent300: '#fdba74',
  },
  orange: {
    name: '橙黄',
    primary500: '#f97316',
    primary600: '#ea580c',
    primary300: '#fdba74',
    accent500: '#14b8a6',
    accent300: '#5eead4',
  },
  blue: {
    name: '科技蓝',
    primary500: '#3b82f6',
    primary600: '#2563eb',
    primary300: '#93c5fd',
    accent500: '#f97316',
    accent300: '#fdba74',
  },
  purple: {
    name: '神秘紫',
    primary500: '#8b5cf6',
    primary600: '#7c3aed',
    primary300: '#c4b5fd',
    accent500: '#f97316',
    accent300: '#fdba74',
  },
}

// SSR 安全的共享状态（客户端 onMounted 后拉取）
const siteState = () => {
  if (!process.client) return null
  return useState('agent_site', () => null)
}

export function useSite() {
  const api = useApi()
  const site = siteState()

  // 预览模式：?preview=1 时用 query 参数模拟分站配置（站点名/主题/logo/倍率）
  // 供分站预览、以及分站工作台 iframe 内嵌的主站功能页共用同一份预览配置
  function readPreviewParams() {
    if (!process.client) return null
    try {
      const route = useRoute()
      if (!(route.query.preview === '1' || route.query.preview === 'true')) return null
      const themeKey = typeof route.query.theme === 'string' && route.query.theme ? route.query.theme : 'teal'
      const name = typeof route.query.name === 'string' ? route.query.name.slice(0, 32) : ''
      const logo = typeof route.query.logo === 'string' ? route.query.logo : ''
      const priceRateRaw = typeof route.query.price_rate === 'string' ? route.query.price_rate : ''
      const price_rate = priceRateRaw && !isNaN(Number(priceRateRaw)) && Number(priceRateRaw) > 0 ? Number(priceRateRaw).toFixed(2) : '1.00'
      return {
        is_sub_site: true,
        site_name: name,
        theme: themeKey,
        logo,
        price_rate,
        feature_map: null,
      }
    } catch (e) {
      return null
    }
  }

  // 拉取站点配置（幂等：只拉一次）
  async function fetchSite(force = false) {
    if (!process.client) return site.value
    // 预览模式：直接用 query 参数，不请求真实接口（避免覆盖预览配置）
    const preview = readPreviewParams()
    if (preview) {
      if (!site.value || force) {
        site.value = preview
        applyTheme(preview.theme)
      }
      return site.value
    }
    if (site.value && !force) return site.value
    const res = await api.get('/api/agent/site')
    if (res.ok && res.data && typeof res.data === 'object') {
      site.value = res.data
      applyTheme(res.data.theme)
    }
    return site.value
  }

  // 应用配色主题：把主题变量写入 <html> style，覆盖全局 CSS 变量
  function applyTheme(themeKey) {
    if (!process.client) return
    const theme = THEMES[themeKey] || THEMES.teal
    const el = document.documentElement
    el.style.setProperty('--primary-500', theme.primary500)
    el.style.setProperty('--primary-600', theme.primary600)
    el.style.setProperty('--primary-300', theme.primary300)
    el.style.setProperty('--accent-500', theme.accent500)
    el.style.setProperty('--accent-300', theme.accent300)
  }

  const isSubSite = computed(() => !!site.value?.is_sub_site)
  const theme = computed(() => site.value?.theme || 'teal')
  const siteName = computed(() => site.value?.site_name || '')
  const logo = computed(() => site.value?.logo || '')
  // Favicon：后台「网站设置-网页图标」(site_icon)，未设置时后端已回退 site_logo
  const favicon = computed(() => site.value?.favicon || '')
  const priceRate = computed(() => Number(site.value?.price_rate || 1))

  // 默认首页落地路由：由商品管理「设为默认/首页默认」商品（is_default=1）决定；
  // 无默认商品或默认商品无对应落地页时回退到 /create
  const defaultHomeRoute = computed(() => {
    const list = site.value?.menu_products
    if (Array.isArray(list)) {
      const def = list.find(p => Number(p.is_default) === 1)
      if (def && PRODUCT_ROUTE[def.code]) return PRODUCT_ROUTE[def.code]
    }
    return '/pc/create'
  })

  // 某功能是否开放
  function featureEnabled(key) {
    const map = site.value?.feature_map
    if (!map) return true // 站点信息未加载时默认全开
    return !!map[key]
  }

  // 已开放功能列表（供功能广场展示）
  const enabledFeatures = computed(() => {
    const map = site.value?.feature_map
    const list = []
    for (const key of Object.keys(FEATURE_PAGES)) {
      if (!map || map[key] === true || map[key] === undefined) {
        list.push({ key, ...FEATURE_PAGES[key] })
      }
    }
    return list
  })

  return {
    site,
    fetchSite,
    isSubSite,
    theme,
    siteName,
    logo,
    favicon,
    priceRate,
    defaultHomeRoute,
    featureEnabled,
    enabledFeatures,
    featurePages: FEATURE_PAGES,
    themes: THEMES,
    applyTheme,
  }
}
