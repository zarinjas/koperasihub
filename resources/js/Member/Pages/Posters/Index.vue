<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ImagePlay, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import PosterLightbox from '@/Shared/Components/PosterLightbox.vue';

const props = defineProps({
    posters: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const search = ref(props.filters.search || '');
const lightboxPoster = ref(null);

watch(search, (val) => {
    router.get(route('member.posters.index'), { search: val || null }, { preserveState: true, replace: true });
});

function openLightbox(poster) {
    lightboxPoster.value = poster;
}

function closeLightbox() {
    lightboxPoster.value = null;
}
</script>

<template>
    <Head title="Galeri Poster" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Galeri Poster"
                description="Koleksi poster dan infografik terkini daripada koperasi."
            >
                <template #actions>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari poster..."
                            class="h-10 w-full sm:w-56 rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                </template>
            </PageHeader>

            <div v-if="posters.data.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada poster tersedia."
                    description="Poster dan infografik terkini akan dipaparkan di sini apabila diterbitkan oleh pihak koperasi."
                    compact
                >
                    <template #icon>
                        <ImagePlay class="h-12 w-12 text-slate-300" />
                    </template>
                </EmptyState>
            </div>

            <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                <button
                    v-for="poster in posters.data"
                    :key="poster.id"
                    class="group aspect-[4/5] overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                    @click="openLightbox(poster)"
                >
                    <img
                        :src="poster.image_url"
                        :alt="poster.alt_text || poster.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/40 to-transparent p-3 opacity-0 transition-opacity group-hover:opacity-100">
                        <p class="text-sm font-medium text-white">{{ poster.title }}</p>
                    </div>
                </button>
            </div>

            <div v-if="posters.total > posters.per_page" class="flex justify-center">
                <Link v-if="posters.prev_page_url" :href="posters.prev_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Sebelumnya</Link>
                <span class="mx-3 self-center text-sm text-slate-500">Halaman {{ posters.current_page }} / {{ posters.last_page }}</span>
                <Link v-if="posters.next_page_url" :href="posters.next_page_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Seterusnya</Link>
            </div>

            <PosterLightbox
                v-if="lightboxPoster"
                :poster="lightboxPoster"
                @close="closeLightbox"
            />
        </section>
    </MemberLayout>
</template>