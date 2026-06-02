<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    category: { type: Object, default: null },
    types: { type: Array, required: true },
});

const isEdit = computed(() => Boolean(props.category));

const form = useForm({
    name: props.category?.name || '',
    type: props.category?.type || (props.types[0]?.value ?? ''),
    icon: props.category?.icon || '',
    description: props.category?.description || '',
});

const submit = () => {
    const onSuccess = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    const onError = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    if (isEdit.value) {
        form.put(`/admin/financing/categories/${props.category.id}`, { onSuccess, onError });
    } else {
        form.post('/admin/financing/categories', { onSuccess, onError });
    }
};

const cancel = () => {
    router.get('/admin/financing/categories');
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Kategori' : 'Kategori Baharu'" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <PageHeader
                :title="isEdit ? 'Edit Kategori' : 'Kategori Baharu'"
                :description="isEdit ? 'Kemas kini maklumat kategori pembiayaan.' : 'Daftar kategori baharu untuk produk pembiayaan.'"
            >
                <template #actions>
                    <Button type="button" variant="outline" @click="cancel">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <FormSection title="Maklumat Kategori" description="Isikan maklumat asas kategori pembiayaan." :columns="2">
                <TextInput
                    id="category-name"
                    v-model="form.name"
                    label="Nama"
                    :error="form.errors.name"
                />
                <SelectInput
                    id="category-type"
                    v-model="form.type"
                    label="Jenis"
                    :options="types"
                    :error="form.errors.type"
                />
                <TextInput
                    id="category-icon"
                    v-model="form.icon"
                    label="Ikon"
                    placeholder="cth: HandCoins"
                    :error="form.errors.icon"
                />
                <div class="md:col-span-2">
                    <TextareaInput
                        id="category-description"
                        v-model="form.description"
                        label="Deskripsi"
                        :error="form.errors.description"
                    />
                </div>
            </FormSection>

            <FormActions
                :submit-label="isEdit ? 'Simpan Kategori' : 'Cipta Kategori'"
                :submitting="form.processing"
                @cancel="cancel"
            />
        </form>
    </AdminLayout>
</template>