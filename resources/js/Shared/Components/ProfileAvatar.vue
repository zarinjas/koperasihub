<script setup>
import { computed } from 'vue';
import { UserRound } from 'lucide-vue-next';

const props = defineProps({
    photoUrl: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
    },
});

const sizeClasses = {
    sm: 'h-14 w-14 text-base',
    md: 'h-20 w-20 text-xl',
    lg: 'h-24 w-24 text-2xl',
    xl: 'h-32 w-32 text-3xl',
};

const iconClasses = {
    sm: 'h-5 w-5',
    md: 'h-7 w-7',
    lg: 'h-8 w-8',
    xl: 'h-10 w-10',
};

const initials = computed(() => {
    const segments = (props.name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    return segments.map((segment) => segment.charAt(0)).join('').toUpperCase();
});
</script>

<template>
    <div
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/70 bg-gradient-to-br from-teal-100 via-white to-blue-100 font-semibold text-teal-800 shadow-sm"
        :class="sizeClasses[size] ?? sizeClasses.md"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="`Foto profil ${name || 'ahli'}`"
            class="h-full w-full object-cover"
        />
        <span v-else-if="initials">{{ initials }}</span>
        <UserRound v-else class="text-teal-700" :class="iconClasses[size] ?? iconClasses.md" />
    </div>
</template>
