<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, ImagePlay, PenLine, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    banners: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canPublish: { type: Boolean, default: false },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const deleting = ref(null);
const bannersIndexUrl = '/admin/banners';
const bannersCreateUrl = '/admin/banners/create';

function bannerEditUrl(bannerId) {
    return `/admin/banners/${bannerId}/edit`;
}

watch(search, (val) => {
    router.get(bannersIndexUrl, { search: val || null, status: status.value || null }, { preserveState: true, replace: true });
});

watch(status, (val) => {
    router.get(bannersIndexUrl, { search: search.value || null, status: val || null }, { preserveState: true, replace: true });
});

function confirmDelete(banner) {
    deleting.value = banner;
}

function executeDelete() {
    if (!deleting.value) return;
    router.delete(`/admin/banners/${deleting.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleting.value = null; },
    });
}

function handlePublish(banner) {
    router.post(`/admin/banners/${banner.id}/publish`, {}, { preserveScroll: true });
}

function handleUnpublish(banner) {
    router.post(`/admin/banners/${banner.id}/unpublish`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Banner Digital" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Banner Digital"
                description="Urus banner iklan digital yang akan dipaparkan di dashboard portal ahli."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" :href="bannersCreateUrl">
                        <Plus class="mr-2 h-4 w-4" />
                        Muat Naik Banner
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari banner..."
                        class="h-10 w-full sm:w-56 rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    />
                </div>
                <select
                    v-model="status"
                    class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </AdminFilterBar>

            <div v-if="banners.data.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada banner lagi."
                    description="Muat naik banner digital pertama anda untuk dipaparkan kepada ahli di portal."
                    compact
                >
                    <template #icon>
                        <ImagePlay class="h-12 w-12 text-slate-300" />
                    </template>
                    <template #actions>
                        <Button v-if="canCreate" :as="Link" :href="bannersCreateUrl">
                            <Plus class="mr-2 h-4 w-4" />
                            Muat Naik Banner
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="banner in banners.data"
                    :key="banner.id"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                >
                    <div class="relative aspect-[4/1] w-full overflow-hidden bg-slate-100">
                        <img
                            :src="banner.image_url"
                            :alt="banner.alt_text || banner.title"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>

                    <div class="flex flex-1 flex-col p-3">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="truncate text-sm font-semibold text-slate-950">{{ banner.title }}</h3>
                            <StatusBadge :status="banner.status" />
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                            <ExternalLink class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ banner.link_url }}</span>
                        </div>
                        <div class="mt-3 flex items-center gap-1">
                            <Button
                                v-if="canEdit"
                                :as="Link"
                                :href="bannerEditUrl(banner.id)"
                                variant="ghost"
                                size="sm"
                            >
                                <PenLine class="h-3.5 w-3.5" />
                            </Button>

                            <Button
                                v-if="canPublish && banner.status === 'draft'"
                                variant="ghost"
                                size="sm"
                                @click="handlePublish(banner)"
                            >
                                Terbitkan
                            </Button>
                            <Button
                                v-if="canPublish && banner.status === 'published'"
                                variant="ghost"
                                size="sm"
                                @click="handleUnpublish(banner)"
                            >
                                Draf
                            </Button>

                            <Button
                                v-if="canDelete"
                                variant="ghost"
                                size="sm"
                                class="ml-auto text-red-500 hover:bg-red-50 hover:text-red-600"
                                @click="confirmDelete(banner)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="banners.total > banners.per_page" class="flex justify-center">
                <Link v-if="banners.prev_page_url" :href="banners.prev_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Sebelumnya</Link>
                <span class="mx-3 self-center text-sm text-slate-500">Halaman {{ banners.current_page }} / {{ banners.last_page }}</span>
                <Link v-if="banners.next_page_url" :href="banners.next_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Seterusnya</Link>
            </div>

            <ConfirmDialog
                :open="!!deleting"
                title="Padam banner?"
                description="Tindakan ini tidak boleh dibatalkan. Banner akan dipadamkan secara kekal."
                confirm-label="Padam"
                variant="destructive"
                @confirm="executeDelete"
                @cancel="deleting = null"
            />
        </section>
    </AdminLayout>
</template>
