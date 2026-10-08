<template>
  <ToolShell
    wide
    theme="cyan"
    name="AI检测"
    desc="分段送检定位 AI 痕迹 · 整体 AI 率 · 逐段明细"
    hideHeader
    icon='<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>'
  >
    <div class="aic-layout">
      <!-- ======================== 三列工作区 ======================== -->
      <div class="aic-cols">
        <!-- ========== 列 1：计费与检测平台（参照 AI降重左侧面板） ========== -->
        <aside class="aic-options">
          <!-- 钱包卡 -->
          <div class="aic-wallet">
            <div class="aic-wallet-head">
              <span class="aic-wallet-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
              </span>
              <span class="aic-wallet-title">我的钱包</span>
            </div>
            <div class="aic-wallet-body">
              <div class="aic-wallet-row aic-wallet-row--price" :class="billingMode">
                <span class="aic-wallet-label">
                  每千字单价
                  <span class="aic-wallet-sub">{{ billingMode === 'paid' ? '按字符计费' : '当前免费' }}</span>
                </span>
                <span class="aic-wallet-value">
                  <template v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}<em>/千字符</em></template>
                  <template v-else>免费</template>
                </span>
              </div>
              <div class="aic-wallet-row">
                <span class="aic-wallet-label">
                  账户余额
                  <span class="aic-wallet-sub">按单价扣</span>
                </span>
                <span class="aic-wallet-value">¥{{ walletMoney.toFixed(2) }}</span>
              </div>
              <div class="aic-wallet-row aic-wallet-row--packs">
                <div class="aic-wallet-pack">
                  <span class="aic-wallet-label">
                    时长包
                    <span class="aic-wallet-sub">{{ timeActive ? '剩余 ' + formatTimeRemain(timeBalanceSec) : '未开通' }}</span>
                  </span>
                </div>
                <span class="aic-wallet-pack-sep"></span>
                <div class="aic-wallet-pack">
                  <span class="aic-wallet-label">
                    字符包
                    <span class="aic-wallet-sub">按实际字数扣</span>
                  </span>
                  <span class="aic-wallet-pack-value">{{ walletChar.toLocaleString() }}<em>字</em></span>
                </div>
              </div>
            </div>
            <!-- 免费领取额度（每用户限一次，点击打开领取弹窗） -->
            <button
              v-if="freeClaimEnabled"
              class="aic-wallet-free"
              :class="{ claimed: freeClaimClaimed }"
              :disabled="freeClaimClaimed"
              type="button"
              @click="openFreeClaim"
            >
              <svg v-if="!freeClaimClaimed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span>{{ freeClaimClaimed ? '已领取免费额度' : '新人福利 · 免费领 ' + (walletFreeClaim?.value_text || '') }}</span>
            </button>
            <NuxtLink to="/pc/package-shop" class="aic-wallet-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              <span>套餐购买</span>
            </NuxtLink>
          </div>

          <!-- 支付方式 -->
          <div class="aic-config-card">
            <h3 class="aic-config-title">支付方式</h3>
            <div class="aic-chips">
              <button
                v-for="o in payMethodOptions"
                :key="o.value"
                class="aic-chip"
                :class="{ active: payMethod === o.value }"
                :title="o.desc"
                @click="payMethod = o.value; payMethodTouched = true"
              >{{ o.label }}</button>
            </div>
          </div>

          <!-- 检测平台 -->
          <div class="aic-config-card">
            <h3 class="aic-config-title">
              检测平台
              <span class="aic-config-hint">单选</span>
            </h3>
            <div class="aic-chips aic-chips--wrap">
              <button
                v-for="p in platList"
                :key="p.key"
                class="aic-chip aic-chip--platform"
                :class="{ active: selectedPlat === p.key }"
                @click="selectedPlat = p.key"
              >
                {{ p.name }}
                <span v-if="p.recommend" class="aic-tag">荐</span>
              </button>
              <div v-if="!platList.length" class="aic-plats-empty">平台参数加载中…</div>
            </div>
          </div>

          <!-- 所选平台效果报告（平台判定 × 本站检测 对照，参照效果报告.md 口径） -->
          <div v-if="selPlatInfo" class="aic-cap" :style="{ borderColor: hexA(selPlatInfo.color, 0.25), background: hexA(selPlatInfo.color, 0.05) }">
            <div class="aic-cap-head">
              <span class="aic-cap-name">{{ selPlatInfo.name }}</span>
              <span class="aic-cap-badge">效果报告</span>
            </div>
            <div v-if="selPlatInfo.report" class="aic-cap-report">
              <div class="aic-cap-sample">样本：{{ selPlatInfo.report.sample }}</div>
              <div class="aic-cap-thead">
                <span>平台判定 → 本站检测</span>
                <span>数量 / 占比</span>
              </div>
              <div v-for="r in selPlatInfo.report.rows" :key="r.label" class="aic-cap-row" :class="rowTone(r)">
                <span class="aic-cap-row-label">{{ r.label }}</span>
                <span class="aic-cap-row-data">
                  <em>{{ r.n }}/{{ r.total }}</em>
                  <b>{{ r.pct.toFixed(1) }}%</b>
                </span>
              </div>
              <div class="aic-cap-total">
                <span>总一致率</span>
                <span class="aic-cap-total-data">
                  <em>{{ selPlatInfo.report.total.n }}/{{ selPlatInfo.report.total.total }}</em>
                  <b :style="{ color: selPlatInfo.color }">{{ selPlatInfo.report.total.pct.toFixed(2) }}%</b>
                </span>
              </div>
            </div>
            <div v-else class="aic-cap-main">
              <span class="aic-cap-num" :style="{ color: selPlatInfo.color }">{{ accPercent(selPlatInfo.accuracy) }}<em>%</em></span>
              <span class="aic-cap-desc">{{ selPlatInfo.desc }}</span>
            </div>
          </div>

          <!-- 口径速览（贴列底，填满左栏） -->
          <div class="aic-strip">
            <span class="aic-strip-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              检测口径
            </span>
            <span class="aic-strip-item"><i></i>按段送检</span>
            <span class="aic-strip-item"><i></i>字数加权</span>
            <span class="aic-strip-item"><i></i>仅作参考</span>
          </div>
        </aside>

        <!-- ========== 中右区包裹层：输入+结果并排，操作行紧贴两框底部（不受左列高度影响） ========== -->
        <div class="aic-main">
        <!-- 滚动提示横幅 + 模式切换（参照 AI降重：置于右侧内容区顶部，让左列顶到最上） -->
        <div class="aic-notice">
          <div class="aic-notice-track">
            <span class="aic-notice-text">基于所选平台真实识别接口 · 逐段定位 AI 生成痕迹 · 检测完成即时生成分段报告 · 支持文本与文档双模式</span>
          </div>
        </div>
        <div class="aic-toolbar">
          <!-- 文档检测未上线，暂时隐藏模式切换 tab（mode 固定为 text），上线后移除 v-if 即可 -->
          <div class="aic-tabs" v-if="false">
            <button :class="['aic-tab', { active: mode === 'text' }]" @click="switchMode('text')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
              粘贴文本
            </button>
            <button :class="['aic-tab', { active: mode === 'file' }]" @click="switchMode('file')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              检测文档
            </button>
          </div>
          <p class="aic-toolbar-tip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            逐段分析 · 按字数加权汇总 · 单次最多 30000 字
          </p>
        </div>
        <!-- H5（m 壳）原文/结果切换条：PC 隐藏（.aic-m-tabs 基类 display:none），≤640 且 .m-main 下启用 -->
        <div class="aic-m-tabs">
          <button type="button" :class="{ active: mPane === 'input' }" @click="mPane = 'input'">原文</button>
          <button type="button" :class="{ active: mPane === 'result' }" @click="mPane = 'result'">检测结果</button>
        </div>
        <!-- ========== 列 2：原文输入 ========== -->
        <div class="aic-col aic-col--input" :class="{ 'aic-m-off': mPane !== 'input' }">
          <div class="aic-col-head">
            <span class="aic-col-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </span>
            <span class="aic-col-title">{{ mode === 'text' ? '原文内容' : '上传文档' }}</span>
            <div class="aic-col-head-right">
              <button v-if="mode === 'text' && pasteText" class="aic-clear-mini" @click="pasteText = ''">清空</button>
              <button v-else-if="mode === 'file' && uploadedFile" class="aic-clear-mini" @click="resetUpload">删除文件</button>
            </div>
          </div>

          <div class="aic-col-body">
            <!-- 粘贴文本（v-show 保持占位，切换 tab 高度不变） -->
            <div v-show="mode === 'text'" class="aic-input-wrap">
              <textarea
                v-model="pasteText"
                class="aic-textarea"
                :maxlength="MAX_CHARS"
                placeholder="请输入或粘贴需要检测的文本（建议不少于 50 字）..."
              ></textarea>
              <div class="aic-wordcount" :class="{ near: pasteText.length >= MAX_CHARS * 0.9 }">{{ pasteText.length.toLocaleString() }} / {{ MAX_CHARS.toLocaleString() }} 字</div>
            </div>

            <!-- 检测文档 -->
            <div
              v-show="mode === 'file'"
              class="aic-dropzone"
              :class="{ 'is-over': isDragging, 'is-done': uploadedFile, 'is-uploading': uploading }"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              @click="!uploadedFile && !uploading && fileInput && fileInput.click()"
            >
              <input ref="fileInput" type="file" accept=".docx,.doc,.txt" hidden @change="handleFileSelect">

              <div v-if="!uploadedFile && !uploading" class="aic-dropzone-empty">
                <div class="aic-dropzone-circle">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <p class="aic-dropzone-hint">点击或拖拽文件上传</p>
                <p class="aic-dropzone-sub">支持 .docx / .doc / .txt · 最大 50MB</p>
              </div>

              <div v-else-if="uploading" class="aic-dropzone-file" @click.stop>
                <div class="aic-file-row">
                  <span class="aic-file-icon aic-file-icon--loading">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  </span>
                  <div class="aic-file-meta">
                    <div class="aic-file-name">{{ pendingFileName }}</div>
                    <div class="aic-file-size">{{ pendingFileSize }}</div>
                  </div>
                </div>
                <div class="aic-progress"><div class="aic-progress-fill" :style="{ width: uploadProgress + '%' }"></div></div>
                <span class="aic-file-state">正在上传... {{ uploadProgress }}%</span>
              </div>

              <div v-else class="aic-dropzone-file" @click.stop>
                <div class="aic-file-row">
                  <span class="aic-file-icon aic-file-icon--ok">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  </span>
                  <div class="aic-file-meta">
                    <div class="aic-file-name">{{ uploadedFile.name }}</div>
                    <div class="aic-file-size">{{ formatFileSize(uploadedFile.size) }}</div>
                  </div>
                </div>
                <span class="aic-file-state aic-file-state--ok">文件已就绪 · 可开始检测</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ========== 列 3：检测结果 ========== -->
        <div class="aic-col aic-col--result" :class="{ 'aic-m-off': mPane !== 'result' }">
          <div class="aic-col-head">
            <span class="aic-col-ico aic-col-ico--result">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
            </span>
            <span class="aic-col-title">检测结果</span>
          </div>

          <div class="aic-result-body">
            <!-- 空态 -->
            <div v-if="!result && !detecting" class="aic-result-empty">
              <!-- 品牌引擎行 -->
              <div class="aic-hero">
                <span class="aic-hero-ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                </span>
                <div class="aic-hero-txt">
                  <b>智鉴 · 深度语义检测引擎</b>
                  <span>V3 逐段比对 · 字数加权 · 与主流平台判定对齐</span>
                </div>
              </div>

              <!-- 报告效果预览（示意） -->
              <div class="aic-demo">
                <div class="aic-demo-left">
                  <svg viewBox="0 0 84 84" width="74" height="74">
                    <circle cx="42" cy="42" r="35" fill="none" stroke="rgba(15,23,42,0.07)" stroke-width="8"/>
                    <circle cx="42" cy="42" r="35" fill="none" stroke-linecap="round" stroke-width="8"
                            :stroke="selPlatInfo?.color || '#0284c7'"
                            stroke-dasharray="219.9" stroke-dashoffset="79.2"
                            transform="rotate(-90 42 42)"/>
                    <text x="42" y="48" text-anchor="middle" class="aic-demo-ring-num">AI 率</text>
                  </svg>
                  <span class="aic-demo-ring-label">报告效果示意</span>
                </div>
                <div class="aic-demo-bars">
                  <div class="aic-demo-row"><em>段 1</em><i style="width:86%" class="is-ai"></i></div>
                  <div class="aic-demo-row"><em>段 2</em><i style="width:42%" class="is-human"></i></div>
                  <div class="aic-demo-row"><em>段 3</em><i style="width:64%" class="is-ai"></i></div>
                  <span class="aic-demo-note">检测完成后逐段显示 AI 率分布，支持定位与全文查看</span>
                </div>
              </div>

              <!-- 实力带：平台对照准确率 -->
              <div class="aic-cred">
                <span class="aic-cred-title">与主流平台判定对照准确率</span>
                <div class="aic-cred-plats">
                  <span v-for="p in platList" :key="p.key" class="aic-cred-plat">
                    <i :style="{ background: p.color }"></i>
                    {{ p.name }}
                    <b>{{ accPercent(p.accuracy) }}%</b>
                  </span>
                </div>
              </div>
            </div>

            <!-- 检测中 -->
            <div v-else-if="detecting && !result" class="aic-result-empty">
              <span class="aic-spinner"></span>
              <span>正在逐段分析文本特征，请稍候…</span>
            </div>

            <!-- 结果 -->
            <template v-else>
              <!-- 流式检测进度 -->
              <div v-if="result._streaming" class="aic-stream-banner">
                <span class="aic-stream-spinner"></span>
                <div class="aic-stream-info">
                  <b>正在逐段检测</b>
                  <span>已完成 {{ streamProgress.done }}/{{ streamProgress.total }} 段，结果逐段实时展示</span>
                </div>
                <div class="aic-stream-bar"><i :style="{ width: (streamProgress.total ? streamProgress.done / streamProgress.total * 100 : 0) + '%' }"></i></div>
              </div>

              <!-- 顶部结论区（完成态）：单段=圆环+判定结论卡；多段=AI 段占比圆环+KPI（逐段概率见下方列表） -->
              <div class="aic-result-top" v-if="!result._streaming" :class="isAi ? 'is-ai' : 'is-human'">
                <template v-if="isSingle">
                  <div class="aic-gauge">
                    <svg viewBox="0 0 120 120" width="110" height="110">
                      <defs>
                        <linearGradient id="aicGradAi" x1="0" y1="0" x2="1" y2="1">
                          <stop offset="0%" stop-color="#f87171"/><stop offset="100%" stop-color="#dc2626"/>
                        </linearGradient>
                        <linearGradient id="aicGradHuman" x1="0" y1="0" x2="1" y2="1">
                          <stop offset="0%" stop-color="#38bdf8"/><stop offset="100%" stop-color="#0284c7"/>
                        </linearGradient>
                      </defs>
                      <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(15,23,42,0.06)" stroke-width="10"/>
                      <circle cx="60" cy="60" r="52" fill="none" stroke-linecap="round" stroke-width="10"
                              class="aic-gauge-ring"
                              :stroke="isAi ? 'url(#aicGradAi)' : 'url(#aicGradHuman)'"
                              :stroke-dasharray="gaugeCircum"
                              :stroke-dashoffset="gaugeOffset"/>
                      <text x="60" y="55" text-anchor="middle" class="aic-gauge-num" :fill="isAi ? '#dc2626' : '#0284c7'">{{ mainPercent }}%</text>
                      <text x="60" y="75" text-anchor="middle" class="aic-gauge-label">AI 率</text>
                    </svg>
                  </div>
                  <div class="aic-verdict">
                    <div class="aic-verdict-badge" :class="isAi ? 'is-ai' : 'is-human'">
                      <svg v-if="isAi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                      {{ isAi ? '疑似 AI 生成' : '人工撰写特征' }}
                    </div>
                    <p class="aic-verdict-tip">{{ isAi ? '该段落 AI 生成特征明显，建议针对性改写后再次复检' : '该段落整体呈现人工写作特征，可放心使用' }}</p>
                    <div class="aic-kpis is-single">
                      <div class="aic-kpi">
                        <span class="aic-kpi-num">{{ result.total_chars.toLocaleString() }}</span>
                        <span class="aic-kpi-label">检测字数</span>
                      </div>
                      <div class="aic-kpi">
                        <span class="aic-kpi-num">{{ result.detect_ms }}<i class="aic-kpi-unit">ms</i></span>
                        <span class="aic-kpi-label">检测耗时</span>
                      </div>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <div class="aic-gauge">
                    <svg viewBox="0 0 120 120" width="110" height="110">
                      <defs>
                        <linearGradient id="aicGradAi" x1="0" y1="0" x2="1" y2="1">
                          <stop offset="0%" stop-color="#f87171"/><stop offset="100%" stop-color="#dc2626"/>
                        </linearGradient>
                        <linearGradient id="aicGradHuman" x1="0" y1="0" x2="1" y2="1">
                          <stop offset="0%" stop-color="#38bdf8"/><stop offset="100%" stop-color="#0284c7"/>
                        </linearGradient>
                      </defs>
                      <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(15,23,42,0.06)" stroke-width="10"/>
                      <circle cx="60" cy="60" r="52" fill="none" stroke-linecap="round" stroke-width="10"
                              class="aic-gauge-ring"
                              :stroke="isAi ? 'url(#aicGradAi)' : 'url(#aicGradHuman)'"
                              :stroke-dasharray="gaugeCircum"
                              :stroke-dashoffset="segGaugeOffset"/>
                      <text x="60" y="55" text-anchor="middle" class="aic-gauge-num" :fill="isAi ? '#dc2626' : '#0284c7'">{{ segPct }}%</text>
                      <text x="60" y="75" text-anchor="middle" class="aic-gauge-label">AI 段占比</text>
                    </svg>
                  </div>
                  <div class="aic-kpis">
                    <div class="aic-kpi">
                      <span class="aic-kpi-num is-ai">{{ aiSegCount }}</span>
                      <span class="aic-kpi-label">判定 AI 段</span>
                    </div>
                    <div class="aic-kpi">
                      <span class="aic-kpi-num is-human">{{ okSegCount - aiSegCount }}</span>
                      <span class="aic-kpi-label">判定人工段</span>
                    </div>
                    <div class="aic-kpi">
                      <span class="aic-kpi-num">{{ result.total_chars.toLocaleString() }}</span>
                      <span class="aic-kpi-label">检测字数</span>
                    </div>
                    <div class="aic-kpi">
                      <span class="aic-kpi-num">{{ result.chunk_count }}</span>
                      <span class="aic-kpi-label">送检分段</span>
                    </div>
                  </div>
                </template>
              </div>

              <!-- 单段完成态：检测详情 + 建议 -->
              <template v-if="isSingle && !result._streaming">
                <div class="aic-detail">
                  <div class="aic-detail-item">
                    <span class="aic-detail-num" :class="isAi ? 'is-ai' : 'is-human'">{{ mainPercent }}%</span>
                    <span class="aic-detail-label">AI 生成概率</span>
                  </div>
                  <div class="aic-detail-item">
                    <span class="aic-detail-num">{{ result.total_chars.toLocaleString() }}</span>
                    <span class="aic-detail-label">检测字数</span>
                  </div>
                  <div class="aic-detail-item">
                    <span class="aic-detail-num">{{ result.detect_ms }}<i class="aic-detail-unit">ms</i></span>
                    <span class="aic-detail-label">检测耗时</span>
                  </div>
                  <div class="aic-detail-item">
                    <span class="aic-detail-num is-name">{{ selPlatInfo?.name || '—' }}</span>
                    <span class="aic-detail-label">检测引擎</span>
                  </div>
                </div>
                <div class="aic-guide" :class="isAi ? 'is-ai' : 'is-human'">
                  <div class="aic-guide-info">
                    <b>{{ isAi ? '该段落 AI 特征明显' : '该段落人工特征良好' }}</b>
                    <span>{{ isAi ? '建议对段落进行改写润色，降低整体 AI 痕迹后再提交' : '保持当前写作风格，无需额外处理' }}</span>
                  </div>
                  <NuxtLink v-if="isAi" class="aic-guide-btn" to="/pc/tools/aigcreduceweight">前往 AI 降重 →</NuxtLink>
                </div>
              </template>
              <p v-if="result.truncated && !result._streaming" class="aic-warn-line">内容超长，已按 {{ MAX_CHARS_LABEL }} 字截断检测</p>

              <!-- AI 分布（多段落，完成态） -->
              <div class="aic-dist" v-if="!isSingle && !result._streaming">
                <div class="aic-dist-head">
                  <span class="aic-dist-title">AI 分布</span>
                  <span class="aic-dist-legend">
                    <span class="aic-leg"><i class="aic-leg-dot is-ai"></i>AI</span>
                    <span class="aic-leg"><i class="aic-leg-dot is-human"></i>人工</span>
                    <span class="aic-leg"><i class="aic-leg-dot is-failed"></i>失败</span>
                  </span>
                </div>
                <div class="aic-dist-bar">
                  <div
                    v-for="seg in distSegs"
                    :key="seg.index"
                    class="aic-dist-seg"
                    :class="[seg.failed ? 'is-failed' : (seg.is_ai_generated ? 'is-ai' : 'is-human'), { 'is-active': activeSeg === seg.index }]"
                    :style="{ width: seg.percent + '%' }"
                    :title="'#' + seg.index + ' ' + (seg.failed ? '检测失败' : (seg.is_ai_generated ? 'AI' : '人工') + ' ' + seg.scorePct + '%')"
                    @click="focusSeg(seg.index)"
                  ></div>
                </div>
              </div>

              <!-- 段落 AI 率（多段落；流式时列表骨架渐进填充） -->
              <div class="aic-segs" v-if="!isSingle">
                <div class="aic-segs-head">
                  <span class="aic-segs-title">段落 AI 率</span>
                  <div class="aic-seg-tabs" v-if="!result._streaming">
                    <button class="aic-seg-tab" :class="{ active: segFilter === 'all' }" @click="segFilter = 'all'">全部 {{ distSegs.length }}</button>
                    <button class="aic-seg-tab is-ai" :class="{ active: segFilter === 'ai' }" @click="segFilter = 'ai'">AI {{ aiSegCount }}</button>
                    <button class="aic-seg-tab is-human" :class="{ active: segFilter === 'human' }" @click="segFilter = 'human'">人工 {{ okSegCount - aiSegCount }}</button>
                  </div>
                  <span class="aic-segs-streaming" v-else>{{ streamProgress.done }}/{{ streamProgress.total }} 段完成</span>
                </div>
                <template v-for="seg in filteredSegs" :key="seg.index">
                  <!-- 待检测骨架 -->
                  <div v-if="seg.pending" class="aic-seg is-pending">
                    <span class="aic-seg-idx">#{{ seg.index }}</span>
                    <div class="aic-seg-main">
                      <span class="aic-skel" style="width: 34%"></span>
                      <span class="aic-skel" style="width: 82%"></span>
                      <span class="aic-skel" style="width: 60%"></span>
                    </div>
                  </div>
                  <!-- 过短跳过段 -->
                  <div v-else-if="seg.skipped" class="aic-seg is-skipped">
                    <span class="aic-seg-idx">#{{ seg.index }}</span>
                    <div class="aic-seg-main">
                      <div class="aic-seg-top">
                        <span class="aic-seg-tag is-skipped">内容过短 · 未送检</span>
                        <span class="aic-seg-chars">{{ seg.chars }} 字</span>
                      </div>
                      <p class="aic-seg-excerpt">{{ seg.excerpt }}</p>
                    </div>
                  </div>
                  <!-- 正常段 -->
                  <div v-else :data-seg="seg.index" class="aic-seg" :class="{ 'is-failed': seg.failed, 'is-active': activeSeg === seg.index }">
                    <span class="aic-seg-idx">#{{ seg.index }}</span>
                    <div class="aic-seg-main">
                      <div class="aic-seg-top">
                        <span v-if="seg.failed" class="aic-seg-tag is-failed">检测失败</span>
                        <span v-else :class="['aic-seg-tag', seg.is_ai_generated ? 'is-ai' : 'is-human']">
                          {{ seg.is_ai_generated ? 'AI' : '人工' }}
                        </span>
                        <span class="aic-seg-chars">{{ seg.chars }} 字</span>
                      </div>
                      <div v-if="!seg.failed" class="aic-seg-gauge-row">
                        <div class="aic-seg-bar">
                          <i :style="{ width: seg.scorePct + '%', background: seg.is_ai_generated ? 'linear-gradient(90deg,#f87171,#dc2626)' : 'linear-gradient(90deg,#38bdf8,#0284c7)' }"></i>
                        </div>
                        <span class="aic-seg-score" :class="seg.is_ai_generated ? 'is-ai' : 'is-human'">{{ seg.scorePct }}%</span>
                      </div>
                      <p class="aic-seg-excerpt" :class="{ 'is-full': expandedSeg === seg.index }">{{ expandedSeg === seg.index ? (seg.content || seg.excerpt) : seg.excerpt }}</p>
                      <button class="aic-seg-more" @click="toggleSegFull(seg.index)">{{ expandedSeg === seg.index ? '收起' : '查看全文' }}</button>
                    </div>
                  </div>
                </template>
                <p v-if="!allSegs.length" class="aic-segs-empty">暂无分段数据</p>
              </div>
            </template>
          </div>
        </div>

        <!-- ======================== 操作行（输入框+结果框共同下方，不受左列高度影响） ======================== -->
        <div class="aic-btn-row">
          <button class="aic-btn aic-btn--ghost" @click="clearAll">清空</button>
          <button class="aic-btn" :disabled="detecting" @click="submitDetect">
            <span v-if="!detecting" class="aic-btn-inner">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><path d="M12 12l5.5-5.5"/></svg>
              {{ detectBtnText }}
            </span>
            <span v-else class="aic-btn-inner"><span class="aic-btn-spinner"></span>检测中...</span>
          </button>
        </div>

        <!-- ======================== 信息区：计费口径 + 常见问题（紧凑版，紧跟按钮组下方） ======================== -->
        <div class="aic-info">
          <div class="aic-info-card">
            <div class="aic-info-head">
              <span class="aic-info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z" fill="currentColor"/></svg>
              </span>
              <h3 class="aic-info-title">计费与检测口径</h3>
            </div>
            <div class="aic-info-body">
              <div class="aic-bill-item">
                <div class="aic-bill-item-head">
                  <span class="aic-bill-badge aic-bill-badge--time">时长包</span>
                  <span class="aic-bill-item-label">优先扣 · 按小时限额</span>
                </div>
                <p class="aic-bill-item-desc">生效期间优先扣时长包，每小时有字数限额，超限自动切换</p>
              </div>
              <div class="aic-bill-item">
                <div class="aic-bill-item-head">
                  <span class="aic-bill-badge aic-bill-badge--char">字符包</span>
                  <span class="aic-bill-item-label">可扣 · 按实际字数</span>
                </div>
                <p class="aic-bill-item-desc">时长包未生效或余额不足时，按实际检测字数扣减字符包</p>
              </div>
              <div class="aic-bill-item">
                <div class="aic-bill-item-head">
                  <span class="aic-bill-badge aic-bill-badge--balance">余额支付</span>
                  <span class="aic-bill-item-label">兜底 · 千字符单价</span>
                </div>
                <p class="aic-bill-item-desc">包余额不足时，自动按<b v-if="billingMode === 'paid'">¥{{ pricePerChar.toFixed(2) }}/千字符</b><b v-else>单价</b>从账户余额扣费</p>
              </div>
              <div class="aic-bill-note">
                <span class="aic-bill-note-item"><i></i>自动提取正文按段送检</span>
                <span class="aic-bill-note-item"><i></i>整体 AI 率按字数加权</span>
                <span class="aic-bill-note-item"><i></i>结果仅作特征参考</span>
              </div>
            </div>
          </div>

          <div class="aic-info-card">
            <div class="aic-info-head">
              <span class="aic-info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              </span>
              <h3 class="aic-info-title">常见问题</h3>
            </div>
            <div class="aic-info-body">
              <div class="aic-faq-list">
                <div
                  v-for="(faq, idx) in faqList"
                  :key="idx"
                  class="aic-faq-item"
                  :class="{ expanded: faqOpen === idx }"
                >
                  <div class="aic-faq-question" @click="faqOpen = faqOpen === idx ? -1 : idx">
                    <span class="aic-faq-mark">?</span>
                    <span class="aic-faq-text">{{ faq.q }}</span>
                    <svg class="aic-faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                  <div class="aic-faq-answer">
                    <p>{{ faq.a }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
  </ToolShell>

  <!-- ==================== 新人福利领取弹窗 ==================== -->
  <Teleport to="body">
    <div v-if="freeClaimModal" class="afc-modal" @click.self="dismissFreeClaim">
      <div class="afc-dialog">
        <button class="afc-close" type="button" aria-label="关闭" @click="dismissFreeClaim">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <!-- 待领取 -->
        <template v-if="!freeClaimSuccess">
          <div class="afc-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
          </div>
          <h3 class="afc-title">新人专享福利</h3>
          <p class="afc-desc">免费领取 AI检测 <strong>{{ walletFreeClaim?.value_text || '' }}</strong></p>
          <div class="afc-tips">
            <span>每位用户仅限领取一次，领取后不可重复</span>
            <span>{{ walletFreeClaim?.type === 'time' ? '领取后自动顺延 AI检测时长包到期时间' : '领取后自动累加到字符包余额' }}</span>
          </div>
          <div class="afc-actions">
            <button class="afc-btn afc-btn--primary" type="button" :disabled="freeClaiming" @click="claimFree">{{ freeClaiming ? '领取中…' : '立即领取' }}</button>
            <button class="afc-btn afc-btn--ghost" type="button" @click="dismissFreeClaim">暂不领取</button>
          </div>
        </template>
        <!-- 已领取成功 -->
        <template v-else>
          <div class="afc-badge afc-badge--ok">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h3 class="afc-title">领取成功</h3>
          <p class="afc-desc">已到账 <strong>{{ walletFreeClaim?.value_text || '' }}</strong></p>
          <div class="afc-actions">
            <button class="afc-btn afc-btn--primary" type="button" @click="dismissFreeClaim">好的</button>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: 'AI检测 - AI写作助手' })

const api = useApi()
const toast = useToast()

const MAX_CHARS = 30000
const MAX_CHARS_LABEL = '30000'
const mode = ref('text')

// ── 检测平台（单选） ──
const platList = ref([])
const selectedPlat = ref('paperpass')

const selPlatInfo = computed(() => platList.value.find(p => p.key === selectedPlat.value) || null)

// 平台识别准确率（保留一位小数展示，如 97.1%）
function accPercent(v) {
  return (Math.round((Number(v) || 0) * 1000) / 10).toFixed(1)
}

// 效果报告行色调：判定一致=ok（绿），不一致=warn（橙红）
function rowTone(r) {
  const parts = String(r?.label || '').split('→')
  return parts.length === 2 && parts[0].trim() === parts[1].trim() ? 'ok' : 'warn'
}

// 十六进制颜色转 rgba（用于平台彩色卡片的选中态 / 结果区底色）
function hexA(hex, alpha) {
  if (!hex) return 'rgba(2, 132, 199, ' + alpha + ')'
  const h = String(hex).replace('#', '')
  if (h.length !== 6) return 'rgba(2, 132, 199, ' + alpha + ')'
  return 'rgba(' + parseInt(h.slice(0, 2), 16) + ',' + parseInt(h.slice(2, 4), 16) + ',' + parseInt(h.slice(4, 6), 16) + ',' + alpha + ')'
}

// ── 计费（AI检测独立单价 ai_check.char_price，wallet 接口 /api/ai_check/wallet） ──
const wallet = ref(null)
const walletMoney = computed(() => Number(wallet.value?.user_money || 0))
const walletChar = computed(() => Number(wallet.value?.char_balance || 0))
const pricePerChar = computed(() => Number(wallet.value?.char_price || 0))
const billingMode = computed(() => wallet.value?.mode || 'free')
const timeActive = computed(() => Boolean(wallet.value?.time_active))
const timeBalanceSec = computed(() => Number(wallet.value?.time_balance_sec || 0))
const timeHourUsed = computed(() => Number(wallet.value?.time_hour_used_chars || 0))
const timeHourLimit = computed(() => Number(wallet.value?.time_hour_limit || 0))

// 支付方式：time=时长包(生效时显示) | char=字符包(有余量时显示) | balance=余额支付(按千字符单价扣)
const payMethod = ref('balance')
const payMethodOptions = computed(() => {
  const opts = []
  if (timeActive.value) {
    const hourDesc = timeHourLimit.value > 0
      ? `本小时 ${timeHourUsed.value.toLocaleString()}/${timeHourLimit.value.toLocaleString()} 字`
      : `本小时已用 ${timeHourUsed.value.toLocaleString()} 字（不限）`
    opts.push({ value: 'time', label: '时长包', desc: `剩余 ${formatTimeRemain(timeBalanceSec.value)} · ${hourDesc}` })
  }
  if (walletChar.value > 0) {
    opts.push({ value: 'char', label: '字符包', desc: `剩余 ${walletChar.value.toLocaleString()} 字，按实际字数扣` })
  }
  opts.push({
    value: 'balance',
    label: '余额支付',
    desc: billingMode.value === 'paid' ? `¥${pricePerChar.value.toFixed(2)}/千字符 · 余额 ¥${walletMoney.value.toFixed(2)}` : `按单价扣 · 余额 ¥${walletMoney.value.toFixed(2)}`,
  })
  return opts
})

// 时长包生效/失效时的默认迁移：生效→time；失效→按优先级回退（字符包有余量→char，否则 balance）
watch(timeActive, (active) => {
  if (payMethodTouched.value) {
    // 用户手动选过：仅在当前方式失效时兜底回退
    if (!active && payMethod.value === 'time') payMethod.value = walletChar.value > 0 ? 'char' : 'balance'
    return
  }
  applyDefaultPayMethod()
})

// 预估金额（仅用于余额预检，按钮不再展示价格）：按文本字数 × 千字符单价（不满 1000 字按 1000 字计）
function calcEstimate(chars) {
  const w = Number(chars) || 0
  if (billingMode.value !== 'paid' || !pricePerChar.value || w <= 0) return 0
  const billableUnits = Math.ceil(w / 1000)
  const fen = Math.ceil(billableUnits * pricePerChar.value * 100)
  return fen / 100
}
const estAmount = computed(() => calcEstimate(mode.value === 'text' ? pasteText.value.length : 0))

const detectBtnText = computed(() => {
  if (billingMode.value !== 'paid' || !pricePerChar.value) return '开始检测（免费）'
  return '开始检测'
})

function formatTimeRemain(sec) {
  const s = Number(sec) || 0
  if (s <= 0) return '0分钟'
  const day = 86400, hour = 3600, minute = 60
  const days = Math.floor(s / day)
  const hours = Math.floor((s % day) / hour)
  const minutes = Math.floor((s % hour) / minute)
  if (days > 0) return days + '天' + (hours > 0 ? hours + '小时' : '')
  if (hours > 0) return hours + '小时' + (minutes > 0 ? minutes + '分钟' : '')
  return minutes + '分钟'
}

async function fetchWallet() {
  try {
    const res = await api.get('/api/ai_check/wallet')
    if (res.ok) {
      wallet.value = res.data
      applyDefaultPayMethod()
    }
  } catch (e) { /* 钱包加载失败时保持默认 */ }
}

// 支付方式默认优先级：时长包（生效）> 字符包（有余量）> 余额支付
// payMethodTouched：用户手动切换过支付方式后，不再自动改默认（仅后续 time 失效回退兜底）
const payMethodTouched = ref(false)
function applyDefaultPayMethod() {
  if (payMethodTouched.value) return
  if (timeActive.value) payMethod.value = 'time'
  else if (walletChar.value > 0) payMethod.value = 'char'
  else payMethod.value = 'balance'
}

// ── 免费领取额度（每用户限一次，额度由管理员后台配置；点击钱包按钮弹窗，人工确认领取） ──
const walletFreeClaim = computed(() => wallet.value?.free_claim || null)
const freeClaimEnabled = computed(() => Boolean(walletFreeClaim.value?.enabled))
const freeClaimClaimed = computed(() => Boolean(walletFreeClaim.value?.claimed))
const freeClaiming = ref(false)
const freeClaimModal = ref(false)
const freeClaimSuccess = ref(false)

function openFreeClaim() {
  // 已领取（含其他端已领）不允许再打开领取弹窗
  if (freeClaimClaimed.value) return
  freeClaimSuccess.value = false
  freeClaimModal.value = true
}

function dismissFreeClaim() {
  if (freeClaiming.value) return
  freeClaimModal.value = false
  // 「暂不领取」：本次浏览器会话内不再自动弹出（领取成功后无需再标记，claimed 状态会拦截）
  try { sessionStorage.setItem('aic_free_claim_dismissed', '1') } catch (e) { /* 忽略隐私模式 */ }
}

async function claimFree() {
  if (freeClaimClaimed.value || freeClaiming.value) return
  freeClaiming.value = true
  try {
    const res = await api.post('/api/ai_check/claimFree')
    if (res.ok) {
      freeClaimSuccess.value = true
      await fetchWallet()
    } else {
      toast.error(res.msg || '领取失败')
      fetchWallet()
    }
  } catch (e) {
    toast.error('领取失败，请稍后重试')
  } finally {
    freeClaiming.value = false
  }
}

// 领取到账（含其他端领取）后：若弹窗仍开着，强制切到成功态，杜绝再次领取入口
watch(freeClaimClaimed, (claimed) => {
  if (claimed && freeClaimModal.value) freeClaimSuccess.value = true
})

// 进入页面 wallet 加载后：活动开启且未领取时自动弹窗一次（弹窗仅展示，领取必须人工点击「立即领取」）
const freeClaimAutoShown = ref(false)
watch(freeClaimEnabled, (enabled) => {
  if (!enabled || freeClaimClaimed.value || freeClaimAutoShown.value) return
  let dismissed = false
  try { dismissed = sessionStorage.getItem('aic_free_claim_dismissed') === '1' } catch (e) { /* 忽略 */ }
  if (dismissed) return
  freeClaimAutoShown.value = true
  freeClaimModal.value = true
}, { immediate: true })

// ── 结果派生 ──
const result = ref(null)
const detecting = ref(false)
// H5（m 壳）原文/结果 Tab 切换：<640 双栏过长，收成单屏 Tab；样式由 .m-main 门控，PC/console 窄窗口不受影响
const mPane = ref('input')
const streamProgress = ref({ done: 0, total: 0 })
const isAi = computed(() => !!result.value?.is_ai_generated)
// 分段判定统计（仅送检段；跳过段不计入）
const okSegCount = computed(() => distSegs.value.filter(s => !s.failed).length)
const aiSegCount = computed(() => distSegs.value.filter(s => !s.failed && s.is_ai_generated).length)

const selPlatResult = computed(() => (result.value?.platforms || [])[0] || null)
const selPlatPercent = computed(() => selPlatResult.value ? selPlatResult.value.ai_percent : (result.value ? Math.round(result.value.ai_score * 100) : 0))
const mainPercent = computed(() => selPlatPercent.value)

const gaugeCircum = 2 * Math.PI * 52
const gaugeOffset = computed(() => gaugeCircum * (1 - Math.min(mainPercent.value, 100) / 100))

// 多段：圆环显示 AI 段占比（判定 AI 段数 ÷ 送检段数，段落维度，非字数加权）
const segPct = computed(() => {
  const total = okSegCount.value
  return total > 0 ? Math.round(aiSegCount.value / total * 100) : 0
})
const segGaugeOffset = computed(() => gaugeCircum * (1 - Math.min(segPct.value, 100) / 100))

// 全部段落（列表渲染：送检段 + 跳过段提示行）
const allSegs = computed(() => {
  if (!result.value?.segments?.length) return []
  return result.value.segments.map(seg => ({
    index: seg.index,
    chars: seg.chars,
    skipped: !!seg.skipped,
    pending: !!seg._pending,
    failed: !!seg.failed,
    is_ai_generated: !!seg.is_ai_generated,
    excerpt: seg.excerpt,
    content: seg.content || '',
    scorePct: seg.score === null || seg.score === undefined ? 0 : Math.round((seg.score || 0) * 1000) / 10,
  }))
})

// AI 分布条：仅送检段，按各段字符数占比着色
const distSegs = computed(() => {
  const segs = allSegs.value.filter(s => !s.skipped)
  const total = segs.reduce((s, x) => s + (x.chars || 0), 0)
  return segs.map(seg => ({
    ...seg,
    percent: total ? Math.max((seg.chars || 0) / total * 100, 0.15) : 0.15,
  }))
})

// 分段筛选 + 分布条联动（多段落时按判定快速过滤、点击分布格定位分段卡）
const isSingle = computed(() => distSegs.value.length === 1 && allSegs.value.length === 1)
const segFilter = ref('all')
const activeSeg = ref(-1)
const failedSegCount = computed(() => distSegs.value.filter(s => s.failed).length)
const skippedSegCount = computed(() => allSegs.value.filter(s => s.skipped).length)
const filteredSegs = computed(() => {
  if (segFilter.value === 'ai') return allSegs.value.filter(s => !s.skipped && !s.failed && s.is_ai_generated)
  if (segFilter.value === 'human') return allSegs.value.filter(s => !s.skipped && !s.failed && !s.is_ai_generated)
  return allSegs.value
})
function focusSeg(idx) {
  activeSeg.value = activeSeg.value === idx ? -1 : idx
  nextTick(() => {
    const el = document.querySelector(`[data-seg="${idx}"]`)
    if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  })
}

// 段落全文展开/收起（单开互斥）
const expandedSeg = ref(-1)
function toggleSegFull(idx) {
  expandedSeg.value = expandedSeg.value === idx ? -1 : idx
}

async function loadPlatforms() {
  try {
    const r = await api.get('/api/ai_check/platforms')
    if (r.ok && Array.isArray(r.data?.platforms)) {
      platList.value = r.data.platforms
      if (!platList.value.some(p => p.key === selectedPlat.value)) {
        selectedPlat.value = platList.value[0]?.key || 'paperpass'
      }
    }
  } catch (e) { /* 平台列表加载失败时保持默认 */ }
}

function switchMode(m) {
  mode.value = m
}

// ── 常见问题（信息区手风琴，默认展开第一条） ──
const faqOpen = ref(0)
const faqList = [
  { q: '整体 AI 率是怎么算的？', a: '系统将正文按段落拆分后逐段送检，再按各段字数加权汇总得到整篇 AI 率，长文短段都能稳定反映整体特征。' },
  { q: '为什么要分段检测？', a: '模型对长文本的判断会被"稀释"，按段送检能精确定位 AI 痕迹集中的位置，便于针对性修改后再复检。' },
  { q: '支持哪些文件格式？', a: '支持粘贴文本，或上传 .docx / .doc / .txt 文档（50MB 以内），自动提取正文并按段送检，单次最多 30000 字。' },
  { q: '检测会保存我的文本吗？', a: '检测仅在当次会话中展示结果，页面刷新后不保留原文与报告，请及时复制需要的分段信息。' },
  { q: '不同平台结果为什么有差异？', a: '各平台算法与训练语料不同，同一文本的 AI 率可能存在差异，建议以最终提交对象要求的平台为准。' },
]

// ── 上传 ──
const fileInput = ref(null)
const isDragging = ref(false)
const uploadedFile = ref(null)
const uploadedUrl = ref('')
const uploading = ref(false)
const uploadProgress = ref(0)
const pendingFileName = ref('')
const pendingFileSize = ref('')

async function handleFileSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  await uploadFile(file)
}

async function handleDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (!file) return
  await uploadFile(file)
}

async function uploadFile(file) {
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['docx', 'doc', 'txt'].includes(ext)) {
    toast.error('请上传 .docx / .doc / .txt 格式文件')
    return
  }
  if (file.size > 50 * 1024 * 1024) {
    toast.error('文件大小不能超过 50MB')
    return
  }

  pendingFileName.value = file.name
  pendingFileSize.value = formatFileSize(file.size)
  uploading.value = true
  uploadProgress.value = 0
  try {
    const sigRes = await api.post('/api/ai_check/getUploadSignature', { ext })
    if (!sigRes.ok) {
      toast.error(sigRes.msg || '获取上传签名失败')
      return
    }
    const sig = sigRes.data

    await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      xhr.open('PUT', sig.put_url)
      xhr.setRequestHeader('Content-Type', sig.content_type)
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) uploadProgress.value = Math.round((e.loaded / e.total) * 100)
      }
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) resolve()
        else reject(new Error(`上传失败 HTTP ${xhr.status}`))
      }
      xhr.onerror = () => reject(new Error('网络错误'))
      xhr.send(file)
    })

    uploadedFile.value = file
    uploadedUrl.value = sig.download_url
    toast.success('上传成功')
  } catch (e) {
    toast.error(e.message || '上传失败')
  } finally {
    uploading.value = false
    pendingFileName.value = ''
    pendingFileSize.value = ''
  }
}

function resetUpload() {
  uploadedFile.value = null
  uploadedUrl.value = ''
  uploadProgress.value = 0
  pendingFileName.value = ''
  pendingFileSize.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

// ── 提交检测（本站为同步接口：上游 openapi 一次性返回整体 AI 率与逐段明细，等待期展示分析动效） ──
const pasteText = ref('')

async function submitDetect() {
  if (detecting.value) return
  if (mode.value === 'text' && pasteText.value.trim().length === 0) {
    toast.error('请粘贴待检测文本')
    return
  }
  if (mode.value === 'file' && !uploadedUrl.value) {
    toast.error('请先上传文档')
    return
  }
  if (!selectedPlat.value) {
    toast.error('请选择检测平台')
    return
  }
  // 余额支付余额预检（仅文本模式可估算；文档模式交由后端校验）
  if (payMethod.value === 'balance' && billingMode.value === 'paid' && estAmount.value > 0 && walletMoney.value < estAmount.value) {
    toast.error(`余额不足，当前余额 ¥${walletMoney.value.toFixed(2)}，需支付 ¥${estAmount.value.toFixed(2)}`)
    return
  }

  detecting.value = true
  result.value = null
  // H5：提交后切到结果页看逐段进度（PC 双栏无此需要，状态变更无副作用）
  mPane.value = 'result'
  streamProgress.value = { done: 0, total: 0 }
  segFilter.value = 'all'
  activeSeg.value = -1
  expandedSeg.value = -1
  try {
    const payload = { platform_keys: selectedPlat.value, pay_method: payMethod.value }
    if (mode.value === 'text') payload.text = pasteText.value
    else payload.file_url = uploadedUrl.value

    const res = await api.post('/api/ai_check/detect', payload)
    if (res.ok && res.data) {
      result.value = res.data
      streamProgress.value = { done: res.data.chunk_count, total: res.data.chunk_count }
      toast.success('检测完成')
    } else {
      toast.error(res.msg || '检测失败，请稍后重试')
    }
  } catch (e) {
    toast.error('网络异常，检测中断，请重试')
  } finally {
    detecting.value = false
    fetchWallet()
  }
}

function clearAll() {
  pasteText.value = ''
  result.value = null
  resetUpload()
  // H5：清空后回到原文输入页（m 壳 Tab；PC 双栏该状态无副作用）
  mPane.value = 'input'
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
}

onMounted(() => {
  loadPlatforms()
  fetchWallet()
})
</script>

<style scoped>
/* ================= 整体布局 ================= */
/* 自然高度布局：不拉伸内容区撑高页面，各列按内容自适应 */
.aic-layout {
  position: relative;
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
}

/* ================= 顶部公告横幅（跑马灯，参照 AI降重，置于右侧内容区顶部） ================= */
.aic-notice {
  flex: 1 1 100%;
  width: 100%;
  flex-shrink: 0;
  border-radius: 9px;
  padding: 3px 12px;
  font-size: 11px;
  line-height: 1.5;
  overflow: hidden;
  box-sizing: border-box;
  border: 1px solid rgba(14, 165, 233, 0.2);
  background: linear-gradient(90deg, rgba(14, 165, 233, 0.08) 0%, rgba(14, 165, 233, 0.02) 100%);
}
.aic-notice-track { width: 100%; overflow: hidden; white-space: nowrap; }
.aic-notice-text {
  display: inline-block;
  font-size: 11px;
  line-height: 1.5;
  font-weight: 600;
  color: #0284c7;
  padding-left: 100%;
  animation: aic-notice-scroll 24s linear infinite;
}
@keyframes aic-notice-scroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-100%); }
}

/* ================= 顶部工具栏：模式切换（右侧内容区顶部，独立成行） ================= */
.aic-toolbar {
  flex: 1 1 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.aic-tabs {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  border-radius: 999px;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  width: fit-content;
}
.aic-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--gray-500);
  padding: 7px 16px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
}
.aic-tab svg { width: 14px; height: 14px; }
.aic-tab:hover:not(.active) { color: #0284c7; background: rgba(14, 165, 233, 0.06); }
.aic-tab.active {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: #fff;
  box-shadow: 0 2px 10px rgba(2, 132, 199, 0.22);
}
.aic-toolbar-tip {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0;
  font-size: 11.5px;
  color: var(--gray-400);
  font-weight: 500;
}
.aic-toolbar-tip svg { width: 13px; height: 13px; color: #0ea5e9; flex-shrink: 0; }

/* ================= 三列工作区 ================= */
/* 自然高度：列不互相拉伸，输入/结果区固定高度（两种模式等高，切换 tab 页面高度不变） */
.aic-cols {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.aic-col {
  display: flex;
  flex-direction: column;
  background: var(--white);
  padding: 14px;
  border-radius: 12px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.aic-col:hover {
  border-color: rgba(14, 165, 233, 0.25);
  box-shadow: 0 4px 16px rgba(2, 132, 199, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03);
}
/* 中右区包裹层：输入+结果并排，操作行换行贴两框底部，与左列高度解耦 */
.aic-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-flow: row wrap;
  gap: 12px;
}
.aic-col--input, .aic-col--result { flex: 1 1 calc(50% - 6px); min-width: 0; }

/* ========== 列 1：计费与检测平台（参照 AI降重左侧面板） ========== */
.aic-options {
  flex: 0 0 320px;
  min-width: 290px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  position: sticky;
  top: 12px;
}

/* 钱包卡 */
.aic-wallet {
  position: relative; /* 配额角标（右上角）+ hover 弹层定位锚点 */
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  border-radius: 12px;
  padding: 14px;
  color: #fff;
  box-shadow: 0 6px 18px rgba(2, 132, 199, 0.22), 0 2px 4px rgba(2, 132, 199, 0.12);
}
.aic-wallet-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.aic-wallet-icon {
  width: 26px;
  height: 26px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.aic-wallet-icon svg { width: 16px; height: 16px; color: #fff; }
.aic-wallet-title { font-size: 13px; font-weight: 600; color: rgba(255, 255, 255, 0.95); }
.aic-wallet-mode {
  margin-left: auto;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.92);
  letter-spacing: 0.02em;
}
.aic-wallet-mode.free { background: rgba(255, 255, 255, 0.18); }
.aic-wallet-mode.paid { background: rgba(255, 255, 255, 0.28); }
/* 配额角标：钱包卡右上角绝对定位，hover 展示配额信息（暖色实心、显眼） */
.aic-col-head-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
}
.aic-quota-chip {
  position: absolute;
  top: 10px;
  right: 12px;
  z-index: 5;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  padding: 3px 11px;
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #7c2d12;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  border-radius: 999px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.22);
  transition: all 0.2s ease;
}
.aic-quota-chip:hover {
  color: #7c2d12;
  background: linear-gradient(135deg, #fcd34d, #fbbf24);
  box-shadow: 0 0 12px rgba(251, 191, 36, 0.6), 0 2px 8px rgba(0, 0, 0, 0.18);
  transform: translateY(-1px);
}
/* 配额 hover 弹层（相对钱包卡定位，从右上角角标下方展开） */
.aic-quota-pop {
  position: absolute;
  top: 38px;
  right: 12px;
  z-index: 30;
  width: 250px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18), 0 4px 12px rgba(15, 23, 42, 0.08);
  color: #1e293b;
}
.aic-quota-pop::before {
  content: '';
  position: absolute;
  top: -5px;
  right: 40px;
  width: 10px;
  height: 10px;
  background: #fff;
  border-left: 1px solid #e2e8f0;
  border-top: 1px solid #e2e8f0;
  transform: rotate(45deg);
}
.aic-quota-pop-head { margin-bottom: 9px; }
.aic-quota-pop-title { font-size: 12px; font-weight: 700; color: #0f172a; }
.aic-quota-pop-sub { display: block; margin-top: 3px; font-size: 10px; color: #94a3b8; letter-spacing: 0.02em; }
.aic-quota-pop-list { display: flex; flex-direction: column; gap: 10px; }
.aic-quota-pop-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.aic-quota-pop-label { font-size: 11px; font-weight: 600; color: #1e293b; }
.aic-quota-pop-used { font-size: 10.5px; color: #64748b; font-variant-numeric: tabular-nums; }
.aic-quota-pop-bar {
  height: 5px;
  border-radius: 99px;
  background: rgba(2, 132, 199, 0.12);
  overflow: hidden;
}
.aic-quota-pop-fill {
  display: block;
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, #38bdf8, #0284c7);
}
.aic-quota-pop-fill.is-over { background: linear-gradient(90deg, #f87171, #ef4444); }
.aic-quota-pop-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 3px; }
.aic-quota-pop-pct { font-size: 10.5px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
.aic-quota-pop-pct.is-over { color: #ef4444; }
.aic-quota-pop-remain { font-size: 10px; color: #64748b; font-variant-numeric: tabular-nums; }
.aic-quota-pop-empty { font-size: 11px; color: #64748b; text-align: center; padding: 6px 0; }
.quota-pop-enter-active,
.quota-pop-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.quota-pop-enter-from,
.quota-pop-leave-to { opacity: 0; transform: translateY(-4px); }
.aic-wallet-body {
  background: rgba(255, 255, 255, 0.14);
  border-radius: 9px;
  padding: 9px 12px;
  margin-bottom: 10px;
}
.aic-wallet-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }
.aic-wallet-row + .aic-wallet-row { border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 6px; margin-top: 2px; }
.aic-wallet-row--price {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  padding: 7px 10px;
  margin-bottom: 4px;
}
.aic-wallet-row--price.free { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.1); }
.aic-wallet-row--price + .aic-wallet-row { border-top: none; padding-top: 4px; margin-top: 0; }
.aic-wallet-row--price .aic-wallet-value { font-size: 16px; font-weight: 800; }
.aic-wallet-row--price .aic-wallet-value em { font-size: 10px; }
.aic-wallet-row--packs {
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  gap: 0;
  padding: 7px 0 3px;
}
.aic-wallet-pack {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 0 6px;
  text-align: center;
}
.aic-wallet-pack .aic-wallet-label { align-items: center; }
.aic-wallet-pack-sep { width: 1px; background: rgba(255, 255, 255, 0.14); flex-shrink: 0; margin: 2px 0; }
.aic-wallet-pack-value {
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
  white-space: nowrap;
}
.aic-wallet-pack-value em { font-style: normal; font-size: 10px; font-weight: 600; color: rgba(255, 255, 255, 0.6); margin-left: 2px; }
.aic-wallet-label { font-size: 11.5px; color: rgba(255, 255, 255, 0.85); display: flex; flex-direction: column; gap: 1px; }
.aic-wallet-sub { font-size: 9.5px; color: rgba(255, 255, 255, 0.55); font-weight: 500; }
.aic-wallet-value { font-size: 13.5px; font-weight: 700; color: #fff; font-variant-numeric: tabular-nums; }
.aic-wallet-value em { font-style: normal; font-size: 10px; font-weight: 600; color: rgba(255, 255, 255, 0.6); margin-left: 2px; }
.aic-wallet-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 9px;
  background: rgba(255, 255, 255, 0.95);
  border: none;
  border-radius: 8px;
  color: #0284c7;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.aic-wallet-btn:hover { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12); }

/* 免费领取额度（每用户限一次） */
.aic-wallet-free {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  margin-bottom: 6px;
  padding: 8px 9px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px dashed rgba(255, 255, 255, 0.45);
  border-radius: 8px;
  color: #fff;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.aic-wallet-free svg { width: 13px; height: 13px; flex-shrink: 0; }
.aic-wallet-free:hover:not(:disabled) { background: rgba(255, 255, 255, 0.26); border-color: rgba(255, 255, 255, 0.7); transform: translateY(-1px); }
.aic-wallet-free:disabled { cursor: default; }
.aic-wallet-free.claimed { background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); color: rgba(255, 255, 255, 0.6); font-weight: 500; }

/* ================= 新人福利领取弹窗 ================= */
.afc-modal {
  position: fixed;
  inset: 0;
  z-index: 2100;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(3px);
  animation: afc-fade 0.18s ease;
}
@keyframes afc-fade { from { opacity: 0; } }
@keyframes afc-pop { from { opacity: 0; transform: translateY(14px) scale(0.96); } }
.afc-dialog {
  position: relative;
  width: 340px;
  max-width: calc(100vw - 40px);
  background: #fff;
  border-radius: 14px;
  padding: 26px 24px 20px;
  text-align: center;
  box-shadow: 0 20px 50px rgba(2, 60, 100, 0.28);
  animation: afc-pop 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.afc-close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}
.afc-close:hover { background: #f1f5f9; color: #475569; }
.afc-close svg { width: 15px; height: 15px; }
.afc-badge {
  width: 58px;
  height: 58px;
  margin: 0 auto 12px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background: linear-gradient(135deg, #38bdf8, #0284c7);
  box-shadow: 0 8px 18px rgba(2, 132, 199, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.35);
}
.afc-badge svg { width: 27px; height: 27px; }
.afc-badge--ok { background: linear-gradient(135deg, #34d399, #059669); box-shadow: 0 8px 18px rgba(5, 150, 105, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.35); }
.afc-title { margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0f172a; }
.afc-desc { margin: 0 0 14px; font-size: 12.5px; color: #475569; }
.afc-desc strong { color: #0284c7; font-size: 15px; font-weight: 700; }
.afc-tips {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 10px 12px;
  border-radius: 9px;
  background: #f8fafc;
  border: 1px dashed #e2e8f0;
  text-align: left;
}
.afc-tips span {
  position: relative;
  padding-left: 13px;
  font-size: 11px;
  line-height: 1.5;
  color: #64748b;
}
.afc-tips span::before {
  content: '';
  position: absolute;
  left: 2px;
  top: 6.5px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #7dd3fc;
}
.afc-actions { display: flex; align-items: stretch; gap: 10px; margin-top: 16px; }
.afc-btn {
  flex: 1;
  height: 38px;
  min-width: 0;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.15s ease, border-color 0.15s ease;
}
.afc-btn--primary {
  border: none;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
  box-shadow: 0 6px 14px rgba(2, 132, 199, 0.3);
}
.afc-btn--primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2, 132, 199, 0.38); }
.afc-btn--primary:disabled { opacity: 0.6; cursor: default; }
.afc-btn--ghost {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-weight: 500;
}
.afc-btn--ghost:hover { border-color: #cbd5e1; color: #334155; background: #f8fafc; }

/* 配置卡 */
.aic-config-card {
  background: var(--white);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.aic-config-card:hover {
  border-color: rgba(14, 165, 233, 0.25);
  box-shadow: 0 4px 16px rgba(2, 132, 199, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03);
}
.aic-config-title {
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
.aic-config-hint {
  font-size: 10px;
  font-weight: 600;
  color: var(--gray-400);
  background: var(--gray-50);
  padding: 1px 8px;
  border-radius: 999px;
  white-space: nowrap;
  letter-spacing: 0;
}

/* 胶囊按钮组 */
.aic-chips { display: flex; gap: 7px; flex-wrap: wrap; }
.aic-chip {
  padding: 5px 11px;
  border-radius: 18px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid var(--gray-200);
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--white);
  color: var(--gray-600);
  font-family: inherit;
}
.aic-chip:hover:not(.active) { border-color: rgba(14, 165, 233, 0.4); color: #0284c7; }
.aic-chip.active {
  border-color: #0284c7;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
}
/* 平台胶囊：内置"荐"角标，选中后角标反白 */
.aic-chip--platform { display: inline-flex; align-items: center; }
.aic-chip--platform .aic-tag { margin-left: 5px; }
.aic-chip--platform.active .aic-tag { background: rgba(255, 255, 255, 0.95); color: #0284c7; box-shadow: none; }
.aic-col-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 10px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--gray-100);
}
.aic-col-ico {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(14, 165, 233, 0.1);
  color: #0ea5e9;
  flex-shrink: 0;
}
.aic-col-ico svg { width: 14px; height: 14px; }
.aic-col-ico--result { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
.aic-col-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--dark-900);
}
.aic-clear-mini {
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 600;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.06);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.aic-clear-mini:hover { background: #ef4444; color: #fff; }

.aic-plats-empty {
  flex: 1 0 100%;
  padding: 14px;
  text-align: center;
  font-size: 12px;
  color: var(--gray-400);
  background: var(--gray-50);
  border: 1px dashed var(--gray-200);
  border-radius: 9px;
}
.aic-tag {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  font-size: 10px;
  font-weight: 700;
  line-height: 1.2;
  color: #fff;
  background: linear-gradient(135deg, #fb923c, #f97316);
  border-radius: 999px;
  box-shadow: 0 1px 3px rgba(249, 115, 22, 0.35);
  flex-shrink: 0;
}

/* ========== 列 1：所选平台能力 ========== */
.aic-cap {
  flex-shrink: 0;
  padding: 11px 12px;
  border-radius: 10px;
  border: 1px solid var(--gray-100);
}
.aic-cap-head {
  display: flex;
  align-items: center;
  gap: 7px;
}
.aic-cap-name { font-size: 12.5px; font-weight: 700; color: var(--dark-900); }
.aic-cap-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 700;
  color: var(--gray-400);
  background: rgba(255, 255, 255, 0.7);
  padding: 1px 8px;
  border-radius: 999px;
  white-space: nowrap;
}
.aic-cap-main {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
}
.aic-cap-num {
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -1px;
  flex-shrink: 0;
}
.aic-cap-num em { font-size: 14px; font-style: normal; font-weight: 700; letter-spacing: 0; }
.aic-cap-desc {
  flex: 1;
  min-width: 0;
  font-size: 11px;
  line-height: 1.6;
  color: var(--gray-500);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
/* 效果报告对照表（平台判定 × 本站检测） */
.aic-cap-report {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.aic-cap-sample { font-size: 10px; font-weight: 500; color: var(--gray-500); margin-bottom: 2px; }
.aic-cap-thead {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 2px 4px;
  border-bottom: 1px dashed rgba(15, 23, 42, 0.1);
  font-size: 10px;
  font-weight: 700;
  color: var(--gray-400);
}
.aic-cap-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 7px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.75);
  border: 1px solid rgba(15, 23, 42, 0.045);
}
.aic-cap-row-label { font-size: 11px; font-weight: 600; color: var(--dark-700); }
.aic-cap-row-data { display: inline-flex; align-items: baseline; gap: 6px; font-variant-numeric: tabular-nums; }
.aic-cap-row-data em { font-style: normal; font-size: 10px; font-weight: 500; color: var(--gray-400); }
.aic-cap-row-data b { font-size: 11px; font-weight: 800; }
.aic-cap-row.ok .aic-cap-row-data b { color: #059669; }
.aic-cap-row.warn .aic-cap-row-data b { color: #ea580c; }
.aic-cap-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 3px;
  padding: 5px 7px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(15, 23, 42, 0.07);
}
.aic-cap-total > span:first-child { font-size: 11px; font-weight: 800; color: var(--dark-900); }
.aic-cap-total-data { display: inline-flex; align-items: baseline; gap: 6px; font-variant-numeric: tabular-nums; }
.aic-cap-total-data em { font-style: normal; font-size: 10px; font-weight: 500; color: var(--gray-400); }
.aic-cap-total-data b { font-size: 12.5px; font-weight: 800; }

/* ========== 列 2：输入区 ========== */
.aic-input-wrap {
  position: relative;
  display: flex;
  flex-direction: column;
}
.aic-textarea {
  width: 100%;
  height: 440px;
  padding: 11px 12px 30px;
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
.aic-textarea:focus {
  border-color: #0ea5e9;
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.14);
}
.aic-textarea::placeholder { color: var(--gray-400); font-size: 12.5px; }
/* 输入框细圆角滚动条 */
.aic-textarea {
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 116, 139, 0.3) transparent;
}
.aic-textarea::-webkit-scrollbar { width: 6px; }
.aic-textarea::-webkit-scrollbar-track { background: transparent; }
.aic-textarea::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.22);
  border-radius: 999px;
  transition: background 0.2s ease;
}
.aic-textarea::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.42); }
.aic-textarea::-webkit-scrollbar-corner { background: transparent; }
.aic-wordcount {
  position: absolute;
  bottom: 10px;
  right: 14px;
  font-size: 11px;
  color: var(--gray-400);
  background: rgba(255, 255, 255, 0.85);
  padding: 2px 8px;
  border-radius: 6px;
  pointer-events: none;
  font-variant-numeric: tabular-nums;
}
.aic-wordcount.near { color: #f59e0b; font-weight: 600; }

/* ========== 列 2：上传区 ========== */
.aic-dropzone {
  border: 2px dashed var(--gray-200);
  border-radius: 10px;
  background: #fcfdff;
  height: 440px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-sizing: border-box;
  padding: 16px;
}
.aic-dropzone:hover, .aic-dropzone.is-over {
  border-color: rgba(14, 165, 233, 0.5);
  background: rgba(14, 165, 233, 0.03);
}
.aic-dropzone.is-done { border-style: solid; border-color: var(--gray-100); cursor: default; }
.aic-dropzone-empty { display: flex; flex-direction: column; align-items: center; gap: 9px; }
.aic-dropzone-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(14, 165, 233, 0.06);
  border: 1px solid rgba(14, 165, 233, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0ea5e9;
  transition: all 0.25s ease;
}
.aic-dropzone:hover .aic-dropzone-circle {
  background: rgba(14, 165, 233, 0.12);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(14, 165, 233, 0.15);
}
.aic-dropzone-hint { font-size: 13.5px; font-weight: 700; color: var(--dark-800); margin: 0; }
.aic-dropzone-sub { font-size: 11.5px; color: var(--gray-500); margin: 0; }
.aic-dropzone-file { display: flex; flex-direction: column; align-items: center; gap: 12px; width: 100%; max-width: 340px; }
.aic-file-row { display: flex; align-items: center; gap: 12px; width: 100%; }
.aic-file-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(14, 165, 233, 0.05);
  border: 1px solid rgba(14, 165, 233, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0ea5e9;
  flex-shrink: 0;
}
.aic-file-icon--loading { animation: aic-pulse 1.6s ease-in-out infinite; }
.aic-file-icon--ok { background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.15); color: #10b981; }
.aic-file-meta { min-width: 0; flex: 1; }
.aic-file-name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--dark-800);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.aic-file-size { font-size: 11.5px; color: var(--gray-500); margin-top: 2px; }
.aic-progress {
  width: 100%;
  height: 6px;
  background: var(--gray-100);
  border-radius: 3px;
  overflow: hidden;
}
.aic-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #0ea5e9, #0284c7);
  border-radius: 3px;
  transition: width 0.25s ease;
}
.aic-file-state {
  font-size: 12px;
  font-weight: 600;
  color: #0284c7;
  background: rgba(14, 165, 233, 0.06);
  padding: 4px 12px;
  border-radius: 6px;
}
.aic-file-state--ok { color: #10b981; background: rgba(16, 185, 129, 0.06); }

/* ========== 列 3：结果区 ========== */

.aic-result-body {
  height: 440px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-right: 2px;
  /* 细圆角滚动条 */
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 116, 139, 0.3) transparent;
}
.aic-result-body::-webkit-scrollbar { width: 6px; }
.aic-result-body::-webkit-scrollbar-track { background: transparent; }
.aic-result-body::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.22);
  border-radius: 999px;
  transition: background 0.2s ease;
}
.aic-result-body::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.42); }
.aic-result-body::-webkit-scrollbar-corner { background: transparent; }
.aic-result-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  flex: 1;
  min-height: 200px;
  color: var(--gray-400);
  font-size: 12.5px;
  font-weight: 500;
  text-align: center;
  padding: 0 14px;
}
/* 空态-品牌引擎行 */
.aic-hero { display: flex; align-items: center; gap: 10px; }
.aic-hero-ico {
  width: 38px; height: 38px;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9, #0284c7 60%, #0369a1);
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}
.aic-hero-ico svg { width: 20px; height: 20px; }
.aic-hero-txt { display: flex; flex-direction: column; gap: 3px; text-align: left; }
.aic-hero-txt b { font-size: 14px; font-weight: 800; color: #1e293b; letter-spacing: 0.02em; }
.aic-hero-txt span { font-size: 10.5px; color: var(--gray-400); font-weight: 500; }

/* 空态-报告效果预览（示意） */
.aic-demo {
  display: flex; align-items: center; gap: 16px;
  width: 100%; max-width: 330px;
  padding: 13px 15px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.05);
}
.aic-demo-left { display: flex; flex-direction: column; align-items: center; gap: 3px; flex-shrink: 0; }
.aic-demo-ring-num { font-size: 14px; font-weight: 800; fill: #334155; letter-spacing: 0.02em; }
.aic-demo-ring-label { font-size: 9.5px; color: var(--gray-400); font-weight: 600; }
.aic-demo-bars { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 9px; }
.aic-demo-row { display: flex; align-items: center; gap: 8px; }
.aic-demo-row em { font-style: normal; font-size: 9.5px; font-weight: 700; color: var(--gray-400); width: 24px; flex-shrink: 0; }
.aic-demo-row i { height: 9px; border-radius: 999px; position: relative; overflow: hidden; flex: 0 0 auto; }
.aic-demo-row i.is-ai { background: linear-gradient(90deg, #fca5a5, #dc2626); }
.aic-demo-row i.is-human { background: linear-gradient(90deg, #7dd3fc, #0284c7); }
.aic-demo-row i::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(100deg, transparent 25%, rgba(255, 255, 255, 0.55) 50%, transparent 75%);
  animation: aicDemoSweep 2.6s ease-in-out infinite;
}
.aic-demo-row:nth-child(2) i::after { animation-delay: 0.35s; }
.aic-demo-row:nth-child(3) i::after { animation-delay: 0.7s; }
@keyframes aicDemoSweep {
  0% { transform: translateX(-110%); }
  55%, 100% { transform: translateX(110%); }
}
.aic-demo-note { font-size: 9.5px; color: var(--gray-400); line-height: 1.5; margin-top: 1px; }

/* 空态-平台实力带 */
.aic-cred { display: flex; flex-direction: column; align-items: center; gap: 7px; width: 100%; }
.aic-cred-title { font-size: 10px; font-weight: 700; color: var(--gray-400); letter-spacing: 0.08em; }
.aic-cred-plats { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center; }
.aic-cred-plat {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px;
  background: #fff;
  border: 1px solid var(--gray-100);
  border-radius: 999px;
  font-size: 10.5px; font-weight: 600;
  color: var(--gray-500);
  white-space: nowrap;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
}
.aic-cred-plat i { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.aic-cred-plat b { font-weight: 800; color: #1e293b; font-size: 11px; }
.aic-spinner {
  width: 26px;
  height: 26px;
  border: 3px solid var(--gray-100);
  border-top-color: #0ea5e9;
  border-radius: 50%;
  animation: aic-spin 0.7s linear infinite;
}
@keyframes aicSpin { to { transform: rotate(360deg); } }

/* 结果主信息：结论圆环 + KPI 网格横幅 */
.aic-result-top {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid var(--gray-100);
  background:
    radial-gradient(120px 80px at 18% 50%, rgba(148, 163, 184, 0.08), transparent 70%),
    linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
  flex-shrink: 0;
}

/* 单段：判定结论卡 */
.aic-verdict {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.aic-verdict-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  align-self: flex-start;
  padding: 8px 18px 8px 14px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #fff;
  position: relative;
  overflow: hidden;
}
/* 胶囊内高光：顶部柔光让渐变更有质感 */
.aic-verdict-badge::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0) 55%);
  pointer-events: none;
}
.aic-verdict-badge svg { width: 16px; height: 16px; flex-shrink: 0; position: relative; }
.aic-verdict-badge.is-ai {
  background: linear-gradient(135deg, #f05252, #dc2626 55%, #b91c1c);
  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.18);
}
.aic-verdict-badge.is-human {
  background: linear-gradient(135deg, #0ea5e9, #0284c7 55%, #0369a1);
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.18);
}
.aic-verdict-tip {
  margin: 0;
  font-size: 11.5px;
  line-height: 1.6;
  color: var(--gray-500);
}
.aic-verdict .aic-kpis.is-single { width: 100%; }

.aic-result-top.is-ai {
  background:
    radial-gradient(120px 80px at 18% 50%, rgba(248, 113, 113, 0.1), transparent 70%),
    linear-gradient(135deg, #ffffff 0%, #fefbfb 100%);
}
.aic-result-top.is-human {
  background:
    radial-gradient(120px 80px at 18% 50%, rgba(56, 189, 248, 0.14), transparent 70%),
    linear-gradient(135deg, #ffffff 0%, #f5fbff 100%);
}
.aic-gauge {
  align-self: center;
  flex-shrink: 0;
  filter: drop-shadow(0 4px 10px rgba(15, 23, 42, 0.1));
}
.aic-gauge-ring {
  transform: rotate(-90deg);
  transform-origin: 60px 60px;
  transition: stroke-dashoffset 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.aic-gauge-num { font-size: 25px; font-weight: 800; font-family: system-ui, sans-serif; }
.aic-gauge-label { font-size: 11px; font-weight: 600; fill: #94a3b8; letter-spacing: 0.06em; }

/* KPI 指标网格 */
.aic-kpis {
  flex: 1;
  min-width: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 7px;
}
.aic-kpi {
  display: flex;
  flex-direction: column;
  gap: 1px;
  padding: 7px 10px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid var(--gray-100);
  border-radius: 9px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.aic-kpi:hover {
  border-color: rgba(14, 165, 233, 0.3);
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.08);
}
.aic-kpi-num {
  font-size: 15px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1.25;
  font-variant-numeric: tabular-nums;
}
.aic-kpi-num.is-ai { color: #dc2626; }
.aic-kpi-num.is-human { color: #0284c7; }
.aic-kpi-label { font-size: 9.5px; font-weight: 600; color: var(--gray-400); letter-spacing: 0.03em; }
/* 单段结果：两格指标拉宽、数字更醒目 */
.aic-kpis.is-single { grid-template-columns: 1fr 1fr; }
.aic-kpis.is-single .aic-kpi { padding: 10px 12px; }
.aic-kpis.is-single .aic-kpi-num { font-size: 17px; }
.aic-kpi-unit { font-style: normal; font-size: 10px; font-weight: 600; color: var(--gray-400); margin-left: 3px; }

/* 流式检测进度横幅 */
.aic-stream-banner {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 12px 14px;
  background: linear-gradient(135deg, rgba(2, 132, 199, 0.06), rgba(2, 132, 199, 0.02));
  border: 1px solid rgba(2, 132, 199, 0.18);
  border-radius: 10px;
  flex-shrink: 0;
}
.aic-stream-spinner {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2.5px solid rgba(2, 132, 199, 0.18);
  border-top-color: #0284c7;
  animation: aicSpin 0.8s linear infinite;
  flex-shrink: 0;
}
.aic-stream-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.aic-stream-info b { font-size: 12.5px; font-weight: 700; color: var(--dark-900); }
.aic-stream-info span { font-size: 11px; color: var(--gray-500); }
.aic-stream-bar {
  flex: 1;
  min-width: 60px;
  max-width: 180px;
  height: 6px;
  background: rgba(15, 23, 42, 0.06);
  border-radius: 3px;
  overflow: hidden;
}
.aic-stream-bar i {
  display: block;
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(90deg, #38bdf8, #0284c7);
  transition: width 0.35s ease;
}
.aic-segs-streaming { margin-left: auto; font-size: 10.5px; font-weight: 600; color: #0284c7; font-variant-numeric: tabular-nums; }

/* 待检测骨架行 */
.aic-seg.is-pending .aic-seg-main { gap: 7px; }
.aic-skel {
  display: block;
  height: 10px;
  border-radius: 4px;
  background: linear-gradient(90deg, rgba(15, 23, 42, 0.06) 25%, rgba(15, 23, 42, 0.11) 45%, rgba(15, 23, 42, 0.06) 65%);
  background-size: 200% 100%;
  animation: aicShimmer 1.3s ease infinite;
}
@keyframes aicShimmer {
  0% { background-position: 120% 0; }
  100% { background-position: -80% 0; }
}

/* 过短跳过段 */
.aic-seg.is-skipped { background: var(--gray-50); border-style: dashed; }
.aic-seg-tag.is-skipped {
  background: var(--gray-100);
  color: var(--gray-500);
  border: 1px solid var(--gray-200);
}

/* 单段：检测详情 4 格 */
.aic-detail {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  flex-shrink: 0;
}
.aic-detail-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 11px 8px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.03);
}
.aic-detail-num {
  font-size: 16px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1;
  font-variant-numeric: tabular-nums;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.aic-detail-num.is-ai { color: #dc2626; }
.aic-detail-num.is-human { color: #0284c7; }
.aic-detail-num.is-name { font-size: 12px; letter-spacing: 0.02em; }
.aic-detail-unit { font-style: normal; font-size: 9.5px; font-weight: 600; color: var(--gray-400); margin-left: 2px; }
.aic-detail-label { font-size: 9.5px; font-weight: 600; color: var(--gray-400); letter-spacing: 0.03em; }

/* 单段：建议引导卡 */
.aic-guide {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border-radius: 10px;
  flex-shrink: 0;
}
.aic-guide.is-ai {
  background: rgba(220, 38, 38, 0.05);
  border: 1px solid rgba(220, 38, 38, 0.16);
}
.aic-guide.is-human {
  background: rgba(2, 132, 199, 0.05);
  border: 1px solid rgba(2, 132, 199, 0.16);
}
.aic-guide-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.aic-guide-info b { font-size: 12px; font-weight: 700; color: var(--dark-900); }
.aic-guide-info span { font-size: 11px; color: var(--gray-500); line-height: 1.5; }
.aic-guide-btn {
  margin-left: auto;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 7px 13px;
  border-radius: 8px;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: var(--white);
  font-size: 11.5px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 2px 6px rgba(2, 132, 199, 0.28);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  white-space: nowrap;
}
.aic-guide-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(2, 132, 199, 0.36); }

/* 截断提示 */
.aic-warn-line {
  margin: 0;
  padding: 7px 11px;
  background: rgba(245, 158, 11, 0.07);
  border: 1px solid rgba(245, 158, 11, 0.22);
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  color: #b45309;
  flex-shrink: 0;
}

/* AI 分布条 */
.aic-dist { flex-shrink: 0; }
.aic-dist-head {
  display: flex;
  align-items: center;
  gap: 8px;
}
.aic-dist-title { font-size: 12px; font-weight: 700; color: var(--gray-600); }
.aic-dist-legend {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 9px;
}
.aic-leg {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  color: var(--gray-500);
  font-weight: 600;
}
.aic-leg-dot {
  width: 8px;
  height: 8px;
  border-radius: 3px;
}
.aic-leg-dot.is-ai { background: linear-gradient(135deg, #f87171, #ef4444); }
.aic-leg-dot.is-human { background: linear-gradient(135deg, #38bdf8, #0284c7); }
.aic-leg-dot.is-failed { background: #cbd5e1; }
.aic-dist-bar {
  display: flex;
  gap: 2px;
  height: 13px;
  margin-top: 8px;
  border-radius: 7px;
  overflow: hidden;
}
.aic-dist-seg {
  height: 100%;
  border-radius: 4px;
  transition: opacity 0.2s ease;
  cursor: default;
}
.aic-dist-seg.is-ai { background: linear-gradient(90deg, #f87171, #ef4444); }
.aic-dist-seg.is-human { background: linear-gradient(90deg, #38bdf8, #0284c7); }
.aic-dist-seg.is-failed { background: #cbd5e1; }

/* 分段明细 */
.aic-segs {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}
.aic-segs-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 2px 1px 0;
}
.aic-segs-title { font-size: 12px; font-weight: 700; color: var(--gray-600); }
.aic-seg-tabs {
  margin-left: auto;
  display: inline-flex;
  gap: 2px;
  padding: 2px;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  border-radius: 999px;
}
.aic-seg-tab {
  border: none;
  background: transparent;
  font-size: 10.5px;
  font-weight: 600;
  color: var(--gray-500);
  padding: 3px 9px;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-variant-numeric: tabular-nums;
}
.aic-seg-tab:hover { color: var(--dark-800); }
.aic-seg-tab.active { background: var(--white); color: #0284c7; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }
.aic-seg-tab.is-ai.active { color: #dc2626; }
.aic-seg.is-active {
  border-color: rgba(14, 165, 233, 0.45);
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.12);
}
.aic-dist-seg {
  cursor: pointer;
  transition: opacity 0.15s ease, box-shadow 0.15s ease;
}
.aic-dist-seg:hover { opacity: 0.82; }
.aic-dist-seg.is-active {
  box-shadow: 0 0 0 1.5px var(--white), 0 0 0 3px #0284c7;
  z-index: 1;
}
.aic-seg {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 8px 11px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: 9px;
  transition: border-color 0.2s ease;
}
.aic-seg:hover { border-color: var(--gray-200); }
.aic-seg.is-failed { opacity: 0.6; }
.aic-seg-idx {
  font-size: 10.5px;
  font-weight: 800;
  color: var(--gray-500);
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  border-radius: 8px;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
  margin-top: 1px;
}
.aic-seg-main { flex: 1; min-width: 0; }
.aic-seg-top {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 3px;
}
.aic-seg-chars { font-size: 11px; color: var(--gray-500); font-variant-numeric: tabular-nums; }
.aic-seg-tag {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
}
.aic-seg-tag.is-ai { color: #dc2626; background: rgba(220, 38, 38, 0.08); }
.aic-seg-tag.is-human { color: #0284c7; background: rgba(2, 132, 199, 0.08); }
.aic-seg-tag.is-failed { color: var(--gray-400); background: var(--gray-100); }
.aic-seg-score {
  margin-left: auto;
  font-size: 12px;
  font-weight: 800;
  color: var(--dark-800);
  font-variant-numeric: tabular-nums;
}
.aic-seg-excerpt {
  margin: 0;
  padding-left: 9px;
  border-left: 2px solid var(--gray-200);
  font-size: 11.5px;
  color: var(--gray-600);
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}
.aic-seg-excerpt.is-full {
  display: block;
  max-height: 220px;
  overflow-y: auto;
  padding: 6px 9px;
  background: var(--gray-50);
  border-left: none;
  border-radius: 7px;
}
.aic-seg-more {
  align-self: flex-start;
  margin-top: 4px;
  margin-left: 11px;
  border: none;
  background: transparent;
  padding: 0;
  font-size: 10.5px;
  font-weight: 600;
  color: #0284c7;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.aic-seg-more:hover { text-decoration: underline; }
/* 每段 AI 率条形行 */
.aic-seg-gauge-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
}
.aic-seg-gauge-row .aic-seg-bar {
  flex: 1;
  min-width: 0;
  height: 9px;
  margin-top: 0;
}
.aic-seg-gauge-row .aic-seg-score {
  margin-left: 0;
  font-size: 12.5px;
  flex-shrink: 0;
  min-width: 48px;
  text-align: right;
}
.aic-seg-gauge-row .aic-seg-score.is-ai { color: #dc2626; }
.aic-seg-gauge-row .aic-seg-score.is-human { color: #0284c7; }
.aic-seg-bar {
  height: 5px;
  background: rgba(15, 23, 42, 0.05);
  border-radius: 3px;
  margin-top: 6px;
  overflow: hidden;
}
.aic-seg-bar i {
  display: block;
  height: 100%;
  border-radius: 3px;
  transition: width 0.5s ease;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.3);
}
.aic-segs-empty {
  margin: 0;
  padding: 14px;
  text-align: center;
  font-size: 12px;
  color: var(--gray-400);
}

/* ================= 操作行 ================= */
/* 按钮行在 .aic-main 内换行，紧贴输入框+结果框底部（间距仅 gap 12px） */
.aic-btn-row {
  display: flex;
  justify-content: center;
  gap: 10px;
  flex: 1 1 100%;
}
.aic-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 42px;
  padding: 10px 28px;
  border-radius: 10px;
  border: none;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
  transition: all 0.2s ease;
  font-family: inherit;
}
.aic-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(2, 132, 199, 0.4); }
.aic-btn:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15); transform: none; }
.aic-btn--ghost {
  background: var(--white);
  color: var(--gray-600);
  border: 1px solid var(--gray-200);
  box-shadow: none;
}
.aic-btn--ghost:hover:not(:disabled) {
  background: var(--white);
  color: #0284c7;
  border-color: rgba(14, 165, 233, 0.4);
  box-shadow: 0 4px 10px rgba(2, 132, 199, 0.1);
}
.aic-btn-inner { display: inline-flex; align-items: center; gap: 6px; position: relative; z-index: 1; }
.aic-btn-spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: aic-spin 0.7s linear infinite;
  display: inline-block;
}

/* ================= H5（m 壳）原文/结果切换条 =================
   基类恒隐藏：PC 双栏与 console 窄窗口均不渲染显示；
   仅 ≤640 且 .m-main 祖先下启用（见响应式块，Vue 端 aic-m-off 类才生效） */
.aic-m-tabs { display: none; }

/* ================= 检测口径条 ================= */
.aic-strip {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px 18px;
  padding: 10px 14px;
  border-radius: 10px;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  margin-top: auto;
}
.aic-strip-title {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: var(--gray-600);
}
.aic-strip-title svg { width: 13px; height: 13px; color: #0ea5e9; }
.aic-strip-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: var(--gray-500);
  white-space: nowrap;
}
.aic-strip-item i {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #0ea5e9;
}

/* ================= 底部信息区：计费口径 + 常见问题（参照 AI降重） ================= */
/* 信息区在 .aic-main（wrap）内，独占一行、紧跟按钮组下方；整体紧凑 */
.aic-info { display: flex; gap: 10px; flex: 1 1 100%; min-width: 0; }
.aic-info-card {
  flex: 1;
  min-width: 0;
  background: var(--white);
  border-radius: 10px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.aic-info-card:hover {
  border-color: rgba(14, 165, 233, 0.25);
  box-shadow: 0 4px 16px rgba(2, 132, 199, 0.08), 0 2px 4px rgba(15, 23, 42, 0.03);
}
.aic-info-head {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 11px 7px;
  border-bottom: 1px solid var(--gray-100);
}
.aic-info-icon {
  width: 24px;
  height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(2, 132, 199, 0.1));
  color: #0284c7;
}
.aic-info-icon svg { width: 14px; height: 14px; }
.aic-info-title { font-size: 12px; font-weight: 700; color: var(--dark-900); margin: 0; }
.aic-info-body { padding: 8px 11px 10px; }

/* 计费口径卡 */
.aic-bill-item {
  padding: 6px 8px;
  background: var(--gray-50);
  border-radius: 7px;
  border: 1px solid var(--gray-100);
  margin-bottom: 5px;
}
.aic-bill-item:last-of-type { margin-bottom: 6px; }
.aic-bill-item-head { display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.aic-bill-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 7px;
  border-radius: 999px;
  font-size: 9.5px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.02em;
}
.aic-bill-badge--time { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.aic-bill-badge--char { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
.aic-bill-badge--balance { background: linear-gradient(135deg, #fb923c, #f97316); }
.aic-bill-item-label { font-size: 10px; font-weight: 600; color: var(--gray-500); }
.aic-bill-item-desc { margin: 0; font-size: 10px; line-height: 1.5; color: var(--gray-400); }
.aic-bill-item-desc b { color: #0284c7; font-weight: 700; }
.aic-bill-note {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px 12px;
  padding: 2px 2px 0;
}
.aic-bill-note-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 500;
  color: var(--gray-400);
  white-space: nowrap;
}
.aic-bill-note-item i { width: 4px; height: 4px; border-radius: 50%; background: #0ea5e9; flex-shrink: 0; }

/* 常见问题手风琴 */
.aic-faq-list { display: flex; flex-direction: column; }
.aic-faq-item {
  border: 1px solid transparent;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.25s ease;
}
.aic-faq-item + .aic-faq-item { margin-top: 2px; }
.aic-faq-item:hover { background: rgba(14, 165, 233, 0.03); }
.aic-faq-item.expanded { background: rgba(14, 165, 233, 0.04); border-color: rgba(14, 165, 233, 0.12); }
.aic-faq-item.expanded .aic-faq-chevron { transform: rotate(180deg); }
.aic-faq-item.expanded .aic-faq-mark { background: #0284c7; color: #fff; }
.aic-faq-question {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  padding: 6px 9px;
  cursor: pointer;
  user-select: none;
}
.aic-faq-question:active { background: rgba(14, 165, 233, 0.05); }
.aic-faq-mark {
  width: 18px;
  height: 18px;
  border-radius: 5px;
  background: rgba(14, 165, 233, 0.12);
  color: #0284c7;
  font-size: 10px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.25s ease;
}
.aic-faq-text { flex: 1; font-size: 11.5px; font-weight: 500; color: var(--dark-700); line-height: 1.45; }
.aic-faq-chevron { width: 13px; height: 13px; color: var(--gray-400); flex-shrink: 0; margin-top: 1px; transition: transform 0.3s ease; }
.aic-faq-answer {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.35s ease, padding 0.3s ease;
}
.aic-faq-answer p {
  margin: 0;
  padding: 0 9px 0 34px;
  font-size: 10.5px;
  line-height: 1.6;
  color: var(--gray-500);
  word-break: break-word;
}
.aic-faq-item.expanded .aic-faq-answer { max-height: 320px; padding-bottom: 8px; }

/* ================= 动效 ================= */
@keyframes aic-spin { to { transform: rotate(360deg); } }
@keyframes aic-pulse {
  0%, 100% { opacity: 0.55; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.06); }
}

/* ================= 响应式 ================= */
@media (max-width: 1024px) {
  /* 纵向堆叠后必须恢复水平拉伸：否则 align-items:flex-start 保留 max-content 宽，
     textarea 固有宽会把 .aic-main 撑出视口（右侧按钮/横幅被裁剪） */
  .aic-cols { flex-direction: column; align-items: stretch; }
  .aic-options { flex: none; width: 100%; min-width: 0; position: static; }
  .aic-main { flex-flow: column nowrap; width: 100%; max-width: 100%; }
  .aic-col--input, .aic-col--result { flex: none; width: 100%; }
  .aic-btn-row { flex: none; flex-wrap: wrap; }
  .aic-info { flex-direction: column; }
}
@media (max-width: 768px) {
  .aic-toolbar { flex-direction: column; align-items: flex-start; }
  .aic-textarea, .aic-dropzone, .aic-result-body { height: 320px; }
  .aic-info { flex-direction: column; }
}
@media (max-width: 480px) {
  .aic-toolbar-tip { display: none; }
}
/* ============ ≤640px：钱包卡轻量化（浅底主题色，替代深色大渐变卡头） ============ */
@media (max-width: 640px) {
  /* H5 重排（修 PC 照搬）：侧栏 display:contents 拆卡参与排序——
     配置卡（支付/检测平台/效果）→ 正文 → 钱包垫底（原钱包面板整块压在正文前占一屏） */
  .aic-cols { display: flex; flex-direction: column; }
  .aic-options { display: contents; }
  .aic-main { order: 0; }
  .aic-config-card, .aic-cap, .aic-strip { order: 0; }
  .aic-wallet { order: 9; }
  /* 钱包压成下单摘要：head/余额明细行/时长包行全隐，只留费用行 + 双按钮横排
     （PC 大卡照搬在 m 层把页面拖出一长串，下单动线被稀释） */
  .aic-wallet-head { display: none; }
  .aic-wallet-row:not(.aic-wallet-row--price) { display: none; }
  .aic-wallet-row--packs { display: none; }
  .aic-wallet { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
  .aic-wallet-body { flex: 1 1 100%; }
  .aic-wallet-free { flex: 1; margin: 0; }
  .aic-wallet-btn { flex: 1; margin: 0; padding: 9px 12px; font-size: 13px; display: flex; align-items: center; justify-content: center; }
  .aic-wallet {
    background: #fff;
    border: 1px solid #e0f2fe;
    box-shadow: 0 2px 10px rgba(2, 132, 199, 0.08);
  }
  .aic-wallet-icon { background: rgba(2, 132, 199, 0.1); }
  .aic-wallet-icon svg { color: #0284c7; }
  .aic-wallet-title { color: #0f172a; }
  .aic-wallet-mode { background: rgba(2, 132, 199, 0.08); color: #0369a1; }
  .aic-wallet-mode.free { background: rgba(2, 132, 199, 0.06); color: #0369a1; }
  .aic-wallet-mode.paid { background: rgba(2, 132, 199, 0.14); color: #075985; }
  .aic-wallet-body { background: #f8fafc; }
  .aic-wallet-row + .aic-wallet-row { border-top-color: rgba(15, 23, 42, 0.06); }
  .aic-wallet-row--price { background: rgba(2, 132, 199, 0.05); border-color: rgba(2, 132, 199, 0.16); }
  .aic-wallet-row--price.free { background: rgba(2, 132, 199, 0.03); border-color: rgba(2, 132, 199, 0.1); }
  .aic-wallet-row + .aic-wallet-row--price { border-top-color: rgba(2, 132, 199, 0.16); }
  .aic-wallet-label { color: #475569; }
  .aic-wallet-sub { color: #94a3b8; }
  .aic-wallet-value { color: #0f172a; }
  .aic-wallet-value em { color: #64748b; }
  .aic-wallet-pack-sep { background: rgba(15, 23, 42, 0.08); }
  .aic-wallet-pack-value { color: #0284c7; }
  .aic-wallet-pack-value em { color: #64748b; }
  .aic-wallet-btn {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #fff;
  }
  .aic-wallet-btn:hover { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
  .aic-wallet-free {
    background: rgba(2, 132, 199, 0.04);
    border: 1px dashed rgba(2, 132, 199, 0.4);
    color: #0369a1;
  }
  .aic-wallet-free:hover:not(:disabled) { background: rgba(2, 132, 199, 0.1); border-color: rgba(2, 132, 199, 0.6); }
  .aic-wallet-free.claimed { background: #f8fafc; border: 1px dashed #cbd5e1; color: #94a3b8; }

  /* 效果报告/检测口径/信息区（计费口径+FAQ）：纯参考营销内容，m 层删减
     （跑马灯与 toolbar tip 已带口径摘要，钱包费用行已带单价） */
  .aic-cap { display: none; }
  .aic-strip { display: none; }
  .aic-info { display: none; }

  /* 原文/结果 Tab 切换（仅 m 壳，.m-main 门控）：双栏上下堆叠改为单屏 Tab，
     PC 双栏与 console 窄窗口（无 .m-main 祖先）不受影响 */
  .m-main .aic-m-tabs { display: flex; gap: 8px; padding: 2px 0 12px; }
  .m-main .aic-m-tabs button {
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
  .m-main .aic-m-tabs button.active {
    border-color: #0284c7;
    background: rgba(2, 132, 199, 0.08);
    color: #0369a1;
  }
  .m-main .aic-m-off { display: none !important; }
  /* 结果区取消 PC 内滚固定高（基类 440px + ≤720 的 320px 把检测结果锁死在
     小窗里内滚，m 层看不到完整结果）：自然撑开走整页滚动，仅 m 壳生效 */
  .m-main .aic-result-body { height: auto; min-height: 0; overflow: visible; padding-right: 0; }
}
</style>
