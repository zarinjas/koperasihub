<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, Newspaper, Search } from 'lucide-vue-next';
import { reactive } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    news: { type: Object, required: true },
    filters: { type: Object, required: true },
    categoryOptions: { type: Array, required: true },
});

const filters = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
});

const applyFilters = () => {
    router.get('/berita', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.category = '';
    applyFilters();
};

const setCategory = (value) => {
    filters.category = filters.category === value ? '' : value;
    applyFilters();
};

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
    <Head title="Berita dan Pengumuman" />

    <PublicLayout>
        <main>
            <section class="bg-gradient-to-b from-slate-50 to-white py-16 lg:py-20">
                <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-sm font-medium text-teal-700">
                            <Newspaper class="h-4 w-4" />
                            Berita & Pengumuman
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                            Berita terkini koperasi
                        </h1>
                        <p class="mt-4 text-base leading-7 text-slate-600">
                            Ikuti perkembangan terbaru, pengumuman rasmi, dan aktiviti koperasi.
                        </p>
                    </div>
                </div>
            </section>

            <section class="py-10 lg:py-14">
                <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button
                                class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors"
                                :class="!filters.category ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:text-teal-700'"
                                @click="setCategory('')"
                            >
                                Semua
                            </button>
                            <button
                                v-for="opt in categoryOptions"
                                :key="opt.value"
                                class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors"
                                :class="filters.category === opt.value ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:text-teal-700'"
                                @click="setCategory(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>

                        <div class="relative w-full sm:w-72">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Cari berita..."
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                                @keydown.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <div v-if="news.data.length === 0" class="py-24 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                            <Newspaper class="h-8 w-8 text-slate-400" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-950">Tiada berita ditemui</h3>
                        <p class="mt-2 text-sm text-slate-500">Cuba carian atau kategori yang lain.</p>
                        <Button class="mt-6" variant="outline" @click="resetFilters">Papar semua berita</Button>
                    </div>

                    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="item in news.data"
                            :key="item.id"
                            :href="item.url"
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
                                    <Newspaper class="h-12 w-12 text-teal-200" />
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span v-if="item.category_label" class="rounded-full bg-teal-50 px-2.5 py-0.5 font-medium text-teal-700">
                                        {{ item.category_label }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        {{ formatDate(item.published_at) }}
                                    </span>
                                </div>
                                <h2 class="mt-3 text-base font-semibold leading-snug text-slate-950 group-hover:text-teal-800">
                                    {{ item.title }}
                                </h2>
                                <p class="mt-2 flex-1 text-sm leading-6 text-slate-600 line-clamp-3">{{ item.excerpt }}</p>
                                <div class="mt-4 inline-flex items-center text-sm font-semibold text-teal-700">
                                    Baca Lagi
                                    <ArrowRight class="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-if="news.links?.length > 3" class="mt-10 flex flex-wrap justify-center gap-2">
                        <Link
                            v-for="link in news.links"
                            :key="`${link.label}-${link.url}`"
                            :href="link.url || '#'"
                            class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                            :class="link.active
                                ? 'border-teal-700 bg-teal-700 text-white'
                                : link.url
                                    ? 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:text-teal-700'
                                    : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>