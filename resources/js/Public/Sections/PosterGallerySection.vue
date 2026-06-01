<script setup>
import { ImagePlay } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});
const items = computed(() => data.value.items ?? []);

const lightboxPoster = ref(null);

function openLightbox(poster) {
    lightboxPoster.value = poster;
}

function closeLightbox() {
    lightboxPoster.value = null;
}
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-10">
        <SectionHeader
            :title="data.title || 'Poster & Infografik'"
            :description="data.subtitle"
        />

        <div v-if="items.length === 0" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 py-16 text-center">
            <ImagePlay class="h-10 w-10 text-slate-300" />
            <p class="mt-3 text-sm text-slate-500">Tiada poster tersedia buat masa ini.</p>
        </div>

        <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            <button
                v-for="item in items"
                :key="item.id"
                class="group relative aspect-[4/5] overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                @click="openLightbox(item)"
            >
                <img
                    :src="item.image_url"
                    :alt="item.alt_text || item.title"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/40 to-transparent p-3 opacity-0 transition-opacity group-hover:opacity-100">
                    <p class="text-sm font-medium text-white">{{ item.title }}</p>
                </div>
            </button>
        </div>

        <PosterLightbox
            v-if="lightboxPoster"
            :poster="lightboxPoster"
            @close="closeLightbox"
        />
    </PublicSection>
</template>