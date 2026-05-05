<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, CheckCircle2, FileText } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SignaturePad from '@/Shared/Components/SignaturePad.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    requestRecord: { type: Object, required: true },
    consentText: { type: String, required: true },
});

const form = useForm({
    action: 'accept',
    consent: false,
    signature: '',
    rejection_reason: '',
});

const canRespond = computed(() => props.requestRecord.status === 'pending');

const submitAccept = () => {
    form.transform((data) => ({
        ...data,
        action: 'accept',
        consent_text: props.consentText,
    })).post(`/member/financing/guarantor-requests/${props.requestRecord.id}`, {
        preserveScroll: true,
    });
};

const submitReject = () => {
    form.transform((data) => ({
        ...data,
        action: 'reject',
        consent_text: props.consentText,
    })).post(`/member/financing/guarantor-requests/${props.requestRecord.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Maklum Balas Penjamin" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Maklum Balas Penjamin" description="Semak maklumat permohonan dengan teliti sebelum anda membuat keputusan sebagai penjamin.">
                <template #actions>
                    <Button :as="Link" href="/member/financing/guarantor-requests" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <StatusBadge :status="requestRecord.status" :label="requestRecord.status_label" />
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-6">
                    <FormSection title="Ringkasan Permintaan Penjamin" description="Maklumat selamat yang boleh disemak sebelum anda memberikan maklum balas." :columns="2">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pemohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.applicant_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli Pemohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.applicant_member_no || '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.product_name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Dimohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.amount_requested }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.tenure_months }} bulan</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.submitted_at || '-' }}</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ requestRecord.purpose }}</p></div>
                    </FormSection>

                    <FormSection v-if="canRespond" title="Persetujuan Penjamin" description="Lengkapkan langkah berikut jika anda bersetuju menjadi penjamin." :columns="1">
                        <div class="rounded-2xl border border-teal-100 bg-teal-50 p-4 text-sm text-teal-900">
                            <div class="flex items-start gap-3">
                                <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-teal-700" />
                                <div class="space-y-1">
                                    <p class="font-semibold">Sebelum anda menghantar maklum balas</p>
                                    <p>Sila pastikan anda memahami tujuan permohonan ini dan bersedia menyokong pemohon sebagai penjamin.</p>
                                </div>
                            </div>
                        </div>

                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <input v-model="form.consent" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700" />
                            <span class="text-sm text-slate-700">{{ consentText }}</span>
                        </label>
                        <p v-if="form.errors.consent" class="text-sm text-red-700">{{ form.errors.consent }}</p>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="mb-3 text-sm font-medium text-slate-900">Tandatangan Digital</p>
                            <SignaturePad v-model="form.signature" :error="form.errors.signature" />
                        </div>

                        <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-medium text-slate-900">Jika anda tidak bersetuju</p>
                            <TextareaInput id="rejection-reason" v-model="form.rejection_reason" label="Sebab Tidak Bersetuju" :error="form.errors.rejection_reason" />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Button type="button" class="sm:flex-1" :disabled="form.processing" @click="submitAccept">Setuju Menjadi Penjamin</Button>
                            <Button type="button" variant="outline" class="sm:flex-1" :disabled="form.processing" @click="submitReject">Tidak Bersetuju</Button>
                        </div>
                    </FormSection>

                    <FormSection v-else title="Maklum Balas Direkodkan" description="Permintaan ini telah dijawab dan tidak boleh diubah semula." :columns="1">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm text-slate-700">{{ requestRecord.rejection_reason || 'Tiada catatan tambahan direkodkan.' }}</p>
                        </div>
                        <div v-if="requestRecord.signature_preview" class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="mb-3 text-sm font-medium text-slate-900">Tandatangan Direkodkan</p>
                            <img :src="requestRecord.signature_preview" alt="Tandatangan penjamin" class="h-24 rounded-2xl border border-slate-200 bg-white p-2" />
                        </div>
                    </FormSection>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start gap-3">
                            <FileText class="mt-0.5 h-5 w-5 text-teal-700" />
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Panduan Ringkas</h2>
                                <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                                    <p>Semak nama pemohon, produk, amaun, tempoh, dan tujuan permohonan sebelum membuat keputusan.</p>
                                    <p>Jika bersetuju, tandatangan digital anda akan direkodkan sebagai bukti persetujuan.</p>
                                    <p>Jika tidak bersetuju, berikan sebab yang ringkas dan profesional untuk memudahkan tindakan susulan.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <EmptyState v-if="!canRespond && !requestRecord.signature_preview && !requestRecord.rejection_reason" title="Tiada catatan tambahan." description="Permintaan ini telah ditutup tanpa nota atau tandatangan tambahan dipaparkan." compact />
                </aside>
            </div>
        </section>
    </MemberLayout>
</template>
