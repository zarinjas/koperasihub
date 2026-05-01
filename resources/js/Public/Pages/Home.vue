<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
</script>

<template>
    <Head title="Laman Utama" />

    <PublicLayout>
        <main class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <section class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-6">
                    <div class="inline-flex rounded-full border border-teal-100 bg-white px-3 py-1 text-sm font-medium text-teal-700 shadow-sm">
                        Platform pengurusan koperasi
                    </div>
                    <div class="space-y-4">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">
                            Asas laman awam, panel admin, dan portal ahli sudah tersedia.
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-slate-600">
                            KoperasiHub dibina sebagai platform putih label untuk koperasi yang memerlukan struktur digital yang kemas dan mudah dikembangkan.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Button as="a" href="/admin/login">Log Masuk Admin</Button>
                        <Button as="a" href="/member/login" variant="outline">Log Masuk Ahli</Button>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-950">Status Fasa 1</p>
                                <p class="text-sm text-slate-500">Autentikasi dan layout asas</p>
                            </div>
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-800">Aktif</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-950">Panel Admin</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Akses dilindungi untuk pengguna pentadbiran.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-950">Portal Ahli</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Akses berasingan untuk akaun ahli.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
