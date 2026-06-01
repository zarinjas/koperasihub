<script setup>
import { Download, FileCheck, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/Shared/Components/ui/button';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
});

const rejecting = ref(null);
const reason = ref('');

const verify = (document) => {
    router.post(document.verify_url, {}, { preserveScroll: true });
};

const reject = (document) => {
    router.post(document.reject_url, { reason: reason.value }, {
        preserveScroll: true,
        onSuccess: () => {
            rejecting.value = null;
            reason.value = '';
        },
    });
};
</script>

<template>
    <div v-if="documents.length" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-950">Semakan Dokumen</h2>
            <p class="mt-1 text-sm text-slate-500">Sahkan atau tolak setiap dokumen yang dimuat naik oleh ahli.</p>
        </div>

        <div class="space-y-3">
            <div v-for="document in documents" :key="document.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">{{ document.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.code }}</p>
                        <p v-if="document.uploaded_file_name" class="mt-1 text-xs text-slate-600">
                            Fail: {{ document.uploaded_file_name }}
                        </p>
                        <p v-if="document.rejection_reason" class="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ document.rejection_reason }}
                        </p>
                    </div>
                    <StatusBadge :status="document.status" :label="document.status.replaceAll('_', ' ')" />
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Button v-if="document.generated" :as="'a'" :href="document.download_url" type="button" variant="outline" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Dokumen Dijana
                    </Button>
                    <Button v-if="document.uploaded_download_url" :as="'a'" :href="document.uploaded_download_url" type="button" variant="outline" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Fail Ahli
                    </Button>
                    <Button v-if="document.uploaded && document.status !== 'verified'" type="button" size="sm" @click="verify(document)">
                        <FileCheck class="mr-2 h-4 w-4" />
                        Sahkan
                    </Button>
                    <Button v-if="document.uploaded && document.status !== 'verified'" type="button" variant="outline" size="sm" class="border-red-300 text-red-700 hover:bg-red-50" @click="rejecting = document.id">
                        <XCircle class="mr-2 h-4 w-4" />
                        Tolak
                    </Button>
                </div>

                <div v-if="rejecting === document.id" class="mt-4 space-y-2">
                    <textarea v-model="reason" rows="3" placeholder="Nyatakan sebab penolakan..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20" />
                    <div class="flex gap-2">
                        <Button type="button" variant="destructive" size="sm" :disabled="!reason" @click="reject(document)">Hantar Penolakan</Button>
                        <Button type="button" variant="ghost" size="sm" @click="rejecting = null; reason = '';">Batal</Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
