<script setup>
defineProps({
    color: { type: String, default: 'teal' },
});
</script>

<template>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div
            class="absolute -top-16 right-1/4 h-48 w-48 rounded-full blur-3xl"
            :class="{
                'bg-teal-100/50': color === 'teal',
                'bg-blue-100/50': color === 'blue',
                'bg-emerald-100/50': color === 'emerald',
                'bg-indigo-100/50': color === 'indigo',
                'bg-amber-100/50': color === 'amber',
                'bg-cyan-100/50': color === 'cyan',
                'bg-violet-100/50': color === 'violet',
            }"
        />
        <div
            class="absolute bottom-0 left-1/4 h-36 w-36 rounded-full blur-3xl"
            :class="{
                'bg-cyan-100/40': color === 'teal',
                'bg-cyan-100/40': color === 'blue',
                'bg-green-100/40': color === 'emerald',
                'bg-violet-100/40': color === 'indigo',
                'bg-orange-100/40': color === 'amber',
                'bg-teal-100/40': color === 'cyan',
                'bg-purple-100/40': color === 'violet',
            }"
        />
        <div
            class="absolute right-0 top-1/3 h-32 w-32 rounded-full blur-3xl"
            :class="{
                'bg-blue-100/30': color === 'teal',
                'bg-indigo-100/30': color === 'blue',
                'bg-teal-100/30': color === 'emerald',
                'bg-blue-100/30': color === 'indigo',
                'bg-yellow-100/30': color === 'amber',
                'bg-blue-100/30': color === 'cyan',
                'bg-indigo-100/30': color === 'violet',
            }"
        />
    </div>
</template>