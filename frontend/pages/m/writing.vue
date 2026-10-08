<template>
  <div class="m-writing-wrap"><WritingCenter /></div>
</template>

<script setup>
import WritingCenter from '../pc/writing/index.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '写作中心' })
</script>

<style scoped>
.m-writing-wrap { min-height: calc(100vh - 52px); }
/* ≤640：hero 收紧（m-main 14px + 卡体 12~14px，与工具页边距口径一致） */
@media (max-width: 640px) {
  .m-writing-wrap :deep(.wc-hero) { padding: 12px 14px; gap: 12px; }
}
</style>
