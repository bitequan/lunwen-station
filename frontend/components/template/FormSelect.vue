<template>
  <div class="form-select">
    <label v-if="label" class="fs-label">{{ label }}</label>
    <div class="fs-wrap">
      <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" class="fs-select">
        <option v-for="opt in sortedOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <svg class="fs-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ label: String, modelValue: [String, Number], options: { type: Array, default: () => [] }, defaultVal: String })
defineEmits(['update:modelValue'])
const sortedOptions = computed(() => {
  if (!props.defaultVal) return props.options
  const def = props.options.find(o => o.value === props.defaultVal)
  if (!def) return props.options
  return [def, ...props.options.filter(o => o.value !== props.defaultVal)]
})
</script>

<style scoped>
.form-select { display: flex; align-items: center; gap: 8px; min-width: 0; }
.fs-label {
  font-size: 13px; color: #64748b; white-space: nowrap; flex-shrink: 0;
}
.fs-wrap { position: relative; min-width: 0; }
.fs-select {
  min-width: 110px; width: auto; padding: 8px 26px 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; cursor: pointer; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
  appearance: none; -webkit-appearance: none;
}
.fs-select:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.fs-select:hover:not(:focus) { border-color: #cbd5e1; }
.fs-arrow {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  width: 16px; height: 16px; color: #94a3b8;
  pointer-events: none; transition: color 0.15s;
}
.fs-wrap:hover .fs-arrow { color: #64748b; }
</style>
