<template>
  <section id="how-it-works" class="how-it-works">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow">使用流程</span>
        <h2 class="section-title">四步完成，后付费无忧</h2>
        <p class="section-subtitle">从选服务到下载成果，全程透明，满意才扣费</p>
      </div>

      <div class="steps">
        <div v-for="(step, i) in steps" :key="i" class="step-node">
          <div class="step-marker">
            <div class="step-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="step.icon"></svg>
            </div>
            <span class="step-number">0{{ i + 1 }}</span>
          </div>
          <h3 class="step-title">{{ step.title }}</h3>
          <p class="step-desc">{{ step.desc }}</p>
          <div class="step-tags">
            <span v-for="(t, j) in step.tags" :key="j" class="step-tag">{{ t }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const steps = [
  {
    title: '选择服务',
    desc: '在论文写作、PPT 生成、格式重排三大服务中按需选择。',
    tags: ['论文', 'PPT', '排版'],
    icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'
  },
  {
    title: '配置参数',
    desc: '填写学历、学科、字数、模板与语言，可上传开题报告与参考资料投喂 AI。',
    tags: ['6 学历', '6 语言', 'AI 投喂', '1.7万+ 模板'],
    icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"/>'
  },
  {
    title: '流式生成',
    desc: 'SSE 实时生成大纲，章节可拖拽排序、在线检索文献，确认后生成正文与图表。',
    tags: ['SSE 流式', '拖拽编辑', '文献检索', '图表生成'],
    icon: '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'
  },
  {
    title: '支付下载',
    desc: '预览满意后再扣费，支持余额与充值，订单中心随时查看、下载与复检。',
    tags: ['后付费', '余额支付', '订单中心', '随时下载'],
    icon: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'
  }
]
</script>

<style scoped>
.how-it-works {
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
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-bottom: 14px;
}

.steps {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  position: relative;
}

.steps::before {
  content: '';
  position: absolute;
  top: 32px;
  left: calc(12.5% + 32px);
  right: calc(12.5% + 32px);
  height: 1px;
  background: var(--gray-200);
  z-index: 0;
}

.step-node {
  position: relative;
  z-index: 1;
  padding: 0 8px;
  text-align: center;
}

.step-marker {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.step-icon {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--white);
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
  border: 4px solid #f8fafc;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-node:hover .step-icon {
  transform: scale(1.05);
}

.step-number {
  margin-top: 10px;
  font-size: 12px;
  font-weight: 700;
  color: var(--gray-400);
  letter-spacing: 0.15em;
}

.step-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--dark-800);
  margin-bottom: 8px;
}

.step-desc {
  font-size: 13px;
  color: var(--gray-500);
  line-height: 1.7;
  margin-bottom: 16px;
}

.step-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  justify-content: center;
}

.step-tag {
  padding: 3px 9px;
  border-radius: 5px;
  font-size: 10.5px;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--white);
  border: 1px solid var(--gray-100);
}

@media (max-width: 900px) {
  .steps {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px 24px;
  }

  .steps::before {
    display: none;
  }
}

@media (max-width: 600px) {
  .steps {
    grid-template-columns: 1fr;
    gap: 28px;
  }
}
</style>
