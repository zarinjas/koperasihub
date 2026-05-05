<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pin, PinOff, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterActions from '@/Admin/Components/AdminFilterActions.vue';
import AdminFilterGrid from '@/Admin/Components/AdminFilterGrid.vue';
import AdminFilterPanel from '@/Admin/Components/AdminFilterPanel.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
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

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    audience: props.filters.audience || '',
});

const columns = [
    { key: 'title', label: 'Pengumuman' },
    { key: 'audience', label: 'Audiens' },
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

    router.delete(`/admin/announcements/${deletingId.value}`, {
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

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterPanel>
                <AdminFilterGrid>
                    <AdminSearchInput id="announcement-search-filter" v-model="filters.search" placeholder="Cari tajuk atau ringkasan" />
                    <AdminSelectFilter id="announcement-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                    <AdminSelectFilter id="announcement-audience-filter" v-model="filters.audience" label="Audiens" :options="audienceOptions" />
                    <AdminFilterActions>
                        <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                        <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                    </AdminFilterActions>
                </AdminFilterGrid>
            </AdminFilterPanel>

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

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-published_at_human="{ row }">
                    <span class="text-sm text-slate-600">{{ row.published_at_human || '-' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="row.public_url" variant="outline">
                            <Eye class="mr-2 h-4 w-4" />
                            Lihat
                        </Button>
                        <Button v-if="canEdit" :as="Link" :href="`/admin/announcements/${row.id}/edit`" variant="outline">Edit</Button>
                        <Button v-if="canPublish && row.status !== 'published'" type="button" variant="outline" @click="runAction(row.id, 'publish')">Terbitkan</Button>
                        <Button v-if="canPublish && row.status === 'published'" type="button" variant="outline" @click="runAction(row.id, 'unpublish')">Nyahterbit</Button>
                        <Button v-if="canPublish && row.status !== 'archived'" type="button" variant="outline" @click="runAction(row.id, 'archive')">Arkib</Button>
                        <Button v-if="canEdit && !row.is_pinned" type="button" variant="outline" @click="runAction(row.id, 'pin')">
                            <Pin class="mr-2 h-4 w-4" />
                            Pin
                        </Button>
                        <Button v-if="canEdit && row.is_pinned" type="button" variant="outline" @click="runAction(row.id, 'unpin')">
                            <PinOff class="mr-2 h-4 w-4" />
                            Nyahpin
                        </Button>
                        <Button v-if="canDelete" type="button" variant="destructive" @click="askDelete(row.id)">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Padam
                        </Button>
                    </div>
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
