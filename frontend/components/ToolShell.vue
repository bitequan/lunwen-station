<template>
  <div class="tool-shell" :class="['tool-shell--' + theme, { 'tool-shell--wide': wide }]">
    <!-- 简洁头部（可通过 hideHeader 隐藏） -->
    <header v-if="!hideHeader" class="shell-head" :class="theme">
      <div class="shell-head-main">
        <div class="shell-icon-chip" :class="theme">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icon"></svg>
        </div>
        <div class="shell-head-text">
          <h1 class="shell-title">{{ name }}</h1>
          <p v-if="desc" class="shell-desc">{{ desc }}</p>
        </div>
      </div>
      <div v-if="$slots.actions" class="shell-head-actions">
        <slot name="actions" />
      </div>
    </header>

    <!-- 主体内容 -->
    <div class="shell-body">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  name: { type: String, required: true },
  desc: { type: String, default: '' },
  icon: { type: String, default: '' },
  theme: { type: String, default: 'teal' },
  wide: { type: Boolean, default: false },
  hideHeader: { type: Boolean, default: false },
})
</script>

<style scoped>
/* ============ 容器 ============ */
.tool-shell {
  position: relative;
  width: 100%;
  max-width: 1080px;
  margin: 0 auto;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  overflow: hidden;
}
.tool-shell--wide { max-width: none; }

/* ============ 头部 ============ */
.shell-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 28px;
  border-bottom: 1px solid var(--gray-100);
}

/* 主题背景色 - 非常淡 */
.shell-head.teal { background: #f0fdfa; }
.shell-head.blue { background: #eff6ff; }
.shell-head.orange { background: #fff7ed; }
.shell-head.cyan { background: #ecfeff; }
.shell-head.violet { background: #f5f3ff; }

.shell-head-main {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.shell-icon-chip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: #fff;
  flex-shrink: 0;
}

.shell-icon-chip.teal { background: #14b8a6; }
.shell-icon-chip.blue { background: #3b82f6; }
.shell-icon-chip.orange { background: #f97316; }
.shell-icon-chip.cyan { background: #06b6d4; }
.shell-icon-chip.violet { background: #7c3aed; }

.shell-head-text {
  min-width: 0;
}

.shell-title {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  line-height: 1.4;
  margin: 0 0 2px 0;
}

.shell-desc {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.shell-head-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* ============ 主体 ============ */
.shell-body {
  padding: 24px 28px;
}

/* ============ 响应式 ============ */
@media (max-width: 768px) {
  .shell-head {
    padding: 16px 18px;
  }
  .shell-icon-chip {
    width: 36px;
    height: 36px;
  }
  .shell-icon-chip svg { width: 18px; height: 18px; }
  .shell-title { font-size: 15px; }
  .shell-desc { font-size: 12px; }
  .shell-body { padding: 18px; }
}

@media (max-width: 480px) {
  .shell-head { flex-wrap: wrap; }
  .shell-head-main { gap: 10px; }
}

/* ≤640 紧凑屏：溶解白卡外壳，消除「console .main 12px + 卡体 18px」双层留白
   （内容距屏缘 12+4=16px）；标题行去淡色底与描边，正文白卡直接落在灰底上，
   与 /m 层「灰底 + 白卡」语言一致 */
@media (max-width: 640px) {
  .tool-shell { border: none; border-radius: 0; box-shadow: none; background: transparent; }
  /* 归零自身水平内边距：m 壳 .m-main 14px / console .main 12px 即最终边距，
     不再叠加 4px 让卡片两侧发闷（内容区舒张） */
  .shell-head { padding: 14px 2px 10px; border-bottom: none; }
  /* 价格签换行后与标题文字左缘对齐（icon-chip 36px + gap 10px），不再孤悬屏左 */
  .shell-head-actions { margin-left: 46px; }
  .shell-head.teal, .shell-head.blue, .shell-head.orange,
  .shell-head.cyan, .shell-head.violet { background: transparent; }
  .shell-body { padding: 0 0 24px; }
}
</style>
