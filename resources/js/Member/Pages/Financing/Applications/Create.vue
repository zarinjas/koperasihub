<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    product: { type: Object, required: true },
    member: { type: Object, required: true },
    guarantorSearchUrl: { type: String, required: true },
});

const form = useForm({
    financing_product_id: props.product.id,
    amount_requested: '',
    tenure_months: '',
    purpose: '',
    monthly_income: '',
    monthly_commitment: '',
    employment_notes: '',
    guarantor_member_ids: [],
    documents: [],
});

const guarantorSearch = ref('');
const guarantorResults = ref([]);
const guarantorSearchError = ref('');

const searchGuarantors = async () => {
    guarantorSearchError.value = '';
    const response = await fetch(`${props.guarantorSearchUrl}?search=${encodeURIComponent(guarantorSearch.value)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        guarantorSearchError.value = 'Carian penjamin tidak berjaya.';
        return;
    }

    const payload = await response.json();
    guarantorResults.value = payload.results || [];
};

const toggleGuarantor = (memberId) => {
    if (form.guarantor_member_ids.includes(memberId)) {
        form.guarantor_member_ids = form.guarantor_member_ids.filter((id) => id !== memberId);
        return;
    }

    if (form.guarantor_member_ids.length >= props.product.guarantor_count) {
        return;
    }

    form.guarantor_member_ids = [...form.guarantor_member_ids, memberId];
};

const submit = () => {
    form.post('/member/financing/applications', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const onDocumentsChange = (event) => {
    form.documents = Array.from(event.target.files || []);
};
</script>

<template>
    <Head title="Permohonan Pembiayaan Baharu" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader title="Permohonan Pembiayaan Baharu" description="Lengkapkan maklumat berikut untuk menghantar permohonan pembiayaan anda.">
                <template #actions>
                    <Button :as="Link" :href="`/member/financing/products/${product.id}`" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <FormSection title="Butiran Produk" description="Maklumat produk yang anda pilih." :columns="2">
                    <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Produk</p><p class="mt-1 text-sm text-slate-700">{{ product.name }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p><p class="mt-1 text-sm text-slate-700">{{ product.category_name }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Amaun</p><p class="mt-1 text-sm text-slate-700">RM {{ product.min_amount ?? '-' }} hingga RM {{ product.max_amount ?? '-' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tempoh</p><p class="mt-1 text-sm text-slate-700">{{ product.min_tenure_months || '-' }} hingga {{ product.max_tenure_months || '-' }} bulan</p></div>
                </FormSection>

                <FormSection title="Maklumat Permohonan" description="Isikan amaun, tempoh, dan tujuan permohonan pembiayaan." :columns="2">
                    <TextInput id="amount-requested" v-model="form.amount_requested" label="Amaun Dimohon (RM)" type="number" :error="form.errors.amount_requested" />
                    <TextInput id="tenure-months" v-model="form.tenure_months" label="Tempoh (Bulan)" type="number" :error="form.errors.tenure_months" />
                    <TextInput id="monthly-income" v-model="form.monthly_income" label="Pendapatan Bulanan (RM)" type="number" :error="form.errors.monthly_income" />
                    <TextInput id="monthly-commitment" v-model="form.monthly_commitment" label="Komitmen Bulanan (RM)" type="number" :error="form.errors.monthly_commitment" />
                    <div class="md:col-span-2">
                        <TextareaInput id="purpose" v-model="form.purpose" label="Tujuan Pembiayaan" :error="form.errors.purpose" />
                    </div>
                    <div class="md:col-span-2">
                        <TextareaInput id="employment-notes" v-model="form.employment_notes" label="Catatan Pekerjaan" :error="form.errors.employment_notes" />
                    </div>
                </FormSection>

                <FormSection v-if="product.requires_guarantor" title="Penjamin" description="Cari dan pilih penjamin aktif yang mempunyai log masuk ahli." :columns="1">
                    <div class="flex flex-col gap-4 md:flex-row">
                        <TextInput id="guarantor-search" v-model="guarantorSearch" label="Cari Penjamin" />
                        <div class="flex items-end">
                            <Button type="button" class="h-11" @click="searchGuarantors">
                                <Search class="mr-2 h-4 w-4" />
                                Cari
                            </Button>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Pilih sehingga {{ product.guarantor_count }} penjamin.</p>
                    <p v-if="guarantorSearchError" class="text-sm text-red-700">{{ guarantorSearchError }}</p>
                    <p v-if="form.errors.guarantor_member_ids" class="text-sm text-red-700">{{ form.errors.guarantor_member_ids }}</p>
                    <div class="space-y-3">
                        <article v-for="result in guarantorResults" :key="result.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-semibold text-slate-950">{{ result.name }}</p>
                                <p class="text-sm text-slate-500">{{ result.member_no }} · {{ result.employee_no || 'Tiada no. staf' }}</p>
                            </div>
                            <Button type="button" :disabled="!result.has_login" :variant="form.guarantor_member_ids.includes(result.id) ? 'default' : 'outline'" @click="toggleGuarantor(result.id)">
                                {{ form.guarantor_member_ids.includes(result.id) ? 'Dipilih' : 'Pilih' }}
                            </Button>
                        </article>
                    </div>
                </FormSection>

                <FormSection title="Dokumen Sokongan" description="Muat naik dokumen yang diperlukan untuk permohonan ini." :columns="1">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                        <label class="block text-sm font-medium text-slate-800">Dokumen</label>
                        <input class="mt-3 block w-full text-sm text-slate-700" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" @change="onDocumentsChange" />
                        <p class="mt-2 text-xs text-slate-500">Saiz maksimum 5MB setiap fail. Format dibenarkan: PDF, JPG, JPEG, PNG, WEBP.</p>
                        <p v-if="form.errors.documents" class="mt-2 text-sm text-red-700">{{ form.errors.documents }}</p>
                    </div>
                    <div v-if="product.required_documents?.length" class="space-y-2">
                        <p class="text-sm font-medium text-slate-900">Senarai dokumen dicadangkan</p>
                        <p v-for="document in product.required_documents" :key="document" class="text-sm text-slate-600">{{ document }}</p>
                    </div>
                </FormSection>

                <FormActions submit-label="Hantar Permohonan" :submitting="form.processing" cancel-label="Kembali" />
            </form>
        </section>
    </MemberLayout>
</template>
