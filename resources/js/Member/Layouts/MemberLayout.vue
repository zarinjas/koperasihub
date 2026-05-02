<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building2, ClipboardList, FileText, Home, LogOut, MessagesSquare, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const navItems = computed(() => page.props.navigation?.member ?? []);
const currentUrl = computed(() => page.url);
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_path);

const icons = {
    ClipboardList,
    FileText,
    Home,
    MessagesSquare,
    UserRound,
};

const isActive = (href) => currentUrl.value === new URL(href, window.location.origin).pathname;

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <Link href="/member/dashboard" class="flex items-center gap-3 font-semibold">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                        <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-7 w-7 rounded object-contain" />
                        <Building2 v-else class="h-5 w-5" />
                    </span>
                    <span>{{ cooperativeName }}</span>
                </Link>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ user?.name }}</p>
                        <p class="text-xs text-slate-500">Akaun ahli</p>
                    </div>
                    <Button type="button" variant="outline" @click="logout">
                        <LogOut class="mr-2 h-4 w-4" />
                        Log Keluar
                    </Button>
                </div>
            </div>
        </header>

        <div class="mx-auto grid w-full max-w-6xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[220px_1fr] lg:px-8">
            <nav class="flex gap-2 overflow-x-auto lg:block lg:space-y-2">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex shrink-0 items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium"
                    :class="isActive(item.href) ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-white hover:text-slate-950'"
                >
                    <component :is="icons[item.icon] ?? Home" class="h-4 w-4" />
                    {{ item.label }}
                </Link>
            </nav>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
