// 全局 Toast 提示管理
// 用法：const toast = useToast()
//      toast.success('操作成功')
//      toast.error('操作失败')
//      toast.info('提示信息')
//      toast.warning('警告信息')

const toasts = ref([]) // 全局共享同一份状态
let idCounter = 0

export function useToast() {
  function add(message, type = 'info', duration = 2500) {
    const id = ++idCounter
    toasts.value.push({
      id,
      message,
      type, // success | error | info | warning
      duration,
    })
    // 自动移除
    if (process.client) {
      setTimeout(() => remove(id), duration)
    }
    return id
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx > -1) toasts.value.splice(idx, 1)
  }

  return {
    toasts,
    remove,
    success: (msg, duration) => add(msg, 'success', duration),
    error: (msg, duration) => add(msg, 'error', duration),
    info: (msg, duration) => add(msg, 'info', duration),
    warning: (msg, duration) => add(msg, 'warning', duration),
  }
}
