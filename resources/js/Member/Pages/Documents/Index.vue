<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    memberLinked: { type: Boolean, default: true },
    memberDocuments: { type: Array, required: true },
    generalDocuments: { type: Array, required: true },
});
</script>

<template>
    <Head title="Dokumen Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Dokumen Saya"
                description="Lihat fail rujukan yang dipautkan kepada akaun anda serta dokumen penting yang boleh dimuat turun. Permohonan dan borang penghantaran diurus dalam modul Permohonan."
            />

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan. Hanya dokumen umum untuk ahli dipaparkan buat masa ini.
            </div>

            <FormSection title="Dokumen Saya" description="Dokumen ini dipautkan terus kepada rekod keahlian anda untuk rujukan dan muat turun." :columns="1">
                <div v-if="memberDocuments.length" class="space-y-3">
                    <article v-for="document in memberDocuments" :key="document.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-950">{{ document.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ document.category_name || 'Tanpa kategori' }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ document.description || 'Tiada penerangan disediakan.' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <StatusBadge :status="document.visibility" />
                                </div>
                            </div>
                            <div class="text-right text-sm text-slate-500">
                                <p>{{ document.file_size_label }}</p>
                                <p class="mt-1">{{ document.published_at || '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Button :as="Link" :href="document.download_url" variant="outline">Muat Turun</Button>
                        </div>
                    </article>
                </div>
                <EmptyState
                    v-else
                    title="Tiada dokumen dimuat naik setakat ini."
                    description="Dokumen yang dipautkan terus kepada rekod anda akan dipaparkan di sini."
                    compact
                />
            </FormSection>

            <FormSection title="Dokumen Penting" description="Dokumen umum yang boleh diakses oleh semua ahli yang log masuk." :columns="1">
                <div v-if="generalDocuments.length" class="space-y-3">
                    <article v-for="document in generalDocuments" :key="document.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-950">{{ document.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ document.category_name || 'Tanpa kategori' }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ document.description || 'Tiada penerangan disediakan.' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <StatusBadge :status="document.visibility" />
                                </div>
                            </div>
                            <div class="text-right text-sm text-slate-500">
                                <p>{{ document.file_size_label }}</p>
                                <p class="mt-1">{{ document.published_at || '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Button :as="Link" :href="document.download_url" variant="outline">Muat Turun</Button>
                        </div>
                    </article>
                </div>
                <EmptyState
                    v-else
                    title="Tiada dokumen tersedia."
                    description="Dokumen umum untuk ahli akan dipaparkan di sini apabila diterbitkan."
                    compact
                />
            </FormSection>
        </section>
    </MemberLayout>
</template>