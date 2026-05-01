<script setup>
import { Head } from '@inertiajs/vue3';
import { FileText, ShieldCheck, Users } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';

const cards = [
    { label: 'Pengguna admin', value: 'Aktif', icon: ShieldCheck },
    { label: 'Akaun ahli', value: 'Dilindungi', icon: Users },
    { label: 'Layout asas', value: 'Sedia', icon: FileText },
];
</script>

<template>
    <Head title="Papan Pemuka Admin" />

    <AdminLayout>
        <section class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Papan Pemuka Admin</h1>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Ringkasan asas untuk mengesahkan akses admin dan susun atur panel.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="card in cards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <component :is="card.icon" class="h-5 w-5 text-teal-700" />
                    <p class="mt-4 text-sm text-slate-500">{{ card.label }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ card.value }}</p>
                </article>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold">Ruang kerja admin</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Modul CMS, ahli, dokumen, aduan, dan tetapan akan ditambah dalam fasa kemudian mengikut roadmap.
                </p>
            </div>
        </section>
    </AdminLayout>
</template>
