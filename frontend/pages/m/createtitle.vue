<template>
  <div class="m-createtitle-wrap"><CreatetitlePage /></div>
</template>

<script setup>
import CreatetitlePage from '../pc/tools/createtitle.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '题目生成' })
</script>

<style scoped>
.m-createtitle-wrap { min-height: calc(100vh - 52px); }
</style>
