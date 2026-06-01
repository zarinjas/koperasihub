<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Eye, Pencil, Plus, Trash2, XCircle, CheckCircle2, Ban } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
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
    programs: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    programTypeOptions: { type: Array, required: true },
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
    category: props.filters.category || '',
    program_type: props.filters.program_type || '',
});

const columns = [
    { key: 'title', label: 'Program' },
    { key: 'program_type', label: 'Jenis' },
    { key: 'category', label: 'Kategori' },
    { key: 'start_date_human', label: 'Tarikh Mula' },
    { key: 'stats', label: 'RSVP / Hadir' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/programs', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.category = '';
    filters.program_type = '';
    applyFilters();
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;
    router.delete(`/admin/programs/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const runAction = (id, action) => {
    router.post(`/admin/programs/${id}/${action}`, {}, { preserveScroll: true });
};

const programTypeLabel = (value) => ({
    physical: 'Fizikal',
    online: 'Atas Talian',
    hybrid: 'Hibrid',
})[value] || value;

const categoryLabel = (value) => {
    const labels = {
        agm: 'AGM',
        seminar: 'Seminar',
        kursus: 'Kursus',
        webinar: 'Webinar',
        community: 'Komuniti',
        volunteer: 'Sukarelawan',
        social: 'Sosial',
        other: 'Lain-lain',
    };
    return labels[value] || value;
};

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: `/admin/programs/${row.id}` },
    { label: 'Edit', icon: Pencil, condition: props.canEdit, href: `/admin/programs/${row.id}/edit` },
    { label: 'Kehadiran', icon: CalendarDays, href: `/admin/programs/${row.id}/attendance` },
    { divider: true, condition: props.canPublish },
    { label: 'Terbitkan', icon: CheckCircle2, condition: props.canPublish && row.status === 'draft', onClick: () => runAction(row.id, 'publish') },
    { label: 'Tanda Selesai', icon: CheckCircle2, condition: props.canPublish && row.status === 'published', onClick: () => runAction(row.id, 'complete') },
    { label: 'Batalkan', icon: XCircle, variant: 'warning', condition: props.canPublish && row.status !== 'cancelled', onClick: () => runAction(row.id, 'cancel') },
    { divider: true, condition: props.canDelete },
    { label: 'Padam', icon: Trash2, variant: 'destructive', condition: props.canDelete, onClick: () => askDelete(row.id) },
];

</script>

<template>
    <Head title="Program & Kehadiran" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Program & Kehadiran"
                description="Urus program, acara, dan kehadiran ahli koperasi."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/programs/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Program
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterBar>
                <AdminSearchInput id="program-search-filter" v-model="filters.search" placeholder="Cari tajuk atau lokasi" />
                <AdminSelectFilter id="program-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <AdminSelectFilter id="program-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                <AdminSelectFilter id="program-type-filter" v-model="filters.program_type" label="Jenis" :options="programTypeOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="programs.data.length === 0"
                title="Tiada program ditemui."
                description="Cipta program baharu untuk mula mengurus acara dan kehadiran ahli."
                :action-label="canCreate ? 'Tambah Program' : null"
                :action-href="canCreate ? '/admin/programs/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="programs.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.title }}</p>
                        <p v-if="row.location" class="text-xs text-slate-500">{{ row.location }}</p>
                    </div>
                </template>

                <template #cell-program_type="{ row }">
                    <StatusBadge :status="row.program_type" :label="programTypeLabel(row.program_type)" />
                </template>

                <template #cell-category="{ row }">
                    <span v-if="row.category" class="text-sm text-slate-600">{{ categoryLabel(row.category) }}</span>
                    <span v-else class="text-sm text-slate-400">-</span>
                </template>

                <template #cell-start_date_human="{ row }">
                    <span class="text-sm text-slate-600">{{ row.start_date_human || '-' }}</span>
                </template>

                <template #cell-stats="{ row }">
                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-slate-600">{{ row.rsvps_hadir_count ?? 0 }} RSVP</span>
                        <span class="font-medium text-teal-700">{{ row.rsvps_checked_in_count ?? 0 }} Hadir</span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="programs.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in programs.links"
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
            title="Padam program"
            description="Program ini akan dibuang daripada sistem. Semua data RSVP dan kehadiran akan hilang."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>
