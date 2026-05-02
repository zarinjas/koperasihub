<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CirclePlay, PhoneCall } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const contact = computed(() => page.props.appSettings?.contact ?? {});
const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});
const stats = computed(() => [
    { label: 'Nama koperasi', value: cooperative.value.name ?? 'KoperasiHub Demo' },
    { label: 'Telefon', value: contact.value.phone ?? 'Maklumat akan dikemas kini' },
    { label: 'E-mel', value: contact.value.email ?? 'Maklumat akan dikemas kini' },
]);
</script>

<template>
    <PublicSection
        :settings="{ ...settings, spacing: settings.spacing || 'xl', background: settings.background || 'gradient' }"
        container-class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8"
    >
        <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
            <div class="space-y-8">
                <div class="space-y-5">
                    <div
                        v-if="data.badge"
                        class="inline-flex items-center rounded-full border border-teal-200 bg-white/90 px-4 py-1.5 text-sm font-semibold text-teal-800 shadow-sm backdrop-blur"
                    >
                        {{ data.badge }}
                    </div>
                    <div class="space-y-4">
                        <h1 class="max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                            {{ data.title }}
                        </h1>
                        <p v-if="data.subtitle" class="max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                            {{ data.subtitle }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button v-if="data.primary_button_text && data.primary_button_url" :as="Link" :href="data.primary_button_url">
                        {{ data.primary_button_text }}
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                    <Button
                        v-if="data.secondary_button_text && data.secondary_button_url"
                        :as="Link"
                        :href="data.secondary_button_url"
                        variant="outline"
                    >
                        <CirclePlay class="mr-2 h-4 w-4" />
                        {{ data.secondary_button_text }}
                    </Button>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div
                        v-for="item in stats"
                        :key="item.label"
                        class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ item.label }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-900">{{ item.value }}</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 rounded-[2rem] bg-gradient-to-br from-teal-200/50 via-sky-100/40 to-white blur-3xl" />
                <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 p-8 text-white shadow-xl sm:p-10">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.26),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.22),_transparent_38%)]" />
                    <div class="relative space-y-8">
                        <div class="space-y-4">
                            <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-sm font-medium text-white/85">
                                Laman rasmi koperasi
                            </div>
                            <div class="space-y-3">
                                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                                    Maklumat utama disusun dengan lebih jelas untuk anggota dan pelawat.
                                </h2>
                                <p class="text-sm leading-7 text-slate-300 sm:text-base">
                                    Paparan awam ini dibina untuk memudahkan akses kepada perkhidmatan, pengumuman dan dokumen penting tanpa mengorbankan imej profesional koperasi.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                                <p class="text-sm font-semibold text-white">Akses pantas</p>
                                <ul class="mt-3 space-y-2 text-sm text-slate-200">
                                    <li>Maklumat perkhidmatan ahli</li>
                                    <li>Pengumuman rasmi koperasi</li>
                                    <li>Muat turun borang umum</li>
                                </ul>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-xl bg-white/10 p-2 text-white">
                                        <PhoneCall class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-white">Hubungi koperasi</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-200">{{ contact.phone || 'Maklumat telefon akan dikemas kini' }}</p>
                                        <p class="text-sm leading-6 text-slate-300">{{ contact.email || 'Maklumat e-mel akan dikemas kini' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicSection>
</template>
