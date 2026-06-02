<script setup>
import { Head } from '@inertiajs/vue3';
import { CheckCircle2, Home } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';

defineProps({
    application_no: { type: String, required: true },
    applicant_name: { type: String, required: true },
    submitted_at: { type: String, required: true },
});
</script>

<template>
    <Head title="Permohonan Dihantar" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center gap-8 px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <CheckCircle2 class="h-10 w-10" />
                </span>

                <div class="space-y-3 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                        Permohonan Berjaya Dihantar
                    </h1>
                    <p class="text-sm leading-6 text-slate-600">
                        Terima kasih, <span class="font-semibold text-slate-900">{{ applicant_name }}</span>. Permohonan keahlian anda telah diterima.
                    </p>
                </div>

                <div class="w-full space-y-4 rounded-3xl border border-emerald-100 bg-white/90 p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-600">No. Permohonan</span>
                        <span class="font-mono text-sm font-semibold text-slate-950">{{ application_no }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-600">Tarikh dihantar</span>
                        <span class="text-sm text-slate-900">{{ submitted_at }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Anggaran pemprosesan</span>
                        <span class="text-sm font-medium text-emerald-700">3 hari bekerja</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50/80 p-5 text-center">
                    <p class="text-sm leading-6 text-blue-900">
                        Permohonan anda akan disemak dalam tempoh <strong>3 hari bekerja</strong>.
                        Anda akan menerima notifikasi melalui e-mel setelah permohonan diluluskan atau jika terdapat sebarang maklumat tambahan diperlukan.
                    </p>
                </div>

                <a
                    href="/"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800"
                >
                    <Home class="h-4 w-4" />
                    Kembali ke Laman Utama
                </a>
            </div>
        </section>
    </PublicLayout>
</template>