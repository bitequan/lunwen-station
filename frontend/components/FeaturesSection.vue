<template>
  <section id="features" class="features">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow">核心功能</span>
        <h2 class="section-title">一站式智能写作服务</h2>
        <p class="section-subtitle">从论文写作到智能降重，覆盖学术写作全链路，每个环节都有真实能力支撑</p>
      </div>

      <div class="features-grid">
        <div v-for="(feature, i) in features" :key="i" class="feature-card">
          <div class="feature-head">
            <div class="feature-icon" :class="feature.theme">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="feature.icon"></svg>
            </div>
            <h3 class="feature-title">{{ feature.title }}</h3>
          </div>
          <p class="feature-desc">{{ feature.desc }}</p>
          <div class="feature-tags">
            <span v-for="(t, j) in feature.tags" :key="j" class="feature-tag">{{ t }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const features = [
  {
    title: '论文写作',
    desc: '快速/完整双模式，支持 AI 投喂开题报告与参考资料，SSE 流式生成大纲，章节可拖拽排序、在线文献检索。',
    theme: 'teal',
    tags: ['6 种学历', '6 种语言', '流式大纲', 'AI 投喂'],
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
  },
  {
    title: 'PPT 生成',
    desc: '普通版 4 步、高级版 5 步（含配色主题），章节小节数自由配置，预览满意后才扣费下载。',
    theme: 'orange',
    tags: ['普通/高级版', '配色主题', '后付费', '模板筛选'],
    icon: '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'
  },
  {
    title: '格式重排',
    desc: '上传 .docx 自动套用高校模板，带水印预览免费、下载付费，支持自定义模板上传。',
    theme: 'cyan',
    tags: ['docx 解析', '水印预览', '自定义模板', '一键套用'],
    icon: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'
  },
  {
    title: '模板编辑器',
    desc: '9 大配置全覆盖：封面、摘要、目录、标题、正文、页眉页脚、页边距、参考文献、章节换页，字号行距全可调。',
    theme: 'teal',
    tags: ['9 大配置', '字体行距', '页码类型', '纸张尺寸'],
    icon: '<path d="M12 2L2 7l10 5 10-5-10-5z"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>'
  },
  {
    title: '智能降重',
    desc: '语义级改写，在保持原意的前提下有效降低重复率，适配主流查重系统，对接权威查重引擎生成报告。',
    theme: 'orange',
    tags: ['语义改写', '查重报告', '降重降率', '精准定位'],
    icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"/>'
  }
]
</script>

<style scoped>
.features {
  padding: 72px 0;
  background: #f8fafc;
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

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.feature-card {
  padding: 28px 24px;
  border-radius: var(--radius-md);
  background: transparent;
  border: 1px solid transparent;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
}

.feature-card:hover {
  background: var(--white);
  border-color: var(--gray-100);
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.feature-head {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
}

.feature-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: var(--radius-md);
  color: var(--white);
  flex-shrink: 0;
}

.feature-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.feature-icon.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
.feature-icon.cyan { background: linear-gradient(135deg, #22d3ee, #06b6d4); }
.feature-icon.purple { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

.feature-title {
  font-size: 19px;
  font-weight: 800;
  color: var(--dark-800);
}

.feature-desc {
  font-size: 13.5px;
  color: var(--gray-500);
  line-height: 1.75;
  margin-bottom: 18px;
  flex: 1;
}

.feature-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.feature-tag {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
}

@media (max-width: 900px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
