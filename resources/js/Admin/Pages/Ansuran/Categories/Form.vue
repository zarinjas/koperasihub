<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';

const props = defineProps({
    category: { type: Object, default: null },
});

const isEdit = !!props.category;

const form = useForm({
    name: props.category?.name || '',
    description: props.category?.description || '',
    image: null,
    is_active: props.category?.is_active ?? true,
});

const imagePreview = ref(props.category?.image_url || null);

watch(() => form.image, (file) => {
    if (file) imagePreview.value = URL.createObjectURL(file);
});

const submit = () => {
    const onSuccess = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    const onError = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    if (isEdit) {
        form.put('/admin/ansuran/categories/' + props.category.id, { onSuccess, onError });
    } else {
        form.post('/admin/ansuran/categories', { onSuccess, onError });
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="isEdit ? 'Edit Kategori' : 'Tambah Kategori'" />
        <PageHeader :title="isEdit ? 'Edit Kategori' : 'Tambah Kategori'" :description="isEdit ? 'Kemaskini maklumat kategori' : 'Tambah kategori produk baru'" />

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <FormSection title="Maklumat Kategori">
                <TextInput id="name" v-model="form.name" label="Nama Kategori" :error="form.errors.name" required />
                <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="form.errors.description" :rows="3" />

                <div>
                    <FileUploader id="image" v-model="form.image" label="Gambar Kategori" accept="image/*" helper-text="Pilih gambar untuk kategori" :existing-file="category?.image_url ? { name: 'Gambar semasa' } : null" :error="form.errors.image" />
                    <img v-if="imagePreview" :src="imagePreview" class="mt-3 w-24 h-24 rounded-lg object-cover" />
                </div>

                <ToggleSwitch id="is_active" v-model="form.is_active" label="Aktif" />
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Kemaskini' : 'Simpan'" cancel-label="Batal" :submitting="form.processing" @cancel="window.history.back()" />
        </form>
    </AdminLayout>
</template>