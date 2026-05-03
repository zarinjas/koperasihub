<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquarePlus } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    memberLinked: { type: Boolean, default: true },
    complaints: { type: Array, required: true },
});

const columns = [
    { key: 'ticket_no', label: 'No. tiket' },
    { key: 'subject', label: 'Butiran' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Keutamaan' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];
</script>

<template>
    <Head title="Aduan Saya" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Aduan Saya"
                description="Hantar aduan atau cadangan, kemudian semak maklum balas dan status terkini."
            >
                <template #actions>
                    <Button :as="Link" href="/member/complaints/create">
                        <MessageSquarePlus class="mr-2 h-4 w-4" />
                        Hantar Aduan
                    </Button>
                </template>
            </PageHeader>

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Akaun anda belum dipautkan kepada rekod ahli, tetapi anda masih boleh menghantar aduan atau cadangan melalui portal ini.
            </div>

            <EmptyState
                v-if="complaints.length === 0"
                title="Belum ada aduan atau cadangan."
                description="Hantar rekod pertama jika anda memerlukan bantuan atau ingin berkongsi cadangan."
                action-label="Hantar Aduan"
                action-href="/member/complaints/create"
            />

            <DataTable v-else :columns="columns" :rows="complaints">
                <template #cell-ticket_no="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.ticket_no }}</p>
                        <p class="text-xs text-slate-500">{{ row.category_label }}</p>
                    </div>
                </template>

                <template #cell-subject="{ row }">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ row.subject }}</p>
                        <p class="text-xs text-slate-500">{{ row.visible_replies_count }} balasan</p>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-priority="{ row }">
                    <StatusBadge :status="row.priority" />
                </template>

                <template #cell-actions="{ row }">
                    <Button :as="Link" :href="row.show_url" variant="outline">Lihat</Button>
                </template>
            </DataTable>
        </section>
    </MemberLayout>
</template>
