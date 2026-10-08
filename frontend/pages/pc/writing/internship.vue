<template>
  <div class="internship-page">
    <div class="container internship-container">
      <ToolShell
        name="实习报告生成器"
        desc="填写实习信息，选择模板一键生成实习报告"
        theme="orange"
        icon='<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>'
      >
        <template #actions>
          <ToolShellPrice
            :loading="priceLoading"
            :final-price="priceInfo.final_price"
            :sell-price="priceInfo.sell_price"
            :text="priceInfo.text"
            theme="orange"
          />
        </template>
        <div class="main-grid">
          <!-- 左侧表单区 -->
          <section class="form-panel">

            <!-- ============ 配置卡片 1: 实习基本信息 ============ -->
            <div class="config-card">
              <h3 class="config-title">
                <span class="config-title-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </span>
                实习基本信息
              </h3>
              <el-form
                ref="formRef"
                :model="formData"
                label-position="top"
                class="dyn-form"
                @submit.prevent
              >
                <el-form-item
                  v-for="field in basicFields"
                  :key="field.props.field"
                  :label="field.props.title"
                  :required="!!field.props.isRequired"
                >
                  <template v-if="field.name === 'WidgetTitle' || field.name === 'WidgetInput'">
                    <el-input
                      v-model="formData[field.props.field]"
                      :placeholder="field.props.placeholder || ''"
                      :maxlength="field.props.maxlength || undefined"
                      :disabled="!!field.props.disabled"
                      resize="none"
                    />
                  </template>
                  <template v-else-if="field.name === 'WidgetSelect'">
                    <div class="chip-group chip-group--wrap">
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

            <!-- ============ 配置卡片 2: 实习详情补充 ============ -->
            <div class="config-card">
              <h3 class="config-title">
                <span class="config-title-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                实习详情
              </h3>
              <el-form
                :model="formData"
                label-position="top"
                class="dyn-form"
                @submit.prevent
              >
                <el-form-item
                  v-for="field in detailFields"
                  :key="field.props.field"
                  :label="field.props.title"
                  :required="!!field.props.isRequired"
                >
                  <template v-if="field.name === 'WidgetTitle' || field.name === 'WidgetInput'">
                    <el-input
                      v-model="formData[field.props.field]"
                      :placeholder="field.props.placeholder || ''"
                      :maxlength="field.props.maxlength || undefined"
                      :disabled="!!field.props.disabled"
                      resize="none"
                    />
                  </template>
                  <template v-else-if="field.name === 'WidgetSelect'">
                    <div class="chip-group chip-group--wrap">
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

            <!-- ============ 配置卡片 3: 辅助补充 ============ -->
            <div class="config-card" :class="{ 'is-active': enableAssist }">
              <div class="config-head-row">
                <h3 class="config-title">
                  <span class="config-title-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/><circle cx="12" cy="12" r="10"/></svg>
                  </span>
                  辅助补充
                </h3>
                <el-switch v-model="enableAssist" />
              </div>
              <el-input
                v-if="enableAssist"
                v-model="assistContent"
                type="textarea"
                :rows="4"
                placeholder="可输入任何需要补充的说明、关键词、实习心得、个人感悟..."
                resize="none"
                class="config-textarea"
              />
              <p v-else class="config-hint">开启后可填写额外的个性化说明，AI 将在生成时结合这些内容</p>
            </div>

            <!-- ============ 生成按钮 ============ -->
            <div class="submit-area">
              <el-button
                class="gen-btn"
                type="primary"
                size="large"
                :loading="loading"
                :disabled="!formFields.length"
                @click="openTemplate"
              >
                <span class="gen-btn-inner">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                  {{ loading ? '加载中...' : '获取模板' }}
                </span>
              </el-button>
            </div>
          </section>

          <!-- 右侧说明区 -->
          <section class="info-panel">

            <!-- 操作步骤卡片 -->
            <div class="config-card info-steps-card">
              <h3 class="config-title">
                <span class="config-title-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                操作步骤
              </h3>
              <div class="info-steps">
                <div class="info-step">
                  <span class="info-step-no">1</span>
                  <span class="info-step-text">填写左侧实习相关信息，包括岗位、单位与实习内容</span>
                </div>
                <div class="info-step">
                  <span class="info-step-no">2</span>
                  <span class="info-step-text">点击「获取模板」，选择您需要的实习报告模板</span>
                </div>
                <div class="info-step">
                  <span class="info-step-no">3</span>
                  <span class="info-step-text">确认后即可一键生成完整的实习报告</span>
                </div>
              </div>
            </div>

            <!-- 温馨提示卡片 -->
            <div class="config-card info-tip-card">
              <h3 class="config-title">
                <span class="config-title-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                </span>
                温馨提示
              </h3>
              <div class="info-note">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>内容越详细，生成的报告越贴合实际，请尽量完整填写。</span>
              </div>
            </div>
          </section>
        </div>
      </ToolShell>
    </div>

    <!-- 模板选择弹窗 -->
    <el-dialog
      v-model="showTemplateModal"
      title="选择模板"
      width="720px"
      custom-class="tpl-dialog"
      modal-class="tpl-dialog-mask"
      append-to-body
    >
      <div class="tpl-search">
        <el-input
          v-model="templateSearch"
          placeholder="输入模板名称搜索"
          clearable
          class="tpl-search-input-el"
          @keyup.enter="searchTemplates"
          @clear="loadTemplates(1)"
        >
          <template #append>
            <el-button @click="searchTemplates">搜索</el-button>
          </template>
        </el-input>
      </div>
      <div v-if="templateLoading" class="tpl-loading">加载中...</div>
      <div v-else class="template-grid">
        <div
          v-for="t in templates"
          :key="t.tid"
          class="template-item"
          :class="{ selected: selectedTemplateId === String(t.tid) }"
          @click="selectTemplate(t)"
        >
          <img :src="templateImg(t)" :alt="t.name" loading="lazy" />
          <p>{{ t.name }}</p>
          <span v-if="selectedTemplateId === String(t.tid)" class="tpl-check">✓</span>
        </div>
        <el-empty v-if="!templates.length" description="暂无模板" />
      </div>
      <template #footer>
        <el-button class="tpl-btn-cancel" @click="showTemplateModal = false">取消</el-button>
        <el-button class="tpl-btn-confirm" :loading="saving" :disabled="!selectedTemplateId" @click="confirmTemplate">
          确认下单
        </el-button>
      </template>
    </el-dialog>

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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

definePageMeta({
  layout: 'console',
})

useSeoMeta({
  title: '实习报告生成器 - AI写作助手',
  description: '填写实习信息，选择模板一键生成实习报告。',
})

const api = useApi()

const formFields = ref([])
const formData = reactive({})
const formRef = ref(null)

const loading = ref(false)
const saving = ref(false)

const enableAssist = ref(false)
const assistContent = ref('')

const basicFields = computed(() => {
  const all = formFields.value
  const textareaIdx = all.findIndex((f) => f.name === 'WidgetTextarea')
  if (textareaIdx === -1) return all
  const basic = all.slice(0, textareaIdx)
  if (!basic.length) return all.slice(0, Math.ceil(all.length / 2))
  return basic
})

const detailFields = computed(() => {
  const all = formFields.value
  const textareaIdx = all.findIndex((f) => f.name === 'WidgetTextarea')
  if (textareaIdx === -1) return []
  const detail = all.slice(textareaIdx)
  if (!detail.length) return all.slice(Math.ceil(all.length / 2))
  return detail
})

// 模板
const showTemplateModal = ref(false)
const templates = ref([])
const templateLoading = ref(false)
const templateSearch = ref('')
const selectedTemplateId = ref('')

// 支付
const showPayment = ref(false)
const orderId = ref('')
const price = ref(0)

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
  const res = await api.get('/api/write/detail', { id: 16 })
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
  forms.forEach((f) => {
    const field = f.props?.field
    if (!field) return
    formData[field] = f.props?.defaultValue || ''
  })
}

// 打开模板弹窗
function openTemplate() {
  showTemplateModal.value = true
  loadTemplates(1)
}

// 模板列表
async function loadTemplates(page = 1) {
  templateLoading.value = true
  try {
    const res = await api.get('/api/write/sxtemplist', { page })
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
    const res = await api.get('/api/write/sxtemplist', { name: kw })
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

// 确认下单
async function confirmTemplate() {
  if (!selectedTemplateId.value) {
    ElMessage.warning('请先选择一个模板')
    return
  }
  saving.value = true
  try {
    const requestData = {
      ...JSON.parse(JSON.stringify(formData)),
      assistContent: enableAssist.value ? assistContent.value : '',
      templateId: selectedTemplateId.value,
    }
    const res = await api.post('/api/write/sxsave', requestData)
    if (!res.ok) {
      ElMessage.error(res.msg || '下单失败，请重试')
      return
    }
    orderId.value = res.data?.record_id || ''
    price.value = res.data?.price || 0
    showTemplateModal.value = false
    if (orderId.value) {
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
  ElMessage.success('实习报告已成功生成，可在订单中心查看进度')
  navigateTo('/pc/orders/write')
}

onMounted(() => {
  loadForm()
})
</script>

<style scoped>
.internship-page {
  max-width: 1100px;
  margin: 0 auto;
}

.internship-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0;
}

/* ============ 主体布局 ============ */
.main-grid {
  display: grid;
  grid-template-columns: minmax(340px, 1.4fr) minmax(280px, 1fr);
  gap: 18px;
}

@media (max-width: 900px) {
  .main-grid {
    grid-template-columns: 1fr;
  }
}

/* ============ 面板容器 ============ */
.form-panel,
.info-panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ============ 分组配置卡片 (橙色主题) ============ */
.config-card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  padding: 20px 22px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  transition: border-color 0.2s ease, background 0.2s ease;
}

.config-card.is-active {
  border-color: #fed7aa;
  background: #fff7ed;
}

.config-title {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 14px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 14px 0;
  line-height: 1;
}

.config-title-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
  color: #f97316;
}

.config-title-icon svg {
  width: 15px;
  height: 15px;
}

.config-head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.config-head-row .config-title {
  margin-bottom: 0;
}

.config-hint {
  margin: 0;
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.6;
  padding-left: 37px;
}

.config-textarea :deep(.el-textarea__inner) {
  background: #fff;
  border-radius: 8px;
}

/* ============ 动态表单 ============ */
.dyn-form :deep(.el-form-item) {
  margin-bottom: 14px;
}

.dyn-form :deep(.el-form-item:last-child) {
  margin-bottom: 0;
}

.dyn-form :deep(.el-form-item__label) {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  line-height: 1.4;
  padding-bottom: 5px;
}

.dyn-form :deep(.el-input__wrapper),
.dyn-form :deep(.el-textarea__inner) {
  border-radius: 8px;
}

.dyn-form :deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #d1d5db inset;
  background: #fafafa;
  transition: box-shadow 0.2s, background 0.2s;
}

.dyn-form :deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #9ca3af inset;
}

.dyn-form :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.25), 0 0 0 1px #f97316 inset;
  background: #fff;
}

.dyn-form :deep(.el-textarea__inner) {
  background: #fafafa;
  box-shadow: 0 0 0 1px #d1d5db inset;
  transition: box-shadow 0.2s, background 0.2s;
}

.dyn-form :deep(.el-textarea__inner:hover) {
  box-shadow: 0 0 0 1px #9ca3af inset;
}

.dyn-form :deep(.el-textarea__inner:focus) {
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.25), 0 0 0 1px #f97316 inset;
  background: #fff;
}

/* ============ Chip 选择器 (替代原生 select, 橙色主题) ============ */
.chip-group {
  display: flex;
  gap: 8px;
}

.chip-group--wrap {
  flex-wrap: wrap;
}

.chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #4b5563;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.chip:hover:not(:disabled) {
  border-color: #fdba74;
  color: #ea580c;
  background: #fff7ed;
}

.chip.active {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(249, 115, 22, 0.25);
}

.chip:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============ 生成按钮 ============ */
.submit-area {
  padding-top: 4px;
}

.gen-btn {
  width: 100%;
  height: 46px;
  padding: 0 24px;
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14.5px;
  color: #fff;
  letter-spacing: 0.2px;
  transition: box-shadow 0.2s ease, transform 0.1s ease;
  box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}

.gen-btn:hover:not(.is-disabled) {
  box-shadow: 0 6px 20px rgba(249, 115, 22, 0.38);
}

.gen-btn:active:not(.is-disabled) {
  transform: translateY(1px);
}

.gen-btn-inner {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

/* ============ 说明区 - 步骤卡片 ============ */
.info-steps-card .info-steps {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-left: 37px;
}

.info-step {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.info-step-no {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  flex-shrink: 0;
  background: #f97316;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}

.info-step-text {
  font-size: 13px;
  color: #4b5563;
  line-height: 1.6;
  padding-top: 3px;
  flex: 1;
}

/* ============ 说明区 - 提示卡片 ============ */
.info-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 14px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 8px;
  font-size: 12.5px;
  color: #c2410c;
  line-height: 1.6;
}

.info-note svg {
  flex-shrink: 0;
  margin-top: 2px;
  color: #f97316;
}

/* ============ 模板弹窗 ============ */
.tpl-search {
  margin-bottom: 14px;
}

.template-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  max-height: 420px;
  overflow-y: auto;
}

@media (max-width: 700px) {
  .template-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.template-item {
  position: relative;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  transition: border-color 0.2s, transform 0.15s, box-shadow 0.2s;
}

.template-item:hover {
  border-color: #f97316;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.template-item.selected {
  border-color: #f97316;
  box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
}

.template-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.template-item p {
  margin: 0;
  padding: 8px 10px;
  font-size: 12.5px;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tpl-check {
  position: absolute;
  top: 6px;
  right: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #f97316;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}

.tpl-loading {
  padding: 40px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

/* ============ 响应式 ============ */
@media (max-width: 600px) {
  .config-card {
    padding: 14px;
  }
  .config-hint {
    padding-left: 0;
  }
  .info-steps-card .info-steps {
    padding-left: 0;
  }
}
</style>

<style>
/* ============ 模板选择弹窗全局样式（对齐 /pc/create 弹窗风格） ============ */
.el-overlay.tpl-dialog-mask {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  background-color: rgba(15, 23, 42, 0.5);
}

.el-dialog.tpl-dialog {
  border-radius: 20px;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2);
  overflow: hidden;
}

.el-dialog.tpl-dialog .el-dialog__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  margin-right: 0;
  border-bottom: 1px solid var(--gray-100);
}

.el-dialog.tpl-dialog .el-dialog__title {
  font-size: 17px;
  font-weight: 800;
  color: var(--dark-800);
  line-height: 1;
}

.el-dialog.tpl-dialog .el-dialog__headerbtn {
  position: static;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--gray-100);
  transition: all 0.2s;
}

.el-dialog.tpl-dialog .el-dialog__headerbtn:hover {
  background: var(--gray-200);
}

.el-dialog.tpl-dialog .el-dialog__close {
  color: var(--gray-500);
  font-size: 15px;
}

.el-dialog.tpl-dialog .el-dialog__body {
  padding: 20px 24px;
}

.el-dialog.tpl-dialog .el-dialog__footer {
  padding: 0 24px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.el-dialog.tpl-dialog .el-dialog__footer .el-button {
  height: 42px;
  padding: 0 26px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
}

.el-dialog.tpl-dialog .tpl-btn-cancel {
  background: #fff;
  color: var(--gray-600);
  border: 1px solid var(--gray-300);
}

.el-dialog.tpl-dialog .tpl-btn-cancel:hover {
  border-color: var(--gray-400);
  background: var(--gray-50);
  color: var(--dark-800);
}

.el-dialog.tpl-dialog .tpl-btn-confirm {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  border: none;
  color: #fff;
  box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}

.el-dialog.tpl-dialog .tpl-btn-confirm:hover {
  box-shadow: 0 6px 20px rgba(249, 115, 22, 0.38);
  transform: translateY(-1px);
}

/* 搜索框 */
.el-dialog.tpl-dialog .tpl-search-input-el .el-input__wrapper {
  border-radius: 10px;
}

@media (max-width: 640px) {
  .el-dialog.tpl-dialog {
    width: calc(100% - 32px) !important;
    border-radius: 14px;
  }
  .el-dialog.tpl-dialog .el-dialog__header { padding: 14px 16px; }
  .el-dialog.tpl-dialog .el-dialog__body { padding: 14px 16px; }
  .el-dialog.tpl-dialog .el-dialog__footer { padding: 0 16px 14px; }
}
</style>
