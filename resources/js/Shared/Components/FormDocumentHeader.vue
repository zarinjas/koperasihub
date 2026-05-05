<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    formRecord: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const cooperative = computed(() => page.props.appSettings?.cooperative ?? {});
</script>

<template>
    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100">
                    <img
                        v-if="cooperative.logo_url"
                        :src="cooperative.logo_url"
                        :alt="cooperative.name || 'Logo koperasi'"
                        class="h-12 w-12 rounded-2xl object-contain"
                    />
                    <div v-else class="text-sm font-semibold text-slate-500">Logo</div>
                </div>
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-700">Dokumen Rasmi</p>
                    <h2 class="text-xl font-semibold text-slate-950">
                        {{ formRecord.document_title || formRecord.title }}
                    </h2>
                    <p class="text-sm text-slate-600">{{ cooperative.name || 'KoperasiHub Demo' }}</p>
                    <p v-if="formRecord.description" class="text-sm leading-6 text-slate-600">
                        {{ formRecord.description }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kod Borang</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formRecord.document_code || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. Semakan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formRecord.revision_no || '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Berkuatkuasa</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formRecord.effective_date || '-' }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
