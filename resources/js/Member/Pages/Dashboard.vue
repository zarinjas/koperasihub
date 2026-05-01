<script setup>
import { Head } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
</script>

<template>
    <Head title="Papan Pemuka Ahli" />

    <MemberLayout>
        <section class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-teal-700">Portal Ahli</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-normal">Papan Pemuka Ahli</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Ruang ringkas untuk ahli mengakses maklumat akaun dan kemas kini koperasi apabila modul seterusnya tersedia.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Status akaun</p>
                    <p class="mt-2 text-xl font-semibold">Aktif</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Kemas kini terkini</p>
                    <p class="mt-2 text-xl font-semibold">Belum tersedia</p>
                </article>
            </div>
        </section>
    </MemberLayout>
</template>
