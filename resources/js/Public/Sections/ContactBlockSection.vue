<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MapPin, MessageCircle, Phone, Send } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicSection from '@/Public/Components/PublicSection.vue';
import SectionHeader from '@/Shared/Components/SectionHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const social = computed(() => page.props.appSettings?.social ?? {});
const data = computed(() => props.section.data ?? {});
const settings = computed(() => props.section.settings ?? {});

const contactItems = computed(() => [
    {
        label: 'Telefon',
        value: data.value.phone,
        href: data.value.phone ? `tel:${data.value.phone}` : null,
        icon: Phone,
    },
    {
        label: 'E-mel',
        value: data.value.email,
        href: data.value.email ? `mailto:${data.value.email}` : null,
        icon: Mail,
    },
    {
        label: 'WhatsApp',
        value: data.value.whatsapp,
        href: data.value.whatsapp ? `https://wa.me/${data.value.whatsapp.replace(/[^0-9]/g, '')}` : null,
        icon: MessageCircle,
    },
    {
        label: 'Alamat',
        value: data.value.address,
        href: data.value.map_url || null,
        icon: MapPin,
    },
].filter((item) => item.value));

const socialLinks = computed(() => [
    { label: 'Facebook', href: social.value.facebook_url },
    { label: 'Instagram', href: social.value.instagram_url },
    { label: 'LinkedIn', href: social.value.linkedin_url },
].filter((item) => item.href));
</script>

<template>
    <PublicSection :settings="settings" content-class="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-start">
        <div class="space-y-6">
            <SectionHeader
                eyebrow="Hubungi kami"
                :title="data.title"
                :description="data.subtitle"
            />

            <div class="grid gap-4">
                <a
                    v-for="item in contactItems"
                    :key="item.label"
                    :href="item.href || '#'"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:border-teal-200 hover:shadow-md"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ item.label }}</p>
                            <p class="mt-1 text-sm leading-7 text-slate-900">{{ item.value }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                <h3 class="text-lg font-semibold text-slate-950">Saluran pertanyaan</h3>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Gunakan saluran rasmi di bawah untuk pertanyaan umum, maklumat perkhidmatan atau bantuan lanjut.
                </p>
            </div>
            <div class="space-y-6 p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4">
                        <p class="text-sm font-medium text-slate-500">Waktu respons</p>
                        <p class="mt-2 text-base font-semibold text-slate-950">Hari bekerja</p>
                        <p class="mt-1 text-sm text-slate-600">Balasan akan dibuat melalui saluran rasmi koperasi.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-teal-50 to-white p-4">
                        <p class="text-sm font-medium text-slate-500">Portal ahli</p>
                        <p class="mt-2 text-base font-semibold text-slate-950">Akses maklumat kendiri</p>
                        <p class="mt-1 text-sm text-slate-600">Semakan maklumat boleh dibuat melalui portal ahli yang disediakan oleh koperasi.</p>
                    </div>
                </div>

                <div v-if="socialLinks.length" class="space-y-3">
                    <p class="text-sm font-medium text-slate-500">Ikuti kami</p>
                    <div class="flex flex-wrap gap-3">
                        <a
                            v-for="item in socialLinks"
                            :key="item.label"
                            :href="item.href"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-teal-200 hover:text-teal-700"
                        >
                            {{ item.label }}
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button :as="Link" href="/member/login">Portal Ahli</Button>
                    <Button :as="'a'" :href="contactItems[0]?.href || '/hubungi'" variant="outline">
                        <Send class="mr-2 h-4 w-4" />
                        Hubungi Sekarang
                    </Button>
                </div>
            </div>
        </div>
    </PublicSection>
</template>