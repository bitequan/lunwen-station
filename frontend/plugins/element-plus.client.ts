// Element Plus 全局注册（仅客户端，SSR 关闭无需担心）
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(ElementPlus)
})
