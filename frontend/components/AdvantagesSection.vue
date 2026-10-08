<template>
  <section id="advantages" class="advantages">
    <div class="container">
      <div class="section-head">
        <h2 class="section-title">为什么选择AI写作助手</h2>
        <p class="section-subtitle">源头价格、完整功能、灵活合作，打造渠道长期收益</p>
      </div>

      <div class="advantage-grid">
        <div class="advantage-card large">
          <div class="advantage-content">
            <span class="advantage-tag">价格优势</span>
            <h3>源头直供，利润空间更大</h3>
            <p>直连底层服务能力，去除中间环节，让代理以更低成本获取服务，自主定价空间充足。</p>
          </div>
          <div class="advantage-visual price">
            <div class="price-bar">
              <span>传统渠道</span>
              <div class="bar"><div class="fill" style="width: 80%"></div></div>
            </div>
            <div class="price-bar">
              <span>AI写作助手</span>
              <div class="bar"><div class="fill accent" style="width: 45%"></div></div>
            </div>
          </div>
        </div>

        <div class="advantage-card">
          <span class="advantage-tag">部署优势</span>
          <h3>免费开通独立分站</h3>
          <p>支持独立域名与品牌展示，用户自主下单，收益自动结算。</p>
        </div>

        <div class="advantage-card">
          <span class="advantage-tag">功能优势</span>
          <h3>全场景覆盖</h3>
          <p>论文、PPT、降重、查重、小工具一站式满足，提升用户复购率。</p>
        </div>

        <div class="advantage-card">
          <span class="advantage-tag">结算优势</span>
          <h3>收益实时透明</h3>
          <p>订单、收益、分站数据实时可查，资金明细清晰，结算稳定。</p>
        </div>

        <div class="advantage-card">
          <span class="advantage-tag">服务优势</span>
          <h3>7×24 技术支持</h3>
          <p>商务与技术团队全天候在线，协助代理快速上手与解决问题。</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.advantages {
  padding: 72px 0;
  background: var(--white);
}

.advantage-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.advantage-card {
  padding: 32px;
  border-radius: var(--radius-lg);
  background: var(--white);
  border: 1px solid var(--gray-100);
  transition: all 0.25s ease;
}

.advantage-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px rgba(15, 23, 42, 0.08);
}

.advantage-card.large {
  grid-column: span 2;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  align-items: center;
  background: linear-gradient(135deg, #f0fdfa 0%, #fff7ed 100%);
}

.advantage-tag {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-600);
  background: rgba(20, 184, 166, 0.1);
  margin-bottom: 14px;
}

.advantage-card h3 {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 12px;
}

.advantage-card p {
  font-size: 14px;
  color: var(--gray-500);
  line-height: 1.7;
}

.advantage-visual {
  padding: 24px;
  border-radius: var(--radius-md);
  background: var(--white);
  border: 1px solid var(--gray-100);
}

.price-bar {
  margin-bottom: 16px;
}

.price-bar span {
  display: block;
  font-size: 13px;
  color: var(--gray-500);
  margin-bottom: 6px;
}

.bar {
  height: 10px;
  border-radius: 999px;
  background: var(--gray-100);
  overflow: hidden;
}

.fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
}

.fill.accent {
  background: linear-gradient(90deg, var(--accent-300), var(--accent-500));
}

@media (max-width: 900px) {
  .advantage-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .advantage-card.large {
    grid-column: span 2;
  }
}

@media (max-width: 600px) {
  .advantage-grid {
    grid-template-columns: 1fr;
  }

  .advantage-card.large {
    grid-template-columns: 1fr;
    grid-column: span 1;
  }
}
</style>
