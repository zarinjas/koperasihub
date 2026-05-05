<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, FolderPlus, Pencil, Power, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminFilterActions from '@/Admin/Components/AdminFilterActions.vue';
import AdminFilterGrid from '@/Admin/Components/AdminFilterGrid.vue';
import AdminFilterPanel from '@/Admin/Components/AdminFilterPanel.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const filters = reactive({ search: props.filters.search || '' });

const columns = [
    { key: 'name', label: 'Kategori' },
    { key: 'published_forms_count', label: 'Borang diterbitkan' },
    { key: 'sort_order', label: 'Susunan' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const deleteTarget = ref(null);

const applyFilters = () => {
    router.get('/admin/form-categories', filters, { preserveState: true, replace: true });
};

const move = (id, direction) => router.post(`/admin/form-categories/${id}/${direction}`, {}, { preserveScroll: true });
const toggle = (id) => router.post(`/admin/form-categories/${id}/toggle`, {}, { preserveScroll: true });
const destroy = () => {
    if (!deleteTarget.value) return;
    router.delete(`/admin/form-categories/${deleteTarget.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Kategori Borang" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Kategori Borang"
                description="Kumpulkan borang mengikut unit atau perkhidmatan supaya pengurusan kandungan lebih tersusun."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/form-categories/create">
                        <FolderPlus class="mr-2 h-4 w-4" />
                        Cipta Kategori
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterPanel>
                <AdminFilterGrid columns="xl:grid-cols-3">
                    <AdminSearchInput id="form-category-search-filter" v-model="filters.search" placeholder="Cari kategori borang" />
                    <AdminFilterActions>
                        <Button type="button" variant="outline" class="h-11" @click="filters.search = ''; applyFilters()">Set Semula</Button>
                        <Button type="button" class="h-11" @click="applyFilters">Cari</Button>
                    </AdminFilterActions>
                </AdminFilterGrid>
            </AdminFilterPanel>

            <EmptyState
                v-if="categories.length === 0"
                title="Tiada kategori borang ditemui."
                description="Cipta kategori pertama untuk mula menyusun borang online mengikut unit koperasi."
                :action-label="canCreate ? 'Cipta Kategori' : null"
                :action-href="canCreate ? '/admin/form-categories/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="categories">
                <template #cell-name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.name }}</p>
                        <p class="text-xs text-slate-500">{{ row.description || 'Tiada penerangan.' }}</p>
                    </div>
                </template>
                <template #cell-is_active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button v-if="canEdit" type="button" variant="outline" @click="move(row.id, 'move-up')">
                            <ArrowUp class="mr-2 h-4 w-4" />
                            Naik
                        </Button>
                        <Button v-if="canEdit" type="button" variant="outline" @click="move(row.id, 'move-down')">
                            <ArrowDown class="mr-2 h-4 w-4" />
                            Turun
                        </Button>
                        <Button v-if="canEdit" type="button" variant="outline" @click="toggle(row.id)">
                            <Power class="mr-2 h-4 w-4" />
                            {{ row.is_active ? 'Nyahaktif' : 'Aktifkan' }}
                        </Button>
                        <Button v-if="canEdit" :as="Link" :href="`/admin/form-categories/${row.id}/edit`" variant="outline">
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                        <Button v-if="canDelete" type="button" variant="destructive" @click="deleteTarget = row.id">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Padam
                        </Button>
                    </div>
                </template>
            </DataTable>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteTarget)"
            title="Padam kategori borang"
            description="Kategori tanpa borang akan dipadam. Jika kategori masih digunakan, sistem akan menyahaktifkannya sahaja."
            confirm-label="Teruskan"
            @cancel="deleteTarget = null"
            @confirm="destroy"
        />
    </AdminLayout>
</template>
