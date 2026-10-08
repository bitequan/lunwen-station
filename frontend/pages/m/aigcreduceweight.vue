<template>
  <div class="m-arw-wrap"><AigcReduceWeightPage /></div>
</template>

<script setup>
import AigcReduceWeightPage from '../pc/tools/aigcreduceweight.vue'
definePageMeta({ layout: 'm' })
useHead({ title: 'AI降重' })
</script>

<style scoped>
.m-arw-wrap { min-height: calc(100vh - 52px); }
</style>
