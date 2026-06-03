<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Download, FileCheck, FileX } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormDocumentHeader from '@/Shared/Components/FormDocumentHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    submissionRecord: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
});

const isStampedForm = computed(() => props.submissionRecord.submission_method === 'requires_stamped_upload');

const form = useForm({
    status: props.submissionRecord.submission.status,
    admin_notes: props.submissionRecord.submission.admin_notes || '',
});

const submit = () => form.patch(`/admin/forms/${props.submissionRecord.form.id}/submissions/${props.submissionRecord.submission.id}`, { preserveScroll: true });

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
    if (['address_my', 'member_address'].includes(field.type) && typeof field.value === 'object' && field.value) {
        const parts = [field.value.line1];
        if (field.value.line2) parts.push(field.value.line2);
        if (field.value.postcode) parts.push(field.value.postcode + (field.value.city ? ` ${field.value.city}` : ''));
        else if (field.value.city) parts.push(field.value.city);
        if (field.value.state) parts.push(field.value.state);
        return parts.filter(Boolean).join(', ') || '-';
    }
    return field.value || '-';
};

const statusLabel = (status) => {
    const map = {
        draft: 'Draf',
        pending_stamp_upload: 'Menunggu Borang Bercop',
        submitted: 'Dihantar',
        under_review: 'Dalam Proses',
        incomplete_documents: 'Dokumen Tidak Lengkap',
        approved: 'Diluluskan',
        rejected: 'Ditolak',
        closed: 'Ditutup',
    };
    return map[status] || status;
};

const isPendingStampUpload = computed(() => props.submissionRecord.submission.status === 'pending_stamp_upload');
</script>

<template>
    <Head :title="`Submission ${submissionRecord.submission.reference_no}`" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="submissionRecord.submission.reference_no"
                description="Semakan lengkap jawapan borang, fail sokongan, tandatangan, dan catatan tindakan admin."
            >
                <template #actions>
                    <StatusBadge :status="submissionRecord.submission.status" :label="statusLabel(submissionRecord.submission.status)" />
                    <Button :as="Link" :href="submissionRecord.print_url" variant="outline">Cetak / Simpan sebagai PDF</Button>
                </template>
            </PageHeader>

            <FormDocumentHeader
                v-if="submissionRecord.form.show_document_header"
                :form-record="{
                    ...submissionRecord.form,
                    effective_date: submissionRecord.form.effective_date ? new Date(submissionRecord.form.effective_date).toLocaleDateString('ms-MY') : ''
                }"
            />

            <FormSection title="Maklumat Submission" description="Ringkasan asas penghantaran untuk rujukan admin." :columns="2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama penghantar</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submissionRecord.submission.submitted_by_name || submissionRecord.submission.member?.full_name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Emel penghantar</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submissionRecord.submission.submitted_by_email || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh dihantar</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submissionRecord.submission.submitted_at }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Ahli berkaitan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submissionRecord.submission.member?.full_name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kaedah penghantaran</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ submissionRecord.submission_method === 'requires_stamped_upload' ? 'Perlu Borang Bercop' : 'Hantar Online Sahaja' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Unit Bertanggungjawab</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ submissionRecord.submission_unit_name || '-' }}</p>
                </div>
            </FormSection>

            <div v-if="isStampedForm" class="rounded-3xl border p-6 shadow-sm" :class="submissionRecord.has_stamped_file ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="submissionRecord.has_stamped_file ? 'bg-emerald-100' : 'bg-amber-100'">
                        <FileCheck v-if="submissionRecord.has_stamped_file" class="h-5 w-5 text-emerald-700" />
                        <FileX v-else class="h-5 w-5 text-amber-700" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold" :class="submissionRecord.has_stamped_file ? 'text-emerald-900' : 'text-amber-900'">
                            {{ submissionRecord.has_stamped_file ? 'Borang bercop telah dimuat naik' : 'Borang bercop belum dimuat naik' }}
                        </p>
                        <template v-if="submissionRecord.has_stamped_file">
                            <p class="mt-1 text-sm text-emerald-800">
                                Fail: <span class="font-medium">{{ submissionRecord.stamped_file_original_name }}</span>
                            </p>
                            <p class="text-sm text-emerald-800">
                                Dimuat naik pada: <span class="font-medium">{{ submissionRecord.stamped_file_uploaded_at }}</span>
                            </p>
                            <div class="mt-3">
                                <Button :as="Link" :href="submissionRecord.stamped_file_download_url" variant="outline">
                                    <Download class="mr-2 h-4 w-4" />
                                    Muat Turun Borang Bercop
                                </Button>
                            </div>
                        </template>
                        <p v-else class="mt-1 text-sm text-amber-800">
                            Pengguna belum muat naik borang bercop. Submission kekal sebagai <strong>Menunggu Borang Bercop</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <FormSection
                    v-for="section in submissionRecord.sections"
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
                            <Button :as="Link" :href="field.file.download_url" variant="outline">{{ field.file.name }}</Button>
                        </div>
                        <p v-else class="mt-2 text-sm leading-6 text-slate-700">{{ formatValue(field) }}</p>
                    </div>
                </FormSection>
            </div>

            <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-slate-950">Tindakan Admin</h2>
                <div v-if="isStampedForm && isPendingStampUpload" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Submission ini masih menunggu borang bercop. Admin tidak boleh meluluskan submission sebelum borang bercop dimuat naik oleh pengguna.
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <SelectInput id="submission-status-select" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                    <div />
                    <div class="md:col-span-2">
                        <TextareaInput id="submission-notes" v-model="form.admin_notes" label="Catatan admin" :error="form.errors.admin_notes" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan Tindakan' }}</Button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>