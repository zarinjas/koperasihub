<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, Newspaper } from 'lucide-vue-next';
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
const items = computed(() => data.value.items ?? []);

function formatDate(dateString) {
    if (!dateString) return '';
    return new Intl.DateTimeFormat('ms-MY', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(dateString));
}
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <SectionHeader
                eyebrow="Berita"
                :title="data.title || 'Berita dan Pengumuman Terkini'"
                :description="data.subtitle"
            />
            <Button v-if="data.button_text && data.button_url" :as="Link" :href="data.button_url" variant="outline">
                {{ data.button_text }}
            </Button>
        </div>

        <div v-if="items.length === 0" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 py-16 text-center">
            <Newspaper class="h-10 w-10 text-slate-300" />
            <p class="mt-3 text-sm text-slate-500">Tiada berita tersedia buat masa ini.</p>
        </div>

        <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="item in items"
                :key="item.slug || item.title"
                :href="item.url || '/berita'"
                class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-1 hover:border-teal-200 hover:shadow-md"
            >
                <div class="aspect-[16/9] overflow-hidden bg-gradient-to-br from-teal-50 to-blue-50">
                    <img
                        v-if="item.image_url"
                        :src="item.image_url"
                        :alt="item.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                        <Newspaper class="h-10 w-10 text-teal-200" />
                    </div>
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span v-if="item.category_label" class="rounded-full bg-teal-50 px-2.5 py-0.5 font-medium text-teal-700">
                            {{ item.category_label }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <CalendarDays class="h-3.5 w-3.5" />
                            {{ formatDate(item.published_at) }}
                        </span>
                    </div>

                    <h3 class="mt-3 text-base font-semibold leading-snug text-slate-950 group-hover:text-teal-800">
                        {{ item.title }}
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-600 line-clamp-3">{{ item.excerpt }}</p>

                    <div class="mt-4 inline-flex items-center text-sm font-semibold text-teal-700">
                        Baca Lagi
                        <ArrowRight class="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                    </div>
                </div>
            </Link>
        </div>
    </PublicSection>
</template>