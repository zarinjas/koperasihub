<script setup>
import { Link } from '@inertiajs/vue3';
import { Building2, LogIn } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3 font-semibold text-slate-950">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-700 text-white">
                        <Building2 class="h-5 w-5" />
                    </span>
                    <span>KoperasiHub</span>
                </Link>

                <nav class="flex items-center gap-2">
                    <Button as="a" href="/admin/login" variant="ghost">
                        <LogIn class="mr-2 h-4 w-4" />
                        Admin
                    </Button>
                    <Button as="a" href="/member/login">
                        Portal Ahli
                    </Button>
                </nav>
            </div>
        </header>

        <slot />
    </div>
</template>
