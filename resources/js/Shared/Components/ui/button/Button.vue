<script setup>
import { computed } from 'vue';
import { cn } from '@/Shared/lib/utils';

const props = defineProps({
    as: {
        type: [String, Object],
        default: 'button',
    },
    class: {
        type: [String, Array, Object],
        default: '',
    },
    variant: {
        type: String,
        default: 'default',
    },
});

const variants = {
    default: 'bg-teal-700 text-white shadow-sm hover:bg-teal-800',
    outline: 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-100',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-950',
    destructive: 'bg-red-600 text-white shadow-sm hover:bg-red-700',
};

const classes = computed(() => cn(
    'inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
    variants[props.variant] ?? variants.default,
    props.class,
));
</script>

<template>
    <component :is="as" :class="classes">
        <slot />
    </component>
</template>
