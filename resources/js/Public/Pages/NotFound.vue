<script setup>
import { Head } from '@inertiajs/vue3';
import { ArrowLeft, SearchX } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
</script>

<template>
    <Head title="Halaman Tidak Dijumpai" />

    <PublicLayout>
        <main class="mx-auto flex min-h-[60vh] w-full max-w-4xl items-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="w-full rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm sm:p-12">
                <div class="mx-auto max-w-2xl text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                        <SearchX class="h-8 w-8" />
                    </div>
                    <div class="mt-6 space-y-4">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">404</p>
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                            Halaman yang anda cari tidak dijumpai
                        </h1>
                        <p class="text-base leading-7 text-slate-600">
                            Pautan ini mungkin belum diterbitkan atau kandungannya telah dialihkan. Sila kembali ke laman utama untuk meneruskan carian.
                        </p>
                    </div>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Button :as="'a'" href="/">Kembali ke Utama</Button>
                        <Button :as="'a'" href="/membership/apply" variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Mohon Jadi Ahli
                        </Button>
                    </div>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>
