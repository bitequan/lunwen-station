<template>
  <div class="toggle-item">
    <label v-if="label" class="tg-label">{{ label }}</label>
    <label class="switch">
      <input type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)">
      <span class="slider"></span>
    </label>
    <span v-if="hint" class="tg-hint">{{ hint }}</span>
  </div>
</template>

<script setup>
defineProps({ label: String, modelValue: Boolean, defaultVal: Boolean, hint: String })
defineEmits(['update:modelValue'])
</script>

<style scoped>
.toggle-item { display: flex; align-items: center; gap: 8px; min-width: 0; }
.tg-label {
  font-size: 13px; color: #64748b; white-space: nowrap; flex-shrink: 0;
}
.tg-hint {
  font-size: 12px; color: #94a3b8; font-weight: 400;
  background: #f1f5f9; padding: 2px 6px; border-radius: 4px;
}
.switch { position: relative; display: inline-block; width: 42px; height: 24px; flex-shrink: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch .slider {
  position: absolute; cursor: pointer; inset: 0; background: #cbd5e1;
  border-radius: 24px; transition: background 0.2s;
}
.switch .slider::before {
  content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px;
  background: #fff; border-radius: 50%; transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.switch input:checked + .slider { background: #14b8a6; }
.switch input:checked + .slider::before { transform: translateX(18px); }
.switch:hover .slider { background: #94a3b8; }
.switch:hover input:checked + .slider { background: #0d9488; }
</style>
