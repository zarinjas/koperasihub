<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toJpeg } from 'html-to-image';
import jsPDF from 'jspdf';
import {
    ArrowLeft,
    ArrowDown,
    ArrowUp,
    Download,
    Eye,
    Layers,
    FileText,
    Pencil,
    Plus,
    Printer,
    Save,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import CurrencyInput from '@/Shared/Components/CurrencyInput.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import RichTextEditor from '@/Shared/Components/Form/RichTextEditor.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import DocumentTemplateManager from '@/Admin/Components/Financing/DocumentTemplateManager.vue';
import SupportingDocumentManager from '@/Admin/Components/Financing/SupportingDocumentManager.vue';
import FieldTypePicker from '@/Admin/Components/Financing/FieldTypePicker.vue';
import FieldTemplateSelector from '@/Admin/Components/Financing/FieldTemplateSelector.vue';
import FormFieldEditor from '@/Admin/Components/Financing/FormFieldEditor.vue';
import MiniFieldPreview from '@/Admin/Components/Financing/MiniFieldPreview.vue';
import DynamicSectionRenderer from '@/Shared/Components/Financing/DynamicSectionRenderer.vue';
import { getFieldTypeConfig } from '@/Admin/Helpers/financingFieldTypes';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, default: null },
    categoryOptions: { type: Array, default: () => [] },
    fieldTypeOptions: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    documentTemplates: { type: Array, default: () => [] },
    supportingDocuments: { type: Array, default: () => [] },
});

const isEdit = computed(() => Boolean(props.product));
const activeTab = ref('preview');

const page = usePage();
const appSettings = computed(() => page.props.appSettings ?? {});
const cooperative = computed(() => appSettings.value?.cooperative ?? {});
const contact = computed(() => appSettings.value?.contact ?? {});

const categoryOptions = computed(() => [
    { value: '', label: 'Pilih Kategori' },
    ...props.categoryOptions.map((option) => ({
        value: String(option.value ?? ''),
        label: option.label ?? '',
    })),
]);

const isOptionsType = (type) => ['select', 'radio', 'checkbox'].includes(type);
const isRichTextType = (type) => type === 'rich_text';
const isNoteType = (type) => ['note', 'instruction_text'].includes(type);
const isAdminUploadType = (type) => ['image', 'pdf_document'].includes(type);
const isChecklistType = (type) => type === 'document_checklist';
const isSignatureType = (type) => type === 'signature_block';
const isAddressType = (type) => type === 'address_my';
const isRepeaterType = (type) => type === 'repeater_table';
const isContentType = (type) => isRichTextType(type) || isNoteType(type);

const useTieredRates = ref(props.product?.rate_tiers_json?.length > 0);

const defaultRepeaterSettings = () => JSON.stringify({
    columns: [
        { key: 'jenis_barang', label: 'Jenis Barang', type: 'text', required: true },
        { key: 'berat', label: 'Berat', type: 'text', required: true },
        { key: 'upah', label: 'Upah', type: 'currency' },
        { key: 'harga', label: 'Harga', type: 'currency', required: true },
    ],
    min_rows: 1,
    max_rows: 20,
}, null, 2);

const parseRepeaterSettings = (value) => {
    try { return JSON.parse(value || '{}'); }
    catch { return JSON.parse(defaultRepeaterSettings()); }
};

// --- Product form ---
const form = useForm({
    financing_category_id: props.product?.financing_category_id ? String(props.product.financing_category_id) : '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    min_amount: props.product?.min_amount ?? '',
    max_amount: props.product?.max_amount ?? '',
    min_tenure_months: props.product?.min_tenure_months ?? '',
    max_tenure_months: props.product?.max_tenure_months ?? '',
    annual_rate_percent: props.product?.annual_rate_percent ?? '',
    rate_tiers_json: props.product?.rate_tiers_json || [],
    rate_note: props.product?.rate_note || '',
    rate_image: null,
    requires_guarantor: Boolean(props.product?.requires_guarantor),
    guarantor_count: props.product?.guarantor_count ?? 1,
    requires_stamped_upload: Boolean(props.product?.requires_stamped_upload),
    stamped_upload_instructions: props.product?.stamped_upload_instructions || '',
    is_active: props.product?.is_active ?? true,
});

const submitProduct = () => {
    if (isEdit.value) {
        form.post(`/admin/financing/products/${props.product.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: showSaveSuccess,
        });
    } else {
        form.post('/admin/financing/products', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: showSaveSuccess,
        });
    }
};

const cancel = () => {
    router.get('/admin/financing/products');
};

// --- CSRF ---
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// --- JSON API helpers ---
const apiPost = async (url, body) => {
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    return r.json();
};

const apiPostFormData = async (url, formData) => {
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: formData,
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

const apiPatchFormData = async (url, formData) => {
    formData.append('_method', 'PATCH');
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: formData,
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

// --- Sections local state ---
const localSections = ref(props.sections.map((s) => ({
    ...s,
    fields: (s.fields || []).map((f) => ({ ...f })),
})));

const sectionForm = ref({ title: '', description: '' });
const sectionSubmitting = ref(false);

const addSection = async () => {
    if (!isEdit.value || !sectionForm.value.title.trim()) return;
    sectionSubmitting.value = true;
    const data = await apiPost(`/admin/financing/products/${props.product.id}/sections`, {
        title: sectionForm.value.title,
        description: sectionForm.value.description,
    });
    if (data.ok && data.section) {
        localSections.value.push({ ...data.section, fields: data.section.fields || [] });
        sectionForm.value = { title: '', description: '' };
        showSaveSuccess('Seksyen berjaya ditambah.');
    }
    sectionSubmitting.value = false;
};

const editingSectionId = ref(null);
const editSectionForm = ref({ title: '', description: '' });

const startEditSection = (section) => {
    editingSectionId.value = section.id;
    editSectionForm.value = { title: section.title, description: section.description || '' };
};

const submitEditSection = async () => {
    if (!isEdit.value) return;
    sectionSubmitting.value = true;
    const data = await apiPatch(
        `/admin/financing/products/${props.product.id}/sections/${editingSectionId.value}`,
        editSectionForm.value,
    );
    if (data.ok && data.section) {
        const idx = localSections.value.findIndex((s) => s.id === editingSectionId.value);
        if (idx !== -1) {
            localSections.value[idx] = { ...localSections.value[idx], title: data.section.title, description: data.section.description };
        }
        editingSectionId.value = null;
        showSaveSuccess('Seksyen berjaya dikemas kini.');
    }
    sectionSubmitting.value = false;
};

const deleteSectionTarget = ref(null);
const deleteSection = async () => {
    if (!deleteSectionTarget.value) return;
    const data = await apiDelete(`/admin/financing/products/${props.product.id}/sections/${deleteSectionTarget.value}`);
    if (data.ok) {
        localSections.value = localSections.value.filter((s) => s.id !== deleteSectionTarget.value);
    }
    deleteSectionTarget.value = null;
};

const moveSection = async (id, dir) => {
    const data = await apiPost(`/admin/financing/products/${props.product.id}/sections/${id}/${dir}`, {});
    if (data.ok) {
        const idx = localSections.value.findIndex((s) => s.id === id);
        if (dir === 'move-up' && idx > 0) {
            [localSections.value[idx - 1], localSections.value[idx]] = [localSections.value[idx], localSections.value[idx - 1]];
        } else if (dir === 'move-down' && idx < localSections.value.length - 1) {
            [localSections.value[idx], localSections.value[idx + 1]] = [localSections.value[idx + 1], localSections.value[idx]];
        }
        showSaveSuccess('Urutan dikemas kini.');
    }
};

// --- Fields local state ---
const showAddFieldFor = ref(null);
const addFieldForm = ref({
    label: '',
    type: 'short_text',
    is_required: false,
    placeholder: '',
    help_text: '',
    options: '',
    content: '',
    file_max_size_kb: 5120,
    checklist_items: [''],
    checklist_notes: [''],
    sig_left_label: 'Tandatangan Pemohon',
    sig_right_label: 'T/tangan Penerima Borang',
    sig_enable_left: true,
    sig_enable_right: true,
    repeater_settings: defaultRepeaterSettings(),
});
const fieldSubmitting = ref(false);
const fieldError = ref('');
const saveSuccess = ref(false);
const saveSuccessMessage = ref('Berjaya disimpan.');
let saveSuccessTimer = null;

const showSaveSuccess = (message) => {
    saveSuccessMessage.value = typeof message === 'string' ? message : 'Berjaya disimpan.';
    saveSuccess.value = true;
    if (saveSuccessTimer) clearTimeout(saveSuccessTimer);
    saveSuccessTimer = setTimeout(() => { saveSuccess.value = false; }, 3000);
};

watch(() => page.props.flash?.status, (val) => {
    if (val) showSaveSuccess(val);
});

const downloadPdf = async () => {
    const el = document.querySelector('.print-area');
    if (!el) return;
    try {
        const dataUrl = await toJpeg(el, { quality: 0.95, pixelRatio: 2 });
        const img = new Image();
        img.src = dataUrl;
        await new Promise((resolve) => { img.onload = resolve; });
        const imgWidth = 210;
        const pageHeight = 297;
        const imgHeight = (img.height * imgWidth) / img.width;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        let heightLeft = imgHeight;
        let position = 0;
        pdf.addImage(dataUrl, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
        while (heightLeft > 0) {
            position = position - pageHeight;
            pdf.addPage();
            pdf.addImage(dataUrl, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }
        pdf.save(`borang-${props.product?.name?.toLowerCase().replace(/\s+/g, '-') || 'produk'}.pdf`);
    } catch {
        window.print();
    }
};

const openAddField = (sectionId) => {
    showAddFieldFor.value = sectionId;
    addFieldForm.value = { label: '', type: 'short_text', is_required: false, placeholder: '', help_text: '', options: '', content: '', file_max_size_kb: 5120, checklist_items: [''], checklist_notes: [''], sig_left_label: 'Tandatangan Pemohon', sig_right_label: 'T/tangan Penerima Borang', sig_enable_left: true, sig_enable_right: true, repeater_settings: defaultRepeaterSettings() };
    fieldError.value = '';
};

const submitAddField = async () => {
    if (!isEdit.value || !showAddFieldFor.value) return;
    fieldSubmitting.value = true;
    fieldError.value = '';

    const addFieldData = addFieldForm.value;
    const hasFileUpload = isAdminUploadType(addFieldData.type) && addFieldData._uploadFile;

    try {
        if (hasFileUpload) {
            const fd = new FormData();
            fd.append('financing_product_section_id', showAddFieldFor.value);
            fd.append('label', addFieldData.label);
            fd.append('type', addFieldData.type);
            fd.append('is_required', addFieldData.is_required ? '1' : '0');
            fd.append('placeholder', addFieldData.placeholder);
            fd.append('help_text', addFieldData.help_text);
            fd.append('options', '');
            if (isContentType(addFieldData.type)) {
                fd.append('settings_json[content]', addFieldData.content);
            }
            fd.append('file', addFieldData._uploadFile);

            const data = await apiPostFormData(`/admin/financing/products/${props.product.id}/fields`, fd);
            if (data.ok && data.field) {
                const section = localSections.value.find((s) => s.id === showAddFieldFor.value);
                if (section) {
                    if (!section.fields) section.fields = [];
                    section.fields.push(data.field);
                }
                showAddFieldFor.value = null;
                showSaveSuccess('Maklumat berjaya ditambah.');
            } else {
                fieldError.value = data.message || 'Ralat berlaku. Sila cuba lagi.';
            }
        } else {
            const payload = {
                financing_product_section_id: showAddFieldFor.value,
                label: isNoteType(addFieldData.type) ? addFieldData.content : addFieldData.label,
                field_key: '',
                type: addFieldData.type,
                is_required: addFieldData.is_required,
                placeholder: addFieldData.placeholder,
                help_text: addFieldData.help_text,
                options: isOptionsType(addFieldData.type) ? addFieldData.options : '',
            };
            if (isRichTextType(addFieldData.type)) {
                payload.settings_json = { content: addFieldData.content };
            }
            if (isChecklistType(addFieldData.type)) {
                payload.settings_json = {
                    checklist_items: addFieldData.checklist_items.filter((i) => i.trim()),
                    checklist_notes: addFieldData.checklist_notes.filter((n) => n.trim()),
                };
            }
            if (isSignatureType(addFieldData.type)) {
                payload.settings_json = {
                    left_label: addFieldData.sig_left_label || 'Tandatangan Pemohon',
                    right_label: addFieldData.sig_right_label || 'T/tangan Penerima Borang',
                    enable_left: addFieldData.sig_enable_left,
                    enable_right: addFieldData.sig_enable_right,
                };
            }
            if (isRepeaterType(addFieldData.type)) {
                payload.settings_json = parseRepeaterSettings(addFieldData.repeater_settings);
            }
            const data = await apiPost(`/admin/financing/products/${props.product.id}/fields`, payload);
            if (data.ok && data.field) {
                const section = localSections.value.find((s) => s.id === showAddFieldFor.value);
                if (section) {
                    if (!section.fields) section.fields = [];
                    section.fields.push(data.field);
                }
                showAddFieldFor.value = null;
                showSaveSuccess('Maklumat berjaya ditambah.');
            } else {
                fieldError.value = data.message || 'Ralat berlaku. Sila cuba lagi.';
            }
        }
    } catch {
        fieldError.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    fieldSubmitting.value = false;
};

const editingFieldId = ref(null);
const editFieldForm = ref({
    label: '', type: 'short_text', is_required: false,
    placeholder: '', help_text: '', options: '', content: '', file_max_size_kb: 5120,
    checklist_items: [''], checklist_notes: [''],
    sig_left_label: 'Tandatangan Pemohon', sig_right_label: 'T/tangan Penerima Borang',
    sig_enable_left: true, sig_enable_right: true,
    repeater_settings: defaultRepeaterSettings(),
});

const startEditField = (field) => {
    fieldError.value = '';
    editingFieldId.value = field.id;
    const settings = typeof field.settings_json === 'string' ? JSON.parse(field.settings_json) : (field.settings_json ?? {});
    editFieldForm.value = {
        label: field.label || '',
        type: field.type || 'short_text',
        is_required: Boolean(field.is_required),
        placeholder: field.placeholder || '',
        help_text: field.help_text || '',
        options: (field.options_json || []).join('\n'),
        content: isNoteType(field.type) ? (field.label || '') : (settings.content || ''),
        file_max_size_kb: field.file_max_size_kb || 5120,
        checklist_items: settings.checklist_items?.length ? [...settings.checklist_items, ''] : [''],
        checklist_notes: settings.checklist_notes?.length ? [...settings.checklist_notes, ''] : [''],
        sig_left_label: settings.left_label || 'Tandatangan Pemohon',
        sig_right_label: settings.right_label || 'T/tangan Penerima Borang',
        sig_enable_left: settings.enable_left !== false,
        sig_enable_right: settings.enable_right !== false,
        repeater_settings: JSON.stringify({
            columns: settings.columns ?? JSON.parse(defaultRepeaterSettings()).columns,
            min_rows: settings.min_rows ?? 1,
            max_rows: settings.max_rows ?? 20,
        }, null, 2),
        _existingFile: settings.original_name || settings.file_path || '',
    };
};

const submitEditField = async () => {
    if (!isEdit.value || !editingFieldId.value) return;
    fieldSubmitting.value = true;
    fieldError.value = '';

    const editFieldData = editFieldForm.value;
    const hasFileUpload = isAdminUploadType(editFieldData.type) && editFieldData._uploadFile;

    try {
        if (hasFileUpload) {
            const fd = new FormData();
            fd.append('label', editFieldData.label);
            fd.append('type', editFieldData.type);
            fd.append('is_required', editFieldData.is_required ? '1' : '0');
            fd.append('placeholder', editFieldData.placeholder);
            fd.append('help_text', editFieldData.help_text);
            fd.append('options', '');
            if (isContentType(editFieldData.type)) {
                fd.append('settings_json[content]', editFieldData.content);
            }
            fd.append('file', editFieldData._uploadFile);

            const data = await apiPatchFormData(
                `/admin/financing/products/${props.product.id}/fields/${editingFieldId.value}`,
                fd,
            );

            if (data.ok && data.field) {
                for (const section of localSections.value) {
                    const idx = (section.fields || []).findIndex((f) => f.id === editingFieldId.value);
                    if (idx !== -1) { section.fields[idx] = { ...section.fields[idx], ...data.field }; break; }
                }
                editingFieldId.value = null;
                showSaveSuccess('Maklumat berjaya dikemas kini.');
            } else {
                fieldError.value = data.message || 'Ralat berlaku. Sila cuba lagi.';
            }
        } else {
            const payload = {
                label: isNoteType(editFieldData.type) ? editFieldData.content : editFieldData.label,
                type: editFieldData.type,
                is_required: editFieldData.is_required,
                placeholder: editFieldData.placeholder,
                help_text: editFieldData.help_text,
                options: isOptionsType(editFieldData.type) ? editFieldData.options : '',
            };
            if (isRichTextType(editFieldData.type)) {
                payload.settings_json = { content: editFieldData.content };
            }
            if (isChecklistType(editFieldData.type)) {
                payload.settings_json = {
                    checklist_items: editFieldData.checklist_items.filter((i) => i.trim()),
                    checklist_notes: editFieldData.checklist_notes.filter((n) => n.trim()),
                };
            }
            if (isSignatureType(editFieldData.type)) {
                payload.settings_json = {
                    left_label: editFieldData.sig_left_label || 'Tandatangan Pemohon',
                    right_label: editFieldData.sig_right_label || 'T/tangan Penerima Borang',
                    enable_left: editFieldData.sig_enable_left,
                    enable_right: editFieldData.sig_enable_right,
                };
            }
            if (isRepeaterType(editFieldData.type)) {
                payload.settings_json = parseRepeaterSettings(editFieldData.repeater_settings);
            }
            const data = await apiPatch(
                `/admin/financing/products/${props.product.id}/fields/${editingFieldId.value}`,
                payload,
            );
            if (data.ok && data.field) {
                for (const section of localSections.value) {
                    const idx = (section.fields || []).findIndex((f) => f.id === editingFieldId.value);
                    if (idx !== -1) { section.fields[idx] = { ...section.fields[idx], ...data.field }; break; }
                }
                editingFieldId.value = null;
                showSaveSuccess('Maklumat berjaya dikemas kini.');
            } else {
                fieldError.value = data.message || 'Ralat berlaku. Sila cuba lagi.';
            }
        }
    } catch {
        fieldError.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    fieldSubmitting.value = false;
};

const duplicateField = async (section, field) => {
    const baseKey = field.field_key;
    const usedKeys = new Set(localSections.value.flatMap((s) => (s.fields || []).map((f) => f.field_key)));
    let newKey = baseKey + '_copy';
    let counter = 1;
    while (usedKeys.has(newKey)) {
        counter++;
        newKey = baseKey + '_copy' + counter;
    }

    const payload = {
        label: field.label + ' (Salinan)',
        field_key: newKey,
        type: field.type,
        placeholder: field.placeholder || '',
        help_text: field.help_text || '',
        is_required: field.is_required ? 1 : 0,
        options: Array.isArray(field.options_json) ? field.options_json.join('\n') : '',
        settings_json: field.settings_json || {},
        financing_product_section_id: section.id,
    };

    const data = await apiPost(`/admin/financing/products/${props.product.id}/fields`, payload);
    if (data.ok) {
        if (data.field) {
            const idx = section.fields ? section.fields.findIndex((f) => f.id === field.id) : -1;
            if (idx !== -1) {
                section.fields.splice(idx + 1, 0, data.field);
            } else {
                section.fields.push(data.field);
            }
        }
        showSaveSuccess('Soalan berjaya disalin.');
    }
};

const deleteFieldTarget = ref({ sectionId: null, fieldId: null });
const deleteField = async () => {
    if (!deleteFieldTarget.value.fieldId) return;
    const data = await apiDelete(`/admin/financing/products/${props.product.id}/fields/${deleteFieldTarget.value.fieldId}`);
    if (data.ok) {
        const section = localSections.value.find((s) => s.id === deleteFieldTarget.value.sectionId);
        if (section) section.fields = (section.fields || []).filter((f) => f.id !== deleteFieldTarget.value.fieldId);
    }
    deleteFieldTarget.value = { sectionId: null, fieldId: null };
};

const moveField = async (fieldId, dir) => {
    const data = await apiPost(`/admin/financing/products/${props.product.id}/fields/${fieldId}/${dir}`, {});
    if (data.ok) {
        for (const section of localSections.value) {
            const idx = (section.fields || []).findIndex((f) => f.id === fieldId);
            if (idx !== -1) {
                if (dir === 'move-up' && idx > 0) {
                    [section.fields[idx - 1], section.fields[idx]] = [section.fields[idx], section.fields[idx - 1]];
                } else if (dir === 'move-down' && idx < section.fields.length - 1) {
                    [section.fields[idx], section.fields[idx + 1]] = [section.fields[idx + 1], section.fields[idx]];
                }
                break;
            }
        }
        showSaveSuccess('Urutan dikemas kini.');
    }
};

const totalFields = computed(() => localSections.value.reduce((sum, s) => sum + (s.fields?.length || 0), 0));

const applyTemplate = async (sectionId, templateFields, templateName) => {
    if (!isEdit.value || !sectionId || !templateFields?.length) return;
    fieldSubmitting.value = true;
    try {
        const payload = {
            fields: templateFields.map((f) => ({
                ...f,
                financing_product_section_id: sectionId,
                field_key: '',
                is_required: f.is_required ?? false,
                placeholder: f.placeholder || '',
                help_text: f.help_text || '',
                options: '',
            })),
        };
        const data = await apiPost(
            `/admin/financing/products/${props.product.id}/fields/batch`,
            payload,
        );
        if (data.ok && data.fields) {
            const section = localSections.value.find((s) => s.id === sectionId);
            if (section) {
                if (!section.fields) section.fields = [];
                section.fields.push(...data.fields);
            }
            showSaveSuccess(`Templat "${templateName}" berjaya digunakan.`);
        } else {
            fieldError.value = data.message || 'Ralat menggunakan templat.';
        }
    } catch {
        fieldError.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    fieldSubmitting.value = false;
};

const getTypeLabel = (type) => {
    const cfg = getFieldTypeConfig(type);
    return cfg ? cfg.label : type;
};

// --- Preview helpers ---
const allFields = computed(() => localSections.value.flatMap((s) =>
    (s.fields || []).map((f) => ({ ...f, sectionTitle: s.title })),
));
</script>

<template>
    <Head :title="isEdit ? `Edit ${product.name}` : 'Produk Baharu'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="isEdit ? 'Edit Produk' : 'Produk Baharu'"
                :description="isEdit ? 'Kemas kini maklumat produk dan bina borang permohonan.' : 'Daftar produk pembiayaan baharu.'"
            >
                <template #actions>
                    <Button type="button" variant="outline" @click="cancel">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <Button type="button" :disabled="form.processing" @click="submitProduct">
                        <Save class="mr-2 h-4 w-4" />
                        {{ isEdit ? 'Simpan Produk' : 'Cipta Produk' }}
                    </Button>
                </template>
            </PageHeader>

            <!-- Tabs -->
            <div class="border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-6">
                    <button type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'preview' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        @click="activeTab = 'preview'"
                    >
                        <Eye class="mr-1.5 inline h-4 w-4" />
                        Pratonton
                    </button>
                    <button type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'maklumat' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        @click="activeTab = 'maklumat'"
                    >
                        Maklumat
                    </button>
                    <button type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'borang' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        :disabled="!isEdit"
                        @click="isEdit ? activeTab = 'borang' : null"
                    >
                        <Layers class="mr-1.5 inline h-4 w-4" />
                        Borang
                    </button>
                    <button type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'dokumen' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        :disabled="!isEdit"
                        @click="isEdit ? activeTab = 'dokumen' : null"
                    >
                        <FileText class="mr-1.5 inline h-4 w-4" />
                        Dokumen
                    </button>
                </nav>
            </div>

            <!-- === TAB: PRATONTON === -->
            <template v-if="activeTab === 'preview'">
                <div class="mx-auto max-w-4xl">
                    <!-- Toolbar -->
                    <div class="mb-4 flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="downloadPdf">
                            <Download class="mr-1.5 h-4 w-4" />
                            Muat Turun PDF
                        </Button>
                        <Button type="button" variant="outline" @click="() => window.print()">
                            <Printer class="mr-1.5 h-4 w-4" />
                            Cetak
                        </Button>
                    </div>

                    <!-- A4 Preview -->
                    <div class="print-area rounded-lg border border-slate-200 bg-white shadow print:border-none print:shadow-none" style="min-height: 297mm; max-width: 210mm; padding: 20mm;">
                        <!-- Cooperative Header -->
                        <div class="mb-6 border-b pb-4 text-center">
                            <img v-if="cooperative.logo_url" :src="cooperative.logo_url" class="mx-auto mb-3 h-16 object-contain" :alt="cooperative.name" />
                            <h1 class="text-lg font-bold uppercase">{{ cooperative.name || 'Koperasi' }}</h1>
                            <p v-if="cooperative.registration_no" class="text-xs text-slate-500">No. Pendaftaran: {{ cooperative.registration_no }}</p>
                            <div v-if="contact" class="mt-1 text-xs text-slate-500">
                                <span v-if="contact.address_line_1">{{ contact.address_line_1 }}</span>
                                <span v-if="contact.address_line_2">, {{ contact.address_line_2 }}</span>
                                <span v-if="contact.postcode || contact.city || contact.state">
                                    , {{ [contact.postcode, contact.city, contact.state].filter(Boolean).join(' ') }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-2">Tarikh Cetakan: {{ new Date().toLocaleDateString('ms-MY') }}</p>
                        </div>

                        <!-- Product info -->
                        <div class="mb-6">
                            <h2 class="text-base font-bold text-slate-900">{{ form.name || 'Nama Produk' }}</h2>
                            <p v-if="form.description" class="mt-1 text-sm text-slate-600">{{ form.description }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs md:grid-cols-4">
                                <div v-if="form.min_amount || form.max_amount" class="rounded bg-slate-50 p-2">
                                    <p class="text-slate-500">Jumlah</p>
                                    <p class="font-semibold">RM {{ Number(form.min_amount || 0).toLocaleString() }} – RM {{ Number(form.max_amount || 0).toLocaleString() }}</p>
                                </div>
                                <div v-if="form.min_tenure_months || form.max_tenure_months" class="rounded bg-slate-50 p-2">
                                    <p class="text-slate-500">Tempoh</p>
                                    <p class="font-semibold">{{ form.min_tenure_months }} – {{ form.max_tenure_months }} bulan</p>
                                </div>
                                <div v-if="form.annual_rate_percent" class="rounded bg-slate-50 p-2">
                                    <p class="text-slate-500">Kadar</p>
                                    <p class="font-semibold">{{ form.annual_rate_percent }}%</p>
                                </div>
                                <div class="rounded bg-slate-50 p-2">
                                    <p class="text-slate-500">Penjamin</p>
                                    <p class="font-semibold">{{ form.requires_guarantor ? `Perlu (${form.guarantor_count})` : 'Tidak Perlu' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic form fields preview -->
                        <DynamicSectionRenderer
                            :sections="localSections"
                            mode="builder-preview"
                        >
                            <template #empty>
                                <div class="py-8 text-center text-sm text-slate-400">
                                    <Layers class="mx-auto mb-2 h-6 w-6" />
                                    <p>Belum ada borang.</p>
                                    <p v-if="isEdit" class="mt-1">Pergi ke tab Borang untuk menambah seksyen dan soalan.</p>
                                    <p v-else class="mt-1">Simpan produk dahulu, kemudian edit untuk membina borang.</p>
                                </div>
                            </template>
                        </DynamicSectionRenderer>

                        <!-- Print footer -->
                        <div class="hidden print:block mt-8 text-xs text-slate-400 border-t pt-4">
                            <p>Dokumen ini dijana secara automatik oleh sistem. Tidak memerlukan tandatangan.</p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- === TAB: MAKLUMAT === -->
            <template v-if="activeTab === 'maklumat'">
                <FormSection title="Maklumat Asas" description="Isikan butiran produk pembiayaan." :columns="2">
                    <SelectInput id="product-category" v-model="form.financing_category_id" label="Kategori" :options="categoryOptions" :error="form.errors.financing_category_id" />
                    <TextInput id="product-name" v-model="form.name" label="Nama Produk" :error="form.errors.name" />
                    <div class="md:col-span-2">
                        <TextareaInput id="product-description" v-model="form.description" label="Deskripsi" :error="form.errors.description" />
                    </div>
                </FormSection>

                <FormSection title="Jumlah & Tempoh Pembiayaan" :columns="2">
                    <CurrencyInput v-model="form.min_amount" label="Jumlah Minimum (RM)" :error="form.errors.min_amount" />
                    <CurrencyInput v-model="form.max_amount" label="Jumlah Maksimum (RM)" :error="form.errors.max_amount" />
                    <div>
                        <label class="text-sm font-medium text-slate-800">Tempoh Minimum (bulan)</label>
                        <input v-model.number="form.min_tenure_months" type="number" min="1"
                            class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            :class="form.errors.min_tenure_months ? 'border-red-500' : ''" />
                        <p v-if="form.errors.min_tenure_months" class="mt-1 text-sm text-red-700">{{ form.errors.min_tenure_months }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-800">Tempoh Maksimum (bulan)</label>
                        <input v-model.number="form.max_tenure_months" type="number" min="1"
                            class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            :class="form.errors.max_tenure_months ? 'border-red-500' : ''" />
                        <p v-if="form.errors.max_tenure_months" class="mt-1 text-sm text-red-700">{{ form.errors.max_tenure_months }}</p>
                    </div>
                </FormSection>

                <FormSection title="Kadar Keuntungan" :columns="2">
                    <div class="md:col-span-2 flex items-center gap-3">
                        <label class="text-sm font-medium text-slate-800">Jenis Kadar:</label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="rate_mode" :value="false"
                                v-model="useTieredRates" class="h-4 w-4 accent-teal-700" />
                            Kadar Tunggal
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="rate_mode" :value="true"
                                v-model="useTieredRates" class="h-4 w-4 accent-teal-700" />
                            Kadar Bertingkat
                        </label>
                    </div>

                    <template v-if="!useTieredRates">
                        <TextInput id="product-rate" v-model="form.annual_rate_percent" label="Kadar Keuntungan (%)" type="number" step="0.01" :error="form.errors.annual_rate_percent" />
                        <div></div>
                    </template>

                    <template v-else>
                        <div class="md:col-span-2 space-y-3">
                            <label class="text-sm font-medium text-slate-800">Kadar Keuntungan Mengikut Tempoh</label>
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-slate-300">
                                        <th class="py-2 pr-2 text-left font-medium text-slate-700">Tempoh (bulan)</th>
                                        <th class="py-2 pr-2 text-left font-medium text-slate-700">Kadar (%)</th>
                                        <th class="py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(tier, idx) in form.rate_tiers_json" :key="idx">
                                        <td class="py-1.5 pr-2">
                                            <div class="flex items-center gap-1">
                                                <input v-model.number="tier.min_months" type="number" min="1" placeholder="Dari"
                                                    class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20" />
                                                <span class="text-slate-400">–</span>
                                                <input v-model.number="tier.max_months" type="number" min="1" placeholder="Hingga"
                                                    class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20" />
                                                <span class="text-slate-500 text-xs ml-1">bln</span>
                                            </div>
                                        </td>
                                        <td class="py-1.5 pr-2">
                                            <div class="flex items-center gap-1">
                                                <input v-model.number="tier.rate_percent" type="number" step="0.01" placeholder="0.00"
                                                    class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20" />
                                                <span class="text-slate-500">%</span>
                                            </div>
                                        </td>
                                        <td class="py-1.5">
                                            <button type="button" @click="form.rate_tiers_json.splice(idx, 1)"
                                                class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" @click="form.rate_tiers_json.push({ min_months: 1, max_months: 12, rate_percent: 0 })"
                                class="text-sm font-medium text-teal-700 hover:text-teal-800">
                                + Tambah Julat Kadar
                            </button>
                            <p v-if="form.errors.rate_tiers_json" class="text-sm text-red-700">{{ form.errors.rate_tiers_json }}</p>
                        </div>
                    </template>

                    <div class="md:col-span-2">
                        <TextareaInput id="product-rate-note" v-model="form.rate_note" label="Nota Kadar" :error="form.errors.rate_note" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-800">Imej Kadar Keuntungan</label>
                        <input type="file" accept="image/*"
                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
                            @change="(e) => { const f = e.target.files?.[0]; if (f) form.rate_image = f; }" />
                        <img v-if="product?.existing_rate_image_url && !form.rate_image"
                            :src="product.existing_rate_image_url"
                            class="h-32 rounded-lg border border-slate-200 object-contain" alt="Imej kadar semasa" />
                        <p v-if="form.errors.rate_image" class="text-sm text-red-700">{{ form.errors.rate_image }}</p>
                    </div>
                </FormSection>

                <FormSection title="Tetapan Lanjutan" :columns="2">
                    <div class="md:col-span-2">
                        <ToggleSwitch id="product-guarantor" v-model="form.requires_guarantor" label="Perlukan Penjamin?" description="Aktifkan jika produk ini memerlukan penjamin." />
                    </div>
                    <div v-if="form.requires_guarantor">
                        <label class="text-sm font-medium text-slate-800">Bilangan Penjamin</label>
                        <input v-model.number="form.guarantor_count" type="number" min="1"
                            class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            :class="form.errors.guarantor_count ? 'border-red-500' : ''" />
                        <p v-if="form.errors.guarantor_count" class="mt-1 text-sm text-red-700">{{ form.errors.guarantor_count }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <ToggleSwitch id="product-stamped" v-model="form.requires_stamped_upload" label="Perlukan Muat Naik Borang Bercop?" description="Aktifkan jika pemohon perlu memuat naik borang bercop." />
                    </div>
                    <div v-if="form.requires_stamped_upload" class="md:col-span-2">
                        <TextareaInput id="product-stamped-instructions" v-model="form.stamped_upload_instructions" label="Arahan Muat Naik Borang Bercop" :error="form.errors.stamped_upload_instructions" />
                    </div>
                </FormSection>

                <FormSection title="Paparan" :columns="2">
                    <div class="md:col-span-2">
                        <ToggleSwitch id="product-active" v-model="form.is_active" label="Produk Aktif" description="Produk tidak aktif tidak akan dipaparkan kepada ahli." />
                    </div>
                </FormSection>
            </template>

            <!-- === TAB: BORANG === -->
            <template v-if="activeTab === 'borang' && isEdit">
                <div class="grid grid-cols-1 xl:grid-cols-[1.3fr_1fr] gap-6">
                <!-- Left: Form Builder -->
                <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-base font-semibold text-slate-950">Tambah Seksyen</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <TextInput id="section-title" v-model="sectionForm.title" label="Tajuk Seksyen" placeholder="cth: Maklumat Peribadi" />
                        <div class="md:col-span-2">
                            <TextareaInput id="section-description" v-model="sectionForm.description" label="Penerangan (pilihan)" />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <Button type="button" :disabled="!sectionForm.title.trim() || sectionSubmitting" @click="addSection">
                            <Plus class="mr-2 h-4 w-4" /> Tambah Seksyen
                        </Button>
                    </div>
                </div>

                <div v-if="localSections.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                    <Layers class="mx-auto mb-3 h-8 w-8 text-slate-400" />
                    <p class="font-semibold text-slate-700">Belum ada seksyen.</p>
                    <p class="mt-1 text-sm text-slate-500">Tambah seksyen untuk menyusun medan dalam borang permohonan.</p>
                </div>

                <div v-else class="space-y-4">
                    <article v-for="section in localSections" :key="section.id" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                            <div class="flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-lg bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700">Seksyen</span>
                                    <h3 class="text-base font-semibold text-slate-950">{{ section.title }}</h3>
                                </div>
                                <p v-if="section.description" class="text-sm text-slate-500">{{ section.description }}</p>
                                <p class="text-xs text-slate-400">{{ section.fields?.length || 0 }} medan</p>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <Button type="button" variant="outline" size="sm" title="Naikkan" :disabled="section === localSections[0]" @click="moveSection(section.id, 'move-up')"><ArrowUp class="h-4 w-4" /></Button>
                                <Button type="button" variant="outline" size="sm" title="Turunkan" :disabled="section === localSections[localSections.length - 1]" @click="moveSection(section.id, 'move-down')"><ArrowDown class="h-4 w-4" /></Button>
                                <Button type="button" variant="outline" size="sm" @click="startEditSection(section)"><Pencil class="h-4 w-4" /></Button>
                                <Button type="button" variant="destructive" size="sm" @click="deleteSectionTarget = section.id"><Trash2 class="h-4 w-4" /></Button>
                            </div>
                        </div>

                        <div v-if="editingSectionId === section.id" class="border-t border-slate-100 p-5">
                            <div class="grid gap-4 md:grid-cols-2">
                                <TextInput id="section-edit-title" v-model="editSectionForm.title" label="Tajuk Seksyen" />
                                <div class="md:col-span-2"><TextareaInput id="section-edit-description" v-model="editSectionForm.description" label="Penerangan" /></div>
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="editingSectionId = null">Batal</Button>
                                <Button type="button" :disabled="sectionSubmitting" @click="submitEditSection">Simpan</Button>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 p-5 pt-0">
                            <div v-if="!section.fields || section.fields.length === 0" class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-400">
                                Belum ada medan dalam seksyen ini.
                            </div>

                            <div v-else class="mt-4 space-y-2">
                                <div v-for="field in section.fields" :key="field.id"
                                    class="group rounded-lg border bg-white p-3 transition-all hover:border-slate-300 hover:shadow-sm"
                                    :class="editingFieldId === field.id ? 'ring-2 ring-teal-500/20 border-teal-200' : 'border-slate-200'">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
                                                {{ getTypeLabel(field.type) }}
                                            </span>
                                            <span class="text-sm font-medium text-slate-900">{{ field.label || '—' }}</span>
                                            <span v-if="field.is_required" class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600">Wajib</span>
                                            <span v-if="isAdminUploadType(field.type) && field.settings_json?.file_path"
                                                class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-600">Fail Dimuat Naik</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <Button type="button" variant="outline" size="sm" title="Naikkan" :disabled="field === section.fields[0]" @click="moveField(field.id, 'move-up')"><ArrowUp class="h-3.5 w-3.5" /></Button>
                                            <Button type="button" variant="outline" size="sm" title="Turunkan" :disabled="field === section.fields[section.fields.length - 1]" @click="moveField(field.id, 'move-down')"><ArrowDown class="h-3.5 w-3.5" /></Button>
                                            <Button type="button" variant="outline" size="sm" @click="startEditField(field)"><Pencil class="h-3.5 w-3.5" /></Button>
                                            <Button type="button" variant="outline" size="sm" title="Duplikat" @click="duplicateField(section, field)"><FileText class="h-3.5 w-3.5" /></Button>
                                            <Button type="button" variant="destructive" size="sm" @click="deleteFieldTarget = { sectionId: section.id, fieldId: field.id }"><Trash2 class="h-3.5 w-3.5" /></Button>
                                        </div>
                                    </div>

                                    <MiniFieldPreview v-if="editingFieldId !== field.id" :field="field" />

                                    <!-- Edit field inline -->
                                    <div v-if="editingFieldId === field.id" class="mt-3 border-t border-slate-100 pt-3">
                                        <FormFieldEditor
                                            v-model="editFieldForm"
                                            mode="edit"
                                            :field-error="fieldError"
                                            @save="submitEditField"
                                            @cancel="editingFieldId = null; fieldError = ''"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Add field (compact) -->
                            <div v-if="showAddFieldFor === section.id" class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <div class="w-80 shrink-0">
                                        <FieldTypePicker v-model="addFieldForm.type" compact />
                                    </div>
                                    <div class="flex-1 space-y-2 min-w-0">
                                        <input v-model="addFieldForm.label"
                                            :placeholder="isNoteType(addFieldForm.type) ? 'Kandungan nota...' : 'Label soalan, cth: Nama Penuh Pemohon'"
                                            class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-1 focus:ring-teal-700/20" />
                                        <div v-if="isOptionsType(addFieldForm.type)" class="space-y-1">
                                            <textarea v-model="addFieldForm.options"
                                                placeholder="Satu pilihan setiap baris"
                                                class="h-20 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-1 focus:ring-teal-700/20" />
                                            <p class="text-xs text-slate-400">Jika pilihan kosong, akan ditambah kemudian.</p>
                                        </div>
                                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                                            <input type="checkbox" v-model="addFieldForm.is_required"
                                                class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                            Wajib Diisi
                                        </label>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <Button size="sm" :disabled="fieldSubmitting || !addFieldForm.label.trim()" @click="submitAddField">
                                            <Plus class="mr-1 h-3.5 w-3.5" />
                                            Tambah
                                        </Button>
                                        <Button size="sm" variant="outline" @click="showAddFieldFor = null; fieldError = ''">
                                            Batal
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="fieldError" class="mt-2 text-xs text-red-600">{{ fieldError }}</p>
                            </div>
                            <div v-else class="mt-4 space-y-2">
                                <Button type="button" variant="outline" size="sm" @click="openAddField(section.id)">
                                    <Plus class="mr-1.5 h-4 w-4" /> Tambah Maklumat
                                </Button>
                                <FieldTemplateSelector @select="(fields, name) => applyTemplate(section.id, fields, name)" />
                            </div>
                        </div>
                    </article>
                </div>
                </div><!-- /left panel -->

                <!-- Right: Live Preview -->
                <div class="xl:sticky xl:top-6 xl:self-start xl:max-h-[calc(100vh-8rem)] xl:overflow-y-auto">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center gap-2 border-b border-slate-100 pb-2">
                            <Eye class="h-4 w-4 text-slate-500" />
                            <h3 class="text-sm font-semibold text-slate-800">Pratonton Langsung</h3>
                        </div>

                        <DynamicSectionRenderer
                            :sections="localSections"
                            mode="builder-preview"
                        >
                            <template #empty>
                                <div class="py-6 text-center text-sm text-slate-400">
                                    <Layers class="mx-auto mb-2 h-5 w-5" />
                                    <p>Belum ada seksyen.</p>
                                </div>
                            </template>
                        </DynamicSectionRenderer>
                    </div>
                </div><!-- /right preview -->

                </div><!-- /grid -->
            </template>

            <!-- === TAB: DOKUMEN === -->
            <template v-if="activeTab === 'dokumen' && isEdit">
                <div class="space-y-6">
                    <DocumentTemplateManager :product-id="product.id" :templates="documentTemplates" />
                    <SupportingDocumentManager :product-id="product.id" :documents="supportingDocuments" />
                </div>
            </template>
        </section>

        <ConfirmDialog :open="Boolean(deleteSectionTarget)" title="Padam Seksyen" description="Semua medan dalam seksyen ini juga akan dipadam." confirm-label="Padam" @cancel="deleteSectionTarget = null" @confirm="deleteSection" />
        <ConfirmDialog :open="Boolean(deleteFieldTarget.fieldId)" title="Padam Maklumat" description="Medan ini akan dibuang daripada borang." confirm-label="Padam" @cancel="deleteFieldTarget = { sectionId: null, fieldId: null }" @confirm="deleteField" />

        <!-- Save success toast -->
        <div v-if="saveSuccess" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl bg-teal-700 px-5 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            {{ saveSuccessMessage }}
        </div>
    </AdminLayout>
</template>

<style scoped>
</style>

<style>
@media print {
    body > * { display: none !important; }
    .print-area, .print-area * { display: revert !important; }
    .print-area { display: block !important; margin: 0 auto; }
}
</style>