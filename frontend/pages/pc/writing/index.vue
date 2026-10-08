<template>
  <div class="writing-center">
    <!-- 页面头部：标题 + 简介 + 统计 -->
    <header class="wc-hero">
      <div class="hero-main">
        <div class="hero-icon-chip">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div class="hero-text">
          <h1 class="hero-title">写作中心</h1>
          <p class="hero-desc">开题报告、任务书、实习报告、实习日志等常用文书，填写信息即可智能生成大纲、选择模板一键成稿。</p>
        </div>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="stat-num">{{ tools.length }}</span>
          <span class="stat-label">种文书</span>
        </div>
        <div class="hero-stat-divider"></div>
        <div class="hero-stat">
          <span class="stat-num">AI</span>
          <span class="stat-label">智能生成</span>
        </div>
      </div>
    </header>

    <!-- 写作工具卡片 -->
    <div class="wc-grid">
      <NuxtLink
        v-for="tool in tools"
        :key="tool.to"
        :to="tool.to"
        class="wc-card"
        :class="tool.theme"
      >
        <!-- 顶部图标区 -->
        <div class="wc-icon-area">
          <div class="wc-icon-chip">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="tool.icon"></svg>
          </div>
          <!-- 右上角箭头（hover 显现） -->
          <span class="wc-go">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
          </span>
        </div>
        <!-- 底部文字区 -->
        <div class="wc-info">
          <span class="wc-name">{{ tool.name }}</span>
          <span class="wc-desc">{{ tool.desc }}</span>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

useSeoMeta({
  title: '写作中心 - AI写作助手',
  description: '开题报告、任务书、实习报告、实习日志等常用文书智能生成，填写信息一键成稿。',
})

const tools = [
  {
    name: '开题报告',
    desc: '智能生成开题报告大纲，选择模板一键成稿',
    to: '/pc/writing/proposal',
    theme: 'teal',
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
  },
  {
    name: '任务书',
    desc: '智能生成任务书大纲，选择模板一键成稿',
    to: '/pc/writing/task',
    theme: 'violet',
    icon: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
  },
  {
    name: '实习报告',
    desc: '填写实习信息，选择模板一键生成实习报告',
    to: '/pc/writing/internship',
    theme: 'orange',
    icon: '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
  },
  {
    name: '实习日志',
    desc: '填写日志信息，一键生成实习日志内容',
    to: '/pc/writing/internshipdiary',
    theme: 'cyan',
    icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
  },
]
</script>

<style scoped>
.writing-center {
  color: var(--dark-800);
  display: flex;
  flex-direction: column;
  gap: 22px;
  max-width: 1040px;
  margin: 0 auto;
}

/* ============ 页面头部 ============ */
.wc-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 22px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid var(--gray-100);
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.04), 0 2px 8px rgba(15, 23, 42, 0.02);
  position: relative;
  overflow: hidden;
}

.wc-hero::before {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(124, 58, 237, 0.08) 0%, transparent 70%);
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
  width: 48px;
  height: 48px;
  border-radius: 13px;
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: var(--white);
  box-shadow: 0 6px 16px rgba(124, 58, 237, 0.28);
  flex-shrink: 0;
}

.hero-text {
  min-width: 0;
}

.hero-title {
  font-size: 19px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1.3;
  letter-spacing: -0.01em;
  margin-bottom: 4px;
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
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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

/* ============ 卡片网格 ============ */
.wc-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.wc-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 20px 16px 16px;
  border-radius: 13px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  text-decoration: none;
  color: inherit;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.wc-card:hover {
  transform: translateY(-4px);
  border-color: transparent;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04);
}

.wc-card.teal:hover { box-shadow: 0 10px 26px rgba(20, 184, 166, 0.14), 0 0 0 1px rgba(20, 184, 166, 0.28), 0 2px 6px rgba(15, 23, 42, 0.04); }
.wc-card.orange:hover { box-shadow: 0 10px 26px rgba(249, 115, 22, 0.14), 0 0 0 1px rgba(249, 115, 22, 0.28), 0 2px 6px rgba(15, 23, 42, 0.04); }
.wc-card.cyan:hover { box-shadow: 0 10px 26px rgba(6, 182, 212, 0.14), 0 0 0 1px rgba(6, 182, 212, 0.28), 0 2px 6px rgba(15, 23, 42, 0.04); }
.wc-card.violet:hover { box-shadow: 0 10px 26px rgba(124, 58, 237, 0.14), 0 0 0 1px rgba(124, 58, 237, 0.28), 0 2px 6px rgba(15, 23, 42, 0.04); }

/* ============ 图标区 ============ */
.wc-icon-area {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 52px;
}

.wc-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border-radius: 14px;
  color: var(--white);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.wc-card.teal .wc-icon-chip {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 5px 14px rgba(13, 148, 136, 0.24);
}
.wc-card.orange .wc-icon-chip {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 5px 14px rgba(249, 115, 22, 0.24);
}
.wc-card.cyan .wc-icon-chip {
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  box-shadow: 0 5px 14px rgba(6, 182, 212, 0.24);
}
.wc-card.violet .wc-icon-chip {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  box-shadow: 0 5px 14px rgba(124, 58, 237, 0.24);
}

.wc-card:hover .wc-icon-chip {
  transform: translateY(-2px) scale(1.06);
}

.wc-card.teal:hover .wc-icon-chip { box-shadow: 0 9px 22px rgba(13, 148, 136, 0.36); }
.wc-card.orange:hover .wc-icon-chip { box-shadow: 0 9px 22px rgba(249, 115, 22, 0.36); }
.wc-card.cyan:hover .wc-icon-chip { box-shadow: 0 9px 22px rgba(6, 182, 212, 0.36); }
.wc-card.violet:hover .wc-icon-chip { box-shadow: 0 9px 22px rgba(124, 58, 237, 0.36); }

/* 右上角箭头 */
.wc-go {
  position: absolute;
  top: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 7px;
  color: var(--gray-300);
  opacity: 0;
  transform: translate(4px, -4px);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.wc-card:hover .wc-go {
  opacity: 1;
  transform: translate(0, 0);
}

.wc-card.teal:hover .wc-go { color: #14b8a6; background: rgba(20, 184, 166, 0.08); }
.wc-card.orange:hover .wc-go { color: #f97316; background: rgba(249, 115, 22, 0.08); }
.wc-card.cyan:hover .wc-go { color: #06b6d4; background: rgba(6, 182, 212, 0.08); }
.wc-card.violet:hover .wc-go { color: #7c3aed; background: rgba(124, 58, 237, 0.08); }

/* ============ 文字区 ============ */
.wc-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: center;
}

.wc-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--dark-900);
  line-height: 1.3;
  transition: color 0.2s ease;
}

.wc-card:hover .wc-name {
  color: var(--primary-600);
}

.wc-desc {
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
  .wc-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 900px) {
  .wc-hero { flex-direction: column; align-items: flex-start; gap: 14px; padding: 16px; }
  .hero-stats { align-self: stretch; }
}

@media (max-width: 600px) {
  .wc-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .wc-card { padding: 16px 12px 13px; gap: 12px; }
  .hero-title { font-size: 17px; }
  .hero-desc { font-size: 12px; }
}
</style>
