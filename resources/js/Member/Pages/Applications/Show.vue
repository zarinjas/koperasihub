<script setup>
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle, FileCheck, FileX } from 'lucide-vue-next';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    submission: { type: Object, required: true },
    form: { type: Object, required: true },
    sections: { type: Array, required: true },
    stampedFile: { type: Object, default: null },
});

const needsStampedUpload = computed(() => props.form.submission_method === 'requires_stamped_upload');
const isPendingStamp = computed(() => props.submission.status === 'pending_stamp_upload');
const isRejected = computed(() => props.submission.status === 'rejected');
const isIncomplete = computed(() => props.submission.status === 'incomplete_documents');

const formatValue = (field) => {
    if (field.type === 'checkbox' && Array.isArray(field.value)) {
        return field.value.join(', ');
    }
    if (field.type === 'yes_no') {
        return field.value === 'yes' ? 'Ya' : field.value === 'no' ? 'Tidak' : '-';
    }
    if (field.type === 'agreement_checkbox') {
        return field.value ? 'Disahkan' : 'Tidak disahkan';
    }
    return field.value || '-';
};
</script>

<template>
    <Head :title="`Permohonan - ${submission.reference_no}`" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                :title="submission.reference_no"
                :description="form.title"
            >
                <template #actions>
                    <StatusBadge :status="submission.status" :label="submission.status_label" />
                </template>
            </PageHeader>

            <FormSection title="Maklumat Permohonan" description="Ringkasan maklumat asas permohonan anda." :columns="2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Rujukan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submission.reference_no }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Unit</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ form.category_name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submission.submitted_at || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Disemak</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submission.reviewed_at || '-' }}</p>
                </div>
            </FormSection>

            <div v-if="needsStampedUpload" class="rounded-3xl border p-6 shadow-sm" :class="stampedFile ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="stampedFile ? 'bg-emerald-100' : 'bg-amber-100'">
                        <FileCheck v-if="stampedFile" class="h-5 w-5 text-emerald-700" />
                        <FileX v-else class="h-5 w-5 text-amber-700" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold" :class="stampedFile ? 'text-emerald-900' : 'text-amber-900'">
                            {{ stampedFile ? 'Borang bercop telah dimuat naik' : 'Borang bercop belum dimuat naik' }}
                        </p>
                        <template v-if="stampedFile">
                            <p class="mt-1 text-sm text-emerald-800">
                                Fail: <span class="font-medium">{{ stampedFile.name }}</span>
                            </p>
                            <p class="text-sm text-emerald-800">
                                Dimuat naik pada: <span class="font-medium">{{ stampedFile.uploaded_at }}</span>
                            </p>
                        </template>
                        <div v-else class="mt-2 rounded-xl border border-amber-200 bg-amber-100/50 p-3 text-sm text-amber-800">
                            <p v-if="form.stamped_upload_instructions" class="whitespace-pre-line">{{ form.stamped_upload_instructions }}</p>
                            <p v-else>Sila muat naik borang bercop untuk melengkapkan permohonan anda. Rujuk arahan yang diberikan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isRejected || isIncomplete" class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <AlertTriangle class="h-5 w-5 text-red-700" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-900">
                            {{ isIncomplete ? 'Dokumen Tidak Lengkap' : 'Permohonan Ditolak' }}
                        </p>
                        <p class="mt-1 text-sm text-red-800">
                            Klik butang di atas untuk menghantar permohonan baharu.
                        </p>
                        <p class="mt-1 text-sm text-red-800 whitespace-pre-line">{{ submission.admin_notes || 'Tiada catatan tambahan.' }}</p>
                    </div>
                </div>
            </div>

            <div v-if="submission.status === 'approved'" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <CheckCircle class="h-5 w-5 text-emerald-700" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-emerald-900">Permohonan Diluluskan</p>
                        <p v-if="submission.admin_notes" class="mt-1 text-sm text-emerald-800 whitespace-pre-line">{{ submission.admin_notes }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <FormSection
                    v-for="section in sections"
                    :key="section.id"
                    :title="section.title"
                    :description="section.description"
                >
                    <div v-for="field in section.fields" :key="field.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">{{ field.label }}</p>
                        <p v-if="field.type === 'agreement_checkbox' && field.agreement_text" class="mt-2 text-sm leading-6 text-slate-600">{{ field.agreement_text }}</p>
                        <img
                            v-if="field.file?.is_signature && field.file.signature_data_url"
                            :src="field.file.signature_data_url"
                            alt="Tandatangan"
                            class="mt-3 h-28 rounded-xl border border-slate-200 bg-white p-2"
                        />
                        <div v-else-if="field.file" class="mt-3">
                            <p class="text-sm font-medium text-slate-700">{{ field.file.name }}</p>
                        </div>
                        <p v-else class="mt-2 text-sm leading-6 text-slate-700">{{ formatValue(field) }}</p>
                    </div>
                </FormSection>
            </div>

            <div v-if="submission.admin_notes && submission.status !== 'rejected' && submission.status !== 'incomplete_documents' && submission.status !== 'approved'" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Catatan Admin</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ submission.admin_notes }}</p>
            </div>
        </section>
    </MemberLayout>
</template>