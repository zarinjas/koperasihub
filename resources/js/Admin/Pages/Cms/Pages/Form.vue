<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Archive, EyeOff, FolderKanban, Send, Upload } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import { useAutoSlug } from '@/Shared/Composables/useAutoSlug.js';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    pageRecord: { type: Object, default: null },
    templateOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    canPublish: { type: Boolean, default: false },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    title: props.pageRecord?.title || '',
    slug: props.pageRecord?.slug || '',
    template: props.pageRecord?.template || 'default',
    summary: props.pageRecord?.summary || '',
    status: props.pageRecord?.status || 'draft',
    meta_title: props.pageRecord?.meta_title || '',
    meta_description: props.pageRecord?.meta_description || '',
    featured_image: null,
    published_at: props.pageRecord?.published_at || '',
});

const { slugHelp } = useAutoSlug(() => form.title, form, 'slug');

const submit = () => {
    const cb = {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };

    if (isEdit.value) {
        form.patch(`/admin/cms/pages/${props.pageRecord.id}`, {
            forceFormData: true,
            ...cb,
        });

        return;
    }

    form.post('/admin/cms/pages', { forceFormData: true, ...cb });
};

const cb = { onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }) };
const publish = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/publish`, {}, cb);
const unpublish = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/unpublish`, {}, cb);
const archive = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/archive`, {}, cb);
const cancel = () => router.get('/admin/cms/pages');
</script>

<template>
    <Head :title="isEdit ? `Edit ${pageRecord.title}` : 'Cipta Halaman'" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <PageHeader
                :title="isEdit ? 'Edit Halaman CMS' : 'Cipta Halaman CMS'"
                :description="isEdit ? 'Kemas kini metadata halaman dan urus status penerbitan.' : 'Sediakan metadata asas sebelum menambah seksyen kandungan.'"
            >
                <template #actions>
                    <StatusBadge v-if="pageRecord" :status="pageRecord.status" />
                    <Button v-if="isEdit" :as="Link" :href="`/admin/cms/pages/${pageRecord.id}/sections`" variant="outline">
                        <FolderKanban class="mr-2 h-4 w-4" />
                        Urus Seksyen
                    </Button>
                    <Button v-if="canPublish && isEdit && pageRecord.status !== 'published'" type="button" @click="publish">
                        <Send class="mr-2 h-4 w-4" />
                        Terbitkan
                    </Button>
                    <Button v-if="canPublish && isEdit && pageRecord.status === 'published'" type="button" variant="outline" @click="unpublish">
                        <EyeOff class="mr-2 h-4 w-4" />
                        Nyahterbit
                    </Button>
                    <Button v-if="canPublish && isEdit && pageRecord.status !== 'archived'" type="button" variant="outline" @click="archive">
                        <Archive class="mr-2 h-4 w-4" />
                        Arkib
                    </Button>
                </template>
            </PageHeader>

            <FormSection title="Maklumat Halaman" description="Medan asas untuk halaman awam yang akan dirender melalui CMS." :columns="2">
                <TextInput id="title" v-model="form.title" label="Tajuk halaman" :error="form.errors.title" />
                <TextInput id="slug" v-model="form.slug" label="Slug" :error="form.errors.slug" :help="slugHelp" />
                <SelectInput id="template" v-model="form.template" label="Templat" :options="templateOptions" :error="form.errors.template" />
                <SelectInput id="status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                <div class="md:col-span-2">
                    <TextareaInput id="summary" v-model="form.summary" label="Ringkasan" :error="form.errors.summary" />
                </div>
                <div class="space-y-3">
                    <label class="text-sm font-medium text-slate-800">Imej utama</label>
                    <label
                        for="page-featured-image"
                        class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition hover:border-teal-300 hover:bg-teal-50/40"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                            <Upload class="h-5 w-5" />
                        </span>
                        <div class="space-y-1">
                            <p v-if="form.featured_image" class="text-sm font-medium text-slate-900">{{ form.featured_image.name }}</p>
                            <p v-else class="text-sm font-medium text-slate-900">Pilih fail imej untuk dimuat naik</p>
                            <p class="text-xs leading-5 text-slate-500">JPEG, PNG, JPG atau WebP. Maks 5MB.</p>
                        </div>
                    </label>
                    <input
                        id="page-featured-image"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        type="file"
                        class="hidden"
                        @change="(e) => { const file = e.target.files?.[0]; if (file) form.featured_image = file; }"
                    />
                    <img
                        v-if="pageRecord?.featured_image_url && !form.featured_image"
                        :src="pageRecord.featured_image_url"
                        class="h-40 rounded-2xl border border-slate-200 object-cover shadow-sm"
                        alt="Imej semasa"
                    />
                    <p v-if="form.errors.featured_image" class="text-sm text-red-700">{{ form.errors.featured_image }}</p>
                </div>
                <TextInput id="published-at" v-model="form.published_at" label="Tarikh terbit" type="datetime-local" :error="form.errors.published_at" />
            </FormSection>

            <FormSection title="SEO" description="Metadata ini akan digunakan pada halaman awam dan enjin carian." :columns="2">
                <TextInput id="meta-title" v-model="form.meta_title" label="Tajuk SEO" :error="form.errors.meta_title" />
                <div />
                <div class="md:col-span-2">
                    <TextareaInput id="meta-description" v-model="form.meta_description" label="Penerangan SEO" :error="form.errors.meta_description" />
                </div>
            </FormSection>

            <FormActions submit-label="Simpan Halaman" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>