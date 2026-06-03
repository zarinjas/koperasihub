<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, Bell, Eye, FileX2, Mail, Pencil, Pin, PinOff, Plus, Trash2, Upload } from 'lucide-vue-next';
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
    announcements: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    audienceOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canPublish: { type: Boolean, default: false },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    audience: props.filters.audience || '',
});

const columns = [
    { key: 'title', label: 'Pengumuman' },
    { key: 'audience', label: 'Audiens' },
    { key: 'notifications', label: 'Notifikasi' },
    { key: 'status', label: 'Status' },
    { key: 'published_at_human', label: 'Tarikh terbit' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/announcements', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.audience = '';
    applyFilters();
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;

    router.post(`/admin/announcements/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const runAction = (id, action) => {
    router.post(`/admin/announcements/${id}/${action}`, {}, { preserveScroll: true });
};

const audienceLabel = (value) => ({
    public: 'Public',
    members: 'Ahli sahaja',
    admins: 'Admin sahaja',
})[value] || value;

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.public_url },
    { label: 'Edit', icon: Pencil, condition: props.canEdit, href: `/admin/announcements/${row.id}/edit` },
    { label: 'Pin', icon: Pin, condition: props.canEdit && !row.is_pinned, onClick: () => runAction(row.id, 'pin') },
    { label: 'Nyahpin', icon: PinOff, condition: props.canEdit && row.is_pinned, onClick: () => runAction(row.id, 'unpin') },
    { divider: true, condition: props.canPublish },
    { label: 'Terbitkan', icon: Upload, condition: props.canPublish && row.status !== 'published', onClick: () => runAction(row.id, 'publish') },
    { label: 'Nyahterbit', icon: FileX2, condition: props.canPublish && row.status === 'published', onClick: () => runAction(row.id, 'unpublish') },
    { label: 'Arkib', icon: Archive, condition: props.canPublish && row.status !== 'archived', onClick: () => runAction(row.id, 'archive') },
    { divider: true, condition: props.canDelete },
    { label: 'Padam', icon: Trash2, variant: 'destructive', condition: props.canDelete, onClick: () => askDelete(row.id) },
];
</script>

<template>
    <Head title="Pengumuman" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Pengumuman"
                description="Urus hebahan awam dan tetapkan tarikh siaran, tamat tempoh, serta status pin."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/announcements/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Pengumuman
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <AdminSearchInput id="announcement-search-filter" v-model="filters.search" placeholder="Cari tajuk atau ringkasan" />
                <AdminSelectFilter id="announcement-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <AdminSelectFilter id="announcement-audience-filter" v-model="filters.audience" label="Audiens" :options="audienceOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="announcements.data.length === 0"
                title="Tiada pengumuman ditemui."
                description="Cipta pengumuman baharu untuk dipaparkan pada laman awam."
                :action-label="canCreate ? 'Tambah Pengumuman' : null"
                :action-href="canCreate ? '/admin/announcements/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="announcements.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-950">{{ row.title }}</p>
                            <span v-if="row.is_pinned" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Dipin</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ row.summary || row.content_preview || 'Tiada ringkasan disediakan.' }}</p>
                    </div>
                </template>

                <template #cell-audience="{ row }">
                    <StatusBadge :status="row.audience" :label="audienceLabel(row.audience)" />
                </template>

                <template #cell-notifications="{ row }">
                    <div class="flex items-center gap-2">
                        <span
                            v-if="row.send_notification"
                            class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700"
                        >
                            <Bell class="h-3 w-3" />
                            Sistem
                        </span>
                        <span
                            v-if="row.send_email"
                            class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                        >
                            <Mail class="h-3 w-3" />
                            Emel
                        </span>
                        <span v-if="!row.send_notification && !row.send_email" class="text-xs text-slate-400">-</span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-published_at_human="{ row }">
                    <span class="text-sm text-slate-600">{{ row.published_at_human || '-' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="announcements.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in announcements.links"
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
            title="Padam pengumuman"
            description="Pengumuman ini akan dibuang daripada sistem dan tidak lagi dipaparkan kepada pelawat."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>