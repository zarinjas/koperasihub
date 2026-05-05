<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
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

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
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

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="flex max-w-sm">
                <SearchInput v-model="filters.search" placeholder="Cari unit" @update:model-value="applyFilters" />
            </div>

            <EmptyState v-if="units.data.length === 0" title="Tiada unit." description="Unit yang ditambah akan dipaparkan di sini." compact />

            <DataTable v-else :columns="columns" :rows="units.data">
                <template #cell-active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex gap-2">
                        <Button :as="Link" :href="`/admin/units/${row.id}/edit`" variant="outline">
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                        <Button variant="destructive" @click="deleteTarget = row.id">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Padam
                        </Button>
                    </div>
                </template>
            </DataTable>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteTarget)"
            title="Padam unit"
            description="Unit ini akan dipadam secara kekal. Tindakan ini tidak boleh dikembalikan."
            confirm-label="Padam"
            @cancel="deleteTarget = null"
            @confirm="router.delete(`/admin/units/${deleteTarget}`, { preserveScroll: true, onFinish: () => { deleteTarget = null; } })"
        />
    </AdminLayout>
</template>
