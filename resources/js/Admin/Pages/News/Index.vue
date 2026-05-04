<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FilterBar from '@/Shared/Components/FilterBar.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    news: { type: Object, required: true },
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
    { key: 'title', label: 'Berita' },
    { key: 'category', label: 'Kategori' },
    { key: 'status', label: 'Status' },
    { key: 'published_at_human', label: 'Tarikh terbit' },
    { key: 'actions', label: 'Tindakan' },
];

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/news', filters, { preserveState: true, replace: true });
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

    router.delete(`/admin/news/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};

const runAction = (id, action) => {
    router.post(`/admin/news/${id}/${action}`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Berita" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Berita"
                description="Urus artikel dan berita koperasi untuk paparan awam."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/news/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Berita
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FilterBar>
                <SearchInput v-model="filters.search" placeholder="Cari tajuk atau petikan" />
                <SelectInput id="news-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                <SelectInput id="news-category-filter" v-model="filters.category" label="Kategori" :options="categoryOptions" />

                <template #actions>
                    <Button type="button" variant="outline" @click="resetFilters">Set Semula</Button>
                    <Button type="button" @click="applyFilters">Tapis</Button>
                </template>
            </FilterBar>

            <EmptyState
                v-if="news.data.length === 0"
                title="Tiada berita ditemui."
                description="Cipta artikel berita baharu untuk dipaparkan pada laman awam."
                :action-label="canCreate ? 'Tambah Berita' : null"
                :action-href="canCreate ? '/admin/news/create' : null"
            />

            <DataTable v-else :columns="columns" :rows="news.data">
                <template #cell-title="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.title }}</p>
                        <p class="text-xs text-slate-500">{{ row.excerpt || row.content_preview || 'Tiada petikan disediakan.' }}</p>
                    </div>
                </template>

                <template #cell-category="{ row }">
                    <span v-if="row.category" class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                        {{ categoryOptions.find(c => c.value === row.category)?.label || row.category }}
                    </span>
                    <span v-else class="text-sm text-slate-400">-</span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-published_at_human="{ row }">
                    <span class="text-sm text-slate-600">{{ row.published_at_human || '-' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" :href="row.public_url" variant="outline" target="_blank">
                            <Eye class="mr-2 h-4 w-4" />
                            Lihat
                        </Button>
                        <Button v-if="canEdit" :as="Link" :href="`/admin/news/${row.id}/edit`" variant="outline">Edit</Button>
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

            <div v-if="news.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in news.links"
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
            title="Padam berita"
            description="Artikel ini akan dibuang daripada sistem dan tidak lagi dipaparkan kepada pelawat."
            confirm-label="Padam"
            @cancel="dialogOpen = false"
            @confirm="deleteRecord"
        />
    </AdminLayout>
</template>
