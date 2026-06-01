<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    template: { type: Object, required: true },
    variables: { type: Array, default: () => [] },
    isSystem: { type: Boolean, default: false },
    typeLabel: { type: String, default: '' },
    canEdit: { type: Boolean, default: false },
});

const form = useForm({
    subject: props.template.subject,
    body: props.template.body,
    variables: props.variables,
    is_active: props.template.is_active ?? true,
});

const submit = () => {
    form.put('/admin/email-templates/' + props.template.type, {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
};
</script>

<template>
    <Head :title="'Edit Templat: ' + template.type" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Edit Templat E-mel</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                        <span v-if="isSystem" class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs text-blue-800 mr-1.5">Sistem</span>
                        <span v-else class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-xs text-amber-800 mr-1.5">Tersuai</span>
                        <span class="text-slate-500">{{ typeLabel }}</span>
                        <code class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs">{{ template.type }}</code>
                    </p>
                </div>
                <Button variant="outline" :as="Link" href="/admin/email-templates">
                    Kembali
                </Button>
            </div>

            <div v-if="!canEdit" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Akaun anda tidak mempunyai kebenaran untuk mengedit templat e-mel.
            </div>

            <div v-if="variables.length" class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                <p class="mb-2 text-sm font-medium text-blue-900">Pemboleh ubah yang tersedia:</p>
                <div class="flex flex-wrap gap-2">
                    <code
                        v-for="v in variables"
                        :key="v"
                        class="rounded-md bg-blue-100 px-2 py-1 font-mono text-xs text-blue-800"
                    >{{ '\x7b\x7b' + v + '\x7d\x7d' }}</code>
                </div>
                <p class="mt-2 text-xs text-blue-700">
                    Guna pemboleh ubah di atas dalam tajuk atau kandungan e-mel. Sistem akan menggantikannya dengan nilai sebenar semasa penghantaran.
                </p>
            </div>

            <div v-if="!isSystem && !variables.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm text-slate-600">
                    Templat tersuai tidak mempunyai pemboleh ubah pratakrif. Gunakan teks statik di dalam tajuk dan kandungan.
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <TextInput
                            id="template-subject"
                            v-model="form.subject"
                            label="Tajuk e-mel"
                            required
                            :error="form.errors.subject"
                            :disabled="!canEdit"
                        />

                        <div class="space-y-2">
                            <label for="template-body" class="text-sm font-medium text-slate-800">Kandungan e-mel</label>
                            <textarea
                                id="template-body"
                                v-model="form.body"
                                rows="12"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                                :disabled="!canEdit"
                            />
                            <p v-if="form.errors.body" class="text-sm text-red-700">{{ form.errors.body }}</p>
                            <p class="text-xs text-slate-500">
                                Gunakan format teks biasa. Pemboleh ubah akan digantikan secara automatik.
                            </p>
                        </div>

                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700"
                                :disabled="!canEdit"
                            />
                            <span class="text-sm text-slate-800">Aktifkan templat ini</span>
                        </label>
                    </div>
                </section>

                <div v-if="canEdit" class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Templat' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
