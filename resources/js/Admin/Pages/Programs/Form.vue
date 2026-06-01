<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ImagePlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    program: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    programTypeOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');
const isSubmitting = ref(false);

const form = ref({
    title: props.program?.title || '',
    slug: props.program?.slug || '',
    description: props.program?.description || '',
    category: props.program?.category || '',
    program_type: props.program?.program_type || 'physical',
    location: props.program?.location || '',
    online_url: props.program?.online_url || '',
    capacity: props.program?.capacity || '',
    start_date: props.program?.start_date || '',
    end_date: props.program?.end_date || '',
    registration_deadline: props.program?.registration_deadline || '',
    status: props.program?.status || 'draft',
    is_featured: props.program?.is_featured || false,
});

const errors = ref({});
const coverImagePreview = ref(props.program?.cover_image_url || null);

const handleCoverUpload = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { coverImagePreview.value = ev.target?.result; };
    reader.readAsDataURL(file);
};

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    const url = isEdit.value ? `/admin/programs/${props.program.id}` : '/admin/programs';
    const method = isEdit.value ? 'put' : 'post';

    const payload = {
        ...form.value,
        capacity: form.value.capacity ? Number(form.value.capacity) : '',
    };

    router[method](url, payload, {
        preserveScroll: true,
        onError: (errs) => {
            errors.value = errs;
            isSubmitting.value = false;
        },
        onFinish: () => { isSubmitting.value = false; },
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Program' : 'Tambah Program'" />

    <AdminLayout>
        <section class="mx-auto max-w-4xl space-y-6">
            <PageHeader
                :title="isEdit ? 'Edit Program' : 'Tambah Program'"
                :description="isEdit ? 'Kemaskini maklumat program dan acara.' : 'Cipta program atau acara baharu untuk ahli.'"
            >
                <template #actions>
                    <Button variant="outline" :as="Link" href="/admin/programs">
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <form @submit.prevent="submit">
                <FormSection title="Maklumat Asas">
                    <TextInput id="title" v-model="form.title" label="Tajuk Program" :error="errors.title" placeholder="Contoh: Mesyuarat Agung Tahunan 2026" required class="md:col-span-2" />

                    <SelectInput id="category" v-model="form.category" label="Kategori" :options="categoryOptions" :error="errors.category" />

                    <SelectInput id="program_type" v-model="form.program_type" label="Jenis Program" :options="programTypeOptions" :error="errors.program_type" required />

                    <TextInput id="location" v-model="form.location" label="Lokasi" :error="errors.location" placeholder="Dewan Serbaguna Koperasi" />

                    <TextInput id="online_url" v-model="form.online_url" label="Pautan Atas Talian" :error="errors.online_url" placeholder="https://zoom.us/j/..." />

                    <TextareaInput id="description" v-model="form.description" label="Penerangan" :error="errors.description" placeholder="Terangkan program ini..." class="md:col-span-2" rows="5" />
                </FormSection>

                <FormSection title="Tarikh & Kapasiti">
                    <TextInput id="start_date" v-model="form.start_date" label="Tarikh Mula" type="datetime-local" :error="errors.start_date" required />

                    <TextInput id="end_date" v-model="form.end_date" label="Tarikh Tamat" type="datetime-local" :error="errors.end_date" />

                    <TextInput id="registration_deadline" v-model="form.registration_deadline" label="Tamat Pendaftaran" type="datetime-local" :error="errors.registration_deadline" />

                    <TextInput id="capacity" v-model="form.capacity" label="Kapasiti" type="number" :error="errors.capacity" placeholder="100" />
                </FormSection>

                <FormSection title="Imej & Status">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Imej Latar</label>
                        <div class="flex flex-col items-center gap-3 rounded-lg border-2 border-dashed border-slate-300 p-4">
                            <img v-if="coverImagePreview" :src="coverImagePreview" class="h-32 w-full rounded-lg object-cover" />
                            <div v-else class="flex flex-col items-center gap-2 text-slate-400">
                                <ImagePlus class="h-10 w-10" />
                                <span class="text-sm">Muat naik imej</span>
                            </div>
                            <input id="cover_image" type="file" accept="image/*" class="w-full rounded-lg border border-slate-300 p-2 text-sm" @change="handleCoverUpload" />
                        </div>
                        <p v-if="errors.cover_image" class="text-sm text-red-600">{{ errors.cover_image }}</p>
                    </div>

                    <SelectInput id="status" v-model="form.status" label="Status" :options="statusOptions" :error="errors.status" required />

                    <div class="flex items-center gap-3">
                        <input id="is_featured" v-model="form.is_featured" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-700" />
                        <label for="is_featured" class="text-sm font-medium text-slate-700">Tanda sebagai program utama</label>
                    </div>
                </FormSection>

                <FormActions>
                    <Button type="button" variant="outline" :as="Link" href="/admin/programs">Batal</Button>
                    <Button type="submit" :disabled="isSubmitting">
                        {{ isEdit ? 'Simpan Perubahan' : 'Cipta Program' }}
                    </Button>
                </FormActions>
            </form>
        </section>
    </AdminLayout>
</template>