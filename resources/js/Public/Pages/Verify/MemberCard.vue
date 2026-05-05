<script setup>
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, BadgeCheck, Ban, ShieldCheck } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import AppLogo from '@/Shared/Components/AppLogo.vue';
import ProfileAvatar from '@/Shared/Components/ProfileAvatar.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    isValid: {
        type: Boolean,
        required: true,
    },
    verification: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Semakan Kad Ahli" />

    <PublicLayout>
        <section class="bg-gradient-to-b from-teal-50 via-white to-slate-50 py-16 sm:py-20">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div v-if="isValid && verification" class="space-y-6">
                    <div class="mx-auto max-w-3xl text-center">
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm font-semibold text-emerald-800">
                            <ShieldCheck class="mr-2 h-4 w-4" />
                            Verifikasi kad berjaya
                        </span>
                        <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Semakan Kad Keahlian Digital</h1>
                        <p class="mt-3 text-base leading-7 text-slate-600">
                            Paparan ini menunjukkan maklumat semakan awam yang selamat untuk pengesahan status keahlian.
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <AppLogo
                                :name="verification.cooperative.full_name || verification.cooperative.name"
                                :logo-url="verification.cooperative.logo_url"
                                href="/"
                                size="md"
                            />
                            <StatusBadge :status="verification.member.membership_status" />
                        </div>

                        <div class="mt-8 grid gap-6 lg:grid-cols-[0.7fr_1.3fr]">
                            <div class="flex flex-col items-center rounded-3xl border border-slate-200 bg-slate-50 p-6 text-center">
                                <ProfileAvatar
                                    :photo-url="verification.member.profile_photo_url"
                                    :name="verification.member.full_name"
                                    size="xl"
                                />
                                <p class="mt-4 text-lg font-semibold text-slate-950">{{ verification.member.full_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ verification.member.member_no }}</p>

                                <div
                                    v-if="verification.member.is_inactive"
                                    class="mt-4 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-800"
                                >
                                    <Ban class="mr-2 h-4 w-4" />
                                    Status keahlian terhad
                                </div>
                                <div
                                    v-else
                                    class="mt-4 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800"
                                >
                                    <BadgeCheck class="mr-2 h-4 w-4" />
                                    Status keahlian aktif
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama ahli</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ verification.member.full_name }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. ahli</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ verification.member.member_no }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jenis keahlian</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ verification.member.membership_type_label }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status keahlian</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ verification.member.membership_status_label }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:col-span-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh sertai</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ verification.member.joined_at || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                            Maklumat sensitif seperti nombor kad pengenalan penuh, alamat, telefon, e-mel, dan dokumen peribadi tidak dipaparkan pada halaman verifikasi ini.
                        </div>
                    </div>
                </div>

                <div v-else class="mx-auto max-w-2xl rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/60 sm:p-10">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-700">
                        <AlertTriangle class="h-8 w-8" />
                    </span>
                    <h1 class="mt-6 text-3xl font-semibold text-slate-950">Pautan verifikasi tidak sah</h1>
                    <p class="mt-3 text-base leading-7 text-slate-600">
                        Pautan kad ahli ini tidak ditemui atau telah tidak lagi sah untuk digunakan.
                    </p>
                    <div class="mt-8">
                        <Button as="a" href="/">Kembali ke laman utama</Button>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
