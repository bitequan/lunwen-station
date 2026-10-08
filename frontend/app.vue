<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <ToastContainer />
    <!-- 全局登录弹窗：唯一实例，由 useLoginModal 单例控制（已移除独立 /pc/login 登录页）。
     注意：useLoginModal 返回的是普通对象、其 isOpen 是 ref，模板中普通对象里的 ref 不会被自动拆包，
     因此必须经 computed loginOpen 显式取 .value，否则会给 modal 传入 ref 对象（恒真）导致弹窗常驻、关闭无效 -->
    <LoginModal :model-value="loginOpen" @update:model-value="login.setOpen" @success="login.handleSuccess" @contact="handleContact" />
    <!-- 公共公告弹窗（全局，由 useAnnouncement 控制可见性） -->
    <AnnouncementModal />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted } from 'vue'

// 全局 SEO：根据后台配置的 SEO 标题/关键词/描述设置 <title> 与 meta
useSeo()

const login = useLoginModal()
// 普通对象里的 ref 模板不会自动拆包，这里显式取 .value 作为弹窗可见性
const loginOpen = computed(() => login.isOpen.value)

// 全局登录态恢复：restore 从 localStorage 同步读回 token+user（幂等，页面级的散点调用保留无害）。
// 此前 restore 只散落在部分页面（m/index、m/user、Navbar 等），ai-check / aigcreduceweight 等
// 薄壳页未调用 → 刷新或直达这些页时 header 误显示未登录，切到调用过 restore 的页面才恢复
const auth = useAuth()
onMounted(() => { auth.restore() })

// 联系客服：当前页有 #contact 元素则滚动，否则跳转到首页联系区
const handleContact = async () => {
  const route = useRoute()
  const router = useRouter()
  if (route.path === '/' || route.path === '/pc/' || route.path === '/pc') {
    await nextTick()
    document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })
  } else {
    await router.push('/')
    await nextTick()
    setTimeout(() => {
      document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })
    }, 100)
  }
}
</script>