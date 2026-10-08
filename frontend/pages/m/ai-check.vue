<template>
  <div class="m-aicheck-wrap"><AiCheckPage /></div>
</template>

<script setup>
import AiCheckPage from '../pc/ai-check.vue'
definePageMeta({ layout: 'm' })
useHead({ title: 'AI检测' })
</script>

<style scoped>
.m-aicheck-wrap { min-height: calc(100vh - 52px); }
</style>
