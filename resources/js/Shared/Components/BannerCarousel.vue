<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    banners: { type: Array, required: true },
});

const currentIndex = ref(0);
const isPaused = ref(false);
let intervalId = null;

const totalBanners = computed(() => props.banners.length);

function goTo(index) {
    currentIndex.value = ((index % totalBanners.value) + totalBanners.value) % totalBanners.value;
}

function prev() {
    goTo(currentIndex.value - 1);
    resetTimer();
}

function next() {
    goTo(currentIndex.value + 1);
    resetTimer();
}

function startTimer() {
    if (totalBanners.value <= 1) return;
    stopTimer();
    intervalId = setInterval(() => {
        if (!isPaused.value) {
            goTo(currentIndex.value + 1);
        }
    }, 5000);
}

function stopTimer() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

function resetTimer() {
    stopTimer();
    startTimer();
}

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <div
        v-if="banners.length"
        class="relative w-full overflow-hidden rounded-2xl"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
    >
        <template v-for="(banner, idx) in banners" :key="banner.id">
            <a
                v-if="idx === currentIndex && banner.link_url"
                :href="banner.link_url"
                class="block w-full aspect-[2/1] lg:aspect-[3/1]"
            >
                <img
                    :src="banner.image_url"
                    :alt="banner.alt_text || banner.title"
                    class="h-full w-full object-cover"
                />
            </a>
            <div
                v-else-if="idx === currentIndex"
                class="w-full aspect-[2/1] lg:aspect-[3/1]"
            >
                <img
                    :src="banner.image_url"
                    :alt="banner.alt_text || banner.title"
                    class="h-full w-full object-cover"
                />
            </div>
        </template>

        <button
            v-if="totalBanners > 1"
            type="button"
            class="absolute left-3 top-1/2 z-10 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/75 shadow-sm backdrop-blur-sm transition hover:bg-white/90"
            @click="prev"
        >
            <ChevronLeft class="h-3 w-3 text-slate-700" />
        </button>

        <button
            v-if="totalBanners > 1"
            type="button"
            class="absolute right-3 top-1/2 z-10 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/75 shadow-sm backdrop-blur-sm transition hover:bg-white/90"
            @click="next"
        >
            <ChevronRight class="h-3 w-3 text-slate-700" />
        </button>

        <div
            v-if="totalBanners > 1"
            class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 gap-1.5"
        >
            <button
                v-for="i in totalBanners"
                :key="i"
                type="button"
                class="h-1.5 rounded-full transition-all"
                :class="i - 1 === currentIndex ? 'w-5 bg-white shadow-sm' : 'w-1.5 bg-white/60'"
                @click="goTo(i - 1)"
            />
        </div>
    </div>
</template>
