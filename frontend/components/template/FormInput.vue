<template>
  <div class="form-input">
    <label v-if="label" class="fi-label">{{ label }}</label>
    <div class="fi-row">
      <input :type="type || 'text'" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" class="fi-input" :placeholder="placeholder" />
      <span v-if="unit" class="fi-unit" :style="unitStyle">{{ unit }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({ label: String, modelValue: [String, Number], unit: String, type: String, placeholder: String, wide: Boolean, unitStyle: Object })
defineEmits(['update:modelValue'])
</script>

<style scoped>
.form-input { display: flex; align-items: center; gap: 8px; min-width: 0; }
.fi-label {
  font-size: 13px; color: #64748b; white-space: nowrap; flex-shrink: 0;
}
.fi-row { display: flex; align-items: center; gap: 6px; min-width: 0; }
.fi-input {
  width: 100px; padding: 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.fi-input::placeholder { color: #cbd5e1; }
.fi-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.fi-input:hover:not(:focus) { border-color: #cbd5e1; }
.fi-unit { font-size: 12px; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }
</style>
