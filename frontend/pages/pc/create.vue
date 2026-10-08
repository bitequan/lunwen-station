<template>
  <div class="create-page">
    <main class="create-main">
      <div class="container create-body">
        <div class="page-head">
          <div class="page-head-tabs" :class="{ 'full-tabs': mode === 'full' }">
            <!-- 快速模式 / 完整模式 tab 已注释（保留备用）
            <button
              class="tab-btn"
              :class="{ active: mode === 'quick' }"
              @click="mode = 'quick'"
            >快速模式</button>
            <button
              class="tab-btn"
              :class="{ active: mode === 'full' }"
              @click="mode = 'full'"
            >完整模式</button>
            -->
          </div>
        </div>
        <!-- 快速模式 -->
        <section v-if="mode === 'quick'" class="mode-panel quick-panel">
          <div class="order-card compact">
            <!-- 下单流程指引 -->
            <div class="order-flow-wrap">
              <div class="order-flow">
                <div class="flow-step" :class="{ done: quickStep > 1, current: quickStep === 1 }">
                  <span class="flow-dot">1</span>
                  <span class="flow-label">填写标题与参数</span>
                </div>
                <div class="flow-line" :class="{ done: quickStep > 1 }"></div>
                <div class="flow-step" :class="{ done: quickStep > 2, current: quickStep === 2 }">
                  <span class="flow-dot">2</span>
                  <span class="flow-label">选择模板</span>
                </div>
                <div class="flow-line" :class="{ done: quickStep > 2 }"></div>
                <div class="flow-step" :class="{ current: quickStep === 3 }">
                  <span class="flow-dot">3</span>
                  <span class="flow-label">生成大纲</span>
                </div>
              </div>
              <button class="price-standard-btn" @click="showPriceModal = true">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                收费标准
              </button>
            </div>

            <!-- 表单视图 -->
            <div class="quick-form-view">
            <!-- 步骤 1：论文标题与参数 -->
            <div v-if="quickStep === 1" class="quick-step-pane">
            <!-- 论文标题 -->
            <div class="topic-input-wrap">
              <textarea
                v-model="quickForm.title"
                class="topic-textarea"
                rows="2"
                maxlength="100"
                placeholder="请输入论文标题"
              ></textarea>
              <div class="topic-counter" :class="{ danger: quickForm.title.length >= 100 }">
                已输入 {{ quickForm.title.length }}/100{{ quickForm.title.length >= 100 ? '，已达上限' : '' }}
              </div>
              <button class="smart-topic-btn" @click="openTitleSuggest">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2l2.4 7.2h7.6l-6 4.8 2.4 7.2-6-4.8-6 4.8 2.4-7.2-6-4.8h7.6z"/></svg>
                智能选题
              </button>
            </div>

            <!-- 智能选题面板（quick 模式：接 /api/tools/createtitle，与完整模式共享状态与逻辑） -->
            <div v-if="showSuggest" class="suggest-panel suggest-panel--quick">
              <div class="suggest-row">
                <div class="field suggest-field">
                  <label>研究方向与兴趣</label>
                  <input v-model="suggestForm.interest" type="text" placeholder="输入您的研究领域或关键词..." @keydown.enter="generateTitles" />
                </div>
                <div class="field suggest-field">
                  <label>研究类型</label>
                  <select v-model="suggestForm.type">
                    <option v-for="t in suggestTypes" :key="t" :value="t">{{ t }}</option>
                  </select>
                </div>
                <button class="btn btn-primary suggest-btn" :disabled="suggestLoading" @click="generateTitles">
                  {{ suggestLoading ? '生成中...' : '生成推荐题目' }}
                </button>
              </div>
              <div v-if="suggestedTitles.length" class="suggest-list">
                <div
                  v-for="(t, i) in suggestedTitles"
                  :key="i"
                  class="suggest-item"
                  @click="selectTitle(t)"
                >
                  <span class="suggest-title">{{ t.title }}</span>
                  <span class="suggest-desc">{{ t.desc }}</span>
                  <span class="suggest-select">点击选择</span>
                </div>
              </div>
            </div>

            <!-- 参数区（无标题，紧凑单网格） -->
            <div class="param-grid compact-grid">
              <div class="param-cell wide">
                <span class="cell-label">选择学历</span>
                <div class="option-pills compact">
                  <button
                    v-for="d in degrees"
                    :key="d"
                    class="option-pill"
                    :class="{ active: quickForm.degree === d }"
                    @click="quickForm.degree = d"
                  >{{ d }}</button>
                </div>
              </div>
              <div class="param-cell wide">
                <span class="cell-label">学科分类</span>
                <div class="option-pills compact">
                  <button
                    v-for="cat in subjectList"
                    :key="cat.id"
                    class="option-pill"
                    :class="{ active: selectedCategory && selectedCategory.id === cat.id }"
                    @click="selectedCategory = cat"
                  >{{ cat.name }}</button>
                </div>
              </div>
              <div class="param-cell wide">
                <span class="cell-label">字数要求</span>
                <div class="word-options compact">
                  <button
                    v-for="w in displayedWordOptions"
                    :key="w"
                    class="option-pill"
                    :class="{ active: quickForm.words === w && !quickForm.customWords }"
                    @click="quickForm.words = w; quickForm.customWords = ''"
                  >{{ w }}</button>
                  <button
                    v-if="wordOptions.length > 5"
                    class="option-pill more-pill"
                    @click="showMoreWords = !showMoreWords"
                  >
                    {{ showMoreWords ? '收起' : '更多' }}
                  </button>
                  <div class="custom-word" :class="{ active: quickForm.customWords }">
                    <input v-model="quickForm.customWords" type="number" placeholder="自定义" @input="quickForm.words = ''" />
                    <span>字</span>
                  </div>
                </div>
              </div>
              <div class="param-cell wide">
                <span class="cell-label">生成模型</span>
                <div class="model-cell-content">
                <div class="model-options compact">
                  <button
                    v-for="m in aiModels"
                    :key="m.value"
                    class="model-card"
                    :class="{ active: quickForm.model === m.value }"
                    @click="quickForm.model = m.value"
                  >
                    <span class="model-name">{{ m.label }}</span>
                    <span v-if="m.tag" class="model-tag">{{ m.tag }}</span>
                  </button>
                </div>
                <transition name="adv-slide">
                  <div v-if="isAdvancedModel" class="model-advantages">
                    <div class="adv-banner">
                      <span class="adv-badge">高级</span>
                      <span class="adv-banner-icon">🚀</span>
                      <div class="adv-banner-text">
                        <span class="adv-banner-title">高级模型专属优势</span>
                        <span class="adv-banner-sub">真实文献可溯源，专业图表与原文链接全程可查证</span>
                      </div>
                    </div>
                    <div class="adv-grid">
                      <div v-for="adv in advancedModelAdvantages" :key="adv.title" class="adv-item">
                        <span class="adv-item-icon">{{ adv.icon }}</span>
                        <div class="adv-item-body">
                          <span class="adv-item-title">{{ adv.title }}</span>
                          <span class="adv-item-desc">{{ adv.desc }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition>
                </div>
              </div>
              <div class="param-cell">
                <span class="cell-label">大纲级别</span>
                <div class="option-pills compact">
                  <button
                    v-for="o in outlineOptions"
                    :key="o.value"
                    class="option-pill"
                    :class="{ active: quickForm.outlineLevel === o.value }"
                    @click="quickForm.outlineLevel = o.value"
                  >{{ o.label }}</button>
                </div>
              </div>
              <div class="param-cell">
                <span class="cell-label">写作语言</span>
                <div class="option-pills compact">
                  <button
                    v-for="l in displayedLanguages"
                    :key="l"
                    class="option-pill"
                    :class="{ active: quickForm.language === l }"
                    @click="quickForm.language = l"
                  >{{ l }}</button>
                  <button
                    v-if="languages.length > 4"
                    class="option-pill more-pill"
                    @click="showMoreLanguages = !showMoreLanguages"
                  >
                    {{ showMoreLanguages ? '收起' : '更多' }}
                  </button>
                </div>
              </div>
              <div class="param-cell">
                <span class="cell-label">文献条数</span>
                <div class="lit-count-input">
                  <div class="custom-word" :class="{ active: quickForm.literatureCount }">
                    <input v-model.number="quickForm.literatureCount" type="number" min="5" max="80" placeholder="25" />
                    <span>条</span>
                  </div>
                  <span class="cell-hint">最终参考文献数量</span>
                </div>
              </div>
              <div class="param-cell wide en-lit-input-cell">
                <span class="cell-label">外文文献条数</span>
                <div class="lit-count-input">
                  <div class="custom-word" :class="{ active: quickForm.enLiteratureCount > 0 }">
                    <input v-model.number="quickForm.enLiteratureCount" type="number" min="0" :max="quickForm.literatureCount" placeholder="0" />
                    <span>条</span>
                  </div>
                  <span class="cell-hint">包含在文献条数中，不能大于文献条数（{{ quickForm.literatureCount }} 条）</span>
                </div>
              </div>
              <div class="param-cell wide">
                <span class="cell-label">AI 优化</span>
                <div class="inline-checks compact">
                  <label class="check-item">
                    <input v-model="quickForm.needLowerAI" type="checkbox" />
                    <span class="check-box"></span>
                    <span>降低 AI 率</span>
                  </label>
                </div>
              </div>
              <div class="param-cell wide outline-toggle-cell">
                <span class="cell-label">自定义大纲</span>
                <label class="toggle-switch">
                  <input v-model="quickForm.useCustomOutline" type="checkbox" />
                  <span class="toggle-slider"></span>
                </label>
                <span class="toggle-hint">{{ quickForm.useCustomOutline ? '已启用，将使用自定义大纲生成' : '启用后可手动输入大纲结构' }}</span>
              </div>
            </div>

            <!-- 自定义大纲输入区 -->
            <div v-if="quickForm.useCustomOutline" class="custom-outline-area">
              <div class="outline-editor-head">
                <span class="outline-editor-title">
                  <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  自定义大纲
                </span>
                <button class="outline-load-sample" @click="loadOutlineSample">载入示例</button>
              </div>
              <textarea
                v-model="quickForm.customOutline"
                class="outline-editor"
                rows="8"
                placeholder="支持粘贴或手动输入大纲，例如：&#10;一、绪论&#10;1.1 研究背景&#10;1.2 研究意义&#10;二、相关理论&#10;2.1 理论概述&#10;2.2 理论应用&#10;三、研究方法&#10;..."
              ></textarea>
              <div class="outline-editor-tip">
                <span>提示：按"一、""1.1"等层级格式输入，AI 将基于此大纲生成全文</span>
                <span class="outline-counter">{{ (quickForm.customOutline || '').length }} 字</span>
              </div>
            </div>

            <!-- 步骤 1 导航 -->
            <div class="quick-submit tpl-view-actions">
              <button class="btn btn-side-primary" @click="goQuickStep(2)">
                下一步：选择模板
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
              </button>
            </div>
            </div>

            <!-- 步骤 2：选择格式模板 -->
            <div v-if="quickStep === 2" class="quick-step-pane">
              <div class="step-card-head compact">
                <h3>选择格式模板</h3>
                <p>选择论文格式模板，未选择将使用通用国标格式</p>
              </div>

              <!-- 模板分类切换 -->
              <div class="tpl-type-switch">
                <button class="tpl-type-btn" :class="{ active: tplTab === 'public' }" @click="switchTplTab('public')">公共模板</button>
                <button class="tpl-type-btn" :class="{ active: tplTab === 'private' }" @click="switchTplTab('private')">私有模板</button>
              </div>
              <div class="tpl-view-bar">
                <div class="tpl-view-search">
                  <svg class="tpl-search-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                  <input v-model="templateKeyword" type="text" placeholder="输入模板名称搜索" @input="onTemplateSearch" />
                </div>
              </div>
              <div class="tpl-view-body">
                <div v-if="templateLoading && !templateResults.length" class="tpl-loading">正在加载模板…</div>
                <div v-else-if="templateResults.length" class="tpl-grid">
                  <div
                    v-if="tplTab === 'public'"
                    class="tpl-card"
                    :class="{ selected: quickForm.templateId === 0 }"
                    @click="pickTemplate({ id: 0, name: '通用格式', profession: '', years: '', avt: '', degree: '' })"
                  >
                    <div class="tpl-card-preview tpl-general-preview">
                      <svg viewBox="0 0 24 24" width="32" height="32"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </div>
                    <div class="tpl-card-info">
                      <div class="tpl-card-name">通用格式</div>
                      <div class="tpl-card-meta">GB/T 7714 国标</div>
                    </div>
                    <span v-if="quickForm.templateId === 0" class="tpl-card-check">
                      <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </span>
                  </div>
                  <div
                    v-for="t in templateResults"
                    :key="t.id"
                    class="tpl-card"
                    :class="{ selected: quickForm.templateId === t.id }"
                    @click="pickTemplate(t)"
                  >
                    <div class="tpl-card-preview">
                      <img v-if="t.avt" :src="t.avt" alt="" @error="onTplLogoError" />
                      <span class="tpl-card-placeholder" :style="t.avt ? 'display:none' : ''">{{ t.name.charAt(0) }}</span>
                    </div>
                    <div class="tpl-card-info">
                      <div class="tpl-card-name">{{ t.name }}</div>
                      <div class="tpl-card-meta">{{ t.degree }} · {{ t.profession }}</div>
                      <div class="tpl-card-year">{{ t.years }}</div>
                    </div>
                    <span v-if="quickForm.templateId === t.id" class="tpl-card-check">
                      <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </span>
                  </div>
                </div>
                <div v-else-if="templateSearched" class="tpl-empty">
                  <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" opacity="0.4" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                  <p v-if="tplTab === 'private'">暂无私有模板</p>
                  <p v-else-if="templateKeyword">未找到包含"{{ templateKeyword }}"的模板</p>
                  <p v-else>暂无可用模板</p>
                  <span v-if="tplTab === 'public'">可使用通用格式，或联系客服添加</span>
                  <span v-else>可前往「模板制作」上传自己的模板</span>
                </div>
              </div>
              <div class="tpl-view-foot">
                <div class="tpl-stats-banner" v-if="modalTotal > 0 || modalDbTotal > 0">
                  <template v-if="tplTab === 'public'">
                    <div v-if="templateKeyword" class="stats-search-row">
                      <svg class="stats-search-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                      <div class="stats-search-text">
                        搜索"<span class="stats-keyword">{{ templateKeyword }}</span>"，共找到
                        <span class="stats-highlight">{{ modalTotal }}</span> 个匹配模板
                      </div>
                    </div>
                    <div v-else class="stats-showcase">
                      <div class="stats-icon-wrap">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z"/></svg>
                      </div>
                      <div class="stats-content">
                        <div class="stats-num-row">
                          <span class="stats-big-num">{{ modalDbTotal.toLocaleString() }}</span>
                          <span class="stats-label">个模板</span>
                          <span class="stats-badge">覆盖全国高校</span>
                        </div>
                        <div class="stats-sub-row">
                          当前随机展示 <span class="stats-em">{{ modalTotal }}</span> 个 · 使用搜索可查找更多模板
                        </div>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="stats-private-row">
                      <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                      <span>私有模板共 <span class="stats-highlight">{{ modalTotal }}</span> 个</span>
                    </div>
                  </template>
                </div>
              </div>

              <!-- 步骤 2 导航 -->
              <div class="quick-submit tpl-view-actions">
                <button class="btn btn-side-secondary" @click="goQuickStep(1)">
                  <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                  上一步
                </button>
                <button class="btn btn-side-primary" @click="onQuickGenerate">
                  生成大纲
                  <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                </button>
              </div>
            </div>
            </div>

            <!-- 步骤 3：大纲编辑（内嵌组件，v-show 保持挂载状态，上一步/下一步不丢失数据） -->
            <OutlineEditor
              v-show="quickStep === 3"
              :form-data="quickForm"
              :active="quickStep === 3"
              :restore-outline-no="restoreOutlineNo"
              @back="onOutlineBack"
              @paid="onOutlinePaid"
              @reset="onOutlineReset"
            />

          </div>
        </section>

        <!-- 完整模式 -->
        <section v-else class="mode-panel full-panel">
          <div class="full-layout">
            <div class="full-content">
              <!-- 顶部横向步骤条 -->
              <div class="full-stepbar">
                <div
                  v-for="(s, i) in steps"
                  :key="i"
                  class="full-stepbar-item"
                  :class="{
                    active: fullStep >= i + 1,
                    current: fullStep === i + 1
                  }"
                  @click="goStep(i + 1)"
                >
                  <div class="full-stepbar-node">
                    <svg v-if="fullStep > i + 1" viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <span v-else>{{ i + 1 }}</span>
                  </div>
                  <div class="full-stepbar-text">
                    <div class="full-stepbar-title">{{ s.title }}</div>
                    <div class="full-stepbar-sub">{{ s.sub }}</div>
                  </div>
                </div>
              </div>
              <!-- 第1步：提交标题 -->
              <div v-if="fullStep === 1" class="step-card">
                <div class="step-card-head compact">
                  <h3>提交论文标题</h3>
                  <p>输入完整的论文标题，获得更好的生成效果</p>
                </div>

                <div class="field full">
                  <label class="field-label required">论文标题</label>
                  <div class="field-content">
                    <div class="title-input-bar">
                      <input
                        v-model="fullForm.title"
                        type="text"
                        maxlength="100"
                        placeholder="输入完整的标题，获得更好的生成效果（5-50字内）"
                      />
                      <button class="btn btn-primary" @click="openTitleSuggest">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2l2.4 7.2h7.6l-6 4.8 2.4 7.2-6-4.8-6 4.8 2.4-7.2-6-4.8h7.6z"/></svg>
                        推荐标题
                      </button>
                    </div>
                    <div class="title-counter">{{ fullForm.title.length }}/50</div>
                  </div>
                </div>

                <div v-if="showSuggest" class="suggest-panel">
                  <div class="panel-head">智能选题</div>
                  <div class="suggest-row">
                    <div class="field suggest-field">
                      <label>研究方向与兴趣</label>
                      <input v-model="suggestForm.interest" type="text" placeholder="输入您的研究领域或关键词..." />
                    </div>
                    <div class="field suggest-field">
                      <label>研究类型</label>
                      <select v-model="suggestForm.type">
                        <option v-for="t in suggestTypes" :key="t" :value="t">{{ t }}</option>
                      </select>
                    </div>
                    <button class="btn btn-primary suggest-btn" :disabled="suggestLoading" @click="generateTitles">
                      {{ suggestLoading ? '生成中...' : '生成推荐题目' }}
                    </button>
                  </div>
                  <div v-if="suggestedTitles.length" class="suggest-list">
                    <div
                      v-for="(t, i) in suggestedTitles"
                      :key="i"
                      class="suggest-item"
                      @click="selectTitle(t)"
                    >
                      <span class="suggest-title">{{ t.title }}</span>
                      <span class="suggest-desc">{{ t.desc }}</span>
                      <span class="suggest-select">点击选择</span>
                    </div>
                  </div>
                </div>



                <div class="agreement-row">
                  <label class="check-item">
                    <input v-model="fullForm.agreed" type="checkbox" />
                    <span class="check-box"></span>
                    <span>我已阅读并同意：本站提供的是文献下载服务，下载的内容仅用于参考，不作为毕业、发表使用</span>
                  </label>
                </div>

                <div class="step-actions">
                  <button class="btn btn-primary next-btn" @click="fullStep = 2">
                    下一步
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                  </button>
                </div>
              </div>

              <!-- 第2步：完善信息 -->
              <div v-if="fullStep === 2" class="step-card">
                <div class="step-card-head compact">
                  <h3>完善论文信息</h3>
                  <p>补充学历、专业、字数等关键信息，让生成结果更精准</p>
                </div>

                <!-- 基础配置 -->
                <div class="form-section">
                  <div class="section-title">基础配置</div>
                  <div class="form-grid clean">
                    <div class="field">
                      <label class="field-label required">选择学历</label>
                      <div class="option-pills">
                        <button
                          v-for="d in degrees"
                          :key="d"
                          class="option-pill"
                          :class="{ active: fullForm.degree === d }"
                          @click="fullForm.degree = d"
                        >{{ d }}</button>
                      </div>
                    </div>

                    <div class="field">
                      <label class="field-label required">学科专业</label>
                      <div class="subject-inline">
                        <div class="major-select">
                          <select v-model="selectedCategory" @change="subjectSearch = ''">
                            <option v-for="cat in subjectList" :key="cat.id" :value="cat">{{ cat.name }}</option>
                          </select>
                        </div>
                        <div class="major-select">
                          <select v-model="fullForm.subject">
                            <option value="">选择具体专业</option>
                            <option v-for="s in filteredSubjects" :key="s.id" :value="s.name">{{ s.name }}</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <div class="field">
                      <label class="field-label required">字数要求</label>
                      <div class="word-options">
                        <button
                          v-for="w in wordOptions"
                          :key="w"
                          class="option-pill"
                          :class="{ active: fullForm.words === w && !fullForm.customWords }"
                          @click="fullForm.words = w; fullForm.customWords = ''"
                        >{{ w }}</button>
                        <div class="custom-word" :class="{ active: fullForm.customWords }">
                          <input v-model="fullForm.customWords" type="number" placeholder="自定义" @input="fullForm.words = ''" />
                          <span>字</span>
                        </div>
                      </div>
                    </div>

                    <div class="field">
                      <label class="field-label">写作语言</label>
                      <div class="option-pills">
                        <button
                          v-for="l in languages"
                          :key="l"
                          class="option-pill"
                          :class="{ active: fullForm.language === l }"
                          @click="fullForm.language = l"
                        >{{ l }}</button>
                      </div>
                    </div>

                    <div class="field">
                      <label class="field-label">生成模型</label>
                      <div class="model-cell-content">
                      <div class="model-options">
                        <button
                          v-for="m in aiModels"
                          :key="m.value"
                          class="model-card"
                          :class="{ active: fullForm.model === m.value }"
                          @click="fullForm.model = m.value"
                        >
                          <span class="model-name">{{ m.label }}</span>
                          <span v-if="m.tag" class="model-tag">{{ m.tag }}</span>
                        </button>
                      </div>
                      <transition name="adv-slide">
                        <div v-if="isFullAdvanced" class="model-advantages">
                          <div class="adv-banner">
                            <span class="adv-badge">高级</span>
                            <span class="adv-banner-icon">🚀</span>
                            <div class="adv-banner-text">
                              <span class="adv-banner-title">高级模型专属优势</span>
                              <span class="adv-banner-sub">真实文献可溯源，专业图表与原文链接全程可查证</span>
                            </div>
                          </div>
                          <div class="adv-grid">
                            <div v-for="adv in advancedModelAdvantages" :key="adv.title" class="adv-item">
                              <span class="adv-item-icon">{{ adv.icon }}</span>
                              <div class="adv-item-body">
                                <span class="adv-item-title">{{ adv.title }}</span>
                                <span class="adv-item-desc">{{ adv.desc }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </transition>
                      </div>
                    </div>

                    <div class="field">
                      <label class="field-label">大纲级别</label>
                      <div class="option-pills">
                        <button
                          v-for="o in outlineOptions"
                          :key="o.value"
                          class="option-pill"
                          :class="{ active: fullForm.outlineLevel === o.value }"
                          @click="fullForm.outlineLevel = o.value"
                        >{{ o.label }}</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 内容、格式与资料 -->
                <div class="form-section">
                  <div class="section-title">内容、格式与资料</div>
                  <div class="field full">
                    <label class="field-label">关键词</label>
                    <input
                      v-model="fullForm.keywords"
                      type="text"
                      placeholder="每个关键词用空格或者；隔开，至少3个，至多8个，将在论文中显示"
                    />
                  </div>

                  <div class="field full">
                    <label class="field-label">AI 投喂 / 补充要求</label>
                    <textarea
                      v-model="fullForm.feedContent"
                      rows="3"
                      placeholder="建议输入相关研究思路，方便 AI 能够更加准确了解您的需求，如关键词、核心思路、观点、研究内容、研究方法、案例、问卷、数据参考的辅助材料。"
                    ></textarea>
                    <div class="area-counter">{{ fullForm.feedContent.length }}/1500</div>
                  </div>

                  <div class="field full">
                    <label class="field-label">图文要求</label>
                    <div class="inline-checks">
                      <label class="check-item">
                        <input v-model="fullForm.needChart" type="checkbox" />
                        <span class="check-box"></span>
                        <span>图表 / 表格 / 插图 / 公式 / 代码</span>
                      </label>
                      <label class="check-item">
                        <input v-model="fullForm.needTable" type="checkbox" />
                        <span class="check-box"></span>
                        <span>数据表格</span>
                      </label>
                      <label class="check-item">
                        <input v-model="fullForm.needReference" type="checkbox" />
                        <span class="check-box"></span>
                        <span>自动匹配参考文献</span>
                      </label>
                    </div>
                  </div>

                  <div class="field full">
                    <label class="field-label">格式模板</label>
                    <div class="major-select">
                      <select v-model="fullForm.template">
                        <option value="">先选择对应学历，再输入学校全称搜索</option>
                        <option v-for="tpl in templates" :key="tpl" :value="tpl">{{ tpl }}</option>
                      </select>
                    </div>
                    <div class="field-tip">学校没收录？别担心，联系客服即可添加！可先生成论文内容，后续再找客服套格式。</div>
                  </div>

                  <div class="field full">
                    <label class="field-label">资料上传</label>
                    <div class="field-content">
                      <label class="check-item">
                        <input v-model="fullForm.needFeedFile" type="checkbox" />
                        <span class="check-box"></span>
                        <span>投喂 AI：上传开题报告 / 参考资料</span>
                      </label>
                      <div v-if="fullForm.needFeedFile" class="upload-area">
                        <div class="upload-card">
                          <span class="upload-icon">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                          </span>
                          <div class="upload-text">
                            <div class="upload-main">点击上传开题报告 / 参考资料</div>
                            <div class="upload-sub">仅支持 doc/docx 格式，上传后请检查识别内容是否一致</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="step-actions">
                  <button class="btn btn-secondary prev-btn" @click="fullStep = 1">上一步</button>
                  <button class="btn btn-primary next-btn" @click="fullStep = 3">
                    下一步
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                  </button>
                </div>
              </div>

              <!-- 第3步：选择资源 -->
              <div v-if="fullStep === 3" class="step-card">
                <div class="step-card-head compact">
                  <h3>选择生成资源</h3>
                  <p>挑选图表、文献等辅助资源，提升论文质量</p>
                </div>

                <div class="form-section">
                  <div class="resource-tabs">
                    <button
                      class="resource-tab"
                      :class="{ active: resourceTab === 'chart' }"
                      @click="resourceTab = 'chart'"
                    >
                      <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                      图表资源
                    </button>
                    <button
                      class="resource-tab"
                      :class="{ active: resourceTab === 'literature' }"
                      @click="resourceTab = 'literature'"
                    >
                      <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg>
                      网络文献
                    </button>
                  </div>

                  <div v-if="resourceTab === 'chart'" class="resource-panel">
                    <div class="resource-empty">
                      <div class="empty-icon">
                        <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                      </div>
                      <div class="empty-title">图表将在生成后自动匹配</div>
                      <div class="empty-sub">您可以在后续大纲编辑页面为章节选择图表类型，系统将自动匹配相关图片</div>
                    </div>
                  </div>

                  <div v-else class="resource-panel literature-panel">
                    <div class="lit-toolbar">
                      <!-- 已选文献（置顶，避免结果列表动态下移） -->
                      <div v-if="fullForm.selectedLiterature.length" class="lit-selected">
                        <div class="lit-selected-header">
                          <span class="lit-selected-title">
                            已选文献
                            <span class="lit-selected-count">{{ fullForm.selectedLiterature.length }}</span>
                          </span>
                          <button class="lit-clear" @click="clearLiterature">清空</button>
                        </div>
                        <div class="lit-selected-list">
                          <span
                            v-for="(lit, idx) in fullForm.selectedLiterature"
                            :key="idx"
                            class="lit-selected-tag"
                            :class="{ ref: lit.reference, content: lit.content }"
                            :title="lit.title"
                          >
                            <span class="lit-tag-title">{{ lit.title }}</span>
                            <button class="lit-remove" @click.stop="removeLiterature(idx)">×</button>
                          </span>
                        </div>
                      </div>

                      <div class="lit-search-bar">
                        <svg class="lit-search-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input
                          v-model="literatureKeyword"
                          type="text"
                          placeholder="输入关键词搜索在线文献，留空使用论文标题"
                          @keyup.enter="searchLiterature"
                        />
                        <button class="lit-search-btn" :disabled="literatureLoading" @click="searchLiterature">
                          <svg v-if="!literatureLoading" viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                          {{ literatureLoading ? '检索中…' : '搜索文献' }}
                        </button>
                      </div>
                      <div class="lit-search-hint">
                        <svg viewBox="0 0 24 24" width="13" height="13"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        留空时将自动使用论文标题进行在线检索
                      </div>
                    </div>

                    <!-- 加载中 -->
                    <div v-if="literatureLoading" class="lit-state lit-loading">
                      <span class="lit-spinner"></span>
                      <span class="lit-state-text">正在检索相关文献…</span>
                    </div>

                    <!-- 搜索结果 -->
                    <div v-else-if="literatureResults.length" class="lit-results">
                      <div class="lit-results-head">
                        <span class="lit-results-count">检索到 <em>{{ literatureResults.length }}</em> 条相关文献</span>
                      </div>
                      <div
                        v-for="(item, idx) in literatureResults"
                        :key="idx"
                        class="lit-card"
                        :class="{ selected: isLiteratureSelected(item) }"
                      >
                        <label class="lit-main-check" @click.stop>
                          <input
                            type="checkbox"
                            :checked="isLiteratureSelected(item)"
                            @change="toggleMainSelect(item)"
                          />
                          <span class="check-box"></span>
                        </label>
                        <div class="lit-card-body">
                          <div class="lit-card-head">
                            <div class="lit-card-title">{{ item.article_title || item.title || '无标题' }}</div>
                            <span v-if="item.Year" class="lit-card-year">{{ item.Year }}</span>
                          </div>

                          <div v-if="item.Summary || item.abstract" class="lit-abstract-wrap">
                            <div
                              class="lit-abstract"
                              :class="{ expanded: isAbstractExpanded(item) }"
                            >
                              {{ item.Summary || item.abstract }}
                            </div>
                            <button
                              v-if="(item.Summary || item.abstract || '').length > 72"
                              class="lit-abstract-toggle"
                              @click.stop="toggleAbstract(item)"
                            >
                              <svg viewBox="0 0 24 24" width="14" height="14" :class="{ flip: isAbstractExpanded(item) }"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                              {{ isAbstractExpanded(item) ? '收起摘要' : '展开摘要' }}
                            </button>
                          </div>

                          <div v-if="item.citations && Object.keys(item.citations).length" class="lit-citation">
                            <div class="lit-citation-header">
                              <span>引用格式</span>
                              <select
                                v-model="litCitationFormats[idx]"
                                class="lit-citation-select"
                                @click.stop
                              >
                                <option v-for="fmt in Object.keys(item.citations)" :key="fmt" :value="fmt">{{ fmt }}</option>
                              </select>
                            </div>
                            <div class="lit-citation-text">{{ item.citations[litCitationFormats[idx]] || '' }}</div>
                          </div>

                          <div v-if="isLiteratureSelected(item)" class="lit-options" @click.stop>
                            <label class="lit-chip" :class="{ active: isLitRef(item, 'reference') }">
                              <input
                                type="checkbox"
                                :checked="isLitRef(item, 'reference')"
                                @change="toggleLitRef(item, 'reference')"
                              />
                              <span>插入引用</span>
                            </label>
                            <label class="lit-chip" :class="{ active: isLitRef(item, 'content') }">
                              <input
                                type="checkbox"
                                :checked="isLitRef(item, 'content')"
                                @change="toggleLitRef(item, 'content')"
                              />
                              <span>参考内容生成</span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- 无结果 -->
                    <div v-else-if="literatureSearched" class="lit-state lit-empty">
                      <span class="lit-state-text">未检索到相关在线文献，建议更换关键词或仅使用论文标题再试</span>
                    </div>

                    <!-- 初始占位 -->
                    <div v-else class="lit-state lit-placeholder">
                      <span class="lit-state-text">输入关键词搜索在线文献，留空时将自动使用论文标题进行匹配</span>
                    </div>
                  </div>
                </div>

                <div class="step-actions">
                  <button class="btn btn-secondary prev-btn" @click="fullStep = 2">上一步</button>
                  <button class="btn btn-primary next-btn" @click="goToConfirm">
                    下一步
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                  </button>
                </div>
              </div>

              <!-- 第4步：确认下单 -->
              <div v-if="fullStep === 4" class="step-card">
                <div class="step-card-head compact">
                  <h3>确认订单信息</h3>
                  <p>核对论文信息、选择增值服务后提交订单</p>
                </div>

                <div class="form-section">
                  <div class="section-title">订单信息</div>
                  <div class="order-summary">
                    <div class="summary-row">
                      <span>论文标题</span>
                      <span>{{ fullForm.title || '未填写' }}</span>
                    </div>
                    <div class="summary-row">
                      <span>学科专业</span>
                      <span>{{ fullForm.subject || '未选择' }}</span>
                    </div>
                    <div class="summary-row">
                      <span>学历层次</span>
                      <span>{{ fullForm.degree }}</span>
                    </div>
                    <div class="summary-row">
                      <span>总字数</span>
                      <span>{{ finalWords(fullForm) }} 字</span>
                    </div>
                    <div class="summary-row">
                      <span>生成模型</span>
                      <span>{{ modelLabel(fullForm.model) }}</span>
                    </div>
                    <div class="summary-row">
                      <span>论文模板</span>
                      <span>{{ fullForm.templateName || '通用格式' }}</span>
                    </div>
                    <div class="summary-row total">
                      <span>基础费用</span>
                      <span>¥{{ basePrice }}</span>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="section-title">增值服务（可选）</div>
                  <div class="service-list">
                    <div
                      v-for="s in services"
                      :key="s.id"
                      class="service-card"
                      :class="{ selected: selectedServices.includes(s.id) }"
                      @click="toggleService(s.id)"
                    >
                      <div class="service-icon">{{ s.icon }}</div>
                      <div class="service-info">
                        <div class="service-name">{{ s.title }}</div>
                        <div class="service-desc">{{ s.desc }}</div>
                      </div>
                      <div class="service-price">¥{{ s.price }}</div>
                      <div class="service-check">
                        <svg v-if="selectedServices.includes(s.id)" viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="order-total">
                  <span>合计：</span>
                  <span class="total-price">¥{{ totalPrice }}</span>
                </div>

                <div class="step-actions">
                  <button class="btn btn-secondary prev-btn" @click="fullStep = 3">上一步</button>
                  <button class="btn btn-primary submit-btn" @click="submitOrder">
                    提交订单
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

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
              <!-- 论文生成 -->
              <div class="price-section">
                <div class="price-section-head">
                  <span class="price-section-icon primary">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                  </span>
                  <span class="price-section-title">论文生成（按模型 × 字数）</span>
                  <span class="price-section-tag">核心服务</span>
                </div>
                <div class="price-table price-table-model">
                  <div class="price-table-head">
                    <span>字数区间</span>
                    <span v-for="col in priceData.modelColumns" :key="col.code">{{ col.name }}</span>
                  </div>
                  <div v-for="(row, rIdx) in priceData.thesis" :key="rIdx" class="price-table-row">
                    <span class="price-col-range">{{ row.range }}</span>
                    <span v-for="col in priceData.modelColumns" :key="col.code" class="price-col-price" :class="col.code">￥{{ row[col.code] }}</span>
                  </div>
                </div>
                <div class="price-table-legend">
                  <span v-for="(col, i) in priceData.modelColumns" :key="col.code" class="legend-item">
                    <i class="legend-dot" :class="col.code"></i>{{ col.name }}{{ col.tag ? '：' + col.tag : '' }}
                  </span>
                </div>
              </div>

              <!-- 增值服务 -->
              <div class="price-section">
                <div class="price-section-head">
                  <span class="price-section-icon green">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                  </span>
                  <span class="price-section-title">增值服务</span>
                  <span class="price-section-tag">附赠 / 加价</span>
                </div>
                <div class="price-grid-2">
                  <div v-for="s in priceData.service" :key="s.name" class="price-grid-item">
                    <span class="grid-item-name">{{ s.name }}</span>
                    <span class="grid-item-price free" :class="{ paid: s.price !== '免费' }">{{ s.price }}</span>
                  </div>
                </div>
              </div>

              <!-- 说明 -->
              <div class="price-note">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <span>以上价格仅供参考，最终以订单确认页显示为准；增值服务可与论文生成组合计费。</span>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- 模板选择弹窗 -->
    <Teleport to="body">
      <Transition name="price-modal">
        <div v-if="showTplModal" class="price-modal-mask" @click.self="showTplModal = false">
          <div class="price-modal tpl-modal">
            <div class="price-modal-head">
              <div class="price-modal-title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <span>选择格式模板</span>
              </div>
              <button class="price-modal-close" @click="showTplModal = false">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>
            <div class="price-modal-body tpl-modal-body">
              <!-- 模板分类切换 -->
              <div class="tpl-type-switch">
                <button class="tpl-type-btn" :class="{ active: tplTab === 'public' }" @click="switchTplTab('public')">公共模板</button>
                <button class="tpl-type-btn" :class="{ active: tplTab === 'private' }" @click="switchTplTab('private')">私有模板</button>
              </div>
              <div class="tpl-view-bar">
                <div class="tpl-view-search">
                  <svg class="tpl-search-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                  <input v-model="templateKeyword" type="text" placeholder="输入模板名称搜索" @input="onTemplateSearch" />
                </div>
              </div>
              <div class="tpl-view-body">
                <div v-if="templateLoading && !templateResults.length" class="tpl-loading">正在加载模板…</div>
                <div v-else-if="templateResults.length" class="tpl-grid">
                  <div
                    v-if="tplTab === 'public'"
                    class="tpl-card"
                    :class="{ selected: quickForm.templateId === 0 }"
                    @click="pickTemplate({ id: 0, name: '通用格式', profession: '', years: '', avt: '', degree: '' })"
                  >
                    <div class="tpl-card-preview tpl-general-preview">
                      <svg viewBox="0 0 24 24" width="32" height="32"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </div>
                    <div class="tpl-card-info">
                      <div class="tpl-card-name">通用格式</div>
                      <div class="tpl-card-meta">GB/T 7714 国标</div>
                    </div>
                    <span v-if="quickForm.templateId === 0" class="tpl-card-check">
                      <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </span>
                  </div>
                  <div
                    v-for="t in templateResults"
                    :key="t.id"
                    class="tpl-card"
                    :class="{ selected: quickForm.templateId === t.id }"
                    @click="pickTemplate(t)"
                  >
                    <div class="tpl-card-preview">
                      <img v-if="t.avt" :src="t.avt" alt="" @error="onTplLogoError" />
                      <span class="tpl-card-placeholder" :style="t.avt ? 'display:none' : ''">{{ t.name.charAt(0) }}</span>
                    </div>
                    <div class="tpl-card-info">
                      <div class="tpl-card-name">{{ t.name }}</div>
                      <div class="tpl-card-meta">{{ t.degree }} · {{ t.profession }}</div>
                      <div class="tpl-card-year">{{ t.years }}</div>
                    </div>
                    <span v-if="quickForm.templateId === t.id" class="tpl-card-check">
                      <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </span>
                  </div>
                </div>
                <div v-else-if="templateSearched" class="tpl-empty">
                  <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" opacity="0.4" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                  <p v-if="tplTab === 'private'">暂无私有模板</p>
                  <p v-else-if="templateKeyword">未找到包含"{{ templateKeyword }}"的模板</p>
                  <p v-else>暂无可用模板</p>
                  <span v-if="tplTab === 'public'">可使用通用格式，或联系客服添加</span>
                  <span v-else>可前往「模板制作」上传自己的模板</span>
                </div>
              </div>
              <div class="tpl-view-foot">
                <div class="tpl-stats-banner" v-if="modalTotal > 0 || modalDbTotal > 0">
                  <template v-if="tplTab === 'public'">
                    <div v-if="templateKeyword" class="stats-search-row">
                      <svg class="stats-search-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                      <div class="stats-search-text">
                        搜索"<span class="stats-keyword">{{ templateKeyword }}</span>"，共找到
                        <span class="stats-highlight">{{ modalTotal }}</span> 个匹配模板
                      </div>
                    </div>
                    <div v-else class="stats-showcase">
                      <div class="stats-icon-wrap">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z"/></svg>
                      </div>
                      <div class="stats-content">
                        <div class="stats-num-row">
                          <span class="stats-big-num">{{ modalDbTotal.toLocaleString() }}</span>
                          <span class="stats-label">个模板</span>
                          <span class="stats-badge">覆盖全国高校</span>
                        </div>
                        <div class="stats-sub-row">
                          当前随机展示 <span class="stats-em">{{ modalTotal }}</span> 个 · 使用搜索可查找更多模板
                        </div>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="stats-private-row">
                      <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                      <span>私有模板共 <span class="stats-highlight">{{ modalTotal }}</span> 个</span>
                    </div>
                  </template>
                </div>
              </div>
              <div class="tpl-modal-actions">
                <button class="btn btn-primary" @click="showTplModal = false">确认选择</button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'console',
})

useSeoMeta({
  title: '创建订单 - AI写作助手',
  description: '选择论文类型、字数和学科，快速创建写作订单。',
})

const mode = ref('quick')
const fullStep = ref(1)
const resourceTab = ref('chart')
const showSuggest = ref(false)
const selectedServices = ref([])
const subjectList = ref([])
const selectedCategory = ref(null)
const subjectSearch = ref('')

// 将学科分类选择同步到 quickForm.subject，使大纲页"论文参数"面板能正确显示
watch(selectedCategory, (cat) => {
  quickForm.subject = cat ? cat.name : ''
})

// 快速模式当前步骤：1=填写标题与参数 2=选择模板 3=大纲编辑（内嵌组件）
const quickStep = ref(1)
// 订单详情查看大纲时从 URL 传入的 outline_no
const restoreOutlineNo = ref('')

// 快速模式步骤导航（含标题校验）
function goQuickStep(n) {
  if (n === 2 && !quickForm.title.trim()) {
    toast('请先输入论文标题或写作要求', 'warning')
    return
  }
  quickStep.value = n
}

// 模板选择弹窗
const showTplModal = ref(false)

// 格式模板（内嵌视图，按学校去重，无搜索时随机抽取，防爬）
const templateKeyword = ref('')
const templateResults = ref([])
const templateLoading = ref(false)
const templateSearched = ref(false)
const modalTotal = ref(0)      // 当前实际返回数量
const modalDbTotal = ref(0)    // 数据库模板总数
const selectedTemplateLogo = ref('')
let templateTimer = null

// 模板分类 tab：public=公共模板 private=私有模板（收藏为主站 token 级共享、无法按本站用户隔离，不开放）
const tplTab = ref('public')

// 获取 token（与 useAuth 存储格式一致：aidian_auth_v2 JSON 中的 token 字段）
// 兼容旧 key aidian_auth：命中后迁移删除旧 key，避免误判未登录
function getToken() {
  if (process.client) {
    try {
      let raw = localStorage.getItem('aidian_auth_v2')
      if (!raw) {
        const oldRaw = localStorage.getItem('aidian_auth')
        if (oldRaw) {
          raw = oldRaw
          try { localStorage.removeItem('aidian_auth') } catch (e) {}
        }
      }
      if (!raw) return ''
      const data = JSON.parse(raw)
      return data?.token || ''
    } catch (e) {
      return ''
    }
  }
  return ''
}

// 带 token 的 fetch 封装
async function apiFetch(url, options = {}) {
  const token = getToken()
  const headers = { ...(options.headers || {}) }
  if (token) headers['token'] = token
  return fetch(url, { ...options, headers })
}

// 切换模板分类 tab
function switchTplTab(tab) {
  if (tab === tplTab.value) return
  // 私有模板需要登录
  if (tab !== 'public' && !getToken()) {
    toast('请先登录后再查看', 'warning')
    return
  }
  tplTab.value = tab
  templateKeyword.value = ''
  templateResults.value = []
  loadTemplates()
}

async function loadTemplates() {
  templateLoading.value = true
  templateSearched.value = false
  try {
    const kw = templateKeyword.value.trim()
    const url = tplTab.value === 'public'
      ? '/api/pc/searchTemplates?' + new URLSearchParams({ keyword: kw })
      : '/api/pc/userTemplates?' + new URLSearchParams({ keyword: kw })
    const res = await apiFetch(url)
    const json = await res.json()
    if (json.code === -1) {
      toast('登录已过期，请重新登录', 'warning')
      return
    }
    const d = json?.data || {}
    // 后端原样返回 avt（相对路径如 school-logo/xxx.png），前端拼接网关 URL
    templateResults.value = (d.list || []).map(t => ({
      ...t,
      avt: normalizeLogoUrl(t.avt),
    }))
    modalTotal.value = d.total || 0
    modalDbTotal.value = d.db_total || 0
  } catch (e) {
    templateResults.value = []
    modalTotal.value = 0
    modalDbTotal.value = 0
  } finally {
    templateLoading.value = false
    templateSearched.value = true
  }
}

// logo URL 拼接：使用下载网关代理
// 数据库 avt 存储相对路径（school-logo/xxx.png），前端拼接网关 URL，无需下载到本地
function normalizeLogoUrl(avt) {
  if (!avt) return ''
  const v = String(avt).trim()
  if (!v) return ''
  if (/^https?:\/\//i.test(v)) return v
  const gateway = useRuntimeConfig().public.downloadGateway
  // 未配置下载网关时回退为资源直链（自建部署按需配置 NUXT_DOWNLOAD_GATEWAY）
  if (!gateway) return v
  return gateway + '?path=' + encodeURIComponent(v)
}

function onTemplateSearch() {
  clearTimeout(templateTimer)
  templateTimer = setTimeout(loadTemplates, 350)
}

function openTplModal() {
  showTplModal.value = true
  templateKeyword.value = ''
  templateResults.value = []
  loadTemplates()
}

// 进入步骤 2 时自动加载模板列表（若尚未加载）
watch(quickStep, (n) => {
  if (n === 2 && !templateResults.value.length && !templateLoading.value) {
    loadTemplates()
  }
})

function pickTemplate(t) {
  quickForm.templateId = t.id
  // 记录模板来源: 私有模板 tab 存 private, 公共模板存 public
  quickForm.templateSource = tplTab.value === 'private' ? 'private' : 'public'
  if (t.id === 0) {
    quickForm.templateName = '通用格式'
  } else {
    quickForm.templateName = `${t.name} · ${t.profession} · ${t.years}`
  }
  selectedTemplateLogo.value = t.avt || ''
}

// logo 加载失败时，隐藏 img 并显示占位符（首字母）
function onTplLogoError(e) {
  const img = e.target
  img.style.display = 'none'
  const placeholder = img.nextElementSibling
  if (placeholder && placeholder.classList.contains('tpl-card-placeholder')) {
    placeholder.style.display = 'flex'
  }
}

function clearTemplate() {
  quickForm.templateId = 0
  quickForm.templateName = ''
  quickForm.templateSource = ''
  selectedTemplateLogo.value = ''
}

onMounted(async () => {
  // 判断是否需要恢复表单状态——两种恢复场景：
  // 1. F5 刷新：navType === 'reload' AND createNavAway === 'false'
  //    navType 在 SPA 中不更新，由 createNavAway 补偿；createNavAway 在全页面导航不更新，由 navType 补偿
  // 2. 其他情况（首次访问、从其他页面进入、全页面导航）：清除状态
  const navEntries = performance.getEntriesByType('navigation')
  const navType = navEntries.length > 0 ? navEntries[0].type : 'navigate'
  const isReload = navType === 'reload'
  const wasOnCreate = sessionStorage.getItem('createNavAway') === 'false'

  if (isReload && wasOnCreate) {
    // F5 刷新：恢复步骤与表单状态
    mode.value = localStorage.getItem('createMode') || 'quick'
    fullStep.value = parseInt(localStorage.getItem('fullStep') || '1', 10) || 1
    // 刷新页面统一回到下单第一步，不跳回生成大纲步骤，避免自动重新生成大纲（表单保留）
    quickStep.value = 1
    localStorage.setItem('quickStep', '1')

    // 恢复 quickForm
    try {
      const saved = localStorage.getItem('quickForm')
      if (saved) {
        const obj = JSON.parse(saved)
        if (obj.title && obj.title.trim()) {
          Object.assign(quickForm, obj)
        }
      }
    } catch (e) {}
  } else {
    // 从其他页面进入、首次访问、后退/前进、或全页面导航：清除持久化状态
    localStorage.removeItem('createMode')
    localStorage.removeItem('fullStep')
    localStorage.removeItem('quickForm')
    localStorage.removeItem('quickStep')
  }

  // 标记当前在下单页
  sessionStorage.setItem('createNavAway', 'false')

  // 检测 URL 中的 outline_no 参数（从订单详情跳转查看大纲）
  const route = useRoute()
  const urlOutlineNo = route.query.outline_no
  if (urlOutlineNo) {
    restoreOutlineNo.value = String(urlOutlineNo)
    quickStep.value = 3
    mode.value = 'quick'
  }

  // 分站/首页生成入口跳转：?from_sub_site=1&title=xxx&degree=...&subject=...&words=...&model=...
  // 在快速模式的标题与参数中回填，保留 F5 恢复的优先级（恢复后不覆盖）
  const q = route.query
  if (q.from_sub_site === '1' || (!urlOutlineNo && (typeof q.title === 'string' && q.title.trim()))) {
    if (typeof q.title === 'string' && q.title.trim() && !quickForm.title.trim()) quickForm.title = q.title.trim()
    if (typeof q.degree === 'string' && degrees.includes(q.degree)) quickForm.degree = q.degree
    if (typeof q.subject === 'string' && q.subject.trim()) quickForm.subject = q.subject.trim()
    if (typeof q.words === 'string' && q.words.trim() && !quickForm.customWords) quickForm.words = q.words.trim()
    if (typeof q.model === 'string' && q.model.trim()) quickForm.model = q.model.trim()
    quickStep.value = 1
    mode.value = 'quick'
  }

  // 持久化 watch
  watch(quickForm, (v) => localStorage.setItem('quickForm', JSON.stringify(v)), { deep: true })
  watch(mode, (v) => localStorage.setItem('createMode', v))
  watch(fullStep, (v) => localStorage.setItem('fullStep', String(v)))
  watch(quickStep, (v) => localStorage.setItem('quickStep', String(v)))

  try {
    const res = await fetch(useRuntimeConfig().app.baseURL + 'subjects.json')
    const json = await res.json()
    const list = json?.data?.list || []
    // 学科热度排名（数值越大越热门）
    const subjectHeat = {
      '工学': 100, '管理学': 95, '理学': 90, '文学': 85,
      '经济学': 80, '法学': 75, '教育学': 70, '医学': 65,
      '艺术学': 60, '农学': 50, '历史学': 40, '哲学': 30,
    }
    subjectList.value = list.map(item => ({
      id: item.id,
      name: item.name.replace(/^\d+/, ''),
      rawName: item.name,
      heat: subjectHeat[item.name.replace(/^\d+/, '')] || 0,
      children: (item.children || []).map(c => ({
        id: c.id,
        name: c.name
      }))
    })).sort((a, b) => b.heat - a.heat)
    if (subjectList.value.length) {
      // 刷新恢复场景：如果 quickForm.subject 有值，匹配对应学科；否则选第一个
      const restored = quickForm.subject
        ? subjectList.value.find(s => s.name === quickForm.subject)
        : null
      selectedCategory.value = restored || subjectList.value[0]
    }
  } catch (e) {
    console.error('加载学科数据失败', e)
  }

  // 加载后端配置的 AI 模型列表
  try {
    const modelRes = await fetch('/api/ai/models?type=paper')
    const modelJson = await modelRes.json()
    if (modelJson.code === 1 && Array.isArray(modelJson.data)) {
      aiModelsRaw.value = modelJson.data
      // 若当前选中的模型不在后端列表中，回退到第一个可用模型
      const validCodes = aiModelsRaw.value.map(m => m.code)
      if (validCodes.length && !validCodes.includes(quickForm.model)) {
        quickForm.model = validCodes[0]
      }
      if (validCodes.length && !validCodes.includes(fullForm.model)) {
        fullForm.model = validCodes[0]
      }
    }
  } catch (e) {
    console.error('加载模型列表失败', e)
  }
})

// 离开下单页时标记（路由导航守卫，在路由切换前触发；页面刷新不触发）
// - 去其他页面：设为 'true'，返回时清除状态
onBeforeRouteLeave(() => {
  sessionStorage.setItem('createNavAway', 'true')
})

const filteredSubjects = computed(() => {
  if (!selectedCategory.value) return []
  const children = selectedCategory.value.children || []
  if (!subjectSearch.value.trim()) return children
  const kw = subjectSearch.value.trim().toLowerCase()
  return children.filter(s => s.name.toLowerCase().includes(kw))
})

const majors = [
  '哲学', '经济学', '法学', '教育学', '文学', '历史学', '理学', '工学', '农学', '医学',
  '军事学', '管理学', '艺术学', '计算机科学与技术', '工商管理', '会计学', '金融学', '教育学'
]

const degrees = ['专科', '本科', '研究生', '博士', 'MBA']
const wordOptions = ['3000', '5000', '8000', '1万', '2万', '3万', '5万', '10万']
const outlineOptions = [
  { value: 'two', label: '二级大纲' },
  { value: 'three', label: '三级大纲' }
]
const languages = ['中文', '英文', '日语', '韩语', '俄语', '泰语']


// 收费标准弹窗
const showPriceModal = ref(false)

// 字数区间标签
const wordRangeLabels = ['5000 字以内', '5001 - 10000 字', '10001 - 20000 字', '20000 字以上']

// 价格数据：从后端 write_type_config.price_config 动态构建
const priceData = computed(() => {
  const models = aiModelsRaw.value
  const modelColumns = models.map(m => ({ code: m.code, name: m.name, tag: m.tag || '' }))

  // 从第一个有 price_config 的模型获取字数区间数
  const firstWithConfig = models.find(m => m.price_config)
  let tierCount = 0
  if (firstWithConfig) {
    try { tierCount = JSON.parse(firstWithConfig.price_config).length } catch (e) {}
  }

  // 构建表格行：每行 = { range, [modelCode]: price }
  const thesis = []
  for (let i = 0; i < tierCount; i++) {
    const row = { range: wordRangeLabels[i] || `区间${i + 1}` }
    models.forEach(m => {
      let config = []
      try { config = JSON.parse(m.price_config || '[]') } catch (e) {}
      row[m.code] = config[i] ? config[i].price : '—'
    })
    thesis.push(row)
  }

  return {
    thesis,
    modelColumns,
    service: [
      { name: '降低 AI 率', price: '+30%' },
      { name: '自定义大纲', price: '免费' },
      { name: '图表 / 公式 / 代码', price: '免费' },
      { name: '多语言生成', price: '免费' },
    ],
  }
})

const showMoreSubjects = ref(false)
const showMoreWords = ref(false)
const showMoreLanguages = ref(false)

const displayedCategories = computed(() => {
  const list = subjectList.value
  if (showMoreSubjects.value || list.length <= 8) return list
  const visible = list.slice(0, 8)
  if (selectedCategory.value && !visible.includes(selectedCategory.value)) {
    return [...visible, selectedCategory.value]
  }
  return visible
})

const subjectMoreCount = computed(() => Math.max(0, subjectList.value.length - 8))

const displayedWordOptions = computed(() => {
  if (showMoreWords.value || wordOptions.length <= 5) return wordOptions
  const visible = wordOptions.slice(0, 5)
  if (quickForm.words && !visible.includes(quickForm.words)) {
    return [...visible, quickForm.words]
  }
  return visible
})

const displayedLanguages = computed(() => {
  if (showMoreLanguages.value || languages.length <= 4) return languages
  const visible = languages.slice(0, 4)
  if (quickForm.language && !visible.includes(quickForm.language)) {
    return [...visible, quickForm.language]
  }
  return visible
})
const aiModelsRaw = ref([])
const aiModels = computed(() => aiModelsRaw.value.map(m => ({
  value: m.code,
  label: m.name,
  tag: m.tag || ''
})))

// 高级模型专属优势（基于 lunwen-new 高级版真实功能，后续可对接后端模型配置）
const advancedModelAdvantages = [
  {
    icon: '🚀',
    title: '更高级模型',
    desc: '强推理模型 + 高级提示词，学术性显著提升'
  },
  {
    icon: '📚',
    title: '真实文献引用',
    desc: '每段引用真实文献，附原文链接可溯源'
  },
  {
    icon: '🖼️',
    title: '文献原图引用',
    desc: '原图来自真实文献，附链接可溯源'
  },
  {
    icon: '📊',
    title: '专业图表模板',
    desc: '更多高质量图表，数据附链接可溯源'
  },
  {
    icon: '🔗',
    title: '段落来源标注',
    desc: '每段标注来源链接，全篇可溯源'
  },
  {
    icon: '📖',
    title: '文献原文链接',
    desc: '参考文献附原文链接，一键溯源'
  }
]

const steps = [
  { title: '提交标题', sub: '输入论文标题' },
  { title: '完善信息', sub: '补充学历/专业' },
  { title: '选择资源', sub: '图表/文献' },
  { title: '确认下单', sub: '核对并支付' }
]

const suggestTypes = ['实证研究', '理论研究', '案例研究', '对比研究', '综述性研究']
const suggestForm = reactive({ interest: '', type: '实证研究' })
const suggestedTitles = ref([])
const suggestLoading = ref(false)
const api = useApi()

const templates = ['清华大学本科毕业论文模板', '北京大学硕士论文模板', '郑州大学本科论文模板', '通用 GB/T 7714 格式']

const services = [
  { id: 'aigc', title: 'AIGC 率降低', desc: '自动优化表达，降低知网/维普 AIGC 检测率', price: 15, icon: '🛡️' },
  { id: 'proofread', title: '人工精校', desc: '专业编辑逐字校对，提升语言流畅度', price: 30, icon: '✍️' },
  { id: 'format', title: '论文格式排版', desc: '按学校模板自动排版，含目录、页眉页脚、引用格式', price: 25, icon: '📄' },
  { id: 'chart', title: '高级图表', desc: 'AI 生成专业数据图表与可视化', price: 10, icon: '📊' },
]

const quickForm = reactive({
  subject: '',
  title: '',
  major: '',
  degree: '本科',
  words: '1万',
  customWords: '',
  model: 'standard',
  outlineLevel: 'two',
  language: '中文',
  needChart: true,
  needLowerAI: false,
  useCustomOutline: false,
  customOutline: '',
  templateId: 0,
  templateName: '',
  templateSource: '',
  literatureCount: 25,
  enLiteratureCount: 0,
  is_advanced: 0
})

// 恢复 quickForm 状态（在 onMounted 中执行，避免 SSR hydration mismatch）
// 见 onMounted 中的状态恢复逻辑

// 当前快捷模式所选模型是否为高级版(is_advanced=1),用于 OutlineEditor 显隐"文献原图"选项
const isAdvancedModel = computed(() => {
  const m = aiModelsRaw.value.find(m => m.code === quickForm.model)
  return Number(m?.is_advanced) === 1
})
watch(isAdvancedModel, (v) => { quickForm.is_advanced = v ? 1 : 0 }, { immediate: true })

watch(() => quickForm.degree, () => {
  if (quickForm.templateId) clearTemplate()
})

const fullForm = reactive({
  subject: '',
  title: '',
  degree: '本科',
  major: '',
  words: '1万',
  customWords: '',
  model: 'standard',
  outlineLevel: 'two',
  language: '中文',
  keywords: '',
  feedContent: '',
  needChart: true,
  needTable: false,
  needReference: false,
  template: '',
  templateId: 0,
  templateName: '',
  needFeedFile: false,
  agreed: true,
  selectedLiterature: []
})

// 完整模式所选模型是否为高级版(is_advanced=1)
const isFullAdvanced = computed(() => {
  const m = aiModelsRaw.value.find(m => m.code === fullForm.model)
  return Number(m?.is_advanced) === 1
})

// 在线文献搜索
const literatureKeyword = ref('')
const literatureResults = ref([])
const literatureLoading = ref(false)
const literatureSearched = ref(false)
const expandedAbstracts = ref(new Set())
const litCitationFormats = ref({})

function getLiteratureUniqueId(item) {
  return item.unique_id || item.id || item.article_title || item.title || ''
}

function getDefaultCitation(item) {
  if (!item.citations || typeof item.citations !== 'object') return ''
  const keys = Object.keys(item.citations)
  if (!keys.length) return ''
  const gbKey = keys.find(k => k.includes('GB/T 7714'))
  return item.citations[gbKey || keys[0]] || ''
}

function isLitRef(item, key) {
  const uid = getLiteratureUniqueId(item)
  const selected = fullForm.selectedLiterature.find(l => l.unique_id === uid)
  return selected ? !!selected[key] : false
}

function isLiteratureSelected(item) {
  return isLitRef(item, 'reference') || isLitRef(item, 'content')
}

function isAbstractExpanded(item) {
  return expandedAbstracts.value.has(getLiteratureUniqueId(item))
}

function toggleAbstract(item) {
  const uid = getLiteratureUniqueId(item)
  if (expandedAbstracts.value.has(uid)) {
    expandedAbstracts.value.delete(uid)
  } else {
    expandedAbstracts.value.add(uid)
  }
}

function ensureSelectedItem(item) {
  const uid = getLiteratureUniqueId(item)
  let idx = fullForm.selectedLiterature.findIndex(l => l.unique_id === uid)
  if (idx === -1) {
    fullForm.selectedLiterature.push({
      unique_id: uid,
      title: item.article_title || item.title || '无标题',
      abstract: item.Summary || item.abstract || '',
      citation: getDefaultCitation(item),
      reference: false,
      content: false
    })
    idx = fullForm.selectedLiterature.length - 1
  }
  return fullForm.selectedLiterature[idx]
}

function toggleLitRef(item, key) {
  const selected = ensureSelectedItem(item)
  selected[key] = !selected[key]
  if (!selected.reference && !selected.content) {
    const uid = getLiteratureUniqueId(item)
    const idx = fullForm.selectedLiterature.findIndex(l => l.unique_id === uid)
    if (idx > -1) fullForm.selectedLiterature.splice(idx, 1)
  }
}

function toggleMainSelect(item) {
  if (isLiteratureSelected(item)) {
    const uid = getLiteratureUniqueId(item)
    const idx = fullForm.selectedLiterature.findIndex(l => l.unique_id === uid)
    if (idx > -1) fullForm.selectedLiterature.splice(idx, 1)
  } else {
    const selected = ensureSelectedItem(item)
    selected.reference = true
  }
}

function removeLiterature(idx) {
  fullForm.selectedLiterature.splice(idx, 1)
}

function clearLiterature() {
  fullForm.selectedLiterature = []
}

function litHint(item) {
  const hints = []
  if (isLitRef(item, 'reference')) hints.push('文档标记引用点')
  if (isLitRef(item, 'content')) hints.push('参考文献内容生成段落')
  return hints.length ? hints.join('，') : ''
}

async function searchLiterature() {
  const keyword = literatureKeyword.value.trim() || fullForm.title.trim()
  if (!keyword) {
    toast('请输入搜索关键词或先填写论文标题', 'warning')
    return
  }
  literatureLoading.value = true
  literatureSearched.value = false
  expandedAbstracts.value.clear()
  try {
    const res = await apiFetch('/api/write/onlineLiterature', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: keyword })
    })
    const json = await res.json()
    if (json.code === 1 && Array.isArray(json.data)) {
      literatureResults.value = json.data
      const formats = {}
      json.data.forEach((item, idx) => {
        const keys = Object.keys(item.citations || {})
        const gb = keys.find(k => k.includes('GB/T 7714'))
        formats[idx] = gb || keys[0] || ''
      })
      litCitationFormats.value = formats
    } else {
      literatureResults.value = []
      litCitationFormats.value = {}
      toast(json.msg || '检索失败', 'warning')
    }
  } catch (e) {
    literatureResults.value = []
    litCitationFormats.value = {}
    toast('网络异常，请稍后重试', 'error')
  } finally {
    literatureLoading.value = false
    literatureSearched.value = true
  }
}

const basePrice = computed(() => {
  const words = finalWords(fullForm)
  const num = parseInt(words) || 10000
  return Math.max(9.9, (num / 1000) * 2.5).toFixed(2)
})

const totalPrice = computed(() => {
  const extra = selectedServices.value.reduce((sum, id) => {
    const s = services.find(x => x.id === id)
    return sum + (s ? s.price : 0)
  }, 0)
  return (parseFloat(basePrice.value) + extra).toFixed(2)
})

function finalWords(form) {
  if (form.customWords) return form.customWords
  const map = { '3000': 3000, '5000': 5000, '8000': 8000, '1万': 10000, '2万': 20000, '3万': 30000, '5万': 50000, '10万': 100000 }
  return map[form.words] || 10000
}

function modelLabel(value) {
  const m = aiModels.value.find(x => x.value === value)
  return m ? m.label : value
}

function openTitleSuggest() {
  showSuggest.value = !showSuggest.value
}

async function generateTitles() {
  if (!suggestForm.interest.trim()) {
    toast('请输入研究方向', 'warning')
    return
  }
  if (suggestLoading.value) return
  suggestLoading.value = true
  try {
    const res = await api.post('/api/tools/createtitle', {
      researchText: suggestForm.interest.trim(),
      titleType: suggestForm.type,
    })
    if (res.ok && Array.isArray(res.data?.titles) && res.data.titles.length) {
      suggestedTitles.value = res.data.titles.map(t => ({
        title: String(t.title || ''),
        desc: String(t.abstract || t.desc || ''),
      }))
    } else {
      toast(res.msg || '题目生成失败，请稍后重试', 'error')
    }
  } catch (e) {
    toast('网络异常，请稍后重试', 'error')
  } finally {
    suggestLoading.value = false
  }
}

function selectTitle(t) {
  if (mode.value === 'quick') {
    quickForm.title = t.title
  } else {
    fullForm.title = t.title
  }
  showSuggest.value = false
}

function goStep(n) {
  if (n <= fullStep.value) fullStep.value = n
}

function goToConfirm() {
  fullStep.value = 4
}

function toggleService(id) {
  const idx = selectedServices.value.indexOf(id)
  if (idx > -1) selectedServices.value.splice(idx, 1)
  else selectedServices.value.push(id)
}

function onQuickGenerate() {
  // 登录鉴权：生成大纲需登录
  if (!getToken()) {
    toast('请先登录后再生成大纲', 'warning')
    return
  }
  if (!quickForm.title.trim()) {
    toast('请输入论文标题或写作要求', 'warning')
    return
  }
  if (quickForm.useCustomOutline && !quickForm.customOutline.trim()) {
    toast('请输入自定义大纲内容，或取消勾选"自定义大纲"', 'warning')
    return
  }
  // 切换到步骤 3：大纲编辑（OutlineEditor 组件内嵌，无需跳转页面）
  quickStep.value = 3
}

// OutlineEditor 组件事件处理
function onOutlineBack() {
  quickStep.value = 2
}
// 支付完成后返回下单首页（第一步）
function onOutlineReset() {
  quickStep.value = 1
}
function onOutlinePaid(result) {
  // 支付成功后刷新用户余额（如果有 user ref）
  if (result?.left_money != null && auth.user.value) {
    auth.user.value.user_money = result.left_money
  }
  // 订单流程结束：清除下单页持久化缓存，确保下次进入视为新订单（不再恢复到已支付的大纲步骤）
  localStorage.removeItem('createMode')
  localStorage.removeItem('fullStep')
  localStorage.removeItem('quickForm')
  localStorage.removeItem('quickStep')
  // 标记已离开下单流程：即使随后整页刷新或从其他页返回，也不会触发 F5 恢复分支
  sessionStorage.setItem('createNavAway', 'true')
}

// 载入大纲示例
function loadOutlineSample() {
  quickForm.customOutline = `一、绪论
1.1 研究背景与意义
1.2 国内外研究现状
1.3 研究内容与方法
二、相关理论基础
2.1 理论概述
2.2 理论应用分析
三、研究设计
3.1 研究假设
3.2 样本选择与数据来源
3.3 研究方法
四、实证分析
4.1 描述性统计
4.2 相关性分析
4.3 回归分析
五、结论与建议
5.1 研究结论
5.2 政策建议
5.3 研究不足与展望`
}

function submitOrder() {
  // 登录鉴权：提交订单需登录
  if (!getToken()) {
    toast('请先登录后再提交订单', 'warning')
    return
  }
  if (!fullForm.title.trim()) {
    toast('请先填写论文标题', 'warning')
    fullStep.value = 1
    return
  }
  toast(`订单提交成功，合计 ¥${totalPrice.value}`, 'success')
}

function toast(msg, type = 'info') {
  const t = useToast()
  t[type](msg)
}

// 监听 console 布局顶栏刷新按钮：通过 window 自定义事件通信
onMounted(() => {
  window.addEventListener('console-refresh', handleRefreshEvent)
})
onUnmounted(() => {
  window.removeEventListener('console-refresh', handleRefreshEvent)
})
function handleRefreshEvent() {
  loadTemplates()
}
</script>

<style scoped>
.create-page {
  background: #f8fafc;
}

.create-body {
  position: relative;
  padding-bottom: 48px;
  z-index: 3;
}

.page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.page-head-tabs {
  display: inline-flex;
  gap: 4px;
  padding: 3px;
  background: #f1f5f9;
  border-radius: 8px;
}

.tab-btn {
  padding: 6px 16px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: #334155;
}

.tab-btn.active {
  background: #fff;
  color: #0f172a;
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.mode-panel {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.order-card.compact {
  background: #fff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.order-flow-wrap {
  position: relative;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--gray-100);
}

.order-flow {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* 收费标准按钮 */
.price-standard-btn {
  position: absolute;
  right: 0;
  top: -2px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s;
}

.price-standard-btn:hover {
  color: var(--primary-600);
  border-color: var(--primary-300);
  background: var(--primary-50);
  transform: translateY(-1px);
}

.price-standard-btn svg {
  color: var(--accent-500);
}

.flow-step {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-400);
}

.flow-step.done {
  color: var(--primary-600);
}

.flow-step.current {
  color: var(--accent-500);
}

.flow-dot {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  background: var(--gray-100);
  color: var(--gray-400);
}

.flow-step.done .flow-dot {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
}

.flow-step.current .flow-dot {
  background: linear-gradient(135deg, var(--accent-500) 0%, #ea580c 100%);
  color: #fff;
  box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
}

.flow-line {
  width: 40px;
  height: 2px;
  background: var(--gray-100);
  border-radius: 1px;
}

.flow-line.done {
  background: linear-gradient(90deg, var(--primary-500) 0%, var(--primary-400) 100%);
}

.form-section {
  padding-bottom: 22px;
  margin-bottom: 22px;
  border-bottom: 1px solid var(--gray-100);
}

.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title-text {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 800;
  color: var(--dark-700);
  margin-bottom: 16px;
}

.section-title-text::before {
  content: '';
  width: 4px;
  height: 18px;
  border-radius: 2px;
  background: linear-gradient(180deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 14px;
}

.label-icon {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.param-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  align-items: start;
  gap: 0;
}

.param-grid.compact-grid {
  gap: 0;
  margin-top: 14px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 14px;
  overflow: hidden;
}

.param-cell {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--gray-100);
  transition: background 0.15s;
}

.param-cell:hover {
  background: var(--gray-50);
}

.param-cell.wide {
  grid-column: 1 / -1;
}

.cell-label {
  flex-shrink: 0;
  width: 72px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  line-height: 34px;
  letter-spacing: 0.3px;
}

.cell-select {
  width: 100%;
  padding: 9px 36px 9px 14px;
  border: 1.5px solid var(--gray-200);
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  background: #fff;
  appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='18' height='18'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  transition: border-color 0.2s;
}

.cell-select:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.step-card {
  background: #fff;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 20px;
}

.title-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 800;
  color: #fff;
}

.title-icon.teal {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.subject-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 18px;
  margin-bottom: 18px;
  border-bottom: 1px solid var(--gray-100);
  flex-wrap: wrap;
}

.subject-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  flex: 1;
  align-items: center;
}

.subject-tabs.compact {
  gap: 6px;
}

.subject-tab {
  padding: 7px 14px;
  border-radius: 8px;
  border: 1px solid var(--gray-200);
  background: #fff;
  color: var(--gray-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.subject-tabs.compact .subject-tab {
  padding: 7px 14px;
  font-size: 13px;
}

.subject-tab:hover {
  border-color: var(--primary-300);
  color: var(--primary-600);
  background: var(--primary-50);
}

.subject-tab.active {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  border-color: transparent;
  color: #fff;
}

.subject-tab.more-tab {
  border-style: dashed;
  color: var(--primary-600);
  background: rgba(20, 184, 166, 0.06);
}

.subject-tab.more-tab:hover {
  background: rgba(20, 184, 166, 0.12);
}

.subject-choose select {
  min-width: 180px;
  padding: 10px 36px 10px 14px;
  border: 1.5px solid var(--gray-200);
  border-radius: 12px;
  font-size: 14px;
  outline: none;
  background: #fff;
  appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='18' height='18'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
}

.topic-area {
  margin-bottom: 18px;
}

.topic-input-wrap {
  position: relative;
}

.smart-topic-btn {
  position: absolute;
  right: 12px;
  top: 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.smart-topic-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(251, 191, 36, 0.25);
}

.topic-textarea {
  width: 100%;
  padding: 16px 130px 16px 16px;
  border: 1.5px solid var(--gray-200);
  border-radius: 14px;
  font-size: 15px;
  line-height: 1.7;
  resize: vertical;
  outline: none;
  transition: all 0.2s ease;
  background: #fff;
  min-height: 96px;
}

.topic-textarea:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
}

.topic-counter {
  margin-top: 6px;
  font-size: 12px;
  color: var(--gray-500);
  text-align: right;
}

.topic-counter.danger {
  color: #e11d48;
  font-weight: 700;
}

.params-rows {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 16px;
  background: #f8fafc;
  border-radius: 16px;
  border: 1px solid var(--gray-100);
}

.params-rows.flat {
  gap: 18px;
  padding: 20px;
  background: #f1f5f9;
  border-radius: 16px;
}

.param-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  flex-wrap: wrap;
}

.param-row.extras-row {
  align-items: flex-start;
}

.row-label {
  flex-shrink: 0;
  width: 64px;
  padding-top: 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-700);
  text-align: right;
}

.param-control {
  flex: 1;
  min-width: 220px;
  max-width: 360px;
}

.option-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  flex: 1;
  align-items: center;
}

.option-pills.compact {
  gap: 8px;
}

.option-pill {
  padding: 7px 16px;
  border-radius: 8px;
  border: 1px solid var(--gray-200);
  background: #fff;
  color: var(--gray-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.option-pills.compact .option-pill {
  padding: 7px 16px;
  font-size: 13px;
}

.option-pill:hover {
  border-color: var(--primary-300);
  color: var(--primary-600);
  background: var(--primary-50);
}

.option-pill.more-pill {
  border-style: dashed;
  color: var(--primary-600);
  background: rgba(20, 184, 166, 0.06);
}

.option-pill.more-pill:hover {
  background: rgba(20, 184, 166, 0.12);
}

.option-pill.active {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.2);
}

.word-options {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.word-options.compact {
  gap: 8px;
}

.word-options.compact .option-pill,
.word-options.compact .custom-word {
  padding: 7px 16px;
  font-size: 13px;
}

.custom-word {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 6px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  background: #fff;
  transition: all 0.2s ease;
  flex-shrink: 0;
  width: 130px;
  height: 32px;
}

.word-options.compact .custom-word {
  width: 130px;
  height: 32px;
}

.custom-word.active {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.custom-word input {
  width: 82px;
  border: none;
  outline: none;
  font-size: 14px;
  text-align: center;
  font-weight: 600;
  line-height: 1;
  padding: 0;
  background: transparent;
}

.custom-word span {
  line-height: 1;
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-600);
}

/* 文献条数输入项 */
.lit-count-input {
  display: flex;
  align-items: center;
  gap: 8px;
}
.lit-count-input .custom-word {
  width: 110px;
  height: 32px;
}
.lit-count-input .custom-word input {
  width: 56px;
}
.cell-hint {
  font-size: 12px;
  color: var(--gray-400);
  white-space: nowrap;
}

.model-options {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  flex: 1;
  align-items: center;
}

/* 模型单元格内容容器：包裹模型卡片 + 优势展示，避免破坏 param-cell/field 的网格布局 */
.model-cell-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.model-options.compact {
  gap: 8px;
}

.model-card {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 16px;
  border-radius: 8px;
  border: 1px solid var(--gray-200);
  background: #fff;
  color: var(--gray-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.model-options.compact .model-card {
  padding: 7px 16px;
  font-size: 13px;
}

.model-card:hover {
  border-color: var(--primary-300);
  background: var(--primary-50);
}

.model-card.active {
  border-color: var(--primary-500);
  background: #f0fdfa;
  color: var(--primary-700);
}

.model-tag {
  font-size: 11px;
  padding: 2px 7px;
  border-radius: 6px;
  background: var(--accent-500);
  color: #fff;
}

/* 高级模型专属优势展示 */
.model-advantages {
  margin-top: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 55%, #f0fdfa 100%);
  border: 1px solid #99f6e4;
  box-shadow: 0 2px 10px rgba(20, 184, 166, 0.07);
}

.adv-banner {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 8px;
}

.adv-badge {
  flex-shrink: 0;
  padding: 2px 7px;
  border-radius: 5px;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.25);
}

.adv-banner-icon {
  font-size: 14px;
  line-height: 1;
}

.adv-banner-text {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.adv-banner-title {
  font-size: 12.5px;
  font-weight: 700;
  color: #0f766e;
  line-height: 1.3;
}

.adv-banner-sub {
  font-size: 10.5px;
  color: var(--gray-500);
  line-height: 1.3;
}

.adv-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 5px;
}

.adv-item {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 4px 7px;
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.7);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.adv-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(20, 184, 166, 0.1);
}

.adv-item-icon {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
  font-size: 13px;
  line-height: 1;
}

.adv-item-body {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.adv-item-title {
  font-size: 11.5px;
  font-weight: 600;
  color: #334155;
  line-height: 1.3;
}

.adv-item-desc {
  font-size: 10.5px;
  color: var(--gray-500);
  line-height: 1.4;
}

/* 完整模式：indigo 配色 */
.full-panel .model-advantages {
  background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 55%, #eef2ff 100%);
  border-color: #c7d2fe;
  box-shadow: 0 2px 10px rgba(99, 102, 241, 0.07);
}

.full-panel .adv-badge {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
}

.full-panel .adv-banner-title {
  color: #4338ca;
}

.full-panel .adv-item:hover {
  box-shadow: 0 3px 10px rgba(99, 102, 241, 0.1);
}

.full-panel .adv-item-icon {
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
}

.full-panel .adv-item-title {
  color: #3730a3;
}

/* 展开过渡动画 */
.adv-slide-enter-active,
.adv-slide-leave-active {
  transition: opacity 0.28s ease, max-height 0.28s ease, margin 0.28s ease, transform 0.28s ease;
  overflow: hidden;
}

.adv-slide-enter-from,
.adv-slide-leave-to {
  opacity: 0;
  max-height: 0;
  margin-top: 0;
  transform: translateY(-4px);
}

.adv-slide-enter-to,
.adv-slide-leave-from {
  opacity: 1;
  max-height: 340px;
  transform: translateY(0);
}

.extras-card {
  grid-column: 1 / -1;
}

.inline-checks {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  flex: 1;
  align-items: center;
}

.inline-checks.compact {
  gap: 16px;
  min-height: 34px;
  align-items: center;
}

.inline-checks.compact .check-item {
  font-size: 13px;
}

.check-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--gray-600);
  font-weight: 600;
}

.check-item input {
  display: none;
}

.check-box {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  border: 2px solid var(--gray-300);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.check-item input:checked + .check-box {
  background: var(--primary-500);
  border-color: var(--primary-500);
}

.check-item input:checked + .check-box::after {
  content: '';
  width: 5px;
  height: 9px;
  border-right: 2px solid #fff;
  border-bottom: 2px solid #fff;
  transform: rotate(45deg) translate(-1px, -1px);
}

.upload-area {
  margin-top: 16px;
}

.upload-area.inline {
  margin-top: 12px;
}

.upload-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 22px 28px;
  border: 2px dashed var(--gray-300);
  border-radius: 16px;
  background: #f8fafc;
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.2s ease;
}

.upload-card.small {
  padding: 14px 20px;
  border-radius: 12px;
}

.upload-card.small .upload-main {
  font-size: 14px;
}

.upload-card.small .upload-sub {
  font-size: 12px;
}

.upload-card:hover {
  border-color: var(--primary-500);
  background: #f0fdfa;
}

.upload-icon {
  display: flex;
  color: var(--primary-500);
}

.upload-main {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-700);
}

.upload-sub {
  font-size: 13px;
  margin-top: 4px;
}

.quick-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  padding-top: 18px;
  margin-top: 4px;
}

/* 快速模式步骤面板 */
.quick-step-pane {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* 步骤导航：对称的次要按钮 + 主要按钮 */
.tpl-view-actions {
  justify-content: center;
}

/* 次要按钮（上一步） */
.btn-side-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 140px;
  padding: 12px 28px;
  font-size: 15px;
  font-weight: 600;
  border-radius: 999px;
  background: #fff;
  border: 1.5px solid var(--gray-200);
  color: var(--gray-600);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-side-secondary:hover {
  border-color: var(--primary-400);
  color: var(--primary-600);
  background: var(--gray-50);
}

/* 主要按钮（下一步/生成大纲） */
.btn-side-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 140px;
  padding: 12px 28px;
  font-size: 15px;
  font-weight: 600;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 6px 20px rgba(13, 148, 136, 0.25);
}

.btn-side-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 28px rgba(13, 148, 136, 0.35);
}

.btn-text {
  padding: 10px 16px;
  background: transparent;
  border: none;
  color: var(--primary-600);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.btn-text:hover {
  color: var(--primary-700);
}

/* 完整模式 */
.full-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.full-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.full-stepbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 4px;
  background: transparent;
  border-radius: 0;
  border: none;
  box-shadow: none;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.full-stepbar::-webkit-scrollbar {
  display: none;
}

.full-stepbar-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  cursor: pointer;
  padding: 4px 0;
}

.full-stepbar-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: calc(50% + 18px);
  right: -2px;
  top: 13px;
  height: 2px;
  background: var(--gray-200);
  border-radius: 2px;
  transition: background 0.3s;
}

.full-stepbar-item.active:not(:last-child)::after {
  background: linear-gradient(90deg, #818cf8 0%, #c7d2fe 100%);
}

.full-stepbar-node {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  background: #fff;
  color: var(--gray-400);
  flex-shrink: 0;
  transition: all 0.2s ease;
  border: 1.5px solid var(--gray-200);
}

.full-stepbar-item.active .full-stepbar-node {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 3px 10px rgba(99, 102, 241, 0.2);
}

.full-stepbar-item.current .full-stepbar-node {
  border-color: #6366f1;
  background: #fff;
  color: #6366f1;
}

.full-stepbar-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.full-stepbar-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  transition: color 0.2s;
}

.full-stepbar-item.active .full-stepbar-title {
  color: #312e81;
}

.full-stepbar-sub {
  font-size: 11px;
  color: var(--gray-400);
}

.step-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #fff;
  border: 1px solid #eef2f6;
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
}

.step-card-head {
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.step-card-head h3 {
  font-size: 17px;
  font-weight: 700;
  color: var(--dark-800);
  display: flex;
  align-items: center;
  gap: 8px;
}

.step-card-head h3::before {
  content: '';
  width: 4px;
  height: 16px;
  background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
  border-radius: 2px;
}

.step-card-head p {
  margin-top: 6px;
  font-size: 13px;
  color: var(--gray-500);
  padding-left: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600);
}

.field-label.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 4px;
}

.field input[type="text"],
.field input[type="number"],
.field textarea,
.major-select select {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid var(--gray-200);
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
  background: #fff;
}

.field input:focus,
.field textarea:focus,
.major-select select:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.field textarea {
  resize: vertical;
  line-height: 1.7;
}

.title-input-bar {
  display: flex;
  gap: 12px;
}

.title-input-bar input {
  flex: 1;
  min-width: 0;
}

.title-counter {
  text-align: right;
  font-size: 13px;
  color: var(--gray-400);
}

.suggest-panel {
  padding: 0;
  border-radius: 14px;
  background: #fff;
  border: 1px solid var(--gray-100);
  overflow: hidden;
}

/* quick 模式英雄区的选题面板：与上方标题输入留出间距 */
.suggest-panel--quick {
  margin-top: 12px;
}

.panel-head {
  padding: 12px 18px;
  font-size: 13px;
  font-weight: 700;
  color: var(--gray-600);
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-100);
  letter-spacing: 0.3px;
}

.suggest-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  padding: 16px 18px;
}

.suggest-field {
  flex: 1;
}

.suggest-field label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
  margin-bottom: 6px;
}

.suggest-field input,
.suggest-field select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--gray-200);
  border-radius: 10px;
  outline: none;
  font-size: 14px;
  transition: all 0.2s;
}

.suggest-field input:focus,
.suggest-field select:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.suggest-btn {
  padding: 10px 22px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
}

.suggest-list {
  padding: 0 18px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.suggest-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: #fff;
  border: 1px solid var(--gray-200);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.suggest-item:hover {
  border-color: var(--primary-500);
  background: var(--primary-50);
}

.suggest-title {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-800);
}

.suggest-desc {
  color: var(--gray-500);
  font-size: 12px;
}

.suggest-select {
  font-size: 12px;
  color: var(--primary-600);
  font-weight: 600;
}

.form-grid.two-col {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 12px;
  overflow: hidden;
}

.subject-inline {
  display: flex;
  gap: 8px;
}

.subject-inline .major-select {
  flex: 1;
  min-width: 0;
}

.major-select select {
  width: 100%;
  padding: 10px 36px 10px 14px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
  outline: none;
  background: #fff;
  appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='16' height='16'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  transition: all 0.2s;
}

.major-select select:hover {
  border-color: var(--primary-300);
  background-color: var(--gray-50);
}

.major-select select:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.major-select select option {
  font-size: 13px;
  padding: 8px;
}

.form-grid.two-col .field {
  padding: 14px 18px;
  border-bottom: 1px solid var(--gray-100);
  transition: background 0.15s;
}

.form-grid.two-col .field:hover {
  background: var(--gray-50);
}

.form-grid.two-col .field:nth-child(odd) {
  border-right: 1px solid var(--gray-100);
}

/* 完整模式：分组卡片布局 + 水平标签内容 */
.step-card {
  gap: 18px;
  padding: 28px;
}

.step-card-head.compact {
  padding-bottom: 12px;
  border-bottom: 1px solid var(--gray-100);
}

.form-section {
  background: #fff;
  border: 1px solid #eef2f6;
  border-radius: 14px;
  padding: 20px 22px;
  box-shadow: none;
}

.form-section + .form-section {
  margin-top: 14px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  margin-bottom: 16px;
}

.section-title::before {
  content: '';
  width: 4px;
  height: 14px;
  background: linear-gradient(180deg, #818cf8 0%, #6366f1 100%);
  border-radius: 2px;
}

/* 水平标签-内容布局（标签固定 72px，下边框分隔，移动端转垂直） */
.full-panel .step-card > .field,
.full-panel .form-section .field {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 6px 16px;
  align-items: start;
  padding: 14px 0;
  border-bottom: 1px solid #f6f8fa;
}

.full-panel .step-card > .field:last-child,
.full-panel .form-section .field:last-child {
  border-bottom: none;
}

.full-panel .step-card > .field > .field-label,
.full-panel .form-section .field > .field-label {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  display: flex;
  align-items: center;
  min-height: 38px;
  line-height: 1.4;
}

.full-panel .step-card > .field > .field-content,
.full-panel .form-section .field > .field-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.full-panel .form-section .field > .field-tip {
  grid-column: 2;
}

.full-panel .form-section .field.full,
.full-panel .form-section .field.full + .field.full {
  margin-top: 0;
}

.form-grid.clean {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: transparent;
  border: none;
  border-radius: 0;
}

.form-grid.clean .field {
  padding: 16px 0;
  border-bottom: 1px solid #f1f5f9;
}

.form-grid.clean .field:last-child {
  border-bottom: none;
}

.form-grid.clean .field:hover {
  background: transparent;
}

.form-section .inline-checks {
  flex-wrap: wrap;
  gap: 12px 20px;
  min-height: 40px;
  align-items: center;
}

.form-section .upload-card {
  margin-top: 4px;
}

.area-counter {
  text-align: right;
  font-size: 13px;
  color: var(--gray-400);
}

.field-tip {
  font-size: 13px;
  color: var(--accent-500);
}

/* 自定义大纲开关行 */
.outline-toggle-cell {
  align-items: center;
}

/* 外文文献条数输入行 */
.en-lit-input-cell {
  align-items: center;
  flex-wrap: wrap;
}
.en-lit-input-cell .cell-label {
  line-height: 1.4;
}

.outline-toggle-cell .cell-label {
  line-height: 1.4;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
  flex-shrink: 0;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
  position: absolute;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background: var(--gray-300);
  border-radius: 999px;
  transition: all 0.25s ease;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  left: 3px;
  top: 3px;
  background: #fff;
  border-radius: 50%;
  transition: all 0.25s ease;
  box-shadow: 0 2px 5px rgba(15, 23, 42, 0.15);
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(18px);
}

.toggle-hint {
  font-size: 12px;
  color: var(--gray-400);
  flex: 1;
}

/* 自定义大纲编辑器 */
.custom-outline-area {
  margin-top: 14px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 14px;
  overflow: hidden;
}

.outline-editor-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-100);
}

.outline-editor-title {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: var(--gray-600);
  letter-spacing: 0.3px;
}

.outline-editor-title svg {
  color: var(--primary-500);
}

.outline-load-sample {
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--primary-600);
  background: #fff;
  border: 1px solid var(--primary-300);
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s;
}

.outline-load-sample:hover {
  background: var(--primary-500);
  color: #fff;
  border-color: var(--primary-500);
}

.outline-editor {
  display: block;
  width: 100%;
  padding: 14px 16px;
  border: none;
  outline: none;
  font-size: 14px;
  line-height: 1.8;
  font-family: 'Menlo', 'Consolas', 'PingFang SC', 'Microsoft YaHei', monospace;
  color: var(--dark-800);
  background: #fff;
  resize: vertical;
  min-height: 200px;
}

.outline-editor::placeholder {
  color: var(--gray-400);
  font-size: 13px;
  line-height: 1.7;
}

.outline-editor-tip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: var(--gray-50);
  border-top: 1px solid var(--gray-100);
  font-size: 12px;
  color: var(--gray-400);
}

.outline-counter {
  color: var(--gray-500);
  font-weight: 600;
}

/* 已选模板摘要 */
.selected-tpl-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding: 10px 14px;
  background: var(--primary-50);
  border: 1px solid var(--primary-200);
  border-radius: 10px;
}

.summary-logo {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  object-fit: cover;
}

.summary-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.summary-label {
  font-size: 11px;
  color: var(--primary-600);
  font-weight: 600;
}

.summary-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-700);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.summary-change {
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--primary-600);
  background: #fff;
  border: 1px solid var(--primary-300);
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}

.summary-change:hover {
  background: var(--primary-500);
  color: #fff;
}

/* 模板选择视图 */
.quick-template-view {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.tpl-view-bar {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}

.tpl-type-switch {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 14px;
}

.tpl-type-btn {
  padding: 6px 18px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  background: #fff;
  border: 1px solid var(--gray-200);
  border-radius: 18px;
  cursor: pointer;
  transition: all 0.18s;
}

.tpl-type-btn:hover {
  border-color: var(--primary-300);
  color: var(--primary-500);
}

.tpl-type-btn.active {
  background: var(--primary-500);
  color: #fff;
  border-color: var(--primary-500);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.tpl-view-search {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1.5px solid var(--gray-200);
  border-radius: 12px;
  padding: 0 14px;
  height: 44px;
  box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
  transition: all 0.2s;
}

.tpl-view-search:focus-within {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.tpl-search-icon {
  color: var(--gray-400);
  flex-shrink: 0;
  transition: color 0.2s;
}

.tpl-view-search:focus-within .tpl-search-icon {
  color: var(--primary-500);
}

.tpl-view-search input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: var(--dark-700);
  height: 100%;
}

.tpl-view-search input::placeholder {
  color: var(--gray-400);
}

.tpl-view-body {
  min-height: 200px;
  max-height: 460px;
  overflow-y: auto;
  padding-right: 4px;
}

.tpl-view-body::-webkit-scrollbar {
  width: 5px;
}

.tpl-view-body::-webkit-scrollbar-thumb {
  background: var(--gray-200);
  border-radius: 5px;
}

.tpl-view-body::-webkit-scrollbar-thumb:hover {
  background: var(--gray-300);
}

.tpl-loading {
  padding: 60px 0;
  text-align: center;
  color: var(--gray-400);
  font-size: 14px;
}

.tpl-loading::before {
  content: '';
  display: block;
  width: 28px;
  height: 28px;
  margin: 0 auto 12px;
  border: 2.5px solid var(--gray-100);
  border-top-color: var(--primary-500);
  border-radius: 50%;
  animation: tpl-spin 0.8s linear infinite;
}

@keyframes tpl-spin {
  to { transform: rotate(360deg); }
}

.tpl-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.tpl-card {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 16px 12px;
  background: #fff;
  border: 1.5px solid var(--gray-200);
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.18s;
  text-align: center;
}

.tpl-card:hover {
  border-color: var(--primary-300);
  box-shadow: 0 4px 14px rgba(20, 184, 166, 0.1);
  transform: translateY(-2px);
}

.tpl-card.selected {
  border-color: var(--primary-500);
  background: #f0fdfa;
  box-shadow: 0 4px 14px rgba(20, 184, 166, 0.16);
}

.tpl-card-preview {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  margin: 0 auto 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gray-100);
}

.tpl-card-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.tpl-general-preview {
  background: linear-gradient(135deg, #ccfbf1, #fef3c7);
  color: var(--primary-600);
}

.tpl-card-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #ccfbf1, #fef3c7);
  color: var(--primary-600);
  font-weight: 700;
  font-size: 22px;
}

.tpl-card-info {
  width: 100%;
}

.tpl-card-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--dark-700);
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tpl-card-meta {
  font-size: 11px;
  color: var(--gray-500);
  margin-top: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tpl-card-year {
  font-size: 11px;
  color: var(--gray-400);
  margin-top: 2px;
}

.tpl-card-check {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-500);
  color: #fff;
}

.tpl-selected-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  margin-top: 4px;
  background: #f5f3ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  font-size: 13px;
}

.tpl-selected-label {
  color: var(--gray-500);
  flex-shrink: 0;
}

.tpl-selected-name {
  flex: 1;
  font-weight: 600;
  color: #4338ca;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tpl-selected-clear {
  flex-shrink: 0;
  padding: 3px 10px;
  border: 1px solid #c7d2fe;
  border-radius: 6px;
  background: #fff;
  color: #6366f1;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.tpl-selected-clear:hover {
  background: #eef2ff;
  border-color: #a5b4fc;
}

.tpl-empty {
  padding: 60px 0;
  text-align: center;
  color: var(--gray-400);
}

.tpl-empty svg {
  margin-bottom: 12px;
}

.tpl-empty p {
  font-size: 15px;
  font-weight: 600;
  color: var(--gray-500);
  margin: 0 0 4px;
}

.tpl-empty span {
  font-size: 13px;
  color: var(--gray-400);
}

.tpl-view-foot {
  padding: 14px 0 4px;
}

/* ============ 模板统计横幅 ============ */
.tpl-stats-banner {
  width: 100%;
}

/* 默认展示：数据库总量 */
.stats-showcase {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary-50) 0%, rgba(255,255,255,0.6) 100%);
  border: 1px solid var(--primary-200);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
  position: relative;
  overflow: hidden;
}

.stats-showcase::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, var(--primary-500) 0%, var(--primary-600) 100%);
  border-radius: 2px 0 0 2px;
}

.stats-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 3px 8px rgba(59, 130, 246, 0.25);
}

.stats-content {
  display: flex;
  flex-direction: column;
  gap: 5px;
  flex: 1;
  min-width: 0;
}

.stats-num-row {
  display: flex;
  align-items: baseline;
  gap: 7px;
  flex-wrap: wrap;
}

.stats-big-num {
  font-size: 26px;
  font-weight: 800;
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800, #1e40af) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
  line-height: 1;
  font-family: 'DIN Alternate', 'Helvetica Neue', sans-serif;
}

.stats-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600);
}

.stats-badge {
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  padding: 3px 10px;
  border-radius: 999px;
  line-height: 1.4;
  box-shadow: 0 1px 3px rgba(59, 130, 246, 0.2);
}

.stats-sub-row {
  font-size: 12.5px;
  color: var(--gray-600);
  line-height: 1.5;
}

.stats-em {
  color: #fff;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 999px;
  font-size: 12px;
  margin: 0 1px;
}

/* 搜索结果行 */
.stats-search-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 10px;
  background: var(--primary-50);
  border: 1px solid var(--primary-200);
}

.stats-search-icon {
  color: var(--primary-500);
  flex-shrink: 0;
}

.stats-search-text {
  font-size: 13px;
  color: var(--gray-600);
  line-height: 1.6;
}

.stats-keyword {
  color: #f43f5e;
  font-weight: 700;
  background: rgba(244, 63, 94, 0.08);
  padding: 1px 6px;
  border-radius: 4px;
  margin: 0 2px;
}

.stats-highlight {
  color: var(--primary-600);
  font-weight: 800;
  font-size: 15px;
  margin: 0 2px;
}

/* 私有 */
.stats-private-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  color: var(--gray-600);
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
}

.stats-private-row svg {
  color: var(--gray-500);
  flex-shrink: 0;
}

.tpl-back-btn {
  display: flex;
  align-items: center;
  gap: 6px;
}

@media (max-width: 768px) {
  .tpl-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.agreement-row {
  padding: 8px 0;
}

.step-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding-top: 14px;
  margin-top: 4px;
  border-top: 1px dashed var(--gray-100);
}

.next-btn,
.prev-btn,
.submit-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 140px;
  padding: 11px 28px;
  border-radius: 999px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.prev-btn {
  background: #fff;
  border: 1.5px solid var(--gray-200);
  color: var(--gray-600);
}

.prev-btn:hover {
  border-color: var(--primary-400);
  color: var(--primary-600);
  background: var(--gray-50);
}

.next-btn,
.submit-btn {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  border: none;
  color: #fff;
  box-shadow: 0 6px 20px rgba(13, 148, 136, 0.25);
}

.next-btn:hover,
.submit-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 28px rgba(13, 148, 136, 0.35);
}

/* 资源切换：分段控件 */
.resource-tabs {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  background: var(--gray-100);
  border-radius: 12px;
  align-self: flex-start;
}

.resource-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--gray-600);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.resource-tab:hover {
  color: var(--primary-600);
}

.resource-tab.active {
  background: #fff;
  color: var(--primary-600);
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
}

.resource-empty {
  text-align: center;
  padding: 24px 20px;
  color: var(--gray-500);
}

.empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  color: var(--gray-400);
  margin-bottom: 10px;
}

.empty-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-700);
}

.empty-sub {
  font-size: 13px;
  margin-top: 6px;
  color: var(--gray-400);
  line-height: 1.6;
}

.lit-toolbar {
  margin-bottom: 14px;
  padding: 14px 16px 12px;
  background: linear-gradient(180deg, #fafbff 0%, #f5f7fb 100%);
  border: 1px solid #eef0f5;
  border-radius: 14px;
}

.lit-search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 6px 6px 14px;
  border: 1.5px solid var(--gray-200);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
  transition: all 0.2s;
}

.lit-search-bar:focus-within {
  border-color: var(--primary-400);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.lit-search-icon {
  color: var(--gray-400);
  flex-shrink: 0;
  transition: color 0.2s;
}

.lit-search-bar:focus-within .lit-search-icon {
  color: var(--primary-500);
}

.lit-search-bar input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
  color: var(--dark-700);
  min-width: 0;
  padding: 6px 0;
}

.lit-search-bar input::placeholder {
  color: var(--gray-400);
}

.lit-search-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border-radius: 10px;
  border: none;
  background: var(--primary-500);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
}

.lit-search-btn svg {
  flex-shrink: 0;
}

.lit-search-btn:hover:not(:disabled) {
  background: var(--primary-600);
  box-shadow: 0 4px 10px rgba(99, 102, 241, 0.32);
  transform: translateY(-1px);
}

.lit-search-btn:active:not(:disabled) {
  transform: translateY(0);
}

.lit-search-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  box-shadow: none;
}

.lit-search-hint {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 10px;
  padding-left: 2px;
  font-size: 12px;
  color: var(--gray-400);
}

.lit-search-hint svg {
  color: #a5b4fc;
  flex-shrink: 0;
}

.lit-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 22px 16px;
  color: var(--gray-500);
  font-size: 13px;
  text-align: center;
}

.lit-state-text {
  line-height: 1.6;
}

.lit-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid var(--gray-100);
  border-top-color: var(--primary-500);
  border-radius: 50%;
  animation: lit-spin 0.8s linear infinite;
}

@keyframes lit-spin {
  to { transform: rotate(360deg); }
}

.lit-results {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 480px;
  overflow-y: auto;
  padding-right: 6px;
}

.lit-results::-webkit-scrollbar {
  width: 6px;
}

.lit-results::-webkit-scrollbar-thumb {
  background: var(--gray-200);
  border-radius: 6px;
}

.lit-results::-webkit-scrollbar-thumb:hover {
  background: var(--gray-300);
}

.lit-results-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 4px 6px;
  border-bottom: 1px dashed var(--gray-100);
  margin-bottom: 4px;
}

.lit-results-count {
  font-size: 12px;
  color: var(--gray-500);
}

.lit-results-count em {
  font-style: normal;
  font-weight: 700;
  color: var(--primary-600);
  font-size: 13px;
  margin: 0 2px;
}

.lit-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid var(--gray-200);
  border-radius: 12px;
  background: #fff;
  transition: all 0.2s ease;
}

.lit-card:hover {
  border-color: var(--primary-200);
  background: #fafbff;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.06);
}

.lit-card.selected {
  border-color: #c7d2fe;
  background: #f5f3ff;
}

.lit-main-check {
  position: relative;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-top: 2px;
  cursor: pointer;
}

.lit-main-check input {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
  z-index: 1;
}

.lit-main-check .check-box {
  position: absolute;
  top: 0;
  left: 0;
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--gray-300);
  border-radius: 5px;
  background: #fff;
  transition: all 0.15s;
}

.lit-main-check .check-box::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 2px;
  width: 5px;
  height: 9px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  opacity: 0;
  transition: opacity 0.15s;
}

.lit-main-check input:checked + .check-box {
  background: var(--primary-500);
  border-color: var(--primary-500);
}

.lit-main-check input:checked + .check-box::after {
  opacity: 1;
}

.lit-card-body {
  flex: 1;
  min-width: 0;
}

.lit-card-head {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 8px;
}

.lit-card-title {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-700);
  line-height: 1.5;
  word-break: break-all;
}

.lit-card-year {
  flex-shrink: 0;
  font-size: 12px;
  color: var(--primary-600);
  background: #eef2ff;
  padding: 2px 8px;
  border-radius: 999px;
}

.lit-abstract-wrap {
  margin-bottom: 10px;
}

.lit-abstract {
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.7;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.lit-abstract.expanded {
  display: block;
  -webkit-line-clamp: unset;
}

.lit-abstract-toggle {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0;
  border: none;
  background: transparent;
  color: var(--primary-600);
  font-size: 12px;
  cursor: pointer;
  margin-top: 6px;
}

.lit-abstract-toggle .flip {
  transform: rotate(180deg);
}

.lit-citation {
  margin-bottom: 10px;
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 3px solid var(--primary-200);
}

.lit-citation-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  font-size: 12px;
  color: var(--gray-600);
}

.lit-citation-select {
  padding: 3px 8px;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  background: #fff;
  color: var(--dark-700);
  font-size: 12px;
  outline: none;
}

.lit-citation-text {
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.5;
  word-break: break-all;
}

.lit-options {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px dashed var(--gray-200);
}

.lit-chip {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 999px;
  background: #fff;
  color: var(--gray-600);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
}

.lit-chip input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.lit-chip::before {
  content: '';
  width: 12px;
  height: 12px;
  border: 1.5px solid var(--gray-300);
  border-radius: 3px;
  flex-shrink: 0;
  transition: all 0.15s;
}

.lit-chip.active {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: var(--primary-700);
}

.lit-chip.active::before {
  background: var(--primary-500);
  border-color: var(--primary-500);
}

.lit-chip.active::after {
  content: '';
  position: absolute;
  left: 17px;
  width: 5px;
  height: 9px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.lit-chip:hover {
  border-color: var(--primary-300);
}

.lit-selected {
  margin-bottom: 12px;
  padding: 10px 12px;
  background: #fff;
  border: 1px dashed #c7d2fe;
  border-radius: 10px;
}

.lit-selected-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.lit-selected-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-700);
}

.lit-selected-count {
  background: var(--primary-500);
  color: #fff;
  border-radius: 10px;
  padding: 1px 7px;
  font-size: 12px;
  font-weight: 500;
  margin-left: 6px;
}

.lit-clear {
  padding: 3px 10px;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  background: #fff;
  color: var(--gray-500);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.lit-clear:hover {
  border-color: var(--primary-300);
  color: var(--primary-600);
  background: #f8fafc;
}

.lit-selected-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.lit-selected-tag {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 22px 5px 10px;
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 999px;
  font-size: 12px;
  color: #4338ca;
  max-width: 100%;
  transition: all 0.15s;
}

.lit-selected-tag:hover {
  background: #e0e7ff;
}

.lit-tag-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lit-remove {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border: none;
  background: transparent;
  color: #6366f1;
  cursor: pointer;
  border-radius: 50%;
  font-size: 14px;
  line-height: 1;
}

.lit-remove:hover {
  background: #c7d2fe;
}

/* 订单摘要 / 增值服务：分组卡片内样式 */
.order-summary,
.service-section {
  padding: 0;
  background: transparent;
  border: none;
  overflow: hidden;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 11px 0;
  border-bottom: 1px dashed var(--gray-200);
  font-size: 14px;
  color: var(--gray-600);
}

.summary-row:last-child {
  border-bottom: none;
}

.summary-row span:last-child {
  font-weight: 600;
  color: var(--dark-800);
}

.summary-row.total {
  background: #eef2ff;
  border-radius: 10px;
  padding: 12px 14px;
  margin-top: 8px;
  font-size: 15px;
}

.summary-row.total span:last-child {
  color: #4f46e5;
  font-size: 18px;
  font-weight: 800;
}

.service-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.service-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.15s;
}

.service-card:hover {
  border-color: #c7d2fe;
  background: #eef2ff;
}

.service-card.selected {
  background: #eef2ff;
  border-color: #c7d2fe;
}

.service-icon {
  font-size: 22px;
  flex-shrink: 0;
}

.service-info {
  flex: 1;
  min-width: 0;
}

.service-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
}

.service-desc {
  font-size: 12px;
  color: var(--gray-500);
  margin-top: 2px;
}

.service-price {
  font-size: 16px;
  font-weight: 800;
  color: var(--accent-500);
  flex-shrink: 0;
}

.service-check {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 1.5px solid var(--gray-300);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
  transition: all 0.18s;
}

.service-card.selected .service-check {
  background: var(--primary-500);
  border-color: var(--primary-500);
}

.order-total {
  display: flex;
  align-items: baseline;
  justify-content: flex-end;
  gap: 8px;
  padding: 14px 18px;
  font-size: 14px;
  color: var(--gray-600);
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  border-radius: 12px;
}

.total-price {
  font-size: 26px;
  font-weight: 800;
  color: var(--accent-500);
  letter-spacing: -0.5px;
}

/* ============ 完整模式：紫色/靛蓝主题覆盖 ============ */
.full-tabs .tab-btn.active {
  color: #4f46e5;
}

.full-panel .step-card-head h3::before {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
}

.full-panel .field input:focus,
.full-panel .field textarea:focus,
.full-panel .major-select select:focus,
.full-panel .suggest-field input:focus,
.full-panel .suggest-field select:focus,
.full-panel .lit-search-bar:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}

.full-panel .lit-search-bar:focus-within .lit-search-icon {
  color: #6366f1;
}

.full-panel .lit-search-btn {
  background: #6366f1;
  box-shadow: 0 2px 6px rgba(99, 102, 241, 0.28);
}

.full-panel .lit-search-btn:hover:not(:disabled) {
  background: #4f46e5;
  box-shadow: 0 4px 10px rgba(99, 102, 241, 0.36);
}

.full-panel .lit-results-count em {
  color: #4f46e5;
}

.full-panel .lit-card.selected {
  border-color: #c7d2fe;
  background: #f5f3ff;
}

.full-panel .lit-card:hover {
  border-color: #c7d2fe;
}

.full-panel .lit-main-check input:checked + .check-box {
  background: #6366f1;
  border-color: #6366f1;
}

.full-panel .lit-selected-tag {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #4338ca;
}

.full-panel .lit-remove:hover {
  background: #c7d2fe;
}

.full-panel .lit-abstract-toggle {
  color: #6366f1;
}

.full-panel .lit-abstract-toggle:hover {
  color: #4f46e5;
}

.full-panel .lit-citation-select:focus {
  border-color: #6366f1;
}

.full-panel .lit-chip.active {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #4338ca;
}

.full-panel .lit-chip.active::before {
  background: #6366f1;
  border-color: #6366f1;
}

.full-panel .lit-empty p {
  color: #312e81;
}

.full-panel .lit-clear:hover {
  border-color: #c7d2fe;
  color: #4f46e5;
  background: #eef2ff;
}

/* 完整模式模板选择紫色主题 */
.full-panel .tpl-type-btn.active {
  background: #6366f1;
  border-color: #6366f1;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.full-panel .tpl-loading::before {
  border-top-color: #6366f1;
}

.full-panel .tpl-type-btn:hover {
  border-color: #a5b4fc;
  color: #6366f1;
}

.full-panel .tpl-view-search:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}

.full-panel .tpl-view-search:focus-within .tpl-search-icon {
  color: #6366f1;
}

.full-panel .tpl-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.1);
}

.full-panel .tpl-card.selected {
  border-color: #6366f1;
  background: #f5f3ff;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.16);
}

.full-panel .tpl-general-preview {
  background: linear-gradient(135deg, #e0e7ff, #ede9fe);
  color: #4f46e5;
}

.full-panel .tpl-card-placeholder {
  background: linear-gradient(135deg, #e0e7ff, #ede9fe);
  color: #4f46e5;
}

.full-panel .tpl-card-check {
  background: #6366f1;
}

.full-panel .tpl-selected-info {
  background: #f5f3ff;
  border-color: #c7d2fe;
}

.full-panel .tpl-selected-name {
  color: #4338ca;
}

.full-panel .tpl-selected-clear {
  border-color: #c7d2fe;
  color: #6366f1;
}

.full-panel .tpl-selected-clear:hover {
  background: #eef2ff;
  border-color: #a5b4fc;
}

.full-panel .next-btn,
.full-panel .submit-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  box-shadow: 0 6px 18px rgba(99, 102, 241, 0.22);
}

.full-panel .next-btn:hover,
.full-panel .submit-btn:hover {
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.32);
}

.full-panel .prev-btn:hover {
  border-color: #c7d2fe;
  color: #4f46e5;
  background: #eef2ff;
}

.full-panel .resource-tab.active {
  color: #4f46e5;
}

.full-panel .field-tip {
  color: #6366f1;
}

.full-panel .option-pill:hover {
  border-color: #c7d2fe;
  color: #4f46e5;
  background: #eef2ff;
}

.full-panel .option-pill.active {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.full-panel .option-pill.more-pill {
  color: #4f46e5;
  background: rgba(99, 102, 241, 0.06);
}

.full-panel .option-pill.more-pill:hover {
  background: rgba(99, 102, 241, 0.12);
}

.full-panel .custom-word.active {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.full-panel .model-card:hover {
  border-color: #c7d2fe;
  background: #eef2ff;
}

.full-panel .model-card.active {
  border-color: #6366f1;
  background: #eef2ff;
  color: #4338ca;
}

.full-panel .check-item input:checked + .check-box {
  background: #6366f1;
  border-color: #6366f1;
}

.full-panel .upload-card:hover {
  border-color: #6366f1;
  background: #eef2ff;
}

.full-panel .upload-icon {
  color: #6366f1;
}

.full-panel .suggest-item:hover {
  border-color: #6366f1;
  background: #eef2ff;
}

.full-panel .suggest-select {
  color: #4f46e5;
}

.full-panel .suggest-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #fff;
  border: none;
}

.full-panel .btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border-color: transparent;
  color: #fff;
}

.full-panel .next-btn,
.full-panel .submit-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.25);
}

.full-panel .next-btn:hover,
.full-panel .submit-btn:hover {
  box-shadow: 0 10px 28px rgba(99, 102, 241, 0.35);
}

.full-panel .prev-btn:hover {
  border-color: #c7d2fe;
  color: #4f46e5;
  background: #eef2ff;
}

.full-panel .resource-tab:hover {
  color: #4f46e5;
}

.full-panel .resource-tab.active {
  color: #4f46e5;
}

.full-panel .service-card.selected {
  background: #eef2ff;
}

.full-panel .service-card.selected .service-check {
  background: #6366f1;
  border-color: #6366f1;
}

.full-panel .summary-row.total {
  background: #eef2ff;
}

.full-panel .summary-row.total span:last-child {
  color: #4f46e5;
}

/* 完整模式：更紧凑的选项组 */
.full-panel .word-options,
.full-panel .model-options,
.full-panel .option-pills,
.full-panel .inline-checks {
  gap: 6px 10px;
}

.full-panel .option-pill,
.full-panel .word-options .option-pill,
.full-panel .model-card {
  padding: 6px 12px;
  font-size: 13px;
}

.full-panel .model-card .model-tag {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 4px;
}

.full-panel .custom-word {
  width: 120px;
  height: 34px;
  padding: 5px 10px;
}

.full-panel .custom-word input {
  width: 72px;
}

.full-panel .major-select select:hover {
  border-color: #c7d2fe;
}

/* 完整模式：协议行对齐优化 */
.full-panel .agreement-row .check-item {
  align-items: flex-start;
}

.full-panel .agreement-row .check-box {
  margin-top: 2px;
}

.full-panel .full-stepbar {
  background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);
  border: 1px solid #e0e7ff;
  border-radius: 14px;
  padding: 18px 20px;
  box-shadow: 0 2px 10px rgba(99, 102, 241, 0.06);
}

.full-panel .full-stepbar-item.current .full-stepbar-node {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}

.full-panel .full-stepbar-item:hover:not(.active) .full-stepbar-node {
  border-color: #a5b4fc;
  color: #6366f1;
}

.full-panel .full-stepbar-item:hover .full-stepbar-title {
  color: #4f46e5;
}

.full-panel .full-stepbar-item.active .full-stepbar-title {
  color: #312e81;
}

.full-panel .full-stepbar-item.current .full-stepbar-title {
  color: #4f46e5;
}

.full-panel .full-stepbar-item.current .full-stepbar-sub {
  color: #6366f1;
}

.full-panel .full-stepbar-item.active .full-stepbar-sub {
  color: #818cf8;
}

@media (max-width: 1024px) {
  .full-stepbar {
    padding: 16px;
    gap: 12px;
    overflow-x: auto;
  }

  .full-stepbar-item {
    min-width: 130px;
    flex: 0 0 auto;
  }

  /* 小屏隐藏步骤副标题，避免滚动裁切出半截字 */
  .full-stepbar-sub {
    display: none;
  }

  .full-stepbar-item:not(:last-child)::after {
    display: none;
  }
}

@media (max-width: 768px) {
  .main-card,
  .step-card {
    padding: 20px;
    border-radius: 18px;
  }

  .order-card.compact {
    padding: 18px;
  }

  .order-flow {
    gap: 6px;
  }

  .flow-line {
    width: 20px;
  }

  .flow-label {
    font-size: 12px;
  }

  .param-grid,
  .form-grid.two-col,
  .form-grid.clean,
  .param-row.split {
    grid-template-columns: 1fr;
  }

  .form-section {
    padding: 14px;
  }

  .form-section .inline-checks {
    flex-direction: column;
    align-items: flex-start;
  }

  .full-panel .step-card > .field,
  .full-panel .form-section .field {
    grid-template-columns: 1fr;
    gap: 6px;
    padding: 14px 0;
  }

  .full-panel .step-card > .field > .field-label,
  .full-panel .form-section .field > .field-label {
    line-height: 1.4;
  }

  .subject-inline {
    flex-direction: column;
  }

  .param-cell {
    flex-direction: column;
    gap: 8px;
    min-width: 0;
  }

  /* 手机端防溢出：grid item 默认 min-width:auto 会被选项行内容撑破裁切 */
  .option-pills,
  .word-options,
  .model-options {
    max-width: 100%;
    min-width: 0;
  }

  .option-pill {
    white-space: normal;
  }

  /* 提示文字小屏允许换行，避免 nowrap 撑破被裁 */
  .cell-hint {
    white-space: normal;
  }

  /* 快速模式顶栏：小屏步骤标签收缩 + 可横滑，防"生成大纲"被裁出半截字 */
  .order-flow {
    overflow-x: auto;
    scrollbar-width: none;
    flex-wrap: nowrap;
  }

  .order-flow::-webkit-scrollbar {
    display: none;
  }

  .flow-step {
    flex: 0 0 auto;
    gap: 5px;
    font-size: 12px;
  }

  .flow-label {
    max-width: 72px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .flow-dot {
    width: 22px;
    height: 22px;
    font-size: 11px;
  }

  .cell-label {
    width: auto;
    line-height: 1.4;
  }

  .param-cell.wide {
    grid-column: auto;
  }

  .param-half {
    gap: 8px;
  }

  .subject-picker {
    grid-template-columns: 1fr;
  }

  .subject-categories {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    border-right: none;
    border-bottom: 1.5px solid var(--gray-200);
    max-height: none;
    padding: 12px;
  }

  .subject-category {
    white-space: nowrap;
    margin-bottom: 0;
    margin-right: 8px;
  }

  .subject-children {
    grid-template-columns: repeat(2, 1fr);
  }

  .title-input-bar {
    flex-direction: column;
  }

  .suggest-row {
    flex-direction: column;
    align-items: stretch;
  }

  .quick-submit {
    flex-direction: column;
    gap: 12px;
  }

  .subject-bar {
    flex-direction: column;
    align-items: flex-start;
  }

  .subject-tabs {
    width: 100%;
  }

  .param-row {
    gap: 10px;
  }

  .smart-topic-btn {
    position: static;
    margin-top: 10px;
    width: 100%;
    justify-content: center;
  }

  .topic-textarea {
    padding-right: 18px;
    min-height: 110px;
  }
}

/* ============ 收费标准弹窗 ============ */
.price-modal-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.price-modal {
  width: 100%;
  max-width: 640px;
  max-height: 85vh;
  background: #fff;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2);
}

.price-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid var(--gray-100);
  flex-shrink: 0;
}

.price-modal-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 800;
  color: var(--dark-800);
}

.price-modal-title svg {
  color: var(--accent-500);
}

.price-modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: var(--gray-100);
  color: var(--gray-500);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.price-modal-close:hover {
  background: var(--gray-200);
  color: var(--gray-700);
}

.price-modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.price-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.price-section-head {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-section-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
}

.price-section-icon.primary {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.price-section-icon.accent {
  background: linear-gradient(135deg, var(--accent-500) 0%, #ea580c 100%);
}

.price-section-icon.violet {
  background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
}

.price-section-icon.green {
  background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

.price-section-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800);
  flex: 1;
}

.price-section-tag {
  font-size: 11px;
  font-weight: 600;
  color: var(--gray-400);
  background: var(--gray-100);
  padding: 3px 8px;
  border-radius: 999px;
}

/* 论文生成价格表 */
.price-table {
  border: 1px solid var(--gray-100);
  border-radius: 12px;
  overflow: hidden;
}

.price-table-head,
.price-table-row {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1fr;
  padding: 10px 14px;
  align-items: center;
}

.price-table-head {
  background: var(--gray-50);
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
}

.price-table-row {
  border-top: 1px solid var(--gray-100);
  font-size: 13px;
  transition: background 0.15s;
}

.price-table-row:hover {
  background: var(--primary-50);
}

.price-col-range {
  color: var(--dark-800);
  font-weight: 600;
}

.price-col-price {
  color: var(--gray-700);
  font-weight: 700;
  font-size: 14px;
}

.price-col-price.pro {
  color: var(--accent-500);
  font-weight: 800;
  font-size: 15px;
}

.price-col-price.deepseek {
  color: #6d28d9;
  font-weight: 800;
  font-size: 15px;
}

/* 模型图例 */
.price-table-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  padding: 4px 4px 0;
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--gray-500);
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.legend-dot.standard {
  background: var(--gray-500);
}

.legend-dot.pro {
  background: var(--accent-500);
}

.legend-dot.deepseek {
  background: #6d28d9;
}

/* 两列网格 */
.price-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.price-grid-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  transition: all 0.15s;
}

.price-grid-item:hover {
  border-color: var(--primary-300);
  background: var(--primary-50);
}

.grid-item-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600);
}

.grid-item-price {
  font-size: 16px;
  font-weight: 800;
  color: var(--accent-500);
}

.grid-item-price.free {
  color: var(--primary-600);
  font-size: 13px;
}

.grid-item-price.paid {
  color: var(--accent-500);
}

/* 说明 */
.price-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 12px 14px;
  background: var(--gray-50);
  border-radius: 10px;
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.6;
}

.price-note svg {
  color: var(--gray-400);
  flex-shrink: 0;
  margin-top: 1px;
}

/* 弹窗过渡动画 */
.price-modal-enter-active,
.price-modal-leave-active {
  transition: opacity 0.2s ease;
}

.price-modal-enter-active .price-modal,
.price-modal-leave-active .price-modal {
  transition: transform 0.25s ease;
}

.price-modal-enter-from,
.price-modal-leave-to {
  opacity: 0;
}

.price-modal-enter-from .price-modal,
.price-modal-leave-to .price-modal {
  transform: scale(0.95) translateY(10px);
}

/* 移动端弹窗适配 */
@media (max-width: 640px) {
  .price-modal {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 14px;
  }

  .price-modal-head {
    padding: 14px 16px;
  }

  .price-modal-body {
    padding: 14px 16px;
    gap: 18px;
  }

  .price-grid-2 {
    grid-template-columns: 1fr;
  }

  .price-table-head,
  .price-table-row {
    grid-template-columns: 1.3fr 1fr 1fr 1fr;
    padding: 9px 8px;
    font-size: 12px;
  }

  .price-col-price {
    font-size: 13px;
  }

  .price-col-price.pro,
  .price-col-price.deepseek {
    font-size: 14px;
  }
}

/* ===== 快速模式：内联模板选择器 ===== */
.tpl-inline-cell {
  align-items: center;
}

.tpl-inline-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.tpl-inline-info {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
}

.tpl-inline-logo {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  object-fit: cover;
  flex-shrink: 0;
  border: 1px solid var(--gray-100);
}

.tpl-inline-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.tpl-inline-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tpl-inline-meta {
  font-size: 12px;
  color: var(--gray-500);
}

.tpl-inline-change {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid var(--gray-200);
  background: var(--white);
  color: var(--primary-600);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.tpl-inline-change:hover {
  border-color: var(--primary-400);
  background: #f0fdfa;
}

.tpl-inline-change svg {
  color: var(--accent-500);
}

/* 步骤 2：当前已选模板摘要条 */
.tpl-selected-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
  border: 1px solid var(--primary-200);
  border-radius: 12px;
}

.tpl-selected-tag {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  border-radius: 999px;
  flex-shrink: 0;
}

/* ===== 模板选择弹窗 ===== */
.tpl-modal {
  max-width: 720px;
}

.tpl-modal-body {
  padding: 16px 20px 20px;
}

.tpl-modal-actions {
  display: flex;
  justify-content: center;
  padding-top: 16px;
  border-top: 1px solid var(--gray-100);
  margin-top: 12px;
}

</style>
