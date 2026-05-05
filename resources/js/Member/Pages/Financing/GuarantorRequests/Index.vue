<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    requests: { type: Array, required: true },
});
</script>

<template>
    <Head title="Permintaan Penjamin" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permintaan Penjamin" description="Semak dan beri maklum balas terhadap permintaan penjamin yang ditugaskan kepada anda." />

            <div v-if="requests.length" class="space-y-4">
                <article v-for="requestRecord in requests" :key="requestRecord.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-base font-semibold text-slate-950">{{ requestRecord.product_name }}</h2>
                                <StatusBadge :status="requestRecord.status" :label="requestRecord.status_label" />
                            </div>
                            <p class="text-sm text-slate-600">Pemohon: {{ requestRecord.applicant_name }} · {{ requestRecord.applicant_member_no || '-' }}</p>
                            <p class="text-xs text-slate-500">Amaun {{ requestRecord.amount_requested }} · {{ requestRecord.tenure_months }} bulan</p>
                        </div>
                        <Button :as="Link" :href="requestRecord.show_url" variant="outline">Lihat Butiran</Button>
                    </div>
                </article>
            </div>

            <EmptyState v-else title="Tiada permintaan penjamin." description="Permintaan penjamin yang ditugaskan kepada anda akan dipaparkan di sini." />
        </section>
    </MemberLayout>
</template>
