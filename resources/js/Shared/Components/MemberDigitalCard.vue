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
    size: {
        type: String,
        default: 'default',
    },
    fluidOnMobile: {
        type: Boolean,
        default: false,
    },
});

const qrCodeDataUrl = ref(null);

const sizeMap = {
    compact: {
        shell: 'max-w-[320px] p-5',
        name: 'text-2xl',
        meta: 'text-[11px]',
        value: 'text-sm',
        qr: 'h-28 w-28',
        footer: 'text-[10px]',
        avatar: 'lg',
    },
    default: {
        shell: 'max-w-[360px] p-6',
        name: 'text-[1.85rem]',
        meta: 'text-xs',
        value: 'text-sm',
        qr: 'h-36 w-36',
        footer: 'text-[11px]',
        avatar: 'xl',
    },
    large: {
        shell: 'max-w-[420px] p-7',
        name: 'text-[2.1rem]',
        meta: 'text-xs',
        value: 'text-base',
        qr: 'h-40 w-40',
        footer: 'text-xs',
        avatar: 'xl',
    },
};

const currentSize = computed(() => sizeMap[props.size] ?? sizeMap.default);

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

const shellClasses = computed(() => [
    'relative isolate overflow-hidden rounded-[2rem] border border-white/50 bg-gradient-to-br from-teal-700 via-cyan-700 to-blue-800 text-white shadow-xl shadow-cyan-950/20',
    currentSize.value.shell,
    props.fluidOnMobile ? 'max-w-none sm:max-w-[320px]' : '',
    props.card.readiness?.is_active ? '' : 'grayscale-[0.1]',
]);

const generateQrCode = async () => {
    qrCodeDataUrl.value = await QRCode.toDataURL(props.card.verification_url, {
        errorCorrectionLevel: 'H',
        margin: 1,
        width: 420,
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
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.28),_transparent_42%),radial-gradient(circle_at_bottom_right,_rgba(191,219,254,0.32),_transparent_34%)]" />
        <div class="pointer-events-none absolute inset-x-5 top-5 h-24 rounded-full bg-white/10 blur-3xl" />

        <div :class="shellClasses">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(155deg,rgba(255,255,255,0.16),transparent_32%,transparent_68%,rgba(255,255,255,0.08))]" />

            <div class="relative flex h-full flex-col">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-white/20 bg-white/12">
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
                    </div>

                    <ProfileAvatar
                        :photo-url="card.profile_photo_url"
                        :name="card.full_name"
                        :size="currentSize.avatar"
                    />
                </div>

                <div class="mt-7">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/65">Nama ahli</p>
                    <h2 class="mt-3 max-w-[11rem] truncate font-semibold leading-tight text-white" :class="currentSize.name">
                        {{ card.full_name }}
                    </h2>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/16 bg-white/10 px-3 py-3 backdrop-blur-sm">
                        <p class="font-semibold uppercase tracking-[0.18em] text-white/60" :class="currentSize.meta">Jenis</p>
                        <p class="mt-2 font-semibold text-white" :class="currentSize.value">{{ card.membership_type_label }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/16 bg-white/10 px-3 py-3 backdrop-blur-sm">
                        <p class="font-semibold uppercase tracking-[0.18em] text-white/60" :class="currentSize.meta">Status</p>
                        <p class="mt-2 font-semibold text-white" :class="currentSize.value">{{ card.membership_status_label }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/16 bg-white/10 px-3 py-3 backdrop-blur-sm">
                        <p class="font-semibold uppercase tracking-[0.18em] text-white/60" :class="currentSize.meta">No. ahli</p>
                        <p class="mt-2 font-semibold text-white" :class="currentSize.value">{{ card.member_no }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/16 bg-white/10 px-3 py-3 backdrop-blur-sm">
                        <p class="font-semibold uppercase tracking-[0.18em] text-white/60" :class="currentSize.meta">Tarikh sertai</p>
                        <p class="mt-2 font-semibold text-white" :class="currentSize.value">{{ card.joined_at || '-' }}</p>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-end justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-white/60">Sahkan keahlian</p>
                            <p class="mt-2 text-sm font-medium text-white/80">
                                Imbas kod QR untuk paparan pengesahan awam.
                            </p>
                        </div>
                        <img
                            v-if="qrCodeDataUrl"
                            :src="qrCodeDataUrl"
                            alt="Kod QR Kad Ahli"
                            :class="currentSize.qr"
                        />
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <span
                        class="inline-flex rounded-full border px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em]"
                        :class="stateBadge.classes"
                    >
                        {{ stateBadge.label }}
                    </span>
                    <p class="min-w-0 truncate text-right font-medium text-white/72" :class="currentSize.footer">
                        {{ cooperative.full_name || cooperative.name }}
                    </p>
                </div>
            </div>
        </div>
    </article>
</template>