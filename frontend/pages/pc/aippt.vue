<template>
  <div class="aippt-page">
    <ClientOnly>
      <!-- 版本切换 Tab -->
      <div class="version-tabs">
        <button
          :class="['ver-tab', { active: version === 'standard' }]"
          @click="version = 'standard'"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          <span>普通版</span>
        </button>
        <button
          :class="['ver-tab', { active: version === 'advanced' }]"
          @click="version = 'advanced'"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span>高级版</span>
          <span class="ver-badge">PRO</span>
        </button>
      </div>

      <!-- 高级版：完整流程 -->
      <div v-if="version === 'advanced'" class="advanced-flow">
        <!-- 高级版步骤指示器 -->
        <nav class="adv-steps">
          <div
            v-for="(s, i) in advStepList"
            :key="i"
            :class="['adv-step', {
              active: advStep === i + 1,
              done: advStep > i + 1
            }]"
          >
            <div class="adv-step__num">
              <svg v-if="advStep > i + 1" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span class="adv-step__label">{{ s.label }}</span>
            <span v-if="i < advStepList.length - 1" class="adv-step__line"></span>
          </div>
        </nav>

        <!-- 高级 Step 1: 输入主题 -->
        <div v-if="advStep === 1" class="adv-card adv-step-content">
          <div class="adv-step-head">
            <div class="adv-step-head__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5z"/></svg>
            </div>
            <div class="adv-step-head__text">
              <h2 class="adv-step-head__title">输入 PPT 主题</h2>
              <p class="adv-step-head__desc">AI 将根据主题自动生成结构化大纲，并匹配专业模板与配色</p>
            </div>
            <button class="price-btn" @click="showPriceModal = true">
              <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
              收费标准
            </button>
          </div>

          <div class="adv-form">
            <div class="adv-field">
              <label class="adv-field__label">PPT 标题</label>
              <div class="adv-field__control">
                <input
                  v-model="advForm.title"
                  type="text"
                  class="adv-input"
                  placeholder="如：基于深度学习的图像识别研究"
                  @keydown.enter="advGoStep2"
                />
              </div>
            </div>
            <div class="adv-field">
              <label class="adv-field__label">作者/汇报人</label>
              <div class="adv-field__control">
                <input
                  v-model="advForm.author"
                  type="text"
                  class="adv-input"
                  placeholder="选填，将显示在封面"
                />
              </div>
            </div>
          </div>

          <!-- 高级版特性展示 -->
          <div class="adv-features">
            <div class="adv-feature-chip">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5z"/></svg>
              <span>144 套配色方案</span>
            </div>
            <div class="adv-feature-chip">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
              <span>专业模板预览</span>
            </div>
            <div class="adv-feature-chip">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <span>智能排版生成</span>
            </div>
          </div>

          <div class="adv-step-nav">
            <button class="btn btn--primary btn--lg" @click="advGoStep2">
              下一步：生成大纲
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>
        </div>

        <!-- 高级 Step 2: 大纲生成 + 编辑 -->
        <div v-if="advStep === 2" class="adv-card adv-step-content">
          <!-- 生成中：流式展示 -->
          <div v-if="advGenerating" class="outline-card streaming-mode">
            <div class="card-head">
              <div class="head-title">
                <span class="head-icon stream-orb-wrap">
                  <span class="stream-orb-mini">
                    <span class="orb-core"></span>
                    <span class="orb-ring"></span>
                  </span>
                </span>
                <h3>AI 正在生成大纲<span class="stream-badge">实时</span></h3>
              </div>
              <div class="head-actions">
                <div class="stream-wave">
                  <span></span><span></span><span></span><span></span><span></span>
                </div>
              </div>
            </div>

            <div v-if="advStreamOutline.chapters.length" class="outline-tree streaming">
              <div
                v-for="(chapter, cIdx) in advStreamOutline.chapters"
                :key="cIdx"
                class="chapter stream-fade-in"
              >
                <div class="chapter-title">
                  <span class="stream-chapter-num">{{ cIdx + 1 }}</span>
                  <h4>{{ chapter.title }}</h4>
                </div>
                <div v-if="chapter.sections && chapter.sections.length" class="sections">
                  <div
                    v-for="(section, sIdx) in chapter.sections"
                    :key="sIdx"
                    class="section stream-fade-in"
                  >
                    <div class="section-main">
                      <span class="stream-section-dot"></span>
                      <span>{{ section.title }}</span>
                    </div>
                    <div v-if="section.items && section.items.length" class="section-items">
                      <div
                        v-for="(item, iIdx) in section.items"
                        :key="iIdx"
                        class="section-item stream-fade-in"
                      >
                        <span class="stream-item-dot"></span>
                        <span>{{ item }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="stream-skeleton">
                  <div class="skeleton-line"></div>
                  <div class="skeleton-line short"></div>
                </div>
              </div>
              <div class="stream-typing-row">
                <span class="stream-typing-cursor"></span>
                <span class="stream-typing-text">{{ advGenStepText }}</span>
              </div>
            </div>

            <div v-else class="stream-empty">
              <div class="stream-ripple">
                <span class="orb-core"></span>
                <span class="ripple-ring"></span>
                <span class="ripple-ring d1"></span>
                <span class="ripple-ring d2"></span>
              </div>
              <p>{{ advGenStepText }}</p>
            </div>

            <div class="stream-footer-bar">
              <div class="stream-shimmer-bar"></div>
              <span class="stream-tip">内容正在实时生成，请稍候</span>
            </div>
          </div>

          <!-- 生成完成：结构化大纲展示 -->
          <div v-else class="outline-card">
            <div class="outline-header">
              <div class="outline-header__left">
                <div class="outline-header__icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <h3 class="outline-title">大纲已生成</h3>
                  <p class="outline-subtitle">{{ advParsedOutline.chapters.length }} 个章节 · 可编辑后继续</p>
                </div>
              </div>
              <div class="outline-header__right">
                <div class="outline-header__badge">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5z"/></svg>
                  <span>AI 生成</span>
                </div>
                <button class="edit-toggle-btn" :class="{ active: advEditMode }" @click="toggleAdvEditMode">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  {{ advEditMode ? '完成编辑' : '编辑大纲' }}
                </button>
              </div>
            </div>

            <!-- 结构化大纲（默认视图） -->
            <div v-if="!advEditMode" class="outline-tree">
              <div
                v-for="(chapter, cIdx) in advParsedOutline.chapters"
                :key="cIdx"
                class="chapter-card"
              >
                <div class="chapter-card__head" @click="toggleAdvChapter(cIdx)">
                  <span class="chapter-card__num">{{ cIdx + 1 }}</span>
                  <span class="chapter-card__title">{{ chapter.title }}</span>
                  <span class="chapter-card__count">{{ (chapter.sections || []).length }} 节</span>
                  <svg class="chapter-card__arrow" :class="{ expanded: advExpandedChapters.includes(cIdx) }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <Transition name="chapter-expand">
                  <div v-if="advExpandedChapters.includes(cIdx)" class="chapter-card__body">
                    <div
                      v-for="(section, sIdx) in (chapter.sections || [])"
                      :key="sIdx"
                      class="section-block"
                    >
                      <div class="section-block__title">
                        <span class="section-block__dot"></span>
                        <span>{{ section.title }}</span>
                      </div>
                      <div v-if="section.items && section.items.length" class="section-block__items">
                        <div v-for="(item, iIdx) in section.items" :key="iIdx" class="section-block__item">
                          <span class="section-block__bullet"></span>
                          <span>{{ item }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- 编辑模式 -->
            <div v-else class="outline-edit-mode">
              <textarea
                v-model="advForm.outline_content"
                class="outline-textarea"
                placeholder="大纲内容将在这里显示..."
              ></textarea>
            </div>

            <div class="adv-step-nav">
              <button class="btn btn--ghost" @click="advStep = 1">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                上一步
              </button>
              <button class="btn btn--ghost" @click="handleAdvGenerateOutline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                重新生成
              </button>
              <button class="btn btn--primary" @click="advGoStep3">
                下一步：选择模板
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- 高级 Step 3: 选择模板（带预览图） -->
        <div v-if="advStep === 3" class="adv-card adv-step-content">
          <!-- 分类筛选 -->
          <div class="adv-cat-bar">
            <button
              v-for="cat in advCategories"
              :key="cat.name"
              :class="['adv-cat-chip', { active: advSelectedCategory === cat.name }]"
              @click="selectAdvCategory(cat.name)"
            >
              {{ cat.name }}
              <span class="adv-cat-chip__count">{{ cat.count }}</span>
            </button>
          </div>

          <!-- 模板网格（带预览图） -->
          <div v-if="advLoadingTemplates" class="adv-tpl-loading">
            <div class="adv-skeleton-grid">
              <div v-for="i in 8" :key="i" class="adv-tpl-skeleton"></div>
            </div>
          </div>
          <div v-else-if="advTemplates.length" class="adv-tpl-grid">
            <div
              v-for="tpl in advTemplates"
              :key="tpl.tpl_uid"
              :class="['adv-tpl-card', { selected: advForm.tpl_uid === tpl.tpl_uid }]"
              @click="selectAdvTemplate(tpl)"
            >
              <div class="adv-tpl-card__cover">
                <img
                  v-if="tpl.preview"
                  :src="tpl.preview"
                  :alt="tpl.category + ' - ' + tpl.style"
                  @error="onAdvCoverError($event, tpl)"
                />
                <div v-else class="adv-tpl-card__placeholder">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                </div>
                <div v-if="advForm.tpl_uid === tpl.tpl_uid" class="adv-tpl-card__check">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </div>
              <div class="adv-tpl-card__info">
                <span class="adv-tpl-card__cat">{{ tpl.category || '通用' }}</span>
                <span v-if="tpl.style" class="adv-tpl-card__style">{{ tpl.style }}</span>
              </div>
            </div>
          </div>
          <div v-else class="adv-tpl-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            <p>暂无模板</p>
          </div>

          <!-- 分页 -->
          <div v-if="advTplPages > 1 && !advLoadingTemplates" class="adv-pagination">
            <button
              class="adv-page-btn"
              :disabled="advTplPage <= 1"
              @click="advTplGoPage(advTplPage - 1)"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <template v-for="(p, i) in advTplPageList" :key="i">
              <span v-if="p === '...'" class="adv-page-ellipsis">...</span>
              <button
                v-else
                :class="['adv-page-num', { active: p === advTplPage }]"
                @click="advTplGoPage(p)"
              >{{ p }}</button>
            </template>
            <button
              class="adv-page-btn"
              :disabled="advTplPage >= advTplPages"
              @click="advTplGoPage(advTplPage + 1)"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <span class="adv-page-info">共 {{ advTplTotal }} 个</span>
          </div>

          <div class="adv-step-nav">
            <button class="btn btn--ghost" @click="advStep = 2">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              上一步
            </button>
            <!-- 第四步「选择配色」已停用，模板选择后直接进入确认下单 -->
            <!--
            <button class="btn btn--primary" :disabled="!advForm.tpl_uid" @click="advGoStep4">
              下一步：选择配色
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
            -->
            <button class="btn btn--primary" :disabled="!advForm.tpl_uid" @click="advShowConfirm = true">
              确认生成
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </button>
          </div>
        </div>

        <!-- ========== 高级 Step 4: 选择配色（已停用，仅注释保留，暂不删除） ==========
        注意：下方“已选模板信息”、“使用默认配色选项（置顶）”、“配色网格”等行原为 HTML 注释，为便于整体注释已去除其注释标记
        <div v-if="advStep === 4" class="adv-card adv-step-content">
          <div class="adv-step-head">
            <div class="adv-step-head__icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
            </div>
            <div>
              <h2 class="adv-step-head__title">选择主题配色</h2>
              <p class="adv-step-head__desc">144 套精选配色方案，选择适合主题的颜色风格</p>
            </div>
          </div>

          <!-- 已选模板信息
          <div class="adv-selected-tpl">
            <div class="adv-selected-tpl__preview">
              <img v-if="advSelectedTemplate?.preview" :src="advSelectedTemplate.preview" alt="" />
              <div v-else class="adv-tpl-card__placeholder"></div>
            </div>
            <div class="adv-selected-tpl__info">
              <span class="adv-selected-tpl__cat">{{ advSelectedTemplate?.category || '通用' }}</span>
              <span class="adv-selected-tpl__style">{{ advSelectedTemplate?.style || '默认风格' }}</span>
            </div>
            <button class="btn btn--ghost btn--sm" @click="advStep = 3">更换</button>
          </div>

          <!-- 使用默认配色选项（置顶）
          <div
            :class="['adv-theme-default', { selected: !advForm.theme_id }]"
            @click="advForm.theme_id = ''"
          >
            <span :class="['adv-skip-radio', { checked: !advForm.theme_id }]"></span>
            <div class="adv-theme-default__info">
              <span class="adv-theme-default__name">使用模板默认配色</span>
              <span class="adv-theme-default__desc">不应用额外配色，保持模板原始风格</span>
            </div>
            <div class="adv-theme-default__colors">
              <span class="adv-theme-color" style="background:#64748b"></span>
              <span class="adv-theme-color" style="background:#94a3b8"></span>
              <span class="adv-theme-color" style="background:#cbd5e1"></span>
            </div>
          </div>

          <!-- 配色网格
          <div v-if="advLoadingThemes" class="adv-tpl-loading">
            <div class="adv-skeleton-grid">
              <div v-for="i in 12" :key="i" class="adv-theme-skeleton"></div>
            </div>
          </div>
          <div v-else-if="advThemes.length" class="adv-theme-grid">
            <div
              v-for="theme in advThemes"
              :key="theme.id"
              :class="['adv-theme-card', { selected: advForm.theme_id === String(theme.id) }]"
              @click="advForm.theme_id = String(theme.id)"
            >
              <div class="adv-theme-card__colors">
                <span class="adv-theme-color" :style="{ background: theme.accent1 }"></span>
                <span class="adv-theme-color" :style="{ background: theme.accent2 }"></span>
                <span class="adv-theme-color" :style="{ background: theme.accent3 }"></span>
              </div>
              <span class="adv-theme-card__name">{{ theme.name }}</span>
              <div v-if="advForm.theme_id === String(theme.id)" class="adv-theme-card__check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
            </div>
          </div>
          <div v-else class="adv-tpl-empty">
            <p>暂无配色方案，将使用模板默认色</p>
          </div>

          <div class="adv-step-nav">
            <button class="btn btn--ghost" @click="advStep = 3">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              上一步
            </button>
            <button class="btn btn--primary" @click="advShowConfirm = true">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              确认生成
            </button>
          </div>
        </div>
        <!-- ========== 高级 Step 4: 选择配色（注释保留结束） ========== -->

        <!-- 高级 Step 5: 提交成功 -->
        <div v-if="advStep === 5" class="adv-card adv-step-content">
          <div class="progress-card">
            <div class="progress-done">
              <div class="progress-done__icon">
                <svg width="56" height="56" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#6366f1"/><path d="M7 12l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <h3 class="progress-title">订单提交成功</h3>
              <p class="progress-desc">你的 PPT 订单已成功提交，系统将在后台处理生成</p>
              <div class="progress-order">
                <span class="progress-order__label">订单号</span>
                <span class="progress-order__value">{{ advOrderSn }}</span>
              </div>
              <div class="progress-tip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>下单不扣费，生成完成后可在订单列表免费预览，满意后下载时才计费</span>
              </div>
              <div class="progress-actions">
                <button class="btn btn--ghost" @click="goAdvOrders">查看订单</button>
                <button class="btn btn--primary btn--lg" @click="resetAdvAll">再做一个</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 高级版确认下单弹窗 -->
        <Teleport to="body">
          <Transition name="price-modal">
            <div v-if="advShowConfirm" class="price-modal-mask" @click.self="advShowConfirm = false">
              <div class="confirm-modal adv-confirm-modal">
                <div class="confirm-modal__head">
                  <div class="confirm-modal__title">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2L4 12l8 10 8-10z"/></svg>
                    <span>高级版订单确认</span>
                  </div>
                  <button class="price-modal-close" :disabled="advSubmitting" @click="advShowConfirm = false">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                  </button>
                </div>
                <div class="confirm-modal__body">
                  <!-- 订单信息 -->
                  <div class="confirm-section">
                    <div class="confirm-section__head">
                      <span class="confirm-section__icon primary">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6z"/></svg>
                      </span>
                      <span class="confirm-section__title">订单信息</span>
                    </div>
                    <div class="confirm-info">
                      <div class="confirm-info__row">
                        <span class="confirm-info__label">PPT 标题</span>
                        <span class="confirm-info__value">{{ advForm.title }}</span>
                      </div>
                      <div v-if="advForm.author" class="confirm-info__row">
                        <span class="confirm-info__label">作者</span>
                        <span class="confirm-info__value">{{ advForm.author }}</span>
                      </div>
                      <div class="confirm-info__row">
                        <span class="confirm-info__label">版本</span>
                        <span class="confirm-info__value adv-tag-pro">高级版 PRO</span>
                      </div>
                      <div class="confirm-info__row">
                        <span class="confirm-info__label">大纲内容</span>
                        <span class="confirm-info__value">{{ advForm.outline_content.length }} 字</span>
                      </div>
                    </div>
                  </div>

                  <!-- 模板信息 -->
                  <div class="confirm-section">
                    <div class="confirm-section__head">
                      <span class="confirm-section__icon green">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                      </span>
                      <span class="confirm-section__title">模板与配色</span>
                    </div>
                    <div class="confirm-tpl">
                      <div class="confirm-tpl__cover">
                        <img v-if="advSelectedTemplate?.preview" :src="advSelectedTemplate.preview" alt="" />
                        <div v-else class="confirm-tpl__cover-fallback"></div>
                      </div>
                      <div class="confirm-tpl__info">
                        <span class="confirm-tpl__name">{{ advSelectedTemplate?.category || '通用模板' }}</span>
                        <span class="confirm-tpl__cat">{{ advSelectedTemplate?.style || '默认风格' }}</span>
                      </div>
                    </div>
                    <div v-if="advSelectedTheme" class="adv-confirm-theme">
                      <span class="adv-confirm-theme__label">配色方案</span>
                      <div class="adv-confirm-theme__colors">
                        <span :style="{ background: advSelectedTheme.accent1 }"></span>
                        <span :style="{ background: advSelectedTheme.accent2 }"></span>
                        <span :style="{ background: advSelectedTheme.accent3 }"></span>
                        <span class="adv-confirm-theme__name">{{ advSelectedTheme.name }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- 价格信息 -->
                  <div class="confirm-section">
                    <div class="confirm-section__head">
                      <span class="confirm-section__icon price">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                      </span>
                      <span class="confirm-section__title">价格明细</span>
                    </div>
                    <div class="confirm-price">
                      <div class="confirm-price__row">
                        <span class="confirm-price__label">{{ advPriceData.model_name }} · 高级版</span>
                        <span class="confirm-price__unit">按篇</span>
                      </div>
                      <div class="confirm-price__divider"></div>
                      <div class="confirm-price__row confirm-price__row--total">
                        <span class="confirm-price__label">应付金额</span>
                        <span class="confirm-price__amount">{{ pptPriceLabel(advPriceData.price) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="confirm-tip">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    <span>下单后不扣费，生成完成后可在订单列表免费预览，满意后下载时才计费。</span>
                  </div>
                </div>
                <div class="confirm-modal__footer">
                  <button class="btn btn--ghost" :disabled="advSubmitting" @click="advShowConfirm = false">取消</button>
                  <button class="btn btn--primary" :disabled="advSubmitting || !priceReady(advPriceData.price)" @click="confirmAdvSubmit">
                    <span v-if="advSubmitting" class="btn-spinner"></span>
                    <span>{{ advSubmitting ? '提交中...' : (priceReady(advPriceData.price) ? `确认下单 ￥${advPriceData.price}` : '价格未获取') }}</span>
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </Teleport>
      </div>

      <!-- 普通版：3步流程 -->
      <template v-else>
        <!-- 步骤指示器 -->
        <nav class="steps">
          <div
            v-for="(s, i) in stepList"
            :key="i"
            :class="['steps__item', { 'steps__item--active': currentStep === s.num, 'steps__item--done': currentStep > s.num }]"
          >
            <span class="steps__num">{{ currentStep > s.num ? '✓' : s.num }}</span>
            <span class="steps__label">{{ s.label }}</span>
            <span v-if="i < stepList.length - 1" class="steps__line" :class="{ 'steps__line--done': currentStep > s.num }"></span>
          </div>
        </nav>

        <!-- Step 1: 输入参数 -->
        <div v-if="currentStep === 1" class="content">
          <div class="form-card">
            <div class="form-header">
              <div class="form-header__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              </div>
              <div class="form-header__text">
                <h3 class="form-title">输入 PPT 主题</h3>
                <p class="form-subtitle">AI 将根据你的主题自动生成结构化 PPT 大纲</p>
              </div>
              <button class="price-btn" @click="showPriceModal = true">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                收费标准
              </button>
            </div>

            <div class="form-body">
              <div class="form-group">
                <label class="form-label">PPT 标题</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="form-input"
                  placeholder="如：人工智能的发展趋势与应用前景"
                  @keyup.enter="handleGenerateOutline"
                />
              </div>

              <div class="form-group">
                <label class="form-label">作者（可选）</label>
                <input
                  v-model="form.author"
                  type="text"
                  class="form-input"
                  placeholder="如：张三"
                />
              </div>

              <div class="form-row">
                <div class="form-group form-group--flex">
                  <label class="form-label">章节数量</label>
                  <select v-model="form.chapterCount" class="form-select">
                    <option v-for="n in [4,5,6,7,8,9,10]" :key="n" :value="n">{{ n }} 个章节</option>
                  </select>
                </div>
                <div class="form-group form-group--flex">
                  <label class="form-label">每章小节数</label>
                  <select v-model="form.sectionRange" class="form-select">
                    <option value="2-4">2-4 个小节</option>
                    <option value="3-5">3-5 个小节</option>
                    <option value="3-6">3-6 个小节</option>
                    <option value="4-6">4-6 个小节</option>
                    <option value="5-8">5-8 个小节</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-footer">
              <button
                class="btn btn--primary btn--lg"
                :disabled="!form.title.trim() || generating"
                @click="handleGenerateOutline"
              >
                <span v-if="generating" class="btn-spinner"></span>
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5z"/><path d="M5 19l1 2 1-2 1 2-1-2 1-2-1 2-1-2-1 2z"/></svg>
                <span>{{ generating ? '正在生成...' : '生成大纲' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Step 2: 大纲生成 + 编辑 -->
        <div v-if="currentStep === 2" class="content">
          <!-- 生成中：流式动态展示 -->
          <div v-if="generating" class="outline-card streaming-mode">
            <div class="card-head">
              <div class="head-title">
                <span class="head-icon stream-orb-wrap">
                  <span class="stream-orb-mini">
                    <span class="orb-core"></span>
                    <span class="orb-ring"></span>
                  </span>
                </span>
                <h3>AI 正在生成大纲<span class="stream-badge">实时</span></h3>
              </div>
              <div class="head-actions">
                <div class="stream-wave">
                  <span></span><span></span><span></span><span></span><span></span>
                </div>
              </div>
            </div>

            <div v-if="streamOutline.chapters.length" class="outline-tree streaming">
              <div
                v-for="(chapter, cIdx) in streamOutline.chapters"
                :key="cIdx"
                class="chapter stream-fade-in"
              >
                <div class="chapter-title">
                  <span class="stream-chapter-num">{{ cIdx + 1 }}</span>
                  <h4>{{ chapter.title }}</h4>
                </div>
                <div v-if="chapter.sections && chapter.sections.length" class="sections">
                  <div
                    v-for="(section, sIdx) in chapter.sections"
                    :key="sIdx"
                    class="section stream-fade-in"
                  >
                    <div class="section-main">
                      <span class="stream-section-dot"></span>
                      <span>{{ section.title }}</span>
                    </div>
                    <div v-if="section.items && section.items.length" class="section-items">
                      <div
                        v-for="(item, iIdx) in section.items"
                        :key="iIdx"
                        class="section-item stream-fade-in"
                      >
                        <span class="stream-item-dot"></span>
                        <span>{{ item }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="stream-skeleton">
                  <div class="skeleton-line"></div>
                  <div class="skeleton-line short"></div>
                </div>
              </div>
              <div class="stream-typing-row">
                <span class="stream-typing-cursor"></span>
                <span class="stream-typing-text">{{ genStepText }}</span>
              </div>
            </div>

            <div v-else class="stream-empty">
              <div class="stream-ripple">
                <span class="orb-core"></span>
                <span class="ripple-ring"></span>
                <span class="ripple-ring d1"></span>
                <span class="ripple-ring d2"></span>
              </div>
              <p>{{ genStepText }}</p>
            </div>

            <div class="stream-footer-bar">
              <div class="stream-shimmer-bar"></div>
              <span class="stream-tip">内容正在实时生成，请稍候</span>
            </div>
          </div>

          <!-- 生成完成：结构化大纲展示 -->
          <div v-else class="outline-card">
            <div class="outline-header">
              <div class="outline-header__left">
                <div class="outline-header__icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <h3 class="outline-title">大纲已生成</h3>
                  <p class="outline-subtitle">{{ streamOutline.chapters.length }} 个章节 · 可编辑后继续</p>
                </div>
              </div>
              <div class="outline-header__right">
                <div class="outline-header__badge">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5z"/></svg>
                  <span>AI 生成</span>
                </div>
                <button class="edit-toggle-btn" :class="{ active: editMode }" @click="toggleEditMode">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  {{ editMode ? '完成编辑' : '编辑大纲' }}
                </button>
              </div>
            </div>

            <!-- 结构化大纲（默认视图） -->
            <div v-if="!editMode" class="outline-tree">
              <div
                v-for="(chapter, cIdx) in parsedOutline.chapters"
                :key="cIdx"
                class="chapter-card"
              >
                <div class="chapter-card__head" @click="toggleChapter(cIdx)">
                  <span class="chapter-card__num">{{ cIdx + 1 }}</span>
                  <span class="chapter-card__title">{{ chapter.title }}</span>
                  <span class="chapter-card__count">{{ (chapter.sections || []).length }} 节</span>
                  <svg class="chapter-card__arrow" :class="{ expanded: expandedChapters.includes(cIdx) }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <Transition name="chapter-expand">
                  <div v-if="expandedChapters.includes(cIdx)" class="chapter-card__body">
                    <div
                      v-for="(section, sIdx) in (chapter.sections || [])"
                      :key="sIdx"
                      class="section-block"
                    >
                      <div class="section-block__title">
                        <span class="section-block__dot"></span>
                        <span>{{ section.title }}</span>
                      </div>
                      <div v-if="section.items && section.items.length" class="section-block__items">
                        <div v-for="(item, iIdx) in section.items" :key="iIdx" class="section-block__item">
                          <span class="section-block__item-dot"></span>
                          <span>{{ item }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- 源码编辑模式 -->
            <textarea
              v-else
              v-model="outlineContent"
              class="outline-editor"
              placeholder="大纲内容将在这里显示..."
              spellcheck="false"
            ></textarea>

            <div class="step-nav">
              <button class="btn btn--ghost" @click="currentStep = 1">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                上一步
              </button>
              <button class="btn btn--primary" @click="goToTemplateStep">
                下一步：选择模板
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Step 3: 选择模板 -->
        <div v-if="currentStep === 3" class="content">
          <div class="template-section">
            <!-- 分类筛选 -->
            <div class="tpl-filters">
              <button
                :class="['tpl-cat', { active: selectedCategoryId === 0 }]"
                @click="selectedCategoryId = 0"
              >全部</button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                :class="['tpl-cat', { active: selectedCategoryId === cat.id }]"
                @click="selectedCategoryId = cat.id"
              >{{ cat.name || cat.category_name }}</button>
            </div>

            <!-- 模板网格 -->
            <div v-if="loadingTemplates" class="tpl-loading">
              <div class="spinner"></div>
              <span>加载模板中...</span>
            </div>
            <div v-else-if="filteredTemplates.length === 0" class="tpl-empty">暂无模板</div>
            <div v-else class="tpl-grid">
              <div
                v-for="tpl in filteredTemplates"
                :key="tpl.id"
                :class="['tpl-card', { selected: selectedTemplateId === tpl.id }]"
                @click="selectedTemplateId = tpl.id"
              >
                <div class="tpl-card__cover">
                  <img :src="tpl.preview_url" :alt="tpl.template_name" loading="lazy" @error="onCoverError" />
                </div>
                <div class="tpl-card__info">
                  <span class="tpl-card__name">{{ tpl.template_name }}</span>
                  <span class="tpl-card__cat">{{ tpl.category }}</span>
                </div>
                <div v-if="selectedTemplateId === tpl.id" class="tpl-card__check">
                  <svg width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#14b8a6"/><path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" fill="none"/></svg>
                </div>
              </div>
            </div>

            <!-- 分页 -->
            <div v-if="totalPages > 1" class="tpl-pagination">
              <button :disabled="templatePage === 1" @click="templatePage--; loadTemplates()">上一页</button>
              <span class="tpl-pagination__info">{{ templatePage }} / {{ totalPages }}</span>
              <button :disabled="templatePage >= totalPages" @click="templatePage++; loadTemplates()">下一页</button>
            </div>

            <div class="step-nav">
              <button class="btn btn--ghost" @click="currentStep = 2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                上一步
              </button>
              <button
                class="btn btn--primary"
                :disabled="!selectedTemplateId || submitting"
                @click="handleSubmitOrder"
              >
                <span v-if="submitting" class="btn-spinner"></span>
                <span>{{ submitting ? '提交中...' : '提交订单' }}</span>
                <svg v-if="!submitting" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Step 4: 提交成功 -->
        <div v-if="currentStep === 4" class="content">
          <div class="progress-card">
            <div class="progress-done">
              <div class="progress-done__icon">
                <svg width="56" height="56" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#14b8a6"/><path d="M7 12l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <h3 class="progress-title">订单提交成功</h3>
              <p class="progress-desc">你的 PPT 订单已成功提交，系统将在后台处理生成</p>
              <div class="progress-order">
                <span class="progress-order__label">订单号</span>
                <span class="progress-order__value">{{ orderSn }}</span>
              </div>
              <div class="progress-tip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>PPT 生成由后台异步处理，完成后可在订单列表查看下载</span>
              </div>
              <div class="progress-actions">
                <button class="btn btn--primary btn--lg" @click="resetAll">再做一个</button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </ClientOnly>

    <!-- 收费标准弹窗 -->
    <Teleport to="body">
      <Transition name="price-modal">
        <div v-if="showPriceModal" class="price-modal-mask" @click.self="showPriceModal = false">
          <div class="price-modal">
            <div class="price-modal-head">
              <div class="price-modal-title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                <span>收费标准与价格明细</span>
              </div>
              <button class="price-modal-close" @click="showPriceModal = false">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>
            <div class="price-modal-body">
              <!-- PPT 生成价格 -->
              <div class="price-section">
                <div class="price-section-head">
                  <span class="price-section-icon primary">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                  </span>
                  <span class="price-section-title">AIPPT 生成（按篇收费）</span>
                  <span class="price-section-tag">核心服务</span>
                </div>
                <div class="ppt-price-card">
                  <div class="ppt-price-card__left">
                    <span class="ppt-price-card__name">{{ priceData.model_name }}</span>
                    <div class="ppt-price-card__tags">
                      <span v-if="priceData.model_tag" class="ppt-tag">{{ priceData.model_tag }}</span>
                      <span class="ppt-tag">普通版</span>
                    </div>
                  </div>
                  <div class="ppt-price-card__right">
                    <span class="ppt-price-card__unit">按篇</span>
                    <span class="ppt-price-card__price">{{ pptPriceLabel(priceData.price) }}</span>
                  </div>
                </div>
                <div class="ppt-price-card ppt-price-card--pro">
                  <div class="ppt-price-card__left">
                    <span class="ppt-price-card__name">{{ advPriceData.model_name }}</span>
                    <div class="ppt-price-card__tags">
                      <span v-if="advPriceData.model_tag" class="ppt-tag ppt-tag--pro">{{ advPriceData.model_tag }}</span>
                      <span class="ppt-tag ppt-tag--pro">高级版</span>
                    </div>
                  </div>
                  <div class="ppt-price-card__right">
                    <span class="ppt-price-card__unit">按篇</span>
                    <span class="ppt-price-card__price">{{ pptPriceLabel(advPriceData.price) }}</span>
                  </div>
                </div>
              </div>

              <!-- 后付费模式提示 -->
              <div class="price-highlight">
                <div class="price-highlight__icon">
                  <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2z"/></svg>
                </div>
                <div class="price-highlight__content">
                  <span class="price-highlight__title">后付费模式 · 满意再付费</span>
                  <span class="price-highlight__desc">下单生成 PPT 不扣费，免费预览满意后，下载时才扣费</span>
                </div>
              </div>

              <!-- 服务说明 -->
              <div class="price-section">
                <div class="price-section-head">
                  <span class="price-section-icon green">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                  </span>
                  <span class="price-section-title">服务说明</span>
                </div>
                <div class="price-features">
                  <div class="price-feature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>AI 自动生成结构化 PPT 大纲</span>
                  </div>
                  <div class="price-feature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>支持自定义章节数量与小节深度</span>
                  </div>
                  <div class="price-feature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>多种 PPT 模板可选，自动套用样式</span>
                  </div>
                  <div class="price-feature">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>后台异步生成，完成后可下载</span>
                  </div>
                </div>
              </div>

              <!-- 说明 -->
              <div class="price-note">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <span>以上价格仅供参考，最终以订单提交时显示为准。</span>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- 确认下单弹窗 -->
    <Teleport to="body">
      <Transition name="price-modal">
        <div v-if="showConfirmModal" class="price-modal-mask" @click.self="showConfirmModal = false">
          <div class="confirm-modal">
            <div class="confirm-modal__head">
              <div class="confirm-modal__title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                <span>确认订单信息</span>
              </div>
              <button class="price-modal-close" :disabled="submitting" @click="showConfirmModal = false">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>
            <div class="confirm-modal__body">
              <!-- 订单信息 -->
              <div class="confirm-section">
                <div class="confirm-section__head">
                  <span class="confirm-section__icon primary">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                  </span>
                  <span class="confirm-section__title">订单信息</span>
                </div>
                <div class="confirm-info">
                  <div class="confirm-info__row">
                    <span class="confirm-info__label">PPT 标题</span>
                    <span class="confirm-info__value">{{ form.title }}</span>
                  </div>
                  <div v-if="form.author" class="confirm-info__row">
                    <span class="confirm-info__label">作者</span>
                    <span class="confirm-info__value">{{ form.author }}</span>
                  </div>
                  <div class="confirm-info__row">
                    <span class="confirm-info__label">章节数量</span>
                    <span class="confirm-info__value">{{ form.chapterCount }} 章</span>
                  </div>
                  <div class="confirm-info__row">
                    <span class="confirm-info__label">每章小节</span>
                    <span class="confirm-info__value">{{ form.sectionRange }} 个</span>
                  </div>
                  <div class="confirm-info__row">
                    <span class="confirm-info__label">大纲内容</span>
                    <span class="confirm-info__value">{{ outlineContent.length }} 字</span>
                  </div>
                </div>
              </div>

              <!-- 模板信息 -->
              <div class="confirm-section">
                <div class="confirm-section__head">
                  <span class="confirm-section__icon green">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                  </span>
                  <span class="confirm-section__title">选择模板</span>
                </div>
                <div v-if="selectedTemplate" class="confirm-tpl">
                  <div class="confirm-tpl__cover">
                    <img v-if="selectedTemplate.preview_url" :src="selectedTemplate.preview_url" :alt="selectedTemplate.template_name" @error="onCoverError" />
                    <div v-else class="confirm-tpl__cover-fallback"></div>
                  </div>
                  <div class="confirm-tpl__info">
                    <span class="confirm-tpl__name">{{ selectedTemplate.template_name }}</span>
                    <span v-if="selectedTemplate.category" class="confirm-tpl__cat">{{ selectedTemplate.category }}</span>
                  </div>
                </div>
              </div>

              <!-- 价格信息 -->
              <div class="confirm-section">
                <div class="confirm-section__head">
                  <span class="confirm-section__icon price">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                  </span>
                  <span class="confirm-section__title">价格明细</span>
                </div>
                <div class="confirm-price">
                  <div class="confirm-price__row">
                    <span class="confirm-price__label">{{ priceData.model_name }}</span>
                    <span class="confirm-price__unit">按篇</span>
                  </div>
                  <div class="confirm-price__divider"></div>
                  <div class="confirm-price__row confirm-price__row--total">
                    <span class="confirm-price__label">应付金额</span>
                    <span class="confirm-price__amount">{{ pptPriceLabel(priceData.price) }}</span>
                  </div>
                </div>
              </div>

              <!-- 说明 -->
              <div class="confirm-tip">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <span>PPT 为后付费模式：下单不扣费，生成完成后可先免费预览内容，确认满意后下载时才扣除费用。</span>
              </div>
            </div>
            <div class="confirm-modal__footer">
              <button class="btn btn--ghost" :disabled="submitting" @click="showConfirmModal = false">取消</button>
              <button class="btn btn--primary" :disabled="submitting || !priceReady(priceData.price)" @click="confirmSubmitOrder">
                <span v-if="submitting" class="btn-spinner"></span>
                <span>{{ submitting ? '提交中...' : (priceReady(priceData.price) ? `确认下单 ￥${priceData.price}` : '价格未获取') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

const api = useApi()
const toast = useToast()

// 版本切换（localStorage 持久化，刷新页面保持选中）
const version = ref('standard')

onMounted(() => {
  const saved = localStorage.getItem('aippt_version')
  if (saved === 'advanced' || saved === 'standard') {
    version.value = saved
  }
})

watch(() => version.value, (v) => {
  if (import.meta.client) {
    localStorage.setItem('aippt_version', v)
  }
})

// 收费标准弹窗
const showPriceModal = ref(false)
// price=0 表示价格未获取（上游不可用）：展示「--」并禁止下单，与实扣 quoteFromMain 同源
const priceData = ref({
  price: 0,
  model_name: 'PPT默认模型',
  model_tag: '',
  is_advanced: 0,
})
// 高级版价格配置
const advPriceData = ref({
  price: 0,
  model_name: 'PPT高级模型',
  model_tag: '高级版',
  is_advanced: 1,
})

// 价格展示：0/缺失显示「--」
function pptPriceLabel(p) {
  return Number(p) > 0 ? `￥${p}` : '--'
}
function priceReady(p) {
  return Number(p) > 0
}

// 加载价格配置
async function loadPriceConfig() {
  try {
    const res = await api.get('/api/ppt/getPriceConfig')
    if (res.ok) {
      // 普通版（兜底 0=价格未获取，展示「--」并禁止下单）
      priceData.value = {
        price: res.data.standard?.price ?? 0,
        model_name: res.data.standard?.model_name ?? 'PPT默认模型',
        model_tag: res.data.standard?.model_tag ?? '',
        is_advanced: 0,
      }
      // 高级版
      advPriceData.value = {
        price: res.data.advanced?.price ?? 0,
        model_name: res.data.advanced?.model_name ?? 'PPT高级模型',
        model_tag: res.data.advanced?.model_tag ?? '高级版',
        is_advanced: 1,
      }
    }
  } catch (e) {
    // 静默失败，使用默认值
  }
}

onMounted(() => {
  loadPriceConfig()
})

// ===================================================================
// 高级版 PPT 生成流程
// ===================================================================

const advStep = ref(1)
const advStepList = [
  { label: '输入主题' },
  { label: '编辑大纲' },
  { label: '选择模板' },
  // 第四步「选择配色」已停用（先注释保留，暂不删除）
  // { label: '选择配色' },
  { label: '生成下载' },
]
const advForm = reactive({
  title: '',
  author: '',
  tpl_uid: '',
  theme_id: '',
  outline_content: '',
})
const advTemplates = ref([])
const advThemes = ref([])
const advLoadingTemplates = ref(false)
const advLoadingThemes = ref(false)
const advTplKeyword = ref('')
const advShowConfirm = ref(false)
const advSubmitting = ref(false)
const advOrderSn = ref('')
const advTaskId = ref('')
const advTaskInfo = ref({ status: 'pending', progress: 0, message: '' })
let advPollTimer = null
let advAbortController = null

// 高级版大纲生成状态
const advGenerating = ref(false)
const advStreamText = ref('')
const advEditMode = ref(false)
const advExpandedChapters = ref([])
const advGenStepText = ref('正在连接 AI 服务…')

const advStreamOutline = computed(() => parsePptOutline(advStreamText.value))
const advParsedOutline = computed(() => parsePptOutline(advForm.outline_content))

// 模板分类
const advCategories = ref([])
const advSelectedCategory = ref('论文答辩')

// 模板分页
const advTplPage = ref(1)
const advTplPageSize = 20
const advTplTotal = ref(0)
const advTplPages = ref(1)

// 加载分类列表（高级版专用：主站 category_use_name 聚合，含各分类模板数量）
async function loadAdvCategories() {
  try {
    const res = await api.get('/api/ppt/getAdvancedTemplateCategories')
    if (res.ok) {
      advCategories.value = res.data || []
      // 默认分类不在新分类列表中时回退到第一个
      if (advCategories.value.length && !advCategories.value.some(c => c.name === advSelectedCategory.value)) {
        advSelectedCategory.value = advCategories.value[0].name
      }
    }
  } catch (e) {}
}

// 选择分类
function selectAdvCategory(name) {
  advSelectedCategory.value = name
  advTplPage.value = 1
  loadAdvTemplates()
}

// 当前选中的模板对象
const advSelectedTemplate = computed(() => {
  return advTemplates.value.find(t => t.tpl_uid === advForm.tpl_uid) || null
})

// 当前选中的配色对象
const advSelectedTheme = computed(() => {
  if (!advForm.theme_id) return null
  return advThemes.value.find(t => String(t.id) === advForm.theme_id) || null
})

// Step 1 → 2: 进入大纲生成
function advGoStep2() {
  if (!advForm.title.trim()) {
    toast.error('请输入 PPT 标题')
    return
  }
  advStep.value = 2
  // 自动开始生成大纲
  if (!advForm.outline_content) {
    handleAdvGenerateOutline()
  }
}

// 高级版大纲生成（SSE 流式）
async function handleAdvGenerateOutline() {
  advGenerating.value = true
  advStreamText.value = ''
  advForm.outline_content = ''
  advEditMode.value = false
  advExpandedChapters.value = []
  advGenStepText.value = '正在连接 AI 服务…'

  // 取消之前的 SSE 请求（如果存在）
  if (advAbortController) {
    advAbortController.abort()
  }
  advAbortController = new AbortController()

  const stageTexts = [
    '正在检索相关素材…',
    '正在构建章节逻辑结构…',
    '正在生成各小节内容…',
    '正在优化大纲层次与衔接…',
  ]

  let token = ''
  try {
    let raw = localStorage.getItem('aidian_auth_v2')
    if (!raw) {
      const oldRaw = localStorage.getItem('aidian_auth')
      if (oldRaw) { raw = oldRaw; try { localStorage.removeItem('aidian_auth') } catch (e) {} }
    }
    if (raw) token = JSON.parse(raw)?.token || ''
  } catch (e) {}

  try {
    const response = await fetch('/api/ppt/generateAdvancedOutlineStream', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
        ...(token ? { token } : {}),
      },
      body: JSON.stringify({
        title: advForm.title.trim(),
      }),
      signal: advAbortController.signal,
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('application/json')) {
      const errJson = await response.json()
      if (errJson.code === -1) {
        toast.warning('登录已过期，请重新登录')
        localStorage.removeItem('aidian_auth')
        setTimeout(() => useLoginModal().openIfNeeded(), 1200)
        return
      }
      throw new Error(errJson.msg || '生成失败')
    }

    if (!response.body || typeof response.body.getReader !== 'function') {
      const rawText = await response.text()
      const fullText = parseSseFullText(rawText)
      if (!fullText) throw new Error('未收到任何内容')
      finishAdvOutline(fullText)
      return
    }

    const reader = response.body.getReader()
    const decoder = new TextDecoder('utf-8')
    let fullText = ''
    let buffer = ''
    let firstChunkReceived = false

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      buffer += decoder.decode(value, { stream: true })

      while (buffer.includes('\n\n')) {
        const idx = buffer.indexOf('\n\n')
        const event = buffer.slice(0, idx)
        buffer = buffer.slice(idx + 2)

        const lines = event.split('\n')
        for (const line of lines) {
          if (!line.startsWith('data:')) continue
          const jsonStr = line.slice(5).trim()
          if (!jsonStr) continue

          try {
            const data = JSON.parse(jsonStr)
            if (data.error) throw new Error(data.error)
            if (data.done) {
              advGenStepText.value = '生成完成，正在加载大纲…'
              if (fullText) finishAdvOutline(fullText)
              return
            }
            if (data.content) {
              if (!firstChunkReceived) {
                firstChunkReceived = true
                advGenStepText.value = stageTexts[0]
              }
              fullText += data.content
              advStreamText.value += data.content
              const stageIdx = Math.min(Math.floor(fullText.length / 200), stageTexts.length - 1)
              advGenStepText.value = stageTexts[stageIdx]
            }
          } catch (parseErr) {}
        }
      }
    }

    if (buffer.trim()) {
      const lines = buffer.split('\n')
      for (const line of lines) {
        if (!line.startsWith('data:')) continue
        const jsonStr = line.slice(5).trim()
        if (!jsonStr) continue
        try {
          const data = JSON.parse(jsonStr)
          if (data.content) {
            fullText += data.content
            advStreamText.value += data.content
          }
        } catch (e) {}
      }
    }

    if (fullText) {
      finishAdvOutline(fullText)
    } else {
      throw new Error('未收到任何内容')
    }
  } catch (e) {
    // 用户主动取消或组件卸载时不提示错误、不降级
    if (e && e.name === 'AbortError') {
      advGenerating.value = false
      return
    }
    console.error('[aippt-adv] 流式生成失败，降级到同步接口:', e)
    try {
      const res = await api.post('/api/ppt/generateAdvancedOutline', {
        title: advForm.title.trim(),
      })
      if (res.ok) {
        finishAdvOutline(res.data.outline || '')
      } else {
        toast.error(res.msg || '大纲生成失败')
        advGenerating.value = false
      }
    } catch (e2) {
      toast.error('大纲生成失败')
      advGenerating.value = false
    }
  } finally {
    advAbortController = null
  }
}

// 完成高级版大纲生成
function finishAdvOutline(fullText) {
  advGenStepText.value = '生成完成，正在加载大纲…'
  advForm.outline_content = fullText
  advExpandedChapters.value = [0]
  setTimeout(() => {
    advGenerating.value = false
    toast.success('大纲生成成功')
  }, 400)
}

// 切换编辑模式
function toggleAdvEditMode() {
  advEditMode.value = !advEditMode.value
}

// 展开/折叠章节
function toggleAdvChapter(idx) {
  const i = advExpandedChapters.value.indexOf(idx)
  if (i >= 0) {
    advExpandedChapters.value.splice(i, 1)
  } else {
    advExpandedChapters.value.push(idx)
  }
}

// 加载模板列表
async function loadAdvTemplates() {
  advLoadingTemplates.value = true
  try {
    const res = await api.post('/api/ppt/getAdvancedTemplates', {
      category: advSelectedCategory.value,
      keyword: advTplKeyword.value,
      page: advTplPage.value,
      page_size: advTplPageSize,
    })
    if (res.ok) {
      advTemplates.value = res.data.templates || []
      advTplTotal.value = res.data.total || 0
      advTplPages.value = res.data.pages || 1
    } else {
      toast.error(res.msg || '获取模板失败')
    }
  } catch (e) {
    toast.error('获取模板失败')
  } finally {
    advLoadingTemplates.value = false
  }
}

// 搜索时重置页码
function searchAdvTemplates() {
  advTplPage.value = 1
  loadAdvTemplates()
}

// 翻页
function advTplGoPage(p) {
  if (p < 1 || p > advTplPages.value || p === advTplPage.value) return
  advTplPage.value = p
  loadAdvTemplates()
  // 滚动到模板列表顶部
  if (import.meta.client) {
    document.querySelector('.adv-tpl-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// 分页页码列表（带省略号）
const advTplPageList = computed(() => {
  const cur = advTplPage.value
  const total = advTplPages.value
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  const list = [1]
  if (cur > 4) list.push('...')
  const start = Math.max(2, cur - 1)
  const end = Math.min(total - 1, cur + 1)
  for (let i = start; i <= end; i++) list.push(i)
  if (cur < total - 3) list.push('...')
  list.push(total)
  return list
})

// 选择模板
function selectAdvTemplate(tpl) {
  advForm.tpl_uid = tpl.tpl_uid
}

// 模板封面加载失败处理
function onAdvCoverError(e, tpl) {
  tpl.preview = ''
}

// Step 2 → 3: 大纲确认后进入模板选择
function advGoStep3() {
  if (!advForm.outline_content.trim()) {
    toast.error('大纲内容不能为空')
    return
  }
  advStep.value = 3
  // 先确保分类加载完成（可能触发默认分类回退），再按当前分类加载模板，避免 tab 与数据错位
  const ensureCats = advCategories.value.length ? Promise.resolve() : loadAdvCategories()
  Promise.resolve(ensureCats).then(() => {
    if (!advTemplates.value.length) {
      loadAdvTemplates()
    }
  })
}

// ===== 第四步「选择配色」已停用（先注释保留，暂不删除） =====
// Step 3 → 4: 模板确认后进入配色选择
// function advGoStep4() {
//   if (!advForm.tpl_uid) {
//     toast.error('请选择一个模板')
//     return
//   }
//   advStep.value = 4
//   if (!advThemes.value.length) {
//     loadAdvThemes()
//   }
// }
//
// // 加载配色方案
// async function loadAdvThemes() {
//   advLoadingThemes.value = true
//   try {
//     const res = await api.get('/api/ppt/getAdvancedThemes')
//     if (res.ok) {
//       advThemes.value = res.data.themes || []
//     }
//   } catch (e) {
//     // 静默失败
//   } finally {
//     advLoadingThemes.value = false
//   }
// }
// ===== 第四步「选择配色」注释保留结束 =====

// 确认下单
async function confirmAdvSubmit() {
  if (!priceReady(advPriceData.value.price)) {
    toast.error('价格获取失败，请刷新重试')
    return
  }
  advSubmitting.value = true
  try {
    const res = await api.post('/api/ppt/createAdvancedOrder', {
      title: advForm.title.trim(),
      tpl_uid: advForm.tpl_uid,
      theme_id: advForm.theme_id,
      outline_content: advForm.outline_content || '',
      author: advForm.author || '',
    })
    if (!res.ok) {
      toast.error(res.msg || '创建订单失败')
      return
    }
    advOrderSn.value = res.data.order_sn || ''
    advShowConfirm.value = false
    advStep.value = 5
    await nextTick()
    toast.success('订单提交成功')
  } catch (e) {
    toast.error('提交失败')
  } finally {
    advSubmitting.value = false
  }
}

// 查看订单
function goAdvOrders() {
  navigateTo('/pc/orders/ppt')
}

// 重置高级版（再做一个）
function resetAdvAll() {
  advStep.value = 1
  advForm.title = ''
  advForm.author = ''
  advForm.tpl_uid = ''
  advForm.theme_id = ''
  advForm.outline_content = ''
  advGenerating.value = false
  advStreamText.value = ''
  advEditMode.value = false
  advExpandedChapters.value = []
  advTemplates.value = []
  advThemes.value = []
  advCategories.value = []
  advSelectedCategory.value = '论文答辩'
  advTplPage.value = 1
  advTplTotal.value = 0
  advTplPages.value = 1
  advOrderSn.value = ''
}

// 切换到高级版时重置
watch(() => version.value, (v) => {
  if (v === 'advanced') {
    advStep.value = 1
    advForm.title = ''
    advForm.author = ''
    advForm.tpl_uid = ''
    advForm.theme_id = ''
    advForm.outline_content = ''
    advGenerating.value = false
    advStreamText.value = ''
    advEditMode.value = false
    advExpandedChapters.value = []
    advTemplates.value = []
    advThemes.value = []
    advCategories.value = []
    advSelectedCategory.value = '论文答辩'
    advTplPage.value = 1
    advTplTotal.value = 0
    advTplPages.value = 1
    advTaskInfo.value = { status: 'pending', progress: 0, message: '' }
  }
})

// 步骤
const currentStep = ref(1)
const stepList = [
  { num: 1, label: '输入主题' },
  { num: 2, label: '编辑大纲' },
  { num: 3, label: '选择模板' },
  { num: 4, label: '提交订单' },
]

// 表单
const form = reactive({
  title: '',
  author: '',
  chapterCount: 6,
  sectionRange: '3-6',
})

// 大纲
const generating = ref(false)
const outlineContent = ref('')
const streamText = ref('')
const editMode = ref(false)
const expandedChapters = ref([])
const genStepText = ref('正在连接 AI 服务…')

// 流式实时解析的大纲（生成中展示）
const streamOutline = computed(() => parsePptOutline(streamText.value))

// 生成完成后的结构化大纲（从完整 markdown 解析）
const parsedOutline = computed(() => parsePptOutline(outlineContent.value))

// PPT 大纲 markdown 解析：4 级结构（主标题 / 章 / 节 / 内容项）
function parsePptOutline(md) {
  if (!md) return { mainTitle: '', chapters: [] }
  const lines = md.split('\n')
  const chapters = []
  let mainTitle = ''
  let currentChapter = null
  let currentSection = null

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim()
    if (!line) continue

    // 一级标题：# 主标题
    if (line.startsWith('# ') && !line.startsWith('## ')) {
      mainTitle = line.slice(2).trim().replace(/[*`]/g, '')
    }
    // 二级标题：## 章标题
    else if (line.startsWith('## ') && !line.startsWith('### ')) {
      const raw = line.slice(3).trim()
      currentChapter = {
        title: stripMdNumber(raw),
        sections: []
      }
      chapters.push(currentChapter)
      currentSection = null
    }
    // 三级标题：### 节标题
    else if (line.startsWith('### ') && !line.startsWith('#### ')) {
      const raw = line.slice(4).trim()
      if (currentChapter) {
        currentSection = {
          title: stripMdNumber(raw),
          items: []
        }
        currentChapter.sections.push(currentSection)
      }
    }
    // 四级标题：#### 要点标题 | 描述
    else if (line.startsWith('#### ')) {
      const raw = line.slice(5).trim()
      // 拆分 "标题 | 描述" 格式
      let title = raw
      let desc = ''
      if (raw.includes('|')) {
        const parts = raw.split('|', 2)
        title = parts[0].trim()
        desc = parts[1].trim()
      }
      title = title.replace(/[*`]/g, '').trim()
      if (!title) continue
      const itemText = desc ? `${title}：${desc}` : title
      if (currentSection) {
        currentSection.items.push(itemText)
      } else if (currentChapter) {
        if (!currentChapter.sections.length) {
          currentChapter.sections.push({ title: '概述', items: [] })
          currentSection = currentChapter.sections[0]
        }
        currentSection.items.push(itemText)
      }
    }
    // 列表项或描述文字 → 内容项
    else {
      let text = line
      // 去除列表标记
      if (text.startsWith('- ') || text.startsWith('* ')) {
        text = text.slice(2)
      } else if (/^\d+[.)]\s/.test(text)) {
        text = text.replace(/^\d+[.)]\s*/, '')
      }
      text = text.replace(/[*`]/g, '').trim()
      if (!text) continue

      if (currentSection) {
        currentSection.items.push(text)
      } else if (currentChapter) {
        // 章标题下的描述文字
        if (!currentChapter.sections.length) {
          currentChapter.sections.push({ title: '概述', items: [text] })
        }
      }
    }
  }
  return { mainTitle, chapters }
}

// 去除 markdown 标题中的序号前缀（如 "第一章 " "1. " "一、"）
function stripMdNumber(text) {
  return text
    .replace(/^(第[一二三四五六七八九十百]+[章节部分]|[一二三四五六七八九十]+[、.．]|\d+[.．]\d*|\d+[、.．])\s*/, '')
    .replace(/[*`]/g, '')
    .trim()
}

// 切换章节展开
function toggleChapter(idx) {
  const i = expandedChapters.value.indexOf(idx)
  if (i >= 0) {
    expandedChapters.value.splice(i, 1)
  } else {
    expandedChapters.value.push(idx)
  }
}

// 切换编辑模式
function toggleEditMode() {
  editMode.value = !editMode.value
}

// 模板
const categories = ref([])
const templates = ref([])
const loadingTemplates = ref(false)
const selectedCategoryId = ref(0)
const selectedTemplateId = ref(null)
const templatePage = ref(1)
const templateLimit = 20
const templateTotal = ref(0)
const totalPages = computed(() => Math.ceil(templateTotal.value / templateLimit) || 1)

// 提交
const submitting = ref(false)
const orderSn = ref('')
const showConfirmModal = ref(false)
let outlineAbortController = null

// 当前选中的模板对象（用于确认弹窗展示）
const selectedTemplate = computed(() => {
  if (!selectedTemplateId.value) return null
  return templates.value.find(t => t.id === selectedTemplateId.value) || null
})

// 过滤后的模板（按分类）
const filteredTemplates = computed(() => {
  if (selectedCategoryId.value === 0) return templates.value
  const cat = categories.value.find(c => c.id === selectedCategoryId.value)
  if (!cat) return templates.value
  return templates.value.filter(t => t.category === cat.category_name)
})

// 生成大纲（SSE 流式）
async function handleGenerateOutline() {
  if (!form.title.trim()) {
    toast.error('请输入 PPT 标题')
    return
  }

  // 重置状态
  generating.value = true
  streamText.value = ''
  outlineContent.value = ''
  editMode.value = false
  expandedChapters.value = []
  genStepText.value = '正在连接 AI 服务…'
  currentStep.value = 2

  // 取消之前的 SSE 请求（如果存在）
  if (outlineAbortController) {
    outlineAbortController.abort()
  }
  outlineAbortController = new AbortController()

  const stageTexts = [
    '正在检索相关素材…',
    '正在构建章节逻辑结构…',
    '正在生成各小节内容…',
    '正在优化大纲层次与衔接…',
  ]

  // 读取 token
  let token = ''
  try {
    let raw = localStorage.getItem('aidian_auth_v2')
    if (!raw) {
      const oldRaw = localStorage.getItem('aidian_auth')
      if (oldRaw) { raw = oldRaw; try { localStorage.removeItem('aidian_auth') } catch (e) {} }
    }
    if (raw) token = JSON.parse(raw)?.token || ''
  } catch (e) {}

  try {
    const response = await fetch('/api/ppt/generatePptOutlineStream', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
        ...(token ? { token } : {}),
      },
      body: JSON.stringify({
        title: form.title.trim(),
        chapterCount: form.chapterCount,
        sectionRange: form.sectionRange,
      }),
      signal: outlineAbortController.signal,
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    // 检测响应类型：若后端返回 JSON（如登录过期错误），直接处理
    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('application/json')) {
      const errJson = await response.json()
      if (errJson.code === -1) {
        toast.warning('登录已过期，请重新登录')
        localStorage.removeItem('aidian_auth')
        setTimeout(() => useLoginModal().openIfNeeded(), 1200)
        return
      }
      throw new Error(errJson.msg || '生成失败')
    }

    // 检查是否支持流式读取
    if (!response.body || typeof response.body.getReader !== 'function') {
      // 降级：直接读取完整响应文本
      const rawText = await response.text()
      const fullText = parseSseFullText(rawText)
      if (!fullText) throw new Error('未收到任何内容')
      finishOutline(fullText)
      return
    }

    const reader = response.body.getReader()
    const decoder = new TextDecoder('utf-8')
    let fullText = ''
    let buffer = ''
    let firstChunkReceived = false

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      buffer += decoder.decode(value, { stream: true })

      // 解析 SSE 事件（以 \n\n 分隔）
      while (buffer.includes('\n\n')) {
        const idx = buffer.indexOf('\n\n')
        const event = buffer.slice(0, idx)
        buffer = buffer.slice(idx + 2)

        const lines = event.split('\n')
        for (const line of lines) {
          if (!line.startsWith('data:')) continue
          const jsonStr = line.slice(5).trim()
          if (!jsonStr) continue

          try {
            const data = JSON.parse(jsonStr)

            if (data.error) {
              throw new Error(data.error)
            }

            if (data.done) {
              genStepText.value = '生成完成，正在加载大纲…'
              if (fullText) {
                finishOutline(fullText)
              }
              return
            }

            if (data.content) {
              if (!firstChunkReceived) {
                firstChunkReceived = true
                genStepText.value = stageTexts[0]
              }
              fullText += data.content
              streamText.value += data.content
              // 基于内容长度切换阶段文案
              const stageIdx = Math.min(Math.floor(fullText.length / 200), stageTexts.length - 1)
              genStepText.value = stageTexts[stageIdx]
            }
          } catch (parseErr) {
            // JSON 解析失败，跳过
          }
        }
      }
    }

    // 流结束后处理缓冲区剩余内容
    if (buffer.trim()) {
      const lines = buffer.split('\n')
      for (const line of lines) {
        if (!line.startsWith('data:')) continue
        const jsonStr = line.slice(5).trim()
        if (!jsonStr) continue
        try {
          const data = JSON.parse(jsonStr)
          if (data.content) {
            fullText += data.content
            streamText.value += data.content
          }
        } catch (e) {}
      }
    }

    // 流结束但未收到 done 标记
    if (fullText) {
      finishOutline(fullText)
    } else {
      throw new Error('未收到任何内容')
    }
  } catch (e) {
    // 用户主动取消或组件卸载时不提示错误、不降级
    if (e && e.name === 'AbortError') {
      generating.value = false
      return
    }
    console.error('[aippt] 流式生成失败，降级到同步接口:', e)
    // 降级到同步接口
    try {
      const res = await api.post('/api/ppt/generatePptOutline', {
        title: form.title.trim(),
        chapterCount: form.chapterCount,
        sectionRange: form.sectionRange,
      })
      if (res.ok) {
        finishOutline(res.data.outline || '')
      } else {
        toast.error(res.msg || '大纲生成失败')
        generating.value = false
        currentStep.value = 1
      }
    } catch (e2) {
      toast.error('大纲生成失败')
      generating.value = false
      currentStep.value = 1
    }
  } finally {
    outlineAbortController = null
  }
}

// 从完整 SSE 文本中提取 content 内容（降级用）
function parseSseFullText(rawText) {
  let fullText = ''
  const lines = rawText.split('\n')
  for (const line of lines) {
    const trimmed = line.trim()
    if (!trimmed.startsWith('data:')) continue
    const jsonStr = trimmed.slice(5).trim()
    if (!jsonStr) continue
    try {
      const data = JSON.parse(jsonStr)
      if (data.content) fullText += data.content
    } catch (e) {}
  }
  return fullText
}

// 完成大纲生成
function finishOutline(fullText) {
  genStepText.value = '生成完成，正在加载大纲…'
  outlineContent.value = fullText
  // 默认展开第一个章节
  expandedChapters.value = [0]
  setTimeout(() => {
    generating.value = false
    toast.success('大纲生成成功')
  }, 400)
}

// 进入模板选择步骤
function goToTemplateStep() {
  if (!outlineContent.value.trim()) {
    toast.error('大纲内容不能为空')
    return
  }
  currentStep.value = 3
  loadCategories()
  loadTemplates()
}

// 加载分类
async function loadCategories() {
  if (categories.value.length > 0) return
  const res = await api.get('/api/ppt/templateCategories')
  if (res.ok) {
    categories.value = res.data.categories || []
  }
}

// 加载模板
async function loadTemplates() {
  loadingTemplates.value = true
  try {
    const res = await api.post('/api/ppt/templateList', {
      category_id: 0,
      page: templatePage.value,
      limit: templateLimit,
      is_private: 0,
    })
    if (res.ok) {
      templates.value = res.data.list || []
      templateTotal.value = res.data.count || 0
    }
  } finally {
    loadingTemplates.value = false
  }
}

// 封面加载失败处理
function onCoverError(e) {
  e.target.style.display = 'none'
  e.target.parentElement.classList.add('tpl-card__cover--fallback')
}

// 提交订单
// 点击"提交订单"按钮：打开确认弹窗
function handleSubmitOrder() {
  if (!selectedTemplateId.value) {
    toast.error('请选择一个模板')
    return
  }
  showConfirmModal.value = true
}

// 确认下单
async function confirmSubmitOrder() {
  if (!priceReady(priceData.value.price)) {
    toast.error('价格获取失败，请刷新重试')
    return
  }
  submitting.value = true
  try {
    // 创建订单（入库，由外部脚本处理 PPT 生成）
    const res = await api.post('/api/ppt/createPptOrder', {
      title: form.title.trim(),
      outline_content: outlineContent.value,
      chapter_count: form.chapterCount,
      section_range: form.sectionRange,
      author: form.author || '',
      template_id: selectedTemplateId.value,
    })
    if (!res.ok) {
      toast.error(res.msg || '创建订单失败')
      return
    }
    orderSn.value = res.data.order_sn
    showConfirmModal.value = false
    currentStep.value = 4
    toast.success('订单提交成功')
  } catch (e) {
    toast.error('提交失败')
  } finally {
    submitting.value = false
  }
}

// 重置
function resetAll() {
  currentStep.value = 1
  form.title = ''
  form.author = ''
  outlineContent.value = ''
  streamText.value = ''
  editMode.value = false
  expandedChapters.value = []
  generating.value = false
  selectedTemplateId.value = null
  selectedCategoryId.value = 0
  templatePage.value = 1
  orderSn.value = ''
}

// 监听 layout 刷新按钮（通过 window 自定义事件通信）
onMounted(() => {
  window.addEventListener('console-refresh', handleRefreshEvent)
})
onUnmounted(() => {
  window.removeEventListener('console-refresh', handleRefreshEvent)
  // 组件卸载时取消进行中的 SSE 请求，避免 net::ERR_ABORTED 等异常
  if (advAbortController) {
    advAbortController.abort()
    advAbortController = null
  }
  if (outlineAbortController) {
    outlineAbortController.abort()
    outlineAbortController = null
  }
})
function handleRefreshEvent() {
  if (version.value === 'advanced') {
    if (advStep.value === 3) {
      loadAdvCategories()
      loadAdvTemplates()
    }
    // 第四步「选择配色」已停用，不再刷新配色
    // else if (advStep.value === 4) {
    //   loadAdvThemes()
    // }
  } else {
    categories.value = []
    loadCategories()
    loadTemplates()
  }
}
</script>

<style scoped>
.aippt-page {
  max-width: 920px;
  margin: 0 auto;
  padding: 24px 0 48px;
}

/* 版本切换 Tab */
.version-tabs {
  display: flex;
  gap: 10px;
  background: #fff;
  padding: 6px;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(20, 184, 166, 0.06);
  margin-bottom: 28px;
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
}
.ver-tab {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 22px;
  border: none;
  background: transparent;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.ver-tab.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(20, 184, 166, 0.25);
}
.ver-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 5px;
  background: rgba(255,255,255,0.25);
  letter-spacing: 0.5px;
}
.ver-tab:not(.active) .ver-badge {
  background: #f1f5f9;
  color: #94a3b8;
}

/* 开发中占位 */
.dev-placeholder {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}
.dev-card {
  background: #fff;
  border-radius: 20px;
  padding: 56px 48px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0,0,0,0.04);
  max-width: 560px;
  width: 100%;
}
.dev-icon {
  width: 96px;
  height: 96px;
  margin: 0 auto 24px;
  border-radius: 24px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #14b8a6;
}
.dev-title {
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 12px;
}
.dev-desc {
  font-size: 14px;
  color: #64748b;
  line-height: 1.7;
  margin: 0 0 32px;
}
.dev-features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.dev-feature {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 10px;
  font-size: 13px;
  color: #475569;
  font-weight: 500;
}
.dev-feature svg {
  color: #14b8a6;
  flex-shrink: 0;
}

/* 步骤指示器 */
.steps {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32px;
  gap: 0;
}
.steps__item {
  display: flex;
  align-items: center;
  gap: 8px;
}
.steps__num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  transition: all 0.3s;
}
.steps__label {
  font-size: 14px;
  color: #94a3b8;
  font-weight: 500;
}
.steps__line {
  width: 48px;
  height: 2px;
  background: #e2e8f0;
  margin: 0 12px;
  border-radius: 1px;
  transition: background 0.3s;
}
.steps__line--done {
  background: #14b8a6;
}
.steps__item--active .steps__num {
  background: #14b8a6;
  color: #fff;
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
}
.steps__item--active .steps__label {
  color: #14b8a6;
  font-weight: 600;
}
.steps__item--done .steps__num {
  background: #14b8a6;
  color: #fff;
}
.steps__item--done .steps__label {
  color: #475569;
}

/* 内容区 */
.content {
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* 表单卡片 */
.form-card {
  background: #fff;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.form-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 28px;
}
.form-header__text {
  flex: 1;
}
.price-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 8px 14px;
  border: 1.5px solid #14b8a6;
  border-radius: 8px;
  background: #f0fdfa;
  color: #14b8a6;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  flex-shrink: 0;
}
.price-btn:hover {
  background: #14b8a6;
  color: #fff;
}
.form-header__icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #14b8a6;
  flex-shrink: 0;
}
.form-title {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
}
.form-subtitle {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}
.form-body {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.form-group--flex {
  flex: 1;
}
.form-label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.form-input, .form-select {
  padding: 12px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  background: #fff;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  font-family: inherit;
}
.form-input:focus, .form-select:focus {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}
.form-input::placeholder {
  color: #cbd5e1;
}
.form-row {
  display: flex;
  gap: 16px;
}
.form-footer {
  margin-top: 28px;
  display: flex;
  justify-content: flex-end;
}

/* ============ 流式大纲生成展示 ============ */
.streaming-mode {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 40%);
}
.streaming-mode::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, transparent, #14b8a6, transparent);
  animation: stream-top-flow 3s linear infinite;
}
@keyframes stream-top-flow {
  to { left: 100%; }
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.head-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.head-title h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.stream-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 5px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  letter-spacing: 0.5px;
}

/* 流光球体 */
.stream-orb-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.stream-orb-mini {
  position: relative;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.stream-orb-mini .orb-core {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #5eead4, #14b8a6);
  box-shadow: 0 0 12px rgba(20, 184, 166, 0.5);
  animation: orb-pulse 1.8s ease-in-out infinite;
}
.stream-orb-mini .orb-ring {
  position: absolute;
  width: 28px;
  height: 28px;
  border: 2px solid transparent;
  border-top-color: #14b8a6;
  border-radius: 50%;
  animation: orb-ring-spin 1.5s linear infinite;
}
@keyframes orb-pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(0.8); opacity: 0.7; }
}
@keyframes orb-ring-spin {
  to { transform: rotate(360deg); }
}

/* 波形动画 */
.stream-wave {
  display: flex;
  align-items: center;
  gap: 4px;
  height: 32px;
}
.stream-wave span {
  display: block;
  width: 3px;
  height: 8px;
  background: #14b8a6;
  border-radius: 2px;
  animation: wave-bounce 1.2s ease-in-out infinite;
}
@keyframes wave-bounce {
  0%, 100% { height: 8px; }
  50% { height: 24px; }
}
.stream-wave span:nth-child(2) { animation-delay: 0.15s; }
.stream-wave span:nth-child(3) { animation-delay: 0.3s; }
.stream-wave span:nth-child(4) { animation-delay: 0.45s; }
.stream-wave span:nth-child(5) { animation-delay: 0.6s; }

/* 流式大纲树 */
.outline-tree.streaming {
  padding: 8px 0;
}
.outline-tree.streaming .chapter {
  margin-bottom: 18px;
}
.outline-tree.streaming .chapter-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.outline-tree.streaming .chapter-title h4 {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}
.stream-chapter-num {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}
.outline-tree.streaming .sections {
  padding-left: 36px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.outline-tree.streaming .section-main {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #475569;
  font-weight: 500;
}
.stream-section-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #14b8a6;
  flex-shrink: 0;
}
.section-items {
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 5px;
}
.section-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.5;
}
.stream-item-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #94a3b8;
  flex-shrink: 0;
}

/* 骨架屏 */
.stream-skeleton {
  padding-left: 36px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.skeleton-line {
  height: 12px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-line.short {
  width: 60%;
}
@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 打字机光标行 */
.stream-typing-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  padding-left: 36px;
}
.stream-typing-cursor {
  width: 2px;
  height: 16px;
  background: #14b8a6;
  animation: cursor-blink 1s step-end infinite;
}
@keyframes cursor-blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}
.stream-typing-text {
  font-size: 13px;
  color: #14b8a6;
  font-weight: 500;
}

/* 空状态波纹 */
.stream-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}
.stream-empty p {
  margin-top: 24px;
  font-size: 14px;
  color: #94a3b8;
}
.stream-ripple {
  position: relative;
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stream-ripple .orb-core {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #5eead4, #14b8a6);
  box-shadow: 0 0 16px rgba(20, 184, 166, 0.4);
  animation: orb-pulse 1.8s ease-in-out infinite;
}
.ripple-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border: 2px solid #14b8a6;
  border-radius: 50%;
  opacity: 0;
  animation: ripple-expand 2s ease-out infinite;
}
.ripple-ring.d1 { animation-delay: 0.6s; }
.ripple-ring.d2 { animation-delay: 1.2s; }
@keyframes ripple-expand {
  0% { transform: scale(0.5); opacity: 0.6; }
  100% { transform: scale(1.8); opacity: 0; }
}

/* 底部 shimmer 进度条 */
.stream-footer-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}
.stream-shimmer-bar {
  flex: 1;
  height: 3px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
  position: relative;
}
.stream-shimmer-bar::after {
  content: '';
  position: absolute;
  top: 0;
  left: -40%;
  width: 40%;
  height: 100%;
  background: linear-gradient(90deg, transparent, #14b8a6, transparent);
  animation: shimmer-slide 1.5s ease-in-out infinite;
}
@keyframes shimmer-slide {
  to { left: 100%; }
}
.stream-tip {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
}

/* fade-in 动画 */
.stream-fade-in {
  animation: stream-fade-up 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes stream-fade-up {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ============ 结构化大纲卡片 ============ */
.outline-header__right {
  display: flex;
  align-items: center;
  gap: 10px;
}
.edit-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.edit-toggle-btn:hover {
  border-color: #14b8a6;
  color: #14b8a6;
}
.edit-toggle-btn.active {
  border-color: #14b8a6;
  background: #f0fdfa;
  color: #14b8a6;
}

/* 章节卡片 */
.outline-tree {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.chapter-card {
  border: 1.5px solid #f1f5f9;
  border-radius: 10px;
  overflow: hidden;
  transition: border-color 0.2s;
}
.chapter-card:hover {
  border-color: #e2e8f0;
}
.chapter-card__head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  cursor: pointer;
  background: #fafbfc;
  transition: background 0.2s;
}
.chapter-card__head:hover {
  background: #f1f5f9;
}
.chapter-card__num {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}
.chapter-card__title {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}
.chapter-card__count {
  font-size: 12px;
  color: #94a3b8;
  padding: 2px 8px;
  background: #fff;
  border-radius: 5px;
}
.chapter-card__arrow {
  color: #94a3b8;
  transition: transform 0.3s;
}
.chapter-card__arrow.expanded {
  transform: rotate(180deg);
}
.chapter-card__body {
  padding: 12px 16px 16px 52px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  background: #fff;
}

/* 章节展开动画 */
.chapter-expand-enter-active, .chapter-expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.chapter-expand-enter-from, .chapter-expand-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}
.chapter-expand-enter-to, .chapter-expand-leave-from {
  opacity: 1;
  max-height: 1000px;
}

.section-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.section-block__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.section-block__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #14b8a6;
  flex-shrink: 0;
}
.section-block__items {
  padding-left: 14px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.section-block__item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.6;
}
.section-block__item-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
  margin-top: 7px;
}

/* 大纲编辑 */
.outline-card {
  background: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.outline-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.outline-header__left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.outline-header__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #14b8a6;
}
.outline-title {
  font-size: 17px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 3px;
}
.outline-subtitle {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}
.outline-header__badge {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #14b8a6;
}
.outline-editor {
  width: 100%;
  min-height: 380px;
  padding: 20px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.8;
  color: #334155;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  resize: vertical;
  outline: none;
  transition: border-color 0.2s;
}
.outline-editor:focus {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

/* 模板选择 */
.template-section {
  background: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.tpl-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
}
.tpl-cat {
  padding: 8px 18px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.tpl-cat:hover {
  border-color: #14b8a6;
  color: #14b8a6;
}
.tpl-cat.active {
  background: #14b8a6;
  border-color: #14b8a6;
  color: #fff;
}
.tpl-loading, .tpl-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 60px 0;
  color: #94a3b8;
  font-size: 14px;
}
.spinner {
  width: 20px;
  height: 20px;
  border: 2.5px solid #e2e8f0;
  border-top-color: #14b8a6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.tpl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
}
.tpl-card {
  border: 2px solid #f1f5f9;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
  position: relative;
}
.tpl-card:hover {
  border-color: #14b8a6;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.1);
}
.tpl-card.selected {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}
.tpl-card__cover {
  width: 100%;
  height: 120px;
  background: #f8fafc;
  overflow: hidden;
}
.tpl-card__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.tpl-card__cover--fallback {
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
}
.tpl-card__info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.tpl-card__name {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.tpl-card__cat {
  font-size: 11px;
  color: #94a3b8;
}
.tpl-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
}
.tpl-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 24px;
}
.tpl-pagination button {
  padding: 8px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 13px;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}
.tpl-pagination button:hover:not(:disabled) {
  border-color: #14b8a6;
  color: #14b8a6;
}
.tpl-pagination button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.tpl-pagination__info {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

/* 步骤导航 */
.step-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 28px;
  gap: 12px;
}

/* 按钮 */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  text-decoration: none;
  white-space: nowrap;
}
.btn--primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
}
.btn--primary:hover:not(:disabled) {
  box-shadow: 0 6px 18px rgba(20, 184, 166, 0.3);
  transform: translateY(-1px);
}
.btn--primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}
.btn--ghost {
  background: #f1f5f9;
  color: #475569;
}
.btn--ghost:hover {
  background: #e2e8f0;
}
.btn--lg {
  padding: 13px 30px;
  font-size: 15px;
}
.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

/* 进度卡片 */
.progress-card {
  background: #fff;
  border-radius: 20px;
  padding: 56px 48px;
  text-align: center;
  box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.progress-generating {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.progress-ring {
  position: relative;
  width: 120px;
  height: 120px;
  margin-bottom: 28px;
}
.progress-ring__text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
.progress-ring__num {
  font-size: 26px;
  font-weight: 800;
  color: #14b8a6;
}
.progress-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 10px;
}
.progress-desc {
  font-size: 14px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.6;
}
.progress-done, .progress-failed {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.progress-done__icon, .progress-failed__icon {
  margin-bottom: 20px;
}
.progress-actions {
  display: flex;
  gap: 12px;
  margin-top: 28px;
}
.progress-order {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
  padding: 12px 20px;
  background: #f1f5f9;
  border-radius: 10px;
}
.progress-order__label {
  font-size: 13px;
  color: #64748b;
}
.progress-order__value {
  font-size: 14px;
  color: #14b8a6;
  font-weight: 600;
  font-family: 'Consolas', 'Monaco', monospace;
}
.progress-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  font-size: 12px;
  color: #94a3b8;
  max-width: 420px;
  text-align: center;
  line-height: 1.6;
}

/* 收费标准弹窗 */
.price-modal-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.price-modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  max-height: 85vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.price-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
}
.price-modal-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
}
.price-modal-title svg {
  color: #14b8a6;
}
.price-modal-close {
  border: none;
  background: #f1f5f9;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all 0.2s;
}
.price-modal-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}
.price-modal-body {
  padding: 20px 24px 24px;
  overflow-y: auto;
}
.price-section {
  margin-bottom: 20px;
}
.price-section:last-child {
  margin-bottom: 0;
}
.price-section-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}
.price-section-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.price-section-icon.primary {
  background: #f0fdfa;
  color: #14b8a6;
}
.price-section-icon.green {
  background: #f0fdf4;
  color: #16a34a;
}
.price-section-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  flex: 1;
}
.price-section-tag {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 5px;
  background: #f1f5f9;
  color: #64748b;
  font-weight: 500;
}
.ppt-price-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  border-radius: 12px;
  border: 1px solid #99f6e4;
}
.ppt-price-card + .ppt-price-card {
  margin-top: 10px;
}
.ppt-price-card--pro {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #fde68a;
}
.ppt-price-card--pro .ppt-price-card__price {
  color: #d97706;
}
.ppt-price-card__left {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.ppt-price-card__name {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}
.ppt-price-card__tags {
  display: flex;
  gap: 5px;
}
.ppt-tag {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 5px;
  background: #fff;
  color: #14b8a6;
  font-weight: 500;
  border: 1px solid #99f6e4;
}
.ppt-tag--pro {
  background: #fef3c7;
  color: #d97706;
  border-color: #fde68a;
}
.ppt-price-card__right {
  display: flex;
  align-items: baseline;
  gap: 5px;
}
.ppt-price-card__unit {
  font-size: 13px;
  color: #64748b;
}
.ppt-price-card__price {
  font-size: 28px;
  font-weight: 800;
  color: #14b8a6;
}
.price-highlight {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  margin-bottom: 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border: 1px solid #f59e0b;
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.18);
}
.price-highlight__icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.price-highlight__content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.price-highlight__title {
  font-size: 15px;
  font-weight: 700;
  color: #92400e;
}
.price-highlight__desc {
  font-size: 12.5px;
  color: #b45309;
}
.price-features {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.price-feature {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #475569;
}
.price-feature svg {
  flex-shrink: 0;
}
.price-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 14px;
  background: #fffbeb;
  border-radius: 8px;
  font-size: 12px;
  color: #92400e;
  line-height: 1.6;
}
.price-note svg {
  color: #f59e0b;
  flex-shrink: 0;
  margin-top: 1px;
}

/* 弹窗过渡动画 */
.price-modal-enter-active, .price-modal-leave-active {
  transition: opacity 0.25s ease;
}
.price-modal-enter-active .price-modal,
.price-modal-enter-active .confirm-modal,
.price-modal-leave-active .price-modal,
.price-modal-leave-active .confirm-modal {
  transition: transform 0.25s ease;
}
.price-modal-enter-from, .price-modal-leave-to {
  opacity: 0;
}
.price-modal-enter-from .price-modal,
.price-modal-enter-from .confirm-modal,
.price-modal-leave-to .price-modal,
.price-modal-leave-to .confirm-modal {
  transform: scale(0.95) translateY(10px);
}

/* ============ 确认下单弹窗 ============ */
.confirm-modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 460px;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.confirm-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid #f1f5f9;
}
.confirm-modal__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
}
.confirm-modal__title svg {
  color: #14b8a6;
}
.confirm-modal__body {
  padding: 14px 20px;
  overflow-y: auto;
}
.confirm-section {
  margin-bottom: 12px;
}
.confirm-section:last-of-type {
  margin-bottom: 0;
}
.confirm-section__head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.confirm-section__icon {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.confirm-section__icon.primary {
  background: #f0fdfa;
  color: #14b8a6;
}
.confirm-section__icon.green {
  background: #f0fdf4;
  color: #16a34a;
}
.confirm-section__icon.price {
  background: #fffbeb;
  color: #f59e0b;
}
.confirm-section__title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

/* 订单信息 */
.confirm-info {
  background: #f8fafc;
  border-radius: 10px;
  padding: 2px 14px;
}
.confirm-info__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 0;
  border-bottom: 1px solid #f1f5f9;
  gap: 12px;
}
.confirm-info__row:last-child {
  border-bottom: none;
}
.confirm-info__label {
  font-size: 13px;
  color: #64748b;
  flex-shrink: 0;
}
.confirm-info__value {
  font-size: 13px;
  color: #1e293b;
  font-weight: 500;
  text-align: right;
  word-break: break-all;
}

/* 模板信息 */
.confirm-tpl {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: #f8fafc;
  border-radius: 10px;
}
.confirm-tpl__cover {
  width: 56px;
  height: 40px;
  border-radius: 6px;
  overflow: hidden;
  background: #f1f5f9;
  flex-shrink: 0;
}
.confirm-tpl__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.confirm-tpl__cover-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
}
.confirm-tpl__info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.confirm-tpl__name {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.confirm-tpl__cat {
  font-size: 12px;
  color: #94a3b8;
}

/* 价格明细 */
.confirm-price {
  background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
  border: 1px solid #99f6e4;
  border-radius: 10px;
  padding: 10px 14px;
}
.confirm-price__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.confirm-price__label {
  font-size: 13px;
  color: #475569;
  font-weight: 500;
}
.confirm-price__unit {
  font-size: 13px;
  color: #64748b;
}
.confirm-price__divider {
  height: 1px;
  background: rgba(20, 184, 166, 0.2);
  margin: 7px 0;
}
.confirm-price__row--total .confirm-price__label {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}
.confirm-price__amount {
  font-size: 22px;
  font-weight: 800;
  color: #14b8a6;
}

/* 说明 */
.confirm-tip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px 12px;
  background: #fffbeb;
  border-radius: 8px;
  font-size: 12px;
  color: #92400e;
  line-height: 1.5;
  margin-top: 10px;
}
.confirm-tip svg {
  color: #f59e0b;
  flex-shrink: 0;
  margin-top: 1px;
}

/* 底部按钮 */
.confirm-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 12px 20px;
  border-top: 1px solid #f1f5f9;
}

/* 移动端 */
@media (max-width: 768px) {
  .aippt-page {
    padding: 16px 0 32px;
  }
  .form-card, .outline-card, .template-section {
    padding: 20px;
  }
  .progress-card {
    padding: 36px 24px;
  }
  .form-row {
    flex-direction: column;
    gap: 18px;
  }
  .steps__label {
    display: none;
  }
  .steps__line {
    width: 24px;
  }
  .tpl-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
  }
  .dev-features {
    grid-template-columns: 1fr;
  }
  .step-nav {
    flex-direction: column-reverse;
  }
  .step-nav .btn {
    width: 100%;
    justify-content: center;
  }
}

/* ≤640 紧凑屏：溶解表单白卡，消除「console .main 12px + 卡体 20px」双层留白
   （内容距屏缘 12+4=16px）；步骤 2/3 卡片仅收内边距保底色 */
@media (max-width: 640px) {
  .form-card { background: transparent; box-shadow: none; border-radius: 0; padding: 0 4px 24px; }
  .outline-card, .template-section { padding: 14px; }
}

/* ============ 高级版 PPT 生成样式 ============ */
.advanced-flow {
  max-width: 1100px;
  margin: 0 auto;
}

/* 高级版步骤指示器 */
.adv-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  margin-bottom: 32px;
  padding: 0 20px;
}
.adv-step {
  display: flex;
  align-items: center;
  gap: 8px;
}
.adv-step__num {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: #fff;
  border: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #94a3b8;
  transition: all 0.3s;
  flex-shrink: 0;
}
.adv-step.active .adv-step__num {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.adv-step.done .adv-step__num {
  background: linear-gradient(135deg, #10b981, #059669);
  border-color: transparent;
  color: #fff;
}
.adv-step__label {
  font-size: 13px;
  font-weight: 500;
  color: #94a3b8;
  transition: color 0.3s;
}
.adv-step.active .adv-step__label {
  color: #6366f1;
  font-weight: 600;
}
.adv-step.done .adv-step__label {
  color: #10b981;
}
.adv-step__line {
  width: 40px;
  height: 2px;
  background: #e2e8f0;
  margin: 0 12px;
  border-radius: 1px;
}
.adv-step.done + .adv-step .adv-step__line,
.adv-step.done .adv-step__line {
  background: #10b981;
}

/* 高级版卡片 */
.adv-card {
  background: #fff;
  border-radius: 16px;
  padding: 36px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
  border: 1px solid #f1f5f9;
  position: relative;
  overflow: hidden;
}
.adv-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
}

/* 步骤头部 */
.adv-step-head {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
}
.adv-step-head__text {
  flex: 1;
}
.adv-step-head__icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #6366f1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.adv-step-head__title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.adv-step-head__desc {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0;
}

/* 表单 */
.adv-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 28px;
}
.adv-field {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.adv-field__label {
  width: 90px;
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  line-height: 42px;
  flex-shrink: 0;
}
.adv-field__control {
  flex: 1;
}
.adv-input {
  width: 100%;
  height: 42px;
  padding: 0 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  background: #fff;
  transition: all 0.2s;
  font-family: inherit;
  box-sizing: border-box;
}
.adv-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.adv-input::placeholder {
  color: #cbd5e1;
}

/* 特性标签 */
.adv-features {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 28px;
  padding: 16px;
  background: linear-gradient(135deg, #f5f3ff, #ede9fe);
  border-radius: 12px;
}
.adv-feature-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #fff;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  color: #6366f1;
  border: 1px solid #ddd6fe;
}
.adv-feature-chip svg {
  color: #8b5cf6;
}

/* 步骤导航 */
.adv-step-nav {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 28px;
}
.btn--lg {
  padding: 12px 28px;
  font-size: 15px;
}
.btn--sm {
  padding: 5px 12px;
  font-size: 12px;
}
.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  margin-right: 6px;
  vertical-align: middle;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* 模板搜索栏 */
.adv-tpl-search {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}
.adv-tpl-search .adv-input {
  flex: 1;
}

/* 分类筛选标签 */
.adv-cat-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}
.adv-cat-chip {
  padding: 6px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  background: #fff;
  font-size: 13px;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.adv-cat-chip:hover {
  border-color: #c7d2fe;
  color: #6366f1;
}
.adv-cat-chip.active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}
.adv-cat-chip__count {
  font-size: 11px;
  opacity: 0.7;
  padding: 0 4px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 8px;
}
.adv-cat-chip.active .adv-cat-chip__count {
  background: rgba(255, 255, 255, 0.2);
}

/* 分页 */
.adv-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 20px;
  flex-wrap: wrap;
}
.adv-page-btn {
  width: 32px;
  height: 32px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.adv-page-btn:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}
.adv-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.adv-page-num {
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.adv-page-num:hover {
  border-color: #6366f1;
  color: #6366f1;
}
.adv-page-num.active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}
.adv-page-ellipsis {
  width: 24px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}
.adv-page-info {
  margin-left: 12px;
  font-size: 12px;
  color: #94a3b8;
}

/* 模板网格 */
.adv-tpl-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.adv-tpl-card {
  border: 2px solid #f1f5f9;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
}
.adv-tpl-card:hover {
  border-color: #c7d2fe;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.1);
}
.adv-tpl-card.selected {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.adv-tpl-card__cover {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #f8fafc;
  overflow: hidden;
}
.adv-tpl-card__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}
.adv-tpl-card:hover .adv-tpl-card__cover img {
  transform: scale(1.05);
}
.adv-tpl-card__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  color: #cbd5e1;
}
.adv-tpl-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #6366f1;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.adv-tpl-card__pages {
  position: absolute;
  bottom: 8px;
  left: 8px;
  padding: 2px 8px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  font-size: 11px;
  border-radius: 4px;
  backdrop-filter: blur(4px);
}
.adv-tpl-card__info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.adv-tpl-card__cat {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.adv-tpl-card__style {
  font-size: 11px;
  color: #94a3b8;
}

/* 骨架屏 */
.adv-skeleton-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.adv-tpl-skeleton {
  aspect-ratio: 16 / 9;
  border-radius: 12px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.adv-theme-skeleton {
  height: 80px;
  border-radius: 10px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
}

/* 空状态 */
.adv-tpl-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
  color: #94a3b8;
  gap: 12px;
}
.adv-tpl-empty p {
  font-size: 14px;
}

/* 已选模板信息 */
.adv-selected-tpl {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  background: linear-gradient(135deg, #f5f3ff, #ede9fe);
  border-radius: 12px;
  margin-bottom: 24px;
}
.adv-selected-tpl__preview {
  width: 96px;
  height: 54px;
  border-radius: 8px;
  overflow: hidden;
  background: #e2e8f0;
  flex-shrink: 0;
}
.adv-selected-tpl__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.adv-selected-tpl__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.adv-selected-tpl__cat {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}
.adv-selected-tpl__style {
  font-size: 12px;
  color: #64748b;
}
.adv-selected-tpl__pages {
  font-size: 11px;
  color: #94a3b8;
}

/* 配色网格 */
.adv-theme-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}
.adv-theme-card {
  position: relative;
  border: 2px solid #f1f5f9;
  border-radius: 10px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.adv-theme-card:hover {
  border-color: #c7d2fe;
  transform: translateY(-1px);
}
.adv-theme-card.selected {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.adv-theme-card__colors {
  display: flex;
  gap: 4px;
}
.adv-theme-color {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.adv-theme-card__name {
  font-size: 11px;
  color: #64748b;
  text-align: center;
  line-height: 1.3;
  word-break: break-all;
}
.adv-theme-card__check {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #6366f1;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 置顶默认配色选项 */
.adv-theme-default {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 2px solid #f1f5f9;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 12px;
}
.adv-theme-default:hover {
  border-color: #c7d2fe;
}
.adv-theme-default.selected {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
  background: #f5f3ff;
}
.adv-theme-default__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.adv-theme-default__name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.adv-theme-default__desc {
  font-size: 11px;
  color: #94a3b8;
}
.adv-theme-default__colors {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}
.adv-skip-radio {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  transition: all 0.2s;
  flex-shrink: 0;
}
.adv-skip-radio.checked {
  border-color: #6366f1;
  background: #6366f1;
  box-shadow: inset 0 0 0 3px #fff;
}

/* 生成中 */
.adv-generating {
  text-align: center;
  padding: 48px 36px;
}
.adv-gen-hero {
  margin-bottom: 36px;
}
.adv-gen-orb {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.adv-gen-orb__core {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #a78bfa, #6366f1);
  box-shadow: 0 0 24px rgba(99, 102, 241, 0.4);
  animation: adv-orb-pulse 1.8s ease-in-out infinite;
}
.adv-gen-orb__ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border: 2px solid transparent;
  border-radius: 50%;
}
.adv-gen-orb__ring.r1 {
  border-top-color: #6366f1;
  animation: adv-ring-spin 2s linear infinite;
}
.adv-gen-orb__ring.r2 {
  border-right-color: #8b5cf6;
  animation: adv-ring-spin 2.5s linear infinite reverse;
}
.adv-gen-orb__ring.r3 {
  border-bottom-color: #ec4899;
  animation: adv-ring-spin 3s linear infinite;
}
@keyframes adv-orb-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(0.85); }
}
@keyframes adv-ring-spin {
  to { transform: rotate(360deg); }
}
.adv-gen-title {
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px;
}
.adv-gen-sub {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* 进度条 */
.adv-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 500px;
  margin: 0 auto 32px;
}
.adv-progress__bar {
  flex: 1;
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}
.adv-progress__fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
  border-radius: 4px;
  transition: width 0.5s ease;
  position: relative;
}
.adv-progress__fill::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 20px;
  height: 100%;
  background: rgba(255, 255, 255, 0.4);
  filter: blur(4px);
}
.adv-progress__num {
  font-size: 15px;
  font-weight: 700;
  color: #6366f1;
  min-width: 48px;
  text-align: right;
}

/* 进度阶段 */
.adv-gen-stages {
  display: flex;
  justify-content: center;
  gap: 32px;
  margin-bottom: 32px;
  flex-wrap: wrap;
}
.adv-gen-stage {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #94a3b8;
  transition: color 0.3s;
}
.adv-gen-stage.active {
  color: #6366f1;
  font-weight: 600;
}
.adv-gen-stage.done {
  color: #10b981;
}
.adv-gen-stage__icon {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  transition: all 0.3s;
}
.adv-gen-stage.active .adv-gen-stage__icon {
  background: #6366f1;
  color: #fff;
  animation: adv-icon-pulse 1.5s ease-in-out infinite;
}
.adv-gen-stage.done .adv-gen-stage__icon {
  background: #10b981;
  color: #fff;
}
@keyframes adv-icon-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); }
}

/* 完成卡片 */
.adv-gen-done {
  max-width: 500px;
  margin: 0 auto;
}
.adv-gen-done__card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 24px;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  border: 1px solid #a7f3d0;
  border-radius: 12px;
}
.adv-gen-done__card--fail {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border-color: #fecaca;
}
.adv-gen-done__icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: #10b981;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.adv-gen-done__icon--fail {
  background: #ef4444;
}
.adv-gen-done__info {
  flex: 1;
  text-align: left;
}
.adv-gen-done__info h4 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
}
.adv-gen-done__info p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

/* 高级版确认弹窗额外样式 */
.adv-tag-pro {
  display: inline-block;
  padding: 2px 8px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  border-radius: 5px;
  font-size: 11px;
  font-weight: 700;
}
.adv-confirm-theme {
  margin-top: 8px;
  padding: 7px 12px;
  background: #f8fafc;
  border-radius: 8px;
}
.adv-confirm-theme__label {
  font-size: 12px;
  color: #64748b;
  display: block;
  margin-bottom: 4px;
}
.adv-confirm-theme__colors {
  display: flex;
  align-items: center;
  gap: 6px;
}
.adv-confirm-theme__colors span:not(.adv-confirm-theme__name) {
  width: 20px;
  height: 20px;
  border-radius: 5px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
.adv-confirm-theme__name {
  font-size: 12px;
  color: #475569;
  font-weight: 500;
  margin-left: 4px;
}

/* 响应式 */
@media (max-width: 768px) {
  .adv-card {
    padding: 20px;
  }
  .adv-tpl-grid,
  .adv-skeleton-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .adv-theme-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  .adv-field {
    flex-direction: column;
    gap: 6px;
  }
  .adv-field__label {
    width: auto;
    line-height: 1.5;
  }
  .adv-gen-stages {
    gap: 16px;
  }
  .adv-step__label {
    display: none;
  }
  .adv-step__line {
    width: 20px;
    margin: 0 4px;
  }
}
</style>
