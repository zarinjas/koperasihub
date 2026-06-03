<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, ChevronDown, FileText, LogIn, Mail, MapPin, Menu, Phone, ShieldCheck, UserRound, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppLogo from '@/Shared/Components/AppLogo.vue';
import FlashToast from '@/Shared/Components/FlashToast.vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const mobileMenuOpen = ref(false);
const activeDropdown = ref(null);
const navRef = ref(null);
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const contact = computed(() => page.props.appSettings?.contact ?? {});

function toggleDropdown(label) {
    activeDropdown.value = activeDropdown.value === label ? null : label;
}

function closeAllDropdowns() {
    activeDropdown.value = null;
}

function handleClickOutside(event) {
    if (navRef.value && !navRef.value.contains(event.target)) {
        closeAllDropdowns();
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

const navigation = [
    { label: 'Utama', href: '/' },
    { label: 'Tentang Kami', href: '/tentang-kami' },
    {
        label: 'Perkhidmatan',
        href: '/perkhidmatan',
        children: [
            { label: 'Keanggotaan', href: '/perkhidmatan/keanggotaan' },
            { label: 'Pembiayaan Anggota', href: '/perkhidmatan/pembiayaan-anggota' },
            { label: 'Simpanan & Syer', href: '/perkhidmatan/simpanan-syer' },
            { label: 'Takaful Kenderaan', href: '/perkhidmatan/takaful-kenderaan' },
            { label: 'Kebajikan Anggota', href: '/perkhidmatan/kebajikan-anggota' },
        ],
    },
    {
        label: 'Perniagaan',
        href: '/perniagaan',
        children: [
            { label: 'Kedai Koperasi', href: '/perniagaan' },
            { label: 'Hartanah & Sewaan', href: '/perniagaan' },
            { label: 'Stesen Minyak', href: '/perniagaan' },
            { label: 'E-Dagang', href: '/perniagaan' },
            { label: 'Bilik Seminar', href: '/perniagaan' },
        ],
    },
    {
        label: 'Sumber',
        href: '/pengumuman',
        children: [
            { label: 'Pengumuman', href: '/pengumuman' },
            { label: 'Borang Online', href: '/forms' },
            { label: 'Muat Turun', href: '/muat-turun' },
            { label: 'Soalan Lazim', href: '/soalan-lazim' },
        ],
    },
    { label: 'Hubungi Kami', href: '/hubungi' },
];

const LOGIN_LABEL = 'Log Masuk';
const loginLinks = [
    { label: 'Admin', href: '/admin/login', icon: ShieldCheck },
    { label: 'Portal Ahli', href: '/member/login', icon: UserRound },
];

const footerGroups = [
    {
        title: 'Koperasi',
        links: [
            { label: 'Tentang Kami', href: '/tentang-kami' },
            { label: 'Lembaga & Pengurusan', href: '/tentang-kami' },
            { label: 'Tadbir Urus', href: '/tentang-kami' },
            { label: 'Hubungi Kami', href: '/hubungi' },
        ],
    },
    {
        title: 'Perkhidmatan',
        links: [
            { label: 'Keanggotaan', href: '/perkhidmatan/keanggotaan' },
            { label: 'Pembiayaan Anggota', href: '/perkhidmatan/pembiayaan-anggota' },
            { label: 'Simpanan & Syer', href: '/perkhidmatan/simpanan-syer' },
            { label: 'Takaful Kenderaan', href: '/perkhidmatan/takaful-kenderaan' },
            { label: 'Kebajikan Anggota', href: '/perkhidmatan/kebajikan-anggota' },
        ],
    },
    {
        title: 'Perniagaan',
        links: [
            { label: 'Kedai Koperasi', href: '/perniagaan' },
            { label: 'Hartanah & Sewaan', href: '/perniagaan' },
            { label: 'Stesen Minyak', href: '/perniagaan' },
            { label: 'E-Dagang', href: '/perniagaan' },
            { label: 'Bilik Seminar', href: '/perniagaan' },
        ],
    },
    {
        title: 'Sumber',
        links: [
            { label: 'Permohonan Ahli', href: '/membership/apply' },
            { label: 'Pengumuman', href: '/pengumuman' },
            { label: 'Borang Online', href: '/forms' },
            { label: 'Muat Turun', href: '/muat-turun' },
            { label: 'Soalan Lazim', href: '/soalan-lazim' },
            { label: 'Dasar Privasi', href: '/dasar-privasi' },
        ],
    },
];

const footerText = computed(
    () => cooperative.value.footer_text || `${cooperative.value.name || 'Koperasi ini'} menyediakan platform maklumat, perkhidmatan dan sokongan anggota secara lebih tersusun.`,
);

const address = computed(() => [
    contact.value.address_line_1,
    contact.value.address_line_2,
    [contact.value.postcode, contact.value.city, contact.value.state].filter(Boolean).join(' '),
].filter(Boolean).join(', '));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-blue-100/40 via-white to-sky-100/25 text-slate-950">
        <header class="sticky top-0 z-40 border-b border-teal-900/10 bg-white/90 shadow-sm shadow-slate-200/40 backdrop-blur-xl">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-4">
                        <AppLogo
                            :name="cooperative.name || 'KoperasiHub'"
                            :logo-url="cooperative.logo_url"
                            href="/"
                            size="md"
                        />
                    </div>

                    <div ref="navRef" class="hidden min-w-0 flex-nowrap items-center gap-2 lg:flex">
                        <nav class="flex min-w-0 flex-nowrap items-center gap-0.5">
                            <div v-for="item in navigation" :key="item.label" class="relative">
                                <button
                                    v-if="item.children"
                                    type="button"
                                    class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-teal-50 hover:text-teal-800 xl:px-3.5"
                                    :class="activeDropdown === item.label ? 'bg-teal-50 text-teal-800' : ''"
                                    @click.stop="toggleDropdown(item.label)"
                                >
                                    {{ item.label }}
                                    <ChevronDown
                                        class="ml-1 h-4 w-4 transition-transform duration-200"
                                        :class="activeDropdown === item.label ? 'rotate-180' : ''"
                                    />
                                </button>
                                <Link
                                    v-else
                                    :href="item.href"
                                    class="inline-flex whitespace-nowrap rounded-full px-2.5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-teal-50 hover:text-teal-800 xl:px-3.5"
                                >
                                    {{ item.label }}
                                </Link>

                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1"
                                >
                                    <div
                                        v-if="item.children && activeDropdown === item.label"
                                        class="absolute left-0 top-full z-50 mt-1.5 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10"
                                    >
                                        <Link
                                            v-for="child in item.children"
                                            :key="child.href"
                                            :href="child.href"
                                            class="block whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800"
                                            @click="closeAllDropdowns"
                                        >
                                            {{ child.label }}
                                        </Link>
                                    </div>
                                </Transition>
                            </div>
                        </nav>

                        <div class="relative flex shrink-0 flex-nowrap items-center gap-2">
                            <Button :as="Link" href="/membership/apply" class="hidden whitespace-nowrap px-3.5 xl:inline-flex">
                                Mohon Jadi Ahli
                            </Button>

                            <div class="relative">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="whitespace-nowrap px-3.5"
                                    :class="activeDropdown === LOGIN_LABEL ? 'border-teal-300 bg-teal-50' : ''"
                                    @click.stop="toggleDropdown(LOGIN_LABEL)"
                                >
                                    <LogIn class="mr-2 h-4 w-4" />
                                    Log Masuk
                                    <ChevronDown
                                        class="ml-2 h-4 w-4 transition-transform duration-200"
                                        :class="activeDropdown === LOGIN_LABEL ? 'rotate-180' : ''"
                                    />
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1"
                                >
                                    <div
                                        v-if="activeDropdown === LOGIN_LABEL"
                                        class="absolute right-0 top-full z-50 mt-1.5 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10"
                                    >
                                        <Link
                                            v-for="loginItem in loginLinks"
                                            :key="loginItem.href"
                                            :href="loginItem.href"
                                            class="flex items-center whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800"
                                            @click="closeAllDropdowns"
                                        >
                                            <component :is="loginItem.icon" class="mr-2 h-4 w-4 text-teal-700" />
                                            {{ loginItem.label }}
                                        </Link>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>

                    <Button type="button" variant="ghost" size="icon" class="lg:hidden" aria-label="Buka menu" @click="mobileMenuOpen = true">
                        <Menu class="h-5 w-5" />
                    </Button>
                </div>
            </div>
        </header>

        <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false">
            <aside class="ml-auto flex h-full w-full max-w-sm flex-col border-l border-slate-200 bg-white shadow-2xl" @click.stop>
                <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
                    <AppLogo
                        :name="cooperative.name || 'KoperasiHub'"
                        :logo-url="cooperative.logo_url"
                        href="/"
                        size="sm"
                    />
                    <Button type="button" variant="ghost" size="icon" aria-label="Tutup menu" @click="mobileMenuOpen = false">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                    <div v-for="item in navigation" :key="item.href">
                        <Link
                            :href="item.href"
                            class="block rounded-xl px-3 py-3 text-sm font-semibold text-slate-800 transition hover:bg-teal-50 hover:text-teal-800"
                            @click="mobileMenuOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                        <div v-if="item.children" class="mb-2 ml-3 grid gap-1 border-l border-slate-200 pl-3">
                            <Link
                                v-for="child in item.children"
                                :key="child.href"
                                :href="child.href"
                                class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-teal-800"
                                @click="mobileMenuOpen = false"
                            >
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                </nav>

                <div class="space-y-3 border-t border-slate-200 px-4 py-4">
                    <Button :as="Link" href="/membership/apply" class="w-full" @click="mobileMenuOpen = false">
                        Mohon Jadi Ahli
                    </Button>

                    <div class="grid gap-2">
                        <Link
                            v-for="item in loginLinks"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center rounded-xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800"
                            @click="mobileMenuOpen = false"
                        >
                            <component :is="item.icon" class="mr-2 h-4 w-4 text-teal-700" />
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </aside>
        </div>

        <slot />

        <footer class="relative overflow-hidden border-t border-teal-900/20 bg-slate-950 text-slate-100">
            <div class="absolute inset-x-0 h-24 bg-gradient-to-b from-teal-400/10 to-transparent" />
            <div class="relative mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.15fr_1.85fr] lg:px-8">
                <div class="space-y-6">
                    <AppLogo
                        :name="cooperative.name || 'KoperasiHub'"
                        :logo-url="cooperative.logo_url"
                        href="/"
                        size="sm"
                    />
                    <p class="max-w-md text-sm leading-7 text-slate-300">
                        {{ footerText }}
                    </p>
                    <div class="grid gap-3 text-sm text-slate-300">
                        <div class="flex items-start gap-3">
                            <MapPin class="mt-1 h-4 w-4 shrink-0 text-teal-300" />
                            <span>{{ address || 'Alamat koperasi akan dikemas kini.' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Phone class="h-4 w-4 shrink-0 text-teal-300" />
                            <span>{{ contact.phone || 'Maklumat telefon akan dikemas kini' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Mail class="h-4 w-4 shrink-0 text-teal-300" />
                            <span>{{ contact.email || 'Maklumat e-mel akan dikemas kini' }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="group in footerGroups" :key="group.title">
                        <div class="flex items-center gap-2 text-sm font-semibold text-white">
                            <FileText class="h-4 w-4 text-teal-300" />
                            <span>{{ group.title }}</span>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <Link
                                v-for="item in group.links"
                                :key="item.href"
                                :href="item.href"
                                class="text-sm text-slate-300 transition-colors hover:text-white"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10">
                <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-5 text-sm text-slate-400 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <span>© {{ new Date().getFullYear() }} {{ cooperative.name || 'KoperasiHub' }}</span>
                    <div class="flex items-center gap-2 text-slate-400">
                        <Building2 class="h-4 w-4" />
                        <span>Laman awam berasaskan CMS</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <FlashToast />
</template>