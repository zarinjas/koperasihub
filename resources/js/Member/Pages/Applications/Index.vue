<script setup>
import { Head } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

defineProps({
    memberLinked: { type: Boolean, default: true },
    applications: { type: Array, required: true },
});
</script>

<template>
    <Head title="Permohonan Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Permohonan Saya"
                description="Semak status permohonan keahlian yang dipautkan kepada rekod anda."
            />

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan, jadi status permohonan tidak dapat dipaparkan lagi.
            </div>

            <FormSection title="Status Permohonan" description="Portal ini memaparkan ringkasan semakan terbaru untuk rujukan anda." :columns="1">
                <div v-if="applications.length" class="space-y-4">
                    <article v-for="application in applications" :key="application.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ application.application_no }}</p>
                                <p class="mt-1 text-sm text-slate-500">Dihantar pada {{ application.submitted_at || '-' }}</p>
                            </div>
                            <StatusBadge :status="application.status" />
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh semakan</p>
                                <p class="mt-1 text-sm text-slate-700">{{ application.reviewed_at || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan semakan</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.review_notes || application.rejection_reason || '-' }}</p>
                            </div>
                        </div>
                    </article>
                </div>
                <EmptyState
                    v-else
                    title="Tiada data untuk dipaparkan."
                    description="Permohonan keahlian yang sudah dipautkan kepada rekod anda akan dipaparkan di sini."
                    compact
                />
            </FormSection>
        </section>
    </MemberLayout>
</template>
