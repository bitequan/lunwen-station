<template>
  <div class="m-paperweight-wrap"><PaperweightPage /></div>
</template>

<script setup>
import PaperweightPage from '../pc/tools/paperweight.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '论文增重' })
</script>

<style scoped>
.m-paperweight-wrap { min-height: calc(100vh - 52px); }
</style>
