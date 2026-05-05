<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    category: { type: Object, default: null },
    typeOptions: { type: Array, required: true },
});

const form = useForm({
    name: props.category?.name || '',
    slug: props.category?.slug || '',
    description: props.category?.description || '',
    type: props.category?.type || 'guaranteed',
    rate_image: null,
    remove_rate_image: false,
    is_active: props.category?.is_active ?? true,
    sort_order: props.category?.sort_order ?? 0,
});

const submit = () => {
    const url = props.mode === 'create'
        ? '/admin/financing/categories'
        : `/admin/financing/categories/${props.category.id}`;

    const options = {
        forceFormData: true,
        preserveScroll: true,
    };

    if (props.mode === 'create') {
        form.post(url, options);
        return;
    }

    form.post(url, {
        ...options,
        _method: 'patch',
    });
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Kategori Pembiayaan' : 'Edit Kategori Pembiayaan'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Tambah Kategori Pembiayaan' : 'Edit Kategori Pembiayaan'"
                description="Tetapkan jenis kategori, status aktif, dan imej jadual kadar untuk paparan ahli."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/categories" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <FormSection title="Maklumat Kategori" description="Maklumat asas kategori pembiayaan." :columns="2">
                    <TextInput id="name" v-model="form.name" label="Nama" :error="form.errors.name" />
                    <TextInput id="slug" v-model="form.slug" label="Slug" :error="form.errors.slug" />
                    <SelectInput id="type" v-model="form.type" label="Jenis" :options="typeOptions" :error="form.errors.type" />
                    <TextInput id="sort-order" v-model="form.sort_order" label="Susunan" type="number" :error="form.errors.sort_order" />
                    <div class="md:col-span-2">
                        <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="form.errors.description" />
                    </div>
                </FormSection>

                <FormSection title="Jadual Kadar Pembiayaan" description="Imej ini dipaparkan pada halaman kategori dan produk ahli." :columns="1">
                    <FileUploader
                        id="rate-image"
                        label="Jadual Kadar Pembiayaan"
                        accept=".jpg,.jpeg,.png,.webp"
                        helper-text="Muat naik jadual kadar pembiayaan dalam format JPG, PNG atau WEBP."
                        :error="form.errors.rate_image"
                        :model-value="form.rate_image"
                        :existing-file="category?.existing_rate_image_url ? { name: 'Imej sedia ada' } : null"
                        @update:model-value="form.rate_image = $event"
                    />
                    <div v-if="category?.existing_rate_image_url" class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <img :src="category.existing_rate_image_url" alt="Jadual kadar pembiayaan" class="max-h-80 w-full rounded-2xl object-contain" />
                    </div>
                    <ToggleSwitch id="remove-rate-image" v-model="form.remove_rate_image" label="Buang imej sedia ada" description="Aktifkan pilihan ini jika anda mahu membuang imej jadual kadar semasa." />
                </FormSection>

                <FormSection title="Status" description="Tentukan sama ada kategori ini tersedia kepada ahli." :columns="1">
                    <ToggleSwitch id="is-active" v-model="form.is_active" label="Kategori aktif" description="Kategori aktif akan dipaparkan kepada ahli di portal pembiayaan." />
                </FormSection>

                <FormActions
                    :submit-label="mode === 'create' ? 'Simpan Kategori' : 'Kemas Kini Kategori'"
                    :submitting="form.processing"
                    cancel-label="Kembali"
                    @cancel="router.visit('/admin/financing/categories')"
                />
            </form>
        </section>
    </AdminLayout>
</template>
