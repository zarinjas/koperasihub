<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileCheck, FileText, PenBox } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    availableForms: { type: Array, required: true },
    submissions: { type: Array, required: true },
    memberLinked: { type: Boolean, default: true },
});

const statusColorMap = {
    draft: 'slate',
    pending_stamp_upload: 'amber',
    submitted: 'blue',
    under_review: 'amber',
    incomplete_documents: 'red',
    approved: 'green',
    rejected: 'red',
    closed: 'slate',
};
</script>

<template>
    <Head title="Permohonan" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan"
                description="Mohon perkhidmatan dan semak status permohonan borang anda."
            />

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan. Sila hubungi pentadbir untuk bantuan.
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <SectionHeader title="Permohonan Baharu" description="Pilih borang yang tersedia untuk memulakan permohonan baharu." />

                <div v-if="availableForms.length === 0" class="mt-4">
                    <EmptyState
                        title="Tiada borang tersedia."
                        description="Borang permohonan akan dipaparkan di sini selepas diterbitkan oleh pihak admin."
                        compact
                    />
                </div>

                <div v-else class="mt-5 grid gap-4 md:grid-cols-2">
                    <article
                        v-for="form in availableForms"
                        :key="form.id"
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                                <FileText class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-950">{{ form.title }}</h3>
                                    <StatusBadge
                                        v-if="form.submission_method === 'requires_stamped_upload'"
                                        status="pending_stamp_upload"
                                        label="Perlu Borang Bercop"
                                    />
                                </div>
                                <p class="text-sm leading-6 text-slate-600">{{ form.description || 'Borang rasmi tersedia untuk dihantar secara online.' }}</p>
                                <p class="text-xs text-slate-500">{{ form.category_name || 'Tanpa kategori' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <Button :as="Link" :href="form.url">
                                <PenBox class="mr-2 h-4 w-4" />
                                Isi Borang
                            </Button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <SectionHeader title="Permohonan Saya" description="Semak status semua permohonan borang yang telah anda hantar." />

                <div v-if="submissions.length === 0" class="mt-4">
                    <EmptyState
                        title="Tiada permohonan dihantar."
                        description="Permohonan borang yang telah anda hantar akan dipaparkan di sini."
                        compact
                    />
                </div>

                <div v-else class="mt-5 space-y-4">
                    <article
                        v-for="submission in submissions"
                        :key="submission.id"
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                    <FileCheck class="h-6 w-6" />
                                </div>
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-950">{{ submission.form_title }}</h3>
                                        <StatusBadge :status="submission.status" :label="submission.status_label" />
                                    </div>
                                    <p class="text-sm text-slate-500">
                                        <span class="font-medium">{{ submission.reference_no }}</span>
                                        &middot;
                                        {{ submission.category_name || 'Tanpa unit' }}
                                        &middot;
                                        Dihantar {{ submission.submitted_at }}
                                    </p>
                                    <div v-if="submission.needs_stamped_upload && !submission.has_stamped_file" class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                        <ArrowRight class="h-4 w-4" />
                                        Sila muat naik borang bercop untuk melengkapkan permohonan.
                                    </div>
                                </div>
                            </div>
                            <Button :as="Link" :href="submission.detail_url" variant="outline">
                                Lihat
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </article>
                </div>
            </section>
        </section>
    </MemberLayout>
</template>