<template>
  <div class="m-createchart-wrap"><CreatechartPage /></div>
</template>

<script setup>
import CreatechartPage from '../pc/tools/createchart.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '图表生成' })
</script>

<style scoped>
.m-createchart-wrap { min-height: calc(100vh - 52px); }
</style>
