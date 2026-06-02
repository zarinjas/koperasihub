<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, ChevronRight, Megaphone } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';
import { Badge } from '@/Shared/Components/ui/badge';

const props = defineProps({
    notifications: { type: Object, required: true },
    filter: { type: String, default: '' },
});

const markAsRead = (id) => {
    router.post(`/admin/notifications/${id}/read`, {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post('/admin/notifications/read-all', {}, {
        preserveScroll: true,
    });
};

const filterChanged = (f) => {
    router.get('/admin/notifications', { filter: f === 'all' ? '' : f }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Notifikasi" />

    <AdminLayout>
        <PageHeader title="Notifikasi" description="Semua notifikasi pengumuman yang diterima.">
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="mr-2 h-4 w-4" />
                    Tandakan Semua Dibaca
                </Button>
            </template>
        </PageHeader>

        <div class="mb-4 flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :class="{ 'bg-slate-200': filter === '' }"
                @click="filterChanged('all')"
            >
                Semua
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :class="{ 'bg-slate-200': filter === 'unread' }"
                @click="filterChanged('unread')"
            >
                Belum Dibaca
            </Button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white">
            <div v-if="notifications.data.length === 0" class="px-6 py-12 text-center text-sm text-slate-500">
                <Bell class="mx-auto mb-3 h-8 w-8 text-slate-300" />
                Tiada notifikasi.
            </div>

            <div v-else>
                <div
                    v-for="(n, i) in notifications.data"
                    :key="n.id"
                    class="flex items-start gap-4 border-b border-slate-100 px-6 py-4 last:border-b-0"
                    :class="{ 'bg-teal-50/50': !n.read_at }"
                >
                    <Megaphone class="mt-1 h-5 w-5 shrink-0 text-teal-600" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium">{{ n.title }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ n.summary }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ n.created_at_raw }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <Badge v-if="!n.read_at" variant="default" class="bg-teal-600 text-[10px]">
                                    Baharu
                                </Badge>
                                <Button
                                    v-if="!n.read_at"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-auto text-xs text-teal-700"
                                    @click="markAsRead(n.id)"
                                >
                                    <CheckCheck class="mr-1 h-3 w-3" />
                                    Tandakan Dibaca
                                </Button>
                            </div>
                        </div>
                        <div v-if="n.url && n.url !== '#'" class="mt-2">
                            <Link :href="n.url" class="inline-flex items-center gap-1 text-xs font-medium text-teal-700 hover:underline">
                                Lihat Pengumuman
                                <ChevronRight class="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="notifications.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-2 border-t border-slate-100 px-6 py-4"
            >
                <template v-for="(link, index) in notifications.links" :key="index">
                    <Button
                        v-if="link.url"
                        :as="Link"
                        :href="link.url"
                        variant="outline"
                        size="sm"
                        :class="{ 'bg-teal-700 text-white hover:bg-teal-800': link.active }"
                        v-html="link.label"
                    />
                    <span v-else class="px-2 text-sm text-slate-400" v-html="link.label" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>