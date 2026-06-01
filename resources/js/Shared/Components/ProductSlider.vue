<script setup>
import { ChevronLeft, ChevronRight, ShoppingBag } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    products: { type: Array, required: true },
});

const currentIndex = ref(0);
const perView = ref(3);

const total = computed(() => props.products.length);
const maxIndex = computed(() => Math.max(0, total.value - perView.value));

function updatePerView() {
    perView.value = window.innerWidth < 640 ? 1 : window.innerWidth < 1024 ? 2 : 3;
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

onMounted(() => {
    updatePerView();
    window.addEventListener('resize', updatePerView);
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePerView);
});
</script>

<template>
    <div v-if="products.length" class="relative">
        <button
            v-if="currentIndex > 0"
            class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50"
            @click="prev"
        >
            <ChevronLeft class="h-4 w-4 text-slate-600" />
        </button>

        <div class="flex gap-4 overflow-hidden">
            <Link
                v-for="(product, idx) in products"
                :key="product.id"
                :href="product.url"
                v-show="idx >= currentIndex && idx < currentIndex + perView"
                class="group flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
            >
                <div class="aspect-square overflow-hidden bg-slate-100">
                    <img
                        v-if="product.primary_image_url"
                        :src="product.primary_image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                        <ShoppingBag class="h-10 w-10" />
                    </div>
                </div>
                <div class="p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ product.category_name || 'Produk' }}</p>
                    <p class="mt-1 font-semibold text-slate-950 line-clamp-2">{{ product.name }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        <template v-if="product.min_price === product.max_price">
                            RM {{ Number(product.min_price).toFixed(2) }}
                        </template>
                        <template v-else>
                            RM {{ Number(product.min_price).toFixed(2) }} – RM {{ Number(product.max_price).toFixed(2) }}
                        </template>
                    </p>
                </div>
            </Link>
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

    <div v-else class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-10 text-center">
        <ShoppingBag class="h-10 w-10 text-slate-400" />
        <div>
            <p class="text-sm font-medium text-slate-700">Tiada produk tersedia</p>
            <p class="mt-1 text-xs text-slate-500">Produk ansuran mudah akan dipaparkan di sini apabila tersedia.</p>
        </div>
    </div>
</template>
