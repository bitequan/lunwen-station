<template>
  <div class="tool-shell-price" :class="theme" v-if="!loading && (text || finalPrice >= 0)">
    <div class="tsp-main">
      <span class="tsp-label">{{ label }}</span>
      <span class="tsp-price">
        <span v-if="finalPrice > 0">
          <span class="tsp-symbol">¥</span>
          <span class="tsp-num">{{ finalPriceFormatted }}</span>
        </span>
        <span v-else class="tsp-free">免费试用</span>
      </span>
      <span v-if="finalPrice > 0" class="tsp-unit">{{ unit }}</span>
    </div>
    <div v-if="showOriginal && sellPrice > finalPrice && finalPrice > 0" class="tsp-original">
      <span>原价 ¥{{ sellPriceFormatted }}</span>
    </div>
  </div>
  <div class="tool-shell-price tsp-skeleton" v-else-if="loading">
    <span class="tsp-label">{{ label }}</span>
    <span class="skeleton-bar" />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  finalPrice: { type: Number, default: 0 },
  sellPrice: { type: Number, default: 0 },
  text: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  showOriginal: { type: Boolean, default: true },
  theme: { type: String, default: 'default' }, // default / teal / blue / orange / cyan / violet
  label: { type: String, default: '一口价' }, // 一口价 / 起步价
  unit: { type: String, default: '/ 单' }, // 后缀单位，如 '/ 单' 或 '起 / 单'
})

// 价格统一保留两位小数（¥0.10 / ¥1.00 / ¥6.00），避免“¥0..1”“¥0.1”等不规范显示
const fmtPrice = (v) => {
  const n = Number(v)
  if (!Number.isFinite(n) || n < 0) return '0.00'
  return n.toFixed(2)
}

const finalPriceFormatted = computed(() => {
  if (!props.finalPrice && props.finalPrice !== 0) return '0.00'
  return fmtPrice(props.finalPrice)
})

const sellPriceFormatted = computed(() => fmtPrice(props.sellPrice))
</script>

<style scoped>
.tool-shell-price {
  display: inline-flex;
  align-items: center;
  gap: 14px;
  padding: 10px 16px;
  background: #fafbff;
  border: 1px solid #e6e8f0;
  border-radius: 10px;
  line-height: 1;
}
.tool-shell-price.tsp-skeleton { opacity: 0.55; }
.tool-shell-price.teal     { background: #f0fdfa; border-color: #99f6e4; }
.tool-shell-price.blue     { background: #eff6ff; border-color: #bfdbfe; }
.tool-shell-price.orange   { background: #fff7ed; border-color: #fed7aa; }
.tool-shell-price.cyan     { background: #ecfeff; border-color: #a5f3fc; }
.tool-shell-price.violet   { background: #f5f3ff; border-color: #ddd6fe; }

.tsp-main {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
}
.tsp-label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  letter-spacing: 0.02em;
}
.tsp-price { display: inline-flex; align-items: baseline; gap: 2px; }
.tsp-symbol {
  font-size: 14px;
  font-weight: 700;
  color: #ef4444;
}
.tsp-num {
  font-size: 22px;
  font-weight: 800;
  color: #ef4444;
  font-family: 'JetBrains Mono', 'SF Mono', Consolas, 'Roboto Mono', monospace;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.01em;
}
.tsp-unit {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  padding-left: 2px;
}
.tsp-free {
  font-size: 18px;
  font-weight: 700;
  color: #059669;
}

.tsp-original {
  padding-left: 14px;
  border-left: 1px solid #e5e7eb;
}
.tsp-original span {
  font-size: 12px;
  color: #9ca3af;
  text-decoration: line-through;
  text-decoration-color: #cbd5e1;
  font-family: 'JetBrains Mono', 'SF Mono', Consolas, monospace;
}

.skeleton-bar {
  display: inline-block;
  width: 100px;
  height: 18px;
  border-radius: 4px;
  background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
  background-size: 200% 100%;
  animation: tsp-shimmer 1.4s ease-in-out infinite;
}
@keyframes tsp-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 768px) {
  .tool-shell-price { padding: 8px 12px; gap: 10px; }
  .tsp-num { font-size: 18px; }
  .tsp-symbol { font-size: 13px; }
  .tsp-original { padding-left: 10px; }
}
@media (max-width: 480px) {
  .tool-shell-price { width: 100%; justify-content: space-between; }
}
</style>
