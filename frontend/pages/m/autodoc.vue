<template>
  <div class="m-autodoc-wrap">
    <AutodocPage />
  </div>
</template>

<script setup>
// 复用 PC 格式重排页面组件：功能/逻辑/样式 100% 同源，仅换用 m 壳层
// （顶部 m-header + 底部 tabbar），弹窗与根高度按 52px 顶栏基准适配。
import AutodocPage from '../pc/autodoc.vue'

definePageMeta({ layout: 'm' })
useHead({ title: '格式重排' })
</script>

<style scoped>
/* m 壳层适配：console 壳的 64px 顶栏 / 248px 侧栏在此不存在，改按 52px m-header 基准 */
.m-autodoc-wrap { min-height: calc(100vh - 52px); }
.m-autodoc-wrap :deep(.mode-mask) { top: 52px; left: 0; }
</style>
