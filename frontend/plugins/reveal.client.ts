/**
 * v-reveal 指令：元素滚动进入视口时渐显上移
 *
 * 用法：
 *   <section v-reveal>          // 默认渐显
 *   <section v-reveal="120">    // 延迟 120ms（用于网格内子项错落）
 *
 * 原理：mounted 时给元素加 .reveal（opacity:0 + translateY），
 * IntersectionObserver 检测进入视口后加 .is-revealed（opacity:1 + translateY:0）。
 * 一次性触发，触发后 unobserve。
 */
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('reveal', {
    mounted(el: HTMLElement, binding) {
      // 不支持 IntersectionObserver 的环境直接显示
      if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
        return
      }

      // 元素已完全滚出视口上方（如从中间位置刷新）：保持默认可见，不加入场动效
      const rect = el.getBoundingClientRect()
      if (rect.bottom < 0) {
        return
      }

      el.classList.add('reveal')

      // 延迟参数：v-reveal="120" → transition-delay: 120ms
      const delay = typeof binding.value === 'number' ? binding.value : 0
      if (delay > 0) {
        el.style.transitionDelay = `${delay}ms`
      }

      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              el.classList.add('is-revealed')
              observer.unobserve(el)
            }
          })
        },
        {
          threshold: 0.12,
          rootMargin: '0px 0px -60px 0px'
        }
      )

      // 下一帧再 observe，避免首次加载时已可见的元素被漏掉
      requestAnimationFrame(() => {
        observer.observe(el)
      })
    }
  })
})
