// 双端自适应路由守卫
// 行为：每次导航都按 UA 自适应判定并互切（无「会话只判一次」限制）。
//   - 移动端 UA：访问 /pc/xxx 中已有移动版的页面 → 切到 /m/ 对应页；根 / → /m/
//   - 桌面端 UA：访问 /m/xxx → 切回 /pc/ 对应页
//   - 长尾页（无映射的 /pc/ 页面）任何 UA 都不切换，维持响应式直接浏览
//   - 目标带 ?pc=1 时跳过切换（移动端主动进入指定 PC 页的逃生标记，如「我的模板」）
// 循环安全性：移动端只查 PC2M（命中 /pc/*），桌面端只查 M2PC（命中 /m/*）；
// 切换目标落在对方分支的无映射区，同一 UA 下再次导航不会再次命中，自然终止。
export default defineNuxtRouteMiddleware((to) => {
  if (!process.client) return

  const ua = navigator.userAgent || ''
  const isMobile = /Android|iPhone|iPad|iPod|IEMobile|Opera Mini|Mobile/i.test(ua)
    || (navigator.maxTouchPoints > 1 && window.innerWidth < 768)

  // /pc/xxx（含移动版的页面）→ /m/xxx 映射表；根路径单列
  const PC2M = {
    '/': '/m',
    '/pc': '/m',
    '/pc/create': '/m/create',
    '/pc/tools': '/m/tools',
    '/pc/user': '/m/user',
    '/pc/package-shop': '/m/packages',
    '/pc/package-balance': '/m/user',
    '/pc/orders/paper': '/m/orders?type=paper',
    '/pc/orders/ppt': '/m/orders?type=ppt',
    '/pc/orders/write': '/m/orders?type=write',
    '/pc/orders/recharge': '/m/orders?type=recharge',
    '/pc/orders/autodoc': '/m/orders?type=autodoc',
    '/pc/autodoc': '/m/autodoc',
    '/pc/orders/jiangchong': '/m/orders?type=check',
    '/pc/aippt': '/m/aippt',
    '/pc/ai-check': '/m/ai-check',
    '/pc/tools/aigcreduceweight': '/m/aigcreduceweight',
    '/pc/tools/paperweight': '/m/paperweight',
    '/pc/tools/rewrite': '/m/rewrite',
    '/pc/tools/createtitle': '/m/createtitle',
    '/pc/tools/createoutline': '/m/createoutline',
    '/pc/tools/wxlist': '/m/wxlist',
    '/pc/tools/illustration': '/m/illustration',
    '/pc/tools/createchart': '/m/createchart',
    '/pc/writing': '/m/writing',
    '/pc/writing/proposal': '/m/proposal',
    '/pc/writing/task': '/m/task',
    '/pc/writing/internship': '/m/internship',
    '/pc/writing/internshipdiary': '/m/internshipdiary',
  }
  // /m/xxx → /pc/xxx 映射表
  const M2PC = {
    '/m': '/pc',
    '/m/create': '/pc/create',
    '/m/tools': '/pc/tools',
    '/m/orders': '/pc/orders/paper',
    '/m/autodoc': '/pc/autodoc',
    '/m/user': '/pc/user',
    '/m/balance': '/pc/user?tab=balance-log',
    '/m/recharge': '/pc/user?tab=recharge',
    '/m/packages': '/pc/package-shop',
    '/m/aippt': '/pc/aippt',
    '/m/ai-check': '/pc/ai-check',
    '/m/aigcreduceweight': '/pc/tools/aigcreduceweight',
    '/m/paperweight': '/pc/tools/paperweight',
    '/m/rewrite': '/pc/tools/rewrite',
    '/m/createtitle': '/pc/tools/createtitle',
    '/m/createoutline': '/pc/tools/createoutline',
    '/m/wxlist': '/pc/tools/wxlist',
    '/m/illustration': '/pc/tools/illustration',
    '/m/createchart': '/pc/tools/createchart',
    '/m/writing': '/pc/writing',
    '/m/proposal': '/pc/writing/proposal',
    '/m/task': '/pc/writing/task',
    '/m/internship': '/pc/writing/internship',
    '/m/internshipdiary': '/pc/writing/internshipdiary',
  }

  // nginx 目录访问会 301 补尾斜杠（/pc/create → /pc/create/），映射前先归一化
  const p = to.path !== '/' && to.path.endsWith('/') ? to.path.slice(0, -1) : to.path
  let target = null
  if (isMobile) {
    // 逃生标记：移动端显式要求进入指定 PC 页（如「我的模板」）时不切回
    if (to.query.pc === '1') return
    // 移动端：命中映射表才切换（长尾 PC 页保持响应式直接浏览）
    target = PC2M[p]
  } else {
    // 桌面端：访问移动版页面时切回 PC 完整版（pc=1 不影响桌面端归位）
    target = M2PC[p]
  }
  if (!target || target === to.path) return

  // query 合并：映射目标自带参数（如 type=paper / tab=balance-log）优先，
  // 原 query 其余参数保留（剔除 pc 逃生标记）
  const qi = target.indexOf('?')
  const tPath = qi >= 0 ? target.slice(0, qi) : target
  const merged = new URLSearchParams(qi >= 0 ? target.slice(qi + 1) : '')
  for (const [k, v] of Object.entries(to.query)) {
    if (k === 'pc' || v == null) continue
    if (!merged.has(k)) merged.set(k, Array.isArray(v) ? v.join(',') : String(v))
  }
  const qs = merged.toString()
  const finalPath = qs ? `${tPath}?${qs}` : tPath
  if (finalPath === to.fullPath) return
  return navigateTo(finalPath, { replace: true })
})
