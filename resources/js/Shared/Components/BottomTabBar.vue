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
    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white lg:hidden" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
        <div class="flex h-16 items-center justify-around">
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-0.5"
                :class="isActive(tab.href) ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"
            >
                <component :is="tab.icon" class="h-5 w-5 flex-shrink-0" />
                <span class="text-[10px] font-medium leading-none">{{ tab.label }}</span>
            </Link>
        </div>
    </nav>
</template>
