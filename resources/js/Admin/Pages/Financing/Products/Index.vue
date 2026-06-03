<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    products: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const formFilters = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
});

const categoryOptions = computed(() =>
    props.categoryOptions.map((option) => ({
        value: String(option.value ?? ''),
        label: option.label ?? '',
    })),
);

const applyFilters = () => {
    router.get('/admin/financing/products', formFilters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    formFilters.search = '';
    formFilters.category = '';
    applyFilters();
};

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'category', label: 'Kategori' },
    { key: 'amount', label: 'Jumlah (RM)' },
    { key: 'tenure', label: 'Tempoh' },
    { key: 'rate', label: 'Kadar' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const deleteDialogOpen = ref(false);

const askDelete = (id) => {
    deletingId.value = id;
    deleteDialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;
    router.post(`/admin/financing/products/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const formatAmount = (min, max) => {
    if (min && max) return `${Number(min).toLocaleString()} - ${Number(max).toLocaleString()}`;
    if (min) return `${Number(min).toLocaleString()}+`;
    if (max) return `≤ ${Number(max).toLocaleString()}`;
    return '-';
};
</script>

<template>
    <Head title="Produk Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Produk Pembiayaan"
                description="Urus produk pembiayaan yang ditawarkan kepada ahli koperasi."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/products/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Produk
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <AdminSearchInput
                    id="product-search"
                    v-model="formFilters.search"
                    placeholder="Cari nama produk..."
                />
                <AdminSelectFilter
                    id="product-category-filter"
                    v-model="formFilters.category"
                    label="Kategori"
                    :options="categoryOptions"
                />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">
                        Set Semula
                    </Button>
                    <Button type="button" class="h-11" @click="applyFilters">
                        Tapis
                    </Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="products.length === 0"
                title="Tiada produk pembiayaan"
                description="Tambah produk pembiayaan baharu untuk ditawarkan kepada ahli."
                action-label="Tambah Produk"
                action-href="/admin/financing/products/create"
            />

            <DataTable v-else :columns="columns" :rows="products">
                <template #cell-name="{ row }">
                    <Link
                        :href="`/admin/financing/products/${row.id}/edit`"
                        class="font-semibold text-teal-700 hover:text-teal-800 hover:underline"
                    >
                        {{ row.name }}
                    </Link>
                </template>

                <template #cell-category="{ row }">
                    <span class="text-sm text-slate-600">{{ row.category_name || '-' }}</span>
                </template>

                <template #cell-amount="{ row }">
                    <span class="text-sm text-slate-700">
                        RM{{ formatAmount(row.min_amount, row.max_amount) }}
                    </span>
                </template>

                <template #cell-tenure="{ row }">
                    <span class="text-sm text-slate-700">
                        {{ row.min_tenure_months }} - {{ row.max_tenure_months }} bulan
                    </span>
                </template>

                <template #cell-rate="{ row }">
                    <span class="text-sm font-medium text-slate-700">
                        {{ row.annual_rate_percent !== null ? `${row.annual_rate_percent}%` : '-' }}
                    </span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge
                        :status="row.is_active ? 'active' : 'inactive'"
                        :label="row.is_active ? 'Aktif' : 'Tidak Aktif'"
                    />
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center gap-1.5">
                        <Link
                            :href="`/admin/financing/products/${row.id}/edit`"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Edit
                        </Link>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="askDelete(row.id)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </template>
            </DataTable>
        </section>

        <ConfirmDialog
            :open="deleteDialogOpen"
            title="Padam Produk"
            description="Produk ini akan dipadam secara kekal. Tindakan ini tidak boleh dibatalkan."
            confirm-label="Padam"
            @cancel="deleteDialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>