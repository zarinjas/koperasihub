<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle2, Clock3, Download, History, Printer } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
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
const canMoveToReviewBlocked = computed(() => props.canReview && !canMoveToReview.value && ['pending_completed_form', 'guarantor_pending'].includes(props.application.status));
const canMarkIncomplete = computed(() => props.canReview && ['submitted', 'guarantor_accepted', 'under_review'].includes(props.application.status));
const canApproveNow = computed(() => props.canApprove && props.application.status === 'under_review' && props.application.is_ready_for_review);
const canRejectNow = computed(() => props.canApprove && props.application.status === 'under_review' && props.application.is_ready_for_review);
const canCancelNow = computed(() => props.canReview && ['submitted', 'pending_completed_form', 'guarantor_pending', 'guarantor_rejected', 'incomplete_documents'].includes(props.application.status));
const canCloseNow = computed(() => props.canReview && ['approved', 'rejected', 'cancelled'].includes(props.application.status));

const pendingGuarantors = computed(() => props.application.guarantors.filter((guarantor) => guarantor.status === 'pending').length);
const rejectedGuarantors = computed(() => props.application.guarantors.filter((guarantor) => guarantor.status === 'rejected').length);
const acceptedGuarantors = computed(() => props.application.guarantors.filter((guarantor) => guarantor.status === 'accepted').length);

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
            <PageHeader title="Butiran Permohonan Pembiayaan" description="Semak maklumat pemohon, dokumen, penjamin, dan keputusan semakan dengan lebih pantas.">
                <template #actions>
                    <Button :as="Link" :href="application.print_url" variant="outline">
                        <Printer class="mr-2 h-4 w-4" />
                        Cetak / Simpan PDF
                    </Button>
                    <Button :as="Link" href="/admin/financing/applications" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <StatusBadge :status="application.status" :label="application.status_label" />
                </template>
            </PageHeader>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">No. Rujukan</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.reference_no }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Amaun Dimohon</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.amount_requested_label }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Dokumen</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.documents.length }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Penjamin</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.guarantors.length ? `${acceptedGuarantors}/${application.guarantors.length} bersetuju` : 'Tidak perlu' }}</p>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <FormSection title="Ringkasan Permohonan" description="Maklumat asas permohonan pembiayaan." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p><p class="mt-1 text-sm text-slate-700">{{ application.submitted_at || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Unit</p><p class="mt-1 text-sm text-slate-700">{{ application.unit_name || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ application.category_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ application.product_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Dimohon</p><p class="mt-1 text-sm text-slate-700">{{ application.amount_requested_label }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ application.tenure_months }} bulan</p></div>
                        <div v-if="application.monthly_income"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pendapatan Bulanan</p><p class="mt-1 text-sm text-slate-700">{{ application.monthly_income }}</p></div>
                        <div v-if="application.monthly_commitment"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Komitmen Bulanan</p><p class="mt-1 text-sm text-slate-700">{{ application.monthly_commitment }}</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.purpose }}</p></div>
                        <div v-if="application.employment_notes" class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Pekerjaan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.employment_notes }}</p></div>
                    </FormSection>

                    <FormSection v-if="application.custom_answers && Object.keys(application.custom_answers).length" title="Maklumat Tambahan" description="Jawapan kepada soalan tambahan produk yang diisi oleh pemohon." :columns="2">
                        <template v-for="(value, key) in application.custom_answers" :key="key">
                            <div class="md:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ key }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ Array.isArray(value) ? value.join(', ') : (value || '-') }}</p>
                            </div>
                        </template>
                    </FormSection>

                    <FormSection title="Maklumat Pemohon" description="Ringkasan profil ahli pemohon." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama</p><p class="mt-1 text-sm text-slate-700">{{ application.member.full_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli</p><p class="mt-1 text-sm text-slate-700">{{ application.member.member_no }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">E-mel</p><p class="mt-1 text-sm text-slate-700">{{ application.member.email || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Telefon</p><p class="mt-1 text-sm text-slate-700">{{ application.member.phone || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pekerjaan</p><p class="mt-1 text-sm text-slate-700">{{ application.member.occupation || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Majikan</p><p class="mt-1 text-sm text-slate-700">{{ application.member.employer_name || '-' }}</p></div>
                    </FormSection>

                    <FormSection title="Dokumen Sokongan" description="Dokumen yang dihantar oleh pemohon untuk semakan admin." :columns="1">
                        <div v-if="application.documents.length" class="space-y-3">
                            <article v-for="document in application.documents" :key="document.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <p class="font-semibold text-slate-950">{{ document.label }}</p>
                                    <p class="text-sm text-slate-500">{{ document.file_name }} · {{ document.file_size_label }}</p>
                                    <p class="text-xs text-slate-500">Dimuat naik oleh {{ document.uploaded_by || 'Sistem' }} pada {{ document.uploaded_at || '-' }}</p>
                                </div>
                                <Button :as="Link" :href="document.download_url" variant="outline">
                                    <Download class="mr-2 h-4 w-4" />
                                    Muat Turun
                                </Button>
                            </article>
                        </div>
                        <EmptyState v-else title="Tiada dokumen sokongan." description="Pemohon belum memuat naik dokumen bagi permohonan ini." compact />
                    </FormSection>

                    <FormSection title="Borang Lengkap Bercop" description="Borang rasmi yang telah dicetak, ditandatangani, dan dicop oleh pihak berkaitan." :columns="1">
                        <div v-if="application.completed_form.uploaded" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-950">{{ application.completed_form.file_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">Dimuat naik pada {{ application.completed_form.uploaded_at || '-' }}</p>
                            </div>
                            <Button :as="Link" :href="application.completed_form.download_url" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Muat Turun Borang Lengkap
                            </Button>
                        </div>
                        <EmptyState v-else title="Borang lengkap bercop belum dimuat naik." description="Admin belum boleh memproses permohonan ini sehingga fail PDF rasmi dimuat naik oleh pemohon." compact />
                    </FormSection>

                    <FormSection title="Penjamin" description="Status persetujuan, masa respons, dan tandatangan penjamin." :columns="1">
                        <div v-if="application.guarantors.length" class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Menunggu maklum balas</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ pendingGuarantors }}</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Bersetuju</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ acceptedGuarantors }}</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Tidak bersetuju</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ rejectedGuarantors }}</p>
                                </article>
                            </div>

                            <article v-for="guarantor in application.guarantors" :key="guarantor.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ guarantor.name }}</p>
                                        <p class="text-sm text-slate-500">{{ guarantor.member_no || '-' }} · {{ guarantor.employee_no || 'Tiada nombor staf' }}</p>
                                        <p v-if="guarantor.responded_at" class="mt-1 text-xs text-slate-500">Maklum balas diterima pada {{ guarantor.responded_at }}</p>
                                    </div>
                                    <StatusBadge :status="guarantor.status" :label="guarantor.status_label" />
                                </div>
                                <p v-if="guarantor.consent_text" class="mt-3 text-sm text-slate-600">{{ guarantor.consent_text }}</p>
                                <p v-if="guarantor.rejection_reason" class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ guarantor.rejection_reason }}</p>
                                <div v-if="guarantor.signature_preview" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <img :src="guarantor.signature_preview" alt="Tandatangan penjamin" class="h-24 rounded-2xl border border-slate-200 bg-white p-2" />
                                    <Button :as="Link" :href="guarantor.signature_download_url" variant="outline">
                                        <Download class="mr-2 h-4 w-4" />
                                        Muat Turun Tandatangan
                                    </Button>
                                </div>
                            </article>
                        </div>
                        <EmptyState v-else title="Tiada penjamin diperlukan." description="Permohonan ini tidak memerlukan penjamin untuk diproses." compact />
                    </FormSection>

                    <FormSection title="Sejarah Pembiayaan Pemohon" description="Semakan pantas rekod permohonan pembiayaan sebelum ini." :columns="1">
                        <div v-if="application.applicant_history.length" class="space-y-3">
                            <article v-for="item in application.applicant_history" :key="item.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-slate-950">{{ item.reference_no }}</p>
                                            <StatusBadge v-if="item.is_current" status="active" label="Permohonan Semasa" />
                                        </div>
                                        <p class="text-sm text-slate-600">{{ item.product_name || '-' }} · {{ item.amount_requested }}</p>
                                    </div>
                                    <StatusBadge :status="item.status" :label="item.status_label" />
                                </div>
                                <div class="mt-3 grid gap-3 text-xs text-slate-500 sm:grid-cols-3">
                                    <p>Dihantar: {{ item.submitted_at || '-' }}</p>
                                    <p>Tarikh keputusan: {{ item.decision_date || '-' }}</p>
                                    <p>Pegawai: {{ item.officer_name || '-' }}</p>
                                </div>
                                <p v-if="item.approved_amount" class="mt-3 text-sm text-slate-600">Amaun diluluskan: {{ item.approved_amount }}</p>
                            </article>
                        </div>
                        <EmptyState v-else title="Tiada sejarah permohonan." description="Pemohon ini belum mempunyai rekod pembiayaan terdahulu dalam sistem." compact />
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <FormSection title="Kesiapsediaan Permohonan" description="Semak sama ada semua keperluan minimum telah dipenuhi sebelum tindakan semakan dibuat." :columns="1">
                        <div class="space-y-3">
                            <article v-for="item in application.readiness_checklist" :key="item.label" class="rounded-2xl border p-4" :class="item.complete ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-medium text-slate-950">{{ item.label }}</p>
                                    <StatusBadge :status="item.complete ? 'approved' : 'pending'" :label="item.complete ? 'Lengkap' : 'Belum lengkap'" />
                                </div>
                                <p class="mt-2 text-sm text-slate-700">{{ item.description }}</p>
                            </article>
                        </div>
                    </FormSection>

                    <FormSection title="Keputusan / Status" description="Maklumat semakan dan keputusan terkini untuk permohonan ini." :columns="1">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <Clock3 class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950">Status semasa</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ application.status_label }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="grid gap-3 text-sm text-slate-700">
                                    <p>Amaun diluluskan: {{ application.approved_amount_label || '-' }}</p>
                                    <p>Tempoh diluluskan: {{ application.approved_tenure_months ? `${application.approved_tenure_months} bulan` : '-' }}</p>
                                    <p>Tarikh semakan: {{ application.reviewed_at || '-' }}</p>
                                    <p>Tarikh keputusan: {{ application.approved_at || application.rejected_at || '-' }}</p>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Keputusan</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ application.decision_notes || 'Tiada catatan keputusan direkodkan.' }}</p>
                            </div>
                            <div v-if="application.rejection_reason" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-red-700">Sebab Penolakan</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-red-700">{{ application.rejection_reason }}</p>
                            </div>
                            <div v-if="application.cancellation_reason" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Maklumat Pembatalan</p>
                                <div class="mt-2 space-y-2 text-sm text-slate-700">
                                    <p class="whitespace-pre-line">{{ application.cancellation_reason }}</p>
                                    <p>Dibatalkan pada {{ application.cancelled_at || '-' }}</p>
                                    <p>Dibatalkan oleh {{ application.cancelled_by_name || '-' }}</p>
                                </div>
                            </div>
                            <div v-if="application.processing_blocked_reason" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                {{ application.processing_blocked_reason }}
                            </div>
                        </div>
                    </FormSection>

                    <FormSection title="Tindakan" description="Gunakan tindakan ini mengikut status semasa permohonan." :columns="1">
                        <div class="space-y-4">
                            <div v-if="canMoveToReview" class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-900">Tandakan untuk semakan</p>
                                <TextareaInput id="review-notes" v-model="reviewForm.decision_notes" label="Catatan Semakan" :error="reviewForm.errors.decision_notes" />
                                <Button type="button" class="w-full" @click="markUnderReview">Tandakan Dalam Semakan</Button>
                            </div>

                            <div v-else-if="canMoveToReviewBlocked && application.processing_blocked_reason" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                {{ application.processing_blocked_reason }}
                            </div>

                            <div v-if="canMarkIncomplete" class="space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-start gap-3">
                                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                                    <p class="text-sm text-amber-900">Gunakan pilihan ini jika pemohon perlu memuat naik dokumen tambahan sebelum semakan diteruskan.</p>
                                </div>
                                <TextareaInput id="incomplete-notes" v-model="incompleteForm.decision_notes" label="Catatan Dokumen Tambahan" :error="incompleteForm.errors.decision_notes" />
                                <Button v-if="canMarkIncomplete" type="button" variant="outline" class="w-full" @click="markIncomplete">Minta Dokumen Tambahan</Button>
                            </div>

                            <div v-if="canApproveNow" class="space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <div class="flex items-start gap-3">
                                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" />
                                    <p class="text-sm text-emerald-900">Isi amaun dan tempoh yang diluluskan sebelum menyelesaikan keputusan.</p>
                                </div>
                                <TextInput id="approved-amount" v-model="approvalForm.approved_amount" label="Amaun Diluluskan (RM)" type="number" :error="approvalForm.errors.approved_amount" />
                                <TextInput id="approved-tenure" v-model="approvalForm.approved_tenure_months" label="Tempoh Diluluskan (Bulan)" type="number" :error="approvalForm.errors.approved_tenure_months" />
                                <TextareaInput id="approval-notes" v-model="approvalForm.decision_notes" label="Catatan Keputusan" :error="approvalForm.errors.decision_notes" />
                                <Button type="button" class="w-full" @click="approve">Luluskan Permohonan</Button>
                            </div>

                            <Button v-if="canRejectNow" type="button" variant="destructive" class="w-full" @click="rejectOpen = true">Tolak Permohonan</Button>
                            <div v-else-if="props.canApprove && props.application.status === 'under_review' && application.processing_blocked_reason" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                {{ application.processing_blocked_reason }}
                            </div>
                            <Button v-if="canCancelNow" type="button" variant="outline" class="w-full" @click="closeOpen = true">Batalkan Permohonan</Button>
                            <Button v-if="canCloseNow" type="button" variant="outline" class="w-full" @click="closeOpen = true">Tutup Permohonan</Button>
                        </div>
                    </FormSection>

                    <FormSection title="Audit Ringkas" description="Jejak tindakan utama bagi permohonan ini." :columns="1">
                        <div v-if="application.histories.length" class="space-y-3">
                            <article v-for="history in application.histories" :key="history.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-teal-700">
                                            <History class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ history.action_label }}</p>
                                            <p class="text-sm text-slate-500">{{ history.actor_name || 'Sistem' }}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ history.created_at }}</p>
                                </div>
                                <p v-if="history.notes" class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ history.notes }}</p>
                            </article>
                        </div>
                        <EmptyState v-else title="Belum ada sejarah tindakan." description="Rekod audit ringkas akan dipaparkan di sini apabila tindakan diambil." compact />
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
