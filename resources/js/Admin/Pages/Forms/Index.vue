<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Eye, FilePlus2, FileText, FolderPlus, Pencil, Send, Archive, Power, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminFilterActions from '@/Admin/Components/AdminFilterActions.vue';
import AdminFilterGrid from '@/Admin/Components/AdminFilterGrid.vue';
import AdminFilterPanel from '@/Admin/Components/AdminFilterPanel.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    forms: { type: Object, required: true },
    categoryOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    visibilityOptions: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
    canCreateCategory: { type: Boolean, default: false },
    canEditCategory: { type: Boolean, default: false },
    canDeleteCategory: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const activeTab = ref(props.filters.tab || 'borang');
const setTab = (tab) => {
    activeTab.value = tab;
    router.get('/admin/forms', { tab }, { preserveState: true, replace: true });
};

// --- Borang tab ---
const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    visibility: props.filters.visibility || '',
    category: props.filters.category || '',
});

const formColumns = [
    { key: 'title', label: 'Borang' },
    { key: 'category_name', label: 'Kategori' },
    { key: 'visibility', label: 'Akses' },
    { key: 'status', label: 'Status' },
    { key: 'submissions_count', label: 'Hantaran' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => router.get('/admin/forms', { ...filters, tab: 'borang' }, { preserveState: true, replace: true });
const changeStatus = (id, action) => router.post(`/admin/forms/${id}/${action}`, {}, { preserveScroll: true });
const moveForm = (id, action) => router.post(`/admin/forms/${id}/${action}`, {}, { preserveScroll: true });

// --- Kategori tab ---
const categorySearchText = ref('');
const deleteCategoryTarget = ref(null);

const filteredCategories = computed(() =>
    categorySearchText.value
        ? props.categories.filter((c) => c.name.toLowerCase().includes(categorySearchText.value.toLowerCase()))
        : props.categories
);

const categoryColumns = [
    { key: 'name', label: 'Kategori' },
    { key: 'published_forms_count', label: 'Borang diterbitkan' },
    { key: 'sort_order', label: 'Susunan' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Tindakan' },
];

const moveCategory = (id, direction) => router.post(`/admin/form-categories/${id}/${direction}`, {}, { preserveScroll: true });
const toggleCategory = (id) => router.post(`/admin/form-categories/${id}/toggle`, {}, { preserveScroll: true });
const deleteCategory = () => {
    if (!deleteCategoryTarget.value) return;
    router.delete(`/admin/form-categories/${deleteCategoryTarget.value}`, {
        preserveScroll: true,
        onFinish: () => { deleteCategoryTarget.value = null; },
    });
};

const tabs = [
    { key: 'borang', label: 'Borang' },
    { key: 'kategori', label: 'Kategori' },
    { key: 'hantaran', label: 'Hantaran' },
];
</script>

<template>
    <Head title="Borang Online" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Borang Online"
                description="Bina dan urus borang penerimaan, permohonan, dan maklum balas untuk anggota koperasi."
            >
                <template #actions>
                    <Button v-if="activeTab === 'borang'" :as="Link" href="/admin/forms/create">
                        <FilePlus2 class="mr-2 h-4 w-4" />
                        Borang Baharu
                    </Button>
                    <Button v-if="activeTab === 'kategori'" :as="Link" href="/admin/form-categories/create">
                        <FolderPlus class="mr-2 h-4 w-4" />
                        Kategori Baharu
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <!-- Tabs -->
            <div class="border-b border-slate-200">
                <nav class="-mb-px flex gap-6">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === tab.key
                            ? 'border-teal-700 text-teal-800'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        @click="setTab(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab: Borang -->
            <template v-if="activeTab === 'borang'">
                <AdminFilterPanel>
                    <AdminFilterGrid>
                        <AdminSearchInput id="form-search-filter" v-model="filters.search" placeholder="Cari tajuk atau kod borang..." />
                        <AdminSelectFilter id="form-filter-category" v-model="filters.category" label="Kategori" :options="categoryOptions" />
                        <AdminSelectFilter id="form-filter-visibility" v-model="filters.visibility" label="Akses" :options="visibilityOptions" />
                        <AdminSelectFilter id="form-filter-status" v-model="filters.status" label="Status" :options="statusOptions" />
                        <AdminFilterActions>
                            <Button type="button" variant="outline" class="h-11" @click="filters.search='';filters.category='';filters.visibility='';filters.status='';applyFilters()">Set Semula</Button>
                            <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                        </AdminFilterActions>
                    </AdminFilterGrid>
                </AdminFilterPanel>

                <EmptyState
                    v-if="forms.data.length === 0"
                    title="Tiada borang ditemui."
                    description="Cipta borang pertama dan susun bahagian serta soalan mengikut keperluan koperasi."
                    action-label="Borang Baharu"
                    action-href="/admin/forms/create"
                />

                <DataTable v-else :columns="formColumns" :rows="forms.data">
                    <template #cell-title="{ row }">
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-950">{{ row.title }}</p>
                            <p class="text-xs text-slate-500">{{ row.document_code || 'Tiada kod borang' }}</p>
                        </div>
                    </template>
                    <template #cell-visibility="{ row }">
                        <StatusBadge :status="row.visibility" :label="row.visibility === 'public' ? 'Semua orang' : 'Ahli sahaja'" />
                    </template>
                    <template #cell-status="{ row }">
                        <StatusBadge :status="row.status" :label="{ draft: 'Draf', published: 'Diterbitkan', archived: 'Arkib' }[row.status] || row.status" />
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" @click="moveForm(row.id, 'move-up')">
                                <ArrowUp class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="moveForm(row.id, 'move-down')">
                                <ArrowDown class="h-4 w-4" />
                            </Button>
                            <Button :as="Link" :href="`/admin/forms/${row.id}/edit`" variant="outline" size="sm">
                                <Pencil class="mr-1.5 h-4 w-4" />
                                Edit
                            </Button>
                            <Button :as="Link" :href="row.preview_pdf_url" variant="outline" size="sm">
                                <FileText class="mr-1.5 h-4 w-4" />
                                Pratonton Cetakan
                            </Button>
                            <Button :as="Link" :href="row.submissions_url" variant="outline" size="sm">
                                <Eye class="mr-1.5 h-4 w-4" />
                                Hantaran
                            </Button>
                            <Button v-if="row.status !== 'published'" type="button" variant="outline" size="sm" @click="changeStatus(row.id, 'publish')">
                                <Send class="mr-1.5 h-4 w-4" />
                                Terbitkan
                            </Button>
                            <Button v-if="row.status === 'published'" type="button" variant="outline" size="sm" @click="changeStatus(row.id, 'unpublish')">
                                Nyahterbit
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="changeStatus(row.id, 'archive')">
                                <Archive class="mr-1.5 h-4 w-4" />
                                Arkib
                            </Button>
                        </div>
                    </template>
                </DataTable>

                <div v-if="forms.links?.length > 3" class="flex flex-wrap gap-2">
                    <Button
                        v-for="link in forms.links"
                        :key="`${link.label}-${link.url}`"
                        :as="link.url ? Link : 'button'"
                        :href="link.url || undefined"
                        :variant="link.active ? 'default' : 'outline'"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </div>
            </template>

            <!-- Tab: Kategori -->
            <template v-if="activeTab === 'kategori'">
                <AdminFilterPanel>
                    <AdminFilterGrid columns="xl:grid-cols-3">
                        <AdminSearchInput id="form-inline-category-search-filter" v-model="categorySearchText" placeholder="Cari kategori..." />
                    </AdminFilterGrid>
                </AdminFilterPanel>

                <EmptyState
                    v-if="filteredCategories.length === 0"
                    title="Tiada kategori borang."
                    description="Cipta kategori untuk menyusun borang mengikut unit atau perkhidmatan koperasi."
                    action-label="Kategori Baharu"
                    action-href="/admin/form-categories/create"
                />

                <DataTable v-else :columns="categoryColumns" :rows="filteredCategories">
                    <template #cell-name="{ row }">
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-950">{{ row.name }}</p>
                            <p class="text-xs text-slate-500">{{ row.description || 'Tiada penerangan.' }}</p>
                        </div>
                    </template>
                    <template #cell-is_active="{ row }">
                        <StatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Aktif' : 'Tidak aktif'" />
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" @click="moveCategory(row.id, 'move-up')">
                                <ArrowUp class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="moveCategory(row.id, 'move-down')">
                                <ArrowDown class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="toggleCategory(row.id)">
                                <Power class="mr-1.5 h-4 w-4" />
                                {{ row.is_active ? 'Nyahaktif' : 'Aktifkan' }}
                            </Button>
                            <Button :as="Link" :href="`/admin/form-categories/${row.id}/edit`" variant="outline" size="sm">
                                <Pencil class="mr-1.5 h-4 w-4" />
                                Edit
                            </Button>
                            <Button type="button" variant="destructive" size="sm" @click="deleteCategoryTarget = row.id">
                                <Trash2 class="mr-1.5 h-4 w-4" />
                                Padam
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </template>

            <!-- Tab: Hantaran -->
            <template v-if="activeTab === 'hantaran'">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="mb-4 text-sm text-slate-600">
                        Untuk melihat hantaran bagi sesuatu borang, pilih borang daripada tab <strong>Borang</strong> kemudian klik butang <strong>Hantaran</strong>.
                    </p>

                    <EmptyState
                        v-if="forms.data.length === 0"
                        title="Tiada borang diterbitkan."
                        description="Terbitkan borang terlebih dahulu sebelum hantaran boleh diterima."
                        compact
                    />

                    <div v-else class="space-y-3">
                        <div
                            v-for="row in forms.data"
                            :key="row.id"
                            class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="space-y-1">
                                <p class="font-semibold text-slate-950">{{ row.title }}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <StatusBadge :status="row.status" :label="{ draft: 'Draf', published: 'Diterbitkan', archived: 'Arkib' }[row.status] || row.status" />
                                    <span class="text-xs text-slate-500">{{ row.submissions_count }} hantaran</span>
                                </div>
                            </div>
                            <Button :as="Link" :href="row.submissions_url" variant="outline" size="sm">
                                <Eye class="mr-1.5 h-4 w-4" />
                                Lihat Hantaran
                            </Button>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteCategoryTarget)"
            title="Padam kategori borang"
            description="Kategori tanpa borang akan dipadam. Jika kategori masih digunakan, sistem akan menyahaktifkannya sahaja."
            confirm-label="Teruskan"
            @cancel="deleteCategoryTarget = null"
            @confirm="deleteCategory"
        />
    </AdminLayout>
</template>
