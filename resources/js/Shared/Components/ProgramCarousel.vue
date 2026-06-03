<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MapPin } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    programs: { type: Array, required: true },
});

const currentIndex = ref(0);
const perView = ref(3);
const isPaused = ref(false);
let autoPlayTimer = null;

const total = computed(() => props.programs.length);
const maxIndex = computed(() => Math.max(0, total.value - perView.value));

function updatePerView() {
    perView.value = window.innerWidth < 640 ? 1 : 3;
    if (currentIndex.value > maxIndex.value) {
        currentIndex.value = maxIndex.value;
    }
}

function prev() {
    currentIndex.value = Math.max(0, currentIndex.value - 1);
    resetAutoPlay();
}

function next() {
    if (currentIndex.value < maxIndex.value) {
        currentIndex.value++;
    } else {
        currentIndex.value = 0;
    }
    resetAutoPlay();
}

function rsvp(programId, response) {
    router.post(`/member/programs/${programId}/rsvp`, { response }, { preserveScroll: true });
}

function startAutoPlay() {
    if (total.value <= perView.value) return;
    stopAutoPlay();
    autoPlayTimer = setInterval(() => {
        if (!isPaused.value) {
            if (currentIndex.value < maxIndex.value) {
                currentIndex.value++;
            } else {
                currentIndex.value = 0;
            }
        }
    }, 4000);
}

function stopAutoPlay() {
    if (autoPlayTimer) {
        clearInterval(autoPlayTimer);
        autoPlayTimer = null;
    }
}

function resetAutoPlay() {
    stopAutoPlay();
    startAutoPlay();
}

onMounted(() => {
    updatePerView();
    window.addEventListener('resize', updatePerView);
    startAutoPlay();
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePerView);
    stopAutoPlay();
});
</script>

<template>
    <div v-if="programs.length" class="relative" @mouseenter="isPaused = true" @mouseleave="isPaused = false">
        <button
            v-if="currentIndex > 0"
            class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="prev"
        >
            <ChevronLeft class="h-4 w-4 text-slate-600" />
        </button>

        <div class="flex gap-4 overflow-hidden">
            <div
                v-for="(pg, idx) in programs"
                :key="pg.id"
                v-show="idx >= currentIndex && idx < currentIndex + perView"
                class="flex-1 overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-100 transition-all duration-200 hover:shadow-xl"
            >
                <Link :href="`/member/programs/${pg.id}`" class="block">
                    <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200">
                        <img
                            v-if="pg.cover_image_url"
                            :src="pg.cover_image_url"
                            :alt="pg.title"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div v-else class="flex h-full items-center justify-center text-slate-300">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                            </svg>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" />
                        <span class="absolute left-3 top-3 rounded-full bg-white/20 px-2.5 py-1 text-xs font-medium text-white shadow-sm backdrop-blur-sm ring-1 ring-white/30">
                            Akan Datang
                        </span>
                    </div>

                    <div class="space-y-1.5 p-4 pb-3">
                        <p class="text-sm font-semibold text-slate-900 line-clamp-1 group-hover:text-teal-700">{{ pg.title }}</p>
                        <p v-if="pg.description" class="text-xs leading-relaxed text-slate-500 line-clamp-2">
                            {{ pg.description }}
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-400">
                            <span class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-1.5 py-0.5 text-teal-600">{{ pg.start_date_formatted }}</span>
                            <span>{{ pg.start_time }}</span>
                            <span v-if="pg.location" class="flex items-center gap-0.5">
                                <MapPin class="h-3 w-3" />
                                <span class="truncate max-w-[80px]">{{ pg.location }}</span>
                            </span>
                        </div>
                    </div>
                </Link>

                <!-- RSVP buttons (not part of link) -->
                <div class="px-4 pb-4">
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex-1 h-8 rounded-full text-xs font-medium"
                            :class="pg.user_rsvp?.response === 'hadir' ? 'bg-teal-50 border-teal-300 text-teal-700 hover:bg-teal-100' : 'border-slate-200 text-slate-600 hover:border-teal-200 hover:text-teal-600'"
                            @click="rsvp(pg.id, 'hadir')"
                        >
                            Hadir
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex-1 h-8 rounded-full text-xs font-medium"
                            :class="pg.user_rsvp?.response === 'mungkin' ? 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100' : 'border-slate-200 text-slate-600 hover:border-amber-200 hover:text-amber-600'"
                            @click="rsvp(pg.id, 'mungkin')"
                        >
                            Mungkin
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex-1 h-8 rounded-full text-xs font-medium"
                            :class="pg.user_rsvp?.response === 'tidak_hadir' ? 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100' : 'border-slate-200 text-slate-600 hover:border-red-200 hover:text-red-600'"
                            @click="rsvp(pg.id, 'tidak_hadir')"
                        >
                            Tidak
                        </Button>
                    </div>
                </div>
            </div>
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
    </div>
</template>
