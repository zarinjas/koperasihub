<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ImagePlus, Pencil, Plus, Star, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, required: true },
});

const isEdit = !!props.product;

const categoryOptions = computed(() => [
    { value: '', label: 'Pilih kategori' },
    ...props.categories.map((cat) => ({ value: String(cat.id), label: cat.name })),
]);

const statusOptions = [
    { value: 'draf', label: 'Draf' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'tidak_aktif', label: 'Tidak Aktif' },
];

const form = useForm({
    ansuran_category_id: props.product?.ansuran_category_id || '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    min_down_payment_percent: props.product?.min_down_payment_percent ?? 0,
    guarantor_count: props.product?.guarantor_count ?? 0,
    status: props.product?.status || 'aktif',
});

const images = ref(props.product?.images || []);
const uploadLoading = ref(false);

const uploadImages = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.accept = 'image/*';
    input.onchange = async (e) => {
        const files = e.target.files;
        if (!files.length) return;
        const fd = new FormData();
        for (const f of files) fd.append('images[]', f);
        uploadLoading.value = true;
        try {
            await router.post('/admin/ansuran/products/' + props.product.id + '/images', fd, { preserveScroll: true });
            router.reload({ only: ['product'] });
        } finally {
            uploadLoading.value = false;
        }
    };
    input.click();
};

const deleteImage = (imageId) => {
    router.post('/admin/ansuran/products/' + props.product.id + '/images/' + imageId, { _method: 'DELETE' }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['product'] }),
    });
};

const setPrimary = (imageId) => {
    router.post('/admin/ansuran/products/' + props.product.id + '/images/' + imageId + '/primary', {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['product'] }),
    });
};

const variantForm = useForm({
    name: '',
    sku: '',
    price: 0,
    stock: null,
    attributes: {},
    is_active: true,
});

const editingVariant = ref(null);
const variantDialogOpen = ref(false);
const attrKey = ref('');
const attrValue = ref('');

const addAttr = () => {
    if (attrKey.value && attrValue.value) {
        variantForm.attributes[attrKey.value] = attrValue.value;
        attrKey.value = '';
        attrValue.value = '';
    }
};

const removeAttr = (key) => {
    delete variantForm.attributes[key];
};

const openAddVariant = () => {
    editingVariant.value = null;
    variantForm.reset();
    variantForm.attributes = {};
    variantForm.price = 0;
    variantForm.is_active = true;
    variantDialogOpen.value = true;
};

const openEditVariant = (variant) => {
    editingVariant.value = variant.id;
    variantForm.name = variant.name;
    variantForm.sku = variant.sku || '';
    variantForm.price = variant.price;
    variantForm.stock = variant.stock;
    variantForm.attributes = { ...(variant.attributes || {}) };
    variantForm.is_active = variant.is_active;
    variantDialogOpen.value = true;
};

const saveVariant = () => {
    const data = {
        name: variantForm.name,
        sku: variantForm.sku,
        price: parseFloat(variantForm.price),
        stock: variantForm.stock ? parseInt(variantForm.stock) : null,
        attributes: variantForm.attributes,
        is_active: variantForm.is_active,
    };

    if (editingVariant.value) {
        router.put('/admin/ansuran/products/' + props.product.id + '/variants/' + editingVariant.value, data, {
            preserveScroll: true,
            onSuccess: () => { variantDialogOpen.value = false; router.reload({ only: ['product'] }); },
        });
    } else {
        router.post('/admin/ansuran/products/' + props.product.id + '/variants', data, {
            preserveScroll: true,
            onSuccess: () => { variantDialogOpen.value = false; router.reload({ only: ['product'] }); },
        });
    }
};

const deleteVariant = (variantId) => {
    router.post('/admin/ansuran/products/' + props.product.id + '/variants/' + variantId, { _method: 'DELETE' }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['product'] }),
    });
};

const submit = () => {
    const onSuccess = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    const onError = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    if (isEdit) {
        form.put('/admin/ansuran/products/' + props.product.id, { onSuccess, onError });
    } else {
        form.post('/admin/ansuran/products', { onSuccess, onError });
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="isEdit ? 'Edit Produk' : 'Tambah Produk'" />
        <PageHeader :title="isEdit ? 'Edit Produk' : 'Tambah Produk'" :description="isEdit ? 'Kemaskini maklumat produk' : 'Tambah produk ansuran mudah baru'" />

        <div class="max-w-3xl space-y-6">
            <!-- Product Info Section -->
            <form @submit.prevent="submit" class="space-y-6">
                <FormSection title="Maklumat Produk" :columns="2">
                    <SelectInput
                        id="ansuran_category_id"
                        v-model="form.ansuran_category_id"
                        label="Kategori"
                        :options="categoryOptions"
                        :error="form.errors.ansuran_category_id"
                    />
                    <TextInput id="name" v-model="form.name" label="Nama Produk" :error="form.errors.name" required />
                    <TextareaInput id="description" v-model="form.description" label="Penerangan" :rows="5" :error="form.errors.description" />
                    <TextInput id="min_down_payment_percent" v-model.number="form.min_down_payment_percent" label="Down Payment Minimum (%)" type="number" :error="form.errors.min_down_payment_percent" />
                    <TextInput id="guarantor_count" v-model.number="form.guarantor_count" label="Bilangan Penjamin" type="number" help="0, 1, atau 2" :error="form.errors.guarantor_count" />
                    <SelectInput id="status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                </FormSection>

                <FormActions :submit-label="isEdit ? 'Kemaskini' : 'Simpan'" cancel-label="Batal" :submitting="form.processing" @cancel="window.history.back()" />
            </form>

            <!-- Image Gallery (edit only) -->
            <div v-if="isEdit" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950 mb-4">Galeri Gambar</h2>
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-500">Urus gambar produk</p>
                    <Button variant="outline" :disabled="uploadLoading" @click="uploadImages">
                        <ImagePlus class="w-4 h-4 mr-1" /> {{ uploadLoading ? 'Memuat Naik...' : 'Tambah Gambar' }}
                    </Button>
                </div>
                <div v-if="product?.images?.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="img in product.images" :key="img.id" class="relative group rounded-lg border overflow-hidden">
                        <img :src="img.url" class="w-full h-32 object-cover" />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center gap-2">
                            <Button variant="ghost" class="text-white hidden group-hover:flex" @click="setPrimary(img.id)" :title="img.is_primary ? 'Gambar Utama' : 'Jadikan Utama'">
                                <Star class="w-4 h-4" :class="{ 'fill-yellow-400 text-yellow-400': img.is_primary }" />
                            </Button>
                            <Button variant="ghost" class="text-red-400 hidden group-hover:flex" @click="deleteImage(img.id)">
                                <Trash2 class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-slate-400 py-8">Tiada gambar. Klik "Tambah Gambar" untuk muat naik.</div>
            </div>

            <!-- Variant Management (edit only) -->
            <div v-if="isEdit" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950 mb-4">Varian Produk</h2>
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-500">Urus varian produk</p>
                    <Button variant="outline" @click="openAddVariant">
                        <Plus class="w-4 h-4 mr-1" /> Tambah Varian
                    </Button>
                </div>
                <div v-if="product?.variants?.length > 0" class="space-y-3">
                    <div v-for="variant in product.variants" :key="variant.id" class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <div class="font-medium text-slate-900">{{ variant.name }}</div>
                            <div class="text-sm text-slate-500">RM {{ Number(variant.price).toFixed(2) }} <span v-if="variant.sku">&middot; SKU: {{ variant.sku }}</span></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <StatusBadge :status="variant.is_active ? 'active' : 'inactive'" />
                            <Button variant="ghost" @click="openEditVariant(variant)"><Pencil class="w-4 h-4" /></Button>
                            <Button variant="ghost" @click="deleteVariant(variant.id)"><Trash2 class="w-4 h-4 text-red-500" /></Button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-slate-400 py-8">Tiada varian. Tambah varian untuk produk ini.</div>
            </div>
        </div>

        <!-- Variant Modal -->
        <div v-if="variantDialogOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-slate-950">{{ editingVariant ? 'Edit Varian' : 'Tambah Varian' }}</h3>
                <form @submit.prevent="saveVariant" class="mt-4 space-y-4">
                    <TextInput id="variant_name" v-model="variantForm.name" label="Nama Varian" placeholder="Cth: 65 inci" :error="variantForm.errors.name" />
                    <TextInput id="variant_sku" v-model="variantForm.sku" label="SKU" placeholder="SKU-001" :error="variantForm.errors.sku" />
                    <div class="grid grid-cols-2 gap-4">
                        <TextInput id="variant_price" v-model.number="variantForm.price" label="Harga (RM)" type="number" :error="variantForm.errors.price" />
                        <TextInput id="variant_stock" v-model.number="variantForm.stock" label="Stok" type="number" help="Kosongkan jika tiada had" :error="variantForm.errors.stock" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-800 block mb-2">Atribut Tambahan</label>
                        <div class="flex gap-2 mb-2 flex-wrap">
                            <span v-for="(val, key) in variantForm.attributes" :key="key" class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded text-sm">
                                {{ key }}: {{ val }}
                                <button @click="removeAttr(key)" class="text-slate-400 hover:text-red-500"><X class="w-3 h-3" /></button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="attrKey" placeholder="Key" class="h-11 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                            <input v-model="attrValue" placeholder="Value" class="h-11 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
                            <Button type="button" variant="outline" @click="addAttr">Tambah</Button>
                        </div>
                    </div>
                    <ToggleSwitch id="variant_active" v-model="variantForm.is_active" label="Aktif" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="variantDialogOpen = false">Batal</Button>
                        <Button type="submit">{{ editingVariant ? 'Kemaskini' : 'Simpan' }}</Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>