<template>
  <div class="m-wxlist-wrap"><WxlistPage /></div>
</template>

<script setup>
import WxlistPage from '../pc/tools/wxlist.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '参考文献获取' })
</script>

<style scoped>
.m-wxlist-wrap { min-height: calc(100vh - 52px); }
</style>
