<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
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
    applications: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const formFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
    product: props.filters.product || '',
});

const categoryOptions = computed(() => [
    { value: '', label: 'Semua Kategori' },
    ...props.categories.map((c) => ({ value: String(c.id), label: c.name })),
]);

const statusOptions = computed(() => [
    { value: '', label: 'Semua Status' },
    ...props.statuses.map((s) => ({ value: s.value, label: s.label })),
]);

const applyFilters = () => {
    router.get('/admin/financing/applications', formFilters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    formFilters.search = '';
    formFilters.status = '';
    formFilters.category = '';
    formFilters.product = '';
    applyFilters();
};

const columns = [
    { key: 'reference_no', label: 'No. Rujukan' },
    { key: 'member', label: 'Ahli' },
    { key: 'product', label: 'Produk' },
    { key: 'category', label: 'Kategori' },
    { key: 'amount', label: 'Jumlah (RM)' },
    { key: 'tenure', label: 'Tempoh' },
    { key: 'status', label: 'Status' },
    { key: 'date', label: 'Tarikh' },
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
    router.delete(`/admin/financing/applications/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false;
            deletingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Permohonan Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan Pembiayaan"
                description="Urus permohonan pembiayaan daripada ahli koperasi."
            />

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterBar>
                <AdminSearchInput
                    id="application-search"
                    v-model="formFilters.search"
                    placeholder="Cari no. rujukan atau nama ahli..."
                />
                <AdminSelectFilter
                    id="application-status-filter"
                    v-model="formFilters.status"
                    label="Status"
                    :options="statusOptions"
                />
                <AdminSelectFilter
                    id="application-category-filter"
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
                v-if="applications.data.length === 0"
                title="Tiada permohonan pembiayaan"
                description="Permohonan daripada ahli akan dipaparkan di sini."
            />

            <DataTable v-else :columns="columns" :rows="applications.data">
                <template #cell-reference_no="{ row }">
                    <Link
                        :href="`/admin/financing/applications/${row.id}`"
                        class="font-semibold text-teal-700 hover:text-teal-800 hover:underline"
                    >
                        {{ row.reference_no }}
                    </Link>
                </template>

                <template #cell-member="{ row }">
                    <div>
                        <p class="text-sm text-slate-700">{{ row.member_name || '-' }}</p>
                        <p v-if="row.member_no" class="text-xs text-slate-400">{{ row.member_no }}</p>
                    </div>
                </template>

                <template #cell-product="{ row }">
                    <span class="text-sm text-slate-600">{{ row.product?.name || '-' }}</span>
                </template>

                <template #cell-category="{ row }">
                    <span class="text-sm text-slate-600">{{ row.category?.name || '-' }}</span>
                </template>

                <template #cell-amount="{ row }">
                    <span class="text-sm font-medium text-slate-700">
                        RM{{ Number(row.amount_requested).toLocaleString() }}
                    </span>
                </template>

                <template #cell-tenure="{ row }">
                    <span class="text-sm text-slate-600">{{ row.tenure_months ?? '-' }} bulan</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status?.value" :label="row.status?.label" />
                </template>

                <template #cell-date="{ row }">
                    <span class="text-sm text-slate-500">{{ row.created_at ?? '-' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        @click="askDelete(row.id)"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </Button>
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

        <ConfirmDialog
            :open="deleteDialogOpen"
            title="Padam Permohonan"
            description="Permohonan ini akan dipadam secara kekal. Tindakan ini tidak boleh dibatalkan."
            confirm-label="Padam"
            @cancel="deleteDialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>
