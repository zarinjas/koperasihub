<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    banner: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
    audienceOptions: { type: Array, default: () => [] },
    typeOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    title: props.banner?.title || '',
    description: props.banner?.description || '',
    image: null,
    link_url: props.banner?.link_url || '',
    button_text: props.banner?.button_text || '',
    button_url: props.banner?.button_url || '',
    alt_text: props.banner?.alt_text || '',
    type: props.banner?.type || 'promotion',
    audience: props.banner?.audience || 'all',
    status: props.banner?.status || 'published',
    starts_at: props.banner?.starts_at || '',
    ends_at: props.banner?.ends_at || '',
    sort_order: props.banner?.sort_order || 0,
});

const previewUrl = ref(props.banner?.image_url || null);
const imageLoaded = ref(!!props.banner?.image_url);
const bannersIndexUrl = '/admin/banners';

const typeOptionsList = Object.entries(props.typeOptions).map(([value, label]) => ({ value, label }));

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (file) setFile(file);
}

function handleDrop(event) {
    const file = event.dataTransfer?.files?.[0];
    if (file) setFile(file);
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
    imageLoaded.value = !!props.banner?.image_url;
    previewUrl.value = props.banner?.image_url || null;
}

function submit() {
    const cb = {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };
    if (props.mode === 'create') {
        form.post(bannersIndexUrl, { forceFormData: true, ...cb });
    } else {
        form
            .transform((data) => {
                if (!data.image) {
                    const { image, ...rest } = data;
                    return rest;
                }
                return data;
            })
            .post(`/admin/banners/${props.banner.id}`, { forceFormData: true, ...cb });
    }
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Muat Naik Banner' : 'Edit Banner'" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="mode === 'create' ? 'Muat Naik Banner' : 'Edit Banner'"
                :description="mode === 'create' ? 'Muat naik banner digital baharu untuk portal ahli.' : 'Kemas kini banner digital.'"
            >
                <template #actions>
                    <Button :as="Link" :href="bannersIndexUrl" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1fr_1.5fr]">
                    <!-- Image upload -->
                    <div class="space-y-4">
                        <label class="text-sm font-medium text-slate-700">Imej Banner</label>
                        <p class="text-xs text-slate-500">Saiz yang disarankan: <strong>1200×600px</strong> (nisbah 2:1). Pastikan kandungan penting di kawasan tengah 70% untuk elak terpotong pada peranti mudah alih.</p>

                        <div class="relative flex aspect-[2/1] w-full items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 transition-colors hover:border-teal-300"
                            @dragover.prevent
                            @drop.prevent="handleDrop"
                        >
                            <img v-if="previewUrl" :src="previewUrl" alt="Pratonton banner" class="h-full w-full object-cover" />
                            <div v-else class="flex flex-col items-center gap-2 p-6 text-center">
                                <Upload class="h-8 w-8 text-slate-400" />
                                <p class="text-sm text-slate-500">Klik atau seret imej ke sini</p>
                                <p class="text-xs text-slate-400">JPEG, PNG atau WebP. Maks 5MB.</p>
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="absolute inset-0 cursor-pointer opacity-0" @change="handleFileSelect" />
                            <button v-if="previewUrl" type="button"
                                class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 shadow-sm hover:bg-white"
                                @click="removeImage">
                                <X class="h-4 w-4 text-slate-600" />
                            </button>
                        </div>
                        <p v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <!-- Fields -->
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Tajuk</label>
                            <input v-model="form.title" type="text"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Contoh: Promosi Pembiayaan Raya" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Penerangan</label>
                            <textarea v-model="form.description" rows="2"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Penerangan ringkas banner"></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">
                                    <ExternalLink class="mr-1 inline h-3.5 w-3.5 text-slate-400" />URL Pautan
                                    <span class="text-xs font-normal text-slate-400">(opsional)</span>
                                </label>
                                <input v-model="form.link_url" type="url"
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="https://... (biarkan kosong jika tiada pautan)" />
                                <p v-if="form.errors.link_url" class="mt-1 text-sm text-red-600">{{ form.errors.link_url }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Teks Butang</label>
                                <input v-model="form.button_text" type="text"
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="cth: Mohon Sekarang" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">URL Butang (jika berbeza)</label>
                            <input v-model="form.button_url" type="url"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="https://..." />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</label>
                                <select v-model="form.type"
                                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                    <option v-for="opt in typeOptionsList" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Sasaran Audiens</label>
                                <select v-model="form.audience"
                                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                    <option v-for="opt in audienceOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tarikh Mula</label>
                                <input v-model="form.starts_at" type="datetime-local"
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tarikh Tamat</label>
                                <input v-model="form.ends_at" type="datetime-local"
                                    class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                                <p v-if="form.errors.ends_at" class="mt-1 text-sm text-red-600">{{ form.errors.ends_at }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Aturan</label>
                            <input v-model="form.sort_order" type="number" min="0"
                                class="h-11 w-24 rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                            <p class="mt-1 text-xs text-slate-400">Nombor yang lebih kecil dipaparkan dahulu.</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Teks Alternatif</label>
                            <input v-model="form.alt_text" type="text"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="Penerangan ringkas untuk kebolehcapaian" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="form.status"
                                class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <Button :as="Link" :href="bannersIndexUrl" variant="outline">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : mode === 'create' ? 'Muat Naik Banner' : 'Simpan Perubahan' }}
                    </Button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>