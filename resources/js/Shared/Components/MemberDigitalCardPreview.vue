<script setup>
import MemberDigitalCard from '@/Shared/Components/MemberDigitalCard.vue';
import MemberDigitalCardLandscape from '@/Shared/Components/MemberDigitalCardLandscape.vue';

defineProps({
    cooperative: {
        type: Object,
        required: true,
    },
    card: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="w-full">
        <div class="hidden lg:block">
            <MemberDigitalCardLandscape :cooperative="cooperative" :card="card" />
        </div>
        <div class="lg:hidden">
            <MemberDigitalCard :cooperative="cooperative" :card="card" size="compact" fluid-on-mobile />
        </div>
    </div>
</template>
