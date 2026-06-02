<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalculatorIcon, HandCoins, Percent } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import DecorativeBlobs from '@/Shared/Components/DecorativeBlobs.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    products: { type: Array, required: true },
});

const formatNum = (val) => {
    if (val == null || isNaN(val)) return '0.00';
    return Number(val).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const selectedProductId = ref(null);
const calcAmount = ref(0);
const calcTenure = ref(12);
const calcRate = ref(0);
const useCustomRate = ref(false);

const selectedProduct = computed(() => props.products.find((p) => p.id === selectedProductId.value) ?? null);

watch(selectedProduct, (product) => {
    if (!product) return;
    const minAmt = Number(product.min_amount) || 0;
    const maxAmt = Number(product.max_amount) || 0;
    const minTen = Number(product.min_tenure_months) || 12;
    const maxTen = Number(product.max_tenure_months) || 12;
    calcAmount.value = minAmt > 0 ? minAmt + Math.round((maxAmt - minAmt) / 2) : 0;
    calcTenure.value = Math.round(minTen + (maxTen - minTen) / 2);
    calcRate.value = Number(product.annual_rate_percent) || 0;
    useCustomRate.value = false;
});

const effectiveRate = computed(() => useCustomRate.value ? calcRate.value : (selectedProduct.value?.annual_rate_percent ?? 0));

const calcMonthlyInstallment = computed(() => {
    const p = calcAmount.value;
    const r = effectiveRate.value / 100;
    const n = calcTenure.value;
    if (!p || !r || !n || n <= 0) return 0;
    const totalInterest = p * r * (n / 12);
    return (p + totalInterest) / n;
});

const calcTotalRepayment = computed(() => calcMonthlyInstallment.value * calcTenure.value);

const calcTotalProfit = computed(() => calcTotalRepayment.value - calcAmount.value);

const quickAmounts = computed(() => {
    const product = selectedProduct.value;
    if (!product) return [];
    const min = Number(product.min_amount) || 0;
    const max = Number(product.max_amount) || 0;
    return [25, 50, 75, 100].map((pct) => ({
        pct,
        value: Math.round(min + ((max - min) * pct / 100)),
    }));
});

const quickAmount = (pct) => {
    const product = selectedProduct.value;
    if (!product) return 0;
    const min = Number(product.min_amount) || 0;
    const max = Number(product.max_amount) || 0;
    return Math.round(min + ((max - min) * pct / 100));
};

const hasRate = computed(() => (selectedProduct.value?.annual_rate_percent ?? null) !== null);
</script>

<template>
    <Head title="Kalkulator Pembiayaan" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Kalkulator Pembiayaan"
                description="Kira anggaran ansuran bulanan sebelum membuat permohonan."
            />

            <div v-if="!products.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <EmptyState
                    title="Tiada produk tersedia"
                    description="Produk pembiayaan belum ditetapkan oleh admin. Sila semula kemudian."
                    compact
                />
            </div>

            <template v-else>
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <label class="text-sm font-medium text-slate-800">Pilih Produk Pembiayaan</label>
                    <select
                        v-model="selectedProductId"
                        class="mt-1.5 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                    >
                        <option :value="null" disabled>-- Pilih produk --</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }} — {{ product.category_name }}
                        </option>
                    </select>
                </section>

                <div v-if="selectedProduct" class="grid gap-6 lg:grid-cols-5">
                    <section class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 to-blue-50 p-6 shadow-sm lg:col-span-3">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-100 text-teal-700">
                                <CalculatorIcon class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">{{ selectedProduct.name }}</h2>
                                <p class="text-xs text-slate-500">{{ selectedProduct.category_name }}</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="text-sm font-medium text-slate-800">Jumlah Pembiayaan (RM)</label>
                                <input
                                    type="number"
                                    v-model.number="calcAmount"
                                    :min="selectedProduct.min_amount || 0"
                                    :max="selectedProduct.max_amount || 0"
                                    class="mt-1.5 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                                />
                                <div v-if="quickAmounts.length" class="mt-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="qa in quickAmounts"
                                        :key="qa.pct"
                                        type="button"
                                        @click="calcAmount = qa.value"
                                        class="rounded-lg border border-teal-200 px-3 py-1 text-xs font-medium text-teal-700 transition hover:bg-teal-100"
                                        :class="{ 'bg-teal-100 ring-1 ring-teal-500': calcAmount === qa.value }"
                                    >
                                        {{ qa.pct }}%
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Min: RM {{ formatNum(selectedProduct.min_amount) }}
                                    · Maks: RM {{ formatNum(selectedProduct.max_amount) }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-slate-800">Tempoh (bulan)</label>
                                <input
                                    type="number"
                                    v-model.number="calcTenure"
                                    :min="selectedProduct.min_tenure_months || 1"
                                    :max="selectedProduct.max_tenure_months || 1"
                                    class="mt-1.5 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                                />
                                <p class="mt-1 text-xs text-slate-500">
                                    Min: {{ selectedProduct.min_tenure_months }} bln
                                    · Maks: {{ selectedProduct.max_tenure_months }} bln
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-medium text-slate-800">Kadar Keuntungan (%)</label>
                                    <label v-if="hasRate" class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-500">
                                        <input
                                            type="checkbox"
                                            v-model="useCustomRate"
                                            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        Ubah kadar
                                    </label>
                                </div>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="calcRate"
                                    :disabled="!useCustomRate"
                                    class="mt-1.5 h-11 w-full rounded-lg border px-3 text-sm shadow-sm focus:outline-none focus:ring-2"
                                    :class="useCustomRate
                                        ? 'border-slate-300 bg-white focus:border-teal-700 focus:ring-teal-700/20'
                                        : 'border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed'"
                                />
                                <p v-if="!useCustomRate && hasRate" class="mt-1 text-xs text-slate-500">
                                    Kadar dari produk: {{ selectedProduct.annual_rate_percent }}% setahun
                                </p>
                                <p v-if="!hasRate" class="mt-1 text-xs text-amber-600">
                                    Kadar belum ditetapkan untuk produk ini. Sila masukkan kadar secara manual atau pilih produk lain.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="relative overflow-hidden rounded-3xl border border-teal-100 bg-white p-6 shadow-sm lg:col-span-2">
                        <DecorativeBlobs color="teal" />
                        <div class="relative">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Anggaran Ansuran</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-teal-700">
                                RM {{ formatNum(calcMonthlyInstallment) }}
                            </p>
                            <p class="text-sm text-slate-500">sebulan</p>

                            <div v-if="calcMonthlyInstallment > 0" class="mt-6 space-y-3 border-t border-slate-100 pt-5">
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <span class="text-sm text-slate-600">Jumlah Bayaran Balik</span>
                                    <span class="text-base font-semibold text-slate-950">RM {{ formatNum(calcTotalRepayment) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <span class="text-sm text-slate-600">Jumlah Keuntungan</span>
                                    <span class="text-base font-semibold text-emerald-600">RM {{ formatNum(calcTotalProfit) }}</span>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <Button
                                    :as="Link"
                                    :href="selectedProduct.apply_url"
                                    class="w-full"
                                >
                                    <HandCoins class="mr-2 h-4 w-4" />
                                    Mohon Sekarang
                                </Button>
                                <Button
                                    :as="Link"
                                    :href="`/member/financing/products/${selectedProduct.id}`"
                                    variant="outline"
                                    class="w-full"
                                >
                                    Lihat Detail Produk
                                </Button>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 shadow-sm">
                    <div class="text-center">
                        <CalculatorIcon class="mx-auto h-12 w-12 text-slate-300" />
                        <h3 class="mt-4 text-base font-semibold text-slate-950">Sila pilih produk</h3>
                        <p class="mt-1 text-sm text-slate-500">Pilih produk pembiayaan untuk mula mengira anggaran ansuran.</p>
                    </div>
                </div>
            </template>
        </section>
    </MemberLayout>
</template>