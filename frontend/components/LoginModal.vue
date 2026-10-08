<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="modal-backdrop" @click.self="close">
        <Transition name="scale">
          <div v-if="modelValue" class="modal-card">
            <button class="modal-close" @click="close" aria-label="关闭">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>

            <div class="modal-header">
              <img class="modal-logo" :src="brandLogo" :alt="brandName" />
              <h2 class="modal-title">欢迎登录{{ brandName }}</h2>
              <p class="modal-desc">AI 写作 · AI 降重 · 论文排版一站式</p>
            </div>

            <div class="login-tabs">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                class="tab-btn"
                :class="{ active: activeTab === tab.key }"
                @click="activeTab = tab.key"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="tab.icon"></svg>
                {{ tab.label }}
              </button>
            </div>

            <div class="modal-body">
              <div v-if="activeTab === 'wechat'" class="wechat-panel">
                <div class="qr-wrap">
                  <div id="wx-login-container" class="qr-code"></div>
                  <Transition name="qr-fade">
                    <div v-if="wxLoading" class="qr-overlay">
                      <span class="qr-spinner"></span>
                      <p>正在加载二维码...</p>
                    </div>
                  </Transition>
                  <Transition name="qr-fade">
                    <div v-if="wxError" class="qr-overlay qr-overlay-error">
                      <p>{{ wxError }}</p>
                      <button class="qr-retry-btn" @click="renderQrCode">重新加载</button>
                    </div>
                  </Transition>
                </div>
                <p class="qr-tip">请使用微信扫一扫登录</p>
              </div>

              <form v-else-if="activeTab === 'account'" class="account-form" @submit.prevent="forgotMode ? handleForgotReset() : (emailLoginMode ? handleEmailLogin() : handleLogin())">
                <!-- 找回密码（仅支持邮箱） -->
                <template v-if="forgotMode">
                  <div class="forgot-head">
                    <button type="button" class="back-link" @click="closeForgot">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                      返回登录
                    </button>
                  </div>
                  <p class="forgot-desc">仅支持已绑定邮箱的账号通过邮箱验证码重置密码，手机号注册的账号请联系客服处理</p>
                  <div class="field">
                    <label>邮箱</label>
                    <div class="code-row">
                      <input v-model="forgotForm.email" type="text" placeholder="请输入已注册邮箱" required :disabled="forgotSubmitting" autocomplete="email" />
                      <button type="button" class="send-btn" :disabled="forgotSubmitting || forgotCodeSending || forgotCountdown > 0" @click="handleSendResetCode">
                        {{ forgotCountdown > 0 ? forgotCountdown + 's' : (forgotCodeSending ? '发送中...' : '获取验证码') }}
                      </button>
                    </div>
                  </div>
                  <div class="field">
                    <label>验证码</label>
                    <input v-model="forgotForm.code" type="text" maxlength="6" placeholder="请输入验证码" required :disabled="forgotSubmitting" autocomplete="one-time-code" @keyup.enter="handleForgotReset" />
                  </div>
                  <div class="field">
                    <label>新密码</label>
                    <div class="pwd-field">
                      <input v-model="forgotForm.password" :type="showForgotPwd ? 'text' : 'password'" placeholder="请设置新密码（6-32 位）" required :disabled="forgotSubmitting" autocomplete="new-password" />
                      <button type="button" class="pwd-toggle" @click="showForgotPwd = !showForgotPwd" :aria-label="showForgotPwd ? '隐藏密码' : '显示密码'" tabindex="-1">
                        <svg v-if="showForgotPwd" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                          <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                          <circle cx="12" cy="12" r="3"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="field">
                    <label>确认新密码</label>
                    <input v-model="forgotForm.confirmPassword" type="password" placeholder="请再次输入新密码" required :disabled="forgotSubmitting" autocomplete="new-password" @keyup.enter="handleForgotReset" />
                  </div>
                  <div v-if="forgotError" class="form-error">{{ forgotError }}</div>
                  <button type="submit" class="btn btn-primary submit-btn" :disabled="forgotSubmitting">
                    <span v-if="forgotSubmitting" class="btn-spinner"></span>
                    {{ forgotSubmitting ? '提交中...' : '重置密码' }}
                  </button>
                  <p class="forgot-tip">手机号注册的账号，请直接联系客服找回密码</p>
                </template>
                <!-- 账号密码登录 -->
                <template v-else-if="!emailLoginMode">
                  <div class="field">
                    <label>账号</label>
                    <input v-model="form.account" type="text" placeholder="手机号 / 邮箱 / 账号" required :disabled="loading" autocomplete="username" />
                  </div>
                  <div class="field">
                    <label>密码</label>
                    <div class="pwd-field">
                      <input v-model="form.password" :type="showPwd ? 'text' : 'password'" placeholder="请输入密码" required :disabled="loading" autocomplete="current-password" />
                      <button type="button" class="pwd-toggle" @click="showPwd = !showPwd" :aria-label="showPwd ? '隐藏密码' : '显示密码'" tabindex="-1">
                        <svg v-if="showPwd" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                          <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                          <circle cx="12" cy="12" r="3"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="forgot-row">
                    <a href="javascript:void(0)" class="forgot-link" @click="openForgot">忘记密码？</a>
                  </div>
                  <div v-if="errorMsg" class="form-error">{{ errorMsg }}</div>
                  <button type="submit" class="btn btn-primary submit-btn" :disabled="loading">
                    <span v-if="loading" class="btn-spinner"></span>
                    {{ loading ? '登录中...' : '登录' }}
                  </button>
                  <a v-if="emailLoginEnabled" href="javascript:void(0)" class="switch-login" @click="switchToEmailLogin">邮箱验证码登录</a>
                </template>
                <!-- 邮箱验证码登录 -->
                <template v-else>
                  <div class="field">
                    <label>邮箱</label>
                    <input v-model="emailForm.account" type="email" placeholder="请输入已注册邮箱" required :disabled="loading" autocomplete="email" />
                  </div>
                  <div class="field">
                    <label>验证码</label>
                    <div class="code-row">
                      <input v-model="emailForm.code" type="text" maxlength="6" placeholder="请输入验证码" required :disabled="loading" autocomplete="one-time-code" @keyup.enter="handleEmailLogin" />
                      <button type="button" class="send-btn" :disabled="loading || codeSending || countdown > 0" @click="handleSendEmailCode">
                        {{ countdown > 0 ? countdown + 's' : (codeSending ? '发送中...' : '获取验证码') }}
                      </button>
                    </div>
                  </div>
                  <div v-if="emailError" class="form-error">{{ emailError }}</div>
                  <button type="submit" class="btn btn-primary submit-btn" :disabled="loading">
                    <span v-if="loading" class="btn-spinner"></span>
                    {{ loading ? '登录中...' : '登录' }}
                  </button>
                  <a href="javascript:void(0)" class="switch-login" @click="switchToPasswordLogin">返回密码登录</a>
                </template>
              </form>

              <form v-else-if="activeTab === 'register'" class="account-form" @submit.prevent="handleRegister">
                <div class="field">
                  <label>{{ regAccountLabel }}</label>
                  <input
                    v-model="regForm.account"
                    type="text"
                    :placeholder="regAccountPlaceholder"
                    required
                    :disabled="loading"
                    autocomplete="username"
                  />
                </div>
                <div class="field">
                  <label>密码</label>
                  <div class="pwd-field">
                    <input
                      v-model="regForm.password"
                      :type="showPwd ? 'text' : 'password'"
                      placeholder="请设置密码（6-32 位）"
                      required
                      :disabled="loading"
                      autocomplete="new-password"
                      @input="checkRegPwdMatch"
                    />
                    <button
                      type="button"
                      class="pwd-toggle"
                      @click="showPwd = !showPwd"
                      :aria-label="showPwd ? '隐藏密码' : '显示密码'"
                      tabindex="-1"
                    >
                      <svg v-if="showPwd" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                      </svg>
                      <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="field">
                  <label>确认密码</label>
                  <div class="pwd-field">
                    <input v-model="regConfirmPwd" :type="showPwd ? 'text' : 'password'" placeholder="请再次输入密码" required :disabled="loading" autocomplete="new-password" @input="checkRegPwdMatch" />
                    <button type="button" class="pwd-toggle" @click="showPwd = !showPwd" :aria-label="showPwd ? '隐藏密码' : '显示密码'" tabindex="-1">
                      <svg v-if="showPwd" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                      </svg>
                      <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                  </div>
                  <div v-if="regPwdMismatch" class="pwd-match-hint">两次输入的密码不一致</div>
                </div>
                <div v-if="!isSubSite" class="field">
                  <label>邀请码</label>
                  <input v-model="regForm.invite_code" type="text" placeholder="请输入邀请码（必填，由上级代理提供）" required :disabled="loading" maxlength="20" />
                  <p v-if="inviteHint && inviteOk" class="invite-hint ok">{{ inviteHint }}</p>
                </div>
                <p v-else class="subsite-reg-tip">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  {{ siteNameText }} 专属分站：无需邀请码，注册即自动归属本站代理
                </p>
                <div v-if="regError" class="form-error">{{ regError }}</div>
                <button type="submit" class="btn btn-primary submit-btn" :disabled="loading">
                  <span v-if="loading" class="btn-spinner"></span>
                  {{ loading ? '注册中...' : (needTwoStep ? '下一步' : '立即注册') }}
                </button>
              </form>
            </div>

            <div class="modal-footer">
              <p v-if="activeTab === 'register'">已有账号？<a href="javascript:void(0)" @click="activeTab = 'account'">直接登录</a></p>
              <p v-else>还没有账号？<a href="javascript:void(0)" @click="activeTab = 'register'">立即注册</a></p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
// 默认 logo 用 public 静态路径：管理员上传后该文件被直接替换，无需等待站点配置接口；
// 动态 :src 必须用完整 URL 字符串，不能写 '~/assets/images/logo.png'（会跳过构建期路径处理）
const defaultLogo = '/pc/logo.png'
const DEFAULT_BRAND = 'AI写作助手'

const props = defineProps(['modelValue'])
const emit = defineEmits(['update:modelValue', 'contact', 'success'])

// 默认进入微信扫码 Tab（最便捷的登录方式）；微信渠道关闭时回退账号密码。
// 初始取 'account' 保险：打开弹窗的 watch 会按 wechatEnabled 重新设定，未进入微信 Tab 前不闪微信面板
const activeTab = ref('account')

// 微信开放平台扫码登录是否启用（随后台「快捷登录对接」开关动态隐藏微信扫码 Tab）
const wechatEnabled = computed(() => Number(regConfig.value.wechat_auth) === 1)

const WX_TAB = { key: 'wechat', label: '微信扫码', icon: '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>' }
const BASE_TABS = [
  { key: 'account', label: '账号密码', icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' },
  { key: 'register', label: '注册账号', icon: '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>' },
]
const tabs = computed(() => {
  const list = wechatEnabled.value ? [WX_TAB] : []
  list.push(...BASE_TABS)
  return list
})

// 品牌/站点：优先用 useSite() 动态配置（分站），为空则兜底默认品牌
const site = useSite()
const brandName = computed(() => (site.siteName.value || '').trim() || DEFAULT_BRAND)
const brandLogo = computed(() => (site.logo.value || '').trim() || defaultLogo)

const form = ref({ account: '', password: '' })
const regForm = ref({ account: '', password: '', invite_code: '' })
const regConfirmPwd = ref('')
const regPwdMismatch = ref(false)
// 确认密码与密码不一致时实时提示
function checkRegPwdMatch() {
  regPwdMismatch.value = !!regConfirmPwd.value && regForm.value.password !== regConfirmPwd.value
}
const loading = ref(false)
const errorMsg = ref('')
const regError = ref('')
const codeChecking = ref(false)
const inviteOk = ref(false)
const inviteHint = ref('')
// 密码明文切换
const showPwd = ref(false)
// 注册配置（coerce_email / coerce_mobile 开关 + login_way 登录方式 + 第三方开关）
const regConfig = ref({ coerce_email: 0, coerce_mobile: 1, login_way: ['1', '2'], qq_auth: 0, wechat_auth: 0 })

const auth = useAuth()
const toast = useToast()
const api = useApi()
const wxLogging = ref(false) // 扫码回调处理中的登录态
const wxPanelError = ref('') // 扫码面板错误提示（供扫码面板里的「校验失败」展示）

// 分站识别：分站域名下注册免邀请码（注册后自动归属分站代理）
const isSubSite = computed(() => !!site.isSubSite.value)
// 注册相关标题里的站名（默认兜底）
const siteNameText = computed(() => brandName.value)

// 是否走两步流程（任一开关开启）
const needTwoStep = computed(() => !!(regConfig.value.coerce_email || regConfig.value.coerce_mobile))

// ============ 邮箱验证码登录状态 ============
const emailLoginMode = ref(false)
const emailForm = ref({ account: '', code: '' })
const emailError = ref('')
const codeSending = ref(false)
const countdown = ref(0)
let countdownTimer = null
// 是否开启邮箱验证码登录（后端 login_way 含 "4"）
const emailLoginEnabled = computed(() => {
  const ways = regConfig.value.login_way || []
  return ways.map(String).includes('4')
})

// ============ 找回密码（仅支持邮箱） ============
const forgotMode = ref(false)
const forgotForm = ref({ email: '', code: '', password: '', confirmPassword: '' })
const forgotError = ref('')
const forgotCodeSending = ref(false)
const forgotCountdown = ref(0)
let forgotCountdownTimer = null
const showForgotPwd = ref(false)
const forgotSubmitting = ref(false)

function openForgot() {
  forgotMode.value = true
  forgotError.value = ''
  errorMsg.value = ''
  emailError.value = ''
}

function closeForgot() {
  forgotMode.value = false
  forgotError.value = ''
  forgotForm.value = { email: '', code: '', password: '', confirmPassword: '' }
  if (forgotCountdownTimer) { clearInterval(forgotCountdownTimer); forgotCountdownTimer = null }
  forgotCountdown.value = 0
}

function startForgotCountdown() {
  forgotCountdown.value = 60
  forgotCountdownTimer = setInterval(() => {
    forgotCountdown.value--
    if (forgotCountdown.value <= 0) {
      clearInterval(forgotCountdownTimer)
      forgotCountdownTimer = null
    }
  }, 1000)
}

async function handleSendResetCode() {
  const email = forgotForm.value.email.trim()
  forgotError.value = ''
  if (!email) { forgotError.value = '请输入邮箱'; return }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    forgotError.value = /^1[3-9]\d{9}$/.test(email) ? '手机号注册的账号，请联系客服找回密码' : '邮箱格式不正确'
    return
  }
  forgotCodeSending.value = true
  try {
    const res = await auth.sendResetPwdCode(email)
    if (res.ok) {
      toast.success('验证码已发送，请到邮箱查收')
      startForgotCountdown()
    } else {
      forgotError.value = res.msg || '发送失败'
    }
  } catch (e) {
    forgotError.value = e?.message || '发送失败，请稍后重试'
  } finally {
    forgotCodeSending.value = false
  }
}

async function handleForgotReset() {
  if (forgotSubmitting.value) return
  forgotError.value = ''
  const email = forgotForm.value.email.trim()
  const code = forgotForm.value.code.trim()
  const pwd = forgotForm.value.password
  const confirm = forgotForm.value.confirmPassword
  if (!email) { forgotError.value = '请输入邮箱'; return }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { forgotError.value = '请输入正确的邮箱'; return }
  if (!code) { forgotError.value = '请输入验证码'; return }
  if (!pwd || pwd.length < 6 || pwd.length > 32) { forgotError.value = '密码长度需为 6-32 位'; return }
  if (pwd !== confirm) { forgotError.value = '两次输入的密码不一致'; return }
  forgotSubmitting.value = true
  try {
    const res = await auth.resetPassword({ email, code, password: pwd })
    if (res.ok) {
      toast.success('密码重置成功，请使用新密码登录')
      form.value.account = email
      closeForgot()
    } else {
      forgotError.value = res.msg || '重置失败'
    }
  } catch (e) {
    forgotError.value = e?.message || '重置失败，请稍后重试'
  } finally {
    forgotSubmitting.value = false
  }
}

// 注册账号字段标签（与后端 coerce_email / coerce_mobile 开关一致）
const regAccountLabel = computed(() => {
  const { coerce_email, coerce_mobile } = regConfig.value
  if (coerce_email && coerce_mobile) return '手机号 / 邮箱'
  if (coerce_email) return '邮箱'
  if (coerce_mobile) return '手机号'
  return '手机号或邮箱'
})

// 注册账号字段 placeholder（与后端 coerce_email / coerce_mobile 开关一致）
const regAccountPlaceholder = computed(() => {
  const { coerce_email, coerce_mobile } = regConfig.value
  if (coerce_email && coerce_mobile) return '请输入手机号或邮箱'
  if (coerce_email) return '输入邮箱'
  if (coerce_mobile) return '请输入手机号'
  return '填写手机号或者邮箱'
})

// 根据输入值判断账号类型（email / mobile）
function detectAccountType(value) {
  const v = (value || '').trim()
  // 邮箱格式
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'email'
  // 中国手机号
  if (/^1[3-9]\d{9}$/.test(v)) return 'mobile'
  return ''
}

// 拉取注册配置
async function loadRegisterConfig() {
  try {
    const res = await auth.registerConfig()
    if (res.ok && res.data) {
      regConfig.value = {
        coerce_email: res.data.coerce_email || 0,
        coerce_mobile: res.data.coerce_mobile || 0,
        login_way: Array.isArray(res.data.login_way) ? res.data.login_way.map(String) : ['1', '2'],
        qq_auth: Number(res.data.qq_auth) || 0,
        wechat_auth: Number(res.data.wechat_auth) || 0,
      }
    }
  } catch (e) {}
}

onMounted(() => {
  loadRegisterConfig()
  // 扫码回调 postMessage：self_redirect=true 后，扫码确认只在 iframe 内跳 /pc/login，
  // LoginModal 自身会在父页面收到 iframe 内登录页 login.vue 的 postMessage，这里统一处理
  if (import.meta.client) {
    window.addEventListener('message', onWxMessage)
  }
})

onUnmounted(() => {
  if (import.meta.client) {
    window.removeEventListener('message', onWxMessage)
  }
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
  if (forgotCountdownTimer) { clearInterval(forgotCountdownTimer); forgotCountdownTimer = null }
})

function saveWxToken(token, u) {
  try { localStorage.setItem('aidian_auth', JSON.stringify({ token, user: u || {} })) } catch (e) {}
}

// 收到 iframe 内 login.vue 回传的扫码结果
function onWxMessage(ev) {
  const d = ev.data || {}
  if (d.type === 'aidian_wx_login') {
    saveWxToken(d.token, d.user)
    toast.success('登录成功')
    emit('success', { token: d.token, user: d.user || {} })
    close()
    try { useAnnouncement().checkOnLogin({ fresh: true }) } catch (e) {}
    // SPA 跳工作台
    if (import.meta.client) { window.location.href = '/pc/user' }
  }
}

// 微信扫码逻辑（composable 统一封装：SDK 加载、二维码渲染、状态管理）
// self_redirect=true：扫码确认后只在 iframe 内跳转回调，父页面不离开、不被浏览器拦截
const { wxLoading, wxError, renderQrCode: renderQrCodeRaw, clearError } = useWxScanLogin()

function renderQrCode() {
  clearError()
  wxPanelError.value = ''
  // 关键：self_redirect=true 让扫码后在 iframe 里完成跳转 → 父页面通过 postMessage 接收回调
  // → 不会再出现「落地页弹窗扫码扫完跳到 /pc/login 单独登录页」的问题
  return renderQrCodeRaw('wx-login-container', { self_redirect: true })
}

// ============ 渲染去重 ============
// watch modelValue 和 watch activeTab 可能连续触发，
// 用 token 保证一次事件循环里只有最后一次 scheduleRender 生效，避免重复渲染二维码
let renderToken = 0
function scheduleRender() {
  const token = ++renderToken
  nextTick(() => {
    if (token === renderToken && activeTab.value === 'wechat' && props.modelValue) {
      renderQrCode()
    }
  })
}

// 弹窗打开时重置状态并渲染微信扫码二维码（默认微信 tab）
watch(() => props.modelValue, (v) => {
  if (v) {
    form.value = { account: '', password: '' }
    regForm.value = { account: '', password: '', invite_code: '' }
    regConfirmPwd.value = ''
    regPwdMismatch.value = false
    // 从 sessionStorage 恢复缓存的邀请码（用户通过推广链接访问后，切换页面再打开弹窗时自动填入；分站免邀请码不恢复）
    if (import.meta.client && !isSubSite.value) {
      try {
        const cached = sessionStorage.getItem('invite_code')
        if (cached) {
          regForm.value.invite_code = cached
        }
      } catch (e) {}
    }
    errorMsg.value = ''
    regError.value = ''
    inviteOk.value = false
    inviteHint.value = ''
    showPwd.value = false
    loading.value = false
    // 重置邮箱验证码登录状态
    emailLoginMode.value = false
    emailForm.value = { account: '', code: '' }
    emailError.value = ''
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
    countdown.value = 0
    // 重置找回密码状态
    forgotMode.value = false
    forgotForm.value = { email: '', code: '', password: '', confirmPassword: '' }
    forgotError.value = ''
    showForgotPwd.value = false
    forgotSubmitting.value = false
    if (forgotCountdownTimer) { clearInterval(forgotCountdownTimer); forgotCountdownTimer = null }
    forgotCountdown.value = 0
    activeTab.value = wechatEnabled.value ? 'wechat' : 'account'
    scheduleRender()
    // 每次打开弹窗刷新注册配置（管理员可能刚改过开关）
    loadRegisterConfig()
  }
})

// 切换到微信扫码 Tab 时补渲染
watch(activeTab, (key) => {
  if (key === 'wechat' && props.modelValue) {
    scheduleRender()
  }
})

// 微信扫码渠道被后台关闭时，若正停留在微信 Tab 则回退到账号密码
watch(wechatEnabled, (enabled) => {
  if (!enabled && props.modelValue && activeTab.value === 'wechat') {
    activeTab.value = 'account'
  }
})

const close = () => {
  emit('update:modelValue', false)
}

const handleLogin = async () => {
  if (loading.value) return
  errorMsg.value = ''
  if (!form.value.account || !form.value.password) {
    errorMsg.value = '请输入账号和密码'
    return
  }
  loading.value = true
  try {
    const res = await auth.login(form.value)
    if (res.ok) {
      toast.success('登录成功')
      emit('success', res.data)
      close()
      // 登录成功后触发公告检查（覆盖「不强制弹窗」登录时弹 + 启动强制弹窗轮询）
      try { useAnnouncement().checkOnLogin({ fresh: true }) } catch (e) {}
    } else {
      errorMsg.value = res.msg || '登录失败'
    }
  } catch (e) {
    errorMsg.value = e?.message || '登录失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

// ============ 邮箱验证码登录 ============
function switchToEmailLogin() {
  emailLoginMode.value = true
  emailError.value = ''
  errorMsg.value = ''
}

function switchToPasswordLogin() {
  emailLoginMode.value = false
  emailError.value = ''
  emailForm.value = { account: '', code: '' }
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
  countdown.value = 0
}

function startCountdown() {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }, 1000)
}

async function handleSendEmailCode() {
  const email = emailForm.value.account.trim()
  if (!email) { emailError.value = '请输入邮箱'; return }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { emailError.value = '邮箱格式不正确'; return }
  emailError.value = ''
  codeSending.value = true
  try {
    const res = await auth.sendEmailLoginCode(email)
    if (res.ok) {
      toast.success('验证码已发送，请到邮箱查收')
      startCountdown()
    } else {
      emailError.value = res.msg || '发送失败'
    }
  } catch (e) {
    emailError.value = e?.message || '发送失败，请稍后重试'
  } finally {
    codeSending.value = false
  }
}

async function handleEmailLogin() {
  if (loading.value) return
  emailError.value = ''
  const email = emailForm.value.account.trim()
  const code = emailForm.value.code.trim()
  if (!email) { emailError.value = '请输入邮箱'; return }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { emailError.value = '邮箱格式不正确'; return }
  if (!code) { emailError.value = '请输入验证码'; return }
  loading.value = true
  try {
    const res = await auth.login({ account: email, password: '', scene: 4, code })
    if (res.ok) {
      toast.success('登录成功')
      emit('success', res.data)
      close()
      // 登录成功后触发公告检查
      try { useAnnouncement().checkOnLogin({ fresh: true }) } catch (e) {}
    } else {
      emailError.value = res.msg || '登录失败'
    }
  } catch (e) {
    emailError.value = e?.message || '登录失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 邀请码校验（调后端 /api/agent/validateCode）
async function validateInviteCode() {
  const code = regForm.value.invite_code?.trim()
  if (!code) {
    inviteOk.value = false
    inviteHint.value = '请输入邀请码'
    return
  }
  codeChecking.value = true
  inviteHint.value = ''
  try {
    const res = await api.post('/api/agent/validateCode', { code })
    if (res.ok && res.data?.valid) {
      inviteOk.value = true
      const inv = res.data.inviter
      inviteHint.value = inv
        ? `邀请人：${inv.nickname || '—'}（${inv.level || 1}级代理）`
        : '邀请码有效'
    } else {
      inviteOk.value = false
      inviteHint.value = res.data?.msg || res.msg || '邀请码无效'
    }
  } catch (e) {
    inviteOk.value = false
    inviteHint.value = '校验失败，请稍后重试'
  } finally {
    codeChecking.value = false
  }
}

// 注册（账号 + 密码 + 邀请码，注册成功自动登录并跳转工作台）
const handleRegister = async () => {
  if (loading.value) return
  regError.value = ''
  if (!regForm.value.account?.trim()) {
    regError.value = '请输入' + regAccountLabel.value
    return
  }
  if (!regForm.value.password || regForm.value.password.length < 6) {
    regError.value = '密码长度需为 6-32 位'
    return
  }
  if (!regConfirmPwd.value) {
    regError.value = '请再次输入密码'
    return
  }
  if (regForm.value.password !== regConfirmPwd.value) {
    regError.value = '两次输入的密码不一致'
    return
  }
  if (!regForm.value.invite_code?.trim()) {
    if (!isSubSite.value) {
      regError.value = '请输入邀请码'
      return
    }
    // 分站：免邀请码，自动归属分站代理
    // 后端 RegisterLogic 会通过 AgentSiteService::getSiteOwnerUserId() 自动绑定
    regForm.value.invite_code = ''
  }
  // 未校验或校验失败时，自动校验一次（仅非分站模式）
  if (!isSubSite.value && !inviteOk.value) {
    await validateInviteCode()
    if (!inviteOk.value) {
      regError.value = '邀请码无效，请检查'
      return
    }
  }

  // 两步流程：手机号/邮箱 + 验证码 + 用户名
  if (needTwoStep.value) {
    const accountType = detectAccountType(regForm.value.account)
    const { coerce_email, coerce_mobile } = regConfig.value
    if (!accountType) {
      regError.value = '请输入有效的手机号或邮箱'
      return
    }
    if (accountType === 'email' && !coerce_email) {
      regError.value = '当前未开启邮箱注册，请使用手机号'
      return
    }
    if (accountType === 'mobile' && !coerce_mobile) {
      regError.value = '当前未开启手机号注册，请使用邮箱'
      return
    }

    loading.value = true
    try {
      const res = await auth.registerPreCheck({
        account_type: accountType,
        account: regForm.value.account,
        password: regForm.value.password,
        invite_code: regForm.value.invite_code,
      })
      if (res.ok && res.data?.register_token) {
        // sessionStorage 存上下文用于第二步显示
        if (import.meta.client) {
          sessionStorage.setItem('register_context', JSON.stringify({
            account_type: accountType,
            account: regForm.value.account.trim(),
          }))
          // 注册进入下一步，邀请码已使用，清除缓存
          try { sessionStorage.removeItem('invite_code') } catch (e) {}
        }
        toast.success('校验通过，请完成验证码与用户名设置')
        close()
        if (import.meta.client) {
          const token = res.data.register_token
          window.location.href = '/pc/register?register_token=' + encodeURIComponent(token)
        }
      } else {
        regError.value = res.msg || '校验失败'
      }
    } catch (e) {
      regError.value = e?.message || '校验失败，请稍后重试'
    } finally {
      loading.value = false
    }
    return
  }

  // 老流程：账号（邮箱/手机号作为登录账号）+ 密码 + 邀请码
  loading.value = true
  try {
    const res = await auth.register(regForm.value)
    if (res.ok) {
      toast.success('注册成功，已自动登录')
      // 注册成功，邀请码已使用，清除缓存
      if (import.meta.client) {
        try { sessionStorage.removeItem('invite_code') } catch (e) {}
      }
      emit('success', res.data)
      close()
      // 注册成功后跳转工作台
      if (import.meta.client) {
        window.location.href = '/pc/user'
      }
    } else {
      regError.value = res.msg || '注册失败'
    }
  } catch (e) {
    regError.value = e?.message || '注册失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

const contact = () => {
  close()
  emit('contact')
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
}

.modal-card {
  position: relative;
  width: 100%;
  max-width: 400px;
  background: var(--white);
  border-radius: var(--radius-xl);
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2);
  padding: 22px 24px;
}

.modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: none;
  background: var(--gray-100);
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: var(--gray-200);
  color: var(--dark-800);
}

.modal-header {
  text-align: center;
  margin-bottom: 14px;
}

.modal-logo {
  width: 40px;
  height: 40px;
  margin: 0 auto 6px;
}

.modal-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 2px;
}

.modal-desc {
  font-size: 13px;
  color: var(--gray-500);
}

.login-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 999px;
}

.tab-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-500);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn.active {
  background: var(--white);
  color: var(--primary-600);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.wechat-panel {
  text-align: center;
}

.qr-wrap {
  position: relative;
  width: 200px;
  height: 200px;
  max-width: 100%;
  margin: 0 auto 8px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
  background: #fff;
}

/* 微信 WxLogin SDK 渲染的 iframe 撑满容器 */
.qr-wrap :deep(iframe) {
  width: 100% !important;
  height: 100% !important;
  border: 0;
  display: block;
}

/* #wx-login-container 必须显式高度，否则 iframe height:100% 无效 */
.qr-wrap :deep(#wx-login-container) {
  width: 100%;
  height: 100%;
}

/* 加载/错误覆盖层：absolute 定位在 qr-wrap 内部，避免与二维码区域并排重复出现 */
.qr-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: #fff;
  color: var(--gray-500);
  font-size: 13px;
  text-align: center;
  padding: 16px;
  z-index: 2;
}

.qr-overlay-error p {
  margin: 0;
  line-height: 1.5;
}

.qr-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid rgba(13, 148, 136, 0.2);
  border-top-color: var(--primary-600);
  border-radius: 50%;
  animation: qr-spin 0.7s linear infinite;
}

@keyframes qr-spin {
  to { transform: rotate(360deg); }
}

.qr-retry-btn {
  background: none;
  border: none;
  color: var(--primary-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
}

/* loading/error 覆盖层淡入淡出，让二维码平滑显现，无突兀切换 */
.qr-fade-enter-active,
.qr-fade-leave-active {
  transition: opacity 0.35s ease;
}
.qr-fade-enter-from,
.qr-fade-leave-to {
  opacity: 0;
}

.qr-tip {
  font-size: 13px;
  color: var(--gray-500);
  margin: 0;
}

.account-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.field label {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
}

.field input {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-200);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.field input:focus {
  border-color: var(--primary-500);
}

/* 密码字段容器：input 占满，眼睛图标按钮绝对定位右侧 */
.pwd-field {
  position: relative;
}

.pwd-field input {
  width: 100%;
  padding-right: 44px;
}

.pwd-toggle {
  position: absolute;
  top: 50%;
  right: 8px;
  transform: translateY(-50%);
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border: none;
  background: transparent;
  color: var(--gray-400);
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: color 0.2s ease, background 0.2s ease;
}

.pwd-toggle:hover {
  color: var(--primary-600);
  background: var(--gray-100);
}

.submit-btn {
  width: 100%;
  margin-top: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  border-top-color: #fff;
  border-radius: 50%;
  animation: btn-spin 0.6s linear infinite;
}

@keyframes btn-spin {
  to { transform: rotate(360deg); }
}

.form-error {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-size: 13px;
  line-height: 1.5;
}

/* 确认密码实时不一致提示 */
.pwd-match-hint {
  margin: 6px 2px 0;
  font-size: 12px;
  line-height: 1.5;
  color: #dc2626;
}

.invite-hint {
  margin: 6px 0 0;
  font-size: 12px;
  line-height: 1.5;
}

/* 分站免邀请码提示 */
.subsite-reg-tip {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 12px;
  border-radius: var(--radius-md);
  background: rgba(20, 184, 166, 0.06);
  border: 1px dashed rgba(20, 184, 166, 0.35);
  color: var(--primary-600);
  font-size: 12px;
  line-height: 1.5;
}
.subsite-reg-tip svg {
  flex-shrink: 0;
  color: var(--primary-500);
}

.invite-hint.ok {
  color: var(--primary-600);
}

.invite-hint.err {
  color: #dc2626;
}

/* 邮箱验证码登录切换链接 */
.switch-login {
  display: block;
  text-align: center;
  margin-top: 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--primary-600);
  cursor: pointer;
  text-decoration: none;
}

.switch-login:hover {
  opacity: 0.8;
}

/* 忘记密码入口 */
.forgot-row {
  display: flex;
  justify-content: flex-end;
}

.forgot-link {
  font-size: 13px;
  font-weight: 600;
  color: var(--primary-600);
  cursor: pointer;
  text-decoration: none;
}

.forgot-link:hover {
  opacity: 0.8;
}

/* 找回密码面板 */
.forgot-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0;
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  cursor: pointer;
  font-family: inherit;
}

.back-link:hover {
  color: var(--primary-600);
}

.forgot-desc {
  margin: 0 0 12px;
  font-size: 12px;
  line-height: 1.6;
  color: var(--gray-400);
}

.forgot-tip {
  margin: 12px 0 0;
  text-align: center;
  font-size: 12px;
  color: var(--gray-400);
}

/* 验证码输入行：input + 获取验证码按钮 */
.code-row {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.code-row input {
  flex: 1;
  min-width: 0;
}

.send-btn {
  flex-shrink: 0;
  padding: 0 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--primary-500);
  background: var(--primary-50, #ecfdf5);
  color: var(--primary-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.send-btn:hover:not(:disabled) {
  background: var(--primary-500);
  color: var(--white);
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-footer {
  margin-top: 16px;
  text-align: center;
  font-size: 13px;
  color: var(--gray-500);
}

.modal-footer a {
  color: var(--primary-600);
  font-weight: 600;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
  transition: all 0.25s ease;
}

.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
</style>
