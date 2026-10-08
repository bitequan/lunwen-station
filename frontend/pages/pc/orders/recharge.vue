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
            placeholder="搜索订单号"
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
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <p class="empty-title">暂无充值记录</p>
          <p class="empty-desc">充值后可在此查看记录</p>
          <NuxtLink to="/pc/user?tab=wallet" class="btn btn-primary">立即充值</NuxtLink>
        </div>

        <div v-else class="table-wrap">
          <table class="order-table">
            <thead>
              <tr>
                <th class="col-sn">订单号</th>
                <th class="col-type">类型</th>
                <th class="col-payway">支付方式</th>
                <th class="col-amount">充值金额</th>
                <th class="col-status">状态</th>
                <th class="col-time">创建时间</th>
                <th class="col-paytime">支付时间</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.order_sn" class="order-row">
                <td class="col-sn">
                  <span class="order-sn-text" :title="order.order_sn" @click="copyOrderSn(order.order_sn)">
                    {{ order.order_sn }}
                  </span>
                </td>
                <td class="col-type">
                  <span class="type-tag">账户充值</span>
                </td>
                <td class="col-payway">
                  <span class="payway-text">{{ order.pay_way_text }}</span>
                </td>
                <td class="col-amount">
                  <span class="amount-text highlight">+¥{{ order.amount.toFixed(2) }}</span>
                </td>
                <td class="col-status">
                  <span class="status-tag" :class="order.status_class">
                    <span class="status-dot"></span>
                    {{ order.gen_status_text }}
                  </span>
                </td>
                <td class="col-time">
                  <span class="time-text">{{ order.create_time }}</span>
                </td>
                <td class="col-paytime">
                  <span class="time-text">{{ order.pay_time || '-' }}</span>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

definePageMeta({ layout: 'console' })
useHead({ title: '充值订单 - AI写作助手' })

const api = useApi()
const toast = useToast()

const orders = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = 10
const total = ref(0)
const totalPages = ref(0)

const filterStatus = ref('all')
const searchKeyword = ref('')
const searchInput = ref('')

let pollTimer = null

const statusTabs = [
  { key: 'all', label: '全部' },
  { key: 'paid', label: '已完成' },
  { key: 'pending', label: '待支付' },
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

// 是否存在待支付的订单
const hasPendingOrders = computed(() => {
  return orders.value.some(o => o.pay_status === 0)
})

async function loadOrders(silent = false) {
  if (!silent) loading.value = true
  try {
    const res = await api.post('/api/order/rechargeList', {
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
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3);
}
.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(34, 197, 94, 0.4);
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
.filter-chip:hover { border-color: #86efac; background: #f0fdf4; color: #16a34a; }
.filter-chip.active {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 3px 10px rgba(34, 197, 94, 0.25);
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
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
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
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
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
  border-top-color: #16a34a;
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
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.04) 100%);
  color: #16a34a;
  margin-bottom: 8px;
  border: 1px solid #bbf7d0;
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
.order-row:hover { background: linear-gradient(90deg, rgba(34, 197, 94, 0.03) 0%, transparent 100%); }
.order-row:hover td { border-bottom-color: #bbf7d0; }
.order-row:last-child td { border-bottom: none; }

.col-sn { width: 20%; }
.col-type { width: 12%; }
.col-payway { width: 12%; }
.col-amount { width: 13%; }
.col-status { width: 10%; }
.col-time { width: 16%; }
.col-paytime { width: 17%; }

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
.order-sn-text:hover { color: #16a34a; }

.type-tag {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 600;
  background: rgba(34, 197, 94, 0.1);
  color: #15803d;
}

.payway-text { font-size: 12.5px; color: #475569; font-weight: 500; }

.amount-text { font-weight: 700; color: #0f172a; font-size: 14px; }
.amount-text.highlight { color: #16a34a; }

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
.status-tag.completed { background: rgba(34, 197, 94, 0.1); color: #15803d; }

.time-text { font-size: 12.5px; color: #64748b; font-variant-numeric: tabular-nums; }

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
.page-btn:hover:not(:disabled) { border-color: #16a34a; color: #16a34a; background: rgba(34, 197, 94, 0.04); }
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
.page-num:hover { border-color: #86efac; background: #f0fdf4; color: #16a34a; }
.page-num.active {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
}
.page-ellipsis { color: #94a3b8; padding: 0 4px; font-size: 13px; }
.page-info { margin-left: 12px; font-size: 12.5px; color: #64748b; }
.page-info b { color: #0f172a; font-weight: 700; }

@media (max-width: 1024px) {
  .filter-bar { flex-direction: column; align-items: stretch; }
  .search-box { margin-left: 0; width: 100%; }
  .search-input { flex: 1; width: auto; }
}
@media (max-width: 768px) {
  .page-container { padding: 0; }
  .page-header { padding: 16px; }
  .stats-strip { gap: 14px; }
  .stat-value { font-size: 16px; }
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
