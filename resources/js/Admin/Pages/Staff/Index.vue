<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, UserRoundCog } from 'lucide-vue-next';
import { reactive } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    staff: { type: Object, required: true },
    unitOptions: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    role: props.filters.role || '',
    unit_id: props.filters.unit_id || '',
});

const roleOptions = [
    { value: '', label: 'Semua Peranan' },
    { value: 'super_admin', label: 'Super Admin' },
    { value: 'admin', label: 'Admin' },
];

const applyFilters = () => router.get('/admin/staff', filters, { preserveState: true, replace: true });

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'staff_id', label: 'ID Staff' },
    { key: 'role', label: 'Peranan' },
    { key: 'unit', label: 'Unit' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const roleLabel = (role) => role === 'super_admin' ? 'Super Admin' : role === 'admin' ? 'Admin' : role;

const getActions = (row) => [
    { label: 'Edit', icon: Pencil, href: `/admin/staff/${row.id}/edit` },
];
</script>

<template>
    <Head title="Staff & Akses" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader title="Staff & Akses" description="Urus akaun staf pentadbir dan akses sistem.">
                <template #actions>
                    <Button :as="Link" href="/admin/staff/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Staff
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap gap-4">
                <div class="max-w-sm flex-1">
                    <SearchInput v-model="filters.search" placeholder="Cari nama, emel, atau ID staff" @update:model-value="applyFilters" />
                </div>
                <SelectInput id="staff-role-filter" v-model="filters.role" label="Peranan" :options="roleOptions" @update:model-value="applyFilters" />
                <SelectInput id="staff-unit-filter" v-model="filters.unit_id" label="Unit" :options="[{ value: '', label: 'Semua Unit' }, ...unitOptions]" @update:model-value="applyFilters" />
            </div>

            <EmptyState v-if="staff.data.length === 0" title="Tiada akaun staff." description="Akaun staff yang ditambah akan dipaparkan di sini." compact />

            <DataTable v-else :columns="columns" :rows="staff.data">
                <template #cell-name="{ row }">
                    <div>
                        <p class="font-medium text-slate-900">{{ row.name }}</p>
                        <p class="text-xs text-slate-500">{{ row.email }}</p>
                    </div>
                </template>
                <template #cell-staff_id="{ row }">
                    <span class="font-mono text-sm text-slate-600">{{ row.staff_id || '-' }}</span>
                </template>
                <template #cell-role="{ row }">
                    <span class="text-sm text-slate-700">{{ roleLabel(row.role) }}</span>
                </template>
                <template #cell-unit="{ row }">
                    <span class="text-sm text-slate-600">{{ row.unit_name || '-' }}</span>
                    <p v-if="row.position_title" class="text-xs text-slate-400">{{ row.position_title }}</p>
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status === 'active' ? 'active' : 'inactive'" />
                </template>
                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>
        </section>
    </AdminLayout>
</template>