<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import { reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    products: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
});

const columns = [
    { key: 'name', label: 'Produk' },
    { key: 'category_name', label: 'Kategori' },
    { key: 'requires_guarantor', label: 'Penjamin' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/financing/products', filters, { preserveState: true, replace: true });
};

const getActions = (row) => [
    { label: 'Edit', icon: Pencil, href: `/admin/financing/products/${row.id}/edit` },
];
</script>

<template>
    <Head title="Produk Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Produk Pembiayaan"
                description="Urus produk pembiayaan, had amaun, tempoh, dan keperluan dokumen."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/products/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Produk
                    </Button>
                </template>
            </PageHeader>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-3">
                    <TextInput id="search-product" v-model="filters.search" label="Cari produk" />
                    <SelectInput id="category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                    <div class="flex items-end">
                        <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="products.length === 0"
                title="Tiada produk pembiayaan."
                description="Tambah produk pertama untuk membolehkan ahli membuat permohonan pembiayaan."
                action-label="Tambah Produk"
                action-href="/admin/financing/products/create"
            />

            <DataTable v-else :columns="columns" :rows="products">
                <template #cell-name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.name }}</p>
                        <p class="text-xs text-slate-500">RM {{ row.min_amount ?? '-' }} hingga RM {{ row.max_amount ?? '-' }}</p>
                    </div>
                </template>
                <template #cell-requires_guarantor="{ row }">
                    <StatusBadge :status="row.requires_guarantor ? 'approved' : 'inactive'" :label="row.requires_guarantor ? `${row.guarantor_count} penjamin` : 'Tidak perlu'" />
                </template>
                <template #cell-is_active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Aktif' : 'Tidak aktif'" />
                </template>
                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>
