<template>
  <div class="tools-center">
    <!-- 页面头部：标题 + 简介 + 统计 -->
    <header class="tools-hero">
      <div class="hero-main">
        <div class="hero-icon-chip">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <div class="hero-text">
          <h1 class="hero-title">小工具集合</h1>
          <p class="hero-desc">论文增重、段落改写、题目生成、大纲生成、参考文献获取、段落配图、图表生成等论文写作辅助工具，一站式提升写作效率。</p>
        </div>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="stat-num">{{ totalTools }}</span>
          <span class="stat-label">个工具</span>
        </div>
        <div class="hero-stat-divider"></div>
        <div class="hero-stat">
          <span class="stat-num">{{ toolGroups.length }}</span>
          <span class="stat-label">个分组</span>
        </div>
      </div>
    </header>

    <!-- 工具分组 -->
    <div class="tools-groups">
      <section v-for="(group, gi) in toolGroups" :key="gi" class="tool-group">
        <!-- 分组标题：小圆点 + 标题 -->
        <div class="group-head">
          <span class="group-dot" :class="group.theme"></span>
          <span class="group-title">{{ group.title }}</span>
          <span class="group-line"></span>
          <span class="group-count">{{ group.tools.length }} 个</span>
        </div>
        <div class="tool-cards-grid">
          <NuxtLink
            v-for="tool in group.tools"
            :key="tool.to"
            :to="tool.to"
            class="tool-card"
            :class="tool.theme"
          >
            <!-- 顶部图标区 -->
            <div class="tool-icon-area">
              <div class="tool-icon-chip">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="tool.icon"></svg>
              </div>
              <!-- 右上角箭头（hover 显现） -->
              <span class="tool-go">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </div>
            <!-- 底部文字区 -->
            <div class="tool-info">
              <span class="tool-name">{{ tool.name }}</span>
              <span class="tool-desc">{{ tool.desc }}</span>
            </div>
          </NuxtLink>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

useSeoMeta({
  title: '小工具 - AI写作助手',
  description: '论文增重、段落改写、题目生成、大纲生成、参考文献获取、段落配图、图表生成等论文写作辅助小工具集合。',
})

const toolGroups = [
  {
    title: '文本处理',
    theme: 'teal',
    tools: [
      {
        name: '论文增重',
        desc: '简洁增重，提升论文重复率与引用率',
        to: '/pc/tools/paperweight',
        theme: 'orange',
        icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
      },
      {
        name: '段落改写',
        desc: '扩写、缩写、综合文献内容改写等',
        to: '/pc/tools/rewrite',
        theme: 'cyan',
        icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
      },
    ],
  },
  {
    title: '内容生成',
    theme: 'orange',
    tools: [
      {
        name: '题目生成',
        desc: '基于大数据分析，提供热门和前沿主题推荐',
        to: '/pc/tools/createtitle',
        theme: 'orange',
        icon: '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>',
      },
      {
        name: '大纲生成',
        desc: '智能分析主题结构，生成专业学术大纲框架',
        to: '/pc/tools/createoutline',
        theme: 'teal',
        icon: '<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
      },
      {
        name: '参考文献获取',
        desc: '一键式快捷查找相关文献',
        to: '/pc/tools/wxlist',
        theme: 'cyan',
        icon: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
      },
    ],
  },
  {
    title: '可视化创作',
    theme: 'cyan',
    tools: [
      {
        name: '段落配图',
        desc: '自研高阶算法，快速精准提供配图',
        to: '/pc/tools/illustration',
        theme: 'teal',
        icon: '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
      },
      {
        name: '图表生成',
        desc: '根据提供的内容语义快速生成相关的数据图表',
        to: '/pc/tools/createchart',
        theme: 'orange',
        icon: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
      },
    ],
  },
]

const totalTools = computed(() => toolGroups.reduce((n, g) => n + g.tools.length, 0))
</script>

<style scoped>
.tools-center {
  color: var(--dark-800);
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-width: 1040px;
  margin: 0 auto;
}

/* ============ 页面头部 ============ */
.tools-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid var(--gray-100);
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.04), 0 2px 8px rgba(15, 23, 42, 0.02);
  position: relative;
  overflow: hidden;
}

.tools-hero::before {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.08) 0%, transparent 70%);
  pointer-events: none;
}

.hero-main {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
  position: relative;
  z-index: 1;
}

.hero-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: var(--white);
  box-shadow: 0 6px 16px rgba(13, 148, 136, 0.28);
  flex-shrink: 0;
}

.hero-text {
  min-width: 0;
}

.hero-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1.3;
  letter-spacing: -0.01em;
  margin-bottom: 3px;
}

.hero-desc {
  font-size: 12.5px;
  color: var(--gray-500);
  line-height: 1.5;
  margin: 0;
  max-width: 560px;
}

.hero-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 8px 16px;
  border-radius: 10px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  flex-shrink: 0;
  position: relative;
  z-index: 1;
}

.hero-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
}

.stat-num {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1;
  font-variant-numeric: tabular-nums;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.stat-label {
  font-size: 11px;
  color: var(--gray-400);
  font-weight: 500;
}

.hero-stat-divider {
  width: 1px;
  height: 24px;
  background: var(--gray-200);
}

/* ============ 分组 ============ */
.tools-groups {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.tool-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ============ 分组标题行 ============ */
.group-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 2px;
}

.group-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.group-dot.teal { background: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15); }
.group-dot.orange { background: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); }
.group-dot.cyan { background: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15); }
.group-dot.violet { background: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15); }

.group-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-900);
  flex-shrink: 0;
  letter-spacing: -0.005em;
}

.group-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, var(--gray-200) 0%, transparent 100%);
}

.group-count {
  font-size: 11.5px;
  font-weight: 500;
  color: var(--gray-400);
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}

/* ============ 卡片网格 ============ */
.tool-cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

/* ============ 竖向工具卡片 ============ */
.tool-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 14px 14px;
  border-radius: 12px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  text-decoration: none;
  color: inherit;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

/* hover：上浮 + 边框变色 + 阴影 */
.tool-card:hover {
  transform: translateY(-3px);
  border-color: transparent;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04);
}

/* hover 时用主题色光晕替代边框 */
.tool-card.teal:hover { box-shadow: 0 8px 24px rgba(20, 184, 166, 0.12), 0 0 0 1px rgba(20, 184, 166, 0.25), 0 2px 6px rgba(15, 23, 42, 0.04); }
.tool-card.orange:hover { box-shadow: 0 8px 24px rgba(249, 115, 22, 0.12), 0 0 0 1px rgba(249, 115, 22, 0.25), 0 2px 6px rgba(15, 23, 42, 0.04); }
.tool-card.cyan:hover { box-shadow: 0 8px 24px rgba(6, 182, 212, 0.12), 0 0 0 1px rgba(6, 182, 212, 0.25), 0 2px 6px rgba(15, 23, 42, 0.04); }

/* ============ 图标区 ============ */
.tool-icon-area {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 44px;
}

.tool-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  color: var(--white);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.tool-card.teal .tool-icon-chip {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.22);
}
.tool-card.orange .tool-icon-chip {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.22);
}
.tool-card.cyan .tool-icon-chip {
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  box-shadow: 0 4px 12px rgba(6, 182, 212, 0.22);
}

.tool-card:hover .tool-icon-chip {
  transform: translateY(-2px) scale(1.06);
}

.tool-card.teal:hover .tool-icon-chip { box-shadow: 0 8px 20px rgba(13, 148, 136, 0.35); }
.tool-card.orange:hover .tool-icon-chip { box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35); }
.tool-card.cyan:hover .tool-icon-chip { box-shadow: 0 8px 20px rgba(6, 182, 212, 0.35); }

/* 右上角箭头 */
.tool-go {
  position: absolute;
  top: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  color: var(--gray-300);
  opacity: 0;
  transform: translate(4px, -4px);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.tool-card:hover .tool-go {
  opacity: 1;
  transform: translate(0, 0);
}

.tool-card.teal:hover .tool-go { color: #14b8a6; background: rgba(20, 184, 166, 0.08); }
.tool-card.orange:hover .tool-go { color: #f97316; background: rgba(249, 115, 22, 0.08); }
.tool-card.cyan:hover .tool-go { color: #06b6d4; background: rgba(6, 182, 212, 0.08); }

/* violet 主题(AI降重) */
.tool-card.violet:hover { box-shadow: 0 8px 24px rgba(124, 58, 237, 0.12), 0 0 0 1px rgba(124, 58, 237, 0.25), 0 2px 6px rgba(15, 23, 42, 0.04); }
.tool-card.violet .tool-icon-chip {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.22);
}
.tool-card.violet:hover .tool-icon-chip { box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35); }
.tool-card.violet:hover .tool-go { color: #7c3aed; background: rgba(124, 58, 237, 0.08); }

/* ============ 文字区 ============ */
.tool-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  text-align: center;
}

.tool-name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--dark-900);
  line-height: 1.3;
  transition: color 0.2s ease;
}

.tool-card:hover .tool-name {
  color: var(--primary-600);
}

.tool-desc {
  font-size: 11.5px;
  color: var(--gray-400);
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* ============ 响应式 ============ */
@media (max-width: 1200px) {
  .tool-cards-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 900px) {
  .tools-hero { flex-direction: column; align-items: flex-start; gap: 14px; padding: 16px; }
  .hero-stats { align-self: stretch; }
  .tool-cards-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
  .tool-cards-grid { grid-template-columns: repeat(2, 1fr); }
  .hero-title { font-size: 16px; }
  .hero-desc { font-size: 12px; }
}
</style>
