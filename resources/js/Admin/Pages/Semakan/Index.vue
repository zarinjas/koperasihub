<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Eye, FileX2, Inbox } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    items: { type: Object, required: true },
    filters: { type: Object, required: true },
    typeOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Object, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const localFilters = reactive({
    type: props.filters.type || '',
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
});

const categoryLabel = computed(() => {
    const map = { keahlian: 'Jenis Keahlian', borang: 'Kategori Borang', pembiayaan: 'Kategori Pembiayaan' };
    return map[localFilters.type] || 'Kategori';
});

const activeCategoryOptions = computed(() => {
    if (!localFilters.type) return [];
    return [{ value: '', label: `Semua ${categoryLabel.value}` }, ...(props.categoryOptions[localFilters.type] || [])];
});

const showCategory = computed(() => localFilters.type !== '');

watch(() => localFilters.type, () => {
    localFilters.category = '';
});

const applyFilters = () => {
    router.get('/admin/semakan', localFilters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    localFilters.type = '';
    localFilters.search = '';
    localFilters.status = '';
    localFilters.category = '';
    applyFilters();
};

const typeBadge = (type) => {
    const map = {
        keahlian: 'border-purple-200 bg-purple-50 text-purple-700',
        borang: 'border-blue-200 bg-blue-50 text-blue-700',
        pembiayaan: 'border-teal-200 bg-teal-50 text-teal-700',
    };
    return map[type] || 'border-slate-200 bg-slate-100 text-slate-700';
};

const columns = [
    { key: 'type_label', label: 'Jenis' },
    { key: 'reference', label: 'Rujukan' },
    { key: 'applicant', label: 'Pemohon' },
    { key: 'status', label: 'Status' },
    { key: 'submitted_at', label: 'Dihantar' },
    { key: 'actions', label: 'Tindakan' },
];

const actionItem = ref(null);
const rejectDialogOpen = ref(false);
const rejectForm = useForm({ rejection_reason: '', review_notes: '' });
const approveProcessing = ref(false);

const openRejectDialog = (item) => {
    actionItem.value = item;
    rejectForm.rejection_reason = '';
    rejectForm.review_notes = '';
    rejectDialogOpen.value = true;
};

const confirmApprove = (item) => {
    approveProcessing.value = true;
    router.post(item.approve_url, {}, {
        preserveScroll: true,
        onFinish: () => { approveProcessing.value = false; },
    });
};

const confirmReject = () => {
    if (!actionItem.value) return;
    const item = actionItem.value;
    rejectForm.post(item.reject_url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpen.value = false;
            actionItem.value = null;
        },
    });
};

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.detail_url },
    { divider: true, condition: row.can_approve || row.can_reject },
    { label: 'Lulus', icon: CheckCircle2, condition: row.can_approve, disabled: approveProcessing, onClick: () => confirmApprove(row) },
    { label: 'Tolak', icon: FileX2, condition: row.can_reject, variant: 'destructive', onClick: () => openRejectDialog(row) },
];
</script>

<template>
    <Head title="Semakan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Semakan"
                description="Semua permohonan menunggu tindakan — keahlian, borang, dan pembiayaan dalam satu paparan."
            />

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterBar>
                <AdminSelectFilter id="semakan-type" v-model="localFilters.type" label="Jenis" :options="typeOptions" />
                <AdminSelectFilter v-if="showCategory" id="semakan-category" v-model="localFilters.category" :label="categoryLabel" :options="activeCategoryOptions" />
                <AdminSearchInput id="semakan-search" v-model="localFilters.search" placeholder="Cari rujukan atau nama pemohon" />
                <AdminSelectFilter id="semakan-status" v-model="localFilters.status" label="Status" :options="statusOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="items.data.length === 0"
                title="Tiada permohonan menunggu."
                description="Semua permohonan telah selesai diproses. Permohonan baharu akan muncul di sini secara automatik."
            >
                <Inbox class="mx-auto h-12 w-12 text-slate-300" />
            </EmptyState>

            <DataTable v-else :columns="columns" :rows="items.data">
                <template #cell-type_label="{ row }">
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap" :class="typeBadge(row.type)">
                        {{ row.type_label }}
                    </span>
                </template>

                <template #cell-reference="{ row }">
                    <p class="font-semibold text-slate-950">{{ row.reference }}</p>
                    <p v-if="row.identity_no" class="text-xs text-slate-500">{{ row.identity_no }}</p>
                </template>

                <template #cell-applicant="{ row }">
                    <p class="font-medium text-slate-900">{{ row.applicant }}</p>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-submitted_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.submitted_at }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="items.links" class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Menunjukkan {{ items.from || 0 }} - {{ items.to || 0 }} daripada {{ items.total || 0 }}</p>
                <nav class="flex gap-1">
                    <Button
                        v-for="link in items.links"
                        :key="link.label"
                        variant="outline"
                        :disabled="!link.url"
                        :class="link.active ? 'bg-teal-50 border-teal-200 text-teal-800' : ''"
                        @click="link.url ? router.get(link.url, {}, { preserveState: true, replace: true }) : null"
                    >
                        <span v-html="link.label" />
                    </Button>
                </nav>
            </div>
        </section>

        <ConfirmDialog
            :open="rejectDialogOpen"
            title="Tolak permohonan"
            description="Nyatakan sebab penolakan untuk rujukan pemohon dan rekod semakan."
            confirm-label="Sahkan Tolak"
            :loading="rejectForm.processing"
            variant="destructive"
            @cancel="rejectDialogOpen = false"
            @confirm="confirmReject"
        >
            <div class="space-y-4">
                <TextareaInput
                    id="rejection-reason"
                    v-model="rejectForm.rejection_reason"
                    label="Sebab penolakan"
                    :rows="3"
                    :error="rejectForm.errors.rejection_reason"
                />
                <TextareaInput
                    id="reject-review-notes"
                    v-model="rejectForm.review_notes"
                    label="Catatan admin (pilihan)"
                    :rows="2"
                    :error="rejectForm.errors.review_notes"
                />
            </div>
        </ConfirmDialog>
    </AdminLayout>
</template>
