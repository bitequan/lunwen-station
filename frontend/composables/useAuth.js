// 用户登录态管理
// - 使用 Nuxt useState 做 SSR 安全的共享状态（SSR 默认未登录，客户端 onMounted 后从 localStorage 恢复）
// - localStorage 持久化 token + 用户基础信息
// - 提供 login / logout / fetchUser / restore 方法
// - v2 存储 key 升级：旧的 aidian_auth 里存有 account/sn/id 等敏感字段，强制失效后用户会重新登录拿到新脱敏结构
//
// 后端登录返回: { nickname, mobile, avatar, token }
// 后端 /api/user/info 返回: { nickname, real_name, avatar, mobile, email, create_time, user_money, level, invite_code, has_password, has_auth, version, level_text }
//   （已脱敏：不返回 id / parent_id / user_id / sn / account / password 等等价 id 字段，避免被遍历；同时 nickname / real_name 值级别自动剥离 Pxxxx id 号段）

const STORAGE_KEY = 'aidian_auth_v2'
const OLD_STORAGE_KEYS = ['aidian_auth'] // 旧版本 key，读取时会做字段脱敏再迁移，或直接废弃
const TOKEN_HEADER = 'token'

// terminal: 4=PC, scene: 1=账号密码（见后端 UserTerminalEnum / LoginEnum）
const LOGIN_TERMINAL = 4
const LOGIN_SCENE = 1

export function useAuth() {
  // useState 保证 SSR 与客户端共享同一份状态（SSR 阶段为默认值 null）
  const user = useState('auth_user', () => null)
  const token = useState('auth_token', () => '')
  const ready = useState('auth_ready', () => false) // 客户端是否已恢复完 localStorage

  const isLoggedIn = computed(() => !!token.value && !!user.value)

  // 敏感字段黑名单（无论从旧 localStorage 迁移回来，还是从接口响应存，都不能存到前端）
  const SENSITIVE_USER_KEYS = ['id', 'parent_id', 'user_id', 'sn', 'account', 'password']
  // 昵称值级处理：仅做空白裁剪，保留用户真实昵称（含数字号段，如「用户03194341」）
  // 说明：防遍历能力由字段级黑名单（不返回/不存储 id/sn/account 等）保证，不再剥离昵称里的数字号段
  function cleanNameForStore(val) {
    if (!val || typeof val !== 'string') return val
    return val.trim() || undefined
  }
  function sanitizeUserForStore(u) {
    if (!u || typeof u !== 'object') return u
    const next = {}
    for (const k of Object.keys(u)) {
      if (SENSITIVE_USER_KEYS.indexOf(k) !== -1) continue
      let v = u[k]
      if (k === 'nickname' || k === 'real_name') {
        v = cleanNameForStore(v)
        if (v === undefined) continue
      }
      next[k] = v
    }
    return next
  }

  function persist(next) {
    user.value = next?.user ? sanitizeUserForStore(next.user) : null
    token.value = next?.token || ''
    if (process.client) {
      try {
        if (token.value) {
          localStorage.setItem(STORAGE_KEY, JSON.stringify({ token: token.value, user: user.value }))
        } else {
          localStorage.removeItem(STORAGE_KEY)
        }
      } catch (e) {}
    }
  }

  function clear() {
    persist({ user: null, token: '' })
    // 清掉旧版本所有可能残留的 key，保证后续不会再读到旧数据
    if (process.client) {
      try {
        for (const k of (OLD_STORAGE_KEYS || [])) {
          localStorage.removeItem(k)
        }
      } catch (e) {}
    }
  }

  // 从 localStorage 恢复（仅在客户端调用，建议在 onMounted 调用一次）
  function restore() {
    if (!process.client) return
    try {
      let raw = localStorage.getItem(STORAGE_KEY)
      if (!raw) {
        // v2 key 没有，看看有没有旧版本 key 需要做脱敏迁移
        for (const oldKey of (OLD_STORAGE_KEYS || [])) {
          const oldRaw = localStorage.getItem(oldKey)
          if (oldRaw) {
            raw = oldRaw
            // 迁移完就删掉旧 key，下次 restore 直接走 v2
            try { localStorage.removeItem(oldKey) } catch (_) {}
            break
          }
        }
      }
      if (!raw) {
        ready.value = true
        return
      }
      const data = JSON.parse(raw)
      if (data?.token) {
        token.value = data.token
        // 无论从 v2 还是 v1 旧 key 恢复，都强制做一次字段+值级别脱敏
        user.value = sanitizeUserForStore(data.user || null)
        // 迁移完立即写回 v2 key，保证下次 restore 直接命中 v2
        if (process.client) {
          try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ token: token.value, user: user.value }))
          } catch (_) {}
        }
      }
    } catch (e) {}
    ready.value = true
  }

  // 调用后端登录接口
  // params: { account, password, scene?, code? }
  //   scene 不传默认账号密码（1）；邮箱验证码登录传 scene=4 + code
  // 返回: { ok, msg, data }
  async function login(params) {
    const api = useApi()
    const payload = {
      account: params.account?.trim(),
      password: params.password,
      scene: params.scene ?? LOGIN_SCENE,
      terminal: LOGIN_TERMINAL,
    }
    if (params.code) payload.code = params.code
    const res = await api.post('/api/login/account', payload)
    if (res.ok && res.data?.token) {
      const u = {
        nickname: res.data.nickname || '',
        mobile: res.data.mobile || '',
        avatar: res.data.avatar || '',
      }
      persist({ user: u, token: res.data.token })
      return { ok: true, msg: res.msg || '注册成功', data: res.data }
    }
    return { ok: false, msg: res.msg || '注册失败', data: res.data || {} }
  }

  // 发送邮箱登录验证码
  // email: 收件邮箱（必须已注册）
  // 返回: { ok, msg }
  async function sendEmailLoginCode(email) {
    const api = useApi()
    const res = await api.post('/api/login/sendEmailCode', { account: email?.trim() })
    return { ok: !!res.ok, msg: res.msg || (res.ok ? '发送成功' : '发送失败') }
  }

  // 发送找回密码验证码（仅支持邮箱找回）
  // email: 收件邮箱（必须已注册）
  // 返回: { ok, msg }
  async function sendResetPwdCode(email) {
    const api = useApi()
    const res = await api.post('/api/sendResetPasswordCode', { email: email?.trim() })
    return { ok: !!res.ok, msg: res.msg || (res.ok ? '发送成功' : '发送失败') }
  }

  // 邮箱验证码重置密码
  // params: { email, code, password }
  // 返回: { ok, msg }
  async function resetPassword(params) {
    const api = useApi()
    const res = await api.post('/api/resetPassword', {
      email: params.email?.trim(),
      verify_code: params.code?.trim(),
      new_password: params.password,
    })
    return { ok: !!res.ok, msg: res.msg || (res.ok ? '重置成功' : '重置失败') }
  }

  // 调用后端注册接口（必须填邀请码）
  // params: { account, password, invite_code }
  // 返回: { ok, msg, data }
  async function register(params) {
    const api = useApi()
    const res = await api.post('/api/login/register', {
      account: params.account?.trim(),
      password: params.password,
      invite_code: params.invite_code?.trim(),
      terminal: LOGIN_TERMINAL,
    })
    if (res.ok && res.data?.token) {
      const u = {
        nickname: res.data.nickname || '',
        mobile: res.data.mobile || '',
        avatar: res.data.avatar || '',
      }
      persist({ user: u, token: res.data.token })
      return { ok: true, msg: res.msg || '注册成功', data: res.data }
    }
    return { ok: false, msg: res.msg || '注册失败', data: res.data || {} }
  }

  /* ============ 注册两步流程（邮箱/手机号验证码） ============ */

  // 获取注册配置（coerce_email / coerce_mobile 开关）
  // 返回: { ok, data: { coerce_email, coerce_mobile, login_way, ... } }
  // 注意：加 _t 时间戳防止浏览器缓存旧配置（管理员可能刚改过开关）
  async function registerConfig() {
    const api = useApi()
    const res = await api.get('/api/login/registerConfig', { _t: Date.now() })
    if (res.ok && res.data) {
      return { ok: true, data: res.data }
    }
    return { ok: false, data: {} }
  }

  // 第一步预校验：邮箱/手机号 + 密码 + 邀请码
  // params: { account_type, account, password, invite_code }
  // 返回: { ok, msg, data: { register_token, account_type, account, expire_at } }
  async function registerPreCheck(params) {
    const api = useApi()
    const res = await api.post('/api/login/registerPreCheck', {
      account_type: params.account_type,
      account: params.account?.trim(),
      password: params.password,
      invite_code: params.invite_code?.trim(),
      terminal: LOGIN_TERMINAL,
    })
    if (res.ok && res.data?.register_token) {
      return { ok: true, msg: res.msg || '校验通过', data: res.data }
    }
    return { ok: false, msg: res.msg || '校验失败', data: res.data || {} }
  }

  // 发送验证码（邮箱走邮件，手机号走短信）
  // params: { account_type, account }
  // 返回: { ok, msg }
  async function registerSendCode(params) {
    const api = useApi()
    const res = await api.post('/api/login/registerSendCode', {
      account_type: params.account_type,
      account: params.account?.trim(),
      scene: 'register',
    })
    return { ok: !!res.ok, msg: res.msg || (res.ok ? '发送成功' : '发送失败') }
  }

  // 第二步完成注册：验证码 + 用户名
  // params: { register_token, code, username }
  // 返回: { ok, msg, data: { nickname, sn, mobile, avatar, token } }
  async function registerComplete(params) {
    const api = useApi()
    const res = await api.post('/api/login/registerComplete', {
      register_token: params.register_token,
      code: params.code?.trim(),
      username: params.username?.trim(),
      terminal: LOGIN_TERMINAL,
    })
    if (res.ok && res.data?.token) {
      const u = {
        nickname: res.data.nickname || '',
        mobile: res.data.mobile || '',
        avatar: res.data.avatar || '',
      }
      persist({ user: u, token: res.data.token })
      return { ok: true, msg: res.msg || '注册成功', data: res.data }
    }
    return { ok: false, msg: res.msg || '注册失败', data: res.data || {} }
  }

  // 用户名可用性检查
  // 返回: { ok, data: { available, msg } }
  async function checkUsername(username) {
    const api = useApi()
    const res = await api.get('/api/login/checkUsername', { username })
    if (res.ok && res.data) {
      return { ok: true, data: res.data }
    }
    return { ok: false, data: { available: false, msg: res.msg || '校验失败' } }
  }

  // 拉取最新的用户信息（用 /api/user/info）
  async function fetchUser() {
    if (!token.value) return null
    const api = useApi()
    const res = await api.get('/api/user/info')
    if (res.ok && res.data) {
      const d = res.data
      const u = {
        nickname: d.nickname || '',
        real_name: d.real_name || '',
        avatar: d.avatar || '',
        mobile: d.mobile || '',
        email: d.email || '',
        has_auth: !!d.has_auth,
        has_password: !!d.has_password,
        create_time: d.create_time,
        user_money: d.user_money ?? 0,
        level: d.level ?? 1,
        level_text: d.level_text || '',
        invite_code: d.invite_code || '',
        // 上次登录记录（后端 adhp_user_login_log 最新一条）
        // 结构: { ts, time_text, ip, ip_version, device, os, browser, way, terminal }
        last_login: d.last_login || null,
      }
      // 保留现有 token
      persist({ user: u, token: token.value })
      return u
    }
    // 拉取失败且是登录超时 → 清空
    if (res.code === -1) clear()
    return null
  }

  // 退出登录：先调用后端登出（失败也无所谓），再清空本地
  async function logout() {
    const api = useApi()
    try { await api.post('/api/login/logout') } catch (e) {}
    clear()
  }

  /**
   * 获取登录历史（分页）
   * @param {number} page      页码（从 1 开始）
   * @param {number} pageSize  每页条数
   * @returns {Promise<{ok:boolean, data?:{list:array,total:number,page:number,page_size:number}, msg?:string}>}
   *  - list 元素结构：{ id, ts, time_text, time_short, ip, ip_version, device, os, browser, way, terminal }
   */
  async function fetchLoginLog(page = 1, pageSize = 10) {
    if (!token.value) return { ok: false, msg: '请先登录', data: { list: [], total: 0, page, page_size: pageSize } }
    const api = useApi()
    const res = await api.get('/api/user/loginLog', { page, page_size: pageSize })
    if (res.ok && res.data) {
      return { ok: true, data: res.data }
    }
    return { ok: false, msg: res.msg || '加载失败', data: { list: [], total: 0, page, page_size: pageSize } }
  }

  return {
    user,
    token,
    ready,
    isLoggedIn,
    restore,
    clear,
    login,
    sendEmailLoginCode,
    sendResetPwdCode,
    resetPassword,
    register,
    registerConfig,
    registerPreCheck,
    registerSendCode,
    registerComplete,
    checkUsername,
    logout,
    fetchUser,
    fetchLoginLog,
  }
}
