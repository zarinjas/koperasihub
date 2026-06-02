<script setup>
import { Head } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

defineProps({
    announcements: { type: Array, required: true },
});
</script>

<template>
    <Head title="Pengumuman" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Pengumuman"
                description="Lihat hebahan public dan ahli sahaja yang masih aktif untuk koperasi anda."
            />

            <div v-if="announcements.length" class="grid gap-4">
                <article
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-slate-950">{{ announcement.title }}</h2>
                                <StatusBadge v-if="announcement.is_pinned" status="published" label="Dipin" />
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ announcement.published_at || '-' }}</p>
                        </div>
                        <StatusBadge :status="announcement.audience" />
                    </div>

                    <p class="mt-4 text-sm leading-6 text-slate-600">
                        {{ announcement.summary || announcement.content_preview || 'Tiada ringkasan disediakan.' }}
                    </p>
                </article>
            </div>
            <EmptyState
                v-else
                title="Tiada pengumuman tersedia buat masa ini."
                description="Pengumuman aktif untuk ahli akan dipaparkan di sini."
            />
        </section>
    </MemberLayout>
</template>