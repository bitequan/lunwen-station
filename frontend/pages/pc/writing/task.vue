<template>
  <div class="task-page">
    <div class="container task-container">
      <ToolShell
        name="任务书生成器"
        desc="三步完成任务书：填写任务信息 → 生成大纲 → 选择模板下单"
        theme="blue"
        icon='<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'
      >
        <template #actions>
          <ToolShellPrice
            :loading="priceLoading"
            :final-price="priceInfo.final_price"
            :sell-price="priceInfo.sell_price"
            :text="priceInfo.text"
            theme="blue"
          />
        </template>

        <!-- ============ 步骤条 ============ -->
        <div class="stepper">
          <template v-for="(s, i) in steps" :key="i">
            <div class="step" :class="{ active: step === s.key, done: step > s.key }">
              <div class="step-badge">
                <svg v-if="step > s.key" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="s.icon"></svg>
              </div>
              <div class="step-text">
                <span class="step-name">{{ s.name }}</span>
                <span class="step-desc">{{ s.desc }}</span>
              </div>
            </div>
            <div v-if="i < steps.length - 1" class="step-line" :class="{ done: step > s.key }"></div>
          </template>
        </div>

        <!-- ============ 步骤 1：填写任务信息 ============ -->
        <div v-show="step === 1" class="step-panel">
          <div class="panel-intro">
            <h2 class="panel-intro-title">填写任务信息</h2>
            <p class="panel-intro-desc">以下信息将作为 AI 生成任务书大纲的依据，带 <i class="req-star">*</i> 为必填项</p>
          </div>

          <div class="form-layout">
            <!-- 左侧主表单 -->
            <div class="form-main">
              <!-- 任务信息 -->
              <div class="card">
                <div class="card-head">
                  <span class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  </span>
                  <span class="card-title">任务信息</span>
                  <span class="card-flag required">必填</span>
                </div>
                <el-form
                  ref="formRef"
                  :model="formData"
                  label-position="top"
                  class="dyn-form"
                  @submit.prevent
                >
                  <el-form-item
                    v-for="field in formFields"
                    :key="field.props.field"
                    :label="field.props.title"
                    :required="!!field.props.isRequired"
                  >
                    <!-- 标题/单行输入 -->
                    <template v-if="field.name === 'WidgetTitle' || field.name === 'WidgetInput'">
                      <el-input
                        v-if="field.props.field !== 'wxnum'"
                        v-model="formData[field.props.field]"
                        type="textarea"
                        :rows="4"
                        :placeholder="field.props.placeholder || ''"
                        :maxlength="field.props.maxlength || undefined"
                        :disabled="!!field.props.disabled"
                        resize="none"
                      />
                      <el-input-number
                        v-else
                        v-model="formData[field.props.field]"
                        :min="1"
                        :placeholder="field.props.placeholder || ''"
                        :disabled="!!field.props.disabled"
                        controls-position="right"
                        class="num-input"
                      />
                    </template>
                    <!-- 下拉选择: Chip 样式 -->
                    <template v-else-if="field.name === 'WidgetSelect'">
                      <div class="chip-group">
                        <button
                          v-for="opt in field.props.options"
                          :key="opt"
                          type="button"
                          class="chip"
                          :class="{ active: formData[field.props.field] === opt }"
                          :disabled="!!field.props.disabled"
                          @click="formData[field.props.field] = opt"
                        >{{ opt }}</button>
                      </div>
                    </template>
                    <!-- 多行文本 -->
                    <template v-else-if="field.name === 'WidgetTextarea'">
                      <el-input
                        v-model="formData[field.props.field]"
                        type="textarea"
                        :rows="field.props.rows || 5"
                        :placeholder="field.props.placeholder || ''"
                        :maxlength="field.props.maxlength || undefined"
                        :disabled="!!field.props.disabled"
                        resize="none"
                      />
                    </template>
                  </el-form-item>
                </el-form>
              </div>

              <!-- 辅助补充 -->
              <div class="card" :class="{ 'is-active': enableAssist }">
                <div class="card-head">
                  <span class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/><circle cx="12" cy="12" r="10"/></svg>
                  </span>
                  <span class="card-title">辅助补充</span>
                  <span class="card-flag optional">可选</span>
                  <el-switch v-model="enableAssist" class="card-switch" />
                </div>
                <el-input
                  v-if="enableAssist"
                  v-model="assistContent"
                  type="textarea"
                  :rows="4"
                  placeholder="可输入任何需要补充的说明、关键词、倾向、参考要点..."
                  resize="none"
                  class="soft-textarea"
                />
                <p v-else class="card-hint">开启后可填写额外的个性化说明，AI 将在生成时结合这些内容</p>
              </div>
            </div>

            <!-- 右侧参考资料 -->
            <div class="form-side">
              <div class="card upload-card" :class="{ 'is-active': enableFileUpload }">
                <div class="card-head">
                  <span class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  </span>
                  <span class="card-title">参考资料上传</span>
                  <span class="card-flag optional">可选</span>
                  <el-switch v-model="enableFileUpload" class="card-switch" />
                </div>
                <el-upload
                  v-if="enableFileUpload"
                  :http-request="doUpload"
                  :show-file-list="false"
                  :accept="'.txt,.docx'"
                  multiple
                  :disabled="uploading"
                  class="drop-upload"
                >
                  <div class="drop-zone" :class="{ 'is-disabled': uploading }">
                    <div class="drop-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <p class="drop-title">{{ uploading ? '上传中...' : '点击选择文件' }}</p>
                    <p class="drop-sub">支持 TXT / DOCX · 单个 ≤ 10MB</p>
                  </div>
                </el-upload>

                <div v-if="uploadedFiles.length" class="file-list">
                  <div v-for="f in uploadedFiles" :key="f.url" class="file-item">
                    <span class="file-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <span class="file-name">{{ f.name }}</span>
                    <button class="file-del" @click="removeFile(f)" aria-label="删除文件">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  </div>
                </div>
                <p v-if="!enableFileUpload" class="card-hint">可上传课题相关的参考文档、任务书、已有材料等作为生成依据</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ============ 步骤 2：生成大纲 ============ -->
        <div v-show="step === 2" class="step-panel">
          <div class="panel-intro">
            <h2 class="panel-intro-title">生成大纲</h2>
            <p class="panel-intro-desc">AI 正在根据任务信息生成章节结构，生成后可直接在线编辑调整</p>
          </div>

          <!-- 加载动画 -->
          <div v-if="outlineLoading" class="gen-loading">
            <div class="gen-visual">
              <div class="gen-doc">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
              </div>
              <div class="gen-pen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
              </div>
              <div class="gen-ring"></div>
            </div>
            <p class="gen-text">{{ genStageText }}</p>
            <div class="gen-progress">
              <span class="gen-progress-bar"></span>
            </div>
          </div>

          <!-- 大纲展示 -->
          <div v-else-if="outlines.length" class="outline-wrap">
            <OutlineEditorSimple
              v-model="outlines"
              @regenerate="regenerateOutline"
            />
          </div>

          <!-- 空状态 -->
          <div v-else class="empty-state">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <p>还没有生成大纲，返回上一步填写信息后点击「生成大纲」</p>
            <button class="btn ghost-btn" @click="step = 1">返回上一步</button>
          </div>
        </div>

        <!-- ============ 步骤 3：选择模板下单 ============ -->
        <div v-show="step === 3" class="step-panel">
          <div class="panel-intro">
            <h2 class="panel-intro-title">选择模板下单</h2>
            <p class="panel-intro-desc">选择一份任务书模板，确认后支付并开始生成</p>
          </div>

          <div class="tpl-search">
            <div class="tpl-search-inner">
              <svg class="tpl-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input
                v-model="templateSearch"
                class="tpl-search-input"
                placeholder="输入模板名称搜索"
                @keyup.enter="searchTemplates"
              />
              <button v-if="templateSearch" class="tpl-search-clear" @click="templateSearch = ''; loadTemplates(1)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <div v-if="templateLoading" class="tpl-loading">
            <span class="mini-spinner"></span>
            <span>加载中...</span>
          </div>
          <div v-else-if="templates.length" class="template-grid">
            <div
              v-for="t in templates"
              :key="t.tid"
              class="template-card"
              :class="{ selected: selectedTemplateId === String(t.tid) }"
              @click="selectTemplate(t)"
            >
              <div class="template-thumb">
                <img :src="templateImg(t)" :alt="t.name" loading="lazy" />
                <span v-if="selectedTemplateId === String(t.tid)" class="tpl-check">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
              </div>
              <p class="template-name">{{ t.name }}</p>
            </div>
          </div>
          <div v-else class="empty-state small">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <p>暂无可用模板</p>
          </div>
        </div>

        <!-- ============ 底部操作区 ============ -->
        <div class="step-actions">
          <button v-if="step > 1" class="btn back-btn" @click="prevStep">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            上一步
          </button>
          <div class="actions-right">
            <button
              v-if="step === 1"
              class="btn primary-btn"
              :disabled="!formFields.length || outlineLoading"
              @click="generateOutline"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
              {{ outlineLoading ? '生成中...' : '生成大纲' }}
            </button>
            <button
              v-if="step === 2 && outlines.length"
              class="btn primary-btn"
              @click="step = 3"
            >
              下一步：选择模板
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button
              v-if="step === 3"
              class="btn primary-btn"
              :disabled="!selectedTemplateId || saving"
              @click="confirmTemplate"
            >
              {{ saving ? '提交中...' : '确认下单' }}
            </button>
          </div>
        </div>
      </ToolShell>
    </div>

    <!-- 支付弹窗 -->
    <PaymentModal
      v-model="showPayment"
      :order-id="orderId"
      :price="price"
      redirect="/pc/orders/write"
      @success="onPaySuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, watch } from 'vue'
import { ElMessage } from 'element-plus'
import OutlineEditorSimple from '~/components/OutlineEditorSimple.vue'

definePageMeta({
  layout: 'console',
})

useSeoMeta({
  title: '任务书生成器 - AI写作助手',
  description: '三步完成任务书：填写任务信息，智能生成任务书大纲，选择模板一键生成。',
})

const api = useApi()

const steps = [
  { key: 1, name: '填写信息', desc: '任务与要求', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>' },
  { key: 2, name: '生成大纲', desc: 'AI 智能生成', icon: '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/>' },
  { key: 3, name: '选择模板', desc: '下单生成', icon: '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>' },
]

const formFields = ref([])
const formData = reactive({})
const formRef = ref(null)

const step = ref(1)
const outlineLoading = ref(false)
const outlines = ref([])

// 辅助补充 / 文档上传
const enableAssist = ref(false)
const assistContent = ref('')
const enableFileUpload = ref(false)
const uploadedFiles = ref([])
const uploading = ref(false)

// 生成阶段文案（循环切换）
const genStageText = ref('正在解析任务信息...')
let genStageTimer = null
const genStages = [
  '正在解析任务信息...',
  '正在构建章节结构...',
  '正在撰写章节摘要...',
]
let genStageIdx = 0

// 模板
const templates = ref([])
const templateLoading = ref(false)
const templateSearch = ref('')
const selectedTemplateId = ref('')

// 支付
const showPayment = ref(false)
const orderId = ref('')
const price = ref(0)
const saving = ref(false)

// 价格展示
const priceLoading = ref(true)
const priceInfo = reactive({
  final_price: 0,
  sell_price: 0,
  text: '',
})

// 加载表单配置
async function loadForm() {
  priceLoading.value = true
  const res = await api.get('/api/write/detail', { id: 4 })
  if (!res.ok) {
    ElMessage.error(res.msg || '加载表单配置失败')
    priceLoading.value = false
    return
  }
  if (res.data?.price_info) {
    priceInfo.final_price = Number(res.data.price_info.final_price || 0)
    priceInfo.sell_price = Number(res.data.price_info.sell_price || 0)
    priceInfo.text = res.data.price_info.text || ''
  }
  priceLoading.value = false
  const forms = res.data?.forms || []
  formFields.value = forms
  // 初始化表单数据
  forms.forEach((f) => {
    const field = f.props?.field
    if (!field) return
    if (f.name === 'WidgetSelect') {
      formData[field] = f.props?.defaultValue || ''
    } else if (f.name === 'WidgetCheckbox') {
      formData[field] = Array.isArray(f.props?.defaultValue) ? f.props.defaultValue[0] : f.props?.defaultValue || ''
    } else if (field === 'wxnum') {
      formData[field] = 25
    } else {
      formData[field] = f.props?.defaultValue || ''
    }
  })
}

function startGenStage() {
  genStageIdx = 0
  genStageText.value = genStages[0]
  genStageTimer = setInterval(() => {
    genStageIdx = (genStageIdx + 1) % genStages.length
    genStageText.value = genStages[genStageIdx]
  }, 2200)
}

function stopGenStage() {
  if (genStageTimer) {
    clearInterval(genStageTimer)
    genStageTimer = null
  }
}

// 生成大纲
async function generateOutline() {
  const fields = formFields.value
  if (!fields.length) {
    ElMessage.warning('表单尚未加载完成，请稍后再试')
    return
  }
  const missing = fields.find((f) => f.props?.isRequired && !formData[f.props.field])
  if (missing) {
    ElMessage.warning(`请填写「${missing.props.title}」`)
    return
  }

  outlineLoading.value = true
  startGenStage()
  step.value = 2
  try {
    const requestData = {
      ...JSON.parse(JSON.stringify(formData)),
      documentUrls: uploadedFiles.value.map((f) => f.url).join(','),
      assistContent: enableAssist.value ? assistContent.value : '',
      enableAssist: enableAssist.value,
      enableFileUpload: enableFileUpload.value,
    }
    const res = await api.post('/api/write/rwsoutline', requestData)
    if (!res.ok) {
      ElMessage.error(res.msg || '大纲生成失败，请重试')
      return
    }
    // 任务书接口直接返回大纲数组
    outlines.value = Array.isArray(res.data) ? res.data : res.data?.outline || []
    if (outlines.value.length) {
      step.value = 2
      selectedTemplateId.value = ''
    } else {
      ElMessage.warning('未能生成有效大纲，请补充更多信息后重试')
    }
  } catch (e) {
    ElMessage.error(e.message || '大纲生成失败，请稍后重试')
  } finally {
    outlineLoading.value = false
    stopGenStage()
  }
}

// 重新生成大纲（清空当前大纲后重新请求）
async function regenerateOutline() {
  outlines.value = []
  await generateOutline()
}

// 文件上传（OSS 预签名直传：先取签名 → 前端 PUT 到 OSS）
async function doUpload({ file }) {
  if (file.size > 10 * 1024 * 1024) {
    ElMessage.error('文件大小不能超过 10MB')
    return
  }
  const ext = (file.name || '').split('.').pop().toLowerCase()
  if (!['txt', 'docx'].includes(ext)) {
    ElMessage.error('只支持 TXT 和 DOCX 格式的文件')
    return
  }
  uploading.value = true
  try {
    // 第一步：获取预签名上传 URL
    const sig = await api.post('/api/write/uploadDocument', { ext })
    if (!sig.ok || !sig.data?.put_url) {
      ElMessage.error(sig.msg || '获取上传签名失败')
      return
    }
    // 第二步：前端直传 OSS
    const up = await fetch(sig.data.put_url, {
      method: 'PUT',
      headers: { 'Content-Type': sig.data.content_type },
      body: file,
    })
    if (!up.ok) {
      ElMessage.error('文件上传失败')
      return
    }
    uploadedFiles.value.push({ url: sig.data.download_url, name: file.name })
  } catch (e) {
    ElMessage.error(e.message || '上传失败')
  } finally {
    uploading.value = false
  }
}

function removeFile(f) {
  uploadedFiles.value = uploadedFiles.value.filter((x) => x.url !== f.url)
}

function prevStep() {
  if (step.value === 2) {
    step.value = 1
  } else if (step.value === 3) {
    step.value = 2
  }
}

// 进入步骤3时自动加载模板列表
watch(step, (v) => {
  if (v === 3 && !templates.value.length) {
    loadTemplates(1)
  }
})

// 模板列表
async function loadTemplates(page = 1) {
  templateLoading.value = true
  try {
    const res = await api.get('/api/write/rwstemplist', { page })
    if (!res.ok) {
      ElMessage.error(res.msg || '加载模板列表失败')
      return
    }
    const list = Array.isArray(res.data) ? res.data : []
    if (selectedTemplateId.value && !list.some((t) => String(t.tid) === selectedTemplateId.value)) {
      selectedTemplateId.value = ''
    }
    templates.value = list
  } catch (e) {
    ElMessage.error(e.message || '加载模板列表失败')
  } finally {
    templateLoading.value = false
  }
}

async function searchTemplates() {
  templateLoading.value = true
  try {
    const kw = templateSearch.value.trim()
    const res = await api.get('/api/write/rwstemplist', { page: 1, name: kw })
    if (!res.ok) {
      ElMessage.error(res.msg || '搜索模板失败')
      return
    }
    const list = Array.isArray(res.data) ? res.data : []
    if (selectedTemplateId.value && !list.some((t) => String(t.tid) === selectedTemplateId.value)) {
      selectedTemplateId.value = ''
    }
    templates.value = list
  } catch (e) {
    ElMessage.error(e.message || '搜索模板失败')
  } finally {
    templateLoading.value = false
  }
}

function selectTemplate(t) {
  selectedTemplateId.value = String(t.tid)
}

function templateImg(t) {
  const avt = t?.avt
  if (!avt) return '/pc/assets/images/default-template.png'
  if (/^https?:\/\//i.test(avt)) return avt
  return window.location.origin + avt
}

// 确认模板下单
async function confirmTemplate() {
  if (!selectedTemplateId.value) {
    ElMessage.warning('请先选择一个模板')
    return
  }
  saving.value = true
  try {
    const requestData = {
      ...JSON.parse(JSON.stringify(formData)),
      documentUrls: uploadedFiles.value.map((f) => f.url).join(','),
      assistContent: enableAssist.value ? assistContent.value : '',
      enableAssist: enableAssist.value,
      enableFileUpload: enableFileUpload.value,
      outlines: outlines.value,
      templateId: selectedTemplateId.value,
    }
    const res = await api.post('/api/write/rwssave', requestData)
    if (!res.ok) {
      ElMessage.error(res.msg || '下单失败，请重试')
      return
    }
    orderId.value = res.data?.record_id || ''
    price.value = res.data?.price || 0
    if (orderId.value) {
      step.value = 3
      showPayment.value = true
    } else {
      ElMessage.error('下单失败：未获取到订单号')
    }
  } catch (e) {
    ElMessage.error(e.message || '下单失败，请稍后重试')
  } finally {
    saving.value = false
  }
}

function onPaySuccess() {
  ElMessage.success('任务书已成功生成，可在订单中心查看进度')
  navigateTo('/pc/orders/write')
}

onMounted(() => {
  loadForm()
})

onBeforeUnmount(() => {
  stopGenStage()
})
</script>

<style scoped>
.task-page {
  max-width: 1080px;
  margin: 0 auto;
}

/* ============ 步骤条 ============ */
.stepper {
  display: flex;
  align-items: center;
  padding: 6px 4px 22px;
}
.step {
  display: flex;
  align-items: center;
  gap: 11px;
  flex-shrink: 0;
}
.step-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--gray-100);
  color: var(--gray-400);
  border: none;
  transition: all 0.25s ease;
}
.step-badge svg { width: 16px; height: 16px; }
.step.active .step-badge {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}
.step.done .step-badge {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  border-color: transparent;
  color: #fff;
}
.step-text {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}
.step-name {
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
  line-height: 1.2;
  transition: color 0.25s ease;
}
.step.active .step-name { color: #2563eb; }
.step.done .step-name { color: #475569; }
.step-desc {
  font-size: 11.5px;
  color: #94a3b8;
  line-height: 1.2;
}
.step-line {
  flex: 1;
  height: 2px;
  background: var(--gray-100);
  margin: 0 16px;
  border-radius: 2px;
  min-width: 24px;
  transition: background 0.3s ease;
}
.step-line.done { background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 100%); }

/* ============ 面板 ============ */
.step-panel {
  animation: fadeUp 0.35s ease;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.panel-intro {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 2px 2px 16px;
}
.panel-intro-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 800;
  color: var(--dark-700);
  margin: 0;
  line-height: 1.3;
}
.panel-intro-title::before {
  content: '';
  width: 4px;
  height: 18px;
  border-radius: 2px;
  background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 100%);
  flex-shrink: 0;
}
.panel-intro-desc {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}
.req-star {
  color: #ef4444;
  font-style: normal;
}

/* ============ 步骤1：表单布局 ============ */
.form-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 16px;
  align-items: start;
}
.form-main {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
@media (max-width: 860px) {
  .form-layout { grid-template-columns: 1fr; }
}

/* ============ 卡片 ============ */
.card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  padding: 20px 22px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  transition: border-color 0.2s ease, background 0.2s ease;
}
.card.is-active {
  border-color: #bfdbfe;
  background: #f8fbff;
}
.card-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #2563eb;
  flex-shrink: 0;
}
.card-icon svg { width: 16px; height: 16px; }
.card-title {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1;
  flex: 1;
}
.card-flag {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 999px;
  line-height: 1.5;
  flex-shrink: 0;
}
.card-flag.required { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
.card-flag.optional { color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; }
.card-switch { flex-shrink: 0; margin-left: 2px; }
.card-hint {
  margin: 10px 0 0 0;
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.6;
}

/* ============ 动态表单 ============ */
.dyn-form :deep(.el-form-item) {
  margin-bottom: 15px;
}
.dyn-form :deep(.el-form-item:last-child) {
  margin-bottom: 0;
}
.dyn-form :deep(.el-form-item__label) {
  font-size: 12.5px;
  font-weight: 600;
  color: #334155;
  line-height: 1.4;
  padding-bottom: 6px;
}
.dyn-form :deep(.el-form-item__label::before) {
  color: #3b82f6;
  margin-right: 3px;
}
.dyn-form :deep(.el-input__wrapper),
.dyn-form :deep(.el-textarea__inner) {
  border-radius: 9px;
}
.dyn-form :deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #cbd5e1 inset;
  background: #f8fafc;
  transition: box-shadow 0.2s, background 0.2s;
}
.dyn-form :deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #94a3b8 inset;
}
.dyn-form :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}
.dyn-form :deep(.el-textarea__inner) {
  background: #f8fafc;
  box-shadow: 0 0 0 1px #cbd5e1 inset;
  transition: box-shadow 0.2s, background 0.2s;
}
.dyn-form :deep(.el-textarea__inner:hover) {
  box-shadow: 0 0 0 1px #94a3b8 inset;
}
.dyn-form :deep(.el-textarea__inner:focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}
.num-input {
  width: 100%;
}
.soft-textarea :deep(.el-textarea__inner) {
  border-radius: 9px;
  background: #f8fafc;
  box-shadow: 0 0 0 1px #cbd5e1 inset;
  transition: box-shadow 0.2s, background 0.2s;
}
.soft-textarea :deep(.el-textarea__inner:hover) {
  box-shadow: 0 0 0 1px #94a3b8 inset;
}
.soft-textarea :deep(.el-textarea__inner:focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}

/* ============ Chip 选择器 ============ */
.chip-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 16px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}
.chip:hover:not(:disabled) {
  border-color: #93c5fd;
  color: #2563eb;
  background: #eff6ff;
}
.chip.active {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
}
.chip:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============ 上传 ============ */
.drop-upload :deep(.el-upload) { display: block; }
.drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 26px 16px;
  border: 1.5px dashed #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
}
.drop-zone:hover:not(.is-disabled) {
  border-color: #3b82f6;
  background: #eff6ff;
}
.drop-zone.is-disabled { opacity: 0.6; cursor: not-allowed; }
.drop-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #dbeafe;
  color: #2563eb;
}
.drop-icon svg { width: 22px; height: 22px; }
.drop-title {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin: 0;
  line-height: 1.4;
}
.drop-sub {
  font-size: 11.5px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.4;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin-top: 12px;
}
.file-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 11px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.file-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #eff6ff;
  color: #2563eb;
  flex-shrink: 0;
}
.file-icon svg { width: 13px; height: 13px; }
.file-name {
  flex: 1;
  font-size: 12.5px;
  color: #334155;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.file-del {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  color: #94a3b8;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.file-del:hover { color: #ef4444; background: #fef2f2; }

/* ============ 步骤2：加载动画 ============ */
.gen-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
  padding: 64px 20px;
}
.gen-visual {
  position: relative;
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.gen-doc {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 64px;
  border-radius: 10px;
  background: #eff6ff;
  border: 1.5px solid #bfdbfe;
  color: #2563eb;
  z-index: 2;
  animation: docFloat 2.4s ease-in-out infinite;
}
.gen-doc svg { width: 26px; height: 26px; }
@keyframes docFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
.gen-pen {
  position: absolute;
  top: 2px;
  right: 0;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #fff;
  color: #f97316;
  box-shadow: 0 3px 10px rgba(15, 23, 42, 0.12);
  animation: penSwing 2.4s ease-in-out infinite;
}
.gen-pen svg { width: 17px; height: 17px; }
@keyframes penSwing {
  0%, 100% { transform: rotate(-14deg) translateY(0); }
  50% { transform: rotate(10deg) translateY(-3px); }
}
.gen-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid #dbeafe;
  border-top-color: #3b82f6;
  animation: ringSpin 1.1s linear infinite;
  z-index: 1;
}
@keyframes ringSpin {
  to { transform: rotate(360deg); }
}
.gen-text {
  font-size: 14px;
  font-weight: 500;
  color: #334155;
  margin: 0;
  line-height: 1.4;
  letter-spacing: 0.01em;
}
.gen-progress {
  width: 220px;
  height: 4px;
  border-radius: 99px;
  background: #f1f5f9;
  overflow: hidden;
}
.gen-progress-bar {
  display: block;
  width: 40%;
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, #93c5fd, #3b82f6);
  animation: progressSlide 1.4s ease-in-out infinite;
}
@keyframes progressSlide {
  0%   { transform: translateX(-110%); }
  100% { transform: translateX(280%); }
}

/* ============ 大纲展示 ============ */
.outline-wrap {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.outline-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 2px 2px 0;
}
.outline-count {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: #64748b;
}
.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}
.dot.blue { background: #3b82f6; }

.outline-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.chapter-card {
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  padding: 16px 18px;
  background: #fff;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.05);
}
.chapter-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.chapter-index {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}
.chapter-title {
  flex: 1;
}
.chapter-title :deep(.el-input__wrapper) {
  background: #f8fafc;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #e2e8f0 inset;
  font-weight: 600;
}
.chapter-title :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}

.section-block {
  padding: 8px 0 4px 14px;
  border-left: 2px solid #dbeafe;
  margin-left: 6px;
}
.section-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}
.section-index {
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}
.section-name {
  flex: 1;
}
.section-name :deep(.el-input__wrapper) {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #e2e8f0 inset;
  font-weight: 500;
}
.section-name :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
}
.section-abstract {
  margin: 2px 0 8px 0;
}
.section-abstract :deep(.el-textarea__inner) {
  border-radius: 8px;
  background: #f8fafc;
  box-shadow: 0 0 0 1px #e2e8f0 inset;
  color: #64748b;
  font-size: 12.5px;
}
.section-abstract :deep(.el-textarea__inner:focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}

/* 三级小节 */
.sub-block {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 6px 0 6px 18px;
}
.sub-index {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}
.sub-name {
  flex: 1;
}
.sub-name :deep(.el-input__wrapper) {
  background: #f8fafc;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #e2e8f0 inset;
  font-size: 12.5px;
}
.sub-name :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 0 0 1px #3b82f6 inset;
  background: #fff;
}

/* ============ 空状态 ============ */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 56px 20px;
  border: 1px dashed rgba(226, 232, 240, 0.9);
  border-radius: 16px;
  background: var(--gray-50);
  text-align: center;
}
.empty-state.small { padding: 40px 20px; }
.empty-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
}
.empty-icon svg { width: 26px; height: 26px; }
.empty-state p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
}

/* ============ 步骤3：模板 ============ */
.tpl-search {
  margin-bottom: 16px;
}
.tpl-search-inner {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 420px;
}
.tpl-search-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
}
.tpl-search-input {
  width: 100%;
  height: 42px;
  padding: 0 40px 0 40px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 13.5px;
  color: #0f172a;
  outline: none;
  transition: all 0.2s ease;
}
.tpl-search-input::placeholder { color: #94a3b8; }
.tpl-search-input:focus {
  border-color: #3b82f6;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.tpl-search-clear {
  position: absolute;
  right: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.15s ease;
}
.tpl-search-clear:hover { background: #e2e8f0; color: #0f172a; }

.tpl-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 44px;
  color: #64748b;
  font-size: 13px;
}
.mini-spinner {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  animation: ringSpin 0.8s linear infinite;
}

.template-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
@media (max-width: 820px) {
  .template-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 620px) {
  .template-grid { grid-template-columns: repeat(2, 1fr); }
}
.template-card {
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  transition: all 0.2s ease;
}
.template-card:hover {
  border-color: #3b82f6;
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1);
}
.template-card.selected {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.template-thumb {
  position: relative;
  width: 100%;
  height: 160px;
  background: linear-gradient(135deg, #f1f5f9 0%, #eaf0fb 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.template-thumb img {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
}
.tpl-check {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #3b82f6;
  color: #fff;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
}
.tpl-check svg { width: 13px; height: 13px; }
.template-name {
  margin: 0;
  padding: 11px 13px;
  font-size: 12.5px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============ 底部操作 ============ */
.step-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid #f1f5f9;
}
.actions-right {
  display: flex;
  gap: 10px;
  margin-left: auto;
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 44px;
  padding: 0 26px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  line-height: 1;
  user-select: none;
}
.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.primary-btn {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
}
.primary-btn:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.38);
  transform: translateY(-1px);
}
.primary-btn:active:not(:disabled) {
  transform: translateY(0);
}
.back-btn {
  background: #fff;
  color: #475569;
  border: 1px solid #cbd5e1;
}
.back-btn:hover:not(:disabled) {
  border-color: #94a3b8;
  background: #f8fafc;
  color: #0f172a;
}
.ghost-btn {
  background: #fff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  height: 38px;
  padding: 0 20px;
  font-size: 13px;
}
.ghost-btn:hover {
  background: #eff6ff;
}

/* ============ 响应式 ============ */
@media (max-width: 640px) {
  /* 壳层已有 12px 外边距：全局 .container 的 24px 双侧内边距在手机上叠加出 36px 大留白，归零 */
  .task-container { padding: 0; }
  .stepper { gap: 0; padding: 6px 2px 14px; }
  /* H5 步骤条：恢复步骤名（原 display:none 退化成三个无字图标块），只隐藏描述行 */
  .step { gap: 8px; }
  .step-badge { width: 26px; height: 26px; }
  .step-badge svg { width: 13px; height: 13px; }
  .step.active .step-badge { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12); }
  .step-text { display: flex; }
  .step-name { font-size: 12px; }
  .step-desc { display: none; }
  .step-line { flex: 1; margin: 0 8px; }
  /* H5 面板标题：标题/描述上下堆叠（PC 水平挤行在窄屏把描述压成竖条） */
  .panel-intro { flex-direction: column; align-items: flex-start; gap: 4px; padding: 0 2px 12px; }
  .card { padding: 15px 15px; }
  .step-actions { flex-direction: column-reverse; }
  .actions-right { width: 100%; }
  .actions-right .btn { flex: 1; }
  .back-btn { width: 100%; }
}
</style>
