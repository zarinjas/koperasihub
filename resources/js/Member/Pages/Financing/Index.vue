<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BadgeDollarSign, FileClock, Users } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    categories: { type: Array, required: true },
    myApplications: { type: Array, required: true },
    guarantorRequestsCount: { type: Number, default: 0 },
    memberLinked: { type: Boolean, default: true },
});
</script>

<template>
    <Head title="Pembiayaan" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Pembiayaan" description="Lihat kategori, produk aktif, dan status permohonan pembiayaan anda.">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" href="/member/financing/applications" variant="outline">Permohonan Saya</Button>
                        <Button :as="Link" href="/member/financing/guarantor-requests" variant="outline">Permintaan Penjamin</Button>
                    </div>
                </template>
            </PageHeader>

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan. Sila hubungi pentadbir untuk mendapatkan akses penuh modul pembiayaan.
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700"><BadgeDollarSign class="h-6 w-6" /></span>
                        <div>
                            <p class="text-sm text-slate-500">Kategori Aktif</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-950">{{ categories.length }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700"><FileClock class="h-6 w-6" /></span>
                        <div>
                            <p class="text-sm text-slate-500">Permohonan Saya</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-950">{{ myApplications.length }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700"><Users class="h-6 w-6" /></span>
                        <div>
                            <p class="text-sm text-slate-500">Permintaan Penjamin</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-950">{{ guarantorRequestsCount }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="space-y-4">
                <div v-for="category in categories" :key="category.id" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-xl font-semibold text-slate-950">{{ category.name }}</h2>
                                <StatusBadge :status="category.type" :label="category.type_label" />
                            </div>
                            <p class="text-sm leading-6 text-slate-600">{{ category.description || 'Kategori pembiayaan ini tersedia untuk ahli yang layak.' }}</p>
                            <div class="grid gap-4 md:grid-cols-2">
                                <article v-for="product in category.products" :key="product.id" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                                    <h3 class="text-base font-semibold text-slate-950">{{ product.name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ product.description || 'Produk pembiayaan tersedia untuk permohonan ahli.' }}</p>
                                    <p class="mt-3 text-xs text-slate-500">RM {{ product.min_amount ?? '-' }} hingga RM {{ product.max_amount ?? '-' }} · {{ product.min_tenure_months || '-' }} hingga {{ product.max_tenure_months || '-' }} bulan</p>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <StatusBadge :status="product.requires_guarantor ? 'approved' : 'inactive'" :label="product.requires_guarantor ? `${product.guarantor_count} penjamin` : 'Tanpa penjamin'" />
                                        <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline">
                                            Lihat
                                            <ArrowRight class="ml-2 h-4 w-4" />
                                        </Button>
                                    </div>
                                </article>
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-50">
                            <img v-if="category.rate_image_url" :src="category.rate_image_url" alt="Jadual Kadar Pembiayaan" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full min-h-64 items-center justify-center p-8 text-center text-sm text-slate-500">
                                Jadual kadar pembiayaan belum dimuat naik untuk kategori ini.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Permohonan Saya</h2>
                <div v-if="myApplications.length" class="mt-4 space-y-3">
                    <article v-for="application in myApplications" :key="application.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ application.reference_no }}</p>
                                <p class="text-sm text-slate-500">{{ application.product_name || '-' }} · {{ application.submitted_at }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <StatusBadge :status="application.status" :label="application.status_label" />
                                <Button :as="Link" :href="application.show_url" variant="outline">Lihat</Button>
                            </div>
                        </div>
                    </article>
                </div>
                <EmptyState v-else title="Tiada permohonan pembiayaan." description="Permohonan yang anda hantar akan dipaparkan di sini." compact />
            </section>
        </section>
    </MemberLayout>
</template>
