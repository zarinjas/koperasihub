<script setup>
import { Download, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/Shared/Components/ui/button';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
});

const files = ref({});
const uploading = ref({});

const upload = (document) => {
    const file = files.value[document.id];
    if (!file) return;

    uploading.value[document.id] = true;
    const fd = new FormData();
    fd.append('file', file, file.name);

    router.post(document.upload_url, fd, {
        onFinish: () => {
            uploading.value[document.id] = false;
            files.value[document.id] = null;
        },
    });
};
</script>

<template>
    <div v-if="documents.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-950">Pakej Dokumen</h2>
            <p class="mt-1 text-sm text-slate-500">Muat turun dokumen, lengkapkan tandatangan atau cop jika perlu, kemudian muat naik semula.</p>
        </div>

        <div class="space-y-3">
            <div v-for="document in documents" :key="document.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">{{ document.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.code }}</p>
                        <p v-if="document.uploaded_file_name" class="mt-1 text-xs text-slate-600">
                            Dimuat naik: {{ document.uploaded_file_name }}
                        </p>
                        <p v-if="document.rejection_reason" class="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ document.rejection_reason }}
                        </p>
                    </div>
                    <StatusBadge :status="document.status" :label="document.status.replaceAll('_', ' ')" />
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Button v-if="document.generated" :as="'a'" :href="document.download_url" type="button" variant="outline" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Muat Turun
                    </Button>

                    <template v-if="document.requires_upload">
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
                            @change="(event) => files[document.id] = event.target.files?.[0] ?? null"
                        />
                        <Button type="button" size="sm" :disabled="!files[document.id] || uploading[document.id]" @click="upload(document)">
                            <Upload class="mr-2 h-4 w-4" />
                            {{ uploading[document.id] ? 'Memuat Naik...' : 'Muat Naik' }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
