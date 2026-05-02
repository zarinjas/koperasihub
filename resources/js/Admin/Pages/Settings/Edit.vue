<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Building2, Globe2, Mail, Palette, Save, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    cooperative: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const status = computed(() => page.props.flash?.status);

const value = (group, key, fallback = '') => props.settings?.[group]?.[key] ?? fallback;

const form = useForm({
    brand: {
        name: value('brand', 'name', props.cooperative.name),
        short_name: value('brand', 'short_name'),
        registration_no: value('brand', 'registration_no'),
        logo_path: value('brand', 'logo_path'),
        primary_color: value('brand', 'primary_color', '#0F766E'),
        secondary_color: value('brand', 'secondary_color', '#1D4ED8'),
    },
    contact: {
        address_line_1: value('contact', 'address_line_1'),
        address_line_2: value('contact', 'address_line_2'),
        city: value('contact', 'city'),
        state: value('contact', 'state'),
        postcode: value('contact', 'postcode'),
        country: value('contact', 'country', 'Malaysia'),
        phone: value('contact', 'phone'),
        email: value('contact', 'email'),
        whatsapp: value('contact', 'whatsapp'),
        website_url: value('contact', 'website_url'),
    },
    social: {
        facebook_url: value('social', 'facebook_url'),
        instagram_url: value('social', 'instagram_url'),
        linkedin_url: value('social', 'linkedin_url'),
    },
    seo: {
        meta_title: value('seo', 'meta_title'),
        meta_description: value('seo', 'meta_description'),
    },
    system: {
        timezone: value('system', 'timezone', 'Asia/Kuala_Lumpur'),
        date_format: value('system', 'date_format', 'd/m/Y'),
    },
});

const submit = () => {
    form.put('/admin/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Tetapan Koperasi" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Tetapan Koperasi</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                        Urus identiti putih label, maklumat hubungan, pautan sosial dan tetapan asas sistem.
                    </p>
                </div>

                <Button v-if="canEdit" type="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Tetapan' }}
                </Button>
            </div>

            <div v-if="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ status }}
            </div>

            <div v-if="!canEdit" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Akaun anda boleh melihat tetapan, tetapi tidak mempunyai kebenaran untuk mengemas kini tetapan.
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                        <Palette class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">Jenama</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Maklumat ini digunakan pada header, portal dan paparan awam.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <TextInput id="brand-name" v-model="form.brand.name" label="Nama koperasi" :error="form.errors['brand.name']" />
                    <TextInput id="brand-short-name" v-model="form.brand.short_name" label="Nama ringkas" :error="form.errors['brand.short_name']" />
                    <TextInput id="brand-registration-no" v-model="form.brand.registration_no" label="No. pendaftaran" :error="form.errors['brand.registration_no']" />
                    <TextInput id="brand-logo-path" v-model="form.brand.logo_path" label="Laluan logo" :error="form.errors['brand.logo_path']" />
                    <TextInput id="brand-primary-color" v-model="form.brand.primary_color" type="color" label="Warna utama" :error="form.errors['brand.primary_color']" />
                    <TextInput id="brand-secondary-color" v-model="form.brand.secondary_color" type="color" label="Warna sekunder" :error="form.errors['brand.secondary_color']" />
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                        <Mail class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">Hubungan</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Butiran rasmi untuk footer, halaman hubungan dan rujukan pentadbiran.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <TextInput id="contact-address-line-1" v-model="form.contact.address_line_1" label="Alamat baris 1" :error="form.errors['contact.address_line_1']" />
                    <TextInput id="contact-address-line-2" v-model="form.contact.address_line_2" label="Alamat baris 2" :error="form.errors['contact.address_line_2']" />
                    <TextInput id="contact-city" v-model="form.contact.city" label="Bandar" :error="form.errors['contact.city']" />
                    <TextInput id="contact-state" v-model="form.contact.state" label="Negeri" :error="form.errors['contact.state']" />
                    <TextInput id="contact-postcode" v-model="form.contact.postcode" label="Poskod" :error="form.errors['contact.postcode']" />
                    <TextInput id="contact-country" v-model="form.contact.country" label="Negara" :error="form.errors['contact.country']" />
                    <TextInput id="contact-phone" v-model="form.contact.phone" label="Telefon" :error="form.errors['contact.phone']" />
                    <TextInput id="contact-email" v-model="form.contact.email" type="email" label="Emel" :error="form.errors['contact.email']" />
                    <TextInput id="contact-whatsapp" v-model="form.contact.whatsapp" label="WhatsApp" :error="form.errors['contact.whatsapp']" />
                    <TextInput id="contact-website-url" v-model="form.contact.website_url" type="url" label="Laman web" :error="form.errors['contact.website_url']" />
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-start gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <Globe2 class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold">Pautan Sosial</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Pautan rasmi yang boleh dipaparkan pada laman awam.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <TextInput id="social-facebook-url" v-model="form.social.facebook_url" type="url" label="Facebook" :error="form.errors['social.facebook_url']" />
                        <TextInput id="social-instagram-url" v-model="form.social.instagram_url" type="url" label="Instagram" :error="form.errors['social.instagram_url']" />
                        <TextInput id="social-linkedin-url" v-model="form.social.linkedin_url" type="url" label="LinkedIn" :error="form.errors['social.linkedin_url']" />
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-start gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <Building2 class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold">SEO Asas</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Nilai lalai sebelum modul CMS mengurus metadata halaman.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <TextInput id="seo-meta-title" v-model="form.seo.meta_title" label="Tajuk meta" :error="form.errors['seo.meta_title']" />
                        <div class="space-y-2">
                            <label for="seo-meta-description" class="text-sm font-medium text-slate-800">Penerangan meta</label>
                            <textarea
                                id="seo-meta-description"
                                v-model="form.seo.meta_description"
                                rows="4"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            />
                            <p v-if="form.errors['seo.meta_description']" class="text-sm text-red-700">{{ form.errors['seo.meta_description'] }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <Settings class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">Sistem</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Tetapan dalaman untuk paparan masa dan format tarikh.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <TextInput id="system-timezone" v-model="form.system.timezone" label="Zon masa" :error="form.errors['system.timezone']" />
                    <TextInput id="system-date-format" v-model="form.system.date_format" label="Format tarikh" :error="form.errors['system.date_format']" />
                </div>
            </section>

            <div v-if="canEdit" class="flex justify-end">
                <Button type="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Tetapan' }}
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
