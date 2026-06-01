<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar, Clock, MapPin, Monitor, QrCode, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    program: { type: Object, required: true },
    rsvp: { type: Object, default: null },
    rsvpOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const sendRsvp = (response) => {
    router.post(`/member/programs/${props.program.id}/rsvp`, { response }, { preserveScroll: true });
};

const isSelected = (value) => props.rsvp?.response === value;

const typeLabel = (type) => ({
    physical: 'Fizikal',
    online: 'Atas Talian',
    hybrid: 'Hibrid',
})[type] || type;
</script>

<template>
    <Head :title="program.title" />

    <MemberLayout>
        <section class="mx-auto max-w-4xl space-y-6">
            <div v-if="program.cover_image_url" class="h-48 overflow-hidden rounded-2xl sm:h-64">
                <img :src="program.cover_image_url" :alt="program.title" class="h-full w-full object-cover" />
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ program.title }}</h1>
                    <StatusBadge :status="program.program_type" />
                </div>
                <p v-if="program.category" class="mt-1 text-sm text-slate-500">{{ program.category }}</p>
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Maklumat Program</h2>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <div>
                                    <p class="font-medium">{{ program.start_date_formatted }}</p>
                                    <p class="text-sm text-slate-500">Masa: {{ program.start_time }}</p>
                                </div>
                            </div>
                            <div v-if="program.end_date_formatted && program.end_date_formatted !== program.start_date_formatted" class="flex items-start gap-3">
                                <Clock class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <div>
                                    <p class="font-medium">{{ program.end_date_formatted }}</p>
                                    <p v-if="program.end_time" class="text-sm text-slate-500">Masa: {{ program.end_time }}</p>
                                </div>
                            </div>
                            <div v-if="program.location" class="flex items-start gap-3">
                                <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <p>{{ program.location }}</p>
                            </div>
                            <div v-if="program.online_url" class="flex items-start gap-3">
                                <Monitor class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <a :href="program.online_url" target="_blank" class="text-teal-700 hover:underline">{{ program.online_url }}</a>
                            </div>
                            <div v-if="program.capacity" class="flex items-start gap-3">
                                <Users class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <p>Kapasiti: {{ program.capacity }}</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="program.description" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Penerangan</h2>
                        <p class="whitespace-pre-wrap text-sm text-slate-700">{{ program.description }}</p>
                    </section>
                </div>

                <div class="space-y-4">
                    <section v-if="program.is_upcoming && program.registration_open" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-3 text-base font-semibold">RSVP Anda</h2>
                        <p class="mb-3 text-sm text-slate-500">Sila sahkan kehadiran anda:</p>
                        <div class="space-y-2">
                            <Button
                                v-for="opt in rsvpOptions"
                                :key="opt.value"
                                type="button"
                                :variant="isSelected(opt.value) ? 'default' : 'outline'"
                                class="w-full"
                                @click="sendRsvp(opt.value)"
                            >
                                {{ opt.label }}
                            </Button>
                        </div>
                        <p v-if="rsvp" class="mt-2 text-xs text-slate-400">Respon dikemaskini: {{ rsvp.responded_at }}</p>
                    </section>

                    <section v-if="program.is_upcoming" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm text-center">
                        <Button :as="Link" :href="`/member/programs/${program.id}/check-in`" variant="outline" class="w-full">
                            <QrCode class="mr-2 h-4 w-4" />
                            Daftar Masuk
                        </Button>
                    </section>

                    <section v-if="rsvp?.checked_in" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm text-center">
                        <Badge variant="success" class="text-sm">Anda telah hadir</Badge>
                        <p class="mt-1 text-xs text-slate-500">Masa: {{ rsvp.checked_in_at }}</p>
                    </section>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>