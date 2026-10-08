<template>
  <div class="tm-page">
    <div class="tm-wrap">
      <!-- 页头 -->
      <header class="tm-hero">
        <div class="tm-hero-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>
          </svg>
        </div>
        <div class="tm-hero-text">
          <h1 class="tm-hero-title">创建论文模板</h1>
          <p class="tm-hero-sub">请选择一种模板创建方式，两种方式创建好的模板都可用于「我的模板」与后续排版。</p>
        </div>
      </header>

      <!-- 模式卡片 -->
      <section class="tm-grid">
        <button class="tm-card tm-card-param" @click="goParam">
          <span class="tm-card-arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
          <div class="tm-card-top">
            <span class="tm-badge">参数式</span>
            <div class="tm-card-icon icon-param">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="13" y2="12"/><circle cx="16.5" cy="12" r="1.4"/><line x1="7" y1="16" x2="10" y2="16"/><circle cx="14" cy="16" r="1.4"/>
              </svg>
            </div>
          </div>
          <h3 class="tm-card-title">参数填写</h3>
          <p class="tm-card-desc">通过表单逐项设置封面、目录、标题、正文、页眉页脚、页边距等全部版式参数，适合对模板细节精确控制。</p>
          <ul class="tm-card-list">
            <li>逐项配置，精确到字体、字号、缩进</li>
            <li>封面、摘要、目录、正文全覆盖</li>
          </ul>
          <span class="tm-card-action">
            去参数填写
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
        </button>

        <button class="tm-card tm-card-fanwen" @click="goFanwen">
          <span class="tm-card-arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
          <div class="tm-card-top">
            <span class="tm-badge badge-fanwen">上传式</span>
            <div class="tm-card-icon icon-fanwen">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
              </svg>
            </div>
          </div>
          <h3 class="tm-card-title">上传范文文档</h3>
          <p class="tm-card-desc">上传一份排版规范的 Word 范文（.docx），系统自动提取其版式，一键生成拥有相同样式的模板，新稿可直接套用。</p>
          <ul class="tm-card-list">
            <li>仅需一份范文即可生成模板</li>
            <li>自动提取标题层级、正文、页边距等版式</li>
          </ul>
          <span class="tm-card-action">
            去上传范文
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
        </button>
      </section>
    </div>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

const router = useRouter()

function goParam() {
  router.push('/pc/template')
}

function goFanwen() {
  router.push('/pc/template-fanwen')
}
</script>

<style scoped>
.tm-page {
  min-height: 100vh;
  display: flex; align-items: flex-start; justify-content: center;
  padding: 40px 24px;
}
.tm-wrap { width: 860px; max-width: 100%; }

/* ===== 页头 ===== */
.tm-hero {
  display: flex; align-items: center; gap: 18px;
  background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
  border: 1px solid #e4e7ff; border-radius: 18px;
  padding: 24px 28px; margin-bottom: 22px;
}
.tm-hero-icon {
  width: 56px; height: 56px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border-radius: 15px; color: #fff;
  box-shadow: 0 8px 20px rgba(99,102,241,0.3);
}
.tm-hero-icon svg { width: 30px; height: 30px; }
.tm-hero-title { font-size: 21px; font-weight: 800; color: #1e1b4b; margin: 0 0 6px; }
.tm-hero-sub { font-size: 14px; color: #5a5791; margin: 0; line-height: 1.65; }

/* ===== 模式卡片 ===== */
.tm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.tm-card {
  position: relative; display: flex; flex-direction: column; text-align: left;
  padding: 26px 26px 24px; border-radius: 18px; cursor: pointer; font-family: inherit;
  background: #fff; border: 1.5px solid #e5e9f2;
  transition: all 0.2s;
}
.tm-card-param:hover { border-color: #6366f1; box-shadow: 0 14px 34px rgba(99,102,241,0.16); transform: translateY(-2px); }
.tm-card-fanwen:hover { border-color: #0891b2; box-shadow: 0 14px 34px rgba(8,145,178,0.16); transform: translateY(-2px); }
.tm-card-arrow {
  position: absolute; top: 18px; right: 18px;
  width: 28px; height: 28px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  background: #f1f5f9; color: #94a3b8; transition: all 0.2s;
}
.tm-card-arrow svg { width: 15px; height: 15px; }
.tm-card-param:hover .tm-card-arrow { background: #eef2ff; color: #6366f1; }
.tm-card-fanwen:hover .tm-card-arrow { background: #e0f4f8; color: #0891b2; }

.tm-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.tm-badge {
  font-size: 12px; font-weight: 700; letter-spacing: 0.4px;
  color: #6366f1; background: #eef2ff;
  padding: 5px 10px; border-radius: 8px;
}
.tm-badge.badge-fanwen { color: #0891b2; background: #e0f4f8; }
.tm-card-icon {
  width: 52px; height: 52px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center; color: #fff; margin-right: 40px;
}
.tm-card-icon svg { width: 26px; height: 26px; }
.icon-param { background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 18px rgba(99,102,241,0.3); }
.icon-fanwen { background: linear-gradient(135deg, #06b6d4, #0891b2); box-shadow: 0 8px 18px rgba(8,145,178,0.3); }

.tm-card-title { font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 7px; }
.tm-card-desc { font-size: 13px; color: #64748b; line-height: 1.7; margin: 0 0 14px; }
.tm-card-list { margin: 0 0 18px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px; }
.tm-card-list li {
  position: relative; padding-left: 15px;
  font-size: 12.5px; color: #475569; line-height: 1.5;
}
.tm-card-list li::before {
  content: ''; position: absolute; left: 0; top: 7px;
  width: 5px; height: 5px; border-radius: 50%;
}
.tm-card-param .tm-card-list li::before { background: #c7d2fe; }
.tm-card-fanwen .tm-card-list li::before { background: #99e0ec; }

.tm-card-action {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 14px; font-weight: 700; margin-top: auto;
}
.tm-card-action svg { width: 15px; height: 15px; transition: transform 0.2s; }
.tm-card-param .tm-card-action { color: #4f46e5; }
.tm-card-fanwen .tm-card-action { color: #0891b2; }
.tm-card:hover .tm-card-action svg { transform: translateX(4px); }

/* ===== 响应式 ===== */
@media (max-width: 680px) {
  .tm-page { padding: 20px 14px; }
  .tm-hero { padding: 20px; gap: 14px; }
  .tm-hero-icon { width: 46px; height: 46px; }
  .tm-hero-icon svg { width: 24px; height: 24px; }
  .tm-grid { grid-template-columns: 1fr; }
  .tm-card { padding: 20px; }
}
</style>