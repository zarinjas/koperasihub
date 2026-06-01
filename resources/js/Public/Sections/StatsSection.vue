<script setup>
import { BarChart3, Clock3, Layers3, UsersRound } from 'lucide-vue-next';
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

const icons = [BarChart3, Layers3, Clock3, UsersRound];
</script>

<template>
    <PublicSection :settings="{ ...settings, background: settings.background || 'muted' }" content-class="space-y-6">
        <div class="grid gap-4" :class="columnsClass">
            <div
                v-for="(item, index) in data.items || []"
                :key="`${item.label}-${item.value}`"
                class="min-h-36 rounded-3xl border border-white/80 bg-white p-6 shadow-sm shadow-slate-900/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ item.value }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-600">{{ item.label }}</p>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-blue-50 text-teal-700">
                        <component :is="icons[index % icons.length]" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>
    </PublicSection>
</template>