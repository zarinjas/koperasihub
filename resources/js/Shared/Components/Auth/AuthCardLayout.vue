<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';

const props = defineProps({
    variant: {
        type: String,
        default: 'member',
        validator: (v) => ['admin', 'member'].includes(v),
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    cooperativeName: {
        type: String,
        default: '',
    },
    logoUrl: {
        type: String,
        default: null,
    },
    primaryColor: {
        type: String,
        default: '#0F766E',
    },
});

const isAdmin = computed(() => props.variant === 'admin');
</script>

<template>
    <main class="min-h-screen bg-gradient-to-b from-teal-50/50 via-white to-cyan-50/30 px-4 py-8 text-slate-950 sm:px-6 lg:px-8">
        <!-- Admin: 2-column layout -->
        <div
            v-if="isAdmin"
            class="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center gap-8 lg:grid-cols-[1fr_440px]"
        >
            <section class="hidden space-y-6 lg:block">
                <div
                    class="inline-flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-sm"
                    :style="{ backgroundColor: primaryColor }"
                >
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        :alt="cooperativeName"
                        class="h-10 w-10 rounded object-contain"
                    />
                    <Building2 v-else class="h-7 w-7" />
                </div>
                <div class="space-y-4">
                    <span
                        class="inline-block rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        Panel Admin
                    </span>
                    <h1 class="max-w-xl text-3xl font-semibold tracking-normal text-slate-950 lg:text-4xl">
                        Urus operasi koperasi melalui ruang kerja yang tersusun.
                    </h1>
                    <p class="max-w-lg text-base leading-7 text-slate-600">
                        Log masuk untuk mengakses papan pemuka pentadbiran asas. Modul lanjut akan dibina dalam fasa seterusnya.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <Link
                    href="/"
                    class="mb-2 inline-block text-sm font-medium"
                    :style="{ color: primaryColor }"
                >
                    Kembali ke laman utama
                </Link>
                <div class="mb-6 space-y-1">
                    <p class="text-sm font-medium text-slate-500">{{ cooperativeName }}</p>
                    <h2 class="text-2xl font-semibold tracking-normal">{{ title }}</h2>
                    <p v-if="subtitle" class="text-sm leading-6 text-slate-600">{{ subtitle }}</p>
                </div>

                <slot />

                <slot name="quickLogin" />
            </section>
        </div>

        <!-- Member: single centered card -->
        <div v-else class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center">
            <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 space-y-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-white"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="cooperativeName"
                            class="h-9 w-9 rounded object-contain"
                        />
                        <Building2 v-else class="h-6 w-6" />
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-slate-500">{{ cooperativeName }}</p>
                        <h1 class="text-2xl font-semibold tracking-normal">{{ title }}</h1>
                        <p v-if="subtitle" class="text-sm leading-6 text-slate-600">{{ subtitle }}</p>
                    </div>
                </div>

                <slot />

                <slot name="quickLogin" />

                <Link
                    href="/"
                    class="mt-4 inline-block text-sm font-medium"
                    :style="{ color: primaryColor }"
                >
                    Kembali ke laman utama
                </Link>
            </section>
        </div>
    </main>
</template>
