<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
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
    complaints: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    priorityOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    canReply: { type: Boolean, default: false },
    canClose: { type: Boolean, default: false },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    category: props.filters.category || '',
});

const columns = [
    { key: 'ticket_no', label: 'No. tiket' },
    { key: 'member_name', label: 'Ahli' },
    { key: 'subject', label: 'Butiran' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Keutamaan' },
    { key: 'actions', label: 'Tindakan' },
];

const actionsLabel = computed(() => (props.canReply || props.canClose ? 'Lihat' : 'Semak'));

const applyFilters = () => {
    router.get('/admin/complaints', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.priority = '';
    filters.category = '';
    applyFilters();
};
</script>

<template>
    <Head title="Aduan dan Cadangan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Aduan dan Cadangan"
                description="Pantau isu ahli, susun keutamaan tindakan, dan semak maklum balas yang telah dihantar."
            />

            <FilterBar>
                <SearchInput v-model="filters.search" placeholder="Cari no. tiket, ahli, tajuk, atau mesej" />
                <SelectInput id="complaint-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <SelectInput id="complaint-priority-filter" v-model="filters.priority" label="Keutamaan" :options="priorityOptions" />
                <SelectInput id="complaint-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />

                <template #actions>
                    <Button type="button" variant="outline" @click="resetFilters">Set Semula</Button>
                    <Button type="button" @click="applyFilters">Tapis</Button>
                </template>
            </FilterBar>

            <EmptyState
                v-if="complaints.data.length === 0"
                title="Tiada rekod aduan ditemui."
                description="Rekod baharu akan dipaparkan di sini apabila ahli mula menghantar aduan atau cadangan."
            />

            <DataTable v-else :columns="columns" :rows="complaints.data">
                <template #cell-ticket_no="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.ticket_no }}</p>
                        <p class="text-xs text-slate-500">{{ row.updated_at }}</p>
                    </div>
                </template>

                <template #cell-member_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.member_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.assigned_to_name || 'Belum ditugaskan' }}</p>
                    </div>
                </template>

                <template #cell-subject="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.subject }}</p>
                        <p class="text-xs text-slate-500">{{ row.category_label }}</p>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-priority="{ row }">
                    <StatusBadge :status="row.priority" />
                </template>

                <template #cell-actions="{ row }">
                    <Button :as="Link" :href="row.show_url" variant="outline">{{ actionsLabel }}</Button>
                </template>
            </DataTable>

            <div v-if="complaints.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in complaints.links"
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
