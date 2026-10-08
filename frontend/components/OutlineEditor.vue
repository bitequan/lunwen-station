<template>
  <div class="outline-page">
    <main class="outline-main">
      <div class="container outline-body">
        <div class="outline-content">
          <transition name="gen-switch">
            <div v-if="generating" key="gen" class="outline-card streaming-mode" ref="streamBody">
              <div class="card-head">
                <div class="head-title">
                  <span class="head-icon stream-orb-wrap">
                    <span class="stream-orb-mini">
                      <span class="orb-core"></span>
                      <span class="orb-ring"></span>
                    </span>
                  </span>
                  <h2>AI 正在生成大纲<span class="stream-badge">实时</span></h2>
                </div>
                <div class="head-actions">
                  <div class="stream-wave">
                    <span></span><span></span><span></span><span></span><span></span>
                  </div>
                </div>
              </div>

              <div class="outline-tree streaming" v-if="streamOutline.length">
                <div
                  v-for="(chapter, cIdx) in streamOutline"
                  :key="cIdx"
                  class="chapter stream-fade-in"
                >
                  <div class="chapter-title">
                    <span class="stream-chapter-num">{{ cIdx + 1 }}</span>
                    <h3>{{ chapter.title }}</h3>
                  </div>
                  <div v-if="chapter.summary" class="abstract stream-fade-in stream-abstract">{{ chapter.summary }}</div>

                  <div v-if="chapter.sections && chapter.sections.length" class="sections">
                    <div
                      v-for="(section, sIdx) in chapter.sections"
                      :key="sIdx"
                      class="section stream-fade-in"
                      :class="{ 'has-sub': section.subSections && section.subSections.length }"
                    >
                      <div class="section-main">
                        <span class="stream-section-dot"></span>
                        <span>{{ section.title }}</span>
                      </div>
                      <div v-if="section.summary" class="abstract stream-fade-in stream-abstract">{{ section.summary }}</div>

                      <div v-if="section.subSections && section.subSections.length" class="subsections">
                        <div
                          v-for="(sub, ssIdx) in section.subSections"
                          :key="ssIdx"
                          class="subsection stream-fade-in"
                        >
                          <div class="subsection-main">
                            <span class="stream-sub-dot"></span>
                            <span>{{ sub.title }}</span>
                          </div>
                          <div v-if="sub.summary" class="abstract stream-fade-in stream-abstract">{{ sub.summary }}</div>
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

            <div v-else key="outline" class="outline-card">
            <div class="card-head">
              <div class="head-title">
                <span class="head-icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                </span>
                <h2>生成的大纲</h2>
              </div>
              <div class="head-actions">
                <button class="action-btn ghost" @click="copyOutline">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                  复制大纲
                </button>
                <button class="action-btn ghost" @click="regenerate">
                  <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                  重新生成
                </button>
                <button class="action-btn" :class="{ primary: sortMode }" @click="sortMode = !sortMode">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                  {{ sortMode ? '完成排序' : '调整排序' }}
                </button>
              </div>
            </div>

            <div class="outline-tree" :class="{ 'sort-mode': sortMode }">
              <div
                v-for="(chapter, cIdx) in outline"
                :key="cIdx"
                class="chapter"
              >
                <div class="chapter-title">
                  <span class="sort-grip" v-if="sortMode" title="拖动排序">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                  </span>
                  <h3 class="editable"
                    :data-number="chapter.number"
                    :contenteditable="!sortMode"
                    @input="e => chapter.title = e.target.innerText"
                    @keydown.enter.prevent
                  >{{ chapter.title }}</h3>
                  <div class="ops-group" v-if="!sortMode">
                    <button class="op-btn op-add" @click="addSiblingChapter(cIdx)" title="添加同级章节">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <button class="op-btn op-add" @click="addSection(cIdx)" title="添加小节">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </button>
                    <button class="op-btn op-danger" @click="removeChapter(cIdx)" title="删除章节">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </div>
                </div>
                <div
                  v-if="!chapter.sections || !chapter.sections.length"
                  class="editable abstract"
                  :contenteditable="!sortMode"
                  data-placeholder="点击添加章节概要..."
                  @input="e => chapter.summary = e.target.innerText"
                >{{ chapter.summary }}</div>

                <div class="sections" :data-c-idx="cIdx">
                  <div v-for="(section, sIdx) in chapter.sections" :key="sIdx" class="section" :class="{ 'has-sub': section.subSections }">
                      <div class="section-main">
                        <span class="sort-grip" v-if="sortMode" title="拖动排序">
                          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                        </span>
                        <span class="editable"
                          :data-number="section.number"
                          :contenteditable="!sortMode"
                          @input="e => section.title = e.target.innerText"
                          @keydown.enter.prevent
                        >{{ section.title }}</span>

                        <template v-if="!sortMode && !section.subSections && !isChartMenuOpen(cIdx, sIdx)">
                          <span
                            v-for="(item, i) in chartsFor(cIdx, sIdx)"
                            :key="item.label"
                            class="chart-badge"
                            :style="{ '--c': item.color }"
                          >
                            {{ item.label }}
                            <button class="badge-x" @click.stop="removeChartAt(cIdx, sIdx, undefined, i)">×</button>
                          </span>
                        </template>

                        <div class="ops-group" v-if="!sortMode && !isChartMenuOpen(cIdx, sIdx)">
                          <button v-if="!section.subSections" class="op-btn op-chart" @click.stop="activeChartMenu = { cIdx, sIdx }" title="添加图表">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                          </button>
                          <button class="op-btn op-add" @click="addSiblingSection(cIdx, sIdx)" title="添加同级小节">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                          </button>
                          <button class="op-btn op-add" @click="addSubSection(cIdx, sIdx)" title="添加三级小节">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                          </button>
                          <button class="op-btn op-danger" @click="removeSection(cIdx, sIdx)" title="删除小节">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                          </button>
                        </div>
                        <div
                          v-if="!sortMode && !section.subSections && isChartMenuOpen(cIdx, sIdx)"
                          class="chart-menu"
                          @click.stop
                        >
                          <button
                            v-for="t in visibleChartTypeOptions"
                            :key="t.value"
                            class="menu-item"
                            :class="{ active: selectedChartType(cIdx, sIdx) === t.value }"
                            :style="{ '--c': t.color }"
                            @click="toggleChart(t.value, cIdx, sIdx)"
                          >
                            <span class="item-dot" :style="{ background: t.color }"></span>
                            <span class="item-label">{{ t.label }}</span>
                            <svg v-if="selectedChartType(cIdx, sIdx) === t.value" class="item-check" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                          </button>
                        </div>
                      </div>

                      <div
                        v-if="!section.subSections"
                        class="editable abstract"
                        :contenteditable="!sortMode"
                        data-placeholder="点击添加写作概要..."
                        @input="e => section.summary = e.target.innerText"
                      >{{ section.summary }}</div>

                      <div class="subsections" :data-c-idx="cIdx" :data-s-idx="sIdx">
                        <div v-for="(sub, ssIdx) in (section.subSections || [])" :key="ssIdx" class="subsection">
                          <div class="subsection-main">
                            <span class="sort-grip" v-if="sortMode" title="拖动排序">
                              <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                            </span>
                            <span class="editable"
                              :data-number="sub.number"
                              :contenteditable="!sortMode"
                              @input="e => sub.title = e.target.innerText"
                              @keydown.enter.prevent
                            >{{ sub.title }}</span>

                            <template v-if="!sortMode && !isChartMenuOpen(cIdx, sIdx, ssIdx)">
                              <span
                                v-for="(item, i) in chartsFor(cIdx, sIdx, ssIdx)"
                                :key="item.label"
                                class="chart-badge"
                                :style="{ '--c': item.color }"
                              >
                                {{ item.label }}
                                <button class="badge-x" @click.stop="removeChartAt(cIdx, sIdx, ssIdx, i)">×</button>
                              </span>
                            </template>

                            <div class="ops-group" v-if="!sortMode && !isChartMenuOpen(cIdx, sIdx, ssIdx)">
                              <button class="op-btn op-chart" @click.stop="activeChartMenu = { cIdx, sIdx, ssIdx }" title="添加图表">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                              </button>
                              <button class="op-btn op-add" @click="addSiblingSubSection(cIdx, sIdx, ssIdx)" title="添加同级">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                              </button>
                              <button class="op-btn op-danger" @click="removeSubSection(cIdx, sIdx, ssIdx)" title="删除三级小节">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                              </button>
                            </div>
                            <div
                              v-if="!sortMode && isChartMenuOpen(cIdx, sIdx, ssIdx)"
                              class="chart-menu"
                              @click.stop
                            >
                              <button
                                v-for="t in visibleChartTypeOptions"
                                :key="t.value"
                                class="menu-item"
                                :class="{ active: selectedChartType(cIdx, sIdx, ssIdx) === t.value }"
                                :style="{ '--c': t.color }"
                                @click="toggleChart(t.value, cIdx, sIdx, ssIdx)"
                              >
                                <span class="item-dot" :style="{ background: t.color }"></span>
                                <span class="item-label">{{ t.label }}</span>
                                <svg v-if="selectedChartType(cIdx, sIdx, ssIdx) === t.value" class="item-check" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                              </button>
                            </div>
                          </div>
                          <div
                            class="editable abstract"
                            :contenteditable="!sortMode"
                            data-placeholder="点击添加写作概要..."
                            @input="e => sub.summary = e.target.innerText"
                          >{{ sub.summary }}</div>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
          </transition>
        </div>

        <aside class="outline-sidebar" v-show="!generating">
          <div class="sidebar-card params-card">
            <h3>
              <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84a.484.484 0 0 0-.48.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.488.488 0 0 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.63-.07.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.27.41.48.41h3.84c.24 0 .44-.17.48-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
              论文参数
            </h3>
            <div class="param-list">
              <div v-for="(p, i) in params" :key="i" class="param-item">
                <span class="param-label">{{ p.label }}</span>
                <span class="param-value">{{ p.value }}</span>
              </div>
            </div>
          </div>

          <div class="sidebar-card chart-card">
            <h3>
              <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M5 3h14c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2zm0 4v4h14V7H5zm0 6v4h14v-4H5z"/></svg>
              图表与公式
            </h3>

            <div class="chart-block">
              <span class="block-label">自动分配包含的类型</span>
              <div class="chart-types">
                <label
                  v-for="t in visibleChartTypeOptions"
                  :key="t.value"
                  class="chart-type"
                  :class="{ active: selectedChartTypes.includes(t.value) }"
                  :style="{ '--c': t.color }"
                >
                  <input v-model="selectedChartTypes" type="checkbox" :value="t.value" />
                  <span class="ctype-dot" :style="{ background: t.color }"></span>
                  <span>{{ t.label }}</span>
                </label>
              </div>
            </div>

            <button class="btn btn-secondary btn-block" @click="autoAddCharts">
              <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
              自动分配到未选图表的小节
            </button>
          </div>

          <div class="sidebar-card action-card">
            <div class="action-btn-group">
              <button class="btn btn-side-secondary" @click="goBack">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                上一步
              </button>
              <button class="btn btn-side-primary btn-block" @click="generateFull">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                生成全文
              </button>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <!-- 订单确认弹窗（余额支付） -->
    <Teleport to="body">
      <transition name="order-modal">
        <div v-if="showOrderModal" class="order-mask" @click.self="closeOrderModal">
          <div class="order-modal">
            <!-- 头部 -->
            <div class="om-header">
              <div class="om-title-row">
                <div class="om-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2L9.91 8.84 3 9.27l5.46 4.73L6.82 21 12 17.27 17.18 21l-1.64-7 5.46-4.73-6.91-.43L12 2z"/></svg>
                </div>
                <h3 class="om-title">{{ orderPaid ? '支付成功' : '确认订单信息' }}</h3>
              </div>
              <button v-if="!orderPaid" class="om-close" @click="closeOrderModal" :disabled="orderPaying">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>

            <!-- 内容 -->
            <div class="om-body">
              <!-- 支付成功状态 -->
              <div v-if="orderPaid" class="om-success">
                <div class="om-success-icon">
                  <svg viewBox="0 0 24 24" width="36" height="36"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <p class="om-success-text">{{ orderResult?.pay_way === 'package' ? '已使用套餐额度 1 篇' : `已成功扣款 ¥${orderResult?.order_amount}` }}</p>
                <p class="om-success-sub">订单号：{{ orderResult?.order_sn }}</p>
                <p v-if="orderResult?.pay_way === 'package'" class="om-success-bal">{{ currentModelAdvanced ? '高级模型' : '普通模型' }}套餐额度剩余：{{ orderResult?.left_quota ?? packageQuota }} 篇</p>
                <p v-else class="om-success-bal">账户余额：¥{{ Number(userBalance).toFixed(2) }}</p>
                <p class="om-success-count">将在 <b>{{ finishCountdown }}</b> 秒后自动返回首页</p>
              </div>

              <!-- 订单详情 -->
              <template v-else>
                <div class="om-section">
                  <div class="om-section-title">订单信息</div>
                  <div class="om-info-grid">
                    <div class="om-cell om-cell-wide">
                      <span class="om-cell-label">论文标题</span>
                      <span class="om-cell-value om-cell-title">{{ quickForm.title || paperInfo.title }}</span>
                    </div>
                    <div class="om-cell">
                      <span class="om-cell-label">字数要求</span>
                      <span class="om-cell-value">{{ quickForm.customWords || quickForm.words }} 字</span>
                    </div>
                    <div class="om-cell">
                      <span class="om-cell-label">写作语言</span>
                      <span class="om-cell-value">{{ quickForm.language }}</span>
                    </div>
                    <div class="om-cell">
                      <span class="om-cell-label">生成模型</span>
                      <span class="om-cell-value">{{ getModelName(quickForm.model) || '标准模型' }}</span>
                    </div>
                    <div class="om-cell">
                      <span class="om-cell-label">格式模板</span>
                      <span class="om-cell-value">{{ quickForm.templateName || '默认' }}</span>
                    </div>
                  </div>
                </div>

                <div class="om-section">
                  <div class="om-section-title om-title-with-action">
                    <span>金额明细</span>
                    <button class="om-price-btn" @click="showPriceModal = true">
                      <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
                      价格说明
                    </button>
                  </div>
                  <div class="om-amount-list">
                    <div class="om-amount-row">
                      <span class="om-amount-label">论文生成（{{ getModelName(quickForm.model) }}）</span>
                      <span class="om-amount-value">¥{{ basePrice.toFixed(2) }}</span>
                    </div>
                    <div class="om-amount-row" v-if="quickForm.needLowerAI">
                      <span class="om-amount-label">降低 AI 率（+30%）</span>
                      <span class="om-amount-value">¥{{ lowerAiFee.toFixed(2) }}</span>
                    </div>
                    <div class="om-amount-row om-amount-total">
                      <span class="om-amount-label">应付总额</span>
                      <span class="om-amount-value om-amount-price">¥{{ orderAmount.toFixed(2) }}</span>
                    </div>
                  </div>
                </div>

                <div class="om-section">
                  <div class="om-section-title">支付方式</div>
                  <div class="om-pay-options">
                    <!-- 余额支付 -->
                    <div
                      class="om-pay-opt"
                      :class="{ active: payWay === 'balance', disabled: !balanceEnough }"
                      @click="payWay = 'balance'"
                    >
                      <span class="om-pay-radio"></span>
                      <div class="om-pay-info">
                        <span class="om-pay-name">余额支付</span>
                        <span class="om-pay-desc">账户余额 ¥{{ Number(userBalance).toFixed(2) }}</span>
                      </div>
                      <span v-if="!balanceEnough" class="om-pay-tip">余额不足</span>
                    </div>
                    <!-- 套餐额度支付（按当前模型类型显示对应额度） -->
                    <div
                      class="om-pay-opt"
                      :class="{ active: payWay === 'package', disabled: !packageEnough }"
                      @click="payWay = 'package'"
                    >
                      <span class="om-pay-radio"></span>
                      <div class="om-pay-info">
                        <span class="om-pay-name">{{ currentModelAdvanced ? '高级模型' : '普通模型' }}套餐额度</span>
                        <span class="om-pay-desc">剩余 {{ packageQuota }} 篇</span>
                      </div>
                      <span v-if="!packageEnough" class="om-pay-tip">额度不足</span>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- 底部按钮 -->
            <div class="om-footer">
              <template v-if="orderPaid">
                <button class="om-btn om-btn-primary" @click="finishOrder">立即返回首页</button>
              </template>
              <template v-else>
                <button class="om-btn om-btn-ghost" @click="closeOrderModal" :disabled="orderPaying">取消</button>
                <button
                  class="om-btn om-btn-primary"
                  :class="{ loading: orderPaying }"
                  :disabled="orderPaying || !payWayEnough"
                  @click="confirmPay"
                >
                  <span v-if="orderPaying" class="om-spinner"></span>
                  {{ orderPaying ? '支付中…' : (payWay === 'package' ? '确认支付（使用套餐额度）' : `确认支付 ¥${orderAmount.toFixed(2)}`) }}
                </button>
              </template>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- 价格说明弹窗（参考 create 页收费标准） -->
    <Teleport to="body">
      <Transition name="price-modal">
        <div v-if="showPriceModal" class="om-price-mask" @click.self="showPriceModal = false">
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
                  <span v-for="col in priceData.modelColumns" :key="col.code" class="legend-item">
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
  </div>
</template>

<script setup>
import Sortable from 'sortablejs'

const props = defineProps({
  formData: { type: Object, default: () => ({}) },
  active: { type: Boolean, default: false },
  restoreOutlineNo: { type: String, default: '' },
})

const emit = defineEmits(['back', 'paid', 'reset'])

const paperInfo = ref({
  title: '论文大纲'
})

const quickForm = ref({
  title: '',
  subject: '',
  major: '',
  degree: '本科',
  words: '1万',
  customWords: '',
  model: 'standard',
  outlineLevel: 'two',
  language: '中文',
  needLowerAI: false,
  useCustomOutline: false,
  customOutline: '',
  templateId: 0,
  templateName: '',
  templateSource: '',
  enLiteratureCount: 0,
})

// 从父组件 props 同步表单数据
watch(() => props.formData, (val) => {
  if (val && Object.keys(val).length) {
    quickForm.value = { ...quickForm.value, ...val }
    if (quickForm.value.title) paperInfo.value.title = quickForm.value.title
  }
}, { immediate: true, deep: true })

// 论文参数：从 quickForm 实时派生，不再使用硬编码 mock 数据
const params = computed(() => {
  const outlineLevelMap = { two: '二级大纲', three: '三级大纲' }
  const words = quickForm.value.customWords || quickForm.value.words || ''
  return [
    { label: '论文标题', value: quickForm.value.title || '—' },
    { label: '学科门类', value: quickForm.value.subject || '—' },
    { label: '具体专业', value: quickForm.value.major || '—' },
    { label: '学历层次', value: quickForm.value.degree || '本科' },
    { label: '字数要求', value: words ? words + '字' : '—' },
    { label: '大纲级别', value: outlineLevelMap[quickForm.value.outlineLevel] || '二级大纲' },
    { label: '生成模型', value: getModelName(quickForm.value.model) || '标准模型' },
    { label: '写作语言', value: quickForm.value.language || '中文' },
  ]
})

const outline = ref([
  {
    number: '一',
    title: '绪论',
    summary: '介绍研究背景与问题由来，梳理国内外相关研究进展，明确研究内容、方法及论文整体结构。',
    sections: [
      {
        number: '1.1',
        title: '研究背景与意义',
        subSections: [
          { number: '1.1.1', title: '高等教育数字化转型趋势', summary: '分析大数据、人工智能等技术推动教育变革的时代背景。' },
          { number: '1.1.2', title: '个性化学习需求凸显', summary: '说明统一化教学模式难以满足学生差异化发展的现实困境。' },
        ]
      },
      { number: '1.2', title: '国内外研究现状', summary: '综述人工智能教育应用、个性化学习系统和推荐算法的研究进展与不足。' },
      { number: '1.3', title: '研究内容与方法', summary: '阐明本研究要解决的核心问题、技术路线和实验设计思路。' },
      { number: '1.4', title: '论文组织结构', summary: '简要说明各章节的主要内容与逻辑关系。' },
    ]
  },
  {
    number: '二',
    title: '相关技术理论基础',
    summary: '梳理人工智能、个性化学习、教育数据挖掘和推荐系统的基础理论与关键技术。',
    sections: [
      { number: '2.1', title: '人工智能概述', summary: '介绍机器学习、深度学习等核心概念及其在教育中的应用潜力。' },
      { number: '2.2', title: '个性化学习理论', summary: '阐述学习者差异、自适应学习路径与知识追踪的相关理论。' },
      { number: '2.3', title: '教育数据挖掘技术', summary: '说明学习行为数据采集、特征提取与常用分析方法。' },
      { number: '2.4', title: '推荐系统在教育中的应用', summary: '分析协同过滤、内容推荐等算法在学习资源推荐中的适用性。' },
    ]
  },
  {
    number: '三',
    title: '高等教育个性化学习需求分析',
    summary: '通过文献与调研分析高等教育面临的挑战、学习者特征及个性化学习需求。',
    sections: [
      { number: '3.1', title: '当前高等教育面临的挑战', summary: '分析教学资源不均、学生学习效果差异大等现实问题。' },
      {
        number: '3.2',
        title: '学习者个性化特征分析',
        subSections: [
          { number: '3.2.1', title: '学习风格差异', summary: '介绍视觉型、听觉型、动手型等学习风格分类及其教学启示。' },
          { number: '3.2.2', title: '知识基础与能力水平', summary: '讨论先验知识、认知能力对个性化学习路径设计的影响。' },
        ]
      },
      { number: '3.3', title: '个性化学习需求调研设计', summary: '说明问卷设计、样本选择及数据收集过程。' },
      { number: '3.4', title: '需求分析结果与讨论', summary: '呈现调研结果并归纳个性化学习系统的功能需求。' },
    ]
  },
  {
    number: '四',
    title: '基于 AI 的个性化学习系统设计',
    summary: '设计系统总体架构、学习者画像、推荐算法与学习路径规划模块。',
    sections: [
      { number: '4.1', title: '系统总体架构设计', summary: '给出前后端分离的系统架构与各模块交互关系。' },
      { number: '4.2', title: '学习者画像构建模块', summary: '设计多维度画像模型及数据采集与更新机制。' },
      { number: '4.3', title: '学习资源推荐算法设计', summary: '阐述融合知识追踪与协同过滤的推荐算法设计思路。' },
      { number: '4.4', title: '学习路径动态规划模块', summary: '介绍基于强化学习的学习路径自适应调整策略。' },
    ]
  },
  {
    number: '五',
    title: '系统实现与实验分析',
    summary: '介绍系统开发环境、核心功能实现及实验验证结果。',
    sections: [
      { number: '5.1', title: '开发环境与关键技术', summary: '说明开发语言、框架、数据库及部署环境。' },
      { number: '5.2', title: '核心功能实现', summary: '展示推荐模块、路径规划模块的关键代码与界面。' },
      { number: '5.3', title: '实验设计与数据集', summary: '介绍实验方案、评价指标及数据集来源。' },
      { number: '5.4', title: '实验结果与分析', summary: '通过图表对比分析系统性能与用户体验。' },
    ]
  },
  {
    number: '六',
    title: '结论与展望',
    summary: '总结研究工作与创新点，指出不足并展望未来研究方向。',
    sections: [
      { number: '6.1', title: '研究工作总结', summary: '概括论文主要研究内容与取得的成果。' },
      { number: '6.2', title: '创新点与不足', summary: '总结研究创新之处及存在的局限性。' },
      { number: '6.3', title: '未来研究方向', summary: '提出可进一步探索的技术与应用方向。' },
    ]
  },
])

const totalSections = computed(() => {
  return outline.value.reduce((sum, c) => sum + c.sections.length, 0)
})

const chartTypeOptions = [
  { value: 'table', label: '表格', prefix: '表', color: '#0ea5e9' },
  { value: 'chart', label: '图表', prefix: '图', color: '#14b8a6' },
  { value: 'formula', label: '公式', prefix: '式', color: '#8b5cf6' },
  { value: 'code', label: '代码', prefix: '代码', color: '#10b981' },
  { value: 'mindmap', label: '思维导图', prefix: '图', color: '#ec4899' },
  { value: 'literature', label: '文献原图', prefix: '图', color: '#d946ef', advancedOnly: true },
]

// 高级版订单才显示"文献原图"等 advancedOnly 选项
const visibleChartTypeOptions = computed(() => {
  const isAdvanced = Number(quickForm.value.is_advanced) === 1
  return chartTypeOptions.filter(t => !t.advancedOnly || isAdvanced)
})

const selectedChartTypes = ref(['table'])
const activeChartMenu = ref(null)
const chartItems = ref([])
const chartCounters = ref({ table: 0, chart: 0, formula: 0, code: 0, mindmap: 0, literature: 0 })

function positionLabel(item) {
  const chapter = outline.value[item.chapterIdx]
  const section = chapter?.sections[item.sectionIdx]
  let label = `${chapter.number} ${chapter.title} / ${section.number} ${section.title}`
  if (item.subSectionIdx != null) {
    const sub = section.subSections[item.subSectionIdx]
    label += ` / ${sub.number} ${sub.title}`
  }
  return label
}

function addChart(type, chapterIdx, sectionIdx, subSectionIdx) {
  // 每个小节只能有一种图表，先清除该位置已有的图表
  const existing = chartsFor(chapterIdx, sectionIdx, subSectionIdx)
  existing.forEach(item => {
    const idx = chartItems.value.indexOf(item)
    if (idx > -1) chartItems.value.splice(idx, 1)
  })

  const opt = chartTypeOptions.find(o => o.value === type)
  chartCounters.value[type]++
  const num = chartCounters.value[type]
  chartItems.value.push({
    type,
    label: `${opt.prefix}${num}`,
    chapterIdx,
    sectionIdx,
    subSectionIdx,
    color: opt.color,
  })
}

function toggleChart(type, chapterIdx, sectionIdx, subSectionIdx) {
  const existing = chartsFor(chapterIdx, sectionIdx, subSectionIdx)
  if (existing.length && existing[0].type === type) {
    // 已选该类型，取消选择
    const idx = chartItems.value.indexOf(existing[0])
    if (idx > -1) chartItems.value.splice(idx, 1)
  } else {
    addChart(type, chapterIdx, sectionIdx, subSectionIdx)
  }
}

function selectedChartType(chapterIdx, sectionIdx, subSectionIdx) {
  const list = chartsFor(chapterIdx, sectionIdx, subSectionIdx)
  return list.length ? list[0].type : null
}

function removeChart(index) {
  chartItems.value.splice(index, 1)
}

function removeChartAt(chapterIdx, sectionIdx, subSectionIdx, itemIndex) {
  const list = chartsFor(chapterIdx, sectionIdx, subSectionIdx)
  const target = list[itemIndex]
  if (!target) return
  const idx = chartItems.value.indexOf(target)
  if (idx > -1) chartItems.value.splice(idx, 1)
}

function isChartMenuOpen(cIdx, sIdx, ssIdx) {
  const m = activeChartMenu.value
  if (!m) return false
  if (m.cIdx !== cIdx || m.sIdx !== sIdx) return false
  if (ssIdx !== undefined && m.ssIdx !== ssIdx) return false
  return true
}

function collectLeafPositions() {
  const positions = []
  outline.value.forEach((c, ci) => {
    c.sections.forEach((s, si) => {
      if (s.subSections?.length) {
        s.subSections.forEach((_, ssi) => positions.push({ ci, si, ssi }))
      } else {
        positions.push({ ci, si })
      }
    })
  })
  return positions
}

function autoAddCharts() {
  const types = selectedChartTypes.value
  if (!types.length) return

  // 只收集还没有图表的叶子节点
  const positions = collectLeafPositions().filter(pos =>
    !chartsFor(pos.ci, pos.si, pos.ssi).length
  )
  if (!positions.length) return

  types.forEach(type => {
    const pool = [...positions].sort(() => Math.random() - 0.5)
    const count = Math.min(2, pool.length)
    pool.slice(0, count).forEach(pos => addChart(type, pos.ci, pos.si, pos.ssi))
  })
}

function chartsFor(chapterIdx, sectionIdx, subSectionIdx) {
  return chartItems.value.filter(item =>
    item.chapterIdx === chapterIdx &&
    item.sectionIdx === sectionIdx &&
    item.subSectionIdx === subSectionIdx
  )
}

// ============ 章节排序/增删 ============
const chineseNums = ['一','二','三','四','五','六','七','八','九','十',
  '十一','十二','十三','十四','十五','十六','十七','十八','十九','二十']
function toChineseNum(n) {
  return chineseNums[n - 1] || String(n)
}

function renumberOutline() {
  outline.value.forEach((chapter, ci) => {
    chapter.number = toChineseNum(ci + 1)
    chapter.sections.forEach((section, si) => {
      section.number = `${ci + 1}.${si + 1}`
      if (section.subSections) {
        section.subSections.forEach((sub, ssi) => {
          sub.number = `${ci + 1}.${si + 1}.${ssi + 1}`
        })
      }
    })
  })
}

function moveChapter(idx, dir) {
  const arr = outline.value
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]]
  chartItems.value.forEach(item => {
    if (item.chapterIdx === idx) item.chapterIdx = newIdx
    else if (item.chapterIdx === newIdx) item.chapterIdx = idx
  })
  renumberOutline()
}

function moveSection(cIdx, sIdx, dir) {
  const arr = outline.value[cIdx].sections
  const newIdx = sIdx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[sIdx], arr[newIdx]] = [arr[newIdx], arr[sIdx]]
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx) {
      if (item.sectionIdx === sIdx) item.sectionIdx = newIdx
      else if (item.sectionIdx === newIdx) item.sectionIdx = sIdx
    }
  })
  renumberOutline()
}

function moveSubSection(cIdx, sIdx, ssIdx, dir) {
  const arr = outline.value[cIdx].sections[sIdx].subSections
  if (!arr) return
  const newIdx = ssIdx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[ssIdx], arr[newIdx]] = [arr[newIdx], arr[ssIdx]]
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx && item.sectionIdx === sIdx) {
      if (item.subSectionIdx === ssIdx) item.subSectionIdx = newIdx
      else if (item.subSectionIdx === newIdx) item.subSectionIdx = ssIdx
    }
  })
  renumberOutline()
}

// ============ 拖拽排序（SortableJS） ============
const sortMode = ref(false)
const sortableInstances = []

watch(sortMode, async (val) => {
  if (val) {
    await nextTick()
    initSortable()
  } else {
    destroySortable()
  }
})

function revertDom(evt) {
  const { oldIndex, item, from, to } = evt
  if (oldIndex === evt.newIndex && from === to) return
  // item 可能已被移到 to 容器，从当前位置移除后插回 from 的 oldIndex
  if (item.parentNode) item.parentNode.removeChild(item)
  if (oldIndex >= from.children.length) from.appendChild(item)
  else from.insertBefore(item, from.children[oldIndex])
}

function initSortable() {
  // chapter 层级（加入 mixed group，允许接收 section/subsection 升级为 chapter）
  const treeEl = document.querySelector('.outline-tree')
  if (treeEl) {
    sortableInstances.push(Sortable.create(treeEl, {
      draggable: '.chapter',
      animation: 200,
      forceFallback: true,
      fallbackClass: 's-drag',
      fallbackOnBody: true,
      group: { name: 'mixed', pull: true, put: true },
      ghostClass: 's-ghost',
      chosenClass: 's-chosen',
      onEnd: (evt) => {
        // chapter 拖到 sections/subsections 会被 onMove 阻止，这里只处理 chapter 在 tree 内排序
        if (evt.from === evt.to) {
          revertDom(evt)
          moveChapterTo(evt.oldIndex, evt.newIndex)
        }
        // section/subsection 拖到 tree 的情况由源容器的 onEnd(handleMixedDrop) 处理
      }
    }))
  }
  // section 层级 + subsection 层级共享 group 'mixed'，支持跨层级拖拽
  const sharedOpts = {
    animation: 200,
    forceFallback: true,
    fallbackClass: 's-drag',
    fallbackOnBody: true,
    group: { name: 'mixed', pull: true, put: true },
    ghostClass: 's-ghost',
    chosenClass: 's-chosen',
    onMove: (evt) => {
      // 阻止 chapter 拖入 sections/subsections（chapter 不能降级）
      if (evt.dragged.classList.contains('chapter')) return false
      return true
    },
    onEnd: (evt) => {
      revertDom(evt)
      handleMixedDrop(evt)
    }
  }
  document.querySelectorAll('.sections').forEach(el => {
    sortableInstances.push(Sortable.create(el, { ...sharedOpts, draggable: '.section' }))
  })
  document.querySelectorAll('.subsections').forEach(el => {
    sortableInstances.push(Sortable.create(el, { ...sharedOpts, draggable: '.subsection' }))
  })
}

function destroySortable() {
  sortableInstances.forEach(ins => ins.destroy())
  sortableInstances.length = 0
}

function moveChapterTo(oldIndex, newIndex) {
  if (oldIndex === newIndex) return
  const arr = outline.value
  const [item] = arr.splice(oldIndex, 1)
  arr.splice(newIndex, 0, item)
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === oldIndex) ci.chapterIdx = newIndex
    else if (oldIndex < newIndex && ci.chapterIdx > oldIndex && ci.chapterIdx <= newIndex) ci.chapterIdx--
    else if (oldIndex > newIndex && ci.chapterIdx >= newIndex && ci.chapterIdx < oldIndex) ci.chapterIdx++
  })
  renumberOutline()
}

function moveSectionTo(cIdx, oldIndex, newIndex) {
  if (oldIndex === newIndex) return
  const arr = outline.value[cIdx].sections
  const [item] = arr.splice(oldIndex, 1)
  arr.splice(newIndex, 0, item)
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx !== cIdx) return
    if (ci.sectionIdx === oldIndex) ci.sectionIdx = newIndex
    else if (oldIndex < newIndex && ci.sectionIdx > oldIndex && ci.sectionIdx <= newIndex) ci.sectionIdx--
    else if (oldIndex > newIndex && ci.sectionIdx >= newIndex && ci.sectionIdx < oldIndex) ci.sectionIdx++
  })
  renumberOutline()
}

// 跨 chapter 拖拽 section：处理同 chapter 排序 + 跨 chapter 迁移
function moveSectionBetween(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const toCIdx = parseInt(evt.to.dataset.cIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  if (fromCIdx === toCIdx) {
    // 同 chapter 内排序
    moveSectionTo(fromCIdx, oldIndex, newIndex)
    return
  }

  // 跨 chapter 移动
  const fromArr = outline.value[fromCIdx].sections
  const toArr = outline.value[toCIdx].sections
  const [item] = fromArr.splice(oldIndex, 1)
  toArr.splice(newIndex, 0, item)

  // 同步 chartItems 索引
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx) {
      if (ci.sectionIdx === oldIndex) {
        // 被移动的 section：跟随到新位置
        ci.chapterIdx = toCIdx
        ci.sectionIdx = newIndex
      } else if (ci.sectionIdx > oldIndex) {
        // 源 chapter 中位于被移除 section 之后的：索引 -1
        ci.sectionIdx--
      }
    } else if (ci.chapterIdx === toCIdx) {
      if (ci.sectionIdx >= newIndex) {
        // 目标 chapter 中位于插入位置及之后的：索引 +1
        ci.sectionIdx++
      }
    }
  })
  renumberOutline()
}

function moveSubSectionTo(cIdx, sIdx, oldIndex, newIndex) {
  if (oldIndex === newIndex) return
  const arr = outline.value[cIdx].sections[sIdx].subSections
  if (!arr) return
  const [item] = arr.splice(oldIndex, 1)
  arr.splice(newIndex, 0, item)
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx !== cIdx || ci.sectionIdx !== sIdx) return
    if (ci.subSectionIdx === oldIndex) ci.subSectionIdx = newIndex
    else if (oldIndex < newIndex && ci.subSectionIdx > oldIndex && ci.subSectionIdx <= newIndex) ci.subSectionIdx--
    else if (oldIndex > newIndex && ci.subSectionIdx >= newIndex && ci.subSectionIdx < oldIndex) ci.subSectionIdx++
  })
  renumberOutline()
}

// ============ 跨层级拖拽统一处理 ============
function handleMixedDrop(evt) {
  const fromIsSections = evt.from.classList.contains('sections')
  const toIsTree = evt.to.classList.contains('outline-tree')
  const toIsSections = evt.to.classList.contains('sections')

  if (toIsTree) {
    // section/subsection → chapter（升级为一级章节）
    if (fromIsSections) {
      promoteSectionToChapter(evt)
    } else {
      promoteSubsectionToChapter(evt)
    }
  } else if (fromIsSections && toIsSections) {
    moveSectionBetween(evt)
  } else if (fromIsSections && !toIsSections) {
    demoteSection(evt)
  } else if (!fromIsSections && toIsSections) {
    promoteSubsection(evt)
  } else {
    moveSubSectionBetween(evt)
  }
}

// section 升级为 chapter（其 subSections 自动升级为 sections）
function promoteSectionToChapter(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  // 1. 收集被移动的 section 及其 subSections 的 chartItems
  const movedItems = chartItems.value.filter(ci =>
    ci.chapterIdx === fromCIdx && ci.sectionIdx === oldIndex
  )
  chartItems.value = chartItems.value.filter(ci =>
    !(ci.chapterIdx === fromCIdx && ci.sectionIdx === oldIndex)
  )

  // 2. 源 chapter 中后续 section 的索引 -1
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx && ci.sectionIdx > oldIndex) ci.sectionIdx--
  })

  // 3. 从源 chapter 移除 section
  const [section] = outline.value[fromCIdx].sections.splice(oldIndex, 1)

  // 4. 转换为 chapter，subSections 升级为 sections
  const newChapter = {
    number: '',
    title: section.title,
    summary: section.summary,
    sections: (section.subSections || []).map(sub => ({
      number: '',
      title: sub.title,
      summary: sub.summary
    }))
  }
  outline.value.splice(newIndex, 0, newChapter)

  // 5. 新 chapter 及之后的 chapter 索引 +1
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx >= newIndex) ci.chapterIdx++
  })

  // 6. 更新 movedItems：section 自身图表 → chapter 图表；subSections 图表 → sections 图表
  movedItems.forEach(ci => {
    if (ci.subSectionIdx == null) {
      ci.chapterIdx = newIndex
      ci.sectionIdx = undefined
      ci.subSectionIdx = undefined
    } else {
      ci.chapterIdx = newIndex
      ci.sectionIdx = ci.subSectionIdx
      ci.subSectionIdx = undefined
    }
  })
  chartItems.value = [...chartItems.value, ...movedItems]
  renumberOutline()
}

// subsection 升级为 chapter
function promoteSubsectionToChapter(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const fromSIdx = parseInt(evt.from.dataset.sIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  // 1. 收集被移动的 subsection 的 chartItems
  const movedItems = chartItems.value.filter(ci =>
    ci.chapterIdx === fromCIdx && ci.sectionIdx === fromSIdx && ci.subSectionIdx === oldIndex
  )
  chartItems.value = chartItems.value.filter(ci =>
    !(ci.chapterIdx === fromCIdx && ci.sectionIdx === fromSIdx && ci.subSectionIdx === oldIndex)
  )

  // 2. 源 section 中后续 subsection 的索引 -1
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx && ci.sectionIdx === fromSIdx &&
        ci.subSectionIdx != null && ci.subSectionIdx > oldIndex) {
      ci.subSectionIdx--
    }
  })

  // 3. 从源 section 的 subSections 移除
  const fromArr = outline.value[fromCIdx].sections[fromSIdx].subSections || []
  const [sub] = fromArr.splice(oldIndex, 1)

  // 4. 转换为 chapter（无子级）
  const newChapter = {
    number: '',
    title: sub.title,
    summary: sub.summary,
    sections: []
  }
  outline.value.splice(newIndex, 0, newChapter)

  // 5. 新 chapter 及之后的 chapter 索引 +1
  chartItems.value.forEach(ci => {
    if (ci.chapterIdx >= newIndex) ci.chapterIdx++
  })

  // 6. 更新 movedItems
  movedItems.forEach(ci => {
    ci.chapterIdx = newIndex
    ci.sectionIdx = undefined
    ci.subSectionIdx = undefined
  })
  chartItems.value = [...chartItems.value, ...movedItems]
  renumberOutline()
}

// section 降级为 subsection
function demoteSection(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const toCIdx = parseInt(evt.to.dataset.cIdx)
  const toSIdx = parseInt(evt.to.dataset.sIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  const [section] = outline.value[fromCIdx].sections.splice(oldIndex, 1)
  // 清理被丢弃的 subSections 的图表（section 降级为 subsection 后不能有子级）
  if (section.subSections && section.subSections.length) {
    chartItems.value = chartItems.value.filter(ci =>
      !(ci.chapterIdx === fromCIdx && ci.sectionIdx === oldIndex && ci.subSectionIdx != null)
    )
  }
  // 转换为 subsection（三级不能再有子级，丢弃 subSections）
  const newSub = { number: '', title: section.title, summary: section.summary }
  if (!outline.value[toCIdx].sections[toSIdx].subSections) {
    outline.value[toCIdx].sections[toSIdx].subSections = []
  }
  outline.value[toCIdx].sections[toSIdx].subSections.splice(newIndex, 0, newSub)

  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx) {
      if (ci.sectionIdx === oldIndex) {
        // 被降级的 section 的图表（subSectionIdx 为 undefined）→ 跟随到新位置
        ci.chapterIdx = toCIdx
        ci.sectionIdx = toSIdx
        ci.subSectionIdx = newIndex
      } else if (ci.sectionIdx > oldIndex) {
        ci.sectionIdx--
      }
    } else if (ci.chapterIdx === toCIdx && ci.sectionIdx === toSIdx) {
      if (ci.subSectionIdx != null && ci.subSectionIdx >= newIndex) {
        ci.subSectionIdx++
      }
    }
  })
  renumberOutline()
}

// subsection 升级为 section
function promoteSubsection(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const fromSIdx = parseInt(evt.from.dataset.sIdx)
  const toCIdx = parseInt(evt.to.dataset.cIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  const fromArr = outline.value[fromCIdx].sections[fromSIdx].subSections || []
  const [sub] = fromArr.splice(oldIndex, 1)
  const newSection = { number: '', title: sub.title, summary: sub.summary }
  outline.value[toCIdx].sections.splice(newIndex, 0, newSection)

  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx && ci.sectionIdx === fromSIdx) {
      if (ci.subSectionIdx === oldIndex) {
        ci.chapterIdx = toCIdx
        ci.sectionIdx = newIndex
        ci.subSectionIdx = undefined
      } else if (ci.subSectionIdx != null && ci.subSectionIdx > oldIndex) {
        ci.subSectionIdx--
      }
    } else if (ci.chapterIdx === toCIdx && ci.sectionIdx >= newIndex) {
      ci.sectionIdx++
    }
  })
  renumberOutline()
}

// subsection 跨 section 排序（含跨 chapter）
function moveSubSectionBetween(evt) {
  const fromCIdx = parseInt(evt.from.dataset.cIdx)
  const fromSIdx = parseInt(evt.from.dataset.sIdx)
  const toCIdx = parseInt(evt.to.dataset.cIdx)
  const toSIdx = parseInt(evt.to.dataset.sIdx)
  const oldIndex = evt.oldIndex
  const newIndex = evt.newIndex

  if (fromCIdx === toCIdx && fromSIdx === toSIdx) {
    moveSubSectionTo(fromCIdx, fromSIdx, oldIndex, newIndex)
    return
  }

  const fromArr = outline.value[fromCIdx].sections[fromSIdx].subSections || []
  const [item] = fromArr.splice(oldIndex, 1)
  if (!outline.value[toCIdx].sections[toSIdx].subSections) {
    outline.value[toCIdx].sections[toSIdx].subSections = []
  }
  outline.value[toCIdx].sections[toSIdx].subSections.splice(newIndex, 0, item)

  chartItems.value.forEach(ci => {
    if (ci.chapterIdx === fromCIdx && ci.sectionIdx === fromSIdx) {
      if (ci.subSectionIdx === oldIndex) {
        ci.chapterIdx = toCIdx
        ci.sectionIdx = toSIdx
        ci.subSectionIdx = newIndex
      } else if (ci.subSectionIdx != null && ci.subSectionIdx > oldIndex) {
        ci.subSectionIdx--
      }
    } else if (ci.chapterIdx === toCIdx && ci.sectionIdx === toSIdx) {
      if (ci.subSectionIdx != null && ci.subSectionIdx >= newIndex) {
        ci.subSectionIdx++
      }
    }
  })
  renumberOutline()
}

onUnmounted(() => destroySortable())

function addChapter() {
  outline.value.push({
    number: '',
    title: '新章节',
    summary: '',
    sections: []
  })
  renumberOutline()
}

function addSiblingChapter(idx) {
  outline.value.splice(idx + 1, 0, {
    number: '',
    title: '新章节',
    summary: '',
    sections: []
  })
  chartItems.value.forEach(item => {
    if (item.chapterIdx > idx) item.chapterIdx++
  })
  renumberOutline()
}

function addSection(cIdx) {
  outline.value[cIdx].sections.push({
    number: '',
    title: '新小节',
    summary: ''
  })
  renumberOutline()
}

function addSiblingSection(cIdx, sIdx) {
  outline.value[cIdx].sections.splice(sIdx + 1, 0, {
    number: '',
    title: '新小节',
    summary: ''
  })
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx && item.sectionIdx > sIdx) item.sectionIdx++
  })
  renumberOutline()
}

function addSubSection(cIdx, sIdx) {
  const section = outline.value[cIdx].sections[sIdx]
  if (!section.subSections) section.subSections = []
  section.subSections.push({
    number: '',
    title: '新三级小节',
    summary: ''
  })
  renumberOutline()
}

function addSiblingSubSection(cIdx, sIdx, ssIdx) {
  outline.value[cIdx].sections[sIdx].subSections.splice(ssIdx + 1, 0, {
    number: '',
    title: '新三级小节',
    summary: ''
  })
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx && item.sectionIdx === sIdx && item.subSectionIdx > ssIdx) item.subSectionIdx++
  })
  renumberOutline()
}

function removeChapter(idx) {
  if (outline.value.length <= 1) return
  chartItems.value = chartItems.value.filter(item => item.chapterIdx !== idx)
  chartItems.value.forEach(item => {
    if (item.chapterIdx > idx) item.chapterIdx--
  })
  outline.value.splice(idx, 1)
  renumberOutline()
}

function removeSection(cIdx, sIdx) {
  chartItems.value = chartItems.value.filter(item =>
    !(item.chapterIdx === cIdx && item.sectionIdx === sIdx)
  )
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx && item.sectionIdx > sIdx) item.sectionIdx--
  })
  outline.value[cIdx].sections.splice(sIdx, 1)
  renumberOutline()
}

function removeSubSection(cIdx, sIdx, ssIdx) {
  chartItems.value = chartItems.value.filter(item =>
    !(item.chapterIdx === cIdx && item.sectionIdx === sIdx && item.subSectionIdx === ssIdx)
  )
  chartItems.value.forEach(item => {
    if (item.chapterIdx === cIdx && item.sectionIdx === sIdx && item.subSectionIdx > ssIdx) item.subSectionIdx--
  })
  outline.value[cIdx].sections[sIdx].subSections.splice(ssIdx, 1)
  renumberOutline()
}

const regenerate = () => {
  generating.value = true
  progress.value = 0
  outlineNo.value = ''
  streamText.value = ''
  if (quickForm.value.useCustomOutline && quickForm.value.customOutline) {
    enhanceOutlineStream()
  } else {
    generateOutlineStream()
  }
}

// ============ 大纲一键复制（Markdown 格式） ============
/** 将大纲节点转成 markdown 标题行(带序号): 一级 # 二级 ## 三级 ### */
function outlineLabel(item, isChapter) {
  const n = item.number || item.no || item.title_no || ""
  if (!n) return item.title || ""
  const sep = isChapter ? "、" : " "
  return n + sep + (item.title || "")
}
/** 由大纲数组生成 markdown 文本(含各层标题与概要) */
function buildOutlineMarkdown(list) {
  const out = []
  list.forEach((ch) => {
    out.push('# ' + outlineLabel(ch, true));
    if (ch.summary) out.push('', ch.summary);
    (ch.sections || []).forEach((sec) => {
      out.push('', '## ' + outlineLabel(sec, false));
      if (sec.summary) out.push('', sec.summary);
      (sec.subSections || []).forEach((sub) => {
        out.push('', '### ' + outlineLabel(sub, false));
        if (sub.summary) out.push('', sub.summary);
      });
    });
    out.push('');
  });
  return out.join('\n').replace(/\n{3,}/g, '\n\n').trim()
}
async function copyOutline() {
  const list = (outline.value && outline.value.length) ? outline.value : (streamOutline.value.length ? streamOutline.value : [])
  if (!list.length) { useToast().warning("暂无可复制的大纲内容"); return }
  const md = buildOutlineMarkdown(list)
  try {
    await navigator.clipboard.writeText(md)
    useToast().success("大纲已复制(Markdown 格式)")
  } catch (e) {
    // 非安全上下文降级:临时 textarea + execCommand
    const ta = document.createElement("textarea")
    ta.value = md
    ta.style.position = "fixed"
    ta.style.opacity = "0"
    document.body.appendChild(ta)
    ta.select()
    let ok = false
    try { ok = document.execCommand("copy") } catch (_) { ok = false }
    document.body.removeChild(ta)
    ok ? useToast().success("大纲已复制(Markdown 格式)") : useToast().error("复制失败,请手动复制")
  }
}

const generateFull = () => openOrderModal()

// ============ 订单确认弹窗（余额支付） ============
const showOrderModal = ref(false)
const userBalance = ref(0)
const paperBalance = ref(0)
const paperAdvancedBalance = ref(0)
const payWay = ref('balance') // balance(余额) / package(套餐额度)
const orderPaying = ref(false)
const orderPaid = ref(false)
const orderResult = ref(null)

// ============ 价格说明弹窗 ============
const showPriceModal = ref(false)
// 字数区间标签
const wordRangeLabels = ['5000 字以内', '5001 - 10000 字', '10001 - 20000 字', '20000 字以上']

// 价格数据：从后端 write_type_config.price_config 动态构建
const priceData = computed(() => {
  const models = aiModelsRaw.value
  const modelColumns = models.map(m => ({ code: m.code, name: m.name, tag: m.tag || '' }))

  const firstWithConfig = models.find(m => m.price_config)
  let tierCount = 0
  if (firstWithConfig) {
    try { tierCount = JSON.parse(firstWithConfig.price_config).length } catch (e) {}
  }

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

// 价格表：从后端 write_type_config.price_config 动态构建
const priceTable = computed(() => {
  const table = {}
  aiModelsRaw.value.forEach(m => {
    if (!m.price_config) return
    try {
      table[m.code] = JSON.parse(m.price_config)
    } catch (e) {}
  })
  return table
})

const aiModelsRaw = ref([])
const aiModelNames = computed(() => {
  const map = {}
  aiModelsRaw.value.forEach(m => {
    map[m.code] = m.name
  })
  return map
})

// 兼容旧硬编码: 若后端未返回则兜底
function getModelName(code) {
  return aiModelNames.value[code] || code
}

// 字数数值化
const wordCountNum = computed(() => {
  const w = quickForm.value.customWords || quickForm.value.words || ''
  const map = { '1万': 10000, '2万': 20000, '3万': 30000, '5万': 50000, '10万': 100000 }
  if (map[w]) return map[w]
  return parseInt(String(w).replace(/[^\d]/g, ''), 10) || 0
})

// 基础价
const basePrice = computed(() => {
  const wc = wordCountNum.value
  if (wc <= 0) return 0
  const tiers = priceTable.value[quickForm.value.model]
  if (tiers && tiers.length) {
    const tier = tiers.find(t => wc <= t.max_words) || tiers[tiers.length - 1]
    return parseFloat(tier.price) || 0
  }
  return 0
})

// 降低AI率加价
const lowerAiFee = computed(() => {
  if (!quickForm.value.needLowerAI) return 0
  return Math.round(basePrice.value * 0.3 * 100) / 100
})

// 应付总额
const orderAmount = computed(() => {
  return Math.round((basePrice.value + lowerAiFee.value) * 100) / 100
})

// 余额是否充足
const balanceEnough = computed(() => userBalance.value >= orderAmount.value)

// 当前所选模型是否为高级版（is_advanced=1）
const currentModelAdvanced = computed(() => {
  const m = aiModelsRaw.value.find(m => m.code === quickForm.value.model)
  return Number(m?.is_advanced) === 1
})

// 当前模型类型对应的套餐额度（普通→paper_balance / 高级→paper_advanced_balance）
const packageQuota = computed(() => currentModelAdvanced.value ? paperAdvancedBalance.value : paperBalance.value)
const packageEnough = computed(() => packageQuota.value >= 1)

// 当前所选支付方式是否可用
const payWayEnough = computed(() => payWay.value === 'package' ? packageEnough.value : balanceEnough.value)

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

// 带 token 的 fetch
async function apiFetch(url, options = {}) {
  const token = getToken()
  const headers = { ...(options.headers || {}) }
  if (token) headers['token'] = token
  return fetch(url, { ...options, headers })
}

// 打开订单弹窗
async function openOrderModal() {
  // 校验登录
  if (!getToken()) {
    useToast().warning('请先登录后再生成全文')
    return
  }
  if (!quickForm.value.title) {
    useToast().warning('论文标题为空，请返回填写')
    return
  }
  if (orderAmount.value <= 0) {
    useToast().warning('订单金额异常，请检查字数与模型')
    return
  }

  showOrderModal.value = true
  orderPaid.value = false
  orderResult.value = null
  orderPaying.value = false
  payWay.value = 'balance'

  // 拉取余额与套餐额度
  try {
    const res = await apiFetch('/api/pc/userBalance')
    const json = await res.json()
    if (json.code === 1) {
      userBalance.value = json.data?.user_money || 0
      paperBalance.value = json.data?.paper_balance || 0
      paperAdvancedBalance.value = json.data?.paper_advanced_balance || 0
    } else if (json.code === -1) {
      useToast().warning('登录已过期，请重新登录')
      showOrderModal.value = false
    }
  } catch (e) {
    useToast().error('获取余额失败')
  }
}

function closeOrderModal() {
  if (orderPaying.value) return
  showOrderModal.value = false
}

// 支付完成：关闭弹窗并通知父组件跳回下单首页
function finishOrder() {
  stopFinishCountdown()
  showOrderModal.value = false
  emit('reset')
}

// 支付成功后的跳转倒计时（几秒后自动返回下单首页）
const finishCountdown = ref(0)
let finishTimer = null
function startFinishCountdown() {
  stopFinishCountdown()
  finishCountdown.value = 3
  finishTimer = setInterval(() => {
    finishCountdown.value -= 1
    if (finishCountdown.value <= 0) {
      finishTimer = null
      finishOrder()
    }
  }, 1000)
}
function stopFinishCountdown() {
  if (finishTimer) {
    clearInterval(finishTimer)
    finishTimer = null
  }
}

// 确认支付
async function confirmPay() {
  if (orderPaying.value) return
  if (!payWayEnough.value) {
    if (payWay.value === 'package') {
      useToast().warning('套餐额度不足，请先购买对应套餐或改用余额支付')
    } else {
      useToast().warning('余额不足，请先充值')
    }
    return
  }
  orderPaying.value = true
  try {
    // 图表类型 → 后端检测字段映射(与 lunwen-new core/charts/detector.py 对齐)
    // 前端 chartItems.type 映射到后端 _detect_chart_type 期望的字段(值为 "1")
    const chartTypeFieldMap = {
      table: 'excel',      // 表格 → excel
      chart: 'histogram',  // 图表(泛型) → histogram 柱状图
      formula: 'math',     // 公式 → math
      code: 'code',        // 代码 → code
      mindmap: 'mindmap',  // 思维导图 → mindmap(走普通分支,正文生成后额外生成SVG)
      literature: 'literature', // 文献原图 → literature(调 figure-api 搜图,仅高级版)
    }
    // 提交前把 chartItems 合并进 outline 节点(深拷贝避免污染响应式数据)
    // 后端 outline_data 节点带上图表标记字段后,lunwen-new 的 _detect_chart_type 才能识别
    const outlineForSubmit = JSON.parse(JSON.stringify(outline.value))
    chartItems.value.forEach(ci => {
      const field = chartTypeFieldMap[ci.type]
      if (!field) return
      const chapter = outlineForSubmit[ci.chapterIdx]
      if (!chapter?.sections) return
      const section = chapter.sections[ci.sectionIdx]
      if (!section) return
      if (ci.subSectionIdx != null && section.subSections) {
        const sub = section.subSections[ci.subSectionIdx]
        if (sub) sub[field] = '1'
      } else {
        section[field] = '1'
      }
    })
    const payload = {
      title: quickForm.value.title,
      words: quickForm.value.customWords || quickForm.value.words,
      word_count: wordCountNum.value,
      model: quickForm.value.model,
      literature_count: quickForm.value.literatureCount ?? 25,
      en_literature_count: Math.max(0, Math.min(
        Number(quickForm.value.enLiteratureCount) || 0,
        Number(quickForm.value.literatureCount) || 25
      )),
      // 模板只传 id + 来源(public/private/fav), 模板名称由后端查库确定, 不信任前端传入
      template_id: quickForm.value.templateId || 0,
      template_source: quickForm.value.templateSource || '',
      outline_level: quickForm.value.outlineLevel,
      language: quickForm.value.language,
      need_lower_ai: quickForm.value.needLowerAI ? 1 : 0,
      use_custom_outline: quickForm.value.useCustomOutline ? 1 : 0,
      outline_data: JSON.stringify(outlineForSubmit),
      outline_no: outlineNo.value || '',
      pay_way: payWay.value,
    }
    const res = await apiFetch('/api/pc/createOrder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const json = await res.json()
    if (json.code === 1) {
      orderPaid.value = true
      orderResult.value = json.data
      userBalance.value = json.data?.left_money ?? userBalance.value
      // 套餐支付后同步刷新套餐额度
      if (json.data?.left_quota != null) {
        if (currentModelAdvanced.value) {
          paperAdvancedBalance.value = json.data.left_quota
        } else {
          paperBalance.value = json.data.left_quota
        }
      }
      useToast().success('支付成功，订单已提交')
      // 开始倒计时，几秒后自动跳回下单首页
      startFinishCountdown()
      // 通知父组件支付成功（刷新余额、跳转等）
      emit('paid', json.data)
    } else if (json.code === -1) {
      useToast().warning('登录已过期，请重新登录')
      showOrderModal.value = false
    } else {
      useToast().error(json.msg || '支付失败')
    }
  } catch (e) {
    useToast().error('网络异常，支付失败')
  } finally {
    orderPaying.value = false
  }
}
const goBack = () => emit('back')

function onKeydown(e) {
  if (e.key === 'Escape') activeChartMenu.value = null
}

function onDocumentClick(e) {
  if (!activeChartMenu.value) return
  const toolbar = document.querySelector('.chart-menu')
  const addBtn = e.target.closest('.op-chart')
  if (toolbar && !toolbar.contains(e.target) && !addBtn) {
    activeChartMenu.value = null
  }
}

// 生成进度动画
const generating = ref(false)
const progress = ref(0)
const genStepText = ref('正在分析论文标题与关键词…')
// 后端返回的大纲编号（不可推测），下单时回传关联
const outlineNo = ref('')
// 是否已生成过大纲（用于判断 active 切换时是否需要重新生成）
const hasGenerated = ref(false)
// 记录上次生成所用参数的签名，判断回到第一步修改参数后是否需重新生成
const lastGeneratedSig = ref('')
// 摘要影响大纲内容的表单参数，用于比对参数是否变化
function currentFormSig() {
  const f = quickForm.value
  return JSON.stringify([
    f.title, f.subject, f.major, f.degree,
    f.customWords || f.words, f.model, f.outlineLevel, f.language,
    f.useCustomOutline, f.customOutline, f.templateId, f.templateName, f.templateSource,
  ])
}
const stageTexts = [
  '分析论文标题与关键词',
  '检索相关学术文献',
  '构建章节逻辑结构',
  '生成各小节内容',
  '优化大纲层次与衔接',
]
let genTimer = null

// 流式文本展示
const streamText = ref('')
const streamBody = ref(null)

// 实时解析流式文本为大纲结构（复用 parseOutlineMarkdown）
const streamOutline = computed(() => parseOutlineMarkdown(streamText.value))

// 流式大纲自动滚动到底部（rAF 节流，避免频繁触发）
let scrollRaf = null
watch(streamText, () => {
  if (!process.client) return
  if (scrollRaf) return
  scrollRaf = requestAnimationFrame(() => {
    scrollRaf = null
    window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' })
  })
})

onMounted(async () => {
  window.addEventListener('keydown', onKeydown)
  document.addEventListener('click', onDocumentClick)

  // 加载后端模型列表(用于显示模型名称)
  loadAiModels()

  // 如果父组件传入了 restoreOutlineNo（订单详情查看），直接恢复历史大纲
  if (props.restoreOutlineNo) {
    await restoreOutline(props.restoreOutlineNo)
    return
  }
})

// 监听 active 状态：首次激活时触发生成大纲
// 组件用 v-show 保持挂载，返回上一步再回来时 hasGenerated=true，不会重新生成
watch(() => props.active, async (val) => {
  if (!val) return
  if (!getToken()) {
    useToast().warning('请先登录后再查看大纲')
    emit('back')
    return
  }
  // 触发生成（首次激活，或参数发生变化后重新生成）
  const startGen = () => {
    // 重新生成前先清除旧的生成状态与旧大纲内容，避免在旧记录上拼接
    streamText.value = ''
    outlineNo.value = ''
    outline.value = []
    progress.value = 0
    genStepText.value = ''
    lastGeneratedSig.value = currentFormSig()
    hasGenerated.value = true
    generating.value = true
    if (quickForm.value.useCustomOutline && quickForm.value.customOutline) {
      enhanceOutlineStream()
    } else {
      generateOutlineStream()
    }
  }
  if (!hasGenerated.value) {
    // 首次激活，开始生成
    startGen()
    return
  }
  // 已生成过大纲：若影响大纲内容的参数发生变化，则用新参数重新生成
  if (currentFormSig() !== lastGeneratedSig.value) {
    startGen()
  }
  // 参数未变化：保留原大纲，不重复生成
}, { immediate: true })

// 从数据库恢复历史大纲
async function restoreOutline(no) {
  try {
    const token = getToken()
    const res = await fetch(`/api/ai/outlineDetail?outline_no=${encodeURIComponent(no)}`, {
      headers: { 'token': token },
    })
    const json = await res.json()
    if (json.code !== 1 || !json.data?.outline_content) {
      useToast().error('大纲记录不存在或已失效')
      setTimeout(() => emit('back'), 1500)
      return false
    }
    outlineNo.value = json.data.outline_no || ''
    // 恢复 quickForm 字段
    if (json.data.title) quickForm.value.title = json.data.title
    if (json.data.words) quickForm.value.words = json.data.words
    if (json.data.model) quickForm.value.model = json.data.model
    if (json.data.outline_level) quickForm.value.outlineLevel = json.data.outline_level
    if (json.data.language) quickForm.value.language = json.data.language
    if (json.data.degree) quickForm.value.degree = json.data.degree
    if (json.data.profession) quickForm.value.subject = json.data.profession
    if (json.data.major) quickForm.value.major = json.data.major
    if (json.data.template_name) quickForm.value.templateName = json.data.template_name
    if (json.data.use_custom_outline) quickForm.value.useCustomOutline = true
    if (json.data.title) paperInfo.value.title = json.data.title
    // 解析大纲内容
    finishOutline(json.data.outline_content)
    return true
  } catch (e) {
    console.error('[outline] 恢复大纲失败:', e)
    return false
  }
}

async function loadAiModels() {
  try {
    const res = await fetch('/api/ai/models?type=paper')
    const json = await res.json()
    if (json.code === 1 && Array.isArray(json.data)) {
      aiModelsRaw.value = json.data
    }
  } catch (e) {
    console.error('加载模型列表失败', e)
  }
}

// SSE 流式生成大纲
async function generateOutlineStream() {
  const token = getToken()
  if (!token) return

  genStepText.value = '正在连接 AI 服务…'
  progress.value = 5

  // 请求参数
  const reqBody = {
    title: quickForm.value.title,
    words: quickForm.value.customWords || quickForm.value.words || '',
    model: quickForm.value.model || 'standard',
    type: 'paper',
    outline_level: quickForm.value.outlineLevel || 'two',
    language: quickForm.value.language || '中文',
    // profession 取学科门类（create.vue 存的是 subject），major 取具体专业
    profession: quickForm.value.subject || quickForm.value.profession || '',
    major: quickForm.value.major || '',
    degree: quickForm.value.degree || '本科',
    template_name: quickForm.value.templateName || '',
  }

  const stageTextsOnGen = [
    '正在检索相关学术文献…',
    '正在构建章节逻辑结构…',
    '正在生成各小节内容…',
    '正在优化大纲层次与衔接…',
  ]

  try {
    const response = await fetch('/api/ai/generateOutline', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'token': token,
        'Accept': 'text/event-stream',
      },
      body: JSON.stringify(reqBody),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    // 检测响应类型：若后端返回 JSON（如登录过期错误），直接处理
    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('application/json')) {
      const errJson = await response.json()
      if (errJson.code === -1) {
        useToast().warning('登录已过期，请重新登录')
        localStorage.removeItem('aidian_auth')
        setTimeout(() => useLoginModal().openIfNeeded(), 1200)
        return
      }
      throw new Error(errJson.msg || '生成失败')
    }

    // 检查是否支持流式读取
    if (!response.body || typeof response.body.getReader !== 'function') {
      // 降级：直接读取完整响应文本（兼容旧环境）
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
              progress.value = 100
              genStepText.value = '生成完成，正在加载大纲…'
              if (data.outline_no) {
                outlineNo.value = data.outline_no
              }
              if (fullText) {
                finishOutline(fullText)
              }
              return
            }

            if (data.content) {
              if (!firstChunkReceived) {
                firstChunkReceived = true
                progress.value = 15
                genStepText.value = stageTextsOnGen[0]
              }
              fullText += data.content; streamText.value += data.content
              // 基于内容长度估算进度（不超过 90%）
              const lenBased = Math.min(90, 15 + fullText.length / 20)
              progress.value = Math.max(progress.value, lenBased)
              const stageIdx = Math.min(Math.floor(progress.value / 20), stageTextsOnGen.length - 1)
              genStepText.value = stageTextsOnGen[stageIdx]
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
          if (data.content) fullText += data.content; streamText.value += data.content
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
    console.error('[outline] 流式生成失败，降级到同步接口:', e)
    // 降级到同步接口
    try {
      const syncResp = await fetch('/api/ai/generateOutlineSync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'token': token,
        },
        body: JSON.stringify(reqBody),
      })
      const syncJson = await syncResp.json()
      // 登录过期：跳转登录页
      if (syncJson.code === -1) {
        useToast().warning('登录已过期，请重新登录')
        localStorage.removeItem('aidian_auth')
        setTimeout(() => useLoginModal().openIfNeeded(), 1200)
        return
      }
      if (syncJson.code !== 1 || !syncJson.data?.content) {
        throw new Error(syncJson.msg || '同步生成也失败')
      }
      if (syncJson.data.outline_no) {
        outlineNo.value = syncJson.data.outline_no
      }
      // 模拟进度推进
      progress.value = 60
      genStepText.value = '正在生成大纲内容…'
      await new Promise(r => setTimeout(r, 200))
      progress.value = 90
      await new Promise(r => setTimeout(r, 200))
      finishOutline(syncJson.data.content)
    } catch (syncErr) {
      console.error('[outline] 同步生成也失败:', syncErr)
      useToast().error('大纲生成失败: ' + (syncErr.message || e.message || '未知错误'))
      genStepText.value = '生成失败，3 秒后返回…'
      setTimeout(() => {
        emit('back')
      }, 3000)
    }
  }
}

// SSE 流式增强导入大纲
async function enhanceOutlineStream() {
  const token = getToken()
  if (!token) return

  genStepText.value = '正在解析导入的大纲…'
  progress.value = 5

  const reqBody = {
    title: quickForm.value.title,
    words: quickForm.value.customWords || quickForm.value.words || '',
    model: quickForm.value.model || 'standard',
    type: 'paper',
    outline_level: quickForm.value.outlineLevel || 'two',
    language: quickForm.value.language || '中文',
    profession: quickForm.value.subject || quickForm.value.profession || '',
    major: quickForm.value.major || '',
    degree: quickForm.value.degree || '本科',
    custom_outline: quickForm.value.customOutline || '',
  }

  const stageTextsOnEnhance = [
    '正在分析大纲结构…',
    '正在为各章节补充概要…',
    '正在优化学术表达…',
    '正在整合完整大纲…',
  ]

  try {
    const response = await fetch('/api/ai/enhanceOutline', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'token': token,
        'Accept': 'text/event-stream',
      },
      body: JSON.stringify(reqBody),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('application/json')) {
      const errJson = await response.json()
      if (errJson.code === -1) {
        useToast().warning('登录已过期，请重新登录')
        localStorage.removeItem('aidian_auth')
        setTimeout(() => useLoginModal().openIfNeeded(), 1200)
        return
      }
      throw new Error(errJson.msg || '增强失败')
    }

    if (!response.body || typeof response.body.getReader !== 'function') {
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
              progress.value = 100
              genStepText.value = '大纲增强完成，正在加载…'
              if (data.outline_no) {
                outlineNo.value = data.outline_no
              }
              if (fullText) {
                finishOutline(fullText)
              }
              return
            }

            if (data.content) {
              if (!firstChunkReceived) {
                firstChunkReceived = true
                progress.value = 15
                genStepText.value = stageTextsOnEnhance[0]
              }
              fullText += data.content; streamText.value += data.content
              const lenBased = Math.min(90, 15 + fullText.length / 20)
              progress.value = Math.max(progress.value, lenBased)
              const stageIdx = Math.min(Math.floor(progress.value / 20), stageTextsOnEnhance.length - 1)
              genStepText.value = stageTextsOnEnhance[stageIdx]
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
          if (data.content) fullText += data.content; streamText.value += data.content
        } catch (e) {}
      }
    }

    if (fullText) {
      finishOutline(fullText)
    } else {
      throw new Error('未收到任何内容')
    }
  } catch (e) {
    console.error('[outline] 大纲增强失败:', e)
    useToast().error('大纲增强失败: ' + (e.message || '未知错误'))
    genStepText.value = '增强失败，3 秒后返回…'
    setTimeout(() => {
      emit('back')
    }, 3000)
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
      if (data.content) fullText += data.content; streamText.value += data.content
    } catch (e) {}
  }
  return fullText
}

// 完成大纲生成（解析并填充）
async function finishOutline(fullText) {
  progress.value = 100
  genStepText.value = '生成完成，正在加载大纲…'
  // 兼容 JSON 格式（从数据库恢复时 outline_content 已是 JSON）
  if (isJsonOutline(fullText)) {
    try {
      const parsed = jsonToOutline(JSON.parse(fullText))
      if (parsed.length > 0) outline.value = parsed
    } catch (e) {
      console.warn('JSON 大纲解析失败，回退到 Markdown', e)
      const parsed = parseOutlineMarkdown(fullText)
      if (parsed.length > 0) { outline.value = parsed; renumberOutline() }
    }
  } else {
    // AI 流式生成的 Markdown
    const parsed = parseOutlineMarkdown(fullText)
    if (parsed.length > 0) {
      outline.value = parsed
      // 统一由前端生成序号（章=中文一二三，节=1.1，三级=1.1.1）
      // 后端已不再输出任何编号，避免「四4.」这类中英文序号重复
      renumberOutline()
    }
    // 生成完成后将 outline 转为旧版兼容 JSON 格式回写数据库
    // 这样排版系统可直接消费结构化数据，且后续编辑可持久化
    if (outlineNo.value && outline.value.length > 0) {
      try {
        const jsonData = outlineToJson(outline.value)
        const res = await apiFetch('/api/ai/updateOutline', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ outline_no: outlineNo.value, content: JSON.stringify(jsonData) })
        })
        const result = await res.json()
        if (result.code === 1) {
          console.log('[outline] JSON 大纲已回写数据库')
        } else {
          console.warn('[outline] JSON 回写失败:', result.msg)
        }
      } catch (e) {
        console.warn('JSON 大纲回写失败（不影响使用）', e)
      }
    }
  }
  // 大纲生成完成。组件保持挂载（v-show），数据在内存中天然保持，无需任何缓存。
  setTimeout(() => {
    generating.value = false
    // 生成完成后自动滚动到大纲顶部,避免用户手动上划
    if (process.client) {
      // 等 v-if 渲染出最终大纲卡片(nextTick)后再滚动,否则卡片尚不存在滚不到顶部
      nextTick(() => {
        // 选中最终大纲卡片(PC: outline-card / mobile: outline-edit)
        const el = document.querySelector('.outline-card:not(.streaming-mode)')
        if (!el) return
        // 计算并避让顶部固定/吸顶 header,避免大纲顶部被 header 遮挡
        let offset = 88
        try {
          document.querySelectorAll('header, .app-header, .site-header').forEach((h) => {
            const pos = getComputedStyle(h).position
            const r = h.getBoundingClientRect()
            if ((pos === 'fixed' || pos === 'sticky') && r.top === 0 && h.offsetHeight > 0 && h.offsetHeight < 200) {
              offset = h.offsetHeight + 8
            }
          })
        } catch (e) {}
        const top = el.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - offset
        window.scrollTo({ top, behavior: 'smooth' })
      })
    }
  }, 400)
}

// ===== outline ↔ 旧版 JSON 双向转换 =====
// 旧版 JSON 格式（与排版系统兼容）：
// [{"chapter":"绪论","sections":[{"name":"研究背景","abstract":"概要","subsections":[]}]}]
// 当前 outline 结构：{number,title,summary,sections:[{number,title,summary,subSections:[]}]}

// outline 结构 → 旧版 JSON（保存到数据库时使用）
function outlineToJson(outline) {
  return outline.map(chapter => ({
    chapter: chapter.title || '',
    sections: (chapter.sections || []).map(section => ({
      name: section.title || '',
      abstract: section.summary || '',
      subsections: (section.subSections || []).map(sub => ({
        name: sub.title || '',
        abstract: sub.summary || '',
        subsections: []
      }))
    }))
  }))
}

// 旧版 JSON → outline 结构（从数据库恢复时使用）
function jsonToOutline(json) {
  if (!Array.isArray(json)) return []
  return json.map((ch, ci) => ({
    number: toChineseNum(ci + 1),
    title: ch.chapter || '',
    summary: '',
    sections: (ch.sections || []).map((sec, si) => ({
      number: `${ci + 1}.${si + 1}`,
      title: sec.name || '',
      summary: sec.abstract || '',
      subSections: (sec.subsections || []).map((sub, ssi) => ({
        number: `${ci + 1}.${si + 1}.${ssi + 1}`,
        title: sub.name || '',
        summary: sub.abstract || ''
      }))
    }))
  }))
}

// 检测字符串是否为旧版 JSON 格式（以 [ 开头）
function isJsonOutline(content) {
  return typeof content === 'string' && content.trim().startsWith('[')
}

// 剥离标题开头的各种编号前缀，只保留纯标题文字
// 支持：第X章 / 第1章 / 一、 / 1. / 1、 / 1) / 1.1 / 1.1.1 / Chapter 1 / Chapter 1: / I. 等
// 序号统一由前端 renumberOutline() 生成，AI 输出的任何序号都丢弃
function stripLeadingNumber(title) {
  if (!title) return ''
  let s = title.trim()
  // 1) 中文「第X章」/「第1章」+ 可选空格/冒号/点
  s = s.replace(/^第[一二三四五六七八九十百0-9]+章[\s:：、\.]*/u, '')
  // 2) 英文「Chapter 1」「Chapter 1:」「Chapter 1.」+ 可选空格
  s = s.replace(/^Chapter\s+\d+[\s:：\.]*/i, '')
  // 3) 阿拉伯数字 + 分隔符(./、/)) + 可选空格  「1.」「1、」「1)」「1. 」「1、 」
  //    分隔符必须有，避免误伤「5G」「3D」等以数字开头的标题文字
  s = s.replace(/^\d+[\.、\)]\s*/u, '')
  // 4) 阿拉伯数字小数层级编号 + 可选空格  「1.1」「1.1.1」「1.1. 」「1.1.1 」
  s = s.replace(/^\d+(?:\.\d+)+\.?\s*/u, '')
  // 5) 阿拉伯数字 + 空格 + 字母/中文  「1 Introduction」「4 结果分析」
  //    要求数字后至少一个空格，避免误伤「5G」「3D」
  s = s.replace(/^\d+\s+(?=[A-Za-z\u4e00-\u9fa5])/u, '')
  // 6) 中文「一、」「二.」等 + 可选空格
  s = s.replace(/^[一二三四五六七八九十百]+[、\.]\s*/u, '')
  // 7) 罗马数字「I.」「II.」「III.」+ 必须有空格
  s = s.replace(/^[IVXLCDM]+\.\s+/u, '')
  return s.trim()
}

// 解析 AI 返回的 Markdown 大纲为 outline 数据结构
function parseOutlineMarkdown(md) {
  if (!md) return []
  const lines = md.split('\n')
  const chapters = []
  let currentChapter = null
  let currentSection = null
  let currentSubSection = null

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim()
    if (!line) continue

    // 一级标题：# 引言 / # 第一章 引言 / # 1. Introduction / # Chapter 1: Intro
    if (line.startsWith('# ') && !line.startsWith('## ')) {
      const raw = line.slice(2).trim()
      // 统一剥离各种编号前缀，只保留纯标题文字
      // 序号由前端 renumberOutline() 自动生成，AI 输出的序号一律丢弃
      const stripped = stripLeadingNumber(raw)
      currentChapter = {
        number: toChineseNum(chapters.length + 1),
        title: stripped,
        summary: '',
        sections: []
      }
      chapters.push(currentChapter)
      currentSection = null
      currentSubSection = null
    }
    // 二级标题：## 研究背景 / ## 1.1 研究背景
    else if (line.startsWith('## ') && !line.startsWith('### ')) {
      const raw = line.slice(3).trim()
      const stripped = stripLeadingNumber(raw)
      if (currentChapter) {
        currentSection = {
          number: '',
          title: stripped,
          summary: ''
        }
        currentChapter.sections.push(currentSection)
        currentSubSection = null
      }
    }
    // 三级标题：### xxx / ### 1.1.1 xxx
    else if (line.startsWith('### ')) {
      const raw = line.slice(4).trim()
      const stripped = stripLeadingNumber(raw)
      if (currentSection) {
        if (!currentSection.subSections) {
          currentSection.subSections = []
        }
        currentSubSection = {
          number: '',
          title: stripped,
          summary: ''
        }
        currentSection.subSections.push(currentSubSection)
      }
    }
    // 描述文字（括号包围或普通文字）
    else {
      const desc = line.replace(/^\(|\)$/g, '').trim()
      if (desc && currentSubSection) {
        // 三级标题下的概要 → 写入三级 summary
        currentSubSection.summary = currentSubSection.summary
          ? currentSubSection.summary + desc
          : desc
      } else if (desc && currentSection) {
        // 二级标题下的概要 → 写入二级 summary
        currentSection.summary = currentSection.summary
          ? currentSection.summary + desc
          : desc
      } else if (desc && currentChapter && !currentSection) {
        currentChapter.summary = currentChapter.summary
          ? currentChapter.summary + desc
          : desc
      }
    }
  }

  return chapters
}

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.removeEventListener('click', onDocumentClick)
  if (genTimer) clearInterval(genTimer)
  stopFinishCountdown()
})

// 监听 console 布局顶栏刷新按钮：通过 window 自定义事件通信
onMounted(() => {
  window.addEventListener('console-refresh', handleRefreshEvent)
})
onUnmounted(() => {
  window.removeEventListener('console-refresh', handleRefreshEvent)
})
function handleRefreshEvent() {
  loadAiModels()
  if (props.restoreOutlineNo) {
    restoreOutline(props.restoreOutlineNo)
  }
}
</script>

<style scoped>
.outline-page {
  padding-top: 0;
}

.outline-page .container {
  max-width: 1340px;
}

.outline-body {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 28px;
  padding-bottom: 48px;
}

.outline-content {
  position: relative;
  min-height: 320px;
}

.outline-card,
.sidebar-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.05);
}

.outline-card {
  padding: 28px 30px;
}

/* ============ 流式大纲生成展示 ============ */
/* 流式模式卡片：微青背景 + 顶部流光 */
.streaming-mode {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 40%);
}

.streaming-mode::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #14b8a6, #5eead4, #0d9488, #2dd4bf, #14b8a6);
  background-size: 200% 100%;
  animation: stream-top-flow 3s linear infinite;
  z-index: 2;
}

@keyframes stream-top-flow {
  0% { background-position: 0% 0; }
  100% { background-position: 200% 0; }
}

/* 实时徽章 */
.stream-badge {
  display: inline-block;
  margin-left: 10px;
  padding: 2px 9px;
  font-size: 11px;
  font-weight: 600;
  color: #0d9488;
  background: #ccfbf1;
  border-radius: 999px;
  vertical-align: middle;
  animation: badge-pulse 2s ease-in-out infinite;
}

@keyframes badge-pulse {
  0%, 100% { opacity: 0.85; }
  50% { opacity: 1; box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1); }
}

/* 头部 AI 能量球（小） */
.stream-orb-wrap {
  display: grid;
  place-items: center;
}

.stream-orb-mini {
  position: relative;
  width: 22px;
  height: 22px;
  display: inline-block;
}

.orb-core {
  position: absolute;
  top: 50%;
  left: 50%;
  border-radius: 50%;
  background: radial-gradient(circle at 35% 35%, #5eead4, #0d9488);
  box-shadow: 0 0 12px rgba(20, 184, 166, 0.5);
  animation: orb-pulse 2s ease-in-out infinite;
}

.stream-orb-mini .orb-core {
  width: 10px;
  height: 10px;
  margin: -5px 0 0 -5px;
}

@keyframes orb-pulse {
  0%, 100% { transform: scale(1); box-shadow: 0 0 12px rgba(20, 184, 166, 0.5); }
  50% { transform: scale(1.18); box-shadow: 0 0 20px rgba(20, 184, 166, 0.7); }
}

.orb-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid #14b8a6;
  opacity: 0;
  animation: orb-ring-expand 2.4s ease-out infinite;
}

@keyframes orb-ring-expand {
  0% { transform: scale(0.5); opacity: 0.5; }
  100% { transform: scale(1.5); opacity: 0; }
}

/* 音波动画 */
.stream-wave {
  display: flex;
  align-items: center;
  gap: 3px;
  height: 22px;
  flex-shrink: 0;
}

.stream-wave span {
  width: 3px;
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(180deg, #14b8a6, #0d9488);
  animation: wave-bounce 1s ease-in-out infinite;
}

.stream-wave span:nth-child(2) { animation-delay: 0.15s; }
.stream-wave span:nth-child(3) { animation-delay: 0.3s; }
.stream-wave span:nth-child(4) { animation-delay: 0.45s; }
.stream-wave span:nth-child(5) { animation-delay: 0.6s; }

@keyframes wave-bounce {
  0%, 100% { transform: scaleY(0.3); opacity: 0.5; }
  50% { transform: scaleY(1); opacity: 1; }
}

/* 流式大纲树（只读模式） */
.outline-tree.streaming .chapter-title {
  gap: 10px;
}

.outline-tree.streaming .section-main {
  gap: 8px;
}

.outline-tree.streaming .subsection-main {
  gap: 8px;
}

.outline-tree.streaming .chapter-title h3 {
  color: #0f766e;
}

.outline-tree.streaming .section-main span,
.outline-tree.streaming .subsection-main span {
  color: #334155;
}

/* 章节序号徽章 */
.stream-chapter-num {
  display: inline-grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.28);
}

/* 层级圆点 */
.stream-section-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #14b8a6;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}

.stream-sub-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #94a3b8;
  flex-shrink: 0;
}

/* 层级连线 */
.outline-tree.streaming .sections {
  border-left: 2px solid #d1fae5;
  margin-left: 12px;
  padding-left: 18px;
}

.outline-tree.streaming .subsections {
  border-left: 2px solid #e2e8f0;
  margin-left: 5px;
  padding-left: 14px;
}

/* 概要引用风格 */
.stream-abstract {
  color: #64748b !important;
  font-style: normal !important;
  background: #f0fdfa !important;
  border-left: 3px solid #5eead4;
  border-radius: 0 8px 8px 0;
  padding: 8px 14px !important;
  margin: 6px 0 6px 4px;
}

/* 骨架占位 */
.stream-skeleton {
  padding: 6px 0 10px 18px;
}

.skeleton-line {
  height: 11px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
  margin-bottom: 8px;
}

.skeleton-line.short {
  width: 55%;
}

@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 淡入动画：章节/小节/概要出现时 */
.stream-fade-in {
  animation: stream-fade-up 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes stream-fade-up {
  0% { opacity: 0; transform: translateY(10px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* 打字光标行（大纲末尾） */
.stream-typing-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 0 4px;
  margin-top: 8px;
}

.stream-typing-cursor {
  display: inline-block;
  width: 7px;
  height: 16px;
  background: #14b8a6;
  border-radius: 1px;
  animation: cursor-blink 0.9s step-end infinite;
}

@keyframes cursor-blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}

.stream-typing-text {
  font-size: 12.5px;
  color: #94a3b8;
}

/* 空状态：多层涟漪 */
.stream-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 20px 44px;
  text-align: center;
}

.stream-ripple {
  position: relative;
  width: 56px;
  height: 56px;
  margin: 0 auto 22px;
}

.stream-ripple .orb-core {
  width: 20px;
  height: 20px;
  margin: -10px 0 0 -10px;
}

.ripple-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid #14b8a6;
  opacity: 0;
  animation: ripple-expand 2.4s ease-out infinite;
}

.ripple-ring.d1 { animation-delay: 0.6s; }
.ripple-ring.d2 { animation-delay: 1.2s; }

@keyframes ripple-expand {
  0% { transform: scale(0.4); opacity: 0.6; }
  100% { transform: scale(1.8); opacity: 0; }
}

.stream-empty p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* 底部流光条 */
.stream-footer-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 30px 16px;
  border-top: 1px solid #f1f5f9;
}

.stream-shimmer-bar {
  flex: 1;
  height: 3px;
  border-radius: 2px;
  background: linear-gradient(90deg, #e2e8f0 0%, #14b8a6 50%, #e2e8f0 100%);
  background-size: 200% 100%;
  animation: shimmer-sweep 1.8s linear infinite;
}

@keyframes shimmer-sweep {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.stream-tip {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
}

/* 过渡动画：丝滑切换 */
.gen-switch-enter-active {
  transition: opacity 0.4s ease;
}

.gen-switch-leave-active {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1;
  transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.gen-switch-enter-from {
  opacity: 0;
}

.gen-switch-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.99);
}

/* 完成后大纲章节 stagger 依次入场 */
.outline-card:not(.streaming-mode) .chapter {
  animation: outline-stagger-in 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.outline-card:not(.streaming-mode) .chapter:nth-child(1) { animation-delay: 0.05s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(2) { animation-delay: 0.11s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(3) { animation-delay: 0.17s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(4) { animation-delay: 0.23s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(5) { animation-delay: 0.29s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(6) { animation-delay: 0.35s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(7) { animation-delay: 0.41s; }
.outline-card:not(.streaming-mode) .chapter:nth-child(8) { animation-delay: 0.47s; }

@keyframes outline-stagger-in {
  0% { opacity: 0; transform: translateY(14px); }
  85% { opacity: 1; transform: translateY(0); }
  100% { opacity: 1; }
}

/* 移动端适配 */
@media (max-width: 640px) {
  .stream-badge {
    font-size: 10px;
    padding: 1px 7px;
  }

  .stream-wave {
    height: 18px;
  }

  .stream-chapter-num {
    width: 22px;
    height: 22px;
    font-size: 11px;
    border-radius: 6px;
  }

  .outline-tree.streaming .sections {
    margin-left: 10px;
    padding-left: 14px;
  }

  .stream-footer-bar {
    padding: 10px 18px 14px;
  }

  .stream-tip {
    display: none;
  }
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 18px;
  margin-bottom: 18px;
  position: relative;
}

.card-head::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--gray-200), transparent);
}

.head-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.head-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
}

.card-head h2 {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
}

.head-actions {
  display: flex;
  gap: 10px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  background: rgba(241, 245, 249, 0.8);
  color: var(--dark-700);
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600);
}

.action-btn.primary {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: #fff;
}

.action-btn.primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(13, 148, 136, 0.25);
}

.outline-tree {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.chapter {
  border-radius: 14px;
  overflow: visible;
  transition: box-shadow 0.2s ease;
  background: linear-gradient(180deg, #fafbfd 0%, #fff 60%);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  position: relative;
}

/* 当 chapter 内任意 chart-menu 打开时，提升整个 chapter 层级，
   避免被后续 chapter 卡片覆盖 */
.chapter:has(.chart-menu) {
  z-index: 100;
}

.chapter:hover {
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.chapter-title {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px 8px;
}

.chapter > .editable.abstract {
  padding: 0 20px 12px 20px;
  margin-top: 0;
}

.editable {
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 2px 8px;
  font-family: inherit;
  transition: all 0.15s ease;
  outline: none;
  word-break: break-word;
  white-space: pre-wrap;
}

.editable:hover {
  background: rgba(15, 23, 42, 0.04);
}

.editable:focus {
  border-color: var(--primary-300);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.editable:empty::before {
  content: attr(data-placeholder);
  color: var(--gray-300);
  pointer-events: none;
}

.editable[data-number]::before {
  content: attr(data-number) "  ";
  color: var(--gray-500);
  font-weight: 700;
}

.chapter-title .editable {
  flex: 1;
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800);
  margin: 0;
}

.chapter-title .editable[data-number]::before {
  color: var(--primary-600);
}

.sections {
  padding: 4px 20px 16px 24px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.section {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 14px;
  background: rgba(248, 250, 252, 0.55);
  border-radius: 10px;
  font-size: 13px;
  color: var(--dark-700);
  transition: background 0.15s ease;
  position: relative;
}

/* 当 chart-menu 打开时，提升当前 section 层级，避免被后续兄弟节点遮盖 */
.section:has(> .section-main > .chart-menu) {
  z-index: 100;
}

.section.has-sub {
  background: transparent;
  padding: 10px 0 0;
}

.section:hover {
  background: rgba(20, 184, 166, 0.05);
}

.section.has-sub:hover {
  background: transparent;
}

.section-main .editable {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-700);
}

.section-main .editable[data-number]::before {
  color: var(--primary-600);
}

.editable.abstract {
  width: 100%;
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: var(--gray-500);
  font-weight: 400;
}

.editable.abstract[data-number]::before {
  display: none;
}

.subsections {
  width: 100%;
  padding-left: 28px;
  margin-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
/* 普通模式下空的 subsections 不占空间 */
.subsections:empty {
  display: none;
}
/* 排序模式下空 subsections 显示细条，便于拖入 */
.outline-tree.sort-mode .subsections:empty {
  display: block;
  min-height: 10px;
  padding: 0 0 0 28px;
  margin-top: 4px;
  border: 1px dashed rgba(20, 184, 166, 0.25);
  border-radius: 6px;
  background: rgba(20, 184, 166, 0.03);
}

.subsection {
  padding: 9px 14px;
  background: rgba(241, 245, 249, 0.55);
  border-radius: 8px;
  transition: background 0.15s ease;
  position: relative;
}

/* 当 chart-menu 打开时，提升当前 subsection 层级，避免被后续兄弟节点遮盖 */
.subsection:has(> .subsection-main > .chart-menu) {
  z-index: 100;
}

.subsection:hover {
  background: rgba(20, 184, 166, 0.06);
}

.subsection-main {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
}

.subsection-main .editable {
  flex: 1;
  font-size: 13px;
  font-weight: 500;
  color: var(--dark-700);
}

.subsection-main .editable[data-number]::before {
  color: var(--gray-500);
}

.section-main {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  position: relative;
}

.chart-badge {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 4px 2px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  color: var(--c);
  background: color-mix(in srgb, var(--c) 10%, #fff);
  border: 1px solid color-mix(in srgb, var(--c) 24%, transparent);
  line-height: 1.6;
}

.chart-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(15, 23, 42, 0.06);
  padding: 4px;
  z-index: 50;
  min-width: 150px;
  animation: menuIn 0.16s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes menuIn {
  from { opacity: 0; transform: translateY(-4px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 7px 10px;
  border-radius: 8px;
  background: transparent;
  border: none;
  color: var(--gray-600);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: left;
}

.menu-item:hover {
  background: var(--gray-50);
  color: var(--dark-700);
}

.menu-item.active {
  background: color-mix(in srgb, var(--c) 10%, #fff);
  color: var(--c);
}

.item-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1.5px solid #fff;
  box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.12);
  flex-shrink: 0;
}

.item-label {
  flex: 1;
}

.item-check {
  flex-shrink: 0;
  color: var(--c);
}

.badge-x {
  width: 16px;
  height: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--c);
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.15s ease;
}

.chart-badge:hover .badge-x {
  opacity: 1;
}

.badge-x:hover {
  background: color-mix(in srgb, var(--c) 20%, transparent);
}

.ops-group {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  flex-shrink: 0;
  padding: 4px;
  background: rgba(241, 245, 249, 0.65);
  border-radius: 10px;
  opacity: 0.55;
  transition: all 0.2s ease;
}

.chapter:hover > .chapter-title .ops-group,
.section:hover > .section-main .ops-group,
.subsection:hover > .subsection-main .ops-group {
  opacity: 1;
  background: rgba(241, 245, 249, 0.95);
  box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.05);
}

.op-btn {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: var(--gray-500);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}

.op-btn svg {
  width: 15px;
  height: 15px;
}

/* 默认按类型着色（柔和） */
.op-btn.op-add { color: #14b8a6; }
.op-btn.op-chart { color: #6366f1; }
.op-btn.op-danger { color: #94a3b8; }

.op-btn:hover:not(:disabled) {
  background: #fff;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
  transform: translateY(-1px);
}

.op-btn.op-add:hover:not(:disabled) {
  color: #0d9488;
  background: #f0fdfa;
}

.op-btn.op-chart:hover:not(:disabled) {
  color: #4f46e5;
  background: #eef2ff;
}

.op-btn.op-danger:hover:not(:disabled) {
  color: #ef4444;
  background: #fef2f2;
}

.op-btn:disabled {
  opacity: 0.2;
  cursor: default;
}

.op-btn:active:not(:disabled) {
  transform: translateY(0);
}

/* ============ 拖拽排序（SortableJS） ============ */
/* 排序模式：禁用编辑 */
.outline-tree.sort-mode .editable {
  pointer-events: none;
}

/* 排序模式下整个节点十字箭头光标 */
.outline-tree.sort-mode .chapter,
.outline-tree.sort-mode .section,
.outline-tree.sort-mode .subsection,
.outline-tree.sort-mode .chapter-title,
.outline-tree.sort-mode .section-main,
.outline-tree.sort-mode .subsection-main,
.outline-tree.sort-mode .sections,
.outline-tree.sort-mode .subsections,
.outline-tree.sort-mode .editable {
  cursor: move;
}

/* 拖拽手柄 */
.sort-grip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  color: var(--gray-400);
  cursor: move;
  transition: color 0.15s ease;
}
.outline-tree.sort-mode .chapter:hover .sort-grip,
.outline-tree.sort-mode .section:hover .sort-grip,
.outline-tree.sort-mode .subsection:hover .sort-grip {
  color: var(--primary-600);
}
/* 排序模式下手柄默认就可见 */
.outline-tree.sort-mode .sort-grip {
  color: var(--primary-500);
  opacity: 0.85;
}
.outline-tree.sort-mode .sort-grip:hover {
  opacity: 1;
}

/* 排序模式下节点提示 */
.outline-tree.sort-mode .section,
.outline-tree.sort-mode .subsection {
  outline: 1px dashed rgba(20, 184, 166, 0.2);
  outline-offset: -1px;
}

/* SortableJS 状态类 */
.s-ghost {
  opacity: 0 !important;
  background: transparent !important;
  border: 2px dashed rgba(20, 184, 166, 0.35) !important;
  border-radius: 10px;
  box-shadow: none !important;
}
.s-chosen {
  opacity: 0.95;
}
.s-drag {
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.22) !important;
  background: #fff !important;
  border-radius: 14px !important;
  opacity: 1 !important;
  cursor: move !important;
}

.sidebar-card {
  padding: 22px;
  margin-bottom: 16px;
}

.sidebar-card h3 {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 16px;
}

.param-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.param-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 14px;
}

.param-label {
  color: var(--gray-500);
}

.param-value {
  font-weight: 700;
  color: var(--dark-700);
}

.status-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-700);
  margin-bottom: 10px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-dot.success {
  background: var(--primary-500);
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.15);
}

.status-tip {
  font-size: 13px;
  color: var(--gray-500);
  line-height: 1.6;
}

.action-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-btn-group {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: center;
}

/* 次要按钮（上一步） */
.btn-side-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 110px;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 600;
  border-radius: 999px;
  background: #fff;
  border: 1.5px solid var(--gray-200, #e2e8f0);
  color: var(--gray-600, #475569);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-side-secondary:hover {
  border-color: var(--primary-400, #5eead4);
  color: var(--primary-600, #0d9488);
  background: var(--gray-50, #f8fafc);
}

/* 主要按钮（生成全文） */
.btn-side-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 110px;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 600;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6) 0%, var(--primary-600, #0d9488) 100%);
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

.btn-block {
  width: 100%;
}

.chart-card .chart-block + .chart-block {
  margin-top: 16px;
}

.chart-card .btn-block {
  width: auto;
  margin: 18px auto 0;
  padding: 8px 18px;
  font-size: 13px;
  display: flex;
}

.chart-card .block-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  margin-bottom: 10px;
}

.chart-types {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.chart-type {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 10px;
  border-radius: 10px;
  background: rgba(241, 245, 249, 0.6);
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-600);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.chart-type input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.ctype-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.chart-type:hover {
  background: color-mix(in srgb, var(--c, var(--primary-500)) 10%, #fff);
  color: var(--c, var(--primary-600));
}

.chart-type.active {
  font-weight: 700;
  color: var(--c);
  background: color-mix(in srgb, var(--c) 12%, #fff);
  box-shadow: 0 2px 8px color-mix(in srgb, var(--c) 25%, transparent);
}

.manual-chart-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: 14px;
}

.manual-chart-form .cell-select {
  width: 100%;
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-200);
  background: #fff;
  font-size: 13px;
  color: var(--dark-700);
  outline: none;
}

.manual-chart-form .cell-select:focus {
  border-color: var(--primary-300);
}

.manual-chart-form .action-btn.small {
  grid-column: 1 / -1;
  justify-content: center;
  padding: 9px 16px;
  font-size: 13px;
}

.chart-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: 14px 0;
  padding-top: 14px;
  border-top: 1px solid var(--gray-100);
}

.chart-tag {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: var(--gray-50);
  border-radius: 10px;
  font-size: 13px;
}

.chart-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.chart-tag-text {
  flex: 1;
  color: var(--dark-700);
  line-height: 1.4;
}

.chart-remove {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--gray-400);
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.chart-remove:hover {
  background: var(--gray-200);
  color: var(--dark-700);
}

@media (max-width: 1280px) {
  .outline-page .container {
    max-width: 100%;
  }

  .outline-body {
    grid-template-columns: 1fr 300px;
    gap: 22px;
  }

  .outline-card {
    padding: 24px 22px;
  }
}

@media (max-width: 968px) {
  .outline-body {
    grid-template-columns: 1fr;
  }

  .outline-sidebar {
    order: -1;
  }

  .card-head {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 640px) {
  .nav-links {
    display: none;
  }

  /* H5：m 布局外层已有留白（.m-main 14px），去掉 .container 的桌面级 24px 内边距，避免三层面板叠加导致两侧过空 */
  .outline-page .container {
    padding: 0;
  }

  /* 论文参数卡 H5 删减：参数在第一步表单已录入确认，此处重复占屏 */
  .params-card {
    display: none;
  }

  /* 取消 968px 断点的 sidebar 前置：H5 阅读流 = 大纲 → 图表与公式 → 上一步/生成全文 */
  .outline-sidebar {
    order: 0;
  }

  .outline-card {
    padding: 18px;
  }

  .sidebar-card {
    padding: 14px 16px;
  }

  .card-head {
    gap: 12px;
  }

  .head-icon {
    width: 30px;
    height: 30px;
  }

  .card-head h2 {
    font-size: 16px;
  }

  /* 按钮组 H5 化：禁止按钮内文字换行，尺寸压缩至 390px 一行放下三键 */
  .head-actions {
    flex-wrap: wrap;
    gap: 8px;
  }

  .action-btn {
    padding: 7px 11px;
    font-size: 12.5px;
    gap: 4px;
    white-space: nowrap;
  }

  .action-btn svg {
    width: 14px;
    height: 14px;
  }

  .chapter-title {
    padding: 12px 14px 8px;
  }

  .sections {
    padding-left: 16px;
    padding-right: 16px;
  }
}
</style>

<!-- 订单确认弹窗样式（非 scoped，因 Teleport 到 body） -->
<style>
.order-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.order-modal {
  width: 100%;
  max-width: 460px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

/* 头部 */
.om-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px 22px 11px;
  border-bottom: 1px solid #f1f5f9;
}

.om-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.om-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 3px 10px rgba(13, 148, 136, 0.3);
}

.om-title {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.om-close {
  width: 30px;
  height: 30px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
}

.om-close:hover:not(:disabled) {
  background: #f1f5f9;
  color: #475569;
}

.om-close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* 内容 */
.om-body {
  padding: 14px 22px;
  overflow-y: auto;
  flex: 1;
}

.om-section {
  margin-bottom: 12px;
}

.om-section:last-child {
  margin-bottom: 0;
}

.om-section-title {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  padding-left: 8px;
  border-left: 3px solid #0d9488;
}

/* 订单信息网格 */
.om-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 16px;
  background: #f8fafc;
  border-radius: 10px;
  padding: 12px 14px;
}

.om-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.om-cell-wide {
  grid-column: 1 / -1;
}

.om-cell-label {
  font-size: 12px;
  color: #94a3b8;
}

.om-cell-value {
  font-size: 13px;
  color: #1e293b;
  font-weight: 500;
  word-break: break-all;
  line-height: 1.4;
}

.om-cell-title {
  font-weight: 600;
  color: #0f172a;
}

/* 金额明细 */
.om-amount-list {
  background: #f8fafc;
  border-radius: 10px;
  padding: 4px 14px;
}

.om-amount-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 0;
  border-bottom: 1px solid #eef2f7;
}

.om-amount-row:last-child {
  border-bottom: none;
}

.om-amount-label {
  font-size: 13px;
  color: #475569;
}

.om-amount-value {
  font-size: 13px;
  color: #1e293b;
  font-weight: 600;
  font-family: 'DIN Alternate', 'Helvetica Neue', sans-serif;
}

.om-amount-total {
  margin-top: 2px;
  padding-top: 8px;
  border-top: 1px dashed #cbd5e1;
  border-bottom: none;
}

.om-amount-total .om-amount-label {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.om-amount-price {
  font-size: 18px;
  font-weight: 800;
  color: #ef4444;
  letter-spacing: -0.3px;
}

/* 余额行 */
.om-balance-row {
  background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.om-balance-row.insufficient {
  background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
  border-color: #fecaca;
}

.om-balance-info {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.om-balance-label {
  font-size: 12px;
  color: #64748b;
}

.om-balance-value {
  font-size: 16px;
  font-weight: 700;
  color: #0d9488;
  font-family: 'DIN Alternate', 'Helvetica Neue', sans-serif;
}

.om-balance-row.insufficient .om-balance-value {
  color: #ef4444;
}

.om-balance-tip {
  font-size: 12px;
  color: #ef4444;
  font-weight: 500;
}

/* 支付方式选择 */
.om-pay-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.om-pay-opt {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.om-pay-opt:hover {
  border-color: #c7d2fe;
}

.om-pay-opt.active {
  border-color: #6366f1;
  background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
  box-shadow: 0 0 0 1px #6366f1 inset;
}

.om-pay-opt.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.om-pay-radio {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  position: relative;
}

.om-pay-opt.active .om-pay-radio {
  border-color: #6366f1;
}

.om-pay-opt.active .om-pay-radio::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #6366f1;
}

.om-pay-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.om-pay-name {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
}

.om-pay-desc {
  font-size: 12px;
  color: #64748b;
}

.om-pay-tip {
  margin-left: auto;
  font-size: 12px;
  color: #ef4444;
  font-weight: 500;
  flex-shrink: 0;
}

/* 成功状态 */
.om-success {
  text-align: center;
  padding: 16px 0 8px;
}

.om-success-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  margin-bottom: 14px;
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
  animation: om-success-pop 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes om-success-pop {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}

.om-success-text {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px;
}

.om-success-sub {
  font-size: 12px;
  color: #94a3b8;
  margin: 0 0 4px;
}

.om-success-bal {
  font-size: 13px;
  color: #0d9488;
  font-weight: 600;
  margin: 8px 0 0;
}

.om-success-count {
  font-size: 12px;
  color: #94a3b8;
  margin: 12px 0 0;
}

.om-success-count b {
  color: #ef4444;
  font-size: 14px;
}

/* 底部按钮 */
.om-footer {
  display: flex;
  gap: 10px;
  padding: 12px 22px 16px;
  border-top: 1px solid #f1f5f9;
}

.om-btn {
  flex: 1;
  height: 38px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
  padding: 0 16px;
}

.om-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.om-btn-ghost {
  background: #f1f5f9;
  color: #475569;
}

.om-btn-ghost:hover:not(:disabled) {
  background: #e2e8f0;
  color: #1e293b;
}

.om-btn-primary {
  background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%);
  color: #fff;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
}

.om-btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
}

.om-btn-primary.loading {
  opacity: 0.8;
}

.om-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: om-spin 0.7s linear infinite;
}

@keyframes om-spin {
  to { transform: rotate(360deg); }
}

/* 弹窗过渡 */
.order-modal-enter-active,
.order-modal-leave-active {
  transition: opacity 0.3s ease;
}

.order-modal-enter-active .order-modal,
.order-modal-leave-active .order-modal {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.order-modal-enter-from,
.order-modal-leave-to {
  opacity: 0;
}

.order-modal-enter-from .order-modal,
.order-modal-leave-to .order-modal {
  opacity: 0;
  transform: translateY(20px) scale(0.96);
}

/* 移动端 */
@media (max-width: 640px) {
  .order-mask {
    padding: 12px;
  }
  .order-modal {
    max-width: 100%;
    border-radius: 12px;
  }
  .om-header,
  .om-body,
  .om-footer {
    padding-left: 16px;
    padding-right: 16px;
  }
  .om-info-label,
  .om-amount-label {
    font-size: 12px;
  }
  .om-info-value,
  .om-amount-value {
    font-size: 12px;
  }
  .om-amount-price {
    font-size: 18px;
  }
}

/* ============ 金额明细标题 + 价格说明按钮 ============ */
.om-title-with-action {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.om-price-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s;
}

.om-price-btn:hover {
  color: #0d9488;
  border-color: #5eead4;
  background: #f0fdfa;
}

.om-price-btn svg {
  color: #0d9488;
}

/* ============ 价格说明弹窗（唯一类名，避免与 create.vue 全局 .price-modal-mask 同名覆盖导致层级被压到支付弹窗下方） ============ */
.om-price-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 20000;
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
  border-bottom: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.price-modal-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 800;
  color: #0f172a;
}

.price-modal-title svg {
  color: #f59e0b;
}

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
  color: #334155;
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
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
}

.price-section-icon.green {
  background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

.price-section-title {
  font-size: 15px;
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

/* 论文生成价格表 */
.price-table {
  border: 1px solid #f1f5f9;
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

.price-table-row:hover {
  background: #f0fdfa;
}

.price-col-range {
  color: #0f172a;
  font-weight: 600;
}

.price-col-price {
  color: #334155;
  font-weight: 700;
  font-size: 14px;
}

.price-col-price.pro {
  color: #f59e0b;
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
  color: #64748b;
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.legend-dot.standard {
  background: #64748b;
}

.legend-dot.pro {
  background: #f59e0b;
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
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  transition: all 0.15s;
}

.price-grid-item:hover {
  border-color: #5eead4;
  background: #f0fdfa;
}

.grid-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.grid-item-price {
  font-size: 16px;
  font-weight: 800;
  color: #f59e0b;
}

.grid-item-price.free {
  color: #0d9488;
  font-size: 13px;
}

.grid-item-price.paid {
  color: #f59e0b;
}

/* 说明 */
.price-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 12px 14px;
  background: #f8fafc;
  border-radius: 10px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.6;
}

.price-note svg {
  color: #94a3b8;
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
</style>
