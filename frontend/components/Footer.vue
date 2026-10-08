<template>
  <footer id="contact" class="footer">
    <div class="container footer-inner">
      <div class="footer-main">
        <div class="footer-brand">
          <img :src="'/pc/logo.png'" alt="AI写作助手" class="footer-logo" />
          <span class="footer-name">AI写作助手</span>
          <p class="footer-slogan">一手智能写作平台，让渠道轻松变现</p>
        </div>
        <div class="footer-links">
          <div class="footer-col">
            <h4>产品</h4>
            <a href="#features">功能介绍</a>
            <a href="#templates">模板库</a>
            <a href="#pricing">合作方案</a>
          </div>
          <div class="footer-col">
            <h4>合作</h4>
            <a href="#">分站代理</a>
            <a href="#">网站直购</a>
            <a href="#">收益结算</a>
          </div>
          <div class="footer-col">
            <h4>支持</h4>
            <a href="#">帮助中心</a>
            <a href="#">联系我们</a>
            <NuxtLink to="/pc/user">个人中心</NuxtLink>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2024–2026 郑州比特泉网络科技有限公司 版权所有</p>
        <p>
          <a href="https://beian.miit.gov.cn" target="_blank" rel="noopener">豫ICP备2024046993号-4</a>
        </p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  padding: 80px 0 0;
  background: var(--dark-900);
  color: var(--white);
}

.footer-main {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 60px;
  padding-bottom: 60px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-logo {
  width: 48px;
  height: 48px;
}

.footer-name {
  font-size: 22px;
  font-weight: 800;
}

.footer-slogan {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.55);
  max-width: 240px;
}

.footer-links {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
}

.footer-col {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-col h4 {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 6px;
}

.footer-col a {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.55);
  transition: color 0.2s ease;
}

.footer-col a:hover {
  color: var(--white);
}

.footer-bottom {
  padding: 28px 0;
  text-align: center;
  color: rgba(255, 255, 255, 0.45);
  font-size: 13px;
  line-height: 1.8;
}

.footer-bottom a {
  color: rgba(255, 255, 255, 0.45);
}

.footer-bottom a:hover {
  color: var(--primary-300);
}

@media (max-width: 768px) {
  .footer-main {
    grid-template-columns: 1fr;
    gap: 36px;
    padding-bottom: 32px;
  }

  .footer-links {
    grid-template-columns: repeat(2, 1fr);
    gap: 28px 18px;
  }
}

@media (max-width: 480px) {
  .footer-links {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}
</style>
