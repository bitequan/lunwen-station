<template>
  <div class="m-diary-wrap"><InternshipdiaryPage /></div>
</template>

<script setup>
import InternshipdiaryPage from '../pc/writing/internshipdiary.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '实习日记' })
</script>

<style scoped>
.m-diary-wrap { min-height: calc(100vh - 52px); }
</style>
