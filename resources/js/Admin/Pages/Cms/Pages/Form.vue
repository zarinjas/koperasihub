<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Archive, EyeOff, FolderKanban, Send } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
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

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    title: props.pageRecord?.title || '',
    slug: props.pageRecord?.slug || '',
    template: props.pageRecord?.template || 'default',
    summary: props.pageRecord?.summary || '',
    status: props.pageRecord?.status || 'draft',
    meta_title: props.pageRecord?.meta_title || '',
    meta_description: props.pageRecord?.meta_description || '',
    featured_image_path: props.pageRecord?.featured_image_path || '',
    published_at: props.pageRecord?.published_at || '',
});

const submit = () => {
    const url = isEdit.value ? `/admin/cms/pages/${props.pageRecord.id}` : '/admin/cms/pages';
    const method = isEdit.value ? form.put : form.post;

    method(url, { preserveScroll: true });
};

const publish = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/publish`, {}, { preserveScroll: true });
const unpublish = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/unpublish`, {}, { preserveScroll: true });
const archive = () => router.post(`/admin/cms/pages/${props.pageRecord.id}/archive`, {}, { preserveScroll: true });
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

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FormSection title="Maklumat Halaman" description="Medan asas untuk halaman awam yang akan dirender melalui CMS." :columns="2">
                <TextInput id="title" v-model="form.title" label="Tajuk halaman" :error="form.errors.title" />
                <TextInput id="slug" v-model="form.slug" label="Slug" :error="form.errors.slug" />
                <SelectInput id="template" v-model="form.template" label="Templat" :options="templateOptions" :error="form.errors.template" />
                <SelectInput id="status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                <div class="md:col-span-2">
                    <TextareaInput id="summary" v-model="form.summary" label="Ringkasan" :error="form.errors.summary" />
                </div>
                <TextInput id="featured-image-path" v-model="form.featured_image_path" label="Laluan imej utama" :error="form.errors.featured_image_path" />
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
