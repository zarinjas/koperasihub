<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ImagePlay } from 'lucide-vue-next';
import { ref } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';

const props = defineProps({
    posters: { type: Object, required: true },
});

const lightboxPoster = ref(null);

function openLightbox(poster) {
    lightboxPoster.value = poster;
}

function closeLightbox() {
    lightboxPoster.value = null;
}
</script>

<template>
    <Head title="Galeri Poster" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-teal-50 to-blue-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-3xl font-semibold text-slate-950">Galeri Poster</h1>
                    <p class="mt-3 text-lg text-slate-600">Koleksi poster dan infografik terkini daripada koperasi.</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div v-if="posters.data.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada poster tersedia."
                    description="Poster dan infografik terkini akan dipaparkan di sini apabila diterbitkan."
                    compact
                >
                    <template #icon>
                        <ImagePlay class="h-12 w-12 text-slate-300" />
                    </template>
                </EmptyState>
            </div>

            <div v-else class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4">
                <button
                    v-for="poster in posters.data"
                    :key="poster.id"
                    class="group relative aspect-[4/5] overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                    @click="openLightbox(poster)"
                >
                    <img
                        :src="poster.image_url"
                        :alt="poster.alt_text || poster.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/50 to-transparent p-4 opacity-0 transition-opacity group-hover:opacity-100">
                        <p class="text-sm font-medium text-white">{{ poster.title }}</p>
                    </div>
                </button>
            </div>

            <div v-if="posters.total > posters.per_page" class="mt-10 flex justify-center">
                <Link v-if="posters.prev_page_url" :href="posters.prev_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Sebelumnya</Link>
                <span class="mx-3 self-center text-sm text-slate-500">Halaman {{ posters.current_page }} / {{ posters.last_page }}</span>
                <Link v-if="posters.next_page_url" :href="posters.next_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Seterusnya</Link>
            </div>
        </section>

        <PosterLightbox
            v-if="lightboxPoster"
            :poster="lightboxPoster"
            @close="closeLightbox"
        />
    </PublicLayout>
</template>