<script setup>
import { ScanFace } from 'lucide-vue-next';

defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        class="flex w-full items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-700 shadow-sm transition-colors hover:bg-teal-100 hover:text-teal-800"
    >
        <ScanFace class="h-5 w-5" />
        <span>Log Masuk dengan Face ID</span>
    </button>
</template>
