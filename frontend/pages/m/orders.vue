<template>
  <div class="m-orders">
    <!-- 订单类型 Tab -->
    <div class="m-type-tabs">
      <button
        v-for="t in TYPES"
        :key="t.key"
        class="m-type-tab"
        :class="{ active: current === t.key }"
        @click="switchType(t.key)"
      >{{ t.label }}</button>
    </div>

    <!-- 状态筛选 + 搜索 -->
    <div class="m-filter">
      <div class="m-chips m-filter-chips">
        <button
          v-for="s in statusTabs"
          :key="s.key"
          class="m-chip"
          :class="{ active: filterStatus === s.key }"
          @click="setStatus(s.key)"
        >{{ s.label }}</button>
      </div>
      <div class="m-search">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="searchInput" type="text" placeholder="搜索订单号或标题" @keyup.enter="handleSearch" />
        <button class="m-search-btn" @click="handleSearch">搜索</button>
      </div>
    </div>

    <!-- 列表 -->
    <div v-if="loading" class="m-loading">
      <div class="m-spinner"></div>
      <span>加载中...</span>
    </div>

    <section v-else-if="orders.length === 0" class="m-card">
      <div class="m-empty">
        <div class="m-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p class="m-empty-title">暂无{{ currentLabel }}订单</p>
        <p class="m-empty-desc">去创建你的第一笔订单吧</p>
        <NuxtLink to="/m/create" class="m-btn m-btn-primary m-btn-sm" style="margin-top:10px">立即创作</NuxtLink>
      </div>
    </section>

    <template v-else>
      <section v-for="o in orders" :key="o.order_sn || o.order_no" class="m-card m-order-card">
        <div class="m-order-top">
          <span class="m-order-title">{{ o._title || '余额充值' }}</span>
          <span class="m-status" :class="o._statusClass">{{ o._statusText }}</span>
        </div>
        <div class="m-order-sn" @click="copySn(o._sn)">{{ o._sn }}</div>
        <div class="m-order-meta">
          <span class="m-order-time">{{ o._time }}</span>
          <span v-if="o._amount != null" class="m-price m-order-amount"><span class="m-price-symbol">¥</span>{{ Number(o._amount).toFixed(2) }}</span>
        </div>
        <div v-if="o._actions.length" class="m-order-actions">
          <button
            v-for="act in o._actions"
            :key="act.label"
            class="m-btn m-btn-sm"
            :class="act.cls"
            @click="act.run(o)"
          >{{ act.label }}</button>
        </div>
      </section>

      <!-- 分页 -->
      <div class="m-pager">
        <button class="m-pager-btn" :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
        <span class="m-pager-info"><b>{{ page }}</b>/{{ totalPages || 1 }}</span>
        <button class="m-pager-btn" :disabled="page >= totalPages" @click="goPage(page + 1)">下一页</button>
      </div>
    </template>

    <!-- 后付费下载确认弹层 -->
    <Transition name="m-fade">
      <div v-if="confirmOrder" class="m-sheet-mask" @click.self="confirmOrder = null">
        <div class="m-sheet">
          <div class="m-sheet-head">
            <span class="m-sheet-title">确认下载</span>
            <button class="m-sheet-close" @click="confirmOrder = null">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <p class="m-confirm-text">
            {{ confirmOrder._title }}
            <template v-if="confirmOrder._amount != null">（应付 <b class="m-price"><span class="m-price-symbol">¥</span>{{ Number(confirmOrder._amount).toFixed(2) }}</b>）</template>
            ，下载后将通过余额扣除费用，是否继续？
          </p>
          <button class="m-btn m-btn-primary m-btn-block" :disabled="downloading" @click="doPayDownload">
            {{ downloading ? '处理中...' : '确认支付下载' }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '我的订单' })

const api = useApi()
const auth = useAuth()
const toast = useToast()
const login = useLoginModal()
const route = useRoute()
const router = useRouter()

// 订单类型定义：key 同时支持 URL query 定位（/m/orders?type=ppt）
const TYPES = [
  { key: 'paper',   label: '论文' },
  { key: 'ppt',     label: 'PPT' },
  { key: 'write',   label: '写作' },
  { key: 'check',   label: '降重' },
  { key: 'autodoc', label: '排版' },
  { key: 'recharge',label: '充值' },
]

const statusTabs = [
  { key: 'all', label: '全部' },
  { key: 'pending', label: '待处理' },
  { key: 'processing', label: '进行中' },
  { key: 'completed', label: '已完成' },
  { key: 'failed', label: '失败' },
]

const current = ref('paper')
const currentLabel = computed(() => TYPES.find(t => t.key === current.value)?.label || '')
const filterStatus = ref('all')
const searchKeyword = ref('')
const searchInput = ref('')
const orders = ref([])
const loading = ref(false)
const page = ref(1)
const pageSize = 10
const total = ref(0)
const totalPages = ref(0)
const downloading = ref(false)
const confirmOrder = ref(null)

let pollTimer = null

function switchType(key) {
  current.value = key
  filterStatus.value = 'all'
  searchKeyword.value = ''
  searchInput.value = ''
  page.value = 1
  router.replace({ query: { type: key } })
  load()
}

function setStatus(key) {
  filterStatus.value = key
  page.value = 1
  load()
}

function handleSearch() {
  searchKeyword.value = searchInput.value.trim()
  page.value = 1
  load()
}

function goPage(p) {
  if (p < 1 || p > totalPages.value || p === page.value) return
  page.value = p
  load()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

/* ---------- 各类型加载与行归一化 ---------- */
function statusClassFromGen(gen) {
  if (gen === 2) return 'completed'
  if (gen === 3 || gen === 5) return 'failed'
  if (gen === 0) return 'pending'
  return 'processing' // 1/4/6/7
}
function statusTextFromGen(gen) {
  if (gen === 2) return '已完成'
  if (gen === 3 || gen === 5) return '失败'
  if (gen === 0) return '待生成'
  return '生成中'
}

function normalize(type, o) {
  const n = { ...o, _actions: [] }
  if (type === 'paper' || type === 'write' || type === 'autodoc') {
    n._title = o.title || ''
    n._sn = o.order_sn || ''
    n._amount = o.amount
    n._time = o.create_time || ''
    n._statusClass = statusClassFromGen(o.gen_status)
    n._statusText = o.gen_status_text || statusTextFromGen(o.gen_status)
    if (o.pay_status === 1 && o.pay_status != null) {
      // 后付费已支付补充标记（autodoc/ppt），普通单不重复展示
      if (type !== 'paper' && type !== 'write') {
        n._statusClass = n._statusClass
        n._statusText = n._statusText
      }
    }
    if (type === 'autodoc' && o.gen_status === 2) {
      n._actions.push({
        label: o.pay_status === 1 ? '下载' : '支付下载',
        cls: o.pay_status === 1 ? 'm-btn-primary' : 'm-btn-warn',
        run: () => handlePostpaid(o),
      })
    } else if ((type === 'paper' || type === 'write') && o.doc_url && o.gen_status === 2) {
      n._actions.push({ label: '下载', cls: 'm-btn-primary', run: () => openDownload(o.doc_url) })
    }
    return n
  }
  if (type === 'ppt') {
    n._title = o.title || ''
    n._sn = o.order_sn || ''
    n._amount = o.amount
    n._time = o.create_time || ''
    n._statusClass = statusClassFromGen(o.gen_status)
    n._statusText = statusTextFromGen(o.gen_status)
    if (o.gen_status === 2) {
      n._actions.push({
        label: o.pay_status === 1 ? '下载' : '支付下载',
        cls: o.pay_status === 1 ? 'm-btn-primary' : 'm-btn-warn',
        run: () => handlePostpaid(o),
      })
      if (o.preview_url) {
        n._actions.push({ label: '预览', cls: 'm-btn-plain', run: () => window.open(o.preview_url, '_blank') })
      }
    }
    return n
  }
  if (type === 'check') {
    n._title = o.file_name || o.title || ''
    n._sn = o.order_sn || o.order_no || ''
    n._amount = o.amount
    n._time = o.create_time || ''
    n._statusClass = o.status_class || statusClassFromGen(o.status)
    n._statusText = o.status_text || statusTextFromGen(o.status)
    if (o.status === 2 && o.doc_url) {
      n._actions.push({ label: '下载', cls: 'm-btn-primary', run: () => openDownload(o.doc_url) })
    }
    return n
  }
  // recharge
  n._title = '余额充值'
  n._sn = o.order_sn || o.order_no || ''
  n._amount = o.amount
  n._time = o.create_time || ''
  if (o.status_text) {
    n._statusText = o.status_text
    n._statusClass = o.status_class || 'neutral'
  } else {
    n._statusClass = Number(o.pay_status) === 1 ? 'paid' : 'unpaid'
    n._statusText = Number(o.pay_status) === 1 ? '已到账' : '待支付'
  }
  return n
}

async function load(silent = false) {
  if (!silent) loading.value = true
  try {
    const t = current.value
    const params = {
      status: filterStatus.value,
      keyword: searchKeyword.value,
      page: page.value,
      page_size: pageSize,
    }
    let res
    if (t === 'check') {
      res = await api.get('/api/check/orderList', params)
    } else {
      res = await api.post(`/api/order/${t}List`, params)
    }
    if (res.ok) {
      const list = Array.isArray(res.data?.list) ? res.data.list : []
      orders.value = list.map(o => normalize(t, o))
      total.value = Number(res.data?.total || 0)
      totalPages.value = Number(res.data?.total_pages || 0) || Math.ceil(total.value / pageSize)
    } else if (!silent) {
      toast.error(res.msg || '加载失败')
    }
  } catch (e) {
    if (!silent) toast.error('网络错误，请稍后重试')
  } finally {
    if (!silent) loading.value = false
  }
}

// 存在未完成订单时静默轮询（10 秒），保持状态及时更新
const hasPending = computed(() => orders.value.some(o => {
  if (current.value === 'check') return o.status === 0 || o.status === 1
  if (current.value === 'recharge') return Number(o.pay_status) !== 1
  return o.gen_status === 0 || o.gen_status === 1 || o.gen_status === 4
}))
function startPoll() {
  stopPoll()
  pollTimer = setInterval(() => {
    if (!document.hidden && hasPending.value) load(true)
  }, 10000)
}
function stopPoll() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null }
}

/* ---------- 下载 ---------- */
async function copySn(sn) {
  if (!sn) return
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(sn)
    } else {
      const ta = document.createElement('textarea')
      ta.value = sn
      ta.style.cssText = 'position:fixed;opacity:0'
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

// 新窗口下载：先同步开空白窗口保留用户手势，触发后自动关闭
function openDownload(url) {
  if (!url) { toast.info('文件尚未生成，请稍后再试'); return }
  const win = window.open('', '_blank')
  if (!win) {
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
  closeWin(win)
}
function closeWin(win) {
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

// PPT / 排版 后付费下载：已支付直接下载，未支付弹确认
function handlePostpaid(order) {
  if (Number(order.pay_status) === 1) {
    doPayDownload(order)
    return
  }
  confirmOrder.value = order
}

async function doPayDownload(order) {
  const target = order || confirmOrder.value
  if (!target || downloading.value) return
  downloading.value = true
  const win = window.open('', '_blank')
  try {
    const endpoint = current.value === 'ppt' ? '/api/ppt/downloadPpt' : '/api/autodoc/downloadDoc'
    const res = await api.post(endpoint, { order_sn: target.order_sn })
    if (res.ok && res.data?.url) {
      if (res.data.charged) toast.success(`已扣费 ¥${Number(target.amount || 0).toFixed(2)}`)
      if (win) { win.location.href = res.data.url; closeWin(win) }
      else openDownload(res.data.url)
      confirmOrder.value = null
      load(true)
    } else {
      if (win) win.close()
      toast.error(res.msg || '下载失败，请稍后重试')
    }
  } catch (e) {
    if (win) win.close()
    toast.error('网络错误，请稍后重试')
  } finally {
    downloading.value = false
  }
}

onMounted(() => {
  auth.restore()
  // 支持 /m/orders?type=ppt 直达指定类型
  const q = String(route.query.type || '')
  if (TYPES.some(t => t.key === q) && q !== 'paper') {
    current.value = q
    router.replace({ query: { type: q } })
  }
  if (!auth.isLoggedIn.value) {
    // 未登录允许预览空列表，不发请求（避免触发登录失效提示），仅给出登录引导
    login.openIfNeeded()
    return
  }
  load()
  startPoll()
})

onUnmounted(stopPoll)
</script>

<style scoped>
.m-type-tabs {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding: 2px 2px 10px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.m-type-tabs::-webkit-scrollbar { display: none; }
.m-type-tab {
  flex-shrink: 0;
  height: 36px;
  padding: 0 18px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 13.5px;
  font-weight: 600;
}
.m-type-tab.active {
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--primary-600, #0d9488));
  border-color: transparent;
  color: #fff;
  box-shadow: 0 3px 10px rgba(13, 148, 136, 0.25);
}

.m-filter {
  margin-bottom: 12px;
}
.m-filter-chips {
  margin-bottom: 8px;
}
.m-filter-chips .m-chip {
  min-height: 32px;
  padding: 5px 13px;
  font-size: 12.5px;
}
.m-search {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 42px;
  padding: 0 8px 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  color: #94a3b8;
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
.m-search input::placeholder { color: #94a3b8; }
.m-search-btn {
  height: 32px;
  padding: 0 14px;
  border: none;
  border-radius: 9px;
  background: linear-gradient(135deg, var(--primary-500, #14b8a6), var(--primary-600, #0d9488));
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

.m-order-card { padding: 14px 16px; }
.m-order-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.m-order-title {
  flex: 1;
  min-width: 0;
  font-size: 14.5px;
  font-weight: 600;
  color: #0f172a;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.m-order-sn {
  margin-top: 6px;
  font-family: 'SF Mono', Consolas, monospace;
  font-size: 11.5px;
  color: #94a3b8;
  word-break: break-all;
}
.m-order-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #f1f5f9;
}
.m-order-time { font-size: 12px; color: #94a3b8; }
.m-order-amount { font-size: 15px; }
.m-order-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.m-order-actions .m-btn { flex: 1; }

.m-confirm-text {
  margin: 0 0 16px;
  font-size: 14px;
  color: #475569;
  line-height: 1.7;
}
</style>
