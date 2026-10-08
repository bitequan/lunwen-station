<template>
  <ToolShell
    name="大纲生成"
    desc="智能分析主题结构，生成专业学术大纲框架"
    theme="teal"
    icon='<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>'
  >
    <div class="co-layout">
      <!-- 输入区 -->
      <section class="co-panel">
        <div class="panel-head">
          <span class="panel-dot teal"></span>
          <h2 class="panel-title">要求输入区</h2>
        </div>
        <textarea
          v-model="topic"
          class="co-textarea"
          placeholder="请输入论文主题或者写作要求..."
        ></textarea>

        <div class="co-options">
          <div class="co-select-wrap">
            <select v-model="field" class="co-select">
              <option v-for="f in fields" :key="f.value" :value="f.text">{{ f.text }}</option>
            </select>
            <svg class="select-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>

          <label class="co-switch-inline">
            <label class="co-switch">
              <input type="checkbox" v-model="useThirdLevel" />
              <span class="slider teal"></span>
            </label>
            <span class="switch-label">使用三级目录</span>
          </label>

          <div class="co-actions">
            <button class="co-btn-primary" :disabled="loading" @click="generate">
              <span v-if="!loading" class="btn-inner">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
                生成大纲
              </span>
              <span v-else class="btn-inner"><span class="btn-spinner"></span>生成中</span>
            </button>
            <button class="co-btn-default" @click="clearAll">清空</button>
          </div>
        </div>
      </section>

      <!-- 结果区 -->
      <section class="co-panel co-result">
        <div class="panel-head">
          <span class="panel-dot teal"></span>
          <h2 class="panel-title">大纲结果</h2>
          <button v-if="outlineHtml" class="co-copy-btn" :class="{ copied }" @click="copyOutline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            {{ copied ? '已复制' : '复制结果' }}
          </button>
        </div>

        <div ref="resultBody" class="co-result-wrap">
          <transition name="co-fade" mode="out-in">
            <div v-if="loading" key="loading" class="co-loading">
              <span class="co-spinner"></span>
              <span>正在生成大纲...</span>
            </div>
            <div v-else-if="!outlineHtml" key="empty" class="co-empty">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
              <span>大纲结果将显示在这里</span>
            </div>
            <div v-else key="result" class="co-outline markdown-body" v-html="outlineHtml"></div>
          </transition>
        </div>
      </section>
    </div>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '大纲生成 - 小工具 - AI写作助手' })

const toast = useToast()

const fields = [
  { value: 'humanities', text: '人文社科' },
  { value: 'economics', text: '经济管理' },
  { value: 'education', text: '教育学' },
  { value: 'science', text: '自然科学' },
  { value: 'engineering', text: '工程技术' },
  { value: 'medicine', text: '医学卫生' },
  { value: 'agriculture', text: '农林科学' },
  { value: 'art', text: '艺术设计' },
  { value: 'law', text: '法学' },
  { value: 'philosophy', text: '哲学' },
]

const topic = ref('')
const field = ref('人文社科')
const useThirdLevel = ref(false)
const outlineRaw = ref('')
const outlineHtml = ref('')
const loading = ref(false)
const copied = ref(false)
const resultBody = ref(null)
let streamRaf = 0

// 轻量 markdown 解析：支持 #/##/### 标题、- 无序列表、1. 有序列表、加粗、段落
function renderMarkdown(md) {
  if (!md) return ''
  const lines = md.split(/\r?\n/)
  const html = []
  let inUl = false
  let inOl = false
  const closeLists = () => {
    if (inUl) { html.push('</ul>'); inUl = false }
    if (inOl) { html.push('</ol>'); inOl = false }
  }
  const inline = (s) => s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
  for (let raw of lines) {
    const line = raw.replace(/\s+$/, '')
    if (!line.trim()) { closeLists(); continue }
    let m
    if ((m = line.match(/^(#{1,6})\s+(.*)$/))) {
      closeLists()
      const level = m[1].length
      html.push(`<h${level}>${inline(m[2])}</h${level}>`)
    } else if ((m = line.match(/^[-*+]\s+(.*)$/))) {
      if (inOl) { html.push('</ol>'); inOl = false }
      if (!inUl) { html.push('<ul>'); inUl = true }
      html.push(`<li>${inline(m[1])}</li>`)
    } else if ((m = line.match(/^\d+[.)]\s+(.*)$/))) {
      if (inUl) { html.push('</ul>'); inUl = false }
      if (!inOl) { html.push('<ol>'); inOl = true }
      html.push(`<li>${inline(m[1])}</li>`)
    } else {
      closeLists()
      html.push(`<p>${inline(line)}</p>`)
    }
  }
  closeLists()
  return html.join('\n')
}

// ===== 复用论文大纲生成接口 /api/ai/generateOutline (SSE, 降级 sync) =====
// 与 OutlineEditor 一致:登录态从 localStorage 读取并兼容旧 key,解析出 token
function getToken() {
  if (process.client) {
    try {
      let raw = localStorage.getItem('aidian_auth_v2')
      if (!raw) {
        const oldRaw = localStorage.getItem('aidian_auth')
        if (oldRaw) { raw = oldRaw; try { localStorage.removeItem('aidian_auth') } catch (e) {} }
      }
      if (!raw) return ''
      return JSON.parse(raw)?.token || ''
    } catch (e) { return '' }
  }
  return ''
}
// 组装论文大纲生成请求参数(topic→title, field→profession, 三级目录→outline_level)
function buildOutlineReq() {
  return {
    title: topic.value.trim(),
    words: '',
    model: 'standard',
    type: 'paper',
    outline_level: useThirdLevel.value ? 'three' : 'two',
    language: '中文',
    profession: field.value,
    major: '',
    degree: '本科',
    template_name: '',
  }
}
// 兼容非流式环境:从完整 SSE 文本中拼接 data:{content}
function parseSseFullText(rawText) {
  let out = ''
  rawText.split('\n').forEach((line) => {
    if (!line.startsWith('data:')) return
    const s = line.slice(5).trim()
    if (!s) return
    try { const d = JSON.parse(s); if (d.content) out += d.content } catch (e) {}
  })
  return out
}
function finishOutline(md) { outlineRaw.value = md; outlineHtml.value = renderMarkdown(md) }
// 流式跟随滚动:生成时结果区自动滚到底部(rAF 节流),完成滚回顶部
function streamFollow() {
  if (!process.client) return
  if (typeof requestAnimationFrame === "function") {
    cancelAnimationFrame(streamRaf)
    streamRaf = requestAnimationFrame(() => { if (resultBody.value) resultBody.value.scrollTop = resultBody.value.scrollHeight })
  }
}
function streamDone() {
  if (!process.client) return
  cancelAnimationFrame(streamRaf)
  if (resultBody.value) resultBody.value.scrollTop = 0
}
// 登录过期处理
function handleExpired() {
  toast.warning('登录已过期，请重新登录')
  localStorage.removeItem('aidian_auth')
  localStorage.removeItem('aidian_auth_v2')
  useLoginModal().open()
}
// SSE 流式生成大纲(复用论文大纲生成接口)
async function generateSSE() {
  const token = getToken()
  const resp = await fetch('/api/ai/generateOutline', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', token, 'Accept': 'text/event-stream' },
    body: JSON.stringify(buildOutlineReq()),
  })
  if (!resp.ok) throw new Error('HTTP ' + resp.status)
  const ct = resp.headers.get('content-type') || ''
  if (ct.includes('application/json')) {
    const j = await resp.json()
    if (j.code === -1) { handleExpired(); return }
    throw new Error(j.msg || '生成失败')
  }
  if (!resp.body || typeof resp.body.getReader !== 'function') {
    const fullText = parseSseFullText(await resp.text())
    if (!fullText) throw new Error('未收到任何内容')
    finishOutline(fullText)
    return
  }
  const reader = resp.body.getReader()
  const decoder = new TextDecoder('utf-8')
  let fullText = ''
  let buffer = ''
  let started = false
  while (true) {
    const { done, value } = await reader.read()
    if (done) break
    buffer += decoder.decode(value, { stream: true })
    while (buffer.includes('\n\n')) {
      const idx = buffer.indexOf('\n\n')
      const event = buffer.slice(0, idx)
      buffer = buffer.slice(idx + 2)
      for (const line of event.split('\n')) {
        if (!line.startsWith('data:')) continue
        const jsonStr = line.slice(5).trim()
        if (!jsonStr) continue
        const data = JSON.parse(jsonStr)
        if (data.error) throw new Error(data.error)
        if (data.done) { if (fullText) { finishOutline(fullText); streamDone() } return }
        if (data.content) {
          if (!started) { started = true; loading.value = false }
          fullText += data.content
          outlineHtml.value = renderMarkdown(fullText)
          streamFollow()
        }
      }
    }
  }
  if (buffer.trim()) {
    for (const line of buffer.split('\n')) {
      if (!line.startsWith('data:')) continue
      const s = line.slice(5).trim()
      if (!s) continue
      try { const d = JSON.parse(s); if (d.content) fullText += d.content } catch (e) {}
    }
  }
  if (fullText) { finishOutline(fullText); streamDone() }
  else throw new Error('未收到任何内容')
}
// 降级:同步接口
async function generateSync() {
  const resp = await fetch('/api/ai/generateOutlineSync', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', token: getToken() },
    body: JSON.stringify(buildOutlineReq()),
  })
  const j = await resp.json()
  if (j.code === -1) { handleExpired(); return }
  if (j.code !== 1 || !j.data?.content) throw new Error(j.msg || '同步生成失败')
  finishOutline(j.data.content)
}
async function generate() {
  const t = topic.value.trim()
  if (!t) { toast.warning('请输入论文主题'); return }
  loading.value = true
  outlineRaw.value = ''
  outlineHtml.value = ''
  try {
    await generateSSE()
  } catch (e) {
    try { await generateSync() }
    catch (e2) { toast.error('生成大纲失败，请稍后重试') }
  } finally {
    loading.value = false
  }
}


async function copyOutline() {
  if (!outlineRaw.value) return
  try {
    await navigator.clipboard.writeText(outlineRaw.value)
  } catch (e) {
    const ta = document.createElement('textarea')
    ta.value = outlineRaw.value
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  copied.value = true
  toast.success('大纲已复制')
  setTimeout(() => { copied.value = false }, 1800)
}

function clearAll() {
  topic.value = ''
  outlineRaw.value = ''
  outlineHtml.value = ''
}
</script>

<style scoped>
.co-layout { display: flex; flex-direction: column; gap: 10px; }
.co-panel {
  border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px;
  background: var(--white); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  position: relative; overflow: hidden;
}
.co-panel::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, #14b8a6 0%, #5eead4 60%, transparent 100%); opacity: 0.7;
}
.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.teal { background: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }

.co-textarea {
  width: 100%; min-height: 60px; border: 1px solid var(--gray-200); border-radius: 7px;
  padding: 8px 10px; font-size: 13px; line-height: 1.6; color: var(--dark-800); background: #fcfdff;
  outline: none; resize: vertical; font-family: inherit; transition: border-color .2s, box-shadow .2s, background .2s;
}
.co-textarea:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14); background: var(--white); }
.co-textarea::placeholder { color: var(--gray-400); }

.co-options { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 9px; }
.co-switch-inline { display: flex; align-items: center; gap: 5px; }
.co-actions { display: flex; gap: 6px; margin-left: auto; }

.co-select-wrap { position: relative; }
.co-select {
  padding: 6px 28px 6px 10px; border-radius: 7px; border: 1px solid var(--gray-200);
  font-size: 12px; color: var(--dark-800); background: var(--white); cursor: pointer; appearance: none; font-family: inherit;
  min-width: 140px; transition: border-color .2s, box-shadow .2s;
}
.co-select:hover { border-color: var(--gray-300); }
.co-select:focus { outline: none; border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14); }
.select-caret { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; }

/* 开关 */
.co-switch { position: relative; display: inline-block; width: 34px; height: 19px; flex-shrink: 0; }
.co-switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background: var(--gray-300); transition: .3s; border-radius: 22px; }
.slider:before { position: absolute; content: ""; height: 13px; width: 13px; left: 3px; bottom: 3px; background: var(--white); transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
input:checked + .slider.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
input:checked + .slider.teal:before { transform: translateX(15px); }
.switch-label { font-size: 11.5px; color: var(--gray-600); font-weight: 500; }

.co-btn-primary {
  border: none; border-radius: 8px; padding: 7px 14px; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer; box-shadow: 0 3px 10px rgba(13, 148, 136, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s;
}
.co-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(13, 148, 136, 0.32); }
.co-btn-primary:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }
.co-btn-default {
  border: 1px solid var(--gray-200); border-radius: 8px; padding: 7px 14px; background: var(--white);
  color: var(--gray-600); font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.co-btn-default:hover { background: var(--gray-50); border-color: var(--gray-300); }

.btn-inner { display: inline-flex; align-items: center; gap: 5px; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: co-spin .7s linear infinite; }
@keyframes co-spin { to { transform: rotate(360deg); } }

.co-copy-btn {
  margin-left: auto; display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 6px;
  border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.co-copy-btn:hover { border-color: #14b8a6; color: #0d9488; background: rgba(20, 184, 166, 0.06); }
.co-copy-btn.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }

/* 结果区 */
.co-result-wrap { min-height: 170px; max-height: 62vh; overflow-y: auto; position: relative; }
.co-loading, .co-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  min-height: 170px; color: var(--gray-400); font-size: 12px;
}
.co-spinner { width: 24px; height: 24px; border: 2.5px solid rgba(20, 184, 166, 0.15); border-top-color: #14b8a6; border-radius: 50%; animation: co-spin .8s linear infinite; }

/* markdown 渲染 */
.co-outline {
  padding: 12px 14px; background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 8px;
  line-height: 1.65; font-size: 12.5px; color: var(--dark-800); min-height: 170px;
}
.markdown-body :deep(h1) { font-size: 15px; font-weight: 800; color: var(--dark-900); margin: 0 0 7px; padding-bottom: 5px; border-bottom: 2px solid #14b8a6; }
.markdown-body :deep(h2) { font-size: 13.5px; font-weight: 700; color: var(--dark-900); margin: 10px 0 5px; padding-left: 7px; border-left: 3px solid #14b8a6; }
.markdown-body :deep(h3) { font-size: 12.5px; font-weight: 700; color: var(--dark-800); margin: 8px 0 4px; padding-left: 6px; border-left: 2px solid var(--gray-300); }
.markdown-body :deep(h4) { font-size: 12.5px; font-weight: 600; color: var(--dark-800); margin: 7px 0 4px; }
.markdown-body :deep(ul) { padding-left: 16px; margin: 4px 0; }
.markdown-body :deep(ol) { padding-left: 16px; margin: 4px 0; }
.markdown-body :deep(li) { margin-bottom: 3px; line-height: 1.55; }
.markdown-body :deep(li::marker) { color: #14b8a6; font-weight: 700; }
.markdown-body :deep(strong) { color: var(--dark-900); font-weight: 700; }
.markdown-body :deep(p) { margin: 4px 0; }

.co-fade-enter-active, .co-fade-leave-active { transition: opacity .25s; }
.co-fade-enter-from, .co-fade-leave-to { opacity: 0; }

@media (max-width: 560px) {
  .co-options { flex-direction: column; align-items: stretch; }
  .co-actions { margin-left: 0; }
  .co-select { min-width: 0; width: 100%; }
}
</style>
