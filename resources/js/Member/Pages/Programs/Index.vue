<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar, MapPin, Monitor, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    programs: { type: Object, required: true },
    tab: { type: String, default: 'upcoming' },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const switchTab = (tab) => {
    router.get('/member/programs', { tab }, { preserveState: true, replace: true });
};

const typeIcon = (type) => ({
    physical: MapPin,
    online: Monitor,
    hybrid: Users,
})[type] || Calendar;

const typeLabel = (type) => ({
    physical: 'Fizikal',
    online: 'Atas Talian',
    hybrid: 'Hibrid',
})[type] || type;


</script>

<template>
    <Head title="Program" />

    <MemberLayout>
        <section class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Program</h1>
                <p class="mt-1 text-sm text-slate-500">Lihat dan RSVP program serta acara koperasi.</p>
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="flex gap-2 border-b border-slate-200 pb-1">
                <Button type="button" :variant="tab === 'upcoming' ? 'default' : 'ghost'" @click="switchTab('upcoming')">Akan Datang</Button>
                <Button type="button" :variant="tab === 'past' ? 'default' : 'ghost'" @click="switchTab('past')">Lepas</Button>
            </div>

            <EmptyState
                v-if="programs.data.length === 0"
                title="Tiada program"
                :description="tab === 'upcoming' ? 'Belum ada program akan datang buat masa ini.' : 'Tiada program lepas.'"
            />

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="pg in programs.data" :key="pg.id" class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                    <div v-if="pg.cover_image_url" class="h-36 overflow-hidden">
                        <img :src="pg.cover_image_url" :alt="pg.title" class="h-full w-full object-cover" />
                    </div>
                    <div class="space-y-3 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <p class="font-semibold text-slate-900">{{ pg.title }}</p>
                                <p v-if="pg.category" class="text-xs text-slate-500">{{ pg.category }}</p>
                            </div>
                            <component :is="typeIcon(pg.program_type)" class="h-5 w-5 shrink-0 text-slate-400" />
                        </div>

                        <div class="space-y-1.5 text-sm text-slate-600">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 shrink-0" />
                                <span>{{ pg.start_date_formatted }} <span v-if="pg.start_time">| {{ pg.start_time }}</span></span>
                            </div>
                            <div v-if="pg.location" class="flex items-center gap-2">
                                <MapPin class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ pg.location }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <StatusBadge :status="pg.program_type" />
                            <span v-if="pg.rsvps_hadir_count > 0" class="text-xs text-slate-500">{{ pg.rsvps_hadir_count }} hadir</span>
                        </div>

                        <div v-if="pg.user_rsvp" class="flex items-center gap-2">
                            <StatusBadge :status="pg.user_rsvp.response" />
                            <span v-if="pg.user_rsvp.checked_in" class="text-xs text-teal-600">Sudah hadir</span>
                        </div>

                        <Button :as="Link" :href="`/member/programs/${pg.id}`" variant="outline" class="w-full">Lihat Program</Button>
                    </div>
                </div>
            </div>

            <div v-if="programs.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in programs.links"
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
