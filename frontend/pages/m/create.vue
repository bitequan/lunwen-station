<template>
  <div class="m-create">
    <!-- 步骤条 -->
    <div class="m-steps">
      <div v-for="(s, i) in stepDefs" :key="s" class="m-step" :class="{ active: step === i + 1, done: step > i + 1 }">
        <span class="m-step-dot">{{ step > i + 1 ? '✓' : i + 1 }}</span>
        <span class="m-step-label">{{ s }}</span>
        <span v-if="i < stepDefs.length - 1" class="m-step-line"></span>
      </div>
    </div>

    <!-- ============ 步骤 1：标题与参数 ============ -->
    <template v-if="step === 1">
      <section class="m-card">
        <div class="m-field">
          <div class="m-field-label">
            论文标题
            <button class="m-ai-title-btn" @click="openSuggest">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
              智能选题
            </button>
          </div>
          <textarea
            v-model="form.title"
            class="m-textarea"
            style="min-height:64px"
            placeholder="输入论文标题，或点击「智能选题」让 AI 帮你想"
          ></textarea>
        </div>

        <div class="m-field">
          <div class="m-field-label">学历层次</div>
          <div class="m-chips">
            <button v-for="d in degrees" :key="d" class="m-chip" :class="{ active: form.degree === d }" @click="form.degree = d">{{ d }}</button>
          </div>
        </div>

        <div class="m-field">
          <div class="m-field-label">专业方向</div>
          <select v-model="form.major" class="m-select">
            <option value="">不限</option>
            <option v-for="m in majors" :key="m" :value="m">{{ m }}</option>
          </select>
        </div>

        <div class="m-field">
          <div class="m-field-label">论文字数</div>
          <div class="m-chips">
            <button v-for="w in wordOptions" :key="w" class="m-chip" :class="{ active: !form.customWords && form.words === w }" @click="pickWord(w)">{{ w }}</button>
          </div>
          <input v-model="form.customWords" type="number" inputmode="numeric" class="m-input" style="margin-top:10px" placeholder="自定义字数" />
        </div>

        <div class="m-field">
          <div class="m-field-label">生成模型</div>
          <div class="m-chips">
            <button
              v-for="m in aiModels"
              :key="m.value"
              class="m-chip"
              :class="{ active: form.model === m.value }"
              @click="form.model = m.value"
            >{{ m.label }}<i v-if="m.tag" class="m-chip-tag">{{ m.tag }}</i></button>
          </div>
        </div>

        <div class="m-field">
          <div class="m-field-label">大纲层级</div>
          <div class="m-chips">
            <button v-for="o in outlineOptions" :key="o.value" class="m-chip" :class="{ active: form.outlineLevel === o.value }" @click="form.outlineLevel = o.value">{{ o.label }}</button>
          </div>
        </div>

        <div class="m-field">
          <div class="m-field-label">生成语言</div>
          <div class="m-chips">
            <button v-for="l in languages" :key="l" class="m-chip" :class="{ active: form.language === l }" @click="form.language = l">{{ l }}</button>
          </div>
        </div>

        <div class="m-field">
          <div class="m-field-label">参考文献数量</div>
          <input v-model="form.literatureCount" type="number" inputmode="numeric" class="m-input" placeholder="默认 25 篇" />
        </div>

        <div class="m-field">
          <div class="m-field-label">外文文献条数</div>
          <input v-model="form.enLiteratureCount" type="number" inputmode="numeric" class="m-input" :max="form.literatureCount" placeholder="0" />
          <div class="m-field-hint">包含在文献条数中，不能大于文献条数（{{ form.literatureCount }} 条）</div>
        </div>

        <div class="m-field m-field--row">
          <div>
            <div class="m-field-label" style="margin-bottom:2px">自定义大纲</div>
            <div class="m-field-hint">{{ form.useCustomOutline ? '已启用，将使用自定义大纲生成' : '启用后可手动输入大纲结构' }}</div>
          </div>
          <button class="m-switch" :class="{ on: form.useCustomOutline }" @click="form.useCustomOutline = !form.useCustomOutline" aria-label="自定义大纲"></button>
        </div>

        <div v-if="form.useCustomOutline" class="m-field">
          <div class="m-co-head">
            <span class="m-co-title">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              自定义大纲
            </span>
            <button class="m-co-load" @click="loadOutlineSample">载入示例</button>
          </div>
          <textarea v-model="form.customOutline" class="m-textarea" style="min-height:140px" placeholder="支持粘贴或手动输入大纲，例如：&#10;一、绪论&#10;1.1 研究背景&#10;1.2 研究意义&#10;二、相关理论&#10;..."></textarea>
          <div class="m-co-tip">
            <span>按"一、""1.1"等层级格式输入，AI 将基于此大纲生成全文</span>
            <span>{{ (form.customOutline || '').length }} 字</span>
          </div>
        </div>

        <div class="m-field m-field--row">
          <div>
            <div class="m-field-label" style="margin-bottom:2px">降低 AI 率</div>
            <div class="m-field-hint">开启后加收基础价 30%</div>
          </div>
          <button class="m-switch" :class="{ on: form.needLowerAI }" @click="form.needLowerAI = !form.needLowerAI" aria-label="降低AI率"></button>
        </div>
      </section>

      <button class="m-btn m-btn-primary m-btn-block" style="margin-top:14px" @click="goStep2">下一步 · 选择模板</button>
    </template>

    <!-- ============ 步骤 2：选择模板 ============ -->
    <template v-else-if="step === 2">
      <section class="m-card" style="padding:12px 14px">
        <div class="m-tpl-tabs">
          <button class="m-tpl-tab" :class="{ active: tplTab === 'public' }" @click="switchTplTab('public')">公共模板</button>
          <button class="m-tpl-tab" :class="{ active: tplTab === 'private' }" @click="switchTplTab('private')">私有模板</button>
        </div>
        <div class="m-search">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="templateKeyword" type="text" placeholder="搜索学校 / 专业模板" @input="onTplSearch" />
        </div>

        <div v-if="templateLoading" class="m-loading" style="padding:30px 0">
          <div class="m-spinner"></div>
        </div>
        <div v-else class="m-tpl-list">
          <!-- 通用格式固定在首位 -->
          <div class="m-tpl-item" :class="{ active: form.templateId === 0 }" @click="pickTemplate({ id: 0, name: '通用格式', avt: '' })">
            <span class="m-tpl-logo m-tpl-logo--generic">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <span class="m-tpl-info">
              <span class="m-tpl-name">通用格式</span>
              <span class="m-tpl-meta">标准学术论文格式</span>
            </span>
            <span v-if="form.templateId === 0" class="m-tpl-check">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
          </div>
          <div
            v-for="t in templateResults"
            :key="t.id"
            class="m-tpl-item"
            :class="{ active: form.templateId === t.id }"
            @click="pickTemplate(t)"
          >
            <span class="m-tpl-logo">
              <img v-if="t.avt" :src="t.avt" alt="" @error="onLogoError" />
              <b v-else>{{ (t.name || '模')[0] }}</b>
            </span>
            <span class="m-tpl-info">
              <span class="m-tpl-name">{{ t.name }}</span>
              <span class="m-tpl-meta">{{ t.profession }}{{ t.years ? ' · ' + t.years : '' }}</span>
            </span>
            <span v-if="form.templateId === t.id" class="m-tpl-check">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
          </div>
          <div v-if="!templateResults.length" class="m-tpl-empty">未找到相关模板</div>
        </div>
      </section>

      <div class="m-footer-bar" style="border-radius:0">
        <button class="m-btn m-btn-plain" @click="step = 1">上一步</button>
        <button class="m-btn m-btn-primary" @click="goStep3">下一步 · 生成大纲</button>
      </div>
      <div style="height:70px"></div>
    </template>

    <!-- ============ 步骤 3：大纲编辑与支付（同 PC 端 OutlineEditor：章节概要/图表选择/拖拽排序/完整支付闭环） ============ -->
    <div v-show="step === 3">
      <OutlineEditor
        :form-data="form"
        :active="step === 3"
        @back="step = 2"
        @paid="onOutlinePaid"
        @reset="step = 1"
      />
      <div style="height:70px"></div>
    </div>

    <!-- 智能选题弹层 -->
    <Transition name="m-fade">
      <div v-if="showSuggest" class="m-sheet-mask" @click.self="showSuggest = false">
        <div class="m-sheet">
          <div class="m-sheet-head">
            <span class="m-sheet-title">AI 智能选题</span>
            <button class="m-sheet-close" @click="showSuggest = false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="m-field">
            <div class="m-field-label">研究方向 / 关键词</div>
            <input v-model="suggestForm.interest" class="m-input" placeholder="如：短视频营销、乡村振兴..." />
          </div>
          <div class="m-field">
            <div class="m-field-label">题目类型</div>
            <div class="m-chips">
              <button v-for="t in suggestTypes" :key="t" class="m-chip" :class="{ active: suggestForm.type === t }" @click="suggestForm.type = t">{{ t }}</button>
            </div>
          </div>
          <button class="m-btn m-btn-primary m-btn-block" :disabled="suggestLoading" @click="loadSuggest">{{ suggestLoading ? '生成中...' : '生成题目' }}</button>
          <div v-if="suggestedTitles.length" class="m-suggest-list">
            <button v-for="(t, i) in suggestedTitles" :key="i" class="m-suggest-item" @click="applyTitle(t)">
              <span class="m-suggest-title">{{ t.title }}</span>
              <span v-if="t.desc" class="m-suggest-desc">{{ t.desc }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: 'AI论文创作' })

const api = useApi()
const auth = useAuth()
const toast = useToast()
const login = useLoginModal()

const stepDefs = ['填写信息', '选择模板', '生成大纲']
const step = ref(1)

const degrees = ['专科', '本科', '研究生', '博士', 'MBA']
const majors = ['哲学', '经济学', '法学', '教育学', '文学', '历史学', '理学', '工学', '农学', '医学', '管理学', '艺术学', '计算机科学与技术', '工商管理', '会计学', '金融学']
const wordOptions = ['3000', '5000', '8000', '1万', '2万', '3万', '5万', '10万']
const outlineOptions = [
  { value: 'two', label: '二级大纲' },
  { value: 'three', label: '三级大纲' },
]
const languages = ['中文', '英文', '日语', '韩语', '俄语', '泰语']
const suggestTypes = ['实证研究', '理论研究', '案例研究', '对比研究', '综述性研究']

const form = reactive({
  title: '',
  subject: '',
  degree: '本科',
  major: '',
  words: '1万',
  customWords: '',
  model: 'standard',
  outlineLevel: 'two',
  language: '中文',
  needChart: true,
  literatureCount: 25,
  enLiteratureCount: 0,
  needLowerAI: false,
  useCustomOutline: false,
  customOutline: '',
  templateId: 0,
  templateName: '',
  templateSource: '',
  templateLogo: '',
  is_advanced: 0,
})

/* ---------- 智能选题 ---------- */
const showSuggest = ref(false)
const suggestForm = reactive({ interest: '', type: '实证研究' })
const suggestedTitles = ref([])
const suggestLoading = ref(false)

function openSuggest() {
  showSuggest.value = true
}

async function loadSuggest() {
  if (!suggestForm.interest.trim()) { toast.warning('请输入研究方向'); return }
  if (suggestLoading.value) return
  suggestLoading.value = true
  try {
    const res = await api.post('/api/tools/createtitle', {
      researchText: suggestForm.interest.trim(),
      titleType: suggestForm.type,
    })
    if (res.ok && Array.isArray(res.data?.titles) && res.data.titles.length) {
      suggestedTitles.value = res.data.titles.map(t => ({
        title: String(t.title || ''),
        desc: String(t.abstract || t.desc || ''),
      }))
    } else {
      toast.error(res.msg || '题目生成失败，请稍后重试')
    }
  } catch (e) {} finally {
    suggestLoading.value = false
  }
}

function applyTitle(t) {
  form.title = t.title
  showSuggest.value = false
}

/* ---------- 字数与模型 ---------- */
function pickWord(w) {
  form.words = w
  form.customWords = ''
}

const aiModelsRaw = ref([])
const aiModels = computed(() => aiModelsRaw.value.map(m => ({
  value: m.code,
  label: m.name,
  tag: m.tag || '',
})))

async function loadModels() {
  try {
    const res = await fetch('/api/ai/models?type=paper')
    const json = await res.json()
    if (json.code === 1 && Array.isArray(json.data)) {
      aiModelsRaw.value = json.data
      const codes = json.data.map(m => m.code)
      if (codes.length && !codes.includes(form.model)) form.model = codes[0]
    }
  } catch (e) {}
}

// 当前所选模型是否为高级版(is_advanced=1)，随模型选择联动（同 PC 端；OutlineEditor 据此显隐"文献原图"等高级选项）
const isAdvancedModel = computed(() => {
  const m = aiModelsRaw.value.find(x => x.code === form.model)
  return Number(m?.is_advanced) === 1
})
watch(isAdvancedModel, (v) => { form.is_advanced = v ? 1 : 0 }, { immediate: true })

/* ---------- 步骤流转 ---------- */
function goStep2() {
  if (!form.title.trim()) { toast.warning('请先填写论文标题'); return }
  step.value = 2
  if (!templateResults.value.length && !templateLoading.value) loadTemplates()
}

function goStep3() {
  if (!auth.isLoggedIn.value) {
    toast.warning('请先登录后再生成大纲')
    login.open()
    return
  }
  if (form.useCustomOutline && !form.customOutline.trim()) {
    toast.warning('请输入自定义大纲内容，或关闭自定义大纲开关')
    return
  }
  step.value = 3
}

/* ---------- token（与 useApi 同源存储，模板接口需要） ---------- */
function getToken() {
  if (!process.client) return ''
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
    return JSON.parse(raw)?.token || ''
  } catch (e) {
    return ''
  }
}

function tokenHeaders(extra = {}) {
  const h = { ...extra }
  const t = getToken()
  if (t) h.token = t
  return h
}

/* ---------- 模板 ---------- */
const tplTab = ref('public')
const templateKeyword = ref('')
const templateResults = ref([])
const templateLoading = ref(false)
let templateTimer = null

const config = useRuntimeConfig()
function normalizeLogoUrl(avt) {
  if (!avt) return ''
  const v = String(avt).trim()
  if (!v) return ''
  if (/^https?:\/\//i.test(v)) return v
  const gateway = config.public.downloadGateway
  if (!gateway) return v
  return gateway + '?path=' + encodeURIComponent(v)
}

function switchTplTab(tab) {
  if (tab !== 'public' && !auth.isLoggedIn.value) {
    toast.warning('请先登录后再查看私有模板')
    login.open()
    return
  }
  tplTab.value = tab
  templateKeyword.value = ''
  templateResults.value = []
  loadTemplates()
}

async function loadTemplates() {
  templateLoading.value = true
  try {
    const kw = templateKeyword.value.trim()
    const url = tplTab.value === 'public'
      ? '/api/pc/searchTemplates?' + new URLSearchParams({ keyword: kw })
      : '/api/pc/userTemplates?' + new URLSearchParams({ keyword: kw })
    const res = await fetch(url, { headers: tokenHeaders() })
    const json = await res.json()
    if (json.code === 1) {
      const d = json?.data || {}
      templateResults.value = (d.list || []).map(t => ({ ...t, avt: normalizeLogoUrl(t.avt) }))
    } else {
      templateResults.value = []
    }
  } catch (e) {
    templateResults.value = []
  } finally {
    templateLoading.value = false
  }
}

function onTplSearch() {
  clearTimeout(templateTimer)
  templateTimer = setTimeout(loadTemplates, 350)
}

function pickTemplate(t) {
  form.templateId = t.id
  form.templateSource = tplTab.value === 'private' ? 'private' : 'public'
  form.templateName = t.id === 0 ? '通用格式' : `${t.name} · ${t.profession} · ${t.years}`
  form.templateLogo = t.avt || ''
}

function onLogoError(e) {
  e.target.style.display = 'none'
}

/* ---------- OutlineEditor 事件（同 PC 端） ---------- */
function onOutlinePaid(result) {
  // 支付成功后刷新用户余额展示
  if (result?.left_money != null && auth.user.value) {
    auth.user.value.user_money = result.left_money
  }
}

// 载入大纲示例（与 PC 端一致）
function loadOutlineSample() {
  form.customOutline = `一、绪论
1.1 研究背景与意义
1.2 国内外研究现状
1.3 研究内容与方法
二、相关理论基础
2.1 理论概述
2.2 理论应用分析
三、研究设计
3.1 研究假设
3.2 样本选择与数据来源
3.3 研究方法
四、实证分析
4.1 描述性统计
4.2 相关性分析
4.3 回归分析
五、结论与建议
5.1 研究结论
5.2 政策建议
5.3 研究不足与展望`
}

onMounted(() => {
  auth.restore()
  loadModels()
})

onUnmounted(() => {
  clearTimeout(templateTimer)
})
</script>

<style scoped>
.m-field--row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.m-chip-tag {
  font-style: normal;
  font-size: 10px;
  margin-left: 4px;
  padding: 1px 5px;
  border-radius: 5px;
  background: rgba(249, 115, 22, 0.12);
  color: #c2410c;
}

/* 智能选题按钮 */
.m-ai-title-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: none;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600, #0d9488);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: 999px;
}

/* 模板选择 */
.m-tpl-tabs {
  display: flex;
  gap: 4px;
  padding: 3px;
  background: #f1f5f9;
  border-radius: 10px;
  margin-bottom: 10px;
}
.m-tpl-tab {
  flex: 1;
  height: 34px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}
.m-tpl-tab.active {
  background: #fff;
  color: #0f172a;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.m-search {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #f8fafc;
  color: #94a3b8;
  margin-bottom: 10px;
}
.m-search input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: #0f172a;
}
.m-tpl-list {
  max-height: 56vh;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}
.m-tpl-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  background: #f8fafc;
  cursor: pointer;
}
.m-tpl-item.active {
  border-color: var(--primary-500, #14b8a6);
  background: rgba(20, 184, 166, 0.06);
}
.m-tpl-logo {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e2e8f0;
  color: #64748b;
  font-size: 15px;
  font-weight: 700;
  flex-shrink: 0;
}
.m-tpl-logo img { width: 100%; height: 100%; object-fit: cover; }
.m-tpl-logo--generic { background: rgba(20, 184, 166, 0.1); color: var(--primary-600, #0d9488); }
.m-tpl-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; width: 100%; }
.m-tpl-name {
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.m-tpl-meta {
  font-size: 11.5px;
  color: #94a3b8;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.m-tpl-check {
  position: absolute;
  top: 8px;
  right: 8px;
  color: var(--primary-500, #14b8a6);
  flex-shrink: 0;
  display: flex;
}
.m-tpl-empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 20px 0;
  font-size: 13px;
  color: #94a3b8;
}

/* 自定义大纲（与 PC 快捷模式同款交互） */
.m-co-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.m-co-title {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
}
.m-co-load {
  border: none;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600, #0d9488);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: 999px;
}
.m-co-tip {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 6px;
}

/* 智能选题结果 */
.m-suggest-list {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.m-suggest-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  text-align: left;
  padding: 11px 13px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  background: #f8fafc;
}
.m-suggest-title { font-size: 13.5px; font-weight: 600; color: #0f172a; }
.m-suggest-desc {
  font-size: 12px;
  color: #94a3b8;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
