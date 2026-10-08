<template>
  <div class="internship-diary-page">
    <div class="container diary-container">
      <ToolShell
        name="实习日志生成器"
        desc="填写实习日志信息，一键生成实习日志"
        theme="cyan"
        icon='<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'
      >
        <template #actions>
          <ToolShellPrice
            :loading="priceLoading"
            :final-price="priceInfo.final_price"
            :sell-price="priceInfo.sell_price"
            :text="priceInfo.text"
            theme="cyan"
          />
        </template>
        <div class="diary-body">

          <!-- ============ 配置卡片 1: 基础日志信息 ============ -->
          <div class="config-card">
            <h3 class="config-title">
              <span class="config-title-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              </span>
              基础信息
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
                <template v-else-if="field.name === 'WidgetCheckbox'">
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
              </el-form-item>
            </el-form>
          </div>

          <!-- ============ 配置卡片 2: 内容补充 ============ -->
          <div class="config-card">
            <h3 class="config-title">
              <span class="config-title-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </span>
              内容补充
            </h3>
            <el-form
              :model="formData"
              label-position="top"
              class="dyn-form"
              @submit.prevent
            >
              <el-form-item
                v-for="field in contentFields"
                :key="field.props.field"
                :label="field.props.title"
                :required="!!field.props.isRequired"
              >
                <template v-if="field.name === 'WidgetTextarea'">
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
                <template v-else-if="field.name === 'WidgetTitle' || field.name === 'WidgetInput'">
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
                <template v-else-if="field.name === 'WidgetCheckbox'">
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
              </el-form-item>
            </el-form>
          </div>

          <!-- ============ 提示卡片 ============ -->
          <div class="tip-card">
            <div class="tip-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <div class="tip-content">
              <h4 class="tip-title">温馨提示</h4>
              <p class="tip-text">实习日志的「大纲内容」每行代表一条，系统将自动逐条生成日志内容。</p>
            </div>
          </div>

          <!-- ============ 生成按钮 ============ -->
          <div class="submit-area">
            <el-button
              class="gen-btn"
              type="primary"
              size="large"
              :loading="saving"
              :disabled="!formFields.length"
              @click="submitForm"
            >
              <span class="gen-btn-inner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                {{ saving ? '生成中...' : '生成日志' }}
              </span>
            </el-button>
          </div>
        </div>
      </ToolShell>
    </div>

    <!-- 支付弹窗 -->
    <PaymentModal
      v-model="showPayment"
      :order-id="orderId"
      :price="price"
      redirect="/pc/generate/user/sx_record"
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
  title: '实习日志生成器 - AI写作助手',
  description: '填写实习日志信息，一键生成实习日志。',
})

const api = useApi()

const formFields = ref([])
const formData = reactive({})
const formRef = ref(null)

const saving = ref(false)

const basicFields = computed(() => {
  return formFields.value.filter((f) => {
    if (f.name === 'WidgetTextarea') return false
    if (f.props?.field === 'outlines') return false
    return true
  })
})

const contentFields = computed(() => {
  return formFields.value.filter((f) => {
    if (f.name === 'WidgetTextarea') return true
    if (f.props?.field === 'outlines') return true
    return false
  })
})

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

async function loadForm() {
  priceLoading.value = true
  const res = await api.get('/api/write/detail', { id: 5 })
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
    if (f.name === 'WidgetCheckbox') {
      formData[field] = Array.isArray(f.props?.defaultValue) ? f.props.defaultValue[0] : f.props?.defaultValue || ''
    } else if (f.name === 'WidgetSelect') {
      formData[field] = f.props?.defaultValue || ''
    } else {
      formData[field] = f.props?.defaultValue || ''
    }
  })
}

async function submitForm() {
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

  saving.value = true
  try {
    const formEntries = {}
    Object.keys(formData).forEach((key) => {
      const value = formData[key]
      if (key === 'outlines' && value) {
        formEntries[key] = String(value).split('\n').map((s) => s.trim()).filter(Boolean)
      } else {
        formEntries[key] = value
      }
    })

    const res = await api.post('/api/write/sxrzsave', formEntries)
    if (!res.ok) {
      ElMessage.error(res.msg || '生成失败，请重试')
      return
    }
    orderId.value = res.data?.record_id || ''
    price.value = res.data?.price || 0
    if (orderId.value) {
      showPayment.value = true
    } else {
      ElMessage.error('生成失败：未获取到订单号')
    }
  } catch (e) {
    ElMessage.error(e.message || '生成失败，请稍后重试')
  } finally {
    saving.value = false
  }
}

function onPaySuccess() {
  ElMessage.success('实习日志已成功生成，可在订单中心查看进度')
  navigateTo('/pc/orders/write')
}

onMounted(() => {
  loadForm()
})
</script>

<style scoped>
.internship-diary-page {
  max-width: 760px;
  margin: 0 auto;
}

.diary-container {
  max-width: 760px;
  margin: 0 auto;
  padding: 0;
}

.diary-body {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ============ 分组配置卡片 ============ */
.config-card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  padding: 20px 22px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  transition: border-color 0.2s ease, background 0.2s ease;
}
.config-card:hover {
  border-color: #a5f3fc;
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
  background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
  color: #06b6d4;
}
.config-title-icon svg { width: 15px; height: 15px; }

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
  box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.25), 0 0 0 1px #06b6d4 inset;
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
  box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.25), 0 0 0 1px #06b6d4 inset;
  background: #fff;
}

/* ============ Chip 选择器 ============ */
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
  border-color: #a5f3fc;
  color: #0e7490;
  background: #ecfeff;
}
.chip.active {
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(6, 182, 212, 0.25);
}
.chip:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============ 提示卡片 ============ */
.tip-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #ecfeff;
  border: 1px solid #a5f3fc;
  border-radius: 16px;
  padding: 14px 16px;
}
.tip-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 7px;
  background: #fff;
  color: #06b6d4;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(6, 182, 212, 0.15);
}
.tip-content {
  flex: 1;
  min-width: 0;
}
.tip-title {
  margin: 0 0 4px 0;
  font-size: 13px;
  font-weight: 600;
  color: #0e7490;
  line-height: 1.3;
}
.tip-text {
  margin: 0;
  font-size: 12.5px;
  color: #155e75;
  line-height: 1.7;
}

/* ============ 生成按钮 ============ */
.submit-area {
  padding-top: 4px;
}
.gen-btn {
  width: 100%;
  height: 46px;
  padding: 0 24px;
  background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14.5px;
  color: #fff;
  letter-spacing: 0.2px;
  transition: box-shadow 0.2s ease, transform 0.1s ease;
  box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3);
}
.gen-btn:hover:not(.is-disabled) {
  box-shadow: 0 6px 20px rgba(6, 182, 212, 0.38);
}
.gen-btn:active:not(.is-disabled) {
  transform: translateY(1px);
}
.gen-btn-inner {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

/* ============ 响应式 ============ */
@media (max-width: 600px) {
  .config-card { padding: 14px; }
}
</style>
