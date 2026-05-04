<script setup>
import { Head } from '@inertiajs/vue3';
import { ClipboardCheck, Files, MessagesSquare, Users } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
});

const icons = {
    ClipboardCheck,
    Files,
    MessagesSquare,
    Users,
};
</script>

<template>
    <Head title="Papan Pemuka Admin" />

    <AdminLayout>
        <section class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Papan Pemuka Admin</h1>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Ringkasan modul utama untuk semakan cepat semasa demo dan operasi asas pentadbiran.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in stats"
                    :key="card.label"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                        <component :is="icons[card.icon] ?? Users" class="h-5 w-5" />
                    </div>
                    <p class="mt-4 text-sm text-slate-500">{{ card.label }}</p>
                    <p class="mt-1 text-3xl font-semibold">{{ card.value }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ card.description }}</p>
                </article>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold">Ruang kerja admin</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Gunakan menu sebelah untuk mengurus CMS, ahli, permohonan, dokumen, pengumuman, aduan, tetapan dan log audit dengan susun atur yang konsisten.
                    </p>
                </div>

                <div class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 via-white to-blue-50 p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Status demo</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data benih telah disusun untuk menunjukkan aliran kerja admin secara terus tanpa konfigurasi tambahan.
                    </p>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
