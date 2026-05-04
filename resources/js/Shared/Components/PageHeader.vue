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
    align: {
        type: String,
        default: 'between',
    },
});
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-start"
        :class="align === 'start' ? 'sm:justify-start' : 'sm:justify-between'"
    >
        <div class="max-w-3xl space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ title }}</h1>
            <p v-if="description" class="text-sm leading-6 text-slate-600">{{ description }}</p>
        </div>

        <div v-if="$slots.actions" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <slot name="actions" />
        </div>
    </div>
</template>
