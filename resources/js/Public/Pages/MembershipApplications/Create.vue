<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { FileCheck2, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';

const props = defineProps({
    genderOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const form = useForm({
    full_name: '',
    identity_no: '',
    email: '',
    phone: '',
    address: '',
    date_of_birth: '',
    gender: '',
    occupation: '',
    employer_name: '',
    membership_type: '',
    notes: '',
    supporting_document: null,
});

const submit = () => {
    form.post('/membership/apply', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset('supporting_document', 'notes'),
    });
};
</script>

<template>
    <Head title="Permohonan Keahlian" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <PageHeader
                    title="Permohonan Keahlian"
                    description="Lengkapkan borang di bawah untuk menghantar permohonan keahlian baharu. Maklumat anda akan disemak oleh pihak admin koperasi."
                />

                <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <form class="space-y-6" @submit.prevent="submit">
                        <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                            {{ statusMessage }}
                        </div>

                        <FormSection title="Maklumat Peribadi" description="Sila masukkan butiran asas seperti dalam rekod pengenalan rasmi." :columns="2">
                            <TextInput id="apply-full-name" v-model="form.full_name" label="Nama penuh" :error="form.errors.full_name" autocomplete="name" />
                            <TextInput id="apply-identity-no" v-model="form.identity_no" label="No. kad pengenalan" :error="form.errors.identity_no" autocomplete="off" />
                            <TextInput id="apply-email" v-model="form.email" label="E-mel" type="email" :error="form.errors.email" autocomplete="email" />
                            <TextInput id="apply-phone" v-model="form.phone" label="No. telefon" :error="form.errors.phone" autocomplete="tel" />
                            <TextInput id="apply-date-of-birth" v-model="form.date_of_birth" label="Tarikh lahir" type="date" :error="form.errors.date_of_birth" />
                            <SelectInput id="apply-gender" v-model="form.gender" label="Jantina" :options="genderOptions" :error="form.errors.gender" />
                            <div class="md:col-span-2">
                                <TextareaInput id="apply-address" v-model="form.address" label="Alamat" :rows="4" :error="form.errors.address" />
                            </div>
                        </FormSection>

                        <FormSection title="Maklumat Pekerjaan" description="Maklumat ini membantu koperasi memahami latar belakang pemohon." :columns="2">
                            <TextInput id="apply-occupation" v-model="form.occupation" label="Pekerjaan" :error="form.errors.occupation" />
                            <TextInput id="apply-employer-name" v-model="form.employer_name" label="Nama majikan" :error="form.errors.employer_name" />
                            <TextInput id="apply-membership-type" v-model="form.membership_type" label="Jenis keahlian" :error="form.errors.membership_type" />
                            <div class="md:col-span-2">
                                <TextareaInput id="apply-notes" v-model="form.notes" label="Catatan" :rows="4" :error="form.errors.notes" help="Nyatakan tujuan permohonan atau maklumat tambahan jika perlu." />
                            </div>
                        </FormSection>

                        <FormSection title="Dokumen Sokongan" description="Muat naik satu dokumen sokongan jika diperlukan, seperti salinan kad pengenalan atau surat pengesahan." :columns="1">
                            <FileUploader
                                id="apply-supporting-document"
                                v-model="form.supporting_document"
                                label="Dokumen sokongan"
                                accept=".pdf,.jpg,.jpeg,.png"
                                helper-text="Format disokong: PDF, JPG, JPEG, PNG. Saiz maksimum 5MB."
                                :error="form.errors.supporting_document"
                            />
                        </FormSection>

                        <FormActions submit-label="Hantar Permohonan" :submitting="form.processing" @cancel="form.reset()" />
                    </form>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-emerald-100 bg-white/90 p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                    <Users class="h-5 w-5" />
                                </span>
                                <div class="space-y-2">
                                    <h2 class="text-lg font-semibold text-slate-950">Proses ringkas dan jelas</h2>
                                    <p class="text-sm leading-6 text-slate-600">
                                        Permohonan anda akan direkodkan dengan nombor rujukan automatik untuk semakan pihak koperasi.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-blue-100 bg-white/90 p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                                    <ShieldCheck class="h-5 w-5" />
                                </span>
                                <div class="space-y-2">
                                    <h2 class="text-lg font-semibold text-slate-950">Data disemak secara terkawal</h2>
                                    <p class="text-sm leading-6 text-slate-600">
                                        Maklumat yang dihantar hanya digunakan untuk tujuan penilaian permohonan keahlian dan tidak dipaparkan secara awam.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-amber-100 bg-white/90 p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                                    <FileCheck2 class="h-5 w-5" />
                                </span>
                                <div class="space-y-2">
                                    <h2 class="text-lg font-semibold text-slate-950">Sediakan dokumen penting</h2>
                                    <p class="text-sm leading-6 text-slate-600">
                                        Pastikan nombor pengenalan, e-mel, dan dokumen sokongan anda tepat supaya proses semakan dapat dibuat dengan lebih cepat.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
