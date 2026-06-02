<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    staff: { type: Object, default: null },
    unitOptions: { type: Array, required: true },
});

const page = usePage();
const isEdit = computed(() => Boolean(props.staff));

const form = useForm({
    name: props.staff?.name || '',
    email: props.staff?.email || '',
    staff_id: props.staff?.staff_id || '',
    unit_id: props.staff?.unit_id || '',
    position_title: props.staff?.position_title || '',
    role: props.staff?.role || 'admin',
    status: props.staff?.status || 'active',
    phone: props.staff?.phone || '',
    password: '',
});

const roleOptions = [
    { value: 'super_admin', label: 'Super Admin' },
    { value: 'admin', label: 'Admin' },
];

const statusOptions = [
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Tidak Aktif' },
];

const unitWithEmpty = computed(() => [
    { value: '', label: 'Tiada Unit' },
    ...props.unitOptions,
]);

const submit = () => {
    const onSuccess = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    const onError = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    if (isEdit.value) {
        form.patch(`/admin/staff/${props.staff.id}`, { onSuccess, onError });
    } else {
        form.post('/admin/staff', { onSuccess, onError });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Staff' : 'Tambah Staff'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader :title="isEdit ? 'Edit Staff' : 'Tambah Staff'" :description="isEdit ? 'Kemas kini maklumat akaun staff.' : 'Daftar akaun staf pentadbir baharu.'">
                <template #actions>
                    <Button :as="Link" href="/admin/staff" variant="outline">Kembali</Button>
                </template>
            </PageHeader>

            <FormSection title="Maklumat Akaun" :columns="2">
                <TextInput id="staff-name" v-model="form.name" label="Nama penuh" :error="form.errors.name" />
                <TextInput id="staff-email" v-model="form.email" label="Emel" :error="form.errors.email" />
                <TextInput id="staff-id" v-model="form.staff_id" label="ID Staff" :error="form.errors.staff_id" />
                <SelectInput id="staff-role" v-model="form.role" label="Peranan" :options="roleOptions" :error="form.errors.role" />
                <SelectInput id="staff-unit" v-model="form.unit_id" label="Unit" :options="unitWithEmpty" :error="form.errors.unit_id" />
                <TextInput id="staff-position" v-model="form.position_title" label="Jawatan" :error="form.errors.position_title" />

                <TextInput v-if="isEdit" id="staff-phone" v-model="form.phone" label="Telefon" :error="form.errors.phone" />
                <SelectInput v-if="isEdit" id="staff-status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />

                <div class="md:col-span-2">
                    <TextInput id="staff-password" v-model="form.password" type="password" :label="isEdit ? 'Kata laluan baharu (kosongkan jika tidak mahu tukar)' : 'Kata laluan'" :error="form.errors.password" />
                </div>
            </FormSection>

            <div class="flex justify-end gap-3">
                <Button :as="Link" href="/admin/staff" variant="outline">Batal</Button>
                <Button type="button" @click="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Kemas Kini' : 'Simpan') }}
                </Button>
            </div>
        </section>
    </AdminLayout>
</template>