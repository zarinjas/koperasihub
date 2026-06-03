<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileText, Megaphone, ShieldCheck, UsersRound } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import { Button } from '@/Shared/Components/ui/button';


const stats = [
    { value: 'Online', label: 'Urusan ahli' },
    { value: 'Rasmi', label: 'Pengumuman' },
    { value: 'Mudah', label: 'Muat turun dokumen' },
];

const cards = [
    {
        title: 'Keanggotaan',
        description: 'Maklumat permohonan dan status ahli dipaparkan dalam aliran yang jelas.',
        icon: UsersRound,
    },
    {
        title: 'Muat Turun',
        description: 'Dokumen rujukan koperasi boleh disusun untuk capaian awam dan dimuat turun dengan mudah.',
        icon: FileText,
    },
    {
        title: 'Hebahan rasmi',
        description: 'Pengumuman penting boleh dibaca oleh anggota dan pelawat dengan mudah.',
        icon: Megaphone,
    },
];
</script>

<template>
    <Head title="Laman Utama" />

    <PublicLayout>
        <main class="bg-[radial-gradient(circle_at_top_left,_rgba(15,118,110,0.16),_transparent_34%),linear-gradient(135deg,_#ecfdf5_0%,_#eff6ff_52%,_#ffffff_100%)]">
            <section class="mx-auto grid w-full max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1.02fr_0.98fr] lg:px-8 lg:py-24">
                <div class="space-y-8">
                    <div class="inline-flex items-center rounded-full border border-teal-200 bg-white/90 px-4 py-1.5 text-sm font-semibold text-teal-800 shadow-sm">
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        Platform laman koperasi
                    </div>
                    <div class="space-y-4">
                        <h1 class="max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                            Koperasi moden untuk keperluan anggota
                        </h1>
                        <p class="max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                            Akses maklumat keanggotaan, perkhidmatan, pengumuman dan dokumen rujukan koperasi melalui satu laman rasmi yang mudah digunakan.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Button :as="Link" href="/membership/apply">
                            Mohon Jadi Ahli
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button :as="Link" href="/perkhidmatan" variant="outline">Lihat Perkhidmatan</Button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div v-for="item in stats" :key="item.label" class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm">
                            <p class="text-2xl font-semibold tracking-tight text-slate-950">{{ item.value }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-600">{{ item.label }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute inset-4 rounded-[2rem] bg-gradient-to-br from-teal-200/60 via-sky-100/60 to-white blur-3xl" />
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/80 p-4 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-5">
                        <div class="grid gap-4 rounded-[1.65rem] border border-slate-200/80 bg-white/90 p-5 sm:p-6">
                            <div class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 to-blue-50 p-6">
                                <p class="text-sm font-semibold text-teal-800">Laman rasmi koperasi</p>
                                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
                                    Maklumat utama disusun dengan kemas untuk anggota dan pelawat.
                                </h2>
                                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">
                                    Paparan awam ini bersedia untuk kandungan CMS apabila halaman utama diterbitkan.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div
                                    v-for="(item, index) in cards"
                                    :key="item.title"
                                    class="min-h-40 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                                    :class="index === 0 ? 'sm:col-span-2' : ''"
                                >
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                                        <component :is="item.icon" class="h-5 w-5" />
                                    </div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-950">{{ item.title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>