<script setup>
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    confirmLabel: {
        type: String,
        default: 'Teruskan',
    },
    cancelLabel: {
        type: String,
        default: 'Batal',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['cancel', 'confirm']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
        <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-950">{{ title }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ description }}</p>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <Button type="button" variant="outline" @click="$emit('cancel')">{{ cancelLabel }}</Button>
                <Button type="button" variant="destructive" :disabled="loading" @click="$emit('confirm')">
                    {{ loading ? 'Memproses...' : confirmLabel }}
                </Button>
            </div>
        </div>
    </div>
</template>
