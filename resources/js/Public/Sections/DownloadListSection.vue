<script setup>
import { Link } from '@inertiajs/vue3';
import { Download, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-10">
        <SectionHeader
            eyebrow="Muat turun"
            :title="data.title"
            :description="data.subtitle"
        />

        <div class="grid gap-4">
            <Link
                v-for="item in data.items || []"
                :key="item.title"
                :href="item.url || '/muat-turun'"
                class="group flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:border-teal-200 hover:shadow-md sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                        <FileText class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-semibold text-slate-950">{{ item.title }}</h3>
                        <p class="text-sm leading-6 text-slate-600">{{ item.description }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                    <span class="text-sm text-slate-500">{{ item.file_size || 'PDF' }}</span>
                    <span class="inline-flex items-center text-sm font-semibold text-teal-700">
                        <Download class="mr-2 h-4 w-4" />
                        Muat turun
                    </span>
                </div>
            </Link>
        </div>
    </PublicSection>
</template>