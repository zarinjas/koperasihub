<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Shared/Components/ui/dialog';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Mail, Pencil, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    templates: { type: Object, required: true },
    templateCategories: { type: Object, required: true },
    systemTypes: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
});

const showCreateDialog = ref(false);

const createForm = useForm({
    type: '',
    subject: '',
    body: '',
    is_active: true,
});

const placeholder = (v) => '\x7b\x7b' + v + '\x7d\x7d';

const categorizedTemplates = computed(() => {
    const categories = {};

    for (const [category, types] of Object.entries(props.templateCategories)) {
        categories[category] = Object.entries(types).map(([type, meta]) => ({
            type,
            label: meta.label,
            variables: meta.variables,
            template: props.templates[type] ?? null,
            isSystem: true,
        }));
    }

    const systemTypeKeys = new Set(Object.keys(props.systemTypes));
    const customEntries = Object.entries(props.templates)
        .filter(([type]) => !systemTypeKeys.has(type))
        .map(([type, template]) => ({
            type,
            label: type,
            variables: template.variables ?? [],
            template,
            isSystem: false,
        }));

    if (customEntries.length) {
        categories['Templat Tersuai'] = customEntries;
    }

    return categories;
});

const submitCreate = () => {
    createForm.post('/admin/email-templates', {
        onSuccess: () => {
            showCreateDialog.value = false;
            createForm.reset();
        },
    });
};

const deleteTemplate = (type) => {
    if (!confirm(`Padam templat "${type}"? Tindakan ini tidak boleh dibatalkan.`)) return;
    useForm({}).delete(`/admin/email-templates/${type}`);
};
</script>

<template>
    <Head title="Templat E-mel" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Templat E-mel</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                        Urus kandungan e-mel automatik yang dihantar oleh sistem. Kumpulan mengikut kategori.
                    </p>
                </div>
                <Dialog v-if="canEdit" v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button class="h-8 px-2.5 text-xs">
                            <Plus class="mr-1 h-3.5 w-3.5" />
                            Buat Templat Tersuai
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Buat Templat E-mel Tersuai</DialogTitle>
                        </DialogHeader>
                        <form class="mt-4 space-y-4" @submit.prevent="submitCreate">
                            <TextInput
                                id="custom-type"
                                v-model="createForm.type"
                                label="Jenis (slug)"
                                placeholder="cth: welcome_email"
                                required
                                :error="createForm.errors.type"
                                :disabled="createForm.processing"
                            />
                            <p class="text-xs text-slate-500 -mt-2">Hanya huruf kecil, nombor dan garis bawah. Tidak boleh sama dengan jenis templat sistem.</p>
                            <TextInput
                                id="custom-subject"
                                v-model="createForm.subject"
                                label="Tajuk e-mel"
                                required
                                :error="createForm.errors.subject"
                                :disabled="createForm.processing"
                            />
                            <div class="space-y-2">
                                <label for="custom-body" class="text-sm font-medium text-slate-800">Kandungan e-mel</label>
                                <textarea
                                    id="custom-body"
                                    v-model="createForm.body"
                                    rows="8"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                                    :disabled="createForm.processing"
                                />
                                <p v-if="createForm.errors.body" class="text-sm text-red-700">{{ createForm.errors.body }}</p>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <Button variant="outline" type="button" :disabled="createForm.processing" @click="showCreateDialog = false">
                                    Batal
                                </Button>
                                <Button type="submit" :disabled="createForm.processing">
                                    {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-for="(templates, category) in categorizedTemplates" :key="category" class="space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-2">
                    <h2 class="text-base font-semibold text-slate-800">{{ category }}</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ templates.length }}</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="item in templates"
                        :key="item.type"
                        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                    <Mail class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-slate-950 text-sm leading-snug">{{ item.label }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 truncate">
                                        <template v-if="item.template">
                                            {{ item.template.subject }}
                                        </template>
                                        <template v-else>
                                            Guna templat lalai sistem
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <span v-if="item.template?.is_active" class="inline-flex shrink-0 items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                Aktif
                            </span>
                            <span v-else-if="item.template" class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                Tidak Aktif
                            </span>
                            <span v-else class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                Lalai
                            </span>
                        </div>

                        <div v-if="item.variables.length" class="flex flex-wrap gap-1">
                            <span class="text-xs text-slate-500">Pemboleh ubah:</span>
                            <code
                                v-for="v in item.variables"
                                :key="v"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700"
                            >{{ placeholder(v) }}</code>
                        </div>

                        <div class="mt-auto flex justify-between items-end border-t border-slate-100 pt-3">
                            <span v-if="!item.isSystem" class="rounded bg-amber-50 px-1.5 py-0.5 font-mono text-[10px] text-amber-700">
                                {{ item.type }}
                            </span>
                            <span v-else />

                            <div class="flex gap-2">
                                <component
                                    :is="canEdit && !item.isSystem ? 'button' : 'span'"
                                    v-if="canEdit && !item.isSystem"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors hover:bg-red-50"
                                    @click="deleteTemplate(item.type)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </component>
                                <Button
                                    :as="Link"
                                    :href="'/admin/email-templates/' + item.type + '/edit'"
                                    variant="outline"
                                    class="h-8 px-2.5 text-xs"
                                    :disabled="!canEdit"
                                >
                                    <Pencil class="mr-1 h-3 w-3" />
                                    {{ item.template ? 'Edit' : 'Buat' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
