<script setup>
import { Head } from '@inertiajs/vue3';
import FinancingApplicationPrintPack from '@/Shared/Components/FinancingApplicationPrintPack.vue';

defineProps({
    pack: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Cetak ${pack.application.reference_no}`" />
    <FinancingApplicationPrintPack :pack="pack" />
</template>
