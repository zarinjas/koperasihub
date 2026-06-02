<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Paperclip, Printer } from 'lucide-vue-next';
import { reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    formOptions: { type: Array, required: true },
    unitOptions: { type: Array, default: () => [] },
    isSuperAdmin: { type: Boolean, default: false },
    submissions: { type: Object, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
    form: props.filters.form || '',
    unit: props.filters.unit || '',
    date: props.filters.date || '',
});

const applyFilters = () => router.get('/admin/form-submissions', filters, { preserveState: true, replace: true });

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.detail_url },
    { label: 'Cetak', icon: Printer, href: row.print_url },
];

const columns = [
    { key: 'reference_no', label: 'Rujukan' },
    { key: 'form', label: 'Borang' },
    { key: 'unit', label: 'Unit' },
    { key: 'submitted_by', label: 'Penghantar' },
    { key: 'status', label: 'Status' },
    { key: 'submitted_at', label: 'Dihantar' },
    { key: 'stamped', label: 'Borang Bercop' },
    { key: 'actions', label: 'Tindakan' },
];
</script>

<template>
    <Head title="Permohonan Borang" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan Borang"
                description="Semak dan urus semua permohonan borang daripada ahli mengikut status, unit dan tarikh."
            />

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <SearchInput id="submission-search" v-model="filters.search" placeholder="Cari rujukan atau nama penghantar" label="Carian" />
                    <SelectInput id="submission-status" v-model="filters.status" label="Status" :options="statusOptions" />
                    <SelectInput id="submission-category" v-model="filters.category" label="Unit" :options="[{ value: '', label: 'Semua Unit' }, ...categoryOptions]" />
                    <SelectInput v-if="isSuperAdmin" id="submission-unit" v-model="filters.unit" label="Unit Bertanggungjawab" :options="[{ value: '', label: 'Semua Unit' }, ...unitOptions]" />
                    <SelectInput id="submission-form" v-model="filters.form" label="Borang" :options="[{ value: '', label: 'Semua Borang' }, ...formOptions]" />
                    <TextInput id="submission-date" v-model="filters.date" type="date" label="Tarikh" />
                    <div class="flex items-end gap-2">
                        <Button type="button" variant="outline" class="h-11 w-full" @click="filters.search='';filters.status='';filters.category='';filters.form='';filters.unit='';filters.date='';applyFilters()">Set Semula</Button>
                        <Button type="button" class="h-11 w-full" @click="applyFilters">Tapis</Button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="submissions.data.length === 0"
                title="Tiada permohonan ditemui."
                description="Permohonan borang akan muncul di sini selepas ahli menghantar borang yang diterbitkan."
                compact
            />

            <DataTable v-else :columns="columns" :rows="submissions.data">
                <template #cell-form="{ row }">
                    <p class="text-sm font-medium text-slate-900">{{ row.form_title || '-' }}</p>
                </template>
                <template #cell-unit="{ row }">
                    <p class="text-sm text-slate-600">{{ row.unit_name || row.category_name || '-' }}</p>
                </template>
                <template #cell-submitted_by="{ row }">
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ row.submitted_by_name || row.member_name || 'Tidak dinyatakan' }}</p>
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" :label="row.status_label" />
                </template>
                <template #cell-stamped="{ row }">
                    <span v-if="row.has_stamped_file" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                        <Paperclip class="h-3 w-3" />
                        Dimuat naik
                    </span>
                    <span v-else class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                        Belum
                    </span>
                </template>
                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="submissions.links" class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Menunjukkan {{ submissions.from || 0 }} - {{ submissions.to || 0 }} daripada {{ submissions.total || 0 }}</p>
                <nav class="flex gap-1">
                    <Button
                        v-for="link in submissions.links"
                        :key="link.label"
                        variant="outline"
                        :disabled="!link.url"
                        :class="link.active ? 'bg-teal-50 border-teal-200 text-teal-800' : ''"
                        @click="link.url ? router.get(link.url, {}, { preserveState: true, replace: true }) : null"
                    >
                        <span v-html="link.label" />
                    </Button>
                </nav>
            </div>
        </section>
    </AdminLayout>
</template>