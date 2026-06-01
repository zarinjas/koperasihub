<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { QrCode } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    program: { type: Object, required: true },
    rsvps: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, required: true },
    responseOptions: { type: Array, required: true },
    memberSearchUrl: { type: String, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const searchFilters = reactive({
    search: props.filters.search || '',
    response: props.filters.response || '',
    checked_in: props.filters.checked_in || '',
});

const applying = ref(false);
const manualMemberNo = ref('');
const manualMemberSearchResults = ref([]);
const searchingMember = ref(false);
const memberSearchTimeout = ref(null);

const columns = [
    { key: 'member_no', label: 'No. Ahli' },
    { key: 'member_name', label: 'Nama' },
    { key: 'response', label: 'Respon' },
    { key: 'checked_in_at', label: 'Masa Hadir' },
    { key: 'attendance_method', label: 'Kaedah' },
    { key: 'notes', label: 'Catatan' },
];

const methodLabel = (value) => ({
    admin_scan_member_qr: 'Imbas QR',
    member_scan_event_qr: 'QR Acara',
    manual_entry: 'Manual',
})[value] || '-';

const applyFilters = () => {
    applying.value = true;
    router.get(`/admin/programs/${props.program.id}/attendance`, searchFilters, {
        preserveState: true,
        replace: true,
        onFinish: () => { applying.value = false; },
    });
};

const resetFilters = () => {
    searchFilters.search = '';
    searchFilters.response = '';
    searchFilters.checked_in = '';
    applyFilters();
};

const searchMember = () => {
    const q = manualMemberNo.value.trim();
    if (q.length < 2) {
        manualMemberSearchResults.value = [];
        return;
    }

    clearTimeout(memberSearchTimeout.value);
    searchingMember.value = true;

    memberSearchTimeout.value = setTimeout(async () => {
        try {
            const res = await fetch(`${props.memberSearchUrl}?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            manualMemberSearchResults.value = data.slice(0, 10);
        } catch {
            manualMemberSearchResults.value = [];
        } finally {
            searchingMember.value = false;
        }
    }, 300);
};

const recordAttendance = (memberId) => {
    router.post(`/admin/programs/${props.program.id}/attendance/manual`, {
        member_id: memberId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            manualMemberNo.value = '';
            manualMemberSearchResults.value = [];
        },
    });
};
</script>

<template>
    <Head :title="`Kehadiran - ${program.title}`" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader :title="`Kehadiran: ${program.title}`" description="Rekod kehadiran ahli untuk program ini.">
                <template #actions>
                    <div class="flex gap-2">
                        <Button variant="outline" :as="Link" :href="`/admin/programs/${program.id}/event-qr`">
                            <QrCode class="mr-2 h-4 w-4" />
                            QR Acara
                        </Button>
                        <Button variant="outline" :as="Link" :href="`/admin/programs/${program.id}`">
                            Kembali
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <div class="space-y-4">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-base font-semibold">Statistik</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-2xl font-bold text-teal-700">{{ stats.checked_in }}</p>
                                <p class="text-xs text-slate-500">Hadir</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-lg font-semibold">{{ stats.hadir }}</p>
                                    <p class="text-xs text-slate-500">RSVP Hadir</p>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold">{{ stats.total_rsvps }}</p>
                                    <p class="text-xs text-slate-500">Jumlah RSVP</p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400">Kehadiran: {{ stats.attendance_percentage }}%</p>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-base font-semibold">Rekod Kehadiran Manual</h3>
                        <div class="space-y-3">
                            <div>
                                <label for="member-search" class="text-sm font-medium text-slate-700">Cari Ahli</label>
                                <input
                                    id="member-search"
                                    v-model="manualMemberNo"
                                    placeholder="Nama atau No. ahli"
                                    class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    @input="searchMember"
                                />
                            </div>
                            <div v-if="searchingMember" class="text-sm text-slate-500">Mencari...</div>
                            <div v-if="manualMemberSearchResults.length > 0" class="space-y-2">
                                <button
                                    v-for="m in manualMemberSearchResults"
                                    :key="m.id"
                                    type="button"
                                    class="w-full rounded-lg border border-slate-200 p-3 text-left text-sm hover:bg-slate-50"
                                    @click="recordAttendance(m.id)"
                                >
                                    <p class="font-medium">{{ m.full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ m.member_no }} - {{ m.email }}</p>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="lg:col-span-3 space-y-4">
                    <AdminFilterBar>
                        <AdminSearchInput v-model="searchFilters.search" placeholder="Cari nama atau no. ahli" />
                        <AdminSelectFilter v-model="searchFilters.response" label="Respon" :options="responseOptions" />
                        <AdminSelectFilter v-model="searchFilters.checked_in" label="Kehadiran" :options="[
                            { value: '', label: 'Semua' },
                            { value: 'yes', label: 'Sudah Hadir' },
                            { value: 'no', label: 'Belum Hadir' },
                        ]" />
                        <template #actions>
                            <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                            <Button type="button" class="h-11" @click="applyFilters" :disabled="applying">Tapis</Button>
                        </template>
                    </AdminFilterBar>

                    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <EmptyState
                            v-if="rsvps.data.length === 0"
                            title="Tiada rekod kehadiran."
                            description="Gunakan borang di sebelah untuk merekod kehadiran ahli."
                        />
                        <DataTable v-else :columns="columns" :rows="rsvps.data">
                            <template #cell-response="{ row }">
                                <StatusBadge :status="row.response" />
                            </template>
                            <template #cell-checked_in_at="{ row }">
                                <span class="text-sm">{{ row.checked_in_at || '-' }}</span>
                            </template>
                            <template #cell-attendance_method="{ row }">
                                <span class="text-sm text-slate-600">{{ methodLabel(row.attendance_method) }}</span>
                            </template>
                            <template #cell-notes="{ row }">
                                <span class="text-sm text-slate-500">{{ row.notes || '-' }}</span>
                            </template>
                        </DataTable>
                        <div v-if="rsvps.links?.length > 3" class="flex flex-wrap gap-2 p-4">
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
            </div>
        </section>
    </AdminLayout>
</template>
