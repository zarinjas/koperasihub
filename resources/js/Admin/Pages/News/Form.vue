<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    newsRecord: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    title: props.newsRecord?.title || '',
    slug: props.newsRecord?.slug || '',
    excerpt: props.newsRecord?.excerpt || '',
    content: props.newsRecord?.content || '',
    image_path: props.newsRecord?.image_path || '',
    category: props.newsRecord?.category || '',
    status: props.newsRecord?.status || 'draft',
    published_at: props.newsRecord?.published_at || '',
});

const submit = () => {
    if (isEdit.value) {
        form.patch(`/admin/news/${props.newsRecord.id}`, {
            preserveScroll: true,
        });
        return;
    }

    form.post('/admin/news', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/admin/news');
};
</script>

<template>
    <Head :title="isEdit ? `Edit ${newsRecord.title}` : 'Tambah Berita'" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <PageHeader
                :title="isEdit ? 'Edit Berita' : 'Tambah Berita'"
                :description="isEdit ? 'Kemas kini kandungan dan tetapan artikel berita.' : 'Sediakan artikel berita baharu untuk paparan awam.'"
            >
                <template #actions>
                    <StatusBadge v-if="newsRecord" :status="newsRecord.status" />
                    <Button v-if="newsRecord" :as="Link" :href="newsRecord.public_url" variant="outline" target="_blank">
                        <Eye class="mr-2 h-4 w-4" />
                        Lihat Halaman
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FormSection title="Maklumat Berita" description="Maklumat asas artikel yang dipaparkan pada senarai dan halaman butiran." :columns="2">
                <TextInput id="news-title" v-model="form.title" label="Tajuk" :error="form.errors.title" />
                <TextInput id="news-slug" v-model="form.slug" label="Slug" :error="form.errors.slug" />
                <SelectInput id="news-category" v-model="form.category" label="Kategori" :options="categoryOptions" :error="form.errors.category" />
                <SelectInput id="news-status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                <TextInput id="news-published-at" v-model="form.published_at" label="Tarikh terbit" type="datetime-local" :error="form.errors.published_at" />
                <div class="md:col-span-2">
                    <TextareaInput id="news-excerpt" v-model="form.excerpt" label="Petikan" :error="form.errors.excerpt" />
                </div>
                <div class="md:col-span-2">
                    <TextareaInput id="news-content" v-model="form.content" label="Kandungan" :rows="10" :error="form.errors.content" />
                </div>
            </FormSection>

            <FormSection title="Imej" description="Gambar utama artikel untuk paparan kad dan halaman butiran." :columns="1">
                <TextInput id="news-image-path" v-model="form.image_path" label="Path imej" :error="form.errors.image_path" />
                <div v-if="form.image_path" class="mt-3">
                    <img :src="form.image_path" alt="Pratonton imej" class="h-40 rounded-2xl border border-slate-200 object-cover shadow-sm" />
                </div>
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Simpan Berita' : 'Cipta Berita'" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>
