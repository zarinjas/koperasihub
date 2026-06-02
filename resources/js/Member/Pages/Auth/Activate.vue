<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2, CheckCircle, MailQuestion } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    step: { type: Number, default: 1 },
    memberEmail: { type: String, default: null },
    memberPhone: { type: String, default: null },
    contactEmail: { type: String, default: null },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_path);
const currentStep = ref(props.step);
const icNotFound = ref(false);

const step1Form = useForm({
    identity_no: '',
});

const step2Form = useForm({
    email: props.memberEmail || '',
    password: '',
    password_confirmation: '',
});

const hasMemberEmail = !!props.memberEmail;

const submitStep1 = () => {
    icNotFound.value = false;
    step1Form.post('/member/activate', {
        onSuccess: () => {
            currentStep.value = 2;
        },
        onError: (errors) => {
            if (errors.identity_no && errors.identity_no.includes('tidak dijumpai')) {
                icNotFound.value = true;
            }
        },
    });
};

const submitActivation = () => {
    step2Form.post('/member/activate/complete');
};

const mailtoLink = computed(() => {
    const subject = encodeURIComponent('Pertanyaan Pengaktifan Portal Ahli - No IC');
    const body = encodeURIComponent(
        `Salam sejahtera,\n\nSaya ingin bertanya mengenai pengaktifan portal ahli.\n\nNo. Kad Pengenalan: ${step1Form.identity_no}\n\nSila hubungi saya untuk bantuan lanjut.\n\nTerima kasih.`
    );
    const email = props.contactEmail || cooperative.value.email || '';
    return `mailto:${email}?subject=${subject}&body=${body}`;
});
</script>

<template>
    <Head title="Aktifkan Akaun Portal Ahli" />

    <main class="min-h-screen bg-slate-50 px-4 py-8 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center">
            <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 space-y-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-700 text-white">
                        <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-9 w-9 rounded object-contain" />
                        <Building2 v-else class="h-6 w-6" />
                    </div>
                    <div class="space-y-2">
                        <Link href="/member/login" class="text-sm font-medium text-teal-700">Kembali ke log masuk</Link>
                        <p class="text-sm font-medium text-slate-500">{{ cooperativeName }}</p>
                        <h1 class="text-2xl font-semibold tracking-normal">Aktifkan Akaun Portal Ahli</h1>
                    </div>
                </div>

                <div v-if="currentStep === 1" class="space-y-4">
                    <p class="text-sm text-slate-600">Masukkan No. Kad Pengenalan anda untuk mengaktifkan akaun portal.</p>

                    <form class="space-y-4" @submit.prevent="submitStep1">
                        <TextInput
                            id="activate-identity-no"
                            v-model="step1Form.identity_no"
                            label="No. Kad Pengenalan"
                            placeholder="Contoh: 900101141234"
                            :error="step1Form.errors.identity_no"
                        />

                        <Button type="submit" class="w-full" :disabled="step1Form.processing">
                            {{ step1Form.processing ? 'Mengesahkan...' : 'Seterusnya' }}
                        </Button>
                    </form>

                    <div v-if="icNotFound" class="mt-4 space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-3">
                            <MailQuestion class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                            <div>
                                <p class="text-sm font-medium text-amber-800">No. IC tidak dijumpai</p>
                                <p class="mt-1 text-sm text-amber-700">
                                    Jika anda yakin merupakan ahli koperasi, sila hubungi pentadbir untuk bantuan.
                                </p>
                            </div>
                        </div>
                        <a
                            :href="mailtoLink"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-medium text-amber-800 transition hover:bg-amber-100"
                        >
                            <MailQuestion class="h-4 w-4" />
                            Hubungi Pentadbir
                        </a>
                    </div>
                </div>

                <div v-else-if="currentStep === 2" class="space-y-4">
                    <p class="text-sm text-slate-600">Tetapkan kata laluan untuk akaun portal anda.</p>

                    <form class="space-y-4" @submit.prevent="submitActivation">
                        <TextInput
                            id="activate-email"
                            v-model="step2Form.email"
                            label="Alamat E-mel"
                            type="email"
                            placeholder="contoh@email.com"
                            :error="step2Form.errors.email"
                            :help="hasMemberEmail ? 'E-mel dari rekod ahli. Boleh ditukar jika perlu.' : undefined"
                        />

                        <TextInput
                            id="activate-password"
                            v-model="step2Form.password"
                            label="Kata Laluan Baharu"
                            type="password"
                            :error="step2Form.errors.password"
                        />

                        <TextInput
                            id="activate-password-confirm"
                            v-model="step2Form.password_confirmation"
                            label="Sahkan Kata Laluan"
                            type="password"
                            :error="step2Form.errors.password_confirmation"
                        />

                        <Button type="submit" class="w-full" :disabled="step2Form.processing">
                            {{ step2Form.processing ? 'Mengaktifkan...' : 'Aktifkan Akaun' }}
                        </Button>
                    </form>
                </div>

                <div v-else class="space-y-4 text-center">
                    <div class="flex justify-center">
                        <CheckCircle class="h-16 w-16 text-emerald-500" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-950">Akaun Berjaya Diaktifkan</h2>
                    <p class="text-sm text-slate-600">Akaun portal anda telah diaktifkan. Sila lengkapkan profil anda untuk pengalaman yang lebih baik.</p>
                    <Button :as="Link" href="/member/dashboard" class="w-full">
                        Terus ke Portal
                    </Button>
                </div>
            </section>
        </div>
    </main>
</template>