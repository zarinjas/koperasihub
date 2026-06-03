<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    units: { type: Object, required: true },
});

const deleteTarget = ref(null);

const filters = reactive({ search: props.filters.search || '' });
const applyFilters = () => router.get('/admin/units', filters, { preserveState: true, replace: true });

const columns = [
    { key: 'name', label: 'Nama Unit' },
    { key: 'slug', label: 'Slug' },
    { key: 'users_count', label: 'Staff' },
    { key: 'active', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const getActions = (row) => [
    { label: 'Edit', icon: Pencil, href: `/admin/units/${row.id}/edit` },
    { label: 'Padam', icon: Trash2, variant: 'destructive', onClick: () => { deleteTarget.value = row.id; } },
];
</script>

<template>
    <Head title="Unit" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader title="Unit" description="Urus unit atau bahagian dalam koperasi.">
                <template #actions>
                    <Button :as="Link" href="/admin/units/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Unit
                    </Button>
                </template>
            </PageHeader>

            <div class="flex max-w-sm">
                <SearchInput v-model="filters.search" placeholder="Cari unit" @update:model-value="applyFilters" />
            </div>

            <EmptyState v-if="units.data.length === 0" title="Tiada unit." description="Unit yang ditambah akan dipaparkan di sini." compact />

            <DataTable v-else :columns="columns" :rows="units.data">
                <template #cell-active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
                </template>
                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteTarget)"
            title="Padam unit"
            description="Unit ini akan dipadam secara kekal. Tindakan ini tidak boleh dikembalikan."
            confirm-label="Padam"
            @cancel="deleteTarget = null"
            @confirm="router.post(`/admin/units/${deleteTarget}`, { _method: 'DELETE' }, { preserveScroll: true, onFinish: () => { deleteTarget = null; } })"
        />
    </AdminLayout>
</template>