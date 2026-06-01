<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle, Clock } from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    records: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);


</script>

<template>
    <Head title="Kehadiran Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Kehadiran Saya</h1>
                <p class="mt-1 text-sm text-slate-500">Sejarah kehadiran program dan acara anda.</p>
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                    <p class="text-3xl font-bold text-slate-900">{{ stats.total_programs }}</p>
                    <p class="text-sm text-slate-500">Jumlah Program</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                    <p class="text-3xl font-bold text-teal-700">{{ stats.total_attended }}</p>
                    <p class="text-sm text-slate-500">Hadir</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                    <p class="text-3xl font-bold text-amber-600">{{ stats.upcoming }}</p>
                    <p class="text-sm text-slate-500">Akan Datang</p>
                </div>
            </div>

            <EmptyState
                v-if="records.data.length === 0"
                title="Tiada rekod kehadiran."
                description="RSVP program yang akan datang untuk mula merekod kehadiran anda."
            />

            <div v-else class="space-y-3">
                <div v-for="rec in records.data" :key="rec.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0 space-y-1">
                            <Link :href="`/member/programs/${rec.program_id}`" class="font-semibold text-slate-900 hover:text-teal-700">
                                {{ rec.program_title }}
                            </Link>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span v-if="rec.program_start_date">
                                    <Clock class="mr-1 inline h-3 w-3" />
                                    {{ rec.program_start_date }}
                                </span>
                                <StatusBadge :status="rec.program_type" />
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm font-medium"><StatusBadge :status="rec.response" /></p>
                                <p v-if="rec.checked_in" class="flex items-center gap-1 text-xs text-teal-600">
                                    <CheckCircle class="h-3 w-3" />
                                    Hadir
                                </p>
                                <p v-else class="text-xs text-slate-400">
                                    {{ rec.program_status === 'completed' ? 'Tidak hadir' : 'Belum' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="records.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in records.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>
        </section>
    </MemberLayout>
</template>