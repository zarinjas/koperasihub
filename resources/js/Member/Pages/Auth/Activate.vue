<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Building2, CheckCircle, Lock, UserRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    step: { type: Number, default: 1 },
    memberId: { type: Number, default: null },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_path);
const statusMessage = computed(() => page.props.flash?.status);
const currentStep = ref(1);

const step1Form = useForm({
    member_no: '',
    identity_no: '',
    date_of_birth: '',
});

const step2Form = useForm({
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submitStep1 = () => {
    step1Form.post('/member/activate', {
        onSuccess: () => {
            currentStep.value = 2;
        },
    });
};

const submitActivation = () => {
    step2Form.post('/member/activate/complete', {
        onSuccess: () => {
            currentStep.value = 3;
        },
    });
};
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

                <div v-if="statusMessage" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ statusMessage }}
                </div>

                <div v-if="currentStep === 1" class="space-y-4">
                    <p class="text-sm text-slate-600">Masukkan maklumat ahli anda untuk mengesahkan identiti.</p>

                    <form class="space-y-4" @submit.prevent="submitStep1">
                        <TextInput
                            id="activate-member-no"
                            v-model="step1Form.member_no"
                            label="No. Ahli"
                            placeholder="Contoh: MBR-0001"
                            :error="step1Form.errors.member_no"
                        />

                        <TextInput
                            id="activate-identity-no"
                            v-model="step1Form.identity_no"
                            label="No. Kad Pengenalan"
                            placeholder="Contoh: 900101141234"
                            :error="step1Form.errors.identity_no"
                        />

                        <TextInput
                            id="activate-dob"
                            v-model="step1Form.date_of_birth"
                            label="Tarikh Lahir"
                            type="date"
                            :error="step1Form.errors.date_of_birth"
                        />

                        <Button type="submit" class="w-full" :disabled="step1Form.processing">
                            {{ step1Form.processing ? 'Mengesahkan...' : 'Seterusnya' }}
                        </Button>
                    </form>
                </div>

                <div v-else-if="currentStep === 2" class="space-y-4">
                    <p class="text-sm text-slate-600">Tetapkan e-mel dan kata laluan untuk akaun portal anda.</p>

                    <form class="space-y-4" @submit.prevent="submitActivation">
                        <TextInput
                            id="activate-email"
                            v-model="step2Form.email"
                            label="Alamat E-mel"
                            type="email"
                            placeholder="contoh@email.com"
                            :error="step2Form.errors.email"
                        />

                        <TextInput
                            id="activate-phone"
                            v-model="step2Form.phone"
                            label="No. Telefon"
                            placeholder="Contoh: 0123456789"
                            :error="step2Form.errors.phone"
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
                    <p class="text-sm text-slate-600">Akaun portal anda telah diaktifkan. Sila log masuk menggunakan email dan kata laluan yang ditetapkan.</p>
                    <Button :as="Link" href="/member/login" class="w-full">
                        Log Masuk
                    </Button>
                </div>
            </section>
        </div>
    </main>
</template>
