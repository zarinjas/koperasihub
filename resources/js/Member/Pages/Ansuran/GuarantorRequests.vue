<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, UserCheck, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

defineProps({
    requests: { type: Array, required: true },
});

const rejectDialog = ref(false);
const rejectForm = useForm({ action: 'reject', reason: '' });
const selectedId = ref(null);

const accept = (id) => {
    router.post('/member/ansuran/guarantor-requests/' + id, { action: 'accept' }, { preserveScroll: true });
};

const openReject = (id) => {
    selectedId.value = id;
    rejectForm.reason = '';
    rejectDialog.value = true;
};

const doReject = () => {
    rejectForm.post('/member/ansuran/guarantor-requests/' + selectedId.value, {
        preserveScroll: true,
        onSuccess: () => rejectDialog.value = false,
    });
};
</script>

<template>
    <MemberLayout>
        <Head title="Permintaan Penjamin" />
        <section class="space-y-6">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <UserCheck class="h-6 w-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Permintaan Penjamin</h1>
                    <p class="text-sm text-slate-600">Senarai permintaan untuk menjadi penjamin Ansuran Mudah.</p>
                </div>
            </div>

            <div v-if="requests.length > 0" class="space-y-4">
                <div v-for="req in requests" :key="req.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-950">Permohonan: {{ req.application.application_no }}</p>
                            <div class="mt-2 space-y-1 text-sm text-slate-500">
                                <p><span class="font-medium text-slate-600">Ahli:</span> {{ req.application.member_name }}</p>
                                <p><span class="font-medium text-slate-600">Produk:</span> {{ req.application.product_name }} - {{ req.application.variant_name }}</p>
                                <p>
                                    <span class="font-medium text-slate-600">Harga:</span> RM {{ Number(req.application.full_price).toFixed(2) }}
                                    &middot; <span class="font-medium text-teal-700">RM {{ Number(req.application.monthly_amount).toFixed(2) }}</span>/bulan
                                    &middot; {{ req.application.tenure_months }} Bulan
                                </p>
                            </div>
                        </div>
                        <StatusBadge :status="req.status" :label="req.status_label" />
                    </div>
                    <div v-if="req.status === 'pending'" class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <Button size="sm" class="w-full sm:w-auto" @click="accept(req.id)"><CheckCircle class="mr-1 h-4 w-4" /> Setuju</Button>
                        <Button size="sm" variant="outline" class="w-full sm:w-auto" @click="openReject(req.id)"><XCircle class="mr-1 h-4 w-4" /> Tolak</Button>
                    </div>
                    <div v-if="req.rejection_reason" class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
                        Sebab penolakan: {{ req.rejection_reason }}
                    </div>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white py-16 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <UserCheck class="h-6 w-6" />
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-950">Tiada Permintaan Penjamin</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">Tiada permintaan penjamin yang perlu diluluskan pada masa ini.</p>
            </div>
        </section>

        <ConfirmDialog
            :open="rejectDialog"
            title="Tolak Permintaan Penjamin"
            description="Adakah anda pasti untuk menolak permintaan menjadi penjamin?"
            confirmLabel="Tolak"
            @confirm="doReject"
            @cancel="rejectDialog = false"
        >
            <div>
                <label class="text-sm font-medium text-slate-800">Sebab <span class="text-slate-400">(pilihan)</span></label>
                <textarea v-model="rejectForm.reason" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20" />
            </div>
        </ConfirmDialog>
    </MemberLayout>
</template>