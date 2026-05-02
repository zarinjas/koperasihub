<script setup>
import { Link } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        default: 'KoperasiHub',
    },
    logoUrl: {
        type: String,
        default: null,
    },
    href: {
        type: String,
        default: '/',
    },
    size: {
        type: String,
        default: 'md',
    },
    showText: {
        type: Boolean,
        default: true,
    },
});

const sizeMap = {
    sm: {
        shell: 'h-9 w-9 rounded-xl',
        image: 'h-6 w-6',
        icon: 'h-4 w-4',
        text: 'text-sm',
    },
    md: {
        shell: 'h-11 w-11 rounded-2xl',
        image: 'h-7 w-7',
        icon: 'h-5 w-5',
        text: 'text-base',
    },
    lg: {
        shell: 'h-14 w-14 rounded-2xl',
        image: 'h-9 w-9',
        icon: 'h-6 w-6',
        text: 'text-lg',
    },
};

const currentSize = computed(() => sizeMap[props.size] ?? sizeMap.md);
const isInternal = computed(() => props.href?.startsWith('/'));
const rootComponent = computed(() => (isInternal.value ? Link : 'a'));
</script>

<template>
    <component
        :is="rootComponent"
        :href="href"
        class="inline-flex items-center gap-3 text-slate-950 transition-colors hover:text-teal-700"
    >
        <span
            class="flex items-center justify-center bg-gradient-to-br from-teal-700 to-blue-700 text-white shadow-sm"
            :class="currentSize.shell"
        >
            <img v-if="logoUrl" :src="logoUrl" :alt="name" class="rounded object-contain" :class="currentSize.image" />
            <Building2 v-else :class="currentSize.icon" />
        </span>

        <span v-if="showText" class="min-w-0">
            <span class="block truncate font-semibold" :class="currentSize.text">{{ name }}</span>
        </span>
    </component>
</template>
