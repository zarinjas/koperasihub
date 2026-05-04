<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
    eyebrow: {
        type: String,
        default: null,
    },
    centered: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="space-y-4" :class="centered ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl'">
        <p v-if="eyebrow" class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">
            {{ eyebrow }}
        </p>
        <div class="space-y-3">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">
                {{ title }}
            </h2>
            <p v-if="description" class="text-base leading-7 text-slate-600 sm:text-lg">
                {{ description }}
            </p>
        </div>
    </div>
</template>
