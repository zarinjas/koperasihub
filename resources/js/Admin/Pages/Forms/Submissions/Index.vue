<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Paperclip, Printer } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    formRecord: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    stampedStateOptions: { type: Array, required: true },
    submissions: { type: Object, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    date: props.filters.date || '',
    stamped_state: props.filters.stamped_state || '',
});

const isStampedForm = computed(() => props.formRecord.submission_method === 'requires_stamped_upload');

const columns = computed(() => {
    const cols = [
        { key: 'reference_no', label: 'Rujukan' },
        { key: 'submitted_by_name', label: 'Penghantar' },
        { key: 'unit', label: 'Unit Bertanggungjawab' },
        { key: 'status', label: 'Status' },
        { key: 'submitted_at', label: 'Dihantar pada' },
    ];
    if (isStampedForm.value) {
        cols.push({ key: 'stamped', label: 'Borang Bercop' });
    }
    cols.push({ key: 'actions', label: 'Tindakan' });
    return cols;
});

const applyFilters = () => router.get(`/admin/forms/${props.formRecord.id}/submissions`, filters, { preserveState: true, replace: true });

const statusLabel = (status) => {
    const map = {
        draft: 'Draf',
        pending_stamp_upload: 'Menunggu Borang Bercop',
        submitted: 'Dihantar',
        under_review: 'Dalam Proses',
        incomplete_documents: 'Dokumen Tidak Lengkap',
        approved: 'Diluluskan',
        rejected: 'Ditolak',
        closed: 'Ditutup',
    };
    return map[status] || status;
};

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.detail_url },
    { label: 'Cetak', icon: Printer, href: row.print_url },
];
</script>

<template>
    <Head :title="`Submission - ${formRecord.title}`" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader :title="`Submission: ${formRecord.title}`" description="Semak jawapan, dokumen sokongan, tandatangan, dan status tindakan borang ini." />

            <AdminFilterBar>
                <AdminSearchInput id="submission-search-filter" v-model="filters.search" placeholder="Cari rujukan atau nama penghantar" />
                <AdminSelectFilter id="submission-status" v-model="filters.status" label="Status" :options="statusOptions" />
                <TextInput id="submission-date" v-model="filters.date" type="date" label="Tarikh" />
                <AdminSelectFilter v-if="isStampedForm" id="stamped-state" v-model="filters.stamped_state" label="Borang Bercop" :options="stampedStateOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="filters.search='';filters.status='';filters.date='';filters.stamped_state='';applyFilters()">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="submissions.data.length === 0"
                title="Tiada submission ditemui."
                description="Submission akan muncul di sini selepas borang diterbitkan dan dihantar oleh pengguna."
                compact
            />

            <DataTable v-else :columns="columns" :rows="submissions.data">
                <template #cell-unit="{ row }">
                    <p class="text-sm text-slate-600">{{ row.unit_name || '-' }}</p>
                </template>
                <template #cell-submitted_by_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ row.submitted_by_name || row.member_name || 'Tidak dinyatakan' }}</p>
                        <p class="text-xs text-slate-500">{{ row.submitted_by_email || row.member_name || '-' }}</p>
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" :label="statusLabel(row.status)" />
                </template>
                <template #cell-stamped="{ row }">
                    <div class="flex items-center gap-2">
                        <span v-if="row.has_stamped_file" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                            <Paperclip class="h-3 w-3" />
                            Dimuat naik
                        </span>
                        <span v-else class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                            Belum dimuat naik
                        </span>
                    </div>
                </template>
                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>