<script setup>
import { ref } from 'vue';
import { Upload, FileText, Download, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    supportingDocuments: { type: Array, default: () => [] },
    existingUploads: { type: Array, default: () => [] },
    uploadUrl: { type: String, default: '' },
    csrfToken: { type: String, default: '' },
});

const emit = defineEmits(['uploaded']);

const uploading = ref({});
const error = ref('');

const getUploadsForDoc = (docId) => {
    return (props.existingUploads || []).filter((u) => u.supporting_document_id === docId);
};

const getUploadedIndices = (docId) => {
    return new Set(getUploadsForDoc(docId).map((u) => u.upload_index));
};

const handleUpload = async (docId, index = 1, event) => {
    const file = event.target?.files?.[0];
    if (!file) return;

    const key = `${docId}-${index}`;
    uploading.value[key] = true;
    error.value = '';

    const fd = new FormData();
    fd.append('financing_supporting_document_id', docId);
    fd.append('upload_index', index);
    fd.append('file', file);

    try {
        const r = await fetch(props.uploadUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
            },
            body: fd,
        });

        const data = await r.json();
        if (r.ok && data.ok) {
            emit('uploaded', data.upload);
            event.target.value = '';
        } else {
            error.value = data.message || 'Ralat memuat naik.';
        }
    } catch {
        error.value = 'Ralat rangkaian.';
    }
    uploading.value[key] = false;
};
</script>

<template>
    <div v-if="supportingDocuments.length" class="space-y-4">
        <div class="flex items-center gap-2">
            <FileText class="h-4 w-4 text-slate-500" />
            <h3 class="text-sm font-semibold text-slate-800">Dokumen Sokongan</h3>
        </div>

        <div v-for="doc in supportingDocuments" :key="doc.id"
            class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <p class="text-sm font-medium text-slate-900">
                        {{ doc.name }}
                        <span v-if="doc.is_required" class="ml-1 text-red-500">*</span>
                    </p>
                    <p v-if="doc.description" class="mt-0.5 text-xs text-slate-500">{{ doc.description }}</p>
                </div>
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500">{{ doc.accepted_types }} · {{ doc.max_size_kb }}KB</span>
            </div>

            <!-- Monthly mode: numbered upload slots -->
            <template v-if="doc.mode === 'monthly'">
                <div class="space-y-2">
                    <div v-for="i in doc.count" :key="i"
                        class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                        <span class="text-xs font-medium text-slate-500 w-10 shrink-0">{{ i }}/{{ doc.count }}</span>
                        <template v-for="upload in getUploadsForDoc(doc.id)" :key="upload.id">
                            <template v-if="upload.upload_index === i">
                                <FileText class="h-4 w-4 shrink-0 text-green-600" />
                                <span class="flex-1 text-xs text-slate-700 truncate">{{ upload.file_name }}</span>
                                <a :href="upload.download_url" target="_blank"
                                    class="shrink-0 text-teal-600 hover:text-teal-700">
                                    <Download class="h-4 w-4" />
                                </a>
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-green-500" />
                            </template>
                        </template>
                        <template v-if="!getUploadedIndices(doc.id).has(i)">
                            <Upload class="h-4 w-4 shrink-0 text-slate-400" />
                            <input type="file"
                                :accept="doc.accepted_types.split(',').map(t => '.' + t.trim()).join(',')"
                                class="flex-1 text-xs text-slate-500 file:mr-2 file:rounded file:border-0 file:bg-teal-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-teal-700"
                                :disabled="uploading[`${doc.id}-${i}`]"
                                @change="handleUpload(doc.id, i, $event)" />
                            <span v-if="uploading[`${doc.id}-${i}`]" class="text-xs text-slate-400">...</span>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Single mode -->
            <template v-else-if="doc.mode === 'single'">
                <template v-if="getUploadsForDoc(doc.id).length">
                    <div v-for="upload in getUploadsForDoc(doc.id)" :key="upload.id"
                        class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                        <FileText class="h-4 w-4 shrink-0 text-green-600" />
                        <span class="flex-1 text-xs text-slate-700 truncate">{{ upload.file_name }}</span>
                        <a :href="upload.download_url" target="_blank"
                            class="shrink-0 text-teal-600 hover:text-teal-700">
                            <Download class="h-4 w-4" />
                        </a>
                        <CheckCircle2 class="h-4 w-4 shrink-0 text-green-500" />
                    </div>
                </template>
                <div v-else class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                    <Upload class="h-4 w-4 shrink-0 text-slate-400" />
                    <input type="file"
                        :accept="doc.accepted_types.split(',').map(t => '.' + t.trim()).join(',')"
                        class="flex-1 text-xs text-slate-500 file:mr-2 file:rounded file:border-0 file:bg-teal-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-teal-700"
                        :disabled="uploading[`${doc.id}-1`]"
                        @change="handleUpload(doc.id, 1, $event)" />
                    <span v-if="uploading[`${doc.id}-1`]" class="text-xs text-slate-400">...</span>
                </div>
            </template>

            <!-- Multiple mode -->
            <template v-else-if="doc.mode === 'multiple'">
                <div class="space-y-2">
                    <div v-for="upload in getUploadsForDoc(doc.id)" :key="upload.id"
                        class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                        <FileText class="h-4 w-4 shrink-0 text-green-600" />
                        <span class="flex-1 text-xs text-slate-700 truncate">{{ upload.file_name }}</span>
                        <a :href="upload.download_url" target="_blank"
                            class="shrink-0 text-teal-600 hover:text-teal-700">
                            <Download class="h-4 w-4" />
                        </a>
                        <CheckCircle2 class="h-4 w-4 shrink-0 text-green-500" />
                    </div>
                    <div v-if="getUploadsForDoc(doc.id).length < doc.count"
                        class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                        <Upload class="h-4 w-4 shrink-0 text-slate-400" />
                        <input type="file"
                            :accept="doc.accepted_types.split(',').map(t => '.' + t.trim()).join(',')"
                            class="flex-1 text-xs text-slate-500 file:mr-2 file:rounded file:border-0 file:bg-teal-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-teal-700"
                            @change="handleUpload(doc.id, getUploadsForDoc(doc.id).length + 1, $event)" />
                    </div>
                </div>
            </template>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
