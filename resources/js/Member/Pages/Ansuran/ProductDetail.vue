<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Search, ShoppingBag } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, required: true },
    tenures: { type: Array, required: true },
    member: { type: Object, default: null },
});

const selectedVariant = ref(props.product.variants[0] || null);
const selectedTenure = ref(props.tenures[0] || null);
const downPaymentPercent = ref(props.product.min_down_payment_percent);
const showApplyForm = ref(false);
const searchingGuarantors = ref(false);
const guarantorQuery = ref('');
const foundMembers = ref([]);

const selectedGuarantors = ref([]);

const fullPrice = computed(() => selectedVariant.value?.price || 0);
const minDownPayment = computed(() => fullPrice.value * (props.product.min_down_payment_percent / 100));

const downPaymentAmount = computed(() => {
    const pct = Math.max(downPaymentPercent.value, props.product.min_down_payment_percent);
    return fullPrice.value * (pct / 100);
});

const financedAmount = computed(() => fullPrice.value - downPaymentAmount.value);
const monthlyAmount = computed(() => {
    if (!selectedTenure.value) return 0;
    const rate = selectedTenure.value.interest_rate_percent;
    const months = selectedTenure.value.months;
    const years = months / 12;
    const interest = financedAmount.value * (rate / 100) * years;
    const total = financedAmount.value + interest;
    return months > 0 ? (total / months) : 0;
});
const totalPayable = computed(() => monthlyAmount.value * (selectedTenure.value?.months || 0));

const activeImage = ref(props.product.images[0]?.url || null);

const form = useForm({
    product_id: props.product.id,
    variant_id: '',
    tenure_option_id: '',
    down_payment: 0,
    delivery_method: 'pickup',
    delivery_address: '',
    guarantor_member_ids: [],
    notes: '',
});

const openApplyForm = () => {
    form.variant_id = selectedVariant.value?.id || '';
    form.tenure_option_id = selectedTenure.value?.id || '';
    form.down_payment = downPaymentAmount.value;
    showApplyForm.value = true;
};

const searchMembers = async () => {
    if (guarantorQuery.value.length < 2) return;
    searchingGuarantors.value = true;
    try {
        const res = await fetch('/member/ansuran/member-search' + '?q=' + encodeURIComponent(guarantorQuery.value));
        foundMembers.value = await res.json();
    } finally {
        searchingGuarantors.value = false;
    }
};

const addGuarantor = (member) => {
    if (selectedGuarantors.value.length >= props.product.guarantor_count) return;
    if (selectedGuarantors.value.find(m => m.id === member.id)) return;
    selectedGuarantors.value.push(member);
    form.guarantor_member_ids = selectedGuarantors.value.map(m => m.id);
    guarantorQuery.value = '';
    foundMembers.value = [];
};

const removeGuarantor = (member) => {
    selectedGuarantors.value = selectedGuarantors.value.filter(m => m.id !== member.id);
    form.guarantor_member_ids = selectedGuarantors.value.map(m => m.id);
};

const submit = () => {
    form.post('/member/ansuran/apply');
};
</script>

<template>
    <MemberLayout>
        <Head :title="product.name" />
        <section class="space-y-6">
            <Button variant="ghost" size="sm" @click="window.history.back()"><ArrowLeft class="mr-1 h-4 w-4" /> Kembali</Button>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="space-y-4">
                    <div v-if="activeImage" class="aspect-square overflow-hidden rounded-3xl bg-slate-100">
                        <img :src="activeImage" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="flex aspect-square items-center justify-center rounded-3xl bg-slate-100 text-slate-400"><ShoppingBag class="h-24 w-24" /></div>
                    <div v-if="product.images.length > 1" class="flex gap-2">
                        <button v-for="img in product.images" :key="img.id" @click="activeImage = img.url" class="h-20 w-20 overflow-hidden rounded-xl border-2 transition" :class="activeImage === img.url ? 'border-teal-700' : 'border-slate-200'">
                            <img :src="img.url" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ product.category_name }}</span>
                        <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ product.name }}</h1>
                    </div>

                    <div v-if="product.description" class="prose prose-sm text-slate-600" v-html="product.description" />

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950">Pilih Varian</h2>
                        <p class="text-sm text-slate-600">Pilih varian produk yang anda inginkan.</p>
                        <div class="mt-4 grid grid-cols-1 gap-2">
                            <button v-for="v in product.variants" :key="v.id" @click="selectedVariant = v" class="flex items-center justify-between rounded-xl border p-4 text-left transition" :class="selectedVariant?.id === v.id ? 'border-teal-700 bg-teal-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <div>
                                    <div class="font-medium text-slate-950">{{ v.name }}</div>
                                    <div v-if="v.attributes" class="mt-0.5 text-sm text-slate-500">
                                        <span v-for="(val, key) in v.attributes" :key="key">{{ key }}: {{ val }} </span>
                                    </div>
                                </div>
                                <div class="text-lg font-bold text-slate-950">{{ v.formatted_price }}</div>
                            </button>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 to-blue-50 p-6 shadow-sm">
                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-teal-700 shadow-sm">
                                    <ShoppingBag class="h-5 w-5" />
                                </span>
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-950">Kalkulator Ansuran</h2>
                                    <p class="text-sm text-slate-600">Kira anggaran bayaran bulanan anda.</p>
                                </div>
                            </div>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-slate-800">Bayaran Pendahuluan (%)</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input v-model.number="downPaymentPercent" type="number" :min="product.min_down_payment_percent" :max="90" step="5" class="h-10 w-24 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                                        <span class="text-sm font-medium text-slate-600">RM {{ downPaymentAmount.toFixed(2) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-slate-800">Tempoh Ansuran</label>
                                    <select v-model="selectedTenure" class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20">
                                        <option v-for="t in tenures" :key="t.id" :value="t">{{ t.label }}</option>
                                    </select>
                                </div>
                                <div class="space-y-2 rounded-xl border border-white/70 bg-white/80 p-4 shadow-sm">
                                    <div class="flex justify-between text-sm"><span class="text-slate-500">Harga Produk</span><span class="text-slate-950">RM {{ fullPrice.toFixed(2) }}</span></div>
                                    <div class="flex justify-between text-sm"><span class="text-slate-500">Bayaran Pendahuluan</span><span class="text-emerald-600">- RM {{ downPaymentAmount.toFixed(2) }}</span></div>
                                    <div class="flex justify-between text-sm"><span class="text-slate-500">Jumlah Pembiayaan</span><span class="text-slate-950">RM {{ financedAmount.toFixed(2) }}</span></div>
                                    <div class="flex justify-between text-sm"><span class="text-slate-500">Kadar Keuntungan</span><span class="text-slate-950">{{ selectedTenure?.interest_rate_percent || 0 }}%</span></div>
                                    <div class="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold"><span class="text-slate-950">Bayaran Bulanan</span><span class="text-teal-700">RM {{ monthlyAmount.toFixed(2) }}</span></div>
                                    <div class="flex justify-between text-xs text-slate-400"><span>Jumlah Perlu Dibayar</span><span>RM {{ totalPayable.toFixed(2) }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button size="lg" class="w-full" @click="openApplyForm">Mohon Sekarang</Button>
                </div>
            </div>

            <div v-if="showApplyForm" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-xl space-y-5">
                    <div>
                        <h2 class="text-xl font-bold text-slate-950">Permohonan Ansuran Mudah</h2>
                        <p class="text-sm text-slate-600">Sila semak dan sahkan butiran permohonan.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm">
                        <div><span class="text-slate-500">Produk:</span><span class="block font-medium text-slate-950">{{ product.name }}</span></div>
                        <div><span class="text-slate-500">Varian:</span><span class="block font-medium text-slate-950">{{ selectedVariant?.name }}</span></div>
                        <div><span class="text-slate-500">Harga:</span><span class="block font-medium text-slate-950">RM {{ fullPrice.toFixed(2) }}</span></div>
                        <div><span class="text-slate-500">Bulanan:</span><span class="block font-medium text-slate-950">RM {{ monthlyAmount.toFixed(2) }}</span></div>
                        <div><span class="text-slate-500">Tempoh:</span><span class="block font-medium text-slate-950">{{ selectedTenure?.months }} Bulan</span></div>
                        <div><span class="text-slate-500">Down Payment:</span><span class="block font-medium text-slate-950">RM {{ downPaymentAmount.toFixed(2) }}</span></div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-800">Kaedah Penerimaan</label>
                        <select v-model="form.delivery_method" class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20">
                            <option value="pickup">Ambil Sendiri</option>
                            <option value="delivery">Penghantaran</option>
                        </select>
                    </div>

                    <div v-if="form.delivery_method === 'delivery'">
                        <label class="text-sm font-medium text-slate-800">Alamat Penghantaran</label>
                        <textarea v-model="form.delivery_address" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                    </div>

                    <template v-if="product.guarantor_count > 0">
                        <div>
                            <label class="text-sm font-medium text-slate-800">Penjamin ({{ selectedGuarantors.length }}/{{ product.guarantor_count }})</label>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="g in selectedGuarantors" :key="g.id" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700 cursor-pointer hover:bg-slate-100" @click="removeGuarantor(g)">{{ g.name }} &times;</span>
                            </div>
                            <div v-if="selectedGuarantors.length < product.guarantor_count" class="mt-2 flex gap-2">
                                <input v-model="guarantorQuery" placeholder="Cari ahli..." class="h-10 flex-1 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" @keyup.enter="searchMembers" />
                                <Button variant="outline" size="sm" :disabled="searchingGuarantors" @click="searchMembers">{{ searchingGuarantors ? 'Mencari...' : 'Cari' }}</Button>
                            </div>
                            <div v-if="foundMembers.length > 0" class="mt-2 max-h-40 divide-y overflow-y-auto rounded-lg border border-slate-200">
                                <button v-for="m in foundMembers" :key="m.id" @click="addGuarantor(m)" class="block w-full px-3 py-2 text-left text-sm text-slate-950 hover:bg-slate-50">{{ m.name }} ({{ m.member_no }})</button>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="text-sm font-medium text-slate-800">Nota Tambahan <span class="text-slate-400">(pilihan)</span></label>
                        <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="w-full sm:w-auto" @click="showApplyForm = false">Batal</Button>
                        <Button class="w-full sm:w-auto" :disabled="form.processing" @click="submit">{{ form.processing ? 'Menghantar...' : 'Hantar Permohonan' }}</Button>
                    </div>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>
