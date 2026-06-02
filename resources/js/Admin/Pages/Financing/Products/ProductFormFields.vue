<script setup>
import { Download, FileText } from 'lucide-vue-next';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import FormSection from '@/Shared/Components/FormSection.vue';

defineProps({
    form: { type: Object, required: true },
    categoryOptions: { type: Array, required: true },
    unitOptions: { type: Array, required: true },
    product: { type: Object, default: null },
    activeTab: { type: String, required: true },
});
</script>

<template>
    <!-- Tab: Maklumat Produk -->
    <div v-show="activeTab === 'maklumat'" class="space-y-6">
        <FormSection title="Maklumat Produk" description="Maklumat asas produk pembiayaan." :columns="2">
            <SelectInput
                id="category"
                v-model="form.financing_category_id"
                label="Kategori Pembiayaan"
                :options="categoryOptions"
                :error="form.errors.financing_category_id"
            />
            <SelectInput
                id="unit"
                v-model="form.unit_id"
                label="Unit Pengurusan"
                :options="unitOptions"
                :error="form.errors.unit_id"
            />
            <TextInput id="name" v-model="form.name" label="Nama Produk" help="Slug dijana secara automatik daripada nama produk." :error="form.errors.name" />
            <div class="md:col-span-2">
                <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="form.errors.description" />
            </div>
        </FormSection>

        <FormSection title="Syarat Kelayakan" description="Nyatakan syarat asas kelayakan ahli." :columns="1">
            <TextareaInput id="eligibility-terms" v-model="form.eligibility_terms" label="Syarat Kelayakan" :error="form.errors.eligibility_terms" />
        </FormSection>
    </div>

    <!-- Tab: Kadar & Syarat -->
    <div v-show="activeTab === 'kadar'" class="space-y-6">
        <FormSection title="Had & Kadar Pembiayaan" description="Julat amaun, tempoh, dan kadar faedah tahunan." :columns="2">
            <TextInput id="min-amount" v-model="form.min_amount" label="Amaun Minimum (RM)" type="number" :error="form.errors.min_amount" />
            <TextInput id="max-amount" v-model="form.max_amount" label="Amaun Maksimum (RM)" type="number" :error="form.errors.max_amount" />
            <TextInput id="min-tenure" v-model="form.min_tenure_months" label="Tempoh Minimum (Bulan)" type="number" :error="form.errors.min_tenure_months" />
            <TextInput id="max-tenure" v-model="form.max_tenure_months" label="Tempoh Maksimum (Bulan)" type="number" :error="form.errors.max_tenure_months" />
            <TextInput
                id="annual-rate"
                v-model="form.annual_rate_percent"
                label="Kadar Faedah Tahunan (%)"
                type="number"
                step="0.01"
                help="Digunakan untuk kalkulator anggaran ansuran."
                :error="form.errors.annual_rate_percent"
            />
            <div class="md:col-span-2">
                <TextInput id="rate-note" v-model="form.rate_note" label="Nota Kadar (pilihan)" help="Contoh: Kadar tetap, tertakluk kepada kelulusan." :error="form.errors.rate_note" />
            </div>
        </FormSection>

        <FormSection title="Terma & Nota" description="Dipaparkan pada halaman produk dan dokumen cetak." :columns="1">
            <TextareaInput id="product-terms" v-model="form.product_terms" label="Terma Pembiayaan" :error="form.errors.product_terms" />
            <TextareaInput id="application-notes" v-model="form.application_notes" label="Nota Permohonan" :error="form.errors.application_notes" />
            <TextareaInput id="application-instructions" v-model="form.application_instructions" label="Arahan Permohonan" :error="form.errors.application_instructions" />
        </FormSection>
    </div>

    <!-- Tab: Dokumen -->
    <div v-show="activeTab === 'dokumen'" class="space-y-6">
        <FormSection title="Dokumen Diperlukan Ahli" description="Senarai dokumen sokongan yang perlu dimuat naik oleh pemohon." :columns="1">
            <TextareaInput id="required-documents" v-model="form.required_documents_text" label="Dokumen Diperlukan" help="Masukkan satu dokumen bagi setiap baris." :error="form.errors.required_documents_text" />
            <TextareaInput id="required-documents-note" v-model="form.required_documents_note" label="Nota Dokumen Diperlukan" :error="form.errors.required_documents_note" />
        </FormSection>

        <FormSection title="Dokumen Produk" description="PDF rasmi untuk rujukan ahli. Format PDF sahaja, maksimum 10MB. Dokumen PDF boleh diganti selepas produk disimpan." :columns="1">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="block text-sm font-medium text-slate-900" for="consent-pdf">Dokumen Consent / Persetujuan</label>
                    <p class="mt-1 text-xs text-slate-500">Muat naik PDF baharu untuk menggantikan dokumen sedia ada.</p>
                    <input id="consent-pdf" class="mt-3 block w-full text-sm text-slate-700" type="file" accept=".pdf,application/pdf" @input="form.consent_pdf = $event.target.files[0]" />
                    <p v-if="form.errors.consent_pdf" class="mt-2 text-sm text-red-700">{{ form.errors.consent_pdf }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="block text-sm font-medium text-slate-900" for="undertaking-pdf">Letter of Undertaking</label>
                    <p class="mt-1 text-xs text-slate-500">Muat naik PDF baharu untuk menggantikan dokumen sedia ada.</p>
                    <input id="undertaking-pdf" class="mt-3 block w-full text-sm text-slate-700" type="file" accept=".pdf,application/pdf" @input="form.undertaking_pdf = $event.target.files[0]" />
                    <p v-if="form.errors.undertaking_pdf" class="mt-2 text-sm text-red-700">{{ form.errors.undertaking_pdf }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="block text-sm font-medium text-slate-900" for="guide-pdf">Dokumen Panduan</label>
                    <p class="mt-1 text-xs text-slate-500">Muat naik PDF baharu untuk menggantikan dokumen sedia ada.</p>
                    <input id="guide-pdf" class="mt-3 block w-full text-sm text-slate-700" type="file" accept=".pdf,application/pdf" @input="form.guide_pdf = $event.target.files[0]" />
                    <p v-if="form.errors.guide_pdf" class="mt-2 text-sm text-red-700">{{ form.errors.guide_pdf }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="block text-sm font-medium text-slate-900" for="official-form-template-pdf">Template Borang Rasmi</label>
                    <p class="mt-1 text-xs text-slate-500">Muat naik PDF baharu untuk menggantikan dokumen sedia ada.</p>
                    <input id="official-form-template-pdf" class="mt-3 block w-full text-sm text-slate-700" type="file" accept=".pdf,application/pdf" @input="form.official_form_template_pdf = $event.target.files[0]" />
                    <p v-if="form.errors.official_form_template_pdf" class="mt-2 text-sm text-red-700">{{ form.errors.official_form_template_pdf }}</p>
                </div>
            </div>

            <div v-if="product?.product_documents?.length" class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-medium text-slate-900">Dokumen dimuat naik semasa</p>
                <div class="mt-3 space-y-2">
                    <div
                        v-for="document in product.product_documents"
                        :key="document.key"
                        class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-700">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">{{ document.label }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ document.file_name }}</p>
                            </div>
                        </div>
                        <a
                            v-if="document.download_url"
                            :href="document.download_url"
                            class="ml-3 inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-50 transition"
                        >
                            <Download class="h-3.5 w-3.5" />
                            Lihat
                        </a>
                    </div>
                </div>
            </div>
        </FormSection>
    </div>

    <!-- Tab: Tetapan -->
    <div v-show="activeTab === 'tetapan'" class="space-y-6">
        <FormSection title="Status" description="Tentukan sama ada produk ini tersedia kepada ahli." :columns="2">
            <ToggleSwitch id="is-active" v-model="form.is_active" label="Produk aktif" description="Produk aktif akan dipaparkan di portal ahli." />
            <TextInput id="sort-order" v-model="form.sort_order" label="Susunan" type="number" :error="form.errors.sort_order" />
        </FormSection>

        <FormSection title="Penjamin" description="Keperluan penjamin dan dokumen sokongan pemohon." :columns="1">
            <ToggleSwitch
                id="requires-guarantor"
                v-model="form.requires_guarantor"
                label="Produk ini memerlukan penjamin"
                description="Ahli perlu memilih penjamin aktif yang mempunyai log masuk."
            />
            <TextInput
                v-if="form.requires_guarantor"
                id="guarantor-count"
                v-model="form.guarantor_count"
                label="Bilangan Penjamin"
                type="number"
                :error="form.errors.guarantor_count"
            />
        </FormSection>

        <FormSection title="Pegawai Untuk Dihubungi" description="Maklumat ini dipaparkan kepada ahli untuk pertanyaan lanjut." :columns="2">
            <TextInput id="officer-contact-name" v-model="form.officer_contact_name" label="Nama Pegawai Rujukan" :error="form.errors.officer_contact_name" />
            <TextInput id="officer-contact-phone" v-model="form.officer_contact_phone" label="No. Telefon Pegawai" :error="form.errors.officer_contact_phone" />
            <div class="md:col-span-2">
                <TextInput id="officer-contact-email" v-model="form.officer_contact_email" label="Email Pegawai" type="email" :error="form.errors.officer_contact_email" />
            </div>
        </FormSection>
    </div>
</template>