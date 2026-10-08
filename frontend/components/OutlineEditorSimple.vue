<template>
  <div class="outline-editor-simple">
    <div class="card-head">
      <div class="head-title">
        <span class="head-icon">
          <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
        </span>
        <h2>生成的大纲</h2>
      </div>
      <div class="head-actions">
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
      <div v-for="(chapter, cIdx) in outlines" :key="cIdx" class="chapter">
        <div class="chapter-title">
          <span class="sort-grip" v-if="sortMode" title="拖动排序">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
          </span>
          <h3 class="editable" :data-number="toChineseNum(cIdx + 1)" :contenteditable="!sortMode"
            @input="e => chapter.chapter = e.target.innerText" @keydown.enter.prevent>{{ chapter.chapter }}</h3>
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

        <!-- 章节概要：接口有概要内容时显示（可编辑） -->
        <div v-if="chapterAbstract(chapter)" class="editable abstract chapter-abstract"
          :contenteditable="!sortMode" data-placeholder="添加章节概要..."
          @input="e => chapter.abstract = e.target.innerText">{{ chapterAbstract(chapter) }}</div>

        <div class="sections" :data-c-idx="cIdx">
          <div v-for="(section, sIdx) in chapter.sections" :key="sIdx" class="section" :class="{ 'has-sub': section.subsections && section.subsections.length }">
            <div class="section-main">
              <span class="sort-grip" v-if="sortMode" title="拖动排序">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
              </span>
              <span class="editable" :data-number="`${cIdx + 1}.${sIdx + 1}`" :contenteditable="!sortMode"
                @input="e => section.name = e.target.innerText" @keydown.enter.prevent>{{ section.name }}</span>
              <div class="ops-group" v-if="!sortMode">
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
            </div>

            <!-- 小节概要：有概要内容时显示（可编辑） -->
            <div v-if="sectionAbstract(section)" class="editable abstract section-abstract"
              :contenteditable="!sortMode" data-placeholder="添加小节说明..."
              @input="e => section.abstract = e.target.innerText">{{ sectionAbstract(section) }}</div>

            <div class="subsections" :data-c-idx="cIdx" :data-s-idx="sIdx" v-if="section.subsections && section.subsections.length || sortMode">
              <div v-for="(sub, ssIdx) in (section.subsections || [])" :key="ssIdx" class="subsection">
                <div class="subsection-main">
                  <span class="sort-grip" v-if="sortMode" title="拖动排序">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                  </span>
                  <span class="editable" :data-number="`${cIdx + 1}.${sIdx + 1}.${ssIdx + 1}`" :contenteditable="!sortMode"
                    @input="e => sub.name = e.target.innerText" @keydown.enter.prevent>{{ sub.name }}</span>
                  <div class="ops-group" v-if="!sortMode">
                    <button class="op-btn op-add" @click="addSiblingSubSection(cIdx, sIdx, ssIdx)" title="添加同级">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <button class="op-btn op-danger" @click="removeSubSection(cIdx, sIdx, ssIdx)" title="删除三级小节">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </div>
                </div>
                <!-- 三级小节概要：有概要内容时显示（可编辑） -->
                <div v-if="subAbstract(sub)" class="editable abstract sub-abstract"
                  :contenteditable="!sortMode" data-placeholder="添加说明..."
                  @input="e => sub.abstract = e.target.innerText">{{ subAbstract(sub) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sortable from 'sortablejs'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'regenerate'])

const outlines = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// 概要内容提取：兼容 abstract / summary / desc 字段，避免显示空占位
function pickAbstract(node) {
  if (!node) return ''
  const v = node.abstract ?? node.summary ?? node.desc ?? ''
  return typeof v === 'string' ? v : ''
}
function chapterAbstract(chapter) {
  return pickAbstract(chapter)
}
function sectionAbstract(section) {
  return pickAbstract(section)
}
function subAbstract(sub) {
  return pickAbstract(sub)
}

const chineseNums = ['一','二','三','四','五','六','七','八','九','十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十']
function toChineseNum(n) {
  return chineseNums[n - 1] || String(n)
}

// ============ 拖拽排序 ============
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

function initSortable() {
  const root = '.outline-editor-simple .outline-tree'
  // chapter 层级
  const treeEl = document.querySelector(root)
  if (treeEl) {
    sortableInstances.push(Sortable.create(treeEl, {
      draggable: '.chapter',
      animation: 200,
      forceFallback: true,
      fallbackClass: 's-drag',
      fallbackOnBody: true,
      ghostClass: 's-ghost',
      chosenClass: 's-chosen',
      onEnd: (evt) => {
        if (evt.from === evt.to) {
          revertDom(evt)
          moveChapterTo(evt.oldIndex, evt.newIndex)
        }
      }
    }))
  }
  // section 层级（同级排序）
  document.querySelectorAll(`${root} .sections`).forEach(el => {
    sortableInstances.push(Sortable.create(el, {
      draggable: '.section',
      animation: 200,
      forceFallback: true,
      fallbackClass: 's-drag',
      fallbackOnBody: true,
      ghostClass: 's-ghost',
      chosenClass: 's-chosen',
      onEnd: (evt) => {
        if (evt.from !== evt.to) return // 仅同级排序
        revertDom(evt)
        const cIdx = Number(el.dataset.cIdx)
        moveSectionTo(cIdx, evt.oldIndex, evt.newIndex)
      }
    }))
  })
  // subsection 层级（同级排序）
  document.querySelectorAll(`${root} .subsections`).forEach(el => {
    sortableInstances.push(Sortable.create(el, {
      draggable: '.subsection',
      animation: 200,
      forceFallback: true,
      fallbackClass: 's-drag',
      fallbackOnBody: true,
      ghostClass: 's-ghost',
      chosenClass: 's-chosen',
      onEnd: (evt) => {
        if (evt.from !== evt.to) return // 仅同级排序
        revertDom(evt)
        const cIdx = Number(el.dataset.cIdx)
        const sIdx = Number(el.dataset.sIdx)
        moveSubSectionTo(cIdx, sIdx, evt.oldIndex, evt.newIndex)
      }
    }))
  })
}

function destroySortable() {
  sortableInstances.forEach(s => s.destroy())
  sortableInstances.length = 0
}

function revertDom(evt) {
  const { oldIndex, item, from, to } = evt
  if (oldIndex === evt.newIndex && from === to) return
  if (item.parentNode) item.parentNode.removeChild(item)
  if (oldIndex >= from.children.length) from.appendChild(item)
  else from.insertBefore(item, from.children[oldIndex])
}

// ============ 同级排序 ============
function moveChapterTo(oldIdx, newIdx) {
  const arr = outlines.value
  const to = newIdx === -1 ? 0 : newIdx
  if (to < 0 || to >= arr.length) return
  const moved = arr[oldIdx]
  arr.splice(oldIdx, 1)
  arr.splice(to, 0, moved)
  emit('update:modelValue', arr.slice())
}
function moveSectionTo(cIdx, oldIdx, newIdx) {
  const arr = outlines.value
  const sections = arr[cIdx]?.sections || []
  const to = newIdx === -1 ? 0 : newIdx
  if (to < 0 || to >= sections.length) return
  const moved = sections[oldIdx]
  sections.splice(oldIdx, 1)
  sections.splice(to, 0, moved)
  emit('update:modelValue', arr.slice())
}
function moveSubSectionTo(cIdx, sIdx, oldIdx, newIdx) {
  const arr = outlines.value
  const subs = arr[cIdx]?.sections?.[sIdx]?.subsections || []
  const to = newIdx === -1 ? 0 : newIdx
  if (to < 0 || to >= subs.length) return
  const moved = subs[oldIdx]
  subs.splice(oldIdx, 1)
  subs.splice(to, 0, moved)
  emit('update:modelValue', arr.slice())
}

// ============ 章节/小节增删 ============
function addSiblingChapter(idx) {
  outlines.value.splice(idx + 1, 0, { chapter: '新章节', sections: [] })
  emit('update:modelValue', outlines.value.slice())
}
function addSection(cIdx) {
  const ch = outlines.value[cIdx]
  if (!ch.sections) ch.sections = []
  ch.sections.push({ name: '新小节', abstract: '', subsections: [] })
  emit('update:modelValue', outlines.value.slice())
}
function addSiblingSection(cIdx, sIdx) {
  outlines.value[cIdx].sections.splice(sIdx + 1, 0, { name: '新小节', abstract: '', subsections: [] })
  emit('update:modelValue', outlines.value.slice())
}
function addSubSection(cIdx, sIdx) {
  const sec = outlines.value[cIdx].sections[sIdx]
  if (!sec.subsections) sec.subsections = []
  sec.subsections.push({ name: '新三级小节' })
  emit('update:modelValue', outlines.value.slice())
}
function addSiblingSubSection(cIdx, sIdx, ssIdx) {
  const sec = outlines.value[cIdx].sections[sIdx]
  if (!sec.subsections) sec.subsections = []
  sec.subsections.splice(ssIdx + 1, 0, { name: '新三级小节' })
  emit('update:modelValue', outlines.value.slice())
}
function removeChapter(idx) {
  if (outlines.value.length <= 1) return
  outlines.value.splice(idx, 1)
  emit('update:modelValue', outlines.value.slice())
}
function removeSection(cIdx, sIdx) {
  if (outlines.value[cIdx].sections.length <= 1) return
  outlines.value[cIdx].sections.splice(sIdx, 1)
  emit('update:modelValue', outlines.value.slice())
}
function removeSubSection(cIdx, sIdx, ssIdx) {
  const sec = outlines.value[cIdx].sections[sIdx]
  if (!sec.subsections || sec.subsections.length <= 1) return
  sec.subsections.splice(ssIdx, 1)
  emit('update:modelValue', outlines.value.slice())
}

function regenerate() {
  emit('regenerate')
}

onBeforeUnmount(() => destroySortable())
</script>

<style scoped>
.outline-editor-simple {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.05);
  padding: 28px 30px;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}
.head-title { display: flex; align-items: center; gap: 10px; }
.head-icon {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%);
  color: #fff; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
}
.head-title h2 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; }

.head-actions { display: flex; gap: 10px; }
.action-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px; border-radius: 10px;
  border: 1px solid var(--gray-200, #e2e8f0);
  background: #fff; color: var(--gray-600, #475569);
  font-size: 13px; font-weight: 600; cursor: pointer;
  transition: all 0.2s;
}
.action-btn:hover { border-color: #14b8a6; color: #0d9488; background: #f0fdfa; }
.action-btn.primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-color: transparent; color: #fff;
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.action-btn.primary:hover { background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%); color: #fff; }

/* ============ 大纲树 ============ */
.outline-tree { display: flex; flex-direction: column; gap: 14px; }
.chapter {
  border-radius: 14px; background: linear-gradient(180deg, #fafbfd 0%, #fff 60%);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.chapter:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
.chapter-title {
  display: flex; align-items: center; gap: 8px; padding: 16px 20px 8px;
}
.chapter > .editable.abstract { padding: 0 20px 12px; margin-top: 0; }

.editable {
  border: 1px solid transparent; border-radius: 6px; padding: 2px 8px;
  font-family: inherit; transition: all 0.15s ease; outline: none;
  word-break: break-word; white-space: pre-wrap;
}
.editable:hover { background: rgba(15, 23, 42, 0.04); }
.editable:focus {
  border-color: var(--primary-300, #5eead4); background: #fff;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}
.editable:empty::before { content: attr(data-placeholder); color: #cbd5e1; pointer-events: none; }
.editable[data-number]::before { content: attr(data-number) "  "; color: var(--gray-500, #64748b); font-weight: 700; }

.chapter-title .editable {
  flex: 1; font-size: 15px; font-weight: 700; color: var(--dark-800, #1e293b); margin: 0;
}
.chapter-title .editable[data-number]::before { color: #0d9488; }

.sections {
  padding: 4px 20px 16px 24px; display: flex; flex-direction: column; gap: 6px;
}
.section {
  display: flex; flex-wrap: wrap; align-items: flex-start; gap: 8px;
  padding: 10px 14px; background: rgba(248, 250, 252, 0.55); border-radius: 10px;
  font-size: 13px; color: var(--dark-700, #334155); transition: background 0.15s ease;
}
.section.has-sub { background: transparent; padding: 10px 0 0; }
.section:hover { background: rgba(20, 184, 166, 0.05); }
.section.has-sub:hover { background: transparent; }

.section-main { display: flex; align-items: center; gap: 10px; width: 100%; }
.section-main .editable { flex: 1; font-size: 14px; font-weight: 600; color: var(--dark-700, #334155); }
.section-main .editable[data-number]::before { color: #0d9488; }

.editable.abstract {
  width: 100%; margin-top: 6px; font-size: 13px; line-height: 1.7;
  color: var(--gray-500, #64748b); font-weight: 400;
}
.editable.abstract[data-number]::before { display: none; }

.subsections {
  width: 100%; padding-left: 28px; margin-top: 6px; display: flex; flex-direction: column; gap: 6px;
}
.outline-tree.sort-mode .subsections:empty {
  display: block; min-height: 10px; padding: 0 0 0 28px; margin-top: 4px;
  border: 1px dashed rgba(20, 184, 166, 0.25); border-radius: 6px; background: rgba(20, 184, 166, 0.03);
}
.subsection { padding: 9px 14px; background: rgba(241, 245, 249, 0.55); border-radius: 8px; }
.subsection:hover { background: rgba(20, 184, 166, 0.06); }
.subsection-main { display: flex; align-items: center; gap: 8px; }
.subsection-main .editable { flex: 1; font-size: 13px; font-weight: 500; color: var(--dark-700, #334155); }
.subsection-main .editable[data-number]::before { color: var(--gray-500, #64748b); }

/* 操作按钮 */
.ops-group {
  display: inline-flex; align-items: center; gap: 3px; flex-shrink: 0; padding: 4px;
  background: rgba(241, 245, 249, 0.65); border-radius: 10px; opacity: 0.55; transition: all 0.2s ease;
}
.chapter:hover > .chapter-title .ops-group,
.section:hover > .section-main .ops-group,
.subsection:hover > .subsection-main .ops-group {
  opacity: 1; background: rgba(241, 245, 249, 0.95);
  box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.05);
}
.op-btn {
  width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
  border: none; background: transparent; color: var(--gray-500, #64748b); border-radius: 6px;
  cursor: pointer; transition: all 0.15s ease; padding: 0;
}
.op-btn svg { width: 15px; height: 15px; }
.op-btn.op-add { color: #14b8a6; }
.op-btn.op-danger { color: #94a3b8; }
.op-btn:hover:not(:disabled) {
  background: #fff; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08); transform: translateY(-1px);
}
.op-btn.op-add:hover:not(:disabled) { color: #0d9488; background: #f0fdfa; }
.op-btn.op-danger:hover:not(:disabled) { color: #ef4444; background: #fef2f2; }

/* 拖拽排序 */
.outline-tree.sort-mode .editable { pointer-events: none; }
.outline-tree.sort-mode .chapter,
.outline-tree.sort-mode .section,
.outline-tree.sort-mode .subsection,
.outline-tree.sort-mode .chapter-title,
.outline-tree.sort-mode .section-main,
.outline-tree.sort-mode .subsection-main,
.outline-tree.sort-mode .sections,
.outline-tree.sort-mode .subsections,
.outline-tree.sort-mode .editable { cursor: move; }
.sort-grip {
  display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
  width: 18px; height: 18px; color: var(--gray-400, #94a3b8); cursor: move; transition: color 0.15s ease;
}
.outline-tree.sort-mode .sort-grip { color: #14b8a6; opacity: 0.85; }
.outline-tree.sort-mode .sort-grip:hover { opacity: 1; }
.outline-tree.sort-mode .section,
.outline-tree.sort-mode .subsection { outline: 1px dashed rgba(20, 184, 166, 0.2); outline-offset: -1px; }
.s-ghost { opacity: 0 !important; background: transparent !important; border: 2px dashed rgba(20, 184, 166, 0.35) !important; border-radius: 10px; box-shadow: none !important; }
.s-chosen { opacity: 0.95; }
.s-drag {
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.22) !important; background: #fff !important;
  border-radius: 14px !important; opacity: 1 !important; cursor: move !important;
}

@media (max-width: 640px) {
  .outline-editor-simple { padding: 18px 16px; }
  .card-head { flex-direction: column; align-items: flex-start; }
  .chapter-title { padding: 12px 14px 8px; }
  .sections { padding-left: 16px; padding-right: 16px; }
}
</style>