<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    quickLoginEnabled: {
        type: Boolean,
        default: false,
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
const cooperativeName = computed(() => cooperative.value.short_name || cooperative.value.name || 'Portal Ahli');
const logoPath = computed(() => cooperative.value.logo_path);

const submit = () => {
    form.post('/member/login', {
        onFinish: () => form.reset('password'),
    });
};

const quickLogin = () => {
    quickLoginForm.post('/member/quick-login');
};
</script>

<template>
    <Head title="Log Masuk Ahli" />

    <main class="min-h-screen bg-slate-50 px-4 py-8 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center">
            <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 space-y-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-700 text-white">
                        <img v-if="logoPath" :src="logoPath" :alt="cooperativeName" class="h-9 w-9 rounded object-contain" />
                        <Building2 v-else class="h-6 w-6" />
                    </div>
                    <div class="space-y-2">
                        <Link href="/" class="text-sm font-medium text-teal-700">Kembali ke laman utama</Link>
                        <p class="text-sm font-medium text-slate-500">{{ cooperativeName }}</p>
                        <h1 class="text-2xl font-semibold tracking-normal">Log Masuk Ahli</h1>
                        <p class="text-sm leading-6 text-slate-600">
                            Akses portal ahli untuk melihat maklumat akaun dan kemas kini koperasi.
                        </p>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <TextInput
                        id="member-email"
                        v-model="form.email"
                        label="Alamat e-mel"
                        type="email"
                        autocomplete="username"
                        :error="form.errors.email"
                    />

                    <TextInput
                        id="member-password"
                        v-model="form.password"
                        label="Kata laluan"
                        type="password"
                        autocomplete="current-password"
                        :error="form.errors.password"
                    />

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700"
                        />
                        Ingat sesi log masuk
                    </label>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        Log Masuk
                    </Button>
                </form>

                <form v-if="quickLoginEnabled" class="mt-4 space-y-3" @submit.prevent="quickLogin">
                    <div v-if="$page.props.errors.quickLogin" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $page.props.errors.quickLogin }}
                    </div>

                    <Button type="submit" variant="outline" class="w-full" :disabled="quickLoginForm.processing">
                        Log Masuk Demo Ahli
                    </Button>
                </form>
            </section>
        </div>
    </main>
</template>
