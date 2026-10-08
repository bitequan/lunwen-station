<template>
  <!-- 分站入口：根据站点配置选择分站模板
       当前仅有「工作台」模板；后续新增模板在此按 template 字段扩展，互不影响 -->
  <WorkbenchTemplate v-if="template === 'workbench'" />
</template>

<script setup>
import WorkbenchTemplate from './templates/workbench/SubSiteWorkbench.vue'

const site = useSite()

// 站点可配置模板，缺省用工作台模板；未来可在 useSite 增加 template 映射
const template = computed(() => site.site.value?.template || 'workbench')
</script>
