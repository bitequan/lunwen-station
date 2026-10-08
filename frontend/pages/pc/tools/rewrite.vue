<template>
  <ToolShell
    name="段落改写"
    desc="支持同义改写、扩写、缩写，可引用参考文献、AIGC降重"
    theme="cyan"
    icon='<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'
  >
    <div class="rw-layout">
      <!-- 左：输入与选项 -->
      <section class="rw-panel rw-left">
        <div class="panel-head">
          <span class="panel-dot cyan"></span>
          <h2 class="panel-title">原文与改写设置</h2>
        </div>

        <label class="field-label">原文内容</label>
        <textarea
          v-model="sourceText"
          class="rw-textarea"
          placeholder="请输入需要改写的文本..."
        ></textarea>

        <!-- 补充改写条件开关 -->
        <div class="rw-switch-row">
          <label class="rw-switch">
            <input type="checkbox" v-model="conditionEnabled" />
            <span class="slider cyan"></span>
          </label>
          <span class="switch-label">补充改写条件</span>
        </div>
        <transition name="rw-collapse">
          <textarea
            v-show="conditionEnabled"
            v-model="condition"
            class="rw-textarea rw-textarea-sm"
            placeholder="请输入额外的改写要求，例如：使用更专业的术语、增加具体案例等..."
          ></textarea>
        </transition>

        <!-- 选项行：改写类型 + AIGC降重 + 引用文献 -->
        <div class="rw-options">
          <div class="rw-select-wrap">
            <select v-model="rewriteType" class="rw-select">
              <option value="rephrase">同义改写</option>
              <option value="expand">扩写（扩充内容）</option>
              <option value="summarize">缩写（精简内容）</option>
            </select>
            <svg class="select-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>

          <label class="rw-switch-inline">
            <label class="rw-switch sm">
              <input type="checkbox" v-model="aigcMode" />
              <span class="slider cyan"></span>
            </label>
            <span class="switch-label">AIGC降重</span>
          </label>
        </div>

        <!-- 引用参考文献 -->
        <div class="rw-ref-row">
          <label class="rw-switch-inline">
            <label class="rw-switch sm">
              <input type="checkbox" v-model="referenceMode" />
              <span class="slider cyan"></span>
            </label>
            <span class="switch-label">引用参考文献</span>
          </label>
          <transition name="rw-fade">
            <div v-if="referenceMode" class="rw-select-wrap sm">
              <select v-model="referenceType" class="rw-select sm">
                <option value="all">全部文献</option>
                <option value="cn">中文文献</option>
                <option value="en">外文文献</option>
              </select>
              <svg class="select-caret" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
          </transition>
        </div>

        <div class="rw-actions">
          <button class="rw-btn-primary" :disabled="loading" @click="runRewrite">
            <span v-if="!loading" class="btn-inner">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              开始改写
            </span>
            <span v-else class="btn-inner"><span class="btn-spinner"></span>改写中...</span>
          </button>
          <button class="rw-btn-default" @click="clearAll">清空</button>
        </div>
      </section>

      <!-- 右：结果 -->
      <section class="rw-panel rw-right">
        <div class="panel-head">
          <span class="panel-dot cyan"></span>
          <h2 class="panel-title">改写结果</h2>
          <button v-if="resultText" class="rw-copy-btn" :class="{ copied }" @click="copyResult">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            {{ copied ? '已复制' : '复制文本' }}
          </button>
          <button v-if="wenxianlist.length" class="rw-ref-btn" @click="showRefPopup = true">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            参考文献 ({{ wenxianlist.length }})
          </button>
        </div>

        <div class="rw-result-wrap">
          <transition name="rw-fade" mode="out-in">
            <div v-if="loading" key="loading" class="rw-result-loading">
              <span class="rw-spinner"></span>
              <span>正在改写中...</span>
            </div>
            <textarea
              v-else
              key="result"
              v-model="resultText"
              class="rw-textarea rw-result-area"
              placeholder="改写后的文本将显示在这里..."
              readonly
            ></textarea>
          </transition>
        </div>
      </section>
    </div>

    <!-- 参考文献弹窗 -->
    <transition name="rw-popup">
      <div v-if="showRefPopup" class="ref-popup-overlay" @click.self="showRefPopup = false">
        <div class="ref-popup">
          <div class="ref-popup-head">
            <h3><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>参考文献列表</h3>
            <button class="ref-close" @click="showRefPopup = false">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="ref-popup-body">
            <button class="ref-copy-all" @click="copyAllRef">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              {{ allCopied ? '已复制全部' : '复制全部' }}
            </button>
            <ul class="ref-items">
              <li v-for="(item, i) in wenxianlist" :key="i" class="ref-item">
                <span class="ref-text">{{ item }}</span>
                <button class="ref-copy-one" :class="{ copied: copiedIdx === i }" @click="copyOneRef(i)">
                  <span v-if="copiedIdx === i">✓</span>
                  <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  {{ copiedIdx === i ? '已复制' : '复制' }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </transition>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '段落改写 - 小工具 - AI写作助手' })

const api = useApi()
const toast = useToast()

const sourceText = ref('')
const condition = ref('')
const conditionEnabled = ref(false)
const aigcMode = ref(false)
const referenceMode = ref(false)
const referenceType = ref('all')
const rewriteType = ref('rephrase')

const resultText = ref('')
const wenxianlist = ref([])
const loading = ref(false)
const copied = ref(false)

const showRefPopup = ref(false)
const copiedIdx = ref(-1)
const allCopied = ref(false)

async function runRewrite() {
  const src = sourceText.value.trim()
  if (!src) {
    toast.warning('请输入需要改写的文本')
    return
  }
  loading.value = true
  resultText.value = ''
  wenxianlist.value = []
  try {
    const res = await api.post('/api/tools/rewrite', {
      sourceText: src,
      rewriteType: rewriteType.value,
      condition: conditionEnabled.value ? condition.value : '',
      aigcMode: aigcMode.value,
      referenceMode: referenceMode.value,
      referenceType: referenceMode.value ? referenceType.value : null,
    })
    // 后端返回 {data:{resultText, wenxianlist}}，无 code
    const payload = res.data?.data ?? res.data ?? {}
    resultText.value = payload.resultText || ''
    wenxianlist.value = Array.isArray(payload.wenxianlist) ? payload.wenxianlist : []
    if (!resultText.value) toast.warning('未返回改写结果')
  } catch (e) {
    toast.error('改写失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

async function copyResult() {
  if (!resultText.value.trim()) {
    toast.warning('暂无内容可复制')
    return
  }
  await copyText(resultText.value)
  copied.value = true
  toast.success('复制成功')
  setTimeout(() => { copied.value = false }, 1800)
}

async function copyOneRef(i) {
  await copyText(wenxianlist.value[i] || '')
  copiedIdx.value = i
  setTimeout(() => { copiedIdx.value = -1 }, 1800)
}

async function copyAllRef() {
  await copyText(wenxianlist.value.map((t, i) => `[${i + 1}] ${t}`).join('\n'))
  allCopied.value = true
  toast.success('全部复制成功')
  setTimeout(() => { allCopied.value = false }, 1800)
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
  wenxianlist.value = []
  loading.value = false
}
</script>

<style scoped>
.rw-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 10px;
  align-items: stretch;
}
.rw-panel {
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

.rw-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #06b6d4 0%, #67e8f9 60%, transparent 100%);
  opacity: 0.7;
}

.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.cyan { background: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }

.field-label { display: block; margin-bottom: 5px; font-size: 11.5px; font-weight: 600; color: var(--gray-600); }

.rw-textarea {
  width: 100%;
  border: 1px solid var(--gray-200);
  border-radius: 7px;
  padding: 8px 10px;
  font-size: 13px;
  line-height: 1.6;
  color: var(--dark-800);
  background: #fcfdff;
  outline: none;
  resize: vertical;
  font-family: inherit;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
.rw-textarea:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12); background: var(--white); }
.rw-textarea::placeholder { color: var(--gray-400); }
.rw-textarea-sm { min-height: 50px; margin-top: 7px; }
.rw-left .rw-textarea:first-of-type { min-height: 72px; }

/* 开关 */
.rw-switch-row { display: flex; align-items: center; gap: 6px; margin-top: 8px; }
.rw-switch { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
.rw-switch.sm { width: 32px; height: 18px; }
.rw-switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background: var(--gray-300); transition: .3s; border-radius: 22px; }
.slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background: var(--white); transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
.rw-switch.sm .slider:before { height: 12px; width: 12px; left: 3px; bottom: 3px; }
input:checked + .slider.cyan { background: linear-gradient(135deg, #22d3ee, #06b6d4); }
input:checked + .slider.cyan:before { transform: translateX(16px); }
.rw-switch.sm input:checked + .slider.cyan:before { transform: translateX(14px); }
.switch-label { font-size: 11.5px; color: var(--gray-600); font-weight: 500; }

.rw-collapse-enter-active, .rw-collapse-leave-active { transition: all .25s ease; overflow: hidden; }
.rw-collapse-enter-from, .rw-collapse-leave-to { opacity: 0; max-height: 0; margin-top: 0; }
.rw-collapse-enter-to, .rw-collapse-leave-from { opacity: 1; max-height: 200px; }

/* 选项行 */
.rw-options { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 9px; }
.rw-switch-inline { display: flex; align-items: center; gap: 5px; }

.rw-select-wrap { position: relative; }
.rw-select {
  padding: 6px 26px 6px 9px;
  border-radius: 7px;
  border: 1px solid var(--gray-200);
  font-size: 11.5px;
  color: var(--dark-800);
  background: var(--white);
  cursor: pointer;
  appearance: none;
  font-family: inherit;
  transition: border-color .2s, box-shadow .2s;
  min-width: 140px;
}
.rw-select.sm { padding: 5px 22px 5px 8px; font-size: 11px; min-width: 110px; }
.rw-select:hover { border-color: var(--gray-300); }
.rw-select:focus { outline: none; border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12); }
.select-caret { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; }

.rw-ref-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }

/* 按钮 */
.rw-actions { display: flex; gap: 7px; margin-top: 10px; }
.rw-btn-primary {
  border: none; border-radius: 8px; padding: 7px 14px;
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer;
  box-shadow: 0 3px 10px rgba(6, 182, 212, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s;
}
.rw-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(6, 182, 212, 0.32); }
.rw-btn-primary:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }
.rw-btn-default {
  border: 1px solid var(--gray-200); border-radius: 8px; padding: 7px 14px;
  background: var(--white); color: var(--gray-600); font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.rw-btn-default:hover { background: var(--gray-50); border-color: var(--gray-300); }

.btn-inner { display: inline-flex; align-items: center; gap: 5px; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: rw-spin .7s linear infinite; }
@keyframes rw-spin { to { transform: rotate(360deg); } }

/* 复制/文献按钮 */
.rw-copy-btn, .rw-ref-btn {
  margin-left: auto; display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 9px; border-radius: 6px; border: 1px solid var(--gray-200);
  background: var(--white); color: var(--gray-600); font-size: 11px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.rw-copy-btn:hover { border-color: #06b6d4; color: #0e7490; background: rgba(6, 182, 212, 0.05); }
.rw-copy-btn.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }
.rw-ref-btn { margin-left: 6px; border-color: rgba(6, 182, 212, 0.25); color: #0e7490; background: rgba(6, 182, 212, 0.06); }
.rw-ref-btn:hover { background: rgba(6, 182, 212, 0.12); }

/* 结果区 */
.rw-result-wrap { flex: 1; position: relative; min-height: 190px; }
.rw-result-area { width: 100%; height: 100%; min-height: 190px; background: #fbfcff; }
.rw-result-loading {
  position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; color: var(--gray-500); font-size: 12.5px; background: #fbfcff; border: 1px solid var(--gray-100); border-radius: 8px;
}
.rw-spinner { width: 28px; height: 28px; border: 2.5px solid rgba(6, 182, 212, 0.15); border-top-color: #06b6d4; border-radius: 50%; animation: rw-spin .8s linear infinite; }

.rw-fade-enter-active, .rw-fade-leave-active { transition: opacity .25s; }
.rw-fade-enter-from, .rw-fade-leave-to { opacity: 0; }

/* 弹窗 */
.ref-popup-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1000;
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.ref-popup {
  width: 100%; max-width: 560px; max-height: 80vh; background: var(--white); border-radius: 12px;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.2); display: flex; flex-direction: column; overflow: hidden;
}
.ref-popup-head {
  display: flex; align-items: center; justify-content: space-between; padding: 13px 16px;
  border-bottom: 1px solid var(--gray-100); background: var(--gray-50);
}
.ref-popup-head h3 { display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: var(--dark-900); }
.ref-popup-head svg { color: #06b6d4; }
.ref-close { border: none; background: transparent; color: var(--gray-400); cursor: pointer; padding: 4px; border-radius: 6px; transition: all .2s; display: flex; }
.ref-close:hover { background: var(--gray-100); color: var(--dark-800); }
.ref-popup-body { padding: 13px 16px; overflow-y: auto; flex: 1; }
.ref-copy-all {
  display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 7px; border: none;
  background: linear-gradient(135deg, #22d3ee, #06b6d4); color: var(--white); font-size: 11.5px; font-weight: 600; cursor: pointer;
  margin-bottom: 10px; box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25); transition: all .2s;
}
.ref-copy-all:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(6, 182, 212, 0.35); }
.ref-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.ref-item {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 9px 12px;
  background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 10px; transition: all .2s;
}
.ref-item:hover { background: var(--white); border-color: rgba(6, 182, 212, 0.25); box-shadow: 0 4px 12px rgba(6, 182, 212, 0.08); }
.ref-text { flex: 1; font-size: 12px; line-height: 1.55; color: var(--dark-800); }
.ref-copy-one {
  display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 6px;
  border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 11.5px; cursor: pointer;
  white-space: nowrap; transition: all .2s; flex-shrink: 0;
}
.ref-copy-one:hover { border-color: #06b6d4; color: #0e7490; }
.ref-copy-one.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }

.rw-popup-enter-active, .rw-popup-leave-active { transition: opacity .25s; }
.rw-popup-enter-from, .rw-popup-leave-to { opacity: 0; }
.rw-popup-enter-active .ref-popup, .rw-popup-leave-active .ref-popup { transition: transform .25s; }
.rw-popup-enter-from .ref-popup, .rw-popup-leave-to .ref-popup { transform: scale(0.96); }

/* 响应式 */
@media (max-width: 900px) {
  .rw-layout { grid-template-columns: 1fr; }
  .rw-result-wrap { min-height: 150px; }
  .rw-result-area { min-height: 150px; }
}
@media (max-width: 560px) {
  .rw-options { flex-direction: column; align-items: stretch; }
  .rw-select { min-width: 0; width: 100%; }
}
</style>
