<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building2, Calculator, CalendarCheck, CalendarDays, CreditCard, FileCheck, Files, HandCoins, Home, ImagePlay, LogOut, Megaphone, Menu, MessagesSquare, PiggyBank, UserRound, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BottomTabBar from '@/Shared/Components/BottomTabBar.vue';
import ProfileAvatar from '@/Shared/Components/ProfileAvatar.vue';
import NotificationBell from '@/Shared/Components/NotificationBell.vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const sidebarOpen = ref(false);
const user = computed(() => page.props.auth?.user);
const navItems = computed(() => page.props.navigation?.member ?? []);
const currentUrl = computed(() => page.url);
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_url);

const icons = {
    Calculator,
    CalendarCheck,
    CalendarDays,
    CreditCard,
    FileCheck,
    Files,
    HandCoins,
    Home,
    ImagePlay,
    Megaphone,
    MessagesSquare,
    PiggyBank,
    UserRound,
};

const isActive = (href) => currentUrl.value === new URL(href, window.location.origin).pathname;

const pageTitle = computed(() => {
    const titles = {
        'Member/Pages/Dashboard': 'Papan Pemuka',
        'Member/Pages/Card': 'Kad Digital',
        'Member/Pages/Profile': 'Profil Saya',
        'Member/Pages/Applications/Index': 'Permohonan',
        'Member/Pages/Applications/Show': 'Permohonan',
        'Member/Pages/Forms/Index': 'Borang',
        'Member/Pages/Financing/Index': 'Pembiayaan',
        'Member/Pages/Financing/ProductShow': 'Pembiayaan',
        'Member/Pages/Financing/Calculator': 'Kalkulator Pembiayaan',
        'Member/Pages/Financing/Applications/Index': 'Pembiayaan',
        'Member/Pages/Financing/Applications/Create': 'Pembiayaan',
        'Member/Pages/Financing/Applications/Show': 'Pembiayaan',
        'Member/Pages/Financing/Applications/Print': 'Pembiayaan',
        'Member/Pages/Financing/GuarantorRequests/Index': 'Penjamin',
        'Member/Pages/Financing/GuarantorRequests/Show': 'Penjamin',
        'Member/Pages/Announcements/Index': 'Pengumuman',
        'Member/Pages/Complaints/Index': 'Aduan',
        'Member/Pages/Complaints/Create': 'Aduan',
        'Member/Pages/Complaints/Show': 'Aduan',
        'Member/Pages/Documents/Index': 'Dokumen',
        'Member/Pages/Caruman/Index': 'Caruman Saya',
        'Member/Pages/Programs/Index': 'Program',
        'Member/Pages/Programs/Show': 'Program',
        'Member/Pages/Programs/CheckIn': 'Daftar Masuk',
        'Member/Pages/Attendance/Index': 'Kehadiran Saya',
        'Member/Pages/Placeholder': 'Portal Ahli',
    };
    return titles[page.component] ?? 'Portal Ahli';
});

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="relative min-h-screen bg-slate-50 text-slate-950">
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 overflow-y-auto border-r border-slate-200 bg-white lg:block">
            <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6">
                <Link href="/member/dashboard" class="flex items-center gap-3 font-semibold">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                        <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-7 w-7 rounded object-contain" />
                        <Building2 v-else class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold">{{ cooperativeName }}</p>
                        <p class="text-xs text-slate-500">Portal Ahli</p>
                    </div>
                </Link>
            </div>

            <nav class="space-y-1 px-4 py-5">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium"
                    :class="isActive(item.href) ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                >
                    <component :is="icons[item.icon] ?? Home" class="h-4 w-4" />
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-50 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false">
            <aside class="ml-auto flex h-full w-full max-w-xs flex-col border-l border-slate-200 bg-white shadow-xl" @click.stop>
                <div class="flex h-16 items-center justify-between border-b border-slate-200 px-6">
                    <Link href="/member/dashboard" class="flex items-center gap-3 font-semibold" @click="sidebarOpen = false">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                            <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-7 w-7 rounded object-contain" />
                            <Building2 v-else class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold">{{ cooperativeName }}</p>
                            <p class="text-xs text-slate-500">Portal Ahli</p>
                        </div>
                    </Link>
                    <Button type="button" variant="ghost" size="icon" @click="sidebarOpen = false">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium"
                        :class="isActive(item.href) ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                        @click="sidebarOpen = false"
                    >
                        <component :is="icons[item.icon] ?? Home" class="h-4 w-4" />
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="border-t border-slate-200 px-4 py-4">
                    <div class="mb-4 flex items-center gap-3 text-sm">
                        <ProfileAvatar :photo-url="user?.profile_photo_url" :name="user?.name" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-950">{{ user?.name }}</p>
                            <p class="text-slate-500">Akaun ahli</p>
                        </div>
                    </div>
                    <Button type="button" variant="outline" class="w-full" @click="logout">
                        <LogOut class="mr-2 h-4 w-4" />
                        Log Keluar
                    </Button>
                </div>
            </aside>
        </div>

        <div class="lg:pl-72">
            <!-- Mobile header: centered title + hamburger -->
            <header class="sticky top-0 z-30 flex min-h-14 items-center justify-center border-b border-slate-200 bg-white/95 backdrop-blur lg:hidden">
                <Button type="button" variant="ghost" size="icon" class="absolute left-2" @click="sidebarOpen = true">
                    <Menu class="h-5 w-5" />
                </Button>
                <p class="truncate px-12 text-sm font-semibold text-slate-950">{{ pageTitle }}</p>
            </header>

            <!-- Desktop header -->
            <header class="sticky top-0 z-30 hidden border-b border-slate-200 bg-white/95 backdrop-blur lg:block">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">Portal Ahli</p>
                            <p class="hidden truncate text-xs text-slate-500 sm:block">Akses maklumat dan urusan keahlian anda</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <NotificationBell />
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium">{{ user?.name }}</p>
                            <p class="text-xs text-slate-500">Akaun ahli</p>
                        </div>
                        <Button type="button" variant="outline" class="hidden sm:inline-flex" @click="logout">
                            <LogOut class="mr-2 h-4 w-4" />
                            Log Keluar
                        </Button>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 pb-20 sm:px-6 lg:px-8 lg:pb-6">
                <slot />
            </main>

            <BottomTabBar />
        </div>
    </div>
</template>
