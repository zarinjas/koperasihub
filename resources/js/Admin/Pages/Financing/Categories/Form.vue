<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    category: { type: Object, default: null },
});

const form = useForm({
    name: props.category?.name || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
});

const submit = () => {
    form.post(`/admin/financing/categories/${props.category.id}`, {
        preserveScroll: true,
        _method: 'patch',
    });
};
</script>

<template>
    <Head title="Edit Kategori Pembiayaan" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Edit Kategori Pembiayaan"
                description="Kemas kini nama paparan, penerangan, dan status kategori pembiayaan sistem."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/financing/categories" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                    Jenis kategori dan rujukan produk dikekalkan oleh sistem. Perubahan di sini hanya melibatkan nama paparan, penerangan, dan status.
                </div>

                <FormSection title="Maklumat Kategori" description="Maklumat asas kategori pembiayaan." :columns="1">
                    <TextInput id="name" v-model="form.name" label="Nama" :error="form.errors.name" />
                    <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="form.errors.description" />
                </FormSection>

                <FormSection title="Status" description="Tentukan sama ada kategori ini tersedia kepada ahli." :columns="1">
                    <ToggleSwitch id="is-active" v-model="form.is_active" label="Kategori aktif" description="Kategori aktif akan dipaparkan kepada ahli di portal pembiayaan." />
                </FormSection>

                <FormActions
                    submit-label="Kemas Kini Kategori"
                    :submitting="form.processing"
                    cancel-label="Kembali"
                    @cancel="router.visit('/admin/financing/categories')"
                />
            </form>
        </section>
    </AdminLayout>
</template>
