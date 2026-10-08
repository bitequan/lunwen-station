<template>
  <div class="m-createoutline-wrap"><CreateoutlinePage /></div>
</template>

<script setup>
import CreateoutlinePage from '../pc/tools/createoutline.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '大纲生成' })
</script>

<style scoped>
.m-createoutline-wrap { min-height: calc(100vh - 52px); }
</style>
