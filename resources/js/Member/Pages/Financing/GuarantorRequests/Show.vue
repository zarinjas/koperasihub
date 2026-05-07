<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Ban, CheckCircle, Clock, FileText, HandCoins, Loader2, UserPlus, UserRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import SignaturePad from '@/Shared/Components/SignaturePad.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    guarantor: { type: Object, required: true },
});

const formatCurrency = (val) => {
    if (val == null) return '-';
    return 'RM ' + Number(val).toLocaleString('en-MY', { minimumFractionDigits: 0 });
};

const formatDate = (val) => {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const parseAnswers = () => {
    if (!props.guarantor.application?.custom_answers_json) return null;
    try {
        const data = props.guarantor.application.custom_answers_json;
        return typeof data === 'string' ? JSON.parse(data) : data;
    } catch {
        return null;
    }
};

const answers = computed(() => parseAnswers());

const formatAnswer = (key, value) => {
    if (value == null || value === '') return '-';
    if (Array.isArray(value)) return value.join(', ');
    return String(value);
};

const isPending = computed(() => {
    const s = props.guarantor.status;
    return s === 'pending' || s === 'menunggu_penjamin' || s === 'menunggu';
});

const showAcceptDialog = ref(false);
const showRejectDialog = ref(false);
const signature = ref('');
const rejectReason = ref('');
const processing = ref(false);

const respond = (action) => {
    processing.value = true;
    const data = { action };
    if (action === 'accepted' && signature.value) {
        data.signature = signature.value;
    }
    if (action === 'rejected' && rejectReason.value) {
        data.reason = rejectReason.value;
    }

    router.post(`/member/financing/guarantor-requests/${props.guarantor.id}`, data, {
        onFinish: () => {
            processing.value = false;
            showAcceptDialog.value = false;
            showRejectDialog.value = false;
        },
    });
};
</script>

<template>
    <Head title="Permintaan Penjamin" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permintaan Penjamin" description="Anda telah diminta untuk menjadi penjamin bagi permohonan pembiayaan ini.">
                <template #actions>
                    <StatusBadge :status="guarantor.status" :label="guarantor.status === 'pending' ? 'Menunggu' : guarantor.status" />
                </template>
            </PageHeader>

            <!-- Pemohon Info -->
            <FormSection title="Maklumat Pemohon" :columns="2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama Pemohon</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ guarantor.application?.member?.user?.name || guarantor.application?.member?.full_name || '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ guarantor.application?.member?.member_no || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ guarantor.application?.product?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ guarantor.application?.category?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Rujukan</p>
                    <p class="mt-1 text-sm font-semibold text-teal-700">{{ guarantor.application?.reference_no || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Permohonan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(guarantor.created_at) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jumlah Dimohon</p>
                    <p class="mt-1 text-sm font-semibold text-teal-700">
                        {{ formatCurrency(guarantor.application?.amount_requested) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ guarantor.application?.tenure_months || '-' }} bulan</p>
                </div>
                <div v-if="guarantor.application?.purpose" class="col-span-full">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ guarantor.application.purpose }}</p>
                </div>
            </FormSection>

            <!-- Form Answers -->
            <FormSection v-if="answers && Object.keys(answers).length > 0" title="Jawapan Borang Pemohon" description="Maklumat yang telah diisi oleh pemohon dalam borang permohonan.">
                <div v-for="(value, key) in answers" :key="key" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-950">{{ key }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-700">{{ formatAnswer(key, value) }}</p>
                </div>
            </FormSection>

            <!-- Actions -->
            <div v-if="isPending" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Tindakan Anda</h2>
                <p class="mt-1 text-sm text-slate-600">Sila sahkan sama ada anda bersetuju atau menolak untuk menjadi penjamin bagi permohonan ini.</p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <Button type="button" variant="outline" class="border-emerald-500 text-emerald-700 hover:bg-emerald-50" @click="showAcceptDialog = true">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Setuju
                    </Button>
                    <Button type="button" variant="outline" class="border-red-500 text-red-700 hover:bg-red-50" @click="showRejectDialog = true">
                        <Ban class="mr-2 h-4 w-4" />
                        Tolak
                    </Button>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <StatusBadge :status="guarantor.status" :label="guarantor.status === 'accepted' ? 'Anda telah bersetuju menjadi penjamin' : guarantor.status" />
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    {{ guarantor.status === 'accepted' ? 'Permohonan ini akan diteruskan ke peringkat seterusnya.' : 'Anda telah memberi maklum balas untuk permintaan ini.' }}
                </p>
            </div>
        </section>

        <!-- Accept Dialog -->
        <ConfirmDialog
            :open="showAcceptDialog"
            title="Setuju Menjadi Penjamin"
            description="Anda akan bersetuju untuk menjadi penjamin bagi permohonan ini. Tandatangan adalah pilihan."
            confirm-label="Ya, Saya Setuju"
            :variant="'default'"
            :loading="processing"
            @cancel="showAcceptDialog = false"
            @confirm="respond('accepted')"
        >
            <div class="space-y-4">
                <p class="text-sm text-slate-600">Tandatangan (pilihan):</p>
                <SignaturePad v-model="signature" />
            </div>
        </ConfirmDialog>

        <!-- Reject Dialog -->
        <ConfirmDialog
            :open="showRejectDialog"
            title="Tolak Menjadi Penjamin"
            description="Anda akan menolak untuk menjadi penjamin bagi permohonan ini. Sila nyatakan sebab penolakan (pilihan)."
            confirm-label="Tolak"
            variant="destructive"
            :loading="processing"
            @cancel="showRejectDialog = false"
            @confirm="respond('rejected')"
        >
            <div class="space-y-2">
                <label for="reject-reason" class="text-sm font-medium text-slate-800">Sebab Penolakan <span class="text-xs text-slate-500">(Pilihan)</span></label>
                <textarea
                    id="reject-reason"
                    v-model="rejectReason"
                    rows="3"
                    placeholder="Nyatakan sebab penolakan..."
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-950 placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20"
                />
            </div>
        </ConfirmDialog>
    </MemberLayout>
</template>
