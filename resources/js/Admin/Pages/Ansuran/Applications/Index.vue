<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    applications: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const formFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const applyFilters = () => {
    router.get('/admin/ansuran/applications', formFilters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    formFilters.search = '';
    formFilters.status = '';
    applyFilters();
};

const statusOptions = computed(() => [
    { value: '', label: 'Semua Status' },
    ...props.statuses.map((s) => ({ value: s.value, label: s.label })),
]);

const columns = [
    { key: 'application_no', label: 'No. Permohonan' },
    { key: 'member', label: 'Ahli' },
    { key: 'product', label: 'Produk' },
    { key: 'price', label: 'Harga (RM)' },
    { key: 'monthly', label: 'Bulanan (RM)' },
    { key: 'tenure', label: 'Tempoh' },
    { key: 'status', label: 'Status' },
    { key: 'date', label: 'Tarikh' },
    { key: 'actions', label: 'Tindakan' },
];

const rows = computed(() => {
    return (props.applications.data || []).map((app) => ({
        id: app.id,
        application_no: app.application_no,
        member: app.member?.name || '-',
        product: app.product?.name || '-',
        price: `RM ${Number(app.price || app.financial?.full_price || 0).toFixed(2)}`,
        monthly: `RM ${Number(app.monthly || app.financial?.monthly_amount || 0).toFixed(2)}`,
        tenure: `${app.tenure_months || app.financial?.tenure_months || '-'} Bulan`,
        status: app.status,
        status_label: app.status_label,
        date: app.created_at_display || app.created_at || '-',
    }));
});
</script>

<template>
    <AdminLayout>
        <Head title="Permohonan Ansuran Mudah" />
        <PageHeader title="Permohonan Ansuran Mudah" description="Semak dan urus permohonan ansuran mudah" />

        <div class="space-y-6">
            <AdminFilterBar>
                <AdminSearchInput v-model="formFilters.search" placeholder="Cari permohonan..." />
                <AdminSelectFilter id="status-filter" v-model="formFilters.status" label="Status" :options="statusOptions" />
                <template #actions>
                    <Button variant="outline" @click="resetFilters">Set Semula</Button>
                    <Button @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <DataTable v-if="rows.length > 0" :columns="columns" :rows="rows">
                <template #cell-status="{ row: app }">
                    <StatusBadge :status="app.status" :label="app.status_label" />
                </template>
                <template #cell-actions="{ row: app }">
                    <Link :href="'/admin/ansuran/applications/' + app.id" class="text-teal-700 hover:underline text-sm font-medium">Semak</Link>
                </template>
            </DataTable>

            <EmptyState v-else title="Tiada Permohonan" description="Belum ada permohonan ansuran mudah." />
        </div>
    </AdminLayout>
</template>