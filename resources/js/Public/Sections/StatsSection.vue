<script setup>
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});
const columnsClass = computed(() => ({
    2: 'md:grid-cols-2',
    3: 'md:grid-cols-3',
    4: 'md:grid-cols-4',
}[settings.value.columns] ?? 'md:grid-cols-3'));
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-6">
        <div class="grid gap-4" :class="columnsClass">
            <div
                v-for="item in data.items || []"
                :key="`${item.label}-${item.value}`"
                class="rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 shadow-sm"
            >
                <p class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ item.value }}</p>
                <p class="mt-2 text-sm font-medium text-slate-600">{{ item.label }}</p>
            </div>
        </div>
    </PublicSection>
</template>
