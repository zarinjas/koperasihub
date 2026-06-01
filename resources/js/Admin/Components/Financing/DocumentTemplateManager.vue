<script setup>
import { ref } from 'vue';
import { Button } from '@/Shared/Components/ui/button';

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

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        form.value = {
            code: '',
            name: '',
            type: 'application_form',
            source_type: 'html',
            requires_upload: true,
            requires_verification: true,
            sort_order: 0,
            is_active: true,
            html_template: '',
        };
        file.value = null;
    } catch {
        error.value = 'Ralat rangkaian. Sila cuba lagi.';
    }
    saving.value = false;
};
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-950">Templat Dokumen</h2>
            <p class="mt-1 text-sm text-slate-500">Daftar dokumen yang perlu dijana atau dimuat naik semula untuk produk ini.</p>

            <div v-if="items.length" class="mt-5 space-y-2">
                <div v-for="template in items" :key="template.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-950">{{ template.name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ template.code }} · {{ template.type }} · {{ template.source_type }}</p>
                </div>
            </div>
            <p v-else class="mt-5 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Belum ada templat dokumen.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-950">Tambah Templat</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <input v-model="form.code" type="text" placeholder="Kod dokumen, cth: pdpa_consent" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
                <input v-model="form.name" type="text" placeholder="Nama dokumen" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm" />
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
                    <input v-model="form.requires_upload" type="checkbox" /> Perlu muat naik semula
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.requires_verification" type="checkbox" /> Perlu semakan admin
                </label>
                <textarea v-if="form.source_type === 'html'" v-model="form.html_template" rows="8" class="md:col-span-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm" placeholder="Kandungan HTML. Placeholder: {{ member.name }}, {{ application.reference_no }}, {{ answers.nama_field }}"></textarea>
                <input v-if="form.source_type === 'pdf_upload'" type="file" accept=".pdf,.html,.htm" class="md:col-span-2 text-sm" @change="(event) => file = event.target.files?.[0] ?? null" />
            </div>
            <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
            <div class="mt-4 flex justify-end">
                <Button type="button" :disabled="saving || !form.code || !form.name" @click="submit">
                    {{ saving ? 'Menyimpan...' : 'Tambah Templat' }}
                </Button>
            </div>
        </div>
    </div>
</template>
