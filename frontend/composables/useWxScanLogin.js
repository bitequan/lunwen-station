/**
 * 微信扫码登录 composable
 * 封装 WxLogin JS SDK 加载、二维码渲染、状态管理，供 login.vue 和 LoginModal 共用。
 * 扫码回调由后端 /api/wechat/scanLogin 处理（redirect_uri 指向 /pc/login）。
 */
import { ref } from 'vue'

// 微信 WxLogin iframe 内部自定义样式：隐藏"微信登录"标题、底部提示、状态文字，
// 让二维码图片铺满整个 iframe。通过 base64 data URI 注入，避免跨域/混合内容限制。
const WX_LOGIN_CSS = [
  '.impowerBox .title,',
  '.impowerBox .info,',
  '.impowerBox .status,',
  '.impowerBox .wrp_code .status {',
  '  display: none !important;',
  '}',
  '.impowerBox {',
  '  padding: 0 !important;',
  '  height: 100% !important;',
  '  background: transparent !important;',
  '  box-shadow: none !important;',
  '}',
  '.impowerBox .wrp_code {',
  '  padding: 0 !important;',
  '  margin: 0 !important;',
  '  width: 100% !important;',
  '  height: 100% !important;',
  '}',
  '.impowerBox .qrcode {',
  '  width: 100% !important;',
  '  height: 100% !important;',
  '  padding: 0 !important;',
  '  margin: 0 !important;',
  '}',
  '.impowerBox .qrcode img,',
  '.impowerBox .qrcode .lightBorder {',
  '  width: 100% !important;',
  '  height: 100% !important;',
  '  display: block !important;',
  '  border: 0 !important;',
  '  border-radius: 0 !important;',
  '}',
].join('\n')

// WxLogin JS SDK 全局只加载一次
let wxScriptLoading = null

function loadWxLoginScript() {
  if (typeof window === 'undefined') return Promise.reject(new Error('仅在客户端可用'))
  if (window.WxLogin) return Promise.resolve()
  if (wxScriptLoading) return wxScriptLoading
  wxScriptLoading = new Promise((resolve, reject) => {
    const s = document.createElement('script')
    s.src = 'https://res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js'
    s.async = true
    s.onload = () => resolve()
    s.onerror = () => {
      wxScriptLoading = null
      reject(new Error('加载微信 SDK 失败，请检查网络'))
    }
    document.head.appendChild(s)
  })
  return wxScriptLoading
}

export function useWxScanLogin() {
  const wxLoading = ref(false)
  const wxError = ref('')
  const api = useApi()

  /**
   * 渲染微信扫码二维码
   * @param {string} containerId  二维码容器 DOM id
   * @param {object} opts        可选 { self_redirect } 为 true 时，扫码确认后只在 iframe 内跳转，父页面不离开、不弹新标签页（避免被拦截）
   */
  async function renderQrCode(containerId = 'wx-login-container', opts = {}) {
    wxError.value = ''
    wxLoading.value = true
    try {
      const res = await api.get('/api/wechat/scanLoginUrl')
      if (!res.ok) {
        wxError.value = res.msg || '获取扫码配置失败，请稍后重试'
        return
      }
      await loadWxLoginScript()
      const cfg = res.data
      const el = document.getElementById(containerId)
      if (!el) return
      el.innerHTML = ''
      // href 用 base64 data URI 注入自定义 CSS，隐藏 iframe 内标题/提示文字，让二维码铺满容器
      const href = 'data:text/css;base64,' + window.btoa(unescape(encodeURIComponent(WX_LOGIN_CSS)))
      // eslint-disable-next-line no-new
      new window.WxLogin({
        self_redirect: !!(opts && opts.self_redirect),
        id: containerId,
        appid: cfg.appid,
        scope: cfg.scope || 'snsapi_login',
        redirect_uri: encodeURIComponent(cfg.redirect_uri),
        state: cfg.state,
        style: 'black',
        href,
      })
      // WxLogin 创建 iframe 后，等待 iframe 加载微信 qrconnect 页面和二维码图片
      // 监听 iframe load 事件 + 最小等待 900ms（取较大值），确保二维码真正渲染后再淡出 loading
      // 这样配合前端 Transition 淡出动画，二维码平滑显现，无空白闪烁
      await waitQrIframeReady(containerId)
    } catch (e) {
      wxError.value = e?.message || '加载二维码失败，请稍后重试'
    } finally {
      wxLoading.value = false
    }
  }

  /**
   * 等待二维码 iframe 加载完成
   * - 监听 iframe 的 load 事件（微信 qrconnect 页面 HTML 加载完）
   * - 保证最小等待 900ms（二维码图片渲染需要额外时间）
   * - 最大等待 4 秒，避免网络异常时卡死
   */
  function waitQrIframeReady(containerId) {
    return new Promise((resolve) => {
      const start = Date.now()
      const MIN_WAIT = 900
      const MAX_WAIT = 4000
      let settled = false

      const finish = () => {
        if (settled) return
        settled = true
        const elapsed = Date.now() - start
        const remaining = Math.max(0, MIN_WAIT - elapsed)
        setTimeout(resolve, remaining)
      }

      // 兜底：MAX_WAIT 后强制结束
      setTimeout(finish, MAX_WAIT)

      // 轮询找 iframe（WxLogin SDK 异步创建）
      const checkIframe = () => {
        if (settled) return
        if (Date.now() - start > MAX_WAIT) return
        const el = document.getElementById(containerId)
        const iframe = el?.querySelector('iframe')
        if (iframe) {
          // 监听 load 事件
          if (iframe.dataset.qrBound !== '1') {
            iframe.dataset.qrBound = '1'
            iframe.addEventListener('load', finish, { once: true })
          }
          // 如果 iframe 已经加载完（缓存情况），直接 finish
          try {
            if (iframe.contentWindow && iframe.contentWindow.readyState === 'complete') {
              finish()
            }
          } catch (e) {
            // 跨域访问 contentWindow 会抛错，忽略，等 load 事件
          }
        } else {
          setTimeout(checkIframe, 50)
        }
      }
      checkIframe()
    })
  }

  function clearError() {
    wxError.value = ''
  }

  return { wxLoading, wxError, renderQrCode, clearError }
}
