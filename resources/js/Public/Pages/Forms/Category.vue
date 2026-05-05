<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import { reactive } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SearchInput from '@/Shared/Components/SearchInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    category: { type: Object, required: true },
    forms: { type: Array, required: true },
});

const filters = reactive({ search: props.filters.search || '' });
const search = () => router.get(`/forms/category/${props.category.slug}`, filters, { preserveState: true, replace: true });
</script>

<template>
    <Head :title="category.name" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 py-16">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="category.name" :description="category.description || 'Pilih borang yang berkaitan dan lengkapkan secara online.'" align="start" />
                <div class="max-w-xl">
                    <SearchInput v-model="filters.search" placeholder="Cari dalam kategori ini" />
                </div>
                <Button type="button" @click="search">Cari</Button>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EmptyState
                    v-if="forms.length === 0"
                    title="Tiada borang ditemui."
                    description="Tiada borang diterbitkan dalam kategori ini buat masa sekarang."
                    compact
                />

                <div v-else class="grid gap-4">
                    <article v-for="form in forms" :key="form.id" class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
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
                                </div>
                            </div>
                            <Button :as="Link" :href="form.url">Isi Borang</Button>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
