<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Store } from 'lucide-vue-next';
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
            eyebrow="Perniagaan"
            :title="data.title"
            :description="data.subtitle"
        />

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in data.items || []"
                :key="item.title"
                :href="item.url || '/perniagaan'"
                class="group rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <Store class="h-6 w-6" />
                    </div>
                    <ArrowUpRight class="h-5 w-5 text-slate-400 transition-colors group-hover:text-blue-700" />
                </div>
                <div class="mt-5 space-y-2">
                    <h3 class="text-lg font-semibold text-slate-950">{{ item.title }}</h3>
                    <p class="text-sm leading-7 text-slate-600">{{ item.description }}</p>
                </div>
            </Link>
        </div>
    </PublicSection>
</template>
