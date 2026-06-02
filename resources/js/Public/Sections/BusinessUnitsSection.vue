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

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in data.items || []"
                :key="item.title"
                :href="item.url || '/perniagaan'"
                class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-md"
            >
                <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-blue-50 via-teal-50 to-slate-100">
                    <img
                        v-if="item.image_url"
                        :src="item.image_url"
                        :alt="item.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                        <Store class="h-12 w-12 text-blue-200" />
                    </div>
                </div>

                <div class="space-y-3 p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-slate-950">{{ item.title }}</h3>
                        <ArrowUpRight class="h-5 w-5 shrink-0 text-slate-400 transition-colors group-hover:text-blue-700" />
                    </div>
                    <p class="text-sm leading-7 text-slate-600">{{ item.description }}</p>
                </div>
            </Link>
        </div>
    </PublicSection>
</template>