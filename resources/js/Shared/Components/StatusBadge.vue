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
    submitted: 'Dihantar',
    guarantor_pending: 'Menunggu Maklum Balas Penjamin',
    guarantor_accepted: 'Sedia Untuk Semakan',
    guarantor_rejected: 'Penjamin Tidak Bersetuju',
    accepted: 'Bersetuju Menjadi Penjamin',
    expired: 'Tamat Tempoh',
    under_review: 'Dalam Semakan',
    incomplete_documents: 'Dokumen Tambahan Diperlukan',
    open: 'Terbuka',
    in_progress: 'Dalam tindakan',
    resolved: 'Selesai',
    closed: 'Ditutup',
    approved: 'Diluluskan',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
    completed: 'Selesai',
    hadir: 'Hadir',
    tidak_hadir: 'Tidak Hadir',
    mungkin: 'Mungkin',
    physical: 'Fizikal',
    online: 'Atas Talian',
    hybrid: 'Hibrid',
    low: 'Rendah',
    medium: 'Sederhana',
    high: 'Tinggi',
    public: 'Public',
    members_only: 'Ahli sahaja',
    admin_only: 'Admin sahaja',
    specific_member: 'Ahli tertentu',
    aktif: 'Aktif',
    belum_aktif: 'Belum Aktif',
    dinyahaktifkan: 'Dinyahaktifkan',
};

const variants = {
    draft: 'border-slate-200 bg-slate-100 text-slate-700',
    archived: 'border-slate-200 bg-slate-100 text-slate-700',
    inactive: 'border-slate-200 bg-slate-100 text-slate-700',
    cancelled: 'border-slate-200 bg-slate-100 text-slate-700',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    hadir: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    tidak_hadir: 'border-red-200 bg-red-50 text-red-700',
    mungkin: 'border-amber-200 bg-amber-50 text-amber-700',
    physical: 'border-blue-200 bg-blue-50 text-blue-700',
    online: 'border-purple-200 bg-purple-50 text-purple-700',
    hybrid: 'border-teal-200 bg-teal-50 text-teal-700',
    closed: 'border-slate-200 bg-slate-100 text-slate-700',
    published: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    resolved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    pending_stamp_upload: 'border-amber-200 bg-amber-50 text-amber-700',
    guarantor_pending: 'border-amber-200 bg-amber-50 text-amber-700',
    incomplete_documents: 'border-amber-200 bg-amber-50 text-amber-700',
    under_review: 'border-blue-200 bg-blue-50 text-blue-700',
    submitted: 'border-blue-200 bg-blue-50 text-blue-700',
    guarantor_accepted: 'border-cyan-200 bg-cyan-50 text-cyan-700',
    accepted: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    open: 'border-amber-200 bg-amber-50 text-amber-700',
    in_progress: 'border-blue-200 bg-blue-50 text-blue-700',
    guarantor_rejected: 'border-red-200 bg-red-50 text-red-700',
    rejected: 'border-red-200 bg-red-50 text-red-700',
    suspended: 'border-red-200 bg-red-50 text-red-700',
    expired: 'border-slate-200 bg-slate-100 text-slate-700',
    low: 'border-slate-200 bg-slate-100 text-slate-700',
    medium: 'border-blue-200 bg-blue-50 text-blue-700',
    high: 'border-red-200 bg-red-50 text-red-700',
    public: 'border-blue-200 bg-blue-50 text-blue-700',
    members_only: 'border-amber-200 bg-amber-50 text-amber-700',
    admin_only: 'border-slate-200 bg-slate-100 text-slate-700',
    specific_member: 'border-teal-200 bg-teal-50 text-teal-700',
    aktif: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    belum_aktif: 'border-slate-200 bg-slate-100 text-slate-700',
    dinyahaktifkan: 'border-red-200 bg-red-50 text-red-700',
};

const badgeLabel = computed(() => props.label || labels[props.status] || props.status);
const badgeClass = computed(() => variants[props.status] || 'border-slate-200 bg-slate-100 text-slate-700');
</script>

<template>
    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap" :class="badgeClass">
        {{ badgeLabel }}
    </span>
</template>