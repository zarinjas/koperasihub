<?php

namespace App\Enums;

enum PageSectionType: string
{
    case Hero = 'hero';
    case Stats = 'stats';
    case FeatureGrid = 'feature_grid';
    case ServiceGrid = 'service_grid';
    case BusinessUnits = 'business_units';
    case AnnouncementList = 'announcement_list';
    case CtaBanner = 'cta_banner';
    case Faq = 'faq';
    case ContactBlock = 'contact_block';
    case DownloadList = 'download_list';
    case ImageText = 'image_text';
    case Testimonial = 'testimonial';
    case LatestNews = 'latest_news';
    case PosterGallery = 'poster_gallery';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}