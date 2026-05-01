<script setup>
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    area: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <main class="min-h-screen bg-gradient-to-br from-emerald-50 via-slate-50 to-blue-50">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-8 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <p class="text-sm font-medium text-teal-700">KoperasiHub</p>
                    <p class="text-xs text-slate-500">{{ area }}</p>
                </div>
                <nav class="flex flex-wrap justify-end gap-2">
                    <Button
                        v-for="link in links"
                        :key="link.href"
                        as="a"
                        :href="link.href"
                        variant="outline"
                    >
                        {{ link.label }}
                    </Button>
                </nav>
            </header>

            <section class="grid flex-1 items-center gap-8 py-12 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <div class="inline-flex rounded-full border border-teal-100 bg-white px-3 py-1 text-sm font-medium text-teal-700 shadow-sm">
                        Phase 0 foundation
                    </div>
                    <div class="space-y-4">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">
                            {{ title }}
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-slate-600">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <div class="h-2 w-20 rounded-full bg-teal-700"></div>
                        <div class="space-y-2">
                            <div class="h-3 rounded-full bg-slate-100"></div>
                            <div class="h-3 w-5/6 rounded-full bg-slate-100"></div>
                            <div class="h-3 w-2/3 rounded-full bg-slate-100"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-3 h-8 w-8 rounded-lg bg-blue-100"></div>
                                <div class="h-2 rounded-full bg-slate-200"></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-3 h-8 w-8 rounded-lg bg-amber-100"></div>
                                <div class="h-2 rounded-full bg-slate-200"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
