<template>
  <div class="input-with-unit">
    <label v-if="label" class="iwu-label">{{ label }}</label>
    <div class="iwu-row">
      <input type="number" :value="value" @input="$emit('update:value', $event.target.value)" class="iwu-input" :min="min" :max="max" :step="step" />
      <select :value="unit" @change="$emit('update:unit', $event.target.value)" class="iwu-select">
        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({ label: String, value: [Number, String], unit: String, units: { type: Array, default: () => [] }, min: Number, max: Number, step: Number })
defineEmits(['update:value', 'update:unit'])
</script>

<style scoped>
.input-with-unit { display: flex; align-items: center; gap: 8px; min-width: 0; }
.iwu-label {
  font-size: 13px; color: #64748b; white-space: nowrap; flex-shrink: 0;
}
.iwu-row { display: flex; align-items: center; gap: 6px; min-width: 0; }
.iwu-input {
  width: 84px; padding: 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.iwu-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.iwu-input:hover:not(:focus) { border-color: #cbd5e1; }
.iwu-select {
  width: 72px; padding: 8px 10px;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 14px; color: #1e293b; background: #fff;
  outline: none; cursor: pointer; flex-shrink: 0; box-sizing: border-box;
  transition: border-color 0.15s;
}
.iwu-select:focus { border-color: #14b8a6; }
.iwu-select:hover:not(:focus) { border-color: #cbd5e1; }
</style>
