<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Plus, Trash2 } from 'lucide-vue-next';
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
    services: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
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
});

const columns = [
    { key: 'title', label: 'Perkhidmatan' },
    { key: 'category', label: 'Kategori' },
    { key: 'status', label: 'Status' },
    { key: 'sort_order', label: 'Susunan' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/services', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.category = '';
    applyFilters();
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;

    router.delete(`/admin/services/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const runAction = (id, action) => {
    router.post(`/admin/services/${id}/${action}`, {}, { preserveScroll: true });
};

const categoryLabel = (value) => value ? value.replaceAll('_', ' ') : 'Tanpa kategori';
</script>

<template>
    <Head title="Perkhidmatan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Perkhidmatan"
                description="Urus perkhidmatan dan tawaran koperasi yang dipaparkan pada laman awam."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/services/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Perkhidmatan
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterPanel>
                <AdminFilterGrid>
                    <AdminSearchInput id="service-search-filter" v-model="filters.search" placeholder="Cari tajuk atau ringkasan" />
                    <AdminSelectFilter id="service-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                    <AdminSelectFilter id="service-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                    <AdminFilterActions>
                        <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                        <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                    </AdminFilterActions>
                </AdminFilterGrid>
            </AdminFilterPanel>

            <EmptyState
                v-if="services.data.length === 0"
                title="Tiada perkhidmatan ditemui."
                description="Cipta perkhidmatan pertama untuk dipaparkan pada homepage dan halaman awam."
                :action-label="canCreate ? 'Tambah Perkhidmatan' : null"
                :action-href="canCreate ? '/admin/services/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="services.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-950">{{ row.title }}</p>
                            <span v-if="row.is_featured" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Sorotan</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ row.summary || 'Tiada ringkasan disediakan.' }}</p>
                    </div>
                </template>

                <template #cell-category="{ row }">
                    <span class="text-sm text-slate-600">{{ categoryLabel(row.category) }}</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-sort_order="{ row }">
                    <span class="text-sm text-slate-600">{{ row.sort_order }}</span>
                </template>

                <template #cell-updated_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.updated_at }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="row.public_url" variant="outline">
                            <Eye class="mr-2 h-4 w-4" />
                            Lihat
                        </Button>
                        <Button v-if="canEdit" :as="Link" :href="`/admin/services/${row.id}/edit`" variant="outline">Edit</Button>
                        <Button v-if="canPublish && row.status !== 'published'" type="button" variant="outline" @click="runAction(row.id, 'publish')">Terbitkan</Button>
                        <Button v-if="canPublish && row.status === 'published'" type="button" variant="outline" @click="runAction(row.id, 'unpublish')">Nyahterbit</Button>
                        <Button v-if="canPublish && row.status !== 'archived'" type="button" variant="outline" @click="runAction(row.id, 'archive')">Arkib</Button>
                        <Button v-if="canDelete" type="button" variant="destructive" @click="askDelete(row.id)">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Padam
                        </Button>
                    </div>
                </template>
            </DataTable>

            <div v-if="services.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in services.links"
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
            title="Padam perkhidmatan"
            description="Perkhidmatan ini akan dibuang daripada senarai admin dan halaman awam."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>
