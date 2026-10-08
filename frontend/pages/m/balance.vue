<template>
  <div class="m-balance-page">
    <!-- 筛选 chips -->
    <div class="m-chips" style="padding-bottom:12px">
      <button
        v-for="f in filters"
        :key="f.key"
        class="m-chip"
        :class="{ active: filter === f.key }"
        @click="setFilter(f.key)"
      >{{ f.label }}</button>
    </div>

    <div v-if="loading" class="m-loading">
      <div class="m-spinner"></div>
      <span>加载中...</span>
    </div>

    <section v-else-if="logs.length === 0" class="m-card">
      <div class="m-empty">
        <div class="m-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <p class="m-empty-title">暂无金额变动记录</p>
        <p class="m-empty-desc">账户余额的每一笔变动都会记录在这里</p>
      </div>
    </section>

    <template v-else>
      <section v-for="l in logs" :key="l.id" class="m-card m-log-card">
        <div class="m-log-top">
          <span class="m-tag" :class="l.direction === 'income' ? '' : 'gray'">{{ l.type || '余额变动' }}</span>
          <span class="m-log-amount" :class="l.direction === 'income' ? 'is-in' : 'is-out'">
            {{ l.direction === 'income' ? '+' : '-' }}¥{{ Number(l.amount || 0).toFixed(2) }}
          </span>
        </div>
        <div class="m-log-remark">{{ l.remark || '—' }}</div>
        <div class="m-log-bottom">
          <span>{{ l.time }}</span>
          <span>余额 ¥{{ Number(l.after || 0).toFixed(2) }}</span>
        </div>
      </section>

      <div class="m-pager">
        <button class="m-pager-btn" :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
        <span class="m-pager-info"><b>{{ page }}</b>/{{ pageTotal || 1 }}</span>
        <button class="m-pager-btn" :disabled="page >= pageTotal" @click="goPage(page + 1)">下一页</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({ layout: 'm' })
useHead({ title: '资金明细' })

const api = useApi()
const auth = useAuth()
const login = useLoginModal()

const filters = [
  { key: 'all', label: '全部' },
  { key: 'income', label: '收入' },
  { key: 'expense', label: '支出' },
]

const filter = ref('all')
const logs = ref([])
const loading = ref(false)
const page = ref(1)
const pageSize = 15
const pageTotal = ref(0)

function setFilter(key) {
  filter.value = key
  page.value = 1
  load()
}

function goPage(p) {
  if (p < 1 || p > pageTotal.value || p === page.value) return
  page.value = p
  load()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function load() {
  if (!auth.isLoggedIn.value) {
    logs.value = []
    login.openIfNeeded()
    return
  }
  loading.value = true
  try {
    const res = await api.get('/api/pc/accountLogs', {
      page_no: page.value,
      page_size: pageSize,
      filter: filter.value,
    })
    if (res.ok && res.data) {
      logs.value = Array.isArray(res.data.list) ? res.data.list : []
      pageTotal.value = Number(res.data.page_total || 0)
    } else {
      logs.value = []
      pageTotal.value = 0
    }
  } catch (e) {
    logs.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  auth.restore()
  load()
})
</script>

<style scoped>
.m-log-card { padding: 13px 16px; }
.m-log-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.m-log-amount {
  font-size: 16px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.m-log-amount.is-in { color: #16a34a; }
.m-log-amount.is-out { color: #0f172a; }
.m-log-remark {
  margin-top: 6px;
  font-size: 13px;
  color: #475569;
  word-break: break-word;
}
.m-log-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #f1f5f9;
  font-size: 12px;
  color: #94a3b8;
}
</style>
