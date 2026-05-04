<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import ProfileAvatar from '@/Shared/Components/ProfileAvatar.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    member: { type: Object, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const form = useForm({
    phone: props.member.phone || '',
    address: props.member.address || '',
    occupation: props.member.occupation || '',
    employer_name: props.member.employer_name || '',
    profile_photo: null,
});

const submit = () => {
    form.patch('/member/profile', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const reset = () => {
    form.phone = props.member.phone || '';
    form.address = props.member.address || '';
    form.occupation = props.member.occupation || '';
    form.employer_name = props.member.employer_name || '';
    form.profile_photo = null;
    form.clearErrors();
};
</script>

<template>
    <Head title="Profil Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Profil Saya"
                description="Semak butiran keahlian anda dan kemas kini maklumat hubungan yang dibenarkan."
            >
                <template #actions>
                    <StatusBadge :status="member.membership_status" />
                </template>
            </PageHeader>

            <div v-if="!member.is_linked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod ahli anda belum dipautkan. Maklumat yang dipaparkan adalah berdasarkan akaun log masuk semasa.
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="space-y-6">
                    <FormSection title="Foto Profil" description="Gambar ini akan digunakan pada paparan portal ahli dan semakan admin." :columns="1">
                        <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-center sm:text-left">
                            <ProfileAvatar :photo-url="member.profile_photo_url" :name="member.full_name" size="xl" />
                            <div class="space-y-1">
                                <p class="text-base font-semibold text-slate-950">{{ member.full_name }}</p>
                                <p class="text-sm text-slate-600">
                                    Muat naik atau gantikan gambar profil anda untuk kegunaan portal ahli.
                                </p>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection title="Maklumat Keahlian" description="Maklumat asas ini direkodkan oleh pihak koperasi." :columns="2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. ahli</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.member_no }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status ahli</p>
                            <div class="mt-1">
                                <StatusBadge :status="member.membership_status" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama penuh</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. kad pengenalan</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.identity_no || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">E-mel</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.email || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh lahir</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.date_of_birth || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jantina</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.gender || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh sertai</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.joined_at || '-' }}</p>
                        </div>
                    </FormSection>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <FormSection title="Kemas Kini Maklumat" description="Anda boleh mengemas kini maklumat hubungan, pekerjaan, dan gambar profil di sini." :columns="1">
                        <FileUploader
                            id="profile_photo"
                            v-model="form.profile_photo"
                            label="Foto profil"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            helper-text="Saiz dicadangkan: 540px × 540px. Gunakan gambar wajah yang jelas."
                            :error="form.errors.profile_photo"
                        />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            label="No. telefon"
                            autocomplete="tel"
                            :error="form.errors.phone"
                        />
                        <TextareaInput
                            id="address"
                            v-model="form.address"
                            label="Alamat"
                            :rows="5"
                            :error="form.errors.address"
                        />
                        <TextInput
                            id="occupation"
                            v-model="form.occupation"
                            label="Pekerjaan"
                            :error="form.errors.occupation"
                        />
                        <TextInput
                            id="employer_name"
                            v-model="form.employer_name"
                            label="Nama majikan"
                            :error="form.errors.employer_name"
                        />
                    </FormSection>

                    <FormActions submit-label="Simpan Perubahan" :submitting="form.processing" @cancel="reset" />
                </form>
            </div>
        </section>
    </MemberLayout>
</template>
