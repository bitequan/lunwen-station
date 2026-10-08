<template>
  <div class="m-home">
    <!-- Hero：品牌 + 核心行动 -->
    <section class="m-hero">
      <div class="m-hero-orb m-hero-orb--1"></div>
      <div class="m-hero-orb m-hero-orb--2"></div>
      <h1 class="m-hero-title">{{ greeting }}，<br />今天要写点什么？</h1>
      <p class="m-hero-sub">AI 论文 / 降重 / 检测 / 排版，一站搞定</p>
      <div class="m-hero-actions">
        <NuxtLink to="/m/create" class="m-btn m-btn-primary m-hero-cta">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          开始创作
        </NuxtLink>
        <NuxtLink to="/m/tools" class="m-btn m-btn-plain m-hero-cta">全部工具</NuxtLink>
      </div>
    </section>

    <!-- 功能宫格 -->
    <div class="m-section-title">常用功能</div>
    <section class="m-card">
      <div class="m-grid">
        <NuxtLink v-for="f in gridFeatures" :key="f.key" :to="f.to" class="m-grid-item">
          <span class="m-grid-icon" :style="{ background: f.bg }" v-html="f.icon"></span>
          <span class="m-grid-label">{{ f.label }}</span>
        </NuxtLink>
      </div>
    </section>

    <!-- 公告 -->
    <template v-if="announcements.length">
      <div class="m-section-title">平台公告</div>
      <section class="m-card m-ann-card">
        <button v-for="a in announcements" :key="a.id" class="m-cell" @click="openAnnouncement(a)">
          <span class="m-ann-dot"></span>
          <span class="m-cell-body">
            <span class="m-cell-title m-ann-title">{{ a.title }}</span>
            <span class="m-cell-desc">{{ a.create_time }}</span>
          </span>
          <span class="m-cell-arrow">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
        </button>
      </section>
    </template>

    <!-- 公告详情弹层 -->
    <Transition name="m-fade">
      <div v-if="annDetail" class="m-sheet-mask" @click.self="annDetail = null">
        <div class="m-sheet">
          <div class="m-sheet-head">
            <span class="m-sheet-title">{{ annDetail.title }}</span>
            <button class="m-sheet-close" @click="annDetail = null">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="m-ann-content">{{ annDetail.content }}</div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '首页' })

const api = useApi()
const auth = useAuth()
const site = useSite()

const announcements = ref([])
const annDetail = ref(null)

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 6) return '夜深了'
  if (h < 11) return '早上好'
  if (h < 14) return '中午好'
  if (h < 18) return '下午好'
  return '晚上好'
})

const IC = (d) =>
  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

// 功能宫格：来自站点功能开关，全部落在 /m/ 层页面
const ALL_FEATURES = {
  create:        { label: 'AI论文',   to: '/m/create', bg: 'linear-gradient(135deg,#14b8a6,#0d9488)', icon: IC('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>') },
  aippt:         { label: 'AIPPT',   to: '/m/aippt',    bg: 'linear-gradient(135deg,#f97316,#ea580c)', icon: IC('<rect x="3" y="4" width="18" height="13" rx="2"/><line x1="12" y1="17" x2="12" y2="21"/><line x1="8" y1="21" x2="16" y2="21"/>') },
  ai_check:      { label: 'AI检测',  to: '/m/ai-check', bg: 'linear-gradient(135deg,#3b82f6,#2563eb)', icon: IC('<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>') },
  reduce_weight: { label: 'AI降重',  to: '/m/aigcreduceweight', bg: 'linear-gradient(135deg,#8b5cf6,#7c3aed)', icon: IC('<path d="M12 3v18"/><path d="M3 12h18"/>') },
  autodoc:       { label: '格式重排', to: '/m/autodoc',  bg: 'linear-gradient(135deg,#06b6d4,#0891b2)', icon: IC('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/>') },
  writing:       { label: '写作中心', to: '/m/writing',  bg: 'linear-gradient(135deg,#f59e0b,#d97706)', icon: IC('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>') },
  tools:         { label: '小工具',  to: '/m/tools',  bg: 'linear-gradient(135deg,#64748b,#475569)', icon: IC('<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>') },
  package_shop:  { label: '套餐商城', to: '/m/packages', bg: 'linear-gradient(135deg,#ec4899,#db2777)', icon: IC('<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>') },
  orders:        { label: '我的订单', to: '/m/orders', bg: 'linear-gradient(135deg,#22c55e,#16a34a)', icon: IC('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') },
  templates:     { label: '我的模板', to: '/pc/user?tab=templates&pc=1', bg: 'linear-gradient(135deg,#0ea5e9,#0284c7)', icon: IC('<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>') },
}

const gridFeatures = computed(() => {
  const list = []
  for (const key of Object.keys(ALL_FEATURES)) {
    if (!site.featureEnabled(key)) continue
    list.push({ key, ...ALL_FEATURES[key] })
  }
  return list
})

async function loadAnnouncements() {
  try {
    const res = await api.get('/api/announcement/lists', { page_no: 1, page_size: 3 })
    if (res.ok) announcements.value = Array.isArray(res.data?.list) ? res.data.list : []
  } catch (e) {}
}

async function openAnnouncement(a) {
  try {
    const res = await api.get('/api/announcement/detail', { id: a.id })
    if (res.ok && res.data) {
      annDetail.value = { title: res.data.title || a.title, content: res.data.content || '' }
    } else {
      annDetail.value = { title: a.title, content: '' }
    }
  } catch (e) {
    annDetail.value = { title: a.title, content: '' }
  }
}

onMounted(async () => {
  auth.restore()
  if (auth.isLoggedIn.value) auth.fetchUser()
  site.fetchSite()
  loadAnnouncements()
})
</script>

<style scoped>
.m-home {
  display: flex;
  flex-direction: column;
}

/* ---------- Hero ---------- */
.m-hero {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  padding: 24px 20px 22px;
  background: linear-gradient(140deg, rgba(20, 184, 166, 0.12) 0%, rgba(255, 255, 255, 0.9) 45%, rgba(249, 115, 22, 0.08) 100%);
  border: 1px solid rgba(20, 184, 166, 0.15);
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.05);
}
.m-hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(34px);
  opacity: 0.5;
  pointer-events: none;
}
.m-hero-orb--1 {
  width: 150px;
  height: 150px;
  right: -46px;
  top: -56px;
  background: rgba(20, 184, 166, 0.35);
}
.m-hero-orb--2 {
  width: 110px;
  height: 110px;
  left: -40px;
  bottom: -50px;
  background: rgba(249, 115, 22, 0.22);
}
.m-hero-title {
  position: relative;
  margin: 0 0 8px;
  font-size: 21px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.4;
}
.m-hero-sub {
  position: relative;
  margin: 0 0 18px;
  font-size: 13px;
  color: #64748b;
}
.m-hero-actions {
  position: relative;
  display: flex;
  gap: 10px;
}
.m-hero-cta {
  flex: 1;
  height: 44px;
  font-size: 14.5px;
}

/* ---------- 公告 ---------- */
.m-ann-card {
  padding: 4px 14px;
}
.m-ann-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--accent-500, #f97316));
  flex-shrink: 0;
}
.m-ann-title {
  font-weight: 500;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.m-ann-content {
  font-size: 14px;
  color: #334155;
  line-height: 1.8;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
