<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Bell, Calculator, ChevronRight, CircleAlert, CircleCheck, Clock, CreditCard, Eye, EyeOff, FileCheck, FileText, Gift, HandCoins, ImagePlay, Megaphone, MessagesSquare, ScrollText, Sparkles, Star, UserRound, Wallet, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import DecorativeBlobs from '@/Shared/Components/DecorativeBlobs.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import BannerCarousel from '@/Shared/Components/BannerCarousel.vue';
import PosterCarousel from '@/Shared/Components/PosterCarousel.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    member: { type: Object, required: true },
    digitalCard: { type: Object, default: null },
    application: { type: Object, default: null },
    quickActions: { type: Array, required: true },
    featuredForms: { type: Array, required: true },
    latestAnnouncements: { type: Array, required: true },
    financingSummary: { type: Object, default: null },
    caruman: { type: Object, default: null },
    posters: { type: Array, default: () => [] },
    banners: { type: Array, default: () => [] },
});

const showCaruman = ref(false);
const activeCarumanTab = ref('semasa');

const carumanTabs = [
    { key: 'semasa', label: 'Semasa' },
    { key: 'keseluruhan', label: 'Keseluruhan' },
    { key: 'dividen', label: 'Dividen' },
];

const toggleCaruman = () => { showCaruman.value = !showCaruman.value; };

const formatCaruman = (value) => {
    if (value === null || value === undefined) return '*****';
    if (!showCaruman.value) return 'RM *****';
    return 'RM ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const activeCarumanValue = computed(() => {
    const tab = activeCarumanTab.value;
    if (tab === 'semasa') return props.caruman?.caruman_semasa ?? 0;
    if (tab === 'keseluruhan') return props.caruman?.caruman_keseluruhan ?? 0;
    if (tab === 'dividen') return props.caruman?.dividen ?? 0;
    return 0;
});

const activeCarumanLabel = computed(() => {
    const tab = activeCarumanTab.value;
    if (tab === 'semasa') return 'Caruman Setakat Ini';
    if (tab === 'keseluruhan') return 'Caruman Keseluruhan';
    if (tab === 'dividen') return 'Dividen Tahun Ini';
    return '';
});

const isCarumanDividen = computed(() => activeCarumanTab.value === 'dividen');

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat pagi';
    if (hour < 15) return 'Selamat tengah hari';
    if (hour < 19) return 'Selamat petang';
    return 'Selamat malam';
});

const greetingEmoji = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return '\u{1F305}';
    if (hour < 15) return '\u{2600}\u{FE0F}';
    if (hour < 19) return '\u{1F324}';
    return '\u{1F319}';
});

const hasFinancing = computed(() => {
    return props.financingSummary && (props.financingSummary.under_review > 0 || props.financingSummary.guarantor_requests > 0);
});

const actionColors = ['bg-teal-500', 'bg-blue-500', 'bg-emerald-500', 'bg-amber-500'];

const actionIcon = (name) => {
    const map = { FileCheck, HandCoins, MessagesSquare, UserRound };
    return map[name] ?? UserRound;
};

const announcementIcon = (idx) => {
    const list = [Megaphone, Bell, FileText, Star];
    return list[idx % list.length];
};

const showReferral = ref(true);
const closeReferral = () => { showReferral.value = false; };
</script>

<template>
    <Head title="Dashboard Ahli" />

    <MemberLayout>
        <div class="space-y-3 pb-28">
            <!-- Referral Engagement (top banner, dismissible) -->
            <div v-if="showReferral">
                <Link
                    href="/member/referrals"
                    class="relative flex items-center gap-3 rounded-2xl bg-gradient-to-r from-rose-50 via-rose-50 to-orange-50 px-4 py-3.5 shadow-sm ring-1 ring-rose-200/60 transition hover:shadow-md"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-500">
                        <Gift class="h-[18px] w-[18px]" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-rose-700">Rujuk Rakan & Dapatkan Ganjaran</p>
                        <p class="mt-0.5 text-xs text-rose-500">Jemput rakan sertai koperasi, nikmati ganjaran istimewa!</p>
                    </div>
                    <ChevronRight class="h-4 w-4 shrink-0 text-rose-400" />
                    <button
                        type="button"
                        class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200 transition hover:text-slate-600"
                        @click.prevent="closeReferral"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </Link>
            </div>

            <!-- Banner Carousel -->
            <div v-if="banners.length">
                <BannerCarousel :banners="banners" />
            </div>

            <!-- Unlinked Warning -->
            <div v-if="!member.is_linked" class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                <CircleAlert class="h-5 w-5 shrink-0" />
                Rekod ahli anda belum dipautkan sepenuhnya. Sesetengah maklumat portal mungkin belum tersedia.
            </div>

            <!-- Hero / Member Card -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800 px-5 py-5 text-white shadow-lg">
                <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/8 blur-3xl" />
                <div class="pointer-events-none absolute -bottom-8 -left-8 h-40 w-40 rounded-full bg-cyan-400/15 blur-3xl" />
                <div class="pointer-events-none absolute right-12 top-1/3 h-24 w-24 rounded-full bg-teal-400/10 blur-2xl" />

                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium tracking-wide text-teal-100/80 uppercase">
                            {{ greeting }}<span class="ml-1">{{ greetingEmoji }}</span>
                        </p>
                        <h1 class="mt-0.5 text-2xl font-bold tracking-tight">{{ member.full_name }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-md bg-white/15 px-2 py-0.5 text-[11px] font-medium text-teal-50/90">
                                {{ member.member_no || 'Sementara' }}
                            </span>
                            <span
                                class="rounded-md px-2 py-0.5 text-[11px] font-medium"
                                :class="member.membership_status === 'active' ? 'bg-emerald-400/25 text-emerald-100' : 'bg-amber-400/25 text-amber-100'"
                            >
                                {{ member.membership_status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>

                    <Link
                        v-if="digitalCard"
                        :href="digitalCard.view_url"
                        class="relative shrink-0 transition hover:-translate-y-0.5"
                    >
                        <div class="flex h-[88px] w-[60px] flex-col items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-white/20 to-white/5 backdrop-blur ring-1 ring-white/20 transition hover:bg-white/25">
                            <div class="flex h-5 w-5 items-center justify-center rounded-[3px] bg-white/20">
                                <div class="h-2.5 w-2.5 rounded-[1px] bg-white/60" />
                            </div>
                            <CreditCard class="h-5 w-5 text-white/70" />
                        </div>
                        <p class="mt-1 text-center text-[10px] font-medium tracking-wide text-teal-100/70">Kad Digital</p>
                    </Link>
                </div>

                <div v-if="member.joined_at" class="relative mt-3 flex items-center gap-1.5 text-[11px] text-teal-100/60">
                    <Clock class="h-3 w-3" />
                    Ahli sejak {{ member.joined_at }}
                </div>
            </section>

            <!-- Achievement / Milestone -->
            <div v-if="member.joined_at || member.member_no" class="flex gap-2.5">
                <div v-if="member.joined_at" class="flex flex-1 items-center gap-3 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/50 p-4 shadow-sm ring-1 ring-slate-200/60">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                        <Sparkles class="h-4 w-4" />
                    </span>
                    <div>
                        <p class="text-[11px] font-medium text-slate-400">Keahlian Sejak</p>
                        <p class="text-sm font-semibold text-slate-900">{{ member.joined_at }}</p>
                    </div>
                </div>
                <div class="flex flex-1 items-center gap-3 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/50 p-4 shadow-sm ring-1 ring-slate-200/60">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-500">
                        <Star class="h-4 w-4" />
                    </span>
                    <div>
                        <p class="text-[11px] font-medium text-slate-400">Status</p>
                        <p class="text-sm font-semibold text-slate-900">{{ member.membership_status === 'active' ? 'Aktif' : 'Sementara' }}</p>
                    </div>
                </div>
            </div>

            <!-- Account Summary / Caruman -->
            <section v-if="caruman" class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <DecorativeBlobs color="teal" />
                <div class="relative px-5 pb-4 pt-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                                <Wallet class="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Ringkasan Akaun</p>
                                <p class="text-[11px] text-slate-400">Tahun {{ caruman.year }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            :title="showCaruman ? 'Sembunyikan jumlah' : 'Tunjukkan jumlah'"
                            @click="toggleCaruman"
                        >
                            <Eye v-if="showCaruman" class="h-[18px] w-[18px]" />
                            <EyeOff v-else class="h-[18px] w-[18px]" />
                        </button>
                    </div>

                    <!-- Tab Switcher -->
                    <div class="mt-3 flex gap-1 rounded-lg bg-slate-100 p-0.5">
                        <button
                            v-for="tab in carumanTabs"
                            :key="tab.key"
                            type="button"
                            class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition"
                            :class="activeCarumanTab === tab.key ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            @click="activeCarumanTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Value Display -->
                    <div class="mt-3">
                        <p class="text-[11px] font-medium text-slate-400">{{ activeCarumanLabel }}</p>
                        <div class="flex items-baseline gap-2">
                            <p
                                class="mt-0.5 text-3xl font-bold tabular-nums tracking-tight"
                                :class="isCarumanDividen ? 'text-emerald-600' : 'text-slate-900'"
                            >
                                {{ formatCaruman(activeCarumanValue) }}
                            </p>
                        </div>
                        <div v-if="showCaruman && caruman && activeCarumanTab === 'semasa'" class="mt-1.5 flex items-center gap-1 text-[11px] text-teal-600">
                            <CircleCheck class="h-3 w-3" />
                            <span>Caruman semasa untuk tahun {{ caruman.year }}</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <Link
                            href="/member/caruman"
                            class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 transition hover:text-teal-700"
                        >
                            Lihat butiran penuh
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Status Permohonan (shown when no caruman) -->
            <section v-else-if="digitalCard" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <ScrollText class="h-[18px] w-[18px]" />
                    </span>
                    <p class="text-sm font-semibold text-slate-900">Status Permohonan</p>
                </div>
                <div v-if="application" class="mt-3 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ application.application_no }}</p>
                            <p class="text-xs text-slate-400">{{ application.submitted_at || '-' }}</p>
                        </div>
                        <StatusBadge :status="application.status" />
                    </div>
                    <Link
                        href="/member/applications"
                        class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-700"
                    >
                        Semak Permohonan
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <div v-else class="mt-3 text-sm text-slate-400">
                    Tiada permohonan keahlian dipautkan pada akaun anda setakat ini.
                </div>
            </section>

            <!-- Quick Actions -->
            <div class="grid grid-cols-4 gap-2.5">
                <Link
                    v-for="(action, idx) in quickActions"
                    :key="action.href"
                    :href="action.href"
                    class="group flex min-h-[88px] flex-col items-center justify-center gap-2 rounded-2xl bg-white px-1.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-xl text-white shadow-xs transition group-hover:scale-105"
                        :class="actionColors[idx % actionColors.length]"
                    >
                        <component :is="actionIcon(action.icon)" class="h-5 w-5" />
                    </span>
                    <span class="text-center text-[11px] font-medium leading-tight text-slate-600">
                        {{ action.label }}
                    </span>
                </Link>
            </div>

            <!-- Financing Section -->
            <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                            <HandCoins class="h-[18px] w-[18px]" />
                        </span>
                        <p class="text-sm font-semibold text-slate-900">Pembiayaan</p>
                    </div>
                    <Link
                        href="/member/financing"
                        class="inline-flex items-center gap-0.5 text-xs font-medium text-teal-600 hover:text-teal-700"
                    >
                        Semua
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-if="hasFinancing" class="mt-3 flex gap-2.5 overflow-x-auto pb-0.5 scrollbar-none">
                    <div v-if="financingSummary.under_review > 0" class="flex shrink-0 items-center gap-3 rounded-xl bg-blue-50 px-4 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <FileText class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-xs text-blue-500">Dalam Semakan</p>
                            <p class="text-lg font-bold text-slate-900">{{ financingSummary.under_review }}</p>
                        </div>
                    </div>
                    <div v-if="financingSummary.guarantor_requests > 0" class="flex shrink-0 items-center gap-3 rounded-xl bg-amber-50 px-4 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                            <ArrowUpRight class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-xs text-amber-500">Permintaan Penjamin</p>
                            <p class="text-lg font-bold text-slate-900">{{ financingSummary.guarantor_requests }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <Link
                        href="/member/financing"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700"
                    >
                        <HandCoins class="h-4 w-4" />
                        Mohon Baru
                    </Link>
                    <Link
                        href="/member/financing/calculator"
                        class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-700"
                    >
                        <Calculator class="h-4 w-4" />
                        Anggaran
                    </Link>
                </div>
            </section>

            <!-- Announcements Feed -->
            <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <Megaphone class="h-[18px] w-[18px]" />
                        </span>
                        <p class="text-sm font-semibold text-slate-900">Pengumuman</p>
                    </div>
                    <Link
                        v-if="latestAnnouncements.length"
                        href="/member/announcements"
                        class="inline-flex items-center gap-0.5 text-xs font-medium text-teal-600 hover:text-teal-700"
                    >
                        Semua
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-if="latestAnnouncements.length" class="mt-3 divide-y divide-slate-50">
                    <div
                        v-for="(item, idx) in latestAnnouncements.slice(0, 3)"
                        :key="item.id"
                        class="flex gap-3 py-3 first:pt-0 last:pb-0"
                    >
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500">
                            <component :is="announcementIcon(idx)" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                            <p v-if="item.summary" class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ item.summary }}</p>
                            <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                                <span>{{ item.published_at || '-' }}</span>
                                <span v-if="item.audience === 'members'" class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-500">Ahli</span>
                            </div>
                        </div>
                    </div>
                </div>
                <EmptyState
                    v-else
                    class="mt-3"
                    title="Tiada pengumuman"
                    description="Pengumuman terkini akan dipaparkan di sini."
                    compact
                />
            </section>

            <!-- Borang Terkini -->
            <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <FileCheck class="h-[18px] w-[18px]" />
                        </span>
                        <p class="text-sm font-semibold text-slate-900">Borang Terkini</p>
                    </div>
                    <Link
                        v-if="featuredForms.length"
                        href="/member/forms"
                        class="inline-flex items-center gap-0.5 text-xs font-medium text-teal-600 hover:text-teal-700"
                    >
                        Semua
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-if="featuredForms.length" class="mt-3 space-y-2">
                    <Link
                        v-for="form in featuredForms"
                        :key="form.id"
                        :href="form.url"
                        class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/30"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500">
                            <FileText class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ form.title }}</p>
                            <p class="text-xs text-slate-400">{{ form.category_name || 'Borang' }}</p>
                        </div>
                        <ChevronRight class="h-4 w-4 shrink-0 text-slate-300" />
                    </Link>
                </div>
                <EmptyState
                    v-else
                    class="mt-3"
                    title="Tiada borang tersedia"
                    description="Borang yang diterbitkan akan dipaparkan di sini."
                    compact
                />
            </section>

            <!-- Posters -->
            <section v-if="posters.length" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                            <ImagePlay class="h-[18px] w-[18px]" />
                        </span>
                        <p class="text-sm font-semibold text-slate-900">Poster & Infografik</p>
                    </div>
                    <Link
                        href="/member/posters"
                        class="inline-flex items-center gap-0.5 text-xs font-medium text-teal-600 hover:text-teal-700"
                    >
                        Semua
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <div class="mt-3">
                    <PosterCarousel :posters="posters" />
                </div>
            </section>
        </div>

        <!-- Floating Action Button -->
        <div class="fixed bottom-20 right-5 z-30 lg:bottom-6 lg:right-8">
            <Link
                href="/member/financing"
                class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-600 text-white shadow-lg transition hover:bg-teal-700 hover:shadow-xl active:scale-95"
                title="Mohon Pembiayaan Baru"
            >
                <HandCoins class="h-6 w-6" />
            </Link>
        </div>
    </MemberLayout>
</template>
