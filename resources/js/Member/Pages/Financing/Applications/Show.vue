<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    application: { type: Object, required: true },
    canUploadAdditionalDocuments: { type: Boolean, default: false },
});

const form = useForm({
    label: '',
    file: null,
});

const submit = () => {
    form.post(`/member/financing/applications/${props.application.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="application.reference_no" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader :title="application.reference_no" description="Semak status permohonan, dokumen, dan persetujuan penjamin.">
                <template #actions>
                    <StatusBadge :status="application.status" :label="application.status_label" />
                </template>
            </PageHeader>

            <FormSection title="Ringkasan Permohonan" description="Maklumat utama permohonan pembiayaan anda." :columns="2">
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ application.product_name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ application.category_name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun</p><p class="mt-1 text-sm text-slate-700">{{ application.amount_requested }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ application.tenure_months }} bulan</p></div>
                <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.purpose }}</p></div>
                <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Keputusan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ application.decision_notes || '-' }}</p></div>
                <div v-if="application.rejection_reason" class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-red-600">Sebab Penolakan</p><p class="mt-1 whitespace-pre-line text-sm text-red-700">{{ application.rejection_reason }}</p></div>
            </FormSection>

            <FormSection title="Dokumen Sokongan" description="Dokumen yang telah anda muat naik untuk permohonan ini." :columns="1">
                <div v-if="application.documents.length" class="space-y-3">
                    <article v-for="document in application.documents" :key="document.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-950">{{ document.label }}</p>
                            <p class="text-sm text-slate-500">{{ document.file_name }}</p>
                        </div>
                        <Button :as="Link" :href="document.download_url" variant="outline">
                            <Download class="mr-2 h-4 w-4" />
                            Muat Turun
                        </Button>
                    </article>
                </div>
                <p v-else class="text-sm text-slate-600">Tiada dokumen dimuat naik setakat ini.</p>
            </FormSection>

            <FormSection v-if="application.guarantors.length" title="Penjamin" description="Status maklum balas setiap penjamin yang dipilih." :columns="1">
                <div class="space-y-3">
                    <article v-for="guarantor in application.guarantors" :key="guarantor.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ guarantor.name }}</p>
                                <p class="text-sm text-slate-500">{{ guarantor.member_no || '-' }}</p>
                            </div>
                            <StatusBadge :status="guarantor.status" :label="guarantor.status_label" />
                        </div>
                        <p v-if="guarantor.rejection_reason" class="mt-3 text-sm text-red-700">{{ guarantor.rejection_reason }}</p>
                    </article>
                </div>
            </FormSection>

            <FormSection v-if="canUploadAdditionalDocuments" title="Muat Naik Dokumen Tambahan" description="Admin telah meminta dokumen tambahan untuk meneruskan semakan." :columns="1">
                <form class="space-y-4" @submit.prevent="submit">
                    <TextInput id="document-label" v-model="form.label" label="Label Dokumen" :error="form.errors.label" />
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-700" @change="form.file = $event.target.files?.[0] || null" />
                    <p v-if="form.errors.file" class="text-sm text-red-700">{{ form.errors.file }}</p>
                    <Button type="submit" :disabled="form.processing">Muat Naik Dokumen</Button>
                </form>
            </FormSection>
        </section>
    </MemberLayout>
</template>
