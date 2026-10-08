<template>
  <div class="m-internship-wrap"><InternshipPage /></div>
</template>

<script setup>
import InternshipPage from '../pc/writing/internship.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '实习报告' })
</script>

<style scoped>
.m-internship-wrap { min-height: calc(100vh - 52px); }
</style>
