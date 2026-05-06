<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';

const props = defineProps({
    posters: { type: Array, required: true },
});

const currentIndex = ref(0);
const lightboxPoster = ref(null);

const visiblePosters = computed(() => {
    const total = props.posters.length;
    if (total <= 3) return props.posters;
    const items = [];
    for (let i = 0; i < Math.min(3, total); i++) {
        items.push(props.posters[(currentIndex.value + i) % total]);
    }
    return items;
});

function prev() {
    currentIndex.value = (currentIndex.value - 1 + props.posters.length) % props.posters.length;
}

function next() {
    currentIndex.value = (currentIndex.value + 1) % props.posters.length;
}

function openLightbox(poster) {
    lightboxPoster.value = poster;
}
</script>

<template>
    <div class="relative">
        <button
            v-if="posters.length > 3"
            class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="prev"
        >
            <ChevronLeft class="h-4 w-4 text-slate-600" />
        </button>

        <div class="flex gap-4 overflow-hidden">
            <button
                v-for="poster in visiblePosters"
                :key="poster.id"
                class="group aspect-[4/5] flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                @click="openLightbox(poster)"
            >
                <img
                    :src="poster.image_url"
                    :alt="poster.alt_text || poster.title"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
            </button>
        </div>

        <button
            v-if="posters.length > 3"
            class="absolute -right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="next"
        >
            <ChevronRight class="h-4 w-4 text-slate-600" />
        </button>

        <div v-if="posters.length > 3" class="mt-3 flex justify-center gap-1.5">
            <span
                v-for="i in posters.length"
                :key="i"
                class="h-1.5 rounded-full transition-all"
                :class="i - 1 === currentIndex ? 'w-4 bg-teal-600' : 'w-1.5 bg-slate-300'"
            />
        </div>

        <PosterLightbox
            v-if="lightboxPoster"
            :poster="lightboxPoster"
            @close="lightboxPoster = null"
        />
    </div>
</template>
