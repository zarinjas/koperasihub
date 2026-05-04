<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2, FileText, Megaphone, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const data = computed(() => props.section.data ?? {});

const heroBadge = computed(() => data.value.badge || cooperative.value.name || 'Laman koperasi');
const heroTitle = computed(() => data.value.title || 'Koperasi moden untuk keperluan anggota');
const heroSubtitle = computed(() => data.value.subtitle || 'Akses maklumat keanggotaan, perkhidmatan, pengumuman dan borang koperasi melalui satu laman rasmi yang mudah digunakan.');

const highlights = [
    {
        title: 'Keanggotaan',
        description: 'Permohonan dan semakan status dalam satu aliran yang jelas.',
        icon: Building2,
    },
    {
        title: 'Dokumen',
        description: 'Borang dan rujukan penting mudah dicapai bila-bila masa.',
        icon: FileText,
    },
    {
        title: 'Pengumuman',
        description: 'Hebahan rasmi koperasi dipaparkan dengan tepat dan jelas.',
        icon: Megaphone,
    },
];

const bgStyle = computed(() => {
    const imageUrl = data.value.image_url;
    if (imageUrl) {
        return {
            backgroundImage: `url('${imageUrl}')`,
            backgroundSize: 'cover',
            backgroundPosition: 'center top',
        };
    }
    return {};
});
</script>

<template>
    <section class="relative flex min-h-[88vh] items-center overflow-hidden bg-slate-900">
        <!-- Background image layer (used when image_url is set) -->
        <div
            v-if="data.image_url"
            class="absolute inset-0"
            :style="bgStyle"
        />

        <!-- Rich gradient background (visible always; acts as fallback when no image) -->
        <div
            class="absolute inset-0"
            :class="data.image_url ? 'opacity-100' : 'opacity-100'"
        >
            <!-- Base gradient -->
            <div
                class="absolute inset-0"
                :class="data.image_url
                    ? 'bg-gradient-to-br from-slate-900/80 via-slate-900/65 to-teal-900/70'
                    : 'bg-[linear-gradient(135deg,_#0c1a1a_0%,_#0f3330_30%,_#0d2a3d_60%,_#0a1628_100%)]'"
            />
            <!-- Accent glow overlays -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_10%_20%,_rgba(15,118,110,0.35),_transparent_65%)]" />
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_85%_75%,_rgba(29,78,216,0.22),_transparent_60%)]" />
            <!-- Subtle dot pattern for texture -->
            <div
                class="absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 32px 32px;"
            />
        </div>

        <!-- Content -->
        <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

                <!-- Left: Text content -->
                <div class="space-y-8">
                    <!-- Badge -->
                    <div class="inline-flex items-center rounded-full border border-teal-400/25 bg-teal-500/12 px-4 py-1.5 text-sm font-semibold text-teal-300 backdrop-blur-sm">
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        {{ heroBadge }}
                    </div>

                    <!-- Title -->
                    <div class="space-y-4">
                        <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-[3.35rem] lg:leading-[1.15]">
                            {{ heroTitle }}
                        </h1>
                        <p class="max-w-xl text-base leading-8 text-slate-300 sm:text-lg">
                            {{ heroSubtitle }}
                        </p>
                    </div>

                    <!-- CTA buttons -->
                    <div class="flex flex-wrap gap-3">
                        <Button
                            v-if="data.primary_button_text && data.primary_button_url"
                            :as="Link"
                            :href="data.primary_button_url"
                            class="bg-teal-600 text-white shadow-lg shadow-teal-900/40 hover:bg-teal-700"
                        >
                            {{ data.primary_button_text }}
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button
                            v-if="data.secondary_button_text && data.secondary_button_url"
                            :as="Link"
                            :href="data.secondary_button_url"
                            variant="outline"
                            class="border-white/25 bg-white/8 text-white backdrop-blur-sm hover:border-white/40 hover:bg-white/15"
                        >
                            {{ data.secondary_button_text }}
                        </Button>
                    </div>

                    <!-- Divider with trust line -->
                    <div class="flex items-center gap-4 pt-2">
                        <div class="h-px flex-1 bg-white/10" />
                        <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Platform rasmi koperasi</p>
                        <div class="h-px flex-1 bg-white/10" />
                    </div>
                </div>

                <!-- Right: Highlight cards -->
                <div class="space-y-3">
                    <div
                        v-for="item in highlights"
                        :key="item.title"
                        class="flex items-start gap-4 rounded-2xl border border-white/10 bg-white/6 p-5 backdrop-blur-sm transition-colors hover:bg-white/10"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-500/20 text-teal-300">
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ item.title }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-400">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom fade for smooth transition to next section -->
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-900/40 to-transparent" />
    </section>
</template>
