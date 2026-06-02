<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ShoppingBag } from 'lucide-vue-next';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref('');
const selectedCategory = ref('');

const doSearch = () => {
    router.get('/member/ansuran', { search: search.value, category: selectedCategory.value }, { preserveState: true, replace: true });
};

const filterCategory = (catId) => {
    selectedCategory.value = catId;
    doSearch();
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    router.get('/member/ansuran', {}, { preserveState: true, replace: true });
};
</script>

<template>
    <MemberLayout>
        <Head title="Ansuran Mudah" />
        <section class="space-y-6">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                    <ShoppingBag class="h-6 w-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Ansuran Mudah</h1>
                    <p class="text-sm text-slate-600">Beli produk pilihan dan bayar secara ansuran bulanan</p>
                </div>
            </div>

            <div class="mx-auto max-w-xl">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-950 shadow-sm placeholder:text-slate-400 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                        placeholder="Cari produk..."
                        @keyup.enter="doSearch"
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button :variant="!selectedCategory ? 'default' : 'outline'" size="sm" @click="filterCategory('')">Semua</Button>
                <Button v-for="cat in categories" :key="cat.id" :variant="selectedCategory == cat.id ? 'default' : 'outline'" size="sm" @click="filterCategory(cat.id)">{{ cat.name }}</Button>
            </div>

            <div v-if="products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <Link v-for="product in products.data" :key="product.id" :href="'/member/ansuran/products/' + product.slug" class="group block">
                    <div class="h-full rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                        <div class="aspect-square overflow-hidden rounded-t-3xl bg-slate-100">
                            <img v-if="product.primary_image_url" :src="product.primary_image_url" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400"><ShoppingBag class="h-12 w-12" /></div>
                        </div>
                        <div class="p-5">
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ product.category_name }}</div>
                            <div class="mt-1 font-semibold text-slate-950 line-clamp-2">{{ product.name }}</div>
                            <div class="mt-1 text-sm text-slate-600">
                                <template v-if="product.min_price === product.max_price">
                                    RM {{ Number(product.min_price).toFixed(2) }}
                                </template>
                                <template v-else>
                                    RM {{ Number(product.min_price).toFixed(2) }} - RM {{ Number(product.max_price).toFixed(2) }}
                                </template>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs text-slate-400">{{ product.variant_count }} Varian</span>
                                <span class="text-sm font-medium text-teal-700 transition group-hover:text-teal-800">Lihat</span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white py-16 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <ShoppingBag class="h-6 w-6" />
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-950">Tiada Produk</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">Tiada produk dijumpai untuk carian dan penapis yang dipilih.</p>
            </div>

            <div v-if="products.links?.length > 3" class="flex justify-center gap-1">
                <Button v-for="link in products.links" :key="link.label" variant="outline" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.get(link.url, {}, { preserveState: true, replace: true })" />
            </div>
        </section>
    </MemberLayout>
</template>