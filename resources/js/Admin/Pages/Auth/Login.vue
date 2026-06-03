<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthCardLayout from '@/Shared/Components/Auth/AuthCardLayout.vue';
import PasswordInput from '@/Shared/Components/Auth/PasswordInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    quickLoginEnabled: {
        type: Boolean,
        default: false,
    },
    quickLoginOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const quickLoginForm = useForm({});
const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'KoperasiHub');
const logoUrl = computed(() => cooperative.value.logo_url);
const primaryColor = computed(() => cooperative.value.primary_color || '#0F766E');

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
};

const quickLogin = (url) => {
    quickLoginForm.post(url);
};
</script>

<template>
    <Head title="Log Masuk Admin" />

    <AuthCardLayout
        variant="admin"
        title="Log Masuk Admin"
        subtitle="Masukkan e-mel dan kata laluan pentadbir anda."
        :cooperative-name="cooperativeName"
        :logo-url="logoUrl"
        :primary-color="primaryColor"
    >
        <form class="space-y-5" @submit.prevent="submit">
            <TextInput
                id="admin-email"
                v-model="form.email"
                label="Alamat e-mel"
                type="email"
                autocomplete="username"
                :error="form.errors.email"
            />

            <PasswordInput
                id="admin-password"
                v-model="form.password"
                :error="form.errors.password"
            />

            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700"
                />
                Ingat sesi log masuk
            </label>

            <Button
                type="submit"
                class="w-full bg-gradient-to-r from-teal-700 to-teal-600 hover:from-teal-800 hover:to-teal-700"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Sedang diproses...' : 'Log Masuk' }}
            </Button>
        </form>

        <template #quickLogin>
            <div v-if="quickLoginEnabled && quickLoginOptions.length" class="mt-6 space-y-3">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-slate-200" />
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-2 text-xs text-slate-400">Log Masuk Pantas</span>
                    </div>
                </div>

                <div v-if="$page.props.errors.quickLogin" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $page.props.errors.quickLogin }}
                </div>

                <Button
                    v-for="option in quickLoginOptions"
                    :key="option.url"
                    type="button"
                    variant="outline"
                    class="w-full text-xs"
                    :disabled="quickLoginForm.processing"
                    @click="quickLogin(option.url)"
                >
                    {{ option.label }}
                </Button>
            </div>
        </template>
    </AuthCardLayout>
</template>
