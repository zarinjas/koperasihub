<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Megaphone, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    popup: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
});

const form = useForm({
    title: props.popup?.title || '',
    content: props.popup?.content || '',
    image: null,
    button_text: props.popup?.button_text || '',
    button_url: props.popup?.button_url || '',
    is_active: props.popup?.is_active ?? false,
    starts_at: props.popup?.starts_at || '',
    ends_at: props.popup?.ends_at || '',
});

const previewUrl = ref(props.popup?.image_url || null);
const isDragging = ref(false);
const imageLoaded = ref(!!props.popup?.image_url);
const popupsIndexUrl = '/admin/popups';

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
    imageLoaded.value = !!props.popup?.image_url;
    previewUrl.value = props.popup?.image_url || null;
}

function submit() {
    const cb = {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };

    if (props.mode === 'create') {
        form.post(popupsIndexUrl, { forceFormData: true, ...cb });
    } else {
        form.put(`/admin/popups/${props.popup.id}`, { forceFormData: true, ...cb });
    }
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Cipta Popup' : 'Edit Popup'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Cipta Popup' : 'Edit Popup'"
                :description="mode === 'create' ? 'Cipta popup baru untuk ahli.' : 'Kemas kini popup ahli.'"
            >
                <template #actions>
                    <Button :as="Link" :href="popupsIndexUrl" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-slate-950">Maklumat Popup</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tajuk <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Contoh: Promosi Ahli Baru 2026"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Kandungan <span class="text-red-500">*</span></label>
                            <textarea
                                v-model="form.content"
                                rows="4"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Tulis kandungan popup di sini..."
                            ></textarea>
                            <p v-if="form.errors.content" class="mt-1 text-xs text-red-500">{{ form.errors.content }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Imej</label>
                            <div
                                class="relative flex cursor-pointer flex-col items-center rounded-lg border-2 border-dashed border-slate-300 p-6 transition hover:border-teal-400"
                                :class="{ 'border-teal-400 bg-teal-50': isDragging }"
                                @dragenter.prevent="isDragging = true"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                            >
                                <template v-if="!imageLoaded">
                                    <Upload class="mb-2 h-8 w-8 text-slate-400" />
                                    <p class="text-sm text-slate-500">Klik atau seret imej ke sini</p>
                                    <p class="mt-1 text-xs text-slate-400">JPEG, PNG, WebP (maks. 2MB)</p>
                                </template>
                                <template v-else>
                                    <img :src="previewUrl" alt="Preview" class="max-h-40 rounded-lg object-contain" />
                                    <Button type="button" variant="ghost" size="sm" class="mt-2 text-red-500" @click="removeImage">
                                        <X class="mr-1 h-4 w-4" />
                                        Padam Imej
                                    </Button>
                                </template>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="absolute inset-0 cursor-pointer opacity-0"
                                    @change="handleFileSelect"
                                />
                            </div>
                            <p v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Teks Butang</label>
                                <input
                                    v-model="form.button_text"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="Contoh: Daftar Sekarang"
                                />
                                <p v-if="form.errors.button_text" class="mt-1 text-xs text-red-500">{{ form.errors.button_text }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Pautan Butang</label>
                                <input
                                    v-model="form.button_url"
                                    type="url"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="https://..."
                                />
                                <p v-if="form.errors.button_url" class="mt-1 text-xs text-red-500">{{ form.errors.button_url }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-slate-950">Tetapan</h3>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                :true-value="true"
                                :false-value="false"
                                class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <label class="text-sm font-medium text-slate-700">Aktif</label>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Mula</label>
                                <input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                />
                                <p v-if="form.errors.starts_at" class="mt-1 text-xs text-red-500">{{ form.errors.starts_at }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Tamat</label>
                                <input
                                    v-model="form.ends_at"
                                    type="datetime-local"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                />
                                <p v-if="form.errors.ends_at" class="mt-1 text-xs text-red-500">{{ form.errors.ends_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        <Megaphone class="mr-2 h-4 w-4" />
                        {{ mode === 'create' ? 'Cipta Popup' : 'Simpan Perubahan' }}
                    </Button>
                    <Button :as="Link" :href="popupsIndexUrl" variant="outline">Batal</Button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>
