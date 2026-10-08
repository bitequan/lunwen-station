<template>
  <div class="autodoc-page" :class="{ 'autodoc-page--embed': isEmbed }">
    <ClientOnly>
      <!-- 初始模式选择弹窗（只覆盖排版页面，不遮顶部菜单栏） -->
      <Transition name="mode-fade">
        <div v-if="showModeModal" class="mode-mask">
          <div class="mode-modal">
              <div class="mode-head">
                <div class="mode-head__badge">
                  <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                </div>
                <h3 class="mode-head__title">选择<em>排版模式</em></h3>
                <p class="mode-head__sub">根据您的文档情况，选择最合适的排版方式，立即开始</p>
              </div>

              <div class="mode-body">
                <div
                  v-for="m in MODES"
                  :key="m.key"
                  class="mode-card"
                  :class="'mode-card--' + m.key"
                  @click="chooseMode(m.key)"
                >
                  <div class="mode-card__top">
                    <span class="mode-card__icon">
                      <svg v-if="m.key === 'polish'" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/><path d="M14.5 5.5l4 4"/></svg>
                      <svg v-else viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.7L19.5 10l-5.6 1.3L12 17l-1.9-5.7L4.5 10l5.6-1.3L12 3z"/><path d="M19 15l.8 2.4L22 18l-2.2.6L19 21l-.8-2.4L16 18l2.2-.6L19 15z"/></svg>
                    </span>
                    <span class="mode-card__tag">{{ m.tag }}</span>
                  </div>
                  <div class="mode-card__name">{{ m.name }}</div>
                  <p class="mode-card__desc">{{ m.desc }}</p>
                  <ul class="mode-card__points">
                    <li v-for="(p, pi) in m.points" :key="pi">
                      <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                      {{ p }}
                    </li>
                  </ul>
                  <span class="mode-card__go">
                    选择此模式
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                  </span>
                </div>
              </div>

              <div class="mode-foot">
                <span class="mode-foot__tip">选择后自动进入对应流程 · 可在页面顶部随时切换</span>
              </div>
            </div>
        </div>
      </Transition>

      <!-- Steps -->
      <nav class="steps">
        <div v-for="(s, i) in steps" :key="i" :class="['steps__item', { 'steps__item--active': s.active, 'steps__item--done': s.done }]">
          <span class="steps__num">{{ s.done ? '✓' : s.num }}</span>
          <span class="steps__label">{{ s.label }}</span>
          <span v-if="i < 2" class="steps__line" :class="{ 'steps__line--done': s.done }"></span>
        </div>
        <button class="mode-switch" :class="{ 'mode-switch--active': chosenMode }" @click="showModeModal = true">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 5.7L19.5 10l-5.6 1.3L12 17l-1.9-5.7L4.5 10l5.6-1.3L12 3z"/></svg>
          {{ currentModeName }}
        </button>
        <button class="price-standard-btn" @click="showPriceModal = true">
          <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
          收费标准
        </button>
      </nav>

      <!-- Step 1: Template Selection -->
      <div v-if="currentStep === 1" class="content">
        <div class="filters">
          <div class="tpl-type-switch">
            <button :class="['tpl-type-btn', { active: tplTab === 'public' }]" type="button" @click="switchTplTab('public')">公共模板</button>
            <button :class="['tpl-type-btn', { active: tplTab === 'private' }]" type="button" @click="switchTplTab('private')">私有模板</button>
          </div>
          <div class="filters__row">
            <template v-if="tplTab === 'public'">
              <button v-for="d in degrees" :key="d.value" :class="['filter-btn', { active: filterDegree === d.value }]" @click="filterDegree = d.value">{{ d.label }}</button>
            </template>
            <div class="filters__search">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="searchKeyword" :placeholder="tplTab === 'private' ? '输入模板名称搜索' : '输入学校或专业关键词搜索'">
              <span v-if="searchKeyword" @click="searchKeyword = ''">✕</span>
            </div>
          </div>
        </div>

        <div class="body">
          <div v-if="displayedTemplates.length === 0 && !loadingTemplates" class="empty">
            <template v-if="tplTab === 'private'">暂无私有模板，可前往「模板制作」上传自己的模板</template>
            <template v-else>暂无可用模板</template>
          </div>
          <div v-else-if="loadingTemplates" class="empty"><div class="spinner"></div><span>正在检索...</span></div>
          <div v-else class="grid">
            <div
              v-for="(tpl, idx) in displayedTemplates"
              :key="tpl.template_uid"
              :class="['card', { selected: isSelected(tpl) }]"
              :style="{ '--i': idx }"
              @click="selectTemplate(tpl)"
            >
              <div class="card__preview">
                <img v-if="tpl.avt" :src="tpl.avt" :alt="tpl.name">
                <span v-else class="card__fallback-text">{{ tpl.name.slice(0, 3) }}</span>
              </div>
              <div class="card__info">
                <div class="card__title">{{ tpl.name }}</div>
                <div class="card__tags">
                  <span v-if="tpl.degree" class="tag">{{ tpl.degree }}</span>
                  <span v-if="tpl.profession" class="tag">{{ tpl.profession }}</span>
                  <span v-if="tpl.years" class="tag tag--year">{{ tpl.years }}</span>
                </div>
              </div>
              <div v-if="isSelected(tpl)" class="card__check">
                <svg width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#14b8a6"/><path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" fill="none"/></svg>
              </div>
            </div>
          </div>
          <div v-if="!searchKeyword && totalCount > displayedTemplates.length" class="templates-hint" @click="focusSearch">
            <div class="templates-hint__left">
              <div class="templates-hint__badge">{{ totalCount.toLocaleString() }}+</div>
              <div class="templates-hint__text">
                <span class="templates-hint__title">海量模板库</span>
                <span class="templates-hint__desc">输入学校名称查找专属排版模板</span>
              </div>
            </div>
            <div class="templates-hint__btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              去搜索
            </div>
          </div>
        </div>

        <div class="step-nav">
          <button class="btn btn--primary" :disabled="!selectedTemplateId" @click="goToUpload">
            下一步：上传文档
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </div>
      </div>

      <!-- Step 2: Upload -->
      <div v-if="currentStep >= 2" class="upload">
        <header class="header">
          <h2 class="header__heading">上传论文文档</h2>
          <p class="header__desc">{{ uploadDesc }}</p>
        </header>

        <div class="upload-card">
          <div v-if="selectedTemplate" class="upload-card__tip">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            已选模板：{{ selectedTemplate.name }}
          </div>

          <div
            class="dropzone"
            :class="{ 'dropzone--over': isDragging, 'dropzone--done': selectedFile }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
          >
            <input type="file" ref="fileInput" hidden accept=".docx" @change="handleFileChange">
            <div v-if="!selectedFile" class="dropzone__empty" @click="triggerFileUpload">
              <div class="dropzone__circle">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <p class="dropzone__hint">点击或拖拽文件到这里上传</p>
              <p class="dropzone__sub">支持 .docx 格式 · 最大 10MB</p>
            </div>
            <div v-else class="dropzone__file">
              <div class="dropzone__file-row">
                <div class="dropzone__file-icon" :class="{ 'dropzone__file-icon--ok': extractedData }">
                  <svg v-if="extractedData" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <div class="dropzone__fname">{{ selectedFile.name }}</div>
                  <div class="dropzone__fsize">{{ formatFileSize(selectedFile.size) }}</div>
                </div>
              </div>
              <div v-if="extracting" class="dropzone__state">正在解析文档内容...</div>
              <div v-else-if="extractedData" class="dropzone__state dropzone__state--ok">
                <!-- retype=智能重排:结构预览有意义(retype 依据提取内容重建);polish=精修不重建结构,收起章节数 -->
                <template v-if="chosenMode === 'polish'">已解析文档 · 将在原稿基础上精修排版(保留原版式)</template>
                <template v-else>已提取文档内容 · {{ extractedData.summary?.chapters || 0 }} 章节</template>
              </div>
              <div class="dropzone__actions">
                <button v-if="extractedData && chosenMode !== 'polish'" class="btn btn--ghost" @click="showPreviewModal = true">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  预览
                </button>
                <button class="btn btn--ghost" @click="triggerFileUpload">更换文件</button>
                <button class="btn btn--ghost btn--ghost-danger" @click="removeFile">删除文件</button>
              </div>
            </div>
          </div>

          <div v-if="generating" class="upload-card__status">
            <div class="spinner"></div>
            <span>正在生成排版文档，请稍候...</span>
          </div>

          <div class="step-nav">
            <button class="btn btn--ghost" @click="goBackToTemplates">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              上一步
            </button>
            <button class="btn btn--primary" :disabled="!selectedFile || !extractedData || generating" @click="startGenerate">
              <span v-if="generating" class="spinner-mini"></span>
              {{ generating ? '生成中...' : (chosenMode === 'polish' ? '开始精修排版' : '开始生成排版') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Modals -->
      <Teleport to="body">
        <div v-if="showTemplateParams" class="modal-backdrop" @click.self="showTemplateParams = false">
          <div class="modal modal--wide">
            <div class="modal__head">
              <span class="modal__title">{{ selectedTemplate?.name || '' }} 排版参数</span>
              <button class="modal__close" @click="showTemplateParams = false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal__body">
              <div v-if="!templatePreview || !Object.keys(templatePreview).length" class="modal__loading">加载中...</div>
              <div v-else class="params">
                <div v-for="(val, key) in templatePreview" :key="key" class="params__row">
                  <span class="params__key">{{ key }}</span>
                  <span class="params__val">{{ formatValue(val) }}</span>
                </div>
              </div>
            </div>
            <div class="modal__foot"><button class="btn btn--primary" @click="showTemplateParams = false">关闭</button></div>
          </div>
        </div>

        <div v-if="showPreviewModal" class="modal-backdrop" @click.self="showPreviewModal = false">
          <div class="modal modal--wide">
            <div class="modal__head">
              <span class="modal__title">文档解析预览</span>
              <button class="modal__close" @click="showPreviewModal = false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal__body preview-body">
              <div v-for="(sec, i) in previewSections" :key="i" class="preview-sec" :class="'preview-sec--' + sec.kind">
                <!-- 关键词专用渲染: 单行 "标题：内容", 使用独立标签结构便于提交时区分 -->
                <div v-if="sec.kind === 'keywords'" class="preview-sec__keywords" :class="'preview-sec__keywords--' + sec.level">
                  <span class="preview-sec__keywords-label">{{ sec.title }}：</span>
                  <span class="preview-sec__keywords-text">{{ sec.text }}</span>
                </div>
                <!-- 资产占位符渲染: 表格/图片/图表 -->
                <div v-else-if="sec.kind === 'asset'" class="preview-asset" :class="'preview-asset--' + sec.assetType">
                  <!-- 题注 -->
                  <div v-if="sec.assetCaption" class="preview-asset__caption">{{ sec.assetCaption }}</div>
                  <!-- 表格:渲染实际表格内容 -->
                  <div v-if="sec.assetType === 'table' && sec.assetRows && sec.assetRows.length" class="preview-asset__table-wrap">
                    <table class="preview-asset__table">
                      <thead v-if="sec.assetRows[0]">
                        <tr><th v-for="(cell, ci) in sec.assetRows[0]" :key="ci">{{ cell }}</th></tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, ri) in sec.assetRows.slice(1)" :key="ri" :class="{ 'preview-asset__row--alt': ri % 2 === 0 }">
                          <td v-for="(cell, ci) in row" :key="ci">{{ cell }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <!-- 图表:优先用后端渲染的 PNG 预览图(更直观,与原文档外观一致) -->
                  <!-- 排版阶段仍走原文档 chart XML via AssetInjector,与此预览图无关 -->
                  <div v-if="sec.assetType === 'chart' && sec.assetChartData && Object.keys(sec.assetChartData).length" class="preview-asset__chart-wrap">
                    <div class="preview-asset__chart-type">{{ chartTypeLabel(sec.assetChartData.chart_type) }}</div>
                    <!-- PNG 预览图 -->
                    <div v-if="sec.assetChartData.image_data" class="preview-asset__chart-image-wrap">
                      <img :src="sec.assetChartData.image_data" :alt="sec.assetCaption || '图表预览'" class="preview-asset__chart-image" />
                    </div>
                    <!-- 无预览图时的兜底:显示数据表 -->
                    <template v-else>
                      <table v-if="sec.assetChartData.categories && sec.assetChartData.categories.length" class="preview-asset__table preview-asset__table--chart">
                        <thead>
                          <tr>
                            <th>类别</th>
                            <th v-for="(sname, si) in (sec.assetChartData.series_names && sec.assetChartData.series_names.length ? sec.assetChartData.series_names : ['数值'])" :key="si">{{ sname }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(cat, ci) in sec.assetChartData.categories" :key="ci" :class="{ 'preview-asset__row--alt': ci % 2 === 0 }">
                            <td>{{ cat }}</td>
                            <td v-for="(series, si) in (sec.assetChartData.values || [])" :key="si">{{ series[ci] || '' }}</td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-if="!sec.assetChartData.categories || !sec.assetChartData.categories.length" class="preview-asset__placeholder">
                        <span class="preview-asset__placeholder-text">图表数据解析中... (排版后保留原图表)</span>
                      </div>
                    </template>
                  </div>
                  <!-- 图片:渲染实际图片 -->
                  <div v-if="sec.assetType === 'image' && sec.assetImageData" class="preview-asset__image-wrap">
                    <img :src="sec.assetImageData" alt="文档图片" class="preview-asset__image" />
                  </div>
                  <!-- 无内容的占位符(表格或图片无数据时) -->
                  <div v-if="(sec.assetType === 'table' && (!sec.assetRows || !sec.assetRows.length)) || (sec.assetType === 'image' && !sec.assetImageData) || (sec.assetType === 'chart' && (!sec.assetChartData || !Object.keys(sec.assetChartData).length))" class="preview-asset__placeholder">
                    <span class="preview-asset__placeholder-text">{{ sec.assetType === 'table' ? '表格' : sec.assetType === 'image' ? '图片' : '图表' }}(内容将在排版后保留)</span>
                  </div>
                </div>
                <!-- 默认渲染: 标题行 + 内容行 -->
                <template v-else>
                  <div v-if="sec.title || sec.label" class="preview-sec__head" :class="'preview-sec__head--' + sec.level">
                    <span v-if="sec.level === 1 && sec.label" class="preview-sec__badge">{{ sec.label }}</span>
                    <span v-if="sec.number" class="preview-sec__num">{{ sec.number }}</span>
                    <span v-if="sec.title" class="preview-sec__title">{{ sec.title }}</span>
                  </div>
                  <div v-if="sec.text" class="preview-sec__text" :class="'preview-sec__text--' + sec.level">
                    <p
                      v-for="(para, pi) in splitParas(sec.text)"
                      :key="pi"
                      class="preview-sec__para"
                      :class="'preview-sec__para--' + sec.kind"
                    >{{ para }}</p>
                  </div>
                </template>
              </div>
              <div v-if="!previewSections.length" class="modal__loading">暂无解析内容</div>
            </div>
            <div class="modal__foot">
              <span class="preview-stats">{{ previewSections.length }} 个章节</span>
              <button class="btn btn--primary" @click="showPreviewModal = false">关闭</button>
            </div>
          </div>
        </div>

        <!-- 排版后内容预览弹窗已移除(用户要求:排版完成弹窗不添加预览排版内容按钮) -->
      </Teleport>

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
                <!-- 自动排版 -->
                <div class="price-section">
                  <div class="price-section-head">
                    <span class="price-section-icon primary">
                      <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </span>
                    <span class="price-section-title">格式重排（按篇计费）</span>
                    <span class="price-section-tag">核心服务</span>
                  </div>
                  <div class="price-table price-table-autodoc">
                    <div class="price-table-head">
                      <span>服务项目</span>
                      <span>计费方式</span>
                      <span>单价</span>
                    </div>
                    <div v-for="(row, rIdx) in priceData.autodoc" :key="rIdx" class="price-table-row">
                      <span class="price-col-name">{{ row.name }}</span>
                      <span class="price-col-mode">{{ row.mode }}</span>
                      <span class="price-col-price">￥{{ row.price }}</span>
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
                    <span class="price-highlight__desc">提交排版任务不扣费，免费预览排版效果，满意下载时才扣费</span>
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
                  <div class="price-grid-2">
                    <div v-for="s in priceData.service" :key="s.name" class="price-grid-item">
                      <span class="grid-item-name">{{ s.name }}</span>
                      <span class="grid-item-price" :class="{ free: s.price === '免费' }">{{ s.price }}</span>
                    </div>
                  </div>
                </div>

                <!-- 说明 -->
                <div class="price-note">
                  <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                  <span>以上价格仅供参考，最终以订单确认页显示为准；排版失败将自动退还费用。</span>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </ClientOnly>

      <!-- 排版处理动画 + 结果弹窗 -->
      <ClientOnly>
        <Teleport to="body">
          <Transition name="proc-fade">
            <div v-if="processing" class="proc-overlay">
              <div class="proc-card">
                <div class="proc-visual">
                  <div class="proc-doc" :class="'proc-doc--' + processPhase">
                    <div class="proc-doc__paper">
                      <div class="proc-doc__line proc-doc__line--title"></div>
                      <div class="proc-doc__line proc-doc__line--short"></div>
                      <div class="proc-doc__line"></div>
                      <div class="proc-doc__line"></div>
                      <div class="proc-doc__line proc-doc__line--short"></div>
                      <div class="proc-doc__line"></div>
                      <div class="proc-doc__line"></div>
                      <div class="proc-doc__line proc-doc__line--short"></div>
                    </div>
                    <div class="proc-scan-beam"></div>
                    <div class="proc-glow"></div>
                  </div>
                  <div class="proc-rings">
                    <span></span><span></span><span></span>
                  </div>
                </div>

                <div class="proc-info">
                  <div class="proc-title">{{ processPhase === 'parsing' ? '正在解析文档' : (chosenMode === 'polish' ? '正在精修排版' : '正在排版生成') }}</div>
                  <div class="proc-status">{{ processStatus }}</div>
                  <div class="proc-bar">
                    <div class="proc-bar__fill" :style="{ width: processProgress + '%' }"></div>
                  </div>
                  <div class="proc-percent">
                    <span class="proc-percent__num">{{ Math.round(processProgress) }}</span>
                    <span class="proc-percent__sign">%</span>
                  </div>
                  <div class="proc-phases">
                    <div class="proc-phase" :class="{ 'proc-phase--done': processPhase === 'typesetting' || processPhase === 'done' }">
                      <span class="proc-phase__dot"></span>
                      <span class="proc-phase__label">解析</span>
                    </div>
                    <div class="proc-phase__connector"></div>
                    <div class="proc-phase" :class="{ 'proc-phase--active': processPhase === 'typesetting' }">
                      <span class="proc-phase__dot"></span>
                      <span class="proc-phase__label">排版</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <Transition name="result-pop">
            <div v-if="showResultModal" class="result-overlay" @click.self="showResultModal = false">
              <div class="result-dialog">
                <div class="result-accent"></div>
                <button class="result-close" @click="showResultModal = false">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <div class="result-body">
                  <div class="result-icon">
                    <svg viewBox="0 0 52 52" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                      <circle class="result-icon__circle" cx="26" cy="26" r="24"/>
                      <path class="result-icon__check" d="M16 27l7 7l14-14"/>
                    </svg>
                  </div>
                  <div class="result-title">{{ chosenMode === 'polish' ? '精修完成' : '排版完成' }}</div>
                  <div class="result-desc">{{ chosenMode === 'polish' ? '文档已按所选模板在原稿基础上完成精修排版，未覆盖内容保留原样，预览免费查看（带水印），下载原文件需付费 ¥' : '文档已按所选模板完成排版，预览免费查看（带水印），下载原文件需付费 ¥' }}{{ resultInfo.amount.toFixed(2) }}</div>
                  <div class="result-meta">
                    <div class="result-meta__item">
                      <span class="result-meta__label">模板</span>
                      <span class="result-meta__value">{{ resultInfo.templateName || '默认模板' }}</span>
                    </div>
                    <div class="result-meta__item" v-if="resultInfo.chapters">
                      <span class="result-meta__label">章节</span>
                      <span class="result-meta__value">{{ resultInfo.chapters }} 章</span>
                    </div>
                    <div class="result-meta__item" v-if="resultInfo.fileSize">
                      <span class="result-meta__label">大小</span>
                      <span class="result-meta__value">{{ resultInfo.fileSize }}</span>
                    </div>
                    <div class="result-meta__item result-meta__item--full" v-if="resultInfo.orderSn">
                      <span class="result-meta__label">订单号</span>
                      <span class="result-meta__value">{{ resultInfo.orderSn }}</span>
                    </div>
                  </div>
                  <div class="result-actions">
                    <button
                      v-if="resultInfo.previewUrl"
                      class="result-download result-preview-btn"
                      @click="handlePreviewResult"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                      <span>预览 PDF</span>
                    </button>
                    <button class="result-download" @click="goToOrders">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                      </svg>
                      <span>前往订单中心下载</span>
                    </button>
                    <button class="result-secondary" @click="showResultModal = false">关闭</button>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </Teleport>
      </ClientOnly>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'console' })

const api = useApi()
const toast = useToast()

// 嵌入模式（分站工作台 iframe 内嵌 ?embed=1）：页面内无 console 顶栏，
// 弹窗遮罩铺满 iframe 可视区（iframe 上方顶栏天然被排除），且页面不再减 64px
const isEmbed = ref(false)
if (typeof window !== 'undefined') {
  const p = new URLSearchParams(window.location.search)
  isEmbed.value = p.get('embed') === '1' || p.get('embed') === 'true'
}

// ── State ──
const templates = ref([])
const totalCount = ref(0)
const loadingTemplates = ref(false)
const selectedTemplateId = ref(null)
const selectedTemplate = ref(null)
const templatePreview = ref({})
const showTemplateParams = ref(false)
const showPreviewModal = ref(false)
const searchKeyword = ref('')
const filterDegree = ref('')
const currentStep = ref(1)
const professionCount = ref(0)
const degreeCount = ref(0)
const schoolCount = ref(0)
// 模板分类 tab：public=公共模板 private=私有模板（TemplistAuto，按本站用户隔离；收藏为主站 token 级共享，不开放）
const tplTab = ref('public')

// ── 初始模式选择 ──
const showModeModal = ref(true)
const chosenMode = ref(null)  // 'polish'=原稿精修 'retype'=智能重排
const MODES = [
  {
    key: 'polish',
    name: '原稿精修',
    tag: '旧文件修改',
    desc: '在已有排版文档基础上做局部调整，保留原版式，只修改需要改动的部分。',
    points: ['保留原有排版格式，无需重做', '只对指定章节内容精准修改', '改动最小，短时间出稿'],
  },
  {
    key: 'retype',
    name: '智能重排',
    tag: '提取内容 · 新建排版',
    desc: '新建一份全新文档，只写入需要排版的内容（正文、图表、参考文献等），源文档封面等非排版部分自动过滤。',
    points: ['新建文档，只保留需排版内容', '自动过滤源文档封面等冗余部分', '一键套用学校标准排版模板'],
  },
]

const currentModeName = computed(() => {
  const m = MODES.find(x => x.key === chosenMode.value)
  return m ? m.name : '选择模式'
})

const uploadDesc = computed(() => {
  if (chosenMode.value === 'polish') {
    return '上传 .docx 格式的论文文件，系统保留原版式并提取内容，便于您局部修改后重新生成'
  }
  return '上传 .docx 格式的论文文件，系统只提取需排版内容生成全新文档，封面等非排版部分自动过滤'
})

function chooseMode(key) {
  chosenMode.value = key
  showModeModal.value = false
}

// ── 收费标准弹窗 ──
const showPriceModal = ref(false)
const aiModelsRaw = ref([])

// 价格数据：从后端 write_type_config.price_config 动态构建
const priceData = computed(() => {
  const models = aiModelsRaw.value
  // 构建自动排版价格表
  const autodoc = models.map(m => {
    let price = '—'
    let mode = '按篇'
    try {
      const cfg = JSON.parse(m.price_config || '[]')
      if (Array.isArray(cfg) && cfg.length > 0) {
        // 当只有 1 条且 max_words=999999 时为按篇收费
        if (cfg.length === 1 && cfg[0].max_words >= 999999) {
          price = cfg[0].price
          mode = '按篇'
        } else {
          price = cfg[0].price
          mode = '按字数'
        }
      }
    } catch (e) {}
    return { name: m.name, mode, price }
  })
  return {
    autodoc,
    service: [
      { name: '模板自适应排版', price: '免费' },
      { name: '章节结构提取', price: '免费' },
      { name: '参考文献格式化', price: '免费' },
      { name: '排版失败退款', price: '自动' },
    ],
  }
})

const steps = computed(() => [
  { num: 1, label: '选择模板', active: currentStep.value === 1, done: currentStep.value > 1 },
  { num: 2, label: '提取内容', active: currentStep.value === 2, done: currentStep.value > 2 },
  { num: 3, label: '生成排版', active: currentStep.value === 3, done: false },
])

const degrees = [
  { value: '', label: '全部' },
  { value: '本科', label: '本科' },
  { value: '专科', label: '专科' },
  { value: '硕士', label: '硕士' },
  { value: '博士', label: '博士' },
]

const metrics = computed(() => [
  { val: totalCount.value.toLocaleString(), label: '模板总数' },
  { val: degreeCount.value, label: '覆盖学历' },
  { val: professionCount.value, label: '涵盖专业' },
  { val: schoolCount.value, label: '合作院校' },
])

const filteredTemplates = computed(() => {
  let list = templates.value.filter(t => t.status == 1)
  const kw = searchKeyword.value.trim()
  if (kw) {
    list = list.filter(t => (t.name || '').includes(kw) || (t.profession || '').includes(kw))
  }
  if (filterDegree.value) {
    list = list.filter(t => (t.degree || '') === filterDegree.value)
  }
  return list
})

const displayedTemplates = computed(() => {
  // 私有模板全量展示（用户自有模板数量少）；公共池随机展示 8 个
  if (tplTab.value === 'private') {
    return filteredTemplates.value
  }
  const pool = filteredTemplates.value.slice(0, 100)
  const shuffled = [...pool].sort(() => Math.random() - 0.5)
  return shuffled.slice(0, 8)
})

const previewSections = computed(() => {
  // 使用 flattened_content(接口返回的扁平化结构,完整 9+ 项)
  const flat = extractedData.value?.flattened_content
  if (Array.isArray(flat) && flat.length) {
    return buildPreviewSectionsFromFlat(flat, extractedData.value?.title)
  }
  return []
})

// ── 文档解析预览:扁平化结构 → 预览 sections(带章节序号) ──
// 递归遍历扁平化结构中的 lists 嵌套,提取所有章节段落
// data.content 结构:
//   {type: 'abstract_zh', title, abstract, keywords}
//   {type: 'abstract_en', title, abstract, keywords}
//   {title: '绪论', lists: [{chapter, sections: '段落', lists: [...嵌套...]}]}
//   {type: 'conclusion', title, lists: [{chapter, sections}]}
//   {type: 'references', title, entries: [{text}]}
//   {type: 'acknowledgment', title, content: '段落'}
function buildPreviewSectionsFromFlat(flat, docTitle) {
  if (!Array.isArray(flat)) return []
  const result = []
  // 注: docTitle (如"学士学位论文") 不渲染到预览中, 因为这不是需要重新排版的内容
  let chapterNum = 0  // 一级章节计数器(1, 2, 3...)
  for (const item of flat) {
    const type = item.type || ''
    const title = item.title || ''
    if (type === 'abstract_zh') {
      result.push({ level: 1, label: '摘要', title: title || '中文摘要', text: item.abstract || '', kind: 'abstract' })
      if (item.keywords) {
        // 关键词: 保留 title='关键词' 作为标识(用于提交时区分), text=纯内容(去除前缀)
        // 渲染时由 keywords 专用模板渲染为单行 "关键词：内容"
        const kw = cleanKeywordsText(item.keywords, 'zh')
        result.push({ level: 2, label: '', title: '关键词', text: kw, kind: 'keywords' })
      }
    } else if (type === 'abstract_en') {
      result.push({ level: 1, label: 'Abstract', title: title || '英文摘要', text: item.abstract || '', kind: 'abstract' })
      if (item.keywords) {
        // Keywords: 保留 title='Keywords' 作为标识, text=纯内容
        const kw = cleanKeywordsText(item.keywords, 'en')
        result.push({ level: 2, label: '', title: 'Keywords', text: kw, kind: 'keywords' })
      }
    } else if (type === 'references') {
      const entries = item.entries || []
      // 统一加序号 "1. xxx",去除可能已有的 [N] / N. / N、 前缀,避免重复
      const text = entries
        .map((e, i) => {
          let t = (e.text || '').trim()
          t = t.replace(/^\[?\d+\]?[.、\s]+/, '')
          return `${i + 1}. ${t}`
        })
        .filter(t => t.replace(/^\d+\.\s*/, '').trim())
        .join('\n')
      result.push({
        level: 1, label: '参考', kind: 'references',
        title: `${title || '参考文献'}（共 ${entries.length} 条）`,
        text,
      })
    } else if (type === 'acknowledgment') {
      result.push({ level: 1, label: '致谢', title: title || '致谢', text: item.content || '', kind: 'acknowledgment' })
    } else if (type === 'conclusion') {
      // 结论:遍历 lists 提取段落
      result.push({ level: 1, label: '结论', title: title || '结论', text: '', kind: 'conclusion' })
      const conclusionText = extractListsText(item.lists || [])
      if (conclusionText) {
        result.push({ level: 2, label: '', title: '', text: conclusionText, kind: 'conclusion' })
      }
    } else if (type === 'introduction') {
      result.push({ level: 1, label: '引言', title: title || '引言', text: '', kind: 'intro' })
      const introText = extractListsText(item.lists || [])
      if (introText) {
        result.push({ level: 2, label: '', title: '', text: introText, kind: 'intro' })
      }
    } else {
      // 普通章节:type 为空字符串,有 title 和 lists
      // 跳过与文档标题完全相同的章节(避免文档标题被作为正文章节重复显示)
      if (title && title.trim() === (docTitle || '').trim()) {
        // 仍然递归提取 lists 中的子章节段落(避免丢失正文内容)
        const subItems = extractListsTree(item.lists || [], 2, chapterNum)
        result.push(...subItems)
        continue
      }
      if (title) {
        chapterNum++
        result.push({ level: 1, label: '章节', number: String(chapterNum), title, text: '', kind: 'section' })
      }
      // 递归提取 lists 中的子章节和段落,传入父章节序号
      const subItems = extractListsTree(item.lists || [], 2, chapterNum)
      result.push(...subItems)
    }
  }
  return result
}

// 清理关键词文本前缀(中文:关键词:/关键字:; 英文:Keywords:/Key words:)
// 避免在预览中与 label 重复显示 "关键词" 字样
function cleanKeywordsText(text, lang) {
  if (!text) return ''
  let t = String(text).trim()
  if (lang === 'zh') {
    // 去除 "关键词:" / "关键字:" / "关键词：" / "关键字：" 前缀
    t = t.replace(/^关键词[：:]\s*/, '').replace(/^关键字[：:]\s*/, '')
  } else {
    // 去除 "Keywords:" / "Key words:" / "Key-Words:" 前缀(大小写不敏感)
    t = t.replace(/^Key[-\s]?[Ww]ords?\s*[:：]\s*/, '')
  }
  return t
}

// 递归遍历 lists,把所有 chapter/sections 展平为预览项
// lists 结构:[{chapter: '小节标题', sections: '正文段落', lists: [...嵌套...]}]
// 资产项: {chapter:'', sections:'', asset_type:'table|image|chart', asset_id:N, asset_meta:{}}
// parentNum: 父章节序号(用于生成 x.y.z 三级编号)
function extractListsTree(lists, baseLevel, parentNum) {
  const result = []
  if (!Array.isArray(lists)) return result
  let subNum = 0  // 子章节计数器
  for (const item of lists) {
    // 资产项(表格/图片/图表):渲染为实际内容
    if (item.asset_type) {
      const meta = item.asset_meta || {}
      result.push({
        level: baseLevel, label: '', title: '', text: '',
        kind: 'asset', assetType: item.asset_type, assetId: item.asset_id,
        assetCaption: item.asset_caption || '',
        assetRows: meta.rows || [],
        assetChartData: meta.chart_data || {},
        assetImageData: meta.image_data || '',
      })
      continue
    }
    const chTitle = item.chapter || ''
    const secText = item.sections || ''
    let number = ''
    // 章节标题(短文本)
    if (chTitle && chTitle.length <= 80) {
      subNum++
      number = parentNum ? `${parentNum}.${subNum}` : String(subNum)
      result.push({ level: baseLevel, label: '', number, title: chTitle, text: secText, kind: 'section' })
    } else if (chTitle) {
      // 长章节名视为正文(不编号)
      result.push({
        level: baseLevel, label: '', title: '',
        text: chTitle + (secText ? '\n' + secText : ''), kind: 'section',
      })
    } else if (secText) {
      // 无章节名,只有正文(不编号)
      result.push({ level: baseLevel, label: '', title: '', text: secText, kind: 'section' })
    }
    // 递归处理嵌套 lists(三级章节)
    if (item.lists && item.lists.length) {
      result.push(...extractListsTree(item.lists, baseLevel + 1, number || parentNum))
    }
  }
  return result
}

// 把多段落文本按换行拆分为段落数组(用于 <p> 列表渲染)
function splitParas(text) {
  if (!text) return []
  return String(text).split(/\r?\n/).map(s => s.trim()).filter(Boolean)
}

// 图表类型中文标签
function chartTypeLabel(type) {
  const map = { barChart: '柱状图', pieChart: '饼图', lineChart: '折线图',
    areaChart: '面积图', doughnutChart: '圆环图', scatterChart: '散点图',
    radarChart: '雷达图', surfaceChart: '曲面图', bubbleChart: '气泡图' }
  return map[type] || '图表'
}

// 把 lists 中所有 sections 段落合并为一个文本(用于结论/引言等)
function extractListsText(lists) {
  if (!Array.isArray(lists)) return ''
  const texts = []
  for (const item of lists) {
    if (item.sections) texts.push(item.sections)
    if (item.lists && item.lists.length) {
      const nested = extractListsText(item.lists)
      if (nested) texts.push(nested)
    }
  }
  return texts.join('\n')
}

function selectTemplate(tpl) {
  selectedTemplateId.value = tpl.template_uid || tpl.id
  // 记录模板来源：私有模板（TemplistAuto 自增 id）下单走 template_type=private
  selectedTemplate.value = { ...tpl, template_type: tpl.template_type || tplTab.value }
  showTemplateParams.value = false
  templatePreview.value = {}
}

// 判断选中状态：以 selectTemplate 中记录的同一标识（template_uid || id）为准
function isSelected(tpl) {
  return selectedTemplateId.value === (tpl.template_uid || tpl.id)
}

// 切换模板分类 tab（私有模板需登录）
function switchTplTab(tab) {
  if (tab === tplTab.value) return
  if (tab !== 'public' && !getToken()) {
    toast.error('请先登录后再查看私有模板')
    return
  }
  tplTab.value = tab
  searchKeyword.value = ''
  filterDegree.value = ''
  templates.value = []
  fetchTemplates()
}

// 获取 token（与 useAuth 存储格式一致：aidian_auth_v2 JSON 中的 token 字段）
function getToken() {
  if (process.client) {
    try {
      const data = JSON.parse(localStorage.getItem('aidian_auth_v2') || 'null')
      return data?.token || ''
    } catch (e) {
      return ''
    }
  }
  return ''
}

function focusSearch() {
  const input = document.querySelector('.filters__search input')
  if (input) {
    input.focus()
    input.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

async function toggleTemplatePreview() {
  if (!selectedTemplateId.value) return
  showTemplateParams.value = true
  if (!Object.keys(templatePreview.value).length) {
    await fetchTemplatePreview(selectedTemplate.value?.conjson || '')
  }
}

async function fetchTemplatePreview(conjsonPath) {
  templatePreview.value = {}
  const r = await api.post('/api/autodoc/templateConfig', { conjson: conjsonPath })
  if (r.ok && r.code === 1) {
    templatePreview.value = r.data?.config || {}
  }
}

async function fetchTemplates() {
  loadingTemplates.value = true
  try {
    const kw = searchKeyword.value.trim()
    // tab=public 走主站公共池；tab=private 走 /openapi/template/list + agent_str（本站用户隔离）
    const r = await api.post('/api/autodoc/templateList', { keyword: kw, tab: tplTab.value })
    if (r.ok && r.code === 1) {
      const d = r.data || {}
      templates.value = (d.list || []).map(t => ({
        ...t,
        avt: normalizeLogoUrl(t.avt),
      }))
      totalCount.value = d.db_total || d.total || templates.value.length
      degreeCount.value = d.degreeCount || 4
      professionCount.value = d.professionCount || 12
      schoolCount.value = d.schoolCount || 8
    } else if (r.code === -1) {
      toast.error('登录已过期，请重新登录')
    }
  } finally {
    loadingTemplates.value = false
  }
}

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

let timer = null
watch([searchKeyword, filterDegree], () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(fetchTemplates, 300)
})

function goToUpload() {
  if (!selectedTemplate.value) {
    toast.error('请先选择模板')
    return
  }
  currentStep.value = 2
}

function goBackToTemplates() {
  currentStep.value = 1
  if (generatedUrl.value) removeFile()
}

const fileInput = ref(null)
const isDragging = ref(false)
const selectedFile = ref(null)
const extracting = ref(false)
const extractedData = ref(null)

function triggerFileUpload() {
  fileInput.value?.click()
}
function handleFileChange(e) {
  const f = e.target.files?.[0]
  if (f) processFile(f)
}
function handleFileDrop(e) {
  isDragging.value = false
  const f = e.dataTransfer?.files[0]
  if (f) processFile(f)
}

async function processFile(file) {
  if (!file.name.endsWith('.docx')) {
    toast.error('仅支持 .docx 格式')
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    toast.error('文件大小不能超过 10MB')
    return
  }
  const arr = new Uint8Array(await file.slice(0, 4096).arrayBuffer())
  if (arr[0] !== 0x50 || arr[1] !== 0x4B) {
    toast.error('文件格式不正确')
    return
  }
  selectedFile.value = file
  extracting.value = true
  extractedData.value = null
  processing.value = true
  startProcessAnim('parsing')
  try {
    // 1. 获取 OSS 预签名上传地址
    const sigRes = await api.post('/api/autodoc/getUploadSignature', { ext: 'docx' })
    if (!sigRes.ok || sigRes.code !== 1) {
      toast.error(sigRes.msg || '获取上传签名失败')
      return
    }
    const sig = sigRes.data

    // 2. 前端直传 OSS
    const putResp = await fetch(sig.put_url, {
      method: 'PUT',
      body: file,
      headers: { 'Content-Type': sig.content_type },
    })
    if (!putResp.ok) {
      toast.error('文件上传失败：HTTP ' + putResp.status)
      return
    }

    // 3. 用下载链接调用后端解析
    const r = await api.post('/api/autodoc/uploadDoc', {
      file_url: sig.download_url,
    }, { timeout: 120000 })
    if (r.ok && r.code === 1) {
      extractedData.value = r.data
      // 保存原文档 OSS URL,供 generate 接口透传给 Python AssetInjector
      // 用于把原文档表格/图表/图片 importNode 到排版结果(对照方案 阶段6)
      extractedData.value.file_url = sig.download_url
      finishProcess()
      toast.success('文档解析成功')
      await new Promise(r => setTimeout(r, 400))
    } else {
      toast.error(r.msg || '文档解析失败')
    }
  } catch (e) {
    toast.error('文档解析失败')
  } finally {
    stopProcessAnim()
    extracting.value = false
    processing.value = false
  }
}

function removeFile() {
  selectedFile.value = null
  extractedData.value = null
  extracting.value = false
  if (fileInput.value) fileInput.value.value = ''
}

function formatFileSize(b) {
  if (!b) return '0 B'
  const u = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(b) / Math.log(1024))
  return parseFloat((b / Math.pow(1024, i)).toFixed(2)) + ' ' + u[i]
}

function formatValue(v) {
  if (typeof v === 'boolean') return v ? '是' : '否'
  if (typeof v === 'object') return JSON.stringify(v)
  return String(v)
}

const generating = ref(false)
const generatedUrl = ref('')

// ── 排版处理动画状态 ──
const processing = ref(false)
const processPhase = ref('parsing')       // 'parsing' | 'typesetting' | 'done'
const processProgress = ref(0)
const processStatus = ref('正在初始化...')
const showResultModal = ref(false)
const resultInfo = reactive({
  url: '',
  templateName: '',
  chapters: 0,
  fileSize: '',
  orderSn: '',
  previewUrl: '',
  amount: 0,
})
let _procTimer = null
let _procStatusTimer = null

const PARSE_STATUSES = [
  '正在上传文档...',
  '正在扫描文档结构...',
  '正在识别章节标题...',
  '正在提取正文内容...',
  '正在分析参考文献...',
]
const TYPESET_STATUSES = [
  '正在加载模板参数...',
  '正在应用页面设置...',
  '正在渲染封面与目录...',
  '正在排版正文段落...',
  '正在生成页眉页脚...',
  '正在输出最终文档...',
]

function startProcessAnim(phase) {
  processPhase.value = phase
  processProgress.value = 0
  const statuses = phase === 'parsing' ? PARSE_STATUSES : TYPESET_STATUSES
  processStatus.value = statuses[0]
  let si = 0
  clearInterval(_procStatusTimer)
  _procStatusTimer = setInterval(() => {
    si = (si + 1) % statuses.length
    processStatus.value = statuses[si]
  }, 2400)
  clearInterval(_procTimer)
  _procTimer = setInterval(() => {
    if (processProgress.value < 85) {
      processProgress.value = Math.min(85, processProgress.value + Math.random() * 6 + 1.5)
    }
  }, 350)
}

function finishProcess() {
  clearInterval(_procTimer)
  clearInterval(_procStatusTimer)
  processProgress.value = 100
  processStatus.value = '完成'
}

function stopProcessAnim() {
  clearInterval(_procTimer)
  clearInterval(_procStatusTimer)
}

async function startGenerate() {
  if (!selectedTemplate.value) {
    toast.error('请先选择排版模板')
    return
  }
  if (!extractedData.value) {
    toast.error('请先上传文档')
    return
  }
  generating.value = true
  generatedUrl.value = ''
  processing.value = true
  startProcessAnim('typesetting')
  let success = false
  try {
    const r = await api.post('/api/autodoc/generate', {
      templateId: selectedTemplate.value?.id,
      template_no: selectedTemplate.value?.template_uid || '',
      // 模板来源：private=私有模板（TemplistAuto，主站按 token 名下 user_id 校验）public=公共模板
      template_type: selectedTemplate.value?.template_type || 'public',
      // 不携带整份 format_payload 内容(已在 OSS,避免请求体过大被防火墙/网关拦截),只回传 URL
      // 后端从 OSS 下载解析;数据库也只存该 URL
      format_payload_url: extractedData.value?.format_payload_url || '',
      fileName: selectedFile.value?.name || '',
      file_url: extractedData.value?.file_url || '',
      // 排版模式: polish=原稿精修(源文不重建,只重排模板覆盖段) / retype=智能重排
      mode: chosenMode.value === 'polish' ? 'polish' : 'retype',
    }, { timeout: 300000 })
    if (r.ok && r.code === 1) {
      finishProcess()
      await new Promise(r => setTimeout(r, 500))
      // 后付费模式:生成不扣费,doc_url 仅做记录,前端不直接提供下载
      // 实际下载需通过订单中心(/orders/autodoc)调用 downloadDoc 接口扣费后获取
      generatedUrl.value = r.data?.doc_url || ''
      resultInfo.url = r.data?.doc_url || ''
      resultInfo.templateName = selectedTemplate.value?.name || ''
      resultInfo.chapters = extractedData.value?.summary?.chapters || 0
      resultInfo.fileSize = r.data?.file_size ? formatFileSize(r.data.file_size) : ''
      resultInfo.orderSn = r.data?.order_sn || ''
      resultInfo.previewUrl = r.data?.preview_url || ''
      resultInfo.amount = Number(r.data?.amount) || 0
      success = true
      toast.success('排版生成成功,请前往订单中心预览并下载')
    } else {
      toast.error(r.msg || '排版生成失败')
    }
  } catch (e) {
    toast.error('排版生成失败')
  } finally {
    stopProcessAnim()
    generating.value = false
    processing.value = false
    if (success) showResultModal.value = true
  }
}

// 弹窗:预览 PDF(带水印,免费查看)
function handlePreviewResult() {
  if (!resultInfo.previewUrl) {
    toast.info('预览文件不存在')
    return
  }
  window.open(resultInfo.previewUrl, '_blank')
}

// 弹窗:前往订单中心下载(扣费)
function goToOrders() {
  showResultModal.value = false
  navigateTo('/pc/orders/autodoc')
}

onMounted(() => {
  fetchTemplates()
  // 加载自动排版模型+价格配置（type=autodoc 公开免登录）
  api.get('/api/ai/models', { type: 'autodoc' })
    .then(r => {
      if (r.ok && Array.isArray(r.data)) {
        aiModelsRaw.value = r.data
      }
    })
    .catch(() => {})
})
</script>

<style scoped>
.autodoc-page {
  --c-primary: #14b8a6;
  --c-primary-dark: #0d9488;
  --c-accent: #0f766e;
  --c-text: #0f172a;
  --c-text2: #475569;
  --c-muted: #64748b;
  --c-green: #10b981;
  --c-red: #ef4444;
  --c-ring: rgba(20, 184, 166, .35);
  --glass-bg: rgba(255, 255, 255, .55);
  --glass-border: rgba(226, 232, 240, .8);
  --glass-blur: blur(14px);
  --shadow-glass: 0 4px 20px rgba(20, 184, 166, .06), 0 1px 4px rgba(0, 0, 0, .03);
  --shadow-glass-float: 0 8px 32px rgba(20, 184, 166, .1), 0 4px 12px rgba(0, 0, 0, .04);
  --shadow-btn: 0 4px 16px rgba(20, 184, 166, .25);
  min-height: calc(100vh - 64px);
  font-family: system-ui, -apple-system, sans-serif;
  background: linear-gradient(165deg, #f8fafc 0%, #f1f5f9 100%);
  position: relative;
}

/* 嵌入模式（分站 iframe 内嵌 ?embed=1）：iframe 内无 console 顶栏，
   100vh 即 iframe 可视高度，min-height 不应再减 64px（顶栏在 iframe 上方，已天然排除） */
.autodoc-page--embed {
  min-height: 100vh;
}

/* Steps */
.steps {
  display: flex; align-items: center; justify-content: center; gap: 0;
  padding: 14px 24px;
  background: var(--glass-bg);
  backdrop-filter: var(--glass-blur);
  border-bottom: 1px solid var(--glass-border);
  position: sticky; top: 0; z-index: 10;
}
.steps__item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 22px; border-radius: 40px;
  transition: all .25s ease;
}
.steps__item--active {
  background: #fff;
  box-shadow: var(--shadow-glass), 0 0 0 1px rgba(20,184,166,.12);
}
.steps__item--active .steps__num {
  background: linear-gradient(135deg, var(--c-primary), var(--c-accent));
  color: #fff;
}
.steps__item--active .steps__label {
  color: var(--c-primary);
  font-weight: 700;
}
.steps__item--done {
  background: rgba(16,185,129,.06);
  box-shadow: 0 0 0 1px rgba(16,185,129,.1);
}
.steps__item--done .steps__num { background: var(--c-green); color: #fff; }
.steps__item--done .steps__label { color: var(--c-green); font-weight: 600; }
.steps__num {
  width: 26px; height: 26px; border-radius: 50%; border: 2px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: var(--c-muted); background: #fff;
  flex-shrink: 0;
}
.steps__label { font-size: 14px; color: var(--c-text2); font-weight: 600; }
.steps__line {
  width: 40px; height: 2px; background: #e2e8f0; border-radius: 1px; margin-left: 10px;
}
.steps__line--done { background: var(--c-green); }

/* Content */
.content, .upload {
  max-width: 1200px; margin: 0 auto;
  padding: 28px 24px 40px;
}
.header {
  display: flex; align-items: baseline; justify-content: space-between; gap: 24px;
  margin-bottom: 24px;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(20,184,166,.08);
}
.header__heading { font-size: 20px; font-weight: 700; color: var(--c-text); margin: 0 0 6px; }
.header__desc { font-size: 13px; color: var(--c-muted); margin: 0; }
.header__stats { display: flex; gap: 20px; flex-shrink: 0; padding-top: 2px; }
.header__stat { font-size: 12px; color: var(--c-muted); font-weight: 500; white-space: nowrap; }
.header__stat b { color: var(--c-primary); font-weight: 700; }

/* Filters */
.filters { margin-bottom: 18px; }
.tpl-type-switch {
  display: flex; justify-content: center; gap: 10px; margin-bottom: 14px;
}
.tpl-type-btn {
  padding: 6px 18px; font-size: 13px; font-weight: 600;
  color: var(--c-text2); background: #fff;
  border: 1px solid #e2e8f0; border-radius: 18px;
  cursor: pointer; transition: all .18s;
}
.tpl-type-btn:hover { border-color: var(--c-primary); color: var(--c-primary); }
.tpl-type-btn.active {
  background: var(--c-primary); color: #fff; border-color: var(--c-primary);
  box-shadow: 0 2px 8px rgba(20,184,166,.25);
}
.filters__row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.filters__search {
  flex: 1; min-width: 240px; max-width: 400px; margin-left: auto; position: relative;
}
.filters__search svg {
  position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
  color: var(--c-muted); pointer-events: none;
}
.filters__search input {
  width: 100%; box-sizing: border-box;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 20px;
  padding: 9px 34px 9px 40px; font-size: 13px; color: var(--c-text); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.filters__search input:focus {
  border-color: var(--c-primary);
  box-shadow: 0 0 0 3px rgba(20,184,166,.08);
}
.filters__search span {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  cursor: pointer; color: #94a3b8; font-size: 16px; width: 22px; height: 22px;
  display: flex; align-items: center; justify-content: center; border-radius: 50%;
}
.filters__search span:hover { color: var(--c-text); background: rgba(0,0,0,.04); }

.filter-btn {
  padding: 6px 14px; border-radius: 6px; border: 1px solid #e2e8f0;
  background: #fff; color: var(--c-text2); font-size: 12px; font-weight: 600; cursor: pointer;
  transition: all .15s; white-space: nowrap;
}
.filter-btn:hover { border-color: var(--c-primary); color: var(--c-primary); }
.filter-btn.active { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }

/* Grid */
.grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  overflow: hidden; cursor: pointer; position: relative;
  box-shadow: 0 1px 3px rgba(0,0,0,.03);
  transition: all .2s ease;
}
.card:hover {
  border-color: rgba(20,184,166,.25);
}
.card.selected {
  border-color: var(--c-primary);
  box-shadow: var(--shadow-glass-float), 0 0 0 2px rgba(20,184,166,.1);
}
.card__preview {
  width: 100%; height: 130px;
  background: linear-gradient(155deg, rgba(20,184,166,.04), #f8fafc);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.card__preview img { width: 78%; height: 74%; object-fit: contain; }
.card__fallback-text { font-size: 13px; font-weight: 700; color: var(--c-muted); }
.card__info { padding: 10px 14px 12px; }
.card__title { font-size: 13px; font-weight: 700; color: var(--c-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 5px; }
.card__tags { display: flex; gap: 4px; flex-wrap: nowrap; overflow: hidden; }
.card__check { position: absolute; top: 110px; right: 10px; }

.templates-hint {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 16px; padding: 16px 20px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-radius: 12px;
  cursor: pointer; transition: all .25s ease;
  box-shadow: 0 4px 12px rgba(20,184,166,.2);
}
.templates-hint:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(20,184,166,.3);
}
.templates-hint__left { display: flex; align-items: center; gap: 14px; }
.templates-hint__badge {
  font-size: 18px; font-weight: 800; color: #fff;
  background: rgba(255,255,255,.2); border-radius: 8px;
  padding: 6px 12px; white-space: nowrap;
  backdrop-filter: blur(4px);
}
.templates-hint__text { display: flex; flex-direction: column; gap: 2px; }
.templates-hint__title { font-size: 15px; font-weight: 700; color: #fff; }
.templates-hint__desc { font-size: 12px; color: rgba(255,255,255,.85); }
.templates-hint__btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600; color: #0d9488;
  background: #fff; border-radius: 8px; padding: 8px 14px;
  transition: all .2s; white-space: nowrap;
}
.templates-hint:hover .templates-hint__btn { gap: 8px; }

/* Step navigation */
.step-nav {
  display: flex; justify-content: center; align-items: center; gap: 12px;
  margin-top: 24px; padding-top: 20px;
  border-top: 1px solid #f1f5f9;
}

.tag {
  display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 4px;
  font-size: 11px; font-weight: 600; white-space: nowrap;
  background: rgba(20,184,166,.08); color: var(--c-primary);
}
.tag--year { background: rgba(16,185,129,.08); color: var(--c-green); }

/* Upload */
.upload-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.03);
}
.upload-card__tip {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 20px; padding: 10px 14px;
  background: rgba(16,185,129,.04); border: 1px solid rgba(16,185,129,.08);
  border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--c-green);
}
.upload-card__status {
  text-align: center; padding: 22px; margin-top: 18px;
  border-radius: 10px;
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  background: rgba(20,184,166,.03); font-size: 14px; font-weight: 600; color: var(--c-text2);
}
.upload-card__status--ok {
  background: rgba(16,185,129,.04); border: 1px solid rgba(16,185,129,.08);
  color: var(--c-green);
}
.upload-card__status--ok strong { font-size: 16px; font-weight: 700; }
.upload-card__status--ok .btn--primary { text-decoration: none; }

/* Dropzone */
.dropzone {
  border: 2px dashed #e2e8f0; border-radius: 12px;
  background: #f8fafc; transition: all .2s; cursor: pointer; overflow: hidden;
}
.dropzone:hover { border-color: rgba(20,184,166,.35); background: rgba(20,184,166,.02); }
.dropzone--over { border-color: var(--c-primary); border-style: solid; background: rgba(20,184,166,.04); }
.dropzone--done { border-style: solid; border-color: #e2e8f0; background: #fff; cursor: default; }
.dropzone__empty { display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 56px 24px; }
.dropzone__circle {
  width: 54px; height: 54px; border-radius: 50%;
  background: rgba(20,184,166,.06); border: 1px solid rgba(20,184,166,.1);
  display: flex; align-items: center; justify-content: center; color: var(--c-primary);
}
.dropzone__hint { font-size: 15px; font-weight: 700; color: var(--c-text); margin: 0; }
.dropzone__sub { font-size: 13px; color: var(--c-muted); margin: 0; }
.dropzone__file { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px; }
.dropzone__file-row { display: flex; align-items: center; gap: 14px; }
.dropzone__file-icon {
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(20,184,166,.05); border: 1px solid rgba(20,184,166,.1);
  display: flex; align-items: center; justify-content: center; color: var(--c-primary);
}
.dropzone__file-icon--ok { background: rgba(16,185,129,.05); border-color: rgba(16,185,129,.1); color: var(--c-green); }
.dropzone__fname { font-size: 15px; font-weight: 700; color: var(--c-text); max-width: 360px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dropzone__fsize { font-size: 12px; color: var(--c-muted); font-weight: 500; }
.dropzone__state {
  font-size: 13px; font-weight: 600; padding: 5px 12px; border-radius: 6px;
  color: var(--c-primary); background: rgba(20,184,166,.05);
}
.dropzone__state--ok { color: var(--c-green); background: rgba(16,185,129,.05); }
.dropzone__actions { display: flex; gap: 10px; }

/* Buttons */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  min-height: 40px; padding: 8px 20px; border-radius: 8px;
  font-size: 14px; font-weight: 600; cursor: pointer; border: none;
  transition: all .2s ease;
}
.btn--primary {
  background: linear-gradient(135deg, var(--c-primary), var(--c-accent));
  color: #fff; box-shadow: var(--shadow-btn);
}
.btn--primary:hover:not(:disabled) { box-shadow: 0 6px 20px rgba(20,184,166,.35); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
.btn--ghost {
  background: #fff; border: 1px solid #e2e8f0; color: var(--c-text2);
}
.btn--ghost:hover { border-color: var(--c-primary); color: var(--c-primary); background: #f0fdfa; }
.btn--ghost-danger { color: var(--c-red); border-color: #fee2e2; }
.btn--ghost-danger:hover { background: #fef2f2; border-color: var(--c-red); }

/* Inputs */
.input {
  box-sizing: border-box; width: 100%;
  background: #fff; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 10px 12px;
  font-size: 14px; color: var(--c-text); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.input:focus { border-color: var(--c-primary); box-shadow: 0 0 0 3px rgba(20,184,166,.08); }
.input--select { cursor: pointer; }
.input--textarea { min-height: 72px; resize: vertical; }

/* Spinner */
.spinner { width: 32px; height: 32px; border: 3px solid #e2e8f0; border-top-color: var(--c-primary); border-radius: 50%; animation: spin .6s linear infinite; }
.spinner-mini { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }

.empty { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 48px 24px; color: var(--c-muted); font-size: 13px; font-weight: 600; }

/* Modal */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, .35);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px; width: 480px; max-width: 94vw; max-height: 85vh;
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow: 0 24px 64px rgba(0,0,0,.12);
}
.modal--wide { width: 720px; }
.modal__head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.modal__title { font-size: 16px; font-weight: 700; color: var(--c-text); }
.modal__close { width: 30px; height: 30px; border: none; border-radius: 6px; background: none; color: var(--c-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal__close:hover { background: #f1f5f9; color: var(--c-text); }
.modal__body { padding: 20px; overflow-y: auto; flex: 1; }
.modal__foot { display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding: 14px 20px; border-top: 1px solid #f1f5f9; background: #f8fafc; }
.modal__loading { text-align: center; padding: 20px; color: var(--c-muted); }

/* Preview Modal */
.preview-body { max-height: 70vh; padding: 4px 8px; }
.preview-sec { margin-bottom: 2px; }
/* 资产内容渲染(表格/图片/图表) */
.preview-asset {
  /* 资产容器(表格/图表/图片)限制宽度 480px 并居中,避免在 720px 弹窗中显得过宽
     章节标题/正文仍占满父宽度,只有资产区域收窄 */
  max-width: 480px;
  margin: 8px auto;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fafbfc;
}
.preview-asset--table {
  border-color: #dbe4f0;
  background: linear-gradient(180deg, #fbfdff 0%, #f5f8fc 100%);
  box-shadow: 0 1px 2px rgba(30, 58, 95, 0.04);
}
.preview-asset--image {
  border-color: #c7e9d0;
  background: linear-gradient(180deg, #f6fdf6 0%, #f0faf1 100%);
  box-shadow: 0 1px 2px rgba(20, 83, 45, 0.04);
}
.preview-asset--chart {
  border-color: #e0d4f5;
  background: linear-gradient(180deg, #fcfaff 0%, #f6f1fc 100%);
  box-shadow: 0 1px 2px rgba(76, 29, 149, 0.05);
}
.preview-asset__caption {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 8px;
  text-align: center;
  letter-spacing: 0.2px;
}
.preview-asset__table-wrap {
  overflow-x: auto;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
}
.preview-asset__table-wrap::-webkit-scrollbar { height: 6px; }
.preview-asset__table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.preview-asset__table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}
.preview-asset__table th {
  background: linear-gradient(180deg, #f1f5fb 0%, #e8eef7 100%);
  border-bottom: 1px solid #cbd5e1;
  padding: 8px 12px;
  text-align: left;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  letter-spacing: 0.2px;
}
.preview-asset__table th:first-child { border-top-left-radius: 6px; }
.preview-asset__table th:last-child { border-top-right-radius: 6px; }
.preview-asset__table td {
  border-bottom: 1px solid #eef2f7;
  padding: 7px 12px;
  color: #334155;
  line-height: 1.5;
}
.preview-asset__table tbody tr:last-child td { border-bottom: none; }
.preview-asset__table tbody tr.preview-asset__row--alt td { background: #f8fafc; }
.preview-asset__table tbody tr:hover td { background: #eff6ff; }
.preview-asset__table--chart th {
  background: linear-gradient(180deg, #f3eefa 0%, #ebe2f7 100%);
  color: #4c1d95;
}
.preview-asset__table--chart tbody tr:hover td { background: #f5f0fc; }
.preview-asset__chart-type {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #6d28d9;
  margin-bottom: 8px;
  padding: 3px 8px;
  background: #ede9fe;
  border-radius: 10px;
  font-weight: 600;
  border: 1px solid #ddd3fb;
}
.preview-asset__chart-type::before {
  content: '';
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #8b5cf6;
}
.preview-asset__chart-image-wrap {
  text-align: center;
  padding: 8px;
  background: #fff;
  border-radius: 6px;
  border: 1px solid #e9d5ff;
}
.preview-asset__chart-image {
  /* 限制图片最大宽度 320px:后端渲染 400×240 PNG,前端按 320px 显示更紧凑
     之前 420px 在 720px 弹窗中显得过大,用户反馈"图表尺寸太大" */
  max-width: 320px;
  width: 100%;
  height: auto;
  border-radius: 4px;
  display: inline-block;
}
.preview-asset__image-wrap {
  text-align: center;
  padding: 6px;
  background: #fff;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}
.preview-asset__image {
  /* 图片限制最大宽度 360px + 最大高度 280px,避免大图撑满 480px 容器 */
  max-width: 360px;
  max-height: 280px;
  border-radius: 4px;
}
.preview-asset__placeholder {
  padding: 16px 12px;
  text-align: center;
  background: #fff;
  border-radius: 6px;
  border: 1px dashed #cbd5e1;
}
.preview-asset__placeholder-text {
  font-size: 12px;
  color: var(--c-text-soft);
}
.preview-sec__head {
  display: flex;
  align-items: center;
  gap: 8px;
  line-height: 1.5;
}
/* 文档标题(level 0):居中、大号、加粗,带底部边框 */
.preview-sec__head--0 {
  justify-content: center;
  padding: 6px 0 12px;
  margin-bottom: 6px;
  border-bottom: 2px solid #e2e8f0;
}
.preview-sec__head--0 .preview-sec__title {
  font-size: 18px;
  font-weight: 700;
  color: var(--c-text);
  text-align: center;
}
.preview-sec__head--0:first-child { margin-top: 0; }
/* 一级章节:大号加粗,有下边框 */
.preview-sec__head--1 {
  margin-top: 22px;
  padding-bottom: 6px;
  border-bottom: 1px solid #f1f5f9;
}
.preview-sec__head--1:first-child { margin-top: 0; }
.preview-sec__head--1 .preview-sec__title {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
}
/* 二级标题:中等缩进 */
.preview-sec__head--2 {
  padding-left: 24px;
  margin-top: 12px;
}
.preview-sec__head--2 .preview-sec__title {
  font-size: 14px;
  font-weight: 600;
  color: var(--c-text2);
}
/* 三级标题:更深缩进 */
.preview-sec__head--3 {
  padding-left: 48px;
  margin-top: 8px;
}
.preview-sec__head--3 .preview-sec__title {
  font-size: 13px;
  font-weight: 500;
  color: var(--c-text2);
}
.preview-sec__badge {
  font-size: 11px;
  font-weight: 600;
  color: #0d9488;
  background: #ccfbf1;
  padding: 2px 8px;
  border-radius: 4px;
  flex-shrink: 0;
}
/* 章节序号(1 / 1.1 / 1.1.1) */
.preview-sec__num {
  font-weight: 700;
  color: var(--c-text);
  flex-shrink: 0;
  margin-right: 2px;
}
.preview-sec__head--1 .preview-sec__num { font-size: 17px; }
.preview-sec__head--2 .preview-sec__num { font-size: 14px; color: var(--c-text2); }
.preview-sec__head--3 .preview-sec__num { font-size: 13px; color: var(--c-text2); font-weight: 600; }
/* 段落容器:跟随标题层级缩进 */
.preview-sec__text {
  margin-top: 4px;
}
.preview-sec__text--1 { padding-left: 0; }
.preview-sec__text--2 { padding-left: 24px; }
.preview-sec__text--3 { padding-left: 48px; }
/* 单段:首行缩进 2 字符,行高 1.8 */
.preview-sec__para {
  font-size: 13px;
  color: var(--c-muted);
  line-height: 1.8;
  text-indent: 2em;
  margin: 0 0 6px 0;
  overflow-wrap: break-word;
}
.preview-sec__para:last-child { margin-bottom: 0; }
/* 关键词专用样式: 单行 "标题：内容", 使用 inline 布局确保不换行,独立标签结构便于提交时区分 */
.preview-sec__keywords {
  margin-top: 12px;
  padding: 0 16px 0 24px;
  font-size: 13px;
  line-height: 1.8;
}
.preview-sec__keywords--2 { padding-left: 24px; }
.preview-sec__keywords--3 { padding-left: 48px; }
.preview-sec__keywords-label {
  font-weight: 600;
  color: var(--c-text2);
  display: inline;
}
.preview-sec__keywords-text {
  color: var(--c-muted);
  display: inline;
  word-break: break-word;
}
/* 致谢:多段落,首行缩进 2em(与正文一致),左右加间距与弹窗两侧拉开 */
.preview-sec--acknowledgment .preview-sec__text {
  padding-left: 24px;
  padding-right: 16px;
}
/* 参考文献:每条整体缩进,条目间留白 */
.preview-sec--references .preview-sec__text {
  padding-left: 32px;
  padding-right: 16px;
}
.preview-sec__para--references {
  text-indent: 0;
  padding-left: 0;
  margin-bottom: 8px;
  line-height: 1.7;
  color: var(--c-text2);
}
.preview-sec__para--references:last-child { margin-bottom: 0; }
.preview-stats { font-size: 13px; color: var(--c-muted); margin-right: auto; }

/* Forms */
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--c-text2); margin-bottom: 6px; }
.required { color: var(--c-red); }
.form-row { display: flex; gap: 12px; }
.form-row > * { flex: 1; }

/* Params */
.params {
  display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 420px; overflow-y: auto;
}
.params__row { display: flex; align-items: flex-start; gap: 8px; padding: 8px 12px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; }
.params__key { font-size: 12px; color: var(--c-muted); white-space: nowrap; flex-shrink: 0; min-width: 80px; font-weight: 600; }
.params__val { font-size: 13px; color: var(--c-text2); line-height: 1.5; word-break: break-all; }

/* Responsive */
@media (max-width: 1024px) {
  .grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
  .header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .header__stats { padding-top: 0; }
  .footer-bar { flex-direction: column; align-items: flex-start; }
  .footer-bar__actions { width: 100%; justify-content: flex-end; }
  .steps__label { display: none; }
}
@media (max-width: 480px) {
  .grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .params { grid-template-columns: 1fr; }
  .form-row { flex-direction: column; }
}

/* ≤640 步骤页布局收紧（M3 compact：内容距屏缘 16px、主操作全宽、去大色块投影）
   壳层已提供外边距（console .main 12px / m .m-main 14px），容器内边距只补差值 2px，
   消除 PC 版 24px 容器内边距在手机上叠加出的 38px 大留白与内容蜷缩。
   嵌入模式（分站 iframe ?embed=1）无壳层外边距，不参与收紧。 */
@media (max-width: 640px) {
  .steps { justify-content: flex-start; gap: 6px; padding: 8px 12px; }
  .autodoc-page:not(.autodoc-page--embed) .steps { top: 52px; }
  /* 步骤器 H5 化：mode-switch（精修排版）/price-standard-btn（收费标准）在 PC 是
     absolute 压在步骤条两端，窄屏会盖住居中的步骤圆点（文字重叠）
     → 改回文档流右聚，步骤只留数字圆点（M3 紧凑进度点），连接线省略 */
  .steps__item { padding: 4px; }
  .steps__line { display: none; }
  .steps .mode-switch,
  .steps .price-standard-btn {
    position: static; transform: none;
    flex-shrink: 0; margin: 0;
    padding: 5px 10px; font-size: 12px; gap: 4px;
  }
  .steps .mode-switch { margin-left: auto; }
  .autodoc-page:not(.autodoc-page--embed) .content,
  .autodoc-page:not(.autodoc-page--embed) .upload { padding: 12px 2px 28px; }
  .header { margin-bottom: 16px; padding-bottom: 12px; }
  .filters { margin-bottom: 14px; }
  .tpl-type-switch { gap: 8px; margin-bottom: 12px; }
  .tpl-type-btn { flex: 1; padding: 8px 12px; }
  .filters__row { gap: 6px; }
  .filters__search { min-width: 0; }
  .grid { gap: 10px; }
  .empty { padding: 40px 16px; }
  /* 「海量模板库」横幅：teal 渐变大色块 → M3 tonal 容器（浅底深字、去彩色投影） */
  .templates-hint {
    margin-top: 14px; padding: 12px 14px;
    background: #eefaf7;
    border: 1px solid rgba(20, 184, 166, 0.14);
    box-shadow: none;
  }
  .templates-hint__badge {
    background: rgba(20, 184, 166, 0.12);
    color: var(--c-primary);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
  }
  .templates-hint__title { color: var(--c-text); }
  .templates-hint__desc { color: var(--c-muted); }
  .step-nav { margin-top: 18px; padding-top: 14px; gap: 10px; }
  .step-nav .btn { flex: 1; justify-content: center; min-height: 44px; padding: 8px 12px; }
  .upload-card { padding: 16px; }
  .upload-card__tip { margin-bottom: 14px; padding: 9px 12px; }
  .dropzone__empty { padding: 34px 16px; gap: 12px; }
  .dropzone__file { padding: 16px; }
  .dropzone__actions { gap: 8px; }
}

/* ═══════════════ 排版处理动画 ═══════════════ */
.proc-overlay {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(15, 23, 42, .28);
  backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
}
.proc-card {
  display: flex; align-items: center; gap: 48px;
  background: rgba(255, 255, 255, .96);
  border: 1px solid rgba(226, 232, 240, .6);
  border-radius: 24px;
  padding: 40px 48px;
  box-shadow: 0 32px 80px rgba(15, 23, 42, .12), 0 0 0 1px rgba(255,255,255,.5) inset;
  min-width: 520px;
}

/* ── 文档动画区域 ── */
.proc-visual {
  position: relative; width: 120px; height: 140px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.proc-doc {
  position: relative; width: 80px; height: 104px;
}
.proc-doc__paper {
  position: absolute; inset: 0;
  background: #fff; border: 2px solid var(--c-primary); border-radius: 6px;
  padding: 12px 10px; display: flex; flex-direction: column; gap: 5px;
  box-shadow: 0 8px 24px rgba(20, 184, 166, .12);
  overflow: hidden;
}
.proc-doc__line {
  height: 3px; border-radius: 2px; background: #cbd5e1;
  transform-origin: left center;
  animation: doc-line-pulse 2s ease-in-out infinite;
}
.proc-doc__line--title { height: 5px; background: var(--c-primary); width: 60%; margin-bottom: 4px; animation-delay: 0s; }
.proc-doc__line--short { width: 45%; }
.proc-doc__line:nth-child(2) { animation-delay: .15s; }
.proc-doc__line:nth-child(3) { animation-delay: .3s; }
.proc-doc__line:nth-child(4) { animation-delay: .45s; }
.proc-doc__line:nth-child(5) { animation-delay: .6s; }
.proc-doc__line:nth-child(6) { animation-delay: .75s; }
.proc-doc__line:nth-child(7) { animation-delay: .9s; }
.proc-doc__line:nth-child(8) { animation-delay: 1.05s; }
@keyframes doc-line-pulse {
  0%, 100% { opacity: .35; transform: scaleX(.85); }
  50% { opacity: 1; transform: scaleX(1); }
}

/* 扫描光束 */
.proc-scan-beam {
  position: absolute; left: -6px; right: -6px; top: 0; height: 3px;
  background: linear-gradient(90deg, transparent, var(--c-primary), transparent);
  border-radius: 2px; opacity: 0;
  box-shadow: 0 0 12px 2px rgba(20, 184, 166, .4);
  animation: scan-down 2.2s ease-in-out infinite;
}
.proc-doc--parsing .proc-scan-beam { opacity: 1; }
@keyframes scan-down {
  0% { top: 2px; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100px; opacity: 0; }
}

/* 排版阶段:文档整体微浮动 + 光晕 */
.proc-doc--typesetting .proc-doc__paper { animation: doc-float 2.5s ease-in-out infinite; }
.proc-glow {
  position: absolute; inset: -12px; border-radius: 12px;
  background: radial-gradient(circle at center, rgba(20,184,166,.15), transparent 70%);
  opacity: 0; animation: glow-pulse 2s ease-in-out infinite;
}
.proc-doc--typesetting .proc-glow { opacity: 1; }
@keyframes doc-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
@keyframes glow-pulse { 0%, 100% { opacity: .3; } 50% { opacity: .7; } }

/* 脉冲圆环 */
.proc-rings {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  width: 100px; height: 100px; pointer-events: none;
}
.proc-rings span {
  position: absolute; inset: 0; border: 2px solid var(--c-primary);
  border-radius: 50%; opacity: 0;
  animation: ring-expand 2s ease-out infinite;
}
.proc-rings span:nth-child(2) { animation-delay: .65s; }
.proc-rings span:nth-child(3) { animation-delay: 1.3s; }
@keyframes ring-expand {
  0% { transform: scale(.5); opacity: .5; }
  100% { transform: scale(1.8); opacity: 0; }
}

/* ── 信息区域 ── */
.proc-info { flex: 1; min-width: 240px; }
.proc-title { font-size: 20px; font-weight: 700; color: var(--c-text); margin-bottom: 4px; }
.proc-status {
  font-size: 13px; color: var(--c-muted); margin-bottom: 16px;
  min-height: 18px; transition: opacity .3s;
}
.proc-bar {
  height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;
  margin-bottom: 8px;
}
.proc-bar__fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, #14b8a6, #2dd4bf);
  transition: width .35s ease;
  position: relative;
}
.proc-bar__fill::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
  animation: bar-shimmer 1.5s linear infinite;
}
@keyframes bar-shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

.proc-percent { display: flex; align-items: baseline; gap: 2px; margin-bottom: 18px; }
.proc-percent__num { font-size: 28px; font-weight: 800; color: var(--c-primary); font-variant-numeric: tabular-nums; }
.proc-percent__sign { font-size: 14px; font-weight: 600; color: var(--c-muted); }

/* 阶段指示器 */
.proc-phases { display: flex; align-items: center; gap: 8px; }
.proc-phase { display: flex; align-items: center; gap: 6px; }
.proc-phase__dot {
  width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0;
  transition: all .3s;
}
.proc-phase__label { font-size: 12px; color: var(--c-muted); font-weight: 600; }
.proc-phase--done .proc-phase__dot { background: var(--c-green); }
.proc-phase--done .proc-phase__label { color: var(--c-green); }
.proc-phase--active .proc-phase__dot { background: var(--c-primary); box-shadow: 0 0 0 4px rgba(20,184,166,.2); animation: dot-pulse 1.5s ease-in-out infinite; }
.proc-phase--active .proc-phase__label { color: var(--c-primary); }
@keyframes dot-pulse { 0%, 100% { box-shadow: 0 0 0 4px rgba(20,184,166,.2); } 50% { box-shadow: 0 0 0 8px rgba(20,184,166,.08); } }
.proc-phase__connector { width: 24px; height: 2px; background: #e2e8f0; border-radius: 1px; }

/* 过渡动画 */
.proc-fade-enter-active, .proc-fade-leave-active { transition: opacity .3s ease; }
.proc-fade-enter-from, .proc-fade-leave-to { opacity: 0; }

@media (max-width: 600px) {
  .proc-card { flex-direction: column; gap: 28px; padding: 32px 24px; min-width: auto; width: calc(100vw - 48px); }
  .proc-visual { width: 100px; height: 120px; }
}

/* ═══════════════ 结果弹窗 ═══════════════ */
.result-overlay {
  position: fixed; inset: 0; z-index: 2100;
  background: rgba(15, 23, 42, .4);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.result-dialog {
  position: relative; width: 420px; max-width: 94vw;
  background: #fff; border-radius: 20px; overflow: hidden;
  box-shadow: 0 32px 80px rgba(15, 23, 42, .18);
}
.result-accent {
  height: 5px;
  background: linear-gradient(90deg, #14b8a6, #2dd4bf, #14b8a6);
  background-size: 200% 100%;
  animation: accent-flow 3s linear infinite;
}
@keyframes accent-flow { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }

.result-close {
  position: absolute; top: 14px; right: 14px;
  width: 32px; height: 32px; border: none; border-radius: 8px;
  background: #f8fafc; color: var(--c-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.result-close:hover { background: #f1f5f9; color: var(--c-text); transform: rotate(90deg); }

.result-body { padding: 36px 32px 32px; text-align: center; }

/* 成功图标 */
.result-icon {
  width: 64px; height: 64px; margin: 0 auto 18px; color: var(--c-green);
}
.result-icon__circle {
  stroke-dasharray: 151; stroke-dashoffset: 151;
  animation: icon-circle-draw .6s ease forwards;
}
.result-icon__check {
  stroke-dasharray: 36; stroke-dashoffset: 36;
  animation: icon-check-draw .35s ease .5s forwards;
}
@keyframes icon-circle-draw { to { stroke-dashoffset: 0; } }
@keyframes icon-check-draw { to { stroke-dashoffset: 0; } }

.result-title { font-size: 22px; font-weight: 800; color: var(--c-text); margin-bottom: 6px; }
.result-desc { font-size: 14px; color: var(--c-muted); line-height: 1.6; margin-bottom: 22px; }

/* 信息卡片:2x2 网格布局,订单号独占一行 */
.result-meta {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
  margin-bottom: 24px;
  background: #f8fafc; border-radius: 12px; padding: 14px;
}
.result-meta__item {
  display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
  background: #fff; border-radius: 8px; padding: 10px 12px;
  min-width: 0;
}
.result-meta__item--full { grid-column: 1 / -1; }
.result-meta__label { font-size: 11px; color: var(--c-muted); font-weight: 600; letter-spacing: .3px; }
.result-meta__value {
  font-size: 14px; color: var(--c-text); font-weight: 700;
  word-break: break-all; line-height: 1.4;
}

/* 操作按钮 */
.result-actions { display: flex; flex-direction: column; gap: 10px; }
.result-download {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 14px 24px; border-radius: 12px; text-decoration: none;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff; font-size: 15px; font-weight: 700;
  box-shadow: 0 8px 24px rgba(20, 184, 166, .3);
  transition: all .25s;
}
.result-download:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(20, 184, 166, .4); }
.result-download:active { transform: translateY(0); }
/* 预览按钮:蓝色调与主下载按钮区分 */
.result-preview-btn {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  box-shadow: 0 8px 24px rgba(59, 130, 246, .3);
}
.result-preview-btn:hover { box-shadow: 0 12px 32px rgba(59, 130, 246, .4); }
.result-secondary {
  padding: 10px 24px; border: none; border-radius: 10px;
  background: transparent; color: var(--c-muted); font-size: 13px; font-weight: 600;
  cursor: pointer; transition: color .2s;
}
.result-secondary:hover { color: var(--c-text); }

/* 结果弹窗过渡 */
.result-pop-enter-active { transition: all .4s cubic-bezier(.34, 1.56, .64, 1); }
.result-pop-leave-active { transition: all .25s ease; }
.result-pop-enter-from { opacity: 0; transform: scale(.9) translateY(20px); }
.result-pop-leave-to { opacity: 0; transform: scale(.95); }

/* ============ 收费标准按钮 ============ */
.price-standard-btn {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--c-text2);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s;
}
.price-standard-btn:hover {
  color: var(--c-primary);
  border-color: var(--c-primary);
  background: rgba(20, 184, 166, .06);
  transform: translateY(-50%) translateY(-1px);
}
.price-standard-btn svg { color: var(--c-primary); }

/* ============ 顶部模式切换按钮 ============ */
.mode-switch {
  position: absolute;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 600;
  color: var(--c-text2);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  cursor: pointer;
  transition: all .2s;
}
.mode-switch svg { color: var(--c-primary); }
.mode-switch:hover {
  color: var(--c-primary);
  border-color: var(--c-primary);
  background: rgba(20, 184, 166, .06);
  transform: translateY(-50%) translateY(-1px);
}
.mode-switch--active {
  background: linear-gradient(135deg, rgba(20,184,166,.1), rgba(13,148,136,.08));
  border-color: rgba(20, 184, 166, .4);
  color: var(--c-primary);
}
</style>

<!-- 收费标准弹窗样式（非 scoped，因为 Teleport 到 body） -->
<style>
.price-modal-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}

.price-modal {
  width: 100%;
  max-width: 560px;
  max-height: 85vh;
  background: #fff;
  border-radius: 18px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2);
}

.price-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.price-modal-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.price-modal-title svg { color: #14b8a6; }

.price-modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.price-modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.price-modal-body {
  padding: 20px 22px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.price-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
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
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
}

.price-section-icon.green {
  background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

.price-section-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  flex: 1;
}

.price-section-tag {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 999px;
}

/* 价格表 */
.price-table {
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  overflow: hidden;
}

.price-table-autodoc .price-table-head,
.price-table-autodoc .price-table-row {
  grid-template-columns: 1.4fr 1fr 1fr;
}

.price-table-head,
.price-table-row {
  display: grid;
  padding: 10px 14px;
  align-items: center;
}

.price-table-head {
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.price-table-row {
  border-top: 1px solid #f1f5f9;
  font-size: 13px;
  transition: background 0.15s;
}

.price-table-row:hover { background: rgba(20, 184, 166, .04); }

.price-col-name {
  color: #0f172a;
  font-weight: 600;
}

.price-col-mode {
  color: #475569;
  font-weight: 500;
}

.price-col-price {
  color: #14b8a6;
  font-weight: 800;
  font-size: 14px;
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
  padding: 10px 12px;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  transition: all 0.15s;
}

.price-grid-item:hover {
  border-color: #14b8a6;
  background: rgba(20, 184, 166, .04);
}

.grid-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.grid-item-price {
  font-size: 14px;
  font-weight: 800;
  color: #14b8a6;
}

.grid-item-price.free {
  color: #10b981;
  font-size: 12px;
}

/* 说明 */
.price-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.6;
}

.price-note svg {
  color: #94a3b8;
  flex-shrink: 0;
  margin-top: 1px;
}

/* 后付费模式高亮提示（琥珀金配色，与 teal 主题形成强对比） */
.price-highlight {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
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

  .price-modal-head { padding: 14px 16px; }

  .price-modal-body {
    padding: 14px 16px;
    gap: 16px;
  }

  .price-grid-2 { grid-template-columns: 1fr; }

  .price-table-head,
  .price-table-row {
    padding: 9px 8px;
    font-size: 12px;
  }

  .price-col-price { font-size: 13px; }
}

/* ============ 初始模式选择弹窗（fixed 定位，只覆盖可视区域，不遮顶部菜单栏） ============
   说明：使用 fixed 相对视口（iframe 内即相对 iframe 可视区），顶部留 8vh 间距靠上展示，
   避免弹窗在页面内容高于视口时被压到下方（之前 absolute 定位按整页高度居中会偏下）。
   非嵌入模式偏移出顶部 64px 菜单栏与左侧 248px 侧边栏；嵌入模式（分站 iframe）无顶栏/侧栏，铺满 iframe。 */
.mode-mask {
  position: fixed;
  inset: 0;
  top: 64px;
  left: 248px;
  background: rgba(248, 250, 252, 0.82);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  z-index: 100;
  padding: 8vh 24px 24px;
  border-radius: 0;
}

/* 嵌入模式（分站工作台 iframe 内嵌 ?embed=1，页面根节点带 .autodoc-page--embed）：
   嵌入时 iframe 上方已有分站顶栏，100vh 即 iframe 可视高度，遮罩铺满 iframe 即可（天然把顶部顶栏排除在外） */
.autodoc-page--embed .mode-mask {
  top: 0;
  left: 0;
}

.mode-modal {
  position: relative;
  width: 100%;
  max-width: 700px;
  max-height: 90vh;
  background: #fff;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 32px 90px rgba(15, 23, 42, 0.28);
  border: 1px solid rgba(226, 232, 240, 0.9);
}

.mode-head {
  position: relative;
  padding: 30px 28px 20px;
  text-align: center;
  overflow: hidden;
  background:
    radial-gradient(circle at 18% 0%, rgba(20, 184, 166, 0.14), transparent 55%),
    radial-gradient(circle at 82% 0%, rgba(99, 102, 241, 0.12), transparent 55%),
    linear-gradient(180deg, #f8fafc 0%, #fff 100%);
  border-bottom: 1px solid #f1f5f9;
}
.mode-head__badge {
  position: relative;
  width: 52px;
  height: 52px;
  margin: 0 auto 14px;
  border-radius: 16px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 24px rgba(20, 184, 166, 0.32);
}
.mode-head__badge::after {
  content: '';
  position: absolute;
  inset: -5px;
  border-radius: 20px;
  border: 1.5px dashed rgba(20, 184, 166, 0.35);
}
.mode-head__title {
  margin: 0 0 8px;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 0.5px;
}
.mode-head__title em {
  font-style: normal;
  background: linear-gradient(120deg, #0d9488, #0f766e);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.mode-head__sub {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.mode-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  padding: 26px 28px 22px;
  overflow-y: auto;
}

.mode-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 9px;
  padding: 20px 20px 16px;
  border-radius: 18px;
  cursor: pointer;
  transition: box-shadow 0.2s ease;
}
.mode-card--polish {
  border: 1.5px solid rgba(20, 184, 166, 0.45);
  background:
    radial-gradient(circle at 100% 0%, rgba(20, 184, 166, 0.1), transparent 55%),
    linear-gradient(180deg, #fbfdfd 0%, #ffffff 100%);
  box-shadow: 0 8px 24px rgba(20, 184, 166, 0.08);
}
.mode-card--retype {
  border: 1.5px solid rgba(99, 102, 241, 0.45);
  background:
    radial-gradient(circle at 100% 0%, rgba(99, 102, 241, 0.1), transparent 55%),
    linear-gradient(180deg, #fcfcff 0%, #ffffff 100%);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.08);
}
.mode-card--polish:hover { box-shadow: 0 12px 30px rgba(20, 184, 166, 0.16); }
.mode-card--retype:hover { box-shadow: 0 12px 30px rgba(99, 102, 241, 0.16); }
.mode-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mode-card__icon {
  width: 46px;
  height: 46px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.mode-card--polish .mode-card__icon {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 6px 14px rgba(20, 184, 166, 0.28);
}
.mode-card--retype .mode-card__icon {
  background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
  box-shadow: 0 6px 14px rgba(99, 102, 241, 0.28);
}
.mode-card__tag {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  padding: 3px 10px;
  border-radius: 999px;
  white-space: nowrap;
}
.mode-card--polish .mode-card__tag {
  background: rgba(20, 184, 166, 0.12);
  color: #0d9488;
}
.mode-card--retype .mode-card__tag {
  background: rgba(99, 102, 241, 0.12);
  color: #4f46e5;
}
.mode-card__name {
  font-size: 19px;
  font-weight: 800;
  color: #0f172a;
}
.mode-card__desc {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.7;
  color: #64748b;
  /* 两张卡片内容等高，保证两卡内容与按钮在同一水平线 */
  min-height: 64px;
}
.mode-card__points {
  list-style: none;
  margin: 4px 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.mode-card__points li {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  color: #334155;
}
.mode-card__points li svg {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
  padding: 1px;
  border-radius: 50%;
}
.mode-card--polish .mode-card__points li svg {
  background: rgba(20, 184, 166, 0.12);
  color: #0d9488;
}
.mode-card--retype .mode-card__points li svg {
  background: rgba(99, 102, 241, 0.12);
  color: #4f46e5;
}
.mode-card__go {
  /* margin-top:auto 使按钮吸附到卡片底部，两卡等高时按钮在同一水平线 */
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 0;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  transition: filter 0.2s ease;
}
.mode-card--polish .mode-card__go {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.28);
}
.mode-card--retype .mode-card__go {
  background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
  box-shadow: 0 6px 16px rgba(99, 102, 241, 0.28);
}
.mode-card--polish:hover .mode-card__go { filter: brightness(1.08); }
.mode-card--retype:hover .mode-card__go { filter: brightness(1.08); }

.mode-foot {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px 28px 18px;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}
.mode-foot__tip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #94a3b8;
}
.mode-foot__tip::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15);
}

/* 弹窗过渡动画 */
.mode-fade-enter-active,
.mode-fade-leave-active {
  transition: opacity 0.22s ease;
}
.mode-fade-enter-active .mode-modal,
.mode-fade-leave-active .mode-modal {
  transition: transform 0.28s ease;
}
.mode-fade-enter-from,
.mode-fade-leave-to {
  opacity: 0;
}
.mode-fade-enter-from .mode-modal,
.mode-fade-leave-to .mode-modal {
  transform: scale(0.94) translateY(14px);
}

/* 移动端适配：Material Design 3 Modal Bottom Sheet 规范实现
   （m3.material.io/components/bottom-sheets：顶部圆角 28px、拖拽把手 32×4、
   scrim 40% 纯黑、内容 padding 24/16、层次靠 scrim 不靠彩色阴影） */
@media (max-width: 640px) {
  /* Scrim：M3 规范 40% 黑，纯色遮罩不加毛玻璃 */
  .mode-mask {
    align-items: flex-end;
    padding: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
  }

  /* Sheet：全宽贴底、顶部圆角 28px、无描边无投影（M3 elevation 1dp，scrim 承担层次） */
  .mode-modal {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 28px 28px 0 0;
    border: none;
    box-shadow: none;
    background: #fff;
  }

  /* 拖拽把手：32×4 pill 居中（M3 drag handle，on-surface-variant 灰） */
  .mode-modal::before {
    content: '';
    display: block;
    width: 32px;
    height: 4px;
    border-radius: 999px;
    background: #c4c7c5;
    margin: 18px auto 6px;
    flex-shrink: 0;
  }

  /* Headline：左对齐标题行，icon 以 24px 线性图形呈现（M3 leading icon），去徽章容器 */
  .mode-head {
    padding: 2px 24px 10px;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border-bottom: none;
  }
  .mode-head__badge {
    width: 24px;
    height: 24px;
    margin: 0;
    border-radius: 0;
    background: none;
    box-shadow: none;
    flex-shrink: 0;
  }
  .mode-head__badge::after { display: none; }
  .mode-head__badge svg { width: 24px; height: 24px; }
  .mode-head__title {
    margin: 0 0 1px;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0;
  }
  .mode-head__sub { font-size: 12.5px; }

  /* 内容区：M3 padding 24 水平 / 16 垂直，列表行紧凑排列 */
  .mode-body {
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 10px 24px 16px;
  }

  /* 选项行：去渐变边框与彩色阴影，中性浅底圆角行（M3 tonal list row）；
     整行点击即选择（模板 click 绑定在行上），不再内嵌按钮 */
  .mode-card {
    gap: 6px;
    padding: 14px 16px;
    border-radius: 14px;
    border: none;
    box-shadow: none;
  }
  .mode-card--polish {
    background: #f1f7f6;
  }
  .mode-card--retype {
    background: #f2f3fb;
  }
  .mode-card--polish:hover,
  .mode-card--retype:hover { box-shadow: none; }
  .mode-card__icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
  }
  .mode-card__icon svg { width: 19px; height: 19px; }
  .mode-card__name { font-size: 15px; }
  .mode-card__desc { min-height: 0; font-size: 12.5px; line-height: 1.65; }
  .mode-card__points { gap: 5px; margin-top: 2px; }
  .mode-card__points li { font-size: 12px; gap: 6px; }
  .mode-card__points li svg { width: 14px; height: 14px; }
  .mode-card__go { display: none; }

  /* 底部提示：居中弱化 + 底部安全区（M3 sheet 不设 footer 按钮） */
  .mode-foot {
    padding: 8px 24px calc(14px + env(safe-area-inset-bottom, 0px));
    border-top: none;
    background: #fff;
  }
  .mode-foot__tip { font-size: 11.5px; }

  /* 进入动画：从底部滑入（M3 modal sheet 惯例） */
  .mode-fade-enter-active .mode-modal,
  .mode-fade-leave-active .mode-modal {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0.32, 1);
  }
  .mode-fade-enter-from .mode-modal,
  .mode-fade-leave-to .mode-modal {
    transform: translateY(100%);
  }
}

/* 小屏（与 console 布局断点一致 ≤900px）：顶栏高 56px、无侧边栏，遮罩对应调整 */
@media (max-width: 900px) {
  .mode-mask { top: 56px; left: 0; }
}

/* ≤640 顶栏进一步降为 52px（console H5 topbar / m-header 同高），遮罩随之（须在 ≤900 块之后覆盖） */
@media (max-width: 640px) {
  .mode-mask { top: 52px; }
}
</style>
