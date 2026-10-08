<template>
  <div class="fw-page">
    <!-- 页头横幅：与「参数填写」页 mode-banner 同风格 -->
    <div class="fw-banner">
      <div class="fw-banner-head">
        <span class="fw-banner-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>
          </svg>
        </span>
        <h1 class="fw-banner-title">按范文样式制作模板</h1>
        <span class="fw-banner-tag"><span class="fw-tag-dot"></span>范文结构</span>
      </div>
      <p class="fw-banner-desc">上传一份排版规范的 Word 文档，系统自动识别其版式并生成相同样式的模板，排版新稿时在「我的模板」中选用即可套用。</p>
    </div>

    <!-- 生成结果 -->
    <div v-if="importResult" class="fw-result">
      <div class="fw-result-head">
        <svg class="fw-result-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <div class="fw-result-text">
          <div class="fw-result-title">模板已生成：{{ importResult.name }}</div>
          <div class="fw-result-sub">已保留范文文档的版式样式，排版新稿时可在「我的模板」中选用。</div>
        </div>
        <div class="fw-result-actions">
          <button class="fw-btn ghost" @click="resetPage">继续制作</button>
          <button class="fw-btn solid" @click="goUserTemplates">去「我的模板」</button>
        </div>
      </div>
      <div class="fw-result-meta">
        <span class="fw-meta-item">样式区块 <b>{{ importResult.boardCount }} 个</b></span>
        <span class="fw-meta-item">标题层级 <b>{{ importResult.levelCount }} 级</b></span>
        <span class="fw-meta-item">模板ID <b>#{{ importResult.template_id }}</b></span>
      </div>
    </div>

    <!-- 双栏：左侧上传 / 右侧信息与提交 -->
    <div v-else class="fw-layout">
      <section class="fw-col-main">
        <div class="fw-card">
          <div class="fw-card-head">
            <h2 class="fw-card-title">上传范文文档</h2>
            <span class="fw-card-badge">.docx · 最大 10MB</span>
          </div>

          <div
            class="fw-upload"
            :class="{ 'drag-over': isDragging, 'has-file': coverOssUrl }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
          >
            <input type="file" ref="fileInput" style="display: none;" accept=".docx" @change="handleFileChange">

            <div v-if="!coverOssUrl" class="fw-upload-empty" @click="triggerFileUpload">
              <div class="fw-upload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
              </div>
              <div class="fw-upload-text">
                <div class="fw-upload-main">点击选择文件，或将文档拖拽到此处</div>
                <div class="fw-upload-sub">选择一篇排版整洁的 Word 文档作为版式参考</div>
              </div>
            </div>

            <div v-else class="fw-file-selected">
              <div class="fw-selected-header">
                <svg class="fw-success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span :class="{ uploading: uploadingCover }">{{ uploadingCover ? '正在上传…' : '上传成功' }}</span>
              </div>
              <div class="fw-file-name-tag">{{ selectedFile?.name || '范文文档' }}</div>
              <div class="fw-file-actions">
                <button class="fw-btn-replace" @click="triggerFileUpload">更换文件</button>
                <button class="fw-btn-delete" @click="removeFile">移除</button>
              </div>
            </div>
          </div>

          <div class="fw-requirements">
            <div class="fw-req-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
              </svg>
              文档要求
            </div>
            <ul class="fw-req-list">
              <li>内容清晰整洁，避免批注、修订痕迹、隐藏文字与多余格式说明。</li>
              <li>标题、正文的层级与排版保持统一规范。</li>
              <li>仅支持 Word 2007 及以上版本生成的 .docx 文件，请勿用 .doc 直接改后缀。</li>
            </ul>
          </div>
        </div>
      </section>

      <aside class="fw-col-side">
        <div class="fw-card">
          <div class="fw-card-head">
            <h2 class="fw-card-title">模板信息</h2>
          </div>

          <div class="fw-field">
            <span class="fw-label">模板名称</span>
            <input class="fw-input" v-model="fanwenName" maxlength="100" placeholder="如：某某大学本科学位论文模板" @keyup.enter="importFanwenFull" />
          </div>
          <p class="fw-field-tip">用于在「我的模板」中识别与管理。</p>

          <div class="fw-divider"></div>

          <button class="fw-submit" :disabled="importing || !coverOssUrl || !fanwenName.trim()" @click="importFanwenFull">
            <span v-if="importing" class="fw-loading"></span>
            {{ importing ? '正在生成模板…' : '生成模板' }}
          </button>

          <!-- 分步进度（同步等待期间） -->
          <div v-if="importing" class="fw-progress">
            <div class="fw-progress-head">
              <span>生成中，请勿关闭页面</span>
              <span class="fw-progress-time">{{ importElapsed }}s</span>
            </div>
            <ul class="fw-progress-steps">
              <li v-for="(s, i) in displaySteps" :key="i" :class="{ done: i < importStep, active: i === importStep }">
                <span class="fw-step-dot">
                  <svg v-if="i < importStep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                {{ s }}
              </li>
            </ul>
          </div>
          <template v-else>
            <span v-if="!coverOssUrl" class="fw-hint">请先上传范文文档</span>
            <span v-else-if="!fanwenName.trim()" class="fw-hint">请填写模板名称</span>
          </template>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

definePageMeta({ layout: 'console' })

const api = useApi()
const toast = useToast()
const router = useRouter()

// 范文上传状态
const fileInput = ref(null)
const isDragging = ref(false)
const selectedFile = ref(null)
const coverOssUrl = ref('')
const uploadingCover = ref(false)

// 范文导入表单
const fanwenName = ref('')
const importing = ref(false)
const importResult = ref(null)

// 分步进度（SSE 阶段事件驱动，计时器仅计秒）
const importSteps = ['下载并解析文档', '识别板块与标题层级', '提取正文排版属性', '生成骨架并保存']
const STAGE_INDEX = { download: 0, parse: 1, upload: 2, save: 3 }
const importStep = ref(0)
const importElapsed = ref(0)
const progressMsg = ref('')
let importTimer = null

const displaySteps = computed(() =>
  importSteps.map((s, i) => (i === importStep.value && progressMsg.value ? progressMsg.value : s))
)

function startImportProgress() {
  importStep.value = 0
  importElapsed.value = 0
  progressMsg.value = ''
  importTimer = setInterval(() => {
    importElapsed.value += 1
  }, 1000)
}

function stopImportProgress() {
  if (importTimer) {
    clearInterval(importTimer)
    importTimer = null
  }
}

// 与 createoutline/OutlineEditor 一致:从 localStorage 解析登录 token
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

function goUserTemplates() {
  router.push('/pc/user?tab=templates')
}

function resetPage() {
  coverOssUrl.value = ''
  selectedFile.value = null
  fanwenName.value = ''
  importResult.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function importFanwenFull() {
  if (!coverOssUrl.value) {
    toast.error('请先上传范文文档')
    return
  }
  const name = fanwenName.value.trim()
  if (!name) {
    toast.error('请输入模板名称')
    return
  }
  importing.value = true
  startImportProgress()
  try {
    const resp = await fetch('/api/template/importFanwenStream', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', token: getToken(), Accept: 'text/event-stream' },
      body: JSON.stringify({ name, file_url: coverOssUrl.value, scope: 'private' }),
    })
    if (!resp.ok) throw new Error('HTTP ' + resp.status)
    const ct = resp.headers.get('content-type') || ''
    if (ct.includes('application/json')) {
      const j = await resp.json()
      throw new Error(j.msg || '模板生成失败')
    }
    if (!resp.body || typeof resp.body.getReader !== 'function') {
      throw new Error('当前浏览器不支持流式响应')
    }
    const reader = resp.body.getReader()
    const decoder = new TextDecoder('utf-8')
    let buffer = ''
    let finished = false
    outer: while (true) {
      const { done: rdDone, value } = await reader.read()
      if (rdDone) break
      buffer += decoder.decode(value, { stream: true })
      while (buffer.includes('\n\n')) {
        const idx = buffer.indexOf('\n\n')
        const frame = buffer.slice(0, idx)
        buffer = buffer.slice(idx + 2)
        for (const line of frame.split('\n')) {
          if (!line.startsWith('data:')) continue
          const jsonStr = line.slice(5).trim()
          if (!jsonStr) continue
          const ev = JSON.parse(jsonStr)
          if (ev.status === 'progress') {
            importStep.value = STAGE_INDEX[ev.stage] ?? importStep.value
            progressMsg.value = ev.msg || ''
          } else if (ev.status === 'done') {
            const d = ev.data || {}
            importResult.value = {
              template_id: d.template_id || 0,
              name,
              boardCount: Array.isArray(d.boards) ? d.boards.length : 0,
              levelCount: Object.keys(d.bodyLevels || {}).length,
            }
            toast.success('模板生成成功')
            finished = true
            break outer
          } else if (ev.status === 'error') {
            throw new Error(ev.msg || '模板提取失败')
          }
        }
      }
    }
    if (!finished) throw new Error('连接中断，请稍后在「我的模板」查看结果')
  } catch (e) {
    toast.error(e?.message || '模板生成失败')
  } finally {
    importing.value = false
    stopImportProgress()
  }
}

function triggerFileUpload() {
  fileInput.value?.click()
}

async function handleFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  await validateAndProcessFile(file)
}

async function handleFileDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  await validateAndProcessFile(file)
}

async function validateAndProcessFile(file) {
  if (!file.name.endsWith('.docx')) {
    toast.error('仅支持 .docx 格式文件')
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    toast.error('文件大小不能超过 10MB')
    return
  }
  const check = await validateDocxFile(file)
  if (check === 'doc') {
    toast.error('检测到 .doc 旧格式，请先用 Word/WPS 另存为 .docx 再上传')
    return
  }
  if (check !== true) {
    toast.error('文件格式不正确，请上传有效的 .docx 文档')
    return
  }
  selectedFile.value = file
  coverOssUrl.value = ''
  uploadcingToast(file.name)
  uploadingCover.value = true
  try {
    const sigRes = await api.post('/api/template/uploadCover', { ext: 'docx' })
    if (!sigRes.ok || !sigRes.data?.put_url) {
      toast.error(sigRes.msg || '获取上传签名失败')
      return
    }
    const sig = sigRes.data
    const putResp = await fetch(sig.put_url, {
      method: 'PUT',
      body: file,
      headers: { 'Content-Type': sig.content_type },
    })
    if (!putResp.ok) {
      toast.error('文件上传失败：HTTP ' + putResp.status)
      return
    }
    coverOssUrl.value = sig.download_url
    toast.success('范文上传成功')
  } catch {
    toast.error('范文上传失败')
  } finally {
    uploadingCover.value = false
  }
}

function uploadcingToast(name) {
  toast.success(`文件「${name}」已选择`)
}

async function validateDocxFile(file) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const arr = new Uint8Array(e.target?.result)
      // .doc(OLE 复合文档)魔数 D0 CF 11 E0 → 改后缀的旧格式
      if (arr.length >= 4 && arr[0] === 0xD0 && arr[1] === 0xCF && arr[2] === 0x11 && arr[3] === 0xE0) {
        resolve('doc')
        return
      }
      // .docx = ZIP 包,魔数 PK\x03\x04(仅凭魔数即可区分,不再检查包内文件名字符串,
      // 避免 WPS 等工具生成的 docx 前 4KB 不含 [Content_Types].xml 被误判)
      resolve(arr.length >= 4 && arr[0] === 0x50 && arr[1] === 0x4B && arr[2] === 0x03 && arr[3] === 0x04)
    }
    reader.onerror = () => resolve(false)
    reader.readAsArrayBuffer(file.slice(0, 8))
  })
}

function removeFile() {
  selectedFile.value = null
  coverOssUrl.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
</script>

<style scoped>
.fw-page {
  max-width: 1080px;
  margin: 0 auto;
  background: transparent;
}

/* ===== 页头横幅（同参数填写页 mode-banner） ===== */
.fw-banner {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 20px 15px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.fw-banner-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 5px;
}
.fw-banner-icon {
  position: relative;
  width: 32px; height: 32px;
  border-radius: 9px;
  color: #fff;
  flex-shrink: 0;
  overflow: hidden;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.28);
}
.fw-banner-icon svg {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 15px; height: 15px;
  display: block;
}
.fw-banner-title {
  font-size: 14px; font-weight: 600; color: #0f172a;
  margin: 0;
  line-height: 1.3;
  flex: 1;
  min-width: 0;
}
.fw-banner-tag {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px;
  font-size: 11px; font-weight: 500;
  border-radius: 5px;
  line-height: 1.5;
  flex-shrink: 0;
  color: #0d9488; background: #f0fdfa;
}
.fw-tag-dot { width: 5px; height: 5px; border-radius: 50%; background: #14b8a6; }
.fw-banner-desc {
  font-size: 12.5px; color: #64748b;
  margin: 0;
  line-height: 1.55;
  padding-left: 44px; /* 对齐到标题：图标 32 + gap 12 */
}

/* ===== 双栏布局 ===== */
.fw-layout {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}
.fw-col-main { flex: 1; min-width: 0; }
.fw-col-side { width: 300px; flex-shrink: 0; }

/* ===== 卡片（同 section-card） ===== */
.fw-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}
.fw-card-head {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}
.fw-card-title {
  font-size: 15px; font-weight: 600; color: #0f172a; margin: 0;
  display: flex; align-items: center; gap: 8px;
}
.fw-card-title::before {
  content: ''; width: 4px; height: 14px; background: #14b8a6; border-radius: 2px;
}
.fw-card-badge {
  font-size: 12px; color: #14b8a6; background: #f0fdfa;
  padding: 3px 10px; border-radius: 6px; font-weight: 500;
  flex-shrink: 0;
}

/* ===== 上传区（同参数填写页 upload-area） ===== */
.fw-upload {
  border: 2px dashed #cbd5e1; border-radius: 14px; padding: 38px 32px;
  text-align: center; background: #f8fafc; transition: all 0.2s ease; cursor: pointer;
}
.fw-upload:hover, .fw-upload.drag-over { border-color: #14b8a6; background: #f0fdfa; }
.fw-upload.has-file { border-style: solid; border-color: #e2e8f0; background: #fff; cursor: default; }
.fw-upload.has-file:hover { border-color: #e2e8f0; background: #fff; }

.fw-upload-empty { display: flex; flex-direction: column; align-items: center; gap: 14px; }
.fw-upload-icon { width: 44px; height: 44px; color: #94a3b8; transition: color 0.2s ease; }
.fw-upload-icon svg { width: 100%; height: 100%; }
.fw-upload:hover .fw-upload-icon, .fw-upload.drag-over .fw-upload-icon { color: #14b8a6; }
.fw-upload-text { display: flex; flex-direction: column; gap: 7px; }
.fw-upload-main { font-size: 14.5px; color: #374151; font-weight: 500; }
.fw-upload-sub { font-size: 12.5px; color: #94a3b8; }

/* 已选文件状态 */
.fw-file-selected { display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 8px 16px; }
.fw-selected-header {
  display: flex; align-items: center; gap: 8px; color: #0d9488; font-size: 15px; font-weight: 600;
}
.fw-selected-header.uploading, .fw-selected-header span.uploading { color: #14b8a6; }
.fw-success-icon { width: 20px; height: 20px; }
.fw-file-name-tag {
  background: #f3f4f6; border-radius: 20px; padding: 9px 22px; font-size: 13.5px;
  color: #374151; font-weight: 500; max-width: 100%;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.fw-file-actions { display: flex; gap: 12px; }
.fw-btn-replace, .fw-btn-delete {
  padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: all 0.15s; border: none;
}
.fw-btn-replace { background: #fff; color: #374151; border: 1px solid #e2e8f0; }
.fw-btn-replace:hover { border-color: #14b8a6; }
.fw-btn-delete { background: rgba(239, 68, 68, 0.06); color: #ef4444; }
.fw-btn-delete:hover { background: rgba(239, 68, 68, 0.1); }

/* ===== 文档要求 ===== */
.fw-requirements {
  margin-top: 16px; padding: 15px 18px;
  background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9;
}
.fw-req-title {
  display: flex; align-items: center; gap: 7px;
  font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 9px;
}
.fw-req-title svg { width: 16px; height: 16px; color: #14b8a6; }
.fw-req-list { margin: 0; padding: 0; list-style: none; }
.fw-req-list li {
  position: relative; padding-left: 15px;
  font-size: 12.5px; color: #6b7280; line-height: 1.9;
}
.fw-req-list li::before {
  content: ''; position: absolute; left: 0; top: 10px;
  width: 6px; height: 6px; background: #14b8a6; border-radius: 50%; opacity: 0.3;
}

/* ===== 右栏：模板信息 ===== */
.fw-field { display: flex; flex-direction: column; }
.fw-label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
.fw-input {
  width: 100%; padding: 9px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b;
  background: #fff; outline: none; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.fw-input::placeholder { color: #cbd5e1; }
.fw-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.08); }
.fw-input:hover:not(:focus) { border-color: #cbd5e1; }
.fw-field-tip { font-size: 12px; color: #94a3b8; margin: 8px 0 0; line-height: 1.6; }

.fw-divider { height: 1px; background: #f1f5f9; margin: 16px 0; }

/* ===== 提交按钮（同 ds-btn-primary） ===== */
.fw-submit {
  width: 100%;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 10px 20px; border: none; border-radius: 8px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff;
  font-size: 14px; font-weight: 500; cursor: pointer;
  transition: box-shadow 0.15s;
  box-shadow: 0 1px 12px rgba(20, 184, 166, 0.15);
}
.fw-submit:hover:not(:disabled) { box-shadow: 0 2px 16px rgba(20, 184, 166, 0.25); }
.fw-submit:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }
.fw-loading {
  width: 14px; height: 14px; border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff; border-radius: 50%;
  animation: fw-spin 0.7s linear infinite; display: inline-block;
}
@keyframes fw-spin { to { transform: rotate(360deg); } }
.fw-hint { display: block; font-size: 12px; color: #94a3b8; margin-top: 10px; text-align: center; }

/* ===== 分步进度 ===== */
.fw-progress {
  margin-top: 12px; padding: 12px 14px;
  background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;
}
.fw-progress-head {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px;
}
.fw-progress-head > span:first-child { font-size: 12.5px; font-weight: 600; color: #0f766e; }
.fw-progress-time { font-size: 11.5px; color: #94a3b8; font-variant-numeric: tabular-nums; }
.fw-progress-steps { margin: 0; padding: 0; list-style: none; }
.fw-progress-steps li {
  display: flex; align-items: center; gap: 8px;
  font-size: 12.5px; color: #94a3b8; line-height: 2;
}
.fw-progress-steps li.active { color: #0f766e; font-weight: 500; }
.fw-progress-steps li.done { color: #6b7280; }
.fw-step-dot {
  width: 14px; height: 14px; flex-shrink: 0;
  border: 1.5px solid #cbd5e1; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  background: #fff;
}
.fw-progress-steps li.active .fw-step-dot { border-color: #14b8a6; animation: fw-pulse 1.2s ease-in-out infinite; }
.fw-progress-steps li.done .fw-step-dot { border-color: #14b8a6; background: #14b8a6; }
.fw-step-dot svg { width: 8px; height: 8px; color: #fff; display: block; }
@keyframes fw-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.25); }
  50% { box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.08); }
}

/* ===== 生成结果 ===== */
.fw-result {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  padding: 18px 22px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}
.fw-result-head { display: flex; align-items: center; gap: 12px; }
.fw-result-check { width: 26px; height: 26px; color: #14b8a6; flex-shrink: 0; }
.fw-result-text { flex: 1; min-width: 0; }
.fw-result-title {
  font-size: 15px; font-weight: 600; color: #0f172a;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.fw-result-sub { font-size: 12.5px; color: #64748b; margin-top: 3px; }
.fw-result-actions { display: flex; gap: 10px; flex-shrink: 0; }
.fw-btn {
  padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: all 0.15s;
}
.fw-btn.ghost { background: #fff; color: #374151; border: 1px solid #e2e8f0; }
.fw-btn.ghost:hover { border-color: #14b8a6; color: #0d9488; }
.fw-btn.solid {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; border: none;
  box-shadow: 0 1px 12px rgba(20, 184, 166, 0.15);
}
.fw-btn.solid:hover { box-shadow: 0 2px 16px rgba(20, 184, 166, 0.25); }

.fw-result-meta {
  margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9;
  display: flex; flex-wrap: wrap; gap: 10px 28px;
}
.fw-meta-item { font-size: 12px; color: #94a3b8; }
.fw-meta-item b { font-size: 13px; font-weight: 600; color: #0f172a; margin-left: 4px; }

/* ===== 响应式 ===== */
@media (max-width: 900px) {
  .fw-layout { flex-direction: column; }
  .fw-col-side { width: 100%; }
  .fw-banner-desc { padding-left: 0; padding-top: 4px; }
  .fw-result-head { flex-wrap: wrap; }
  .fw-result-actions { width: 100%; }
  .fw-result-actions .fw-btn { flex: 1; }
}
</style>
