<template>
  <section id="quality" class="quality">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow">范文质量</span>
        <h2 class="section-title">数据有根，配图有门</h2>
        <p class="section-subtitle">从文献检索到智能降重，每一处细节都有真实功能支撑，经得起查证</p>
      </div>

      <div class="quality-grid">
        <div v-for="(item, i) in qualities" :key="i" class="quality-item">
          <div class="quality-icon" :class="item.theme">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="item.icon"></svg>
          </div>
          <h3 class="quality-title">{{ item.title }}</h3>
          <p class="quality-desc">{{ item.desc }}</p>
          <div class="quality-stat">
            <b>{{ item.stat }}</b>
            <span>{{ item.statLabel }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const qualities = [
  {
    title: '文献可溯',
    desc: '内置在线文献检索，自动匹配参考文献并标注引用 [1][2]，符合 GB/T 7714 规范，可查看作者、期刊与年份。',
    stat: 'GB/T 7714',
    statLabel: '引用规范',
    theme: 'teal',
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
  },
  {
    title: '数据有据',
    desc: '正文自动生成图表、表格、公式、代码片段，每张图标注数据来源，支持同比环比可视化，拒绝凭空捏造。',
    stat: '4 类',
    statLabel: '可视化元素',
    theme: 'orange',
    icon: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'
  },
  {
    title: '配图有门',
    desc: '9 大模板配置全覆盖：封面、摘要、目录、标题、正文、页眉页脚、页边距、参考文献、章节换页，字号行距全可调。',
    stat: '9 大',
    statLabel: '配置维度',
    theme: 'cyan',
    icon: '<path d="M12 2L2 7l10 5 10-5-10-5z"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>'
  },
  {
    title: '格式合规',
    desc: '六大维度逐项校验：字体字号、标题层级、段落格式、页眉页脚、参考文献、图表编号，基础检测免费生成报告。',
    stat: '6 项',
    statLabel: '检测维度',
    theme: 'purple',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
  }
]
</script>

<style scoped>
.quality {
  padding: 72px 0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.section-eyebrow {
  display: inline-block;
  padding: 5px 14px;
  border-radius: 999px;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-bottom: 14px;
}

.quality-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
}

.quality-item {
  display: flex;
  flex-direction: column;
}

.quality-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: var(--radius-md);
  color: var(--white);
  margin-bottom: 22px;
}

.quality-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.quality-icon.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
.quality-icon.cyan { background: linear-gradient(135deg, #22d3ee, #06b6d4); }
.quality-icon.purple { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

.quality-title {
  font-size: 19px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 10px;
}

.quality-desc {
  font-size: 13px;
  color: var(--gray-500);
  line-height: 1.75;
  flex: 1;
  margin-bottom: 20px;
}

.quality-stat {
  display: flex;
  align-items: baseline;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid var(--gray-100);
}

.quality-stat b {
  font-size: 18px;
  font-weight: 900;
  color: var(--primary-600);
  letter-spacing: -0.01em;
}

.quality-stat span {
  font-size: 12px;
  color: var(--gray-500);
}

@media (max-width: 900px) {
  .quality-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .quality-grid {
    grid-template-columns: 1fr;
  }
}
</style>
