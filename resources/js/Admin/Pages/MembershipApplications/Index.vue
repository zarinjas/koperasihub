<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { reactive } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    applications: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const columns = [
    { key: 'application_no', label: 'No. Permohonan' },
    { key: 'full_name', label: 'Pemohon' },
    { key: 'status', label: 'Status' },
    { key: 'submitted_at', label: 'Dihantar' },
    { key: 'reviewer_name', label: 'Disemak oleh' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/membership-applications', filters, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    applyFilters();
};

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.show_url },
];
</script>

<template>
    <Head title="Permohonan Keahlian" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan Keahlian"
                description="Semak, tapis, dan urus permohonan keahlian yang dihantar melalui laman awam."
            />

            <AdminFilterBar>
                <AdminSearchInput id="membership-application-search-filter" v-model="filters.search" placeholder="Cari nombor permohonan atau nama pemohon" />
                <AdminSelectFilter id="membership-application-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="applications.data.length === 0"
                title="Tiada permohonan ditemui."
                description="Permohonan baharu akan dipaparkan di sini selepas dihantar melalui halaman awam."
            />

            <DataTable v-else :columns="columns" :rows="applications.data">
                <template #cell-application_no="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.application_no }}</p>
                        <p class="text-xs text-slate-500">{{ row.identity_no || '-' }}</p>
                    </div>
                </template>

                <template #cell-full_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.full_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.email || '-' }} · {{ row.phone || '-' }}</p>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-submitted_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.submitted_at || '-' }}</span>
                </template>

                <template #cell-reviewer_name="{ row }">
                    <span class="text-sm text-slate-600">{{ row.reviewer_name || '-' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
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
    </AdminLayout>
</template>