<script setup>
import DynamicFieldRenderer from '@/Shared/Components/Financing/DynamicFieldRenderer.vue';

defineProps({
  sections: { type: Array, default: () => [] },
  values: { type: Object, default: () => ({}) },
  mode: { type: String, default: 'builder-preview' },
  errors: { type: Object, default: () => ({}) },
  product: { type: Object, default: null },
});

const emit = defineEmits(['update:value', 'file-change']);

function getFieldValue(field, allValues) {
  const v = allValues[field.field_key];
  return v !== undefined ? v : null;
}

function getFieldErrors(field, allErrors) {
  return allErrors[field.field_key] || '';
}
</script>

<template>
  <div v-if="!sections?.length" class="py-6 text-center text-sm text-slate-400">
    <slot name="empty">Tiada seksyen.</slot>
  </div>

  <div v-else class="space-y-6">
    <div v-for="section in sections" :key="section.id || section.title" class="section-block">
      <!-- Section header -->
      <div v-if="section.title || section.description" class="mb-3">
        <h3 v-if="section.title" class="text-sm font-semibold text-slate-800"
          :class="mode === 'print' || mode === 'pdf' ? 'border-b pb-1 text-xs uppercase text-slate-700' : ''">
          {{ section.title }}
        </h3>
        <p v-if="section.description" class="mt-0.5 text-xs text-slate-500"
          :class="mode === 'print' || mode === 'pdf' ? 'text-[10px]' : ''">
          {{ section.description }}
        </p>
      </div>

      <!-- Fields -->
      <div class="space-y-3" :class="mode === 'print' || mode === 'pdf' ? 'space-y-2' : 'space-y-3'">
        <DynamicFieldRenderer
          v-for="field in (section.fields || [])"
          :key="field.id || field.field_key"
          :field="field"
          :value="getFieldValue(field, values)"
          :mode="mode"
          :errors="getFieldErrors(field, errors)"
          :product="product"
          @update:value="(val) => emit('update:value', { fieldKey: field.field_key, value: val })"
          @file-change="(file) => emit('file-change', { fieldKey: field.field_key, file })"
        />
      </div>
    </div>
  </div>
</template>