<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
    accountRoleOptions: { type: Array, default: () => [] },
    canManageAccountRole: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');
const lastAutoFilledDateOfBirth = ref(props.member?.date_of_birth || '');

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
    employment_no: props.member?.employment_no || '',
    membership_status: props.member?.membership_status || 'active',
    joined_at: props.member?.joined_at || '',
    notes: props.member?.notes || '',
    password: '',
    password_confirmation: '',
    account_role: props.canManageAccountRole ? (props.member?.account_role || 'member') : '',
});

const parseDateOfBirthFromIdentityNo = (identityNo) => {
    const digits = (identityNo || '').replace(/\D/g, '');

    if (digits.length < 6) {
        return '';
    }

    const yy = Number.parseInt(digits.slice(0, 2), 10);
    const mm = Number.parseInt(digits.slice(2, 4), 10);
    const dd = Number.parseInt(digits.slice(4, 6), 10);

    if (!Number.isInteger(yy) || !Number.isInteger(mm) || !Number.isInteger(dd)) {
        return '';
    }

    const currentYear = new Date().getFullYear() % 100;
    const fullYear = yy <= currentYear ? 2000 + yy : 1900 + yy;
    const date = new Date(Date.UTC(fullYear, mm - 1, dd));

    if (
        Number.isNaN(date.getTime())
        || date.getUTCFullYear() !== fullYear
        || date.getUTCMonth() !== mm - 1
        || date.getUTCDate() !== dd
    ) {
        return '';
    }

    return `${fullYear}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
};

watch(
    () => form.identity_no,
    (identityNo) => {
        const parsedDate = parseDateOfBirthFromIdentityNo(identityNo);

        if (!parsedDate) {
            if (form.date_of_birth === lastAutoFilledDateOfBirth.value) {
                form.date_of_birth = '';
                lastAutoFilledDateOfBirth.value = '';
            }

            return;
        }

        if (!form.date_of_birth || form.date_of_birth === lastAutoFilledDateOfBirth.value) {
            form.date_of_birth = parsedDate;
            lastAutoFilledDateOfBirth.value = parsedDate;
        }
    }
);

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
                <div>
                    <TextInput id="member-date-of-birth" v-model="form.date_of_birth" label="Tarikh lahir" type="date" :error="form.errors.date_of_birth" />
                    <p class="mt-2 text-xs text-slate-500">Tarikh lahir akan diisi automatik jika nombor IC sah dimasukkan.</p>
                </div>
                <SelectInput id="member-gender" v-model="form.gender" label="Jantina" :options="genderOptions" :error="form.errors.gender" />
                <SelectInput id="member-status" v-model="form.membership_status" label="Status ahli" :options="statusOptions" :error="form.errors.membership_status" />
                <TextInput id="member-joined-at" v-model="form.joined_at" label="Tarikh sertai" type="date" :error="form.errors.joined_at" />
                <div class="md:col-span-2">
                    <TextareaInput id="member-address" v-model="form.address" label="Alamat" :error="form.errors.address" :rows="4" />
                </div>
            </FormSection>

            <FormSection title="Profil Pekerjaan" description="Maklumat tambahan untuk rujukan pengurusan ahli." :columns="2">
                <TextInput id="member-occupation" v-model="form.occupation" label="Pekerjaan" :error="form.errors.occupation" />
                <TextInput id="member-employer" v-model="form.employer_name" label="Jabatan" :error="form.errors.employer_name" />
                <TextInput id="member-employment-no" v-model="form.employment_no" label="No. pekerja" :error="form.errors.employment_no" />
            </FormSection>

            <FormSection title="Akaun Pengguna" description="Pautkan rekod ahli kepada akaun pengguna jika ahli sudah mempunyai akses log masuk." :columns="1">
                <SelectInput id="member-user" v-model="form.user_id" label="Akaun pengguna" :options="userOptions" :error="form.errors.user_id" />
            </FormSection>

            <FormSection title="Akses Portal Ahli" description="Isi kata laluan jika admin mahu cipta akses portal ahli atau tukar kata laluan mereka secara manual." :columns="2">
                <SelectInput
                    v-if="canManageAccountRole"
                    id="member-account-role"
                    v-model="form.account_role"
                    label="Jenis akaun"
                    :options="accountRoleOptions"
                    :error="form.errors.account_role"
                />
                <div v-else class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Jenis akaun portal ditetapkan sebagai ahli. Hanya super admin boleh menukarnya kepada admin.
                </div>
                <div>
                    <TextInput
                        id="member-password"
                        v-model="form.password"
                        label="Kata laluan baharu"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password"
                    />
                    <p class="mt-2 text-xs text-slate-500">
                        Jika tiada akaun dipautkan, sistem akan cipta akaun portal ahli menggunakan e-mel ahli ini.
                    </p>
                </div>
                <TextInput
                    id="member-password-confirmation"
                    v-model="form.password_confirmation"
                    label="Sahkan kata laluan"
                    type="password"
                    autocomplete="new-password"
                    :error="form.errors.password_confirmation"
                />
            </FormSection>

            <FormSection title="Catatan" description="Catatan dalaman untuk rujukan admin sahaja." :columns="1">
                <TextareaInput id="member-notes" v-model="form.notes" label="Catatan admin" :error="form.errors.notes" :rows="4" />
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Simpan Perubahan' : 'Cipta Ahli'" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>
