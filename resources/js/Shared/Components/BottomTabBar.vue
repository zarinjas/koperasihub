<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, FileCheck, HandCoins, Home, UserRound } from 'lucide-vue-next';

const page = usePage();

const tabs = [
    { label: 'Utama', href: '/member/dashboard', icon: Home },
    { label: 'Pembiayaan', href: '/member/financing', icon: HandCoins },
    { label: 'Borang', href: '/member/applications', icon: FileCheck },
    { label: 'Kad', href: '/member/card', icon: CreditCard },
    { label: 'Profil', href: '/member/profile', icon: UserRound },
];

const currentUrl = page.url;

const isActive = (href) => {
    if (href === '/member/dashboard') {
        return currentUrl === '/member/dashboard';
    }
    return currentUrl.startsWith(href);
};
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-100 bg-white/95 shadow-[0_-2px_12px_rgba(0,0,0,0.06)] backdrop-blur-lg lg:hidden" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
        <div class="flex h-16 items-center justify-around">
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="relative flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-1 transition"
            >
                <div class="relative flex flex-col items-center gap-0.5">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full transition"
                        :class="isActive(tab.href) ? 'text-teal-600' : 'text-slate-400'"
                    >
                        <component :is="tab.icon" class="h-5 w-5" />
                    </div>
                    <span
                        class="text-[10px] font-medium leading-none transition"
                        :class="isActive(tab.href) ? 'font-semibold text-teal-700' : 'text-slate-400'"
                    >
                        {{ tab.label }}
                    </span>
                    <span
                        v-if="isActive(tab.href)"
                        class="absolute -top-0.5 left-1/2 h-1 w-5 -translate-x-1/2 rounded-full bg-teal-500"
                    />
                </div>
            </Link>
        </div>
    </nav>
</template>
