<template>
  <div></div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

const router = useRouter()
const { fetchSite, defaultHomeRoute } = useSite()

// 首页落地跟随商品管理「设为默认/首页默认」商品；无默认商品时回退 /create。
// 等待 router ready 后再落地，避免 hydration 时 router 未就绪导致 replace 被吞、停留旧目标。
onMounted(async () => {
  await fetchSite()
  try { await router.isReady() } catch (e) {}
  const target = defaultHomeRoute.value
  if (target && target !== router.currentRoute.value.path) {
    router.replace(target)
  }
})
</script>