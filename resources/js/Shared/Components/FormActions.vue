<script setup>
import { Loader2 } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    submitLabel: {
        type: String,
        required: true,
    },
    cancelLabel: {
        type: String,
        default: 'Batal',
    },
    submitting: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['cancel']);
</script>

<template>
    <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-end">
        <button type="button" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100 sm:w-auto" @click="$emit('cancel')">{{ cancelLabel }}</button>
        <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-teal-800 disabled:pointer-events-none disabled:opacity-50 sm:w-auto" :disabled="submitting">
            <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
            {{ submitting ? 'Menyimpan...' : submitLabel }}
        </button>
    </div>
</template>