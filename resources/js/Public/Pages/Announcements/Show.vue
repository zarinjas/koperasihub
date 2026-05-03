<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, Pin } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    announcement: { type: Object, required: true },
});

const paragraphs = computed(() => (props.announcement.content || '').split('\n').filter(Boolean));
</script>

<template>
    <Head :title="announcement.title" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-amber-50 via-white to-teal-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader :title="announcement.title" :description="announcement.summary" align="start">
                    <template #actions>
                        <Button :as="Link" href="/pengumuman" variant="outline">
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </template>
                </PageHeader>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <span class="inline-flex items-center gap-2">
                            <CalendarDays class="h-4 w-4 text-amber-700" />
                            {{ announcement.published_at || 'Tarikh akan dikemas kini' }}
                        </span>
                        <span v-if="announcement.is_pinned" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                            <Pin class="h-3.5 w-3.5" />
                            Dipin
                        </span>
                    </div>

                    <div class="space-y-4 text-sm leading-7 text-slate-700">
                        <p v-if="paragraphs.length === 0">{{ announcement.summary }}</p>
                        <p v-for="paragraph in paragraphs" :key="paragraph">{{ paragraph }}</p>
                    </div>
                </article>
            </div>
        </section>
    </PublicLayout>
</template>
