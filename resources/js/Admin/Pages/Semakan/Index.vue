<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Clock, Eye, FileText, Inbox } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    items: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
});

const localFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    category: props.filters.category || '',
});

const applyFilters = () => {
    router.get('/admin/semakan', localFilters, { preserveState: false, replace: false });
};

const resetFilters = () => {
    localFilters.search = '';
    localFilters.status = '';
    localFilters.category = '';
    applyFilters();
};

const columns = [
    { key: 'reference', label: 'No. Rujukan' },
    { key: 'applicant', label: 'Pemohon' },
    { key: 'category', label: 'Kategori' },
    { key: 'status', label: 'Status' },
    { key: 'submitted_at', label: 'Dihantar' },
    { key: 'actions', label: 'Tindakan' },
];

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.detail_url },
];
</script>

<template>
    <Head title="Semakan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Semakan Borang"
                description="Senarai permohonan borang yang menunggu tindakan — semak, kemas kini status, dan urus dokumen."
            />

            <AdminFilterBar>
                <AdminSearchInput id="semakan-search" v-model="localFilters.search" placeholder="Cari rujukan atau nama pemohon" />
                <AdminSelectFilter id="semakan-category" v-model="localFilters.category" label="Kategori Borang" :options="[{ value: '', label: 'Semua Kategori' }, ...categoryOptions]" />
                <AdminSelectFilter id="semakan-status" v-model="localFilters.status" label="Status" :options="statusOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="items.data.length === 0"
                :title="localFilters.search || localFilters.status || localFilters.category ? 'Tiada hasil untuk tapisan ini.' : 'Tiada permohonan borang menunggu.'"
                :description="localFilters.search || localFilters.status || localFilters.category ? 'Cuba ubah atau set semula tapisan anda.' : 'Semua permohonan borang telah selesai diproses. Permohonan baharu akan muncul di sini secara automatik.'"
            >
                <Inbox class="mx-auto h-12 w-12 text-slate-300" />
            </EmptyState>

            <DataTable v-else :columns="columns" :rows="items.data">
                <template #cell-reference="{ row }">
                    <p class="font-semibold text-slate-950">{{ row.reference }}</p>
                </template>

                <template #cell-applicant="{ row }">
                    <p class="font-medium text-slate-900">{{ row.applicant }}</p>
                    <p v-if="row.member_no" class="text-xs text-slate-500">{{ row.member_no }}</p>
                </template>

                <template #cell-category="{ row }">
                    <span v-if="row.category" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        {{ row.category }}
                    </span>
                    <span v-else class="text-xs text-slate-400">-</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-submitted_at="{ row }">
                    <div class="flex items-center gap-1.5 text-sm text-slate-600">
                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                        <span>{{ row.submitted_at }}</span>
                    </div>
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
                        @click="link.url ? router.get(link.url, {}, { preserveState: false, replace: false }) : null"
                    >
                        <span v-html="link.label" />
                    </Button>
                </nav>
            </div>
        </section>
    </AdminLayout>
</template>