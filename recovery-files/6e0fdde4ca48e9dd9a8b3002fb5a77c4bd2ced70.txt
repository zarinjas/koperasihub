<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    application: { type: Object, required: true },
});
</script>

<template>
    <MemberLayout>
        <Head title="Permohonan Diterima" />
        <section class="mx-auto max-w-lg space-y-6 py-12">
            <div class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-green-50 p-8 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm">
                    <CheckCircle class="h-8 w-8" />
                </div>
                <h1 class="mt-5 text-2xl font-bold text-slate-950">Permohonan Diterima</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Permohonan Ansuran Mudah anda telah diterima dan akan diproses dalam tempoh
                    <strong class="text-slate-950">7 hari bekerja</strong>. Anda boleh menyemak status
                    permohonan di halaman <strong class="text-slate-950">Permohonan Saya</strong>.
                </p>

                <div class="mt-6 rounded-xl bg-white/90 p-4 text-left text-sm shadow-sm">
                    <div class="space-y-2">
                        <div class="flex justify-between"><span class="text-slate-500">No Permohonan</span><span class="font-medium text-slate-950">{{ application.application_no }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Produk</span><span class="text-slate-950">{{ application.product_name }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Varian</span><span class="text-slate-950">{{ application.variant_name }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Bayaran Bulanan</span><span class="font-medium text-slate-950">RM {{ Number(application.monthly_amount).toFixed(2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Tempoh</span><span class="text-slate-950">{{ application.tenure_months }} Bulan</span></div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <Button :as="Link" :href="'/member/ansuran/applications/' + application.id">Semak Status</Button>
                    <Button :as="Link" :href="'/member/ansuran'" variant="outline">Kembali ke Katalog</Button>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>
