<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    application: { type: Object, required: true },
    canReview: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
});

const reviewForm = useForm({ decision_notes: '' });
const incompleteForm = useForm({ decision_notes: '' });
const approvalForm = useForm({
    approved_amount: props.application.approved_amount || '',
    approved_tenure_months: props.application.approved_tenure_months || '',
    decision_notes: props.application.decision_notes || '',
});
const rejectForm = useForm({ rejection_reason: '', decision_notes: '' });
const closeForm = useForm({ decision_notes: '' });
const rejectOpen = ref(false);
const closeOpen = ref(false);

const canMoveToReview = computed(() => props.canReview && ['submitted', 'guarantor_accepted', 'incomplete_documents'].includes(props.application.status));
const canMarkIncomplete = computed(() => props.canReview && ['submitted', 'guarantor_accepted', 'under_review'].includes(props.application.status));
const canApproveNow = computed(() => props.canApprove && props.application.status === 'under_review');
const canRejectNow = computed(() => props.canApprove && props.application.status === 'under_review');
const canCancelNow = computed(() => props.canReview && ['submitted', 'guarantor_pending', 'guarantor_rejected', 'incomplete_documents'].includes(props.application.status));
const canCloseNow = computed(() => props.canReview && ['approved', 'rejected', 'cancelled'].includes(props.application.status));

const markUnderReview = () => reviewForm.post(`/admin/financing/applications/${props.application.id}/under-review`, { preserveScroll: true });
const markIncomplete = () => incompleteForm.post(`/admin/financing/applications/${props.application.id}/incomplete`, { preserveScroll: true });
const approve = () => approvalForm.post(`/admin/financing/applications/${props.application.id}/approve`, { preserveScroll: true });
const reject = () => rejectForm.post(`/admin/financing/applications/${props.application.id}/reject`, { preserveScroll: true, onSuccess: () => { rejectOpen.value = false; } });
const cancel = () => closeForm.post(`/admin/financing/applications/${props.application.id}/cancel`, { preserveScroll: true, onSuccess: () => { closeOpen.value = false; } });
const closeApplication = () => closeForm.post(`/admin/financing/applications/${props.application.id}/close`, { preserveScroll: true, onSuccess: () => { closeOpen.value = false; } });
</script>

<template>
    <Head :title="application.reference_no" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader title="Butiran Permohonan Pembiayaan" description="Semak maklumat pemohon, dokumen, penjamin, dan keputusan semakan.">
                <template #actions>
                    <Button :as="Link" href="/admin/financing/applications" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <StatusBadge :status="application.status" :label="application.status_label" />
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <FormSection title="Ringkasan Permohonan" description="Maklumat asas permohonan pembiayaan." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Rujukan</p><p class="mt-1 text-sm font-semibold text-slate-950">{{ application.reference_no }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p><p class="mt-1 text-sm text-slate-700">{{ application.submitted_at || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ application.category_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ application.product_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Dimohon</p><p class="mt-1 text-sm text-slate-700">{{ application.amount_requested_label }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ application.tenure_months }} bulan</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.purpose }}</p></div>
                    </FormSection>

                    <FormSection title="Maklumat Pemohon" description="Ringkasan profil ahli pemohon." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama</p><p class="mt-1 text-sm text-slate-700">{{ application.member.full_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli</p><p class="mt-1 text-sm text-slate-700">{{ application.member.member_no }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">E-mel</p><p class="mt-1 text-sm text-slate-700">{{ application.member.email || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Telefon</p><p class="mt-1 text-sm text-slate-700">{{ application.member.phone || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pekerjaan</p><p class="mt-1 text-sm text-slate-700">{{ application.member.occupation || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Majikan</p><p class="mt-1 text-sm text-slate-700">{{ application.member.employer_name || '-' }}</p></div>
                    </FormSection>

                    <FormSection title="Sejarah Pembiayaan Pemohon" description="Semakan pantas rekod permohonan pembiayaan sebelum ini." :columns="1">
                        <div class="space-y-3">
                            <article v-for="item in application.applicant_history" :key="item.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ item.reference_no }}</p>
                                        <p class="text-sm text-slate-600">{{ item.product_name || '-' }} · {{ item.amount_requested }}</p>
                                    </div>
                                    <StatusBadge :status="item.status" :label="item.status_label" />
                                </div>
                                <p class="mt-3 text-xs text-slate-500">Dihantar {{ item.submitted_at || '-' }} · Pegawai: {{ item.officer_name || '-' }}</p>
                            </article>
                        </div>
                    </FormSection>

                    <FormSection title="Dokumen Sokongan" description="Dokumen yang dihantar oleh pemohon." :columns="1">
                        <div v-if="application.documents.length" class="space-y-3">
                            <article v-for="document in application.documents" :key="document.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ document.label }}</p>
                                    <p class="text-sm text-slate-500">{{ document.file_name }} · {{ document.file_size_label }}</p>
                                </div>
                                <Button :as="Link" :href="document.download_url" variant="outline">
                                    <Download class="mr-2 h-4 w-4" />
                                    Muat Turun
                                </Button>
                            </article>
                        </div>
                        <p v-else class="text-sm text-slate-600">Tiada dokumen dimuat naik setakat ini.</p>
                    </FormSection>

                    <FormSection title="Penjamin" description="Status persetujuan dan tandatangan penjamin." :columns="1">
                        <div v-if="application.guarantors.length" class="space-y-4">
                            <article v-for="guarantor in application.guarantors" :key="guarantor.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ guarantor.name }}</p>
                                        <p class="text-sm text-slate-500">{{ guarantor.member_no || '-' }} · {{ guarantor.employee_no || 'Tiada no. staf' }}</p>
                                    </div>
                                    <StatusBadge :status="guarantor.status" :label="guarantor.status_label" />
                                </div>
                                <p v-if="guarantor.consent_text" class="mt-3 text-sm text-slate-600">{{ guarantor.consent_text }}</p>
                                <p v-if="guarantor.rejection_reason" class="mt-3 text-sm text-red-700">{{ guarantor.rejection_reason }}</p>
                                <div v-if="guarantor.signature_preview" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <img :src="guarantor.signature_preview" alt="Tandatangan Penjamin" class="h-24 rounded-2xl border border-slate-200 bg-white p-2" />
                                    <Button :as="Link" :href="guarantor.signature_download_url" variant="outline">
                                        <Download class="mr-2 h-4 w-4" />
                                        Muat Turun Tandatangan
                                    </Button>
                                </div>
                            </article>
                        </div>
                        <p v-else class="text-sm text-slate-600">Permohonan ini tidak memerlukan penjamin.</p>
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <FormSection title="Keputusan / Status" description="Maklumat semakan dan tindakan admin." :columns="1">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status Semasa</p>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-sm text-slate-700">{{ application.status_label }}</p>
                                    <StatusBadge :status="application.status" :label="application.status_label" />
                                </div>
                            </div>
                            <p class="text-sm text-slate-600">Amaun diluluskan: {{ application.approved_amount_label || '-' }}</p>
                            <p class="text-sm text-slate-600">Tempoh diluluskan: {{ application.approved_tenure_months ? `${application.approved_tenure_months} bulan` : '-' }}</p>
                            <p class="whitespace-pre-line text-sm text-slate-600">{{ application.decision_notes || 'Tiada catatan keputusan.' }}</p>
                            <p v-if="application.rejection_reason" class="whitespace-pre-line text-sm text-red-700">{{ application.rejection_reason }}</p>
                        </div>
                    </FormSection>

                    <FormSection title="Tindakan" description="Gunakan tindakan ini mengikut status semasa permohonan." :columns="1">
                        <div class="space-y-4">
                            <TextareaInput v-if="canMoveToReview" id="review-notes" v-model="reviewForm.decision_notes" label="Catatan Semakan" :error="reviewForm.errors.decision_notes" />
                            <Button v-if="canMoveToReview" type="button" class="w-full" @click="markUnderReview">Tandakan Dalam Semakan</Button>

                            <TextareaInput v-if="canMarkIncomplete" id="incomplete-notes" v-model="incompleteForm.decision_notes" label="Catatan Dokumen Tidak Lengkap" :error="incompleteForm.errors.decision_notes" />
                            <Button v-if="canMarkIncomplete" type="button" variant="outline" class="w-full" @click="markIncomplete">Tandakan Dokumen Tidak Lengkap</Button>

                            <div v-if="canApproveNow" class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <TextInput id="approved-amount" v-model="approvalForm.approved_amount" label="Amaun Diluluskan (RM)" type="number" :error="approvalForm.errors.approved_amount" />
                                <TextInput id="approved-tenure" v-model="approvalForm.approved_tenure_months" label="Tempoh Diluluskan (Bulan)" type="number" :error="approvalForm.errors.approved_tenure_months" />
                                <TextareaInput id="approval-notes" v-model="approvalForm.decision_notes" label="Catatan Keputusan" :error="approvalForm.errors.decision_notes" />
                                <Button type="button" class="w-full" @click="approve">Luluskan Permohonan</Button>
                            </div>

                            <Button v-if="canRejectNow" type="button" variant="destructive" class="w-full" @click="rejectOpen = true">Tolak Permohonan</Button>
                            <Button v-if="canCancelNow" type="button" variant="outline" class="w-full" @click="closeOpen = true">Batalkan Permohonan</Button>
                            <Button v-if="canCloseNow" type="button" variant="outline" class="w-full" @click="closeOpen = true">Tutup Permohonan</Button>
                        </div>
                    </FormSection>

                    <FormSection title="Audit Ringkas" description="Sejarah tindakan utama berkaitan permohonan ini." :columns="1">
                        <div class="space-y-3">
                            <article v-for="history in application.histories" :key="history.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ history.action }}</p>
                                        <p class="text-sm text-slate-500">{{ history.actor_name || 'Sistem' }}</p>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ history.created_at }}</p>
                                </div>
                                <p v-if="history.notes" class="mt-2 text-sm text-slate-600">{{ history.notes }}</p>
                            </article>
                        </div>
                    </FormSection>
                </div>
            </div>
        </section>

        <ConfirmDialog
            :open="rejectOpen"
            title="Tolak Permohonan"
            description="Nyatakan sebab penolakan untuk rujukan pemohon dan rekod admin."
            confirm-label="Sahkan Tolak"
            :loading="rejectForm.processing"
            variant="destructive"
            @cancel="rejectOpen = false"
            @confirm="reject"
        >
            <div class="space-y-4">
                <TextareaInput id="reject-reason" v-model="rejectForm.rejection_reason" label="Sebab Penolakan" :error="rejectForm.errors.rejection_reason" />
                <TextareaInput id="reject-notes" v-model="rejectForm.decision_notes" label="Catatan Admin" :error="rejectForm.errors.decision_notes" />
            </div>
        </ConfirmDialog>

        <ConfirmDialog
            :open="closeOpen"
            :title="canCloseNow ? 'Tutup Permohonan' : 'Batalkan Permohonan'"
            description="Tambahkan catatan jika perlu sebelum tindakan ini direkodkan."
            confirm-label="Sahkan"
            :loading="closeForm.processing"
            @cancel="closeOpen = false"
            @confirm="canCloseNow ? closeApplication() : cancel()"
        >
            <TextareaInput id="close-notes" v-model="closeForm.decision_notes" label="Catatan" :error="closeForm.errors.decision_notes" />
        </ConfirmDialog>
    </AdminLayout>
</template>
