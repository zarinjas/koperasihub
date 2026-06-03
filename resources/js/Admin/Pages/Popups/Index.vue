<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Megaphone, PenLine, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    popups: { type: Object, required: true },
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
const popupsIndexUrl = '/admin/popups';
const popupsCreateUrl = '/admin/popups/create';

function popupEditUrl(popupId) {
    return `/admin/popups/${popupId}/edit`;
}

watch(search, (val) => {
    router.get(popupsIndexUrl, { search: val || null, status: status.value || null }, { preserveState: true, replace: true });
});

watch(status, (val) => {
    router.get(popupsIndexUrl, { search: search.value || null, status: val || null }, { preserveState: true, replace: true });
});

function confirmDelete(popup) {
    deleting.value = popup;
}

function executeDelete() {
    if (!deleting.value) return;
    router.post(`/admin/popups/${deleting.value.id}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onSuccess: () => { deleting.value = null; },
    });
}

function handlePublish(popup) {
    router.post(`/admin/popups/${popup.id}/publish`, {}, { preserveScroll: true });
}

function handleUnpublish(popup) {
    router.post(`/admin/popups/${popup.id}/unpublish`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Popup Ahli" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Popup Ahli"
                description="Urus popup yang akan dipaparkan kepada ahli selepas log masuk."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" :href="popupsCreateUrl">
                        <Plus class="mr-2 h-4 w-4" />
                        Cipta Popup
                    </Button>
                </template>
            </PageHeader>

            <AdminFilterBar>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari popup..."
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

            <div v-if="popups.data.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada popup lagi."
                    description="Cipta popup pertama anda untuk dipaparkan kepada ahli selepas log masuk."
                    compact
                >
                    <template #icon>
                        <Megaphone class="h-12 w-12 text-slate-300" />
                    </template>
                    <template #actions>
                        <Button v-if="canCreate" :as="Link" :href="popupsCreateUrl">
                            <Plus class="mr-2 h-4 w-4" />
                            Cipta Popup
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tajuk</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Mula</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Tamat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="popup in popups.data" :key="popup.id" class="hover:bg-slate-50">
                            <td class="max-w-xs truncate px-6 py-4 text-sm font-medium text-slate-950">
                                {{ popup.title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ popup.starts_at_human || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ popup.ends_at_human || '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="popup.is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ popup.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="popupEditUrl(popup.id)"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <PenLine class="h-3.5 w-3.5" />
                                    </Button>

                                    <Button
                                        v-if="canPublish && !popup.is_active"
                                        variant="ghost"
                                        size="sm"
                                        @click="handlePublish(popup)"
                                    >
                                        Terbitkan
                                    </Button>
                                    <Button
                                        v-if="canPublish && popup.is_active"
                                        variant="ghost"
                                        size="sm"
                                        @click="handleUnpublish(popup)"
                                    >
                                        Draf
                                    </Button>

                                    <Button
                                        v-if="canDelete"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-500 hover:bg-red-50 hover:text-red-600"
                                        @click="confirmDelete(popup)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="popups.total > popups.per_page" class="flex justify-center">
                <Link v-if="popups.prev_page_url" :href="popups.prev_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Sebelumnya</Link>
                <span class="mx-3 self-center text-sm text-slate-500">Halaman {{ popups.current_page }} / {{ popups.last_page }}</span>
                <Link v-if="popups.next_page_url" :href="popups.next_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Seterusnya</Link>
            </div>

            <ConfirmDialog
                :open="!!deleting"
                title="Padam popup?"
                description="Tindakan ini tidak boleh dibatalkan. Popup akan dipadamkan secara kekal."
                confirm-label="Padam"
                variant="destructive"
                @confirm="executeDelete"
                @cancel="deleting = null"
            />
        </section>
    </AdminLayout>
</template>
