<template>
  <div class="m-tools">
    <!-- 主推：AI 论文大卡 -->
    <NuxtLink to="/m/create" class="m-tools-hero">
      <div class="m-tools-hero-orb"></div>
      <div class="m-tools-hero-body">
        <span class="m-tag">核心功能</span>
        <div class="m-tools-hero-title">AI 论文一键生成</div>
        <div class="m-tools-hero-desc">大纲 → 正文 → 文献引用，全流程辅助</div>
      </div>
      <span class="m-tools-hero-arrow">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </span>
    </NuxtLink>

    <div class="m-section-title">降重与检测</div>
    <section class="m-card">
      <div class="m-grid-2">
        <NuxtLink v-for="t in checkTools" :key="t.to" :to="t.to" class="m-tool-item">
          <span class="m-tool-icon" :style="{ background: t.bg }" v-html="t.icon"></span>
          <span class="m-tool-name">{{ t.name }}</span>
          <span class="m-tool-desc">{{ t.desc }}</span>
        </NuxtLink>
      </div>
    </section>

    <div class="m-section-title">写作助手</div>
    <section class="m-card">
      <div class="m-grid-2">
        <NuxtLink v-for="t in writeTools" :key="t.to" :to="t.to" class="m-tool-item">
          <span class="m-tool-icon" :style="{ background: t.bg }" v-html="t.icon"></span>
          <span class="m-tool-name">{{ t.name }}</span>
          <span class="m-tool-desc">{{ t.desc }}</span>
        </NuxtLink>
      </div>
    </section>

    <div class="m-section-title">实用小工具</div>
    <section class="m-card">
      <div class="m-grid">
        <NuxtLink v-for="t in miniTools" :key="t.to" :to="t.to" class="m-grid-item">
          <span class="m-grid-icon" :style="{ background: t.bg }" v-html="t.icon"></span>
          <span class="m-grid-label">{{ t.name }}</span>
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '工具箱' })

const site = useSite()

const IC = (d) =>
  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

// 工具清单（名称与 PC 端 /pc/tools 口径一致；feature_map 有开关的走 site.featureEnabled 过滤）
const TOOLS = {
  aigcreduceweight: { name: 'AI降重', desc: '文档 / 段落双模式降AI', to: '/m/aigcreduceweight', key: 'reduce_weight', bg: 'linear-gradient(135deg,#8b5cf6,#7c3aed)', icon: IC('<path d="M12 3v18"/><path d="M3 12h18"/>') },
  aicheck:   { name: 'AI检测', desc: '多平台 AI 率检测', to: '/m/ai-check', key: 'ai_check', bg: 'linear-gradient(135deg,#3b82f6,#2563eb)', icon: IC('<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>') },
  aippt:     { name: 'AIPPT', desc: '大纲生成整套幻灯片', to: '/m/aippt', key: 'aippt', bg: 'linear-gradient(135deg,#f97316,#ea580c)', icon: IC('<rect x="3" y="4" width="18" height="13" rx="2"/><line x1="12" y1="17" x2="12" y2="21"/><line x1="8" y1="21" x2="16" y2="21"/>') },
  autodoc:   { name: '格式重排', desc: '上传 Word 一键排版', to: '/m/autodoc', key: 'autodoc', bg: 'linear-gradient(135deg,#06b6d4,#0891b2)', icon: IC('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') },
  proposal:  { name: '开题报告', desc: '选题依据 + 研究方案', to: '/m/proposal', key: 'writing', bg: 'linear-gradient(135deg,#14b8a6,#0d9488)', icon: IC('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>') },
  task:      { name: '任务书', desc: '毕业设计任务书生成', to: '/m/task', key: 'writing', bg: 'linear-gradient(135deg,#f59e0b,#d97706)', icon: IC('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>') },
  internship:{ name: '实习报告', desc: '岗位实践内容撰写', to: '/m/internship', key: 'writing', bg: 'linear-gradient(135deg,#22c55e,#16a34a)', icon: IC('<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') },
  diary:     { name: '实习日记', desc: '按周期自动记写日记', to: '/m/internshipdiary', key: 'writing', bg: 'linear-gradient(135deg,#0ea5e9,#0284c7)', icon: IC('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') },
  paperweight:{ name: '论文增重', desc: '提升论文重复率与引用率', to: '/m/paperweight', bg: 'linear-gradient(135deg,#f97316,#ea580c)', icon: IC('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>') },
  rewrite:   { name: '段落改写', desc: '扩写、缩写、综合文献改写', to: '/m/rewrite', bg: 'linear-gradient(135deg,#14b8a6,#0d9488)', icon: IC('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>') },
  createtitle:{ name: '题目生成', desc: '热门和前沿主题推荐', to: '/m/createtitle', bg: 'linear-gradient(135deg,#8b5cf6,#7c3aed)', icon: IC('<path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>') },
  createoutline:{ name: '大纲生成', desc: '生成专业学术大纲框架', to: '/m/createoutline', bg: 'linear-gradient(135deg,#06b6d4,#0891b2)', icon: IC('<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>') },
  wxlist:    { name: '参考文献获取', desc: '一键式快捷查找相关文献', to: '/m/wxlist', bg: 'linear-gradient(135deg,#0ea5e9,#0284c7)', icon: IC('<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>') },
  illustration:{ name: '段落配图', desc: '按内容语义快速精准配图', to: '/m/illustration', bg: 'linear-gradient(135deg,#ec4899,#db2777)', icon: IC('<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>') },
  createchart:{ name: '图表生成', desc: '按内容语义生成数据图表', to: '/m/createchart', bg: 'linear-gradient(135deg,#f59e0b,#d97706)', icon: IC('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>') },
}

const checkTools = computed(() => {
  const out = []
  for (const k of ['aigcreduceweight', 'aicheck']) {
    if (TOOLS[k].key && !site.featureEnabled(TOOLS[k].key)) continue
    out.push(TOOLS[k])
  }
  return out
})
const writeTools = computed(() => {
  const out = []
  for (const k of ['aippt', 'autodoc', 'proposal', 'task', 'internship', 'diary']) {
    if (TOOLS[k].key && !site.featureEnabled(TOOLS[k].key)) continue
    out.push(TOOLS[k])
  }
  return out
})
const miniTools = computed(() => {
  const out = []
  for (const k of ['createtitle', 'createoutline', 'createchart', 'illustration', 'rewrite', 'wxlist', 'paperweight']) {
    out.push(TOOLS[k])
  }
  return out
})
</script>

<style scoped>
.m-tools-hero {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 16px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.13), rgba(255, 255, 255, 0.94) 60%, rgba(14, 165, 233, 0.1));
  border: 1px solid rgba(20, 184, 166, 0.16);
  box-shadow: 0 8px 26px rgba(15, 23, 42, 0.05);
  text-decoration: none;
}
.m-tools-hero-orb {
  position: absolute;
  width: 130px;
  height: 130px;
  border-radius: 50%;
  right: -40px;
  top: -50px;
  background: rgba(20, 184, 166, 0.3);
  filter: blur(30px);
  pointer-events: none;
}
.m-tools-hero-body {
  position: relative;
  flex: 1;
  min-width: 0;
}
.m-tools-hero-title {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
}
.m-tools-hero-desc {
  margin-top: 3px;
  font-size: 12.5px;
  color: #64748b;
}
.m-tools-hero-arrow {
  position: relative;
  color: var(--primary-500, #14b8a6);
  flex-shrink: 0;
}

/* 中卡工具 */
.m-tool-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 14px;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  text-decoration: none;
  transition: transform 0.12s ease;
}
.m-tool-item:active {
  transform: scale(0.97);
  background: #f1f5f9;
}
.m-tool-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.m-tool-icon svg {
  width: 19px;
  height: 19px;
}
.m-tool-name {
  font-size: 14.5px;
  font-weight: 700;
  color: #0f172a;
}
.m-tool-desc {
  font-size: 11.5px;
  color: #94a3b8;
}
</style>
