<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    applications: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    productOptions: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
    product: props.filters.product || '',
    type: props.filters.type || '',
});

const columns = [
    { key: 'reference_no', label: 'Rujukan' },
    { key: 'member_name', label: 'Pemohon' },
    { key: 'product_name', label: 'Produk' },
    { key: 'status', label: 'Status' },
    { key: 'submitted_at', label: 'Dihantar' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/financing/applications', filters, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Permohonan Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan Pembiayaan"
                description="Semak, tapis, dan urus permohonan pembiayaan ahli secara berstruktur."
            />

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-5">
                    <TextInput id="search-financing-application" v-model="filters.search" label="Cari" />
                    <SelectInput id="status" v-model="filters.status" label="Status" :options="statusOptions" />
                    <SelectInput id="category" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                    <SelectInput id="product" v-model="filters.product" label="Produk" :options="productOptions" />
                    <SelectInput id="type" v-model="filters.type" label="Jenis" :options="typeOptions" />
                </div>
                <div class="mt-4 flex justify-end">
                    <Button type="button" @click="applyFilters">Tapis</Button>
                </div>
            </div>

            <EmptyState
                v-if="applications.data.length === 0"
                title="Tiada permohonan pembiayaan ditemui."
                description="Permohonan yang dihantar oleh ahli akan dipaparkan di sini."
            />

            <DataTable v-else :columns="columns" :rows="applications.data">
                <template #cell-reference_no="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.reference_no }}</p>
                        <p class="text-xs text-slate-500">{{ row.category_name || '-' }} · {{ row.unit_name || 'Tiada unit' }}</p>
                    </div>
                </template>
                <template #cell-member_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.member_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.member_no || '-' }}</p>
                    </div>
                </template>
                <template #cell-product_name="{ row }">
                    <div class="space-y-1">
                        <p class="text-sm text-slate-700">{{ row.product_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.amount_requested }}</p>
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" :label="row.status_label" />
                </template>
                <template #cell-actions="{ row }">
                    <Button :as="Link" :href="row.show_url" variant="outline">Lihat</Button>
                </template>
            </DataTable>

            <div v-if="applications.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in applications.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>
        </section>
    </AdminLayout>
</template>
