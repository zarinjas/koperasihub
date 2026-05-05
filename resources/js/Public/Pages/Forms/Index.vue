<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, FileText, FolderKanban } from 'lucide-vue-next';
import { reactive } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, required: true },
    featuredForms: { type: Array, required: true },
});

const filters = reactive({ search: props.filters.search || '' });
const search = () => router.get('/forms', filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Borang Online" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 py-16">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Borang Online"
                    description="Cari borang rasmi koperasi mengikut kategori perkhidmatan dan isi terus secara online."
                    align="start"
                />
                <div class="max-w-xl">
                    <SearchInput v-model="filters.search" placeholder="Cari tajuk borang" />
                </div>
                <Button type="button" @click="search">Cari Borang</Button>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="category in categories"
                        :key="category.id"
                        class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                            <FolderKanban class="h-6 w-6" />
                        </div>
                        <h2 class="mt-5 text-lg font-semibold text-slate-950">{{ category.name }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ category.description || 'Kategori ini menghimpunkan borang berkaitan unit atau perkhidmatan tertentu.' }}</p>
                        <div class="mt-4 flex items-center justify-between">
                            <StatusBadge status="published" :label="`${category.published_forms_count} borang`" />
                            <Button :as="Link" :href="category.url" variant="outline">
                                Lihat
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </article>
                </div>

                <EmptyState
                    v-if="featuredForms.length === 0"
                    title="Tiada borang diterbitkan buat masa ini."
                    description="Borang yang tersedia akan dipaparkan di sini selepas diterbitkan oleh pihak admin."
                    compact
                />

                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-950">Borang Terkini</h2>
                        <span class="text-sm text-slate-500">{{ featuredForms.length }} borang</span>
                    </div>
                    <div class="grid gap-4">
                        <article
                            v-for="form in featuredForms"
                            :key="form.id"
                            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                        <FileText class="h-6 w-6" />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-semibold text-slate-950">{{ form.title }}</h3>
                                            <StatusBadge :status="form.visibility" :label="form.visibility_label" />
                                        </div>
                                        <p class="text-sm leading-6 text-slate-600">{{ form.description || 'Borang rasmi tersedia untuk dihantar secara online.' }}</p>
                                        <p class="text-xs text-slate-500">{{ form.category_name || 'Tanpa kategori' }}</p>
                                    </div>
                                </div>
                                <Button :as="Link" :href="form.url">Isi Borang</Button>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
