<template>
  <div class="outline-redirect">
    <p>正在跳转...</p>
  </div>
</template>

<script setup>
// /outline 路由已合并到 /create 单页流程中
// 如果 URL 带 outline_no 参数（订单详情查看大纲），传递给 /create
definePageMeta({
  layout: 'console',
})

onMounted(() => {
  const route = useRoute()
  const outlineNo = route.query.outline_no
  if (outlineNo) {
    navigateTo({ path: '/pc/create', query: { outline_no: outlineNo } }, { replace: true })
  } else {
    navigateTo('/pc/create', { replace: true })
  }
})
</script>

<style scoped>
.outline-redirect {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  color: var(--gray-400, #9ca3af);
  font-size: 14px;
}
</style>
