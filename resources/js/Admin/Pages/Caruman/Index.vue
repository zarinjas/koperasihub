<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Pencil, Banknote, Plus, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    contributions: { type: Object, required: true },
    members: { type: Array, default: () => [] },
    years: { type: Array, required: true },
    selectedYear: { type: Number, required: true },
    search: { type: String, default: '' },
});

const filters = reactive({
    search: props.search || '',
    year: String(props.selectedYear),
});

const yearOptions = computed(() =>
    props.years.map((y) => ({ value: String(y), label: String(y) }))
);

const applyFilters = () => {
    router.get('/admin/caruman', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.year = String(new Date().getFullYear());
    applyFilters();
};

const editingId = ref(null);
const editForm = reactive({
    caruman_semasa: '',
    caruman_keseluruhan: '',
    dividen: '',
});

const startEdit = (row) => {
    editingId.value = row.id;
    editForm.caruman_semasa = row.caruman_semasa !== null ? String(row.caruman_semasa) : '0';
    editForm.caruman_keseluruhan = row.caruman_keseluruhan !== null ? String(row.caruman_keseluruhan) : '0';
    editForm.dividen = row.dividen !== null ? String(row.dividen) : '0';
};

const cancelEdit = () => {
    editingId.value = null;
};

const saveEdit = (row) => {
    router.put(`/admin/caruman/${row.id}`, {
        caruman_semasa: parseFloat(editForm.caruman_semasa) || 0,
        caruman_keseluruhan: parseFloat(editForm.caruman_keseluruhan) || 0,
        dividen: parseFloat(editForm.dividen) || 0,
    }, {
        onFinish: () => {
            editingId.value = null;
        },
    });
};

const showAddForm = ref(false);

const addForm = reactive({
    member_id: '',
    caruman_semasa: '',
    caruman_keseluruhan: '',
    dividen: '',
});

const openAdd = () => {
    if (!props.members.length) return;
    addForm.member_id = props.members[0]?.id || '';
    addForm.caruman_semasa = '';
    addForm.caruman_keseluruhan = '';
    addForm.dividen = '';
    showAddForm.value = true;
};

const closeAdd = () => {
    showAddForm.value = false;
};

const submitAdd = () => {
    if (!addForm.member_id) return;

    router.post('/admin/caruman', {
        member_id: addForm.member_id,
        year: filters.year,
        caruman_semasa: parseFloat(addForm.caruman_semasa) || 0,
        caruman_keseluruhan: parseFloat(addForm.caruman_keseluruhan) || 0,
        dividen: parseFloat(addForm.dividen) || 0,
    }, {
        onFinish: () => {
            showAddForm.value = false;
        },
    });
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return 'RM ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const columns = [
    { key: 'member_no', label: 'No. Ahli' },
    { key: 'member_name', label: 'Nama Ahli' },
    { key: 'caruman_semasa', label: 'Caruman Semasa' },
    { key: 'caruman_keseluruhan', label: 'Caruman Keseluruhan' },
    { key: 'dividen', label: 'Dividen' },
    { key: 'actions', label: 'Tindakan' },
];
</script>

<template>
    <Head title="Caruman Ahli" />

    <AdminLayout>
        <div class="space-y-6">
            <PageHeader
                title="Caruman Ahli"
                description="Urus data caruman dan dividen untuk setiap ahli koperasi."
            >
                <template #actions>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <Banknote class="h-5 w-5" />
                    </span>
                    <Button @click="openAdd" size="sm">
                        <Plus class="mr-1.5 h-4 w-4" />
                        Tambah Baru
                    </Button>
                </template>
            </PageHeader>

            <div v-if="showAddForm" class="rounded-3xl border border-teal-200 bg-gradient-to-br from-teal-50 to-cyan-50 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-base font-semibold text-slate-950">Tambah Caruman Baru</h3>
                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="closeAdd">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Ahli</label>
                        <select
                            v-model="addForm.member_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        >
                            <option value="" disabled>Pilih ahli...</option>
                            <option
                                v-for="m in members"
                                :key="m.id"
                                :value="m.id"
                            >{{ m.member_no }} — {{ m.full_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Caruman Setakat Ini (RM)</label>
                        <input
                            v-model="addForm.caruman_semasa"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Caruman Keseluruhan (RM)</label>
                        <input
                            v-model="addForm.caruman_keseluruhan"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Dividen (RM)</label>
                        <input
                            v-model="addForm.dividen"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                </div>
                <div class="mt-5 flex items-center gap-3">
                    <Button @click="submitAdd">
                        <Check class="mr-1.5 h-4 w-4" />
                        Simpan
                    </Button>
                    <Button variant="outline" @click="closeAdd">Batal</Button>
                </div>
            </div>

            <AdminFilterBar
                :search.sync="filters.search"
                :search-placeholder="'Cari ahli...'"
                @apply="applyFilters"
                @reset="resetFilters"
            >
                <template #filters>
                    <AdminSelectFilter
                        v-model="filters.year"
                        label="Tahun"
                        :options="yearOptions"
                    />
                </template>
            </AdminFilterBar>

            <DataTable
                v-if="contributions.data?.length"
                :columns="columns"
                :rows="contributions.data"
            >
                <template #cell-caruman_semasa="{ row }">
                    <template v-if="editingId === row.id">
                        <input
                            v-model="editForm.caruman_semasa"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full sm:w-36 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </template>
                    <span v-else class="text-sm font-medium tabular-nums">{{ formatCurrency(row.caruman_semasa) }}</span>
                </template>

                <template #cell-caruman_keseluruhan="{ row }">
                    <template v-if="editingId === row.id">
                        <input
                            v-model="editForm.caruman_keseluruhan"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full sm:w-36 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </template>
                    <span v-else class="text-sm font-medium tabular-nums">{{ formatCurrency(row.caruman_keseluruhan) }}</span>
                </template>

                <template #cell-dividen="{ row }">
                    <template v-if="editingId === row.id">
                        <input
                            v-model="editForm.dividen"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full sm:w-36 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </template>
                    <span v-else class="text-sm font-medium tabular-nums">{{ formatCurrency(row.dividen) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <template v-if="editingId === row.id">
                        <div class="flex items-center gap-1.5">
                            <Button size="sm" variant="ghost" @click="saveEdit(row)" title="Simpan">
                                <Check class="h-4 w-4 text-emerald-600" />
                            </Button>
                            <Button size="sm" variant="ghost" @click="cancelEdit()" title="Batal">
                                <X class="h-4 w-4 text-slate-500" />
                            </Button>
                        </div>
                    </template>
                    <Button v-else size="sm" variant="outline" @click="startEdit(row)">
                        <Pencil class="mr-1.5 h-3.5 w-3.5" />
                        Edit
                    </Button>
                </template>
            </DataTable>

            <div v-if="contributions.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
                <Button
                    v-for="link in contributions.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>

            <EmptyState
                v-else
                :title="`Tiada data caruman untuk tahun ${selectedYear}.`"
                description="Klik butang Tambah Baru untuk mengisi caruman secara manual, atau muat naik fail CSV untuk kemasukan pukal."
            />
        </div>
    </AdminLayout>
</template>