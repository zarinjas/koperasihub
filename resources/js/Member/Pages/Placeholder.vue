<script setup>
import { Head } from '@inertiajs/vue3';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head :title="title" />

    <MemberLayout>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-teal-700">Portal Ahli</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-normal">{{ title }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                {{ description }}
            </p>
        </section>
    </MemberLayout>
</template>
