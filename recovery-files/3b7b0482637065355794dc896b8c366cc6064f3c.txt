<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Info, LogIn, ShoppingBag } from 'lucide-vue-next';
import { ref } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, required: true },
});

const activeImage = ref(props.product.images?.[0]?.url || null);

const setActiveImage = (url) => {
    activeImage.value = url;
};
</script>

<template>
    <PublicLayout>
        <Head :title="product.name" />
        <section class="mx-auto max-w-7xl space-y-8 px-4 py-12">
            <Link :href="'/ansuran'" class="inline-flex items-center text-sm font-medium text-slate-600 transition hover:text-slate-950"><ArrowLeft class="mr-1 h-4 w-4" /> Kembali ke Katalog</Link>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="space-y-4">
                    <div v-if="activeImage" class="aspect-square overflow-hidden rounded-3xl bg-slate-100">
                        <img :src="activeImage" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="flex aspect-square items-center justify-center rounded-3xl bg-slate-100 text-slate-400"><ShoppingBag class="h-24 w-24" /></div>
                    <div v-if="product.images.length > 1" class="flex gap-2">
                        <button v-for="img in product.images" :key="img.id" @click="setActiveImage(img.url)" class="h-20 w-20 overflow-hidden rounded-xl border-2 transition" :class="activeImage === img.url ? 'border-teal-700' : 'border-slate-200'">
                            <img :src="img.url" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ product.category_name }}</span>
                        <h1 class="mt-1 text-2xl font-bold text-slate-950 lg:text-3xl">{{ product.name }}</h1>
                    </div>

                    <div v-if="product.description" class="prose prose-sm text-slate-600" v-html="product.description" />

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950">Varian</h2>
                        <p class="text-sm text-slate-600">Pilihan varian yang tersedia untuk produk ini.</p>
                        <div class="mt-4 grid grid-cols-1 gap-2">
                            <div v-for="v in product.variants" :key="v.id" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4">
                                <div>
                                    <div class="font-medium text-slate-950">{{ v.name }}</div>
                                    <div v-if="v.attributes" class="mt-0.5 text-sm text-slate-500">
                                        <span v-for="(val, key) in v.attributes" :key="key">{{ key }}: {{ val }} </span>
                                    </div>
                                </div>
                                <div class="text-lg font-bold text-slate-950">{{ v.formatted_price }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm">
                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                                    <Info class="h-5 w-5" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-950">Berminat?</h3>
                                    <p class="text-sm text-slate-600">Log masuk sebagai ahli untuk membuat permohonan Ansuran Mudah.</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Button :as="Link" href="/member/login" class="w-full">
                                    <LogIn class="mr-1.5 h-4 w-4" /> Log Masuk
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
