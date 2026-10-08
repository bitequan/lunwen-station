<template>
  <section id="integration" class="integration">
    <div class="container">
      <div class="section-head">
        <h2 class="section-title">灵活的合作方式</h2>
        <p class="section-subtitle">网站直购、分站代理、深度合作，总有一种方式适合你</p>
      </div>

      <div class="cooperation-grid">
        <div class="coop-card">
          <div class="coop-icon teal">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <h3>独立分站部署</h3>
          <p>免费开通独立域名分站，自定义品牌与价格，拥有专属用户体系与收益后台。</p>
        </div>

        <div class="coop-card featured">
          <div class="coop-icon orange">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          </div>
          <h3>网站直接下单</h3>
          <p>无需技术开发，用户在主站或分站直接选购服务，订单自动处理，收益实时到账。</p>
        </div>

        <div class="coop-card">
          <div class="coop-icon cyan">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3>多级分销体系</h3>
          <p>发展下级代理，享受持续分润；支持 API 对接，满足技术团队的深度集成需求。</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.integration {
  padding: 72px 0;
  background: #f8fafc;
}

.cooperation-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.coop-card {
  position: relative;
  padding: 36px;
  border-radius: var(--radius-lg);
  background: var(--white);
  border: 1px solid var(--gray-100);
  transition: all 0.25s ease;
}

.coop-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 56px rgba(15, 23, 42, 0.1);
}

.coop-card.featured {
  border-color: rgba(249, 115, 22, 0.25);
  background: linear-gradient(180deg, #fff7ed 0%, var(--white) 100%);
}

.coop-card.featured::before {
  content: '推荐';
  position: absolute;
  top: 0;
  right: 24px;
  padding: 4px 12px;
  border-radius: 0 0 8px 8px;
  background: var(--accent-500);
  color: var(--white);
  font-size: 12px;
  font-weight: 700;
}

.coop-icon {
  width: 60px;
  height: 60px;
  display: grid;
  place-items: center;
  border-radius: var(--radius-md);
  color: var(--white);
  margin-bottom: 24px;
}

.coop-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.coop-icon.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
.coop-icon.cyan { background: linear-gradient(135deg, #22d3ee, #06b6d4); }

.coop-card h3 {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 12px;
}

.coop-card p {
  font-size: 14px;
  color: var(--gray-500);
  line-height: 1.7;
}

@media (max-width: 900px) {
  .cooperation-grid {
    grid-template-columns: 1fr;
  }
}
</style>
