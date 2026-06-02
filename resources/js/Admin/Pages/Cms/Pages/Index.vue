<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { FilePlus2, FolderKanban, Layers3, PencilLine } from 'lucide-vue-next';
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
    pages: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canPublish: { type: Boolean, default: false },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const columns = [
    { key: 'title', label: 'Halaman' },
    { key: 'status', label: 'Status' },
    { key: 'sections_count', label: 'Seksyen' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/cms/pages', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    applyFilters();
};

const getActions = (row) => [
    { label: 'Edit', icon: PencilLine, condition: props.canEdit, href: `/admin/cms/pages/${row.id}/edit` },
    { label: 'Seksyen', icon: FolderKanban, href: `/admin/cms/pages/${row.id}/sections` },
];
</script>

<template>
    <Head title="Halaman CMS" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Halaman CMS"
                description="Urus halaman awam, metadata SEO, status penerbitan dan akses kepada editor seksyen."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/cms/pages/create">
                        <FilePlus2 class="mr-2 h-4 w-4" />
                        Cipta Halaman
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <AdminSearchInput id="cms-page-search-filter" v-model="filters.search" placeholder="Cari tajuk, slug atau tajuk SEO" />
                <AdminSelectFilter id="status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="pages.data.length === 0"
                title="Tiada halaman ditemui."
                description="Cipta halaman baharu untuk mula mengurus kandungan CMS koperasi."
                :action-label="canCreate ? 'Cipta Halaman' : null"
                :action-href="canCreate ? '/admin/cms/pages/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="pages.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-950">{{ row.title }}</p>
                            <span v-if="row.template === 'homepage'" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                Homepage
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">/{{ row.slug }}</p>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-sections_count="{ row }">
                    <div class="inline-flex items-center gap-2 text-sm">
                        <Layers3 class="h-4 w-4 text-slate-400" />
                        {{ row.sections_count }}
                    </div>
                </template>

                <template #cell-updated_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.updated_at }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="pages.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in pages.links"
                    :key="link.label"
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