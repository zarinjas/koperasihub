<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    memberLinked: { type: Boolean, default: true },
    categoryOptions: { type: Array, required: true },
    priorityOptions: { type: Array, required: true },
});

const form = useForm({
    category: props.categoryOptions[0]?.value || 'aduan',
    subject: '',
    message: '',
    priority: 'medium',
});

const submit = () => {
    form.post('/member/complaints', {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
};
</script>

<template>
    <Head title="Hantar Aduan" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Hantar Aduan"
                description="Sampaikan isu, pertanyaan, atau cadangan anda supaya pihak admin boleh membuat susulan."
            >
                <template #actions>
                    <Button :as="Link" href="/member/complaints" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div v-if="!memberLinked" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                Rekod akan dihantar menggunakan akaun portal anda walaupun rekod ahli belum dipautkan.
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <FormSection title="Maklumat Aduan" description="Lengkapkan maklumat asas untuk memudahkan semakan admin." :columns="2">
                    <SelectInput id="complaint-category" v-model="form.category" label="Kategori" :options="categoryOptions" :error="form.errors.category" />
                    <SelectInput id="complaint-priority" v-model="form.priority" label="Keutamaan" :options="priorityOptions" :error="form.errors.priority" />
                    <div class="md:col-span-2">
                        <TextInput id="complaint-subject" v-model="form.subject" label="Tajuk" :error="form.errors.subject" />
                    </div>
                    <div class="md:col-span-2">
                        <TextareaInput
                            id="complaint-message"
                            v-model="form.message"
                            label="Mesej"
                            :rows="7"
                            :error="form.errors.message"
                            help="Terangkan isu atau cadangan anda dengan jelas supaya tindakan susulan dapat dibuat dengan lebih cepat."
                        />
                    </div>
                </FormSection>

                <FormActions submit-label="Hantar Aduan" :submitting="form.processing" @cancel="$inertia.get('/member/complaints')" />
            </form>
        </section>
    </MemberLayout>
</template>