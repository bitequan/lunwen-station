<template>
  <ToolShell
    name="图表生成"
    desc="根据提供的内容语义，快速生成相关的数据图表"
    theme="orange"
    :wide="true"
    class="cc-fill"
    icon='<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'
  >
    <!-- H5 双 Tab 切换条：仅 m 层 ≤640 显示（基类 display:none，PC/桌面窄窗口恒隐藏） -->
    <div class="cc-m-tabs">
      <button :class="{ active: mPane === 'config' }" @click="mPane = 'config'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
        图表配置
      </button>
      <button :class="{ active: mPane === 'result' }" @click="mPane = 'result'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        生成结果
      </button>
    </div>
    <div class="cc-layout">
      <!-- 左：图表类型 -->
      <aside class="cc-types" :class="{ 'cc-m-off': mPane !== 'config' }">
        <div class="cc-types-head">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <span>图表类型</span>
          <span class="cc-types-count">{{ totalTypes }}</span>
        </div>
        <div class="cc-types-scroll">
          <div v-for="g in chartGroups" :key="g.title" class="cc-type-group">
            <h3 class="cc-group-title">{{ g.title }}</h3>
            <div class="cc-type-list">
              <button
                v-for="t in g.items"
                :key="t.type"
                class="cc-type-btn"
                :class="{ active: selectedType === t.type }"
                @click="selectedType = t.type"
              >
                <span class="cc-type-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="t.icon"></svg></span>
                <span class="cc-type-name">{{ t.name }}</span>
                <span class="cc-type-check" v-if="selectedType === t.type">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- 右：预览 + Tab + 配置 -->
      <div class="cc-main" :class="{ 'cc-m-off': mPane !== 'result' }">
        <!-- 预览 -->
        <section class="cc-panel cc-preview">
          <div class="panel-head">
            <span class="panel-dot orange"></span>
            <h2 class="panel-title">图表预览</h2>
            <span v-if="currentTypeName" class="cc-meta-tag">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              {{ currentTypeName }}
            </span>
            <div class="panel-head-actions">
              <button v-if="chartImg" class="cc-head-btn" @click="downloadChart">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                下载
              </button>
            </div>
          </div>
          <div class="cc-preview-box">
            <transition name="cc-fade" mode="out-in">
              <div v-if="loading" key="loading" class="cc-preview-loading">
                <div class="cc-loading-orb">
                  <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="20" stroke="rgba(249,115,22,0.12)" stroke-width="3"/>
                    <circle class="cc-orb-arc" cx="24" cy="24" r="20" stroke="url(#cc-grad)" stroke-width="3" stroke-linecap="round" stroke-dasharray="94 126"/>
                    <defs>
                      <linearGradient id="cc-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#fb923c"/>
                        <stop offset="100%" stop-color="#f97316"/>
                      </linearGradient>
                    </defs>
                  </svg>
                </div>
                <span class="cc-loading-text">正在生成图表，请稍候...</span>
                <span class="cc-loading-sub">{{ loadingStep }}</span>
              </div>
              <div v-else-if="chartImg" key="img" class="cc-preview-img-wrap">
                <img :src="chartImg" alt="生成的图表" class="cc-preview-img" />
                <div class="cc-preview-info">
                  <span class="cc-info-chip"><span class="cc-info-dot"></span>{{ currentTypeName }}</span>
                  <span class="cc-info-chip">{{ chartSize }}</span>
                  <span class="cc-info-chip">{{ generatedAt }}</span>
                </div>
              </div>
              <div v-else key="empty" class="cc-preview-empty">
                <div class="cc-empty-illu">
                  <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
                </div>
                <span class="cc-empty-title">选择图表类型并输入数据</span>
                <span class="cc-empty-sub">支持 16 种图表类型，可叠加文献与联网数据</span>
              </div>
            </transition>
          </div>
        </section>

        <!-- Tab 区：说明 / 文献 / 联网数据 -->
        <section class="cc-panel cc-tabs-panel">
          <div class="cc-tabs-head">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              class="cc-tab"
              :class="{ active: activeTab === tab.key, 'has-badge': tab.count > 0 }"
              @click="activeTab = tab.key"
            >
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" v-html="tab.icon"></svg>
              <span>{{ tab.label }}</span>
              <span v-if="tab.count > 0" class="cc-tab-badge">{{ tab.count }}</span>
            </button>
          </div>
          <div class="cc-tabs-body">
            <!-- 说明 -->
            <div v-show="activeTab === 'desc'" class="cc-tab-pane">
              <button v-if="chartDesc" class="cc-copy-desc" :class="{ copied: descCopied }" @click="copyDesc">
                <svg v-if="!descCopied" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ descCopied ? '已复制' : '复制内容' }}
              </button>
              <div class="cc-desc-text" :class="{ empty: !chartDesc }">{{ chartDesc || '暂无图表说明，点击「生成图表」后此处将展示图表的解读与分析。' }}</div>
            </div>

            <!-- 参考文献 -->
            <div v-show="activeTab === 'ref'" class="cc-tab-pane">
              <div v-if="!wenxianlist.length" class="cc-empty-inline">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                <span>暂无参考文献数据</span>
                <span class="cc-empty-hint">开启「启用文献引用」后生成图表即可查看</span>
              </div>
              <template v-else>
                <div class="cc-list-toolbar">
                  <span class="cc-list-count">共 {{ wenxianlist.length }} 篇文献</span>
                  <div class="cc-list-actions">
                    <button class="cc-tool-btn" @click="copyAllRef">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                      {{ allCopied ? '已复制全部' : '复制全部' }}
                    </button>
                  </div>
                </div>
                <ul class="cc-link-list">
                  <li v-for="(item, i) in wenxianlist" :key="i" class="cc-link-item">
                    <div class="cc-link-index">{{ i + 1 }}</div>
                    <div class="cc-link-body">
                      <a v-if="item.url" :href="item.url" target="_blank" rel="noopener noreferrer" class="cc-link-title">
                        {{ item.title }}
                        <svg class="cc-external" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      </a>
                      <span v-else class="cc-link-title">{{ item.title }}</span>
                      <div v-if="item.source || item.authors || item.year" class="cc-link-meta">
                        <span class="cc-meta-source"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>{{ item.source }}</span>
                        <span class="cc-meta-sep">·</span>
                        <span class="cc-meta-authors">{{ item.authors }}</span>
                        <span class="cc-meta-sep">·</span>
                        <span class="cc-meta-year">{{ item.year }}</span>
                      </div>
                      <p v-if="item.snippet" class="cc-link-snippet">{{ item.snippet }}</p>
                      <div class="cc-link-foot">
                        <span v-if="item.url" class="cc-link-url" :title="item.url">{{ item.url }}</span>
                        <div class="cc-link-ops">
                          <button class="cc-op-btn" :class="{ copied: copiedIdx === i }" @click="copyOneRef(i)">
                            <svg v-if="copiedIdx !== i" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ copiedIdx === i ? '已复制' : '复制' }}
                          </button>
                          <a v-if="item.url" :href="item.url" target="_blank" rel="noopener noreferrer" class="cc-op-btn primary">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            查看原文
                          </a>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </template>
            </div>

            <!-- 联网数据 -->
            <div v-show="activeTab === 'online'" class="cc-tab-pane">
              <div v-if="!onlineList.length" class="cc-empty-inline">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span>暂无联网数据</span>
                <span class="cc-empty-hint">开启「启用联网数据」后生成图表即可查看</span>
              </div>
              <template v-else>
                <div class="cc-list-toolbar">
                  <span class="cc-list-count">共 {{ onlineList.length }} 条数据源</span>
                  <div class="cc-list-actions">
                    <button class="cc-tool-btn" @click="copyAllOnline">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                      {{ onlineCopied ? '已复制全部' : '复制全部' }}
                    </button>
                  </div>
                </div>
                <ul class="cc-link-list online">
                  <li v-for="(item, i) in onlineList" :key="i" class="cc-link-item">
                    <div class="cc-link-index online">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <div class="cc-link-body">
                      <a :href="item.url" target="_blank" rel="noopener noreferrer" class="cc-link-title">
                        {{ item.title }}
                        <svg class="cc-external" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      </a>
                      <div class="cc-link-meta">
                        <span class="cc-meta-source online"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>{{ item.source }}</span>
                        <span class="cc-meta-sep">·</span>
                        <span class="cc-meta-domain">{{ item.domain }}</span>
                        <span class="cc-meta-sep">·</span>
                        <span class="cc-meta-year">检索于 {{ item.retrievedAt }}</span>
                      </div>
                      <p class="cc-link-snippet">{{ item.snippet }}</p>
                      <div class="cc-link-foot">
                        <span class="cc-link-url" :title="item.url">{{ item.url }}</span>
                        <div class="cc-link-ops">
                          <button class="cc-op-btn" :class="{ copied: onlineCopiedIdx === i }" @click="copyOneOnline(i)">
                            <svg v-if="onlineCopiedIdx !== i" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ onlineCopiedIdx === i ? '已复制' : '复制' }}
                          </button>
                          <a :href="item.url" target="_blank" rel="noopener noreferrer" class="cc-op-btn primary">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            查看原文
                          </a>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </template>
            </div>
          </div>
        </section>
      </div>

      <!-- 右：数据配置 -->
      <section class="cc-panel cc-config" :class="{ 'cc-m-off': mPane !== 'config' }">
          <div class="panel-head">
            <span class="panel-dot orange"></span>
            <h2 class="panel-title">数据配置</h2>
            <span class="cc-config-tip"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>文献与联网只能选择其一</span>
          </div>
          <textarea
            v-model="dataInput"
            class="cc-textarea"
            placeholder="请输入数据或相关文档段落，例如：&#10;2018-2024 年各季度销售额：Q1 120, Q2 185, Q3 240, Q4 310..."
          ></textarea>

          <!-- 开关选项 -->
          <div class="cc-section-label">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            <span>数据增强</span>
          </div>
          <div class="cc-config-opts">
            <label class="cc-switch-inline" :class="{ on: enableReferences }">
              <label class="cc-switch">
                <input type="checkbox" v-model="enableReferences" />
                <span class="slider orange"></span>
              </label>
              <div class="switch-text">
                <span class="switch-label">启用文献引用</span>
                <span class="switch-sub">生成时附带相关文献</span>
              </div>
            </label>
            <label class="cc-switch-inline" :class="{ on: enableOnlineData }">
              <label class="cc-switch">
                <input type="checkbox" v-model="enableOnlineData" />
                <span class="slider orange"></span>
              </label>
              <div class="switch-text">
                <span class="switch-label">启用联网数据</span>
                <span class="switch-sub">实时检索网络数据源</span>
              </div>
            </label>
          </div>

          <!-- 图表参数 -->
          <div class="cc-section-label">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>图表参数</span>
          </div>
          <div class="cc-params">
            <div class="cc-param-row">
              <span class="cc-param-label">配色方案</span>
              <div class="cc-palette-list">
                <button v-for="p in palettes" :key="p.key" class="cc-palette" :class="{ active: selectedPalette === p.key }" :title="p.name" @click="selectedPalette = p.key">
                  <span v-for="(c, i) in p.colors" :key="i" class="cc-palette-dot" :style="{ background: c }"></span>
                </button>
              </div>
            </div>
            <div class="cc-param-row">
              <span class="cc-param-label">输出尺寸</span>
              <div class="cc-size-list">
                <button v-for="s in sizePresets" :key="s.key" class="cc-size-btn" :class="{ active: selectedSize === s.key }" @click="selectedSize = s.key">{{ s.label }}</button>
              </div>
            </div>
            <div class="cc-param-row">
              <span class="cc-param-label">数据标签</span>
              <label class="cc-mini-switch">
                <input type="checkbox" v-model="showLabels" />
                <span class="mini-track"></span>
              </label>
              <span class="cc-param-hint">{{ showLabels ? '显示数值' : '隐藏数值' }}</span>
            </div>
          </div>

          <button class="cc-gen-btn" :disabled="loading" @click="generate">
            <span v-if="!loading" class="btn-inner">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              生成图表
            </span>
            <span v-else class="btn-inner"><span class="btn-spinner"></span>生成中</span>
          </button>

          <!-- 生成历史 -->
          <div class="cc-history" v-if="history.length">
            <div class="cc-section-label">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>生成历史</span>
              <button class="cc-history-clear" @click="clearHistory">清空</button>
            </div>
            <div class="cc-history-list">
              <button v-for="(h, i) in history" :key="i" class="cc-history-item" @click="restoreHistory(h)">
                <span class="cc-history-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="h.icon"></svg></span>
                <span class="cc-history-info">
                  <span class="cc-history-name">{{ h.name }}</span>
                  <span class="cc-history-time">{{ h.time }}</span>
                </span>
                <span class="cc-history-arrow">›</span>
              </button>
            </div>
          </div>
        </section>
    </div>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '图表生成 - 小工具 - AI写作助手' })

const toast = useToast()
const api = useApi()

const chartGroups = [
  {
    title: '基础图表',
    items: [
      { type: 'line', name: '折线图', icon: '<polyline points="3 17 9 11 13 15 21 7"/>' },
      { type: 'scatter', name: '散点图', icon: '<circle cx="6" cy="18" r="1.6"/><circle cx="12" cy="10" r="1.6"/><circle cx="18" cy="14" r="1.6"/><circle cx="9" cy="6" r="1.6"/>' },
      { type: 'bar', name: '柱状图', icon: '<line x1="6" y1="20" x2="6" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="14"/>' },
      { type: 'pie', name: '饼图', icon: '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>' },
      { type: 'box', name: '箱线图', icon: '<rect x="4" y="9" width="6" height="9" rx="1"/><line x1="7" y1="4" x2="7" y2="9"/><line x1="7" y1="18" x2="7" y2="21"/><rect x="14" y="6" width="6" height="12" rx="1"/><line x1="17" y1="2" x2="17" y2="6"/><line x1="17" y1="18" x2="17" y2="22"/>' },
      { type: 'heatmap', name: '热图', icon: '<rect x="3" y="3" width="6" height="6" rx="1"/><rect x="15" y="3" width="6" height="6" rx="1"/><rect x="3" y="15" width="6" height="6" rx="1"/><rect x="15" y="15" width="6" height="6" rx="1"/>' },
      { type: 'area', name: '面积图', icon: '<path d="M3 17 L9 11 L13 15 L21 7 L21 21 L3 21 Z"/>' },
      { type: 'radar', name: '雷达图', icon: '<polygon points="12 2 22 8.5 18 20 6 20 2 8.5"/><polygon points="12 7 17 10 15 16 9 16 7 10"/>' },
    ],
  },
  {
    title: '高级图表',
    items: [
      { type: 'stacked_bar', name: '堆叠柱状图', icon: '<rect x="5" y="11" width="4" height="9"/><rect x="5" y="5" width="4" height="6"/><rect x="15" y="8" width="4" height="12"/><rect x="15" y="3" width="4" height="5"/>' },
      { type: 'stacked_line', name: '堆叠折线图', icon: '<polyline points="3 14 9 8 13 12 21 4"/><polyline points="3 19 9 13 13 17 21 9"/>' },
      { type: 'dual_axis', name: '双轴图', icon: '<line x1="6" y1="20" x2="6" y2="6"/><polyline points="12 17 16 11 20 14"/><line x1="3" y1="3" x2="3" y2="21"/><line x1="3" y1="21" x2="21" y2="21"/>' },
      { type: 'polar', name: '极坐标图', icon: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><line x1="12" y1="3" x2="12" y2="21"/><line x1="3" y1="12" x2="21" y2="12"/>' },
      { type: '3d_scatter', name: '3D 散点图', icon: '<circle cx="8" cy="16" r="1.6"/><circle cx="14" cy="11" r="1.6"/><circle cx="17" cy="7" r="1.6"/><line x1="4" y1="20" x2="20" y2="20"/><line x1="4" y1="20" x2="4" y2="6"/><line x1="4" y1="20" x2="14" y2="10"/>' },
      { type: '3d_surface', name: '3D 曲面图', icon: '<path d="M3 16 Q8 8 13 14 T21 10"/><path d="M3 20 Q8 12 13 18 T21 14"/>' },
      { type: 'seaborn_kde', name: '核密度估计图', icon: '<path d="M3 19 Q9 4 13 12 T21 19"/>' },
      { type: 'seaborn_regression_scatter', name: '回归散点图', icon: '<circle cx="6" cy="17" r="1.4"/><circle cx="10" cy="13" r="1.4"/><circle cx="14" cy="9" r="1.4"/><circle cx="18" cy="6" r="1.4"/><line x1="4" y1="19" x2="20" y2="5"/>' },
    ],
  },
]

const totalTypes = computed(() => chartGroups.reduce((n, g) => n + g.items.length, 0))

const selectedType = ref('')
const dataInput = ref('')
// H5 双 Tab 视图（仅 m 层 ≤640 生效）：config=类型+数据配置 / result=预览+说明
const mPane = ref('config')
const enableReferences = ref(false)
const enableOnlineData = ref(false)

// 文献与联网互斥：开启一个时自动关闭另一个
watch(enableReferences, (v) => { if (v) enableOnlineData.value = false })
watch(enableOnlineData, (v) => { if (v) enableReferences.value = false })
const chartImg = ref('')
const chartDesc = ref('')
const wenxianlist = ref([])
const onlineList = ref([])
const loading = ref(false)
const loadingStep = ref('正在分析输入数据...')
const chartSize = ref('1200 × 720')
const generatedAt = ref('')

const activeTab = ref('desc')
const copiedIdx = ref(-1)
const onlineCopiedIdx = ref(-1)
const allCopied = ref(false)
const onlineCopied = ref(false)
const descCopied = ref(false)

// 新增：图表参数
const selectedPalette = ref('sunset')
const selectedSize = ref('standard')
const showLabels = ref(false)
const history = ref([])

// 配色方案
const palettes = [
  { key: 'sunset', name: '日落橙', colors: ['#f97316', '#fb923c', '#fdba74', '#fed7aa'] },
  { key: 'ocean', name: '海洋蓝', colors: ['#0ea5e9', '#38bdf8', '#7dd3fc', '#bae6fd'] },
  { key: 'forest', name: '森林绿', colors: ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0'] },
  { key: 'grape', name: '葡萄紫', colors: ['#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'] },
  { key: 'rose', name: '玫瑰红', colors: ['#f43f5e', '#fb7185', '#fda4af', '#fecdd3'] },
  { key: 'slate', name: '商务灰', colors: ['#475569', '#64748b', '#94a3b8', '#cbd5e1'] },
]

// 尺寸预设
const sizePresets = [
  { key: 'standard', label: '标准', w: 1200, h: 720 },
  { key: 'wide', label: '宽屏', w: 1600, h: 720 },
  { key: 'square', label: '方形', w: 800, h: 800 },
  { key: 'portrait', label: '竖版', w: 800, h: 1200 },
]

function getTypeIcon(type) {
  for (const g of chartGroups) {
    const t = g.items.find(x => x.type === type)
    if (t) return t.icon
  }
  return '<polyline points="3 17 9 11 13 15 21 7"/>'
}

function restoreHistory(h) {
  selectedType.value = h.type
  dataInput.value = h.data
  if (h.img) chartImg.value = h.img
  if (h.desc) chartDesc.value = h.desc
  if (h.refs) wenxianlist.value = h.refs
  if (h.online) onlineList.value = h.online
  toast.success(`已恢复「${h.name}」`)
}

function clearHistory() {
  history.value = []
  toast.success('已清空历史')
}

const currentTypeName = computed(() => {
  for (const g of chartGroups) {
    const t = g.items.find(x => x.type === selectedType.value)
    if (t) return t.name
  }
  return ''
})

const tabs = computed(() => [
  { key: 'desc', label: '图表说明', icon: '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="14" y2="18"/>', count: 0 },
  { key: 'ref', label: '参考文献', icon: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>', count: wenxianlist.value.length },
  { key: 'online', label: '联网数据', icon: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>', count: onlineList.value.length },
])

async function generate() {
  if (!selectedType.value) {
    toast.warning('请选择图表类型')
    return
  }
  if (!dataInput.value.trim()) {
    toast.warning('请输入数据')
    return
  }
  loading.value = true
  mPane.value = 'result' // m 层提交后自动切到结果页看生成进度
  chartImg.value = ''
  chartDesc.value = ''
  wenxianlist.value = []
  onlineList.value = []
  activeTab.value = 'desc'

  const steps = ['正在分析输入数据...', '正在匹配图表类型...', '正在渲染可视化图形...', '正在整合辅助信息...']
  let si = 0
  loadingStep.value = steps[0]
  const stepTimer = setInterval(() => {
    si = (si + 1) % steps.length
    loadingStep.value = steps[si]
  }, 700)

  try {
    const res = await api.post('/api/tools/createchart', {
      chartType: selectedType.value,
      data: dataInput.value.trim(),
      enableReferences: enableReferences.value,
      enableOnlineData: enableOnlineData.value,
    })
    if (!res.ok) throw new Error(res.msg || '生成图表失败')
    const d = res.data || {}
    if (!d.imgbase64) throw new Error('图表生成失败，请稍后重试')

    let img = String(d.imgbase64)
    if (!img.startsWith('data:')) img = 'data:image/png;base64,' + img
    chartImg.value = img
    chartDesc.value = String(d.content || '暂无图表说明')

    if (enableReferences.value) {
      // 上游返回文献引用字符串数组，包装为对象以适配列表模板
      const quotes = Array.isArray(d.wenxianlist) ? d.wenxianlist : []
      wenxianlist.value = quotes.map(q => {
        const s = String(q ?? '').trim()
        return { title: s, quote: s, authors: '', source: '', year: '', url: '', snippet: '' }
      })
    }
    // 联网数据已由上游融合进图表说明，无结构化列表返回

    const now = new Date()
    generatedAt.value = now.toLocaleString('zh-CN', { hour12: false })
    // 根据尺寸预设设置图表尺寸
    const sizePreset = sizePresets.find(s => s.key === selectedSize.value)
    const dims = { pie: '800 × 800', radar: '800 × 800', polar: '800 × 800' }
    if (['pie', 'radar', 'polar'].includes(selectedType.value)) {
      chartSize.value = dims[selectedType.value]
    } else if (sizePreset) {
      chartSize.value = `${sizePreset.w} × ${sizePreset.h}`
    }

    // 自动切换到有内容的 tab
    if (enableReferences.value && wenxianlist.value.length) {
      activeTab.value = 'ref'
    } else if (enableOnlineData.value && onlineList.value.length) {
      activeTab.value = 'online'
    }

    // 添加到生成历史
    history.value.unshift({
      type: selectedType.value,
      name: currentTypeName.value,
      icon: getTypeIcon(selectedType.value),
      time: now.toLocaleTimeString('zh-CN', { hour12: false }),
      data: dataInput.value,
      img: chartImg.value,
      desc: chartDesc.value,
      refs: [...wenxianlist.value],
      online: [...onlineList.value],
    })
    if (history.value.length > 5) history.value = history.value.slice(0, 5)

    toast.success('图表生成成功')
  } catch (e) {
    toast.error(e?.message || '生成图表时发生错误')
  } finally {
    clearInterval(stepTimer)
    loading.value = false
  }
}

function downloadChart() {
  if (!chartImg.value) return
  const link = document.createElement('a')
  link.href = chartImg.value
  const ts = new Date().toISOString().replace(/[:.]/g, '-')
  link.download = `图表_${currentTypeName.value || 'chart'}_${ts}.svg`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  toast.success('已开始下载')
}

async function copyDesc() {
  if (!chartDesc.value) return
  await copyText(chartDesc.value)
  descCopied.value = true
  toast.success('复制成功')
  setTimeout(() => { descCopied.value = false }, 1800)
}

async function copyOneRef(i) {
  const item = wenxianlist.value[i]
  if (!item) return
  const text = item.quote || [item.title, item.authors, item.source, item.year, item.url].filter(Boolean).join('. ')
  await copyText(`[${i + 1}] ${text}`)
  copiedIdx.value = i
  toast.success('复制成功')
  setTimeout(() => { copiedIdx.value = -1 }, 1800)
}

async function copyAllRef() {
  const text = wenxianlist.value.map((t, i) => {
    const s = t.quote || [t.title, t.authors, t.source, t.year, t.url].filter(Boolean).join('. ')
    return `[${i + 1}] ${s}`
  }).join('\n')
  await copyText(text)
  allCopied.value = true
  toast.success('全部复制成功')
  setTimeout(() => { allCopied.value = false }, 1800)
}

async function copyOneOnline(i) {
  const item = onlineList.value[i]
  if (!item) return
  await copyText(`${item.title}. ${item.source}. ${item.url} (检索于 ${item.retrievedAt})`)
  onlineCopiedIdx.value = i
  toast.success('复制成功')
  setTimeout(() => { onlineCopiedIdx.value = -1 }, 1800)
}

async function copyAllOnline() {
  const text = onlineList.value.map((t, i) => `[${i + 1}] ${t.title}. ${t.source}. ${t.url} (检索于 ${t.retrievedAt})`).join('\n')
  await copyText(text)
  onlineCopied.value = true
  toast.success('全部复制成功')
  setTimeout(() => { onlineCopied.value = false }, 1800)
}

async function copyText(t) {
  try {
    await navigator.clipboard.writeText(t)
  } catch (e) {
    const ta = document.createElement('textarea')
    ta.value = t
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
}

// 默认选中折线图，方便预览
onMounted(() => {
  selectedType.value = 'line'
})
</script>

<style scoped>
.cc-layout { display: grid; grid-template-columns: 176px minmax(0, 1fr) 316px; gap: 10px; align-items: start; }

/* 左：类型 */
.cc-types { border: 1px solid var(--gray-100); border-radius: 10px; background: var(--white); box-shadow: var(--shadow-sm); overflow: hidden; position: sticky; top: 80px; }
.cc-types-head { display: flex; align-items: center; gap: 6px; padding: 11px 12px; font-size: 13px; font-weight: 700; color: var(--dark-900); border-bottom: 1px solid var(--gray-100); background: linear-gradient(135deg, rgba(249,115,22,0.04), rgba(251,146,60,0.02)); }
.cc-types-head svg { color: #f97316; }
.cc-types-count { margin-left: auto; font-size: 11px; font-weight: 600; color: #c2410c; background: rgba(249,115,22,0.1); padding: 2px 8px; border-radius: 999px; font-variant-numeric: tabular-nums; }
.cc-types-scroll { max-height: 580px; overflow-y: auto; padding: 8px; scrollbar-width: thin; scrollbar-color: rgba(249,115,22,0.3) transparent; }
.cc-types-scroll::-webkit-scrollbar { width: 6px; }
.cc-types-scroll::-webkit-scrollbar-track { background: transparent; margin: 4px 0; }
.cc-types-scroll::-webkit-scrollbar-thumb { background: linear-gradient(180deg, rgba(249,115,22,0.25) 0%, rgba(251,146,60,0.2) 100%); border-radius: 3px; border: 1px solid transparent; background-clip: padding-box; transition: background .2s; }
.cc-types-scroll::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, rgba(249,115,22,0.5) 0%, rgba(251,146,60,0.4) 100%); background-clip: padding-box; }
.cc-type-group { margin-bottom: 12px; }
.cc-type-group:last-child { margin-bottom: 0; }
.cc-group-title { display: flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.06em; margin: 0 2px 8px; }
.cc-group-title::before { content: ''; width: 3px; height: 3px; border-radius: 50%; background: var(--gray-300); }
.cc-type-list { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.cc-type-btn { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; width: 100%; padding: 10px 4px 8px; border-radius: 9px; border: 1px solid transparent; background: var(--gray-50); color: var(--gray-600); font-size: 11px; font-weight: 500; cursor: pointer; text-align: center; transition: all .18s ease; font-family: inherit; line-height: 1.25; }
.cc-type-btn:hover { background: var(--white); border-color: rgba(249,115,22,0.2); color: var(--dark-800); transform: translateY(-1px); box-shadow: 0 3px 10px rgba(15,23,42,0.05); }
.cc-type-btn.active { background: linear-gradient(135deg, rgba(249,115,22,0.1) 0%, rgba(251,146,60,0.04) 100%); border-color: rgba(249,115,22,0.3); color: #c2410c; font-weight: 600; box-shadow: 0 3px 10px rgba(249,115,22,0.1); }
.cc-type-icon { display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: var(--white); border: 1px solid var(--gray-100); color: var(--gray-500); transition: all .18s ease; }
.cc-type-icon svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.cc-type-btn:hover .cc-type-icon { color: #f97316; border-color: rgba(249,115,22,0.25); }
.cc-type-btn.active .cc-type-icon { color: #f97316; background: rgba(249,115,22,0.08); border-color: rgba(249,115,22,0.3); }
.cc-type-name { width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cc-type-check { position: absolute; top: 3px; right: 3px; display: flex; align-items: center; justify-content: center; width: 14px; height: 14px; border-radius: 50%; background: linear-gradient(135deg, #fb923c, #f97316); color: #fff; box-shadow: 0 1px 4px rgba(249,115,22,0.4); }
.cc-type-check svg { width: 9px; height: 9px; }

/* 右：主区 */
.cc-main { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
.cc-panel { border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px 13px; background: var(--white); box-shadow: var(--shadow-sm); }
.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; min-height: 30px; }
.panel-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.orange { background: #f97316; box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-800); }
.panel-head-actions { margin-left: auto; display: flex; align-items: center; gap: 6px; }

.cc-meta-tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; background: rgba(249,115,22,0.08); color: #c2410c; font-size: 11px; font-weight: 600; border: 1px solid rgba(249,115,22,0.15); }
.cc-meta-tag svg { color: #f97316; }

.cc-head-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 8px; border: 1px solid rgba(249, 115, 22, 0.25); background: rgba(249, 115, 22, 0.06); color: #c2410c; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all .2s; }
.cc-head-btn:hover { background: rgba(249, 115, 22, 0.12); }

/* 预览 */
.cc-preview-box { position: relative; min-height: 400px; border: 1px solid var(--gray-100); border-radius: 8px; background:
  linear-gradient(45deg, #f8fafc 25%, transparent 25%),
  linear-gradient(-45deg, #f8fafc 25%, transparent 25%),
  linear-gradient(45deg, transparent 75%, #f8fafc 75%),
  linear-gradient(-45deg, transparent 75%, #f8fafc 75%);
  background-size: 20px 20px;
  background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
  background-color: #fff;
  display: flex; align-items: center; justify-content: center; overflow: hidden; }
.cc-preview-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 400px; }
.cc-loading-orb { display: flex; align-items: center; justify-content: center; }
.cc-orb-arc { transform-origin: center; animation: cc-orb-spin 1.1s linear infinite; }
@keyframes cc-orb-spin { to { transform: rotate(360deg); } }
.cc-loading-text { font-size: 13px; font-weight: 600; color: var(--dark-800); }
.cc-loading-sub { font-size: 11.5px; color: var(--gray-400); }

.cc-preview-img-wrap { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; gap: 8px; }
.cc-preview-img { max-width: 100%; max-height: 348px; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 16px rgba(15,23,42,0.06); background: #fff; }
.cc-preview-info { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center; }
.cc-info-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; background: var(--gray-50); border: 1px solid var(--gray-100); font-size: 11px; color: var(--gray-500); font-variant-numeric: tabular-nums; }
.cc-info-dot { width: 6px; height: 6px; border-radius: 50%; background: #f97316; }

.cc-preview-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; min-height: 400px; color: var(--gray-400); }
.cc-empty-illu { display: flex; align-items: center; justify-content: center; width: 76px; height: 76px; border-radius: 18px; background: linear-gradient(135deg, rgba(249,115,22,0.06), rgba(251,146,60,0.03)); color: #fdba74; margin-bottom: 4px; }
.cc-empty-title { font-size: 13px; font-weight: 600; color: var(--gray-500); }
.cc-empty-sub { font-size: 11.5px; color: var(--gray-400); }

/* Tab 区 */
.cc-tabs-panel { padding: 0; overflow: hidden; }
.cc-tabs-head { display: flex; align-items: center; gap: 2px; padding: 8px 8px 0; border-bottom: 1px solid var(--gray-100); background: var(--gray-50); }
.cc-tab { display: inline-flex; align-items: center; gap: 5px; padding: 8px 14px; border: 1px solid transparent; border-bottom: none; background: transparent; color: var(--gray-500); font-size: 12.5px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; position: relative; transition: color .18s ease, background .18s ease, border-color .18s ease; font-family: inherit; margin-bottom: -1px; }
.cc-tab:hover { color: var(--dark-800); background: rgba(249,115,22,0.04); }
.cc-tab.active { color: #c2410c; background: var(--white); border-color: var(--gray-100); border-bottom: 1px solid var(--white); }
.cc-tab.active::after { content: ''; position: absolute; left: 10px; right: 10px; bottom: -1px; height: 2px; background: linear-gradient(90deg, #fb923c, #f97316); border-radius: 2px; }
.cc-tab-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 17px; height: 17px; padding: 0 5px; border-radius: 999px; background: var(--gray-200); color: var(--gray-600); font-size: 10px; font-weight: 700; font-variant-numeric: tabular-nums; }
.cc-tab.active .cc-tab-badge { background: rgba(249,115,22,0.15); color: #c2410c; }
.cc-tab.has-badge .cc-tab-badge { background: rgba(249,115,22,0.12); color: #c2410c; }
.cc-tab.active.has-badge .cc-tab-badge { background: #f97316; color: #fff; }

.cc-tabs-body { padding: 12px 14px; min-height: 240px; }
.cc-tab-pane { position: relative; }

/* 说明 */
.cc-desc-text { font-size: 12.5px; color: var(--gray-600); line-height: 1.75; padding-right: 6px; white-space: pre-wrap; }
.cc-desc-text.empty { color: var(--gray-400); font-style: italic; }
.cc-copy-desc { position: absolute; top: 0; right: 0; display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 7px; border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s; }
.cc-copy-desc:hover { border-color: #f97316; color: #c2410c; background: rgba(249, 115, 22, 0.05); }
.cc-copy-desc.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }

/* 空内联 */
.cc-empty-inline { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 28px 16px; color: var(--gray-400); }
.cc-empty-inline svg { color: var(--gray-300); }
.cc-empty-inline > span:nth-child(2) { font-size: 13px; font-weight: 600; color: var(--gray-500); }
.cc-empty-hint { font-size: 11.5px; color: var(--gray-400); }

/* 列表工具条 */
.cc-list-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px dashed var(--gray-100); }
.cc-list-count { font-size: 12px; color: var(--gray-500); font-weight: 500; }
.cc-list-actions { display: flex; gap: 6px; }
.cc-tool-btn { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 7px; border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all .2s; }
.cc-tool-btn:hover { border-color: #f97316; color: #c2410c; background: rgba(249,115,22,0.05); }

/* 链接列表 */
.cc-link-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.cc-link-item { display: flex; gap: 10px; padding: 11px 13px; background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 9px; transition: all .2s ease; }
.cc-link-item:hover { background: var(--white); border-color: rgba(249, 115, 22, 0.22); box-shadow: 0 4px 14px rgba(249, 115, 22, 0.07); }
.cc-link-index { width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0; background: linear-gradient(135deg, #fb923c, #f97316); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; box-shadow: 0 2px 6px rgba(249, 115, 22, 0.25); }
.cc-link-index.online { background: linear-gradient(135deg, #22d3ee, #06b6d4); box-shadow: 0 2px 6px rgba(6, 182, 212, 0.25); }
.cc-link-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.cc-link-title { display: inline-flex; align-items: center; gap: 5px; font-size: 13.5px; font-weight: 700; color: var(--dark-900); line-height: 1.4; text-decoration: none; transition: color .18s; }
.cc-link-title:hover { color: #c2410c; }
.cc-external { color: var(--gray-400); flex-shrink: 0; transition: color .18s; }
.cc-link-title:hover .cc-external { color: #f97316; }
.cc-link-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 11.5px; color: var(--gray-500); }
.cc-meta-source { display: inline-flex; align-items: center; gap: 4px; color: #c2410c; font-weight: 600; }
.cc-meta-source svg { color: #f97316; }
.cc-meta-source.online { color: #0e7490; }
.cc-meta-source.online svg { color: #06b6d4; }
.cc-meta-sep { color: var(--gray-300); }
.cc-meta-authors, .cc-meta-year, .cc-meta-domain { font-variant-numeric: tabular-nums; }
.cc-link-snippet { font-size: 12px; color: var(--gray-600); line-height: 1.6; margin: 0; padding-left: 10px; border-left: 2px solid var(--gray-200); }
.cc-link-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 2px; }
.cc-link-url { font-size: 11px; color: var(--gray-400); font-family: 'SF Mono', 'Consolas', monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 380px; }
.cc-link-ops { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.cc-op-btn { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 6px; border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 11.5px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all .2s; text-decoration: none; }
.cc-op-btn:hover { border-color: #f97316; color: #c2410c; background: rgba(249, 115, 22, 0.05); }
.cc-op-btn.primary { background: rgba(249,115,22,0.08); border-color: rgba(249,115,22,0.25); color: #c2410c; }
.cc-op-btn.primary:hover { background: rgba(249,115,22,0.15); }
.cc-op-btn.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }

/* 配置 */
.cc-config { position: sticky; top: 80px; display: flex; flex-direction: column; gap: 9px; }
.cc-config .panel-head { flex-wrap: wrap; margin-bottom: 0; }
.cc-config-tip { margin-left: auto; display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; color: var(--gray-400); font-weight: 400; max-width: 100%; }
.cc-config-tip svg { color: var(--gray-300); flex-shrink: 0; }

/* 小节标签 */
.cc-section-label { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }
.cc-section-label svg { color: #f97316; flex-shrink: 0; }
.cc-section-label span { flex: 1; }

.cc-config-opts { display: flex; flex-direction: column; gap: 6px; }
.cc-switch-inline { display: flex; align-items: center; gap: 9px; padding: 8px 12px; border-radius: 9px; border: 1px solid var(--gray-100); background: var(--gray-50); transition: all .2s; }
.cc-switch-inline.on { border-color: rgba(249,115,22,0.25); background: rgba(249,115,22,0.04); }
.cc-switch { position: relative; display: inline-block; width: 34px; height: 19px; flex-shrink: 0; }
.cc-switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background: var(--gray-300); transition: .3s; border-radius: 22px; }
.slider:before { position: absolute; content: ""; height: 13px; width: 13px; left: 3px; bottom: 3px; background: var(--white); transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
input:checked + .slider.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
input:checked + .slider.orange:before { transform: translateX(15px); }
.switch-text { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.switch-label { font-size: 12.5px; color: var(--dark-800); font-weight: 600; }
.switch-sub { font-size: 11px; color: var(--gray-400); font-weight: 400; }

/* 图表参数 */
.cc-params { display: flex; flex-direction: column; gap: 7px; padding: 9px 10px; border-radius: 9px; border: 1px solid var(--gray-100); background: var(--gray-50); }
.cc-param-row { display: flex; align-items: center; gap: 8px; }
.cc-param-label { font-size: 11.5px; color: var(--gray-500); font-weight: 600; flex-shrink: 0; min-width: 52px; }
.cc-palette-list { display: flex; gap: 4px; flex: 1; flex-wrap: wrap; }
.cc-palette { display: flex; align-items: center; gap: 2px; padding: 4px 6px; border-radius: 7px; border: 1px solid transparent; background: var(--white); cursor: pointer; transition: all .18s ease; }
.cc-palette:hover { border-color: var(--gray-200); transform: translateY(-1px); }
.cc-palette.active { border-color: #f97316; background: rgba(249,115,22,0.05); box-shadow: 0 2px 6px rgba(249,115,22,0.12); }
.cc-palette-dot { width: 10px; height: 10px; border-radius: 50%; }
.cc-size-list { display: flex; gap: 4px; flex: 1; }
.cc-size-btn { padding: 4px 10px; border-radius: 6px; border: 1px solid var(--gray-100); background: var(--white); color: var(--gray-500); font-size: 11px; font-weight: 600; cursor: pointer; transition: all .18s ease; font-family: inherit; }
.cc-size-btn:hover { border-color: var(--gray-200); color: var(--dark-800); }
.cc-size-btn.active { background: linear-gradient(135deg, rgba(249,115,22,0.1), rgba(251,146,60,0.05)); border-color: rgba(249,115,22,0.3); color: #c2410c; }
.cc-param-hint { font-size: 11px; color: var(--gray-400); font-weight: 500; }
.cc-mini-switch { position: relative; display: inline-block; width: 28px; height: 16px; flex-shrink: 0; cursor: pointer; }
.cc-mini-switch input { opacity: 0; width: 0; height: 0; }
.mini-track { position: absolute; cursor: pointer; inset: 0; background: var(--gray-300); transition: .3s; border-radius: 22px; }
.mini-track:before { position: absolute; content: ""; height: 10px; width: 10px; left: 3px; bottom: 3px; background: var(--white); transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
.cc-mini-switch input:checked + .mini-track { background: linear-gradient(135deg, #fb923c, #f97316); }
.cc-mini-switch input:checked + .mini-track:before { transform: translateX(12px); }

/* 生成历史 */
.cc-history { margin-top: 2px; display: flex; flex-direction: column; gap: 6px; }
.cc-history-clear { margin-left: auto; border: none; background: transparent; color: var(--gray-400); font-size: 10.5px; font-weight: 500; cursor: pointer; padding: 2px 6px; border-radius: 5px; transition: all .18s; font-family: inherit; }
.cc-history-clear:hover { color: #ef4444; background: rgba(239,68,68,0.06); }
.cc-history-list { display: flex; flex-direction: column; gap: 4px; }
.cc-history-item { display: flex; align-items: center; gap: 8px; padding: 7px 9px; border-radius: 8px; border: 1px solid var(--gray-100); background: var(--white); cursor: pointer; transition: all .18s ease; font-family: inherit; text-align: left; }
.cc-history-item:hover { border-color: rgba(249,115,22,0.25); background: rgba(249,115,22,0.03); transform: translateX(2px); }
.cc-history-icon { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: linear-gradient(135deg, rgba(249,115,22,0.1), rgba(251,146,60,0.04)); color: #f97316; flex-shrink: 0; }
.cc-history-icon svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.cc-history-info { flex: 1; display: flex; flex-direction: column; gap: 0; min-width: 0; }
.cc-history-name { font-size: 12px; font-weight: 600; color: var(--dark-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cc-history-time { font-size: 10.5px; color: var(--gray-400); font-variant-numeric: tabular-nums; }
.cc-history-arrow { color: var(--gray-300); font-size: 16px; flex-shrink: 0; }

.cc-gen-btn { width: 100%; justify-content: center; border: none; border-radius: 9px; padding: 10px 18px; background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); color: var(--white); font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.28); transition: transform .15s, box-shadow .2s, opacity .2s; }
.cc-gen-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(249, 115, 22, 0.34); }
.cc-gen-btn:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }

.btn-inner { display: inline-flex; align-items: center; gap: 6px; }
.btn-spinner { width: 12px; height: 12px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: cc-spin .7s linear infinite; }
@keyframes cc-spin { to { transform: rotate(360deg); } }

.cc-textarea { width: 100%; min-height: 110px; border: 1px solid var(--gray-200); border-radius: 8px; padding: 10px 12px; font-size: 13px; line-height: 1.7; color: var(--dark-800); background: #fcfdff; outline: none; resize: vertical; font-family: inherit; transition: border-color .2s, box-shadow .2s; }
.cc-textarea:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12); background: var(--white); }
.cc-textarea::placeholder { color: var(--gray-400); }

.cc-fade-enter-active, .cc-fade-leave-active { transition: opacity .25s; }
.cc-fade-enter-from, .cc-fade-leave-to { opacity: 0; }

/* ============ 桌面宽屏：撑满视口高度，消除底部灰底留白 ============ */
@media (min-width: 1241px) {
  /* ToolShell 卡片至少撑满视口（减去顶栏 84 + 底部内边距 32），
     用视口单位避免父级 min-height 链不定导致百分比高度失效 */
  .cc-fill { display: flex; flex-direction: column; min-height: calc(100vh - 116px); }
  :deep(.shell-body) { flex: 1; min-height: 0; display: flex; flex-direction: column; }

  /* 三栏网格撑满卡片主体：中栏预览弹性增长，左栏类型列表随高度延伸 */
  .cc-layout { flex: 1; min-height: 0; align-items: stretch; grid-template-rows: 1fr; }
  .cc-types { align-self: stretch; position: static; display: flex; flex-direction: column; min-height: 0; }
  .cc-types-scroll { flex: 1; min-height: 0; max-height: none; }
  .cc-config { align-self: start; position: static; }

  /* 中栏：预览区固定高度（图表生成前后保持一致，不再跳动）；Tab 区弹性增长，撑满剩余空间 */
  .cc-main { min-height: 0; }
  .cc-main > .cc-preview { flex: 0 0 auto; min-height: 0; display: flex; flex-direction: column; }
  .cc-main > .cc-tabs-panel { flex: 1 1 0; min-height: 0; display: flex; flex-direction: column; }
  .cc-preview-box { flex: none; height: 380px; min-height: 0; }
  .cc-preview-loading, .cc-preview-empty { height: 100%; align-self: stretch; min-height: 0; }
  .cc-preview-img { max-height: 320px; }
  .cc-tabs-body { flex: 1; min-height: 280px; overflow-y: auto; }
}

/* 响应式：中屏退化为两列，配置移到主区下方 */
@media (max-width: 1240px) {
  .cc-layout { grid-template-columns: 168px minmax(0, 1fr); }
  .cc-config { position: static; }
  .cc-layout > .cc-config { grid-column: 1 / -1; }
}
@media (max-width: 900px) {
  .cc-layout { grid-template-columns: 1fr; }
  .cc-types { position: static; }
  .cc-types-scroll { max-height: 280px; }
  .cc-preview-box { min-height: 340px; }
  .cc-preview-loading, .cc-preview-empty { min-height: 340px; }
}
@media (max-width: 560px) {
  .cc-link-foot { flex-direction: column; align-items: flex-start; }
  .cc-link-url { max-width: 100%; }
  .cc-preview-img { max-height: 200px; }
  .cc-preview-box { min-height: 280px; }
  .cc-preview-loading, .cc-preview-empty { min-height: 280px; }
}

/* ============ m 层 H5 双 Tab（.m-main 门控 + ≤640，PC/桌面窄窗口零影响） ============ */
/* 基类：切换条恒隐藏；config=类型+数据配置 / result=预览+说明文献 */
.cc-m-tabs { display: none; }
@media (max-width: 640px) {
  /* 切换条：orange 主题 */
  .m-main .cc-m-tabs { display: flex; gap: 8px; margin-bottom: 10px; }
  .m-main .cc-m-tabs button { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; height: 38px; border: 1px solid var(--gray-200); border-radius: 10px; background: var(--white); font-size: 13px; font-weight: 600; color: var(--gray-500); cursor: pointer; font-family: inherit; transition: all .18s; }
  .m-main .cc-m-tabs button.active { color: #c2410c; border-color: rgba(249, 115, 22, 0.45); background: #fff7ed; }
  /* Tab 显隐 */
  .m-main .cc-m-off { display: none !important; }
  /* 类型宫格 3 列紧凑（原 2 列 8 行 + 280px 内滚拉高页面） */
  .m-main .cc-types-scroll { max-height: none; overflow: visible; padding: 8px; }
  .m-main .cc-type-list { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .m-main .cc-type-btn { padding: 8px 3px 7px; gap: 4px; }
  .m-main .cc-type-icon { width: 22px; height: 22px; border-radius: 6px; }
  .m-main .cc-type-icon svg { width: 13px; height: 13px; }
  .m-main .cc-type-name { font-size: 10.5px; }
  .m-main .cc-group-title { margin: 0 2px 6px; }
  .m-main .cc-type-group { margin-bottom: 10px; }
  /* 预览与空态紧凑（原 400px 撑高） */
  .m-main .cc-preview-box { min-height: 230px; }
  .m-main .cc-preview-loading, .m-main .cc-preview-empty { min-height: 230px; }
  .m-main .cc-preview-img { max-height: 230px; }
  .m-main .cc-empty-illu { width: 56px; height: 56px; border-radius: 14px; }
  .m-main .cc-empty-illu svg { width: 40px; height: 40px; }
  /* Tab 卡体与空内联提示紧凑 */
  .m-main .cc-tabs-body { min-height: 0; padding: 10px 12px; }
  .m-main .cc-empty-inline { padding: 16px 12px; }
  /* 配置面板紧凑 */
  .m-main .cc-textarea { min-height: 96px; }
  .m-main .cc-switch-inline { padding: 7px 10px; }
  .m-main .cc-params { padding: 8px 9px; gap: 6px; }
  .m-main .cc-gen-btn { padding: 11px 18px; font-size: 13.5px; }
}
</style>
