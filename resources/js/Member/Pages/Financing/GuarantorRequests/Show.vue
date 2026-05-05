<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
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
            <PageHeader title="Maklum Balas Penjamin" description="Semak maklumat permohonan dan berikan persetujuan sebagai penjamin jika berkaitan.">
                <template #actions>
                    <StatusBadge :status="requestRecord.status" :label="requestRecord.status_label" />
                </template>
            </PageHeader>

            <FormSection title="Ringkasan Permintaan Penjamin" description="Maklumat selamat yang boleh disemak sebelum anda memberikan persetujuan." :columns="2">
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pemohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.applicant_name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Ahli Pemohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.applicant_member_no || '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.product_name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun Dimohon</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.amount_requested }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.tenure_months }} bulan</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Dihantar</p><p class="mt-1 text-sm text-slate-700">{{ requestRecord.submitted_at || '-' }}</p></div>
                <div class="md:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ requestRecord.purpose }}</p></div>
            </FormSection>

            <FormSection v-if="canRespond" title="Persetujuan Penjamin" description="Sahkan persetujuan dan tandatangan anda untuk menerima sebagai penjamin." :columns="1">
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <input v-model="form.consent" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700" />
                    <span class="text-sm text-slate-700">{{ consentText }}</span>
                </label>
                <p v-if="form.errors.consent" class="text-sm text-red-700">{{ form.errors.consent }}</p>
                <SignaturePad v-model="form.signature" :error="form.errors.signature" />
                <div class="flex flex-wrap gap-3">
                    <Button type="button" :disabled="form.processing" @click="submitAccept">Terima Sebagai Penjamin</Button>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="submitReject">Tolak</Button>
                </div>
                <TextareaInput id="rejection-reason" v-model="form.rejection_reason" label="Sebab Penolakan" :error="form.errors.rejection_reason" />
            </FormSection>

            <FormSection v-else title="Maklum Balas Direkodkan" description="Permintaan ini telah dijawab dan tidak boleh diubah semula." :columns="1">
                <p class="text-sm text-slate-700">{{ requestRecord.rejection_reason || 'Tiada catatan tambahan.' }}</p>
                <img v-if="requestRecord.signature_preview" :src="requestRecord.signature_preview" alt="Tandatangan Penjamin" class="h-24 rounded-2xl border border-slate-200 bg-white p-2" />
            </FormSection>
        </section>
    </MemberLayout>
</template>
