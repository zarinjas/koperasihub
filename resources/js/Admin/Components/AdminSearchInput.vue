<script setup>
import SearchInput from '@/Shared/Components/SearchInput.vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Cari...',
    },
    label: {
        type: String,
        default: 'Carian',
    },
    id: {
        type: String,
        default: 'admin-search-filter',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="w-full space-y-2">
        <label :for="id" class="text-sm font-medium text-slate-800">{{ label }}</label>
        <SearchInput
            :id="id"
            :model-value="modelValue"
            :placeholder="placeholder"
            @update:model-value="$emit('update:modelValue', $event)"
        />
    </div>
</template>