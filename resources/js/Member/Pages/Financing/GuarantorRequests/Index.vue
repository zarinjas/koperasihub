<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Clock, Eye, FileText, UserPlus, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import { Button } from '@/Shared/Components/ui/button';
import { Badge } from '@/Shared/Components/ui/badge';

const props = defineProps({
    requests: { type: Array, required: true },
});

const pendingCount = computed(() => {
    return props.requests.filter((r) => r.status === 'pending' || r.status === 'menunggu_penjamin').length;
});

const formatDate = (val) => {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Permintaan Penjamin" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permintaan Penjamin" description="Senarai permohonan pembiayaan yang memerlukan pengesahan anda sebagai penjamin.">
                <template #actions>
                    <Badge v-if="pendingCount > 0" variant="secondary" class="text-sm px-3 py-1.5">
                        {{ pendingCount }} masih menunggu
                    </Badge>
                </template>
            </PageHeader>

            <div v-if="requests.length === 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada permintaan penjamin"
                    description="Anda belum menerima sebarang permintaan untuk menjadi penjamin."
                    compact
                />
            </div>

            <div v-else class="space-y-3">
                <article
                    v-for="request in requests"
                    :key="request.id"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                                <UserPlus class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-950">
                                        {{ request.application?.member?.user?.name || request.application?.member?.full_name || 'Pemohon' }}
                                    </h3>
                                    <StatusBadge :status="request.status" :label="request.status === 'pending' ? 'Menunggu' : request.status" />
                                </div>
                                <p class="text-sm text-slate-600">
                                    {{ request.application?.product?.name || '-' }}
                                </p>
                                <p class="text-sm text-slate-500 flex items-center gap-3">
                                    <span class="font-medium">{{ request.application?.reference_no || '-' }}</span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3.5 w-3.5" />
                                        {{ formatDate(request.created_at) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <Button :as="Link" :href="`/member/financing/guarantor-requests/${request.id}`">
                            <Eye class="mr-2 h-4 w-4" />
                            Lihat
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </article>
            </div>
        </section>
    </MemberLayout>
</template>