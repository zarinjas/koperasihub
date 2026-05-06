<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Pencil, ShieldCheck } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import ProfileAvatar from '@/Shared/Components/ProfileAvatar.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    member: { type: Object, required: true },
    editing: { type: Boolean, default: false },
});

const genderOptions = [
    { value: '', label: 'Pilih jantina' },
    { value: 'male', label: 'Lelaki' },
    { value: 'female', label: 'Perempuan' },
    { value: 'other', label: 'Lain-lain' },
];

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const localPhotoPreviewUrl = ref(null);
const isEditing = computed(() => props.editing);

const form = useForm({
    full_name: props.member.full_name || '',
    email: props.member.email || '',
    phone: props.member.phone || '',
    address: props.member.address || '',
    date_of_birth: props.member.date_of_birth_input || '',
    gender: props.member.gender_value || '',
    occupation: props.member.occupation || '',
    employer_name: props.member.employer_name || '',
    profile_photo: null,
});

const avatarPreviewUrl = computed(() => localPhotoPreviewUrl.value || props.member.profile_photo_url || null);

watch(() => form.profile_photo, (file) => {
    if (localPhotoPreviewUrl.value) {
        URL.revokeObjectURL(localPhotoPreviewUrl.value);
        localPhotoPreviewUrl.value = null;
    }

    if (file instanceof File) {
        localPhotoPreviewUrl.value = URL.createObjectURL(file);
    }
});

onBeforeUnmount(() => {
    if (localPhotoPreviewUrl.value) {
        URL.revokeObjectURL(localPhotoPreviewUrl.value);
    }
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post('/member/profile?edit=1', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.defaults({
                    full_name: form.full_name,
                    email: form.email,
                    phone: form.phone,
                    address: form.address,
                    date_of_birth: form.date_of_birth,
                    gender: form.gender,
                    occupation: form.occupation,
                    employer_name: form.employer_name,
                    profile_photo: null,
                });
                form.reset('profile_photo');
            },
        });
};

const cancel = () => {
    router.get('/member/profile', {}, { preserveScroll: true });
};

const reset = () => {
    form.full_name = props.member.full_name || '';
    form.email = props.member.email || '';
    form.phone = props.member.phone || '';
    form.address = props.member.address || '';
    form.date_of_birth = props.member.date_of_birth_input || '';
    form.gender = props.member.gender_value || '';
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
                description="Semak butiran keahlian anda dan kemas kini maklumat peribadi yang dibenarkan."
            >
                <template #actions>
                    <Button
                        v-if="!isEditing"
                        :as="Link"
                        href="/member/profile?edit=1"
                        variant="outline"
                    >
                        <Pencil class="mr-2 h-4 w-4" />
                        Kemaskini Profil
                    </Button>
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
                            <ProfileAvatar :photo-url="avatarPreviewUrl" :name="member.full_name" size="xl" />
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
                            <p class="mt-1 text-sm font-semibold text-slate-950">{{ member.member_no || '-' }}</p>
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
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. telefon</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.phone || '-' }}</p>
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
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pekerjaan</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.occupation || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama majikan</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.employer_name || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Alamat</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ member.address || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh sertai</p>
                            <p class="mt-1 text-sm text-slate-700">{{ member.joined_at || '-' }}</p>
                        </div>
                    </FormSection>

                    <FormSection title="Medan Dikawal Koperasi" description="Butiran ini tidak boleh dikemas kini sendiri oleh ahli." :columns="1">
                        <div class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <span class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-slate-700">
                                <ShieldCheck class="h-5 w-5" />
                            </span>
                            <div class="space-y-2 text-sm text-slate-600">
                                <p>No. ahli, status keahlian, tarikh sertai, tarikh kelulusan, pegawai pelulus, dan peranan akaun hanya boleh dikemas kini oleh admin.</p>
                            </div>
                        </div>
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <FormSection
                        v-if="isEditing"
                        title="Kemas Kini Profil"
                        description="Anda boleh mengemas kini maklumat peribadi dan gambar profil di sini."
                        :columns="1"
                    >
                        <form class="space-y-6" @submit.prevent="submit">
                            <TextInput
                                id="member-no-readonly"
                                :model-value="member.member_no || '-'"
                                label="No. ahli"
                                disabled
                            />
                            <FileUploader
                                id="profile_photo"
                                v-model="form.profile_photo"
                                label="Foto profil"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                helper-text="Saiz dicadangkan: 540px × 540px. Gunakan gambar wajah yang jelas."
                                :error="form.errors.profile_photo"
                            />
                            <TextInput
                                id="full_name"
                                v-model="form.full_name"
                                label="Nama penuh"
                                autocomplete="name"
                                :error="form.errors.full_name"
                            />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                label="E-mel"
                                type="email"
                                autocomplete="email"
                                :error="form.errors.email"
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
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                label="Tarikh lahir"
                                type="date"
                                :error="form.errors.date_of_birth"
                            />
                            <SelectInput
                                id="gender"
                                v-model="form.gender"
                                label="Jantina"
                                :options="genderOptions"
                                :error="form.errors.gender"
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

                            <FormActions submit-label="Simpan Perubahan" :submitting="form.processing" @cancel="cancel" />
                            <Button type="button" variant="ghost" @click="reset">
                                Set semula borang
                            </Button>
                        </form>
                    </FormSection>

                    <FormSection
                        v-else
                        title="Kemaskini Profil"
                        description="Gunakan tindakan ini untuk mengemas kini maklumat peribadi anda."
                        :columns="1"
                    >
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                            <p class="text-sm text-slate-600">
                                Anda boleh mengemas kini nama penuh, e-mel, nombor telefon, alamat, tarikh lahir, jantina, pekerjaan, nama majikan, dan foto profil.
                            </p>
                            <div class="mt-4">
                                <Button :as="Link" href="/member/profile?edit=1">
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Kemaskini Profil
                                </Button>
                            </div>
                        </div>
                    </FormSection>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>
