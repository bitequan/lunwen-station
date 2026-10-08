<template>
  <div class="order-page">
    <div class="page-container">
      <!-- 状态筛选 -->
      <div class="filter-bar">
        <div class="filter-group">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            class="filter-chip"
            :class="{ active: filterStatus === tab.key }"
            @click="setFilterStatus(tab.key)"
          >
            {{ tab.label }}
          </button>
        </div>
        <div class="search-box">
          <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input
            v-model="searchInput"
            type="text"
            class="search-input"
            placeholder="搜索订单号或文件名"
            @keyup.enter="handleSearch"
          />
          <button class="search-btn" @click="handleSearch">搜索</button>
        </div>
      </div>

      <!-- 表格 -->
      <section class="panel">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>加载中...</p>
        </div>

        <div v-else-if="orders.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <p class="empty-title">暂无格式重排订单</p>
          <p class="empty-desc">去排版你的第一篇文档吧</p>
          <NuxtLink to="/pc/autodoc" class="btn btn-primary">立即排版</NuxtLink>
        </div>

        <div v-else class="table-wrap">
          <table class="order-table">
            <thead>
              <tr>
                <th class="col-sn">订单号</th>
                <th class="col-title">文件名</th>
                <th class="col-amount">金额</th>
                <th class="col-status">生成状态</th>
                <th class="col-pay">支付状态</th>
                <th class="col-time">创建时间</th>
                <th class="col-action">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.order_sn" class="order-row">
                <td class="col-sn">
                  <span class="order-sn-text" :title="order.order_sn" @click="copyOrderSn(order.order_sn)">
                    {{ order.order_sn }}
                  </span>
                </td>
                <td class="col-title">
                  <span class="order-title" :title="stripExt(order.title)">{{ stripExt(order.title) }}</span>
                </td>
                <td class="col-amount">
                  <span class="amount-text">¥{{ order.amount.toFixed(2) }}</span>
                </td>
                <td class="col-status">
                  <span class="status-tag" :class="order.status_class">
                    <span class="status-dot"></span>
                    {{ order.gen_status_text }}
                  </span>
                </td>
                <td class="col-pay">
                  <span v-if="order.pay_status === 1" class="status-tag status-paid">
                    <span class="status-dot"></span>
                    已支付
                  </span>
                  <span v-else-if="order.gen_status === 2" class="status-tag status-unpaid">
                    <span class="status-dot"></span>
                    未支付
                  </span>
                  <span v-else class="status-tag status-na">
                    <span class="status-dot"></span>
                    -
                  </span>
                </td>
                <td class="col-time">
                  <span class="time-text">{{ order.create_time }}</span>
                </td>
                <td class="col-action">
                  <div class="action-buttons">
                    <button
                      v-if="order.gen_status === 2 && order.preview_url"
                      class="action-btn preview-btn"
                      @click="handlePreview(order)"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      <span>预览</span>
                    </button>
                    <button
                      v-if="order.gen_status === 2"
                      class="action-btn download-btn"
                      :class="{ 'download-btn--pay': order.pay_status !== 1 }"
                      :disabled="downloading[order.order_sn]"
                      @click="handleDownload(order)"
                    >
                      <span v-if="downloading[order.order_sn]" class="btn-spinner"></span>
                      <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                      <span>{{ order.pay_status === 1 ? '下载' : '支付下载' }}</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- 分页 -->
      <div v-if="total > 0" class="pagination">
        <button class="page-btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          上一页
        </button>
        <div class="page-numbers">
          <template v-for="(p, i) in pageList" :key="i">
            <span v-if="p === '...'" class="page-ellipsis">...</span>
            <button v-else class="page-num" :class="{ active: p === currentPage }" @click="goToPage(p)">{{ p }}</button>
          </template>
        </div>
        <button class="page-btn" :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
          下一页
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
        <div class="page-info">共 <b>{{ total }}</b> 条 · 第 {{ currentPage }}/{{ totalPages }} 页</div>
      </div>
    </div>

    <!-- 下载确认弹窗 -->
    <div v-if="confirmOrder" class="confirm-mask" @click.self="confirmOrder = null">
      <div class="confirm-dialog">
        <div class="confirm-dialog__header">
          <h3>确认下载</h3>
        </div>
        <div class="confirm-dialog__body">
          <div class="confirm-info">
            <div class="confirm-info__row">
              <span class="confirm-info__label">文件名</span>
              <span class="confirm-info__value">{{ stripExt(confirmOrder.title) }}</span>
            </div>
            <div class="confirm-info__row">
              <span class="confirm-info__label">订单编号</span>
              <span class="confirm-info__value">{{ confirmOrder.order_sn }}</span>
            </div>
            <div class="confirm-info__row confirm-info__row--highlight">
              <span class="confirm-info__label">下载费用</span>
              <span class="confirm-info__value">¥{{ confirmOrder.amount.toFixed(2) }}</span>
            </div>
          </div>
          <p class="confirm-tip">首次下载将扣除费用，下载后可永久免费重复下载。</p>
        </div>
        <div class="confirm-dialog__footer">
          <button class="confirm-btn confirm-btn--cancel" @click="confirmOrder = null">取消</button>
          <button class="confirm-btn confirm-btn--ok" :disabled="downloading[confirmOrder.order_sn]" @click="doDownload(confirmOrder)">
            <span v-if="downloading[confirmOrder.order_sn]" class="btn-spinner"></span>
            <span v-else>确认下载 ¥{{ confirmOrder.amount.toFixed(2) }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'

definePageMeta({ layout: 'console' })
useHead({ title: '格式重排订单 - AI写作助手' })

const api = useApi()
const toast = useToast()

const orders = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = 10
const total = ref(0)
const totalPages = ref(0)
const downloading = reactive({})
const confirmOrder = ref(null)

const filterStatus = ref('all')
const searchKeyword = ref('')
const searchInput = ref('')

let pollTimer = null

const statusTabs = [
  { key: 'all', label: '全部' },
  { key: 'pending', label: '待生成' },
  { key: 'processing', label: '生成中' },
  { key: 'completed', label: '已完成' },
  { key: 'failed', label: '生成失败' },
]

const pageList = computed(() => {
  const tp = totalPages.value
  const cp = currentPage.value
  if (tp <= 7) return Array.from({ length: tp }, (_, i) => i + 1)
  const list = [1]
  if (cp > 4) list.push('...')
  const start = Math.max(2, cp - 1)
  const end = Math.min(tp - 1, cp + 1)
  for (let i = start; i <= end; i++) list.push(i)
  if (cp < tp - 3) list.push('...')
  list.push(tp)
  return list
})

// 是否存在未完成且非失败的订单
const hasPendingOrders = computed(() => {
  return orders.value.some(o => o.gen_status === 0 || o.gen_status === 1)
})

async function loadOrders(silent = false) {
  if (!silent) loading.value = true
  try {
    const res = await api.post('/api/order/autodocList', {
      status: filterStatus.value,
      keyword: searchKeyword.value,
      page: currentPage.value,
      page_size: pageSize,
    })
    if (res.ok) {
      orders.value = res.data.list || []
      total.value = res.data.total || 0
      totalPages.value = res.data.total_pages || 0
    } else if (!silent) {
      toast.error(res.msg || '加载失败')
    }
  } catch (e) {
    if (!silent) toast.error('网络错误，请稍后重试')
  } finally {
    if (!silent) loading.value = false
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(() => {
    if (hasPendingOrders.value) {
      loadOrders(true)
    }
  }, 5000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function setFilterStatus(key) {
  filterStatus.value = key
  currentPage.value = 1
  loadOrders()
}

function handleSearch() {
  searchKeyword.value = searchInput.value.trim()
  currentPage.value = 1
  loadOrders()
}

// 剔除文件名后缀(如 xxx.docx -> xxx)
function stripExt(name) {
  if (!name) return ''
  return String(name).replace(/\.[^.]+$/, '')
}

function goToPage(page) {
  if (page < 1 || page > totalPages.value || page === currentPage.value) return
  currentPage.value = page
  loadOrders()
  if (process.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function copyOrderSn(sn) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(sn)
    } else {
      const ta = document.createElement('textarea')
      ta.value = sn
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
    toast.success('订单号已复制')
  } catch (e) {
    toast.error('复制失败')
  }
}

function handlePreview(order) {
  if (!order.preview_url) {
    toast.info('预览文件不存在')
    return
  }
  // 预览 PDF 已加水印,允许查看但禁止打印/复制
  window.open(order.preview_url, '_blank')
}

async function handleDownload(order) {
  // 已付费,直接下载
  if (order.pay_status === 1) {
    doDownload(order)
    return
  }
  // 未付费,显示确认弹窗
  confirmOrder.value = order
}

// 新窗口下载：先同步打开空白窗口保留用户手势，触发下载后自动关闭窗口回到订单页
function openDownload(url) {
  if (!url) return
  const win = window.open('', '_blank')
  if (!win) {
    // 弹窗被拦截时降级：用 <a target="_blank"> 触发（保留用户手势，避免“无法安全下载”）
    const a = document.createElement('a')
    a.href = url
    a.target = '_blank'
    a.rel = 'noopener noreferrer'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    return
  }
  win.location.href = url
  // 文件在浏览器内直接触发下载，新窗口保持空白；下载完成后自动关闭窗口
  closeDownloadWindow(win)
}

// 下载开始后自动关闭新窗口：下载期间 win.close() 可能被浏览器拦截，
// 因此定时重试，直到下载完成、窗口成功关闭（最多约 12 秒兜底）
function closeDownloadWindow(win) {
  if (!win) return
  let tries = 0
  const timer = setInterval(() => {
    tries++
    try {
      if (win.closed) { clearInterval(timer); return }
      win.close()
      if (win.closed) { clearInterval(timer); return }
    } catch (e) {}
    if (tries >= 24) clearInterval(timer)
  }, 500)
}

async function doDownload(order) {
  if (downloading[order.order_sn]) return
  downloading[order.order_sn] = true
  // 先同步打开空白窗口，保留用户手势，避免异步请求完成后被浏览器拦截
  const win = window.open('', '_blank')
  try {
    const res = await api.post('/api/autodoc/downloadDoc', { order_sn: order.order_sn })
    if (res.ok) {
      if (res.data.charged) {
        toast.success(`已扣费 ¥${order.amount.toFixed(2)}`)
      }
      if (win) {
        win.location.href = res.data.url
        // 文件在浏览器内直接触发下载，新窗口保持空白；下载完成后自动关闭窗口
        closeDownloadWindow(win)
      } else {
        openDownload(res.data.url)
      }
      // 刷新列表更新状态
      loadOrders(true)
    } else {
      if (win) win.close()
      toast.error(res.msg || '下载失败')
    }
  } catch (e) {
    if (win) win.close()
    toast.error('网络错误，请稍后重试')
  } finally {
    downloading[order.order_sn] = false
    confirmOrder.value = null
  }
}

onMounted(() => {
  loadOrders()
  startPolling()
  window.addEventListener('console-refresh', handleRefreshEvent)
})

onUnmounted(() => {
  stopPolling()
  window.removeEventListener('console-refresh', handleRefreshEvent)
})

function handleRefreshEvent() {
  loadOrders()
}
</script>

<style scoped>
.order-page { min-height: calc(100vh - 64px); }
.page-container { max-width: 1400px; margin: 0 auto; }

/* 下载确认弹窗 */
.confirm-mask {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.45);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
}
.confirm-dialog {
  background: #fff; border-radius: 14px; width: 400px; max-width: 90vw;
  overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}
.confirm-dialog__header { padding: 20px 24px 0; }
.confirm-dialog__header h3 { margin: 0; font-size: 18px; font-weight: 700; color: #1a1a1a; }
.confirm-dialog__body { padding: 16px 24px; }
.confirm-info { background: #f8fafc; border-radius: 10px; padding: 14px 16px; }
.confirm-info__row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; }
.confirm-info__row--highlight { border-top: 1px solid #e2e8f0; margin-top: 4px; padding-top: 10px; }
.confirm-info__label { font-size: 13px; color: #64748b; }
.confirm-info__value { font-size: 13px; color: #1e293b; font-weight: 500; max-width: 220px; text-align: right; word-break: break-all; }
.confirm-info__row--highlight .confirm-info__value { font-size: 18px; color: #ef4444; font-weight: 700; }
.confirm-tip { margin: 12px 0 0; font-size: 12px; color: #94a3b8; }
.confirm-dialog__footer { padding: 0 24px 20px; display: flex; gap: 12px; justify-content: flex-end; }
.confirm-btn { padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.confirm-btn--cancel { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
.confirm-btn--cancel:hover { background: #e2e8f0; }
.confirm-btn--ok { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff; }
.confirm-btn--ok:hover { opacity: 0.9; }
.confirm-btn--ok:disabled { opacity: 0.6; cursor: not-allowed; }

/* 按钮 */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.18s ease;
  border: 1px solid transparent;
  white-space: nowrap;
}
.btn-primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3);
}
.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(20, 184, 166, 0.4);
}

/* 筛选区 */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.filter-group { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-chip {
  padding: 7px 16px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.18s ease;
}
.filter-chip:hover { border-color: #99f6e4; background: #f0fdfa; color: #0d9488; }
.filter-chip.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 3px 10px rgba(20, 184, 166, 0.25);
}
.search-box {
  margin-left: auto;
  display: flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
  transition: all 0.18s ease;
}
.search-box:focus-within {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}
.search-icon { margin-left: 12px; color: #94a3b8; flex-shrink: 0; }
.search-input {
  border: none;
  outline: none;
  padding: 8px 10px;
  font-size: 13px;
  width: 220px;
  background: transparent;
  color: #0f172a;
}
.search-input::placeholder { color: #94a3b8; }
.search-btn {
  border: none;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.search-btn:hover { filter: brightness(1.05); }

/* 面板 */
.panel {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.04);
  overflow: hidden;
  min-height: 400px;
}

/* 加载/空状态 */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  gap: 16px;
  color: #94a3b8;
}
.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #f1f5f9;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 70px 20px;
  gap: 8px;
  text-align: center;
}
.empty-icon {
  width: 80px;
  height: 80px;
  display: grid;
  place-items: center;
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(13, 148, 136, 0.04) 100%);
  color: #0d9488;
  margin-bottom: 8px;
  border: 1px solid #ccfbf1;
}
.empty-title { font-size: 15px; font-weight: 600; color: #475569; margin: 0; }
.empty-desc { font-size: 13px; color: #94a3b8; margin: 0 0 12px; }

/* 表格 */
.table-wrap { padding: 0 24px 8px; }
.order-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
}
.order-table thead th {
  padding: 13px 12px;
  text-align: left;
  font-size: 11.5px;
  font-weight: 700;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.order-table thead th:first-child { border-top-left-radius: 10px; }
.order-table thead th:last-child { border-top-right-radius: 10px; }
.order-row { transition: all 0.18s ease; }
.order-table tbody td {
  padding: 14px 12px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  color: #334155;
  vertical-align: middle;
}
.order-row:hover { background: linear-gradient(90deg, rgba(20, 184, 166, 0.03) 0%, transparent 100%); }
.order-row:hover td { border-bottom-color: #ccfbf1; }
.order-row:last-child td { border-bottom: none; }

.col-sn { width: 17%; }
.col-title { width: 28%; }
.col-amount { width: 8%; }
.col-status { width: 10%; }
.col-pay { width: 10%; }
.col-time { width: 13%; }
.col-action { width: 14%; }

.order-sn-text {
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  font-size: 12.5px;
  color: #475569;
  cursor: pointer;
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  transition: color 0.18s ease;
}
.order-sn-text:hover { color: #0d9488; }
.order-title {
  display: block;
  font-weight: 500;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.amount-text { font-weight: 700; color: #0f172a; font-size: 14px; }

.status-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.status-tag.pending { background: rgba(249, 115, 22, 0.1); color: #c2410c; }
.status-tag.processing { background: rgba(6, 182, 212, 0.1); color: #0e7490; }
.status-tag.processing .status-dot { animation: pulse-dot 1.4s ease-in-out infinite; }
.status-tag.completed { background: rgba(34, 197, 94, 0.1); color: #15803d; }
.status-tag.failed { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }
.status-tag.status-paid { background: rgba(34, 197, 94, 0.1); color: #15803d; }
.status-tag.status-unpaid { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }
.status-tag.status-na { background: rgba(148, 163, 184, 0.1); color: #64748b; }
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.3); }
}

.time-text { font-size: 12.5px; color: #64748b; font-variant-numeric: tabular-nums; }

.action-buttons { display: flex; gap: 6px; }
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 11px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.18s ease;
}
.action-btn:hover { transform: translateY(-1px); }
.action-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.download-btn:hover:not(:disabled) { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); border-color: transparent; color: #fff; }
.download-btn--pay {
  color: #fff;
  border-color: transparent;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.28);
}
.download-btn--pay:hover:not(:disabled) { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.38); }
.preview-btn:hover { background: #f0f9ff; border-color: #0284c7; color: #0369a1; }
.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: btn-spin 0.7s linear infinite;
  display: inline-block;
}
@keyframes btn-spin { to { transform: rotate(360deg); } }

/* 分页 */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px 0 8px;
  flex-wrap: wrap;
}
.page-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.18s ease;
}
.page-btn:hover:not(:disabled) { border-color: #0d9488; color: #0d9488; background: rgba(20, 184, 166, 0.04); }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-numbers { display: flex; align-items: center; gap: 4px; }
.page-num {
  min-width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.18s ease;
  padding: 0 6px;
}
.page-num:hover { border-color: #99f6e4; background: #f0fdfa; color: #0d9488; }
.page-num.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 3px 10px rgba(20, 184, 166, 0.3);
}
.page-ellipsis { color: #94a3b8; padding: 0 4px; font-size: 13px; }
.page-info { margin-left: 12px; font-size: 12.5px; color: #64748b; }
.page-info b { color: #0f172a; font-weight: 700; }

/* 响应式 */
@media (max-width: 1024px) {
  .filter-bar { flex-direction: column; align-items: stretch; }
  .search-box { margin-left: 0; width: 100%; }
  .search-input { flex: 1; width: auto; }
}
@media (max-width: 768px) {
  .page-container { padding: 0; }
  .panel-header, .filter-bar, .table-wrap, .pagination { padding-left: 14px; padding-right: 14px; }
  .order-table thead { display: none; }
  .order-table, .order-table tbody, .order-table tr, .order-table td { display: block; width: 100%; }
  .order-row { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; padding: 8px 14px; }
  .order-row:hover { background: #fff; transform: none; }
  .order-table tbody td {
    border-bottom: 1px solid #f1f5f9;
    padding: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: right;
    gap: 12px;
  }
  .page-info { width: 100%; text-align: center; margin: 8px 0 0; }
}
</style>
