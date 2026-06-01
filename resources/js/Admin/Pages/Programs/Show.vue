<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, QrCode, ScanLine } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    program: { type: Object, required: true },
    rsvps: { type: Object, required: true },
    stats: { type: Object, required: true },
    canScanAttendance: { type: Boolean, default: false },
    canViewReports: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const columns = [
    { key: 'member_no', label: 'No. Ahli' },
    { key: 'member_name', label: 'Nama' },
    { key: 'response', label: 'Respon' },
    { key: 'checked_in_at', label: 'Masa Hadir' },
    { key: 'attendance_method', label: 'Kaedah' },
];

const methodLabel = (value) => ({
    admin_scan_member_qr: 'Imbas QR Ahli',
    member_scan_event_qr: 'Imbas QR Acara',
    manual_entry: 'Manual',
})[value] || '-';
</script>

<template>
    <Head :title="program.title" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader :title="program.title" description="Lihat maklumat program dan rekod kehadiran.">
                <template #actions>
                    <Button variant="outline" :as="Link" href="/admin/programs">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <div class="lg:col-span-3 space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Maklumat Program</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-sm text-slate-500">Kategori</p>
                                <p class="font-medium">{{ program.category ? program.category.charAt(0).toUpperCase() + program.category.slice(1) : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Jenis</p>
                                <StatusBadge :status="program.program_type" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Tarikh Mula</p>
                                <p class="font-medium">{{ program.start_date_human }}</p>
                            </div>
                            <div v-if="program.end_date_human">
                                <p class="text-sm text-slate-500">Tarikh Tamat</p>
                                <p class="font-medium">{{ program.end_date_human }}</p>
                            </div>
                            <div v-if="program.location">
                                <p class="text-sm text-slate-500">Lokasi</p>
                                <p class="font-medium">{{ program.location }}</p>
                            </div>
                            <div v-if="program.online_url">
                                <p class="text-sm text-slate-500">Pautan</p>
                                <a :href="program.online_url" target="_blank" class="font-medium text-teal-700 hover:underline">{{ program.online_url }}</a>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Status</p>
                                <StatusBadge :status="program.status" />
                            </div>
                            <div v-if="program.capacity">
                                <p class="text-sm text-slate-500">Kapasiti</p>
                                <p class="font-medium">{{ program.capacity }}</p>
                            </div>
                        </div>
                        <div v-if="program.description" class="mt-4">
                            <p class="text-sm text-slate-500">Penerangan</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm">{{ program.description }}</p>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex flex-row items-center justify-between">
                            <h2 class="text-lg font-semibold">Senarai Kehadiran</h2>
                            <div class="flex gap-2">
                                <Button v-if="canScanAttendance" variant="outline" size="sm" :as="Link" :href="`/admin/programs/${program.id}/attendance`">
                                    <ScanLine class="mr-1 h-4 w-4" />
                                    Urus Kehadiran
                                </Button>
                                <Button v-if="canViewReports" variant="outline" size="sm" :as="Link" :href="`/admin/programs/${program.id}/event-qr`">
                                    <QrCode class="mr-1 h-4 w-4" />
                                    QR Acara
                                </Button>
                            </div>
                        </div>

                        <EmptyState
                            v-if="rsvps.data.length === 0"
                            title="Tiada rekod kehadiran."
                            description="Belum ada ahli yang RSVP atau hadir program ini."
                        />
                        <DataTable v-else :columns="columns" :rows="rsvps.data">
                            <template #cell-response="{ row }">
                                <StatusBadge :status="row.response" />
                            </template>
                            <template #cell-attendance_method="{ row }">
                                <span class="text-sm text-slate-600">{{ methodLabel(row.attendance_method) }}</span>
                            </template>
                            <template #cell-checked_in_at="{ row }">
                                <span class="text-sm">{{ row.checked_in_at || '-' }}</span>
                            </template>
                        </DataTable>
                        <div v-if="rsvps.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
                            <Button
                                v-for="link in rsvps.links"
                                :key="`${link.label}-${link.url}`"
                                :as="link.url ? Link : 'button'"
                                :href="link.url || undefined"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                v-html="link.label"
                            />
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-base font-semibold">Ringkasan Kehadiran</h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-3xl font-bold text-teal-700">{{ stats.checked_in }}</p>
                                <p class="text-sm text-slate-500">Hadir</p>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-lg font-semibold">{{ stats.hadir }}</p>
                                    <p class="text-xs text-slate-500">RSVP Hadir</p>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-amber-600">{{ stats.mungkin }}</p>
                                    <p class="text-xs text-slate-500">Mungkin</p>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-red-600">{{ stats.tidak_hadir }}</p>
                                    <p class="text-xs text-slate-500">Tidak Hadir</p>
                                </div>
                            </div>
                            <div class="border-t border-slate-200 pt-3">
                                <p class="text-sm text-slate-500">Peratus Kehadiran (RSVP Hadir)</p>
                                <p class="text-xl font-semibold">{{ stats.attendance_percentage }}%</p>
                            </div>
                            <div v-if="stats.capacity" class="border-t border-slate-200 pt-3">
                                <p class="text-sm text-slate-500">Kapasiti Terisi</p>
                                <p class="text-xl font-semibold">{{ stats.capacity_percentage }}% ({{ stats.checked_in }}/{{ stats.capacity }})</p>
                            </div>
                            <p class="text-xs text-slate-400">Jumlah RSVP: {{ stats.total_rsvps }}</p>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>