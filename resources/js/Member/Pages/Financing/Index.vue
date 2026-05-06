<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BadgeDollarSign, FileClock, HandCoins, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    categories: { type: Array, required: true },
    myApplications: { type: Array, required: true },
    guarantorRequestsCount: { type: Number, default: 0 },
    memberLinked: { type: Boolean, default: true },
});

const guaranteedCategories = computed(() => props.categories.filter((c) => c.type === 'guaranteed'));
const nonGuaranteedCategories = computed(() => props.categories.filter((c) => c.type === 'non_guaranteed'));
</script>

<template>
    <Head title="Pembiayaan" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Pembiayaan" description="Semak produk pembiayaan yang tersedia dan mulakan permohonan anda.">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button :as="Link" href="/member/financing/applications" variant="outline">Permohonan Saya</Button>
                        <Button v-if="guarantorRequestsCount > 0" :as="Link" href="/member/financing/guarantor-requests" variant="outline">
                            Permintaan Penjamin
                            <span class="ml-2 flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700">{{ guarantorRequestsCount }}</span>
                        </Button>
                        <Button v-else :as="Link" href="/member/financing/guarantor-requests" variant="outline">Permintaan Penjamin</Button>
                    </div>
                </template>
            </PageHeader>

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan. Sila hubungi pentadbir untuk mendapatkan akses penuh modul pembiayaan.
            </div>

            <!-- Summary counts -->
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700"><BadgeDollarSign class="h-6 w-6" /></span>
                        <div>
                            <p class="text-sm text-slate-500">Produk Tersedia</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-950">{{ categories.reduce((n, c) => n + c.products.length, 0) }}</p>
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

            <!-- Category type intro cards -->
            <section v-if="categories.length" class="grid gap-4 md:grid-cols-2">
                <article class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 to-blue-50 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                            <ShieldCheck class="h-6 w-6" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-slate-950">Pembiayaan Berpenjamin</h2>
                            <p class="mt-1 text-sm text-slate-600">Memerlukan persetujuan penjamin. Biasanya menawarkan amaun dan tempoh yang lebih tinggi.</p>
                            <p class="mt-3 text-sm font-medium text-teal-700">{{ guaranteedCategories.length }} kategori tersedia</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 to-slate-50 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-700 shadow-sm">
                            <HandCoins class="h-6 w-6" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-slate-950">Pembiayaan Tanpa Penjamin</h2>
                            <p class="mt-1 text-sm text-slate-600">Tiada penjamin diperlukan. Proses yang lebih mudah untuk ahli yang layak.</p>
                            <p class="mt-3 text-sm font-medium text-blue-700">{{ nonGuaranteedCategories.length }} kategori tersedia</p>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Berpenjamin categories -->
            <template v-if="guaranteedCategories.length">
                <div class="flex items-center gap-3">
                    <ShieldCheck class="h-5 w-5 text-teal-700" />
                    <h2 class="text-lg font-semibold text-slate-950">Pembiayaan Berpenjamin</h2>
                </div>
                <div v-for="category in guaranteedCategories" :key="category.id" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold text-slate-950">{{ category.name }}</h3>
                            <StatusBadge :status="category.type" :label="category.type_label" />
                        </div>
                        <p class="text-sm leading-6 text-slate-600">{{ category.description || 'Kategori pembiayaan ini tersedia untuk ahli yang layak.' }}</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <article v-for="product in category.products" :key="product.id" class="flex flex-col rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                                <h4 class="text-base font-semibold text-slate-950">{{ product.name }}</h4>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ product.description || 'Produk pembiayaan tersedia untuk permohonan ahli.' }}</p>
                                <div class="mt-3 space-y-1 text-xs text-slate-500">
                                    <p>RM {{ product.min_amount?.toLocaleString('ms-MY') ?? '-' }} – RM {{ product.max_amount?.toLocaleString('ms-MY') ?? '-' }}</p>
                                    <p>{{ product.min_tenure_months || '-' }} – {{ product.max_tenure_months || '-' }} bulan</p>
                                    <p v-if="product.annual_rate_percent" class="font-medium text-teal-700">{{ product.annual_rate_percent }}% setahun</p>
                                </div>
                                <div class="mt-4 flex items-center gap-2">
                                    <StatusBadge
                                        :status="product.requires_guarantor ? 'guarantor_pending' : 'approved'"
                                        :label="product.requires_guarantor ? `${product.guarantor_count} penjamin` : 'Tiada penjamin'"
                                    />
                                </div>
                                <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                    <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline" size="sm">Lihat Syarat</Button>
                                    <Button :as="Link" :href="product.apply_url" size="sm">
                                        Mohon Sekarang
                                        <ArrowRight class="ml-1.5 h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tanpa Penjamin categories -->
            <template v-if="nonGuaranteedCategories.length">
                <div class="flex items-center gap-3">
                    <HandCoins class="h-5 w-5 text-blue-700" />
                    <h2 class="text-lg font-semibold text-slate-950">Pembiayaan Tanpa Penjamin</h2>
                </div>
                <div v-for="category in nonGuaranteedCategories" :key="category.id" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold text-slate-950">{{ category.name }}</h3>
                            <StatusBadge :status="category.type" :label="category.type_label" />
                        </div>
                        <p class="text-sm leading-6 text-slate-600">{{ category.description || 'Kategori pembiayaan ini tersedia untuk ahli yang layak.' }}</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <article v-for="product in category.products" :key="product.id" class="flex flex-col rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                                <h4 class="text-base font-semibold text-slate-950">{{ product.name }}</h4>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ product.description || 'Produk pembiayaan tersedia untuk permohonan ahli.' }}</p>
                                <div class="mt-3 space-y-1 text-xs text-slate-500">
                                    <p>RM {{ product.min_amount?.toLocaleString('ms-MY') ?? '-' }} – RM {{ product.max_amount?.toLocaleString('ms-MY') ?? '-' }}</p>
                                    <p>{{ product.min_tenure_months || '-' }} – {{ product.max_tenure_months || '-' }} bulan</p>
                                    <p v-if="product.annual_rate_percent" class="font-medium text-teal-700">{{ product.annual_rate_percent }}% setahun</p>
                                </div>
                                <div class="mt-4 flex items-center gap-2">
                                    <StatusBadge
                                        :status="product.requires_guarantor ? 'guarantor_pending' : 'approved'"
                                        :label="product.requires_guarantor ? `${product.guarantor_count} penjamin` : 'Tiada penjamin'"
                                    />
                                </div>
                                <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                    <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline" size="sm">Lihat Syarat</Button>
                                    <Button :as="Link" :href="product.apply_url" size="sm">
                                        Mohon Sekarang
                                        <ArrowRight class="ml-1.5 h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </template>

            <EmptyState v-if="!categories.length" title="Tiada produk pembiayaan tersedia." description="Produk pembiayaan yang aktif akan dipaparkan di sini apabila tersedia. Sila hubungi koperasi untuk maklumat lanjut." />

            <!-- My applications summary -->
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="min-w-0 truncate text-lg font-semibold text-slate-950">Permohonan Saya</h2>
                    <Button :as="Link" href="/member/financing/applications" variant="outline">Lihat Semua</Button>
                </div>
                <div v-if="myApplications.length" class="mt-4 space-y-3">
                    <article v-for="application in myApplications.slice(0, 3)" :key="application.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-950">{{ application.reference_no }}</p>
                                <p class="text-sm text-slate-500">{{ application.product_name || '-' }} · {{ application.submitted_at }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <StatusBadge :status="application.status" :label="application.status_label" />
                                <Button :as="Link" :href="application.show_url" variant="outline" size="sm">Lihat</Button>
                            </div>
                        </div>
                    </article>
                </div>
                <EmptyState v-else title="Tiada permohonan pembiayaan." description="Permohonan yang anda hantar akan dipaparkan di sini untuk rujukan mudah." compact />
            </section>
        </section>
    </MemberLayout>
</template>
