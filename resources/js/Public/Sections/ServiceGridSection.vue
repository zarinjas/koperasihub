<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, BriefcaseBusiness } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

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
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <SectionHeader
                :eyebrow="data.eyebrow || 'Perkhidmatan'"
                :title="data.title"
                :description="data.subtitle"
            />
            <Button :as="Link" href="/perkhidmatan" variant="outline">Lihat semua</Button>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in data.items || []"
                :key="item.title"
                :href="item.url || '/perkhidmatan'"
                class="group min-h-40 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-1 hover:border-teal-200 hover:shadow-md md:min-h-64"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-blue-50 text-teal-700">
                        <BriefcaseBusiness class="h-6 w-6" />
                    </div>
                    <ArrowUpRight class="h-5 w-5 text-slate-400 transition-colors group-hover:text-teal-700" />
                </div>
                <div class="mt-5 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-slate-950">{{ item.title }}</h3>
                        <span v-if="item.category" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                            {{ item.category.replaceAll('_', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm leading-7 text-slate-600">{{ item.description }}</p>
                </div>
            </Link>
        </div>
    </PublicSection>
</template>