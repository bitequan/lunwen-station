<template>
  <header class="navbar" :class="{ scrolled: isScrolled }">
    <div class="nav-bg-layer"></div>
    <div class="nav-glow nav-glow-left"></div>
    <div class="nav-glow nav-glow-right"></div>

    <div class="container nav-inner">
      <NuxtLink to="/pc" class="nav-brand">
        <div class="nav-logo-wrap">
          <img :src="'/pc/logo.png'" alt="AI写作助手" class="nav-logo" />
          <div class="nav-logo-glow"></div>
        </div>
        <span class="nav-name">AI写作助手</span>
      </NuxtLink>

      <nav class="nav-links">
        <div class="nav-item">
          <NuxtLink to="/pc" class="nav-link">
            <span class="nav-link-text">首页</span>
          </NuxtLink>
        </div>

        <div
          v-for="item in menuItems"
          :key="item.id"
          class="nav-item"
          @mouseenter="openMenu(item.id)"
          @mouseleave="closeMenu"
        >
          <a
            href="#"
            class="nav-link"
            :class="{ active: activeMenu === item.id }"
            @click.prevent="scrollToSection(item.id)"
          >
            <span class="nav-link-text">{{ item.label }}</span>
            <svg v-if="item.children" class="nav-chevron" :class="{ rotated: activeMenu === item.id }" width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>

          <!-- 二级菜单 -->
          <transition name="dropdown">
            <div v-if="item.children && activeMenu === item.id" class="nav-dropdown">
              <div class="dropdown-arrow"></div>
              <div class="dropdown-inner">
                <a
                  v-for="child in item.children"
                  :key="child.id"
                  href="#"
                  class="dropdown-item"
                  @click.prevent="scrollToSection(child.id)"
                >
                  <div class="dropdown-item-icon">
                    <component :is="child.icon" />
                  </div>
                  <div class="dropdown-item-body">
                    <div class="dropdown-item-title">{{ child.label }}</div>
                    <div class="dropdown-item-desc">{{ child.desc }}</div>
                  </div>
                </a>
              </div>
            </div>
          </transition>
        </div>

        </nav>

      <div class="nav-actions">
        <NuxtLink v-if="mounted && isLoggedIn" to="/pc/user" class="btn btn-primary nav-order-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M3 7l9-4 9 4-9 4-9-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M3 7v6l9 4 9-4V7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
          </svg>
          进入工作台
        </NuxtLink>

        <button v-if="mounted && !isLoggedIn" class="btn btn-primary nav-login" @click="$emit('login')">
          登录
        </button>
      </div>
    </div>

    <div class="nav-bottom-line"></div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, h } from 'vue'

defineEmits(['login'])

const isScrolled = ref(false)
const mounted = ref(false)
const activeMenu = ref(null)

const auth = useAuth()
const router = useRouter()
const route = useRoute()

// 图标渲染函数
const iconMonitor = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>' })
const iconBolt = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' })
const iconChart = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 14l3-3 3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' })
const iconGrid = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" stroke-width="2"/><line x1="9" y1="21" x2="9" y2="9" stroke="currentColor" stroke-width="2"/>' })
const iconGift = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<rect x="3" y="8" width="18" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="M5 12v9h14v-9" stroke="currentColor" stroke-width="2"/><path d="M12 8v13" stroke="currentColor" stroke-width="2"/><path d="M12 8S10 2 7 3s2 5 5 5zM12 8s2-6 5-5-2 5-5 5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' })
const iconLayers = () => h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', innerHTML: '<path d="M12 2l10 6-10 6L2 8l10-6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M2 16l10 6 10-6M2 12l10 6 10-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' })

// 带二级菜单的导航数据
const menuItems = [
  {
    id: 'features',
    label: '功能介绍',
    children: [
      { id: 'features', label: '全部功能', desc: '一站式智能写作平台', icon: iconGrid },
      { id: 'feature-ai', label: 'AI 写作', desc: '智能论文与公文生成', icon: iconBolt },
      { id: 'feature-ppt', label: 'PPT 生成', desc: '一键生成专业演示文稿', icon: iconMonitor },
    ]
  },
  {
    id: 'how-it-works',
    label: '使用流程',
    children: [
      { id: 'how-it-works', label: '操作指南', desc: '三步开始使用', icon: iconChart },
      { id: 'how-deploy', label: '部署流程', desc: '快速搭建分站', icon: iconLayers },
    ]
  },
  {
    id: 'advantages',
    label: '核心优势',
  },
  {
    id: 'integration',
    label: '合作方式',
    children: [
      { id: 'integration', label: '渠道代理', desc: '低门槛高收益', icon: iconGift },
      { id: 'integration-oem', label: '独立分站', desc: '白标定制部署', icon: iconLayers },
    ]
  },
  {
    id: 'templates',
    label: '模板库',
  },
  {
    id: 'pricing',
    label: '合作方案',
  },
]

const openMenu = (id) => {
  const item = menuItems.find(m => m.id === id)
  if (item && item.children) {
    activeMenu.value = id
  }
}

const closeMenu = () => {
  activeMenu.value = null
}

const scrollToSection = async (id) => {
  activeMenu.value = null
  const scroll = () => {
    const el = document.getElementById(id)
    if (el) {
      const top = el.getBoundingClientRect().top + window.scrollY - 72
      window.scrollTo({ top, behavior: 'smooth' })
    }
  }
  if (route.path === '/' || route.path === '/pc/' || route.path === '/pc') {
    scroll()
  } else {
    await router.push('/pc')
    await nextTick()
    setTimeout(scroll, 120)
  }
}

const isLoggedIn = computed(() => auth.isLoggedIn.value)

onMounted(() => {
  auth.restore()
  mounted.value = true
  window.addEventListener('scroll', onScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})

const onScroll = () => {
  isScrolled.value = window.scrollY > 20
}
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  height: 72px;
}

/* 背景层：渐变毛玻璃 */
.nav-bg-layer {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.88) 0%, rgba(248, 250, 252, 0.82) 100%);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  transition: all 0.3s ease;
}

.navbar.scrolled .nav-bg-layer {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 250, 252, 0.94) 100%);
  border-bottom-color: rgba(203, 213, 225, 0.7);
  box-shadow: 0 8px 32px rgba(15, 23, 42, 0.06), 0 1px 0 rgba(15, 23, 42, 0.02);
}

/* 光晕装饰 */
.nav-glow {
  position: absolute;
  top: 0;
  width: 320px;
  height: 72px;
  filter: blur(40px);
  pointer-events: none;
  opacity: 0.5;
  transition: opacity 0.3s ease;
}

.nav-glow-left {
  left: -60px;
  background: radial-gradient(ellipse at center, rgba(20, 184, 166, 0.18) 0%, transparent 70%);
}

.nav-glow-right {
  right: -60px;
  background: radial-gradient(ellipse at center, rgba(249, 115, 22, 0.14) 0%, transparent 70%);
}

.navbar.scrolled .nav-glow {
  opacity: 0.3;
}

/* 底部渐变线 */
.nav-bottom-line {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(20, 184, 166, 0.15) 20%,
    rgba(249, 115, 22, 0.15) 80%,
    transparent 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.navbar.scrolled .nav-bottom-line {
  opacity: 1;
}

.nav-inner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  z-index: 1;
}

/* 品牌区 */
.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 800;
  font-size: 20px;
  color: var(--dark-800);
  transition: transform 0.2s ease;
}

.nav-brand:hover {
  transform: scale(1.02);
}

.nav-logo-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-logo {
  width: 38px;
  height: 38px;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 2px 8px rgba(20, 184, 166, 0.2));
}

.nav-logo-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.25) 0%, transparent 70%);
  filter: blur(6px);
}

.nav-name {
  background: linear-gradient(135deg, var(--dark-900) 0%, var(--dark-700) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 导航链接区 */
.nav-links {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 2px;
  height: 72px;
  font-size: 14px;
  font-weight: 500;
  color: var(--gray-600);
  white-space: nowrap;
}

.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  height: 44px;
  flex-shrink: 0;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  border-radius: 10px;
  color: var(--gray-600);
  font-weight: 500;
  cursor: pointer;
  position: relative;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.nav-link-text {
  position: relative;
  z-index: 1;
}

/* 悬停背景 pill */
.nav-link::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.06) 0%, rgba(20, 184, 166, 0.02) 100%);
  opacity: 0;
  transform: scale(0.9);
  transition: all 0.2s ease;
}

/* 悬停底部指示线 */
.nav-link::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 2px;
  border-radius: 2px;
  background: linear-gradient(90deg, var(--primary-500), var(--accent-500));
  transition: width 0.25s ease;
}

.nav-link:hover {
  color: var(--primary-600);
}

.nav-link:hover::before {
  opacity: 1;
  transform: scale(1);
}

.nav-link:hover::after {
  width: 60%;
}

.nav-link.active {
  color: var(--primary-600);
}

.nav-link.active::before {
  opacity: 1;
  transform: scale(1);
}

/* 下拉箭头 */
.nav-chevron {
  transition: transform 0.2s ease;
  opacity: 0.6;
}

.nav-chevron.rotated {
  transform: rotate(180deg);
}

/* 二级下拉菜单 */
.nav-dropdown {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-top: 8px;
  z-index: 100;
}

.dropdown-arrow {
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
  width: 12px;
  height: 12px;
  background: var(--white);
  border-top: 1px solid var(--gray-100);
  border-left: 1px solid var(--gray-100);
  z-index: 0;
}

.dropdown-inner {
  position: relative;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: var(--radius-lg);
  box-shadow:
    0 20px 60px rgba(15, 23, 42, 0.1),
    0 4px 12px rgba(15, 23, 42, 0.04);
  padding: 8px;
  min-width: 280px;
  overflow: hidden;
}

.dropdown-inner::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--primary-500), var(--accent-500));
  opacity: 0.6;
}

.dropdown-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  transition: all 0.15s ease;
}

.dropdown-item:hover {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.05) 0%, rgba(249, 115, 22, 0.03) 100%);
}

.dropdown-item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(20, 184, 166, 0.04) 100%);
  color: var(--primary-600);
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.dropdown-item:hover .dropdown-item-icon {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: var(--white);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
  transform: scale(1.05);
}

.dropdown-item-body {
  flex: 1;
  min-width: 0;
}

.dropdown-item-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  margin-bottom: 2px;
}

.dropdown-item-desc {
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.4;
}

/* 下拉动画 */
.dropdown-enter-active {
  transition: all 0.2s ease;
}

.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}

/* 操作按钮区 */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-login {
  padding: 10px 24px;
  font-size: 14px;
}

.nav-order-btn {
  padding: 10px 18px;
  font-size: 14px;
  gap: 7px;
  justify-content: center;
}

@media (max-width: 768px) {
  .nav-links {
    display: none;
  }

  .nav-order-btn {
    min-width: auto;
  }

  .nav-order-btn svg {
    display: none;
  }
}
</style>
