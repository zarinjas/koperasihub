<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, BriefcaseBusiness } from 'lucide-vue-next';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';

defineProps({
    services: { type: Array, required: true },
});
</script>

<template>
    <Head title="Perkhidmatan" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-teal-50 via-white to-blue-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Perkhidmatan"
                    description="Semak perkhidmatan dan kemudahan koperasi yang telah diterbitkan untuk rujukan pelawat."
                    align="start"
                />
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EmptyState
                    v-if="services.length === 0"
                    title="Tiada perkhidmatan tersedia."
                    description="Perkhidmatan yang telah diterbitkan akan dipaparkan di sini."
                    :compact="true"
                />

                <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="service in services"
                        :key="service.id"
                        :href="service.detail_url"
                        class="group rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-teal-200 hover:shadow-md"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                                <BriefcaseBusiness class="h-6 w-6" />
                            </div>
                            <ArrowUpRight class="h-5 w-5 text-slate-400 transition-colors group-hover:text-teal-700" />
                        </div>
                        <div class="mt-5 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-slate-950">{{ service.title }}</h2>
                                <span v-if="service.category" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ service.category.replaceAll('_', ' ') }}
                                </span>
                            </div>
                            <p class="text-sm leading-7 text-slate-600">{{ service.summary }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
