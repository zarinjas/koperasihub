<script setup>
import { computed } from 'vue';
import { resolveBackground, resolveContainer, resolveSpacing } from '@/Public/utils/sectionStyles';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    containerClass: {
        type: String,
        default: null,
    },
    contentClass: {
        type: String,
        default: null,
    },
});

const sectionClass = computed(() => [
    'relative overflow-hidden',
    resolveBackground(props.settings.background),
    resolveSpacing(props.settings.spacing),
]);

const innerClass = computed(() => props.containerClass ?? resolveContainer(props.settings.container));
</script>

<template>
    <section :class="sectionClass">
        <div :class="innerClass">
            <div :class="contentClass">
                <slot />
            </div>
        </div>
    </section>
</template>
