<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicSectionRenderer from '@/Public/Components/SectionRenderer.vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';

const props = defineProps({
    page: {
        type: Object,
        required: true,
    },
});

const sharedPage = usePage();
const defaultDescription = computed(() => sharedPage.props.appSettings?.seo?.meta_description ?? '');
</script>

<template>
    <Head :title="page.meta_title || page.title">
        <meta head-key="description" name="description" :content="page.meta_description || defaultDescription" />
        <link v-if="page.canonical_url" head-key="canonical" rel="canonical" :href="page.canonical_url" />
    </Head>

    <PublicLayout>
        <main>
            <PublicSectionRenderer :sections="page.sections" />
        </main>
    </PublicLayout>
</template>
