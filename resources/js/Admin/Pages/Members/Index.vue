<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Plus } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import AdminFilterActions from '@/Admin/Components/AdminFilterActions.vue';
import AdminFilterGrid from '@/Admin/Components/AdminFilterGrid.vue';
import AdminFilterPanel from '@/Admin/Components/AdminFilterPanel.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    members: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    canCreate: { type: Boolean, default: false },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const columns = [
    { key: 'member_no', label: 'No. Ahli' },
    { key: 'full_name', label: 'Maklumat Ahli' },
    { key: 'membership_status', label: 'Status' },
    { key: 'joined_at', label: 'Tarikh Sertai' },
    { key: 'user_name', label: 'Akaun Pengguna' },
    { key: 'actions', label: 'Tindakan' },
];

const applyFilters = () => {
    router.get('/admin/members', filters, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    applyFilters();
};
</script>

<template>
    <Head title="Ahli" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Ahli"
                description="Urus profil ahli, status keahlian, dan pautan akaun pengguna bagi rekod yang telah diluluskan."
            >
                <template #actions>
                    <Button v-if="canCreate" :as="Link" href="/admin/members/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Baharu
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <AdminFilterPanel>
                <AdminFilterGrid columns="xl:grid-cols-3">
                    <AdminSearchInput id="member-search-filter" v-model="filters.search" placeholder="Cari no. ahli, nama, nombor pengenalan, e-mel atau telefon" />
                    <AdminSelectFilter id="member-status-filter" v-model="filters.status" label="Status" :options="statusOptions" />
                    <AdminFilterActions>
                        <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                        <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                    </AdminFilterActions>
                </AdminFilterGrid>
            </AdminFilterPanel>

            <EmptyState
                v-if="members.data.length === 0"
                title="Tiada rekod ahli ditemui."
                description="Rekod ahli yang dicipta secara manual atau melalui kelulusan permohonan akan dipaparkan di sini."
            />

            <DataTable v-else :columns="columns" :rows="members.data">
                <template #cell-member_no="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.member_no }}</p>
                        <p class="text-xs text-slate-500">{{ row.identity_no || '-' }}</p>
                    </div>
                </template>

                <template #cell-full_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.full_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.email || '-' }} · {{ row.phone || '-' }}</p>
                    </div>
                </template>

                <template #cell-membership_status="{ row }">
                    <StatusBadge :status="row.membership_status" />
                </template>

                <template #cell-joined_at="{ row }">
                    <span class="text-sm text-slate-600">{{ row.joined_at || '-' }}</span>
                </template>

                <template #cell-user_name="{ row }">
                    <span class="text-sm text-slate-600">{{ row.user_name || 'Belum dipautkan' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <Button :as="Link" :href="row.show_url" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        Lihat
                    </Button>
                </template>
            </DataTable>

            <div v-if="members.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in members.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>
        </section>
    </AdminLayout>
</template>
