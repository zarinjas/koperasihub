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
    columns: {
        type: Number,
        default: 1,
    },
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 space-y-1">
            <h2 class="text-lg font-semibold text-slate-950">{{ title }}</h2>
            <p v-if="description" class="text-sm leading-6 text-slate-600">{{ description }}</p>
        </div>

        <div class="grid gap-4" :class="columns === 2 ? 'md:grid-cols-2' : 'grid-cols-1'">
            <slot />
        </div>
    </section>
</template>
