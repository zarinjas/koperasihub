<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    categories: { type: Array, required: true },
});

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'type', label: 'Jenis' },
    { key: 'products_count', label: 'Produk' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const typeLabel = (type) => type === 'berpenjamin' ? 'Berpenjamin' : 'Tanpa Penjamin';
const typeClass = (type) => type === 'berpenjamin'
    ? 'border-orange-200 bg-orange-50 text-orange-700'
    : 'border-blue-200 bg-blue-50 text-blue-700';
</script>

<template>
    <Head title="Kategori Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Kategori Pembiayaan"
                description="Urus kategori produk pembiayaan koperasi."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/categories/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Kategori
                    </Button>
                </template>
            </PageHeader>

            <EmptyState
                v-if="categories.length === 0"
                title="Tiada kategori pembiayaan"
                description="Tambah kategori untuk mengelaskan produk pembiayaan."
                action-label="Tambah Kategori"
                action-href="/admin/financing/categories/create"
            />

            <DataTable v-else :columns="columns" :rows="categories">
                <template #cell-name="{ row }">
                    <Link
                        :href="`/admin/financing/categories/${row.id}/edit`"
                        class="font-semibold text-teal-700 hover:text-teal-800 hover:underline"
                    >
                        {{ row.name }}
                    </Link>
                </template>

                <template #cell-type="{ row }">
                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                        :class="typeClass(row.type)"
                    >
                        {{ typeLabel(row.type) }}
                    </span>
                </template>

                <template #cell-products_count="{ row }">
                    <span class="text-sm text-slate-600">{{ row.products_count ?? 0 }}</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge
                        :status="row.is_active ? 'active' : 'inactive'"
                        :label="row.is_active ? 'Aktif' : 'Tidak Aktif'"
                    />
                </template>

                <template #cell-actions="{ row }">
                    <Link
                        :href="`/admin/financing/categories/${row.id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        Edit
                    </Link>
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>