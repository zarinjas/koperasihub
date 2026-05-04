<script setup>
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
        <Button type="button" variant="outline" class="w-full sm:w-auto" @click="$emit('cancel')">{{ cancelLabel }}</Button>
        <Button type="submit" class="w-full sm:w-auto" :disabled="submitting">{{ submitting ? 'Menyimpan...' : submitLabel }}</Button>
    </div>
</template>
