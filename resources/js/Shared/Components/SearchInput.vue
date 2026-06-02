<script setup>
import { Search, X } from 'lucide-vue-next';

const props = defineProps({
    id: {
        type: String,
        default: undefined,
    },
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
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="w-full" :class="{ 'space-y-2': label }">
        <label v-if="label" :for="id" class="text-sm font-medium text-slate-800">{{ label }}</label>
        <div class="relative w-full">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                :id="id"
                :value="modelValue"
                :placeholder="placeholder"
                class="h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                v-if="modelValue"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-700"
                @click="$emit('update:modelValue', '')"
            >
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>