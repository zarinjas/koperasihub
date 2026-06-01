<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    tenures: { type: Array, required: true },
});

const dialogOpen = ref(false);
const editingId = ref(null);

const form = useForm({
    months: 12,
    interest_rate_percent: 0,
    label: '',
    is_active: true,
});

const openAdd = () => {
    editingId.value = null;
    form.reset();
    form.months = 12;
    form.interest_rate_percent = 0;
    form.label = '';
    form.is_active = true;
    dialogOpen.value = true;
};

const openEdit = (t) => {
    editingId.value = t.id;
    form.months = t.months;
    form.interest_rate_percent = t.interest_rate_percent;
    form.label = t.label || '';
    form.is_active = t.is_active;
    dialogOpen.value = true;
};

const save = () => {
    if (editingId.value) {
        form.put('/admin/ansuran/tenures/' + editingId.value, {
            preserveScroll: true,
            onSuccess: () => dialogOpen.value = false,
        });
    } else {
        form.post('/admin/ansuran/tenures', {
            preserveScroll: true,
            onSuccess: () => dialogOpen.value = false,
        });
    }
};

const toggle = (id) => {
    router.post('/admin/ansuran/tenures/' + id + '/toggle', {}, { preserveScroll: true });
};

const deleteTenure = (id) => {
    router.delete('/admin/ansuran/tenures/' + id, { preserveScroll: true });
};
</script>

<template>
    <AdminLayout>
        <Head title="Tempoh Ansuran" />
        <PageHeader title="Tempoh Ansuran" description="Urus pilihan tempoh dan kadar keuntungan ansuran">
            <template #actions>
                <Button @click="openAdd"><Plus class="w-4 h-4 mr-1" /> Tambah Tempoh</Button>
            </template>
        </PageHeader>

        <div class="max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-200">
                <div v-for="t in tenures" :key="t.id" class="flex items-center justify-between px-6 py-4">
                    <div>
                        <div class="font-medium text-slate-900">{{ t.formatted_label }}</div>
                        <div class="text-sm text-slate-500">Kadar Keuntungan: {{ Number(t.interest_rate_percent).toFixed(2) }}%</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="t.is_active ? 'active' : 'inactive'" />
                        <Button variant="outline" @click="toggle(t.id)">
                            {{ t.is_active ? 'Nyahaktif' : 'Aktifkan' }}
                        </Button>
                        <Button variant="ghost" @click="openEdit(t)">
                            <Pencil class="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" @click="deleteTenure(t.id)">
                            <Trash2 class="w-4 h-4 text-red-500" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Modal -->
        <div v-if="dialogOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">{{ editingId ? 'Edit Tempoh' : 'Tambah Tempoh' }}</h3>
                <form @submit.prevent="save" class="mt-4 space-y-4">
                    <TextInput id="months" v-model.number="form.months" label="Bilangan Bulan" type="number" :error="form.errors.months" required />
                    <TextInput id="interest_rate" v-model.number="form.interest_rate_percent" label="Kadar Keuntungan (%)" type="number" :error="form.errors.interest_rate_percent" />
                    <TextInput id="label" v-model="form.label" label="Label (pilihan)" placeholder="Cth: 12 Bulan" :error="form.errors.label" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="dialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">{{ editingId ? 'Kemaskini' : 'Simpan' }}</Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
