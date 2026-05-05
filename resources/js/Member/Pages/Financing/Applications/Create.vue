<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Download, FileText, ImageIcon, Mail, Search, ShieldCheck, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, required: true },
    member: { type: Object, required: true },
    guarantorSearchUrl: { type: String, required: true },
});

const CONTENT_TYPES = ['instruction_text', 'note', 'rich_text'];

const initialCustomAnswers = Object.fromEntries(
    (props.product.product_fields ?? [])
        .filter((f) => !CONTENT_TYPES.includes(f.type))
        .map((f) => [f.field_key, f.type === 'checkbox' ? [] : '']),
);

const form = useForm({
    financing_product_id: props.product.id,
    amount_requested: '',
    tenure_months: '',
    purpose: '',
    monthly_income: '',
    monthly_commitment: '',
    employment_notes: '',
    guarantor_member_ids: [],
    documents: [],
    custom_answers: initialCustomAnswers,
});

const guarantorSearch = ref('');
const guarantorResults = ref([]);
const guarantorSearchError = ref('');
const guarantorSearchLoading = ref(false);
const selectedGuarantors = ref([]);

const selectedGuarantorIds = computed(() => form.guarantor_member_ids);
const remainingGuarantorSlots = computed(() => Math.max((props.product.guarantor_count || 0) - selectedGuarantorIds.value.length, 0));
const selectedDocumentNames = computed(() => form.documents.map((file) => file.name));

const searchGuarantors = async () => {
    guarantorSearchError.value = '';

    if (!guarantorSearch.value.trim()) {
        guarantorResults.value = [];
        guarantorSearchError.value = 'Masukkan nama, nombor ahli, atau nombor staf untuk mencari penjamin.';
        return;
    }

    guarantorSearchLoading.value = true;

    try {
        const response = await fetch(`${props.guarantorSearchUrl}?search=${encodeURIComponent(guarantorSearch.value)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            guarantorSearchError.value = 'Carian penjamin tidak berjaya. Sila cuba lagi.';
            return;
        }

        const payload = await response.json();
        guarantorResults.value = payload.results || [];

        if (!guarantorResults.value.length) {
            guarantorSearchError.value = 'Tiada penjamin yang sepadan ditemui.';
        }
    } finally {
        guarantorSearchLoading.value = false;
    }
};

const rememberSelectedGuarantor = (result) => {
    if (selectedGuarantors.value.some((item) => item.id === result.id)) {
        return;
    }

    selectedGuarantors.value = [...selectedGuarantors.value, result];
};

const toggleGuarantor = (result) => {
    if (form.guarantor_member_ids.includes(result.id)) {
        form.guarantor_member_ids = form.guarantor_member_ids.filter((id) => id !== result.id);
        selectedGuarantors.value = selectedGuarantors.value.filter((item) => item.id !== result.id);
        return;
    }

    if (form.guarantor_member_ids.length >= props.product.guarantor_count) {
        guarantorSearchError.value = `Anda hanya boleh memilih ${props.product.guarantor_count} penjamin.`;
        return;
    }

    form.guarantor_member_ids = [...form.guarantor_member_ids, result.id];
    rememberSelectedGuarantor(result);
};

const removeSelectedGuarantor = (guarantorId) => {
    form.guarantor_member_ids = form.guarantor_member_ids.filter((id) => id !== guarantorId);
    selectedGuarantors.value = selectedGuarantors.value.filter((item) => item.id !== guarantorId);
};

const submit = () => {
    form.post('/member/financing/applications', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const onDocumentsChange = (event) => {
    form.documents = Array.from(event.target.files || []);
};
</script>

<template>
    <Head title="Permohonan Pembiayaan Baharu" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permohonan Pembiayaan Baharu" description="Semak produk, sediakan dokumen, dan lengkapkan maklumat permohonan anda dengan jelas.">
                <template #actions>
                    <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <form class="space-y-6" @submit.prevent="submit">
                    <FormSection title="Butiran Produk" description="Maklumat produk yang anda pilih untuk permohonan ini." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ product.name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ product.category_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Dibenarkan</p><p class="mt-1 text-sm text-slate-700">RM {{ product.min_amount ?? '-' }} hingga RM {{ product.max_amount ?? '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh Dibenarkan</p><p class="mt-1 text-sm text-slate-700">{{ product.min_tenure_months || '-' }} hingga {{ product.max_tenure_months || '-' }} bulan</p></div>
                        <div class="md:col-span-2 flex flex-wrap gap-3">
                            <StatusBadge :status="product.requires_guarantor ? 'guarantor_pending' : 'approved'" :label="product.requires_guarantor ? `${product.guarantor_count} penjamin diperlukan` : 'Tiada penjamin diperlukan'" />
                            <StatusBadge status="active" label="Produk aktif" />
                        </div>
                    </FormSection>

                    <FormSection title="Pengenalan Permohonan" description="Sila semak syarat, terma, dan arahan sebelum melengkapkan permohonan." :columns="1">
                        <div v-if="product.eligibility_terms" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">Syarat Kelayakan</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.eligibility_terms }}</p>
                        </div>
                        <div v-if="product.product_terms || product.application_notes" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">Terma & Nota</p>
                            <p v-if="product.product_terms" class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.product_terms }}</p>
                            <p v-if="product.application_notes" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.application_notes }}</p>
                        </div>
                        <div v-if="product.application_instructions" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">Arahan Permohonan</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">{{ product.application_instructions }}</p>
                        </div>
                    </FormSection>

                    <FormSection title="Maklumat Permohonan" description="Isikan butiran yang diperlukan untuk semakan pembiayaan." :columns="2">
                        <TextInput id="amount-requested" v-model="form.amount_requested" label="Amaun Dimohon (RM)" type="number" :error="form.errors.amount_requested" />
                        <TextInput id="tenure-months" v-model="form.tenure_months" label="Tempoh (Bulan)" type="number" :error="form.errors.tenure_months" />
                        <TextInput id="monthly-income" v-model="form.monthly_income" label="Pendapatan Bulanan (RM)" type="number" :error="form.errors.monthly_income" />
                        <TextInput id="monthly-commitment" v-model="form.monthly_commitment" label="Komitmen Bulanan (RM)" type="number" :error="form.errors.monthly_commitment" />
                        <div class="md:col-span-2">
                            <TextareaInput id="purpose" v-model="form.purpose" label="Tujuan Pembiayaan" :error="form.errors.purpose" />
                        </div>
                        <div class="md:col-span-2">
                            <TextareaInput id="employment-notes" v-model="form.employment_notes" label="Catatan Pekerjaan" :error="form.errors.employment_notes" />
                        </div>
                    </FormSection>

                    <FormSection v-if="product.product_fields?.length" title="Maklumat Tambahan" description="Sila lengkapkan maklumat tambahan yang diperlukan untuk produk ini." :columns="1">
                        <template v-for="field in product.product_fields" :key="field.id">
                            <!-- Content block: instruction / note / rich_text -->
                            <div v-if="['instruction_text', 'note', 'rich_text'].includes(field.type)" class="rounded-2xl border border-amber-100 bg-amber-50 p-4 text-sm leading-7 text-slate-700 whitespace-pre-line">
                                <p class="font-semibold text-slate-900 mb-1">{{ field.label }}</p>
                                <p>{{ field.help_text || field.label }}</p>
                            </div>

                            <!-- Textarea fields -->
                            <div v-else-if="field.type === 'long_text'">
                                <label :for="`cf-${field.field_key}`" class="block text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></label>
                                <textarea
                                    :id="`cf-${field.field_key}`"
                                    v-model="form.custom_answers[field.field_key]"
                                    :placeholder="field.placeholder || ''"
                                    rows="4"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                />
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Select dropdown -->
                            <div v-else-if="field.type === 'select'">
                                <label :for="`cf-${field.field_key}`" class="block text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></label>
                                <select
                                    :id="`cf-${field.field_key}`"
                                    v-model="form.custom_answers[field.field_key]"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="">-- Pilih --</option>
                                    <option v-for="opt in (field.options_json || [])" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Radio -->
                            <div v-else-if="field.type === 'radio'">
                                <p class="text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></p>
                                <div class="mt-2 space-y-2">
                                    <label v-for="opt in (field.options_json || [])" :key="opt" class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input v-model="form.custom_answers[field.field_key]" type="radio" :name="`cf-${field.field_key}`" :value="opt" class="text-teal-600 focus:ring-teal-500" />
                                        {{ opt }}
                                    </label>
                                </div>
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Checkbox (multi) -->
                            <div v-else-if="field.type === 'checkbox'">
                                <p class="text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></p>
                                <div class="mt-2 space-y-2">
                                    <label v-for="opt in (field.options_json || [])" :key="opt" class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input v-model="form.custom_answers[field.field_key]" type="checkbox" :value="opt" class="rounded text-teal-600 focus:ring-teal-500" />
                                        {{ opt }}
                                    </label>
                                </div>
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Yes/No -->
                            <div v-else-if="field.type === 'yes_no'">
                                <p class="text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></p>
                                <div class="mt-2 flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input v-model="form.custom_answers[field.field_key]" type="radio" :name="`cf-${field.field_key}`" value="Ya" class="text-teal-600 focus:ring-teal-500" /> Ya
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input v-model="form.custom_answers[field.field_key]" type="radio" :name="`cf-${field.field_key}`" value="Tidak" class="text-teal-600 focus:ring-teal-500" /> Tidak
                                    </label>
                                </div>
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Agreement checkbox -->
                            <div v-else-if="field.type === 'agreement_checkbox'" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input v-model="form.custom_answers[field.field_key]" type="checkbox" :true-value="'setuju'" :false-value="''" class="mt-0.5 rounded text-teal-600 focus:ring-teal-500" />
                                    <span class="text-sm text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></span>
                                </label>
                                <p v-if="field.help_text" class="mt-2 text-xs text-slate-500 ml-6">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Signature field -->
                            <div v-else-if="field.type === 'signature'" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
                                <p class="text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></p>
                                <p class="mt-1 text-xs text-slate-500">Muat naik tandatangan di bahagian Dokumen Sokongan.</p>
                                <input
                                    :id="`cf-${field.field_key}`"
                                    v-model="form.custom_answers[field.field_key]"
                                    type="hidden"
                                />
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- File upload field -->
                            <div v-else-if="field.type === 'file'" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
                                <p class="text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></p>
                                <p class="mt-1 text-xs text-slate-500">Muat naik fail di bahagian Dokumen Sokongan.</p>
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <input
                                    :id="`cf-${field.field_key}`"
                                    v-model="form.custom_answers[field.field_key]"
                                    type="hidden"
                                />
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Default: text-like inputs (short_text, email, phone, identity_no, number, currency, date) -->
                            <div v-else>
                                <label :for="`cf-${field.field_key}`" class="block text-sm font-medium text-slate-700">{{ field.label }}<span v-if="field.is_required" class="ml-1 text-red-600">*</span></label>
                                <input
                                    :id="`cf-${field.field_key}`"
                                    v-model="form.custom_answers[field.field_key]"
                                    :type="field.type === 'date' ? 'date' : field.type === 'email' ? 'email' : field.type === 'number' || field.type === 'currency' ? 'number' : 'text'"
                                    :placeholder="field.placeholder || ''"
                                    :step="field.type === 'currency' ? '0.01' : undefined"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                />
                                <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
                                <p v-if="form.errors[`custom_answers.${field.field_key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`custom_answers.${field.field_key}`] }}</p>
                            </div>
                        </template>
                    </FormSection>

                    <FormSection v-if="product.requires_guarantor" title="Pemilihan Penjamin" description="Pilih penjamin aktif yang mempunyai log masuk ahli dan bersedia memberi maklum balas." :columns="1">
                        <div class="rounded-2xl border border-teal-100 bg-teal-50/80 p-4 text-sm text-teal-900">
                            <div class="flex items-start gap-3">
                                <ShieldCheck class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <div class="space-y-1">
                                    <p class="font-semibold">Keperluan penjamin</p>
                                    <p>Permohonan ini memerlukan {{ product.guarantor_count }} penjamin. Pilih penjamin yang boleh menyemak permintaan anda dengan segera.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 md:flex-row md:items-end">
                            <div class="flex-1">
                                <TextInput id="guarantor-search" v-model="guarantorSearch" label="Cari Penjamin" />
                            </div>
                            <Button type="button" class="h-11 md:min-w-36" :disabled="guarantorSearchLoading" @click="searchGuarantors">
                                <Search class="mr-2 h-4 w-4" />
                                {{ guarantorSearchLoading ? 'Mencari...' : 'Cari' }}
                            </Button>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <span class="font-medium">Pilihan semasa:</span>
                            <span>{{ selectedGuarantorIds.length }} / {{ product.guarantor_count }} dipilih</span>
                            <span class="text-slate-500">Baki: {{ remainingGuarantorSlots }}</span>
                        </div>

                        <p v-if="guarantorSearchError" class="text-sm text-red-700">{{ guarantorSearchError }}</p>
                        <p v-if="form.errors.guarantor_member_ids" class="text-sm text-red-700">{{ form.errors.guarantor_member_ids }}</p>

                        <div v-if="selectedGuarantors.length" class="space-y-3">
                            <p class="text-sm font-medium text-slate-900">Penjamin dipilih</p>
                            <article v-for="guarantor in selectedGuarantors" :key="guarantor.id" class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ guarantor.name }}</p>
                                    <p class="text-sm text-slate-600">{{ guarantor.member_no }} · {{ guarantor.employee_no || 'Tiada nombor staf' }}</p>
                                </div>
                                <Button type="button" variant="outline" @click="removeSelectedGuarantor(guarantor.id)">Buang</Button>
                            </article>
                        </div>

                        <div v-if="guarantorResults.length" class="space-y-3">
                            <p class="text-sm font-medium text-slate-900">Hasil carian</p>
                            <article v-for="result in guarantorResults" :key="result.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ result.name }}</p>
                                    <p class="text-sm text-slate-500">{{ result.member_no }} · {{ result.employee_no || 'Tiada nombor staf' }}</p>
                                </div>
                                <Button
                                    type="button"
                                    :disabled="!result.has_login"
                                    :variant="form.guarantor_member_ids.includes(result.id) ? 'default' : 'outline'"
                                    @click="toggleGuarantor(result)"
                                >
                                    {{ form.guarantor_member_ids.includes(result.id) ? 'Dipilih' : 'Pilih' }}
                                </Button>
                            </article>
                        </div>

                        <EmptyState
                            v-else-if="!guarantorSearchLoading && !guarantorSearchError"
                            title="Belum ada carian penjamin."
                            description="Cari mengikut nama, nombor ahli, atau nombor staf untuk memilih penjamin."
                            compact
                        />
                    </FormSection>

                    <FormSection title="Dokumen Sokongan" description="Muat naik dokumen yang diperlukan untuk menyokong permohonan anda." :columns="1">
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-5">
                            <div class="flex items-start gap-3">
                                <Upload class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-800" for="supporting-documents">Dokumen</label>
                                    <input id="supporting-documents" class="block w-full text-sm text-slate-700" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" @change="onDocumentsChange" />
                                    <p class="text-xs text-slate-500">Saiz maksimum 5MB setiap fail. Format dibenarkan: PDF, JPG, JPEG, PNG, dan WEBP.</p>
                                </div>
                            </div>

                            <p v-if="form.errors.documents" class="mt-3 text-sm text-red-700">{{ form.errors.documents }}</p>

                            <div v-if="selectedDocumentNames.length" class="mt-4 space-y-2">
                                <p class="text-sm font-medium text-slate-900">Fail dipilih</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="fileName in selectedDocumentNames" :key="fileName" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ fileName }}
                                    </span>
                                </div>
                            </div>
                            <p v-else class="mt-4 text-sm text-slate-600">Belum ada fail dipilih. Anda boleh memuat naik beberapa dokumen sekaligus.</p>
                        </div>

                        <div v-if="product.required_documents_note || product.required_documents?.length" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-medium text-slate-900">Dokumen yang disyorkan</p>
                            <p v-if="product.required_documents_note" class="whitespace-pre-line text-sm leading-7 text-slate-600">{{ product.required_documents_note }}</p>
                            <article v-for="document in product.required_documents" :key="document" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <CheckCircle2 class="h-4 w-4 text-teal-700" />
                                {{ document }}
                            </article>
                        </div>
                    </FormSection>

                    <FormActions submit-label="Hantar Permohonan" :submitting="form.processing" cancel-label="Kembali" @cancel="router.visit(`/member/financing/products/${product.id}`)" />
                </form>

                <aside class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Ringkasan Pemohon</h2>
                        <div class="mt-4 space-y-4">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama</p><p class="mt-1 text-sm text-slate-700">{{ member.full_name }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli</p><p class="mt-1 text-sm text-slate-700">{{ member.member_no }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pekerjaan</p><p class="mt-1 text-sm text-slate-700">{{ member.occupation || '-' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Majikan</p><p class="mt-1 text-sm text-slate-700">{{ member.employer_name || '-' }}</p></div>
                        </div>
                    </section>

                    <section v-if="product.product_documents?.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Dokumen Produk</h2>
                        <div class="mt-4 space-y-3">
                            <article v-for="document in product.product_documents" :key="document.key" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">{{ document.label }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ document.file_name }}</p>
                                <Button :as="Link" :href="document.download_url" variant="outline" class="mt-3 w-full">
                                    <Download class="mr-2 h-4 w-4" />
                                    {{ document.download_label }}
                                </Button>
                            </article>
                        </div>
                    </section>

                    <section v-if="product.officer_contact_name || product.officer_contact_phone || product.officer_contact_email" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Pegawai Untuk Dihubungi</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <p v-if="product.officer_contact_name" class="font-medium text-slate-900">{{ product.officer_contact_name }}</p>
                            <p v-if="product.officer_contact_phone">{{ product.officer_contact_phone }}</p>
                            <p v-if="product.officer_contact_email" class="flex items-center gap-2"><Mail class="h-4 w-4 text-teal-700" /> {{ product.officer_contact_email }}</p>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <ImageIcon class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h2 class="text-base font-semibold text-slate-950">Jadual Kadar</h2>
                                    <p class="text-sm text-slate-500">Rujuk kadar semasa sebelum menghantar permohonan.</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="product.rate_image_url" class="bg-slate-50 p-4">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <img :src="product.rate_image_url" alt="Jadual kadar pembiayaan" class="h-auto max-h-[32rem] w-full object-contain" />
                            </div>
                        </div>
                        <div v-else class="flex min-h-64 items-center justify-center p-8 text-center text-sm text-slate-500">
                            Jadual kadar belum tersedia untuk produk ini. Anda masih boleh meneruskan permohonan jika telah dimaklumkan oleh pihak koperasi.
                        </div>
                    </section>
                </aside>
            </div>
        </section>
    </MemberLayout>
</template>
