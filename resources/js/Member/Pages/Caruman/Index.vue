<script setup>
import { Head } from '@inertiajs/vue3';
import { Eye, EyeOff, Banknote } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import DecorativeBlobs from '@/Shared/Components/DecorativeBlobs.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    member: { type: Object, default: null },
    contribution: { type: Object, default: null },
    years: { type: Array, required: true },
    selectedYear: { type: Number, required: true },
});

const showAmounts = ref(false);

const toggleAll = () => {
    showAmounts.value = !showAmounts.value;
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '*****';
    if (!showAmounts.value) return 'RM *****';
    return 'RM ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const switchYear = (year) => {
    const params = new URLSearchParams(window.location.search);
    params.set('year', year);
    window.location.search = params.toString();
};

const totalDisplay = computed(() => {
    if (!props.contribution) return showAmounts.value ? 'RM 0.00' : 'RM *****';
    const total = (props.contribution.caruman_semasa || 0) + (props.contribution.dividen || 0);
    if (!showAmounts.value) return 'RM *****';
    return 'RM ' + total.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});
</script>

<template>
    <Head title="Caruman Saya" />

    <MemberLayout>
        <div class="space-y-6">
            <PageHeader
                title="Caruman Saya"
                description="Semak caruman dan dividen anda mengikut tahun. Klik ikon mata untuk tunjukkan atau sembunyikan jumlah."
            >
                <template #actions>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <Banknote class="h-5 w-5" />
                    </span>
                </template>
            </PageHeader>

            <!-- Year Selector -->
            <div v-if="years.length > 1" class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="year in years"
                    :key="year"
                    :variant="year === selectedYear ? 'default' : 'outline'"
                    size="sm"
                    @click="switchYear(year)"
                >
                    {{ year }}
                </Button>
            </div>

            <!-- Contribution Card -->
            <template v-if="contribution">
                <section class="relative overflow-hidden rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50 via-cyan-50 to-blue-50 p-6 shadow-sm sm:p-8">
                    <DecorativeBlobs color="teal" />

                    <div class="relative">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                                    <Banknote class="h-6 w-6" />
                                </span>
                                <div>
                                    <h2 class="text-2xl font-bold text-slate-950">Caruman {{ selectedYear }}</h2>
                                    <p class="text-sm text-slate-600">No. Ahli: {{ member?.member_no }}</p>
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-12 w-12 rounded-2xl bg-white shadow-sm hover:bg-slate-100"
                                :title="showAmounts ? 'Sembunyikan jumlah' : 'Tunjukkan jumlah'"
                                @click="toggleAll"
                            >
                                <Eye v-if="showAmounts" class="h-5 w-5 text-teal-600" />
                                <EyeOff v-else class="h-5 w-5 text-slate-400" />
                            </Button>
                        </div>

                        <div class="mt-8 space-y-4">
                            <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Caruman Setakat Ini</p>
                                <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-slate-950">
                                    {{ formatCurrency(contribution.caruman_semasa) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Jumlah caruman terkumpul bagi tahun {{ selectedYear }}</p>
                            </div>

                            <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Caruman Keseluruhan</p>
                                <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-slate-950">
                                    {{ formatCurrency(contribution.caruman_keseluruhan) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Jumlah caruman sepanjang masa sehingga tahun {{ selectedYear }}</p>
                            </div>

                            <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Dividen {{ selectedYear }}</p>
                                <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-emerald-600">
                                    {{ formatCurrency(contribution.dividen) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Dividen yang diisytiharkan untuk tahun {{ selectedYear }}</p>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="mt-6 rounded-2xl border border-teal-200/60 bg-teal-50/60 p-5">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <p class="text-sm font-semibold text-teal-800">Jumlah Caruman + Dividen {{ selectedYear }}</p>
                                <p class="text-xl font-bold tabular-nums tracking-tight text-teal-800">
                                    {{ totalDisplay }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            <EmptyState
                v-else
                title="Tiada data caruman."
                :description="`Data caruman untuk tahun ${selectedYear} belum tersedia. Sila hubungi pihak koperasi jika anda mempunyai sebarang pertanyaan.`"
            />
        </div>
    </MemberLayout>
</template>