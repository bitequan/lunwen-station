// 公告弹窗管理（PC 端）
// 后端接口: GET /api/announcement/popup  → 返回最新一条启用且可见的公告
// 返回: { has_announcement, id, title, content, version(update_time), popup_mode(1|2), visibility(1|2) }
//
// 行为:
// - popup_mode=1 (不强制弹窗): 登录时检查一次，版本号变化则弹
// - popup_mode=2 (强制弹窗):   10s 轮询，版本号变化即弹（不重新登录/不刷新也弹）
// - 无启用公告: has_announcement=false，不弹
//
// context 区分:
// - 'login': 登录/挂载时调用，popup_mode=1 或 2 都弹
// - 'poll':  轮询调用，仅 popup_mode=2 弹（不强制弹窗的不轮询弹）
//
// 弹窗去重: localStorage 记录上次已读版本号 `aidian_announcement_read_v`
// - 同一版本号只弹一次（除非用户清缓存）
// - 强制弹窗：管理端修改公告 → update_time 变化 → 新版本号 → 再次弹窗

const STORAGE_KEY = 'aidian_announcement_read_v'
const POLL_INTERVAL = 10 * 1000  // 强制弹窗轮询间隔: 10s

// 模块级共享：保证多次调用 useAnnouncement() 共用同一个轮询定时器与防抖锁
let _pollTimer = null
let _loginChecking = false

export function useAnnouncement() {
  const visible = useState('announcement_visible', () => false)
  const current = useState('announcement_current', () => ({
    title: '',
    content: '',
    version: 0,
  }))

  function readVersion() {
    if (!process.client) return 0
    try {
      const v = localStorage.getItem(STORAGE_KEY)
      return v ? parseInt(v, 10) || 0 : 0
    } catch (e) { return 0 }
  }

  function markRead(version) {
    if (!process.client || !version) return
    try { localStorage.setItem(STORAGE_KEY, String(version)) } catch (e) {}
  }

  // 拉取弹窗公告（用 useApi 附带登录 token；裸 $fetch 不带 token 会被后端判为未登录）
  async function fetchAnnouncement() {
    const api = useApi()
    const res = await api.get('/api/announcement/popup', { _t: Date.now() })
    if (!res.ok) return null
    return res.data
  }

  // 拉取并按需弹窗（context 区分）
  // context: 'login' | 'poll'
  // - login: 登录/挂载时调用；fresh=true(真正重新登录) 时总是展示最新公告，否则做已读版本去重
  // - poll:  轮询调用，仅 popup_mode=2 且版本号变化才弹（避免强制弹窗刷屏）
  async function checkAndShow(context = 'login', opts = {}) {
    const data = await fetchAnnouncement()
    if (!data) return
    // 无启用公告 → 不弹
    if (!data.has_announcement) return

    const popupMode = Number(data.popup_mode) || 0
    // 轮询场景：仅强制弹窗(popup_mode=2)才弹
    if (context === 'poll' && popupMode !== 2) return

    const version = Number(data.version) || 0
    // 版本去重：同一版本已读(点过"我知道了")后不再重复弹窗
    // 例外：真正重新登录(fresh=true)时总是展示最新公告
    const alreadyRead = version && version === readVersion()
    if (alreadyRead && !opts.fresh) return

    current.value = {
      title: data.title || '',
      content: data.content || '',
      version: version,
    }
    visible.value = true
    markRead(version)
  }

  // 登录成功后调用（也用于页面挂载时调用）
  // - 立即检查一次（覆盖"登录时弹窗"场景）
  // - 启动轮询（覆盖"强制弹窗"场景）
  // opts.fresh=true 表示本次是真正登录动作(LoginModal/登录页)，总是展示最新公告
  async function checkOnLogin(opts = {}) {
    if (!process.client) return
    if (_loginChecking) return
    _loginChecking = true
    try {
      await checkAndShow('login', opts)
      startPolling()
    } finally {
      _loginChecking = false
    }
  }

  // 启动强制弹窗轮询（幂等：重复调用只会保留一个定时器）
  function startPolling() {
    if (!process.client) return
    stopPolling()
    _pollTimer = setInterval(async () => {
      try {
        const auth = useAuth()
        if (!auth.isLoggedIn.value) return
        await checkAndShow('poll')
      } catch (e) {}
    }, POLL_INTERVAL)
  }

  function stopPolling() {
    if (_pollTimer) {
      clearInterval(_pollTimer)
      _pollTimer = null
    }
  }

  function close() {
    visible.value = false
  }

  return {
    visible,
    current,
    checkOnLogin,
    startPolling,
    stopPolling,
    close,
  }
}
