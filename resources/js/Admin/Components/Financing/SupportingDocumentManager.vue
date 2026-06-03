<script setup>
import { ref } from 'vue';
import { Plus, Pencil, Trash2, Layers, FileText } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';

const props = defineProps({
    productId: { type: Number, required: true },
    documents: { type: Array, default: () => [] },
});

const items = ref([...props.documents]);
const editingId = ref(null);
const showForm = ref(false);
const saving = ref(false);
const error = ref('');
const deleteTarget = ref(null);
const deleting = ref(false);

const defaultForm = () => ({
    name: '',
    description: '',
    mode: 'single',
    count: 3,
    is_required: true,
    accepted_types: 'pdf,jpg,jpeg,png',
    max_size_kb: 5120,
    sort_order: 0,
    is_active: true,
});

const form = ref(defaultForm());

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const modeLabel = (mode) => ({ single: 'Satu Fail', multiple: 'Banyak Fail', monthly: 'Bulanan' }[mode] || mode);

const slotPreview = (doc) => {
    if (doc.mode === 'monthly') {
        return [...Array(doc.count)].map((_, i) => `${doc.name} ${i + 1}/${doc.count}`);
    }
    if (doc.mode === 'multiple') {
        return [`${doc.name} (maksimum ${doc.count})`];
    }
    return [doc.name];
};

const startAdd = () => {
    form.value = defaultForm();
    editingId.value = null;
    showForm.value = true;
    error.value = '';
};

const startEdit = (doc) => {
    form.value = {
        name: doc.name,
        description: doc.description || '',
        mode: doc.mode,
        count: doc.count,
        is_required: doc.is_required,
        accepted_types: doc.accepted_types,
        max_size_kb: doc.max_size_kb,
        sort_order: doc.sort_order,
        is_active: doc.is_active,
    };
    editingId.value = doc.id;
    showForm.value = true;
    error.value = '';
};

const cancelForm = () => {
    showForm.value = false;
    editingId.value = null;
    error.value = '';
};

const submit = async () => {
    saving.value = true;
    error.value = '';

    const url = editingId.value
        ? `/admin/financing/products/${props.productId}/supporting-documents/${editingId.value}`
        : `/admin/financing/products/${props.productId}/supporting-documents`;

    const method = editingId.value ? 'PATCH' : 'POST';

    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...(method === 'PATCH' ? { 'X-HTTP-Method-Override': 'PATCH' } : {}),
            },
            body: JSON.stringify(form.value),
        });

        const data = await r.json();

        if (r.ok && data.document) {
            if (editingId.value) {
                const idx = items.value.findIndex((d) => d.id === editingId.value);
                if (idx !== -1) {
                    items.value[idx] = { ...items.value[idx], ...data.document };
                }
            } else {
                items.value.push(data.document);
            }
            cancelForm();
        } else {
            error.value = data.message || 'Ralat menyimpan.';
        }
    } catch {
        error.value = 'Ralat rangkaian.';
    }
    saving.value = false;
};

const confirmDelete = async () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        const r = await fetch(
            `/admin/financing/products/${props.productId}/supporting-documents/${deleteTarget.value}`,
            { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() } },
        );
        if (r.ok) {
            items.value = items.value.filter((d) => d.id !== deleteTarget.value);
        }
    } catch {}
    deleteTarget.value = null;
    deleting.value = false;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Dokumen Sokongan Pemohon</h3>
                <p class="mt-0.5 text-sm text-slate-500">Tetapkan dokumen sokongan yang perlu dimuat naik oleh pemohon.</p>
            </div>
            <Button v-if="!showForm" type="button" size="sm" @click="startAdd">
                <Plus class="mr-1 h-4 w-4" /> Tambah Dokumen
            </Button>
        </div>

        <!-- Existing list -->
        <div v-if="items.length && !showForm" class="space-y-2">
            <div v-for="doc in items" :key="doc.id"
                class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="space-y-1 min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900">{{ doc.name }}</span>
                        <span v-if="doc.is_required" class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-medium text-red-600">Wajib</span>
                        <span v-else class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Pilihan</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="rounded bg-slate-100 px-1.5 py-0.5">{{ modeLabel(doc.mode) }}</span>
                        <span v-if="doc.mode === 'monthly'">{{ doc.count }} slot</span>
                        <span>{{ doc.accepted_types }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span v-for="label in slotPreview(doc)" :key="label"
                            class="rounded bg-teal-50 px-2 py-0.5 text-[11px] text-teal-700 font-medium">
                            {{ label }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-1">
                    <Button type="button" variant="outline" size="sm" @click="startEdit(doc)">
                        <Pencil class="h-3.5 w-3.5" />
                    </Button>
                    <Button type="button" variant="destructive" size="sm" @click="deleteTarget = doc.id">
                        <Trash2 class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="!items.length && !showForm" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center">
            <Layers class="mx-auto mb-2 h-6 w-6 text-slate-300" />
            <p class="text-sm text-slate-500">Belum ada dokumen sokongan.</p>
            <p class="mt-0.5 text-xs text-slate-400">Tambah dokumen yang perlu dimuat naik oleh pemohon.</p>
        </div>

        <!-- Add / Edit form -->
        <div v-if="showForm" class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="mb-4 text-sm font-semibold text-slate-900">
                {{ editingId ? 'Edit Dokumen Sokongan' : 'Dokumen Sokongan Baharu' }}
            </p>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2 space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Nama Dokumen</label>
                    <input v-model="form.name" type="text" placeholder="cth: Slip Gaji"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div class="md:col-span-2 space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Penerangan (pilihan)</label>
                    <input v-model="form.description" type="text" placeholder="cth: Slip gaji 3 bulan terkini"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Mod</label>
                    <select v-model="form.mode"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20">
                        <option value="single">Satu Fail</option>
                        <option value="multiple">Banyak Fail</option>
                        <option value="monthly">Bulanan</option>
                    </select>
                </div>

                <div v-if="form.mode === 'monthly'" class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Bilangan Slot</label>
                    <input v-model.number="form.count" type="number" min="1" max="50"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div v-if="form.mode === 'multiple'" class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Maksimum Fail</label>
                    <input v-model.number="form.count" type="number" min="1" max="50"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Jenis Fail Diterima</label>
                    <input v-model="form.accepted_types" type="text" placeholder="pdf,jpg,png"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800">Had Saiz (KB)</label>
                    <input v-model.number="form.max_size_kb" type="number" min="1"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                </div>

                <div class="md:col-span-2 flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.is_required"
                            class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                        Wajib
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.is_active"
                            class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                        Aktif
                    </label>
                </div>

                <!-- Slot preview -->
                <div v-if="form.name && form.mode === 'monthly'" class="md:col-span-2 rounded-lg border border-teal-200 bg-teal-50 p-3">
                    <p class="text-xs font-medium text-teal-800 mb-1">Pratonton Slot:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(_, i) in [...Array(form.count)]" :key="i"
                            class="rounded bg-teal-100 px-2 py-0.5 text-[11px] font-medium text-teal-700">
                            {{ form.name }} {{ i + 1 }}/{{ form.count }}
                        </span>
                    </div>
                </div>
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>

            <div class="mt-4 flex justify-end gap-2">
                <Button type="button" variant="outline" @click="cancelForm">Batal</Button>
                <Button type="button" :disabled="saving || !form.name.trim()" @click="submit">
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </div>

        <ConfirmDialog
            :open="Boolean(deleteTarget)"
            title="Padam Dokumen Sokongan"
            description="Dokumen ini akan dibuang daripada senarai dokumen sokongan produk ini."
            confirm-label="Padam"
            @cancel="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
