<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileText, PenBox } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    product: { type: Object, required: true },
});
</script>

<template>
    <Head :title="product.name" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader :title="product.name" description="Semak butiran produk pembiayaan sebelum memulakan permohonan.">
                <template #actions>
                    <Button :as="Link" href="/member/financing" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <Button :as="Link" :href="product.apply_url">
                        <PenBox class="mr-2 h-4 w-4" />
                        Mohon Sekarang
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <section class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <StatusBadge :status="product.category?.type_label ? 'submitted' : 'active'" :label="product.category?.type_label || 'Produk Aktif'" />
                        <StatusBadge :status="product.requires_guarantor ? 'approved' : 'inactive'" :label="product.requires_guarantor ? `${product.guarantor_count} Penjamin` : 'Tanpa Penjamin'" />
                    </div>
                    <p class="text-sm leading-7 text-slate-600">{{ product.description || 'Produk pembiayaan ini tersedia untuk ahli yang layak.' }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun</p><p class="mt-2 text-sm text-slate-700">RM {{ product.min_amount ?? '-' }} hingga RM {{ product.max_amount ?? '-' }}</p></article>
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-2 text-sm text-slate-700">{{ product.min_tenure_months || '-' }} hingga {{ product.max_tenure_months || '-' }} bulan</p></article>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Dokumen Diperlukan</h2>
                        <div v-if="product.required_documents?.length" class="mt-4 space-y-3">
                            <article v-for="document in product.required_documents" :key="document" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <FileText class="h-4 w-4 text-teal-700" />
                                {{ document }}
                            </article>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-600">Tiada dokumen wajib ditetapkan buat masa ini.</p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <img v-if="product.category?.rate_image_url" :src="product.category.rate_image_url" alt="Jadual Kadar Pembiayaan" class="h-full w-full object-cover" />
                    <div v-else class="flex h-full min-h-80 items-center justify-center p-8 text-center text-sm text-slate-500">
                        Jadual kadar pembiayaan belum dimuat naik untuk kategori ini.
                    </div>
                </section>
            </div>
        </section>
    </MemberLayout>
</template>
