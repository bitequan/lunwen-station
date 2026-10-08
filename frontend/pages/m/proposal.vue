<template>
  <div class="m-proposal-wrap"><ProposalPage /></div>
</template>

<script setup>
import ProposalPage from '../pc/writing/proposal.vue'
definePageMeta({ layout: 'm' })
useHead({ title: '开题报告' })
</script>

<style scoped>
.m-proposal-wrap { min-height: calc(100vh - 52px); }
</style>
