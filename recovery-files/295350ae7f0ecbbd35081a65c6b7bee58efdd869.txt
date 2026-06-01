<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar, CheckCircle, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    program: { type: Object, required: true },
    alreadyCheckedIn: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const confirmCheckIn = () => {
    router.post(`/member/programs/${props.program.id}/check-in`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Daftar Masuk - ${program.title}`" />

    <MemberLayout>
        <section class="mx-auto max-w-lg space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-semibold text-slate-900">Daftar Masuk</h1>
                <p class="mt-1 text-sm text-slate-500">Sahkan kehadiran anda untuk program ini.</p>
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div v-if="alreadyCheckedIn" class="space-y-4 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-teal-50">
                        <CheckCircle class="h-10 w-10 text-teal-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-teal-700">Kehadiran Disahkan</h2>
                    <p class="text-sm text-slate-500">Anda telah daftar masuk untuk program ini.</p>
                    <Button :as="Link" :href="`/member/programs/${program.id}`" variant="outline">Kembali ke Program</Button>
                </div>

                <div v-else class="space-y-6 text-center">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold">{{ program.title }}</h2>
                        <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
                            <Calendar class="h-4 w-4" />
                            <span>{{ program.start_date_formatted }} | {{ program.start_time }}</span>
                        </div>
                        <div v-if="program.location" class="flex items-center justify-center gap-2 text-sm text-slate-500">
                            <MapPin class="h-4 w-4" />
                            <span>{{ program.location }}</span>
                        </div>
                    </div>

                    <p class="text-sm text-slate-600">Sila sahkan bahawa anda hadir ke program ini.</p>

                    <div class="flex flex-col gap-3">
                        <Button @click="confirmCheckIn" class="w-full">Sahkan Kehadiran</Button>
                        <Button :as="Link" :href="`/member/programs/${program.id}`" variant="outline" class="w-full">Kembali</Button>
                    </div>
                </div>
            </section>
        </section>
    </MemberLayout>
</template>
