<template>
  <section id="templates" class="template-library">
    <div class="container">
      <!-- 顶部真实数据统计 -->
      <div class="tpl-stats">
        <div v-for="(s, i) in stats" :key="i" class="tpl-stat">
          <b :class="s.theme">{{ s.value }}</b>
          <span>{{ s.label }}</span>
        </div>
      </div>

      <div class="section-head">
        <span class="section-eyebrow">模板库</span>
        <h2 class="section-title">海量模板，覆盖全学科全场景</h2>
        <p class="section-subtitle">论文模板按高校与学历精准匹配，PPT 模板按 9 大场景分类，支持自建私有模板</p>
      </div>

      <!-- 三分类胶囊 tab -->
      <div class="tpl-tabs">
        <button
          v-for="(tab, i) in tabs"
          :key="i"
          class="tpl-tab"
          :class="{ active: activeTab === i }"
          @click="activeTab = i"
        >
          <span class="tpl-tab-name">{{ tab.name }}</span>
          <span v-if="tab.count" class="tpl-tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <!-- 公共模板：marquee 滚动 -->
      <div v-show="activeTab === 0" class="tpl-panel">
        <div class="marquee-wrap">
          <div class="marquee-row row-1">
            <div class="marquee-track">
              <div v-for="t in row1" :key="t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
              <div v-for="t in row1" :key="'dup-' + t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
            </div>
          </div>
          <div class="marquee-row row-2">
            <div class="marquee-track reverse">
              <div v-for="t in row2" :key="t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
              <div v-for="t in row2" :key="'dup-' + t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
            </div>
          </div>
          <div class="marquee-row row-3">
            <div class="marquee-track">
              <div v-for="t in row3" :key="t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
              <div v-for="t in row3" :key="'dup-' + t.id" class="template-item">
                <img :src="t.logo" :alt="t.name" loading="lazy" />
                <span>{{ t.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 我的收藏 -->
      <div v-show="activeTab === 1" class="tpl-panel tpl-empty">
        <div class="empty-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <h4>收藏喜欢的模板</h4>
        <p>登录后在模板库中点击收藏，常用高校模板一键调用</p>
      </div>

      <!-- 私有模板：突出自建能力 -->
      <div v-show="activeTab === 2" class="tpl-panel tpl-private">
        <div class="private-card">
          <div class="private-visual">
            <div class="private-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            </div>
            <span class="private-badge">支持共享</span>
          </div>
          <div class="private-content">
            <h3>自建私有模板</h3>
            <p>不仅限于系统公共模板，支持上传自有高校模板，配置 9 大格式维度（封面、摘要、目录、标题、正文、页眉页脚、页边距、参考文献、章节换页），并可分享给下级或分站使用。</p>
            <div class="private-tags">
              <span class="private-tag">9 大配置</span>
              <span class="private-tag">字体行距</span>
              <span class="private-tag">页码类型</span>
              <span class="private-tag">纸张尺寸</span>
              <span class="private-tag">权限可控</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import templates from '~/data/templates.json'

const allTemplates = ref(templates)
const activeTab = ref(0)

const tabs = [
  { name: '公共模板', count: '17,692+' },
  { name: '我的收藏', count: '' },
  { name: '私有模板', count: '自建' }
]

const stats = [
  { value: '17,692+', label: '论文模板', theme: 'teal' },
  { value: '7,075+', label: 'PPT 模板', theme: 'orange' },
  { value: '3,034', label: '覆盖高校', theme: 'cyan' },
  { value: '9 大', label: 'PPT 场景分类', theme: 'purple' }
]

const row1 = computed(() => allTemplates.value.filter((_, i) => i % 3 === 0))
const row2 = computed(() => allTemplates.value.filter((_, i) => i % 3 === 1))
const row3 = computed(() => allTemplates.value.filter((_, i) => i % 3 === 2))
</script>

<style scoped>
.template-library {
  padding: 72px 0;
  background: #f8fafc;
  overflow: hidden;
}

/* 顶部统计 */
.tpl-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 56px;
  padding: 24px;
  border-radius: var(--radius-lg);
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}

.tpl-stat {
  text-align: center;
  padding: 8px 4px;
  border-right: 1px solid var(--gray-100);
}

.tpl-stat:last-child {
  border-right: none;
}

.tpl-stat b {
  display: block;
  font-size: 28px;
  font-weight: 900;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.tpl-stat b.teal { color: var(--primary-600); }
.tpl-stat b.orange { color: var(--accent-500); }
.tpl-stat b.cyan { color: #06b6d4; }
.tpl-stat b.purple { color: #8b5cf6; }

.tpl-stat span {
  display: block;
  font-size: 13px;
  color: var(--gray-500);
  margin-top: 4px;
}

/* section head */
.section-eyebrow {
  display: inline-block;
  padding: 5px 14px;
  border-radius: 999px;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-bottom: 14px;
}

/* 三分类 tab */
.tpl-tabs {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 40px;
  padding: 6px;
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: 999px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.tpl-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  background: transparent;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.25s ease;
}

.tpl-tab:hover {
  color: var(--dark-800);
}

.tpl-tab.active {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: var(--white);
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
}

.tpl-tab-count {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  background: var(--gray-100);
  color: var(--gray-600);
}

.tpl-tab.active .tpl-tab-count {
  background: rgba(255, 255, 255, 0.25);
  color: var(--white);
}

/* 面板 */
.tpl-panel {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* marquee */
.marquee-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
  mask-image: linear-gradient(90deg, transparent, black 8%, black 92%, transparent);
  -webkit-mask-image: linear-gradient(90deg, transparent, black 8%, black 92%, transparent);
}

.marquee-row {
  overflow: hidden;
}

.marquee-track {
  display: flex;
  gap: 16px;
  width: fit-content;
  animation: marquee 110s linear infinite;
}

.marquee-track.reverse {
  animation-direction: reverse;
}

.row-2 .marquee-track {
  animation-duration: 130s;
}

.row-3 .marquee-track {
  animation-duration: 100s;
}

@keyframes marquee {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

.template-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 20px;
  border-radius: 999px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
  white-space: nowrap;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.template-item:hover {
  border-color: var(--primary-300);
  box-shadow: 0 4px 16px rgba(20, 184, 166, 0.12);
  transform: translateY(-2px);
}

.template-item img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  border-radius: 50%;
  flex-shrink: 0;
  background: var(--gray-50);
}

.template-item span {
  font-size: 15px;
  font-weight: 600;
  color: var(--dark-800);
}

/* 空状态 */
.tpl-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  text-align: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--gray-50);
  color: var(--gray-300);
  margin-bottom: 20px;
}

.tpl-empty h4 {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 8px;
}

.tpl-empty p {
  font-size: 14px;
  color: var(--gray-500);
}

/* 私有模板卡 */
.tpl-private {
  display: flex;
  justify-content: center;
}

.private-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 32px;
  align-items: center;
  max-width: 880px;
  width: 100%;
  padding: 36px;
  border-radius: var(--radius-xl);
  background: linear-gradient(135deg, #f0fdfa 0%, #fff7ed 100%);
  border: 1px solid rgba(20, 184, 166, 0.2);
}

.private-visual {
  position: relative;
  display: grid;
  place-items: center;
}

.private-icon {
  width: 72px;
  height: 72px;
  display: grid;
  place-items: center;
  border-radius: var(--radius-lg);
  background: var(--white);
  color: var(--primary-600);
  box-shadow: 0 8px 24px rgba(20, 184, 166, 0.15);
}

.private-badge {
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  color: var(--white);
  background: var(--accent-500);
  white-space: nowrap;
}

.private-content h3 {
  font-size: 22px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 10px;
}

.private-content p {
  font-size: 14px;
  color: var(--gray-600);
  line-height: 1.8;
  margin-bottom: 16px;
}

.private-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.private-tag {
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--primary-600);
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(20, 184, 166, 0.2);
}

@media (max-width: 900px) {
  .tpl-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .tpl-stat {
    border-right: none;
    border-bottom: 1px solid var(--gray-100);
    padding-bottom: 12px;
  }

  .tpl-stat:nth-last-child(-n+2) {
    border-bottom: none;
  }

  .tpl-stat b {
    font-size: 22px;
  }

  .tpl-tabs {
    width: 100%;
    justify-content: stretch;
  }

  .tpl-tab {
    flex: 1;
    justify-content: center;
    padding: 10px 12px;
    font-size: 13px;
  }

  .private-card {
    grid-template-columns: 1fr;
    text-align: center;
    padding: 28px;
  }

  .private-visual {
    justify-self: center;
  }

  .private-tags {
    justify-content: center;
  }

  .marquee-track {
    animation-duration: 85s;
  }

  .row-2 .marquee-track,
  .row-3 .marquee-track {
    animation-duration: 100s;
  }
}
</style>
