<template>
  <div class="user-center">
      <!-- 未登录拦截提示 -->
      <ClientOnly>
        <div v-if="!isLoggedIn && ready" class="auth-gate">
          <div class="auth-card">
            <h3>请先登录</h3>
            <p>登录后即可查看订单、收益与个人中心</p>
            <div class="auth-actions">
              <button type="button" class="btn btn-primary" @click="login.open()">前往登录</button>
              <NuxtLink to="/pc" class="btn btn-outline">返回首页</NuxtLink>
            </div>
          </div>
        </div>
      </ClientOnly>

      <template v-if="isLoggedIn">
      <div v-if="activeMenu === 'overview'" class="dashboard">
        <div class="stats-row">
          <div v-for="(s, i) in stats" :key="i" class="stat-card" :class="[s.theme, { 'is-zero': s.isZero }]">
            <div class="stat-main">
              <span class="stat-label">{{ s.label }}</span>
              <span class="stat-value">{{ s.value }}</span>
              <span class="stat-trend" :class="s.trendType">{{ s.trend }}</span>
              <NuxtLink v-if="s.ctaLink" :to="s.ctaLink" class="stat-cta">
                {{ s.ctaLabel }}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </NuxtLink>
            </div>
            <div class="stat-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="s.icon"></svg>
            </div>
          </div>
        </div>

        <div class="dashboard-grid">
          <div class="dashboard-main">
            <section class="panel chart-panel span-2">
              <div class="panel-header">
                <div>
                  <h3>近 7 日充值 / 消费趋势</h3>
                  <p class="panel-desc">数据实时统计，单位：元 · 绿=消费 / 橙=充值</p>
                </div>
                <div class="chart-legend">
                  <span class="legend-item"><span class="legend-dot" style="background:#f97316"></span>充值</span>
                  <span class="legend-item"><span class="legend-dot" style="background:#0d9488"></span>消费</span>
                  <span class="badge">实时</span>
                </div>
              </div>
              <div v-if="!dashboardLoading && chartAllZero" class="chart-empty">
                <svg width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="20" x2="18" y2="10"/>
                  <line x1="12" y1="20" x2="12" y2="4"/>
                  <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <p>还没有充值或消费记录</p>
                <p class="chart-empty-sub">开始使用后，这里将以双折线展示您每日的资金动向</p>
                <NuxtLink to="/pc/user?tab=recharge" class="btn btn-primary btn-sm chart-empty-btn">去充值体验</NuxtLink>
              </div>
              <div v-else class="trend-chart" @mousemove="handleChartMove" @mouseleave="chartHover = -1">
                <svg viewBox="0 0 800 220" preserveAspectRatio="none" class="trend-svg">
                  <defs>
                    <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#0d9488" stop-opacity="0.35" />
                      <stop offset="100%" stop-color="#0d9488" stop-opacity="0.02" />
                    </linearGradient>
                    <linearGradient id="lineGradient" x1="0" y1="0" x2="1" y2="0">
                      <stop offset="0%" stop-color="#14b8a6" />
                      <stop offset="100%" stop-color="#0d9488" />
                    </linearGradient>
                  </defs>

                  <g class="grid-lines">
                    <line v-for="n in 4" :key="n" x1="20" :y1="35 + (n - 1) * 45" x2="780" :y2="35 + (n - 1) * 45" />
                  </g>

                  <path :d="areaPath" fill="url(#areaGradient)" />
                  <!-- 充值曲线（橙色虚线） -->
                  <path :d="rechargeLinePath" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="6 4" opacity="0.95" />
                  <!-- 消费曲线（实线） -->
                  <path :d="linePath" fill="none" stroke="url(#lineGradient)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                  <!-- 消费节点圆点 -->
                  <circle
                    v-for="(p, i) in chartPoints"
                    :key="'cp-'+i"
                    :cx="p.x"
                    :cy="p.y"
                    r="5"
                    fill="#fff"
                    stroke="#0d9488"
                    stroke-width="2.5"
                    :class="{ active: chartHover === i }"
                  />
                  <!-- 充值节点小方块 -->
                  <rect
                    v-for="(p, i) in rechargePoints"
                    :key="'rp-'+i"
                    :x="p.x - 3.5"
                    :y="p.y - 3.5"
                    width="7"
                    height="7"
                    rx="1.5"
                    fill="#fff"
                    stroke="#f97316"
                    stroke-width="2"
                    :opacity="chartHover === i ? 1 : 0.9"
                  />

                  <g v-if="chartHover >= 0">
                    <line :x1="chartPoints[chartHover].x" y1="35" :x2="chartPoints[chartHover].x" y2="180" class="hover-line" />
                    <rect
                      :x="Math.max(10, Math.min(650, chartPoints[chartHover].x - 70))"
                      y="4"
                      width="140"
                      height="62"
                      rx="8"
                      class="hover-card"
                    />
                    <text
                      :x="Math.max(10, Math.min(650, chartPoints[chartHover].x - 70)) + 70"
                      y="24"
                      text-anchor="middle"
                      class="hover-date"
                    >{{ chartLabels[chartHover] }}</text>
                    <text
                      :x="Math.max(10, Math.min(650, chartPoints[chartHover].x - 70)) + 70"
                      y="43"
                      text-anchor="middle"
                      class="hover-value"
                      style="fill:#f97316"
                    >充值 ¥{{ Number(chartRechargeValues[chartHover] || 0).toLocaleString() }}</text>
                    <text
                      :x="Math.max(10, Math.min(650, chartPoints[chartHover].x - 70)) + 70"
                      y="60"
                      text-anchor="middle"
                      class="hover-value"
                      style="fill:#0d9488"
                    >消费 ¥{{ Number(chartValues[chartHover] || 0).toLocaleString() }}</text>
                  </g>
                </svg>

                <div class="x-labels">
                  <span v-for="(label, i) in chartLabels" :key="i">{{ label }}</span>
                </div>
              </div>
            </section>

            <!-- 我的产出概览：真实订单量 + 模板数（新模块补左格，让 2 列布局平衡） -->
            <section class="panel my-output-panel">
              <div class="panel-header">
                <div>
                  <h3>我的产出</h3>
                  <p class="panel-desc">论文 / PPT 已支付订单与私有模板的真实统计</p>
                </div>
              </div>
              <div v-if="dashboardLoading" class="output-grid">
                <div v-for="i in 3" :key="i" class="output-card skeleton-card">
                  <div class="output-icon skeleton-block" style="width:40px;height:40px;flex:0 0 40px"></div>
                  <div class="output-info">
                    <div class="skeleton-line w-60" style="height:10px;margin-bottom:8px"></div>
                    <div class="skeleton-line w-40" style="height:18px"></div>
                  </div>
                </div>
              </div>
              <div v-else class="output-grid">
                <NuxtLink to="/pc/orders/paper" class="output-card output-card-paper">
                  <div class="output-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  </div>
                  <div class="output-info">
                    <b>{{ dashboard.my_paper_order_count || 0 }}</b>
                    <span>论文订单</span>
                  </div>
                </NuxtLink>
                <NuxtLink to="/pc/orders/ppt" class="output-card output-card-ppt">
                  <div class="output-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><line x1="6" y1="8" x2="18" y2="8"/><line x1="6" y1="11" x2="15" y2="11"/></svg>
                  </div>
                  <div class="output-info">
                    <b>{{ dashboard.my_ppt_order_count || 0 }}</b>
                    <span>PPT 订单</span>
                  </div>
                </NuxtLink>
                <NuxtLink to="/pc/user?tab=templates" class="output-card output-card-tpl">
                  <div class="output-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v4H4z"/><path d="M4 10h16v10H4z"/><path d="M9 1v3"/><path d="M15 1v3"/></svg>
                  </div>
                  <div class="output-info">
                    <b>{{ dashboard.my_template_count || 0 }}</b>
                    <span>私有模板</span>
                  </div>
                </NuxtLink>
              </div>
              <div class="output-footer">
                <NuxtLink to="/pc/orders/paper" class="output-footer-link">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  新建论文订单
                </NuxtLink>
                <NuxtLink to="/pc/orders/ppt" class="output-footer-link">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  新建 PPT 订单
                </NuxtLink>
              </div>
            </section>

            <!-- 近 30 天消费类型分布（真实账变数据按 change_type 聚合） -->
            <section class="panel consume-dist-panel">
              <div class="panel-header">
                <div>
                  <h3>消费类型分布</h3>
                  <p class="panel-desc">近 30 天真实账变分类 · 合计 <b style="color:#0f172a">¥{{ fmtMoney(dashboard.consume_dist_total_30d) }}</b></p>
                </div>
              </div>
              <div v-if="dashboardLoading" class="service-bars">
                <div v-for="i in 5" :key="i" class="service-bar-row">
                  <div class="skeleton-line w-60" style="height:12px;margin-bottom:10px"></div>
                  <div class="skeleton-line w-100" style="height:10px"></div>
                </div>
              </div>
              <div v-else-if="!dashboard.consume_dist_30d || dashboard.consume_dist_30d.length === 0" class="empty-state consume-empty">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-6"/></svg>
                <p>近 30 天暂无消费</p>
                <p class="notice-empty-sub">使用论文 / PPT / AI 降重后，这里会展示各项消费占比</p>
                <NuxtLink to="/pc/outline" class="btn btn-outline btn-sm">去生成试试</NuxtLink>
              </div>
              <div v-else class="service-bars">
                <div v-for="s in consumeDistWithColor" :key="s.key" class="service-bar-row">
                  <div class="service-bar-info">
                    <span class="service-bar-name">
                      <span class="svc-dot" :style="{ background: s.color }"></span>
                      {{ s.type }}
                    </span>
                    <span class="service-bar-value">{{ s.cnt }} 笔 · ¥{{ fmtMoney(s.value) }} · {{ s.percent }}%</span>
                  </div>
                  <div class="service-bar-track">
                    <div class="service-bar-fill" :style="{ width: s.percent + '%', background: s.color }"></div>
                  </div>
                </div>
              </div>
            </section>

            <!-- 消费洞察：基于真实账变数据的派生指标 -->
            <section class="panel consume-insight-panel">
              <div class="panel-header">
                <div>
                  <h3>消费洞察</h3>
                  <p class="panel-desc">基于真实账变数据的派生指标 · 补充消费类型分布与资金变动</p>
                </div>
              </div>
              <div v-if="dashboardLoading" class="insight-body">
                <div class="skeleton-line w-100" style="height:14px;margin-bottom:12px"></div>
                <div class="skeleton-line w-100" style="height:22px;margin-bottom:18px"></div>
                <div v-for="i in 4" :key="i" class="insight-metric-skeleton">
                  <div class="skeleton-line w-50" style="height:11px;margin-bottom:8px"></div>
                  <div class="skeleton-line w-70" style="height:18px"></div>
                </div>
              </div>
              <div v-else class="insight-body">
                <!-- 充值 vs 消费 比例条 -->
                <div class="insight-ratio-section">
                  <div class="insight-ratio-header">
                    <span class="ratio-tag ratio-tag-recharge">充值 ¥{{ fmtMoney(dashboard.total_recharge) }}</span>
                    <span class="ratio-tag ratio-tag-consume">消费 ¥{{ fmtMoney(dashboard.total_consume) }}</span>
                  </div>
                  <div class="insight-ratio-bar">
                    <div class="ratio-fill ratio-fill-recharge" :style="{ width: rechargeRatio + '%' }"></div>
                    <div class="ratio-fill ratio-fill-consume" :style="{ width: consumeRatio + '%' }"></div>
                  </div>
                  <div class="insight-ratio-legend">
                    <span>充值占比 {{ rechargeRatio }}%</span>
                    <span>消费占比 {{ consumeRatio }}%</span>
                  </div>
                </div>

                <!-- 派生指标网格 -->
                <div class="insight-metrics">
                  <div class="insight-metric">
                    <span class="metric-label">平均单笔消费</span>
                    <span class="metric-value">¥{{ fmtMoney(avgConsumePerTx) }}</span>
                  </div>
                  <div class="insight-metric">
                    <span class="metric-label">净流入</span>
                    <span class="metric-value" :class="netInflow >= 0 ? 'positive' : 'negative'">{{ netInflow >= 0 ? '+' : '' }}¥{{ fmtMoney(netInflow) }}</span>
                  </div>
                  <div class="insight-metric">
                    <span class="metric-label">赠送金额</span>
                    <span class="metric-value gift">¥{{ fmtMoney(dashboard.total_gift) }}</span>
                  </div>
                  <div class="insight-metric">
                    <span class="metric-label">总交易笔数</span>
                    <span class="metric-value">{{ totalTxCount }} 笔</span>
                  </div>
                </div>
              </div>
            </section>

            <section class="panel orders-panel">
              <div class="panel-header">
                <div>
                  <h3>最近资金变动</h3>
                  <p class="panel-desc">充值、消费、退款、赠送等余额变动 · 点击底部"资金明细"查看全部</p>
                </div>
                <NuxtLink to="/pc/user?tab=balance-log" class="text-link">查看全部 →</NuxtLink>
              </div>

              <div class="order-tabs">
                <button
                  v-for="tab in orderTabs"
                  :key="tab.key"
                  class="order-tab"
                  :class="{ active: orderFilter === tab.key }"
                  @click="orderFilter = tab.key"
                >
                  {{ tab.label }}
                  <span class="tab-count">{{ orderCounts[tab.key] || 0 }}</span>
                </button>
              </div>

              <div v-if="dashboardLoading" class="order-list">
                <div v-for="i in 4" :key="i" class="order-item skeleton-item">
                  <div class="order-icon skeleton-block"></div>
                  <div class="order-info">
                    <div class="order-top"><div class="skeleton-line w-60"></div></div>
                    <div class="order-time"><div class="skeleton-line w-40"></div></div>
                  </div>
                  <div class="order-right">
                    <div class="skeleton-line w-20"></div>
                    <div class="skeleton-line w-20"></div>
                  </div>
                </div>
              </div>
              <div v-else-if="filteredRecentOrders.length === 0" class="empty-state" style="min-height:180px">
                <p>暂无资金变动记录</p>
                <p class="notice-empty-sub">充值或消费后，这里会显示最近的变动流水</p>
              </div>
              <div v-else class="order-list">
                <div
                  v-for="o in filteredRecentOrders"
                  :key="o.id"
                  class="order-item"
                >
                  <div class="order-icon" :class="o.iconClass">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="o.is_income ? rechargeDonutSliceIcon : consumeDonutSliceIcon"></svg>
                  </div>
                  <div class="order-info">
                    <div class="order-top">
                      <span class="order-type">{{ o.type }}</span>
                      <span class="order-id">{{ o.sn || ('#ID' + o.id) }}</span>
                    </div>
                    <div class="order-time">
                      {{ o.time || '—' }}
                      <span v-if="o.remark" style="color:#64748b; margin-left:6px" :title="o.remark"> · {{ String(o.remark).slice(0, 18) }}{{ String(o.remark).length > 18 ? '…' : '' }}</span>
                    </div>
                  </div>
                  <div class="order-right">
                    <span class="order-amount" :style="{ color: o.is_income ? '#0f766e' : '#b91c1c' }">{{ o.amount_text }}</span>
                    <span class="status" :class="o.statusClass">{{ o.status }}</span>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <aside class="dashboard-side">
            <section class="panel profile-panel">
              <ClientOnly>
                <div class="profile-head">
                  <div class="profile-avatar">{{ displayNameInitial }}</div>
                  <div class="profile-head-info">
                    <div class="profile-name-row">
                      <span class="profile-name">{{ displayName }}</span>
                      <span class="level-chip" :class="'lv-' + (auth.user.value?.level || 1)">{{ auth.user.value?.level_text || '一级代理' }}</span>
                    </div>
                    <span class="profile-id">{{ displayAccount || '—' }}</span>
                  </div>
                </div>
                <template #fallback>
                  <div class="profile-head">
                    <div class="profile-avatar">U</div>
                    <div class="profile-head-info">
                      <div class="profile-name-row">
                        <span class="profile-name">用户</span>
                        <span class="level-chip lv-1">一级代理</span>
                      </div>
                      <span class="profile-id">{{ displayAccount || '—' }}</span>
                    </div>
                  </div>
                </template>
              </ClientOnly>

              <div class="profile-balance">
                <span class="balance-label">账户余额</span>
                <span class="balance-amount">¥ {{ userMoneyText }}</span>
              </div>

              <dl class="profile-info-list">
                <div class="profile-info-row">
                  <dt>账号</dt>
                  <dd>{{ displayAccount || '—' }}</dd>
                </div>
                <div class="profile-info-row">
                  <dt>注册时间</dt>
                  <dd>{{ registerDate }}</dd>
                </div>
              </dl>

              <div class="profile-actions">
                <button class="btn btn-primary" @click="router.push('/pc/user?tab=recharge')">充值</button>
                <button class="btn btn-outline" @click="handleLogout">退出登录</button>
              </div>
            </section>

            <section class="panel notice-center-panel">
              <div class="panel-header">
                <div>
                  <h3>公告中心</h3>
                  <p class="panel-desc">系统与上级公告，点击查看详情</p>
                </div>
              </div>
              <div class="notice-tabs">
                <button
                  class="notice-tab"
                  :class="{ active: noticeTab === 'system' }"
                  @click="switchNoticeTab('system')"
                >
                  系统公告
                  <span class="notice-tab-count">{{ notices.length }}</span>
                </button>
              </div>
              <div class="notice-list">
                <!-- 系统公告 -->
                <template v-if="noticeTab === 'system'">
                  <div v-if="noticeLoading" class="notice-empty">加载中...</div>
                  <div v-else-if="notices.length === 0" class="notice-empty">暂无系统公告</div>
                  <div
                    v-for="n in notices"
                    :key="'sys-' + n.id"
                    class="notice-item"
                    @click="openNotice(n)"
                  >
                    <div class="notice-dot system"></div>
                    <div class="notice-body">
                      <p>{{ n.title }}</p>
                      <span>{{ n.time }}</span>
                    </div>
                  </div>
                </template>
              </div>
            </section>
          </aside>
        </div>

        <footer class="page-footer">
          <p>© 2024–2026 郑州比特泉网络科技有限公司 版权所有</p>
          <p>
            <a href="https://beian.miit.gov.cn" target="_blank" rel="noopener">豫ICP备2024046993号-4</a>
          </p>
        </footer>
      </div>

      <div v-else-if="activeMenu === 'outlines'" class="dashboard">
        <section class="panel single-panel">
          <div class="panel-header">
            <div>
              <h3>我的大纲</h3>
              <p class="panel-desc">历史生成的大纲记录，可恢复后继续编辑或下单</p>
            </div>
          </div>
          <div v-if="outlineLoading" class="empty-state">
            <p>加载中…</p>
          </div>
          <div v-else-if="outlineList.length === 0" class="empty-state">
            <p>暂无大纲记录，去生成第一篇吧</p>
          </div>
          <div v-else class="order-list">
            <div
              v-for="item in outlineList"
              :key="item.outline_no"
              class="order-item"
              style="cursor:pointer"
              @click="restoreOutline(item)"
            >
              <div class="order-icon" :class="item.source === 2 ? 'warning' : 'primary'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
              </div>
              <div class="order-info">
                <div class="order-top">
                  <span class="order-type">{{ item.title || '未命名大纲' }}</span>
                  <span class="order-id">{{ item.outline_no }}</span>
                </div>
                <div class="order-time">
                  {{ item.degree || '—' }} · {{ item.profession || '—' }} · {{ item.words || '—' }}字 · {{ item.model_name || item.model }}
                  <span v-if="item.is_ordered" style="color:#52c41a"> · 已下单</span>
                </div>
              </div>
              <div class="order-right">
                <span class="status" :class="item.is_ordered ? 'success' : 'warning'">{{ item.is_ordered ? '已下单' : '待下单' }}</span>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div v-else-if="activeMenu === 'templates'" class="dashboard">
        <section class="panel single-panel">
          <div class="panel-header">
            <div>
              <h3>我的模板</h3>
              <p class="panel-desc">保存的自定义排版模板，可继续编辑或删除</p>
            </div>
            <button class="btn btn-primary" @click="goCreateTemplate">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              新建模板
            </button>
          </div>
          <!-- 加载中:骨架屏 -->
          <div v-if="templateLoading" class="template-list">
            <div v-for="i in 5" :key="i" class="template-card skeleton-card">
              <div class="template-cover skeleton-block"></div>
              <div class="template-info">
                <div class="skeleton-line w-60"></div>
                <div class="skeleton-line w-40"></div>
              </div>
              <div class="template-actions">
                <div class="skeleton-btn"></div>
                <div class="skeleton-btn"></div>
              </div>
            </div>
          </div>
          <!-- 空状态 -->
          <div v-else-if="templateList.length === 0" class="empty-state">
            <p>暂无模板，点击右上角“新建模板”开始创建</p>
          </div>
          <!-- 模板列表 -->
          <div v-else class="template-list">
            <div v-for="tpl in templateList" :key="tpl.id" class="template-card">
              <div class="template-cover">
                <img v-if="tpl.avt" :src="tpl.avt" alt="封面" />
                <div v-else class="template-cover-placeholder">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                  </svg>
                </div>
              </div>
              <div class="template-info">
                <div class="template-name" :title="tpl.name">{{ tpl.name || '未命名模板' }}</div>
                <div class="template-meta">
                  <span class="tpl-type-badge" :class="Number(tpl.source_type) === 1 ? 'st' : 'pm'">{{ Number(tpl.source_type) === 1 ? '范文结构模板' : '参数模板' }}</span>
                  <span>创建于 {{ formatTemplateTime(tpl.create_time) }}</span>
                  <span v-if="tpl.update_time && tpl.update_time !== tpl.create_time"> · 更新于 {{ formatTemplateTime(tpl.update_time) }}</span>
                </div>
              </div>
              <div class="template-actions">
                <button
                  class="tpl-action-btn edit"
                  :class="{ readonly: Number(tpl.source_type) === 1 }"
                  @click="Number(tpl.source_type) === 1 ? viewStructure(tpl) : editTemplate(tpl)"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  {{ Number(tpl.source_type) === 1 ? '查看' : '编辑' }}
                </button>
                <button class="tpl-action-btn delete" @click="deleteTemplate(tpl)" :disabled="templateDeleting === tpl.id">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  {{ templateDeleting === tpl.id ? '删除中…' : '删除' }}
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div v-else-if="activeMenu === 'recharge'" class="dashboard recharge-dashboard">
        <!-- 充值配置加载中，避免闪现充值表单（分站下线时将直接显示联系客服提示） -->
        <div v-if="!rechargeLoaded" class="recharge-loading">
          <span class="recharge-loading-spinner"></span>
          <p>正在读取充值配置…</p>
        </div>

        <!-- 分站：未开启在线支付 → 联系人工客服人工充值（替代充值表单） -->
        <div v-else-if="isSitePayUnavailable" class="recharge-off">
          <div class="recharge-off-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          </div>
          <h3 class="recharge-off-title">暂不支持在线充值</h3>
          <p class="recharge-off-desc">本分站未开启在线支付，请添加人工客服微信或致电客服，由客服为您人工充值到账。</p>
          <p class="recharge-off-note">分站开启在线支付后即可在站内自助充值，到账后余额即时可用。</p>
        </div>

        <!-- 二三级用户上级未配置支付接口：无法在线充值提示 -->
        <div v-else-if="isAgentPayUnavailable" class="recharge-unavailable">
          <div class="recharge-unavailable-card">
            <div class="recharge-unavailable-icon">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><path d="M12 15v3"/></svg>
            </div>
            <h3 class="recharge-unavailable-title">暂不支持在线充值</h3>
            <p class="recharge-unavailable-desc">
              您的上级代理尚未配置在线支付接口，当前无法使用在线充值。
            </p>
            <div class="recharge-unavailable-agent" v-if="rechargeConfig.agent_info">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>请先联系上级代理「<b>{{ rechargeConfig.agent_info.nickname || '上级代理' }}</b>」开通在线充值</span>
            </div>
            <p class="recharge-unavailable-agent" v-else>
              请先联系您的上级代理开通在线充值
            </p>
            <div class="recharge-unavailable-note">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span>二三级用户充值由上级代理收款，平台不代收。上级代理开通支付后即可在线充值。</span>
            </div>
            <NuxtLink to="/pc/user?tab=balance-log" class="recharge-unavailable-btn">查看资金明细</NuxtLink>
          </div>
        </div>

        <!-- PC 收银台：左右两栏布局（Stripe/支付宝 PC 标准模式） -->
        <div v-else class="recharge-grid">
          <!-- 左栏：操作区（选金额 → 选支付方式） -->
          <div class="recharge-left panel">
            <!-- 代理充值到账倍率提示条（仅声明倍率规则，金额明细统一见右侧订单摘要） -->
            <div v-if="hasRechargeGift" class="agent-gift-banner">
              <div class="gift-banner-main">
                <span class="gift-banner-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                </span>
                <span class="gift-banner-text">
                  <span class="gift-banner-title-prefix">充值专享</span>
                  充值赠送 <b>{{ rechargeRateText }} 到账</b>，充得越多送得越多
                </span>
              </div>
            </div>

            <div class="recharge-section">
              <div class="recharge-section-head">
                <span class="recharge-section-index">01</span>
                <h3 class="recharge-section-title">充值金额</h3>
              </div>
              <div class="amount-grid">
                <button
                  v-for="amt in presetAmounts"
                  :key="amt"
                  type="button"
                  class="amount-opt"
                  :class="{ active: Number(rechargeForm.money) === amt }"
                  @click="rechargeForm.money = amt"
                >
                  <span v-if="hasRechargeGift && getGiftForAmount(amt) > 0" class="amount-opt-gift">
                    赠 ¥{{ getGiftForAmount(amt).toLocaleString('zh-CN', { maximumFractionDigits: 0 }) }}
                  </span>
                  <div class="amount-opt-num">
                    <span class="amount-opt-yen">¥</span>{{ amt }}
                  </div>
                  <span v-if="hasRechargeGift" class="amount-opt-arrive">
                    到账 ¥{{ (Math.round(Number(amt) * rechargeRate * 100) / 100).toLocaleString('zh-CN', { maximumFractionDigits: 0 }) }}
                  </span>
                </button>
              </div>
              <div class="amount-custom-wrap">
                <div class="amount-custom">
                  <span class="amount-custom-prefix">¥</span>
                  <input
                    v-model="rechargeForm.money"
                    type="number"
                    min="1"
                    step="0.01"
                    placeholder="输入自定义金额"
                  />
                </div>
              </div>
            </div>

            <div class="recharge-section" v-if="rechargeConfig.pay_ways?.length">
              <div class="recharge-section-head">
                <span class="recharge-section-index">02</span>
                <h3 class="recharge-section-title">支付方式</h3>
              </div>
              <div class="payway-list">
                <label
                  v-for="w in rechargeConfig.pay_ways"
                  :key="w.pay_type"
                  class="payway-card"
                  :class="{ active: rechargeForm.pay_type === w.pay_type }"
                >
                  <input type="radio" v-model="rechargeForm.pay_type" :value="w.pay_type" />
                  <span class="payway-logo">
                    <img :src="payLogoSrc(w.pay_type)" :alt="w.pay_name" />
                  </span>
                  <span class="payway-card-name">{{ w.pay_name }}</span>
                  <span class="payway-card-desc">扫码支付</span>
                  <span class="payway-card-check">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                </label>
              </div>
              <div class="recharge-notice" v-if="rechargeConfig.is_agent_pay">
                <span class="notice-icon">!</span>
                <span>当前为代理充值模式，付款将直接到上级代理账户，平台不代收。付款完成后请等待代理确认到账。</span>
              </div>
            </div>

            <!-- 充值保障条 -->
            <div class="recharge-assure">
              <div class="assure-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>安全加密支付</span>
              </div>
              <div class="assure-divider"></div>
              <div class="assure-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <span>余额即时到账</span>
              </div>
              <div class="assure-divider"></div>
              <div class="assure-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>资金明细可查</span>
              </div>
            </div>
          </div>

          <!-- 右栏：订单摘要卡（PC 固定信息卡） - 合并式流动卡片设计 -->
          <aside class="recharge-summary-card">
            <div class="summary-card-head">
              <h4>订单摘要</h4>
              <span class="summary-card-tag">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                安全加密
              </span>
            </div>

            <!-- 顶部信息合并条：当前余额 + 商品类型 -->
            <div class="summary-top-meta">
              <div class="meta-item balance">
                <span class="meta-label">当前余额</span>
                <b class="meta-value">¥ {{ userMoneyText }}</b>
              </div>
              <div class="meta-divider"></div>
              <div class="meta-item">
                <span class="meta-label">业务类型</span>
                <b class="meta-value">账户余额充值</b>
              </div>
            </div>

            <!-- 核心金额计算卡：全页唯一的金额汇总展示 -->
            <div class="amount-flow-card" :class="{ 'has-gift': hasRechargeGift }">
              <template v-if="hasRechargeGift">
                <!-- 充值主行 -->
                <div class="flow-line recharge-line">
                  <span class="flow-line-label">
                    <span class="line-dot blue"></span>
                    充值金额
                  </span>
                  <span class="flow-line-value"><em>¥</em>{{ Number(rechargeForm.money || 0).toFixed(2) }}</span>
                </div>

                <!-- 赠送缩进子行（附属行） -->
                <div class="flow-line gift-line">
                  <span class="gift-joint"></span>
                  <span class="flow-line-label">
                    <span class="gift-mini-icon">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/></svg>
                    </span>
                    套餐赠送 <small>({{ rechargeRateText }})</small>
                  </span>
                  <span class="flow-line-value gift-val">+ ¥{{ giftAmountText }}</span>
                </div>

                <!-- 到账汇总行（高亮合并块） -->
                <div class="flow-arrive-block">
                  <span class="arrive-block-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    实际到账
                  </span>
                  <span class="arrive-block-value"><em>¥</em>{{ arrivedAmountText }}</span>
                </div>
              </template>

              <!-- 无赠送：单行金额汇总 -->
              <div v-else class="flow-arrive-block plain">
                <span class="arrive-block-label">充值金额</span>
                <span class="arrive-block-value"><em>¥</em>{{ Number(rechargeForm.money || 0).toFixed(2) }}</span>
              </div>
            </div>

            <!-- 支付方式 + 充值后余额：合并成一卡两列 -->
            <div class="summary-meta-grid">
              <div class="meta-cell pay-cell">
                <span class="meta-cell-label">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  支付方式
                </span>
                <b class="meta-cell-value">{{ getPayWayName(rechargeForm.pay_type) || '请选择' }}</b>
              </div>
              <div class="meta-cell after-cell">
                <span class="meta-cell-label">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  充值后余额
                </span>
                <b class="meta-cell-value">¥ {{ afterRechargeBalance }}</b>
              </div>
            </div>

            <button
              class="recharge-pay-btn"
              @click="submitRecharge"
              :disabled="!rechargeForm.money || !rechargeForm.pay_type"
            >
              <span v-if="rechargeLoading" class="btn-spinner"></span>
              <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="m9 15 2 2 4-4"/></svg>
              <span>确认充值</span>
            </button>

            <div class="summary-foot">
              <span class="summary-payee" v-if="rechargeConfig.agent_info && !isSiteContext">收款方：{{ rechargeConfig.agent_info.nickname || '代理账户' }}</span>
              <span class="summary-payee" v-else>平台收款 · 即时到账</span>
              <span class="summary-payee" v-if="hasRechargeGift">
                <b class="gift-accent">温馨提示：</b>赠送金额按代理套餐倍率计算，实际到账以订单为准。
              </span>
              <NuxtLink to="/pc/user?tab=balance-log" class="summary-log-link">查看资金明细 →</NuxtLink>
            </div>
          </aside>
        </div>
      </div>

      <div v-else-if="activeMenu === 'balance-log'" class="dashboard balance-log-page">
        <!-- ===== 明细表格容器 ===== -->
        <section class="bl-panel">
          <div class="bl-panel-head">
            <div class="bl-title-wrap">
              <h3 class="bl-panel-title">金额变动明细</h3>
              <p class="bl-panel-desc">充值、消费、退款、收益等全部余额变动记录 · 共 {{ balanceLogPagination.total }} 条</p>
            </div>
            <div class="filter-tabs">
              <button
                v-for="f in balanceLogFilters"
                :key="f.key"
                class="filter-tab"
                :class="{ active: balanceLogFilter === f.key }"
                @click="balanceLogFilter = f.key"
              >{{ f.label }}</button>
            </div>
          </div>

          <!-- 内容区统一容器：分页条常驻，loading 时只在表格区铺遮罩 + spinner，避免分页部分跳动 -->
          <div class="bl-body">
            <div class="balance-log-list-wrap">
              <!-- Loading：只在表格区盖一层半透明遮罩，表头/分页条保持原地不动 -->
              <div v-if="balanceLogLoading" class="bl-table-mask" aria-label="loading">
                <div class="bl-spinner bl-spinner-sm"></div>
                <p class="bl-loading-text bl-loading-text-sm">加载中…</p>
              </div>

              <table class="balance-log-table">
                <thead>
                  <tr>
                    <th style="width:13%;">类型</th>
                    <th style="width:16%;">单号</th>
                    <th style="width:14%;">时间</th>
                    <th style="width:23%;">备注</th>
                    <th style="width:8%;text-align:right;">变动前</th>
                    <th style="width:11%;text-align:right;">变动金额</th>
                    <th style="width:8%;text-align:right;">变动后</th>
                    <th style="width:7%;text-align:center;">状态</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- 空态（未加载或加载后 0 条）：用一行合并占满，保留表头 + 分页条结构 -->
                  <tr v-if="!balanceLogLoading && filteredBalanceLogsFinal.length === 0">
                    <td colspan="8" class="bl-empty-cell">
                      <p class="bl-empty-text">暂无金额变动记录</p>
                    </td>
                  </tr>
                  <template v-else>
                    <tr
                      v-for="log in filteredBalanceLogsFinal"
                      :key="log.id"
                      class="balance-log-row"
                    >
                      <td>
                        <span
                          class="log-type-tag"
                          :class="'type-' + (log.typeClass || 'default')"
                          :title="log.type"
                        >
                          <span class="log-type-dot" aria-hidden="true"></span>
                          <span class="log-type-label">{{ log.type }}</span>
                        </span>
                      </td>
                      <td class="log-sn" :title="log.sn || log.id">{{ log.sn || log.id }}</td>
                      <td class="log-time" :title="log.time">{{ log.time }}</td>
                      <td class="log-remark" :title="log.remark || ''">{{ log.remark || '—' }}</td>
                      <td class="log-balance log-align-right" :title="'¥' + log.beforeFixed">¥{{ log.beforeFixed }}</td>
                      <td class="log-align-right" :title="log.changeText">
                        <span class="log-change" :class="log.changeClass">
                          <span class="log-change-prefix">{{ log.changeText.charAt(0) }}</span><span class="log-change-amount">¥{{ log.changeText.replace(/^[+-]/, '').replace('¥', '') }}</span>
                        </span>
                      </td>
                      <td class="log-balance log-align-right log-balance-after" :title="'¥' + log.afterFixed">¥{{ log.afterFixed }}</td>
                      <td class="log-status-cell">
                        <span class="bl-status-tag" :class="log.statusClass" :title="log.status">{{ log.status }}</span>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- 分页控件：始终渲染（保留 DOM 与占位）；loading 时按钮全禁用；0 条时也显示 -->
            <div
              class="bl-pagination"
              :class="{ 'is-loading': balanceLogLoading, 'is-empty': balanceLogPagination.total === 0 && !balanceLogLoading }"
            >
              <div class="bl-pg-info">
                <template v-if="balanceLogPagination.total > 0">
                  第 <b>{{ balanceLogPagination.pageNo }}</b> / {{ balanceLogPagination.pageTotal }} 页，每页 {{ balanceLogPagination.pageSize }} 条，共 {{ balanceLogPagination.total }} 条
                </template>
                <template v-else-if="balanceLogLoading">
                  <span class="bl-pg-placeholder">数据加载中…</span>
                </template>
                <template v-else>
                  <span class="bl-pg-placeholder">共 0 条记录</span>
                </template>
              </div>
              <div class="bl-pg-btns">
                <button
                  class="bl-pg-btn bl-pg-prev"
                  :disabled="balanceLogLoading || balanceLogPagination.pageNo <= 1"
                  @click="goPrevPage"
                >上一页</button>

                <template v-for="(item, idx) in pageListShown" :key="'pg-'+idx+'-'+item">
                  <button
                    v-if="item !== '...'"
                    class="bl-pg-btn bl-pg-num"
                    :class="{ active: item === balanceLogPagination.pageNo }"
                    :disabled="balanceLogLoading"
                    @click="goPage(item)"
                  >{{ item }}</button>
                  <span v-else class="bl-pg-ellipsis">···</span>
                </template>

                <button
                  class="bl-pg-btn bl-pg-next"
                  :disabled="balanceLogLoading || balanceLogPagination.pageNo >= balanceLogPagination.pageTotal || balanceLogPagination.pageTotal === 0"
                  @click="goNextPage"
                >下一页</button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- ===== 账号设置（V7 全新设计：「设置工作站」控制中心风格） ===== -->
      <div v-else-if="activeMenu === 'settings'" class="dashboard settings-dashboard">
        <!-- 顶部条：标题 + 搜索（拟物） -->
        <div class="ws-topbar">
          <div class="ws-topbar-left">
            <h2>账号设置</h2>
            <span class="ws-topbar-sep">·</span>
            <span class="ws-topbar-sub">管理你的资料、安全与偏好</span>
          </div>
          <div class="ws-topbar-right">
            <div class="ws-search">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input placeholder="搜索设置项…" />
              <span class="ws-search-kbd">⌘K</span>
            </div>
          </div>
        </div>

        <!-- 身份卡 (Hero) -->
        <section class="ws-identity">
          <div class="ws-identity-avatar">
            <div class="ws-identity-avatar-inner">
              <span>{{ displayNameInitial }}</span>
            </div>
            <button class="ws-identity-avatar-btn" type="button" title="更换头像">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            </button>
          </div>
          <div class="ws-identity-body">
            <div class="ws-identity-name-row">
              <div class="ws-identity-name">{{ displayName }}</div>
              <span class="ws-identity-level">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                {{ auth.user.value?.level_text || '普通用户' }}
              </span>
              <span v-if="bindMobile" class="ws-identity-tag ok">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                已实名
              </span>
              <span v-else class="ws-identity-tag warn">未完善</span>
            </div>
            <div class="ws-identity-meta">
              <span><i>ID</i>{{ displayAccount || '—' }}</span>
              <span class="ws-dot"></span>
              <span>注册于 {{ registerDate }}</span>
              <span class="ws-dot"></span>
              <span>代理等级 Lv.{{ auth.user.value?.level || 1 }}</span>
            </div>
          </div>
          <div class="ws-identity-stats">
            <div class="ws-identity-stat">
              <div class="ws-identity-stat-value">{{ accountSecurityScore }}<small>分</small></div>
              <div class="ws-identity-stat-label">账号安全分</div>
              <div class="ws-identity-stat-bar"><div class="ws-identity-stat-bar-fill" :style="{ width: accountSecurityScore + '%' }"></div></div>
            </div>
            <div class="ws-identity-stat">
              <div class="ws-identity-stat-value">{{ profileCompleteness }}<small>%</small></div>
              <div class="ws-identity-stat-label">资料完整度</div>
              <div class="ws-identity-stat-bar"><div class="ws-identity-stat-bar-fill cyan" :style="{ width: profileCompleteness + '%' }"></div></div>
            </div>
            <div class="ws-identity-stat">
              <div class="ws-identity-stat-value">{{ boundPlatformCount }}<small>/3</small></div>
              <div class="ws-identity-stat-label">绑定平台</div>
              <div class="ws-identity-stat-bar"><div class="ws-identity-stat-bar-fill orange" :style="{ width: (boundPlatformCount / 3 * 100) + '%' }"></div></div>
            </div>
          </div>
        </section>

        <!-- ===== 分组 1：身份资料 ===== -->
        <section class="ws-group">
          <div class="ws-group-head">
            <h3>身份资料</h3>
            <p>账号基础信息，用于登录与展示</p>
          </div>
          <div class="ws-group-body">
            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-teal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">昵称</div>
                <div class="ws-row-sub">展示于订单、工单等所有场景</div>
              </div>
              <div class="ws-row-value">
                <input
                  v-if="editingNickname"
                  class="ws-row-input"
                  v-model="settingsForm.nickname"
                  type="text"
                  maxlength="20"
                  placeholder="2-20 个字符"
                  @keyup.enter="handleSaveProfile"
                  autofocus
                />
                <span v-else class="ws-row-value-text">{{ settingsForm.nickname || displayName }}</span>
                <span v-if="editingNickname && settingsSaveError" class="ws-row-msg err">{{ settingsSaveError }}</span>
                <span v-if="editingNickname && settingsSaveSuccess" class="ws-row-msg ok">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  已保存
                </span>
              </div>
              <div class="ws-row-action">
                <template v-if="!editingNickname">
                  <button class="ws-row-btn" type="button" @click="editingNickname = true">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    编辑
                  </button>
                </template>
                <template v-else>
                  <button class="ws-row-btn-text" type="button" @click="cancelNicknameEdit">取消</button>
                  <button class="ws-row-btn-primary" type="button" :disabled="settingsSaveLoading" @click="handleSaveProfile">
                    <span v-if="settingsSaveLoading" class="ws-spinner"></span>
                    <span v-else>保存</span>
                  </button>
                </template>
              </div>
            </div>

            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-orange">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">注册时间</div>
                <div class="ws-row-sub">账户创建日期</div>
              </div>
              <div class="ws-row-value">
                <span class="ws-row-value-text">{{ registerDate }}</span>
              </div>
              <div class="ws-row-action">
                <span class="ws-row-tag static">永久有效</span>
              </div>
            </div>

            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-purple">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">代理等级</div>
                <div class="ws-row-sub">根据累计消费自动升级</div>
              </div>
              <div class="ws-row-value">
                <span class="level-chip" :class="'lv-' + (auth.user.value?.level || 1)">
                  {{ auth.user.value?.level_text || '普通用户' }}
                </span>
              </div>
              <div class="ws-row-action">
                <span class="ws-row-tag static">查看权益 →</span>
              </div>
            </div>
          </div>
        </section>

        <!-- ===== 分组 2：账号安全 ===== -->
        <section class="ws-group">
          <div class="ws-group-head">
            <h3>账号安全</h3>
            <p>登录密码与第三方账号绑定</p>
          </div>
          <div class="ws-group-body">
            <div class="ws-row" :class="{ 'ws-row-expanded': editingPassword }">
              <div class="ws-row-icon ws-row-icon-purple">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">登录密码</div>
                <div class="ws-row-sub">字母+数字+符号 ≥ 8 位，每 90 天更换</div>
              </div>
              <div class="ws-row-value ws-row-value-flex">
                <div v-if="!editingPassword" class="ws-row-static">
                  <span class="ws-row-value-text masked">••••••••</span>
                  <span class="ws-pwd-chip" :class="'stg-' + passwordStrengthText">
                    <span class="ws-pwd-dot"></span>
                    {{ passwordStrengthText }}
                  </span>
                </div>
                <div v-else class="ws-pwd-edit">
                  <div class="ws-pwd-grid">
                    <div class="ws-pwd-field">
                      <label>原密码</label>
                      <input class="ws-row-input" type="password" v-model="pwdForm.old_password" placeholder="当前密码" autocomplete="current-password" />
                    </div>
                    <div class="ws-pwd-field">
                      <label>新密码 <span>6-32 位</span></label>
                      <input class="ws-row-input" type="password" v-model="pwdForm.new_password" placeholder="字母+数字+符号 ≥ 8 位" autocomplete="new-password" />
                    </div>
                    <div class="ws-pwd-field">
                      <label>确认密码</label>
                      <input class="ws-row-input" type="password" v-model="pwdForm.confirm_password" placeholder="再次输入" autocomplete="new-password" />
                    </div>
                  </div>
                  <div class="ws-pwd-strengthbar">
                    <div class="ws-pwd-strengthbar-track">
                      <div class="ws-pwd-strengthbar-fill" :class="'stg-' + passwordStrengthText" :style="{
                        width:
                          passwordStrengthText === '很强' ? '100%' :
                          passwordStrengthText === '强' ? '75%' :
                          passwordStrengthText === '中等' ? '50%' :
                          passwordStrengthText === '弱' ? '25%' : '0%'
                      }"></div>
                    </div>
                    <span class="ws-pwd-strengthbar-txt" :class="'stg-' + passwordStrengthText">{{ passwordStrengthText }}</span>
                  </div>
                  <div v-if="pwdSaveError" class="ws-row-msg err">{{ pwdSaveError }}</div>
                </div>
              </div>
              <div class="ws-row-action">
                <button v-if="!editingPassword" class="ws-row-btn" type="button" @click="editingPassword = true">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                  修改
                </button>
                <template v-else>
                  <button class="ws-row-btn-text" type="button" :disabled="pwdSaveLoading" @click="cancelPasswordEdit">取消</button>
                  <button class="ws-row-btn-primary" type="button" :disabled="pwdSaveLoading" @click="handleChangePwd">
                    <span v-if="pwdSaveLoading" class="ws-spinner"></span>
                    <span v-else>确认</span>
                  </button>
                </template>
              </div>
            </div>

            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-cyan">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">手机绑定</div>
                <div class="ws-row-sub">登录验证、找回密码、订单通知</div>
              </div>
              <div class="ws-row-value">
                <span class="ws-row-value-text" :class="{ muted: !bindMobile }">{{ bindMobile || '未绑定' }}</span>
              </div>
              <div class="ws-row-action">
                <span v-if="bindMobile" class="ws-row-tag ok">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  已绑定
                </span>
                <span v-else class="ws-row-value-text muted">未开通</span>
              </div>
            </div>

            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-blue">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">邮箱绑定</div>
                <div class="ws-row-sub">接收账单、合同、升级通知</div>
              </div>
              <div class="ws-row-value">
                <span class="ws-row-value-text" :class="{ muted: !bindEmail }">{{ bindEmail || '未绑定' }}</span>
              </div>
              <div class="ws-row-action">
                <span v-if="bindEmail" class="ws-row-tag ok">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  已绑定
                </span>
                <span v-else class="ws-row-value-text muted">未开通</span>
              </div>
            </div>

            <div class="ws-row">
              <div class="ws-row-icon ws-row-icon-green">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">微信授权</div>
                <div class="ws-row-sub">扫码快速登录</div>
              </div>
              <div class="ws-row-value">
                <span class="ws-row-value-text" :class="{ muted: !bindWechat }">{{ bindWechat ? '已授权' : '未授权' }}</span>
              </div>
              <div class="ws-row-action">
                <span v-if="bindWechat" class="ws-row-tag ok">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  已授权
                </span>
                <span v-else class="ws-row-value-text muted">未开通</span>
              </div>
            </div>

            <div class="ws-row ws-row-record">
              <div class="ws-row-icon ws-row-icon-slate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
              </div>
              <div class="ws-row-info">
                <div class="ws-row-label">上次登录</div>
                <div class="ws-row-sub">如非本人操作，请立即修改密码</div>
              </div>
              <div class="ws-row-value ws-row-value-record">
                <div v-if="lastLogin" class="ws-login-record">
                  <span class="ws-login-time">{{ lastLogin.timeText }}</span>
                  <span class="ws-login-dot">·</span>
                  <span class="ws-login-ip mono">{{ lastLogin.ip }}</span>
                  <span class="ws-login-dot">·</span>
                  <span class="ws-login-device">{{ lastLogin.deviceText }}</span>
                  <span v-if="lastLogin.location" class="ws-login-loc">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ lastLogin.location }}
                  </span>
                </div>
                <div v-else class="ws-login-record ws-login-empty">
                  <span class="ws-row-value-text muted">暂无记录</span>
                </div>
              </div>
              <div class="ws-row-action">
                <span v-if="lastLogin" class="ws-row-tag static">上次登录</span>
                <span v-else class="ws-row-tag warn">未记录</span>
                <button type="button" class="ws-link-btn" @click="openLoginLogDialog">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
                  完整历史
                </button>
              </div>
            </div>
          </div>
        </section>

        <div class="ws-footer">
          <span>遇到问题？<a>联系客服</a></span>
          <span class="ws-footer-dot">·</span>
          <span>登录有疑问？查看<a>登录记录</a></span>
        </div>

        <!-- 登录历史弹窗 -->
        <ClientOnly>
          <div v-if="loginLogVisible" class="loginlog-mask" @click.self="closeLoginLogDialog">
            <div class="loginlog-modal" role="dialog" aria-modal="true" aria-label="登录历史">
              <div class="loginlog-header">
                <div class="loginlog-title">
                  <div class="loginlog-title-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
                  </div>
                  <h3>登录历史</h3>
                  <span class="loginlog-count" v-if="loginLogTotal > 0">共 {{ loginLogTotal }} 条</span>
                </div>
                <button type="button" class="loginlog-close" aria-label="关闭" @click="closeLoginLogDialog">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <div class="loginlog-tips">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                如非本人操作，请立即
                <button type="button" class="loginlog-tip-link" @click="onClickChangePwdFromLog">修改密码</button>
              </div>

              <div class="loginlog-body" :class="{ 'is-loading': loginLogLoading }">
                <div v-if="loginLogLoading && loginLogList.length === 0" class="loginlog-state">
                  <div class="loginlog-spinner"></div>
                  <span>加载中…</span>
                </div>
                <div v-else-if="loginLogList.length === 0" class="loginlog-state">
                  <div class="loginlog-empty-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v6h-6"/><path d="M12 7v5l3 2"/></svg>
                  </div>
                  <p>暂无登录记录</p>
                </div>

                <ul v-else class="loginlog-list">
                  <li
                    v-for="(it, idx) in loginLogList"
                    :key="it.id || idx"
                    class="loginlog-item"
                    :class="{ 'is-current': idx === 0 }"
                  >
                    <div class="loginlog-item-dot" :class="it.ipIsV4 ? 'ipv4' : 'ipv6'"></div>
                    <div class="loginlog-item-main">
                      <div class="loginlog-item-row1">
                        <span class="loginlog-item-time">{{ it.time_text }}</span>
                        <span v-if="idx === 0" class="loginlog-item-tag current">当前会话</span>
                        <span v-else-if="isRecent(it.ts)" class="loginlog-item-tag new">最近</span>
                      </div>
                      <div class="loginlog-item-row2">
                        <span class="loginlog-item-ip mono" :title="it.ip">{{ formatIp(it.ip) }}</span>
                        <span class="loginlog-item-dot-sep">·</span>
                        <span class="loginlog-item-device">{{ formatDevice(it) }}</span>
                        <span class="loginlog-item-dot-sep">·</span>
                        <span class="loginlog-item-way">{{ formatWay(it.way) }}</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>

              <div class="loginlog-footer" v-if="loginLogTotal > loginLogPageSize">
                <button
                  type="button"
                  class="loginlog-pager"
                  :disabled="loginLogPage <= 1"
                  @click="changeLoginLogPage(loginLogPage - 1)"
                >
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                  上一页
                </button>
                <span class="loginlog-pager-text">{{ loginLogPage }} / {{ loginLogTotalPages }}</span>
                <button
                  type="button"
                  class="loginlog-pager"
                  :disabled="loginLogPage >= loginLogTotalPages"
                  @click="changeLoginLogPage(loginLogPage + 1)"
                >
                  下一页
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            </div>
          </div>
        </ClientOnly>
      </div>
      </template>

      <!-- 充值弹窗 -->
      <ClientOnly>
        <div v-if="rechargeVisible" class="recharge-mask" @click.self="closeRecharge">
          <div class="recharge-modal">
            <div class="recharge-header">
              <h3>收银台</h3>
              <button class="recharge-close" @click="closeRecharge">×</button>
            </div>
            <div class="recharge-body">
              <!-- 创建订单中视图（点击充值直接发起，不再二次确认） -->
              <div v-if="qrCreating" class="qrcreating-view">
                <span class="btn-spinner big"></span>
                <h4 class="qrcreating-title">正在创建支付订单</h4>
                <p class="qrcreating-desc">请稍候，即将展示 {{ getPayWayName(rechargeForm.pay_type) || '支付' }} 收款二维码</p>
              </div>
              <!-- 扫码支付视图（平台渠道：二维码弹窗内直显，不跳转外部页面） -->
              <div v-else-if="qrPay" class="qrpay-view">
                <template v-if="qrPay.status === 'pending'">
                  <div class="qrpay-head">
                    <span class="qrpay-brand">
                      <img :src="payLogoSrc(qrPay.brand)" :alt="qrPay.payName" />
                    </span>
                    <h4 class="qrpay-title">{{ qrPay.payName }} · 扫码支付</h4>
                  </div>
                  <div class="qrpay-amount"><em>¥</em>{{ Number(qrPay.amount).toFixed(2) }}</div>
                  <div class="qrpay-qr-box">
                    <img :src="qrPay.img" alt="支付二维码" />
                  </div>
                  <p class="qrpay-scan-tip">请使用 {{ qrPay.payName }}「扫一扫」完成付款，支付后余额自动到账</p>
                  <div class="qrpay-actions">
                    <button class="btn btn-primary" @click="checkRechargePaid()">我已完成支付</button>
                    <button class="btn btn-outline" @click="closeRecharge">取消支付</button>
                  </div>
                </template>
                <template v-else-if="qrPay.status === 'paid'">
                  <div class="qrpay-result paid">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <h4>充值成功</h4>
                    <p>¥{{ Number(qrPay.amount).toFixed(2) }} 已到账，余额已更新</p>
                  </div>
                </template>
                <template v-else>
                  <div class="qrpay-result timeout">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <h4>订单已超时</h4>
                    <p>二维码已失效，请关闭后重新发起充值</p>
                    <button class="btn btn-outline" @click="finishQrPay">关闭</button>
                  </div>
                </template>
              </div>
              <!-- 分站：未开启在线支付 → 联系人工客服人工充值（替代支付表单） -->
              <div v-else-if="isSitePayUnavailable" class="recharge-off">
                <div class="recharge-off-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <h3 class="recharge-off-title">暂不支持在线充值</h3>
                <p class="recharge-off-desc">本分站未开启在线支付，请添加人工客服微信或致电客服，由客服为您人工充值到账。</p>
                <p class="recharge-off-note">分站开启在线支付后即可在站内自助充值，到账后余额即时可用。</p>
              </div>

              <!-- 确认充值视图（金额与支付方式已在充值页选好，弹窗仅做确认） -->
              <div v-else>
                <!-- 当前余额 -->
                <div class="recharge-balance-line">
                  <span class="rbl-label">当前余额</span>
                  <b class="rbl-value">¥ {{ userMoneyText }}</b>
                </div>

                <div v-if="rechargeError" class="form-error">{{ rechargeError }}</div>

                <div class="recharge-confirm-tip" v-if="!rechargeConfig.pay_ways?.length">
                  <p>暂无可用支付方式，请联系{{ rechargeConfig.is_agent_pay ? '上级代理' : '客服' }}开通</p>
                </div>

                <!-- 确认信息卡：充值金额 / 实际到账 / 支付方式 -->
                <div class="recharge-confirm-card">
                  <div class="rc-row">
                    <span class="rc-label">充值金额</span>
                    <span class="rc-value rc-amount">¥ {{ Number(rechargeForm.money || 0).toFixed(2) }}</span>
                  </div>
                  <div class="rc-row" v-if="hasRechargeGift">
                    <span class="rc-label">实际到账</span>
                    <span class="rc-value rc-arrive">
                      ¥ {{ arrivedAmountText }}
                      <small class="rc-arrive-small">含赠送 +¥{{ giftAmountText }}</small>
                    </span>
                  </div>
                  <div class="rc-row">
                    <span class="rc-label">支付方式</span>
                    <span class="rc-value">{{ getPayWayName(rechargeForm.pay_type) || '请选择' }}</span>
                  </div>
                </div>

                <!-- 代理模式提示 -->
                <div class="recharge-notice" v-if="rechargeConfig.is_agent_pay">
                  <span class="notice-icon">!</span>
                  <span>当前为代理充值模式，付款将直接到上级代理账户，平台不代收。付款完成后请等待代理确认到账。</span>
                </div>

                <div class="recharge-payee-line" v-if="rechargeConfig.agent_info && !isSiteContext">
                  收款方：<b>{{ rechargeConfig.agent_info.nickname || '代理账户' }}</b>
                </div>

                <button
                  class="btn btn-primary recharge-submit"
                  :disabled="rechargeLoading || !rechargeForm.money || !rechargeForm.pay_type || !rechargeConfig.pay_ways?.length"
                  @click="submitRecharge"
                >
                  <span v-if="rechargeLoading" class="btn-spinner"></span>
                  <template v-else-if="hasRechargeGift">确认充值 · 实到 ¥{{ arrivedAmountText }}</template>
                  <template v-else>确认充值</template>
                </button>
              </div>
            </div>
          </div>
        </div>
      </ClientOnly>

      <!-- 公告详情弹窗 -->
      <AnnouncementDetailModal v-model="noticeDetailVisible" :data="noticeDetailData" />

      </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const route = useRoute()

definePageMeta({
  layout: 'console',
})

useSeoMeta({
  title: '用户中心 - AI写作助手',
  description: '查看订单记录、账户余额和个人信息。',
})

const auth = useAuth()
const toast = useToast()
const login = useLoginModal()
const router = useRouter()
const api = useApi()

const isLoggedIn = computed(() => auth.isLoggedIn.value)
const ready = computed(() => auth.ready.value)

const displayName = computed(() => auth.user.value?.nickname || '用户')
const displayAccount = computed(() => auth.user.value?.mobile || auth.user.value?.email || '')
const displayNameInitial = computed(() => {
  const n = displayName.value
  if (!n) return 'U'
  const ch = n.charAt(0)
  return /[a-zA-Z]/.test(ch) ? ch.toUpperCase() : ch
})
const userMoneyText = computed(() => {
  const m = Number(auth.user.value?.user_money || 0)
  return m.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})
const registerDate = computed(() => {
  const t = auth.user.value?.create_time
  if (!t) return '—'
  // 后端 create_time 是 Unix 时间戳（秒）
  const d = new Date(Number(t) * 1000)
  if (isNaN(d.getTime())) return '—'
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

// ===== 上次登录记录（来源：后端 adhp_user_login_log 最新一条）=====
// 后端 fetchUser 会在 user 里塞入 last_login: { ts, time_text, ip, ip_version, device, os, browser, way, terminal }
// 此处只负责把后端结构映射成页面直接使用的展示格式。
const LAST_LOGIN_KEY = 'aidianwrite_last_login_v1' // 历史 localStorage 兜底键（v8 之前的数据；后续不再写入）
const lastLogin = ref(null)
// 计算距今多久（'刚刚' / 'X 分钟前' / 'X 小时前' / 'X 天前'）
const relTimeText = (ts) => {
  if (!ts) return ''
  const diff = Math.floor((Date.now() / 1000) - Number(ts))
  if (diff < 60) return '刚刚'
  if (diff < 3600) return `${Math.floor(diff / 60)} 分钟前`
  if (diff < 86400) return `${Math.floor(diff / 3600)} 小时前`
  return `${Math.floor(diff / 86400)} 天前`
}
// 把后端 last_login 规范化成页面展示结构
const normalizeLastLogin = (raw) => {
  if (!raw || typeof raw !== 'object') return null
  const ts = Number(raw.ts || 0)
  if (!ts) return null
  const ip = String(raw.ip || '').trim()
  // 设备展示：「浏览器 / 操作系统」；没解析出来就只显示 device
  const browser = String(raw.browser || '').trim()
  const os = String(raw.os || '').trim()
  const device = String(raw.device || '').trim()
  let deviceText = '—'
  if (browser && os) deviceText = `${browser} / ${os}`
  else if (os) deviceText = os
  else if (device) deviceText = device
  // 后端 IPv4 优先；这里再做一次「是否真的是 IPv4」的兜底校验，避免展示 IPv6
  const ipIsV4 = /^\d{1,3}(\.\d{1,3}){3}$/.test(ip)
  return {
    ts,
    timeText: raw.time_text || '',
    ip: ipIsV4 ? ip : (ip || '—'),
    ipIsV4,
    deviceText,
    location: '', // 后端暂无 IP 库；保留位
    relTime: relTimeText(ts),
  }
}
// 加载上次登录记录：优先用后端字段；旧 localStorage 仅作为 v8 之前用户的兜底
const loadLastLogin = () => {
  if (typeof window === 'undefined') return
  // 1) 优先：auth.user.last_login（fetchUser 写入）
  const fromApi = normalizeLastLogin(auth.user?.value?.last_login)
  if (fromApi) {
    lastLogin.value = fromApi
    return
  }
  // 2) 兜底：旧的 localStorage 记录（仅在未登录后端时展示；不影响功能）
  try {
    const raw = localStorage.getItem(LAST_LOGIN_KEY)
    if (raw) {
      const data = JSON.parse(raw)
      if (data && data.ts) {
        const fallback = normalizeLastLogin({
          ts: data.ts,
          time_text: data.time_text || '',
          ip: data.ip,
          ip_version: data.ipIsV4 ? 4 : 0,
          browser: '',
          os: '',
          device: '',
        })
        if (fallback) {
          fallback.deviceText = data.ua ? data.ua : '—'
          lastLogin.value = fallback
          return
        }
      }
    }
  } catch (e) {}
  lastLogin.value = null
}
// 兼容旧版本：保留 recordCurrentLogin 方法签名（v8 之前页面有用过），现在改为空操作
//   - 后端已经在登录时写入 adhp_user_login_log，无需前端再重复探测 IP
//   - 保留方法只是为了避免页面其他地方引用出现 undefined 报错
const recordCurrentLogin = async () => {
  // no-op: 已由后端 LoginLogic / WechatLogic / RegisterLogic 写入 adhp_user_login_log
}

// ===== 登录历史弹窗 =====
const loginLogVisible = ref(false)
const loginLogLoading  = ref(false)
const loginLogList     = ref([])
const loginLogTotal    = ref(0)
const loginLogPage     = ref(1)
const loginLogPageSize = ref(8)
// 列表项内的扩展字段（前端计算；不污染后端返回）
const enrichLoginLogItem = (raw) => {
  if (!raw || typeof raw !== 'object') return null
  const ip = String(raw.ip || '').trim()
  const ipIsV4 = /^\d{1,3}(\.\d{1,3}){3}$/.test(ip)
  return { ...raw, ipIsV4 }
}
// 打开弹窗（首次打开才拉取；切换 tab 回来时刷新）
const openLoginLogDialog = async () => {
  loginLogVisible.value = true
  loginLogPage.value = 1
  await loadLoginLogList(1)
}
// 关闭弹窗
const closeLoginLogDialog = () => {
  loginLogVisible.value = false
}
// 拉取登录历史
const loadLoginLogList = async (page = 1) => {
  if (!auth.token?.value) {
    loginLogList.value = []
    loginLogTotal.value = 0
    return
  }
  loginLogLoading.value = true
  try {
    const res = await auth.fetchLoginLog(page, loginLogPageSize.value)
    if (res?.ok && res.data) {
      const list = Array.isArray(res.data.list) ? res.data.list.map(enrichLoginLogItem) : []
      loginLogList.value     = list
      loginLogTotal.value    = Number(res.data.total || 0)
      loginLogPage.value     = Number(res.data.page || page)
      loginLogPageSize.value = Number(res.data.page_size || loginLogPageSize.value)
    } else {
      loginLogList.value  = []
      loginLogTotal.value = 0
    }
  } catch (e) {
    loginLogList.value = []
  } finally {
    loginLogLoading.value = false
  }
}
const loginLogTotalPages = computed(() => {
  if (loginLogTotal.value <= 0 || loginLogPageSize.value <= 0) return 1
  return Math.max(1, Math.ceil(loginLogTotal.value / loginLogPageSize.value))
})
const changeLoginLogPage = (p) => {
  const next = Number(p || 0)
  if (!next || next < 1 || next > loginLogTotalPages.value) return
  if (next === loginLogPage.value) return
  loadLoginLogList(next)
}
// 弹窗里的辅助展示
//   - IPv6 太长截断
const formatIp = (ip) => {
  if (!ip) return '—'
  const s = String(ip)
  if (s.length > 32) return s.slice(0, 30) + '…'
  return s
}
//   - 设备：「浏览器 / 操作系统」，若只有 device 类型则兜底
const formatDevice = (it) => {
  if (!it) return '—'
  const browser = String(it.browser || '').trim()
  const os      = String(it.os || '').trim()
  if (browser && os) return `${browser} / ${os}`
  if (os) return os
  const dev = String(it.device || '').trim()
  if (dev && dev !== 'other') {
    return dev === 'pc' ? '电脑' : dev === 'mobile' ? '手机' : dev === 'tablet' ? '平板' : dev
  }
  return '—'
}
//   - 登录方式：枚举转中文
const formatWay = (w) => {
  switch (String(w || '').toLowerCase()) {
    case 'account':  return '账号密码'
    case 'mobile':   return '手机验证'
    case 'email':    return '邮箱验证'
    case 'wechat':   return '微信扫码'
    case 'register': return '注册即登'
    default:         return w || '—'
  }
}
//   - 最近标记（< 5 分钟）
const isRecent = (ts) => {
  if (!ts) return false
  const diff = Math.floor((Date.now() / 1000) - Number(ts))
  return diff >= 0 && diff < 300
}
// 从弹窗的"修改密码"提示点击：直接复用页面已有的密码修改流程
const onClickChangePwdFromLog = () => {
  closeLoginLogDialog()
  editingPassword.value = true
}

// ===== 我的大纲数据 =====
const outlineList = ref([])
const outlineLoading = ref(false)
const outlineTotal = ref(0)
const outlinePage = ref(1)

// ===== 我的大纲 =====
async function loadOutlineList(page = 1) {
  if (!isLoggedIn.value) return
  outlineLoading.value = true
  outlinePage.value = page
  try {
    const res = await api.post('/api/ai/outlineList', { page, limit: 10 })
    if (res.ok && res.data) {
      outlineList.value = res.data.list || []
      outlineTotal.value = res.data.total || 0
    }
  } catch (e) {
    console.error('加载大纲列表失败:', e)
  } finally {
    outlineLoading.value = false
  }
}

function restoreOutline(item) {
  navigateTo(`/pc/create?outline_no=${encodeURIComponent(item.outline_no)}`)
}

// ===== 我的模板数据 =====
const templateList = ref([])
// 若初始 tab 为 templates,直接进入 loading 态,避免先闪一下空状态再切到骨架屏
const templateLoading = ref(route.query.tab === 'templates')
const templateDeleting = ref(0)

async function loadTemplateList() {
  if (!isLoggedIn.value) return
  templateLoading.value = true
  try {
    const res = await api.get('/api/template/list')
    if (res.ok && res.data) {
      templateList.value = res.data.list || []
    }
  } catch (e) {
    console.error('加载模板列表失败:', e)
  } finally {
    templateLoading.value = false
  }
}

function formatTemplateTime(t) {
  if (!t) return '—'
  // 兼容时间戳(秒/毫秒)与日期字符串
  let d
  if (typeof t === 'number') {
    d = new Date(t < 1e12 ? t * 1000 : t)
  } else if (typeof t === 'string') {
    d = new Date(t.replace(/-/g, '/'))
  } else {
    return '—'
  }
  if (isNaN(d.getTime())) return '—'
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

function goCreateTemplate() {
  navigateTo('/pc/template-mode')
}

function editTemplate(tpl) {
  navigateTo(`/pc/template?id=${tpl.id}`)
}

// 范文结构模板:无参数可编辑,查看占位符骨架(占位符替换时自动套用范文排版属性)
function viewStructure(tpl) {
  const url = tpl.skeleton_url || tpl.avt
  if (url) {
    window.open(url, '_blank', 'noopener')
  } else {
    toast.info('该范文结构模板暂无骨架预览')
  }
}

async function deleteTemplate(tpl) {
  if (!window.confirm(`确定删除模板「${tpl.name}」吗？此操作不可撤销`)) return
  templateDeleting.value = tpl.id
  try {
    const res = await api.post('/api/template/delete', { id: tpl.id })
    if (res.ok) {
      toast.success(res.msg || '删除成功')
      // 从列表中移除
      templateList.value = templateList.value.filter(t => t.id !== tpl.id)
    }
  } catch (e) {
    console.error('删除模板失败:', e)
  } finally {
    templateDeleting.value = 0
  }
}

async function copyToClipboard(text) {
  // 优先使用现代 Clipboard API（仅在 HTTPS / localhost 安全上下文下可用）
  if (process.client && navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(text)
      return true
    } catch (e) {
      // 继续走 fallback
    }
  }
  // Fallback：textarea + execCommand，兼容局域网 HTTP 部署
  if (process.client) {
    try {
      const textarea = document.createElement('textarea')
      textarea.value = text
      textarea.setAttribute('readonly', '')
      textarea.style.position = 'fixed'
      textarea.style.top = '-9999px'
      textarea.style.left = '-9999px'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.focus()
      textarea.select()
      const ok = document.execCommand('copy')
      document.body.removeChild(textarea)
      return ok
    } catch (e) {
      return false
    }
  }
  return false
}

// ===== 充值弹窗 =====
const rechargeVisible = ref(false)
const rechargeLoading = ref(false)
const rechargeError = ref('')
const rechargeConfig = ref({})
// 充值配置是否已加载完成（用于避免分站下线时先闪现充值表单）
const rechargeLoaded = ref(false)
const rechargeForm = ref({ money: 100, pay_type: 'alipay' })
const presetAmounts = [50, 100, 200, 500, 1000, 2000]
// 充值到账倍率：一级用户取系统套餐倍率，二三级用户取上级在「下级用户管理」中设置的倍率（由 /api/recharge/config 返回）
const rechargeRate = computed(() => {
  const rate = Number(rechargeConfig.value?.recharge_rate ?? 1)
  if (!rate || rate < 1) return 1
  return rate
})
const hasRechargeGift = computed(() => rechargeRate.value > 1)
// 是否属于代理充值但上级未配置支付（二三级用户无可用支付方式 → 无法在线充值）
const isAgentPayUnavailable = computed(() => {
  return !!(rechargeConfig.value?.is_agent_pay && !rechargeConfig.value?.pay_ways?.length)
})
// 是否处于分站模式（site_pay 非 null；分站且非拥有者本人）
const isSiteContext = computed(() => {
  const sp = rechargeConfig.value?.site_pay
  return sp !== null && sp !== undefined
})
// 分站未开启（或未配置可用）在线支付 → 用「联系人工客服人工充值」视图替代充值表单
const isSitePayUnavailable = computed(() => {
  const sp = rechargeConfig.value?.site_pay
  return sp !== null && sp !== undefined && !sp.enabled
})
// 赠送金额 = 充值金额 × (rate - 1)
const giftAmount = computed(() => {
  if (!hasRechargeGift.value) return 0
  const m = Number(rechargeForm.value?.money || 0)
  const gift = m * (rechargeRate.value - 1)
  // 保留两位小数，兼容整数/小数
  return Math.round(gift * 100) / 100
})
const giftAmountText = computed(() => giftAmount.value.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
// 实际到账金额 = 充值金额 × rate
const arrivedAmount = computed(() => {
  const m = Number(rechargeForm.value?.money || 0)
  return Math.round(m * rechargeRate.value * 100) / 100
})
const arrivedAmountText = computed(() => arrivedAmount.value.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
// 充值倍率格式化（例如 1.6 → 1.6 倍）
const rechargeRateText = computed(() => Number(rechargeRate.value).toFixed(Number(rechargeRate.value) % 1 === 0 ? 0 : 1) + ' 倍')
// 充值后余额预览：当前余额 + 实际到账金额（代理倍率生效后）
const afterRechargeBalance = computed(() => {
  const m = Number(auth.user.value?.user_money || 0)
  return (m + arrivedAmount.value).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})
// 计算某个金额对应的赠送（给金额按钮用）
function getGiftForAmount(amt) {
  if (!hasRechargeGift.value) return 0
  const gift = Number(amt) * (rechargeRate.value - 1)
  return Math.round(gift * 100) / 100
}
function isWechatPay(payType) {
  const t = String(payType || '')
  return t === 'wechat' || t.includes('wx') || t.includes('wechat')
}

// 支付渠道 logo（public/img/pay/，需拼 baseURL /pc/）
const payLogoBase = useRuntimeConfig().app?.baseURL || '/'
function payLogoSrc(payType) {
  return payLogoBase + 'img/pay/' + (isWechatPay(payType) ? 'wechat.svg' : 'alipay.png')
}

function getPayWayName(payType) {
  const w = rechargeConfig.value.pay_ways?.find(x => x.pay_type === payType)
  return w?.pay_name || ''
}

// ===== 充值页面数据 =====
const rechargeOrders = ref([])
const rechargeOrdersLoading = ref(false)
const rechargeTotalText = computed(() => {
  const total = rechargeOrders.value
    .filter(o => o.statusClass === 'success')
    .reduce((sum, o) => sum + Number(o.amount || 0), 0)
  return total.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})
const rechargeCount = computed(() => rechargeOrders.value.length)

// ===== 金额变动页面数据 =====
const balanceLogLoading = ref(false)
const balanceLogFilter = ref('all')
const balanceLogFilters = [
  { key: 'all', label: '全部' },
  { key: 'income', label: '收入' },
  { key: 'expense', label: '支出' },
  { key: 'recharge', label: '充值' },
  { key: 'consume', label: '消费' },
]
const balanceLogs = ref([])
const balanceLogPagination = ref({
  total: 0,
  count: 0,
  pageNo: 1,
  pageSize: 10,
  pageTotal: 0,
})

async function loadAccountLogs(pageNo) {
  if (typeof pageNo === 'number' && pageNo >= 1) {
    balanceLogPagination.value.pageNo = pageNo
  }
  const p = balanceLogPagination.value
  balanceLogLoading.value = true
  try {
    const res = await api.get('/api/pc/accountLogs', {
      page_no: p.pageNo,
      page_size: p.pageSize,
      filter: balanceLogFilter.value,
    })
    if (res.ok && res.data) {
      const d = res.data
      balanceLogs.value = Array.isArray(d.list) ? d.list : []
      balanceLogPagination.value = {
        total:     Number(d.total || 0),
        count:     Number(d.count || 0),
        pageNo:    Number(d.page_no || 1),
        pageSize:  Number(d.page_size || 10),
        pageTotal: Number(d.page_total || 0),
      }
    } else {
      balanceLogs.value = []
      balanceLogPagination.value = {
        total: 0, count: 0, pageNo: 1, pageSize: 10, pageTotal: 0,
      }
    }
  } catch (e) {
    balanceLogs.value = []
  } finally {
    balanceLogLoading.value = false
  }
}

// 修正 balanceLogs 带上 changeText/changeClass/beforeFixed/afterFixed
const balanceLogsWithChange = computed(() => balanceLogs.value.map((l) => ({
  ...l,
  changeText:  (l.direction === 'income' ? '+' : '-') + '¥' + Number(l.amount).toFixed(2),
  changeClass: l.direction === 'income' ? 'income' : 'expense',
  beforeFixed: Number(l.before).toFixed(2),
  afterFixed:  Number(l.after).toFixed(2),
  typeClass:   mapLogTypeClass(l.type),
  statusClass: (l.status || '成功') === '退款' ? 'refund' : 'success',
})))

// 类型 → 标签配色（与 .log-type-tag.type-* 对应）
function mapLogTypeClass(t) {
  const s = String(t || '')
  if (s.includes('充值')) return 'recharge'
  if (s.includes('退款') || s.includes('提现')) return 'refund'
  if (s.includes('收益') || s.includes('赠送')) return 'income'
  if (s.includes('降重') || s.includes('消费') || s.includes('写作') || s.includes('PPT') || s.includes('论文') || s.includes('格式') || s.includes('调整')) return 'consume'
  return 'default'
}

// 后端已按 filter + 分页返回，直接取
const filteredBalanceLogsFinal = computed(() => balanceLogsWithChange.value)

// 切换筛选 → 回到第1页重拉
watch(balanceLogFilter, () => {
  loadAccountLogs(1)
})

// 分页操作函数
function goPrevPage() {
  if (balanceLogPagination.value.pageNo <= 1) return
  loadAccountLogs(balanceLogPagination.value.pageNo - 1)
}
function goNextPage() {
  const p = balanceLogPagination.value
  if (p.pageNo >= p.pageTotal) return
  loadAccountLogs(p.pageNo + 1)
}
function goPage(n) {
  const p = balanceLogPagination.value
  if (n < 1 || n > p.pageTotal) return
  loadAccountLogs(n)
}

// 计算分页按钮（最多 7 个页码显示，带省略号占位）
const pageListShown = computed(() => {
  const p = balanceLogPagination.value
  const total = p.pageTotal
  const cur = p.pageNo
  if (!total) return []
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const arr = [1]
  const left = Math.max(2, cur - 1)
  const right = Math.min(total - 1, cur + 1)
  if (left > 2) arr.push('...')
  for (let i = left; i <= right; i++) arr.push(i)
  if (right < total - 1) arr.push('...')
  if (total !== 1) arr.push(total)
  return arr
})

async function loadRechargeConfig(force = false) {
  // 拉取充值配置（含可用支付方式 + 到账倍率），用于充值页面预览
  // force=true 时强制刷新（进入充值 tab 时需获取最新的上级倍率与支付方式）
  if (!force && rechargeConfig.value?.pay_ways?.length) return
  const res = await api.get('/api/recharge/config')
  if (res.ok && res.data) {
    const cfg = { ...res.data }
    if (Array.isArray(cfg.pay_ways)) {
      cfg.pay_ways = sortPayWays(cfg.pay_ways)
    }
    rechargeConfig.value = cfg
  } else {
    rechargeConfig.value = {}
  }
  rechargeLoaded.value = true
}

// 支付渠道排序：支付宝排第1，微信排第2，其他保持原顺序
function sortPayWays(ways) {
  if (!Array.isArray(ways) || ways.length <= 1) return ways
  const alipayIdx = ways.findIndex(w => String(w.pay_type).toLowerCase().includes('alipay'))
  const wechatIdx = ways.findIndex(w => String(w.pay_type).toLowerCase().includes('wechat'))
  const result = ways.slice()
  const moveToTop = (idx, rank) => {
    if (idx < 0) return
    const [item] = result.splice(idx, 1)
    const insertAt = Math.min(rank, result.length)
    result.splice(insertAt, 0, item)
  }
  // 先处理微信，再处理支付宝，确保支付宝在最上
  moveToTop(wechatIdx, 1)
  // 支付宝优先：重新算 index 因为微信移动过
  const newAliIdx = result.findIndex(w => String(w.pay_type).toLowerCase().includes('alipay'))
  moveToTop(newAliIdx, 0)
  return result
}

async function openRecharge() {
  rechargeError.value = ''
  // 保留用户在外部页面已选的金额和支付方式，不重置
  if (!rechargeForm.value.money) {
    rechargeForm.value.money = 100
  }
  // 先拉取充值配置（判断分站在线支付是否可用 / 可用支付方式），
  // 配置就绪后再打开弹窗，避免先闪现输入表单、再跳转到联系客服提示
  const res = await api.get('/api/recharge/config')
  if (res.ok && res.data) {
    const cfg = { ...res.data }
    // 支付宝排第一，微信排第二
    if (Array.isArray(cfg.pay_ways)) {
      cfg.pay_ways = sortPayWays(cfg.pay_ways)
    }
    rechargeConfig.value = cfg
    const ways = cfg.pay_ways || []
    if (ways.length) {
      const currentOk = ways.find(w => w.pay_type === rechargeForm.value.pay_type)
      if (!currentOk) {
        // 当前选中的渠道不可用 → 默认选支付宝，不存在则回退第一个
        const alipay = ways.find(w => String(w.pay_type).toLowerCase().includes('alipay'))
        rechargeForm.value.pay_type = alipay ? alipay.pay_type : ways[0].pay_type
      }
    }
  } else {
    rechargeConfig.value = {}
  }
  // 兜底：经过拉取后仍没有选择（例如配置为空），默认填支付宝
  if (!rechargeForm.value.pay_type) {
    rechargeForm.value.pay_type = 'alipay'
  }
  rechargeVisible.value = true
}

function closeRecharge() {
  finishQrPay()
  rechargeVisible.value = false
  rechargeError.value = ''
}

// 打开支付收银台：支付宝 PC 返回自动提交的 HTML 表单，微信 PC 返回二维码链接
function openPayGateway(config) {
  if (typeof config === 'string' && /<[a-z!]/i.test(config)) {
    const w = window.open('', '_blank')
    if (w) {
      w.document.write(config)
      w.document.close()
      return
    }
    // 弹窗被拦截：在当前页临时渲染并自动提交表单
    const holder = document.createElement('div')
    holder.style.display = 'none'
    holder.innerHTML = config
    document.body.appendChild(holder)
    const form = holder.querySelector('form')
    if (form) form.submit()
    return
  }
  window.open(config, '_blank')
}

async function submitRecharge() {
  if (rechargeLoading.value) return
  rechargeError.value = ''
  const money = Number(rechargeForm.value.money)
  if (!money || money <= 0) {
    toast.error('请输入有效充值金额')
    return
  }
  if (money < 0.01) {
    toast.error('充值金额不能低于 0.01 元')
    return
  }
  if (money > 50000) {
    toast.error('单笔充值金额不能超过 50000 元')
    return
  }
  if (!rechargeForm.value.pay_type) {
    toast.error('请选择支付方式')
    return
  }
  // 直接进收银台（无二次确认步骤）：先弹窗显示创建中，成功后切扫码视图
  rechargeVisible.value = true
  qrCreating.value = true
  rechargeLoading.value = true
  try {
    const res = await api.post('/api/recharge/recharge', {
      money,
      pay_type: rechargeForm.value.pay_type,
    })
    if (res.ok && res.data) {
      // 根据支付来源处理
      if (res.data.from === 'recharge') {
        // 平台支付：二维码图片直接在弹窗内展示（不跳转）；仅 HTML 收银台才弹新窗
        const pay = res.data.pay
        if (pay?.config) {
          if (typeof pay.config === 'string' && !/<[a-z!]/i.test(pay.config)) {
            const pt = rechargeForm.value.pay_type || ''
            const w = rechargeConfig.value.pay_ways?.find((x) => x.pay_type === pt)
            qrPay.value = {
              orderNo: res.data.order_no,
              amount: money,
              payName: w?.pay_name || (isWechatPay(pt) ? '微信支付' : '支付宝'),
              brand: isWechatPay(pt) ? 'wechat' : 'alipay',
              img: pay.config,
              status: 'pending',
            }
            startQrPolling()
          } else {
            openPayGateway(pay.config)
            closeRecharge()
          }
        } else {
          toast.success('订单已创建')
          closeRecharge()
        }
      } else {
        toast.success('订单已创建')
        closeRecharge()
      }
    } else {
      toast.error(res.msg || '充值失败，请稍后重试')
      closeRecharge()
    }
  } catch (e) {
    toast.error(e?.message || '充值失败，请稍后重试')
    closeRecharge()
  } finally {
    rechargeLoading.value = false
    qrCreating.value = false
  }
}

// 平台充值扫码支付（二维码弹窗内直显，不跳转）
const qrPay = ref(null)
const qrCreating = ref(false)
let qrPollTimer = null
let qrTimeoutTimer = null

function stopQrPolling() {
  if (qrPollTimer) {
    clearInterval(qrPollTimer)
    qrPollTimer = null
  }
  if (qrTimeoutTimer) {
    clearTimeout(qrTimeoutTimer)
    qrTimeoutTimer = null
  }
}

function startQrPolling() {
  stopQrPolling()
  qrPollTimer = setInterval(() => {
    checkRechargePaid(false)
  }, 3000)
  // 二维码 30 分钟超时（与上游订单有效期一致）
  qrTimeoutTimer = setTimeout(() => {
    if (qrPay.value && qrPay.value.status === 'pending') {
      qrPay.value.status = 'timeout'
      stopQrPolling()
    }
  }, 30 * 60 * 1000)
}

function finishQrPay() {
  stopQrPolling()
  qrPay.value = null
}

async function checkRechargePaid(notify = true) {
  if (!qrPay.value || qrPay.value.status !== 'pending') return
  try {
    const res = await api.post('/api/order/rechargeList', { page: 1, page_size: 1, keyword: qrPay.value.orderNo })
    const row = res.ok && res.data?.list?.length ? res.data.list[0] : null
    if (row && row.order_sn === qrPay.value.orderNo && Number(row.pay_status) === 1) {
      qrPay.value.status = 'paid'
      stopQrPolling()
      toast.success('充值成功，余额已更新')
      auth.fetchUser()
      // 成功态展示 1.8s 后自动关闭整个收银台弹窗
      setTimeout(() => {
        if (qrPay.value?.status === 'paid') closeRecharge()
      }, 1800)
    } else if (notify) {
      toast.info('订单暂未支付，请稍后再查')
    }
  } catch {
    /* 轮询失败静默重试 */
  }
}

// mounted=false 时用默认值 'overview'，与预渲染 HTML 一致，避免 hydration mismatch
const mounted = ref(false)
// 当前 tab：直接从 route.query 读取（computed 自动响应路由变化）
const activeMenu = computed(() => {
  if (!mounted.value) return 'overview'
  return route.query.tab || 'overview'
})

// 客户端恢复登录态 + 拉取最新用户信息
onMounted(async () => {
  // 修复预渲染刷新后 route.query 未同步：从实际 URL 读取 tab 并同步到路由
  const params = new URLSearchParams(window.location.search)
  const tab = params.get('tab')
  if (tab && route.query.tab !== tab) {
    await router.replace({ path: route.path, query: { ...route.query, tab } })
  }
  mounted.value = true
  auth.restore()
  // 未登录留在本页，由 auth-gate 展示「请先登录」（登录走全局弹窗，不再跳独立登录页）
  if (!auth.isLoggedIn.value) return
  // 已登录，并行拉取用户信息 / 工作台统计 / 公告（串行 await 会拖慢首屏）
  auth.fetchUser().catch(() => {})
  // 加载上次登录记录
  loadLastLogin()
  // 记录本次登录（每次进入设置页时记录一次；下次进入即显示为"上次登录"）
  if (route.query.tab === 'settings') {
    recordCurrentLogin()
  }
  // 拉取工作台统计 + 公告
  loadDashboard()
  loadNotices()
  // 若直接进入大纲 tab，加载大纲列表
  if (route.query.tab === 'outlines') {
    loadOutlineList(1)
  }
  // 若直接进入模板 tab，加载模板列表
  if (route.query.tab === 'templates') {
    loadTemplateList()
  }
  // 若直接进入金额变动 tab，拉取账户流水（第1页）
  if (route.query.tab === 'balance-log') {
    loadAccountLogs(1)
  }
})

// 监听 tab 切换，进入对应 tab 时加载数据
watch(activeMenu, (val) => {
  if (val === 'overview') {
    if (recentLogsFinal.value.length === 0) loadDashboard()
  }
  if (val === 'outlines' && outlineList.value.length === 0) {
    loadOutlineList(1)
  }
  if (val === 'templates') {
    loadTemplateList()
  }
  if (val === 'recharge') {
    loadRechargeConfig(true)
  }
  if (val === 'balance-log') {
    // 每次进入金额变动页都拉取第1页，保证数据最新
    loadAccountLogs(1)
  }
})

// 监听 console 布局顶栏刷新按钮触发：通过 window 自定义事件通信
onMounted(() => {
  window.addEventListener('console-refresh', handleRefreshEvent)
})
onUnmounted(() => {
  window.removeEventListener('console-refresh', handleRefreshEvent)
})
function handleRefreshEvent() {
  if (!isLoggedIn.value) return
  const tab = activeMenu.value
  if (tab === 'overview') {
    auth.fetchUser().catch(() => {})
    loadDashboard()
    loadNotices()
  } else if (tab === 'outlines') {
    loadOutlineList(outlinePage.value)
  } else if (tab === 'templates') {
    loadTemplateList()
  }
}

const handleLogout = async () => {
  await auth.logout()
  toast.success('已退出登录')
  router.replace('/pc')
}

const menu = [
  { key: 'overview', label: '概览', icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>' },
  { key: 'outlines', label: '我的大纲', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>' },
  { key: 'templates', label: '我的模板', icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>' },
  { key: 'recharge', label: '账户充值', icon: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
  { key: 'balance-log', label: '资金明细', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>' },
]

const currentMenu = computed(() => menu.find(m => m.key === activeMenu.value) || menu[0])
const pageDate = new Date().toLocaleDateString('zh-CN', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' })

// ===== 工作台 Dashboard（真实接口）=====
// 必须预先声明所有字段，否则 Vue 3 对新增属性在某些 SSR/hydration 场景下响应式失效 → 全部显示 0
const dashboard = ref({
  // 顶部 4 张卡片
  user_money: 0,
  total_recharge: 0,
  total_gift: 0,
  total_consume: 0,
  recharge_count: 0,
  consume_count: 0,
  // 近 7 日趋势
  trend_7d: [],
  // 最近资金流水
  recent_logs: [],
  // 我的产出
  my_paper_outline_count: 0,
  my_ppt_outline_count: 0,
  my_outline_count: 0,
  my_template_count: 0,
  // 已支付订单量
  my_paper_order_count: 0,
  my_ppt_order_count: 0,
  // 下级（代理）统计
  my_children_count: 0,
  my_children_order_count: 0,
  my_children_order_amount: 0,
  // 消费类型分布
  consume_dist_30d: [],
  consume_dist_total_30d: 0,
})
const dashboardLoading = ref(false)

async function loadDashboard() {
  if (!isLoggedIn.value) return
  dashboardLoading.value = true
  try {
    const res = await api.get('/api/pc/dashboard')
    if (res.ok && res.data && typeof res.data === 'object') {
      // 用展开语法创建 NEW 对象，保证触发 Vue 3 响应式刷新
      // （Object.assign(旧对象,新数据) 会原地复用旧引用，
      //   SSR/hydration 后部分模板引用计算不到新增属性）
      dashboard.value = Object.assign({}, dashboard.value, res.data)
    } else if (!res.ok && res.msg) {
      try { toast.error('工作台数据加载失败：' + res.msg) } catch (_) {}
    }
  } catch (e) {
    try { toast.error('工作台数据加载异常：' + (e?.message || String(e))) } catch (_) {}
  } finally {
    dashboardLoading.value = false
  }
}

function fmtMoney(n) {
  const v = Number(n || 0)
  return v.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 顶部 4 张卡：账户余额/累计充值/累计消费/消费笔数（全部真实数据）
const stats = computed(() => {
  const d = dashboard.value
  const userMoney = Number(d.user_money || 0)
  const totalRecharge = Number(d.total_recharge || 0)
  const totalConsume = Number(d.total_consume || 0)
  const totalTx = (d.recharge_count || 0) + (d.consume_count || 0)
  return [
    {
      label: '账户余额',
      value: '¥ ' + fmtMoney(userMoney),
      trend: d.total_gift > 0 ? `含赠送 ¥${fmtMoney(d.total_gift)}` : '可用余额',
      trendType: 'up',
      theme: 'teal',
      icon: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
      isZero: userMoney <= 0,
      ctaLink: userMoney <= 0 ? '/pc/user?tab=recharge' : '',
      ctaLabel: '立即充值 →',
    },
    {
      label: '累计充值',
      value: '¥ ' + fmtMoney(totalRecharge),
      trend: `${d.recharge_count || 0} 笔充值记录`,
      trendType: 'up',
      theme: 'orange',
      icon: '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
      isZero: totalRecharge <= 0,
      ctaLink: totalRecharge <= 0 ? '/pc/user?tab=recharge' : '',
      ctaLabel: '完成首次充值',
    },
    {
      label: '累计消费',
      value: '¥ ' + fmtMoney(totalConsume),
      trend: `${d.consume_count || 0} 次消费扣款`,
      trendType: totalConsume > 0 ? 'warn' : 'up',
      theme: 'cyan',
      icon: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
      isZero: totalConsume <= 0,
      ctaLink: totalConsume <= 0 ? '/pc/outline' : '',
      ctaLabel: '去消费试试',
    },
    {
      label: '变动笔数',
      value: String(totalTx),
      trend: `充值 ${d.recharge_count || 0} · 消费 ${d.consume_count || 0}`,
      trendType: 'up',
      theme: 'purple',
      icon: '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
      isZero: totalTx <= 0,
      ctaLink: totalTx <= 0 ? '/pc/user?tab=balance-log' : '',
      ctaLabel: '查看资金明细',
    },
  ]
})

// 近 7 日趋势图（真实数据：以消费为主曲线，hover 展示充值+消费）
const chartValues = computed(() => (dashboard.value.trend_7d || []).map(d => Number(d.consume || 0)))
const chartRechargeValues = computed(() => (dashboard.value.trend_7d || []).map(d => Number(d.recharge || 0)))
const chartLabels = computed(() => {
  if (dashboard.value.trend_7d?.length) return dashboard.value.trend_7d.map(d => d.date)
  // 兜底（未加载时的占位日期）
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date()
    d.setDate(d.getDate() - (6 - i))
    return `${d.getMonth() + 1}/${d.getDate()}`
  })
})
const chartMax = computed(() => {
  const arr = [...chartValues.value, ...chartRechargeValues.value, 1]
  const raw = Math.max(...arr)
  // 向上取整到 100/1000 档位
  if (raw <= 10) return 10
  const step = raw > 1000 ? 1000 : (raw > 100 ? 100 : 10)
  return Math.ceil(raw / step) * step
})

// 趋势图是否全部为 0（新用户、无任何充值/消费记录）
const chartAllZero = computed(() => {
  return Math.max(...chartValues.value, ...chartRechargeValues.value, 0) === 0
})

// 消费类型分布 → 分配颜色（保持条形图视觉多样性）
const _consumeColorPalette = ['#0d9488', '#f97316', '#6366f1', '#0ea5e9', '#8b5cf6', '#ec4899', '#14b8a6', '#f59e0b']
const consumeDistWithColor = computed(() => {
  const list = dashboard.value.consume_dist_30d || []
  return list.map((item, i) => ({ ...item, color: _consumeColorPalette[i % _consumeColorPalette.length] }))
})

// ===== 消费洞察派生指标 =====
const rechargeRatio = computed(() => {
  const r = Number(dashboard.value.total_recharge || 0)
  const c = Number(dashboard.value.total_consume || 0)
  const sum = r + c
  return sum > 0 ? Math.round((r / sum) * 100) : 50
})
const consumeRatio = computed(() => 100 - rechargeRatio.value)
const avgConsumePerTx = computed(() => {
  const c = Number(dashboard.value.total_consume || 0)
  const n = Number(dashboard.value.consume_count || 0)
  return n > 0 ? c / n : 0
})
const netInflow = computed(() => Number(dashboard.value.total_recharge || 0) - Number(dashboard.value.total_consume || 0))
const totalTxCount = computed(() => (Number(dashboard.value.recharge_count) || 0) + (Number(dashboard.value.consume_count) || 0))

const chartPoints = computed(() => {
  const values = chartValues.value.length ? chartValues.value : [0, 0, 0, 0, 0, 0, 0]
  return values.map((v, i) => ({
    x: 20 + (i * 760) / (values.length - 1),
    y: 180 - (v / chartMax.value) * 145
  }))
})

const rechargePoints = computed(() => {
  const values = chartRechargeValues.value.length ? chartRechargeValues.value : [0, 0, 0, 0, 0, 0, 0]
  return values.map((v, i) => ({
    x: 20 + (i * 760) / (values.length - 1),
    y: 180 - (v / chartMax.value) * 145
  }))
})

const rechargeLinePath = computed(() => {
  return rechargePoints.value
    .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`)
    .join(' ')
})

const linePath = computed(() => {
  return chartPoints.value
    .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`)
    .join(' ')
})

const areaPath = computed(() => {
  const points = chartPoints.value
  if (!points.length) return ''
  const first = points[0]
  const last = points[points.length - 1]
  return `M ${first.x} 180 ${points.map(p => `L ${p.x} ${p.y}`).join(' ')} L ${last.x} 180 Z`
})

const chartHover = ref(-1)
const handleChartMove = (e) => {
  const rect = e.currentTarget.getBoundingClientRect()
  const x = (e.clientX - rect.left) * (800 / rect.width)
  const distances = chartPoints.value.map(p => Math.abs(p.x - x))
  const minIndex = distances.indexOf(Math.min(...distances))
  chartHover.value = minIndex
}

// ===== 系统公告（工作台仅展示最新几条，点击查看详情）=====
const notices = ref([])
const noticeLoading = ref(false)
const noticeDetailVisible = ref(false)
const noticeDetailData = ref({ title: '', content: '', update_time: 0 })

// 公告中心 tab: system=系统公告
const noticeTab = ref('system')

async function loadNotices() {
  noticeLoading.value = true
  try {
    const res = await api.get('/api/announcement/lists', { page_no: 1, page_size: 4 })
    if (res.ok) {
      notices.value = (res.data.list || []).map(item => ({
        id: item.id,
        title: item.title || '未命名公告',
        time: formatNoticeTime(item.update_time),
      }))
    }
  } catch (e) {}
  noticeLoading.value = false
}

async function openNotice(item) {
  if (!item?.id) return
  try {
    const res = await api.get('/api/announcement/detail', { id: item.id })
    if (res.ok) {
      noticeDetailData.value = {
        title: res.data.title || '',
        content: res.data.content || '',
        update_time: res.data.update_time || 0,
      }
      noticeDetailVisible.value = true
    }
  } catch (e) {}
}

// 切换公告中心 tab
async function switchNoticeTab(tab) {
  noticeTab.value = tab
}

function formatNoticeTime(ts) {
  if (!ts) return ''
  let d
  if (typeof ts === 'number' || /^\d+$/.test(String(ts))) {
    d = new Date(Number(ts) * 1000)
  } else {
    d = new Date(String(ts).replace(/-/g, '/'))
  }
  if (isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

// ===== 账号绑定信息（手机号/邮箱/微信）=====
// 手机号脱敏：中间4位用 * 替换
const bindMobile = computed(() => {
  const m = auth.user.value?.mobile || ''
  if (!m) return ''
  const s = String(m)
  if (s.length >= 11) return s.slice(0, 3) + '****' + s.slice(-4)
  return s
})
const bindEmail = computed(() => auth.user.value?.email || '')
const bindWechat = computed(() => !!auth.user.value?.has_auth)

// ===== 账号安全分数计算（每绑定一项 + 约33分，满分100，四舍五入）=====
const securityScore = computed(() => {
  let score = 10 // 基础分 10
  if (auth.user.value?.mobile) score += 30
  if (auth.user.value?.email) score += 30
  if (auth.user.value?.has_auth) score += 30
  return Math.min(100, score)
})

// ===== 账号设置（个人资料 + 修改密码）=====
const settingsForm = ref({ nickname: '' })
const settingsSaveLoading = ref(false)
const settingsSaveSuccess = ref(false)
const settingsSaveError = ref('')
const editingNickname = ref(false) // 默认展示态：昵称显示当前值+修改按钮，不显示长条input
const cancelNicknameEdit = () => {
  editingNickname.value = false
  settingsSaveError.value = ''
  settingsSaveSuccess.value = false
  // 回滚成 auth.user 原始值
  settingsForm.value.nickname = auth.user.value?.nickname || ''
}

const pwdForm = ref({ old_password: '', new_password: '', confirm_password: '' })
const pwdSaveLoading = ref(false)
const pwdSaveSuccess = ref(false)
const pwdSaveError = ref('')
const editingPassword = ref(false) // 默认展示态：密码显示状态+强度+CTA，不显示长条input
const cancelPasswordEdit = () => {
  editingPassword.value = false
  pwdSaveError.value = ''
  pwdSaveSuccess.value = false
  pwdForm.value = { old_password: '', new_password: '', confirm_password: '' }
}

// ===== 设置页专属 4 张统计卡（更贴合"设置/安全/资料"主题）=====
// 账号安全分（0-100）：根据绑定平台+密码长度+是否有昵称 加权，满 3 项绑定+有密码+昵称>=2 = 100分
const accountSecurityScore = computed(() => {
  let score = 0
  // 绑定项加权：每绑定一个（手机/邮箱/微信）+25
  if (bindMobile.value) score += 25
  if (bindEmail.value) score += 25
  if (bindWechat.value) score += 25
  // 是否有昵称
  if ((auth.user.value?.nickname || '').length >= 2) score += 10
  // 是否设置过密码（通常 mobile/email 绑定就有密码）
  if (bindMobile.value || bindEmail.value) score += 10
  // 是否有头像（简单判断：用户有 avatar_url 字段）
  if (auth.user.value?.avatar?.url) score += 5
  return Math.min(100, score)
})
const profileCompleteness = computed(() => {
  let total = 0, ok = 0
  total++; if ((auth.user.value?.nickname || '').length >= 2) ok++
  total++; if (bindMobile.value) ok++
  total++; if (bindEmail.value) ok++
  total++; if (auth.user.value?.avatar?.url) ok++
  return total ? Math.round((ok / total) * 100) : 0
})
const boundPlatformCount = computed(() => {
  return (bindMobile.value ? 1 : 0) + (bindEmail.value ? 1 : 0) + (bindWechat.value ? 1 : 0)
})
// 密码强度等级：基于 新密码长度（展示态给一个基准，如果用户有手机/邮箱，默认为中）
const passwordStrengthText = computed(() => {
  if (!bindMobile.value && !bindEmail.value) return '未设置'
  // 如果用户有绑定，默认展示"中等"；当用户在修改密码时，根据新密码实时判断
  const np = pwdForm.value.new_password
  if (editingPassword.value && np) {
    if (np.length >= 12 && /[A-Za-z]/.test(np) && /\d/.test(np) && /[^A-Za-z0-9]/.test(np)) return '很强'
    if (np.length >= 8 && /[A-Za-z]/.test(np) && /\d/.test(np)) return '强'
    if (np.length >= 6) return '中等'
    return '弱'
  }
  return '中等'
})

const settingsStats = computed(() => [
  {
    label: '账号安全分',
    value: accountSecurityScore.value + ' 分',
    trend: accountSecurityScore.value >= 90 ? '非常安全，继续保持 ✓' : (accountSecurityScore.value >= 60 ? '建议完善第三方绑定' : '风险偏高，快去绑定账号'),
    trendType: accountSecurityScore.value >= 60 ? 'up' : 'warn',
    theme: 'teal',
    icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    isZero: false,
    ctaLink: accountSecurityScore.value < 80 ? '#bindings' : '',
    ctaLabel: '提升安全分 →',
  },
  {
    label: '资料完整度',
    value: profileCompleteness.value + ' %',
    trend: `已完善 ${Math.round(profileCompleteness.value / 25)} / 4 项`,
    trendType: profileCompleteness.value >= 75 ? 'up' : 'warn',
    theme: 'cyan',
    icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    isZero: profileCompleteness.value === 0,
    ctaLink: profileCompleteness.value < 100 ? '#profile' : '',
    ctaLabel: '完善资料 →',
  },
  {
    label: '绑定平台',
    value: boundPlatformCount.value + ' / 3',
    trend: `${bindMobile.value ? '✓ 手机 ' : '✗ 手机 '}${bindEmail.value ? '✓ 邮箱 ' : '✗ 邮箱 '}${bindWechat.value ? '✓ 微信' : '✗ 微信'}`,
    trendType: boundPlatformCount.value >= 2 ? 'up' : 'warn',
    theme: 'orange',
    icon: '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
    isZero: boundPlatformCount.value === 0,
    ctaLink: boundPlatformCount.value < 3 ? '#bindings' : '',
    ctaLabel: '去绑定 →',
  },
  {
    label: '密码强度',
    value: passwordStrengthText.value,
    trend: editingPassword.value && pwdForm.value.new_password.length === 0 ? '输入新密码实时查看强度' : '建议字母+数字+符号 ≥8位',
    trendType: passwordStrengthText.value === '很强' || passwordStrengthText.value === '强' ? 'up' : (passwordStrengthText.value === '未设置' ? 'warn' : 'neutral'),
    theme: 'purple',
    icon: '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    isZero: false,
    ctaLink: passwordStrengthText.value === '未设置' ? '#password' : '',
    ctaLabel: '修改密码 →',
  },
])

// 用户信息就绪后填充设置表单
watch(
  () => auth.user.value,
  (u) => {
    if (u) {
      settingsForm.value.nickname = u.nickname || ''
    }
  },
  { immediate: true }
)

// 保存个人资料（昵称）
async function handleSaveProfile() {
  settingsSaveLoading.value = true
  settingsSaveSuccess.value = false
  settingsSaveError.value = ''
  try {
    const nickname = String(settingsForm.value.nickname || '').trim()
    if (nickname.length < 2 || nickname.length > 20) {
      settingsSaveError.value = '昵称长度需为 2-20 个字符'
      return
    }
    const res = await api.post('/api/user/update', { nickname })
    if (!res.ok) {
      settingsSaveError.value = res.msg || '保存失败，请稍后重试'
      return
    }
    settingsSaveSuccess.value = true
    await auth.fetchUser()
    editingNickname.value = false
    setTimeout(() => { settingsSaveSuccess.value = false }, 2500)
  } catch (e) {
    settingsSaveError.value = e?.message || '保存失败，请稍后重试'
  } finally {
    settingsSaveLoading.value = false
  }
}

// 修改登录密码
async function handleChangePwd() {
  pwdSaveLoading.value = true
  pwdSaveSuccess.value = false
  pwdSaveError.value = ''
  try {
    const { old_password, new_password, confirm_password } = pwdForm.value
    if (!old_password) {
      pwdSaveError.value = '请输入原密码'
      return
    }
    if (!new_password || new_password.length < 6 || new_password.length > 32) {
      pwdSaveError.value = '新密码长度需为 6-32 位'
      return
    }
    if (new_password !== confirm_password) {
      pwdSaveError.value = '两次输入的新密码不一致'
      return
    }
    const res = await api.post('/api/user/changePwd', { old_password, new_password })
    if (!res.ok) {
      pwdSaveError.value = res.msg || '修改失败，请稍后重试'
      return
    }
    pwdForm.value = { old_password: '', new_password: '', confirm_password: '' }
    pwdSaveSuccess.value = true
    editingPassword.value = false
    toast.success('密码修改成功')
    setTimeout(() => { pwdSaveSuccess.value = false }, 2500)
  } catch (e) {
    pwdSaveError.value = e?.message || '修改失败，请稍后重试'
  } finally {
    pwdSaveLoading.value = false
  }
}

// ===== 最近资金变动（替换原先的假订单列表）=====
const orderFilter = ref('all')
const orderTabs = [
  { key: 'all', label: '全部' },
  { key: 'income', label: '收入' },
  { key: 'expense', label: '支出' }
]

const recentLogsFinal = computed(() => dashboard.value.recent_logs || [])

const orderCounts = computed(() => {
  const list = recentLogsFinal.value
  return {
    all: list.length,
    processing: list.filter(o => o.is_income).length,
    completed: list.filter(o => !o.is_income).length,
    income: list.filter(o => o.is_income).length,
    expense: list.filter(o => !o.is_income).length,
  }
})

const filteredRecentOrders = computed(() => {
  const list = recentLogsFinal.value
  let out = list
  if (orderFilter.value === 'income') out = list.filter(o => o.is_income)
  else if (orderFilter.value === 'expense') out = list.filter(o => !o.is_income)
  return out.slice(0, 5)
})

const rechargeDonutSliceIcon = '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
const consumeDonutSliceIcon = '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'
</script>

<style scoped>
.user-center {
  color: var(--dark-800);
  max-width: none;
  margin: 0 auto;
  width: 100%;
  padding: 0 24px;
  box-sizing: border-box;
}

.level-chip {
  display: inline-flex;
  align-items: center;
  padding: 0 6px;
  border-radius: 3px;
  font-size: 10px;
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: 0.01em;
}

.level-chip.lv-1 {
  background: rgba(15, 23, 42, 0.06);
  color: var(--dark-800);
  border: 1px solid rgba(15, 23, 42, 0.1);
}

.level-chip.lv-2 {
  background: rgba(20, 184, 166, 0.08);
  color: #0f766e;
  border: 1px solid rgba(20, 184, 166, 0.18);
}

.level-chip.lv-3 {
  background: rgba(249, 115, 22, 0.08);
  color: #c2410c;
  border: 1px solid rgba(249, 115, 22, 0.18);
}

.dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin: 0 auto;
  width: 100%;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.stat-card {
  position: relative;
  display: flex;
  justify-content: space-between;
  padding: 24px;
  border-radius: 16px;
  border: none;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
  overflow: hidden;
}

/* 每张卡整体浅色渐变底（无边框 + 无顶部色条，避免"AI 味"边框配色） */
.stat-card.teal   { background: linear-gradient(135deg, #f3fbf9 0%, #e0f5f0 100%); }
.stat-card.orange { background: linear-gradient(135deg, #fff7ee 0%, #ffe8d1 100%); }
.stat-card.cyan   { background: linear-gradient(135deg, #f1fafd 0%, #dfeffa 100%); }
.stat-card.purple { background: linear-gradient(135deg, #f8f4ff 0%, #ede3fb 100%); }

.stat-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-label {
  font-size: 13px;
  color: var(--gray-500);
  font-weight: 500;
}

.stat-value {
  font-size: 26px;
  font-weight: 800;
  color: var(--dark-800);
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.stat-trend.up {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.stat-trend.warn {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

.stat-icon {
  width: 52px;
  height: 52px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  color: var(--white);
  flex-shrink: 0;
}

.stat-card.teal .stat-icon { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.stat-card.orange .stat-icon { background: linear-gradient(135deg, #fb923c, #f97316); }
.stat-card.cyan .stat-icon { background: linear-gradient(135deg, #22d3ee, #06b6d4); }
.stat-card.purple .stat-icon { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

/* 顶卡 0 值引导：让"空卡片"有交互，避免一片死寂 */
.stat-card.is-zero {
  background: linear-gradient(180deg, rgba(248,250,252,0.9), #fff);
}
.stat-cta {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 6px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  background: rgba(13, 148, 136, 0.08);
  color: #0f766e;
  width: fit-content;
  text-decoration: none;
  transition: background .2s, color .2s, transform .2s;
}
.stat-cta:hover {
  background: #0f766e;
  color: #fff;
  transform: translateY(-1px);
}
.stat-card.orange .stat-cta { background: rgba(249, 115, 22, 0.09); color: #c2410c; }
.stat-card.orange .stat-cta:hover { background: #c2410c; color: #fff; }
.stat-card.cyan .stat-cta   { background: rgba(6, 182, 212, 0.09); color: #0e7490; }
.stat-card.cyan .stat-cta:hover { background: #0e7490; color: #fff; }
.stat-card.purple .stat-cta { background: rgba(139, 92, 246, 0.09); color: #6d28d9; }
.stat-card.purple .stat-cta:hover { background: #6d28d9; color: #fff; }

/* ===== 下级（代理）信息卡片 ===== */
.sub-stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-top: 20px;
}
.sub-stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-radius: 16px;
  border: none;
  color: inherit;
  text-decoration: none;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
  transition: transform .18s ease, box-shadow .18s ease;
}
.sub-stat-card:nth-child(1) { background: linear-gradient(135deg, #eef6ff 0%, #dcebfd 100%); }
.sub-stat-card:nth-child(2) { background: linear-gradient(135deg, #f2f9f1 0%, #e0f0df 100%); }
.sub-stat-card:nth-child(3) { background: linear-gradient(135deg, #fff5ef 0%, #fce6d6 100%); }
.sub-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
}
.sub-stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  color: #fff;
  flex-shrink: 0;
}
.sub-icon-team   { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.sub-icon-order  { background: linear-gradient(135deg, #22c55e, #15803d); }
.sub-icon-amount { background: linear-gradient(135deg, #f97316, #c2410c); }
.sub-stat-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
  min-width: 0;
}
.sub-stat-value {
  font-size: 22px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.2;
}
.sub-stat-label {
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
}
.sub-stat-arrow {
  flex-shrink: 0;
  color: #94a3b8;
  transition: transform .18s, color .18s;
}
.sub-stat-card:hover .sub-stat-arrow {
  transform: translateX(2px);
  color: #475569;
}

/* 趋势图空态占位 */
.chart-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 30px 16px;
  text-align: center;
  color: var(--gray-500);
  border: 1px dashed #e2e8f0;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fafc, #fff);
}
.chart-empty p { margin: 0; font-size: 14px; font-weight: 600; color: #334155; }
.chart-empty-sub { font-size: 12px !important; font-weight: 500 !important; color: #64748b !important; margin-bottom: 10px !important; }
.chart-empty-btn { margin-top: 8px; }
.btn-sm { padding: 7px 14px; font-size: 13px; }

/* ===== 我的产出（第二行左） ===== */
.output-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.output-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border-radius: 12px;
  border: none;
  color: inherit;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
  transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
}
/* 整体浅色渐变底（与顶卡统一，去掉边框配色） */
.output-card-paper { background: linear-gradient(135deg, #f3fbf9 0%, #e2f5f0 100%); }
.output-card-ppt   { background: linear-gradient(135deg, #fff7ee 0%, #ffe8d1 100%); }
.output-card-tpl   { background: linear-gradient(135deg, #f1fafd 0%, #dfeffa 100%); }
.output-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.09);
  filter: brightness(1.01);
}
.output-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  color: #fff;
  flex-shrink: 0;
}
.output-card-paper .output-icon { background: linear-gradient(135deg, #14b8a6, #0f766e); }
.output-card-ppt   .output-icon { background: linear-gradient(135deg, #fb923c, #ea580c); }
.output-card-tpl   .output-icon { background: linear-gradient(135deg, #22d3ee, #0284c7); }

.output-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.output-info b {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-800);
  line-height: 1.1;
}
.output-info span {
  font-size: 12px;
  color: var(--gray-500);
  margin-top: 3px;
  font-weight: 500;
}
.output-footer {
  display: flex;
  gap: 10px;
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px dashed #e2e8f0;
}
.output-footer-link {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  text-decoration: none;
  transition: background .18s, color .18s, border-color .18s;
}
.output-footer-link:hover {
  background: var(--primary-50);
  border-color: var(--primary-300);
  color: var(--primary-700);
}

/* ===== 消费分布条形图 + 小点 ===== */
.svc-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
  vertical-align: middle;
  flex-shrink: 0;
}
.skeleton-line.w-100 { width: 100%; }

/* 消费分布空态 */
.consume-empty {
  flex: 1;
  min-height: 180px;
  border: 1px dashed #e2e8f0;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fafc, #fff);
}
.consume-empty .btn-outline {
  margin-top: 6px;
}

/* ===== 消费洞察 ===== */
.consume-insight-panel .insight-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}
.insight-ratio-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.insight-ratio-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.ratio-tag {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 6px;
}
.ratio-tag-recharge {
  color: #c2410c;
  background: #fff7ed;
}
.ratio-tag-consume {
  color: #0f766e;
  background: #f0fdfa;
}
.insight-ratio-bar {
  display: flex;
  height: 12px;
  border-radius: 6px;
  overflow: hidden;
  background: #f1f5f9;
}
.ratio-fill {
  height: 100%;
  transition: width .4s ease;
}
.ratio-fill-recharge {
  background: linear-gradient(90deg, #fb923c, #f97316);
}
.ratio-fill-consume {
  background: linear-gradient(90deg, #14b8a6, #0d9488);
}
.insight-ratio-legend {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #64748b;
}
.insight-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.insight-metric {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 14px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}
.metric-label {
  font-size: 11px;
  font-weight: 500;
  color: #94a3b8;
}
.metric-value {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.2;
}
.metric-value.positive {
  color: #0f766e;
}
.metric-value.negative {
  color: #b91c1c;
}
.metric-value.gift {
  color: #7c3aed;
}
.insight-metric-skeleton {
  padding: 12px 14px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-side {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }
}

.dashboard-main {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  align-items: stretch;
}

.panel.span-2 {
  grid-column: span 2;
}

@media (max-width: 900px) {
  .dashboard-main {
    grid-template-columns: 1fr;
  }

  .panel.span-2 {
    grid-column: span 1;
  }
}

.dashboard-side {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.panel {
  background: var(--white);
  border-radius: var(--radius-lg);
  border: 1px solid var(--gray-100);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
  padding: 24px;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.chart-panel,
.donut-panel {
  min-height: 340px;
}

/* 主区面板行列高度对齐：
   Row1：近7日趋势(span-2) + 我的产出(1col)，两者等高
   Row2：消费分布(1col) + 消费洞察(1col) + 最近资金变动(1col)，三者等高
   align-items:stretch 已在 dashboard-main 上开启，格子自动撑满行高，下面只给 min 兜底 */
.my-output-panel,
.consume-dist-panel,
.consume-insight-panel {
  min-height: 340px;
}

.orders-panel,
.extra-panel {
  height: auto;
  min-height: 340px;
}

.panel-header {
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
}

.panel-header h3 {
  font-size: 17px;
  font-weight: 800;
  color: var(--dark-800);
}

.panel-desc {
  font-size: 13px;
  color: var(--gray-500);
  margin-top: 4px;
}

.badge {
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: rgba(20, 184, 166, 0.1);
  color: var(--primary-600);
}

.text-link {
  font-size: 13px;
  color: var(--primary-600);
  font-weight: 600;
}

.text-link:hover {
  color: var(--accent-500);
}

.trend-chart {
  position: relative;
  flex: 1;
  min-height: 0;
  cursor: crosshair;
}

.trend-svg {
  width: 100%;
  height: 100%;
  overflow: visible;
}

.grid-lines line {
  stroke: var(--gray-100);
  stroke-width: 1;
  stroke-dasharray: 4 4;
}

.y-labels text {
  font-size: 11px;
  fill: var(--gray-400);
}

.x-labels {
  display: flex;
  justify-content: space-between;
  padding: 4px 20px 0 20px;
  margin-top: -4px;
}

.x-labels span {
  font-size: 12px;
  color: var(--gray-500);
  font-weight: 500;
  text-align: center;
  flex: 1;
}

circle {
  transition: r 0.2s ease, stroke-width 0.2s ease;
  cursor: pointer;
}

circle.active {
  r: 7;
  stroke-width: 3.5;
}

.hover-line {
  stroke: var(--primary-300);
  stroke-width: 1;
  stroke-dasharray: 4 4;
}

.hover-card {
  fill: var(--dark-800);
}

.hover-date {
  font-size: 11px;
  fill: rgba(255, 255, 255, 0.7);
}

.hover-value {
  font-size: 13px;
  font-weight: 700;
  fill: var(--white);
}

.donut-chart {
  position: relative;
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.donut-svg {
  width: 180px;
  height: 180px;
}

.donut-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.donut-center b {
  display: block;
  font-size: 24px;
  font-weight: 900;
  color: var(--dark-800);
}

.donut-center span {
  font-size: 12px;
  color: var(--gray-500);
}

.donut-legend {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 12px;
  flex-shrink: 0;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  white-space: nowrap;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-name {
  color: var(--gray-600);
}

.legend-value {
  font-weight: 700;
  color: var(--dark-800);
}

.order-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
}

.order-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  background: #f1f5f9;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.order-tab:hover {
  background: #e2e8f0;
  color: var(--dark-700);
}

.order-tab.active {
  background: var(--primary-500);
  color: var(--white);
}

.tab-count {
  padding: 2px 7px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.2);
}

.order-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-right: 4px;
}

.order-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--radius-md);
  background: #fafafa;
  transition: all 0.2s ease;
}

.order-item:hover {
  background: #f1f5f9;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
}

.order-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: var(--white);
  flex-shrink: 0;
}

.order-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.order-icon.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
.order-icon.cyan { background: linear-gradient(135deg, #22d3ee, #06b6d4); }
.order-icon.purple { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

.order-info {
  flex: 1;
  min-width: 0;
}

.order-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 2px;
  min-width: 0;
}

.order-type {
  font-weight: 700;
  font-size: 14px;
  color: var(--dark-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex-shrink: 0;
}

.order-id {
  font-size: 11px;
  color: var(--gray-500);
  font-family: monospace;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.order-time {
  font-size: 12px;
  color: var(--gray-500);
}

.order-progress {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.order-progress span {
  font-size: 12px;
  font-weight: 700;
  color: #d97706;
  white-space: nowrap;
}

.mini-progress {
  flex: 1;
  max-width: 140px;
  height: 5px;
  border-radius: 999px;
  background: #fed7aa;
  overflow: hidden;
}

.mini-progress-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #f59e0b, #f97316);
}

.order-right {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-end;
}

.order-amount {
  font-size: 16px;
  font-weight: 800;
  color: var(--dark-800);
}

.order-amount.income { color: #059669; }
.order-amount.expense { color: #dc2626; }

/* ============ 过滤标签 tabs ============ */
.filter-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.filter-tab {
  padding: 5px 14px;
  border-radius: 12px;
  font-size: 12.5px;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  cursor: pointer;
}

.filter-tab:hover {
  /* 不做浮动/位移变化，仅轻微加深文字色 */
  color: #334155;
}

.filter-tab.active {
  color: #0f766e;
  background: linear-gradient(180deg, #f0fdfa 0%, #ccfbf1 100%);
  border-color: #14b8a6;
  box-shadow: inset 0 0 0 1px rgba(20, 184, 166, 0.1);
}

/* ============ 金额变动明细列表样式（无 hover 浮动效果） ============ */
/* 适当突破 user-center 内边距 8px，让表格宽一些（左右各 +24px），
   但保留 user-center 外层两侧的大块留白（不影响其它 tab 的全局 max-width 1160px 布局）。
   注意：balance-log-page 与 dashboard 位于 DOM 同一节点，因此同层用 :is 选择器，不写 .balance-log-page .dashboard（子孙匹配失效） */
.balance-log-page {
  width: calc(100% + 48px);
  margin-left: -24px;
  margin-right: -24px;
  max-width: none;
  overflow-x: hidden;
}
:is(.dashboard.balance-log-page) {
  /* 两个 class 同节点时的补充：让 padding/max-width 在同一层生效 */
  padding: 0;
  max-width: 100%;
  width: calc(100% + 48px);
}

/* 主面板容器：高级质感卡片 */
.bl-panel {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #ececf1;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.035), 0 8px 24px rgba(15, 23, 42, 0.055);
  overflow: hidden;
  /* 垂直滚动条常显占位：避免内容区高度变化导致滚动条出现/消失引发的页面宽度抖动 */
  scrollbar-gutter: stable;
}

/* 内容主体：统一高度基准 —— 约能容纳 10 行 + 分页，避免 loading/空态/分页切换 时窗口高度变化 */
.bl-body {
  min-height: 660px;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Loading 状态：居中 spinner + 文字（整体页面型，保留备用） */
.bl-loading {
  flex: 1;
  min-height: 560px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 20px;
}
.bl-spinner {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 3px solid #e2e8f0;
  border-top-color: #14b8a6;
  border-right-color: #14b8a6;
  animation: bl-spin 0.85s linear infinite;
  box-shadow: 0 0 0 1px rgba(20, 184, 166, 0.05) inset;
}
/* 小号 spinner：用于表格遮罩内 */
.bl-spinner-sm {
  width: 30px;
  height: 30px;
  border-width: 2.5px;
  margin: 0 auto;
}
@keyframes bl-spin {
  to { transform: rotate(360deg); }
}
.bl-loading-text {
  margin: 0;
  color: #64748b;
  font-size: 13.5px;
  letter-spacing: 0.3px;
  font-weight: 500;
}
.bl-loading-text-sm {
  font-size: 12.5px;
  margin: 10px 0 0;
  text-align: center;
}

/* 列表区容器：加 relative 给遮罩定位；固定最小高度，让末页不足 10 条时分页条位置仍不变 */
.balance-log-list-wrap {
  position: relative;
  /* 精确固定高：表头约 47px + 10行 × 56px = 607，再留点余量 616 保证稳定不跳 */
  min-height: 616px;
  padding: 0 4px 4px;
}

/* 遮罩区：只盖 tbody 不盖表头；精确 top = 表头底部 */
.bl-table-mask {
  position: absolute;
  left: 0;
  right: 0;
  top: 47px; /* 表头 th padding 14×2 + 1px border + 12px 文字 ≈ 47px */
  bottom: 0;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  z-index: 5;
  border-radius: 0 0 10px 10px;
  pointer-events: none;
}

/* 空态行：占满表格体高度，与 tbody 10 条等高（= 行高 56 × 10），让分页条位置绝对不跳 */
.bl-empty-cell {
  height: 560px;
  vertical-align: middle;
  text-align: center;
  background: #fff;
}
.bl-empty-text {
  margin: 0;
  color: #94a3b8;
  font-size: 13.5px;
  letter-spacing: 0.2px;
  font-weight: 500;
}

/* 分页条：加载态 / 空态 样式微调 */
.bl-pagination.is-loading {
  opacity: 0.85;
}
.bl-pagination.is-empty .bl-pg-btns {
  opacity: 0.55;
  pointer-events: none;
}
.bl-pg-placeholder {
  color: #94a3b8;
  font-size: 12.5px;
}

/* 头部：标题 + 筛选标签 */
.bl-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 20px 24px 18px;
  border-bottom: 1px solid #f1f1f5;
  background: linear-gradient(180deg, #fcfcfd 0%, #ffffff 100%);
}
.bl-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.bl-panel-title {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: 0.2px;
}
.bl-panel-desc {
  margin: 0;
  font-size: 12.5px;
  color: #64748b;
}

/* 表格外层包裹 */
/* （注意：.balance-log-list-wrap 的主定义已在前面的遮罩/稳定高度区，这里禁止重复定义，否则会覆盖 min-height / position） */

.balance-log-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13.5px;
  /* 让表格行高严格按 td 的 height，避免内容换行抖动 */
  table-layout: fixed;
}

/* 表头：高级质感 + 更紧凑层次 */
.balance-log-table thead tr {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  box-shadow: inset 0 -1px 0 #e2e8f0;
}
.balance-log-table th {
  padding: 14px 18px;
  font-weight: 600;
  font-size: 12px;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
  letter-spacing: 0.4px;
  text-transform: none;
  position: relative;
}
.balance-log-table th::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: -1px;
  height: 1px;
  background: #e2e8f0;
}
.balance-log-table th:first-child {
  padding-left: 24px;
}
.balance-log-table th:last-child {
  padding-right: 24px;
}

/* 表格行：行高适度放宽 + 柔和斑马纹 + 悬停高亮 */
.balance-log-table tbody tr.balance-log-row {
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.18s ease, box-shadow 0.18s ease;
  height: 56px;
}
.balance-log-table tbody tr.balance-log-row:nth-child(even) {
  background: #fafbfc;
}
.balance-log-table tbody tr.balance-log-row:hover {
  background: #f0f9ff;
  box-shadow: inset 3px 0 0 #14b8a6;
}
.balance-log-table tbody tr.balance-log-row:last-child {
  border-bottom: none;
}
.balance-log-table tbody tr.balance-log-row:last-child td:first-child {
  border-bottom-left-radius: 10px;
}
.balance-log-table tbody tr.balance-log-row:last-child td:last-child {
  border-bottom-right-radius: 10px;
}

/* td：内容垂直居中、字号统一 */
.balance-log-table td {
  padding: 12px 18px;
  color: #334155;
  vertical-align: middle;
  line-height: 1.5;
  height: 56px;
  box-sizing: border-box;
}
.balance-log-table td:first-child {
  padding-left: 24px;
}
.balance-log-table td:last-child {
  padding-right: 24px;
}

.log-align-right {
  text-align: right !important;
}

/* ========== 类型列：彩色胶囊 + 圆点指示 ========== */
.log-type-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px 4px 8px;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 600;
  line-height: 1.4;
  max-width: 100%;
  border: 1px solid transparent;
  letter-spacing: 0.1px;
  background: #f1f5f9;
  color: #0f172a;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.balance-log-row:hover .log-type-tag {
  transform: translateY(-0.5px);
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
}
.log-type-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.7);
}
.log-type-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}
/* 类型配色：浅色底 + 深色字 + 同色圆点（XMind 风格的柔和配色） */
.log-type-tag.type-recharge   { color: #0e7490; background: #ecfeff; border-color: #a5f3fc; }  /* 平台充值：青蓝 */
.log-type-tag.type-agentin    { color: #0369a1; background: #f0f9ff; border-color: #bae6fd; }  /* 上级代充：天蓝 */
.log-type-tag.type-consume    { color: #c2410c; background: #fff7ed; border-color: #fed7aa; }  /* 消费：橙红 */
.log-type-tag.type-subrecharge{ color: #7c3aed; background: #f5f3ff; border-color: #ddd6fe; }  /* 给下级充值/扣减：紫 */
.log-type-tag.type-income     { color: #0f766e; background: #ecfdf5; border-color: #a7f3d0; }  /* 收益返佣：绿青 */
.log-type-tag.type-refund     { color: #1e40af; background: #eff6ff; border-color: #bfdbfe; }  /* 退款：靛蓝 */
.log-type-tag.type-default    { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }  /* 默认：灰 */

/* 单号：等宽字体 + 单行截断 + 偏暗色 */
.log-sn {
  font-family: 'SF Mono', 'Monaco', 'Consolas', 'Menlo', monospace;
  color: #64748b;
  font-size: 12px;
  letter-spacing: 0.1px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.balance-log-row:hover .log-sn {
  color: #334155;
}

/* 时间：等宽字体 */
.log-time {
  color: #64748b;
  font-size: 12.5px;
  white-space: nowrap;
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 备注：单行省略；hover 整行时颜色加深 */
.log-remark {
  color: #475569;
  font-size: 13px;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.balance-log-row:hover .log-remark {
  color: #0f172a;
}

/* 余额：等宽字体 + 弱化色彩 */
.log-balance {
  color: #94a3b8;
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  font-size: 12.5px;
  letter-spacing: 0.1px;
  white-space: nowrap;
  font-weight: 500;
}
/* 变动后余额：与变动前对比更弱（作为"结果"参考） */
.log-balance-after {
  color: #64748b;
}

/* 变动金额：突出展示 - 等宽 + 加大字号 + 上下标结构 */
.log-change {
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  white-space: nowrap;
  display: inline-flex;
  align-items: baseline;
  gap: 0;
  letter-spacing: 0.2px;
  transition: transform 0.15s ease;
}
.balance-log-row:hover .log-change {
  transform: scale(1.04);
}
.log-change-prefix {
  font-size: 14px;
  font-weight: 700;
  margin-right: 1px;
  line-height: 1;
}
.log-change-amount {
  font-size: 14.5px;
  font-weight: 700;
  line-height: 1;
}
.log-change.income {
  color: #059669;
}
.log-change.income .log-change-prefix {
  color: #10b981;
}
.log-change.expense {
  color: #dc2626;
}
.log-change.expense .log-change-prefix {
  color: #f87171;
}

/* 状态列：精致胶囊 - 加深边框/字号协调 */
.log-status-cell {
  text-align: center;
  padding-left: 4px !important;
  padding-right: 4px !important;
}
.bl-status-tag {
  display: inline-block;
  padding: 3px 10px;
  min-width: 0;
  border-radius: 12px;
  font-size: 11.5px;
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: 0.2px;
  white-space: nowrap;
  border: 1px solid transparent;
}
.bl-status-tag.pending {
  background: #fff7ed;
  color: #9a3412;
  border-color: #fed7aa;
}
.bl-status-tag.success {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}
.bl-status-tag.refund {
  background: #eff6ff;
  color: #1e40af;
  border-color: #bfdbfe;
}
.bl-status-tag.failed,
.bl-status-tag.cancel {
  background: #fef2f2;
  color: #991b1b;
  border-color: #fecaca;
}

/* 分页控件样式 */
.bl-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 14px 24px 18px;
  border-top: 1px solid #ececf1;
  background: #fafbfd;
}
.bl-pg-info {
  color: #64748b;
  font-size: 12.5px;
}
.bl-pg-info b {
  color: #0f172a;
  font-weight: 700;
  font-size: 13px;
}
.bl-pg-btns {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.bl-pg-btn {
  border: 1px solid #e5e7eb;
  background: #fff;
  color: #475569;
  padding: 6px 12px;
  min-width: 36px;
  height: 34px;
  line-height: 20px;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: center;
}
.bl-pg-btn:hover:not(:disabled) {
  border-color: #14b8a6;
  color: #0f766e;
  background: #f0fdfa;
}
.bl-pg-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.bl-pg-btn.bl-pg-num.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  border-color: #14b8a6;
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.25);
}
.bl-pg-ellipsis {
  min-width: 28px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
  letter-spacing: 1px;
  line-height: 34px;
}

/* ============ 充值页面样式（PC 收银台：左右两栏） ============ */
.recharge-dashboard {
  max-width: 1040px;
  margin: 0 auto;
  padding: 8px 4px 20px;
}

/* ===== 二三级用户上级未配置支付：无法充值提示 ===== */
.recharge-unavailable {
  display: flex;
  justify-content: center;
  padding: 40px 8px 20px;
}
.recharge-unavailable-card {
  width: 100%;
  max-width: 460px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: var(--radius-lg, 16px);
  box-shadow: var(--shadow-sm);
  padding: 40px 32px 32px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.recharge-unavailable-card::before {
  content: '';
  position: absolute;
  right: -50px;
  top: -50px;
  width: 180px;
  height: 180px;
  background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, rgba(249, 115, 22, 0) 70%);
  pointer-events: none;
}
.recharge-unavailable-icon {
  width: 84px;
  height: 84px;
  margin: 0 auto 18px;
  border-radius: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.12) 0%, rgba(20, 184, 166, 0.05) 100%);
  border: 1px solid rgba(249, 115, 22, 0.22);
  color: var(--accent-500, #f97316);
}
.recharge-unavailable-title {
  font-size: 19px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  margin: 0 0 10px;
}
.recharge-unavailable-desc {
  font-size: 13.5px;
  color: var(--gray-500, #64748b);
  line-height: 1.7;
  margin: 0 0 20px;
}
.recharge-unavailable-agent {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  border-radius: 10px;
  padding: 11px 14px;
  font-size: 13px;
  color: var(--gray-600, #475569);
  line-height: 1.6;
  margin: 0 0 14px;
}
.recharge-unavailable-agent svg {
  color: var(--primary-500, #14b8a6);
  flex-shrink: 0;
}
.recharge-unavailable-agent b {
  color: var(--dark-800, #1e293b);
}
.recharge-unavailable-note {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 6px;
  text-align: left;
  background: rgba(20, 184, 166, 0.06);
  border: 1px solid rgba(20, 184, 166, 0.18);
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 12px;
  color: var(--primary-700, #0f766e);
  line-height: 1.6;
  margin: 0 0 22px;
}
.recharge-unavailable-note svg {
  color: var(--primary-600, #0d9488);
  flex-shrink: 0;
  margin-top: 2px;
}
.recharge-unavailable-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  background: var(--primary-600, #0d9488);
  color: var(--white);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.18s ease, transform 0.18s ease;
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.recharge-unavailable-btn:hover {
  background: var(--primary-700, #0f766e);
  transform: translateY(-1px);
}

/* 分站未开启在线支付：联系人工客服人工充值（紧凑、与客服弹窗风格统一） */
.recharge-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 60px 0;
  color: var(--gray-400, #94a3b8);
}
.recharge-loading p { margin: 0; font-size: 13px; }
.recharge-loading-spinner {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 3px solid rgb(20 184 166 / 0.18);
  border-top-color: var(--primary-500, #14b8a6);
  animation: recharge-loading-spin 0.8s linear infinite;
}
@keyframes recharge-loading-spin {
  to { transform: rotate(360deg); }
}

.recharge-off {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  padding: 46px 20px 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.recharge-off-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(20, 184, 166, 0.1);
  border: 1px solid rgba(20, 184, 166, 0.2);
  color: #0d9488;
  margin-bottom: 16px;
}
.recharge-off-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--dark-900, #0f172a);
  margin: 0 0 8px;
}
.recharge-off-desc {
  font-size: 13px;
  color: var(--gray-500, #64748b);
  line-height: 1.7;
  max-width: 330px;
  margin: 0 0 10px;
}
.recharge-off-note {
  font-size: 12px;
  color: var(--gray-400, #94a3b8);
  line-height: 1.6;
  max-width: 330px;
  margin: 0;
}

/* PC 标准左右分栏：左操作区 ~62%，右摘要卡 ~35% */
.recharge-grid {
  display: grid;
  grid-template-columns: 1.75fr 1fr;
  gap: 20px;
  align-items: start;
}

/* ===== 代理赠送 Banner（左栏顶部） ===== */
.agent-gift-banner {
  border-radius: 12px;
  background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 30%, #f0fdfa 100%);
  border: 1px solid rgba(251, 146, 60, 0.22);
  padding: 11px 13px;
  box-shadow: 0 2px 8px rgba(251, 146, 60, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
  position: relative;
  overflow: hidden;
}

.agent-gift-banner::before {
  content: '';
  position: absolute;
  top: -30px;
  right: -30px;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(251, 146, 60, 0.14) 0%, transparent 70%);
  pointer-events: none;
}

.agent-gift-banner::after {
  content: '';
  position: absolute;
  bottom: -40px;
  left: -20px;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.08) 0%, transparent 70%);
  pointer-events: none;
}

.gift-banner-main {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 10px;
}

.gift-banner-icon {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: #ea580c;
  background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
  box-shadow: 0 2px 8px rgba(234, 88, 12, 0.15), 0 0 0 2px rgba(255, 255, 255, 0.7) inset;
  animation: giftPulse 2.6s ease-in-out infinite;
}
.gift-banner-icon svg {
  width: 18px;
  height: 18px;
}
@keyframes giftPulse {
  0%, 100% { transform: rotate(-3deg) scale(1); }
  50%      { transform: rotate(3deg)  scale(1.05); }
}

.gift-banner-text {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: #92400e;
  line-height: 1.4;
}
.gift-banner-text b {
  color: #c2410c;
  font-weight: 800;
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.gift-banner-title-prefix {
  display: inline-block;
  padding: 1.5px 6px;
  border-radius: 5px;
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  color: #fff;
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0.02em;
  box-shadow: 0 2px 5px rgba(234, 88, 12, 0.25);
}
.gift-accent {
  color: #ea580c !important;
  font-weight: 700 !important;
  text-shadow: 0 1px 2px rgba(234, 88, 12, 0.1);
}

/* 左栏：操作面板（金额 + 支付方式） */
.recharge-left {
  position: relative;
  padding: 26px 24px 20px;
  display: flex;
  flex-direction: column;
  gap: 22px;
  border-radius: 16px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 6px 24px rgba(15, 23, 42, 0.05), 0 2px 6px rgba(15, 23, 42, 0.03);
  overflow: hidden;
}

/* 左栏顶部渐变色带（与右栏深色头呼应） */
.recharge-left::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #5eead4 0%, #14b8a6 40%, #0d9488 100%);
}

/* 充值保障条（贴左栏底部，填补无支付方式时的空旷） */
.recharge-assure {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 13px 10px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 100%);
  border: 1px solid rgba(20, 184, 166, 0.14);
}

.assure-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--gray-600);
  white-space: nowrap;
}

.assure-item svg {
  color: var(--primary-500);
  flex-shrink: 0;
}

.assure-divider {
  width: 1px;
  height: 14px;
  background: var(--gray-200);
  flex-shrink: 0;
}

.recharge-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.recharge-section-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 9px;
  border-bottom: 1px solid var(--gray-100);
}

.recharge-section-index {
  font-size: 11px;
  font-weight: 800;
  color: var(--white);
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  letter-spacing: 0.02em;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  padding: 2px 6.5px;
  border-radius: 5px;
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.25);
}

.recharge-section-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-900);
  letter-spacing: -0.01em;
}

/* 金额预设：3 列均匀分布，卡片三层（赠送角标+数字+到账小字） */
.amount-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 9px;
}

.amount-opt {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 19px 8px 14px;
  border-radius: 12px;
  border: 1.5px solid var(--gray-200);
  background: linear-gradient(180deg, #ffffff 0%, #fafaf9 100%);
  color: var(--dark-800);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

.amount-opt::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.06) 0%, transparent 55%);
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
}

.amount-opt:hover {
  border-color: #5eead4;
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06), 0 2px 6px rgba(20, 184, 166, 0.08);
}

.amount-opt:hover::before {
  opacity: 1;
}

.amount-opt.active {
  border-color: transparent;
  background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 45%, #0d9488 100%);
  color: #fff;
  box-shadow: 0 8px 20px rgba(13, 148, 136, 0.32), 0 2px 6px rgba(13, 148, 136, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.28);
  transform: translateY(-2px);
}

/* 顶部赠送角标（右上角贴边飘带） */
.amount-opt-gift {
  position: absolute;
  top: 0;
  right: 0;
  padding: 3px 7px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  border-bottom-left-radius: 9px;
  border-top-right-radius: 10px;
  box-shadow: 0 1px 4px rgba(234, 88, 12, 0.3);
  letter-spacing: 0.01em;
}

.amount-opt.active .amount-opt-gift {
  background: linear-gradient(135deg, #f97316 0%, #c2410c 100%);
  box-shadow: 0 2px 6px rgba(234, 88, 12, 0.38);
}

.amount-opt-num {
  font-size: 21px;
  font-weight: 800;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 2px;
}

.amount-opt.active .amount-opt-num {
  color: #fff;
  text-shadow: 0 1px 2px rgba(13, 148, 136, 0.25);
}

.amount-opt-yen {
  font-size: 11.5px;
  font-weight: 700;
  opacity: 0.55;
}

.amount-opt.active .amount-opt-yen {
  opacity: 0.8;
  color: #fff;
}

.amount-opt-arrive {
  margin-top: 1px;
  font-size: 10px;
  color: #059669;
  font-weight: 600;
  padding: 1.5px 6px;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.18);
  font-variant-numeric: tabular-nums;
}

.amount-opt.active .amount-opt-arrive {
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.32);
  animation: arrivePop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes arrivePop {
  0%   { transform: scale(0.9); }
  60%  { transform: scale(1.08); }
  100% { transform: scale(1); }
}

/* 金额按钮数字选中时的微弹跳 */
.amount-opt.active .amount-opt-num {
  animation: numBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes numBounce {
  0%   { transform: scale(1); }
  40%  { transform: scale(1.12); }
  70%  { transform: scale(0.97); }
  100% { transform: scale(1); }
}

/* 自定义金额 + 赠送文字行 */
.amount-custom-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.amount-custom {
  position: relative;
  display: flex;
  align-items: center;
}

.amount-custom::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.06), rgba(20, 184, 166, 0));
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
}
.amount-custom:focus-within::before {
  opacity: 1;
}

.amount-custom-prefix {
  position: absolute;
  left: 15px;
  font-size: 15px;
  font-weight: 800;
  color: var(--gray-400);
  pointer-events: none;
  z-index: 1;
  transition: color 0.2s ease;
}
.amount-custom:focus-within .amount-custom-prefix {
  color: var(--primary-600);
}

.amount-custom input {
  width: 100%;
  padding: 13px 14px 13px 36px;
  border-radius: 12px;
  border: 1.5px solid var(--gray-200);
  font-size: 15px;
  font-weight: 600;
  outline: none;
  background: var(--white);
  color: var(--dark-800);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
  font-variant-numeric: tabular-nums;
  position: relative;
  z-index: 0;
}

.amount-custom input:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}

.amount-custom input:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14), 0 4px 12px rgba(20, 184, 166, 0.08);
  background: var(--white);
  transform: translateY(-1px);
}

/* 支付方式：竖向列表（PC 端便于看全信息），卡片化 */
.payway-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
  gap: 12px;
}

/* 渠道卡片：品牌 logo + 名称（替代旧的全宽行布局） */
.payway-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 9px;
  padding: 15px 10px 12px;
  border-radius: 13px;
  border: 1.5px solid var(--gray-200);
  background: var(--white);
  cursor: pointer;
  text-align: center;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.payway-card input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.payway-card:hover {
  border-color: #5eead4;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}

.payway-card.active {
  border-color: var(--primary-500);
  background: linear-gradient(180deg, rgba(20, 184, 166, 0.07) 0%, rgba(20, 184, 166, 0.02) 100%);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.14);
}

.payway-logo {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 13px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.07);
  transition: transform 0.22s ease;
}

.payway-card:hover .payway-logo {
  transform: scale(1.06) rotate(-2deg);
}

.payway-logo img {
  width: 27px;
  height: 27px;
  display: block;
}

.payway-card-name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--dark-800);
}

.payway-card-desc {
  font-size: 10.5px;
  color: var(--gray-500);
}

.payway-card-check {
  position: absolute;
  top: 9px;
  right: 9px;
  width: 17px;
  height: 17px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: var(--white);
  opacity: 0;
  transform: scale(0.4);
  transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.35);
}

.payway-card-check svg {
  width: 10px;
  height: 10px;
}

.payway-card.active .payway-card-check {
  opacity: 1;
  transform: scale(1);
}

/* 创建订单中视图（收银台 loading） */
.qrcreating-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 36px 8px 28px;
  text-align: center;
}

.qrcreating-view .btn-spinner.big {
  width: 34px;
  height: 34px;
  border-width: 3px;
}

.qrcreating-title {
  margin: 6px 0 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--dark-800);
}

.qrcreating-desc {
  margin: 0;
  font-size: 12.5px;
  color: var(--gray-500);
}

/* 扫码支付视图（弹窗内直显二维码，不跳转外部页面） */
.qrpay-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4px 4px 2px;
}

.qrpay-head {
  display: flex;
  align-items: center;
  gap: 9px;
}

.qrpay-brand {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--white);
  border: 1px solid var(--gray-100);
  box-shadow: 0 3px 8px rgba(15, 23, 42, 0.06);
}

.qrpay-brand img {
  width: 21px;
  height: 21px;
  display: block;
}

.qrpay-title {
  margin: 0;
  font-size: 15.5px;
  font-weight: 800;
  color: var(--dark-800);
}

.qrpay-amount {
  margin-top: 10px;
  font-size: 30px;
  font-weight: 800;
  color: var(--dark-900);
  line-height: 1;
}

.qrpay-amount em {
  font-style: normal;
  font-size: 17px;
  margin-right: 2px;
}

.qrpay-qr-box {
  margin-top: 14px;
  padding: 12px;
  border: 1.5px solid var(--gray-200);
  border-radius: 14px;
  background: var(--white);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
}

.qrpay-qr-box img {
  display: block;
  width: 200px;
  height: 200px;
  border-radius: 8px;
}

.qrpay-scan-tip {
  margin: 12px 0 0;
  font-size: 12px;
  color: var(--gray-500);
  text-align: center;
  line-height: 1.6;
}

.qrpay-actions {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}

.qrpay-actions .btn {
  min-width: 132px;
}

.qrpay-result {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 26px 8px 10px;
  text-align: center;
}

.qrpay-result.paid svg {
  color: #07c160;
}

.qrpay-result.timeout svg {
  color: var(--gray-400);
}

.qrpay-result h4 {
  margin: 4px 0 0;
  font-size: 17px;
  font-weight: 800;
  color: var(--dark-800);
}

.qrpay-result p {
  margin: 0;
  font-size: 12.5px;
  color: var(--gray-500);
}

.qrpay-result .btn {
  margin-top: 10px;
  min-width: 120px;
}

@keyframes payIconPulse {
  0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.15); }
  50%      { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(20, 184, 166, 0); }
}

/* 右栏：订单摘要卡（典型 PC 收银台右侧卡片） */
.recharge-summary-card {
  position: sticky;
  top: 24px;
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

.summary-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px 16px;
  border-bottom: none;
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #2dd4bf 100%);
  position: relative;
  overflow: hidden;
}

/* 深色头部装饰光斑 */
.summary-card-head::before {
  content: '';
  position: absolute;
  top: -46px;
  right: -30px;
  width: 130px;
  height: 130px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, transparent 70%);
  pointer-events: none;
}

.summary-card-head h4 {
  margin: 0;
  font-size: 15.5px;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.01em;
  position: relative;
  z-index: 1;
}

.summary-card-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.28);
  position: relative;
  z-index: 1;
}

/* ===== 右栏：合并式流动卡片设计 ===== */

/* 顶部信息合并条 */
.summary-top-meta {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: stretch;
  margin: 16px 24px 0;
  padding: 12px 14px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.05) 0%, rgba(20, 184, 166, 0.02) 100%);
  border: 1px solid rgba(20, 184, 166, 0.12);
}
.summary-top-meta .meta-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  justify-content: center;
}
.summary-top-meta .meta-item.balance {
  align-items: flex-start;
}
.summary-top-meta .meta-item:last-child {
  align-items: flex-end;
  text-align: right;
}
.meta-label {
  font-size: 11px;
  color: var(--gray-500);
  font-weight: 500;
  letter-spacing: 0.01em;
}
.meta-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  font-variant-numeric: tabular-nums;
}
.meta-item.balance .meta-value {
  color: var(--primary-700);
}
.meta-divider {
  width: 1px;
  margin: 2px 14px;
  background: linear-gradient(180deg, transparent 0%, rgba(148, 163, 184, 0.35) 25%, rgba(148, 163, 184, 0.35) 75%, transparent 100%);
}

/* 核心金额计算卡：充值主行 → 赠送缩进子行 → 到账汇总行 */
.amount-flow-card {
  position: relative;
  margin: 14px 24px 0;
  padding: 14px 16px 16px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid var(--gray-100);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255,255,255,0.9);
  overflow: visible;
}
.amount-flow-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6 0%, #14b8a6 55%, #fb923c 100%);
  opacity: 0.7;
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}
.amount-flow-card.has-gift::before {
  opacity: 0.9;
}

/* 通用行：标签左 / 数值右 */
.flow-line {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 2px;
}
.flow-line-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--gray-600);
  white-space: nowrap;
}
.flow-line-value {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.01em;
  font-variant-numeric: tabular-nums;
  color: var(--dark-800);
  line-height: 1.1;
  white-space: nowrap;
}
.flow-line-value em {
  font-style: normal;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
  margin-right: 1px;
  vertical-align: 0.12em;
}

/* 充值主行 */
.recharge-line {
  padding-top: 4px;
  padding-bottom: 10px;
}
.line-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
  display: inline-block;
}
.line-dot.blue {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.recharge-line .flow-line-label {
  font-size: 12.5px;
  color: var(--dark-700);
  font-weight: 600;
}
.recharge-line .flow-line-value {
  font-size: 20px;
  font-weight: 800;
}

/* 赠送缩进子行（附属：缩进+左侧L型连接线） */
.gift-line {
  margin-left: 9px;
  padding-left: 18px;
  padding-top: 4px;
  padding-bottom: 4px;
  margin-bottom: 2px;
}
.gift-joint {
  position: absolute;
  left: 9px;
  top: -8px;
  width: 14px;
  height: 18px;
  border-left: 1.5px dashed rgba(249, 115, 22, 0.45);
  border-bottom: 1.5px dashed rgba(249, 115, 22, 0.45);
  border-bottom-left-radius: 6px;
  pointer-events: none;
}
.gift-mini-icon {
  display: inline-grid;
  place-items: center;
  width: 18px;
  height: 18px;
  border-radius: 5px;
  color: #fff;
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  flex-shrink: 0;
  padding: 2px;
  box-sizing: border-box;
}
.gift-line .flow-line-label {
  color: #c2410c;
  font-weight: 500;
  font-size: 11.5px;
}
.gift-line .flow-line-label small {
  font-size: 10px;
  font-weight: 600;
  color: #9a3412;
  background: rgba(251, 146, 60, 0.15);
  padding: 1px 5px;
  border-radius: 4px;
  margin-left: 2px;
}
.flow-line-value.gift-val {
  font-size: 14.5px;
  font-weight: 700;
  color: #ea580c;
}

/* 到账汇总行（独立的高亮合并块） */
.flow-arrive-block {
  position: relative;
  z-index: 1;
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 45%, #fff 100%);
  border: 1px solid rgba(20, 184, 166, 0.22);
  box-shadow: 0 1px 3px rgba(20, 184, 166, 0.06);
}
.flow-arrive-block::before {
  content: '';
  position: absolute;
  left: 0;
  top: 6px; bottom: 6px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #14b8a6 0%, #06b6d4 100%);
}
.flow-arrive-block.plain {
  background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 100%);
  border-color: rgba(148, 163, 184, 0.25);
}
.flow-arrive-block.plain::before {
  background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%);
}
.arrive-block-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: #0f766e;
  white-space: nowrap;
}
.arrive-block-label svg {
  flex-shrink: 0;
  padding: 1px;
  background: rgba(20, 184, 166, 0.2);
  border-radius: 4px;
  box-sizing: content-box;
}
.arrive-block-value {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
  line-height: 1.05;
  white-space: nowrap;
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #06b6d4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.flow-arrive-block.plain .arrive-block-value {
  background: none;
  -webkit-text-fill-color: currentColor;
  color: #0f766e;
}
.arrive-block-value em {
  font-style: normal;
  font-size: 13px;
  font-weight: 700;
  margin-right: 1px;
  vertical-align: 0.12em;
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.flow-arrive-block.plain .arrive-block-value em {
  background: none;
  -webkit-text-fill-color: currentColor;
  color: #0f766e;
}

/* 支付方式 + 充值后余额：双列合并卡 */
.summary-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin: 14px 24px 0;
}
.meta-cell {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 10px 12px;
  border-radius: 11px;
  background: linear-gradient(180deg, #ffffff 0%, #fafaf9 100%);
  border: 1px solid var(--gray-100);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.meta-cell.pay-cell {
  border-left: 3px solid rgba(20, 184, 166, 0.4);
}
.meta-cell.after-cell {
  border-left: 3px solid rgba(234, 88, 12, 0.4);
  background: linear-gradient(180deg, #ffffff 0%, #fffbf5 100%);
}
.meta-cell-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 500;
  color: var(--gray-500);
}
.meta-cell-label svg {
  color: var(--primary-500);
  flex-shrink: 0;
}
.meta-cell.after-cell .meta-cell-label svg {
  color: #ea580c;
}
.meta-cell-value {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--dark-800);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.meta-cell.after-cell .meta-cell-value {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 摘要卡内 CTA 按钮（更精致渐变 + 多层阴影） */
.recharge-summary-card .recharge-pay-btn {
  margin: 16px 24px 0;
  width: calc(100% - 48px);
  padding: 15px 22px;
  border-radius: 14px;
  font-size: 15.5px;
  letter-spacing: 0.01em;
  box-shadow: 0 10px 22px rgba(20, 184, 166, 0.34), 0 2px 4px rgba(20, 184, 166, 0.18);
}

.recharge-pay-btn {
  border: none;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: var(--white);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  position: relative;
  overflow: hidden;
}

.recharge-pay-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 55%);
  opacity: 1;
  pointer-events: none;
}

.recharge-pay-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 14px 28px rgba(20, 184, 166, 0.4), 0 3px 8px rgba(20, 184, 166, 0.22);
}

.recharge-pay-btn:hover:not(:disabled)::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 50%);
  animation: shimmer 1s ease-out;
}

@keyframes shimmer {
  from { transform: translateX(-100%); }
  to { transform: translateX(100%); }
}

.recharge-pay-btn:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 6px 14px rgba(20, 184, 166, 0.32);
}

.recharge-pay-btn:disabled {
  background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
  color: #9ca3af;
  cursor: not-allowed;
  box-shadow: none !important;
  transform: none !important;
}

.recharge-pay-btn:disabled::before {
  opacity: 0.3;
}

/* 摘要卡底部 */
.summary-foot {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 18px 24px 24px;
  margin-top: 14px;
  background: linear-gradient(180deg, transparent 0%, rgba(15, 23, 42, 0.015) 100%);
}

.summary-payee {
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.5;
  display: inline-flex;
  align-items: flex-start;
  gap: 5px;
}
.summary-foot .summary-payee:nth-child(2) {
  color: #9a3412;
  padding: 8px 10px;
  border-radius: 8px;
  background: linear-gradient(135deg, rgba(251, 146, 60, 0.06) 0%, rgba(251, 191, 36, 0.04) 100%);
  border: 1px solid rgba(251, 146, 60, 0.15);
}
.summary-foot .summary-payee:nth-child(2)::before {
  content: '';
  flex-shrink: 0;
  margin-top: 2px;
  width: 14px;
  height: 14px;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23ea580c' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/%3E%3C/svg%3E") no-repeat center;
}

.summary-log-link {
  font-size: 12px;
  color: var(--primary-600);
  text-decoration: none;
  font-weight: 600;
  transition: color 0.18s ease;
  display: inline-flex;
  align-items: center;
}

.summary-log-link:hover {
  color: #0f766e;
  text-decoration: underline;
}

/* 代理充值模式提示条样式 */
.recharge-notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(14, 165, 233, 0.04) 100%);
  border: 1px solid rgba(59, 130, 246, 0.18);
  font-size: 12.5px;
  color: #1e3a8a;
  line-height: 1.55;
}
.notice-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  font-family: 'Georgia', serif;
  box-shadow: 0 2px 5px rgba(59, 130, 246, 0.25);
}

/* 响应式：窄屏自动变单列 */
@media (max-width: 900px) {
  .recharge-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .recharge-summary-card {
    position: static;
  }
}

@media (max-width: 640px) {
  .recharge-dashboard {
    max-width: 100%;
  }
  .amount-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .recharge-left {
    padding: 22px 18px;
    gap: 24px;
  }

  .summary-card-head {
    padding: 18px 18px 14px;
  }
  .summary-list {
    padding: 12px 18px 4px;
  }
  .recharge-summary-card .recharge-pay-btn {
    margin: 16px 18px 0;
    width: calc(100% - 36px);
  }
  .summary-foot {
    padding: 14px 18px 20px;
  }
}



.status {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.status.success { background: rgba(16, 185, 129, 0.1); color: #059669; }
.status.warning { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.status.error { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

.profile-panel {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.profile-head {
  display: flex;
  align-items: center;
  gap: 12px;
}

.profile-avatar {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--dark-800);
  color: var(--white);
  font-size: 17px;
  font-weight: 600;
  flex-shrink: 0;
}

.profile-head-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.profile-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.profile-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}

.profile-id {
  font-size: 12px;
  color: var(--gray-500);
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-balance {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  padding: 14px 16px;
  background: #f8fafc;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-100);
}

.balance-label {
  font-size: 12px;
  color: var(--gray-500);
  font-weight: 500;
}

.balance-amount {
  font-size: 20px;
  font-weight: 700;
  color: var(--dark-800);
  letter-spacing: -0.01em;
  font-variant-numeric: tabular-nums;
}

.profile-info-list {
  margin: 0;
  padding: 0;
  border-top: 1px solid var(--gray-100);
}

.profile-info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--gray-100);
  font-size: 13px;
}

.profile-info-row dt {
  color: var(--gray-500);
  font-weight: 400;
  margin: 0;
}

.profile-info-row dd {
  color: var(--dark-800);
  font-weight: 500;
  margin: 0;
  text-align: right;
  word-break: break-all;
  font-variant-numeric: tabular-nums;
}

.profile-actions {
  display: flex;
  gap: 8px;
}

.profile-actions .btn {
  flex: 1;
  padding: 10px;
  font-size: 13px;
  font-weight: 600;
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 14px;
  background: transparent;
  color: var(--dark-800);
  border: 1.5px solid var(--gray-300);
  transition: all 0.25s ease;
}

.btn-outline:hover {
  border-color: var(--primary-500);
  color: var(--primary-600);
}

.service-bars {
  display: flex;
  flex-direction: column;
  gap: 14px;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-right: 4px;
}

.service-bar-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.service-bar-info {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  gap: 10px;
  min-width: 0;
}

.service-bar-name {
  font-weight: 600;
  color: var(--dark-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.service-bar-value {
  color: var(--gray-500);
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.service-bar-track {
  height: 10px;
  border-radius: 999px;
  background: var(--gray-100);
  overflow: hidden;
}

.service-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.8s ease;
}

.notice-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.notice-empty {
  font-size: 13px;
  color: var(--gray-500);
  text-align: center;
  padding: 18px 0;
}

.notice-empty p {
  margin: 0;
  line-height: 1.6;
}

.notice-empty-sub {
  font-size: 12px;
  color: var(--gray-400);
  margin-top: 4px !important;
}

.notice-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 8px 10px;
  margin: -8px -10px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.18s ease;
}

.notice-item:hover {
  background: var(--gray-50);
}

.notice-item:hover .notice-body p {
  color: var(--accent-500);
}

.notice-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-top: 6px;
  flex-shrink: 0;
}

.notice-dot.system {
  background: var(--primary-500, #14b8a6);
}

.notice-dot.superior {
  background: #f97316;
}

.notice-body p {
  font-size: 14px;
  line-height: 1.55;
  color: var(--dark-700);
  transition: color 0.18s ease;
}

.notice-body span {
  display: block;
  font-size: 12px;
  color: var(--gray-500);
  margin-top: 4px;
}

/* ===== 公告中心 tab ===== */
.notice-tabs {
  display: flex;
  gap: 6px;
  padding: 4px;
  background: #f1f5f9;
  border-radius: 10px;
  margin-bottom: 16px;
}

.notice-tab {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.2s ease;
}

.notice-tab:hover {
  color: var(--dark-700);
}

.notice-tab.active {
  background: var(--white);
  color: var(--dark-800);
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
}

.notice-tab-count {
  min-width: 18px;
  padding: 1px 6px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  background: rgba(15, 23, 42, 0.06);
  color: var(--gray-600);
}

.notice-tab.active .notice-tab-count {
  background: rgba(20, 184, 166, 0.12);
  color: var(--primary-600, #0d9488);
}


/* ===== 账号设置 V7：设置工作站（控制中心风格）===== */

/* 容器：限制最大宽度，居中 */
.settings-dashboard {
  padding-bottom: 32px;
  max-width: 1040px;
  margin: 0 auto;
}

/* ============ 顶部条 ============ */
.ws-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}
.ws-topbar-left {
  display: flex;
  align-items: baseline;
  gap: 12px;
  min-width: 0;
}
.ws-topbar-left h2 {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}
.ws-topbar-sep {
  color: #cbd5e1;
  font-size: 18px;
  font-weight: 600;
  margin: 0 -4px;
}
.ws-topbar-sub {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}
.ws-topbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
}
.ws-search {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 10px 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #94a3b8;
  width: 260px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.ws-search:focus-within {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}
.ws-search input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: #0f172a;
  min-width: 0;
}
.ws-search input::placeholder { color: #94a3b8; }
.ws-search-kbd {
  font-size: 10.5px;
  font-weight: 600;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 5px;
  border: 1px solid #e2e8f0;
  font-family: -apple-system, BlinkMacSystemFont, monospace;
}

/* ============ 身份卡 (Hero) ============ */
.ws-identity {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 24px;
  padding: 18px 24px;
  border-radius: 16px;
  background:
    linear-gradient(135deg, rgba(13, 148, 136, 0.04) 0%, rgba(20, 184, 166, 0.06) 50%, rgba(99, 102, 241, 0.05) 100%),
    #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  margin-bottom: 18px;
  position: relative;
  overflow: hidden;
}
.ws-identity::before {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.10) 0%, rgba(20, 184, 166, 0) 70%);
  pointer-events: none;
}
.ws-identity-avatar {
  position: relative;
  width: 64px;
  height: 64px;
  flex-shrink: 0;
}
.ws-identity-avatar-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 60%, #0f766e 100%);
  color: #fff;
  font-size: 26px;
  font-weight: 800;
  box-shadow: 0 6px 18px rgba(13, 148, 136, 0.28), inset 0 1px 2px rgba(255, 255, 255, 0.25);
}
.ws-identity-avatar-btn {
  position: absolute;
  right: -2px;
  bottom: -2px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #fff;
  background: #f8fafc;
  color: #475569;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
  padding: 0;
}
.ws-identity-avatar-btn:hover {
  background: #14b8a6;
  color: #fff;
  transform: scale(1.08);
}
.ws-identity-body { min-width: 0; }
.ws-identity-name-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}
.ws-identity-name {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.2px;
  line-height: 1.2;
}
.ws-identity-level {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 999px;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
  font-size: 11.5px;
  font-weight: 700;
  border: 1px solid #fde68a;
}
.ws-identity-tag {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
}
.ws-identity-tag.ok {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}
.ws-identity-tag.warn {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
.ws-identity-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12.5px;
  color: #64748b;
  flex-wrap: wrap;
}
.ws-identity-meta i {
  font-style: normal;
  font-size: 10.5px;
  font-weight: 700;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 1px 5px;
  border-radius: 4px;
  margin-right: 5px;
  letter-spacing: 0.3px;
}
.ws-identity-meta span:not(.ws-dot) {
  display: inline-flex;
  align-items: center;
}
.ws-dot {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
}
.ws-identity-stats {
  display: flex;
  align-items: stretch;
  gap: 0;
  border-left: 1px solid #e2e8f0;
  padding-left: 24px;
  position: relative;
  z-index: 1;
}
.ws-identity-stat {
  min-width: 84px;
  padding: 0 14px;
  border-right: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}
.ws-identity-stat:last-child { border-right: none; padding-right: 0; }
.ws-identity-stat:first-child { padding-left: 0; }
.ws-identity-stat-value {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
  letter-spacing: -0.3px;
}
.ws-identity-stat-value small {
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
  margin-left: 2px;
  letter-spacing: 0;
}
.ws-identity-stat-label {
  font-size: 11.5px;
  color: #94a3b8;
  font-weight: 500;
}
.ws-identity-stat-bar {
  height: 3px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
  margin-top: 2px;
}
.ws-identity-stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
  border-radius: 2px;
  transition: width 0.4s ease;
}
.ws-identity-stat-bar-fill.cyan { background: linear-gradient(90deg, #22d3ee 0%, #06b6d4 100%); }
.ws-identity-stat-bar-fill.orange { background: linear-gradient(90deg, #fb923c 0%, #f97316 100%); }

/* ============ 设置分组 ============ */
.ws-group {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
  margin-bottom: 14px;
  overflow: hidden;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.ws-group:hover {
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
  border-color: #cbd5e1;
}
.ws-group-head {
  padding: 14px 20px 10px 20px;
  border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
}
.ws-group-head h3 {
  font-size: 13px;
  font-weight: 750;
  color: #0f172a;
  margin: 0 0 2px 0;
  letter-spacing: 0.2px;
  text-transform: none;
}
.ws-group-head p {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.4;
}
.ws-group-body {
  padding: 4px 0;
}

/* ============ 设置行（核心）============ */
.ws-row {
  display: grid;
  grid-template-columns: 40px minmax(0, 1fr) minmax(0, 1.4fr) auto;
  align-items: center;
  gap: 16px;
  padding: 14px 20px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s ease;
  position: relative;
}
.ws-row:last-child { border-bottom: none; }
.ws-row:hover {
  background: #fafbfc;
}
.ws-row-clickable {
  cursor: pointer;
}
.ws-row-clickable:hover {
  background: #f0fdfa;
}

/* 行图标（彩色背景小方块） */
.ws-row-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  color: #fff;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08), inset 0 1px 2px rgba(255, 255, 255, 0.25);
}
.ws-row-icon-cyan { background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); }
.ws-row-icon-teal { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
.ws-row-icon-blue { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); }
.ws-row-icon-purple { background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); }
.ws-row-icon-orange { background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); }
.ws-row-icon-green { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); }
.ws-row-icon-slate { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); }

/* 行信息（label + sub） */
.ws-row-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.ws-row-label {
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.3;
}
.ws-row-sub {
  font-size: 11.5px;
  color: #94a3b8;
  line-height: 1.4;
}

/* 行值（中间列） */
.ws-row-value {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.ws-row-value-flex {
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
}
.ws-row-static {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.ws-row-value-text {
  font-size: 13px;
  color: #1e293b;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
.ws-row-value-text.mono {
  font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
  font-size: 12.5px;
  color: #475569;
  background: #f8fafc;
  padding: 2px 8px;
  border-radius: 5px;
  border: 1px solid #e2e8f0;
}
.ws-row-value-text.muted { color: #94a3b8; font-weight: 500; }
.ws-row-value-text.masked {
  font-family: ui-monospace, monospace;
  letter-spacing: 3px;
  font-size: 14px;
  color: #475569;
}
.ws-row-value-hint {
  font-size: 11.5px;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 500;
}

/* 行输入（内联编辑） */
.ws-row-input {
  height: 32px;
  padding: 0 10px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  background: #fff;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  min-width: 200px;
  max-width: 320px;
  font-family: inherit;
}
.ws-row-input::placeholder { color: #94a3b8; }
.ws-row-input:focus {
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14);
}

/* 行操作（右列） */
.ws-row-action {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.ws-row-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 30px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 12.5px;
  font-weight: 600;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  font-family: inherit;
}
.ws-row-btn:hover {
  border-color: #14b8a6;
  color: #0d9488;
  background: #f0fdfa;
}
.ws-row-btn.primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.25);
}
.ws-row-btn.primary:hover {
  filter: brightness(1.06);
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(20, 184, 166, 0.35);
}
.ws-row-btn-text {
  height: 30px;
  padding: 0 10px;
  background: transparent;
  color: #64748b;
  font-size: 12.5px;
  font-weight: 500;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
  transition: color 0.15s ease, background 0.15s ease;
}
.ws-row-btn-text:hover { color: #0f172a; background: #f1f5f9; }
.ws-row-btn-text:disabled { color: #cbd5e1; cursor: not-allowed; }
.ws-row-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 30px;
  padding: 0 14px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff;
  font-size: 12.5px;
  font-weight: 600;
  border: none;
  border-radius: 7px;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.28);
  transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
}
.ws-row-btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(20, 184, 166, 0.38);
  filter: brightness(1.04);
}
.ws-row-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

/* 行内状态标签 */
.ws-row-tag {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.ws-row-tag.ok {
  background: #d1fae5;
  color: #065f46;
}
.ws-row-tag.warn {
  background: #fef3c7;
  color: #92400e;
}
.ws-row-tag.static {
  background: #f1f5f9;
  color: #64748b;
  font-weight: 500;
  cursor: default;
}

/* 行内小链接按钮（用于「查看完整历史」等次要操作） */
.ws-link-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 28px;
  padding: 0 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 12px;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.18s ease;
  font-family: inherit;
  white-space: nowrap;
  flex-shrink: 0;
}
.ws-link-btn:hover {
  border-color: #0d9488;
  color: #0d9488;
  background: #f0fdfa;
}
.ws-link-btn:active {
  transform: scale(0.97);
}
.ws-link-btn svg {
  flex-shrink: 0;
}

/* 行内消息（成功/错误） */
.ws-row-msg {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 11.5px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 5px;
}
.ws-row-msg.ok { color: #065f46; background: #d1fae5; }
.ws-row-msg.err { color: #b91c1c; background: #fee2e2; }

/* 登录记录行（上次登录）*/
.ws-row-record .ws-row-value-record {
  align-items: center;
}
.ws-login-record {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 12.5px;
  color: #1e293b;
  font-weight: 500;
}
.ws-login-time {
  font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
  font-size: 12.5px;
  color: #0f172a;
  font-weight: 600;
  background: #f8fafc;
  padding: 3px 8px;
  border-radius: 5px;
  border: 1px solid #e2e8f0;
  letter-spacing: 0.2px;
}
.ws-login-ip {
  font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
  font-size: 12.5px;
  color: #475569;
  font-weight: 600;
  background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
  padding: 3px 8px;
  border-radius: 5px;
  border: 1px solid #99f6e4;
  letter-spacing: 0.2px;
}
.ws-login-device {
  font-size: 12.5px;
  color: #475569;
  font-weight: 500;
}
.ws-login-loc {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 11.5px;
  color: #0d9488;
  background: #f0fdfa;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid #99f6e4;
  font-weight: 600;
}
.ws-login-loc svg { color: #0d9488; }
.ws-login-dot {
  color: #cbd5e1;
  font-size: 11px;
  font-weight: 700;
}
.ws-login-empty {
  color: #94a3b8;
}

/* 密码编辑展开 */
.ws-row-expanded {
  background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%) !important;
  align-items: flex-start;
}
.ws-pwd-edit {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}
.ws-pwd-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.ws-pwd-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.ws-pwd-field label {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 0 2px;
}
.ws-pwd-field label span {
  font-size: 10.5px;
  color: #94a3b8;
  font-weight: 500;
}
.ws-pwd-field .ws-row-input {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  height: 34px;
  background: #fff;
}

.ws-pwd-strengthbar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 2px;
}
.ws-pwd-strengthbar-track {
  flex: 1;
  height: 4px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}
.ws-pwd-strengthbar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease, background 0.2s ease;
}
.ws-pwd-strengthbar-fill.stg-很强 { background: linear-gradient(90deg, #4ade80 0%, #16a34a 100%); }
.ws-pwd-strengthbar-fill.stg-强 { background: linear-gradient(90deg, #a3e635 0%, #65a30d 100%); }
.ws-pwd-strengthbar-fill.stg-中等 { background: linear-gradient(90deg, #fbbf24 0%, #d97706 100%); }
.ws-pwd-strengthbar-fill.stg-弱 { background: linear-gradient(90deg, #f87171 0%, #dc2626 100%); }
.ws-pwd-strengthbar-txt {
  font-size: 11.5px;
  font-weight: 700;
  min-width: 32px;
  text-align: right;
}
.ws-pwd-strengthbar-txt.stg-很强 { color: #15803d; }
.ws-pwd-strengthbar-txt.stg-强 { color: #4d7c0f; }
.ws-pwd-strengthbar-txt.stg-中等 { color: #b45309; }
.ws-pwd-strengthbar-txt.stg-弱 { color: #b91c1c; }
.ws-pwd-strengthbar-txt.stg-未设置 { color: #64748b; }

/* 密码展示态的强度小标签 */
.ws-pwd-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
}
.ws-pwd-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  display: inline-block;
}
.ws-pwd-chip.stg-很强 { background: #d1fae5; color: #065f46; }
.ws-pwd-chip.stg-很强 .ws-pwd-dot { background: #10b981; }
.ws-pwd-chip.stg-强 { background: #ecfccb; color: #4d7c0f; }
.ws-pwd-chip.stg-强 .ws-pwd-dot { background: #84cc16; }
.ws-pwd-chip.stg-中等 { background: #fef3c7; color: #92400e; }
.ws-pwd-chip.stg-中等 .ws-pwd-dot { background: #f59e0b; }
.ws-pwd-chip.stg-弱 { background: #fee2e2; color: #b91c1c; }
.ws-pwd-chip.stg-弱 .ws-pwd-dot { background: #ef4444; }
.ws-pwd-chip.stg-未设置 { background: #f1f5f9; color: #64748b; }
.ws-pwd-chip.stg-未设置 .ws-pwd-dot { background: #94a3b8; }

/* 加载小圈 */
.ws-spinner {
  display: inline-block;
  width: 11px;
  height: 11px;
  border: 1.5px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: ws-spin 0.7s linear infinite;
}
@keyframes ws-spin {
  to { transform: rotate(360deg); }
}

/* 页脚 */
.ws-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  font-size: 12px;
  color: #94a3b8;
}
.ws-footer a {
  color: #0d9488;
  text-decoration: none;
  font-weight: 600;
  cursor: pointer;
}
.ws-footer a:hover { text-decoration: underline; }
.ws-footer-dot { color: #cbd5e1; }

/* 响应式 */
@media (max-width: 880px) {
  .ws-identity {
    grid-template-columns: auto 1fr;
    gap: 18px;
  }
  .ws-identity-stats {
    grid-column: 1 / -1;
    border-left: none;
    border-top: 1px solid #e2e8f0;
    padding-left: 0;
    padding-top: 14px;
    margin-top: 4px;
  }
  .ws-identity-stat:first-child { padding-left: 0; }
  .ws-row {
    grid-template-columns: 36px minmax(0, 1fr) auto;
    gap: 12px;
  }
  .ws-row-value {
    grid-column: 2 / -1;
    margin-top: 4px;
  }
  .ws-row-action {
    grid-row: 1;
  }
  .ws-pwd-grid {
    grid-template-columns: 1fr;
  }
  .ws-search { width: 200px; }
  .ws-topbar-sub { display: none; }
}
@media (max-width: 600px) {
  .ws-identity {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .ws-identity-avatar { margin: 0 auto; }
  .ws-identity-name-row { justify-content: center; }
  .ws-identity-meta { justify-content: center; }
  .ws-identity-stats { justify-content: space-around; }
  .ws-row {
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 14px 16px;
  }
  .ws-row-icon { margin: 0; }
  .ws-row-value { grid-column: 1; }
  .ws-row-action { grid-row: auto; justify-content: flex-start; }
  .ws-topbar-left h2 { font-size: 19px; }
  .ws-search { display: none; }
}

.page-footer {
  margin-top: 24px;
  padding: 20px 36px;
  border-top: 1px solid var(--gray-200);
  text-align: center;
  color: var(--gray-400);
  font-size: 12px;
  line-height: 1.8;
}

.page-footer p {
  margin: 0;
}

.page-footer a {
  color: var(--gray-400);
  text-decoration: none;
  transition: color 0.2s ease;
}

.page-footer a:hover {
  color: var(--primary-600);
  text-decoration: underline;
}

.single-panel {
  min-height: 600px;
}

/* 未登录拦截卡片 */
.auth-gate {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.auth-card {
  max-width: 420px;
  width: 100%;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: var(--radius-xl);
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  padding: 36px 32px;
  text-align: center;
}

.auth-card h3 {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-800);
  margin-bottom: 8px;
}

.auth-card p {
  font-size: 14px;
  color: var(--gray-500);
  margin-bottom: 24px;
}

.auth-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.auth-actions .btn {
  padding: 10px 24px;
  font-size: 14px;
}

.profile-text-sm {
  font-size: 13px !important;
  font-weight: 600 !important;
  word-break: break-all;
}

.station-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.station-card {
  padding: 20px;
  border-radius: var(--radius-md);
  background: #f8fafc;
}

.station-card span {
  display: block;
  font-size: 13px;
  color: var(--gray-500);
  margin-bottom: 8px;
}

.station-card b {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-800);
  word-break: break-all;
}

.setting-panel {
  max-width: 680px;
}

.setting-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field label {
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-800);
}

.field input {
  padding: 12px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-200);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.field input:focus {
  border-color: var(--primary-500);
}

@media (max-width: 900px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .sub-stats-row {
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
  }

  .station-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .stats-row {
    grid-template-columns: 1fr;
  }
  .sub-stats-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .station-grid {
    grid-template-columns: 1fr;
  }
}

/* ===== 代理中心样式 ===== */
.invite-code-text {
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
  color: var(--primary-600);
}

/* ===== 邀请中心 Hero（白底 + teal 角光，与 userlist 设计语言一致）===== */
.agent-hero {
  position: relative;
  background: var(--white);
  border: 1px solid var(--gray-100);
  border-radius: var(--radius-lg);
  padding: 20px 24px 18px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.agent-hero::after {
  content: '';
  position: absolute;
  right: -60px;
  top: -60px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.10) 0%, rgba(20, 184, 166, 0) 70%);
  pointer-events: none;
}

.agent-hero-content { position: relative; z-index: 1; max-width: 760px; }

.agent-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(20, 184, 166, 0.08);
  color: var(--primary-600);
  padding: 4px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 10px;
}

.agent-hero-badge-dot {
  width: 6px;
  height: 6px;
  background: var(--primary-500);
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18);
}

.agent-hero-title {
  font-size: 21px;
  font-weight: 800;
  color: var(--dark-900);
  margin: 0 0 8px;
  letter-spacing: -0.02em;
  line-height: 1.25;
}

.agent-hero-title-accent {
  color: var(--primary-600);
}

.agent-hero-subtitle {
  font-size: 13px;
  line-height: 1.65;
  margin: 0;
  color: var(--gray-500);
}

/* ===== 邀请链接面板（紧凑）===== */
.agent-invite-panel { padding: 16px 20px; }

.agent-invite-block {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.agent-invite-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.agent-invite-field-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
  letter-spacing: 0.2px;
}

.agent-invite-field-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

.agent-invite-link-input {
  flex: 1;
  min-width: 0;
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--gray-200);
  background: var(--gray-50);
  font-size: 13px;
  color: var(--dark-700);
  outline: none;
  font-family: 'Courier New', monospace;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.agent-invite-link-input:focus {
  border-color: var(--primary-500);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}

.agent-invite-code-value {
  font-family: 'Courier New', monospace;
  letter-spacing: 1.5px;
  font-size: 15px;
  font-weight: 800;
  color: var(--primary-600);
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  background: rgba(20, 184, 166, 0.08);
  border: 1px solid rgba(20, 184, 166, 0.2);
  white-space: nowrap;
}

.agent-invite-code-input {
  flex: 1;
  min-width: 120px;
  max-width: 200px;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--primary-500);
  background: var(--white);
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-900);
  outline: none;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}

.agent-invite-code-input::placeholder {
  font-family: inherit;
  font-weight: 500;
  font-size: 12px;
  letter-spacing: 0;
  color: var(--gray-400);
}

.agent-copy-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.agent-invite-tip {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--gray-400);
  line-height: 1.5;
}

.agent-invite-empty {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  color: var(--gray-500);
  font-size: 13px;
}

.agent-invite-empty svg {
  flex-shrink: 0;
  color: var(--gray-400);
}

/* 复制按钮：紧凑尺寸，与 userlist .ul-btn 一致 */
.agent-copy-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: var(--radius-sm);
  border: 1px solid transparent;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  font-family: inherit;
  transition: all 0.18s ease;
}

.agent-copy-primary {
  background: var(--primary-600);
  color: var(--white);
}

.agent-copy-primary:hover { background: var(--primary-700, #0f766e); }

.agent-copy-outline {
  background: var(--white);
  color: var(--primary-600);
  border-color: rgba(20, 184, 166, 0.35);
}

.agent-copy-outline:hover {
  background: rgba(20, 184, 166, 0.07);
  border-color: var(--primary-500);
  color: var(--primary-700, #0f766e);
}

/* ===== 分销规则卡片（紧凑）===== */
.agent-rules-panel { padding: 16px 20px; }

.agent-rules-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.agent-rule-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 13px 15px;
  border-radius: var(--radius-md);
  background: var(--gray-50);
  border: 1px solid var(--gray-100);
  transition: border-color 0.2s ease, background 0.2s ease;
}

.agent-rule-card:hover {
  border-color: rgba(20, 184, 166, 0.3);
  background: rgba(20, 184, 166, 0.03);
}

.agent-rule-head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.agent-rule-num {
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  border-radius: 7px;
  color: var(--white);
  font-size: 11px;
  font-weight: 800;
  font-family: 'Courier New', monospace;
  flex-shrink: 0;
}

.agent-rule-card:nth-child(1) .agent-rule-num { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.agent-rule-card:nth-child(2) .agent-rule-num { background: linear-gradient(135deg, #fb923c, #f97316); }
.agent-rule-card:nth-child(3) .agent-rule-num { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

.agent-rule-head h4 {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  margin: 0;
}

.agent-rule-card p {
  font-size: 12px;
  line-height: 1.55;
  color: var(--gray-500);
  margin: 0;
}

.agent-rule-tag {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.agent-rule-tag.lv-1 { background: rgba(20, 184, 166, 0.12); color: var(--primary-600); }
.agent-rule-tag.lv-2 { background: rgba(249, 115, 22, 0.12); color: var(--accent-500); }
.agent-rule-tag.lv-3 { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }

/* ===== Agent Tab 紧凑化覆盖（缩小顶部统计卡 / 面板间距 / 标题）===== */
.agent-dashboard { gap: 14px; }
.agent-dashboard .stats-row { gap: 12px; }
.agent-dashboard .stat-card {
  padding: 12px 14px;
  border-radius: var(--radius-md);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.agent-dashboard .stat-card::before { height: 2px; }
.agent-dashboard .stat-main { gap: 3px; }
.agent-dashboard .stat-label { font-size: 11.5px; }
.agent-dashboard .stat-value { font-size: 18px; }
.agent-dashboard .stat-value.invite-code-text { font-size: 16px; letter-spacing: 0.5px; }
.agent-dashboard .stat-trend { padding: 2px 8px; font-size: 11px; }
.agent-dashboard .stat-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
}
.agent-dashboard .stat-icon svg { width: 16px; height: 16px; }

/* Hero 进一步紧凑 */
.agent-dashboard .agent-hero { padding: 14px 18px 13px; }
.agent-hero-title { font-size: 17px !important; margin: 0 0 5px !important; }
.agent-hero-subtitle { font-size: 12.5px !important; line-height: 1.55 !important; }
.agent-hero-badge { padding: 3px 9px; font-size: 11px; margin-bottom: 7px; }

/* 面板 header 紧凑 */
.agent-dashboard .panel { padding: 13px 16px; border-radius: var(--radius-md); }
.agent-dashboard .panel-header { margin-bottom: 10px; }
.agent-dashboard .panel-header h3 { font-size: 14.5px; }
.agent-dashboard .panel-desc { font-size: 12px; margin-top: 2px; }

/* 邀请面板内部紧凑 */
.agent-invite-block { gap: 10px !important; }
.agent-invite-field { gap: 4px !important; }
.agent-invite-field-label { font-size: 11.5px !important; }
.agent-invite-link-input { padding: 6px 10px !important; font-size: 12px !important; }
.agent-invite-code-value { font-size: 13px !important; padding: 4px 10px !important; }
.agent-invite-code-input { padding: 4px 10px !important; font-size: 13px !important; }
.agent-copy-btn { padding: 5px 11px !important; font-size: 12px !important; }
.agent-copy-btn svg { width: 11px !important; height: 11px !important; }
.agent-invite-tip { font-size: 11.5px !important; }
.agent-invite-empty { padding: 10px 12px !important; font-size: 12px !important; }
.agent-invite-empty svg { width: 16px !important; height: 16px !important; }

/* 规则卡片紧凑 */
.agent-rules-grid { gap: 10px !important; }
.agent-rule-card { padding: 10px 12px !important; gap: 5px !important; }
.agent-rule-head { gap: 8px !important; }
.agent-rule-num { width: 22px !important; height: 22px !important; font-size: 10px !important; }
.agent-rule-head h4 { font-size: 13px !important; }
.agent-rule-card p { font-size: 11.5px !important; line-height: 1.5 !important; }
.agent-rule-tag { padding: 2px 8px !important; font-size: 10.5px !important; }

@media (max-width: 760px) {
  .agent-rules-grid { grid-template-columns: 1fr; }
  .agent-invite-field-row { flex-wrap: wrap; }
}

.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: var(--gray-500);
  font-size: 14px;
}

/* ===== 代理公告管理 ===== */
.agent-ann-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.agent-ann-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  background: #f8fafc;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.agent-ann-item:hover {
  background: #f1f5f9;
  border-color: var(--gray-100);
}

.agent-ann-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: var(--white);
  flex-shrink: 0;
}

.agent-ann-icon.enabled {
  background: linear-gradient(135deg, #14b8a6, #0d9488);
}

.agent-ann-icon.disabled {
  background: linear-gradient(135deg, #cbd5e1, #94a3b8);
}

.agent-ann-info {
  flex: 1;
  min-width: 0;
}

.agent-ann-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 4px;
  min-width: 0;
}

.agent-ann-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex-shrink: 1;
  min-width: 0;
}

.agent-ann-status {
  flex-shrink: 0;
  display: inline-flex;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.agent-ann-status.success {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.agent-ann-status.muted {
  background: rgba(15, 23, 42, 0.06);
  color: var(--gray-500);
}

.agent-ann-meta {
  font-size: 12px;
  color: var(--gray-500);
}

.agent-ann-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* 自定义开关 */
.ann-switch {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}

.ann-switch input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.ann-switch-slider {
  width: 36px;
  height: 20px;
  background: #cbd5e1;
  border-radius: 999px;
  position: relative;
  transition: background 0.22s ease;
  flex-shrink: 0;
}

.ann-switch-slider::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  background: var(--white);
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.2);
  transition: transform 0.22s ease;
}

.ann-switch input:checked + .ann-switch-slider {
  background: linear-gradient(135deg, #14b8a6, #0d9488);
}

.ann-switch input:checked + .ann-switch-slider::after {
  transform: translateX(16px);
}

.ann-switch-lg .ann-switch-slider {
  width: 42px;
  height: 24px;
}

.ann-switch-lg .ann-switch-slider::after {
  width: 20px;
  height: 20px;
}

.ann-switch-lg input:checked + .ann-switch-slider::after {
  transform: translateX(18px);
}

.ann-switch-text {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-700);
}

/* ===== 公告表单弹窗 ===== */
.ann-form-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.ann-form-modal {
  position: relative;
  width: 100%;
  max-width: 600px;
  max-height: 88vh;
  background: var(--white);
  border-radius: 18px;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24), 0 6px 16px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: ann-form-pop 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes ann-form-pop {
  from { opacity: 0; transform: translateY(12px) scale(0.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.ann-form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid #f1f5f9;
}

.ann-form-header h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: var(--dark-800);
}

.ann-form-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(15, 23, 42, 0.04);
  border-radius: 50%;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.ann-form-close:hover {
  background: rgba(15, 23, 42, 0.08);
  color: var(--dark-800);
}

.ann-form-body {
  padding: 20px 22px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.ann-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

.ann-field label {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
}

.ann-required {
  color: #ef4444;
}

.ann-field input[type="text"],
.ann-field input[type="number"] {
  padding: 11px 14px;
  border-radius: 10px;
  border: 1px solid var(--gray-200);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  background: var(--white);
  color: var(--dark-800);
  width: 100%;
}

.ann-field input:focus {
  border-color: var(--primary-500, #14b8a6);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}

.ann-field textarea {
  padding: 12px 14px;
  border-radius: 10px;
  border: 1px solid var(--gray-200);
  font-size: 14px;
  outline: none;
  resize: vertical;
  min-height: 140px;
  line-height: 1.6;
  font-family: inherit;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  background: var(--white);
  color: var(--dark-800);
  width: 100%;
}

.ann-field textarea:focus {
  border-color: var(--primary-500, #14b8a6);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
}

.ann-field-tip {
  font-size: 12px;
  color: var(--gray-500);
  margin: 0;
  line-height: 1.5;
}

.ann-field-row {
  display: flex;
  gap: 16px;
}

@media (max-width: 600px) {
  .ann-field-row {
    flex-direction: column;
    gap: 18px;
  }
}

.ann-form-error {
  padding: 10px 14px;
  border-radius: 10px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-size: 13px;
}

.ann-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px 20px;
  border-top: 1px solid #f1f5f9;
}

.ann-form-footer .btn {
  padding: 10px 22px;
  font-size: 14px;
  font-weight: 600;
  border-radius: 10px;
}

.ann-form-footer .btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .ann-form-modal {
    max-width: 100%;
    max-height: 92vh;
    border-radius: 14px;
  }
  .agent-ann-actions {
    flex-wrap: wrap;
    justify-content: flex-end;
  }
}

.empty-state.small {
  padding: 20px;
}

.empty-state p {
  margin: 0;
  line-height: 1.6;
}

/* ===== 充值弹窗样式 ===== */
.recharge-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fade-in 0.2s ease;
}

/* ===== 登录历史弹窗 ===== */
.loginlog-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fade-in 0.2s ease;
}
.loginlog-modal {
  width: 100%;
  max-width: 520px;
  background: var(--white);
  border-radius: var(--radius-xl);
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 40px);
  animation: modal-in 0.25s ease;
}
.loginlog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px 14px;
  border-bottom: 1px solid var(--gray-100);
  flex-shrink: 0;
}
.loginlog-title {
  display: flex;
  align-items: center;
  gap: 8px;
}
.loginlog-title-icon {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
  color: #fff;
  flex-shrink: 0;
}
.loginlog-title h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--dark-800);
  margin: 0;
  letter-spacing: 0.2px;
}
.loginlog-count {
  font-size: 12px;
  color: var(--gray-500);
  font-weight: 500;
  padding: 2px 8px;
  background: var(--gray-100);
  border-radius: 10px;
  margin-left: 2px;
}
.loginlog-close {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: none;
  background: transparent;
  color: var(--gray-500);
  cursor: pointer;
  border-radius: 7px;
  transition: all 0.18s ease;
  font-family: inherit;
  padding: 0;
}
.loginlog-close:hover {
  background: var(--gray-100);
  color: var(--dark-800);
}
.loginlog-tips {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 10px 22px;
  font-size: 12.5px;
  color: var(--gray-600);
  background: #fffbeb;
  border-bottom: 1px solid #fef3c7;
  flex-shrink: 0;
}
.loginlog-tips svg {
  color: #f59e0b;
  flex-shrink: 0;
}
.loginlog-tip-link {
  border: none;
  background: none;
  color: #d97706;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.loginlog-tip-link:hover {
  color: #b45309;
}
.loginlog-body {
  flex: 1;
  overflow-y: auto;
  padding: 6px 0;
  min-height: 180px;
  position: relative;
}
.loginlog-body.is-loading::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.4);
  pointer-events: none;
}
.loginlog-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: var(--gray-500);
  font-size: 13px;
  gap: 10px;
}
.loginlog-state p {
  margin: 0;
}
.loginlog-spinner {
  width: 22px;
  height: 22px;
  border: 2px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: loginlog-spin 0.8s linear infinite;
}
@keyframes loginlog-spin {
  to { transform: rotate(360deg); }
}
.loginlog-empty-icon {
  color: #cbd5e1;
}
.loginlog-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.loginlog-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 22px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s ease;
  position: relative;
}
.loginlog-item:last-child {
  border-bottom: none;
}
.loginlog-item:hover {
  background: #f8fafc;
}
.loginlog-item.is-current {
  background: linear-gradient(90deg, rgba(13, 148, 136, 0.04) 0%, rgba(13, 148, 136, 0) 80%);
}
.loginlog-item-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
  margin-top: 6px;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(203, 213, 225, 0.25);
}
.loginlog-item-dot.ipv4 {
  background: #0d9488;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18);
}
.loginlog-item-dot.ipv6 {
  background: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
}
.loginlog-item-main {
  flex: 1;
  min-width: 0;
}
.loginlog-item-row1 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}
.loginlog-item-time {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
  font-variant-numeric: tabular-nums;
}
.loginlog-item-tag {
  font-size: 10.5px;
  font-weight: 600;
  padding: 1.5px 7px;
  border-radius: 4px;
  letter-spacing: 0.2px;
}
.loginlog-item-tag.current {
  background: #0d9488;
  color: #fff;
}
.loginlog-item-tag.new {
  background: #dbeafe;
  color: #1d4ed8;
}
.loginlog-item-row2 {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--gray-600);
  flex-wrap: wrap;
}
.loginlog-item-ip {
  font-family: 'SF Mono', 'Consolas', 'Monaco', monospace;
  font-size: 11.5px;
  background: #f1f5f9;
  padding: 1px 6px;
  border-radius: 4px;
  color: var(--dark-700);
}
.loginlog-item-dot-sep {
  color: #cbd5e1;
  font-weight: 700;
}
.loginlog-item-device {
  color: var(--gray-700);
}
.loginlog-item-way {
  color: var(--gray-500);
}
.loginlog-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 22px;
  border-top: 1px solid var(--gray-100);
  background: #fafbfc;
  flex-shrink: 0;
}
.loginlog-pager {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 30px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: var(--gray-700);
  font-size: 12px;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s ease;
}
.loginlog-pager:hover:not(:disabled) {
  border-color: #0d9488;
  color: #0d9488;
}
.loginlog-pager:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.loginlog-pager-text {
  font-size: 12px;
  color: var(--gray-500);
  font-variant-numeric: tabular-nums;
}
@media (max-width: 540px) {
  .loginlog-modal { max-width: 100%; }
  .loginlog-header { padding: 14px 16px 12px; }
  .loginlog-tips { padding: 8px 16px; }
  .loginlog-item { padding: 10px 16px; }
  .loginlog-footer { padding: 10px 16px; }
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

.recharge-modal {
  width: 100%;
  max-width: 460px;
  background: var(--white);
  border-radius: var(--radius-xl);
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2);
  overflow: hidden;
  animation: modal-in 0.25s ease;
}

@keyframes modal-in {
  from { transform: translateY(20px) scale(0.96); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

.recharge-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--gray-100);
}

.recharge-header h3 {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark-800);
  margin: 0;
}

.recharge-close {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border: none;
  background: transparent;
  font-size: 24px;
  color: var(--gray-500);
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
  line-height: 1;
}

.recharge-close:hover {
  background: var(--gray-100);
  color: var(--dark-800);
}

.recharge-body {
  padding: 20px 24px 24px;
  max-height: calc(100vh - 140px);
  overflow-y: auto;
}

.recharge-info-bar {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 11px 14px;
  border-radius: var(--radius-md);
  background: #f8fafc;
  margin-bottom: 16px;
}

.recharge-info-item {
  text-align: center;
}

.recharge-info-item .label {
  display: block;
  font-size: 11px;
  color: var(--gray-500);
  margin-bottom: 4px;
}

.recharge-info-item .value {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  word-break: break-all;
}

.recharge-section {
  margin-bottom: 18px;
}

.recharge-section-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
  margin-bottom: 10px;
}

/* 充值弹窗：当前余额简单行 */
.recharge-balance-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
  margin-bottom: 16px;
}
.recharge-balance-line .rbl-label { font-size: 12px; color: var(--gray-500); }
.recharge-balance-line .rbl-value { font-size: 15px; font-weight: 700; color: var(--dark-800); font-variant-numeric: tabular-nums; }

/* 充值弹窗：到账/赠送简单提示行 */
.recharge-gift-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #c2410c;
}
.recharge-gift-hint em { font-style: normal; font-weight: 700; }
.recharge-gift-hint b { color: #ea580c; }
.recharge-gift-hint .gift-accent { color: #ea580c; }

/* 充值弹窗：收款方简单行 */
.recharge-payee-line {
  margin-top: 12px;
  text-align: center;
  font-size: 11.5px;
  color: var(--gray-500);
}
.recharge-payee-line b { color: var(--dark-700); font-weight: 600; }

/* 充值弹窗：确认信息卡（金额已在充值页选好，弹窗仅展示确认信息） */
.recharge-confirm-card {
  display: flex;
  flex-direction: column;
  gap: 0;
  border: 1px solid #eef2f7;
  border-radius: var(--radius-md);
  overflow: hidden;
  margin-bottom: 14px;
}
.rc-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 13px 16px;
}
.rc-row + .rc-row {
  border-top: 1px solid #f1f5f9;
}
.rc-label {
  font-size: 13px;
  color: var(--gray-500);
  flex-shrink: 0;
}
.rc-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--dark-800);
  text-align: right;
  font-variant-numeric: tabular-nums;
}
.rc-amount {
  font-size: 20px;
  color: var(--primary-600, #0d9488);
}
.rc-arrive {
  color: #ea580c;
}
.rc-arrive-small {
  display: block;
  font-size: 11.5px;
  font-weight: 600;
  color: #c2410c;
  margin-top: 2px;
}

/* 充值弹窗：支付方式描述 */
.payway-desc {
  margin-left: auto;
  font-size: 11px;
  color: var(--gray-500);
}

.recharge-amount-input input {
  width: 100%;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-200);
  font-size: 16px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.2s ease;
}

.recharge-amount-input input:focus {
  border-color: var(--primary-500);
}

.recharge-amount-presets {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-top: 10px;
}

.preset-btn {
  padding: 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-200);
  background: var(--white);
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-700);
  cursor: pointer;
  transition: all 0.2s ease;
}

.preset-btn:hover {
  border-color: var(--primary-400, #2dd4bf);
  color: var(--primary-600);
}

.preset-btn.active {
  border-color: var(--primary-500);
  background: rgba(20, 184, 166, 0.08);
  color: var(--primary-600);
}

.recharge-payways {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.payway-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  border: 1.5px solid var(--gray-200);
  background: var(--white);
  cursor: pointer;
  transition: all 0.2s ease;
}

.payway-item:hover {
  border-color: var(--primary-400, #2dd4bf);
}

.payway-item.active {
  border-color: var(--primary-500);
  background: rgba(20, 184, 166, 0.04);
}

.payway-item input[type="radio"] {
  accent-color: var(--primary-500);
}

.payway-icon {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.payway-icon.wechat {
  background: rgba(7, 193, 96, 0.1);
  color: #07c160;
}

.payway-icon.alipay {
  background: rgba(0, 144, 233, 0.1);
  color: #0090e9;
}

.payway-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-800);
}

/* 外部页面支付方式选择（精致紧凑） */
.recharge-payways-select {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.payway-select-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1.5px solid var(--gray-200);
  background: var(--white);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.payway-select-item:hover {
  border-color: var(--primary-400, #2dd4bf);
}

.payway-select-item.active {
  border-color: var(--primary-500);
  background: rgba(20, 184, 166, 0.06);
  box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.1);
}

.payway-select-item input[type="radio"] {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.payway-select-icon {
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  border-radius: 7px;
  flex-shrink: 0;
}

.payway-select-icon.wechat {
  background: rgba(7, 193, 96, 0.1);
  color: #07c160;
}

.payway-select-icon.alipay {
  background: rgba(0, 144, 233, 0.1);
  color: #0090e9;
}

.payway-select-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--dark-800);
}

.recharge-confirm-tip {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: rgba(239, 68, 68, 0.06);
  border: 1px solid rgba(239, 68, 68, 0.18);
  font-size: 12.5px;
  color: #dc2626;
  margin-bottom: 14px;
}

.recharge-confirm-tip p {
  margin: 0;
  line-height: 1.5;
}

.recharge-notice {
  display: flex;
  gap: 10px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(249, 115, 22, 0.08);
  border: 1px solid rgba(249, 115, 22, 0.2);
  font-size: 12px;
  color: #c2410c;
  line-height: 1.6;
}

.notice-icon {
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--accent-500);
  color: var(--white);
  font-weight: 700;
  flex-shrink: 0;
  font-size: 11px;
}

.form-error {
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 12px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  border-top-color: #fff;
  border-radius: 50%;
  animation: btn-spin 0.6s linear infinite;
  display: inline-block;
  margin-right: 6px;
  vertical-align: middle;
}

@keyframes btn-spin {
  to { transform: rotate(360deg); }
}

.recharge-summary {
  padding: 14px;
  border-radius: 12px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  margin-bottom: 14px;
  border: 1px solid var(--gray-100);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  font-size: 13px;
  gap: 10px;
}

.summary-row span {
  color: var(--gray-500);
}

.summary-row b {
  color: var(--dark-800);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.summary-row:first-child b {
  font-size: 15px;
  color: var(--primary-600);
}

/* 弹窗摘要：赠送行 */
.summary-row.gift-row {
  padding: 8px 10px;
  margin: 4px -2px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(251, 146, 60, 0.08) 0%, rgba(250, 204, 21, 0.06) 100%);
  border: 1px solid rgba(251, 146, 60, 0.18);
}
.gift-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #9a3412 !important;
  font-weight: 600;
}
.gift-label small {
  font-size: 11px;
  font-weight: 700;
  color: #c2410c;
  padding: 1px 5px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(234, 88, 12, 0.18);
}
.gift-label-icon {
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border-radius: 6px;
  color: #ea580c;
  background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
  box-shadow: 0 1px 3px rgba(234, 88, 12, 0.18);
  flex-shrink: 0;
}
.summary-row.gift-row b.gift {
  color: #ea580c;
  font-weight: 800;
  font-size: 14px;
}

/* 弹窗摘要：实际到账高亮行 */
.summary-row.highlight {
  padding: 8px 10px;
  margin: 4px -2px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(5, 150, 105, 0.07) 100%);
  border: 1px solid rgba(20, 184, 166, 0.25);
}
.highlight-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0f766e !important;
  font-weight: 600;
}
.highlight-icon {
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border-radius: 6px;
  color: #fff;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 1px 4px rgba(20, 184, 166, 0.28);
  flex-shrink: 0;
}
.summary-row.highlight b.arrive {
  color: #047857;
  font-weight: 800;
  font-size: 15px;
}

/* 弹窗摘要：分割行/结果行 */
.summary-row.divider {
  border-top: 1px dashed rgba(148, 163, 184, 0.35);
  padding-top: 10px;
  margin-top: 4px;
}
.summary-row.result {
  padding-top: 2px;
}
.summary-row.result b.accent {
  font-size: 15px;
  color: var(--primary-700);
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 弹窗摘要：应付总计 */
.recharge-summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 11px 12px;
  margin-top: 8px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.09) 0%, rgba(14, 165, 233, 0.05) 100%);
  border: 1px solid rgba(20, 184, 166, 0.2);
  font-size: 14px;
  color: var(--gray-600);
  font-weight: 600;
}
.recharge-summary-total b {
  font-size: 18px;
  font-weight: 800;
  color: #0f766e;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 1px 2px rgba(20, 184, 166, 0.08);
}

.recharge-submit {
  width: 100%;
  padding: 13px;
  font-size: 15px;
  font-weight: 700;
  border-radius: var(--radius-md);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.recharge-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* 等待付款视图 */
.pending-view {
  text-align: center;
  padding: 20px 10px;
}

.pending-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f1f5f9;
  color: var(--gray-500);
}

.pending-icon.pending {
  background: rgba(249, 115, 22, 0.1);
  color: var(--accent-500);
  animation: pending-pulse 2s ease-in-out infinite;
}

.pending-icon.paid {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.pending-icon.timeout {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

@keyframes pending-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.pending-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--dark-800);
  margin: 0 0 8px;
}

.pending-desc {
  font-size: 13px;
  color: var(--gray-500);
  line-height: 1.6;
  margin: 0 auto 20px;
  max-width: 360px;
}

.pending-order-info {
  padding: 14px 16px;
  border-radius: var(--radius-md);
  background: #f8fafc;
  margin-bottom: 18px;
  text-align: left;
}

.pending-order-info .summary-row b {
  font-weight: 600;
  font-size: 13px;
}

.pending-order-info .order-sn {
  font-family: monospace;
  font-size: 12px;
  word-break: break-all;
}

.pending-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.pending-actions .btn {
  padding: 10px 22px;
  font-size: 13px;
  border-radius: var(--radius-md);
}

/* ===== 弹窗：充值摘要合并卡片适配（更紧凑，尺寸微调版） ===== */
.recharge-summary.flow-summary {
  padding: 10px 4px 12px;
  background: transparent;
  border: none;
  box-shadow: none;
  margin-bottom: 10px;
}
.flow-summary .summary-top-meta {
  margin: 0 10px;
  padding: 10px 12px;
  border-radius: 10px;
}
.flow-summary .amount-flow-card {
  margin: 12px 10px 0;
  padding: 12px 14px 14px;
  border-radius: 12px;
}
/* 行尺寸压缩 */
.flow-summary .flow-line {
  padding: 6px 2px;
}
.flow-summary .flow-line-label {
  font-size: 11.5px;
}
.flow-summary .flow-line-value {
  font-size: 17px;
}
.flow-summary .flow-line-value em {
  font-size: 11px;
}
.flow-summary .recharge-line {
  padding-top: 2px;
  padding-bottom: 8px;
}
.flow-summary .recharge-line .flow-line-label {
  font-size: 12px;
}
.flow-summary .recharge-line .flow-line-value {
  font-size: 18.5px;
}
/* 赠送缩进行压缩 */
.flow-summary .gift-line {
  margin-left: 8px;
  padding-left: 16px;
  padding-top: 3px;
  padding-bottom: 3px;
}
.flow-summary .gift-joint {
  left: 8px;
  top: -6px;
  width: 12px;
  height: 16px;
}
.flow-summary .gift-mini-icon {
  width: 16px;
  height: 16px;
}
.flow-summary .gift-line .flow-line-label {
  font-size: 10.5px;
}
.flow-summary .gift-line .flow-line-label small {
  font-size: 9.5px;
}
.flow-summary .flow-line-value.gift-val {
  font-size: 13.5px;
}
/* 到账汇总块压缩 */
.flow-summary .flow-arrive-block {
  margin-top: 8px;
  padding: 9px 11px;
  border-radius: 9px;
}
.flow-summary .arrive-block-label {
  font-size: 11.5px;
}
.flow-summary .arrive-block-value {
  font-size: 20px;
}
.flow-summary .arrive-block-value em {
  font-size: 12px;
}
.flow-summary .summary-meta-grid {
  margin: 12px 10px 0;
  gap: 8px;
}
.flow-summary .meta-cell {
  padding: 8px 10px;
  border-radius: 10px;
}
.flow-summary .meta-cell-value {
  font-size: 12.5px;
}

/* ===== 弹窗：等待付款信息合并卡片 ===== */
.pending-order-info.flow-order-info {
  padding: 12px 14px 14px;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid var(--gray-100);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255,255,255,0.9);
}

/* 金额主视觉块 */
.pending-amount-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 14px 10px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #ffffff 100%);
  border: 1px solid rgba(20, 184, 166, 0.18);
  margin-bottom: 10px;
  position: relative;
  overflow: hidden;
}
.pending-amount-hero::before {
  content: '';
  position: absolute;
  top: -50%; right: -20%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(20, 184, 166, 0.08) 0%, transparent 70%);
  pointer-events: none;
}
.pending-amount-label {
  position: relative;
  z-index: 1;
  font-size: 11.5px;
  color: var(--gray-500);
  font-weight: 500;
}
.pending-amount-value {
  position: relative;
  z-index: 1;
  font-size: 30px;
  font-weight: 800;
  letter-spacing: -0.025em;
  font-variant-numeric: tabular-nums;
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #06b6d4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.1;
}
.pending-amount-value em {
  font-style: normal;
  font-size: 16px;
  font-weight: 700;
  margin-right: 2px;
  vertical-align: 0.08em;
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 信息双列合并 */
.pending-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 10px;
}
.pending-info-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 9px 11px;
  border-radius: 10px;
  background: #fff;
  border: 1px solid var(--gray-100);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.pending-info-cell.sn-cell {
  border-left: 3px solid rgba(59, 130, 246, 0.4);
}
.pending-info-cell.pay-cell {
  border-left: 3px solid rgba(20, 184, 166, 0.4);
}
.pending-info-label {
  font-size: 10.5px;
  color: var(--gray-500);
  font-weight: 500;
}
.pending-info-value {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--dark-800);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pending-info-value.order-sn {
  font-family: monospace;
  font-size: 11.5px;
  font-weight: 600;
}

/* 收款代理卡片 */
.pending-agent-card {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
  border: 1px solid rgba(251, 146, 60, 0.2);
  color: #c2410c;
}
.pending-agent-card svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  padding: 3px;
  border-radius: 6px;
  background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
  color: #fff;
  box-sizing: content-box;
}
.pending-agent-label {
  font-size: 11.5px;
  color: #9a3412;
  font-weight: 500;
}
.pending-agent-name {
  flex: 1;
  text-align: right;
  font-size: 13px;
  font-weight: 700;
  color: #7c2d12;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 480px) {
  .recharge-info-bar {
    grid-template-columns: 1fr;
    gap: 6px;
  }
  .recharge-amount-presets {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* ===== 我的模板列表（横向列表） ===== */
.template-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.template-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.template-cover {
  flex: 0 0 56px;
  width: 56px;
  height: 56px;
  border-radius: 8px;
  overflow: hidden;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}
.template-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.template-cover-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}
.template-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.template-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-800);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.template-meta {
  font-size: 12px;
  color: var(--gray-500);
  line-height: 1.5;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}
.tpl-type-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
}
.tpl-type-badge.st {
  color: #0d9488;
  background: rgba(13, 148, 136, 0.1);
  border: 1px solid rgba(13, 148, 136, 0.2);
}
.tpl-type-badge.pm {
  color: #6b7280;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}
.template-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}
.tpl-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}
.tpl-action-btn.edit {
  background: #fff;
  color: #0d9488;
  border-color: rgba(20, 184, 166, 0.35);
}
.tpl-action-btn.edit:hover {
  background: #f0fdfa;
  border-color: #14b8a6;
}
.tpl-action-btn.edit.readonly { color: #6b7280; border-color: #e2e8f0; }
.tpl-action-btn.edit.readonly:hover { background: #fff; border-color: #e2e8f0; }
.tpl-action-btn.delete {
  background: #fff;
  color: #ef4444;
  border-color: #fecaca;
}
.tpl-action-btn.delete:hover:not(:disabled) {
  background: #fef2f2;
  border-color: #fca5a5;
}
.tpl-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ===== 骨架屏 ===== */
.skeleton-card {
  pointer-events: none;
  border-color: #e2e8f0 !important;
}
.skeleton-card:hover {
  border-color: #e2e8f0 !important;
  box-shadow: none !important;
  transform: none !important;
}
.skeleton-block,
.skeleton-line,
.skeleton-btn {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.4s ease-in-out infinite;
}
.skeleton-block { width: 56px; height: 56px; border-radius: 8px; flex: 0 0 56px; }
.skeleton-line {
  height: 12px;
  border-radius: 4px;
}
.skeleton-line.w-60 { width: 60%; }
.skeleton-line.w-40 { width: 40%; }
.skeleton-btn {
  width: 64px;
  height: 30px;
  border-radius: 6px;
}
@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 新增：图表图例、订单骨架占位、更多宽度预设 */
.chart-legend {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: #64748b;
}
.chart-legend .legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.chart-legend .legend-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  display: inline-block;
}
.skeleton-line.w-20 { width: 20%; }
.skeleton-item {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
.skeleton-item:hover {
  background: transparent !important;
}

@media (max-width: 600px) {
  .template-card {
    flex-wrap: wrap;
  }
  .template-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
