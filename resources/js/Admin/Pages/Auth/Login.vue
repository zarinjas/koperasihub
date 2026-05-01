<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LockKeyhole } from 'lucide-vue-next';
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

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
};

const quickLogin = () => {
    quickLoginForm.post('/admin/quick-login');
};
</script>

<template>
    <Head title="Log Masuk Admin" />

    <main class="min-h-screen bg-slate-100 px-4 py-8 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center gap-8 lg:grid-cols-[1fr_440px]">
            <section class="hidden space-y-5 lg:block">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-700 text-white shadow-sm">
                    <LockKeyhole class="h-6 w-6" />
                </div>
                <div class="space-y-3">
                    <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Panel Admin</p>
                    <h1 class="max-w-xl text-4xl font-semibold tracking-normal text-slate-950">
                        Urus operasi koperasi melalui ruang kerja yang tersusun.
                    </h1>
                    <p class="max-w-lg text-base leading-7 text-slate-600">
                        Log masuk untuk mengakses papan pemuka pentadbiran asas. Modul lanjut akan dibina dalam fasa seterusnya.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 space-y-2">
                    <Link href="/" class="text-sm font-medium text-teal-700">Kembali ke laman utama</Link>
                    <h2 class="text-2xl font-semibold tracking-normal">Log Masuk Admin</h2>
                    <p class="text-sm leading-6 text-slate-600">
                        Masukkan e-mel dan kata laluan pentadbir anda.
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <TextInput
                        id="admin-email"
                        v-model="form.email"
                        label="Alamat e-mel"
                        type="email"
                        autocomplete="username"
                        :error="form.errors.email"
                    />

                    <TextInput
                        id="admin-password"
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
                        Quick Login Admin Demo
                    </Button>
                </form>
            </section>
        </div>
    </main>
</template>
