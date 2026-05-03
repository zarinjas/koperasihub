<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { BriefcaseBusiness, ChevronLeft, Mail, MessageCircle, Phone } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    service: { type: Object, required: true },
});

const paragraphs = computed(() => (props.service.description || '').split('\n').filter(Boolean));
</script>

<template>
    <Head :title="service.title" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-teal-50 via-white to-blue-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader :title="service.title" :description="service.summary" align="start">
                    <template #actions>
                        <Button :as="Link" href="/perkhidmatan" variant="outline">
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </template>
                </PageHeader>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.6fr_0.9fr] lg:px-8">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                            <BriefcaseBusiness class="h-6 w-6" />
                        </div>
                        <span v-if="service.category" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            {{ service.category.replaceAll('_', ' ') }}
                        </span>
                    </div>

                    <div class="space-y-4 text-sm leading-7 text-slate-700">
                        <p v-if="paragraphs.length === 0">{{ service.summary }}</p>
                        <p v-for="paragraph in paragraphs" :key="paragraph">{{ paragraph }}</p>
                    </div>
                </article>

                <aside class="space-y-6">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Hubungi Unit Berkaitan</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <div v-if="service.contact_name" class="flex items-start gap-3">
                                <BriefcaseBusiness class="mt-0.5 h-4 w-4 text-teal-700" />
                                <span>{{ service.contact_name }}</span>
                            </div>
                            <div v-if="service.contact_phone" class="flex items-start gap-3">
                                <Phone class="mt-0.5 h-4 w-4 text-teal-700" />
                                <span>{{ service.contact_phone }}</span>
                            </div>
                            <div v-if="service.contact_email" class="flex items-start gap-3">
                                <Mail class="mt-0.5 h-4 w-4 text-teal-700" />
                                <span>{{ service.contact_email }}</span>
                            </div>
                            <div v-if="service.whatsapp" class="flex items-start gap-3">
                                <MessageCircle class="mt-0.5 h-4 w-4 text-teal-700" />
                                <span>{{ service.whatsapp }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="service.button_text && service.button_url" class="rounded-[1.75rem] border border-teal-200 bg-teal-50 p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Tindakan Lanjut</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Gunakan pautan ini untuk tindakan seterusnya berkaitan perkhidmatan ini.</p>
                        <Button :as="Link" :href="service.button_url" class="mt-4 w-full justify-center">
                            {{ service.button_text }}
                        </Button>
                    </div>
                </aside>
            </div>
        </section>
    </PublicLayout>
</template>
