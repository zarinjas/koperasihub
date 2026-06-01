<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});
</script>

<template>
    <PublicSection
        :settings="{ ...settings, background: settings.background || 'primary' }"
        content-class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-r from-teal-700 via-cyan-600 to-blue-700 p-8 text-white shadow-lg sm:p-10"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.2),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(255,255,255,0.14),_transparent_30%)]" />
        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/80">Tindakan seterusnya</p>
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ data.title }}</h2>
                <p v-if="data.subtitle" class="text-base leading-8 text-white/85 sm:text-lg">{{ data.subtitle }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <Button
                    v-if="data.primary_button_text && data.primary_button_url"
                    :as="Link"
                    :href="data.primary_button_url"
                    class="bg-white text-slate-950 hover:bg-slate-100"
                >
                    {{ data.primary_button_text }}
                    <ArrowRight class="ml-2 h-4 w-4" />
                </Button>
                <Button
                    v-if="data.secondary_button_text && data.secondary_button_url"
                    :as="Link"
                    :href="data.secondary_button_url"
                    class="border border-white/20 bg-white/10 text-white hover:bg-white/20"
                >
                    {{ data.secondary_button_text }}
                </Button>
            </div>
        </div>
    </PublicSection>
</template>