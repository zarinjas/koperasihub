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
    suspended: 'Digantung',
    pending: 'Menunggu',
    pending_stamp_upload: 'Menunggu Borang Bercop',
    under_review: 'Dalam Proses',
    incomplete_documents: 'Dokumen Tidak Lengkap',
    open: 'Terbuka',
    in_progress: 'Dalam tindakan',
    resolved: 'Selesai',
    closed: 'Ditutup',
    approved: 'Diluluskan',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
    low: 'Rendah',
    medium: 'Sederhana',
    high: 'Tinggi',
    public: 'Public',
    members_only: 'Ahli sahaja',
    admin_only: 'Admin sahaja',
    specific_member: 'Ahli tertentu',
};

const variants = {
    draft: 'border-slate-200 bg-slate-100 text-slate-700',
    archived: 'border-slate-200 bg-slate-100 text-slate-700',
    inactive: 'border-slate-200 bg-slate-100 text-slate-700',
    cancelled: 'border-slate-200 bg-slate-100 text-slate-700',
    closed: 'border-slate-200 bg-slate-100 text-slate-700',
    published: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    resolved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    pending_stamp_upload: 'border-amber-200 bg-amber-50 text-amber-700',
    under_review: 'border-amber-200 bg-amber-50 text-amber-700',
    submitted: 'border-blue-200 bg-blue-50 text-blue-700',
    open: 'border-amber-200 bg-amber-50 text-amber-700',
    in_progress: 'border-blue-200 bg-blue-50 text-blue-700',
    incomplete_documents: 'border-red-200 bg-red-50 text-red-700',
    rejected: 'border-red-200 bg-red-50 text-red-700',
    suspended: 'border-red-200 bg-red-50 text-red-700',
    low: 'border-slate-200 bg-slate-100 text-slate-700',
    medium: 'border-blue-200 bg-blue-50 text-blue-700',
    high: 'border-red-200 bg-red-50 text-red-700',
    public: 'border-blue-200 bg-blue-50 text-blue-700',
    members_only: 'border-amber-200 bg-amber-50 text-amber-700',
    admin_only: 'border-slate-200 bg-slate-100 text-slate-700',
    specific_member: 'border-teal-200 bg-teal-50 text-teal-700',
};

const badgeLabel = computed(() => props.label || labels[props.status] || props.status);
const badgeClass = computed(() => variants[props.status] || 'border-slate-200 bg-slate-100 text-slate-700');
</script>

<template>
    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold" :class="badgeClass">
        {{ badgeLabel }}
    </span>
</template>
