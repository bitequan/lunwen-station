<template>
  <ToolShell
    name="论文增重"
    desc="增强学术化表达，融合文献措辞，保留原有观点逻辑"
    theme="orange"
    icon='<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
  >
    <!-- 声明条 -->
    <div class="pw-disclaimer">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span><strong>声明：</strong>本系统内容来源于互联网公开信息，仅提取和处理必要片段，不进行全文存储。如涉及侵权请联系我们处理。</span>
    </div>

    <div class="pw-layout">
      <!-- 左：输入 -->
      <section class="pw-panel pw-left">
        <div class="panel-head">
          <span class="panel-dot orange"></span>
          <h2 class="panel-title">原文与要求</h2>
        </div>

        <label class="field-label">原文内容</label>
        <textarea
          v-model="sourceText"
          class="pw-textarea"
          placeholder="请输入需要增重改写的论文段落..."
        ></textarea>

        <label class="field-label mt">改写模式</label>
        <div class="pw-mode">
          <button type="button" class="pw-mode-btn" :class="{ active: mode === 'replace' }" @click="mode = 'replace'">
            <span class="pw-mode-name">替换内容</span>
            <span class="pw-mode-desc">篇幅与原文相近</span>
          </button>
          <button type="button" class="pw-mode-btn" :class="{ active: mode === 'expand' }" @click="mode = 'expand'">
            <span class="pw-mode-name">扩充内容</span>
            <span class="pw-mode-desc">篇幅膨胀更多</span>
          </button>
        </div>

        <label class="field-label mt">补充要求（可选）</label>
        <textarea
          v-model="condition"
          class="pw-textarea pw-textarea-sm"
          placeholder="可选：补充改写要求（如学术语气、限定方向）..."
        ></textarea>

        <div class="pw-actions">
          <button class="pw-btn-primary" :disabled="loading" @click="runRewrite">
            <span v-if="!loading" class="btn-inner">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              开始增重
            </span>
            <span v-else class="btn-inner">
              <span class="btn-spinner"></span>
              增重中...
            </span>
          </button>
          <button class="pw-btn-default" @click="clearAll">清空</button>
        </div>

        <div class="pw-status" :class="{ active: status }">{{ status }}</div>
      </section>

      <!-- 右：结果 -->
      <section class="pw-panel pw-right">
        <div class="panel-head">
          <span class="panel-dot orange"></span>
          <h2 class="panel-title">增重结果</h2>
          <button
            v-if="resultText"
            class="pw-copy-btn"
            :class="{ copied: copied }"
            @click="copyResult"
          >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            {{ copied ? '已复制' : '复制结果' }}
          </button>
        </div>

        <div class="pw-result-wrap">
          <transition name="pw-fade" mode="out-in">
            <div v-if="loading" key="loading" class="pw-result-loading">
              <span class="pw-spinner"></span>
              <span>正在调用改写服务，请稍候...</span>
            </div>
            <textarea
              v-else
              key="result"
              v-model="resultText"
              class="pw-textarea pw-result-area"
              placeholder="增重结果将显示在这里..."
              readonly
            ></textarea>
          </transition>
        </div>
      </section>
    </div>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '论文增重 - 小工具 - AI写作助手' })

const api = useApi()
const toast = useToast()

const sourceText = ref('')
const condition = ref('')
const mode = ref('expand')
const resultText = ref('')
const loading = ref(false)
const status = ref('')
const copied = ref(false)

async function runRewrite() {
  const src = sourceText.value.trim()
  if (!src) {
    toast.warning('请输入需要增重的文本')
    return
  }
  loading.value = true
  status.value = '正在调用改写服务，请稍候...'
  resultText.value = ''
  try {
    const res = await api.post('/api/paper_weight/rewrite', {
      sourceText: src,
      condition: condition.value.trim(),
      mode: mode.value,
    })
    // 后端返回 {data:{resultText}}，无 code → useApi 整体当 data
    const payload = res.data?.data ?? res.data ?? {}
    const out = payload.resultText || ''
    resultText.value = out
    status.value = out ? '增重完成，可复制结果。' : '改写完成，但未返回文本。'
    if (!out) toast.warning('未返回文本，请稍后重试')
  } catch (e) {
    status.value = '请求失败，请稍后重试。'
    toast.error('请求失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

async function copyResult() {
  const t = resultText.value.trim()
  if (!t) {
    toast.warning('暂无可复制内容')
    return
  }
  await copyText(t)
  copied.value = true
  toast.success('复制成功')
  setTimeout(() => { copied.value = false }, 1800)
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

function clearAll() {
  sourceText.value = ''
  condition.value = ''
  resultText.value = ''
  status.value = ''
  loading.value = false
}
</script>

<style scoped>
/* 声明条 */
.pw-disclaimer {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 8px 11px;
  border-radius: 7px;
  border: 1px solid #fde68a;
  border-left: 3px solid #f59e0b;
  background: #fffbeb;
  color: #92400e;
  font-size: 11px;
  line-height: 1.55;
  margin-bottom: 10px;
}
.pw-disclaimer svg { color: #f59e0b; flex-shrink: 0; margin-top: 1px; }
.pw-disclaimer strong { color: #78350f; font-weight: 700; }

/* 布局 */
.pw-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 10px;
  align-items: stretch;
}

.pw-panel {
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  padding: 12px;
  background: var(--white);
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.pw-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #f97316 0%, #fdba74 60%, transparent 100%);
  opacity: 0.7;
}

.panel-head {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 9px;
}
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.orange { background: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }

.field-label {
  display: block;
  margin-bottom: 5px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--gray-600);
}
.field-label.mt { margin-top: 8px; }

/* 改写模式 */
.pw-mode {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 7px;
}
.pw-mode-btn {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;
  padding: 9px 11px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  background: var(--white);
  cursor: pointer;
  text-align: left;
  transition: all .2s;
}
.pw-mode-btn:hover { border-color: rgba(249, 115, 22, 0.4); }
.pw-mode-btn.active {
  border-color: #f97316;
  background: #fff7ed;
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}
.pw-mode-name { font-size: 12.5px; font-weight: 700; color: var(--dark-800); }
.pw-mode-btn.active .pw-mode-name { color: #c2410c; }
.pw-mode-desc { font-size: 10.5px; color: var(--gray-400); line-height: 1.4; }
.pw-mode-btn.active .pw-mode-desc { color: #c2410c; }

.pw-textarea {
  width: 100%;
  border: 1px solid var(--gray-200);
  border-radius: 7px;
  padding: 8px 10px;
  font-size: 13px;
  line-height: 1.65;
  color: var(--dark-800);
  background: #fcfdff;
  outline: none;
  resize: vertical;
  font-family: inherit;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.pw-textarea:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
  background: var(--white);
}
.pw-textarea::placeholder { color: var(--gray-400); }
.pw-textarea-sm { min-height: 56px; }

.pw-left .pw-textarea:first-of-type { min-height: 100px; }

/* 按钮 */
.pw-actions {
  display: flex;
  gap: 7px;
  align-items: center;
  margin-top: 10px;
}
.pw-btn-primary {
  border: none;
  border-radius: 8px;
  padding: 7px 14px;
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  color: var(--white);
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(249, 115, 22, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s;
}
.pw-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(249, 115, 22, 0.32); }
.pw-btn-primary:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }

.pw-btn-default {
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  padding: 7px 14px;
  background: var(--white);
  color: var(--gray-600);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}
.pw-btn-default:hover { background: var(--gray-50); border-color: var(--gray-300); color: var(--dark-800); }

.btn-inner { display: inline-flex; align-items: center; gap: 5px; }
.btn-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: pw-spin .7s linear infinite;
}
@keyframes pw-spin { to { transform: rotate(360deg); } }

.pw-status {
  margin-top: 8px;
  min-height: 16px;
  font-size: 11px;
  color: var(--gray-400);
  transition: color .2s;
}
.pw-status.active { color: var(--gray-600); }

/* 复制按钮 */
.pw-copy-btn {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 9px;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: var(--white);
  color: var(--gray-600);
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}
.pw-copy-btn:hover { border-color: #f97316; color: #c2410c; background: rgba(249, 115, 22, 0.05); }
.pw-copy-btn.copied { background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3); color: #15803d; }
.pw-copy-btn.ref { margin-right: 6px; border-color: rgba(249, 115, 22, 0.25); color: #c2410c; background: rgba(249, 115, 22, 0.06); }

/* 结果区 */
.pw-result-wrap {
  flex: 1;
  position: relative;
  min-height: 200px;
}
.pw-result-area {
  width: 100%;
  height: 100%;
  min-height: 200px;
  background: #fbfcff;
}
.pw-result-loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--gray-500);
  font-size: 12.5px;
  background: #fbfcff;
  border: 1px solid var(--gray-100);
  border-radius: 8px;
}
.pw-spinner {
  width: 28px; height: 28px;
  border: 2.5px solid rgba(249, 115, 22, 0.15);
  border-top-color: #f97316;
  border-radius: 50%;
  animation: pw-spin .8s linear infinite;
}

.pw-fade-enter-active, .pw-fade-leave-active { transition: opacity .25s; }
.pw-fade-enter-from, .pw-fade-leave-to { opacity: 0; }

/* 响应式 */
@media (max-width: 900px) {
  .pw-layout { grid-template-columns: 1fr; }
  .pw-result-wrap { min-height: 150px; }
  .pw-result-area { min-height: 150px; }
}
</style>
