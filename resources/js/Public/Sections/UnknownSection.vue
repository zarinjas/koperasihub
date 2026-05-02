<script setup>
defineProps({
    section: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
            Jenis seksyen tidak dikenali: <span class="font-semibold">{{ section.type }}</span>
        </div>
    </section>
</template>
