<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    mode: { type: String, required: true },
    member: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
    genderOptions: { type: Array, required: true },
    userOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    user_id: props.member?.user_id || '',
    member_no: props.member?.member_no || '',
    full_name: props.member?.full_name || '',
    identity_no: props.member?.identity_no || '',
    email: props.member?.email || '',
    phone: props.member?.phone || '',
    address: props.member?.address || '',
    date_of_birth: props.member?.date_of_birth || '',
    gender: props.member?.gender || '',
    occupation: props.member?.occupation || '',
    employer_name: props.member?.employer_name || '',
    membership_status: props.member?.membership_status || 'active',
    joined_at: props.member?.joined_at || '',
    notes: props.member?.notes || '',
});

const submit = () => {
    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'patch',
        })).post(`/admin/members/${props.member.id}`, {
            preserveScroll: true,
        });

        return;
    }

    form.post('/admin/members', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get(isEdit.value ? `/admin/members/${props.member.id}` : '/admin/members');
};
</script>

<template>
    <Head :title="isEdit ? `Edit ${member.full_name}` : 'Cipta Ahli'" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <PageHeader
                :title="isEdit ? 'Edit Ahli' : 'Cipta Ahli'"
                :description="isEdit ? 'Kemas kini profil ahli, butiran hubungan, dan pautan akaun pengguna.' : 'Cipta rekod ahli baharu untuk kemasukan manual oleh admin.'"
            >
                <template #actions>
                    <StatusBadge v-if="member" :status="member.membership_status" />
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FormSection title="Maklumat Asas" description="Simpan maklumat pengenalan dan status utama ahli." :columns="2">
                <TextInput
                    id="member-member-no"
                    v-model="form.member_no"
                    label="No. ahli"
                    :error="form.errors.member_no"
                    :disabled="!isEdit"
                />
                <TextInput id="member-full-name" v-model="form.full_name" label="Nama penuh" :error="form.errors.full_name" />
                <TextInput id="member-identity-no" v-model="form.identity_no" label="No. kad pengenalan" :error="form.errors.identity_no" />
                <TextInput id="member-email" v-model="form.email" label="E-mel" type="email" :error="form.errors.email" />
                <TextInput id="member-phone" v-model="form.phone" label="No. telefon" :error="form.errors.phone" />
                <TextInput id="member-date-of-birth" v-model="form.date_of_birth" label="Tarikh lahir" type="date" :error="form.errors.date_of_birth" />
                <SelectInput id="member-gender" v-model="form.gender" label="Jantina" :options="genderOptions" :error="form.errors.gender" />
                <SelectInput id="member-status" v-model="form.membership_status" label="Status ahli" :options="statusOptions" :error="form.errors.membership_status" />
                <TextInput id="member-joined-at" v-model="form.joined_at" label="Tarikh sertai" type="date" :error="form.errors.joined_at" />
                <div class="md:col-span-2">
                    <TextareaInput id="member-address" v-model="form.address" label="Alamat" :error="form.errors.address" :rows="4" />
                </div>
            </FormSection>

            <FormSection title="Profil Pekerjaan" description="Maklumat tambahan untuk rujukan pengurusan ahli." :columns="2">
                <TextInput id="member-occupation" v-model="form.occupation" label="Pekerjaan" :error="form.errors.occupation" />
                <TextInput id="member-employer" v-model="form.employer_name" label="Nama majikan" :error="form.errors.employer_name" />
            </FormSection>

            <FormSection title="Akaun Pengguna" description="Pautkan rekod ahli kepada akaun pengguna jika ahli sudah mempunyai akses log masuk." :columns="1">
                <SelectInput id="member-user" v-model="form.user_id" label="Akaun pengguna" :options="userOptions" :error="form.errors.user_id" />
            </FormSection>

            <FormSection title="Catatan" description="Catatan dalaman untuk rujukan admin sahaja." :columns="1">
                <TextareaInput id="member-notes" v-model="form.notes" label="Catatan admin" :error="form.errors.notes" :rows="4" />
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Simpan Perubahan' : 'Cipta Ahli'" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>
