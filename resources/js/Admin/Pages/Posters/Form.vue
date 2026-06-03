<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    poster: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
});

const form = useForm({
    title: props.poster?.title || '',
    image: null,
    alt_text: props.poster?.alt_text || '',
    status: props.poster?.status || 'draft',
});

const previewUrl = ref(props.poster?.image_url || null);
const isDragging = ref(false);
const imageLoaded = ref(!!props.poster?.image_url);
const postersIndexUrl = '/admin/posters';

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (file) {
        setFile(file);
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        setFile(file);
    }
}

function setFile(file) {
    if (!file.type.startsWith('image/')) return;
    form.image = file;
    imageLoaded.value = true;
    const reader = new FileReader();
    reader.onload = (e) => { previewUrl.value = e.target.result; };
    reader.readAsDataURL(file);
}

function removeImage() {
    form.image = null;
    imageLoaded.value = !!props.poster?.image_url;
    previewUrl.value = props.poster?.image_url || null;
}

function submit() {
    const cb = {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };

    if (props.mode === 'create') {
        form.post(postersIndexUrl, { forceFormData: true, ...cb });
    } else {
        form.put(`/admin/posters/${props.poster.id}`, { forceFormData: true, ...cb });
    }
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Muat Naik Poster' : 'Edit Poster'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Muat Naik Poster' : 'Edit Poster'"
                :description="mode === 'create' ? 'Muat naik poster atau infografik baharu.' : 'Kemas kini poster atau infografik.'"
            >
                <template #actions>
                    <Button :as="Link" :href="postersIndexUrl" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
                    <div class="space-y-4">
                        <label class="text-sm font-medium text-slate-700">Imej Poster</label>
                        <p class="text-xs text-slate-500">Saiz yang disarankan: <strong>1080×1350px</strong> (nisbah 4:5)</p>

                        <div
                            class="relative flex aspect-[4/5] w-full items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed transition-colors"
                            :class="isDragging ? 'border-teal-400 bg-teal-50' : 'border-slate-300 bg-slate-50 hover:border-teal-300'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <img
                                v-if="previewUrl"
                                :src="previewUrl"
                                alt="Pratonton poster"
                                class="h-full w-full object-contain"
                            />
                            <div v-else class="flex flex-col items-center gap-2 p-6 text-center">
                                <Upload class="h-8 w-8 text-slate-400" />
                                <p class="text-sm text-slate-500">Klik atau seret imej ke sini</p>
                                <p class="text-xs text-slate-400">JPEG, PNG atau WebP. Maks 5MB.</p>
                            </div>

                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="absolute inset-0 cursor-pointer opacity-0"
                                @change="handleFileSelect"
                            />

                            <button
                                v-if="previewUrl"
                                type="button"
                                class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 shadow-sm hover:bg-white"
                                @click="removeImage"
                            >
                                <X class="h-4 w-4 text-slate-600" />
                            </button>
                        </div>

                        <p v-if="imageLoaded && !form.errors.image" class="text-sm text-emerald-600">Imej berjaya dimuat.</p>
                        <p v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Tajuk</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Contoh: Infografik Simpanan Anggota"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Teks Alternatif</label>
                            <input
                                v-model="form.alt_text"
                                type="text"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Penerangan ringkas untuk kebolehcapaian"
                            />
                            <p v-if="form.errors.alt_text" class="mt-1 text-sm text-red-600">{{ form.errors.alt_text }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                            <select
                                v-model="form.status"
                                class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            >
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <Button :as="Link" :href="postersIndexUrl" variant="outline">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : mode === 'create' ? 'Muat Naik Poster' : 'Simpan Perubahan' }}
                    </Button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>