<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

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

function handleClick(banner) {
    if (banner.link_url) {
        window.location.href = banner.link_url;
    }
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

function onTouchStart(event) {
    isPaused.value = true;
}

function onTouchEnd(event) {
    isPaused.value = false;
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
        class="relative overflow-hidden rounded-2xl"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
    >
        <div
            class="flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
        >
            <button
                v-for="(banner, idx) in banners"
                :key="banner.id"
                type="button"
                class="relative h-44 w-full shrink-0 grow-0 basis-full overflow-hidden sm:h-56 lg:h-[400px]"
                :class="{ 'cursor-pointer': !!banner.link_url }"
                @click="handleClick(banner)"
            >
                <img
                    :src="banner.image_url"
                    :alt="banner.alt_text || banner.title"
                    class="h-full w-full object-cover"
                />
            </button>
        </div>

        <button
            v-if="totalBanners > 1"
            type="button"
            class="absolute left-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition hover:bg-white"
            @click="prev"
        >
            <ChevronLeft class="h-3 w-3 text-slate-700" />
        </button>

        <button
            v-if="totalBanners > 1"
            type="button"
            class="absolute right-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition hover:bg-white"
            @click="next"
        >
            <ChevronRight class="h-3 w-3 text-slate-700" />
        </button>

        <div
            v-if="totalBanners > 1"
            class="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 gap-1.5"
        >
            <button
                v-for="i in totalBanners"
                :key="i"
                type="button"
                class="rounded-full transition-all duration-300"
                :class="i - 1 === currentIndex ? 'w-6 h-1.5 bg-white shadow-sm' : 'w-1.5 h-1.5 bg-white/60'"
                @click="goTo(i - 1)"
            />
        </div>
    </div>
</template>
