<template>
  <ToolShell
    name="AI降重"
    desc="段落 / 多段落 / 文档降重 · 降低 AIGC 率与重复率 · 多平台多模式"
    theme="teal"
    wide
    hideHeader
    icon='<path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/><path d="M8 12h8"/><path d="M12 8v8"/>'
  >
    <div class="arw-layout">
      <!-- ======================== 左侧：选项面板 ======================== -->
      <aside class="arw-options">
        <!-- 余额卡片 -->
        <div class="arw-wallet">
          <div class="arw-wallet-head">
            <span class="arw-wallet-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
            </span>
            <span class="arw-wallet-title">我的钱包</span>
            <!-- 配额 hover 弹层 -->
            <span
              class="arw-wallet-mode arw-wallet-mode--quota"
              @mouseenter="openQuota"
              @mouseleave="quotaHover = false"
            >时长包配额</span>
            <Transition name="quota-pop">
              <div v-show="quotaHover" class="arw-quota-pop">
                <div class="arw-quota-pop-head">
                  <span class="arw-quota-pop-title">时长包配额使用情况</span>
                </div>
                <div v-if="quotaError" class="arw-quota-pop-empty">配额加载失败</div>
                <div v-else-if="quotaList.length" class="arw-quota-pop-list">
                  <div v-for="q in quotaList" :key="q.key" class="arw-quota-pop-row">
                    <div class="arw-quota-pop-top">
                      <span class="arw-quota-pop-label">{{ q.label }}</span>
                      <span class="arw-quota-pop-used">{{ q.used_text }} / {{ q.quota_text }}</span>
                    </div>
                    <div class="arw-quota-pop-bar">
                      <span class="arw-quota-pop-fill" :class="{ 'is-over': q.percent >= 100 }" :style="{ width: Math.min(q.percent, 100) + '%' }"></span>
                    </div>
                    <div class="arw-quota-pop-bottom">
                      <span class="arw-quota-pop-pct" :class="{ 'is-over': q.percent >= 100 }">{{ q.percent }}%</span>
                      <span class="arw-quota-pop-remain">剩余 {{ q.remaining_text }}</span>
                    </div>
                  </div>
                </div>
                <div v-else class="arw-quota-pop-empty">暂无配额数据</div>
              </div>
            </Transition>
          </div>
          <div class="arw-wallet-body">
            <div class="arw-wallet-row arw-wallet-row--price" :class="billingMode">
              <span class="arw-wallet-label">
                每千字单价
                <span class="arw-wallet-sub">{{ billingMode === 'paid' ? '按字符计费' : '当前免费' }}</span>
              </span>
              <span class="arw-wallet-value">
                <template v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}<em>/千字符</em></template>
                <template v-else>免费</template>
              </span>
            </div>
            <div class="arw-wallet-row">
              <span class="arw-wallet-label">
                账户余额
                <span class="arw-wallet-sub">双余额扣费 · 按单价</span>
              </span>
              <span class="arw-wallet-value">¥{{ walletMoney.toFixed(2) }}</span>
            </div>
            <div class="arw-wallet-row arw-wallet-row--packs">
              <div class="arw-wallet-pack">
                <span class="arw-wallet-label">
                  时长包
                  <span class="arw-wallet-sub">{{ timeActive ? '剩余 ' + formatTimeRemain(timeBalanceSec) : '未开通' }}</span>
                </span>
              </div>
            </div>
          </div>
          <NuxtLink to="/pc/package-shop" class="arw-wallet-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>套餐购买</span>
          </NuxtLink>
        </div>

        <!-- 支付方式卡：段落 / 多段落 / 文档降重共用（渠道选择外部化） -->
        <div class="arw-config-card arw-pay-card">
          <h3 class="arw-config-title">支付方式</h3>
          <div class="arw-chips arw-chips--wrap">
            <button
              v-for="o in (tab === 'document' ? docPayOptions : payMethodOptions)"
              :key="o.value"
              class="arw-chip arw-chip--pay"
              :class="{ active: tab === 'document' ? docPayMethod === o.value : payMethod === o.value }"
              :data-tip="o.tip || ''"
              @click="tab === 'document' ? chooseDocPayMethod(o.value) : (payMethod = o.value)"
            >{{ o.label }}</button>
          </div>
        </div>

        <!-- 配置卡 -->
        <div class="arw-config-card">
          <h3 class="arw-config-title">改写类型</h3>
          <div class="arw-chips arw-chips--wrap">
            <button
              v-for="o in typeOptions"
              :key="o.value"
              class="arw-chip"
              :class="{ active: form.type === o.value }"
              @click="form.type = o.value"
            >{{ o.label }}</button>
          </div>
        </div>

        <div class="arw-config-card">
          <h3 class="arw-config-title">目标平台</h3>
          <div class="arw-langtab">
            <button :class="{ active: platformLang === 'zh' }" @click="platformLang = 'zh'">中文</button>
            <button :class="{ active: platformLang === 'en' }" @click="platformLang = 'en'">英文</button>
          </div>
          <div class="arw-chips arw-chips--wrap">
            <button
              v-for="o in visiblePlatformOptions"
              :key="o.value"
              class="arw-chip arw-chip--platform"
              :class="{ active: form.platform === o.value }"
              @click="form.platform = o.value"
            >
              {{ o.label }}
              <span v-if="o.recommend" class="arw-tag">荐</span>
            </button>
          </div>
        </div>

        <div class="arw-config-card">
          <h3 class="arw-config-title">模式</h3>
          <div class="arw-mode-grid">
            <button
              v-for="o in visibleFamilyOptions"
              :key="o.value"
              class="arw-chip arw-chip--mode"
              :class="{ active: form.family === o.value }"
              @click="form.family = o.value"
            >
              <span class="arw-chip-name">
                {{ o.label }}
                <span v-if="o.recommend" class="arw-tag">荐</span>
              </span>
              <span class="arw-chip-desc">{{ o.desc }}</span>
            </button>
          </div>
        </div>
      </aside>

      <!-- ======================== 右侧：内容面板 ======================== -->
      <section class="arw-content">
        <!-- 模式提示横幅 -->
        <div class="arw-notice" :class="`arw-notice--${tab}`">
          <div class="arw-notice-track">
            <span class="arw-notice-text">{{ noticeText }}</span>
          </div>
        </div>

        <!-- 模式切换 -->
        <div class="arw-toolbar">
          <div class="arw-tabs">
            <button
              v-for="t in tabs"
              :key="t.key"
              class="arw-tab"
              :class="{ active: tab === t.key }"
              @click="tab = t.key"
            >{{ t.label }}</button>
          </div>
          <button v-if="tab !== 'document'" class="arw-records-btn" @click="openRecords">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            改写记录
          </button>
        </div>

        <!-- H5（m 壳）原文/结果切换条：PC 隐藏（.arw-m-tabs 基类 display:none），≤640 且 .m-main 下启用；仅段落降重模式展示 -->
        <div v-if="tab === 'paragraph'" class="arw-m-tabs">
          <button type="button" :class="{ active: mPane === 'input' }" @click="mPane = 'input'">原文</button>
          <button type="button" :class="{ active: mPane === 'result' }" @click="mPane = 'result'">改写结果</button>
        </div>

        <!-- ========== 段落降重 ========== -->
        <div v-show="tab === 'paragraph'" class="arw-mode">
          <div class="arw-text-columns">
            <!-- 原文输入 -->
            <div class="arw-panel arw-panel--input" :class="{ 'arw-m-off': mPane !== 'input' }">
              <div class="arw-panel-title">
                <span class="arw-panel-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </span>
                <span>原文内容</span>
              </div>
              <div class="arw-input-wrap">
                <textarea
                  v-model="form.text"
                  class="arw-textarea"
                  :placeholder="`请输入需要${actionLabel}的文本（至少 ${MIN_PARAGRAPH_CHARS} 字）...`"
                ></textarea>
                <div class="arw-wordcount">{{ textLen.toLocaleString() }} 字</div>
              </div>
            </div>

            <!-- 降重结果 -->
            <div class="arw-panel arw-panel--output" :class="{ 'arw-m-off': mPane !== 'result' }">
              <div class="arw-result-head">
                <h3>降重结果</h3>
                <button v-if="result" class="arw-copy-btn" :class="{ copied }" @click="copyResult">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  {{ copied ? '已复制' : '复制文本' }}
                </button>
              </div>
              <div class="arw-result-content">
                <div class="arw-result-text" :class="{ 'is-empty': !result && !loading }">
                  <span v-if="!result && !loading" class="arw-result-placeholder">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>{{ actionLabel }}结果将显示在这里</span>
                  </span>
                  <template v-else-if="!loading">{{ result }}</template>
                </div>
              </div>
            </div>

            <!-- 段落降重 loading 遮罩（仅覆盖文本工作区） -->
            <div class="arw-loading" :class="{ show: loading }" aria-live="polite" :aria-busy="loading">
              <div class="arw-loading-box">
                <div class="arw-loading-spinner">
                  <svg class="arw-loading-ring" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                      <linearGradient id="arwLoadGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#14b8a6" />
                        <stop offset="100%" stop-color="#0d9488" />
                      </linearGradient>
                    </defs>
                    <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(20,184,166,0.12)" stroke-width="6" />
                    <circle class="arw-loading-ring-fill" cx="60" cy="60" r="50" fill="none" stroke="url(#arwLoadGrad)" stroke-width="6" stroke-linecap="round" stroke-dasharray="80 234" />
                  </svg>
                  <div class="arw-loading-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                  </div>
                </div>
                <div class="arw-loading-text">
                  <h3>正在智能{{ actionLabel }}中</h3>
                  <p>AI 正在为您优化文本，请稍候...</p>
                  <div class="arw-progress">
                    <div class="arw-progress-bar"><div class="arw-progress-fill"></div></div>
                    <div class="arw-progress-text">处理中...</div>
                  </div>
                  <p class="arw-loading-stay">改写很快完成，请勿离开页面</p>
                </div>
              </div>
            </div>
          </div>

          <div class="arw-btn-row">
            <button class="arw-btn arw-btn--ghost" @click="clearParagraph">清空</button>
            <button class="arw-btn" :disabled="loading" @click="startReduction">
              <span v-if="!loading" class="arw-btn-inner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                开始{{ actionLabel }}
              </span>
              <span v-else class="arw-btn-inner"><span class="arw-btn-spinner"></span>{{ actionLabel }}中...</span>
            </button>
          </div>

          <!-- 信息区 -->
          <div class="arw-info">
            <div class="arw-info-card arw-info-card--billing">
              <div class="arw-info-head">
                <span class="arw-info-icon arw-info-icon--billing">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z" fill="currentColor"/></svg>
                </span>
                <h3 class="arw-info-title">计费方式</h3>
              </div>
              <div class="arw-info-body">
                <div class="arw-billing-item">
                  <div class="arw-billing-item-head">
                    <span class="arw-billing-badge arw-billing-badge--char">账户余额</span>
                    <span class="arw-billing-item-label">双余额扣费 · 按千字符</span>
                  </div>
                  <p class="arw-billing-item-desc">段落 / 文档降重统一按<template v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}</template><template v-else>单价</template>/千字符从账户余额双余额扣费，不涉及篇数与包</p>
                </div>
                <div class="arw-billing-rate-table">
                  <div class="arw-billing-rate-title">各类型消耗比例</div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">降AIGC</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">降重</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">双降</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                </div>
                <div class="arw-billing-fallback">
                  <span class="arw-billing-fallback-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  </span>
                  <span class="arw-billing-fallback-text">本次按<b v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}/千字符</b><b v-else>单价</b>双余额扣费：同时扣减本站账户余额与对接方预充值余额（<b>多退少补</b>）</span>
                </div>
              </div>
            </div>

            <div class="arw-info-card arw-info-card--faq">
              <div class="arw-info-head">
                <span class="arw-info-icon arw-info-icon--faq">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <h3 class="arw-info-title">降重更新</h3>
              </div>
              <div class="arw-info-body">
                <div class="arw-faq-list">
                  <p v-if="!faqList.length" class="arw-faq-empty">暂无更新</p>
                  <div
                    v-for="(faq, idx) in faqList"
                    :key="idx"
                    class="arw-faq-item"
                    :class="{ expanded: faq.expanded }"
                  >
                    <div class="arw-faq-question" @click="toggleFaq(idx)">
                      <span class="arw-faq-mark">更</span>
                      <span class="arw-faq-text">{{ faq.title }}</span>
                      <span class="arw-faq-date">{{ faq.date }}</span>
                      <svg class="arw-faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="arw-faq-answer">
                      <p>{{ faq.content }}</p>
                    </div>
                  </div>
                  <div class="arw-updates-sentinel"></div>
                  <p v-if="updatesLoading" class="arw-updates-status">加载中…</p>
                  <p v-else-if="faqList.length && !updatesHasMore" class="arw-updates-status">— 已全部加载 —</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========== 多段落改写 ========== -->
        <div v-show="tab === 'multi'" class="arw-mode">
          <div class="arw-multi-wrap">
            <div class="arw-multi-input-section">
              <textarea
                v-model="multiPasteText"
                class="arw-multi-textarea"
                placeholder="直接输入或粘贴多段落文本，每行视为一个独立段落..."
              ></textarea>
              <div class="arw-multi-input-actions">
                <span class="arw-multi-input-hint">每行一个段落，拆分后可逐个勾选、改写<br>每段需至少 {{ MIN_PARAGRAPH_CHARS }} 字；不足 {{ MIN_PARAGRAPH_CHARS }} 字的段将跳过改写（原文即结果，不扣额度）</span>
                <button class="arw-multi-split-btn" :disabled="!multiPasteText.trim()" @click="splitToParagraphs">拆分为段落</button>
              </div>
            </div>

            <div v-if="multiParagraphs.length > 0" class="arw-multi-list-section">
              <div class="arw-multi-list-header">
                <div class="arw-multi-list-summary">
                  <label class="arw-multi-select-all">
                    <input type="checkbox" :checked="allSelected" @change="toggleSelectAll">
                    <span>全选</span>
                  </label>
                  <span class="arw-multi-stats">共 {{ multiParagraphs.length }} 段 · 已选 {{ selectedCount }} 段 · {{ totalWordCount }} 字</span>
                </div>
                <div class="arw-multi-list-actions">
                  <button class="arw-multi-clear-btn" @click="clearAllParagraphs">清空全部</button>
                </div>
              </div>

              <div class="arw-multi-list">
                <div
                  v-for="(para, idx) in multiParagraphs"
                  :key="para.id"
                  class="arw-multi-item"
                  :class="{ expanded: para.expanded, loading: para.status === 'loading', done: para.status === 'done', failed: para.status === 'failed' }"
                >
                  <div class="arw-multi-item-header" @click="toggleParaExpand(idx)">
                    <label class="arw-multi-checkbox" @click.stop>
                      <input type="checkbox" v-model="para.selected">
                    </label>
                    <span class="arw-multi-index">{{ idx + 1 }}</span>
                    <span class="arw-multi-preview">{{ getParaPreview(para) }}</span>
                    <span class="arw-multi-wordcount">{{ para.original.length }}字</span>
                    <span class="arw-multi-status">
                      <span v-if="para.status === 'pending'" class="arw-multi-status-tag arw-multi-status-tag--pending">待改写</span>
                      <span v-else-if="para.status === 'loading'" class="arw-multi-status-tag arw-multi-status-tag--loading">改写中</span>
                      <span v-else-if="para.status === 'done'" class="arw-multi-status-tag arw-multi-status-tag--done">已完成</span>
                      <span v-else-if="para.status === 'skipped'" class="arw-multi-status-tag arw-multi-status-tag--skipped">过短·跳过</span>
                      <span v-else-if="para.status === 'failed'" class="arw-multi-status-tag arw-multi-status-tag--failed">失败</span>
                    </span>
                    <svg class="arw-multi-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                  <div class="arw-multi-item-body">
                    <div v-if="para.status === 'done'" class="arw-multi-compare">
                      <div class="arw-multi-compare-col">
                        <div class="arw-multi-compare-label">原文</div>
                        <div class="arw-multi-compare-text">{{ para.original }}</div>
                      </div>
                      <div class="arw-multi-compare-col arw-multi-compare-col--result">
                        <div class="arw-multi-compare-label">改写结果</div>
                        <textarea v-if="para.editing" v-model="para.result" class="arw-multi-edit-textarea"></textarea>
                        <div v-else class="arw-multi-compare-text arw-multi-compare-text--result">{{ para.result }}</div>
                        <div class="arw-multi-result-actions">
                          <button class="arw-multi-action-btn arw-multi-action-btn--outline" @click="toggleParaEdit(idx)">{{ para.editing ? '完成编辑' : '修改' }}</button>
                          <button class="arw-multi-action-btn arw-multi-action-btn--copy" :class="{ copied: para.copied }" @click="copyParaResult(idx)">{{ para.copied ? '已复制' : '复制' }}</button>
                        </div>
                      </div>
                    </div>
                    <div v-else class="arw-multi-compare">
                      <div class="arw-multi-compare-col arw-multi-compare-col--full">
                        <div class="arw-multi-compare-label">原文</div>
                        <div class="arw-multi-compare-text">{{ para.original }}</div>
                      </div>
                    </div>
                    <div v-if="para.status === 'pending'" class="arw-multi-item-actions">
                      <button class="arw-multi-action-btn arw-multi-action-btn--primary" @click="rewriteSinglePara(idx)">改写此段</button>
                    </div>
                    <div v-if="para.status === 'failed'" class="arw-multi-item-actions">
                      <span class="arw-multi-error-text">改写失败，请重试</span>
                      <button class="arw-multi-action-btn arw-multi-action-btn--primary" @click="rewriteSinglePara(idx)">重试</button>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="multiIsLoading" class="arw-multi-progress-wrap">
                <div class="arw-multi-progress-info">
                  <span class="arw-multi-progress-label">正在批量改写</span>
                  <span class="arw-multi-progress-stat">{{ multiProgress }}</span>
                </div>
                <div class="arw-multi-progress-bar">
                  <div class="arw-multi-progress-fill" :style="{ width: multiProgressPercent + '%' }"></div>
                </div>
                <div class="arw-multi-progress-stay">改写很快完成，请勿离开页面</div>
              </div>

              <div class="arw-multi-bottom-bar">
                <div class="arw-multi-bottom-left">
                  <button
                    class="arw-btn arw-multi-submit-btn"
                    :disabled="multiIsLoading || selectedCount === 0"
                    @click="startMultiRewrite"
                  >
                    {{ multiIsLoading ? `改写中 (${multiProgress})...` : `全部改写 (${selectedCount}段)` }}
                  </button>
                </div>
                <div v-if="hasAnyResult" class="arw-multi-bottom-right">
                  <button class="arw-multi-global-btn" @click="adoptAll">全部采纳</button>
                  <button class="arw-multi-global-btn" @click="discardAll">全部弃用</button>
                  <button class="arw-multi-global-btn arw-multi-global-btn--merge" @click="mergeAndCopy">合成复制</button>
                </div>
              </div>
            </div>

            <div v-else class="arw-multi-empty">
              <div class="arw-multi-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-3-3v6m-7 4h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
              </div>
              <p>在上方输入多段落文本，点击「拆分为段落」开始</p>
            </div>
          </div>

          <!-- 多段落操作提示 -->
          <div class="arw-info">
            <div class="arw-info-card arw-info-card--faq">
              <div class="arw-info-head">
                <span class="arw-info-icon arw-info-icon--faq">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <h3 class="arw-info-title">操作提示</h3>
              </div>
              <div class="arw-info-body">
                <div class="arw-faq-list">
                  <div
                    v-for="(tip, idx) in multiParaTips"
                    :key="idx"
                    class="arw-faq-item"
                    :class="{ expanded: tip.expanded }"
                  >
                    <div class="arw-faq-question" @click="toggleMultiTip(idx)">
                      <span class="arw-faq-mark">?</span>
                      <span class="arw-faq-text">{{ tip.q }}</span>
                      <svg class="arw-faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="arw-faq-answer">
                      <p>{{ tip.a }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========== 文档降重 ========== -->
        <div v-show="tab === 'document'" class="arw-mode">
          <div class="arw-doc-wrap">
            <div class="arw-doc-header">
              <h3>全文降重</h3>
              <p>仅需上传原文文档（.docx），系统将按全文模式智能处理整篇内容。</p>
            </div>
            <input ref="fileInput" type="file" accept=".docx" hidden @change="onFileChange" />

            <div
              v-if="!docFile"
              class="arw-doc-upload"
              :class="{ 'is-over': dragOver }"
              @click="triggerFileInput"
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="onDrop"
            >
              <div class="arw-doc-upload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15l3-3 3 3"/></svg>
              </div>
              <div class="arw-doc-upload-primary">点击上传原文文档</div>
              <div class="arw-doc-upload-sub">支持 .docx 格式 · 自动统计字数</div>
            </div>

            <div v-else class="arw-doc-file-card" :class="{ 'is-uploading': docUploading, 'is-ready': !docUploading && docWordCount > 0 }">
              <div class="arw-doc-file-thumb">
                <svg v-if="docUploading" class="arw-spin" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13l2 2 4-4"/></svg>
              </div>
              <div class="arw-doc-file-texts">
                <div class="arw-doc-file-title">{{ docFile.name }}</div>
                <div class="arw-doc-file-sub">
                  <span class="arw-doc-file-size">{{ formatFileSize(docFile.size) }}</span>
                  <span v-if="docUploading" class="arw-doc-file-state arw-doc-file-state--loading"><span class="arw-doc-file-dot"></span>正在统计字数...</span>
                  <span v-else-if="docWordCount > 0" class="arw-doc-file-state arw-doc-file-state--ok"><span class="arw-doc-file-dot"></span>{{ docWordCount.toLocaleString() }} 字 · 可开始降重</span>
                  <span v-else class="arw-doc-file-state arw-doc-file-state--wait"><span class="arw-doc-file-dot"></span>等待字数统计</span>
                </div>
              </div>
              <div v-if="docUploading" class="arw-doc-file-progress"><div class="arw-doc-file-progress-bar"></div></div>
              <div v-else class="arw-doc-file-actions">
                <button class="arw-doc-file-action" @click="triggerFileInput">更换</button>
                <button class="arw-doc-file-action is-danger" @click="clearDocFile">删除</button>
              </div>
            </div>

            <div class="arw-doc-submit-row">
              <div class="arw-doc-submit-tip">当前模式：全文降重 · {{ form.platform }} {{ form.family }}</div>
              <button
                class="arw-btn"
                :disabled="docSubmitting || docUploading || !docWordCount"
                @click="submitDocument"
              >
                <span v-if="!docSubmitting" class="arw-btn-inner">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                  提交文档降重
                </span>
                <span v-else class="arw-btn-inner"><span class="arw-btn-spinner"></span>提交中...</span>
              </button>
            </div>
          </div>

          <div class="arw-info">
            <div class="arw-info-card arw-info-card--billing">
              <div class="arw-info-head">
                <span class="arw-info-icon arw-info-icon--billing">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z" fill="currentColor"/></svg>
                </span>
                <h3 class="arw-info-title">计费方式</h3>
              </div>
              <div class="arw-info-body">
                <div class="arw-billing-item">
                  <div class="arw-billing-item-head">
                    <span class="arw-billing-badge arw-billing-badge--char">账户余额</span>
                    <span class="arw-billing-item-label">双余额扣费 · 按千字符</span>
                  </div>
                  <p class="arw-billing-item-desc">段落 / 文档降重统一按<template v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}</template><template v-else>单价</template>/千字符从账户余额双余额扣费，不涉及篇数与包</p>
                </div>
                <div class="arw-billing-rate-table">
                  <div class="arw-billing-rate-title">各类型消耗比例</div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">降AIGC</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">降重</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                  <div class="arw-billing-rate-row">
                    <span class="arw-billing-rate-type">双降</span>
                    <div class="arw-billing-rate-bar-wrap"><div class="arw-billing-rate-bar" style="width:100%"></div></div>
                    <span class="arw-billing-rate-val">×1.0</span>
                  </div>
                </div>
                <div class="arw-billing-fallback">
                  <span class="arw-billing-fallback-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  </span>
                  <span class="arw-billing-fallback-text">本次按<b v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}/千字符</b><b v-else>单价</b>双余额扣费：同时扣减本站账户余额与对接方预充值余额（<b>多退少补</b>）</span>
                </div>
              </div>
            </div>
            <div class="arw-info-card arw-info-card--faq">
              <div class="arw-info-head">
                <span class="arw-info-icon arw-info-icon--faq">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <h3 class="arw-info-title">降重更新</h3>
              </div>
              <div class="arw-info-body">
                <div class="arw-faq-list">
                  <p v-if="!faqList.length" class="arw-faq-empty">暂无更新</p>
                  <div
                    v-for="(faq, idx) in faqList"
                    :key="idx"
                    class="arw-faq-item"
                    :class="{ expanded: faq.expanded }"
                  >
                    <div class="arw-faq-question" @click="toggleFaq(idx)">
                      <span class="arw-faq-mark">更</span>
                      <span class="arw-faq-text">{{ faq.title }}</span>
                      <span class="arw-faq-date">{{ faq.date }}</span>
                      <svg class="arw-faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="arw-faq-answer">
                      <p>{{ faq.content }}</p>
                    </div>
                  </div>
                  <div class="arw-updates-sentinel"></div>
                  <p v-if="updatesLoading" class="arw-updates-status">加载中…</p>
                  <p v-else-if="faqList.length && !updatesHasMore" class="arw-updates-status">— 已全部加载 —</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- 文档降重支付方式弹窗 -->
    <transition name="arw-popup">
      <div v-if="docPayModal" class="arw-modal arw-pay-modal" @click.self="docPayModal = false">
        <div class="arw-modal-box arw-pay-modal-box">
          <div class="arw-modal-head">
            <div class="arw-modal-title-wrap">
              <span class="arw-modal-title">确认支付</span>
              <span class="arw-modal-sub">文档降重 · {{ docWordCount }} 字</span>
            </div>
            <button class="arw-modal-close" @click="docPayModal = false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="arw-modal-body">
            <!-- 当前支付渠道：外部左侧已选择，此处仅展示 -->
            <div class="arw-pay-current" :class="'is-' + docPayMethod">
              <span class="arw-pay-current-icon" :class="docPayMethod">
                <svg v-if="docPayMethod === 'time'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
              </span>
              <span class="arw-pay-current-text">
                <span class="arw-pay-current-label">{{ (docPayOptions.find(o => o.value === docPayMethod) || {}).label || '支付方式' }}</span>
                <span class="arw-pay-current-desc">{{ (docPayOptions.find(o => o.value === docPayMethod) || {}).desc || '' }}</span>
              </span>
              <button class="arw-pay-current-switch" @click="docPayModal = false">切换</button>
            </div>

            <!-- 本次消费预估面板 -->
            <div class="arw-pay-summary" :class="docEstimateInfo.kind">
              <div class="arw-pay-summary-head">
                <span class="arw-pay-summary-title">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                  本次消费预估
                </span>
                <span class="arw-pay-summary-count">
                  {{ docWordCount.toLocaleString() }} 字
                  <em>· {{ docBillableUnits }} 千字符</em>
                </span>
              </div>
              <div class="arw-pay-summary-body">
                <div class="arw-pay-summary-cell arw-pay-summary-cell--consume">
                  <span class="arw-pay-summary-label">本次扣减</span>
                  <span class="arw-pay-summary-value arw-pay-summary-value--consume">{{ docEstimateInfo.consume }}</span>
                  <span class="arw-pay-summary-unit">{{ docEstimateInfo.consumeUnit }}</span>
                </div>
                <div class="arw-pay-summary-arrow">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="arw-pay-summary-cell arw-pay-summary-cell--after">
                  <span class="arw-pay-summary-label">支付后余量</span>
                  <span class="arw-pay-summary-value" :class="{ 'is-insufficient': docEstimateInfo.insufficient }">{{ docEstimateInfo.after }}</span>
                  <span class="arw-pay-summary-unit">当前 {{ docEstimateInfo.current }}</span>
                </div>
              </div>
              <div v-if="docEstimateInfo.warn" class="arw-pay-summary-warn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                {{ docEstimateInfo.warn }}
              </div>
            </div>
          </div>
          <div class="arw-pay-modal-foot">
            <button class="arw-pay-cancel" @click="docPayModal = false">取消</button>
            <button class="arw-pay-confirm" :disabled="docSubmitting || docEstimateInfo.insufficient" @click="confirmDocPay">
              <span v-if="docSubmitting" class="arw-btn-spinner"></span>
              <template v-else>
                {{ docPayMethod === 'time' ? '时长包扣费' : `确认支付 ¥${docEstimate.toFixed(2)}` }}
              </template>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- 降重记录弹窗 -->
    <transition name="arw-popup">
      <div v-if="recordModal" class="arw-modal arw-record-modal" @click.self="closeRecords">
        <div class="arw-modal-box arw-record-box">
          <div class="arw-modal-head">
            <div class="arw-modal-title-wrap">
              <span class="arw-modal-title">改写记录</span>
              <span class="arw-modal-sub">共 {{ recordsTotal }} 条</span>
            </div>
            <button class="arw-modal-close" @click="closeRecords">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="arw-modal-body arw-record-body">
            <div v-if="recordsLoading && records.length === 0" class="arw-record-state">
              <div class="arw-record-spinner"></div>
              <p>正在加载记录...</p>
            </div>
            <div v-else-if="records.length === 0" class="arw-record-state">
              <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" style="opacity:.4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <p class="arw-record-empty-title">暂无改写记录</p>
              <p class="arw-record-hint">开始您的第一次改写吧</p>
            </div>
            <template v-else>
              <div v-for="r in records" :key="r.id" class="arw-record-card">
                <!-- 卡片头：订单号 + 元信息标签 -->
                <div class="arw-rec-card-head">
                  <span class="arw-rec-sn">{{ r.order_sn }}</span>
                  <div class="arw-rec-tags">
                    <span class="arw-rec-tag" :class="'arw-rec-tag--' + r.rewrite_type">{{ r.rewrite_type_text }}</span>
                    <span class="arw-rec-tag arw-rec-tag--platform">{{ r.platform }}</span>
                    <span class="arw-rec-tag arw-rec-tag--pay">{{ r.pay_method_text }}</span>
                  </div>
                </div>
                <!-- 元信息行 -->
                <div class="arw-rec-meta">
                  <div class="arw-rec-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>{{ r.create_time_formatted }}</span>
                  </div>
                  <div class="arw-rec-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                    <span>耗时 {{ r.process_time_text }}</span>
                  </div>
                  <div class="arw-rec-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    <span>{{ Number(r.word_count || 0).toLocaleString() }} → {{ Number(r.answer_word_count || 0).toLocaleString() }} 字</span>
                  </div>
                  <div v-if="Number(r.order_amount) > 0" class="arw-rec-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>¥{{ Number(r.order_amount).toFixed(2) }}</span>
                  </div>
                </div>
                <!-- 原文 -->
                <div class="arw-rec-section">
                  <div class="arw-rec-section-label">
                    <span class="arw-rec-dot arw-rec-dot--orig"></span>
                    原文
                    <span class="arw-rec-section-count">{{ Number(r.word_count || 0).toLocaleString() }} 字</span>
                  </div>
                  <div class="arw-rec-section-text" :class="{ expanded: expandedRecords[r.id + '_content'] }">
                    {{ expandedRecords[r.id + '_content'] ? r.content : r.content_preview }}
                  </div>
                  <button
                    v-if="r.content && r.content.length > 120"
                    class="arw-rec-toggle"
                    @click="toggleExpand(r.id + '_content')"
                  >
                    {{ expandedRecords[r.id + '_content'] ? '收起' : '展开全文' }}
                    <svg :class="{ rotated: expandedRecords[r.id + '_content'] }" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </button>
                </div>
                <!-- 结果 -->
                <div class="arw-rec-section">
                  <div class="arw-rec-section-label">
                    <span class="arw-rec-dot arw-rec-dot--result"></span>
                    改写结果
                    <span class="arw-rec-section-count">{{ Number(r.answer_word_count || 0).toLocaleString() }} 字</span>
                  </div>
                  <div class="arw-rec-section-text arw-rec-section-text--result" :class="{ expanded: expandedRecords[r.id + '_answer'] }">
                    {{ expandedRecords[r.id + '_answer'] ? r.answer : r.answer_preview }}
                  </div>
                  <button
                    v-if="r.answer && r.answer.length > 120"
                    class="arw-rec-toggle"
                    @click="toggleExpand(r.id + '_answer')"
                  >
                    {{ expandedRecords[r.id + '_answer'] ? '收起' : '展开全文' }}
                    <svg :class="{ rotated: expandedRecords[r.id + '_answer'] }" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </button>
                </div>
              </div>
            </template>
          </div>
          <!-- 分页 -->
          <div v-if="records.length > 0" class="arw-rec-footer">
            <button class="arw-rec-page-btn" :disabled="recordsPage <= 1" @click="changeRecordsPage(recordsPage - 1)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              上一页
            </button>
            <span class="arw-rec-page-info">{{ recordsPage }} / {{ recordsTotalPages }}</span>
            <button class="arw-rec-page-btn" :disabled="recordsPage >= recordsTotalPages" @click="changeRecordsPage(recordsPage + 1)">
              下一页
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- 文档提交成功弹窗 -->
    <transition name="arw-popup">
      <div v-if="docSuccessModal" class="arw-modal arw-success-modal" @click.self="docSuccessModal = false">
        <div class="arw-modal-box arw-success-box">
          <div class="arw-success-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <h3 class="arw-success-title">文档提交成功</h3>
          <p class="arw-success-desc">您的文档已成功提交，系统正在处理中</p>
          <p class="arw-success-hint">您可以在订单中心查看处理进度</p>
          <div class="arw-success-actions">
            <button class="arw-success-btn arw-success-btn--secondary" @click="docSuccessModal = false">继续上传</button>
            <NuxtLink to="/pc/orders/jiangchong" class="arw-success-btn arw-success-btn--primary" @click="docSuccessModal = false">
              前往订单中心
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </NuxtLink>
          </div>
        </div>
      </div>
    </transition>
  </ToolShell>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'

definePageMeta({ layout: 'console' })

useSeoMeta({
  title: 'AI降重 - AI写作助手',
  description: '段落降重、多段落改写与文档降重，降低 AIGC 率与重复率，支持多平台多模式。',
})

const api = useApi()
const toast = useToast()

const tabs = [
  { key: 'paragraph', label: '段落降重' },
  { key: 'multi', label: '多段落改写' },
  { key: 'document', label: '文档降重' },
]
const tab = ref('paragraph')
// H5（m 壳）原文/结果 Tab 切换：<640 原文与降重结果上下两个文本框过长，收成单屏 Tab；
// 样式由 .m-main 门控，PC 双栏与 console 窄窗口不受影响（与 ai-check 同款手法）
const mPane = ref('input')

const typeOptions = [
  { value: 'aigc', label: '降低AIGC率' },
  { value: 'repeat', label: '降低重复率' },
  { value: 'both', label: '双降模式' },
]
// 目标平台/模式 = 检测工具配置。全部由后端 /api/jiangchong/config 的 languages 驱动
// languages: {zh|en: [{value,label,recommend,types}]}；types 按任务类型(aigc/dedup/both)拆分模式列表，
// 前端「模式」区随改写类型切换刷新；缺省规则：dedup/both 复用 aigc（双降=先降重后降AI，模式跟随降AIGC）
// desc 按平台可不同；加平台/改描述只改后端 tools.php，前端零 build；接口失败时用内置默认兜底
const defaultPlatformConfig = {
  languages: {
    zh: [
      { value: 'PaperPass', label: 'PaperPass', recommend: true, types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }, { value: 'B', label: '保数模式', desc: '数字/术语保留最强', recommend: false }], dedup: [{ value: 'A', label: '标准模式', desc: '综合最佳，降重效果好', recommend: true }] } },
      { value: '知网', label: '知网', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '维普', label: '维普', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: 'PaperYY', label: 'PaperYY', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }, { value: 'B', label: '保数模式', desc: '数字/术语保留最强', recommend: false }], dedup: [{ value: 'A', label: '标准模式', desc: '综合最佳，降重效果好', recommend: true }] } },
      { value: '万方', label: '万方', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '大雅', label: '大雅', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '朱雀', label: '朱雀', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '格子达个人版', label: '格子达个人版', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '格子达学院版', label: '格子达学院版', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '笔杆网', label: '笔杆网', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '华宸', label: '华宸', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
      { value: '其他', label: '其他', types: { aigc: [{ value: 'A', label: '标准模式', desc: '综合最佳，降AI率高', recommend: true }] } },
    ],
    en: [
      { value: 'Turnitin', label: 'Turnitin', recommend: true, types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: 'ZeroGPT', label: 'ZeroGPT', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: '知网', label: '知网', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: '维普', label: '维普', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: '格子达个人版', label: '格子达个人版', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: '格子达学院版', label: '格子达学院版', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
      { value: '其他', label: '其他', types: { aigc: [{ value: 'EN', label: '英文模式', desc: '英文改写，保留数据', recommend: true }] } },
    ],
  },
}
const platformConfig = ref(JSON.parse(JSON.stringify(defaultPlatformConfig)))
const platformLang = ref('zh') // zh=中文检测 / en=英文检测
const visiblePlatformOptions = computed(() => (platformConfig.value.languages && platformConfig.value.languages[platformLang.value]) || [])
// 最短降重字数阈值：过短文本模型会自由发挥导致胡编/严重扩写
const MIN_PARAGRAPH_CHARS = 20
const normKey = (p) => String(p || '').trim().toLowerCase()
// 当前平台可用族列表 = 该平台 types 按任务类型取族（缺省复用 aigc；兼容旧 family 字段）
const visibleFamilyOptions = computed(() => {
  const pl = normKey(form.platform)
  const cur = ((platformConfig.value.languages && platformConfig.value.languages[platformLang.value]) || []).find(o => normKey(o.value) === pl)
  if (!cur) return []
  const types = (cur && typeof cur.types === 'object' && cur.types) || {}
  const typeKey = form.type === 'repeat' ? 'dedup' : form.type // repeat(降重) 对应用户配置的 dedup 键
  const list = types[typeKey] || types.aigc || (Array.isArray(cur.family) ? cur.family : [])
  return (Array.isArray(list) ? list : []).map(o => ({ ...o }))
})

const actionLabel = computed(() => {
  const map = { repeat: '降重', aigc: '降AIGC', both: '双降' }
  return map[form.type] || '降重'
})

const noticeText = computed(() => {
  if (tab.value === 'paragraph') return '📝 段落降重模式：输入文本，AI 将为您智能改写，降低重复率和 AIGC 检测率'
  if (tab.value === 'multi') return '📑 多段落改写模式：批量管理多个段落，逐段审阅改写结果，支持分段采纳或弃用'
  return '📄 文档降重模式：上传论文文档，系统将自动处理整篇文档的降重工作'
})

// 段落降重表单
const form = reactive({
  text: '',
  type: 'aigc',
  platform: 'PaperPass',
  family: 'A',
})
// 平台/类型/语言/族配置变化时，确保 form.family 是当前可选族里的一项（推荐族优先）
watch([() => form.platform, () => form.type, platformLang, platformConfig], () => {
  const list = visibleFamilyOptions.value
  if (list.length && !list.some(o => o.value === form.family)) {
    form.family = (list.find(o => o.recommend) || list[0]).value
  }
})
// 切换语言 tab: 切到该语言合法平台；family 交给上方 watch 按配置自动校正
watch(platformLang, (lang) => {
  const opts = (platformConfig.value.languages && platformConfig.value.languages[lang]) || []
  if (!opts.some(o => o.value === form.platform)) {
    const rec = opts.find(o => o.recommend) || opts[0]
    if (rec) form.platform = rec.value
  }
})
const textLen = computed(() => form.text.length)
const result = ref('')
const loading = ref(false)
const copied = ref(false)

const docFile = ref(null)
const docWordCount = ref(0)
const docUploading = ref(false)
const docSubmitting = ref(false)
const docSuccessModal = ref(false)
const docOssKey = ref('')
const dragOver = ref(false)
const fileInput = ref(null)

// 钱包
const wallet = ref(null)
const walletMoney = computed(() => Number(wallet.value?.user_money || 0))
const walletChar = computed(() => Number(wallet.value?.char_balance || 0))
// 单价（后端 admin 配置后由 wallet 接口下发，未下发时为 0 表示未启用按单价扣费）
const pricePerChar = computed(() => Number(wallet.value?.char_price || 0))
const pricePerDoc = computed(() => Number(wallet.value?.doc_price || 0))
// 计费模式：free=免计费 / paid=已启用按单价扣费
const billingMode = computed(() => wallet.value?.mode || 'free')

// 时长包状态
const timeActive = computed(() => Boolean(wallet.value?.time_active))
const timeBalanceSec = computed(() => Number(wallet.value?.time_balance_sec || 0))
const timeHourUsed = computed(() => Number(wallet.value?.time_hour_used_chars || 0))
const timeHourLimit = computed(() => Number(wallet.value?.time_hour_limit || 0))
const timeHourResetIn = computed(() => Number(wallet.value?.time_hour_reset_in || 0))

// 配额使用情况（多时间窗口，hover 钱包"配额"角标时展示）
const quotaList = ref([])
const quotaError = ref(false)
const quotaHover = ref(false)
async function fetchQuota() {
  const res = await api.get('/api/jiangchong/quota')
  // 仅展示时长包（>1分钟）窗口，分钟级窗口不展示
  quotaList.value = (res.ok && Array.isArray(res.data) ? res.data : []).filter(q => (q.key || '') !== '1m')
  quotaError.value = !res.ok
}

// 打开配额弹层时实时刷新，保证 hover 信息总是最新
function openQuota() {
  quotaHover.value = true
  fetchQuota()
}

// 时长剩余展示（秒 → "3小时25分钟" / "2天5小时"）
function formatTimeRemain(sec) {
  const s = Number(sec) || 0
  if (s <= 0) return '0分钟'
  const day = 86400
  const hour = 3600
  const minute = 60
  const days = Math.floor(s / day)
  const hours = Math.floor((s % day) / hour)
  const minutes = Math.floor((s % hour) / minute)
  if (days > 0) return days + '天' + (hours > 0 ? hours + '小时' : '')
  if (hours > 0) return hours + '小时' + (minutes > 0 ? minutes + '分钟' : '')
  return Math.max(1, minutes) + '分钟'
}

// 预估扣费（与后端 calcAmount 对齐：不满 1000 字按 1000 字计算，再乘千字符单价）
function calcEstimate(words) {
  const w = Number(words) || 0
  if (billingMode.value !== 'paid' || !pricePerChar.value || w <= 0) return 0
  // 千字符单位：不满 1000 字按 1000 字计算（向上取整到千字符单位）
  const billableUnits = Math.ceil(w / 1000)
  // 金额 = 千字符单位 × 单价，转为分向上取整避免浮点误差
  const fen = Math.ceil(billableUnits * pricePerChar.value * 100)
  return fen / 100
}
// 文档降重预估（基于已解析文档字数）
const docEstimate = computed(() => calcEstimate(docWordCount.value))
// 段落 / 多段落 支付方式：余额（双余额扣费）+ 时长包（主站套餐免金额扣费）
const payMethod = ref('balance')
const payMethodOptions = computed(() => {
  const balanceOpt = {
    value: 'balance',
    label: '余额支付',
    desc: billingMode.value === 'paid' ? `¥${pricePerChar.value.toFixed(2)}/千字符 · 双余额扣费` : `按单价 · 双余额扣费`,
  }
  const timeOpt = {
    value: 'time',
    label: '时长包',
    desc: timeActive.value ? `免余额双扣 · 剩余约 ${formatTimeRemain(timeBalanceSec.value)}` : '需有效时长包 · 免余额双扣',
  }
  // 时长包未过期时排第一位（配合 fetchWallet 默认选中）
  return timeActive.value ? [timeOpt, balanceOpt] : [balanceOpt, timeOpt]
})

// 文档降重支付弹窗
const docPayModal = ref(false)
const docPayMethod = ref('balance') // 余额（双余额扣费）/ 时长包（主站套餐免金额扣费）
const docPayOptions = computed(() => {
  const balanceOpt = {
    value: 'balance',
    label: '账户余额',
    desc: billingMode.value === 'paid' ? `余额 ¥${walletMoney.value.toFixed(2)} · ¥${pricePerChar.value.toFixed(2)}/千字符 双余额扣费` : `余额 ¥${walletMoney.value.toFixed(2)} · 双余额扣费`,
    disabled: false,
  }
  const timeOpt = {
    value: 'time',
    label: '时长包',
    desc: timeActive.value ? `免余额双扣 · 剩余约 ${formatTimeRemain(timeBalanceSec.value)}` : '需有效时长包 · 免余额双扣',
    disabled: false,
  }
  // 时长包未过期时排第一位（配合 fetchWallet 默认选中）
  return timeActive.value ? [timeOpt, balanceOpt] : [balanceOpt, timeOpt]
})

const docPayMethodTouched = ref(false)
const chooseDocPayMethod = (v) => {
  docPayMethodTouched.value = true
  docPayMethod.value = v
}

// 文档支付弹窗：本次消费预估（余额=双余额扣费；时长包=主站套餐免金额扣费）
const docBillableUnits = computed(() => Math.max(1, Math.ceil((docWordCount.value || 0) / 1000)))
const docEstimateInfo = computed(() => {
  if (docPayMethod.value === 'time') {
    return {
      kind: 'time',
      consume: '时长包',
      consumeUnit: `${docBillableUnits.value} 千字符`,
      current: timeActive.value ? formatTimeRemain(timeBalanceSec.value) : '—',
      after: '有效期内不扣余额',
      insufficient: false,
      warn: '',
    }
  }
  const amount = docEstimate.value
  const afterMoney = Math.max(0, walletMoney.value - amount)
  return {
    kind: 'money',
    consume: `¥${amount.toFixed(2)}`,
    consumeUnit: `${docBillableUnits.value} 千字符`,
    current: `¥${walletMoney.value.toFixed(2)}`,
    after: `¥${afterMoney.toFixed(2)}`,
    insufficient: walletMoney.value < amount,
    warn: walletMoney.value < amount ? '余额不足，请充值' : '',
  }
})

// 降重更新(前台加载后台维护的降重系统更新情况, 标题即平台名)
// 排序: 发布时间由近到远; 展示: 无限滚动加载
const faqList = ref([])
const updatesPage = ref(1)
const updatesPageSize = 10
const updatesHasMore = ref(false)
const updatesLoading = ref(false)
let updatesObserver = null

function formatDate(ts) {
  if (!ts) return ''
  const d = new Date(Number(ts) * 1000)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

async function loadUpdates(reset = false) {
  if (updatesLoading.value) return
  if (!reset && !updatesHasMore.value && faqList.value.length) return
  updatesLoading.value = true
  try {
    const pageNo = reset ? 1 : updatesPage.value
    const res = await api.get('/api/jiangchong/updates', {
      page_no: pageNo,
      page_size: updatesPageSize,
    })
    if (res.ok && res.data && Array.isArray(res.data.list)) {
      const mapped = res.data.list.map(it => ({
        title: it.title || '',
        content: it.content || '',
        date: formatDate(it.create_time),
        expanded: false,
      }))
      faqList.value = reset ? mapped : faqList.value.concat(mapped)
      updatesPage.value = pageNo + 1
      updatesHasMore.value = !!res.data.has_more
    } else {
      updatesHasMore.value = false
    }
  } catch (e) {
    updatesHasMore.value = false
  } finally {
    updatesLoading.value = false
  }
}

function initUpdatesObserver() {
  updatesObserver = new IntersectionObserver((entries) => {
    if (entries.some(e => e.isIntersecting)) {
      loadUpdates()
    }
  }, { rootMargin: '100px' })
  document.querySelectorAll('.arw-updates-sentinel').forEach(el => updatesObserver.observe(el))
}

function toggleFaq(idx) {
  if (faqList.value[idx]) faqList.value[idx].expanded = !faqList.value[idx].expanded
}

onMounted(() => {
  loadUpdates(true)
  initUpdatesObserver()
})

// 多段落操作提示
const multiParaTips = ref([
  { q: '如何拆分段落？', a: '将多段落文本粘贴或输入到上方文本框，每行视为一个段落，点击「拆分为段落」即可自动分段。', expanded: false },
  { q: '如何选择改写的段落？', a: '拆分后每个段落前都有复选框，默认全选。您可以取消不需要改写的段落，然后点击「全部改写」批量处理。', expanded: false },
  { q: '改写后如何查看结果？', a: '改写完成后点击段落可展开审阅视图，左侧显示原文，右侧显示改写结果。可逐段选择「修改」「复制」。', expanded: false },
  { q: '如何导出最终结果？', a: '审阅完毕后，点击「合成复制」按钮，系统会将所有改写结果按顺序拼接，一键复制到剪贴板。', expanded: false },
])
function toggleMultiTip(idx) { multiParaTips.value[idx].expanded = !multiParaTips.value[idx].expanded }

// ============ 多段落改写 ============
const multiPasteText = ref('')
const multiParagraphs = ref([])
const multiIsLoading = ref(false)
let multiParaIdCounter = 0

const allSelected = computed(() => {
  if (multiParagraphs.value.length === 0) return false
  return multiParagraphs.value.every(p => p.selected)
})
const selectedCount = computed(() => multiParagraphs.value.filter(p => p.selected).length)
const totalWordCount = computed(() => multiParagraphs.value.reduce((sum, p) => sum + p.original.length, 0))
const hasAnyResult = computed(() => multiParagraphs.value.some(p => p.status === 'done'))
const multiProgress = computed(() => {
  const done = multiParagraphs.value.filter(p => p.status === 'done' || p.status === 'failed').length
  const total = multiParagraphs.value.filter(p => p.selected).length
  return `${done}/${total}`
})
const multiProgressPercent = computed(() => {
  const selected = multiParagraphs.value.filter(p => p.selected)
  if (selected.length === 0) return 0
  const done = selected.filter(p => p.status === 'done' || p.status === 'failed').length
  return Math.round((done / selected.length) * 100)
})

function splitToParagraphs() {
  const text = multiPasteText.value
  if (!text.trim()) return
  const paragraphs = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').map(p => p.trim()).filter(p => p.length > 0)
  if (paragraphs.length === 0) return
  multiParagraphs.value = paragraphs.map(p => ({
    id: ++multiParaIdCounter,
    original: p,
    result: '',
    status: 'pending',
    selected: true,
    expanded: false,
    editing: false,
    copied: false,
  }))
  multiPasteText.value = ''
}
function clearAllParagraphs() { multiParagraphs.value = [] }
function toggleSelectAll() {
  const newVal = !allSelected.value
  multiParagraphs.value.forEach(p => { p.selected = newVal })
}
function getParaPreview(para) {
  const text = para.status === 'done' && para.result ? para.result : para.original
  const preview = text.slice(0, 60)
  return preview.length < text.length ? preview + '…' : preview
}
function toggleParaExpand(idx) { multiParagraphs.value[idx].expanded = !multiParagraphs.value[idx].expanded }
function toggleParaEdit(idx) { multiParagraphs.value[idx].editing = !multiParagraphs.value[idx].editing }
async function copyParaResult(idx) {
  const para = multiParagraphs.value[idx]
  if (!para.result) return
  try { await navigator.clipboard.writeText(para.result) }
  catch (e) {
    const ta = document.createElement('textarea')
    ta.value = para.result
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  para.copied = true
  toast.success('已复制到剪贴板')
  setTimeout(() => { para.copied = false }, 1800)
}

async function rewriteSinglePara(idx) {
  const para = multiParagraphs.value[idx]
  if (!para) return
  // 短段落不调 API：原文即结果（过短文本模型会胡编），且不扣额度
  if (para.original.trim().length < MIN_PARAGRAPH_CHARS) {
    para.result = para.original
    para.status = 'skipped'
    return
  }
  para.status = 'loading'
  try {
    const res = await api.post('/api/jiangchong/adjc', {
      sentence: para.original,
      rewrite_type: form.type,
      platform: form.platform,
      language: platformLang.value === 'en' ? 'en' : 'zh',
      family: form.family,
      pay_method: payMethod.value,
    })
    if (res.ok && res.data && res.data.answer) {
      para.result = res.data.answer
      para.status = 'done'
      para.expanded = true
      fetchWallet()
      fetchQuota()
    } else {
      para.status = 'failed'
      toast.error(res.msg || '改写失败，请稍后重试')
    }
  } catch (e) {
    para.status = 'failed'
    toast.error('改写失败，请稍后重试')
  }
}

async function startMultiRewrite() {
  const selected = multiParagraphs.value.filter(p => p.selected && p.status === 'pending')
  if (selected.length === 0) {
    toast.warning('没有待改写的段落')
    return
  }
  multiIsLoading.value = true
  // 控制并发，避免一次性打满
  const concurrency = 3
  const queue = [...selected]
  const runners = []
  async function run() {
    while (queue.length) {
      const p = queue.shift()
      const idx = multiParagraphs.value.indexOf(p)
      if (idx >= 0) await rewriteSinglePara(idx)
    }
  }
  for (let i = 0; i < Math.min(concurrency, selected.length); i++) runners.push(run())
  await Promise.allSettled(runners)
  multiIsLoading.value = false
  fetchWallet()
  fetchQuota()
  toast.success('批量改写完成')
}

function adoptAll() { multiParagraphs.value.forEach(p => { if (p.status === 'done') p.expanded = true }) }
function discardAll() { multiParagraphs.value.forEach(p => { if (p.status === 'done') p.expanded = false }) }
async function mergeAndCopy() {
  const merged = multiParagraphs.value.map(p => p.status === 'done' && p.result ? p.result : p.original).join('\n\n')
  if (!merged) return
  try { await navigator.clipboard.writeText(merged) }
  catch (e) {
    const ta = document.createElement('textarea')
    ta.value = merged
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  toast.success('已合成并复制到剪贴板')
}

// ============ 钱包 ============
let payMethodInit = false // 首次拿到钱包数据时按时长包状态设置默认支付方式（仅一次，不覆盖用户手动选择）
async function fetchWallet() {
  const res = await api.get('/api/jiangchong/wallet')
  if (res.ok) {
    wallet.value = res.data
    if (!payMethodInit) {
      payMethodInit = true
      // 时长包未过期时默认选择时长包支付渠道
      if (timeActive.value) {
        payMethod.value = 'time'
        if (!docPayMethodTouched.value) docPayMethod.value = 'time'
      }
    }
  }
}

// ============ 段落降重 ============
async function startReduction() {
  if (!form.text.trim()) {
    toast.warning('请输入需要降重的文本')
    return
  }
  if (form.text.trim().length < MIN_PARAGRAPH_CHARS) {
    toast.warning(`内容过短，请至少输入 ${MIN_PARAGRAPH_CHARS} 字`)
    return
  }
  loading.value = true
  result.value = ''
  // H5：提交后切到结果页看进度（PC 双栏无此需要，状态变更无副作用）
  mPane.value = 'result'
  try {
    const res = await api.post('/api/jiangchong/adjc', {
      sentence: form.text,
      rewrite_type: form.type,
      platform: form.platform,
      language: platformLang.value === 'en' ? 'en' : 'zh',
      family: form.family,
      pay_method: payMethod.value,
    })
    if (res.ok && res.data && res.data.answer) {
      result.value = res.data.answer
      toast.success('降重完成')
      fetchWallet()
      fetchQuota()
    } else {
      toast.error(res.msg || '降重失败，请稍后重试')
    }
  } catch (e) {
    toast.error('降重失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

function clearParagraph() {
  form.text = ''
  result.value = ''
  // H5：清空后回到原文输入页（m 壳 Tab；PC 双栏该状态无副作用）
  mPane.value = 'input'
}

async function copyResult() {
  if (!result.value) return
  try { await navigator.clipboard.writeText(result.value) }
  catch (e) {
    const ta = document.createElement('textarea')
    ta.value = result.value
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  copied.value = true
  toast.success('已复制到剪贴板')
  setTimeout(() => { copied.value = false }, 1800)
}

// ============ 文档降重：OSS 预授权直传 ============
function triggerFileInput() {
  if (docUploading.value) return
  fileInput.value && fileInput.value.click()
}
function onFileChange(e) {
  const f = e.target.files && e.target.files[0]
  if (f) handleDocFile(f)
  e.target.value = ''
}
function onDrop(e) {
  dragOver.value = false
  const f = e.dataTransfer.files && e.dataTransfer.files[0]
  if (!f) return
  const ext = f.name.split('.').pop().toLowerCase()
  if (ext !== 'docx') {
    toast.warning('请上传 .docx 文件')
    return
  }
  handleDocFile(f)
}
async function handleDocFile(file) {
  const ext = file.name.split('.').pop().toLowerCase()
  if (ext !== 'docx') {
    toast.warning('请上传 .docx 文件')
    return
  }
  docFile.value = file
  docWordCount.value = 0
  docOssKey.value = ''
  docUploading.value = true
  try {
    const presignRes = await api.post('/api/check/presign', { ext: 'docx' })
    if (!presignRes.ok || !presignRes.data || !presignRes.data.put_url) {
      toast.error(presignRes.msg || '直传凭证获取失败')
      docUploading.value = false
      return
    }
    const { put_url, key, content_type } = presignRes.data
    await $fetch(put_url, {
      method: 'PUT',
      body: file,
      headers: { 'Content-Type': content_type || 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
    })
    docOssKey.value = key
    const wcRes = await api.post('/api/check/wordcount', { oss_key: key })
    if (wcRes.ok && wcRes.data) {
      const wc = Number(wcRes.data.wordcount || wcRes.data.wordCount || 0)
      if (wc > 0) {
        docWordCount.value = wc
      } else {
        toast.error('未能统计文档字数，请更换文件重试')
      }
    } else {
      toast.error(wcRes.msg || '字数统计失败')
    }
  } catch (e) {
    toast.error('文档上传失败，请重试')
  } finally {
    docUploading.value = false
  }
}
function clearDocFile() {
  docFile.value = null
  docWordCount.value = 0
  docOssKey.value = ''
}
async function submitDocument() {
  if (!docFile.value) {
    toast.warning('请先上传论文文档（.docx）')
    return
  }
  if (!docOssKey.value || docWordCount.value <= 0) {
    toast.warning('请等待文档字数统计完成后再提交')
    return
  }
  // 弹出支付方式确认弹窗（渠道已在左侧选择，此处仅确认）
  // 若当前渠道已不可用则回退到第一个可用渠道
  const cur = docPayOptions.value.find(o => o.value === docPayMethod.value)
  if (!cur || cur.disabled) {
    const firstAvailable = docPayOptions.value.find(o => !o.disabled)
    docPayMethod.value = firstAvailable ? firstAvailable.value : 'balance'
  }
  docPayModal.value = true
}
async function confirmDocPay() {
  if (docSubmitting.value) return
  docPayModal.value = false
  docSubmitting.value = true
  try {
    const res = await api.post('/api/check/create_jcorder', {
      type: form.type,
      platform: form.platform,
      family: form.family,
      word_count: docWordCount.value,
      oss_key: docOssKey.value,
      title: docFile.value.name.replace(/\.docx$/i, ''),
      pay_method: docPayMethod.value,
    })
    if (res.ok) {
      clearDocFile()
      fetchWallet()
      docSuccessModal.value = true
    } else {
      toast.error(res.msg || '提交失败，请稍后重试')
    }
  } catch (e) {
    toast.error('提交失败，请稍后重试')
  } finally {
    docSubmitting.value = false
  }
}
function formatFileSize(bytes) {
  if (!bytes || Number(bytes) <= 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`
}

// ============ 降重记录 ============
const recordModal = ref(false)
const records = ref([])
const recordsLoading = ref(false)
const recordsPage = ref(1)
const recordsTotal = ref(0)
const recordsTotalPages = ref(0)
const recordPageSize = 5
const expandedRecords = reactive({})

async function openRecords() {
  recordModal.value = true
  recordsPage.value = 1
  records.value = []
  recordsTotal.value = 0
  recordsTotalPages.value = 0
  Object.keys(expandedRecords).forEach(k => delete expandedRecords[k])
  await loadRecords()
}
function closeRecords() { recordModal.value = false }
async function loadRecords() {
  recordsLoading.value = true
  try {
    const res = await api.get('/api/jiangchong/records', { pageNo: recordsPage.value, pageSize: recordPageSize })
    if (res.ok && res.data) {
      records.value = res.data.lists || res.data.data || []
      recordsTotal.value = Number(res.data.count || 0)
      recordsTotalPages.value = Math.ceil(recordsTotal.value / recordPageSize) || 0
    }
  } catch (e) {
    // ignore
  } finally {
    recordsLoading.value = false
  }
}
function changeRecordsPage(page) {
  if (page < 1 || page > recordsTotalPages.value || page === recordsPage.value) return
  recordsPage.value = page
  Object.keys(expandedRecords).forEach(k => delete expandedRecords[k])
  loadRecords()
}
function toggleExpand(key) {
  expandedRecords[key] = !expandedRecords[key]
}

onMounted(async () => {
  fetchWallet()
  fetchQuota()
  // 拉取平台配置（languages），失败则保留内置默认
      try {
        const cfg = await api.get('/api/jiangchong/config')
        if (cfg && cfg.data && cfg.data.languages && Object.keys(cfg.data.languages).length) {
          platformConfig.value = { languages: cfg.data.languages }
        }
      } catch (e) { /* 忽略，保持默认 */ }
})
</script>

<style scoped>
/* ============================================================
   青绿 teal 主题 · 对齐本项目工具页风格（实色面板 + CSS 变量）
   ============================================================ */
.arw-layout {
  position: relative;
  display: flex;
  gap: 14px;
  max-width: 1280px;
  margin: 0 auto;
  align-items: flex-start;
  padding: 4px;
}

/* ============ 左侧选项面板 ============ */
.arw-options {
  flex: 0 0 320px;
  min-width: 290px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  position: sticky;
  top: 12px;
}

/* 余额卡片 */
.arw-wallet {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-radius: 12px;
  padding: 14px;
  color: #fff;
  box-shadow: 0 6px 18px rgba(13, 148, 136, 0.22), 0 2px 4px rgba(13, 148, 136, 0.12);
}
.arw-wallet-head {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
.arw-wallet-icon {
  width: 26px;
  height: 26px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.arw-wallet-icon svg { width: 16px; height: 16px; color: #fff; }
.arw-wallet-title { font-size: 13px; font-weight: 600; color: rgba(255, 255, 255, 0.95); }
.arw-wallet-mode {
  margin-left: auto;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.92);
  letter-spacing: 0.02em;
}
.arw-wallet-mode.free { background: rgba(255, 255, 255, 0.18); }
.arw-wallet-mode.paid { background: rgba(255, 255, 255, 0.28); }
/* 配额角标：hover 展示配额信息（暖色实心、显眼） */
.arw-wallet-mode--quota {
  position: relative;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  padding: 3px 11px;
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #7c2d12;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.22);
  transition: all 0.2s ease;
}
.arw-wallet-mode--quota:hover {
  color: #7c2d12;
  background: linear-gradient(135deg, #fcd34d, #fbbf24);
  box-shadow: 0 0 12px rgba(251, 191, 36, 0.6), 0 2px 8px rgba(0, 0, 0, 0.18);
  transform: translateY(-1px);
}
.arw-wallet-body {
  background: rgba(255, 255, 255, 0.14);
  border-radius: 9px;
  padding: 9px 12px;
  margin-bottom: 10px;
}
/* 配额 hover 弹层 */
.arw-quota-pop {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 30;
  width: 250px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18), 0 4px 12px rgba(15, 23, 42, 0.08);
  color: var(--dark-800);
}
.arw-quota-pop::before {
  content: '';
  position: absolute;
  top: -5px;
  right: 14px;
  width: 10px;
  height: 10px;
  background: #fff;
  border-left: 1px solid var(--gray-100);
  border-top: 1px solid var(--gray-100);
  transform: rotate(45deg);
}
.arw-quota-pop-head {
  margin-bottom: 9px;
}
.arw-quota-pop-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--dark-900);
}
.arw-quota-pop-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.arw-quota-pop-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}
.arw-quota-pop-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--dark-800);
}
.arw-quota-pop-used {
  font-size: 10.5px;
  color: var(--gray-500);
  font-variant-numeric: tabular-nums;
}
.arw-quota-pop-bar {
  height: 5px;
  border-radius: 99px;
  background: rgba(20, 184, 166, 0.12);
  overflow: hidden;
}
.arw-quota-pop-fill {
  display: block;
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, #14b8a6, #0d9488);
}
.arw-quota-pop-fill.is-over {
  background: linear-gradient(90deg, #f87171, #ef4444);
}
.arw-quota-pop-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 3px;
}
.arw-quota-pop-pct {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--dark-900);
  font-variant-numeric: tabular-nums;
}
.arw-quota-pop-pct.is-over {
  color: #ef4444;
}
.arw-quota-pop-remain {
  font-size: 10px;
  color: var(--gray-500);
  font-variant-numeric: tabular-nums;
}
.arw-quota-pop-empty {
  font-size: 11px;
  color: var(--gray-500);
  text-align: center;
  padding: 6px 0;
}
.quota-pop-enter-active,
.quota-pop-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.quota-pop-enter-from,
.quota-pop-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
.arw-wallet-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}
.arw-wallet-row + .arw-wallet-row {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 6px;
  margin-top: 2px;
}
/* 千字符单价行：置顶高亮，半透明白底突出计费单价 */
.arw-wallet-row--price {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  padding: 7px 10px;
  margin-bottom: 4px;
}
.arw-wallet-row--price.free { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.10); }
.arw-wallet-row--price + .arw-wallet-row { border-top: none; padding-top: 4px; margin-top: 0; }
/* 单价卡居于余额行下方时，保持边框色一致并留出间距 */
.arw-wallet-row + .arw-wallet-row--price { margin-top: 5px; border-top-color: rgba(255, 255, 255, 0.18); }
.arw-wallet-row--price .arw-wallet-value { font-size: 16px; font-weight: 800; }
.arw-wallet-row--price .arw-wallet-value em { font-size: 10px; }
/* 时长包：独立磁贴行，居中对齐展示剩余时长 */
.arw-wallet-row--packs {
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  gap: 0;
  padding: 7px 0 3px;
}
.arw-wallet-pack {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 0 6px;
  text-align: center;
}
.arw-wallet-pack .arw-wallet-label { align-items: center; }
.arw-wallet-label { font-size: 11.5px; color: rgba(255, 255, 255, 0.85); display: flex; flex-direction: column; gap: 1px; }
.arw-wallet-sub { font-size: 9.5px; color: rgba(255, 255, 255, 0.55); font-weight: 500; }
.arw-wallet-value { font-size: 13.5px; font-weight: 700; color: #fff; font-variant-numeric: tabular-nums; }
.arw-wallet-value em { font-style: normal; font-size: 10px; font-weight: 600; color: rgba(255, 255, 255, 0.6); margin-left: 2px; }
.arw-wallet-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 9px;
  background: rgba(255, 255, 255, 0.95);
  border: none;
  border-radius: 8px;
  color: #0d9488;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-wallet-btn:hover { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12); }

/* 配置卡 */
.arw-config-card {
  background: var(--white);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.arw-config-card:hover {
  border-color: rgba(20, 184, 166, 0.25);
  box-shadow: 0 4px 16px rgba(13, 148, 136, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03);
}
.arw-config-title {
  font-size: 11.5px;
  color: var(--gray-500);
  letter-spacing: 0.06em;
  margin: 0 0 10px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}


/* 胶囊按钮组 */
.arw-chips { display: flex; gap: 7px; flex-wrap: wrap; }
.arw-chips--seg { display: grid; grid-template-columns: repeat(3, 1fr); }
.arw-chip {
  padding: 5px 11px;
  border-radius: 18px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid var(--gray-200);
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--white);
  color: var(--gray-600);
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
}
.arw-chip:hover { background: var(--gray-50); border-color: rgba(20, 184, 166, 0.4); color: var(--dark-700); }
.arw-chip.disabled { cursor: not-allowed; background: var(--gray-50); color: var(--gray-400); border-color: var(--gray-200); box-shadow: none; }
.arw-chip--pay.disabled { position: relative; }
.arw-chip--pay.disabled::after {
  content: attr(data-tip);
  position: absolute;
  bottom: calc(100% + 7px);
  left: 50%;
  transform: translateX(-50%);
  background: #111827;
  color: #fff;
  font-size: 11px;
  line-height: 1.4;
  padding: 5px 10px;
  border-radius: 6px;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s ease;
  z-index: 30;
}
.arw-chip--pay.disabled:hover::after { opacity: 1; }
.arw-chip--pay.disabled:hover { border-color: var(--gray-200); color: var(--gray-400); background: var(--gray-50); }
.arw-chip.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-color: #0d9488;
  color: #fff;
  box-shadow: 0 2px 10px rgba(13, 148, 136, 0.22);
}
/* 支付方式 chip：橙色显眼，与改写类型等 teal chip 区分 */
.arw-langtab {
  display: inline-flex;
  gap: 3px;
  padding: 3px;
  margin-bottom: 12px;
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 10px;
}
.arw-langtab button {
  padding: 5px 18px; font-size: 12.5px; line-height: 1.5;
  border: none; border-radius: 8px;
  background: transparent; color: var(--gray-500); cursor: pointer;
  font-family: inherit; font-weight: 500; letter-spacing: 0.5px;
  transition: color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
}
.arw-langtab button:hover { color: #0f766e; }
.arw-langtab button.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff; font-weight: 600;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.28);
}
.arw-chip--pay:hover:not(.active) { border-color: rgba(249, 115, 22, 0.45); color: #c2410c; background: rgba(249, 115, 22, 0.04); }
.arw-chip--pay.active {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  border-color: #ea580c;
  color: #fff;
  box-shadow: 0 2px 10px rgba(249, 115, 22, 0.3), 0 0 0 1px rgba(249, 115, 22, 0.12);
}
.arw-chip--platform .arw-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-left: 5px;
  padding: 1px 6px;
  font-size: 10px;
  font-weight: 700;
  line-height: 1.2;
  color: #fff;
  background: linear-gradient(135deg, #fb923c, #f97316);
  border-radius: 999px;
  box-shadow: 0 1px 3px rgba(249, 115, 22, 0.35);
}
.arw-chip--platform.active .arw-tag { background: rgba(255, 255, 255, 0.95); color: #0d9488; box-shadow: none; }

/* 模式选择：两列等宽卡片(名称+特点)，避免单个按钮独占整行 */
.arw-mode-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 7px;
}
.arw-chip--mode {
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 2px;
  white-space: normal;
  border-radius: 10px;
  padding: 7px 10px;
  line-height: 1.3;
}
.arw-chip--mode .arw-chip-name { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; }
.arw-chip--mode .arw-chip-desc { font-size: 10px; font-weight: 500; opacity: 0.78; line-height: 1.3; }

/* ============ 右侧内容面板 ============ */
.arw-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* 模式提示横幅 */
.arw-notice {
  width: 100%;
  border-radius: 9px;
  padding: 6px 12px;
  overflow: hidden;
  box-sizing: border-box;
  border: 1px solid rgba(20, 184, 166, 0.2);
  background: linear-gradient(90deg, rgba(20, 184, 166, 0.08) 0%, rgba(20, 184, 166, 0.02) 100%);
}
.arw-notice-track { width: 100%; overflow: hidden; white-space: nowrap; }
.arw-notice-text {
  display: inline-block;
  font-size: 11.5px;
  font-weight: 600;
  color: #0d9488;
  padding-left: 100%;
  animation: arw-notice-scroll 24s linear infinite;
}
@keyframes arw-notice-scroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-100%); }
}

/* 工具栏 + 模式 tab */
.arw-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2px;
  min-height: 38px;
}
/* H5（m 壳）原文/结果切换条：基类恒隐藏，≤640 且 .m-main 祖先下启用（与 ai-check .aic-m-tabs 同款） */
.arw-m-tabs { display: none; }
.arw-tabs {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  border-radius: 999px;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  width: fit-content;
}
.arw-tab {
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--gray-500);
  padding: 6px 16px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-tab:hover:not(.active) { color: #0d9488; background: rgba(20, 184, 166, 0.06); }
.arw-tab.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 2px 10px rgba(13, 148, 136, 0.22);
}
.arw-records-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid #d1fae5;
  background: #f0fdfa;
  color: #0d9488;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-records-btn:hover {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 10px rgba(13, 148, 136, 0.25);
}

/* 模式区 */
.arw-mode { position: relative; display: flex; flex-direction: column; gap: 12px; }

/* ============ 段落降重：文本双列 ============ */
.arw-text-columns {
  display: flex;
  gap: 12px;
  align-items: stretch;
  position: relative;
}
.arw-panel {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: var(--white);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.arw-panel:hover {
  border-color: rgba(20, 184, 166, 0.25);
  box-shadow: 0 4px 16px rgba(13, 148, 136, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03);
}
.arw-panel-title {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 10px;
  padding-bottom: 9px;
  border-bottom: 1px solid var(--gray-100);
  font-weight: 600;
  color: var(--dark-900);
  font-size: 13.5px;
}
.arw-panel-icon { color: #14b8a6; display: flex; align-items: center; }
.arw-panel-icon svg { width: 16px; height: 16px; }
.arw-input-wrap { position: relative; width: 100%; flex: 1; display: flex; flex-direction: column; }
.arw-textarea {
  width: 100%;
  min-height: 340px;
  padding: 11px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  resize: none;
  font-size: 13px;
  line-height: 1.7;
  color: var(--dark-800);
  background: #fcfdff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  font-family: inherit;
  box-sizing: border-box;
  outline: none;
}
.arw-textarea:focus {
  border-color: #14b8a6;
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14);
}
.arw-textarea::placeholder { color: var(--gray-400); font-size: 12.5px; }
.arw-wordcount {
  position: absolute;
  bottom: 8px;
  right: 10px;
  font-size: 11px;
  color: var(--gray-400);
  background: rgba(255, 255, 255, 0.9);
  padding: 3px 9px;
  border-radius: 6px;
  pointer-events: none;
  font-weight: 600;
  border: 1px solid var(--gray-100);
}

/* 结果面板 */
.arw-result-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding-bottom: 9px;
  padding-left: 11px;
  border-bottom: 1px solid var(--gray-100);
  position: relative;
}
.arw-result-head::before {
  content: '';
  position: absolute;
  left: 0;
  top: 1px;
  bottom: 11px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%);
}
.arw-result-head h3 { margin: 0; font-size: 13.5px; font-weight: 600; color: var(--dark-900); }
.arw-copy-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  border: none;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(13, 148, 136, 0.18);
  transition: all 0.2s ease;
}
.arw-copy-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 14px rgba(13, 148, 136, 0.28); }
.arw-copy-btn.copied { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); }
.arw-result-content { flex: 1; position: relative; min-height: 340px; }
.arw-result-text {
  width: 100%;
  min-height: 340px;
  max-height: 520px;
  overflow-y: auto;
  padding: 11px 12px;
  font-size: 13px;
  line-height: 1.7;
  color: var(--dark-800);
  white-space: pre-wrap;
  word-break: break-word;
  background: #fcfdff;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  box-sizing: border-box;
}
.arw-result-text::-webkit-scrollbar { width: 7px; }
.arw-result-text::-webkit-scrollbar-thumb { background: rgba(20, 184, 166, 0.22); border-radius: 99px; }
.arw-result-text::-webkit-scrollbar-track { background: rgba(20, 184, 166, 0.04); border-radius: 99px; }
.arw-result-text.is-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gray-50);
}
.arw-result-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  font-size: 11.5px;
  color: var(--gray-400);
  text-align: center;
}
.arw-result-placeholder svg { color: rgba(20, 184, 166, 0.35); animation: arw-float 3s ease-in-out infinite; }
@keyframes arw-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }

/* ============ 按钮 ============ */
.arw-btn-row { display: flex; justify-content: center; gap: 10px; margin: 2px 0; }
.arw-btn {
  border: none;
  color: #fff;
  padding: 10px 30px;
  border-radius: 50px;
  cursor: pointer;
  font-size: 13.5px;
  font-weight: 600;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.28);
  letter-spacing: 0.3px;
  position: relative;
  overflow: hidden;
  outline: none;
  user-select: none;
}
.arw-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(13, 148, 136, 0.4); }
.arw-btn:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: 0 2px 8px rgba(13, 148, 136, 0.15); transform: none; }
.arw-btn--ghost {
  background: var(--white);
  color: var(--gray-500);
  border: 1px solid var(--gray-200);
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}
.arw-btn--ghost:hover:not(:disabled) { background: var(--white); color: #0d9488; border-color: rgba(20, 184, 166, 0.4); box-shadow: 0 4px 10px rgba(13, 148, 136, 0.1); }
.arw-btn-inner { display: inline-flex; align-items: center; gap: 6px; position: relative; z-index: 1; }
.arw-btn-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: arw-spin 0.7s linear infinite;
}
@keyframes arw-spin { to { transform: rotate(360deg); } }

/* ============ 段落降重 loading 遮罩 ============ */
.arw-loading {
  position: absolute;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(248, 250, 252, 0.38);
  backdrop-filter: blur(7px);
  -webkit-backdrop-filter: blur(7px);
  border-radius: 10px;
  z-index: 5;
}
.arw-loading.show { display: flex; }
.arw-loading-box {
  background: var(--white);
  border-radius: 16px;
  padding: 26px 24px;
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.14), 0 6px 14px rgba(15, 23, 42, 0.06);
  max-width: 340px;
  width: calc(100% - 40px);
  text-align: center;
  border: 1px solid rgba(20, 184, 166, 0.12);
  position: relative;
}
.arw-loading-box::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 16px;
  background: radial-gradient(120% 80% at 50% 0%, rgba(20, 184, 166, 0.08), transparent 60%);
  pointer-events: none;
}
.arw-loading-spinner { position: relative; width: 84px; height: 84px; margin: 0 auto 18px; }
.arw-loading-ring { width: 84px; height: 84px; animation: arw-spin 1.4s cubic-bezier(0.5, 0, 0.5, 1) infinite; }
.arw-loading-ring-fill { animation: arw-ring-dash 2s ease-in-out infinite; transform-origin: center; }
@keyframes arw-ring-dash { 0%, 100% { stroke-dasharray: 80 234; } 50% { stroke-dasharray: 160 234; } }
.arw-loading-icon {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  animation: arw-icon-pulse 2s ease-in-out infinite;
  color: #0d9488;
}
@keyframes arw-icon-pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.08); opacity: 0.8; } }
.arw-loading-text h3 { margin: 0 0 8px; font-size: 14.5px; font-weight: 700; color: var(--dark-900); }
.arw-loading-text p { margin: 0 0 14px; color: var(--gray-500); font-size: 12.5px; line-height: 1.6; }
.arw-progress { margin-top: 2px; }
.arw-progress-bar { height: 5px; background: rgba(20, 184, 166, 0.1); border-radius: 99px; overflow: hidden; margin-bottom: 7px; }
.arw-progress-fill {
  height: 100%; width: 40%;
  background: linear-gradient(90deg, #0d9488, #14b8a6, #5eead4);
  border-radius: 99px;
  box-shadow: 0 0 10px rgba(13, 148, 136, 0.35);
  animation: arw-progress-move 2.4s ease-in-out infinite;
}
@keyframes arw-progress-move { 0% { margin-left: -40%; } 100% { margin-left: 100%; } }
.arw-progress-text { font-size: 11.5px; color: var(--gray-400); font-weight: 600; letter-spacing: 0.04em; }
.arw-loading-stay { margin: 12px 0 0; font-size: 11.5px; color: var(--gray-400); }

/* ============ 多段落改写 ============ */
.arw-multi-wrap {
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  background: var(--white);
  padding: 14px;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
}
.arw-multi-input-section { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.arw-multi-textarea {
  width: 100%;
  height: 150px;
  max-height: 150px;
  padding: 11px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  resize: none;
  overflow-y: auto;
  font-size: 13px;
  line-height: 1.7;
  color: var(--dark-800);
  background: #fcfdff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  font-family: inherit;
  box-sizing: border-box;
  outline: none;
}
.arw-multi-textarea:focus { border-color: #14b8a6; background: var(--white); box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14); }
.arw-multi-textarea::placeholder { color: var(--gray-400); font-size: 12.5px; }
.arw-multi-input-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.arw-multi-input-hint { font-size: 11.5px; color: var(--gray-400); }
.arw-multi-split-btn {
  border: none;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  border-radius: 8px;
  padding: 7px 16px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 10px rgba(13, 148, 136, 0.18);
  white-space: nowrap;
}
.arw-multi-split-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 14px rgba(13, 148, 136, 0.28); }
.arw-multi-split-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.arw-multi-list-section { margin-top: 14px; display: flex; flex-direction: column; max-height: 620px; min-height: 0; }
.arw-multi-list-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 12px;
  background: var(--gray-50);
  border-radius: 8px;
  margin-bottom: 9px;
  border: 1px solid var(--gray-100);
  gap: 10px;
  flex-shrink: 0;
}
.arw-multi-list-summary { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; flex-wrap: wrap; }
.arw-multi-select-all { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12.5px; color: var(--gray-600); font-weight: 600; user-select: none; }
.arw-multi-select-all input[type="checkbox"] { width: 14px; height: 14px; accent-color: #0d9488; cursor: pointer; }
.arw-multi-stats { font-size: 11.5px; color: var(--gray-400); }
.arw-multi-list-actions { flex-shrink: 0; }
.arw-multi-clear-btn {
  border: 1px solid var(--gray-200);
  background: var(--white);
  color: var(--gray-400);
  border-radius: 7px;
  padding: 4px 11px;
  font-size: 11.5px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-multi-clear-btn:hover { color: #ef4444; border-color: #fecaca; background: #fef2f2; }

.arw-multi-list { display: flex; flex-direction: column; gap: 7px; flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding-right: 3px; }
.arw-multi-list::-webkit-scrollbar { width: 7px; }
.arw-multi-list::-webkit-scrollbar-thumb { background: rgba(20, 184, 166, 0.22); border-radius: 99px; }
.arw-multi-list::-webkit-scrollbar-track { background: rgba(20, 184, 166, 0.04); border-radius: 99px; }

.arw-multi-item {
  border: 1px solid var(--gray-100);
  border-radius: 9px;
  background: var(--white);
  transition: all 0.25s ease;
  flex-shrink: 0;
}
.arw-multi-item:hover { border-color: rgba(20, 184, 166, 0.25); }
.arw-multi-item.expanded { border-color: rgba(20, 184, 166, 0.3); box-shadow: 0 2px 10px rgba(13, 148, 136, 0.06); }
.arw-multi-item.expanded .arw-multi-chevron { transform: rotate(180deg); }
.arw-multi-item.loading { border-color: rgba(20, 184, 166, 0.35); background: rgba(20, 184, 166, 0.03); }
.arw-multi-item.done { border-color: rgba(34, 197, 94, 0.22); }
.arw-multi-item.failed { border-color: rgba(239, 68, 68, 0.22); background: rgba(254, 226, 226, 0.12); }
.arw-multi-item-header { display: flex; align-items: center; gap: 9px; padding: 9px 12px; cursor: pointer; user-select: none; transition: background 0.15s ease; }
.arw-multi-item-header:hover { background: rgba(20, 184, 166, 0.03); }
.arw-multi-checkbox { display: inline-flex; align-items: center; flex-shrink: 0; }
.arw-multi-checkbox input[type="checkbox"] { width: 14px; height: 14px; accent-color: #0d9488; cursor: pointer; }
.arw-multi-index { width: 22px; height: 22px; border-radius: 6px; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.arw-multi-preview { flex: 1; min-width: 0; font-size: 12.5px; color: var(--dark-700); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; }
.arw-multi-wordcount { font-size: 10.5px; color: var(--gray-400); flex-shrink: 0; }
.arw-multi-status { flex-shrink: 0; }
.arw-multi-status-tag { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 600; }
.arw-multi-status-tag--pending { background: rgba(148, 163, 184, 0.12); color: var(--gray-500); }
.arw-multi-status-tag--loading { background: rgba(20, 184, 166, 0.14); color: #0d9488; animation: arw-pulse 1.5s ease-in-out infinite; }
.arw-multi-status-tag--done { background: rgba(34, 197, 94, 0.14); color: #16a34a; }
.arw-multi-status-tag--skipped { background: rgba(245, 158, 11, 0.14); color: #d97706; }
.arw-multi-status-tag--failed { background: rgba(239, 68, 68, 0.14); color: #dc2626; }
@keyframes arw-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.arw-multi-chevron { width: 15px; height: 15px; color: var(--gray-400); flex-shrink: 0; transition: transform 0.3s ease; }

.arw-multi-item-body { padding: 0 12px 12px; }
.arw-multi-compare { display: flex; gap: 10px; }
.arw-multi-compare-col { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.arw-multi-compare-col--full { flex: 1; }
.arw-multi-compare-label { font-size: 10.5px; font-weight: 700; color: var(--gray-400); margin-bottom: 5px; letter-spacing: 0.04em; }
.arw-multi-compare-text { font-size: 12.5px; line-height: 1.65; color: var(--gray-600); padding: 9px 11px; background: var(--gray-50); border-radius: 7px; border: 1px solid var(--gray-100); white-space: pre-wrap; word-break: break-word; }
.arw-multi-compare-col--result { flex: 1; }
.arw-multi-compare-text--result { color: var(--dark-800); font-weight: 500; background: rgba(34, 197, 94, 0.04); border-color: rgba(34, 197, 94, 0.14); }
.arw-multi-edit-textarea { width: 100%; min-height: 90px; padding: 9px 11px; font-size: 12.5px; line-height: 1.65; color: var(--dark-800); border: 1px solid rgba(20, 184, 166, 0.25); border-radius: 7px; resize: vertical; outline: none; font-family: inherit; box-sizing: border-box; }
.arw-multi-edit-textarea:focus { border-color: rgba(20, 184, 166, 0.45); box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1); }
.arw-multi-result-actions { display: flex; gap: 7px; margin-top: 7px; }
.arw-multi-action-btn { padding: 4px 11px; border-radius: 7px; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; }
.arw-multi-action-btn--outline { background: var(--white); color: #0d9488; border-color: rgba(20, 184, 166, 0.3); }
.arw-multi-action-btn--outline:hover { background: rgba(20, 184, 166, 0.06); border-color: rgba(20, 184, 166, 0.45); }
.arw-multi-action-btn--copy { background: rgba(20, 184, 166, 0.08); color: #0d9488; }
.arw-multi-action-btn--copy:hover { background: rgba(20, 184, 166, 0.14); }
.arw-multi-action-btn--copy.copied { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.arw-multi-action-btn--primary { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; box-shadow: 0 2px 9px rgba(13, 148, 136, 0.18); }
.arw-multi-action-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 3px 13px rgba(13, 148, 136, 0.28); }
.arw-multi-item-actions { display: flex; align-items: center; gap: 9px; margin-top: 9px; }
.arw-multi-error-text { font-size: 11.5px; color: #dc2626; font-weight: 600; }

.arw-multi-progress-wrap { margin-top: 10px; padding: 10px 12px; background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 8px; }
.arw-multi-progress-info { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; }
.arw-multi-progress-label { font-size: 12px; font-weight: 600; color: #0d9488; }
.arw-multi-progress-stat { font-size: 12px; font-weight: 700; color: #0d9488; font-variant-numeric: tabular-nums; }
.arw-multi-progress-bar { height: 5px; background: rgba(20, 184, 166, 0.1); border-radius: 99px; overflow: hidden; margin-bottom: 7px; }
.arw-multi-progress-fill { height: 100%; background: linear-gradient(90deg, #0d9488, #14b8a6); border-radius: 99px; transition: width 0.4s ease; box-shadow: 0 0 6px rgba(13, 148, 136, 0.3); }
.arw-multi-progress-stay { font-size: 11px; color: var(--gray-400); }

.arw-multi-bottom-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--gray-100); flex-wrap: wrap; }
.arw-multi-submit-btn { padding: 9px 26px; font-size: 13px; }
.arw-multi-bottom-right { display: flex; gap: 7px; flex-wrap: wrap; }
.arw-multi-global-btn { border: 1px solid rgba(20, 184, 166, 0.3); background: var(--white); color: #0d9488; border-radius: 8px; padding: 8px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.arw-multi-global-btn:hover { background: rgba(20, 184, 166, 0.06); border-color: rgba(20, 184, 166, 0.45); transform: translateY(-1px); }
.arw-multi-global-btn--merge { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; border: none; box-shadow: 0 2px 10px rgba(13, 148, 136, 0.2); }
.arw-multi-global-btn--merge:hover { box-shadow: 0 3px 14px rgba(13, 148, 136, 0.3); }

.arw-multi-empty { padding: 40px 0; text-align: center; color: var(--gray-400); font-size: 12.5px; }
.arw-multi-empty-icon { display: flex; justify-content: center; margin-bottom: 10px; }
.arw-multi-empty-icon svg { width: 38px; height: 38px; color: rgba(20, 184, 166, 0.35); }

/* ============ 文档降重 ============ */
.arw-doc-wrap {
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  background: var(--white);
  padding: 14px;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
}
.arw-doc-header { margin-bottom: 12px; }
.arw-doc-header h3 { margin: 0 0 6px; font-size: 16px; color: var(--dark-900); font-weight: 700; }
.arw-doc-header p { margin: 0; font-size: 12.5px; color: var(--gray-500); line-height: 1.6; }
.arw-doc-upload {
  border: 1.5px dashed rgba(20, 184, 166, 0.4);
  border-radius: 10px;
  min-height: 160px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 18px;
  cursor: pointer;
  background: var(--gray-50);
  color: #0d9488;
  transition: all 0.2s ease;
  margin-bottom: 12px;
}
.arw-doc-upload:hover { border-color: #14b8a6; background: rgba(20, 184, 166, 0.04); transform: translateY(-1px); }
.arw-doc-upload.is-over { border-color: #0d9488; border-style: solid; background: rgba(20, 184, 166, 0.06); box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1); }
.arw-doc-upload-icon { width: 44px; height: 44px; border-radius: 11px; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; box-shadow: 0 5px 14px rgba(13, 148, 136, 0.25); }
.arw-doc-upload-icon svg { width: 23px; height: 23px; }
.arw-doc-upload-primary { font-size: 14px; font-weight: 700; color: var(--dark-900); }
.arw-doc-upload-sub { font-size: 12px; color: var(--gray-400); margin-top: 4px; }

.arw-doc-file-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; min-height: 160px; background: var(--white); border: 1px solid var(--gray-200); border-radius: 10px; transition: all 0.2s; box-shadow: 0 3px 10px rgba(15, 23, 42, 0.03); position: relative; overflow: hidden; margin-bottom: 12px; }
.arw-doc-file-card.is-uploading { border-color: rgba(20, 184, 166, 0.35); }
.arw-doc-file-card.is-ready { border-color: rgba(34, 197, 94, 0.35); }
.arw-doc-file-thumb { display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, rgba(20, 184, 166, 0.12) 0%, rgba(13, 148, 136, 0.06) 100%); border: 1px solid rgba(20, 184, 166, 0.12); color: #0d9488; flex-shrink: 0; }
.arw-doc-file-card.is-ready .arw-doc-file-thumb { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); border-color: transparent; color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
.arw-doc-file-card.is-uploading .arw-doc-file-thumb { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); border-color: transparent; color: #fff; }
.arw-doc-file-texts { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 3px; }
.arw-doc-file-title { font-size: 12.5px; font-weight: 700; color: var(--dark-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.arw-doc-file-sub { font-size: 11px; color: var(--gray-500); display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.arw-doc-file-size { color: var(--gray-500); font-variant-numeric: tabular-nums; font-weight: 700; padding: 2px 8px; background: rgba(20, 184, 166, 0.06); border: 1px solid rgba(20, 184, 166, 0.1); border-radius: 999px; font-size: 10px; }
.arw-doc-file-state { display: inline-flex; align-items: center; gap: 5px; font-weight: 700; font-size: 10.5px; }
.arw-doc-file-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; box-shadow: 0 0 6px currentColor; }
.arw-doc-file-state--loading { color: #0d9488; }
.arw-doc-file-state--loading .arw-doc-file-dot { animation: arw-pulse 1.2s ease-in-out infinite; }
.arw-doc-file-state--ok { color: #059669; }
.arw-doc-file-state--wait { color: var(--gray-400); }
.arw-spin { animation: arw-spin 0.9s linear infinite; }
.arw-doc-file-progress { width: 90px; height: 4px; background: rgba(20, 184, 166, 0.12); border-radius: 2px; overflow: hidden; position: relative; flex-shrink: 0; }
.arw-doc-file-progress-bar { position: absolute; top: 0; left: 0; height: 100%; width: 40%; background: linear-gradient(90deg, #14b8a6 0%, #0d9488 50%, #14b8a6 100%); background-size: 200% 100%; border-radius: 2px; animation: arw-progress-move 1.4s ease-in-out infinite; box-shadow: 0 0 8px rgba(13, 148, 136, 0.4); }
.arw-doc-file-actions { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.arw-doc-file-action { padding: 5px 12px; background: var(--white); border: 1px solid var(--gray-200); border-radius: 999px; font-size: 11px; font-weight: 600; color: var(--gray-500); cursor: pointer; transition: all 0.18s ease; }
.arw-doc-file-action:hover { border-color: rgba(20, 184, 166, 0.35); color: #0d9488; background: rgba(20, 184, 166, 0.04); transform: translateY(-1px); }
.arw-doc-file-action.is-danger:hover { border-color: rgba(239, 68, 68, 0.35); color: #dc2626; background: rgba(239, 68, 68, 0.04); }

.arw-doc-submit-row { border-top: 1px solid var(--gray-100); padding-top: 12px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.arw-doc-submit-tip { font-size: 11.5px; color: #0d9488; font-weight: 600; background: rgba(20, 184, 166, 0.08); border: 1px solid rgba(20, 184, 166, 0.2); border-radius: 999px; padding: 5px 12px; }

/* ============ 信息区 ============ */
.arw-info { display: flex; gap: 12px; margin-top: 2px; }
.arw-info-card { flex: 1; min-width: 0; background: var(--white); border-radius: 10px; border: 1px solid var(--gray-100); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02); overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
.arw-info-card:hover { border-color: rgba(20, 184, 166, 0.25); box-shadow: 0 4px 16px rgba(13, 148, 136, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03); }
.arw-info-head { display: flex; align-items: center; gap: 9px; padding: 12px 14px 10px; border-bottom: 1px solid var(--gray-100); }
.arw-info-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.arw-info-icon svg { width: 17px; height: 17px; }
.arw-info-icon--billing { background: linear-gradient(135deg, rgba(20, 184, 166, 0.15), rgba(13, 148, 136, 0.1)); color: #0d9488; }
.arw-info-icon--faq { background: linear-gradient(135deg, rgba(20, 184, 166, 0.15), rgba(13, 148, 136, 0.1)); color: #0d9488; }
.arw-info-title { font-size: 13.5px; font-weight: 700; color: var(--dark-900); margin: 0; }
.arw-info-body { padding: 11px 14px 13px; }

/* 计费项：纵向堆叠，避免平铺网格 */
.arw-billing-item { padding: 9px 11px; background: var(--gray-50); border-radius: 8px; border: 1px solid var(--gray-100); margin-bottom: 7px; }
.arw-billing-item:last-of-type { margin-bottom: 10px; }
.arw-billing-item-head { display: flex; align-items: center; gap: 7px; margin-bottom: 3px; }
.arw-billing-badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 999px; font-size: 10px; font-weight: 700; color: #fff; letter-spacing: 0.02em; }
.arw-billing-badge--char { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.arw-billing-badge--doc { background: linear-gradient(135deg, #fb923c, #f97316); }
.arw-billing-item-label { font-size: 11px; font-weight: 600; color: var(--gray-500); }
.arw-billing-item-desc { margin: 0; font-size: 11px; line-height: 1.55; color: var(--gray-400); }

/* 消耗比例表：带进度条的可视化呈现 */
.arw-billing-rate-table { background: var(--gray-50); border-radius: 8px; padding: 9px 11px; border: 1px solid var(--gray-100); margin-bottom: 10px; }
.arw-billing-rate-title { font-size: 10.5px; font-weight: 600; color: var(--gray-400); margin-bottom: 7px; letter-spacing: 0.04em; }
.arw-billing-rate-row { display: flex; align-items: center; gap: 9px; margin-bottom: 6px; }
.arw-billing-rate-row:last-child { margin-bottom: 0; }
.arw-billing-rate-type { font-size: 11.5px; font-weight: 600; color: var(--gray-600); width: 46px; flex-shrink: 0; }
.arw-billing-rate-bar-wrap { flex: 1; height: 5px; background: rgba(20, 184, 166, 0.1); border-radius: 99px; overflow: hidden; }
.arw-billing-rate-bar { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #14b8a6, #0d9488); transition: width 0.4s ease; }
.arw-billing-rate-val { font-size: 11px; font-weight: 700; color: #0d9488; width: 32px; text-align: right; flex-shrink: 0; font-variant-numeric: tabular-nums; }

/* 兜底规则 */
.arw-billing-fallback { display: flex; align-items: center; gap: 7px; padding: 8px 11px; background: linear-gradient(90deg, rgba(20, 184, 166, 0.07), rgba(20, 184, 166, 0.02)); border: 1px solid rgba(20, 184, 166, 0.18); border-radius: 8px; }
.arw-billing-fallback-icon { display: flex; align-items: center; justify-content: center; color: #0d9488; flex-shrink: 0; }
.arw-billing-fallback-text { font-size: 11px; color: var(--gray-600); line-height: 1.5; }
.arw-billing-fallback-text b { color: #0d9488; font-weight: 700; }

/* FAQ */
.arw-faq-list { display: flex; flex-direction: column; }
.arw-faq-item { border: 1px solid transparent; border-radius: 9px; overflow: hidden; transition: all 0.25s ease; }
.arw-faq-item + .arw-faq-item { margin-top: 2px; }
.arw-faq-item:hover { background: rgba(20, 184, 166, 0.03); }
.arw-faq-item.expanded { background: rgba(20, 184, 166, 0.04); border-color: rgba(20, 184, 166, 0.12); }
.arw-faq-item.expanded .arw-faq-chevron { transform: rotate(180deg); }
.arw-faq-item.expanded .arw-faq-mark { background: #0d9488; color: #fff; }
.arw-faq-question { display: flex; align-items: flex-start; gap: 9px; padding: 9px 11px; cursor: pointer; user-select: none; }
.arw-faq-question:active { background: rgba(20, 184, 166, 0.05); }
.arw-faq-mark { width: 20px; height: 20px; border-radius: 6px; background: rgba(20, 184, 166, 0.12); color: #0d9488; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.25s ease; }
.arw-faq-text { flex: 1; font-size: 12.5px; font-weight: 500; color: var(--dark-700); line-height: 1.5; }
.arw-faq-date { font-size: 11px; font-weight: 500; color: var(--gray-400); font-variant-numeric: tabular-nums; flex-shrink: 0; margin-top: 1px; }
.arw-faq-chevron { width: 15px; height: 15px; color: var(--gray-400); flex-shrink: 0; margin-top: 1px; transition: transform 0.3s ease; }
.arw-faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.3s ease; }
.arw-faq-answer p { margin: 0; padding: 0 11px 0 41px; font-size: 11.5px; line-height: 1.65; color: var(--gray-500); white-space: pre-line; word-break: break-word; }
.arw-faq-item.expanded .arw-faq-answer { max-height: 220px; padding-bottom: 10px; }
.arw-faq-empty { padding: 14px 11px 8px; font-size: 11.5px; color: var(--gray-400); text-align: center; }
.arw-updates-sentinel { height: 1px; }
.arw-updates-status { margin: 0; padding: 8px 0 4px; text-align: center; font-size: 11px; color: var(--gray-400); }

/* ============ 降重记录弹窗 ============ */
.arw-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.arw-modal-box { width: 100%; max-width: 560px; max-height: 80vh; background: var(--white); border: 1px solid var(--gray-100); border-radius: 14px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22), 0 8px 16px rgba(15, 23, 42, 0.08); position: relative; }
.arw-modal-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #14b8a6 0%, #0d9488 30%, #5eead4 50%, #0d9488 70%, transparent 100%); box-shadow: 0 0 10px rgba(20, 184, 166, 0.4); }
.arw-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 13px 18px; background: linear-gradient(145deg, var(--white) 0%, var(--gray-50) 100%); border-bottom: 1px solid var(--gray-100); }
.arw-modal-title-wrap { display: flex; align-items: baseline; gap: 8px; }
.arw-modal-title { font-size: 14px; font-weight: 800; color: var(--dark-900); letter-spacing: -0.01em; }
.arw-modal-sub { font-size: 11px; color: #0d9488; font-weight: 700; }
.arw-modal-close { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid var(--gray-200); background: var(--white); border-radius: 8px; color: var(--gray-500); cursor: pointer; transition: all 0.2s ease; }
.arw-modal-close:hover { background: rgba(20, 184, 166, 0.06); border-color: rgba(20, 184, 166, 0.3); color: #0d9488; transform: rotate(90deg); }
.arw-modal-body { flex: 1; overflow-y: auto; padding: 6px 18px; }
.arw-modal-body::-webkit-scrollbar { width: 6px; }
.arw-modal-body::-webkit-scrollbar-thumb { background: rgba(20, 184, 166, 0.25); border-radius: 3px; }
.arw-record-state { padding: 48px 0; text-align: center; color: var(--gray-400); font-size: 12.5px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.arw-record-empty-title { font-size: 13.5px; font-weight: 700; color: var(--gray-600); margin: 4px 0 0; }
.arw-record-hint { margin: 2px 0 0; font-size: 11.5px; }
.arw-record-spinner { width: 24px; height: 24px; border: 2.5px solid rgba(20, 184, 166, 0.14); border-top-color: #0d9488; border-right-color: #14b8a6; border-radius: 50%; animation: arw-spin 0.7s linear infinite; }
.arw-record-spinner--sm { width: 13px; height: 13px; border-width: 2px; }
.arw-popup-enter-active, .arw-popup-leave-active { transition: opacity 0.25s ease; }
.arw-popup-enter-from, .arw-popup-leave-to { opacity: 0; }

/* === 记录弹窗：加宽 + 卡片式 === */
.arw-record-modal .arw-modal-box { max-width: 720px; max-height: 85vh; }
.arw-record-body { padding: 8px 16px; display: flex; flex-direction: column; gap: 10px; }

/* 记录卡片 */
.arw-record-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  background: #fff;
  transition: all 0.18s ease;
}
.arw-record-card:hover { border-color: #99f6e4; box-shadow: 0 2px 12px rgba(20, 184, 166, 0.08); }

/* 卡片头 */
.arw-rec-card-head {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; flex-wrap: wrap; margin-bottom: 8px;
}
.arw-rec-sn {
  font-family: 'SF Mono', 'Consolas', 'Monaco', monospace;
  font-size: 11px; font-weight: 700; color: #0d9488;
  letter-spacing: 0.02em;
}
.arw-rec-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.arw-rec-tag {
  display: inline-flex; align-items: center;
  padding: 2px 8px; border-radius: 999px;
  font-size: 10.5px; font-weight: 700; white-space: nowrap;
}
.arw-rec-tag--aigc { background: rgba(168, 85, 247, 0.1); color: #7c3aed; }
.arw-rec-tag--repeat { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.arw-rec-tag--both { background: rgba(20, 184, 166, 0.1); color: #0d9488; }
.arw-rec-tag--platform { background: rgba(100, 116, 139, 0.1); color: #475569; }
.arw-rec-tag--pay { background: rgba(245, 158, 11, 0.1); color: #b45309; }

/* 元信息行 */
.arw-rec-meta {
  display: flex; flex-wrap: wrap; gap: 12px;
  padding: 6px 0; margin-bottom: 4px;
  border-bottom: 1px dashed #f1f5f9;
}
.arw-rec-meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; color: #64748b; font-weight: 500;
}
.arw-rec-meta-item svg { color: #94a3b8; }

/* 内容区 */
.arw-rec-section { margin-top: 8px; }
.arw-rec-section-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 700; color: #475569;
  margin-bottom: 4px;
}
.arw-rec-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.arw-rec-dot--orig { background: #f59e0b; }
.arw-rec-dot--result { background: #14b8a6; }
.arw-rec-section-count {
  font-size: 10px; font-weight: 600; color: #94a3b8;
  margin-left: 2px;
}
.arw-rec-section-text {
  font-size: 12.5px; line-height: 1.6; color: #64748b;
  word-break: break-word; white-space: pre-wrap;
  max-height: 60px; overflow: hidden;
  position: relative;
  transition: max-height 0.3s ease;
}
.arw-rec-section-text.expanded { max-height: 2000px; }
.arw-rec-section-text--result { color: #1e293b; font-weight: 500; }

/* 展开/收起按钮 */
.arw-rec-toggle {
  display: inline-flex; align-items: center; gap: 3px;
  border: none; background: transparent;
  font-size: 11px; font-weight: 600; color: #0d9488;
  cursor: pointer; padding: 4px 0; margin-top: 2px;
  transition: color 0.15s ease;
}
.arw-rec-toggle:hover { color: #14b8a6; }
.arw-rec-toggle svg { transition: transform 0.25s ease; }
.arw-rec-toggle svg.rotated { transform: rotate(180deg); }

/* 分页底栏 */
.arw-rec-footer {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  padding: 10px 16px; border-top: 1px solid var(--gray-100);
  background: var(--gray-50);
}
.arw-rec-page-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 5px 12px; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #fff;
  font-size: 12px; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.18s ease;
}
.arw-rec-page-btn:hover:not(:disabled) { border-color: #0d9488; color: #0d9488; background: rgba(20, 184, 166, 0.04); }
.arw-rec-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.arw-rec-page-info { font-size: 12px; font-weight: 600; color: #475569; font-variant-numeric: tabular-nums; }
.arw-popup-enter-active, .arw-popup-leave-active { transition: opacity 0.25s ease; }
.arw-popup-enter-from, .arw-popup-leave-to { opacity: 0; }

/* ============ 文档降重支付弹窗 ============ */
.arw-pay-modal-box { max-width: 420px; }
.arw-pay-modal .arw-modal-body { padding: 10px 16px; }
/* 当前支付渠道展示条（渠道选择已外部化到左侧支付方式卡） */
.arw-pay-current {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 10px;
  border: 1px solid rgba(20, 184, 166, 0.35);
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.08), rgba(13, 148, 136, 0.04));
  margin-bottom: 12px;
}
.arw-pay-current-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 9px;
  flex: none;
}
.arw-pay-current-icon.doc { background: linear-gradient(135deg, rgba(249, 115, 22, 0.14), rgba(217, 119, 6, 0.08)); color: #d97706; }
.arw-pay-current-icon.time { background: linear-gradient(135deg, rgba(59, 130, 246, 0.14), rgba(37, 99, 235, 0.08)); color: #3b82f6; }
.arw-pay-current-icon.balance { background: linear-gradient(135deg, rgba(20, 184, 166, 0.14), rgba(13, 148, 136, 0.08)); color: #0d9488; }
.arw-pay-current-text { flex: 1; min-width: 0; }
.arw-pay-current-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--gray-700); }
.arw-pay-current-desc { display: block; margin-top: 2px; font-size: 10.5px; color: var(--gray-400); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.arw-pay-current-switch {
  flex: none;
  padding: 4px 10px;
  border-radius: 14px;
  border: 1px solid var(--gray-200);
  background: var(--white);
  color: var(--gray-500);
  font-size: 10.5px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-pay-current-switch:hover { border-color: rgba(20, 184, 166, 0.5); color: #0d9488; }
.arw-pay-channel-list { display: flex; flex-direction: column; gap: 8px; }
.arw-pay-channel {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 9px;
  border: 1px solid var(--gray-200);
  background: var(--white);
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  position: relative;
}
.arw-pay-channel:hover:not(.disabled) { border-color: rgba(20, 184, 166, 0.4); background: rgba(20, 184, 166, 0.03); }
.arw-pay-channel.active { border-color: #14b8a6; background: rgba(20, 184, 166, 0.06); box-shadow: 0 0 0 1px rgba(20, 184, 166, 0.15); }
.arw-pay-channel.disabled { opacity: 0.5; cursor: not-allowed; background: var(--gray-50); }
.arw-pay-channel-radio {
  width: 15px; height: 15px;
  border-radius: 50%;
  border: 1.5px solid var(--gray-300);
  flex-shrink: 0;
  position: relative;
  transition: all 0.2s ease;
}
.arw-pay-channel-radio.checked { border-color: #14b8a6; }
.arw-pay-channel-radio.checked::after {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 50%;
  background: linear-gradient(135deg, #14b8a6, #0d9488);
}
.arw-pay-channel-icon {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px;
  border-radius: 8px;
  flex-shrink: 0;
}
.arw-pay-channel-icon.doc { background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(217, 119, 6, 0.08)); color: #d97706; }
.arw-pay-channel-icon.char { background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(13, 148, 136, 0.08)); color: #0d9488; }
.arw-pay-channel-icon.balance { background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(37, 99, 235, 0.08)); color: #2563eb; }
.arw-pay-channel-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.arw-pay-channel-label { font-size: 13px; font-weight: 700; color: var(--dark-900); }
.arw-pay-channel-desc { font-size: 11px; color: var(--gray-400); }
.arw-pay-channel.active .arw-pay-channel-label { color: #0d9488; }
.arw-pay-channel-badge {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  flex-shrink: 0;
}
.arw-pay-modal-foot {
  display: flex;
  gap: 10px;
  padding: 12px 16px 14px;
  border-top: 1px solid var(--gray-100);
}
.arw-pay-cancel {
  flex: 1;
  padding: 9px;
  border: 1px solid var(--gray-200);
  background: var(--white);
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.2s ease;
}
.arw-pay-cancel:hover { background: var(--gray-50); }
.arw-pay-confirm {
  flex: 1.6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px;
  border: none;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 3px 12px rgba(13, 148, 136, 0.25);
  transition: all 0.2s ease;
}
.arw-pay-confirm:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(13, 148, 136, 0.35); }
.arw-pay-confirm:disabled { opacity: 0.6; cursor: not-allowed; }

/* ============ 文档提交成功弹窗 ============ */
.arw-success-modal .arw-modal-box { max-width: 400px; }
.arw-success-box {
  padding: 36px 28px 28px;
  text-align: center;
  display: flex; flex-direction: column; align-items: center;
}
.arw-success-icon {
  width: 64px; height: 64px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.12) 0%, rgba(22, 163, 74, 0.06) 100%);
  border: 2px solid rgba(34, 197, 94, 0.2);
  color: #16a34a;
  margin-bottom: 16px;
  animation: arw-success-pop 0.4s ease;
}
@keyframes arw-success-pop {
  0% { transform: scale(0.6); opacity: 0; }
  60% { transform: scale(1.08); }
  100% { transform: scale(1); opacity: 1; }
}
.arw-success-title {
  font-size: 17px; font-weight: 800; color: #0f172a;
  margin: 0 0 6px;
}
.arw-success-desc {
  font-size: 13px; color: #64748b;
  margin: 0 0 4px;
}
.arw-success-hint {
  font-size: 12px; color: #94a3b8;
  margin: 0 0 20px;
}
.arw-success-actions {
  display: flex; gap: 10px; width: 100%;
}
.arw-success-btn {
  flex: 1;
  display: inline-flex; align-items: center; justify-content: center; gap: 5px;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.18s ease;
  text-decoration: none; white-space: nowrap;
  border: 1px solid transparent;
}
.arw-success-btn--secondary {
  border-color: #e2e8f0; background: #fff; color: #475569;
}
.arw-success-btn--secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
.arw-success-btn--primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 3px 12px rgba(13, 148, 136, 0.25);
}
.arw-success-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(13, 148, 136, 0.35); }

/* ============ 支付弹窗：本次消费预估面板 ============ */
.arw-pay-summary {
  margin-top: 10px;
  padding: 11px 13px 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-200);
  background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
  position: relative;
  overflow: hidden;
  transition: all 0.25s ease;
}
.arw-pay-summary::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 3px; height: 100%;
  background: linear-gradient(180deg, #14b8a6, #0d9488);
  opacity: 0.85;
}
.arw-pay-summary.money::before { background: linear-gradient(180deg, #2563eb, #1d4ed8); }
.arw-pay-summary.char::before { background: linear-gradient(180deg, #14b8a6, #0d9488); }
.arw-pay-summary.doc::before { background: linear-gradient(180deg, #f97316, #d97706); }
.arw-pay-summary-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 9px;
}
.arw-pay-summary-title {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 800;
  color: var(--dark-800);
  letter-spacing: -0.01em;
}
.arw-pay-summary-title svg { color: #0d9488; }
.arw-pay-summary.money .arw-pay-summary-title svg { color: #2563eb; }
.arw-pay-summary.char .arw-pay-summary-title svg { color: #0d9488; }
.arw-pay-summary.doc .arw-pay-summary-title svg { color: #d97706; }
.arw-pay-summary-count {
  font-size: 11px;
  font-weight: 700;
  color: var(--gray-500);
  font-variant-numeric: tabular-nums;
}
.arw-pay-summary-count em {
  font-style: normal;
  color: var(--gray-400);
  font-weight: 600;
  margin-left: 2px;
}
.arw-pay-summary-body {
  display: flex;
  align-items: stretch;
  gap: 8px;
}
.arw-pay-summary-cell {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 8px 10px;
  border-radius: 8px;
  background: var(--white);
  border: 1px solid var(--gray-100);
}
.arw-pay-summary-cell--consume { border-color: rgba(20, 184, 166, 0.16); }
.arw-pay-summary.money .arw-pay-summary-cell--consume { border-color: rgba(37, 99, 235, 0.16); background: rgba(37, 99, 235, 0.03); }
.arw-pay-summary.char .arw-pay-summary-cell--consume { border-color: rgba(20, 184, 166, 0.16); background: rgba(20, 184, 166, 0.03); }
.arw-pay-summary.doc .arw-pay-summary-cell--consume { border-color: rgba(249, 115, 22, 0.16); background: rgba(249, 115, 22, 0.03); }
.arw-pay-summary-cell--after { background: var(--gray-50); }
.arw-pay-summary-label {
  font-size: 10px;
  font-weight: 700;
  color: var(--gray-400);
  letter-spacing: 0.02em;
}
.arw-pay-summary-value {
  font-size: 16px;
  font-weight: 800;
  color: var(--dark-900);
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.arw-pay-summary-value--consume { color: #0d9488; }
.arw-pay-summary.money .arw-pay-summary-value--consume { color: #2563eb; }
.arw-pay-summary.char .arw-pay-summary-value--consume { color: #0d9488; }
.arw-pay-summary.doc .arw-pay-summary-value--consume { color: #d97706; }
.arw-pay-summary-value.is-insufficient { color: #dc2626; }
.arw-pay-summary-unit {
  font-size: 10px;
  color: var(--gray-400);
  font-weight: 600;
}
.arw-pay-summary-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--gray-300);
  flex-shrink: 0;
}
.arw-pay-summary-warn {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 9px;
  padding: 7px 10px;
  border-radius: 7px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.16);
  color: #dc2626;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.4;
}
.arw-pay-summary-warn svg { flex-shrink: 0; }

/* ============ 响应式 ============ */
@media (max-width: 1100px) {
  .arw-layout { flex-direction: column; align-items: stretch; }
  .arw-options { position: static; flex: none; width: 100%; min-width: 0; }
  .arw-text-columns { flex-direction: column; }
  .arw-info { flex-direction: column; }
}
@media (max-width: 720px) {
  .arw-layout { padding: 10px; }
  /* Tab 换行成两行后 pill 高度参差：禁止按钮内换行，记录按钮挪到第二行右对齐 */
  .arw-toolbar { flex-wrap: wrap; gap: 8px; }
  .arw-tabs { width: 100%; justify-content: space-between; }
  .arw-tab { flex: 1; padding: 8px 4px; font-size: 12px; white-space: nowrap; min-width: 0; }
  .arw-records-btn { margin-left: auto; white-space: nowrap; flex-shrink: 0; }
  .arw-textarea, .arw-result-text { min-height: 240px; }
  .arw-multi-compare { flex-direction: column; }
  .arw-multi-bottom-bar { flex-direction: column; align-items: stretch; }
  .arw-multi-bottom-right { justify-content: center; }
  .arw-doc-submit-row { flex-direction: column; align-items: stretch; }
  .arw-doc-file-card { flex-wrap: wrap; }
  .arw-doc-file-actions { width: 100%; }
  .arw-doc-file-action { flex: 1; text-align: center; }
}
/* ============ ≤640px：H5 重排 + 钱包卡轻量化 ============ */
@media (max-width: 640px) {
  /* 归零 ≤720 的 10px 补偿（console 时代产物）：m 壳 .m-main 14px 即最终边距，
     叠加 10px 会把卡片两侧挤出 24px 大留白（内容蜷缩） */
  .arw-layout { display: flex; flex-direction: column; padding: 0; }
  /* H5 重排（修 PC 照搬）：侧栏 display:contents 拆卡参与排序——
     配置卡（改写类型/目标平台/模式）→ 正文 → 支付方式 → 钱包。
     钱包/支付是结算组件，垫底不再占首屏（原 PC 照搬时整块钱包面板压在正文前） */
  .arw-options { display: contents; }
  .arw-content { order: 0; }
  .arw-config-card { order: 0; }
  .arw-pay-card { order: 8; }
  .arw-wallet { order: 9; }
  /* 钱包压成下单摘要：head/余额明细行/时长包行全隐，只留费用行 + 套餐入口
     （PC 大卡照搬在 m 层把下单动线拖出一长串） */
  .arw-wallet-head { display: none; }
  .arw-wallet-row:not(.arw-wallet-row--price) { display: none; }
  .arw-wallet-row--packs { display: none; }
  /* 信息区（计费方式/降重更新/操作提示）：纯参考性说明，m 层删减
     （计费口径摘要在 toolbar tip 与钱包费用行已覆盖） */
  .arw-info { display: none; }
  /* 原文/结果 Tab 切换（仅 m 壳，.m-main 门控）：原文与降重结果上下两个文本框
     收成单屏 Tab，主题 teal；PC 双栏与 console 窄窗口不受影响 */
  .m-main .arw-m-tabs { display: flex; gap: 8px; padding: 2px 0 12px; }
  .m-main .arw-m-tabs button {
    flex: 1;
    height: 38px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #64748b;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    -webkit-tap-highlight-color: transparent;
  }
  .m-main .arw-m-tabs button.active {
    border-color: #0d9488;
    background: rgba(13, 148, 136, 0.08);
    color: #0f766e;
  }
  .m-main .arw-m-off { display: none !important; }
  /* 结果文本取消 PC 内滚（基类 min 340/max 520 + overflow-y:auto），m 层自然撑开走整页滚动 */
  .m-main .arw-result-text { max-height: none; overflow: visible; }
  .arw-wallet-btn { padding: 9px 12px; font-size: 13px; }
  .arw-wallet {
    background: #fff;
    border: 1px solid #ccfbf1;
    box-shadow: 0 2px 10px rgba(13, 148, 136, 0.08);
  }
  .arw-wallet-icon { background: rgba(13, 148, 136, 0.1); }
  .arw-wallet-icon svg { color: #0d9488; }
  .arw-wallet-title { color: #0f172a; }
  .arw-wallet-mode { background: rgba(13, 148, 136, 0.08); color: #0f766e; }
  .arw-wallet-mode.free { background: rgba(13, 148, 136, 0.06); color: #0f766e; }
  .arw-wallet-mode.paid { background: rgba(13, 148, 136, 0.14); color: #115e59; }
  .arw-wallet-body { background: #f8fafc; }
  .arw-wallet-row + .arw-wallet-row { border-top-color: rgba(15, 23, 42, 0.06); }
  .arw-wallet-row--price { background: rgba(13, 148, 136, 0.05); border-color: rgba(13, 148, 136, 0.16); }
  .arw-wallet-row--price.free { background: rgba(13, 148, 136, 0.03); border-color: rgba(13, 148, 136, 0.1); }
  .arw-wallet-row + .arw-wallet-row--price { border-top-color: rgba(13, 148, 136, 0.16); }
  .arw-wallet-label { color: #475569; }
  .arw-wallet-sub { color: #94a3b8; }
  .arw-wallet-value { color: #0f172a; }
  .arw-wallet-value em { color: #64748b; }
  .arw-wallet-btn {
    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
    color: #fff;
  }
  .arw-wallet-btn:hover { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); }
}
</style>
