<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, Pin } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    announcement: { type: Object, required: true },
});

const paragraphs = computed(() => (props.announcement.content || '').split('\n').filter(Boolean));
</script>

<template>
    <Head :title="announcement.title" />

    <MemberLayout>
        <div class="space-y-6">
            <Button :as="Link" href="/member/announcements" variant="outline" class="w-fit">
                <ChevronLeft class="mr-2 h-4 w-4" />
                Kembali ke senarai
            </Button>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2">
                        <CalendarDays class="h-4 w-4 text-teal-700" />
                        {{ announcement.published_at || '-' }}
                    </span>
                    <StatusBadge v-if="announcement.is_pinned" status="published" label="Dipin" />
                    <StatusBadge :status="announcement.audience" />
                </div>

                <h1 class="mb-4 text-xl font-semibold text-slate-950">{{ announcement.title }}</h1>

                <div v-if="announcement.summary" class="mb-4 text-sm font-medium text-slate-600">
                    {{ announcement.summary }}
                </div>

                <div class="space-y-4 text-sm leading-7 text-slate-700">
                    <p v-if="paragraphs.length === 0">{{ announcement.summary }}</p>
                    <p v-for="paragraph in paragraphs" :key="paragraph">{{ paragraph }}</p>
                </div>
            </article>
        </div>
    </MemberLayout>
</template>
