// 统一 API 请求封装
// 后端响应格式：{ code, show, msg, data }
//   code: 1=成功, 0=失败, -1=登录超时需重新登录
//   show: 1=前端弹窗提示, 0=静默
// 后端鉴权：HTTP Header `token`
//
// 用法：
//   const api = useApi()
//   const res = await api.post('/api/login/account', { account, password, scene:1, terminal:4 })
//   if (res.ok) { res.data } else { res.msg }
//
// 也可直接用 api.get / api.post，它们都返回 { ok, code, msg, data, raw } 包装对象。

const TOKEN_HEADER = 'token'
// 与 useAuth 保持一致：v2 主key，旧 key 做向后兼容迁移（避免刷新后 token 读不到导致 dashboard 全 0）
const STORAGE_KEY = 'aidian_auth_v2'
const OLD_STORAGE_KEYS = ['aidian_auth']

// 登录态失效（未登录 / token 过期）：只清理本地登录态并提示，不强制跳转登录页，
// 以便未登录访客也能预览各功能页（真正操作时再引导登录）。
// 页面上可能多个并发请求同时返回 -1，各触发一次 handleLoginExpired，
// 若不做去重会导致「请先登录」提示重复弹出（表现为登录框/提示双次出现）。
let loginExpiredHandled = false
function handleLoginExpired() {
  if (!process.client) return
  if (loginExpiredHandled) return
  loginExpiredHandled = true
  try { localStorage.removeItem(STORAGE_KEY) } catch (e) {}
  // 清掉旧版本所有可能残留的 key，双保险
  try { for (const k of (OLD_STORAGE_KEYS || [])) localStorage.removeItem(k) } catch (e) {}
  if (typeof useAuth === 'function') {
    try { useAuth().clear() } catch (e) {}
  }
  try { useToast().error('请先登录') } catch (e) {}
}

// 读取本地 token（仅在客户端）
// - 优先从 v2 主 key aidian_auth_v2 读取
// - 若为空，回退旧 key aidian_auth（命中后迁移写回 v2 key，与 useAuth.restore 保持一致）
function readToken() {
  if (!process.client) return ''
  try {
    let raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      for (const oldKey of (OLD_STORAGE_KEYS || [])) {
        const oldRaw = localStorage.getItem(oldKey)
        if (oldRaw) {
          raw = oldRaw
          // 迁移后就删旧 key，下次直接命中 v2
          try { localStorage.removeItem(oldKey) } catch (_) {}
          break
        }
      }
    }
    if (!raw) return ''
    const data = JSON.parse(raw)
    const token = data?.token || ''
    // 从旧 key 迁移回来的内容，写回 v2 主 key
    if (token && !localStorage.getItem(STORAGE_KEY)) {
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)) } catch (_) {}
    }
    return token
  } catch (e) {
    return ''
  }
}

// 解析后端响应
function parseResponse(body) {
  if (body && typeof body === 'object' && 'code' in body) {
    return {
      ok: body.code === 1,
      code: body.code,
      show: body.show ?? 0,
      msg: body.msg ?? '',
      data: body.data ?? {},
      raw: body,
    }
  }
  // 非标准响应，整体当 data
  return { ok: true, code: 1, show: 0, msg: '', data: body, raw: body }
}

export function useApi() {
  const config = useRuntimeConfig()
  // 客户端用相对路径，SSR 用 runtimeConfig.apiBase（绝对 URL）
  const baseURL = process.client ? '' : (config.public.apiBase || '')

  async function request(url, options = {}) {
    const headers = { ...(options.headers || {}) }
    const token = readToken()
    if (token) headers[TOKEN_HEADER] = token
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
      headers['Content-Type'] = headers['Content-Type'] || 'application/json'
    }

    let resp
    try {
      resp = await $fetch(url, {
        baseURL,
        ...options,
        headers,
        responseType: 'json',
      })
    } catch (err) {
      // $fetch 抛错时（网络错误、非 2xx）处理
      const errResp = err?.data
      if (errResp && typeof errResp === 'object' && 'code' in errResp) {
        const parsed = parseResponse(errResp)
        // 登录超时
        if (parsed.code === -1) {
          handleLoginExpired()
        }
        // 服务端要求弹窗提示
        if (parsed.show === 1 && process.client && parsed.msg && parsed.code !== -1) {
          try { useToast().error(parsed.msg) } catch (e) {}
        }
        return parsed
      }
      // 真正的网络/解析错误
      const msg = err?.message || '网络请求失败'
      if (process.client) {
        try { useToast().error(msg) } catch (e) {}
      }
      return { ok: false, code: 0, show: 1, msg, data: {}, raw: null }
    }

    const parsed = parseResponse(resp)
    // 登录超时清理
    if (parsed.code === -1) {
      handleLoginExpired()
    }
    // 业务失败且需要弹窗
    if (!parsed.ok && parsed.show === 1 && process.client && parsed.msg && parsed.code !== -1) {
      try { useToast().error(parsed.msg) } catch (e) {}
    }
    return parsed
  }

  return {
    get: (url, params, options = {}) => request(url, { ...options, method: 'GET', params }),
    post: (url, body, options = {}) => request(url, { ...options, method: 'POST', body }),
    put: (url, body, options = {}) => request(url, { ...options, method: 'PUT', body }),
    delete: (url, body, options = {}) => request(url, { ...options, method: 'DELETE', body }),
    request,
  }
}
