<script setup>
import { LayoutGrid, Megaphone, MessageSquareMore, ScrollText, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const iconMap = {
    users: Users,
    layout: LayoutGrid,
    megaphone: Megaphone,
    files: ScrollText,
    support: MessageSquareMore,
    shield: ShieldCheck,
};

const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});

function resolveIcon(name) {
    return iconMap[name] ?? LayoutGrid;
}
</script>

<template>
    <PublicSection :settings="settings" content-class="space-y-10">
        <SectionHeader
            :eyebrow="data.eyebrow || 'Kelebihan'"
            :title="data.title"
            :description="data.subtitle"
        />

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="item in data.items || []"
                :key="item.title"
                class="min-h-40 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-1 hover:border-teal-200 hover:shadow-md md:min-h-64"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-blue-50 text-teal-700">
                    <component :is="resolveIcon(item.icon)" class="h-6 w-6" />
                </div>
                <div class="mt-5 space-y-2">
                    <h3 class="text-lg font-semibold text-slate-950">{{ item.title }}</h3>
                    <p class="text-sm leading-7 text-slate-600">
                        {{ item.description || 'Maklumat disusun dengan kemas supaya urusan anggota dan pelawat lebih mudah diakses.' }}
                    </p>
                </div>
            </div>
        </div>
    </PublicSection>
</template>