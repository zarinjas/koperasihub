<script setup>
import { Head, router } from '@inertiajs/vue3';
import { PenLine } from 'lucide-vue-next';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
import SignaturePad from '@/Shared/Components/SignaturePad.vue';

const props = defineProps({
    application_id: Number,
    application_no: String,
    agreement_content: String,
});

const signature = ref('');
const signing = ref(false);

const submit = () => {
    signing.value = true;
    const signatureHtml = signature.value
        ? `<div style="margin-top:20px;"><p><strong>Tandatangan Ahli:</strong></p><img src="${signature.value}" style="max-width:300px;" /></div>`
        : '';
    const fullContent = props.agreement_content + signatureHtml;

    router.post('/member/ansuran/applications/' + props.application_id + '/sign', {
        signed_content: fullContent,
    }, {
        preserveScroll: true,
        onFinish: () => signing.value = false,
    });
};
</script>

<template>
    <MemberLayout>
        <Head title="Tandatangani Perjanjian" />
        <section class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                    <PenLine class="h-6 w-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Tandatangani Perjanjian</h1>
                    <p class="text-sm text-slate-600">Sila baca dan tandatangani perjanjian Ansuran Mudah.</p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <PenLine class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Perjanjian Ansuran Mudah</h2>
                        <p class="text-sm text-slate-600">Permohonan {{ application_no }}</p>
                    </div>
                </div>
                <div class="mt-5 rounded-xl border border-slate-200 bg-white p-6 shadow-inner">
                    <div class="prose prose-sm max-w-none text-slate-950" v-html="agreement_content" />
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <PenLine class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Tandatangan Digital</h2>
                        <p class="text-sm text-slate-600">Sila berikan tandatangan digital anda di ruang yang disediakan.</p>
                    </div>
                </div>
                <div class="mt-5 space-y-4">
                    <SignaturePad v-model="signature" />
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="w-full sm:w-auto" @click="window.history.back()">Batal</Button>
                        <Button class="w-full sm:w-auto" :disabled="!signature || signing" @click="submit">
                            <PenLine class="mr-1 h-4 w-4" /> {{ signing ? 'Menghantar...' : 'Tandatangan & Sahkan' }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>
