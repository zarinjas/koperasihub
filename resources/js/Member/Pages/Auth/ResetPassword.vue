<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2, Lock } from 'lucide-vue-next';
import { computed } from 'vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_path);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/member/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Tetapkan Semula Kata Laluan" />

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
                        <h1 class="text-2xl font-semibold tracking-normal">Tetapkan Semula Kata Laluan</h1>
                        <p class="text-sm leading-6 text-slate-600">Sila masukkan kata laluan baharu untuk akaun portal anda.</p>
                    </div>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">E-mel</p>
                        <p class="mt-1 text-sm text-slate-700">{{ email }}</p>
                    </div>

                    <TextInput
                        id="reset-password"
                        v-model="form.password"
                        label="Kata Laluan Baharu"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password"
                    />

                    <TextInput
                        id="reset-password-confirm"
                        v-model="form.password_confirmation"
                        label="Sahkan Kata Laluan Baharu"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password_confirmation"
                    />

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Tetapkan Semula Kata Laluan' }}
                    </Button>
                </form>
            </section>
        </div>
    </main>
</template>