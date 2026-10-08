<template>
  <div class="spacing-group">
    <label v-if="label" class="sg-label">{{ label }}</label>
    <div class="sg-body">
      <div class="sg-rule-wrap">
        <select :value="rule" @change="$emit('update:rule', $event.target.value)" class="sg-rule">
          <option value="line">单倍</option>
          <option value="multiple">多倍</option>
          <option value="exact">固定</option>
          <option value="atLeast">最小</option>
          <option value="1.5line">1.5倍</option>
          <option value="double">双倍</option>
        </select>
        <svg class="sg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <input type="number" :value="value" @input="$emit('update:value', $event.target.value)" class="sg-num" min="0" max="100" step="0.1" />
      <span class="sg-unit">{{ ruleLabel }}</span>
      <select v-if="showUnitSelect" :value="unit" @change="$emit('update:unit', $event.target.value)" class="sg-unit-sel">
        <option value="line">行</option>
        <option value="pt">磅</option>
        <option value="inch">英寸</option>
        <option value="cm">厘米</option>
        <option value="mm">毫米</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ label: String, rule: String, value: [Number, String], unit: String, defaultVal: String, size: String })
defineEmits(['update:rule', 'update:value', 'update:unit'])
const showUnitSelect = computed(() => ['exact', 'atLeast'].includes(props.rule))
const ruleLabel = computed(() => {
  const map = { line: '行', multiple: '倍', exact: '磅', atLeast: '磅', '1.5line': '倍', double: '倍' }
  return map[props.rule] || '倍'
})
</script>

<style scoped>
.spacing-group { display: flex; align-items: center; gap: 8px; min-width: 0; }
.sg-label {
  font-size: 13px; color: #64748b; white-space: nowrap; flex-shrink: 0;
}
.sg-body { display: flex; align-items: center; gap: 6px; min-width: 0; }
.sg-rule-wrap { position: relative; min-width: 0; }
.sg-rule {
  min-width: 96px; width: auto; padding: 8px 26px 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; cursor: pointer; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
  appearance: none; -webkit-appearance: none;
}
.sg-arrow {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  width: 16px; height: 16px; color: #94a3b8;
  pointer-events: none;
}
.sg-rule:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.sg-rule:hover:not(:focus) { border-color: #cbd5e1; }
.sg-num {
  width: 70px; padding: 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.sg-num:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.sg-num:hover:not(:focus) { border-color: #cbd5e1; }
.sg-unit { font-size: 12px; color: #94a3b8; white-space: nowrap; }
.sg-unit-sel {
  width: 72px; padding: 8px 10px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; cursor: pointer; box-sizing: border-box;
  transition: border-color 0.15s;
}
.sg-unit-sel:focus { border-color: #14b8a6; }
.sg-unit-sel:hover:not(:focus) { border-color: #cbd5e1; }
</style>
