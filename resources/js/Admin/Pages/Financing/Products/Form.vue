<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, ChevronDown, ChevronUp, FilePlus, FileText, HelpCircle, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';
import { Dialog } from '@/Shared/Components/ui/dialog';
import ProductFormFields from './ProductFormFields.vue';

const props = defineProps({
    mode: { type: String, required: true },
    product: { type: Object, default: null },
    categoryOptions: { type: Array, required: true },
    unitOptions: { type: Array, required: true },
    productFields: { type: Array, default: () => [] },
    fieldTypeOptions: { type: Array, default: () => [] },
});

const CONTENT_TYPES = ['instruction_text', 'note', 'rich_text'];

const form = useForm({
    financing_category_id: props.product?.financing_category_id || props.categoryOptions[0]?.value || '',
    unit_id: props.product?.unit_id || '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    eligibility_terms: props.product?.eligibility_terms || '',
    product_terms: props.product?.product_terms || '',
    application_notes: props.product?.application_notes || '',
    application_instructions: props.product?.application_instructions || '',
    required_documents_note: props.product?.required_documents_note || '',
    officer_contact_name: props.product?.officer_contact_name || '',
    officer_contact_phone: props.product?.officer_contact_phone || '',
    officer_contact_email: props.product?.officer_contact_email || '',
    min_amount: props.product?.min_amount ?? '',
    max_amount: props.product?.max_amount ?? '',
    min_tenure_months: props.product?.min_tenure_months ?? '',
    max_tenure_months: props.product?.max_tenure_months ?? '',
    rate_image: null,
    remove_rate_image: false,
    annual_rate_percent: props.product?.annual_rate_percent ?? '',
    rate_note: props.product?.rate_note || '',
    requires_guarantor: props.product?.requires_guarantor ?? false,
    guarantor_count: props.product?.guarantor_count ?? 0,
    required_documents_text: props.product?.required_documents_text || '',
    consent_pdf: null,
    undertaking_pdf: null,
    guide_pdf: null,
    official_form_template_pdf: null,
    is_active: props.product?.is_active ?? true,
    sort_order: props.product?.sort_order ?? 0,
});

const tabs = [
    { key: 'maklumat', label: 'Maklumat Produk' },
    { key: 'kadar', label: 'Kadar & Syarat' },
    { key: 'dokumen', label: 'Dokumen' },
    { key: 'borang', label: 'Borang Permohonan' },
    { key: 'tetapan', label: 'Tetapan' },
];
const activeTab = ref('maklumat');

const isProductTab = computed(() => activeTab.value !== 'borang');

// ---- Delete / deactivate ----
const showDeleteDialog = ref(false);
const deleteError = ref('');
const deleting = ref(false);
const hasApplications = computed(() => props.product?.has_applications ?? false);

function confirmDelete() {
    showDeleteDialog.value = true;
    deleteError.value = '';
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deleteError.value = '';
}

function performDelete() {
    deleting.value = true;
    router.delete(`/admin/financing/products/${props.product.id}`, {
        onError: (errors) => {
            deleteError.value = errors.product || 'Ralat semasa pemadaman.';
            deleting.value = false;
        },
        onFinish: () => { deleting.value = false; },
    });
}

function performDeactivate() {
    router.post(`/admin/financing/products/${props.product.id}/deactivate`, {}, {
        onSuccess: () => { showDeleteDialog.value = false; },
    });
}

// ---- Product field builder ----
const fields = ref(props.productFields.map(f => ({ ...f })));
const fieldSaving = ref(false);
const showFieldModal = ref(false);
const editingField = ref(null);
const fieldForm = ref(getEmptyFieldForm());
const optionsText = ref('');
const richTextContent = ref('');
const agreementText = ref('');
const fileExtensions = ref('');

function getEmptyFieldForm() {
    return {
        label: '',
        type: 'short_text',
        placeholder: '',
        help_text: '',
        is_required: false,
        is_active: true,
    };
}

const needsOptions = computed(() => ['select', 'radio', 'checkbox'].includes(fieldForm.value.type));
const isContentType = computed(() => CONTENT_TYPES.includes(fieldForm.value.type));
const isAgreementType = computed(() => fieldForm.value.type === 'agreement_checkbox');
const isFileType = computed(() => fieldForm.value.type === 'file');
const isRichType = computed(() => fieldForm.value.type === 'rich_text' || fieldForm.value.type === 'instruction_text');
const isNoteType = computed(() => fieldForm.value.type === 'note');

function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content || '';
}

function openAddField(defaultType = 'short_text') {
    editingField.value = null;
    fieldForm.value = { ...getEmptyFieldForm(), type: defaultType };
    optionsText.value = '';
    richTextContent.value = '';
    agreementText.value = '';
    fileExtensions.value = '';
    showFieldModal.value = true;
}

function openEditField(field) {
    editingField.value = field;
    fieldForm.value = {
        label: field.label,
        type: field.type,
        placeholder: field.placeholder || '',
        help_text: field.help_text || '',
        is_required: field.is_required,
        is_active: field.is_active,
    };
    optionsText.value = (field.options_json || []).join('\n');
    richTextContent.value = field.settings_json?.content || '';
    agreementText.value = field.settings_json?.agreement_text || '';
    fileExtensions.value = (field.settings_json?.allowed_extensions || []).join(', ');
    showFieldModal.value = true;
}

function closeFieldModal() {
    showFieldModal.value = false;
    editingField.value = null;
}

async function saveField() {
    if (props.mode !== 'edit') return;
    fieldSaving.value = true;

    const payload = {
        ...fieldForm.value,
        options_json: needsOptions.value
            ? optionsText.value.split('\n').map(s => s.trim()).filter(Boolean)
            : null,
        settings_json: {},
    };

    if (isRichType.value) {
        payload.settings_json = { content: richTextContent.value || fieldForm.value.label };
    } else if (isNoteType.value) {
        payload.settings_json = { content: fieldForm.value.help_text || fieldForm.value.label };
    } else if (isAgreementType.value) {
        payload.settings_json = { agreement_text: agreementText.value };
    } else if (isFileType.value) {
        payload.settings_json = {
            allowed_extensions: fileExtensions.value
                ? fileExtensions.value.split(',').map(s => s.trim()).filter(Boolean)
                : ['pdf', 'jpg', 'jpeg', 'png'],
        };
    }

    if (Object.keys(payload.settings_json).length === 0) {
        payload.settings_json = null;
    }

    try {
        const url = editingField.value
            ? `/admin/financing/products/${props.product.id}/fields/${editingField.value.id}`
            : `/admin/financing/products/${props.product.id}/fields`;

        const res = await fetch(url, {
            method: editingField.value ? 'PATCH' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) throw new Error('Ralat simpan.');
        const saved = await res.json();

        if (editingField.value) {
            const idx = fields.value.findIndex(f => f.id === saved.id);
            if (idx !== -1) fields.value[idx] = saved;
        } else {
            fields.value.push(saved);
        }

        closeFieldModal();
    } finally {
        fieldSaving.value = false;
    }
}

async function deleteField(field) {
    await fetch(`/admin/financing/products/${props.product.id}/fields/${field.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    });
    fields.value = fields.value.filter(f => f.id !== field.id);
}

async function moveField(field, direction) {
    const idx = fields.value.findIndex(f => f.id === field.id);
    const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
    if (swapIdx < 0 || swapIdx >= fields.value.length) return;
    [fields.value[idx], fields.value[swapIdx]] = [fields.value[swapIdx], fields.value[idx]];
    await fetch(`/admin/financing/products/${props.product.id}/fields/reorder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ ids: fields.value.map(f => f.id) }),
    });
}

async function toggleFieldActive(field) {
    const payload = { label: field.label, type: field.type, is_active: !field.is_active, is_required: field.is_required };
    const res = await fetch(`/admin/financing/products/${props.product.id}/fields/${field.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    });
    if (res.ok) {
        const updated = await res.json();
        const idx = fields.value.findIndex(f => f.id === updated.id);
        if (idx !== -1) fields.value[idx] = updated;
    }
}

const fieldToDelete = ref(null);
const showDeleteFieldDialog = ref(false);

function confirmDeleteField(field) {
    fieldToDelete.value = field;
    showDeleteFieldDialog.value = true;
}

function performDeleteField() {
    if (fieldToDelete.value) deleteField(fieldToDelete.value);
    showDeleteFieldDialog.value = false;
    fieldToDelete.value = null;
}

// ---- Form submit ----
function submit() {
    const url = props.mode === 'create'
        ? '/admin/financing/products'
        : `/admin/financing/products/${props.product.id}`;

    props.mode === 'create'
        ? form.post(url, { preserveScroll: true, forceFormData: true })
        : form.patch(url, { preserveScroll: true, forceFormData: true });
}

function getTypeLabel(type) {
    return props.fieldTypeOptions.find(o => o.value === type)?.label || type;
}

function getFieldTypeIcon(type) {
    if (type === 'note') return 'FileText';
    if (type === 'rich_text' || type === 'instruction_text') return 'FileText';
    return 'HelpCircle';
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Produk Pembiayaan' : 'Edit Produk Pembiayaan'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Tambah Produk Pembiayaan' : 'Edit Produk Pembiayaan'"
                description="Tetapkan maklumat produk, kadar faedah, terma rasmi, dokumen sokongan, dan borang permohonan."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="mode === 'edit'"
                            type="button"
                            variant="outline"
                            class="text-red-600 border-red-200 hover:bg-red-50"
                            @click="confirmDelete"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ hasApplications ? 'Nyahaktifkan' : 'Padam' }}
                        </Button>
                        <Button :as="Link" href="/admin/financing/products" variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Delete / deactivate dialog -->
            <teleport to="body">
                <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="closeDeleteDialog" />
                    <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <AlertTriangle class="h-5 w-5 shrink-0 text-red-500" />
                                <h2 class="text-base font-semibold text-slate-900">
                                    {{ hasApplications ? 'Nyahaktifkan Produk?' : 'Padam Produk?' }}
                                </h2>
                            </div>
                            <button type="button" class="rounded p-1 hover:bg-slate-100" @click="closeDeleteDialog">
                                <X class="h-4 w-4 text-slate-500" />
                            </button>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">
                            <span v-if="hasApplications" class="text-amber-700">
                                Produk ini mempunyai permohonan dan tidak boleh dipadam. Anda boleh nyahaktifkan supaya ia tidak muncul kepada ahli.
                            </span>
                            <span v-else>
                                Produk <strong>{{ product?.name }}</strong> akan dipadam sepenuhnya. Tindakan ini tidak boleh dibatalkan.
                            </span>
                        </p>
                        <p v-if="deleteError" class="mt-2 text-sm text-red-600">{{ deleteError }}</p>
                        <div class="mt-5 flex justify-end gap-3">
                            <Button variant="outline" @click="closeDeleteDialog">Batal</Button>
                            <Button
                                v-if="hasApplications"
                                class="bg-amber-600 text-white hover:bg-amber-700"
                                @click="performDeactivate"
                            >
                                Nyahaktifkan Produk
                            </Button>
                            <Button
                                v-else
                                class="bg-red-600 text-white hover:bg-red-700"
                                :disabled="deleting"
                                @click="performDelete"
                            >
                                {{ deleting ? 'Memproses...' : 'Padam Produk' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </teleport>

            <!-- Pill tabs -->
            <div class="overflow-x-auto">
                <div class="flex gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 w-fit min-w-max">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        :class="activeTab === tab.key
                            ? 'rounded-lg bg-white px-4 py-1.5 text-sm font-medium text-slate-900 shadow-sm'
                            : 'rounded-lg px-4 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Product form with tabs -->
            <form @submit.prevent="submit" class="space-y-6">
                <FormSection
                    v-if="activeTab === 'kadar'"
                    title="Jadual Kadar Pembiayaan"
                    description="Muat naik imej jadual kadar khusus untuk produk ini."
                    :columns="1"
                >
                    <FileUploader
                        id="rate-image"
                        label="Jadual Kadar Pembiayaan"
                        accept=".jpg,.jpeg,.png,.webp"
                        helper-text="Muat naik jadual kadar pembiayaan dalam format JPG, PNG atau WEBP."
                        :error="form.errors.rate_image"
                        :model-value="form.rate_image"
                        :existing-file="product?.existing_rate_image_url ? { name: 'Imej sedia ada' } : null"
                        @update:model-value="form.rate_image = $event"
                    />
                    <div v-if="product?.existing_rate_image_url" class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <img :src="product.existing_rate_image_url" alt="Jadual kadar pembiayaan" class="max-h-80 w-full rounded-2xl object-contain" />
                    </div>
                    <ToggleSwitch
                        v-if="mode === 'edit' && product?.existing_rate_image_url"
                        id="remove-rate-image"
                        v-model="form.remove_rate_image"
                        label="Buang imej sedia ada"
                        description="Aktifkan pilihan ini jika anda mahu membuang imej jadual kadar semasa."
                    />
                </FormSection>

                <ProductFormFields
                    :form="form"
                    :category-options="categoryOptions"
                    :unit-options="unitOptions"
                    :product="product"
                    :active-tab="activeTab"
                />
                <FormActions
                    v-show="isProductTab"
                    :submit-label="mode === 'create' ? 'Simpan Produk' : 'Kemas Kini Produk'"
                    :submitting="form.processing"
                    cancel-label="Kembali"
                    @cancel="router.visit('/admin/financing/products')"
                />
            </form>

            <!-- Tab: Borang Permohonan - Create mode -->
            <div v-if="mode === 'create' && activeTab === 'borang'">
                <EmptyState
                    icon="FileCheck"
                    title="Borang Permohonan"
                    description="Simpan produk dahulu untuk menambah soalan permohonan. Anda boleh menambah soalan selepas produk disimpan."
                />
            </div>

            <!-- Tab: Borang Permohonan - Edit mode -->
            <div v-if="mode === 'edit' && activeTab === 'borang'" class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-base font-semibold text-slate-900">Soalan Tambahan Permohonan</p>
                            <p class="mt-1 text-sm text-slate-500">Tambah soalan atau blok kandungan yang akan dipaparkan kepada ahli semasa mengisi permohonan produk ini.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" @click="openAddField('short_text')">
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Soalan
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="openAddField('note')">
                                <FileText class="mr-2 h-4 w-4" />
                                Tambah Nota
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="openAddField('rich_text')">
                                <FilePlus class="mr-2 h-4 w-4" />
                                Tambah Teks Kaya
                            </Button>
                        </div>
                    </div>

                    <div v-if="fields.length === 0" class="rounded-2xl border border-dashed border-slate-300 py-10 text-center">
                        <p class="text-sm text-slate-500">Tiada soalan tambahan buat masa ini.</p>
                        <p class="mt-1 text-xs text-slate-400">Klik "Tambah Soalan" untuk menambah soalan khas produk ini.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(field, idx) in fields"
                            :key="field.id"
                            class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex flex-col gap-1 pt-1">
                                <button
                                    type="button"
                                    :disabled="idx === 0"
                                    class="rounded p-0.5 hover:bg-slate-200 disabled:opacity-30"
                                    title="Naik"
                                    @click="moveField(field, 'up')"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    :disabled="idx === fields.length - 1"
                                    class="rounded p-0.5 hover:bg-slate-200 disabled:opacity-30"
                                    title="Turun"
                                    @click="moveField(field, 'down')"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-slate-900">{{ field.label }}</p>
                                    <span v-if="field.is_required && !field.is_content_block" class="rounded-full bg-red-100 px-1.5 py-0.5 text-xs text-red-700">Wajib</span>
                                    <span v-if="!field.is_active" class="rounded-full bg-slate-300 px-1.5 py-0.5 text-xs text-slate-700">Tidak Aktif</span>
                                    <span v-if="field.is_content_block" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700">Paparan</span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ getTypeLabel(field.type) }}
                                </p>
                                <p v-if="field.help_text && !field.is_content_block" class="mt-1 text-xs text-slate-400">{{ field.help_text }}</p>
                                <p v-if="field.settings_json?.content && field.is_content_block" class="mt-1 line-clamp-2 text-xs text-slate-400">{{ field.settings_json.content }}</p>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button
                                    type="button"
                                    :class="field.is_active ? 'text-slate-500 hover:text-slate-700' : 'text-slate-300 hover:text-slate-600'"
                                    :title="field.is_active ? 'Nyahktifkan' : 'Aktifkan'"
                                    class="rounded p-1 hover:bg-slate-200"
                                    @click="toggleFieldActive(field)"
                                >
                                    <span class="text-xs">{{ field.is_active ? 'Aktif' : 'Nyahaktif' }}</span>
                                </button>
                                <Button type="button" variant="ghost" size="sm" @click="openEditField(field)">
                                    <Pencil class="h-4 w-4 mr-1" />
                                    Edit
                                </Button>
                                <Button type="button" variant="ghost" size="sm" class="text-red-600 hover:text-red-700" @click="confirmDeleteField(field)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field add/edit modal -->
            <Dialog :open="showFieldModal" @update:open="closeFieldModal">
                <div class="max-h-[85vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-slate-950">{{ editingField ? 'Edit Soalan' : 'Tambah Soalan Baharu' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Lengkapkan maklumat soalan permohonan untuk produk ini.</p>

                    <div class="mt-5 space-y-4">
                        <TextInput id="field-label" v-model="fieldForm.label" label="Teks Soalan / Tajuk" :error="!fieldForm.label ? 'Ruangan ini diperlukan.' : ''" />

                        <SelectInput id="field-type" v-model="fieldForm.type" label="Jenis Field" :options="fieldTypeOptions" />

                        <!-- Content block: rich text or instruction text -->
                        <div v-if="isRichType">
                            <label for="field-rich-content" class="block text-sm font-medium text-slate-700">Kandungan Teks Kaya</label>
                            <p class="text-xs text-slate-500 mb-2">Masukkan teks format HTML. Tag disokong: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a&gt;, &lt;table&gt;, &lt;h2&gt;-&lt;h4&gt;. Script dan iframe tidak dibenarkan.</p>
                            <textarea
                                id="field-rich-content"
                                v-model="richTextContent"
                                rows="8"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="<p>Isi kandungan di sini...</p>"
                            ></textarea>
                        </div>

                        <!-- Note: simple text -->
                        <div v-if="isNoteType">
                            <label for="field-note-content" class="block text-sm font-medium text-slate-700">Kandungan Nota</label>
                            <textarea
                                id="field-note-content"
                                v-model="fieldForm.help_text"
                                rows="4"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Masukkan teks nota ringkas..."
                            ></textarea>
                        </div>

                        <!-- Non-content fields: placeholder -->
                        <TextInput
                            v-if="!isContentType"
                            id="field-placeholder"
                            v-model="fieldForm.placeholder"
                            label="Placeholder (pilihan)"
                        />

                        <!-- Help text (non-note, non-rich types) -->
                        <TextareaInput
                            v-if="!isContentType"
                            id="field-help"
                            v-model="fieldForm.help_text"
                            label="Nota Bantuan (pilihan)"
                        />

                        <!-- Options editor -->
                        <div v-if="needsOptions">
                            <label for="field-options" class="block text-sm font-medium text-slate-700">Pilihan (satu baris satu pilihan)</label>
                            <textarea
                                id="field-options"
                                v-model="optionsText"
                                rows="5"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Pilihan A&#10;Pilihan B&#10;Pilihan C"
                            ></textarea>
                        </div>

                        <!-- Agreement text -->
                        <div v-if="isAgreementType">
                            <label for="field-agreement" class="block text-sm font-medium text-slate-700">Teks Persetujuan</label>
                            <textarea
                                id="field-agreement"
                                v-model="agreementText"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Saya dengan ini bersetuju..."
                            ></textarea>
                        </div>

                        <!-- File settings -->
                        <div v-if="isFileType">
                            <label for="field-file-extensions" class="block text-sm font-medium text-slate-700">Jenis Fail Dibenarkan</label>
                            <p class="text-xs text-slate-500 mb-2">Masukkan sambungan fail, dipisahkan dengan koma. Contoh: pdf, jpg, jpeg, png</p>
                            <input
                                id="field-file-extensions"
                                v-model="fileExtensions"
                                type="text"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="pdf, jpg, jpeg, png"
                            />
                        </div>

                        <!-- Required toggle (non-content types only) -->
                        <ToggleSwitch
                            v-if="!isContentType"
                            id="field-required"
                            v-model="fieldForm.is_required"
                            label="Wajib dijawab?"
                        />

                        <!-- Active toggle -->
                        <ToggleSwitch
                            id="field-active"
                            v-model="fieldForm.is_active"
                            label="Soalan aktif"
                        />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="closeFieldModal">Batal</Button>
                        <Button type="button" :disabled="fieldSaving || !fieldForm.label" @click="saveField">
                            {{ fieldSaving ? 'Menyimpan...' : editingField ? 'Simpan Perubahan' : 'Simpan Soalan' }}
                        </Button>
                    </div>
                </div>
            </Dialog>

            <!-- Delete field confirmation -->
            <ConfirmDialog
                :open="showDeleteFieldDialog"
                title="Padam Soalan?"
                :description="`Soalan '${fieldToDelete?.label}' akan dipadam sepenuhnya. Tindakan ini tidak boleh dibatalkan.`"
                confirm-label="Padam"
                cancel-label="Batal"
                variant="destructive"
                @cancel="showDeleteFieldDialog = false; fieldToDelete = null"
                @confirm="performDeleteField"
            />
        </section>
    </AdminLayout>
</template>
