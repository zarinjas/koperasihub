<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle, Printer, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    formRecord: { type: Object, required: true },
    submission: { type: Object, required: true },
});

const uploadErrors = ref({});

const uploadForm = useForm({
    stamped_file: null,
});

const submit = () => {
    uploadForm.post(props.submission.upload_url, {
        forceFormData: true,
        onError: (errors) => {
            uploadErrors.value = errors;
        },
    });
};
</script>

<template>
    <Head :title="`Tindakan Seterusnya - ${formRecord.title}`" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

                <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                            <CheckCircle class="h-6 w-6 text-emerald-700" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-slate-950">Borang berjaya diisi</h1>
                            <p class="mt-1 text-sm text-slate-600">
                                No. rujukan anda: <span class="font-semibold text-slate-900">{{ submission.reference_no }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-amber-200 bg-amber-50 p-6">
                    <h2 class="text-base font-semibold text-amber-900">Tindakan seterusnya</h2>
                    <p class="mt-2 text-sm leading-6 text-amber-800">
                        {{ formRecord.stamped_upload_instructions }}
                    </p>
                    <p class="mt-3 text-sm font-medium text-amber-900">
                        Tindakan seterusnya: Sila cetak borang yang telah dilengkapkan, dapatkan tandatangan serta cop pengesahan, kemudian muat naik semula borang tersebut untuk dihantar kepada koperasi.
                    </p>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Langkah 1: Cetak borang</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Cetak borang yang telah anda lengkapkan untuk mendapatkan cop dan tandatangan pengesahan.
                    </p>
                    <div class="mt-4">
                        <Button :as="'a'" :href="formRecord.print_url" target="_blank" variant="outline">
                            <Printer class="mr-2 h-4 w-4" />
                            Pratonton / Cetak Borang
                        </Button>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Langkah 2: Muat naik borang bercop</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Setelah mendapat cop dan tandatangan, imbas atau ambil gambar borang tersebut, kemudian muat naik di sini.
                    </p>

                    <form class="mt-4 space-y-4" @submit.prevent="submit">
                        <FileUploader
                            id="stamped-file"
                            v-model="uploadForm.stamped_file"
                            label="Borang bercop *"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            helper-text="Format disokong: PDF, JPG, JPEG, PNG, WEBP. Saiz maksimum 5MB."
                            :error="uploadForm.errors.stamped_file || uploadErrors.stamped_file"
                        />

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="uploadForm.processing || !uploadForm.stamped_file">
                                <Upload class="mr-2 h-4 w-4" />
                                {{ uploadForm.processing ? 'Memuat naik...' : 'Hantar Borang Bercop' }}
                            </Button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </PublicLayout>
</template>