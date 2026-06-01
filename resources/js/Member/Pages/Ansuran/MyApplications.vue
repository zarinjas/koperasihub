<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardList, ShoppingBag } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

defineProps({
    applications: { type: Object, required: true },
});
</script>

<template>
    <MemberLayout>
        <Head title="Permohonan Saya" />
        <section class="space-y-6">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                    <ClipboardList class="h-6 w-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Permohonan Saya</h1>
                    <p class="text-sm text-slate-600">Senarai permohonan Ansuran Mudah anda.</p>
                </div>
            </div>

            <div v-if="applications.data.length > 0" class="grid gap-4 sm:grid-cols-2">
                <div v-for="app in applications.data" :key="app.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            <img v-if="app.primary_image_url" :src="app.primary_image_url" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400"><ShoppingBag class="h-8 w-8" /></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-400">{{ app.application_no }}</p>
                                    <p class="mt-0.5 font-semibold text-slate-950 truncate">{{ app.product_name }}</p>
                                </div>
                                <StatusBadge :status="app.status" :label="app.status_label" />
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ app.variant_name }}</p>
                            <p class="text-sm text-slate-500">
                                <span class="font-medium text-slate-700">RM {{ Number(app.monthly_amount).toFixed(2) }}</span>/bulan &middot; {{ app.tenure_months }} Bulan
                            </p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-slate-400">{{ app.created_at }}</span>
                                <Link :href="'/member/ansuran/applications/' + app.id">
                                    <Button variant="outline" size="sm">Semak</Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                title="Tiada Permohonan"
                description="Anda belum mempunyai sebarang permohonan Ansuran Mudah."
                actionLabel="Lihat Katalog"
                :actionHref="'/member/ansuran'"
            />

            <div v-if="applications.links?.length > 3" class="flex justify-center gap-1">
                <Button v-for="link in applications.links" :key="link.label" variant="outline" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.get(link.url, {}, { preserveState: true, replace: true })" />
            </div>
        </section>
    </MemberLayout>
</template>
