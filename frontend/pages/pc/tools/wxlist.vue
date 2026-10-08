<template>
  <ToolShell
    name="参考文献获取"
    desc="输入主题、关键词或多段概要，一键快捷查找相关文献并查看相关度"
    theme="cyan"
    icon='<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>'
  >
    <!-- 搜索面板 -->
    <section class="wx-search">
      <label class="field-label">关键词 / 概要</label>
      <p class="field-hint">支持多行输入，可粘贴摘要或研究背景。</p>
      <textarea
        v-model="keywords"
        class="wx-textarea"
        placeholder="例如：&#10;深度学习在图像识别中的应用&#10;或粘贴一段论文摘要、研究背景……"
      ></textarea>
      <div class="wx-toolbar">
        <div class="wx-select-wrap">
          <select v-model="referenceType" class="wx-select">
            <option value="全部">全部文献</option>
            <option value="中文">中文文献</option>
            <option value="外文">外文文献</option>
          </select>
          <svg class="select-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <button class="wx-fetch-btn" :disabled="loading" @click="fetchReferences">
          <span v-if="!loading" class="btn-inner">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            获取文献
          </span>
          <span v-else class="btn-inner"><span class="btn-spinner"></span>获取中</span>
        </button>
      </div>
    </section>

    <!-- 文献列表 -->
    <section class="wx-list-wrap">
      <div class="panel-head">
        <span class="panel-dot cyan"></span>
        <h2 class="panel-title">文献列表</h2>
        <span v-if="references.length" class="wx-count">{{ references.length }} 条</span>
        <button v-if="references.length" class="wx-copy-all" @click="copyAll">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          {{ allCopied ? '已复制全部' : '复制全部' }}
        </button>
      </div>

      <transition name="wx-fade" mode="out-in">
        <div v-if="loading" key="loading" class="wx-loading">
          <span class="wx-spinner"></span>
          <span>正在获取文献...</span>
        </div>
        <div v-else-if="!references.length" key="empty" class="wx-empty">
          <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          <span>暂无参考文献</span>
          <span class="wx-empty-sub">请输入关键词搜索</span>
        </div>
        <ul v-else key="list" class="wx-items">
          <li v-for="(item, i) in references" :key="i" class="wx-item">
            <div class="wx-item-body">
              <span v-if="item.scoreLabel" class="wx-relevance">{{ item.scoreLabel }}</span>
              <span class="wx-ref-text"><span class="wx-idx">[{{ i + 1 }}]</span> {{ item.quote }}</span>
            </div>
            <div class="wx-item-actions">
              <button class="wx-action-btn copy" :class="{ copied: copiedIdx === i }" @click="copyItem(i)">
                <svg v-if="copiedIdx !== i" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ copiedIdx === i ? '已复制' : '复制' }}
              </button>
              <button class="wx-action-btn danger" @click="deleteItem(i)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                删除
              </button>
            </div>
          </li>
        </ul>
      </transition>
    </section>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '参考文献获取 - 小工具 - AI写作助手' })

const api = useApi()
const toast = useToast()

const keywords = ref('')
const referenceType = ref('全部')
const references = ref([])
const loading = ref(false)
const copiedIdx = ref(-1)
const allCopied = ref(false)

function normalizeQuote(q) {
  if (!q || typeof q !== 'string') return ''
  return q.replace(/^"|"$/g, '').replace(/^\[\d+\]\s*/, '')
}

function formatScore(score) {
  if (score === null || score === undefined || score === '') return ''
  const n = Number(score)
  if (Number.isNaN(n)) return ''
  if (n >= 0 && n <= 1) return '相关度 ' + (n * 100).toFixed(1) + '%'
  return '相关度 ' + n.toFixed(3)
}

function matchType(quote, type) {
  if (type === '全部') return true
  const hasChinese = /[\u4e00-\u9fff]/.test(quote)
  const hasEnglish = /[a-zA-Z]{3,}/.test(quote)
  if (type === '中文') return hasChinese
  if (type === '外文') return hasEnglish && !hasChinese
  return true
}

async function fetchReferences() {
  const kw = keywords.value.trim()
  if (!kw) {
    toast.warning('请输入关键词')
    return
  }
  loading.value = true
  references.value = []
  try {
    const res = await api.post('/api/other_api/wxlist_relevance', {
      keywords: kw,
      type: referenceType.value,
      relevance_method: 'hybrid',
    })
    const arr = Array.isArray(res.data) ? res.data : []
    const rows = arr
      .filter(item => item && typeof item === 'object' && (item.quote || item.title))
      .map(item => ({
        quote: normalizeQuote(item.quote || item.title || ''),
        scoreLabel: formatScore(item.relevance_score),
      }))
      .filter(row => row.quote && matchType(row.quote, referenceType.value))
    references.value = rows
    if (!rows.length) toast.info('未找到相关文献')
  } catch (e) {
    toast.error('获取文献失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

async function copyItem(i) {
  const row = references.value[i]
  if (!row) return
  await copyText(row.quote)
  copiedIdx.value = i
  toast.success('复制成功')
  setTimeout(() => { copiedIdx.value = -1 }, 1800)
}

async function copyAll() {
  if (!references.value.length) return
  const text = references.value.map((r, i) => `[${i + 1}] ${r.quote}`).join('\n')
  await copyText(text)
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

function deleteItem(i) {
  references.value.splice(i, 1)
}
</script>

<style scoped>
.wx-search {
  border: 1px solid var(--gray-100); border-radius: 10px; padding: 11px 12px;
  background: var(--gray-50); margin-bottom: 10px; position: relative; overflow: hidden;
}
.wx-search::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #06b6d4 0%, #67e8f9 60%, transparent 100%); opacity: 0.7; }
.field-label { display: block; font-size: 11.5px; font-weight: 700; color: var(--dark-900); margin-bottom: 2px; }
.field-hint { margin: 0 0 5px; font-size: 10.5px; color: var(--gray-400); line-height: 1.4; }

.wx-textarea {
  width: 100%; min-height: 62px; border: 1px solid var(--gray-200); border-radius: 7px;
  padding: 8px 10px; font-size: 13px; line-height: 1.6; color: var(--dark-800); background: var(--white);
  outline: none; resize: vertical; font-family: inherit; transition: border-color .2s, box-shadow .2s;
}
.wx-textarea:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.14); }
.wx-textarea::placeholder { color: var(--gray-400); }

.wx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 7px; margin-top: 9px; }
.wx-select-wrap { position: relative; }
.wx-select {
  padding: 6px 28px 6px 10px; border-radius: 7px; border: 1px solid var(--gray-200);
  font-size: 12px; color: var(--dark-800); background: var(--white); cursor: pointer; appearance: none; font-family: inherit;
  min-width: 130px; transition: border-color .2s, box-shadow .2s;
}
.wx-select:hover { border-color: var(--gray-300); }
.wx-select:focus { outline: none; border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.14); }
.select-caret { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; }

.wx-fetch-btn {
  border: none; border-radius: 8px; padding: 7px 14px; background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer; box-shadow: 0 3px 10px rgba(6, 182, 212, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s; min-width: 100px;
}
.wx-fetch-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(6, 182, 212, 0.34); }
.wx-fetch-btn:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }

.btn-inner { display: inline-flex; align-items: center; gap: 5px; justify-content: center; }
.btn-spinner { width: 13px; height: 13px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: wx-spin .7s linear infinite; }
@keyframes wx-spin { to { transform: rotate(360deg); } }

/* 列表 */
.wx-list-wrap { border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px; background: var(--white); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02); position: relative; overflow: hidden; }
.wx-list-wrap::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #06b6d4 0%, #67e8f9 60%, transparent 100%); opacity: 0.7; }
.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.cyan { background: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }
.wx-count { margin-left: auto; font-size: 11.5px; color: var(--gray-400); font-variant-numeric: tabular-nums; }
.wx-copy-all {
  margin-left: 6px; display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 6px; border: none;
  background: linear-gradient(135deg, #22d3ee, #06b6d4); color: var(--white); font-size: 11.5px; font-weight: 600; cursor: pointer;
  box-shadow: 0 3px 8px rgba(6, 182, 212, 0.22); transition: all .2s;
}
.wx-copy-all:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(6, 182, 212, 0.32); }

.wx-loading, .wx-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px;
  min-height: 150px; color: var(--gray-400); font-size: 12px;
}
.wx-empty svg { color: var(--gray-200); }
.wx-empty-sub { font-size: 11.5px; color: var(--gray-300); }
.wx-spinner { width: 24px; height: 24px; border: 2.5px solid rgba(6, 182, 212, 0.15); border-top-color: #06b6d4; border-radius: 50%; animation: wx-spin .8s linear infinite; }

.wx-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 5px; }
.wx-item {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 9px; padding: 9px 11px;
  background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 8px; transition: all .2s;
}
.wx-item:hover { background: var(--white); border-color: rgba(6, 182, 212, 0.25); box-shadow: 0 3px 12px rgba(6, 182, 212, 0.08); transform: translateY(-1px); }
.wx-item-body { flex: 1; min-width: 0; }
.wx-relevance {
  display: inline-flex; align-items: center; font-size: 10.5px; color: #0e7490; font-weight: 700;
  padding: 2px 6px; background: rgba(6, 182, 212, 0.1); border-radius: 999px; border: 1px solid rgba(6, 182, 212, 0.22);
  margin-bottom: 4px; letter-spacing: 0.02em;
}
.wx-ref-text { display: block; font-size: 12px; line-height: 1.55; color: var(--dark-800); }
.wx-idx { color: #06b6d4; font-weight: 700; }
.wx-item-actions { display: flex; gap: 4px; flex-shrink: 0; }
.wx-action-btn {
  display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 5px; border: 1px solid var(--gray-200);
  background: var(--white); color: var(--gray-600); font-size: 11.5px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all .2s;
}
.wx-action-btn.copy:hover { border-color: #06b6d4; color: #0e7490; background: rgba(6, 182, 212, 0.05); }
.wx-action-btn.copy.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }
.wx-action-btn.danger:hover { border-color: #ef4444; color: #dc2626; background: rgba(239, 68, 68, 0.05); }

.wx-fade-enter-active, .wx-fade-leave-active { transition: opacity .25s; }
.wx-fade-enter-from, .wx-fade-leave-to { opacity: 0; }

@media (max-width: 560px) {
  .wx-toolbar { flex-direction: column; align-items: stretch; }
  .wx-select { min-width: 0; width: 100%; }
  .wx-fetch-btn { width: 100%; }
  .wx-item { flex-direction: column; gap: 8px; }
  .wx-item-actions { justify-content: flex-end; }
}
/* ≤640：多行 placeholder（示例两行+提示）超出 62px 高度被裁，加高完整展示 */
@media (max-width: 640px) {
  .wx-textarea { min-height: 118px; }
}
</style>
