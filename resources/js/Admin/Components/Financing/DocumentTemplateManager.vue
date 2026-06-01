<script setup>
import { ref } from 'vue';
import { Trash2, Upload, FileText, AlertTriangle } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';

const props = defineProps({
    productId: { type: Number, required: true },
    templates: { type: Array, default: () => [] },
});

const items = ref([...props.templates]);
const form = ref({
    code: '',
    name: '',
    type: 'application_form',
    source_type: 'html',
    requires_upload: true,
    requires_verification: true,
    sort_order: 0,
    is_active: true,
    html_template: '',
});
const file = ref(null);
const saving = ref(false);
const error = ref('');
const deleteTarget = ref(null);
const deleting = ref(false);
const editingId = ref(null);
const editFile = ref(null);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const typeLabel = (type) => ({
    application_form: 'Borang Permohonan',
    guarantor_form: 'Borang Penjamin',
    pdpa_consent: 'Persetujuan PDPA',
    undertaking_letter: 'Surat Aku Janji',
    wakalah: 'Wakalah',
    checklist: 'Senarai Semak',
    other: 'Lain-lain',
}[type] || type);

const sourceLabel = (src) => ({
    html: 'HTML Dijana',
    pdf_upload: 'PDF Sedia Ada',
    manual_upload_only: 'Muat Naik Manual',
}[src] || src);

const resetForm = () => {
    form.value = {
        code: '', name: '', type: 'application_form', source_type: 'html',
        requires_upload: true, requires_verification: true,
        sort_order: 0, is_active: true, html_template: '',
    };
    file.value = null;
    error.value = '';
};

const submit = async () => {
    saving.value = true;
    error.value = '';
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        fd.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value ?? ''));
    });
    if (file.value) fd.append('template_file', file.value, file.value.name);

    try {
        const response = await fetch(`/admin/financing/products/${props.productId}/document-templates`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: fd,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            error.value = data.message || 'Dokumen tidak dapat disimpan.';
            saving.value = false;
            return;
        }

        items.value.push(data.template);
        resetForm();
    } catch {
        error.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    saving.value = false;
};

const confirmDelete = (template) => {
    deleteTarget.value = template;
};

const executeDelete = async () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        const response = await fetch(
            `/admin/financing/products/${props.productId}/document-templates/${deleteTarget.value.id}`,
            { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } },
        );
        const data = await response.json().catch(() => ({}));
        if (response.ok) {
            items.value = items.value.filter((t) => t.id !== deleteTarget.value.id);
        } else {
            error.value = data.message || 'Templat tidak dapat dipadam.';
        }
    } catch {
        error.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    deleting.value = false;
    deleteTarget.value = null;
};

const startEdit = (template) => {
    editingId.value = template.id;
    form.value = { ...template };
    file.value = null;
    editFile.value = null;
    error.value = '';
};

const cancelEdit = () => {
    editingId.value = null;
    resetForm();
};

const submitEdit = async () => {
    if (!editingId.value) return;
    saving.value = true;
    error.value = '';
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
        if (key === 'template_path' || key === 'template_url') return;
        fd.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value ?? ''));
    });
    fd.append('_method', 'PATCH');
    if (editFile.value) fd.append('template_file', editFile.value, editFile.value.name);

    try {
        const response = await fetch(
            `/admin/financing/products/${props.productId}/document-templates/${editingId.value}`,
            { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: fd },
        );
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            error.value = data.message || 'Templat tidak dapat dikemas kini.';
            saving.value = false;
            return;
        }
        const idx = items.value.findIndex((t) => t.id === editingId.value);
        if (idx !== -1) items.value[idx] = data.template;
        editingId.value = null;
        resetForm();
    } catch {
        error.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    saving.value = false;
};
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-950">Dokumen & Templat</h2>
            <p class="mt-1 text-sm text-slate-500">Senarai dokumen yang perlu dijana atau dimuat naik untuk produk ini.</p>

            <div v-if="items.length" class="mt-5 space-y-3">
                <div v-for="template in items" :key="template.id"
                    class="rounded-xl border border-slate-200 p-4 transition hover:border-slate-300"
                    :class="editingId === template.id ? 'border-teal-200 bg-teal-50' : 'bg-white'"
                >
                    <!-- Display mode -->
                    <template v-if="editingId !== template.id">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 shrink-0 text-slate-500" />
                                    <p class="text-sm font-semibold text-slate-950">{{ template.name }}</p>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ typeLabel(template.type) }}
                                    <span class="mx-1">·</span>
                                    {{ sourceLabel(template.source_type) }}
                                    <span class="mx-1">·</span>
                                    <code class="rounded bg-slate-100 px-1 py-0.5 text-[10px]">{{ template.code }}</code>
                                </p>
                                <div class="mt-1.5 flex flex-wrap gap-3 text-[11px] text-slate-500">
                                    <span v-if="template.requires_upload" class="text-amber-600">Perlu muat naik</span>
                                    <span v-if="template.requires_verification" class="text-blue-600">Perlu semakan</span>
                                    <span v-if="template.template_url || template.html_template" class="text-green-600">Sedia</span>
                                    <span v-else class="text-slate-400">Belum sedia</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-1.5">
                                <Button type="button" variant="outline" size="sm" @click="startEdit(template)">
                                    Edit
                                </Button>
                                <Button type="button" variant="destructive" size="sm" @click="confirmDelete(template)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                        <!-- File upload status -->
                        <div v-if="template.template_url" class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                            <Upload class="h-3 w-3" />
                            <a :href="template.template_url" target="_blank" class="text-teal-700 hover:underline">Lihat fail</a>
                        </div>
                    </template>

                    <!-- Edit mode -->
                    <template v-else>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="form.code" type="text" placeholder="Kod dokumen"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
                            <input v-model="form.name" type="text" placeholder="Nama dokumen"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
                            <select v-model="form.type" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="application_form">Borang Permohonan</option>
                                <option value="guarantor_form">Borang Penjamin</option>
                                <option value="pdpa_consent">Persetujuan PDPA</option>
                                <option value="undertaking_letter">Surat Aku Janji</option>
                                <option value="wakalah">Wakalah</option>
                                <option value="checklist">Senarai Semak</option>
                                <option value="other">Lain-lain</option>
                            </select>
                            <select v-model="form.source_type" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="html">HTML Dijana</option>
                                <option value="pdf_upload">PDF Sedia Ada</option>
                                <option value="manual_upload_only">Muat Naik Manual Sahaja</option>
                            </select>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input v-model="form.requires_upload" type="checkbox" class="accent-teal-700" /> Perlu muat naik
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input v-model="form.requires_verification" type="checkbox" class="accent-teal-700" /> Perlu semakan
                            </label>
                            <textarea v-if="form.source_type === 'html'" v-model="form.html_template" rows="6"
                                class="md:col-span-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-mono"
                                placeholder="HTML template. Placeholder: &#123;&#123; member.name &#125;&#125;, &#123;&#123; application.reference_no &#125;&#125;"></textarea>
                            <div v-if="form.source_type === 'pdf_upload'" class="md:col-span-2 space-y-1.5">
                                <label class="text-sm font-medium text-slate-700">Fail Templat</label>
                                <input type="file" accept=".pdf,.html,.htm"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
                                    @change="(e) => editFile = e.target.files?.[0] ?? null" />
                                <p v-if="template.template_url" class="text-xs text-slate-500">
                                    Fail sedia ada: <a :href="template.template_url" target="_blank" class="text-teal-700 hover:underline">lihat</a>
                                </p>
                            </div>
                        </div>
                        <p v-if="error" class="mt-2 text-sm text-red-700">{{ error }}</p>
                        <div class="mt-3 flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="cancelEdit">Batal</Button>
                            <Button type="button" :disabled="saving || !form.code || !form.name" @click="submitEdit">
                                {{ saving ? 'Menyimpan...' : 'Simpan' }}
                            </Button>
                        </div>
                    </template>
                </div>
            </div>

            <p v-else class="mt-5 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
                <FileText class="mx-auto mb-2 h-5 w-5" />
                Belum ada dokumen. Tambah templat dokumen untuk produk ini.
            </p>
        </div>

        <!-- Add new template -->
        <div v-if="!editingId" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-950">Tambah Dokumen Baru</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <input v-model="form.code" type="text" placeholder="Kod dokumen, cth: pdpa_consent"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
                <input v-model="form.name" type="text" placeholder="Nama dokumen"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
                <select v-model="form.type" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="application_form">Borang Permohonan</option>
                    <option value="guarantor_form">Borang Penjamin</option>
                    <option value="pdpa_consent">Persetujuan PDPA</option>
                    <option value="undertaking_letter">Surat Aku Janji</option>
                    <option value="wakalah">Wakalah</option>
                    <option value="checklist">Senarai Semak</option>
                    <option value="other">Lain-lain</option>
                </select>
                <select v-model="form.source_type" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="html">HTML Dijana</option>
                    <option value="pdf_upload">PDF Sedia Ada</option>
                    <option value="manual_upload_only">Muat Naik Manual Sahaja</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.requires_upload" type="checkbox" class="accent-teal-700" /> Perlu muat naik
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.requires_verification" type="checkbox" class="accent-teal-700" /> Perlu semakan
                </label>
                <textarea v-if="form.source_type === 'html'" v-model="form.html_template" rows="8"
                    class="md:col-span-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-mono"
                    placeholder="HTML content. Placeholders: {{ member.name }}, {{ application.reference_no }}, {{ answers.field_key }}"></textarea>
                <div v-if="form.source_type === 'pdf_upload'" class="md:col-span-2 space-y-1.5">
                    <label class="text-sm font-medium text-slate-700">Fail Templat</label>
                    <input type="file" accept=".pdf,.html,.htm"
                        class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
                        @change="(e) => file = e.target.files?.[0] ?? null" />
                </div>
            </div>
            <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
            <div class="mt-4 flex justify-end">
                <Button type="button" :disabled="saving || !form.code || !form.name" @click="submit">
                    {{ saving ? 'Menyimpan...' : 'Tambah Dokumen' }}
                </Button>
            </div>
        </div>

        <ConfirmDialog
            :open="Boolean(deleteTarget)"
            title="Padam Templat Dokumen"
            :description="'Templat ' + (deleteTarget?.name || '') + ' akan dipadam secara kekal. Tindakan ini tidak boleh dibatalkan.'"
            confirm-label="Padam"
            :confirm-disabled="deleting"
            @cancel="deleteTarget = null"
            @confirm="executeDelete"
        />
    </div>
</template>