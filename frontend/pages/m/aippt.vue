<template>
  <div class="m-aippt-wrap"><AipptPage /></div>
</template>

<script setup>
import AipptPage from '../pc/aippt.vue'
definePageMeta({ layout: 'm' })
useHead({ title: 'AIPPT' })
</script>

<style scoped>
.m-aippt-wrap { min-height: calc(100vh - 52px); }
</style>
