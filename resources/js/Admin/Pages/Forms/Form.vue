<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft, Check, ChevronDown, ChevronRight,
    Eye, FileText, Layers, Pencil, Plus, Save, Send, Trash2, X, Copy,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import { useAutoSlug } from '@/Shared/Composables/useAutoSlug.js';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';
import FormFieldEditor from '@/Admin/Components/Forms/FormFieldEditor.vue';
import FieldTemplateSelector from '@/Admin/Components/Forms/FieldTemplateSelector.vue';
import { getFieldTypeConfig, MEMBER_FIELD_MAP } from '@/Admin/Helpers/formFieldTypes';

const props = defineProps({
    mode: { type: String, required: true },
    formRecord: { type: Object, default: null },
    categoryOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    visibilityOptions: { type: Array, required: true },
    submissionMethodOptions: { type: Array, required: true },
    fieldTypeOptions: { type: Array, required: true },
    fieldTypeConfigs: { type: Object, default: () => ({}) },
    sections: { type: Array, default: () => [] },
    sectionOptions: { type: Array, default: () => [] },
});

const isEdit = computed(() => props.mode === 'edit');
const activeTab = ref('maklumat');

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const apiPost = async (url, body) => {
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    return r.json();
};

const apiPatch = async (url, body) => {
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-HTTP-Method-Override': 'PATCH' },
        body: JSON.stringify(body),
    });
    return r.json();
};

const apiDelete = async (url) => {
    const r = await fetch(url, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    });
    return r.json();
};

// ── Form metadata ──
const DEFAULT_STAMPED_INSTRUCTIONS = 'Borang ini perlu dicetak dan mendapatkan tandatangan serta cop pengesahan sebelum dimuat naik semula.';

const form = useForm({
    form_category_id: props.formRecord?.form_category_id || '',
    title: props.formRecord?.title || '',
    slug: props.formRecord?.slug || '',
    description: props.formRecord?.description || '',
    visibility: props.formRecord?.visibility || 'public',
    status: props.formRecord?.status || 'draft',
    success_message: props.formRecord?.success_message || 'Borang anda berjaya dihantar.',
    submission_method: props.formRecord?.submission_method || 'online_only',
    stamped_upload_instructions: props.formRecord?.stamped_upload_instructions || DEFAULT_STAMPED_INSTRUCTIONS,
    document_code: props.formRecord?.document_code || '',
    revision_no: props.formRecord?.revision_no || '',
    effective_date: props.formRecord?.effective_date || '',
    document_title: props.formRecord?.document_title || '',
    show_document_header: props.formRecord?.show_document_header ?? true,
});

useAutoSlug(() => form.title, form, 'slug');

const requiresStampedUpload = computed(() => form.submission_method === 'requires_stamped_upload');
const showDocumentSettings = ref(false);
const saveSuccess = ref(false);
const saveSuccessMessage = ref('');
let saveSuccessTimer = null;

const showSaveSuccess = (message) => {
    saveSuccessMessage.value = message || 'Berjaya disimpan.';
    saveSuccess.value = true;
    if (saveSuccessTimer) clearTimeout(saveSuccessTimer);
    saveSuccessTimer = setTimeout(() => { saveSuccess.value = false; }, 3000);
};

watch(() => form.submission_method, (val) => {
    if (val === 'requires_stamped_upload' && !form.stamped_upload_instructions) {
        form.stamped_upload_instructions = DEFAULT_STAMPED_INSTRUCTIONS;
    }
});

const saveFormMeta = () => {
    const onSuccess = () => {
        showSaveSuccess('Maklumat borang berjaya disimpan.');
        if (!isEdit.value && formRecord?.id) {
            window.location.href = `/admin/forms/${formRecord.id}/edit`;
        }
    };
    if (isEdit.value) {
        form.patch(`/admin/forms/${props.formRecord.id}`, { onSuccess, preserveScroll: true });
    } else {
        form.post('/admin/forms', { onSuccess, preserveScroll: true });
    }
};

const visibilityLabel = computed(() => {
    const opt = props.visibilityOptions.find((o) => o.value === form.visibility);
    return opt?.label || form.visibility;
});

const submissionMethodLabel = computed(() => {
    const opt = props.submissionMethodOptions.find((o) => o.value === form.submission_method);
    return opt?.label || form.submission_method;
});

const categoryLabel = computed(() => {
    const opt = props.categoryOptions.find((o) => String(o.value) === String(form.form_category_id));
    return opt?.label || '—';
});

// ── Templates (create mode) ──
const templates = [
    { label: 'Permohonan Menjadi Anggota', description: 'Borang pendaftaran ahli baharu koperasi.' },
    { label: 'Kemaskini Maklumat Anggota', description: 'Borang kemaskini maklumat peribadi ahli.' },
    { label: 'Permohonan Pembiayaan', description: 'Borang permohonan pinjaman atau pembiayaan.' },
    { label: 'Permohonan Takaful', description: 'Borang pendaftaran skim takaful.' },
    { label: 'Tempahan Bilik Seminar', description: 'Borang tempahan kemudahan koperasi.' },
    { label: 'Maklum Balas / Aduan', description: 'Borang maklum balas dan aduan ahli.' },
];

const showTemplates = ref(!isEdit.value);
const useTemplate = (tmpl) => {
    form.title = tmpl.label;
    form.description = tmpl.description;
    showTemplates.value = false;
};

// ── Local sections state ──
const localSections = ref(props.sections.map((s) => ({
    ...s,
    fields: (s.fields || []).map((f) => ({ ...f })),
})));

const totalFields = computed(() => localSections.value.reduce((sum, s) => sum + (s.fields?.length || 0), 0));

// ── Section CRUD ──
const sectionForm = ref({ title: '', description: '', page_break_before: false, is_active: true });
const sectionSubmitting = ref(false);

const submitSection = async () => {
    if (!isEdit.value || !props.formRecord || !sectionForm.value.title.trim()) return;
    sectionSubmitting.value = true;
    const data = await apiPost(`/admin/forms/${props.formRecord.id}/sections`, sectionForm.value);
    if (data.ok && data.section) {
        localSections.value.push({ ...data.section, fields: data.section.fields || [] });
        sectionForm.value = { title: '', description: '', page_break_before: false, is_active: true };
        showSaveSuccess('Bahagian berjaya ditambah.');
    }
    sectionSubmitting.value = false;
};

const editingSectionId = ref(null);
const editSectionForm = ref({ title: '', description: '', page_break_before: false, is_active: true });

const startEditSection = (section) => {
    editingSectionId.value = section.id;
    editSectionForm.value = {
        title: section.title,
        description: section.description || '',
        page_break_before: Boolean(section.page_break_before),
        is_active: section.is_active ?? true,
    };
};

const submitEditSection = async () => {
    if (!isEdit.value) return;
    sectionSubmitting.value = true;
    const data = await apiPatch(`/admin/forms/${props.formRecord.id}/sections/${editingSectionId.value}`, editSectionForm.value);
    if (data.ok && data.section) {
        const idx = localSections.value.findIndex((s) => s.id === editingSectionId.value);
        if (idx !== -1) {
            localSections.value[idx] = { ...localSections.value[idx], ...data.section };
        }
        editingSectionId.value = null;
        showSaveSuccess('Bahagian berjaya dikemas kini.');
    }
    sectionSubmitting.value = false;
};

const deleteSectionTarget = ref(null);
const deleteSection = async () => {
    if (!deleteSectionTarget.value) return;
    const data = await apiDelete(`/admin/forms/${props.formRecord.id}/sections/${deleteSectionTarget.value}`);
    if (data.ok) {
        localSections.value = localSections.value.filter((s) => s.id !== deleteSectionTarget.value);
        showSaveSuccess('Bahagian berjaya dipadam.');
    }
    deleteSectionTarget.value = null;
};

// ── Field CRUD ──
const FIELD_BASE = {
    form_section_id: '', label: '', field_key: '', type: 'short_text',
    placeholder: '', help_text: '', is_required: false, is_active: true,
    options_text: '', validation_json: {}, settings_json: {},
    file_max_size_kb: 5120, print_only: false,
};

const showAddFieldFor = ref(null);
const addFieldForm = ref({ ...FIELD_BASE });
const editingFieldId = ref(null);
const editFieldForm = ref({ ...FIELD_BASE });
const deleteFieldTarget = ref({ sectionId: null, fieldId: null });
const fieldSubmitting = ref(false);
const fieldError = ref('');

const openAddField = (sectionId) => {
    showAddFieldFor.value = sectionId;
    addFieldForm.value = { ...FIELD_BASE, form_section_id: sectionId, type: 'short_text', is_active: true };
    fieldError.value = '';
};

const submitAddField = async () => {
    if (!isEdit.value || !showAddFieldFor.value) return;
    fieldSubmitting.value = true;
    fieldError.value = '';
    const f = addFieldForm.value;
    const payload = {
        form_section_id: f.form_section_id,
        label: f.label,
        field_key: f.field_key,
        type: f.type,
        placeholder: f.placeholder || '',
        help_text: f.help_text || '',
        is_required: f.type === 'member_name' || f.is_required,
        options_text: f.options_text || '',
        validation_json: f.type === 'file' ? { max_size_kb: Number(f.file_max_size_kb || 5120) } : {},
        settings_json: f.type === 'office_use_box' ? { print_only: Boolean(f.print_only) } : {},
        is_active: true,
    };
    const data = await apiPost(`/admin/forms/${props.formRecord.id}/fields`, payload);
    if (data.ok && data.field) {
        const section = localSections.value.find((s) => s.id === showAddFieldFor.value);
        if (section) {
            if (!section.fields) section.fields = [];
            section.fields.push(data.field);
        }
        showAddFieldFor.value = null;
        showSaveSuccess('Soalan berjaya ditambah.');
    } else {
        fieldError.value = data.message || Object.values(data.errors || {}).join(', ') || 'Ralat berlaku. Sila cuba lagi.';
    }
    fieldSubmitting.value = false;
};

const startEditField = (field) => {
    editingFieldId.value = field.id;
    editFieldForm.value = {
        form_section_id: field.form_section_id,
        label: field.label,
        field_key: field.field_key,
        type: field.type,
        placeholder: field.placeholder || '',
        help_text: field.help_text || '',
        is_required: field.is_required,
        options_text: field.options_text || '',
        is_active: field.is_active,
        file_max_size_kb: field.file_max_size_kb || 5120,
        print_only: field.print_only || false,
    };
    fieldError.value = '';
};

const submitEditField = async () => {
    if (!isEdit.value || !editingFieldId.value) return;
    fieldSubmitting.value = true;
    fieldError.value = '';
    const f = editFieldForm.value;
    const payload = {
        form_section_id: f.form_section_id,
        label: f.label,
        field_key: f.field_key,
        type: f.type,
        placeholder: f.placeholder || '',
        help_text: f.help_text || '',
        is_required: f.is_required,
        options_text: f.options_text || '',
        validation_json: f.type === 'file' ? { max_size_kb: Number(f.file_max_size_kb || 5120) } : {},
        settings_json: f.type === 'office_use_box' ? { print_only: Boolean(f.print_only) } : {},
        is_active: f.is_active,
    };
    const data = await apiPatch(`/admin/forms/${props.formRecord.id}/fields/${editingFieldId.value}`, payload);
    if (data.ok && data.field) {
        for (const section of localSections.value) {
            const idx = (section.fields || []).findIndex((f) => f.id === editingFieldId.value);
            if (idx !== -1) { section.fields[idx] = { ...section.fields[idx], ...data.field }; break; }
        }
        editingFieldId.value = null;
        showSaveSuccess('Soalan berjaya dikemas kini.');
    } else {
        fieldError.value = data.message || Object.values(data.errors || {}).join(', ') || 'Ralat berlaku. Sila cuba lagi.';
    }
    fieldSubmitting.value = false;
};

const deleteField = async () => {
    const { sectionId, fieldId } = deleteFieldTarget.value;
    if (!fieldId) return;
    const data = await apiDelete(`/admin/forms/${props.formRecord.id}/fields/${fieldId}`);
    if (data.ok) {
        const section = localSections.value.find((s) => s.id === sectionId);
        if (section) section.fields = (section.fields || []).filter((f) => f.id !== fieldId);
        showSaveSuccess('Soalan berjaya dipadam.');
    }
    deleteFieldTarget.value = { sectionId: null, fieldId: null };
};

const duplicateField = async (section, field) => {
    const usedKeys = new Set(localSections.value.flatMap((s) => (s.fields || []).map((f) => f.field_key)));
    let newKey = field.field_key + '_copy';
    let counter = 1;
    while (usedKeys.has(newKey)) { counter++; newKey = field.field_key + '_copy' + counter; }

    const payload = {
        form_section_id: section.id,
        label: field.label + ' (Salinan)',
        field_key: newKey,
        type: field.type,
        placeholder: field.placeholder || '',
        help_text: field.help_text || '',
        is_required: field.is_required ? 1 : 0,
        options_text: field.options_text || '',
        is_active: true,
    };
    const data = await apiPost(`/admin/forms/${props.formRecord.id}/fields`, payload);
    if (data.ok && data.field) {
        const idx = section.fields ? section.fields.findIndex((f) => f.id === field.id) : -1;
        if (idx !== -1) section.fields.splice(idx + 1, 0, data.field);
        else section.fields.push(data.field);
        showSaveSuccess('Soalan berjaya disalin.');
    }
};

const updateAddFieldForm = (newVal) => { Object.assign(addFieldForm.value, newVal); };
const updateEditFieldForm = (newVal) => { Object.assign(editFieldForm.value, newVal); };

const addFieldsFromTemplate = async (sectionId, fields) => {
    if (!isEdit.value || !sectionId) return;
    for (const tmplField of fields) {
        const payload = {
            form_section_id: sectionId,
            label: tmplField.label,
            field_key: tmplField.label.toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_|_$/g, ''),
            type: tmplField.type,
            placeholder: '',
            help_text: tmplField.help_text || '',
            is_required: tmplField.is_required,
            options_text: '',
            validation_json: {},
            settings_json: {},
            is_active: true,
        };
        const data = await apiPost(`/admin/forms/${props.formRecord.id}/fields`, payload);
        if (data.ok && data.field) {
            const section = localSections.value.find((s) => s.id === sectionId);
            if (section) {
                if (!section.fields) section.fields = [];
                section.fields.push(data.field);
            }
        }
    }
    showSaveSuccess('Templat berjaya digunakan.');
};

// ── Publish actions ──
const changeStatus = (action) => {
    if (!isEdit.value || !props.formRecord) return;
    router.post(`/admin/forms/${props.formRecord.id}/${action}`, {}, { preserveScroll: true, onSuccess: () => showSaveSuccess('Status berjaya dikemas kini.') });
};

const visibilityLabelMap = { public: 'Terbuka', members_only: 'Ahli sahaja' };
</script>

<template>
    <Head :title="isEdit ? 'Edit Borang' : 'Borang Baharu'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="isEdit ? 'Edit Borang' : 'Borang Baharu'"
                description="Bina dan urus borang dalam talian."
            >
                <template #actions>
                    <StatusBadge v-if="formRecord" :status="formRecord.status" :label="{ draft: 'Draf', published: 'Diterbitkan', archived: 'Arkib' }[formRecord.status] || formRecord.status" />
                    <div v-if="saveSuccess" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 shadow-sm">
                        {{ saveSuccessMessage }}
                    </div>
                    <Button v-if="isEdit && formRecord" :as="Link" :href="formRecord.preview_pdf_url" variant="outline">
                        <FileText class="mr-2 h-4 w-4" />
                        Cetakan
                    </Button>
                    <Button v-if="isEdit && formRecord && formRecord.status === 'published'" :as="Link" :href="formRecord.public_url" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        Lihat
                    </Button>
                    <Button v-if="isEdit && formRecord && formRecord.status !== 'published'" type="button" @click="changeStatus('publish')">
                        <Send class="mr-2 h-4 w-4" />
                        Terbitkan
                    </Button>
                    <Button v-if="isEdit && formRecord && formRecord.status === 'published'" type="button" variant="outline" @click="changeStatus('unpublish')">
                        Nyahterbit
                    </Button>
                </template>
            </PageHeader>

            <!-- Tab Nav -->
            <div class="flex gap-1 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                <button
                    v-for="tab in [{ key: 'maklumat', label: 'Maklumat Borang', icon: FileText }, { key: 'borang', label: 'Kandungan Borang', icon: Layers }, { key: 'pratonton', label: 'Pratonton', icon: Eye }]"
                    :key="tab.key"
                    type="button"
                    class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                    :class="activeTab === tab.key ? 'bg-teal-700 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
                    @click="activeTab = tab.key"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                </button>
            </div>

            <!-- ════════════════════════════════════════════
                 TAB: MAKLUMAT BORANG
            ════════════════════════════════════════════ -->
            <template v-if="activeTab === 'maklumat'">
                <div v-if="showTemplates && !isEdit" class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-950">Guna Template</p>
                            <p class="text-sm text-slate-500">Pilih template untuk mengisi maklumat asas borang secara automatik.</p>
                        </div>
                        <Button type="button" variant="ghost" size="icon" @click="showTemplates = false"><X class="h-4 w-4" /></Button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="tmpl in templates" :key="tmpl.label" type="button"
                            class="rounded-xl border border-blue-200 bg-white p-3 text-left transition-colors hover:border-teal-300 hover:bg-teal-50"
                            @click="useTemplate(tmpl)"
                        >
                            <p class="text-sm font-medium text-slate-950">{{ tmpl.label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ tmpl.description }}</p>
                        </button>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="saveFormMeta">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-base font-semibold text-slate-950">Maklumat Asas</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <TextInput id="form-title" v-model="form.title" label="Tajuk Borang" :error="form.errors.title" class="md:col-span-2" />
                            <SelectInput id="form-category" v-model="form.form_category_id" label="Kategori" :options="categoryOptions" :error="form.errors.form_category_id" />
                            <div class="md:col-span-2">
                                <TextareaInput id="form-description" v-model="form.description" label="Penerangan (pilihan)" :error="form.errors.description" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-base font-semibold text-slate-950">Akses & Kaedah Hantar</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="mb-1.5 text-sm font-medium text-slate-700">Akses Borang</p>
                                <div class="space-y-2">
                                    <label v-for="opt in visibilityOptions" :key="opt.value"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors"
                                        :class="form.visibility === opt.value ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" :value="opt.value" v-model="form.visibility" class="accent-teal-700" />
                                        <span class="text-sm font-medium text-slate-900">{{ opt.label }}</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <p class="mb-1.5 text-sm font-medium text-slate-700">Kaedah Hantar</p>
                                <div class="space-y-2">
                                    <label v-for="opt in submissionMethodOptions" :key="opt.value"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors"
                                        :class="form.submission_method === opt.value ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" :value="opt.value" v-model="form.submission_method" class="accent-teal-700" />
                                        <span class="text-sm font-medium text-slate-900">{{ opt.label }}</span>
                                    </label>
                                </div>
                                <div v-if="requiresStampedUpload" class="mt-3">
                                    <TextareaInput id="stamped-instructions" v-model="form.stamped_upload_instructions" label="Arahan kepada penghantar" :error="form.errors.stamped_upload_instructions" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <button type="button" class="flex w-full items-center justify-between px-6 py-4 text-left" @click="showDocumentSettings = !showDocumentSettings">
                            <div>
                                <p class="text-sm font-semibold text-slate-950">Tetapan Dokumen Rasmi</p>
                                <p class="text-xs text-slate-500">Kod borang, no. semakan, tarikh berkuatkuasa, dan header rasmi.</p>
                            </div>
                            <ChevronDown class="h-4 w-4 text-slate-400 transition-transform" :class="showDocumentSettings ? 'rotate-180' : ''" />
                        </button>
                        <div v-if="showDocumentSettings" class="grid gap-4 border-t border-slate-100 p-6 md:grid-cols-2">
                            <TextInput id="document-code" v-model="form.document_code" label="Kod Borang" :error="form.errors.document_code" />
                            <TextInput id="revision-no" v-model="form.revision_no" label="No. Semakan" :error="form.errors.revision_no" />
                            <TextInput id="effective-date" v-model="form.effective_date" type="date" label="Tarikh Berkuatkuasa" :error="form.errors.effective_date" />
                            <TextInput id="document-title" v-model="form.document_title" label="Tajuk Dokumen" :error="form.errors.document_title" />
                            <div class="md:col-span-2">
                                <ToggleSwitch id="show-document-header" v-model="form.show_document_header" label="Paparkan Header Rasmi" description="Header dipaparkan pada borang awam, pratonton, dan cetakan." />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <Button type="button" variant="outline" :as="Link" href="/admin/forms">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            {{ isEdit ? 'Simpan Maklumat' : 'Cipta Borang' }}
                        </Button>
                    </div>
                </form>
            </template>

            <!-- ════════════════════════════════════════════
                 TAB: KANDUNGAN BORANG (Sections & Fields)
            ════════════════════════════════════════════ -->
            <template v-if="activeTab === 'borang'">
                <div v-if="!isEdit" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Simpan maklumat borang terlebih dahulu pada tab Maklumat Borang sebelum menambah bahagian dan soalan.
                </div>

                <template v-else>
                    <!-- Add Section -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-base font-semibold text-slate-950">Tambah Bahagian</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <TextInput id="section-title" v-model="sectionForm.title" label="Nama Bahagian" placeholder="cth: Maklumat Peribadi" />
                            <div class="md:col-span-2">
                                <TextareaInput id="section-description" v-model="sectionForm.description" label="Penerangan (pilihan)" />
                            </div>
                            <div class="md:col-span-2">
                                <ToggleSwitch id="section-page-break" v-model="sectionForm.page_break_before" label="Mulakan halaman baharu semasa cetakan" description="Sesuai untuk bahagian seperti pengesahan atau kegunaan pejabat." />
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <Button type="button" :disabled="!sectionForm.title || sectionSubmitting" @click="submitSection">
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Bahagian
                            </Button>
                        </div>
                    </div>

                    <!-- Sections List -->
                    <div v-if="localSections.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                        <Layers class="mx-auto mb-3 h-8 w-8 text-slate-400" />
                        <p class="font-semibold text-slate-700">Belum ada bahagian.</p>
                        <p class="mt-1 text-sm text-slate-500">Tambah bahagian seperti "Maklumat Peribadi" atau "Maklumat Pekerjaan" untuk menyusun soalan dalam borang.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <article v-for="section in localSections" :key="section.id" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                                <div class="flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-lg bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700">Bahagian</span>
                                        <h3 class="text-base font-semibold text-slate-950">{{ section.title }}</h3>
                                        <StatusBadge :status="section.is_active ? 'active' : 'inactive'" :label="section.is_active ? 'Aktif' : 'Tidak aktif'" />
                                        <StatusBadge v-if="section.page_break_before" status="info" label="Halaman baharu semasa cetakan" />
                                    </div>
                                    <p v-if="section.description" class="text-sm text-slate-500">{{ section.description }}</p>
                                    <p class="text-xs text-slate-400">{{ section.fields?.length || 0 }} soalan</p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <Button type="button" variant="outline" size="sm" @click="startEditSection(section)"><Pencil class="h-4 w-4" /></Button>
                                    <Button type="button" variant="destructive" size="sm" @click="deleteSectionTarget = section.id"><Trash2 class="h-4 w-4" /></Button>
                                </div>
                            </div>

                            <!-- Edit Section -->
                            <div v-if="editingSectionId === section.id" class="border-t border-slate-100 p-5">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <TextInput id="section-edit-title" v-model="editSectionForm.title" label="Nama Bahagian" />
                                    <div class="md:col-span-2"><TextareaInput id="section-edit-desc" v-model="editSectionForm.description" label="Penerangan" /></div>
                                    <div class="md:col-span-2"><ToggleSwitch id="section-edit-page-break" v-model="editSectionForm.page_break_before" label="Mulakan halaman baharu semasa cetakan" /></div>
                                    <div class="md:col-span-2"><ToggleSwitch id="section-edit-active" v-model="editSectionForm.is_active" label="Bahagian aktif" /></div>
                                </div>
                                <div class="mt-4 flex justify-end gap-2">
                                    <Button type="button" variant="outline" @click="editingSectionId = null">Batal</Button>
                                    <Button type="button" :disabled="sectionSubmitting" @click="submitEditSection">Simpan</Button>
                                </div>
                            </div>

                            <!-- Fields -->
                            <div class="border-t border-slate-100 p-5 pt-0">
                                <div v-if="!section.fields || section.fields.length === 0" class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-400">
                                    Belum ada soalan dalam bahagian ini.
                                </div>

                                <div v-else class="mt-4 space-y-2">
                                    <div v-for="field in section.fields" :key="field.id" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">{{ field.type_label }}</span>
                                                <span class="text-sm font-medium text-slate-900">{{ field.label }}</span>
                                                <span v-if="getFieldTypeConfig(field.type)?.isMemberAutofill" class="shrink-0 rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-600">Auto</span>
                                                <span v-if="field.is_required" class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600">Wajib</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                <Button type="button" variant="outline" size="sm" @click="duplicateField(section, field)"><Copy class="h-3.5 w-3.5" /></Button>
                                                <Button type="button" variant="outline" size="sm" @click="startEditField(field)"><Pencil class="h-3.5 w-3.5" /></Button>
                                                <Button type="button" variant="destructive" size="sm" @click="deleteFieldTarget = { sectionId: section.id, fieldId: field.id }"><Trash2 class="h-3.5 w-3.5" /></Button>
                                            </div>
                                        </div>

                                        <div v-if="editingFieldId === field.id" class="mt-3">
                                            <FormFieldEditor
                                                :model-value="editFieldForm"
                                                mode="edit"
                                                :field-error="fieldError"
                                                @update:model-value="updateEditFieldForm($event)"
                                                @save="submitEditField"
                                                @cancel="editingFieldId = null"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Field -->
                                <div v-if="showAddFieldFor === section.id" class="mt-4">
                                    <FormFieldEditor
                                        :model-value="addFieldForm"
                                        mode="add"
                                        :field-error="fieldError"
                                        @update:model-value="updateAddFieldForm($event)"
                                        @save="submitAddField"
                                        @cancel="showAddFieldFor = null"
                                    />
                                </div>
                                <div v-else class="mt-4 flex flex-wrap gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="openAddField(section.id)">
                                        <Plus class="mr-1.5 h-4 w-4" />
                                        Tambah Soalan
                                    </Button>
                                    <FieldTemplateSelector @select="(fields) => addFieldsFromTemplate(section.id, fields)" />
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            {{ localSections.length }} bahagian · {{ totalFields }} soalan
                        </div>
                    </div>
                </template>
            </template>

            <!-- ════════════════════════════════════════════
                 TAB: PRATONTON
            ════════════════════════════════════════════ -->
            <template v-if="activeTab === 'pratonton'">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-2 text-base font-semibold text-slate-950">Ringkasan Borang</h2>
                    <p class="mb-5 text-sm text-slate-500">Semak struktur borang sebelum diterbitkan.</p>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-slate-500">Tajuk</dt>
                            <dd class="mt-0.5 text-slate-950">{{ form.title || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Kategori</dt>
                            <dd class="mt-0.5 text-slate-950">{{ categoryLabel }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Akses Borang</dt>
                            <dd class="mt-0.5 text-slate-950">{{ visibilityLabel }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Kaedah Hantar</dt>
                            <dd class="mt-0.5 text-slate-950">{{ submissionMethodLabel }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Jumlah Bahagian</dt>
                            <dd class="mt-0.5 text-slate-950">{{ localSections.length }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Jumlah Soalan</dt>
                            <dd class="mt-0.5 text-slate-950">{{ totalFields }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Status</dt>
                            <dd class="mt-0.5">
                                <StatusBadge v-if="formRecord" :status="formRecord.status" :label="{ draft: 'Draf', published: 'Diterbitkan', archived: 'Arkib' }[formRecord.status] || formRecord.status" />
                                <span v-else class="text-slate-950">Draf (belum disimpan)</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div v-for="section in localSections" :key="section.id" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-950">{{ section.title }}</h3>
                    <p v-if="section.description" class="mt-1 text-sm text-slate-500">{{ section.description }}</p>
                    <div v-if="section.fields && section.fields.length > 0" class="mt-4 space-y-3">
                        <div v-for="field in section.fields" :key="field.id" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">{{ field.type_label }}</span>
                            <span class="text-sm text-slate-900">{{ field.label }}</span>
                            <span v-if="getFieldTypeConfig(field.type)?.isMemberAutofill" class="rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-600">Auto</span>
                            <span v-if="field.is_required" class="text-xs text-red-500">*</span>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-400 italic">Tiada soalan dalam bahagian ini.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-2 text-base font-semibold text-slate-950">Tindakan</h2>
                    <p class="mb-5 text-sm text-slate-500">Simpan sebagai draf untuk diteliti kemudian, atau terbitkan borang supaya pengguna boleh mengisi dan menghantar.</p>
                    <div class="flex flex-wrap gap-3">
                        <Button v-if="formRecord" :as="Link" :href="formRecord.preview_pdf_url" variant="outline">
                            <FileText class="mr-2 h-4 w-4" />
                            Pratonton Cetakan
                        </Button>
                        <Button v-if="formRecord && formRecord.status === 'published'" :as="Link" :href="formRecord.public_url" variant="outline">
                            <Eye class="mr-2 h-4 w-4" />
                            Pratonton Borang
                        </Button>
                        <Button v-if="formRecord && formRecord.status !== 'published'" type="button" @click="changeStatus('publish')">
                            <Send class="mr-2 h-4 w-4" />
                            Terbitkan Borang
                        </Button>
                        <Button v-if="formRecord && formRecord.status === 'published'" type="button" variant="outline" @click="changeStatus('unpublish')">
                            Nyahterbit
                        </Button>
                    </div>
                </div>
            </template>
        </section>

        <!-- Delete confirmations -->
        <ConfirmDialog
            :open="Boolean(deleteSectionTarget)"
            title="Padam Bahagian"
            description="Semua soalan dalam bahagian ini juga akan dipadam."
            confirm-label="Padam"
            @cancel="deleteSectionTarget = null"
            @confirm="deleteSection"
        />

        <ConfirmDialog
            :open="Boolean(deleteFieldTarget.fieldId)"
            title="Padam Soalan"
            description="Soalan ini akan dibuang daripada borang. Hantaran lama tidak terjejas."
            confirm-label="Padam"
            @cancel="deleteFieldTarget = { sectionId: null, fieldId: null }"
            @confirm="deleteField"
        />
    </AdminLayout>
</template>
