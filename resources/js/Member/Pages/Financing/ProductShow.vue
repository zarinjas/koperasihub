<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calculator, Download, FileText, ImageIcon, Mail, PenBox, Phone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, required: true },
});

// ---- Financing Calculator ----
const calcAmount = ref(props.product.min_amount ?? '');
const calcTenure = ref(props.product.min_tenure_months ?? '');
const calcResult = ref(null);
const calcError = ref('');

function calculate() {
    calcError.value = '';
    calcResult.value = null;

    const amount = parseFloat(calcAmount.value);
    const tenure = parseInt(calcTenure.value, 10);
    const annualRate = props.product.annual_rate_percent;

    if (!amount || amount <= 0) { calcError.value = 'Sila masukkan jumlah pembiayaan yang sah.'; return; }
    if (!tenure || tenure <= 0) { calcError.value = 'Sila masukkan tempoh pembiayaan yang sah.'; return; }
    if (props.product.min_amount && amount < props.product.min_amount) { calcError.value = `Jumlah minimum ialah RM ${Number(props.product.min_amount).toLocaleString('ms-MY', { minimumFractionDigits: 2 })}.`; return; }
    if (props.product.max_amount && amount > props.product.max_amount) { calcError.value = `Jumlah maksimum ialah RM ${Number(props.product.max_amount).toLocaleString('ms-MY', { minimumFractionDigits: 2 })}.`; return; }
    if (props.product.min_tenure_months && tenure < props.product.min_tenure_months) { calcError.value = `Tempoh minimum ialah ${props.product.min_tenure_months} bulan.`; return; }
    if (props.product.max_tenure_months && tenure > props.product.max_tenure_months) { calcError.value = `Tempoh maksimum ialah ${props.product.max_tenure_months} bulan.`; return; }

    if (!annualRate) {
        // If no rate configured, show total only.
        calcResult.value = {
            monthly: null,
            total: amount,
            note: 'Kadar faedah belum ditetapkan. Sila hubungi koperasi.',
        };
        return;
    }

    // Simple flat-rate monthly installment: total = principal + (principal × annual_rate × years)
    // monthly = total / tenure_months
    const years = tenure / 12;
    const totalInterest = amount * (annualRate / 100) * years;
    const totalRepayment = amount + totalInterest;
    const monthly = totalRepayment / tenure;

    calcResult.value = {
        monthly: monthly.toFixed(2),
        total: totalRepayment.toFixed(2),
        note: null,
    };
}

const formattedMonthly = computed(() => {
    if (!calcResult.value?.monthly) return null;
    return parseFloat(calcResult.value.monthly).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

const formattedTotal = computed(() => {
    if (!calcResult.value?.total) return null;
    return parseFloat(calcResult.value.total).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});
</script>

<template>
    <Head :title="product.name" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader :title="product.name" description="Semak syarat utama produk pembiayaan ini sebelum memulakan permohonan.">
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
                <!-- Left: product details -->
                <section class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <StatusBadge :status="product.category?.type_label ? 'submitted' : 'active'" :label="product.category?.type_label || 'Produk Aktif'" />
                        <StatusBadge
                            :status="product.requires_guarantor ? 'guarantor_pending' : 'approved'"
                            :label="product.requires_guarantor ? `${product.guarantor_count} penjamin diperlukan` : 'Tiada penjamin diperlukan'"
                        />
                    </div>

                    <p class="text-sm leading-7 text-slate-600">{{ product.description || 'Produk pembiayaan ini tersedia untuk ahli yang layak.' }}</p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun</p>
                            <p class="mt-2 text-sm text-slate-700">RM {{ product.min_amount?.toLocaleString('ms-MY', { minimumFractionDigits: 2 }) ?? '-' }} hingga RM {{ product.max_amount?.toLocaleString('ms-MY', { minimumFractionDigits: 2 }) ?? '-' }}</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p>
                            <p class="mt-2 text-sm text-slate-700">{{ product.min_tenure_months || '-' }} hingga {{ product.max_tenure_months || '-' }} bulan</p>
                        </article>
                        <article v-if="product.annual_rate_percent" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kadar Faedah Tahunan</p>
                            <p class="mt-2 text-sm font-semibold text-teal-700">{{ product.annual_rate_percent }}% setahun</p>
                            <p v-if="product.rate_note" class="mt-1 text-xs text-slate-500">{{ product.rate_note }}</p>
                        </article>
                    </div>

                    <div class="grid gap-4">
                        <article v-if="product.eligibility_terms" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h2 class="text-base font-semibold text-slate-950">Syarat Kelayakan</h2>
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.eligibility_terms }}</p>
                        </article>

                        <article v-if="product.product_terms || product.application_notes" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h2 class="text-base font-semibold text-slate-950">Terma & Nota</h2>
                            <p v-if="product.product_terms" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.product_terms }}</p>
                            <p v-if="product.application_notes" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.application_notes }}</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h2 class="text-base font-semibold text-slate-950">Dokumen Diperlukan</h2>
                            <p v-if="product.required_documents_note" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.required_documents_note }}</p>
                            <div v-if="product.required_documents?.length" class="mt-4 space-y-3">
                                <article
                                    v-for="document in product.required_documents"
                                    :key="document"
                                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700"
                                >
                                    <FileText class="h-4 w-4 text-teal-700" />
                                    {{ document }}
                                </article>
                            </div>
                            <EmptyState
                                v-else
                                title="Tiada dokumen wajib ditetapkan."
                                description="Jika perlu, pihak koperasi mungkin akan meminta dokumen tambahan semasa semakan permohonan."
                                compact
                            />
                        </article>

                        <article v-if="product.product_documents?.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h2 class="text-base font-semibold text-slate-950">Dokumen Produk</h2>
                            <div class="mt-4 space-y-3">
                                <article
                                    v-for="document in product.product_documents"
                                    :key="document.key"
                                    class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="font-medium text-slate-900">{{ document.label }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ document.file_name }}</p>
                                    </div>
                                    <Button :as="Link" :href="document.download_url" variant="outline">
                                        <Download class="mr-2 h-4 w-4" />
                                        {{ document.download_label }}
                                    </Button>
                                </article>
                            </div>
                        </article>

                        <article v-if="product.officer_contact_name || product.officer_contact_phone || product.officer_contact_email" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h2 class="text-base font-semibold text-slate-950">Pegawai Untuk Dihubungi</h2>
                            <div class="mt-4 space-y-3 text-sm text-slate-700">
                                <p v-if="product.officer_contact_name" class="font-medium text-slate-900">{{ product.officer_contact_name }}</p>
                                <p v-if="product.officer_contact_phone" class="flex items-center gap-2"><Phone class="h-4 w-4 text-teal-700" /> {{ product.officer_contact_phone }}</p>
                                <p v-if="product.officer_contact_email" class="flex items-center gap-2"><Mail class="h-4 w-4 text-teal-700" /> {{ product.officer_contact_email }}</p>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- Right: calculator + rate image -->
                <div class="space-y-6">
                    <!-- Financing Calculator -->
                    <section class="rounded-3xl border border-teal-200 bg-gradient-to-br from-teal-50 to-blue-50 p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <Calculator class="h-5 w-5 text-teal-700" />
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Kalkulator Pembiayaan</h2>
                                <p class="text-sm text-slate-500">Anggaran ansuran bulanan berdasarkan kadar semasa.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="calc-amount">Jumlah Pembiayaan (RM)</label>
                                <input
                                    id="calc-amount"
                                    v-model="calcAmount"
                                    type="number"
                                    step="100"
                                    :min="product.min_amount || 0"
                                    :max="product.max_amount || undefined"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="Contoh: 10000"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="calc-tenure">Tempoh Pembiayaan (Bulan)</label>
                                <input
                                    id="calc-tenure"
                                    v-model="calcTenure"
                                    type="number"
                                    step="1"
                                    :min="product.min_tenure_months || 1"
                                    :max="product.max_tenure_months || undefined"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="Contoh: 24"
                                />
                            </div>

                            <p v-if="calcError" class="text-sm text-red-600">{{ calcError }}</p>

                            <Button type="button" class="w-full" @click="calculate">
                                <Calculator class="mr-2 h-4 w-4" />
                                Kira Ansuran
                            </Button>

                            <!-- Result -->
                            <div v-if="calcResult" class="rounded-2xl border border-teal-200 bg-white p-4 space-y-3">
                                <div v-if="formattedMonthly">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Anggaran Ansuran Bulanan</p>
                                    <p class="mt-1 text-2xl font-bold text-teal-700">RM {{ formattedMonthly }}</p>
                                </div>
                                <div v-if="formattedTotal && formattedMonthly">
                                    <p class="text-xs text-slate-500">Jumlah Bayaran Balik: <strong class="text-slate-700">RM {{ formattedTotal }}</strong></p>
                                </div>
                                <p v-if="calcResult.note" class="text-sm text-slate-600">{{ calcResult.note }}</p>
                                <p class="text-xs text-slate-400 border-t border-slate-100 pt-3">
                                    Jumlah ini adalah anggaran dan tertakluk kepada semakan koperasi. Kadar dan tempoh akhir ditentukan semasa kelulusan.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Rate image -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <ImageIcon class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h2 class="text-base font-semibold text-slate-950">Jadual Kadar Pembiayaan</h2>
                                    <p class="text-sm text-slate-500">Paparan responsif untuk rujukan kadar semasa.</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="product.rate_image_url" class="bg-slate-50 p-4">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <img :src="product.rate_image_url" alt="Jadual kadar pembiayaan" class="h-auto max-h-[38rem] w-full object-contain" />
                            </div>
                        </div>
                        <div v-else class="flex min-h-40 items-center justify-center p-8 text-center text-sm text-slate-500">
                            Jadual kadar pembiayaan belum dimuat naik untuk produk ini.
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <!-- Bottom CTA bar -->
        <div class="mt-6 rounded-3xl border border-teal-200 bg-gradient-to-r from-teal-50 to-blue-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-slate-950">Bersedia untuk memohon?</p>
                    <p class="mt-1 text-sm text-slate-600">Semak semula syarat kelayakan dan dokumen yang diperlukan sebelum memulakan permohonan.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button :as="Link" href="/member/financing" variant="outline" class="bg-white">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali ke Senarai
                    </Button>
                    <Button :as="Link" :href="product.apply_url">
                        <PenBox class="mr-2 h-4 w-4" />
                        Mohon Sekarang
                    </Button>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
