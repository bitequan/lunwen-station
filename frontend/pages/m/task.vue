<template>
  <div class="m-task-wrap"><TaskPage /></div>
</template>

<script setup>
import TaskPage from '../pc/writing/task.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '任务书' })
</script>

<style scoped>
.m-task-wrap { min-height: calc(100vh - 52px); }
</style>
