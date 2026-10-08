<template>
  <div class="m-illustration-wrap"><IllustrationPage /></div>
</template>

<script setup>
import IllustrationPage from '../pc/tools/illustration.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '段落配图' })
</script>

<style scoped>
.m-illustration-wrap { min-height: calc(100vh - 52px); }
</style>
