<template>
  <section class="hero">
    <div class="hero-glow"></div>
    <div class="hero-orb orb-1"></div>
    <div class="hero-orb orb-2"></div>
    <div class="hero-watermark">
      <img :src="'/pc/logo.png'" alt="" />
    </div>

    <div class="container hero-inner">
      <div class="hero-content">
        <div class="hero-tag">
          <span class="tag-dot"></span>
          一手智能写作平台 · 范文可查证
        </div>
        <p class="hero-slogan">
          <span>数据有根</span>
          <i class="slogan-divider"></i>
          <span>配图有门</span>
        </p>
        <h1 class="hero-title">
          用文献说话，<span class="text-gradient">让数据发光</span>
        </h1>
        <p class="hero-desc">
          每一篇范文都标注真实文献出处，每一张图表都来自可追溯的数据源。
          AI写作助手让 AI 写作不再「凭空生成」，而是有据可查、有图可依、有格式可循。
        </p>
        <div class="hero-actions">
          <a href="#quality" class="btn btn-primary">查看范文质量</a>
          <a href="#features" class="btn btn-secondary">了解功能</a>
        </div>
        <div class="hero-tags">
          <span class="tag">6 种学历</span>
          <span class="tag">6 种语言</span>
          <span class="tag">流式大纲</span>
          <span class="tag">后付费</span>
        </div>
      </div>

      <div class="hero-visual">
        <div class="paper-card">
          <div class="paper-header">
            <div class="dots">
              <span></span><span></span><span></span>
            </div>
            <div class="paper-title">范文质量预览</div>
            <div class="paper-badge">已校验</div>
          </div>

          <div class="paper-body">
            <!-- 范文片段：带引用标注 -->
            <div class="paper-excerpt">
              <p class="excerpt-text">
                2023 年我国数字经济占 GDP 比重达 42.8%<sup class="cite">[1]</sup>，
                对经济增长的贡献率持续提升<sup class="cite">[2]</sup>，
                已成为产业转型升级的核心引擎<sup class="cite">[3]</sup>。
              </p>
              <div class="excerpt-source">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>本段引用 <b>3</b> 篇文献，均可点击查看来源</span>
              </div>
            </div>

            <!-- 精致图表 -->
            <div class="paper-chart">
              <div class="chart-head">
                <span class="chart-title">数字经济占 GDP 比重</span>
                <span class="chart-source">来源：国家统计局</span>
              </div>
              <div class="chart">
                <svg class="chart-svg" viewBox="0 0 380 88" preserveAspectRatio="xMidYMid meet">
                  <defs>
                    <linearGradient id="barGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#2dd4bf" />
                      <stop offset="100%" stop-color="#0d9488" />
                    </linearGradient>
                    <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="rgba(249,115,22,0.22)" />
                      <stop offset="100%" stop-color="rgba(249,115,22,0)" />
                    </linearGradient>
                  </defs>
                  <!-- 网格线（虚线） -->
                  <line x1="30" y1="10" x2="378" y2="10" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="2,3" />
                  <line x1="30" y1="38" x2="378" y2="38" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="2,3" />
                  <line x1="30" y1="66" x2="378" y2="66" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="2,3" />
                  <!-- Y 轴标签 -->
                  <text x="0" y="13" font-size="8" fill="#94a3b8">40%</text>
                  <text x="0" y="41" font-size="8" fill="#94a3b8">20%</text>
                  <text x="0" y="69" font-size="8" fill="#94a3b8">0%</text>
                  <!-- 折线下方面积 -->
                  <polygon points="44,22 124,39 204,21 284,37 364,42 364,66 44,66" fill="url(#areaGrad)" />
                  <!-- 柱状 -->
                  <rect v-for="(b, i) in bars" :key="'b'+i" :x="b.x" :y="b.y" width="20" :height="b.h" rx="3" fill="url(#barGrad)" />
                  <!-- 柱顶数值 -->
                  <text v-for="(b, i) in bars" :key="'t'+i" :x="b.x + 10" :y="b.y - 3" font-size="8" font-weight="700" fill="#0d9488" text-anchor="middle">{{ b.v }}%</text>
                  <!-- 折线 -->
                  <polyline points="44,22 124,39 204,21 284,37 364,42" fill="none" stroke="#f97316" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  <!-- 折线点 -->
                  <circle v-for="(p, i) in linePoints" :key="'p'+i" :cx="p[0]" :cy="p[1]" r="2.5" fill="#ffffff" stroke="#f97316" stroke-width="1.5" />
                  <!-- X 轴年份 -->
                  <text v-for="(y, i) in years" :key="'x'+i" :x="bars[i].x + 10" y="82" font-size="8" fill="#94a3b8" text-anchor="middle">{{ y }}</text>
                </svg>
              </div>
              <div class="chart-legend">
                <span><i class="legend-bar"></i>占比（%）</span>
                <span><i class="legend-line"></i>同比增速</span>
              </div>
            </div>
          </div>

          <div class="paper-footer">
            <div class="footer-stat">
              <b>12</b>
              <span>引用文献</span>
            </div>
            <div class="footer-stat">
              <b>8</b>
              <span>数据图表</span>
            </div>
            <div class="footer-stat">
              <b>100%</b>
              <span>来源可溯</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const years = ['2019', '2020', '2021', '2022', '2023']

// 柱状图：数字经济占 GDP 比重（viewBox 380x88）
const bars = [
  { x: 34,  y: 26, h: 40, v: '36.2' },
  { x: 114, y: 23, h: 43, v: '38.6' },
  { x: 194, y: 21, h: 45, v: '40.0' },
  { x: 274, y: 19, h: 47, v: '41.5' },
  { x: 354, y: 18, h: 48, v: '42.8' }
]

// 折线点：同比增速
const linePoints = [
  [44, 22], [124, 39], [204, 21], [284, 37], [364, 42]
]
</script>

<style scoped>
.hero {
  position: relative;
  padding: 96px 0 72px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  overflow: hidden;
}

.hero-glow {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 20% 30%, rgba(20, 184, 166, 0.1) 0%, transparent 45%),
    radial-gradient(circle at 80% 70%, rgba(249, 115, 22, 0.08) 0%, transparent 45%);
  pointer-events: none;
  z-index: 0;
}

.hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.45;
  pointer-events: none;
}

.orb-1 {
  width: 420px;
  height: 420px;
  background: rgba(20, 184, 166, 0.25);
  top: -120px;
  right: 10%;
}

.orb-2 {
  width: 360px;
  height: 360px;
  background: rgba(249, 115, 22, 0.18);
  bottom: -80px;
  left: 5%;
}

.hero-watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0.03;
  pointer-events: none;
}

.hero-watermark img {
  width: 480px;
  height: 480px;
}

.hero-inner {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 88px;
  align-items: center;
}

.hero-tag {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 14px;
  border-radius: 999px;
  background: rgba(20, 184, 166, 0.08);
  color: var(--primary-600);
  font-size: 13px;
  font-weight: 600;
  width: fit-content;
  margin-bottom: 36px;
}

.tag-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--primary-500);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.hero-title {
  font-size: 52px;
  font-weight: 900;
  line-height: 1.15;
  letter-spacing: -0.03em;
  color: var(--dark-900);
  margin-bottom: 22px;
}

.text-gradient {
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--accent-500) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-slogan {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 700;
  color: var(--primary-700);
  margin-bottom: 18px;
  letter-spacing: 0.06em;
  padding: 9px 20px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(249, 115, 22, 0.07) 100%);
  border-radius: 999px;
  width: fit-content;
  border: 1px solid rgba(20, 184, 166, 0.2);
}

.slogan-divider {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--accent-500);
  opacity: 0.7;
}

.hero-desc {
  font-size: 16px;
  color: var(--gray-600);
  line-height: 1.85;
  margin-bottom: 30px;
  max-width: 540px;
}

.hero-actions {
  display: flex;
  gap: 14px;
  margin-bottom: 26px;
}

.hero-tags {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.tag {
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--white);
  border: 1px solid var(--gray-200);
}

/* 范文质量预览卡 */
.hero-visual {
  display: flex;
  justify-content: flex-end;
  margin-right: -40px;
}

.paper-card {
  width: 100%;
  max-width: 520px;
  border-radius: var(--radius-xl);
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
  overflow: hidden;
  transition: box-shadow 0.4s ease, transform 0.4s ease;
}

.paper-card:hover {
  box-shadow: 0 20px 56px rgba(15, 23, 42, 0.12);
  transform: translateY(-2px);
}

.paper-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--gray-100);
}

.dots {
  display: flex;
  gap: 6px;
}

.dots span {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dots span:nth-child(1) { background: #ff5f57; }
.dots span:nth-child(2) { background: #febc2e; }
.dots span:nth-child(3) { background: #28c840; }

.paper-title {
  flex: 1;
  font-size: 13px;
  font-weight: 700;
  color: var(--dark-800);
}

.paper-badge {
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  color: var(--primary-600);
  background: rgba(20, 184, 166, 0.12);
}

.paper-body {
  padding: 16px;
}

/* 范文片段 */
.paper-excerpt {
  padding: 16px 18px;
  border-radius: var(--radius-md);
  background: #f8fafc;
  border: 1px solid var(--gray-100);
  margin-bottom: 18px;
}

.excerpt-text {
  font-size: 13.5px;
  line-height: 1.9;
  color: var(--dark-700);
  text-align: justify;
  text-indent: 2em;
  margin-bottom: 12px;
}

.cite {
  color: var(--primary-600);
  font-weight: 700;
  font-size: 11px;
  margin: 0 1px;
  cursor: pointer;
}

.excerpt-source {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: var(--gray-500);
}

.excerpt-source svg {
  color: var(--primary-600);
  flex-shrink: 0;
}

.excerpt-source b {
  color: var(--primary-600);
  margin: 0 2px;
}

/* 图表 */
.paper-chart {
  background: var(--white);
}

.chart-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 10px;
}

.chart-title {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--dark-800);
}

.chart-source {
  font-size: 11px;
  color: var(--gray-400);
}

.chart {
  position: relative;
  margin: 6px 0 2px;
}

.chart-svg {
  width: 100%;
  height: auto;
  display: block;
}

.chart-legend {
  display: flex;
  gap: 16px;
  margin-top: 6px;
  font-size: 11px;
  color: var(--gray-500);
}

.chart-legend span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.legend-bar {
  width: 10px;
  height: 8px;
  border-radius: 2px;
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
}

.legend-line {
  width: 10px;
  height: 2px;
  background: var(--accent-500);
  border-radius: 2px;
}

/* 底部统计 */
.paper-footer {
  display: flex;
  border-top: 1px solid var(--gray-100);
}

.footer-stat {
  flex: 1;
  padding: 14px 8px;
  text-align: center;
  border-right: 1px solid var(--gray-100);
}

.footer-stat:last-child {
  border-right: none;
}

.footer-stat b {
  display: block;
  font-size: 18px;
  font-weight: 900;
  color: var(--primary-600);
  margin-bottom: 2px;
}

.footer-stat span {
  font-size: 11px;
  color: var(--gray-500);
}

@media (max-width: 900px) {
  .hero {
    padding: 80px 0 56px;
  }

  .hero-inner {
    grid-template-columns: 1fr;
    gap: 40px;
    text-align: center;
  }

  .hero-title {
    font-size: 38px;
    text-align: center;
  }

  .hero-slogan {
    justify-content: center;
    margin-left: auto;
    margin-right: auto;
  }

  .hero-desc {
    margin: 0 auto 30px;
  }

  .hero-actions {
    justify-content: center;
  }

  .hero-tags {
    justify-content: center;
  }

  .hero-visual {
    margin-right: 0;
    justify-content: center;
  }

  .paper-card {
    max-width: 100%;
  }
}
</style>
