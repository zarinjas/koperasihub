<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, ClipboardList, FileText, MessagesSquare, UserRound } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import MemberDigitalCardPreview from '@/Shared/Components/MemberDigitalCardPreview.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    member: { type: Object, required: true },
    digitalCard: { type: Object, default: null },
    application: { type: Object, default: null },
    quickActions: { type: Array, required: true },
    featuredForms: { type: Array, required: true },
    latestAnnouncements: { type: Array, required: true },
});

const icons = {
    ArrowUpRight,
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
                description="Semak status keahlian, borang terkini, dan pengumuman penting daripada koperasi."
            >
                <template #actions>
                    <StatusBadge :status="member.membership_status" />
                </template>
            </PageHeader>

            <div v-if="!member.is_linked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan sepenuhnya. Sesetengah maklumat portal mungkin belum tersedia.
            </div>

            <div v-if="digitalCard" class="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(20rem,0.75fr)] lg:items-start">
                <section class="relative px-1 py-2 sm:px-2">
                    <div class="pointer-events-none absolute inset-x-4 top-8 hidden h-36 rounded-full bg-gradient-to-r from-teal-100/60 via-cyan-100/50 to-blue-100/60 blur-3xl lg:block" />
                    <div class="pointer-events-none absolute inset-x-0 top-7 mx-auto h-44 max-w-[24rem] rounded-full bg-gradient-to-r from-teal-100/70 via-cyan-100/60 to-blue-100/70 blur-3xl lg:hidden" />
                    <div class="relative flex flex-col items-center gap-4">
                        <Link
                            :href="digitalCard.view_url"
                            class="group relative block w-full max-w-[760px] transition duration-200 hover:-translate-y-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2"
                        >
                            <div class="pointer-events-none absolute inset-x-5 bottom-2 h-24 rounded-full bg-cyan-200/60 blur-3xl transition duration-200 group-hover:bg-cyan-200/80 lg:inset-x-16" />
                            <div class="relative">
                                <MemberDigitalCardPreview
                                    :cooperative="$page.props.appSettings.cooperative"
                                    :card="digitalCard"
                                />
                            </div>
                        </Link>

                        <Link
                            :href="digitalCard.view_url"
                            class="inline-flex items-center gap-2 text-sm font-medium text-teal-700 transition hover:text-teal-800"
                        >
                            Lihat kad penuh
                            <ArrowUpRight class="h-4 w-4" />
                        </Link>

                        <div v-if="digitalCard.readiness.notice" class="max-w-[760px] rounded-2xl bg-white/90 px-4 py-3 text-sm font-medium text-amber-800 shadow-sm ring-1 ring-amber-200/70">
                            {{ digitalCard.readiness.notice }}
                        </div>
                    </div>
                </section>

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
                            <h2 class="text-lg font-semibold text-slate-950">Borang Terkini</h2>
                            <p class="text-sm text-slate-600">Borang online koperasi yang tersedia untuk tindakan anda.</p>
                        </div>
                        <Button :as="Link" href="/member/forms" variant="outline">Lihat Borang</Button>
                    </div>

                    <div v-if="featuredForms.length" class="mt-6 space-y-3">
                        <article v-for="form in featuredForms" :key="form.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ form.title }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ form.category_name || 'Tanpa kategori' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ form.description || 'Borang rasmi tersedia untuk dihantar secara online.' }}</p>
                                </div>
                                <StatusBadge :status="form.visibility" :label="form.visibility_label" />
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-600">{{ form.updated_at || '-' }}</p>
                                <Button :as="Link" :href="form.url" variant="outline">Isi Borang</Button>
                            </div>
                        </article>
                    </div>
                    <EmptyState
                        v-else
                        class="mt-6"
                        title="Tiada borang tersedia."
                        description="Borang koperasi yang diterbitkan akan dipaparkan di sini apabila tersedia."
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
