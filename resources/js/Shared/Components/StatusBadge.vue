<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: null,
    },
});

const labels = {
    draft: 'Draf',
    published: 'Diterbitkan',
    archived: 'Diarkibkan',
    active: 'Aktif',
    inactive: 'Tidak aktif',
    pending: 'Menunggu',
    approved: 'Diluluskan',
    rejected: 'Ditolak',
};

const variants = {
    draft: 'border-slate-200 bg-slate-100 text-slate-700',
    archived: 'border-slate-200 bg-slate-100 text-slate-700',
    inactive: 'border-slate-200 bg-slate-100 text-slate-700',
    published: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    rejected: 'border-red-200 bg-red-50 text-red-700',
};

const badgeLabel = computed(() => props.label || labels[props.status] || props.status);
const badgeClass = computed(() => variants[props.status] || 'border-slate-200 bg-slate-100 text-slate-700');
</script>

<template>
    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold" :class="badgeClass">
        {{ badgeLabel }}
    </span>
</template>
