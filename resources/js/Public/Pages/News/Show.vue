<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, CalendarDays, Newspaper, Tag } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';

const props = defineProps({
    news: { type: Object, required: true },
    suggested: { type: Array, default: () => [] },
});

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
    <Head :title="news.title" />

    <PublicLayout>
        <main>
            <article class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <Link href="/berita" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-800">
                    <ArrowLeft class="h-4 w-4" />
                    Kembali ke senarai berita
                </Link>

                <div class="mt-8 space-y-6">
                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <span v-if="news.category_label" class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-3 py-1 font-medium text-teal-700">
                            <Tag class="h-3.5 w-3.5" />
                            {{ news.category_label }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <CalendarDays class="h-4 w-4" />
                            {{ formatDate(news.published_at) }}
                        </span>
                    </div>

                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                        {{ news.title }}
                    </h1>

                    <p v-if="news.excerpt" class="text-lg leading-8 text-slate-600">
                        {{ news.excerpt }}
                    </p>
                </div>

                <div v-if="news.image_url" class="mt-8 overflow-hidden rounded-3xl">
                    <img
                        :src="news.image_url"
                        :alt="news.title"
                        class="h-auto w-full object-cover"
                    />
                </div>

                <div
                    v-if="news.content"
                    class="prose prose-slate mt-10 max-w-none prose-headings:font-semibold prose-headings:tracking-tight prose-a:text-teal-700 prose-a:no-underline hover:prose-a:underline prose-img:rounded-2xl"
                    v-html="news.content"
                />
            </article>

            <section v-if="suggested.length > 0" class="border-t border-slate-100 bg-slate-50 py-16">
                <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-xl font-semibold text-slate-950">Berita lain yang mungkin anda minati</h2>

                    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="item in suggested"
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
                                    <Newspaper class="h-10 w-10 text-teal-200" />
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
                                <h3 class="mt-3 text-base font-semibold leading-snug text-slate-950 group-hover:text-teal-800">
                                    {{ item.title }}
                                </h3>
                                <p class="mt-2 flex-1 text-sm leading-6 text-slate-600 line-clamp-2">{{ item.excerpt }}</p>
                                <div class="mt-4 inline-flex items-center text-sm font-semibold text-teal-700">
                                    Baca Lagi
                                    <ArrowRight class="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>