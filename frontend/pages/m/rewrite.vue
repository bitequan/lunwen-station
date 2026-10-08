<template>
  <div class="m-rewrite-wrap"><RewritePage /></div>
</template>

<script setup>
import RewritePage from '../pc/tools/rewrite.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '段落改写' })
</script>

<style scoped>
.m-rewrite-wrap { min-height: calc(100vh - 52px); }
</style>
