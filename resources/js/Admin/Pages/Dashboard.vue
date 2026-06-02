<script setup>
import { Head } from '@inertiajs/vue3';
import { FileCheck, MessagesSquare, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
} from 'chart.js';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';

ChartJS.register(
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
);

const props = defineProps({
    stats: { type: Array, default: () => [] },
    charts: { type: Object, required: true },
});

const icons = { FileCheck, MessagesSquare, Users };

const toneClasses = {
    info: 'bg-blue-50 text-blue-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-red-50 text-red-700',
    success: 'bg-emerald-50 text-emerald-700',
};

const submissionBars = computed(() => {
    const { labels, data, colors } = props.charts.submissionsByStatus;
    const total = data.reduce((s, v) => s + v, 0) || 1;
    return labels.map((label, i) => ({
        label,
        count: data[i],
        color: colors[i],
        pct: Math.round((data[i] / total) * 100),
    }));
});

const complaintsBars = computed(() => {
    const { labels, data, colors } = props.charts.complaintsByStatus;
    const total = data.reduce((s, v) => s + v, 0) || 1;
    return labels.map((label, i) => ({
        label,
        count: data[i],
        color: colors[i],
        pct: Math.round((data[i] / total) * 100),
    }));
});

const unitBar = computed(() => ({
    labels: props.charts.submissionsByUnit.labels,
    datasets: [{
        label: 'Permohonan',
        data: props.charts.submissionsByUnit.data,
        backgroundColor: ['#0F766E', '#0D9488', '#14B8A6', '#2DD4BF', '#5EEAD4', '#99F6E4'],
        borderRadius: 6,
        borderSkipped: false,
        barThickness: 20,
    }],
}));

const membersLine = computed(() => ({
    labels: props.charts.membersByMonth.labels,
    datasets: [{
        label: 'Ahli Baharu',
        data: props.charts.membersByMonth.data,
        borderColor: '#0F766E',
        backgroundColor: 'rgba(15, 118, 110, 0.08)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#0F766E',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const barOptions = {
    ...chartOptions,
    indexAxis: 'y',
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, stepSize: 1 } },
        y: { grid: { display: false }, ticks: { font: { size: 11 } } },
    },
    plugins: { legend: { display: false } },
};

const lineOptions = {
    ...chartOptions,
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 10 }, stepSize: 1, callback: (v) => Number.isInteger(v) ? v : '' } },
    },
    plugins: { legend: { display: false } },
};
</script>

<template>
    <Head title="Papan Pemuka Admin" />

    <AdminLayout>
        <section class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Papan Pemuka Admin</h1>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Ringkasan operasi dan visualisasi data untuk pemantauan pantas.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="card in stats"
                    :key="card.label"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="toneClasses[card.tone] || 'bg-teal-50 text-teal-700'">
                            <component :is="icons[card.icon] ?? Users" class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">{{ card.label }}</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-950">{{ card.value }}</p>
                    <p v-if="card.suffix" class="mt-1 text-xs text-slate-400">{{ card.suffix }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ card.description }}</p>
                </article>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Status Permohonan Borang</h2>
                    <p class="mt-1 text-sm text-slate-500">Pecahan jumlah permohonan mengikut status semasa.</p>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in submissionBars" :key="item.label" class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span class="font-medium text-slate-700">{{ item.label }}</span>
                                </div>
                                <span class="tabular-nums text-slate-500">{{ item.count }} <span class="text-slate-400">({{ item.pct }}%)</span></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: item.pct + '%', backgroundColor: item.color }"
                                />
                            </div>
                        </div>
                        <p v-if="submissionBars.length === 0" class="text-center text-sm text-slate-400">Tiada data permohonan.</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Status Aduan</h2>
                    <p class="mt-1 text-sm text-slate-500">Pecahan jumlah aduan mengikut status semasa.</p>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in complaintsBars" :key="item.label" class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span class="font-medium text-slate-700">{{ item.label }}</span>
                                </div>
                                <span class="tabular-nums text-slate-500">{{ item.count }} <span class="text-slate-400">({{ item.pct }}%)</span></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: item.pct + '%', backgroundColor: item.color }"
                                />
                            </div>
                        </div>
                        <p v-if="complaintsBars.length === 0" class="text-center text-sm text-slate-400">Tiada data aduan.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Permohonan Mengikut Unit</h2>
                    <p class="mt-1 text-sm text-slate-500">Jumlah permohonan diterima untuk setiap unit.</p>
                    <div v-if="charts.submissionsByUnit.labels.length" class="mt-4 overflow-hidden" :style="{ height: Math.max(160, charts.submissionsByUnit.labels.length * 48) + 'px' }">
                        <Bar :data="unitBar" :options="barOptions" />
                    </div>
                    <p v-else class="mt-6 text-center text-sm text-slate-400">Tiada data permohonan.</p>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Ahli Baharu Bulanan</h2>
                    <p class="mt-1 text-sm text-slate-500">Trend pendaftaran ahli baharu 6 bulan terkini.</p>
                    <div class="mt-4 h-64 overflow-hidden">
                        <Line :data="membersLine" :options="lineOptions" />
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>