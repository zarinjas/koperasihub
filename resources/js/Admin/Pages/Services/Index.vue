<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, Eye, FileX2, Pencil, Plus, Trash2, Upload } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    services: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canPublish: { type: Boolean, default: false },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
});

const columns = [
    { key: 'title', label: 'Perkhidmatan' },
    { key: 'category', label: 'Kategori' },
    { key: 'status', label: 'Status' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/services', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.category = '';
    applyFilters();
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;

    router.post(`/admin/services/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const runAction = (id, action) => {
    router.post(`/admin/services/${id}/${action}`, {}, { preserveScroll: true });
};

const categoryLabel = (value) => value ? value.replaceAll('_', ' ') : 'Tanpa kategori';

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.public_url },
    { label: 'Edit', icon: Pencil, condition: props.canEdit, href: `/admin/services/${row.id}/edit` },
    { divider: true, condition: props.canPublish },
    { label: 'Terbitkan', icon: Upload, condition: props.canPublish && row.status !== 'published', onClick: () => runAction(row.id, 'publish') },
    { label: 'Nyahterbit', icon: FileX2, condition: props.canPublish && row.status === 'published', onClick: () => runAction(row.id, 'unpublish') },
    { label: 'Arkib', icon: Archive, condition: props.canPublish && row.status !== 'archived', onClick: () => runAction(row.id, 'archive') },
    { divider: true, condition: props.canDelete },
    { label: 'Padam', icon: Trash2, variant: 'destructive', condition: props.canDelete, onClick: () => askDelete(row.id) },
];
</script>

<template>
    <Head title="Perkhidmatan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Perkhidmatan"
                description="Urus perkhidmatan dan tawaran koperasi yang dipaparkan pada laman awam."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/services/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Perkhidmatan
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <AdminSearchInput id="service-search-filter" v-model="filters.search" placeholder="Cari tajuk atau ringkasan" />
                <AdminSelectFilter id="service-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <AdminSelectFilter id="service-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="services.data.length === 0"
                title="Tiada perkhidmatan ditemui."
                description="Cipta perkhidmatan pertama untuk dipaparkan pada homepage dan halaman awam."
                :action-label="canCreate ? 'Tambah Perkhidmatan' : null"
                :action-href="canCreate ? '/admin/services/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="services.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-950">{{ row.title }}</p>
                            <span v-if="row.is_featured" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Sorotan</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ row.summary || 'Tiada ringkasan disediakan.' }}</p>
                    </div>
                </template>

                <template #cell-category="{ row }">
                    <span class="text-sm text-slate-600">{{ categoryLabel(row.category) }}</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-updated_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.updated_at }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="services.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in services.links"
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
            title="Padam perkhidmatan"
            description="Perkhidmatan ini akan dibuang daripada senarai admin dan halaman awam."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>