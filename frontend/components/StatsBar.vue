<template>
  <section class="stats-bar">
    <div class="container stats-inner">
      <div v-for="(stat, i) in stats" :key="i" class="stat-item">
        <div class="stat-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="stat.icon"></svg>
        </div>
        <div class="stat-text">
          <b>{{ stat.value }}</b>
          <span>{{ stat.label }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const stats = [
  {
    value: '17,692+',
    label: '论文模板',
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'
  },
  {
    value: '7,075+',
    label: 'PPT 模板',
    icon: '<path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="M12 16v6"/><path d="M8 22h8"/>'
  },
  {
    value: '3,034',
    label: '覆盖高校',
    icon: '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>'
  },
  {
    value: '6 种',
    label: '学历 / 语言覆盖',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'
  }
]
</script>

<style scoped>
.stats-bar {
  padding: 32px 0;
  background: var(--white);
  border-top: 1px solid var(--gray-100);
  border-bottom: 1px solid var(--gray-100);
}

.stats-inner {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 16px;
  border-radius: var(--radius-md);
  transition: background 0.2s ease;
}

.stat-item:hover {
  background: var(--gray-50);
}

.stat-icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: var(--white);
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
  flex-shrink: 0;
}

.stat-text b {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: var(--dark-800);
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.stat-text span {
  display: block;
  font-size: 12.5px;
  color: var(--gray-500);
  margin-top: 2px;
}

@media (max-width: 768px) {
  .stats-inner {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .stat-item {
    padding: 12px;
  }

  .stat-text b {
    font-size: 20px;
  }
}
</style>
