<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    documents: { type: Array, required: true },
    categories: { type: Array, required: true },
    activeCategory: { type: String, default: '' },
});

const heading = computed(() => props.activeCategory ? 'Muat Turun' : 'Muat Turun');
</script>

<template>
    <Head title="Muat Turun" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    :title="heading"
                    description="Akses dokumen rujukan rasmi yang telah diterbitkan untuk dimuat turun. Untuk penghantaran borang atau permohonan, sila gunakan modul borang yang berkaitan."
                    align="start"
                />
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div v-if="categories.length" class="flex flex-wrap gap-3">
                    <Button :as="Link" href="/muat-turun" :variant="!activeCategory ? 'default' : 'outline'">Semua</Button>
                    <Button
                        v-for="category in categories"
                        :key="category.slug"
                        :as="Link"
                        :href="`/muat-turun?category=${category.slug}`"
                        :variant="activeCategory === category.slug ? 'default' : 'outline'"
                    >
                        {{ category.name }}
                    </Button>
                </div>

                <EmptyState
                    v-if="documents.length === 0"
                    title="Tiada dokumen tersedia."
                    description="Dokumen awam akan dipaparkan di sini selepas diterbitkan oleh pihak admin."
                    :compact="true"
                />

                <div v-else class="grid gap-4">
                    <article
                        v-for="document in documents"
                        :key="document.id"
                        class="flex flex-col gap-4 rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow-md sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                <FileText class="h-6 w-6" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-semibold text-slate-950">{{ document.title }}</h2>
                                    <span v-if="document.category" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ document.category }}
                                    </span>
                                </div>
                                <p v-if="document.description" class="text-sm leading-6 text-slate-600">{{ document.description }}</p>
                                <p class="text-xs text-slate-500">{{ document.file_name }} · {{ document.file_size_label }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span v-if="document.published_at" class="text-xs text-slate-500">{{ document.published_at }}</span>
                            <Button :as="Link" :href="document.download_url">
                                <Download class="mr-2 h-4 w-4" />
                                Muat Turun
                            </Button>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>