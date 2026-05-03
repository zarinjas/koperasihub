<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Download, FilePlus2, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FilterBar from '@/Shared/Components/FilterBar.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    documents: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    visibilityOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    visibility: props.filters.visibility || '',
    category: props.filters.category || '',
});

const columns = [
    { key: 'title', label: 'Dokumen' },
    { key: 'category_name', label: 'Kategori' },
    { key: 'visibility', label: 'Akses' },
    { key: 'status', label: 'Status' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/documents', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.visibility = '';
    filters.category = '';
    applyFilters();
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteDocument = () => {
    if (!deletingId.value) return;

    router.delete(`/admin/documents/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const visibilityLabel = (value) => ({
    public: 'Public',
    members_only: 'Ahli sahaja',
    admin_only: 'Admin sahaja',
})[value] || value;
</script>

<template>
    <Head title="Dokumen" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Dokumen"
                description="Urus borang, fail muat turun awam, dan dokumen dalaman dengan kawalan akses yang selamat."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/documents/create">
                        <FilePlus2 class="mr-2 h-4 w-4" />
                        Muat Naik Dokumen
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FilterBar>
                <SearchInput v-model="filters.search" placeholder="Cari tajuk atau nama fail" />
                <SelectInput id="document-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <SelectInput id="document-visibility-filter" v-model="filters.visibility" label="Tahap akses" :options="visibilityOptions" />
                <SelectInput id="document-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />

                <template #actions>
                    <Button type="button" variant="outline" @click="resetFilters">Set Semula</Button>
                    <Button type="button" @click="applyFilters">Tapis</Button>
                </template>
            </FilterBar>

            <EmptyState
                v-if="documents.data.length === 0"
                title="Tiada dokumen ditemui."
                description="Muat naik dokumen pertama untuk bina pusat muat turun dan rujukan dalaman."
                :action-label="canCreate ? 'Muat Naik Dokumen' : null"
                :action-href="canCreate ? '/admin/documents/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="documents.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.title }}</p>
                        <p class="text-xs text-slate-500">{{ row.file_name }} · {{ row.file_size_label }}</p>
                    </div>
                </template>

                <template #cell-category_name="{ row }">
                    <span class="text-sm text-slate-600">{{ row.category_name || 'Tanpa kategori' }}</span>
                </template>

                <template #cell-visibility="{ row }">
                    <StatusBadge :status="row.visibility" :label="visibilityLabel(row.visibility)" />
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-updated_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.updated_at }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="row.download_url" variant="outline">
                            <Download class="mr-2 h-4 w-4" />
                            Muat Turun
                        </Button>
                        <Button v-if="canEdit" :as="Link" :href="`/admin/documents/${row.id}/edit`" variant="outline">Edit</Button>
                        <Button v-if="canDelete" type="button" variant="destructive" @click="askDelete(row.id)">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Padam
                        </Button>
                    </div>
                </template>
            </DataTable>

            <div v-if="documents.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in documents.links"
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
            :open="dialogOpen"
            title="Padam dokumen"
            description="Dokumen ini akan dipadam daripada sistem dan fail asalnya juga akan dibuang daripada storan."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteDocument"
        />
    </AdminLayout>
</template>
