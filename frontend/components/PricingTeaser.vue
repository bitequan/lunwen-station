<template>
  <section id="pricing" class="pricing">
    <div class="container">
      <div class="section-head">
        <h2 class="section-title">合作方案</h2>
        <p class="section-subtitle">按需计费、灵活代理、深度合作，零门槛开始变现</p>
      </div>

      <div class="pricing-grid">
        <div class="pricing-card">
          <div class="pricing-header">
            <h3>按需计费</h3>
            <p>适合个人或小型渠道</p>
          </div>
          <div class="pricing-price">
            <b>¥ 0</b>
            <span>起 / 单</span>
          </div>
          <ul class="pricing-list">
            <li>无开通费用</li>
            <li>按实际订单付费</li>
            <li>享受渠道底价</li>
            <li>订单实时追踪</li>
          </ul>
          <a href="#contact" class="btn btn-secondary" style="width: 100%;">立即咨询</a>
        </div>

        <div class="pricing-card popular">
          <div class="pricing-badge">最受欢迎</div>
          <div class="pricing-header">
            <h3>分站代理</h3>
            <p>适合有推广资源的渠道</p>
          </div>
          <div class="pricing-price">
            <b>免费</b>
            <span>开通分站</span>
          </div>
          <ul class="pricing-list">
            <li>独立域名分站</li>
            <li>自主定价与品牌</li>
            <li>用户独立体系</li>
            <li>收益自动结算</li>
          </ul>
          <a href="#contact" class="btn btn-primary" style="width: 100%;">免费开通</a>
        </div>

        <div class="pricing-card">
          <div class="pricing-header">
            <h3>深度合作</h3>
            <p>适合团队或平台方</p>
          </div>
          <div class="pricing-price">
            <b>定制</b>
            <span>方案</span>
          </div>
          <ul class="pricing-list">
            <li>批量价格优惠</li>
            <li>API 对接支持</li>
            <li>专属客服经理</li>
            <li>联合运营扶持</li>
          </ul>
          <a href="#contact" class="btn btn-secondary" style="width: 100%;">联系商务</a>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.pricing {
  padding: 72px 0;
  background: var(--white);
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  align-items: start;
}

.pricing-card {
  position: relative;
  padding: 36px;
  border-radius: var(--radius-xl);
  background: var(--white);
  border: 1px solid var(--gray-100);
  transition: all 0.25s ease;
}

.pricing-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 56px rgba(15, 23, 42, 0.1);
}

.pricing-card.popular {
  border-color: rgba(249, 115, 22, 0.25);
  box-shadow: 0 16px 48px rgba(249, 115, 22, 0.1);
}

.pricing-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  padding: 6px 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--accent-300), var(--accent-500));
  color: var(--white);
  font-size: 12px;
  font-weight: 800;
}

.pricing-header {
  margin-bottom: 24px;
}

.pricing-header h3 {
  font-size: 22px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 6px;
}

.pricing-header p {
  font-size: 14px;
  color: var(--gray-500);
}

.pricing-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 28px;
}

.pricing-price b {
  font-size: 40px;
  font-weight: 900;
  color: var(--dark-800);
}

.pricing-price span {
  font-size: 14px;
  color: var(--gray-500);
}

.pricing-list {
  list-style: none;
  margin-bottom: 32px;
}

.pricing-list li {
  position: relative;
  padding-left: 24px;
  margin-bottom: 12px;
  font-size: 14px;
  color: var(--gray-600);
}

.pricing-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--primary-600);
  font-weight: 800;
}

@media (max-width: 900px) {
  .pricing-grid {
    grid-template-columns: 1fr;
    max-width: 420px;
    margin: 0 auto;
  }
}
</style>
