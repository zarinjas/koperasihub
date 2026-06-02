<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle, Clock, Coins, FileText, HandCoins, Percent, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    categories: { type: Array, required: true },
    products: { type: Array, required: true },
    applications: { type: Array, required: true },
    memberLinked: { type: Boolean, default: false },
});

const activeCategory = ref(props.categories[0]?.id ?? null);

const currentCategory = computed(() => props.categories.find((c) => c.id === activeCategory.value));

const currentProducts = computed(() => {
    if (!activeCategory.value) return [];
    const group = props.products.find((g) => g.category_id === activeCategory.value);
    return group?.items ?? [];
});

const fmt = (val) => val != null ? 'RM ' + Number(val).toLocaleString('en-MY', { minimumFractionDigits: 0 }) : '-';
</script>

<template>
    <Head title="Pembiayaan" />

    <MemberLayout>
        <div class="space-y-6">
            <PageHeader
                title="Pembiayaan"
                description="Lihat produk pembiayaan dan semak status permohonan anda."
            >
                <template #actions>
                    <Button :as="Link" href="/member/financing/applications/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Mohon Sekarang
                    </Button>
                </template>
            </PageHeader>

            <!-- Permohonan Terkini -->
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Permohonan Terkini</h2>
                        <p class="mt-0.5 text-sm text-slate-500">5 permohonan terbaharu anda.</p>
                    </div>
                    <Link href="/member/financing/applications" class="text-sm font-medium text-teal-700 hover:underline">
                        Semua permohonan
                    </Link>
                </div>

                <div v-if="applications.length === 0" class="mt-5">
                    <EmptyState
                        title="Tiada permohonan"
                        description="Anda belum membuat sebarang permohonan. Terokai produk di bawah."
                        compact
                    />
                </div>

                <div v-else class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="app in applications"
                        :key="app.id"
                        :href="`/member/financing/applications/${app.id}`"
                        class="group flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-200 hover:bg-white hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-950">{{ app.reference_no }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ app.product_name || '-' }}</p>
                            </div>
                            <StatusBadge :status="app.status" :label="app.status_label" class="shrink-0" />
                        </div>
                        <div class="flex items-center gap-4 text-xs text-slate-600">
                            <span class="flex items-center gap-1">
                                <Coins class="h-3.5 w-3.5 text-slate-400" />
                                {{ app.amount_requested }}
                            </span>
                            <span v-if="app.tenure_months" class="flex items-center gap-1">
                                <Clock class="h-3.5 w-3.5 text-slate-400" />
                                {{ app.tenure_months }} bln
                            </span>
                            <span v-if="app.submitted_at" class="flex items-center gap-1">
                                <CheckCircle class="h-3.5 w-3.5 text-slate-400" />
                                {{ app.submitted_at }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 text-xs font-medium text-teal-700 group-hover:underline">
                            Lihat butiran <ArrowRight class="h-3 w-3" />
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Produk Pembiayaan -->
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-950">Produk Pembiayaan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih kategori untuk melihat produk yang tersedia.</p>
                </div>

                <!-- Tab Kategori -->
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="activeCategory === cat.id
                            ? 'bg-teal-700 text-white shadow-sm'
                            : 'border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                        @click="activeCategory = cat.id"
                    >
                        {{ cat.name }}
                        <span
                            class="ml-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-xs"
                            :class="activeCategory === cat.id ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'"
                        >{{ cat.products_count }}</span>
                    </button>
                </div>

                <p v-if="currentCategory?.description" class="mt-3 text-sm text-slate-500">
                    {{ currentCategory.description }}
                </p>

                <div v-if="currentProducts.length === 0" class="mt-6">
                    <EmptyState title="Tiada produk" description="Tiada produk dalam kategori ini." compact />
                </div>

                <div v-else class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="product in currentProducts"
                        :key="product.id"
                        class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-teal-200 hover:shadow-md"
                    >
                        <div class="flex-1 space-y-3">
                            <h3 class="text-sm font-semibold text-slate-950">{{ product.name }}</h3>
                            <p v-if="product.description" class="line-clamp-2 text-sm leading-6 text-slate-500">{{ product.description }}</p>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-xl bg-white px-3 py-2 shadow-sm">
                                    <p class="text-slate-400">Jumlah</p>
                                    <p class="font-semibold text-slate-950">{{ fmt(product.min_amount) }} – {{ fmt(product.max_amount) }}</p>
                                </div>
                                <div class="rounded-xl bg-white px-3 py-2 shadow-sm">
                                    <p class="text-slate-400">Kadar</p>
                                    <p class="flex items-center gap-0.5 font-semibold text-teal-700">
                                        <Percent class="h-3 w-3" />{{ product.annual_rate_percent ?? '-' }}% setahun
                                    </p>
                                </div>
                                <div class="col-span-2 rounded-xl bg-white px-3 py-2 shadow-sm">
                                    <p class="text-slate-400">Tempoh</p>
                                    <p class="font-semibold text-slate-950 flex items-center gap-1">
                                        <Clock class="h-3 w-3 text-slate-400" />
                                        {{ product.min_tenure_months ?? '-' }} – {{ product.max_tenure_months ?? '-' }} bulan
                                    </p>
                                </div>
                            </div>

                            <div v-if="product.requires_guarantor" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                Memerlukan {{ product.guarantor_count }} penjamin
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline" size="sm" class="flex-1">
                                <FileText class="mr-1.5 h-3.5 w-3.5" />
                                Maklumat
                            </Button>
                            <Button :as="Link" :href="product.apply_url" size="sm" class="flex-1">
                                <HandCoins class="mr-1.5 h-3.5 w-3.5" />
                                Mohon
                            </Button>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>