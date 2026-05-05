<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Clock, Download, FileUp, Info, Printer, ShieldAlert, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
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
    canUploadAdditionalDocuments: { type: Boolean, default: false },
    canUploadCompletedForm: { type: Boolean, default: false },
});

const additionalDocumentForm = useForm({
    label: '',
    file: null,
});

const completedForm = useForm({
    completed_form: null,
});

const cancelDialogOpen = ref(false);
const cancelForm = useForm({
    cancellation_reason: '',
});

const submitAdditionalDocument = () => {
    additionalDocumentForm.post(`/member/financing/applications/${props.application.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const submitCompletedForm = () => {
    completedForm.post(props.application.completed_form.upload_url, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const actionCard = computed(() => {
    const status = props.application.status;
    const map = {
        guarantor_pending: {
            tone: 'amber',
            icon: ShieldAlert,
            title: 'Menunggu Persetujuan Penjamin',
            message: 'Permohonan anda sedang menunggu semua penjamin yang dipilih memberikan persetujuan. Sila maklumkan penjamin anda untuk menyemak permintaan.',
        },
        pending_completed_form: {
            tone: 'amber',
            icon: Printer,
            title: 'Cetak dan Muat Naik Borang Lengkap Bercop',
            message: 'Sila pratonton dan cetak borang permohonan, dapatkan tandatangan serta cop pengesahan yang diperlukan, kemudian muat naik semula borang dalam format PDF.',
        },
        incomplete_documents: {
            tone: 'red',
            icon: AlertCircle,
            title: 'Dokumen Tambahan Diperlukan',
            message: 'Pihak admin memerlukan dokumen tambahan sebelum semakan boleh diteruskan. Sila semak dan muat naik dokumen yang diminta.',
        },
        under_review: {
            tone: 'blue',
            icon: Clock,
            title: 'Permohonan Sedang Disemak',
            message: 'Pihak koperasi sedang menyemak permohonan anda. Tiada tindakan diperlukan buat masa ini. Anda akan dimaklumkan apabila status berubah.',
        },
        submitted: {
            tone: 'blue',
            icon: Clock,
            title: 'Permohonan Sedang Diproses',
            message: 'Permohonan anda telah dihantar dan sedang menunggu semakan awal. Tiada tindakan diperlukan buat masa ini.',
        },
        guarantor_accepted: {
            tone: 'blue',
            icon: Clock,
            title: 'Sedia untuk Semakan',
            message: 'Semua penjamin telah bersetuju. Permohonan anda sedia untuk disemak oleh pihak koperasi.',
        },
        approved: {
            tone: 'green',
            icon: CheckCircle2,
            title: 'Permohonan Diluluskan',
            message: 'Tahniah! Permohonan pembiayaan anda telah diluluskan. Sila hubungi koperasi untuk maklumat lanjut mengenai proses seterusnya.',
        },
        rejected: {
            tone: 'red',
            icon: XCircle,
            title: 'Permohonan Ditolak',
            message: 'Permohonan anda tidak dapat diluluskan. Sila semak sebab penolakan di bawah atau hubungi koperasi untuk penerangan lanjut.',
        },
        cancelled: {
            tone: 'slate',
            icon: XCircle,
            title: 'Permohonan Dibatalkan',
            message: 'Permohonan ini telah dibatalkan dan tidak lagi boleh diproses.',
        },
        closed: {
            tone: 'slate',
            icon: Info,
            title: 'Permohonan Ditutup',
            message: 'Permohonan ini telah ditutup. Sila hubungi koperasi jika anda memerlukan maklumat lanjut.',
        },
    };
    return map[status] ?? {
        tone: 'slate',
        icon: Info,
        title: props.application.next_step.title,
        message: props.application.next_step.description,
    };
});

const actionCardClasses = computed(() => {
    const tone = actionCard.value.tone;
    return {
        wrapper: {
            amber: 'border-amber-200 bg-amber-50',
            red: 'border-red-200 bg-red-50',
            blue: 'border-blue-200 bg-blue-50',
            green: 'border-teal-200 bg-teal-50',
            slate: 'border-slate-200 bg-slate-50',
        }[tone],
        icon: {
            amber: 'text-amber-600',
            red: 'text-red-600',
            blue: 'text-blue-600',
            green: 'text-teal-700',
            slate: 'text-slate-500',
        }[tone],
        title: {
            amber: 'text-amber-900',
            red: 'text-red-900',
            blue: 'text-blue-900',
            green: 'text-teal-900',
            slate: 'text-slate-900',
        }[tone],
        message: {
            amber: 'text-amber-800',
            red: 'text-red-800',
            blue: 'text-blue-800',
            green: 'text-teal-800',
            slate: 'text-slate-700',
        }[tone],
    };
});

const cancelApplication = () => {
    cancelForm.post(props.application.cancel_url, {
        preserveScroll: true,
        onSuccess: () => {
            cancelDialogOpen.value = false;
            cancelForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="application.reference_no" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader :title="application.reference_no" description="Semak perkembangan permohonan, dokumen dimuat naik, dan langkah seterusnya untuk proses rasmi.">
                <template #actions>
                    <Button v-if="application.can_cancel" type="button" variant="outline" @click="cancelDialogOpen = true">
                        <XCircle class="mr-2 h-4 w-4" />
                        Batalkan Permohonan
                    </Button>
                    <Button :as="Link" :href="application.print_url" variant="outline">
                        <Printer class="mr-2 h-4 w-4" />
                        Pratonton / Cetak Borang
                    </Button>
                    <StatusBadge :status="application.status" :label="application.status_label" />
                </template>
            </PageHeader>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Status Semasa</p>
                    <div class="mt-3"><StatusBadge :status="application.status" :label="application.status_label" /></div>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Amaun Dimohon</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.amount_requested }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Tempoh</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ application.tenure_months }} bulan</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Borang Lengkap Bercop</p>
                    <p class="mt-3 text-sm font-semibold text-slate-950">{{ application.completed_form.uploaded ? 'Sudah dimuat naik' : 'Belum dimuat naik' }}</p>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <FormSection title="Ringkasan Permohonan" description="Maklumat utama permohonan pembiayaan anda." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ application.product_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ application.category_name }}</p></div>
                        <div v-if="application.approved_amount"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Diluluskan</p><p class="mt-1 text-sm text-slate-700">{{ application.approved_amount }}</p></div>
                        <div v-if="application.approved_tenure_months"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh Diluluskan</p><p class="mt-1 text-sm text-slate-700">{{ application.approved_tenure_months }} bulan</p></div>
                        <div v-if="application.monthly_income"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pendapatan Bulanan</p><p class="mt-1 text-sm text-slate-700">{{ application.monthly_income }}</p></div>
                        <div v-if="application.monthly_commitment"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Komitmen Bulanan</p><p class="mt-1 text-sm text-slate-700">{{ application.monthly_commitment }}</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.purpose }}</p></div>
                        <div v-if="application.employment_notes" class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Pekerjaan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.employment_notes }}</p></div>
                    </FormSection>

                    <FormSection title="Dokumen Sokongan" description="Dokumen yang telah direkodkan bagi permohonan ini." :columns="1">
                        <div v-if="application.documents.length" class="space-y-3">
                            <article v-for="document in application.documents" :key="document.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ document.label }}</p>
                                    <p class="text-sm text-slate-500">{{ document.file_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Direkodkan pada {{ document.uploaded_at || '-' }}</p>
                                </div>
                                <Button :as="Link" :href="document.download_url" variant="outline">
                                    <Download class="mr-2 h-4 w-4" />
                                    Muat Turun
                                </Button>
                            </article>
                        </div>
                        <EmptyState v-else title="Tiada dokumen dimuat naik." description="Dokumen yang anda muat naik akan dipaparkan di sini untuk semakan semula." compact />
                    </FormSection>

                    <FormSection v-if="application.product_documents.length" title="Dokumen Produk" description="Dokumen rujukan rasmi yang disediakan untuk produk pembiayaan ini." :columns="1">
                        <div class="space-y-3">
                            <article v-for="document in application.product_documents" :key="document.key" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ document.label }}</p>
                                    <p class="text-sm text-slate-500">{{ document.file_name }}</p>
                                </div>
                                <Button :as="Link" :href="document.download_url" variant="outline">
                                    <Download class="mr-2 h-4 w-4" />
                                    {{ document.download_label }}
                                </Button>
                            </article>
                        </div>
                    </FormSection>

                    <FormSection v-if="application.guarantors.length" title="Status Penjamin" description="Semak maklum balas setiap penjamin yang dipilih untuk permohonan ini." :columns="1">
                        <div class="space-y-3">
                            <article v-for="guarantor in application.guarantors" :key="guarantor.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ guarantor.name }}</p>
                                        <p class="text-sm text-slate-500">{{ guarantor.member_no || '-' }}</p>
                                        <p v-if="guarantor.responded_at" class="mt-1 text-xs text-slate-500">Dijawab pada {{ guarantor.responded_at }}</p>
                                    </div>
                                    <StatusBadge :status="guarantor.status" :label="guarantor.status_label" />
                                </div>
                                <p v-if="guarantor.rejection_reason" class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ guarantor.rejection_reason }}</p>
                            </article>
                        </div>
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <!-- Status-aware next action card -->
                    <FormSection title="Tindakan Diperlukan" description="Panduan tindakan berdasarkan status permohonan semasa anda." :columns="1">
                        <div class="space-y-4">
                            <!-- Action card -->
                            <div :class="['rounded-2xl border p-4', actionCardClasses.wrapper]">
                                <div class="flex items-start gap-3">
                                    <component :is="actionCard.icon" :class="['mt-0.5 h-5 w-5 shrink-0', actionCardClasses.icon]" />
                                    <div>
                                        <p :class="['font-semibold', actionCardClasses.title]">{{ actionCard.title }}</p>
                                        <p :class="['mt-1 text-sm leading-6', actionCardClasses.message]">{{ actionCard.message }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Status timeline -->
                            <div v-if="application.histories.length" class="space-y-0 rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Perkembangan Permohonan</p>
                                <ol class="relative ml-3 border-l border-slate-200">
                                    <li v-for="(history, index) in application.histories" :key="history.id" :class="['pb-4 pl-5', index === application.histories.length - 1 ? '' : '']">
                                        <span class="absolute -left-[9px] flex h-[18px] w-[18px] items-center justify-center rounded-full bg-white ring-2" :class="index === 0 ? 'ring-teal-500' : 'ring-slate-200'">
                                            <span :class="['h-2 w-2 rounded-full', index === 0 ? 'bg-teal-500' : 'bg-slate-300']" />
                                        </span>
                                        <p class="text-sm font-semibold text-slate-950">{{ history.action_label }}</p>
                                        <p class="text-xs text-slate-500">{{ history.created_at }} · {{ history.actor_name || 'Sistem' }}</p>
                                        <p v-if="history.notes" class="mt-1 text-xs leading-5 text-slate-600">{{ history.notes }}</p>
                                    </li>
                                </ol>
                            </div>

                            <!-- Completed form section -->
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Borang Lengkap Bercop</p>
                                <p class="mt-2 text-sm text-slate-700">
                                    {{ application.completed_form.uploaded ? `Dimuat naik pada ${application.completed_form.uploaded_at || '-'}` : 'Belum dimuat naik.' }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <Button :as="Link" :href="application.print_url" variant="outline">
                                        <Printer class="mr-2 h-4 w-4" />
                                        Pratonton / Cetak Borang
                                    </Button>
                                    <Button v-if="application.completed_form.download_url" :as="Link" :href="application.completed_form.download_url" variant="outline">
                                        <Download class="mr-2 h-4 w-4" />
                                        Muat Turun Borang Lengkap
                                    </Button>
                                </div>
                            </div>

                            <form v-if="canUploadCompletedForm" class="space-y-4 rounded-2xl border border-teal-200 bg-teal-50 p-4" @submit.prevent="submitCompletedForm">
                                <p class="text-sm font-medium text-slate-950">Muat Naik Borang Lengkap Bercop</p>
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-4">
                                    <label class="block text-sm font-medium text-slate-800" for="completed-form-file">Fail PDF</label>
                                    <input id="completed-form-file" type="file" accept=".pdf,application/pdf" class="mt-3 block w-full text-sm text-slate-700" @change="completedForm.completed_form = $event.target.files?.[0] || null" />
                                    <p class="mt-2 text-xs text-slate-500">Format PDF sahaja. Saiz maksimum 10MB.</p>
                                </div>
                                <p v-if="completedForm.errors.completed_form" class="text-sm text-red-700">{{ completedForm.errors.completed_form }}</p>
                                <Button type="submit" :disabled="completedForm.processing">
                                    <FileUp class="mr-2 h-4 w-4" />
                                    {{ completedForm.processing ? 'Memuat naik...' : (application.completed_form.uploaded ? 'Ganti Borang Lengkap' : 'Muat Naik Borang Lengkap Bercop') }}
                                </Button>
                            </form>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Keputusan</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ application.decision_notes || 'Belum ada catatan keputusan direkodkan.' }}</p>
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
                                    <p v-if="application.cancelled_by_name">Direkodkan oleh {{ application.cancelled_by_name }}</p>
                                </div>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection v-if="canUploadAdditionalDocuments" title="Muat Naik Dokumen Tambahan" description="Pihak admin memerlukan dokumen tambahan sebelum semakan boleh diteruskan." :columns="1">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Sila namakan dokumen dengan jelas supaya semakan lebih cepat, contohnya slip gaji, penyata bank, atau surat sokongan.
                        </div>

                        <form class="space-y-4" @submit.prevent="submitAdditionalDocument">
                            <TextInput id="document-label" v-model="additionalDocumentForm.label" label="Label Dokumen" :error="additionalDocumentForm.errors.label" />
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                <label class="block text-sm font-medium text-slate-800" for="additional-document-file">Fail Dokumen</label>
                                <input id="additional-document-file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-3 block w-full text-sm text-slate-700" @change="additionalDocumentForm.file = $event.target.files?.[0] || null" />
                                <p class="mt-2 text-xs text-slate-500">Format dibenarkan: PDF, JPG, JPEG, PNG, dan WEBP.</p>
                            </div>
                            <p v-if="additionalDocumentForm.errors.file" class="text-sm text-red-700">{{ additionalDocumentForm.errors.file }}</p>
                            <Button type="submit" :disabled="additionalDocumentForm.processing">
                                <FileUp class="mr-2 h-4 w-4" />
                                {{ additionalDocumentForm.processing ? 'Memuat naik...' : 'Muat Naik Dokumen' }}
                            </Button>
                        </form>
                    </FormSection>

                </div>
            </div>
        </section>
    </MemberLayout>

    <ConfirmDialog
        :open="cancelDialogOpen"
        title="Batalkan Permohonan"
        description="Sila nyatakan sebab pembatalan. Tindakan ini akan direkodkan dan permohonan tidak boleh diproses semula."
        confirm-label="Sahkan Pembatalan"
        :loading="cancelForm.processing"
        variant="destructive"
        @cancel="cancelDialogOpen = false"
        @confirm="cancelApplication"
    >
        <TextareaInput
            id="cancellation-reason"
            v-model="cancelForm.cancellation_reason"
            label="Sebab Pembatalan"
            :error="cancelForm.errors.cancellation_reason"
        />
    </ConfirmDialog>
</template>
