<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight, Clock, Coins, Eye, FilePlus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    applications: { type: Object, required: true },
    statuses: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const activeStatus = ref(props.filters.status || '');

const formatCurrency = (val) => {
    if (val == null || val === '') return '-';
    return 'RM ' + Number(val).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (val) => {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' });
};

const filterUrl = (status) => {
    const params = {};
    if (status) params.status = status;
    return `/member/financing/applications?status=${params.status || ''}`;
};
</script>

<template>
    <Head title="Permohonan Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permohonan Saya" description="Semak status semua permohonan pembiayaan yang telah anda hantar.">
                <template #actions>
                    <Button :as="Link" href="/member/financing/applications/create">
                        <FilePlus class="mr-2 h-4 w-4" />
                        Mohon Baharu
                    </Button>
                </template>
            </PageHeader>

            <div class="flex gap-2 overflow-x-auto pb-2">
                <Link
                    :href="filterUrl('')"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition"
                    :class="!activeStatus
                        ? 'bg-teal-700 text-white shadow-sm'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                >
                    Semua
                </Link>
                <Link
                    v-for="s in statuses"
                    :key="s.value"
                    :href="filterUrl(s.value)"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition"
                    :class="activeStatus === s.value
                        ? 'text-white shadow-sm'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                    :style="activeStatus === s.value ? { backgroundColor: s.color || '#0f766e' } : {}"
                >
                    {{ s.label }}
                </Link>
            </div>

            <div v-if="applications.data.length === 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada permohonan"
                    description="Anda belum membuat sebarang permohonan pembiayaan. Klik butang Mohon Baharu untuk bermula."
                    action-label="Mohon Baharu"
                    action-href="/member/financing/applications/create"
                />
            </div>

            <div v-else class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left">
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Rujukan</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jumlah (RM)</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="app in applications.data"
                                :key="app.id"
                                class="border-b border-slate-100 transition hover:bg-slate-50"
                            >
                                <td class="px-5 py-4">
                                    <Link :href="`/member/financing/applications/${app.id}`" class="font-semibold text-teal-700 hover:text-teal-800">
                                        {{ app.reference_no }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ app.product_name || '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ app.category_name || '-' }}</td>
                                <td class="px-5 py-4 font-medium text-slate-950">{{ formatCurrency(app.amount_requested) }}</td>
                                <td class="px-5 py-4">
                                    <StatusBadge :status="app.status" :label="app.status_label" />
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ app.submitted_at || '-' }}</td>
                                <td class="px-5 py-4">
                                    <Button :as="Link" :href="`/member/financing/applications/${app.id}`" variant="ghost" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="applications.links" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-5 py-4">
                    <p class="text-sm text-slate-600">
                        Menunjukkan {{ applications.from || 0 }} - {{ applications.to || 0 }} daripada {{ applications.total || 0 }} permohonan
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <Link
                            v-for="link in applications.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-2.5 text-sm font-medium transition"
                            :class="link.active
                                ? 'bg-teal-700 text-white shadow-sm'
                                : link.url
                                    ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    : 'text-slate-400 cursor-default'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>