<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
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
    canEdit: { type: Boolean, default: false },
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
                description="Kategori pembiayaan ini ialah rujukan sistem yang digunakan untuk menyusun produk pembiayaan."
            />

            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                Kategori pembiayaan disediakan sebagai rujukan sistem. Produk masih boleh diurus mengikut peraturan sedia ada tanpa memadam kategori ini.
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row">
                    <TextInput id="search-category" v-model="filters.search" label="Cari kategori pembiayaan" />
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
                description="Kategori pembiayaan sistem belum tersedia untuk koperasi ini."
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
                    <div v-if="canEdit" class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="`/admin/financing/categories/${row.id}/edit`" variant="outline">Edit</Button>
                    </div>
                    <span v-else class="text-sm text-slate-500">Rujukan sistem</span>
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>
