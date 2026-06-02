<script setup>
import QRCode from 'qrcode';
import { computed, ref, watch } from 'vue';
import ProfileAvatar from '@/Shared/Components/ProfileAvatar.vue';

const props = defineProps({
    cooperative: {
        type: Object,
        required: true,
    },
    card: {
        type: Object,
        required: true,
    },
});

const qrCodeDataUrl = ref(null);

const stateBadge = computed(() => {
    if (!props.card.readiness?.has_profile_photo) {
        return {
            label: 'Belum aktif',
            classes: 'border-amber-200 bg-amber-100/90 text-amber-900',
        };
    }

    if (!props.card.readiness?.is_active) {
        return {
            label: props.card.membership_status_label,
            classes: 'border-red-200 bg-red-100/90 text-red-900',
        };
    }

    return {
        label: 'Kad aktif',
        classes: 'border-emerald-200 bg-emerald-100/90 text-emerald-900',
    };
});

const generateQrCode = async () => {
    qrCodeDataUrl.value = await QRCode.toDataURL(props.card.verification_url, {
        errorCorrectionLevel: 'H',
        margin: 1,
        width: 360,
        color: {
            dark: '#0f172a',
            light: '#ffffff',
        },
    });
};

watch(() => props.card.verification_url, () => {
    generateQrCode();
}, { immediate: true });
</script>

<template>
    <article class="relative w-full">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.24),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(191,219,254,0.24),_transparent_32%)]" />

        <div class="relative isolate overflow-hidden rounded-[2rem] border border-white/60 bg-gradient-to-br from-teal-700 via-cyan-700 to-blue-800 px-6 py-5 text-white shadow-[0_24px_60px_-28px_rgba(15,23,42,0.5)]">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(145deg,rgba(255,255,255,0.16),transparent_34%,transparent_65%,rgba(255,255,255,0.08))]" />
            <div class="pointer-events-none absolute -right-20 top-4 h-40 w-40 rounded-full bg-white/10 blur-3xl" />
            <div class="pointer-events-none absolute left-10 top-0 h-24 w-44 rounded-full bg-cyan-200/10 blur-3xl" />

            <div class="relative flex items-start justify-between gap-5">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-white/20 bg-white/12">
                            <img
                                v-if="cooperative.logo_url"
                                :src="cooperative.logo_url"
                                :alt="cooperative.name"
                                class="h-8 w-8 object-contain"
                            />
                            <span v-else class="text-lg font-semibold">KH</span>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/70">Kad Keahlian Digital</p>
                            <p class="truncate text-sm font-semibold text-white">{{ cooperative.short_name || cooperative.name }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex items-start gap-4">
                        <ProfileAvatar
                            :photo-url="card.profile_photo_url"
                            :name="card.full_name"
                            size="lg"
                        />

                        <div class="min-w-0 flex-1">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/65">Nama ahli</p>
                            <h2 class="mt-2 max-w-[16rem] truncate text-[1.7rem] font-semibold leading-tight text-white">
                                {{ card.full_name }}
                            </h2>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex rounded-full border px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em]"
                                    :class="stateBadge.classes"
                                >
                                    {{ stateBadge.label }}
                                </span>
                                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/85">
                                    No. ahli: {{ card.member_no }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-[154px] shrink-0">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-white/60">Sahkan keahlian</p>
                    <img
                        v-if="qrCodeDataUrl"
                        :src="qrCodeDataUrl"
                        alt="Kod QR Kad Ahli"
                        class="mt-3 h-[126px] w-[126px]"
                    />
                    <p class="mt-3 text-xs font-medium leading-5 text-white/80">
                        Imbas untuk paparan verifikasi awam.
                    </p>
                </div>
            </div>

            <div class="relative mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/16 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-white/60">Jenis</p>
                    <p class="mt-2 text-sm font-semibold text-white">{{ card.membership_type_label }}</p>
                </div>
                <div class="rounded-2xl border border-white/16 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-white/60">Status</p>
                    <p class="mt-2 text-sm font-semibold text-white">{{ card.membership_status_label }}</p>
                </div>
                <div class="rounded-2xl border border-white/16 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-white/60">Tarikh sertai</p>
                    <p class="mt-2 text-sm font-semibold text-white">{{ card.joined_at || '-' }}</p>
                </div>
            </div>
        </div>
    </article>
</template>