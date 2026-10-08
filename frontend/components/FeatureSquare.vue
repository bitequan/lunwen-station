<template>
  <div class="feature-square">
    <!-- 顶部品牌区 -->
    <header class="fs-header">
      <div class="fs-bg-layer"></div>
      <div class="fs-glow fs-glow-a"></div>
      <div class="fs-glow fs-glow-b"></div>
      <div class="fs-container">
        <div class="fs-brand">
          <NuxtLink to="/pc" class="fs-brand-link">
            <span v-if="logo" class="fs-logo">
              <img :src="logo" :alt="siteName" />
            </span>
            <span v-else class="fs-logo">
              <img :src="'/pc/logo.png'" alt="AI写作助手" />
            </span>
            <span class="fs-brand-name">{{ siteName || 'AI写作助手' }}</span>
          </NuxtLink>
          <button v-if="mounted && !isLoggedIn" class="fs-btn fs-btn-primary" @click="goLogin('/pc/user')">
            登录 / 注册
          </button>
          <NuxtLink v-else to="/pc/user" class="fs-btn fs-btn-primary">
            进入工作台
          </NuxtLink>
        </div>

        <div class="fs-hero">
          <h1 class="fs-title">{{ siteName || 'AI写作助手' }} · 智能写作平台</h1>
          <p class="fs-subtitle">论文写作 · PPT生成 · 智能降重 · 格式重排，一站式智能写作服务</p>
        </div>
      </div>
    </header>

    <!-- 功能广场 -->
    <main class="fs-main">
      <div class="fs-container">
        <div class="fs-section-head">
          <h2 class="fs-section-title">功能广场</h2>
          <span class="fs-section-desc">选择所需功能，快速开始</span>
        </div>

        <div class="fs-grid">
          <button
            v-for="f in features"
            :key="f.key"
            class="fs-card"
            @click="goFeature(f)"
          >
            <span class="fs-card-icon" v-html="f.icon"></span>
            <span class="fs-card-body">
              <span class="fs-card-name">{{ f.label }}</span>
              <span class="fs-card-desc">{{ f.desc }}</span>
            </span>
            <span class="fs-card-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </span>
          </button>
        </div>

        <div v-if="features.length === 0" class="fs-empty">
          暂未开放任何功能，请稍后再来
        </div>
      </div>
    </main>

    <!-- 底部 -->
    <footer class="fs-footer">
      <div class="fs-container fs-footer-inner">
        <span>{{ siteName || 'AI写作助手' }} 智能写作平台</span>
        <span class="fs-footer-divider"></span>
        <NuxtLink to="/pc/user" class="fs-footer-link">进入工作台</NuxtLink>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  // 需要展示的功能列表：{ key, route, label }
  features: { type: Array, default: () => [] }
})

const auth = useAuth()
const site = useSite()
const router = useRouter()

const mounted = ref(false)
const isLoggedIn = computed(() => auth.isLoggedIn.value)
const siteName = computed(() => site.siteName.value)
const logo = computed(() => site.logo.value)

onMounted(() => {
  mounted.value = true
})

// 每个功能卡片的图标与描述
const FEATURE_META = {
  create:        { icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>', desc: '智能生成论文初稿' },
  writing:       { icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>', desc: '各类文体写作中心' },
  autodoc:       { icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M8 17h4"/><path d="M9 11V7l3 2-3 2z"/>', desc: '一键格式重排规范' },
  aippt:         { icon: '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>', desc: '一键生成专业演示文稿' },
  reduce_weight: { icon: '<path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/><path d="M8 12h8"/><path d="M12 8v8"/>', desc: 'AI 智能降重改写' },
  tools:         { icon: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', desc: '创意标题·图表·插图' },
  orders:        { icon: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>', desc: '订单中心·进度查询' },
  templates:     { icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>', desc: '我的论文模板管理' },
}

const features = computed(() => {
  return props.features
    .filter(f => FEATURE_META[f.key])
    .map(f => ({ ...f, icon: FEATURE_META[f.key].icon, desc: FEATURE_META[f.key].desc }))
})

function goFeature(f) {
  const target = f.route || '/pc/user'
  if (isLoggedIn.value) {
    router.push(target)
  } else {
    // 未登录 → 打开全局登录弹窗，登录成功后再带回跳目标
    openLoginWithRedirect(target)
  }
}

function openLoginWithRedirect(target) {
  const dest = target.startsWith('/pc/') || target.startsWith('http')
    ? target
    : '/pc' + (target.startsWith('/') ? target : '/' + target)
  useLoginModal().open(() => router.push(dest))
}

function goLogin(redirect) {
  openLoginWithRedirect(redirect)
}
</script>

<style scoped>
.feature-square {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

.fs-container {
  width: 100%;
  max-width: 1080px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ============ 顶部 ============ */
.fs-header {
  position: relative;
  overflow: hidden;
  background: linear-gradient(160deg, #0f172a 0%, #134e4a 100%);
  padding: 20px 0 72px;
}

.fs-bg-layer {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 25% 20%, rgba(20, 184, 166, 0.25) 0%, transparent 55%),
    radial-gradient(circle at 80% 90%, rgba(249, 115, 22, 0.18) 0%, transparent 55%);
  pointer-events: none;
}

.fs-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  pointer-events: none;
}
.fs-glow-a { width: 280px; height: 280px; background: rgba(20, 184, 166, 0.28); top: -120px; left: -80px; }
.fs-glow-b { width: 240px; height: 240px; background: rgba(249, 115, 22, 0.2); bottom: -100px; right: -60px; }

.fs-brand {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.fs-brand-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.fs-logo {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(20, 184, 166, 0.4);
}
.fs-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fs-brand-name {
  font-size: 16px;
  font-weight: 800;
  color: #f1f5f9;
  letter-spacing: -0.01em;
}

.fs-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 18px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}
.fs-btn-primary {
  color: #0f172a;
  background: linear-gradient(135deg, var(--primary-300, #5eead4) 0%, var(--primary-500, #14b8a6) 100%);
  box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3);
}
.fs-btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(20, 184, 166, 0.42);
}

.fs-hero {
  position: relative;
  text-align: center;
  padding-top: 48px;
}
.fs-title {
  margin: 0 0 12px;
  font-size: 32px;
  font-weight: 900;
  color: #ffffff;
  letter-spacing: -0.01em;
  line-height: 1.3;
}
.fs-subtitle {
  margin: 0;
  font-size: 15px;
  color: #cbd5e1;
  letter-spacing: 0.02em;
}

/* ============ 功能广场 ============ */
.fs-main {
  flex: 1;
  padding: 28px 0 56px;
}

.fs-section-head {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 20px;
}
.fs-section-title {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.01em;
}
.fs-section-desc {
  font-size: 13px;
  color: #94a3b8;
}

.fs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.fs-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px;
  border-radius: 14px;
  background: #ffffff;
  border: 1px solid #eef2f6;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  cursor: pointer;
  text-align: left;
  transition: all 0.22s ease;
}
.fs-card:hover {
  transform: translateY(-3px);
  border-color: rgba(20, 184, 166, 0.35);
  box-shadow: 0 10px 26px rgba(20, 184, 166, 0.14);
}

.fs-card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary-300, #5eead4) 0%, var(--primary-500, #14b8a6) 100%);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.28);
}
.fs-card-icon :deep(svg) {
  width: 22px;
  height: 22px;
}

.fs-card-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.fs-card-name {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.fs-card-desc {
  font-size: 12.5px;
  color: #94a3b8;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.fs-card-arrow {
  color: #cbd5e1;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.fs-card:hover .fs-card-arrow {
  color: var(--primary-500, #14b8a6);
  transform: translateX(3px);
}

.fs-empty {
  text-align: center;
  padding: 60px 0;
  color: #94a3b8;
  font-size: 14px;
}

/* ============ 底部 ============ */
.fs-footer {
  border-top: 1px solid #eef2f6;
  background: #ffffff;
}
.fs-footer-inner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 24px;
  font-size: 12.5px;
  color: #94a3b8;
}
.fs-footer-divider {
  width: 1px;
  height: 14px;
  background: #e2e8f0;
}
.fs-footer-link {
  color: var(--primary-600, #0d9488);
  text-decoration: none;
  font-weight: 600;
}
.fs-footer-link:hover {
  text-decoration: underline;
}

@media (max-width: 900px) {
  .fs-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .fs-title {
    font-size: 24px;
  }
}
@media (max-width: 560px) {
  .fs-grid {
    grid-template-columns: 1fr;
  }
}
</style>
