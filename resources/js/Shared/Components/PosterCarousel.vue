<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';

const props = defineProps({
    posters: { type: Array, required: true },
});

const currentIndex = ref(0);
const lightboxPoster = ref(null);
const perView = ref(3);

const total = computed(() => props.posters.length);
const maxIndex = computed(() => Math.max(0, total.value - perView.value));

function updatePerView() {
    perView.value = window.innerWidth < 640 ? 1 : 3;
    if (currentIndex.value > maxIndex.value) {
        currentIndex.value = maxIndex.value;
    }
}

function prev() {
    currentIndex.value = Math.max(0, currentIndex.value - 1);
}

function next() {
    currentIndex.value = Math.min(maxIndex.value, currentIndex.value + 1);
}

function openLightbox(poster) {
    lightboxPoster.value = poster;
}

onMounted(() => {
    updatePerView();
    window.addEventListener('resize', updatePerView);
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePerView);
});
</script>

<template>
    <div class="relative" v-if="posters.length">
        <button
            v-if="currentIndex > 0"
            class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="prev"
        >
            <ChevronLeft class="h-4 w-4 text-slate-600" />
        </button>

        <div class="flex gap-4 overflow-hidden">
            <button
                v-for="(poster, idx) in posters"
                :key="poster.id"
                v-show="idx >= currentIndex && idx < currentIndex + perView"
                class="group flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                @click="openLightbox(poster)"
            >
                <div class="aspect-[4/5]">
                    <img
                        :src="poster.image_url"
                        :alt="poster.alt_text || poster.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                </div>
            </button>
        </div>

        <button
            v-if="currentIndex < maxIndex"
            class="absolute -right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="next"
        >
            <ChevronRight class="h-4 w-4 text-slate-600" />
        </button>

        <div v-if="total > perView" class="mt-3 flex justify-center gap-1.5">
            <span
                v-for="i in maxIndex + 1"
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