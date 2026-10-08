/**
 * 全局登录弹窗控制器（单例）
 *
 * 背景：移除独立登录页 /pc/login 后，整站只在 header 触发的登录弹窗（LoginModal）
 * 登录。本 composable 在 app.vue 挂载唯一的 LoginModal，供任意布局/页面统一开关，
 * 避免各布局各自维护本地 showLogin 导致重复实例。
 */
import { ref } from 'vue'
import { useAuth } from './useAuth'

// 模块级单例状态：被所有 useLoginModal() 调用方共享
const isOpen = ref(false)
// 用户是否主动关掉了弹窗（用于抑制「登录过期」等自动重弹，保证可先不登录预览页面）
const manuallyClosed = ref(false)
let successHandler = null

/**
 * @param {(payload?:object)=>void} [cb] 登录成功回调（弹窗关闭后执行，只执行一次）
 */
function open(cb) {
  manuallyClosed.value = false
  successHandler = (typeof cb === 'function' ? cb : null)
  isOpen.value = true
}

function close() {
  manuallyClosed.value = true
  isOpen.value = false
}

/**
 * 供模板 v-model 写入使用。显式写 ref 的 .value，避免对返回值属性做赋值时
 * 意外覆盖掉 ref（导致 isOpen 与 DOM 显示状态偏离、弹窗开/关失控）。
 */
function setOpen(v) {
  if (v !== true) manuallyClosed.value = true
  isOpen.value = v === true
}

/**
 * 仅当用户未主动关闭、且当前仍未登录时才自动弹出。
 * 供「登录过期」等定时器调用，避免用户关掉弹窗后又被强弹、无法先浏览页面。
 */
function openIfNeeded(cb) {
  if (manuallyClosed.value) return
  let loggedIn = false
  try { loggedIn = useAuth().isLoggedIn.value === true } catch (e) {}
  if (loggedIn) return
  open(cb)
}

function handleSuccess(payload) {
  const cb = successHandler
  successHandler = null
  isOpen.value = false
  if (cb) cb(payload)
}

export function useLoginModal() {
  return { isOpen, open, openIfNeeded, close, setOpen, handleSuccess }
}