<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';
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
    <PublicSection :settings="settings" content-class="grid items-center gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-16">
        <div class="order-2 lg:order-1">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-teal-50 via-white to-blue-50 p-8 shadow-sm sm:p-10">
                <div class="space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Sorotan</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white bg-white/80 p-4">
                            <p class="text-sm font-medium text-slate-500">Maklumat</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">Disusun secara jelas dan profesional.</p>
                        </div>
                        <div class="rounded-2xl border border-white bg-white/80 p-4">
                            <p class="text-sm font-medium text-slate-500">Akses</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">Mesra pelawat dan mudah diteroka.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="order-1 space-y-6 lg:order-2">
            <SectionHeader
                :eyebrow="data.eyebrow || 'Maklumat lanjut'"
                :title="data.title"
                :description="data.content"
            />
            <Button v-if="data.button_text && data.button_url" :as="Link" :href="data.button_url">
                {{ data.button_text }}
                <ArrowRight class="ml-2 h-4 w-4" />
            </Button>
        </div>
    </PublicSection>
</template>