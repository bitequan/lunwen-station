// SEO 优化组合式函数
// 读取公共接口 /api/agent/site 返回的站点 SEO 配置（标题/关键词/描述），
// 应用到页面 <title> 与 <meta keywords/description>，用于优化搜索引擎权重。
//
// 说明：本项目为 ssr:false 的静态生成 SPA，站点配置在客户端 onMounted 后拉取，
// 因此下面通过 reactive 的 computed 喂给 useSeoMeta，配置返回后 head 自动更新；
// 配置文件里的 app.head 默认值作为静态 HTML 的兜底（未拉到配置前先按默认值输出）。

export function useSeo() {
  // 与 useSite 共享同一份站点状态，避免重复请求
  const site = useState('agent_site', () => null)

  // 默认值（与 nuxt.config 的 app.head 保持一致，作为静态兜底）
  const DEFAULT_TITLE = 'AI写作助手 - AI论文写作/AI降重/论文查重源头工厂价 高性价比平台'
  const DEFAULT_DESC =
    'AI写作助手源头工厂直供，AI论文写作一键生成、AI率降重降低重复率、论文查重、自动排版与AIPPT生成一站式搞定。去除中间环节，工厂价高性价比，正规出稿快，欢迎网站直购与独立分站合作。'
  const DEFAULT_KEYWORDS =
    'AI论文写作,论文写作平台,AI率降重,论文降重,论文查重,自动排版,AIPPT生成,开题报告,源头工厂价,高性价比,一手论文服务,AI写作助手'

  const seo = computed(() => site.value?.seo || {})

  const title = computed(() => {
    const t = (seo.value.title || '').trim()
    if (t) return t
    // 分站未单独配置标题时，回退到分站站点名
    const siteName = (site.value?.site_name || '').trim()
    return siteName ? `${siteName} - 一手智能写作平台` : DEFAULT_TITLE
  })
  const keywords = computed(() => (seo.value.keywords || '').trim() || DEFAULT_KEYWORDS)
  const description = computed(() => (seo.value.description || '').trim() || DEFAULT_DESC)

  // 站点名（分站取分站名，为主站时用 SEO 标题前段）
  const siteName = computed(() => (site.value?.site_name || '').trim() || 'AI写作助手')

  // 规范化地址 canonical：客户端用当前真实域名 + baseURL + 路由路径
  // 多入口（主站 /pc、分站顶级域名）各取自己的绝对地址，避免权重被重复入口稀释
  const canonicalUrl = computed(() => {
    if (!process.client) return ''
    try {
      const route = useRoute()
      const base = useRuntimeConfig().app?.baseURL || '/pc/'
      const baseTrim = base.endsWith('/') ? base.slice(0, -1) : base
      let p = route.path
      if (!p) p = '/'
      if (p === '/' && baseTrim) p = baseTrim
      else if (baseTrim && p.startsWith(baseTrim)) p = p // 已含 base
      else if (baseTrim) p = baseTrim + p
      if (p.length > 1 && p.endsWith('/')) p = p.slice(0, -1)
      return window.location.origin + p
    } catch (e) {
      return ''
    }
  })

  // OpenAI Graph / Twitter Card（分享到微信/QQ/外部的展示）
  const ogUrl = computed(() => canonicalUrl.value || '')
  const ogImage = computed(() => {
    try {
      return window.location.origin + (useRuntimeConfig().app?.baseURL || '/pc/') + 'favicon.ico'
    } catch (e) {
      return ''
    }
  })

  // WebSite + SearchAction + Organization 结构化数据（JSON-LD），利于富摘要与排名
  const ldJson = computed(() => {
    const url = canonicalUrl.value
    return [
      {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: siteName.value,
        url,
        description: description.value,
        potentialAction: {
          '@type': 'SearchAction',
          target: `${url}/tools?q={search_term_string}`,
          'query-input': 'required name=search_term_string',
        },
      },
      {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: siteName.value,
        url,
        logo: ogImage.value || undefined,
      },
    ]
  })

  // Favicon：拿到站点配置后优先用管理员设置的网页图标（favicon=site_icon，后端回退 site_logo），
  // 未配置时回退静态 /pc/favicon.ico；unhead 对 rel=icon 去重，会覆盖 nuxt.config 的静态 link
  const faviconHref = computed(() => {
    const f = (site.value?.favicon || site.value?.logo || '').trim()
    if (f) return f
    try {
      return (useRuntimeConfig().app?.baseURL || '/pc/') + 'favicon.ico'
    } catch (e) {
      return '/pc/favicon.ico'
    }
  })
  useHead({
    link: computed(() => [{ rel: 'icon', href: faviconHref.value }]),
  })

  useSeoMeta({
    title,
    description,
    keywords,
    // canonical
    link: computed(() => (canonicalUrl.value ? [{ rel: 'canonical', href: canonicalUrl.value }] : [])),
    // Open Graph
    ogTitle: title,
    ogDescription: description,
    ogType: 'website',
    ogSiteName: siteName,
    ogUrl: computed(() => ogUrl.value || null),
    ogImage: computed(() => ogImage.value || null),
    // Twitter Card
    twitterCard: 'summary',
    twitterTitle: title,
    twitterDescription: description,
    twitterImage: computed(() => ogImage.value || null),
    // JSON-LD 结构化数据（type 必须为 application/ld+json，供搜索引擎解析富摘要）
    script: computed(() =>
      ldJson.value && ldJson.value.length
        ? [
            {
              type: 'application/ld+json',
              innerHTML: JSON.stringify(ldJson.value),
              tagPosition: 'head',
            },
          ]
        : []
    ),
  })

  // 站点配置只在客户端拉取，拿到后 head 跟随更新
  if (process.client) {
    onMounted(() => {
      try {
        useSite().fetchSite()
      } catch (e) {
        // 拉取失败忽略，保持默认 head
      }
    })
  }

  return { title, keywords, description }
}