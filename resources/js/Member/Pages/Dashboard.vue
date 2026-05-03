<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, FileText, MessagesSquare, UserRound } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    member: { type: Object, required: true },
    application: { type: Object, default: null },
    quickActions: { type: Array, required: true },
    recentDocuments: { type: Array, required: true },
    latestAnnouncements: { type: Array, required: true },
});

const icons = {
    ClipboardList,
    FileText,
    MessagesSquare,
    UserRound,
};
</script>

<template>
    <Head title="Dashboard Ahli" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Dashboard Ahli"
                description="Semak status keahlian, dokumen terkini, dan pengumuman penting daripada koperasi."
            >
                <template #actions>
                    <StatusBadge :status="member.membership_status" />
                </template>
            </PageHeader>

            <div v-if="!member.is_linked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan sepenuhnya. Sesetengah maklumat portal mungkin belum tersedia.
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <article class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 via-white to-blue-50 p-6 shadow-sm">
                    <p class="text-sm font-medium text-teal-700">Profil Ahli</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ member.full_name }}</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. ahli</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950">{{ member.member_no }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh sertai</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950">{{ member.joined_at || '-' }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-700">Status Permohonan</p>
                    <div v-if="application" class="mt-4 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ application.application_no }}</p>
                                <p class="text-sm text-slate-500">{{ application.submitted_at || '-' }}</p>
                            </div>
                            <StatusBadge :status="application.status" />
                        </div>
                        <Button :as="Link" href="/member/applications" variant="outline">Semak Permohonan</Button>
                    </div>
                    <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Tiada permohonan keahlian dipautkan pada akaun anda setakat ini.
                    </div>
                </article>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="action in quickActions"
                    :key="action.href"
                    :href="action.href"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                >
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                        <component :is="icons[action.icon] ?? UserRound" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-slate-950">{{ action.label }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ action.description }}</p>
                </Link>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">Dokumen Terkini</h2>
                            <p class="text-sm text-slate-600">Dokumen ahli yang tersedia untuk akses akaun anda.</p>
                        </div>
                        <Button :as="Link" href="/member/documents" variant="outline">Lihat Dokumen</Button>
                    </div>

                    <div v-if="recentDocuments.length" class="mt-6 space-y-3">
                        <article v-for="document in recentDocuments" :key="document.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ document.title }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ document.updated_at || '-' }}</p>
                                </div>
                                <StatusBadge :status="document.visibility" />
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-600">{{ document.file_size_label }}</p>
                                <Button :as="Link" :href="document.download_url" variant="outline">Muat Turun</Button>
                            </div>
                        </article>
                    </div>
                    <EmptyState
                        v-else
                        class="mt-6"
                        title="Tiada dokumen tersedia."
                        description="Dokumen ahli akan dipaparkan di sini apabila ia dimuat naik atau dipautkan kepada akaun anda."
                        compact
                    />
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">Pengumuman Terkini</h2>
                            <p class="text-sm text-slate-600">Hebahan public dan ahli sahaja yang masih aktif.</p>
                        </div>
                        <Button :as="Link" href="/member/announcements" variant="outline">Lihat Pengumuman</Button>
                    </div>

                    <div v-if="latestAnnouncements.length" class="mt-6 space-y-3">
                        <article v-for="announcement in latestAnnouncements" :key="announcement.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ announcement.title }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ announcement.published_at || '-' }}</p>
                                </div>
                                <StatusBadge :status="announcement.audience" />
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ announcement.summary || 'Tiada ringkasan disediakan.' }}</p>
                        </article>
                    </div>
                    <EmptyState
                        v-else
                        class="mt-6"
                        title="Tiada pengumuman tersedia."
                        description="Pengumuman koperasi yang aktif akan dipaparkan di sini."
                        compact
                    />
                </section>
            </div>
        </section>
    </MemberLayout>
</template>
