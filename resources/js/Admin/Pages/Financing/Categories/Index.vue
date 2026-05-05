<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
});

const columns = [
    { key: 'name', label: 'Kategori' },
    { key: 'type_label', label: 'Jenis' },
    { key: 'products_count', label: 'Produk' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/financing/categories', filters, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Kategori Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Kategori Pembiayaan"
                description="Urus kategori pembiayaan dan jadual kadar yang dipaparkan kepada ahli."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/categories/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Kategori
                    </Button>
                </template>
            </PageHeader>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row">
                    <TextInput id="search-category" v-model="filters.search" label="Cari kategori" />
                    <div class="flex items-end">
                        <Button type="button" class="h-11" @click="applyFilters">
                            <Search class="mr-2 h-4 w-4" />
                            Cari
                        </Button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="categories.length === 0"
                title="Tiada kategori pembiayaan."
                description="Tambah kategori pertama untuk mula mengurus produk pembiayaan."
                action-label="Tambah Kategori"
                action-href="/admin/financing/categories/create"
            />

            <DataTable v-else :columns="columns" :rows="categories">
                <template #cell-name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.name }}</p>
                        <p class="text-xs text-slate-500">{{ row.description || 'Tiada penerangan.' }}</p>
                    </div>
                </template>

                <template #cell-is_active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Aktif' : 'Tidak aktif'" />
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="`/admin/financing/categories/${row.id}/edit`" variant="outline">Edit</Button>
                    </div>
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>
