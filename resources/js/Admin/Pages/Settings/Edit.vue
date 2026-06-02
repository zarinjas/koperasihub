<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Building2, Globe2, ImageIcon, Mail, Palette, Save, Settings, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
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
    units: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const appSettings = computed(() => page.props.appSettings?.cooperative ?? {});

const value = (group, key, fallback = '') => props.settings?.[group]?.[key] ?? fallback;

const form = useForm({
    brand: {
        name: value('brand', 'name', props.cooperative.name),
        short_name: value('brand', 'short_name'),
        registration_no: value('brand', 'registration_no'),
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
    membership: {
        member_no_prefix: value('membership', 'member_no_prefix', ''),
        member_no_digits: value('membership', 'member_no_digits', '4'),
    },
    notification: {
        keanggotaan_unit_id: value('notification', 'keanggotaan_unit_id', ''),
        pembiayaan_unit_id: value('notification', 'pembiayaan_unit_id', ''),
    },
});

const logoForm = useForm({ logo: null });
const faviconForm = useForm({ favicon: null });

const logoPreview = ref(appSettings.value.logo_url || null);
const faviconPreview = ref(appSettings.value.favicon_url || null);

const onLogoChange = (file) => {
    logoForm.logo = file;
    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const onFaviconChange = (file) => {
    faviconForm.favicon = file;
    if (file) {
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

const submitLogo = () => {
    logoForm.post('/admin/settings/branding/logo', {
        onSuccess: () => {
            scrollToTop();
            logoForm.reset();
        },
        onError: () => scrollToTop(),
    });
};

const submitFavicon = () => {
    faviconForm.post('/admin/settings/branding/favicon', {
        onSuccess: () => {
            scrollToTop();
            faviconForm.reset();
        },
        onError: () => scrollToTop(),
    });
};

const submit = () => {
    form.put('/admin/settings', {
        onSuccess: () => scrollToTop(),
        onError: () => scrollToTop(),
    });
};
</script>

<template>
    <Head title="Tetapan Koperasi" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Tetapan Koperasi</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                        Urus identiti putih label, maklumat hubungan, pautan sosial dan tetapan asas sistem.
                    </p>
                </div>
            </div>

            <div v-if="!canEdit" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Akaun anda boleh melihat tetapan, tetapi tidak mempunyai kebenaran untuk mengemas kini tetapan.
            </div>

            <!-- Logo & Favicon Upload Section -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                        <ImageIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">Logo &amp; Favicon</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Muat naik logo dan favicon koperasi. Logo dipaparkan pada header, sidebar dan footer.
                        </p>
                    </div>
                </div>

                <div class="grid gap-8 md:grid-cols-2">
                    <!-- Logo Upload -->
                    <div class="space-y-4">
                        <div v-if="logoPreview" class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                                <img :src="logoPreview" alt="Logo semasa" class="h-12 w-12 rounded-lg object-contain" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">Logo semasa</p>
                                <p class="mt-0.5 text-xs text-slate-500">Imej yang dipaparkan pada seluruh sistem</p>
                            </div>
                        </div>

                        <FileUploader
                            id="logo-upload"
                            label="Logo Koperasi"
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                            helper-text="Saiz dicadangkan: 540px × 540px. Gunakan fail PNG, JPG, WEBP atau SVG."
                            :error="logoForm.errors.logo"
                            :model-value="logoForm.logo"
                            @update:model-value="onLogoChange"
                        />

                        <div v-if="canEdit" class="flex justify-end">
                            <Button
                                type="button"
                                :disabled="!logoForm.logo || logoForm.processing"
                                @click="submitLogo"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                {{ logoForm.processing ? 'Memuat naik...' : 'Simpan Logo' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Favicon Upload -->
                    <div class="space-y-4">
                        <div v-if="faviconPreview" class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                                <img :src="faviconPreview" alt="Favicon semasa" class="h-10 w-10 rounded object-contain" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">Favicon semasa</p>
                                <p class="mt-0.5 text-xs text-slate-500">Ikon yang dipaparkan pada tab pelayar</p>
                            </div>
                        </div>

                        <FileUploader
                            id="favicon-upload"
                            label="Favicon"
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                            helper-text="Saiz dicadangkan: 540px × 540px. Gunakan ikon berbentuk ringkas supaya jelas pada tab pelayar."
                            :error="faviconForm.errors.favicon"
                            :model-value="faviconForm.favicon"
                            @update:model-value="onFaviconChange"
                        />

                        <div v-if="canEdit" class="flex justify-end">
                            <Button
                                type="button"
                                :disabled="!faviconForm.favicon || faviconForm.processing"
                                @click="submitFavicon"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                {{ faviconForm.processing ? 'Memuat naik...' : 'Simpan Favicon' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Brand & General Settings Form -->
            <form class="space-y-6" @submit.prevent="submit">
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
                        <div />
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
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                            <Building2 class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold">Pemberitahuan &amp; Unit</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Tetapkan unit mana yang akan menerima pemberitahuan dan emel mengikut jenis permohonan.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="notification-keanggotaan-unit-id" class="text-sm font-medium text-slate-800">Unit Keanggotaan</label>
                            <select
                                id="notification-keanggotaan-unit-id"
                                v-model="form.notification.keanggotaan_unit_id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            >
                                <option value="">-- Pilih Unit --</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors['notification.keanggotaan_unit_id']" class="text-sm text-red-700">{{ form.errors['notification.keanggotaan_unit_id'] }}</p>
                            <p class="text-xs text-slate-500">Terima pemberitahuan permohonan keahlian baharu.</p>
                        </div>
                        <div class="space-y-2">
                            <label for="notification-pembiayaan-unit-id" class="text-sm font-medium text-slate-800">Unit Pembiayaan</label>
                            <select
                                id="notification-pembiayaan-unit-id"
                                v-model="form.notification.pembiayaan_unit_id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
                            >
                                <option value="">-- Pilih Unit --</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                            <p v-if="form.errors['notification.pembiayaan_unit_id']" class="text-sm text-red-700">{{ form.errors['notification.pembiayaan_unit_id'] }}</p>
                            <p class="text-xs text-slate-500">Terima pemberitahuan permohonan pembiayaan dan ansuran.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-start gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                            <Users class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold">Keahlian</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Format penomboran ahli baru dan tetapan keahlian asas.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <TextInput id="membership-member-no-prefix" v-model="form.membership.member_no_prefix" label="Awalan no. ahli" :error="form.errors['membership.member_no_prefix']" help="Contoh: KDB- akan menghasilkan KDB-0001" />
                        <TextInput id="membership-member-no-digits" v-model="form.membership.member_no_digits" label="Digit no. ahli" :error="form.errors['membership.member_no_digits']" help="Bilangan digit, contoh: 4 untuk 0001" />
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
        </div>
    </AdminLayout>
</template>