<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Building, LogIn, Mail, MapPin, MenuSquare, Phone } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/Shared/Components/AppLogo.vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const contact = computed(() => page.props.appSettings?.contact ?? {});

const navigation = [
    { label: 'Utama', href: '/' },
    { label: 'Tentang Kami', href: '/tentang-kami' },
    { label: 'Perkhidmatan', href: '/perkhidmatan' },
    { label: 'Permohonan Ahli', href: '/membership/apply' },
    { label: 'Perniagaan', href: '/perniagaan' },
    { label: 'Pengumuman', href: '/pengumuman' },
    { label: 'Muat Turun', href: '/muat-turun' },
    { label: 'Hubungi Kami', href: '/hubungi' },
];

const footerGroups = [
    {
        title: 'Koperasi',
        links: [
            { label: 'Tentang Kami', href: '/tentang-kami' },
            { label: 'Lembaga & Pengurusan', href: '/lembaga-pengurusan' },
            { label: 'Tadbir Urus', href: '/tadbir-urus' },
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
            { label: 'Kedai Koperasi', href: '/perniagaan/kedai-koperasi' },
            { label: 'Hartanah & Sewaan', href: '/perniagaan/hartanah-sewaan' },
            { label: 'Stesen Minyak', href: '/perniagaan/stesen-minyak' },
            { label: 'E-Dagang', href: '/perniagaan/e-dagang' },
            { label: 'Bilik Seminar', href: '/perniagaan/bilik-seminar' },
        ],
    },
    {
        title: 'Sumber',
        links: [
            { label: 'Permohonan Ahli', href: '/membership/apply' },
            { label: 'Pengumuman', href: '/pengumuman' },
            { label: 'Muat Turun Borang', href: '/muat-turun' },
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
    <div class="min-h-screen bg-white text-slate-950">
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center justify-between gap-4">
                        <AppLogo
                            :name="cooperative.name || 'KoperasiHub'"
                            :logo-url="cooperative.logo_path"
                            href="/"
                            size="md"
                        />
                        <div class="hidden items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600 xl:flex">
                            <Phone class="h-4 w-4 text-teal-700" />
                            <span>{{ contact.phone || 'Maklumat telefon akan dikemas kini' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 lg:items-end">
                        <nav class="flex flex-wrap items-center gap-1">
                            <Link
                                v-for="item in navigation"
                                :key="item.href"
                                :href="item.href"
                                class="rounded-full px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950"
                            >
                                {{ item.label }}
                            </Link>
                        </nav>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button :as="Link" href="/membership/apply" variant="outline">
                                Mohon Jadi Ahli
                            </Button>
                            <Button :as="Link" href="/admin/login" variant="ghost">
                                <LogIn class="mr-2 h-4 w-4" />
                                Admin
                            </Button>
                            <Button :as="Link" href="/member/login">Portal Ahli</Button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <slot />

        <footer class="border-t border-slate-200 bg-slate-950 text-slate-100">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.15fr_1.85fr] lg:px-8">
                <div class="space-y-6">
                    <AppLogo
                        :name="cooperative.name || 'KoperasiHub'"
                        :logo-url="cooperative.logo_path"
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
                            <MenuSquare class="h-4 w-4 text-teal-300" />
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
                        <Building class="h-4 w-4" />
                        <span>Laman awam berasaskan CMS</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
