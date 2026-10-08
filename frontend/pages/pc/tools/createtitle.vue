<template>
  <ToolShell
    name="题目生成"
    desc="基于大数据分析，提供热门和前沿主题推荐"
    theme="orange"
    icon='<path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>'
  >
    <div class="ct-layout">
      <!-- 输入区 -->
      <section class="ct-panel">
        <div class="panel-head">
          <span class="panel-dot orange"></span>
          <h2 class="panel-title">研究方向与兴趣</h2>
        </div>
        <textarea
          v-model="researchText"
          class="ct-textarea"
          placeholder="请描述您的研究方向、兴趣领域和关键词..."
        ></textarea>

        <div class="ct-options">
          <div class="ct-select-wrap">
            <select v-model="titleType" class="ct-select">
              <option value="empirical">实证研究</option>
              <option value="theoretical">理论研究</option>
              <option value="case">案例研究</option>
              <option value="comparative">对比研究</option>
              <option value="review">综述性研究</option>
            </select>
            <svg class="select-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="ct-actions">
            <button class="ct-btn-primary" :disabled="loading" @click="generate">
              <span v-if="!loading" class="btn-inner">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>
                生成题目
              </span>
              <span v-else class="btn-inner"><span class="btn-spinner"></span>获取中</span>
            </button>
            <button class="ct-btn-default" @click="clearAll">清空</button>
          </div>
        </div>
      </section>

      <!-- 结果区 -->
      <section class="ct-panel ct-result">
        <div class="panel-head">
          <span class="panel-dot orange"></span>
          <h2 class="panel-title">推荐题目</h2>
          <span v-if="titles.length" class="ct-count">{{ titles.length }} 个结果</span>
        </div>

        <div class="ct-list">
          <transition name="ct-fade" mode="out-in">
            <div v-if="loading" key="loading" class="ct-loading">
              <span class="ct-spinner"></span>
              <span>正在生成题目...</span>
            </div>
            <div v-else-if="!titles.length" key="empty" class="ct-empty">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>
              <span>推荐题目将显示在这里</span>
            </div>
            <div v-else key="list" class="ct-items">
              <article v-for="(item, i) in titles" :key="i" class="ct-item">
                <div class="ct-item-index">{{ i + 1 }}</div>
                <div class="ct-item-body">
                  <h3 class="ct-item-title">{{ item.title }}</h3>
                  <p class="ct-item-abstract">{{ item.abstract }}</p>
                </div>
                <button class="ct-copy-btn" :class="{ copied: copiedIdx === i }" @click="copyTitle(i)">
                  <svg v-if="copiedIdx !== i" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  {{ copiedIdx === i ? '已复制' : '复制' }}
                </button>
              </article>
            </div>
          </transition>
        </div>
      </section>
    </div>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '题目生成 - 小工具 - AI写作助手' })

const api = useApi()
const toast = useToast()

const titleTypeOptions = [
  { value: 'empirical', text: '实证研究' },
  { value: 'theoretical', text: '理论研究' },
  { value: 'case', text: '案例研究' },
  { value: 'comparative', text: '对比研究' },
  { value: 'review', text: '综述性研究' },
]

const researchText = ref('')
const titleType = ref('empirical')
const titles = ref([])
const loading = ref(false)
const copiedIdx = ref(-1)

async function generate() {
  const r = researchText.value.trim()
  if (!r) {
    toast.warning('请输入研究方向和兴趣')
    return
  }
  loading.value = true
  titles.value = []
  try {
    const typeText = titleTypeOptions.find(o => o.value === titleType.value)?.text || '实证研究'
    const res = await api.post('/api/tools/createtitle', {
      researchText: r,
      titleType: typeText,
    })
    titles.value = Array.isArray(res.data?.titles) ? res.data.titles : []
    if (!titles.value.length) toast.warning('未能生成题目，请稍后重试')
  } catch (e) {
    toast.error('生成题目失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

async function copyTitle(i) {
  const item = titles.value[i]
  if (!item) return
  const typeText = titleTypeOptions.find(o => o.value === titleType.value)?.text || ''
  const text = `题目：${item.title} (${typeText})\n写作方向：${item.abstract}`
  try {
    await navigator.clipboard.writeText(text)
  } catch (e) {
    const ta = document.createElement('textarea')
    ta.value = text
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  copiedIdx.value = i
  toast.success('复制成功')
  setTimeout(() => { copiedIdx.value = -1 }, 1800)
}

function clearAll() {
  researchText.value = ''
  titles.value = []
}
</script>

<style scoped>
.ct-layout { display: flex; flex-direction: column; gap: 10px; }
.ct-panel {
  border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px;
  background: var(--white); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02);
  position: relative; overflow: hidden;
}
.ct-panel::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, #f97316 0%, #fdba74 60%, transparent 100%); opacity: 0.7;
}
.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.orange { background: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }
.ct-count { margin-left: auto; font-size: 11.5px; color: var(--gray-400); font-variant-numeric: tabular-nums; }

.ct-textarea {
  width: 100%; min-height: 70px; border: 1px solid var(--gray-200); border-radius: 7px;
  padding: 8px 10px; font-size: 13px; line-height: 1.6; color: var(--dark-800); background: #fcfdff;
  outline: none; resize: vertical; font-family: inherit; transition: border-color .2s, box-shadow .2s, background .2s;
}
.ct-textarea:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12); background: var(--white); }
.ct-textarea::placeholder { color: var(--gray-400); }

.ct-options { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 9px; }
.ct-actions { display: flex; gap: 6px; margin-left: auto; }

.ct-select-wrap { position: relative; }
.ct-select {
  padding: 6px 28px 6px 10px; border-radius: 7px; border: 1px solid var(--gray-200);
  font-size: 12px; color: var(--dark-800); background: var(--white); cursor: pointer; appearance: none; font-family: inherit;
  min-width: 140px; transition: border-color .2s, box-shadow .2s;
}
.ct-select:hover { border-color: var(--gray-300); }
.ct-select:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12); }
.select-caret { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; }

.ct-btn-primary {
  border: none; border-radius: 8px; padding: 7px 14px; background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer; box-shadow: 0 3px 10px rgba(249, 115, 22, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s;
}
.ct-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(249, 115, 22, 0.32); }
.ct-btn-primary:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }
.ct-btn-default {
  border: 1px solid var(--gray-200); border-radius: 8px; padding: 7px 14px; background: var(--white);
  color: var(--gray-600); font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.ct-btn-default:hover { background: var(--gray-50); border-color: var(--gray-300); }

.btn-inner { display: inline-flex; align-items: center; gap: 5px; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: ct-spin .7s linear infinite; }
@keyframes ct-spin { to { transform: rotate(360deg); } }

/* 结果列表 */
.ct-list { min-height: 120px; }
.ct-loading, .ct-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  min-height: 120px; color: var(--gray-400); font-size: 12px;
}
.ct-spinner { width: 24px; height: 24px; border: 2.5px solid rgba(249, 115, 22, 0.15); border-top-color: #f97316; border-radius: 50%; animation: ct-spin .8s linear infinite; }

.ct-items { display: flex; flex-direction: column; gap: 7px; }
.ct-item {
  display: flex; align-items: flex-start; gap: 9px; padding: 9px 11px;
  background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 8px;
  transition: all .25s ease;
}
.ct-item:hover { background: var(--white); border-color: rgba(249, 115, 22, 0.2); box-shadow: 0 3px 12px rgba(249, 115, 22, 0.08); transform: translateX(2px); }
.ct-item-index {
  width: 22px; height: 22px; border-radius: 5px; flex-shrink: 0;
  background: linear-gradient(135deg, #fb923c, #f97316); color: var(--white);
  display: flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums;
  box-shadow: 0 2px 5px rgba(249, 115, 22, 0.22);
}
.ct-item-body { flex: 1; min-width: 0; }
.ct-item-title { font-size: 13px; font-weight: 700; color: var(--dark-900); line-height: 1.35; margin-bottom: 4px; }
.ct-item-abstract {
  font-size: 11.5px; color: var(--gray-500); line-height: 1.55; padding-left: 9px;
  border-left: 2px solid var(--gray-200);
}
.ct-copy-btn {
  display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 5px;
  border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); font-size: 11.5px; font-weight: 600;
  cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: all .2s;
}
.ct-copy-btn:hover { border-color: #f97316; color: #c2410c; background: rgba(249, 115, 22, 0.05); }
.ct-copy-btn.copied { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #15803d; }

.ct-fade-enter-active, .ct-fade-leave-active { transition: opacity .25s; }
.ct-fade-enter-from, .ct-fade-leave-to { opacity: 0; }

@media (max-width: 560px) {
  .ct-options { flex-direction: column; align-items: stretch; }
  .ct-actions { margin-left: 0; }
  .ct-select { min-width: 0; width: 100%; }
  .ct-item { flex-wrap: wrap; }
  .ct-copy-btn { margin-left: 36px; }
}
</style>
