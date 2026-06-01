<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    minMonths: { type: Number, default: 1 },
    maxMonths: { type: Number, default: 360 },
    label: { type: String, default: 'Tempoh' },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const totalMonths = computed({
    get: () => Number(props.modelValue) || 0,
    set: (val) => emit('update:modelValue', val),
});

const years = computed({
    get: () => Math.floor(totalMonths.value / 12),
    set: (val) => {
        const y = Math.max(0, Number(val) || 0);
        const m = Math.min(months.value, 11);
        totalMonths.value = y * 12 + m;
    },
});

const months = computed({
    get: () => totalMonths.value % 12,
    set: (val) => {
        const m = Math.max(0, Math.min(11, Number(val) || 0));
        totalMonths.value = years.value * 12 + m;
    },
});

const yearOptions = computed(() => {
    const maxY = Math.ceil(props.maxMonths / 12);
    const minY = Math.floor(props.minMonths / 12);
    const arr = [];
    for (let i = Math.max(1, minY); i <= maxY; i++) arr.push(i);
    return arr;
});
</script>

<template>
    <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-800">
            {{ label }}<span v-if="required" class="text-red-500"> *</span>
        </label>
        <div class="flex items-center gap-2">
            <select
                :value="years"
                @change="years = parseInt($event.target.value)"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            >
                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }} tahun</option>
            </select>
            <select
                :value="months"
                @change="months = parseInt($event.target.value)"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
            >
                <option v-for="m in 12" :key="m" :value="m - 1">{{ m - 1 }} bulan</option>
            </select>
        </div>
        <p class="text-xs text-slate-500">Jumlah: {{ totalMonths }} bulan</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
