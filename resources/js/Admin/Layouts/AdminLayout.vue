<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    ChartNoAxesColumnIncreasing,
    ClipboardCheck,
    ClipboardList,
    ChevronDown,
    HandCoins,
    FileCheck,
    Files,
    History,
    Image,
    ImagePlay,
    Inbox,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    MessagesSquare,
    Newspaper,
    PanelsTopLeft,
    PiggyBank,
    Search,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import NotificationBell from '@/Shared/Components/NotificationBell.vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const sidebarOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const navItems = computed(() => page.props.navigation?.admin ?? []);
const currentUrl = computed(() => page.url);
const currentPath = computed(() => currentUrl.value.split('?')[0]);
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'KoperasiHub');
const logoPath = computed(() => cooperative.value.logo_url);

const staffLabel = computed(() => {
    const parts = [];
    if (user.value?.unit_name) parts.push(user.value.unit_name);
    if (user.value?.staff_id) parts.push(user.value.staff_id);
    return parts.length ? parts.join(' \u00B7 ') : null;
});

const icons = {
    BriefcaseBusiness,
    CalendarDays,
    ChartNoAxesColumnIncreasing,
    ClipboardCheck,
    ClipboardList,
    ChevronDown,
    FileCheck,
    HandCoins,
    Files,
    History,
    Image,
    ImagePlay,
    Inbox,
    LayoutDashboard,
    Megaphone,
    MessagesSquare,
    Newspaper,
    PanelsTopLeft,
    PiggyBank,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
};

const pathFor = (href) => new URL(href, window.location.origin).pathname;

const matchesPattern = (pattern) => {
    if (!pattern) return false;
    const escaped = pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replaceAll('*', '.*');
    return new RegExp(`^${escaped}$`).test(currentPath.value);
};

const isItemActive = (item) => {
    if (Array.isArray(item.active_patterns) && item.active_patterns.some(matchesPattern)) return true;
    if (item.href && currentPath.value === pathFor(item.href)) return true;
    if (Array.isArray(item.children)) return item.children.some(isItemActive);
    return false;
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="relative min-h-screen bg-slate-100 text-slate-950">
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 overflow-y-auto border-r border-slate-200 bg-white lg:block">
            <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                    <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-7 w-7 rounded object-contain" />
                    <Building2 v-else class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold">{{ cooperativeName }}</p>
                    <p class="text-xs text-slate-500">Panel Admin</p>
                </div>
            </div>

            <nav class="space-y-1 px-4 py-5">
                <div v-for="item in navItems" :key="item.label" class="space-y-1">
                    <Link
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium"
                        :class="isItemActive(item) ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                    >
                        <component :is="icons[item.icon] ?? LayoutDashboard" class="h-4 w-4" />
                        <span class="flex-1">{{ item.label }}</span>
                        <span v-if="item.badge > 0" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">{{ item.badge > 99 ? '99+' : item.badge }}</span>
                        <ChevronDown v-if="item.children?.length" class="h-4 w-4" :class="isItemActive(item) ? 'rotate-180' : ''" />
                    </Link>
                    <div v-if="item.children?.length" class="ml-4 space-y-1 border-l border-slate-200 pl-3">
                        <Link v-for="child in item.children" :key="child.label" :href="child.href" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium" :class="isItemActive(child) ? 'bg-teal-50 text-teal-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950'">
                            <span class="flex-1">{{ child.label }}</span>
                            <span v-if="child.badge > 0" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-50 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false">
            <aside class="ml-auto flex h-full w-full max-w-xs flex-col border-l border-slate-200 bg-white shadow-xl" @click.stop>
                <div class="flex h-16 items-center justify-between gap-3 border-b border-slate-200 px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                            <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-7 w-7 rounded object-contain" />
                            <Building2 v-else class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold">{{ cooperativeName }}</p>
                            <p class="text-xs text-slate-500">Panel Admin</p>
                        </div>
                    </div>
                    <Button type="button" variant="ghost" size="icon" @click="sidebarOpen = false">
                        <X class="h-5 w-5" />
                    </Button>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                    <div v-for="item in navItems" :key="item.label" class="space-y-1">
                        <Link :href="item.href" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium" :class="isItemActive(item) ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'" @click="sidebarOpen = false">
                            <component :is="icons[item.icon] ?? LayoutDashboard" class="h-4 w-4" />
                            <span class="flex-1">{{ item.label }}</span>
                            <span v-if="item.badge > 0" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">{{ item.badge > 99 ? '99+' : item.badge }}</span>
                        </Link>
                    </div>
                </nav>
                <div class="border-t border-slate-200 px-4 py-4">
                    <div class="mb-4 text-sm">
                        <p class="font-medium text-slate-950">{{ user?.name }}</p>
                        <p class="text-slate-500">{{ user?.email }}</p>
                    </div>
                    <Button type="button" variant="outline" class="w-full" @click="logout">
                        <LogOut class="mr-2 h-4 w-4" />
                        Log Keluar
                    </Button>
                </div>
            </aside>
        </div>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <Button type="button" variant="ghost" class="px-3 lg:hidden" @click="sidebarOpen = true">
                            <Menu class="h-5 w-5" />
                        </Button>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">Papan Pemuka</p>
                            <p v-if="staffLabel" class="hidden truncate text-xs text-slate-500 sm:block">{{ staffLabel }}</p>
                            <p v-else class="hidden truncate text-xs text-slate-500 sm:block">Ringkasan operasi asas</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <NotificationBell />
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium">{{ user?.name }}</p>
                            <p class="text-xs text-slate-500">{{ staffLabel || user?.email }}</p>
                        </div>
                        <Button type="button" variant="outline" class="hidden sm:inline-flex" @click="logout">
                            <LogOut class="mr-2 h-4 w-4" />
                            Log Keluar
                        </Button>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
