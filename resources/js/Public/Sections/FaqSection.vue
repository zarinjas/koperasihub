<script setup>
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const openIndex = ref(0);
const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});

function toggle(index) {
    openIndex.value = openIndex.value === index ? -1 : index;
}
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-10">
        <SectionHeader
            eyebrow="Soalan lazim"
            :title="data.title"
            :description="data.subtitle"
        />

        <div class="grid gap-4">
            <div
                v-for="(item, index) in data.items || []"
                :key="item.question"
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                    @click="toggle(index)"
                >
                    <span class="text-base font-semibold text-slate-950">{{ item.question }}</span>
                    <ChevronDown class="h-5 w-5 shrink-0 text-slate-500 transition-transform" :class="openIndex === index ? 'rotate-180' : ''" />
                </button>
                <div v-if="openIndex === index" class="border-t border-slate-100 px-6 py-5 text-sm leading-7 text-slate-600">
                    {{ item.answer }}
                </div>
            </div>
        </div>
    </PublicSection>
</template>