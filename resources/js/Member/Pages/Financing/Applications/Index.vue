<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    applications: { type: Array, required: true },
});
</script>

<template>
    <Head title="Permohonan Pembiayaan Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permohonan Pembiayaan Saya" description="Semak status dan keputusan bagi semua permohonan pembiayaan anda." />

            <div v-if="applications.length" class="space-y-4">
                <article v-for="application in applications" :key="application.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-base font-semibold text-slate-950">{{ application.reference_no }}</h2>
                                <StatusBadge :status="application.status" :label="application.status_label" />
                            </div>
                            <p class="text-sm text-slate-600">{{ application.product_name || '-' }} · {{ application.amount_requested }} · {{ application.tenure_months }} bulan</p>
                            <p class="text-xs text-slate-500">Dihantar {{ application.submitted_at }}</p>
                        </div>
                        <Button :as="Link" :href="application.show_url" variant="outline">Lihat Butiran</Button>
                    </div>
                </article>
            </div>

            <EmptyState v-else title="Tiada permohonan pembiayaan." description="Permohonan pembiayaan yang telah dihantar akan dipaparkan di sini." />
        </section>
    </MemberLayout>
</template>
