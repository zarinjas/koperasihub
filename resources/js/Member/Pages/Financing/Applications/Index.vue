<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Clock3 } from 'lucide-vue-next';
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
            <PageHeader title="Permohonan Pembiayaan Saya" description="Lihat semua permohonan anda dan semak statusnya dengan pantas." />

            <div v-if="applications.length" class="space-y-4">
                <article v-for="application in applications" :key="application.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-base font-semibold text-slate-950">{{ application.reference_no }}</h2>
                                <StatusBadge :status="application.status" :label="application.status_label" />
                            </div>
                            <p class="text-sm text-slate-600">{{ application.product_name || '-' }} · {{ application.amount_requested }} · {{ application.tenure_months }} bulan</p>
                            <p class="inline-flex items-center gap-2 text-xs text-slate-500">
                                <Clock3 class="h-4 w-4" />
                                Dihantar {{ application.submitted_at }}
                            </p>
                        </div>
                        <Button :as="Link" :href="application.show_url" variant="outline">Lihat Butiran</Button>
                    </div>
                </article>
            </div>

            <EmptyState v-else title="Tiada permohonan pembiayaan." description="Bila anda menghantar permohonan baharu, rekodnya akan dipaparkan di sini untuk semakan mudah." />
        </section>
    </MemberLayout>
</template>
