<script setup>
defineProps({
    columns: {
        type: String,
        default: 'xl:grid-cols-4',
    },
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2" :class="columns">
        <slot />
    </div>
</template>