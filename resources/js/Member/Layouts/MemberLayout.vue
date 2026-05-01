<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Home, LogOut, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/Shared/Components/ui/button';

const page = usePage();
const user = computed(() => page.props.auth?.user);

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
                        <UserRound class="h-5 w-5" />
                    </span>
                    <span>Portal Ahli</span>
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
                    href="/member/dashboard"
                    class="flex shrink-0 items-center gap-2 rounded-lg bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800"
                >
                    <Home class="h-4 w-4" />
                    Papan Pemuka
                </Link>
                <span class="flex shrink-0 items-center gap-2 rounded-lg px-4 py-3 text-sm text-slate-500">
                    <FileText class="h-4 w-4" />
                    Dokumen
                </span>
            </nav>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
