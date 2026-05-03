<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Megaphone, Pin } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';

defineProps({
    announcements: { type: Array, required: true },
});
</script>

<template>
    <Head title="Pengumuman" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-amber-50 via-white to-teal-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Pengumuman"
                    description="Ikuti hebahan rasmi dan makluman terkini yang telah diterbitkan oleh pihak koperasi."
                    align="start"
                />
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EmptyState
                    v-if="announcements.length === 0"
                    title="Tiada pengumuman tersedia."
                    description="Pengumuman yang telah diterbitkan akan dipaparkan di sini."
                    :compact="true"
                />

                <div v-else class="grid gap-5 lg:grid-cols-2">
                    <Link
                        v-for="announcement in announcements"
                        :key="announcement.id"
                        :href="announcement.detail_url"
                        class="group rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                                <Megaphone class="h-5 w-5" />
                            </div>
                            <span class="inline-flex items-center gap-2">
                                <CalendarDays class="h-4 w-4" />
                                {{ announcement.published_at || 'Tarikh akan dikemas kini' }}
                            </span>
                            <span v-if="announcement.is_pinned" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                <Pin class="h-3.5 w-3.5" />
                                Dipin
                            </span>
                        </div>
                        <div class="mt-5 space-y-3">
                            <h2 class="text-lg font-semibold text-slate-950">{{ announcement.title }}</h2>
                            <p class="text-sm leading-7 text-slate-600">{{ announcement.content_preview }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
