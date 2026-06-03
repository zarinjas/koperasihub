<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Ban, CheckCircle, Clock, Download, FileText, Info, Loader2, Printer, Upload, UserPlus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DocumentPackagePanel from '@/Member/Components/Financing/DocumentPackagePanel.vue';
import SupportingDocumentUploader from '@/Shared/Components/Financing/SupportingDocumentUploader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    application: { type: Object, required: true },
});

const isCancellable = computed(() => props.application.can_cancel);

const contentFieldTypes = new Set(['rich_text', 'note', 'instruction_text', 'image', 'pdf_document']);
const contentFields = computed(() => (props.application.product_fields || []).filter((f) => contentFieldTypes.has(f.type)));
const hasContentFields = computed(() => contentFields.value.length > 0);

const showCancelDialog = ref(false);
const cancelReason = ref('');
const cancelLoading = ref(false);

const isPendingStamp = computed(() => props.application.status === 'menunggu_muat_naik');

const showStampUpload = ref(false);
const stampFile = ref(null);
const productFormFile = ref(null);
const stampLoading = ref(false);

const hasProductForm = computed(() => !!props.application.form_template_url);

const uploadStamp = () => {
    if (!stampFile.value) return;
    stampLoading.value = true;
    const fd = new FormData();
    fd.append('file', stampFile.value, stampFile.value.name);
    if (productFormFile.value) {
        fd.append('product_form', productFormFile.value, productFormFile.value.name);
    }
    router.post(props.application.stamped_form.upload_url, fd, {
        onFinish: () => { stampLoading.value = false; },
    });
};

const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content || '');
const uploadList = ref([...(props.application.supporting_document_uploads || [])]);

const cancelApplication = () => {
    cancelLoading.value = true;
    router.post(props.application.cancel_url, { reason: cancelReason.value }, {
        onFinish: () => { cancelLoading.value = false; showCancelDialog.value = false; },
    });
};

const customSections = computed(() => {
    const sections = props.application.sections_snapshot || [];
    const answers = props.application.custom_answers_json || {};
    const nonTextTypes = ['address_my', 'address_spouse', 'address_beneficiary', 'digital_signature', 'file', 'signature_block', 'image', 'pdf_document', 'document_checklist'];
    const addressSuffixes = ['_line1', '_line2', '_postcode', '_city', '_state'];

    const formatValue = (value) => {
        if (value == null || value === '') return null;
        if (Array.isArray(value) && value.some((row) => row && typeof row === 'object' && !Array.isArray(row))) {
            return value.map((row) => Object.values(row).filter(Boolean).join(' / ')).join('; ');
        }
        if (Array.isArray(value)) return value.join(', ');
        return String(value);
    };

    return sections.map((section) => ({
        title: section.title,
        fields: (section.fields || [])
            .filter((f) => {
                if (nonTextTypes.includes(f.type)) return false;
                if (addressSuffixes.some((s) => f.field_key.endsWith(s))) return false;
                const val = answers[f.field_key];
                return val != null && val !== '';
            })
            .map((f) => ({
                label: f.label || f.field_key,
                value: formatValue(answers[f.field_key]),
                field_key: f.field_key,
            })),
    })).filter((s) => s.fields.length > 0);
});
</script>

<template>
    <Head :title="'Permohonan - ' + application.reference_no" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader :title="'Permohonan ' + application.reference_no" :description="application.product_name || 'Pembiayaan'">
                <template #actions>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="application.status" :label="application.status_label" />
                        <Button v-if="isPendingStamp" :as="Link" :href="application.print_url" size="sm">
                            <Printer class="mr-2 h-4 w-4" />
                            Cetak Borang
                        </Button>
                        <Button :as="Link" href="/member/financing/applications" variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Banner makluman: menunggu penjamin -->
            <div v-if="application.status === 'menunggu_penjamin'" class="flex items-start gap-4 rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                    <UserPlus class="h-5 w-5 text-amber-700" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-900">Menunggu Kelulusan Penjamin</p>
                    <p class="mt-1 text-sm text-amber-800">
                        Permohonan anda telah dihantar. Penjamin yang anda pilih perlu bersetuju terlebih dahulu sebelum permohonan ini boleh diteruskan. Sila tunggu maklum balas daripada penjamin.
                    </p>
                    <div v-if="application.guarantors?.length" class="mt-3 space-y-2">
                        <div v-for="g in application.guarantors" :key="g.id" class="flex items-center gap-3 rounded-xl bg-white border border-amber-100 px-4 py-2.5">
                            <UserPlus class="h-4 w-4 shrink-0 text-slate-400" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900">{{ g.name || '-' }}</p>
                                <p class="text-xs text-slate-500">{{ g.member_no }}</p>
                            </div>
                            <StatusBadge :status="g.status" :label="g.status_label" />
                        </div>
                    </div>
                </div>
            </div>

            <DocumentPackagePanel :documents="application.generated_documents || []" />

            <SupportingDocumentUploader
                :supporting-documents="application.supporting_documents || []"
                :existing-uploads="uploadList"
                :upload-url="application.supporting_upload_url"
                :csrf-token="csrfToken"
                @uploaded="(upload) => uploadList.push(upload)"
            />

            <!-- Banner arahan: status menunggu muat naik -->
            <div v-if="isPendingStamp && !application.stamped_form?.uploaded" class="flex items-start gap-4 rounded-3xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100">
                    <Info class="h-5 w-5 text-blue-700" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-blue-900">Tindakan Diperlukan: Cetak, Cop &amp; Muat Naik Semula</p>
                    <ol class="mt-2 space-y-1 text-sm text-blue-800 list-decimal list-inside">
                        <li>Klik butang <strong>Cetak Borang</strong> di atas untuk pergi ke halaman cetakan.</li>
                        <li v-if="application.form_template_url">Cetak <strong>kedua-dua borang</strong> — borang permohonan dan borang khas produk.</li>
                        <li>Isi semua ruangan, dapatkan cop rasmi dan tandatangan yang diperlukan.</li>
                        <li>Imbas dan muat naik semula melalui butang <strong>Muat Naik Dokumen</strong> di bawah.</li>
                    </ol>
                </div>
            </div>

            <!-- Status: Pending Stamp Upload -->
            <div v-if="isPendingStamp" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                        <Upload class="h-5 w-5 text-amber-700" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-amber-900">Menunggu Muat Naik Borang Bercop</p>
                        <p class="mt-1 text-sm text-amber-800">
                            Permohonan anda sedang menunggu borang yang telah dicop dan ditandatangani. Sila muat naik borang tersebut untuk meneruskan proses semakan.
                        </p>

                        <div v-if="!application.stamped_form?.uploaded" class="mt-4">
                            <div v-if="showStampUpload" class="space-y-4">

                                <!-- Upload 1: Borang Permohonan -->
                                <div class="space-y-1.5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">
                                        1. Borang Permohonan (dicop &amp; ditandatangani)
                                    </p>
                                    <div v-if="!stampFile"
                                        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-amber-300 bg-white p-5 text-center cursor-pointer transition hover:border-amber-500"
                                        @click="$refs.stampInput.click()">
                                        <Upload class="h-5 w-5 text-amber-400 mb-1.5" />
                                        <p class="text-sm font-medium text-slate-700">Klik untuk pilih fail</p>
                                        <p class="text-xs text-slate-400 mt-0.5">PDF, JPG atau PNG • Maks 10MB</p>
                                        <input ref="stampInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                                            @change="(e) => { stampFile = e.target.files?.[0] ?? null; }" />
                                    </div>
                                    <div v-else class="flex items-center gap-3 rounded-2xl border border-teal-200 bg-white p-3.5">
                                        <CheckCircle class="h-5 w-5 shrink-0 text-teal-600" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ stampFile.name }}</p>
                                            <p class="text-xs text-slate-500">{{ (stampFile.size / 1024).toFixed(0) }} KB</p>
                                        </div>
                                        <button type="button" class="text-slate-400 hover:text-red-500" @click="stampFile = null">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload 2: Borang Khas Produk (jika ada) -->
                                <div v-if="hasProductForm" class="space-y-1.5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">
                                        2. Borang Khas Produk (dicop &amp; ditandatangani)
                                    </p>
                                    <div v-if="!productFormFile"
                                        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-amber-300 bg-white p-5 text-center cursor-pointer transition hover:border-amber-500"
                                        @click="$refs.productFormInput.click()">
                                        <Upload class="h-5 w-5 text-amber-400 mb-1.5" />
                                        <p class="text-sm font-medium text-slate-700">Klik untuk pilih fail</p>
                                        <p class="text-xs text-slate-400 mt-0.5">PDF, JPG atau PNG • Maks 10MB</p>
                                        <input ref="productFormInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                                            @change="(e) => { productFormFile = e.target.files?.[0] ?? null; }" />
                                    </div>
                                    <div v-else class="flex items-center gap-3 rounded-2xl border border-teal-200 bg-white p-3.5">
                                        <CheckCircle class="h-5 w-5 shrink-0 text-teal-600" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ productFormFile.name }}</p>
                                            <p class="text-xs text-slate-500">{{ (productFormFile.size / 1024).toFixed(0) }} KB</p>
                                        </div>
                                        <button type="button" class="text-slate-400 hover:text-red-500" @click="productFormFile = null">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <Button type="button"
                                        :disabled="!stampFile || (hasProductForm && !productFormFile) || stampLoading"
                                        @click="uploadStamp">
                                        <Loader2 v-if="stampLoading" class="mr-2 h-4 w-4 animate-spin" />
                                        <Upload v-else class="mr-2 h-4 w-4" />
                                        {{ stampLoading ? 'Memuat Naik...' : 'Hantar Dokumen' }}
                                    </Button>
                                    <Button type="button" variant="ghost" @click="showStampUpload = false; stampFile = null; productFormFile = null;">Batal</Button>
                                </div>
                            </div>
                            <div v-else>
                                <Button type="button" @click="showStampUpload = true">
                                    <Upload class="mr-2 h-4 w-4" />
                                    Muat Naik Dokumen
                                </Button>
                            </div>
                        </div>

                        <div v-else class="mt-3 flex items-center gap-2 text-sm text-amber-800">
                            <CheckCircle class="h-4 w-4 text-teal-600" />
                            Dokumen telah dimuat naik pada {{ application.stamped_form.uploaded_at }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stamped form already uploaded -->
            <div v-if="application.stamped_form?.uploaded && !isPendingStamp"
                class="flex items-center gap-3 rounded-3xl border border-teal-200 bg-teal-50 p-5 shadow-sm">
                <CheckCircle class="h-5 w-5 shrink-0 text-teal-600" />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-teal-900">Borang Bercop Telah Dimuat Naik</p>
                    <p class="text-xs text-teal-700 mt-0.5">{{ application.stamped_form.file_name }} &middot; {{ application.stamped_form.uploaded_at }}</p>
                </div>
                <a v-if="application.stamped_form.download_url" :href="application.stamped_form.download_url" target="_blank"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-teal-800">
                    <Download class="h-4 w-4" />
                    Muat Turun
                </a>
            </div>

            <!-- Status: Incomplete -->
            <div v-if="application.status === 'dokumen_tidak_lengkap'" class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <AlertTriangle class="h-5 w-5 text-red-700" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-900">Dokumen Tidak Lengkap</p>
                        <p class="mt-1 text-sm text-red-800 whitespace-pre-line">{{ application.decision_notes || 'Sila semak dan muat naik semula dokumen yang diperlukan.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Status: Rejected -->
            <div v-if="application.status === 'ditolak'" class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <Ban class="h-5 w-5 text-red-700" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-900">Permohonan Ditolak</p>
                        <p v-if="application.rejection_reason" class="mt-1 text-sm text-red-800 whitespace-pre-line">{{ application.rejection_reason }}</p>
                        <p v-if="application.decision_notes" class="mt-1 text-sm text-red-800 whitespace-pre-line">{{ application.decision_notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Status: Approved -->
            <div v-if="application.status === 'berjaya'" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <CheckCircle class="h-5 w-5 text-emerald-700" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-emerald-900">Permohonan Berjaya Diluluskan</p>
                        <p v-if="application.approved_amount" class="mt-1 text-sm text-emerald-800">Jumlah diluluskan: <strong>{{ application.approved_amount }}</strong></p>
                        <p v-if="application.approved_tenure_months" class="mt-0.5 text-sm text-emerald-800">Tempoh: <strong>{{ application.approved_tenure_months }} bulan</strong></p>
                        <p v-if="application.decision_notes" class="mt-1 text-sm text-emerald-800 whitespace-pre-line">{{ application.decision_notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Status: Cancelled -->
            <div v-if="application.status === 'dibatalkan'" class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200">
                        <X class="h-5 w-5 text-slate-600" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Permohonan Dibatalkan</p>
                        <p v-if="application.cancellation_reason" class="mt-1 text-sm text-slate-600 whitespace-pre-line">{{ application.cancellation_reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Maklumat Permohonan -->
            <FormSection title="Maklumat Permohonan" :columns="2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.product_name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.category_name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jumlah Dipohon</p>
                    <p class="mt-1 text-sm font-semibold text-teal-700">{{ application.amount_requested }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.tenure_months }} bulan</p>
                </div>
                <div v-if="application.purpose" class="col-span-full">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.purpose }}</p>
                </div>
                <div v-if="application.monthly_income">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pendapatan Bulanan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.monthly_income }}</p>
                </div>
                <div v-if="application.monthly_commitment">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Komitmen Bulanan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.monthly_commitment }}</p>
                </div>
                <div v-if="application.employment_notes" class="col-span-full">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nota Pekerjaan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.employment_notes }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ application.submitted_at || '-' }}</p>
                </div>
            </FormSection>

            <!-- Kandungan Borang (content fields from product) -->
            <FormSection v-if="hasContentFields" title="Maklumat Borang">
                <template v-for="cf in contentFields" :key="cf.id">
                    <div v-if="cf.type === 'rich_text' && cf.settings_json?.content"
                        class="col-span-full rounded-2xl border border-slate-200 bg-white p-5 prose prose-slate prose-sm max-w-none"
                        v-html="cf.settings_json.content" />

                    <div v-else-if="cf.type === 'note'"
                        class="col-span-full rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 whitespace-pre-wrap">
                        {{ cf.label }}
                    </div>

                    <div v-else-if="cf.type === 'instruction_text'"
                        class="col-span-full rounded-2xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-medium text-blue-800 whitespace-pre-wrap">{{ cf.label }}</p>
                    </div>

                    <div v-else-if="cf.type === 'image' && cf.settings_json?.file_path"
                        class="col-span-full rounded-2xl border border-slate-200 bg-white p-4">
                        <img :src="'/storage/' + cf.settings_json.file_path" :alt="cf.label" class="max-h-64 rounded-xl object-contain" />
                    </div>

                    <a v-else-if="cf.type === 'pdf_document' && cf.settings_json?.file_path"
                        :href="'/storage/' + cf.settings_json.file_path"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <Download class="h-4 w-4" />
                        {{ cf.label || 'Muat Turun Dokumen' }}
                    </a>
                </template>
            </FormSection>

            <!-- Jawapan Borang (by section) -->
            <FormSection v-for="section in customSections" :key="section.title" :title="section.title">
                <div v-for="field in section.fields" :key="field.field_key"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ field.label }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-900 whitespace-pre-wrap">{{ field.value || '-' }}</p>
                </div>
            </FormSection>

            <!-- Dokumen -->
            <FormSection v-if="application.documents?.length" title="Dokumen Dimuat Naik">
                <div v-for="doc in application.documents" :key="doc.id"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <FileText class="h-5 w-5 shrink-0 text-slate-400" />
                    <div class="flex-1 min-w-0">
                        <p class="truncate text-sm font-medium text-slate-900">{{ doc.file_name }}</p>
                        <p class="text-xs text-slate-500">{{ doc.uploaded_at }}</p>
                    </div>
                    <a :href="doc.download_url"
                        class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-teal-800">
                        <Download class="h-4 w-4" />
                    </a>
                </div>
            </FormSection>

            <!-- Penjamin -->
            <FormSection v-if="application.guarantors?.length" title="Penjamin">
                <div v-for="g in application.guarantors" :key="g.id"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-400">
                        <UserPlus class="h-5 w-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ g.name || '-' }}</p>
                        <p class="text-xs text-slate-500">{{ g.member_no }}</p>
                    </div>
                    <StatusBadge :status="g.status" :label="g.status_label" />
                </div>
            </FormSection>

            <!-- Sejarah -->
            <FormSection v-if="application.histories?.length" title="Sejarah Status">
                <div class="col-span-full space-y-1">
                    <div v-for="(h, idx) in application.histories" :key="h.id" class="flex items-start gap-3">
                        <div class="flex flex-col items-center">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-teal-200 bg-teal-50">
                                <Clock class="h-4 w-4 text-teal-600" />
                            </div>
                            <div v-if="idx < application.histories.length - 1" class="mt-1 h-8 w-px bg-slate-200" />
                        </div>
                        <div class="flex-1 pb-4">
                            <p class="text-sm font-medium text-slate-950">{{ h.action }}</p>
                            <p v-if="h.notes" class="mt-0.5 text-sm text-slate-600">{{ h.notes }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ h.actor_name || 'Sistem' }} &middot; {{ h.created_at }}</p>
                        </div>
                    </div>
                </div>
            </FormSection>

            <!-- Tindakan: Batalkan -->
            <div v-if="isCancellable" class="rounded-3xl border border-red-100 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-950">Tindakan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Permohonan ini masih boleh dibatalkan.</p>
                <div class="mt-4">
                    <Button type="button" variant="destructive" @click="showCancelDialog = true">
                        <Ban class="mr-2 h-4 w-4" />
                        Batalkan Permohonan
                    </Button>
                </div>
            </div>
        </section>

        <ConfirmDialog
            :open="showCancelDialog"
            title="Batalkan Permohonan"
            description="Adakah anda pasti untuk membatalkan permohonan ini?"
            confirm-label="Ya, Batalkan"
            variant="destructive"
            :loading="cancelLoading"
            @cancel="showCancelDialog = false"
            @confirm="cancelApplication"
        >
            <div class="space-y-2">
                <label for="cancel-reason" class="text-sm font-medium text-slate-800">Sebab Pembatalan</label>
                <textarea
                    id="cancel-reason"
                    v-model="cancelReason"
                    rows="3"
                    placeholder="Nyatakan sebab pembatalan..."
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20"
                />
            </div>
        </ConfirmDialog>
    </MemberLayout>
</template>