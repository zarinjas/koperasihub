<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ImagePlay, PenLine, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    posters: { type: Object, required: true },
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
const lightboxPoster = ref(null);
const postersIndexUrl = '/admin/posters';
const postersCreateUrl = '/admin/posters/create';

function posterEditUrl(posterId) {
    return `/admin/posters/${posterId}/edit`;
}

watch(search, (val) => {
    router.get(postersIndexUrl, { search: val || null, status: status.value || null }, { preserveState: true, replace: true });
});

watch(status, (val) => {
    router.get(postersIndexUrl, { search: search.value || null, status: val || null }, { preserveState: true, replace: true });
});

function confirmDelete(poster) {
    deleting.value = poster;
}

function executeDelete() {
    if (!deleting.value) return;
    router.post(`/admin/posters/${deleting.value.id}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onSuccess: () => { deleting.value = null; },
    });
}

function handlePublish(poster) {
    router.post(`/admin/posters/${poster.id}/publish`, {}, { preserveScroll: true });
}

function handleUnpublish(poster) {
    router.post(`/admin/posters/${poster.id}/unpublish`, {}, { preserveScroll: true });
}

function openLightbox(poster) {
    lightboxPoster.value = poster;
}

function closeLightbox() {
    lightboxPoster.value = null;
}
</script>

<template>
    <Head title="Poster & Infografik" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Poster & Infografik"
                description="Urus poster dan infografik yang akan dipaparkan di portal ahli dan laman web."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" :href="postersCreateUrl">
                        <Plus class="mr-2 h-4 w-4" />
                        Muat Naik Poster
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari poster..."
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

            <div v-if="posters.data.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada poster lagi."
                    description="Muat naik poster atau infografik pertama anda untuk dipaparkan kepada ahli dan pelawat."
                    compact
                >
                    <template #icon>
                        <ImagePlay class="h-12 w-12 text-slate-300" />
                    </template>
                    <template #actions>
                        <Button v-if="canCreate" :as="Link" :href="postersCreateUrl">
                            <Plus class="mr-2 h-4 w-4" />
                            Muat Naik Poster
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <article
                    v-for="poster in posters.data"
                    :key="poster.id"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                >
                    <button
                        type="button"
                        class="aspect-[4/5] w-full overflow-hidden bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                        @click="openLightbox(poster)"
                    >
                        <img
                            :src="poster.image_url"
                            :alt="poster.alt_text || poster.title"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </button>

                    <div class="flex flex-1 flex-col p-3">
                        <h3 class="truncate text-sm font-semibold text-slate-950">{{ poster.title }}</h3>
                        <div class="mt-1 flex items-center gap-2">
                            <StatusBadge :status="poster.status" />
                        </div>
                        <p v-if="poster.published_at_human" class="mt-1 text-xs text-slate-500">{{ poster.published_at_human }}</p>

                        <div class="mt-3 flex items-center gap-1">
                            <Button
                                v-if="canEdit"
                                :as="Link"
                                :href="posterEditUrl(poster.id)"
                                variant="ghost"
                                size="sm"
                            >
                                <PenLine class="h-3.5 w-3.5" />
                            </Button>

                            <Button
                                v-if="canPublish && poster.status === 'draft'"
                                variant="ghost"
                                size="sm"
                                @click="handlePublish(poster)"
                            >
                                Terbitkan
                            </Button>
                            <Button
                                v-if="canPublish && poster.status === 'published'"
                                variant="ghost"
                                size="sm"
                                @click="handleUnpublish(poster)"
                            >
                                Draf
                            </Button>

                            <Button
                                v-if="canDelete"
                                variant="ghost"
                                size="sm"
                                class="ml-auto text-red-500 hover:bg-red-50 hover:text-red-600"
                                @click="confirmDelete(poster)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="posters.total > posters.per_page" class="flex justify-center">
                <Link v-if="posters.prev_page_url" :href="posters.prev_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Sebelumnya</Link>
                <span class="mx-3 self-center text-sm text-slate-500">Halaman {{ posters.current_page }} / {{ posters.last_page }}</span>
                <Link v-if="posters.next_page_url" :href="posters.next_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Seterusnya</Link>
            </div>

            <ConfirmDialog
                :open="!!deleting"
                title="Padam poster?"
                description="Tindakan ini tidak boleh dibatalkan. Poster akan dipadamkan secara kekal."
                confirm-label="Padam"
                variant="destructive"
                @confirm="executeDelete"
                @cancel="deleting = null"
            />

            <PosterLightbox
                v-if="lightboxPoster"
                :poster="lightboxPoster"
                @close="closeLightbox"
            />
        </section>
    </AdminLayout>
</template>