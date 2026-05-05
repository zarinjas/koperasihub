<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
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
    product: { type: Object, default: null },
    categoryOptions: { type: Array, required: true },
    unitOptions: { type: Array, required: true },
});

const form = useForm({
    financing_category_id: props.product?.financing_category_id || props.categoryOptions[0]?.value || '',
    unit_id: props.product?.unit_id || '',
    name: props.product?.name || '',
    slug: props.product?.slug || '',
    description: props.product?.description || '',
    min_amount: props.product?.min_amount || '',
    max_amount: props.product?.max_amount || '',
    min_tenure_months: props.product?.min_tenure_months || '',
    max_tenure_months: props.product?.max_tenure_months || '',
    requires_guarantor: props.product?.requires_guarantor ?? false,
    guarantor_count: props.product?.guarantor_count ?? 0,
    required_documents_text: props.product?.required_documents_text || '',
    is_active: props.product?.is_active ?? true,
    sort_order: props.product?.sort_order ?? 0,
});

const submit = () => {
    const url = props.mode === 'create'
        ? '/admin/financing/products'
        : `/admin/financing/products/${props.product.id}`;

    if (props.mode === 'create') {
        form.post(url, { preserveScroll: true });
        return;
    }

    form.patch(url, { preserveScroll: true });
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Produk Pembiayaan' : 'Edit Produk Pembiayaan'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Tambah Produk Pembiayaan' : 'Edit Produk Pembiayaan'"
                description="Tetapkan maklumat produk, julat amaun, tempoh, dan keperluan penjamin."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/products" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <FormSection title="Maklumat Produk" description="Maklumat asas produk pembiayaan." :columns="2">
                    <SelectInput id="category" v-model="form.financing_category_id" label="Kategori Pembiayaan" :options="categoryOptions" :error="form.errors.financing_category_id" />
                    <SelectInput id="unit" v-model="form.unit_id" label="Unit Pengurusan" :options="unitOptions" :error="form.errors.unit_id" />
                    <TextInput id="name" v-model="form.name" label="Nama Produk" :error="form.errors.name" />
                    <TextInput id="slug" v-model="form.slug" label="Slug" :error="form.errors.slug" />
                    <div class="md:col-span-2">
                        <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="form.errors.description" />
                    </div>
                </FormSection>

                <FormSection title="Had Pembiayaan" description="Julat amaun dan tempoh yang dibenarkan." :columns="2">
                    <TextInput id="min-amount" v-model="form.min_amount" label="Amaun Minimum (RM)" type="number" :error="form.errors.min_amount" />
                    <TextInput id="max-amount" v-model="form.max_amount" label="Amaun Maksimum (RM)" type="number" :error="form.errors.max_amount" />
                    <TextInput id="min-tenure" v-model="form.min_tenure_months" label="Tempoh Minimum (Bulan)" type="number" :error="form.errors.min_tenure_months" />
                    <TextInput id="max-tenure" v-model="form.max_tenure_months" label="Tempoh Maksimum (Bulan)" type="number" :error="form.errors.max_tenure_months" />
                </FormSection>

                <FormSection title="Penjamin & Dokumen" description="Tetapan keperluan penjamin dan senarai dokumen." :columns="1">
                    <ToggleSwitch id="requires-guarantor" v-model="form.requires_guarantor" label="Produk ini memerlukan penjamin" description="Jika aktif, ahli perlu memilih penjamin aktif yang mempunyai log masuk." />
                    <TextInput id="guarantor-count" v-model="form.guarantor_count" label="Bilangan Penjamin" type="number" :error="form.errors.guarantor_count" />
                    <TextareaInput id="required-documents" v-model="form.required_documents_text" label="Dokumen Diperlukan" help="Masukkan satu dokumen bagi setiap baris." :error="form.errors.required_documents_text" />
                </FormSection>

                <FormSection title="Status" description="Tentukan sama ada produk ini tersedia kepada ahli." :columns="1">
                    <ToggleSwitch id="is-active" v-model="form.is_active" label="Produk aktif" description="Produk aktif akan dipaparkan di portal ahli." />
                    <TextInput id="sort-order" v-model="form.sort_order" label="Susunan" type="number" :error="form.errors.sort_order" />
                </FormSection>

                <FormActions
                    :submit-label="mode === 'create' ? 'Simpan Produk' : 'Kemas Kini Produk'"
                    :submitting="form.processing"
                    cancel-label="Kembali"
                    @cancel="router.visit('/admin/financing/products')"
                />
            </form>
        </section>
    </AdminLayout>
</template>
