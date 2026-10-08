<template>
  <ToolShell
    name="段落配图"
    desc="自研高阶算法，根据文字内容快速精准提供配图"
    theme="teal"
    icon='<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'
  >
    <!-- 版权声明 -->
    <div class="il-notice">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span>图片资源来自各大搜索引擎，版权归属原作者，未取得版权前严禁用于商用及发表文章！</span>
    </div>

    <!-- 输入区 -->
    <section class="il-panel">
      <div class="panel-head">
        <span class="panel-dot teal"></span>
        <h2 class="panel-title">内容输入</h2>
      </div>
      <textarea
        v-model="content"
        class="il-textarea"
        placeholder="请输入需要配图的文字内容..."
      ></textarea>

      <div class="il-options">
        <div class="il-match">
          <label class="il-radio" :class="{ active: matchType === 'simple' }">
            <input type="radio" v-model="matchType" value="simple" />
            <span class="radio-dot"></span>
            <span class="radio-text">普通匹配（关键词）</span>
          </label>
          <label class="il-radio" :class="{ active: matchType === 'advanced' }">
            <input type="radio" v-model="matchType" value="advanced" />
            <span class="radio-dot"></span>
            <span class="radio-text">高级匹配（智能算法）</span>
          </label>
        </div>
        <button class="il-btn-primary" :disabled="loading" @click="searchImages">
          <span v-if="!loading" class="btn-inner">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            开始配图
          </span>
          <span v-else class="btn-inner"><span class="btn-spinner"></span>搜索中</span>
        </button>
      </div>
    </section>

    <!-- 结果区 -->
    <section class="il-result">
      <div class="panel-head">
        <span class="panel-dot teal"></span>
        <h2 class="panel-title">配图结果</h2>
        <span v-if="images.length" class="il-count">{{ images.length }} 张</span>
      </div>

      <transition name="il-fade" mode="out-in">
        <div v-if="loading" key="loading" class="il-loading">
          <span class="il-spinner"></span>
          <span>正在搜索匹配的图片...</span>
        </div>
        <div v-else-if="!images.length" key="empty" class="il-empty">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <span>配图结果将显示在这里</span>
        </div>
        <div v-else key="grid" class="il-grid">
          <article v-for="(img, i) in images" :key="i" class="il-card">
            <div class="il-thumb">
              <img :src="img.url" :alt="img.title" loading="lazy" @error="onImgError($event)" />
              <button class="il-copy-link" :class="{ copied: copiedIdx === i }" @click="copyUrl(img.url, i)">
                <svg v-if="copiedIdx !== i" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ copiedIdx === i ? '已复制' : '复制链接' }}
              </button>
            </div>
            <div class="il-card-body">
              <p class="il-title" :title="img.title">{{ img.title }}</p>
              <a :href="img.url" target="_blank" rel="noopener" class="il-source" :title="img.url">{{ img.url }}</a>
            </div>
          </article>
        </div>
      </transition>
    </section>
  </ToolShell>
</template>

<script setup>
definePageMeta({ layout: 'console' })
useSeoMeta({ title: '段落配图 - 小工具 - AI写作助手' })

const api = useApi()
const toast = useToast()

const content = ref('')
const matchType = ref('simple')
const images = ref([])
const loading = ref(false)
const copiedIdx = ref(-1)

async function searchImages() {
  const c = content.value.trim()
  if (!c) {
    toast.warning('请输入文字内容')
    return
  }
  loading.value = true
  images.value = []
  try {
    const res = await api.post('/api/tools/illustration', {
      content: c,
      matchType: matchType.value,
    })
    const arr = Array.isArray(res.data) ? res.data : []
    images.value = arr.filter(item => item && item.url)
    if (!images.value.length) toast.info('未找到匹配的图片')
  } catch (e) {
    toast.error('搜索图片失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

async function copyUrl(url, i) {
  try {
    await navigator.clipboard.writeText(url)
  } catch (e) {
    const ta = document.createElement('textarea')
    ta.value = url
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
  copiedIdx.value = i
  toast.success('链接已复制')
  setTimeout(() => { copiedIdx.value = -1 }, 1800)
}

function onImgError(e) {
  e.target.style.background = '#f1f5f9'
  e.target.style.padding = '30px'
  e.target.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>')
}
</script>

<style scoped>
.il-notice {
  display: flex; align-items: flex-start; gap: 6px; padding: 8px 11px; border-radius: 7px;
  border: 1px solid #fde68a; border-left: 3px solid #f59e0b; background: #fffbeb; color: #92400e;
  font-size: 11px; line-height: 1.55; margin-bottom: 10px;
}
.il-notice svg { color: #f59e0b; flex-shrink: 0; margin-top: 1px; }

.il-panel { border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px; background: var(--white); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02); margin-bottom: 10px; position: relative; overflow: hidden; }
.il-panel::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #14b8a6 0%, #5eead4 60%, transparent 100%); opacity: 0.7; }
.panel-head { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.teal { background: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15); }
.panel-title { font-size: 13px; font-weight: 700; color: var(--dark-900); letter-spacing: -0.005em; }
.il-count { margin-left: auto; font-size: 11px; color: var(--gray-400); font-variant-numeric: tabular-nums; }

.il-textarea {
  width: 100%; min-height: 66px; border: 1px solid var(--gray-200); border-radius: 7px;
  padding: 8px 10px; font-size: 13px; line-height: 1.6; color: var(--dark-800); background: #fcfdff;
  outline: none; resize: vertical; font-family: inherit; transition: border-color .2s, box-shadow .2s, background .2s;
}
.il-textarea:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14); background: var(--white); }
.il-textarea::placeholder { color: var(--gray-400); }

.il-options { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 9px; justify-content: space-between; }
.il-match { display: flex; gap: 8px; flex-wrap: wrap; }
.il-radio { display: flex; align-items: center; gap: 5px; cursor: pointer; padding: 5px 10px; border-radius: 7px; border: 1px solid var(--gray-200); background: var(--white); transition: all .2s; }
.il-radio:hover { border-color: var(--gray-300); }
.il-radio.active { border-color: #14b8a6; background: rgba(20, 184, 166, 0.06); }
.il-radio input { display: none; }
.radio-dot { width: 13px; height: 13px; border-radius: 50%; border: 2px solid var(--gray-300); position: relative; flex-shrink: 0; transition: all .2s; }
.il-radio.active .radio-dot { border-color: #14b8a6; }
.il-radio.active .radio-dot::after { content: ''; position: absolute; inset: 2px; border-radius: 50%; background: linear-gradient(135deg, #14b8a6, #0d9488); }
.radio-text { font-size: 11.5px; color: var(--gray-600); font-weight: 500; }
.il-radio.active .radio-text { color: #0d9488; font-weight: 600; }

.il-btn-primary {
  border: none; border-radius: 8px; padding: 7px 14px; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: var(--white); font-size: 12.5px; font-weight: 700; cursor: pointer; box-shadow: 0 3px 10px rgba(13, 148, 136, 0.25);
  transition: transform .15s, box-shadow .2s, opacity .2s;
}
.il-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(13, 148, 136, 0.34); }
.il-btn-primary:disabled { opacity: 0.75; cursor: not-allowed; box-shadow: none; transform: none; }

.btn-inner { display: inline-flex; align-items: center; gap: 6px; }
.btn-spinner { width: 12px; height: 12px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: il-spin .7s linear infinite; }
@keyframes il-spin { to { transform: rotate(360deg); } }

/* 结果 */
.il-result { border: 1px solid var(--gray-100); border-radius: 10px; padding: 12px; background: var(--white); box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035), 0 1px 2px rgba(15, 23, 42, 0.02); min-height: 170px; position: relative; overflow: hidden; }
.il-result::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #14b8a6 0%, #5eead4 60%, transparent 100%); opacity: 0.7; }
.il-loading, .il-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 7px;
  min-height: 130px; color: var(--gray-400); font-size: 12px;
}
.il-spinner { width: 24px; height: 24px; border: 2.5px solid rgba(20, 184, 166, 0.15); border-top-color: #14b8a6; border-radius: 50%; animation: il-spin .8s linear infinite; }

.il-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 9px; }
.il-card { border: 1px solid var(--gray-100); border-radius: 8px; overflow: hidden; background: var(--white); transition: all .25s ease; display: flex; flex-direction: column; }
.il-card:hover { border-color: rgba(20, 184, 166, 0.25); box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08); transform: translateY(-2px); }
.il-thumb { position: relative; width: 100%; aspect-ratio: 4 / 3; background: var(--gray-100); overflow: hidden; }
.il-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .3s ease; }
.il-card:hover .il-thumb img { transform: scale(1.05); }
.il-copy-link {
  position: absolute; bottom: 5px; right: 5px; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px;
  border-radius: 6px; border: none; background: rgba(15, 23, 42, 0.7); color: var(--white); font-size: 11px; font-weight: 600;
  cursor: pointer; backdrop-filter: blur(8px); transition: all .2s; opacity: 0;
}
.il-card:hover .il-copy-link { opacity: 1; }
.il-copy-link:hover { background: rgba(13, 148, 136, 0.9); }
.il-copy-link.copied { background: rgba(34, 197, 94, 0.9); opacity: 1; }
.il-card-body { padding: 8px 10px; flex: 1; display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.il-title { font-size: 12px; font-weight: 600; color: var(--dark-800); line-height: 1.35; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.il-source { font-size: 10.5px; color: #0d9488; line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; transition: color .2s; }
.il-source:hover { color: #0f766e; text-decoration: underline; }

.il-fade-enter-active, .il-fade-leave-active { transition: opacity .25s; }
.il-fade-enter-from, .il-fade-leave-to { opacity: 0; }

@media (max-width: 560px) {
  .il-options { flex-direction: column; align-items: stretch; }
  .il-btn-primary { width: 100%; }
  .il-grid { grid-template-columns: 1fr; }
}
</style>
