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
const imageFirst = computed(() => settings.value.variant === 'image_left');
</script>

<template>
    <PublicSection :settings="settings" content-class="grid items-center gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-16">
        <div class="order-2" :class="imageFirst ? 'lg:order-1' : 'lg:order-2'">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-teal-50 via-white to-blue-50 shadow-sm">
                <div class="aspect-[4/3] overflow-hidden">
                    <img
                        v-if="data.image_url"
                        :src="data.image_url"
                        :alt="data.title"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full items-end p-8 sm:p-10">
                        <div class="space-y-3">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Ruang imej</p>
                            <p class="max-w-md text-2xl font-semibold leading-tight text-slate-950">
                                Muat naik gambar yang berkaitan untuk menguatkan naratif seksyen ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="order-1 space-y-6" :class="imageFirst ? 'lg:order-2' : 'lg:order-1'">
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