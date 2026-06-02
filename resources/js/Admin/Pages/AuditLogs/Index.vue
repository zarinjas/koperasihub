<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, History, ShieldCheck } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminRowActions from '@/Shared/Components/AdminRowActions.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    auditLogs: { type: Object, required: true },
    actorOptions: { type: Array, required: true },
    actionOptions: { type: Array, required: true },
    subjectTypeOptions: { type: Array, required: true },
    selectedLog: { type: Object, default: null },
});

const filters = reactive({
    search: props.filters.search || '',
    actor: props.filters.actor || '',
    action: props.filters.action || '',
    subject_type: props.filters.subject_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const columns = [
    { key: 'created_at', label: 'Tarikh' },
    { key: 'actor_name', label: 'Pelaku' },
    { key: 'action_label', label: 'Tindakan' },
    { key: 'module_label', label: 'Modul' },
    { key: 'subject_label', label: 'Rekod' },
    { key: 'actions', label: 'Butiran' },
];

const hasSelectedLog = computed(() => Boolean(props.selectedLog));

const applyFilters = () => {
    router.get('/admin/audit-logs', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.actor = '';
    filters.action = '';
    filters.subject_type = '';
    filters.date_from = '';
    filters.date_to = '';
    applyFilters();
};

const getActions = (row) => [
    { label: 'Lihat', icon: Eye, href: row.show_url },
];
</script>

<template>
    <Head title="Log Audit" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Log Audit"
                description="Semak sejarah tindakan sensitif yang direkodkan dalam sistem. Paparan ini adalah untuk semakan sahaja."
            />

            <AdminFilterBar>
                <AdminSearchInput id="audit-search-filter" v-model="filters.search" placeholder="Cari pelaku, tindakan, atau modul" />
                <AdminSelectFilter id="audit-actor-filter" v-model="filters.actor" label="Pelaku" :options="actorOptions" />
                <AdminSelectFilter id="audit-action-filter" v-model="filters.action" label="Tindakan" :options="actionOptions" />
                <AdminSelectFilter id="audit-subject-type-filter" v-model="filters.subject_type" label="Modul" :options="subjectTypeOptions" />
                <TextInput id="audit-date-from" v-model="filters.date_from" type="date" label="Tarikh mula" />
                <TextInput id="audit-date-to" v-model="filters.date_to" type="date" label="Tarikh tamat" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="auditLogs.data.length === 0"
                title="Tiada rekod audit ditemui."
                description="Rekod audit akan dipaparkan di sini apabila tindakan sensitif direkodkan dalam sistem."
            />

            <DataTable v-else :columns="columns" :rows="auditLogs.data">
                <template #cell-created_at="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.created_at }}</p>
                        <p class="text-xs text-slate-500">ID #{{ row.id }}</p>
                    </div>
                </template>

                <template #cell-actor_name="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.actor_name }}</p>
                        <p class="text-xs text-slate-500">{{ row.actor_email || 'Tiada e-mel direkodkan' }}</p>
                    </div>
                </template>

                <template #cell-action_label="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.action_label }}</p>
                        <p class="text-xs text-slate-500">{{ row.action }}</p>
                    </div>
                </template>

                <template #cell-module_label="{ row }">
                    <span class="text-sm text-slate-700">{{ row.module_label }}</span>
                </template>

                <template #cell-subject_label="{ row }">
                    <span class="text-sm text-slate-700">{{ row.subject_label }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <AdminRowActions :actions="getActions(row)" />
                </template>
            </DataTable>

            <div v-if="auditLogs.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in auditLogs.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>

            <div
                v-if="hasSelectedLog"
                class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]"
            >
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                                <History class="h-5 w-5" />
                            </div>
                            <h2 class="text-xl font-semibold text-slate-950">{{ selectedLog.action_label }}</h2>
                            <p class="text-sm text-slate-600">{{ selectedLog.created_at }}</p>
                        </div>

                        <Button :as="Link" :href="selectedLog.index_url" variant="outline">Tutup Butiran</Button>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pelaku</p>
                            <p class="mt-2 font-semibold text-slate-950">{{ selectedLog.actor_name }}</p>
                            <p class="text-sm text-slate-600">{{ selectedLog.actor_email || 'Tiada e-mel direkodkan' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rekod</p>
                            <p class="mt-2 font-semibold text-slate-950">{{ selectedLog.subject_label }}</p>
                            <p class="text-sm text-slate-600">{{ selectedLog.subject_type || 'Tiada subjek khusus' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">IP Address</p>
                            <p class="mt-2 font-semibold text-slate-950">{{ selectedLog.ip_address || '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">ID Audit</p>
                            <p class="mt-2 font-semibold text-slate-950">#{{ selectedLog.id }}</p>
                            <p class="text-sm text-slate-600">Subjek ID: {{ selectedLog.subject_id || '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">User Agent</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ selectedLog.user_agent || '-' }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <ShieldCheck class="h-5 w-5 text-slate-500" />
                            <h3 class="text-lg font-semibold text-slate-950">Nilai Sebelum</h3>
                        </div>

                        <div v-if="Object.keys(selectedLog.old_values || {}).length" class="mt-4 space-y-3">
                            <div
                                v-for="(value, key) in selectedLog.old_values"
                                :key="`old-${key}`"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ key }}</p>
                                <pre class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{{ value }}</pre>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-600">Tiada nilai sebelum direkodkan.</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-950">Nilai Selepas</h3>

                        <div v-if="Object.keys(selectedLog.new_values || {}).length" class="mt-4 space-y-3">
                            <div
                                v-for="(value, key) in selectedLog.new_values"
                                :key="`new-${key}`"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ key }}</p>
                                <pre class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{{ value }}</pre>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-600">Tiada nilai selepas direkodkan.</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-950">Metadata</h3>

                        <div v-if="Object.keys(selectedLog.metadata || {}).length" class="mt-4 space-y-3">
                            <div
                                v-for="(value, key) in selectedLog.metadata"
                                :key="`meta-${key}`"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ key }}</p>
                                <pre class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{{ value }}</pre>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-600">Tiada metadata tambahan direkodkan.</p>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>