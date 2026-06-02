<script setup>
import AnnouncementListSection from '@/Public/Sections/AnnouncementListSection.vue';
import BusinessUnitsSection from '@/Public/Sections/BusinessUnitsSection.vue';
import LatestNewsSection from '@/Public/Sections/LatestNewsSection.vue';
import PosterGallerySection from '@/Public/Sections/PosterGallerySection.vue';
import ContactBlockSection from '@/Public/Sections/ContactBlockSection.vue';
import CtaBannerSection from '@/Public/Sections/CtaBannerSection.vue';
import DownloadListSection from '@/Public/Sections/DownloadListSection.vue';
import FaqSection from '@/Public/Sections/FaqSection.vue';
import FeatureGridSection from '@/Public/Sections/FeatureGridSection.vue';
import HeroSection from '@/Public/Sections/HeroSection.vue';
import ImageTextSection from '@/Public/Sections/ImageTextSection.vue';
import ServiceGridSection from '@/Public/Sections/ServiceGridSection.vue';
import StatsSection from '@/Public/Sections/StatsSection.vue';
import UnknownSection from '@/Public/Sections/UnknownSection.vue';

defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});

const sectionMap = {
    hero: HeroSection,
    stats: StatsSection,
    feature_grid: FeatureGridSection,
    service_grid: ServiceGridSection,
    business_units: BusinessUnitsSection,
    announcement_list: AnnouncementListSection,
    cta_banner: CtaBannerSection,
    faq: FaqSection,
    contact_block: ContactBlockSection,
    download_list: DownloadListSection,
    image_text: ImageTextSection,
    latest_news: LatestNewsSection,
    poster_gallery: PosterGallerySection,
};

function resolveSectionComponent(type) {
    return sectionMap[type] ?? (import.meta.env.DEV ? UnknownSection : null);
}
</script>

<template>
    <template v-for="section in sections" :key="section.id">
        <component
            :is="resolveSectionComponent(section.type)"
            v-if="resolveSectionComponent(section.type)"
            :section="section"
        />
    </template>
</template>