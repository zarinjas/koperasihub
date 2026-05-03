<?php

namespace App\Services\Cms;

use App\Enums\PageTemplate;
use App\Models\Document;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PublicPageService
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {
    }

    public function findHomepage(): ?Page
    {
        if (! $this->canQueryPages()) {
            return null;
        }

        return $this->baseQuery()
            ->where(function ($query): void {
                $query->where('slug', 'home')
                    ->orWhere('template', PageTemplate::Homepage->value);
            })
            ->first();
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        if (! $this->canQueryPages()) {
            return null;
        }

        return $this->baseQuery()
            ->forPublicSlug($slug)
            ->first();
    }

    public function toPayload(Page $page, string $canonicalUrl): array
    {
        $settings = $this->settingsService->shared();

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template?->value,
            'summary' => $page->summary,
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description ?: Arr::get($settings, 'seo.meta_description'),
            'featured_image_path' => $page->featured_image_path,
            'canonical_url' => $canonicalUrl,
            'sections' => $page->activeSections
                ->sortBy('sort_order')
                ->values()
                ->map(fn (PageSection $section): array => $this->transformSection($section, $settings))
                ->all(),
        ];
    }

    private function baseQuery()
    {
        return Page::query()
            ->published()
            ->with(['activeSections' => fn ($query) => $query->active()]);
    }

    private function canQueryPages(): bool
    {
        return Schema::hasTable('pages') && Schema::hasTable('page_sections');
    }

    private function canQueryDocuments(): bool
    {
        return Schema::hasTable('documents') && Schema::hasTable('document_categories');
    }

    private function transformSection(PageSection $section, array $settings): array
    {
        $type = $section->type?->value ?? $section->getRawOriginal('type');
        $data = $section->data ?? [];

        return [
            'id' => $section->id,
            'type' => $type,
            'name' => $section->name,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'data' => match ($type) {
                'service_grid' => $this->resolveServiceGridData($data),
                'business_units' => $this->resolveBusinessUnitsData($data),
                'announcement_list' => $this->resolveAnnouncementData($data),
                'download_list' => $this->resolveDownloadData($data),
                'faq' => $this->resolveFaqData($data),
                'contact_block' => $this->resolveContactData($data, $settings),
                default => $data,
            },
            'settings' => $section->settings ?? [],
        ];
    }

    private function resolveServiceGridData(array $data): array
    {
        if (($data['source'] ?? null) === 'manual' && filled($data['items'] ?? null)) {
            return $data;
        }

        return [
            ...$data,
            'items' => array_slice($this->demoServices(), 0, (int) ($data['limit'] ?? 6)),
        ];
    }

    private function resolveBusinessUnitsData(array $data): array
    {
        if (filled($data['items'] ?? null)) {
            return $data;
        }

        return [
            ...$data,
            'items' => $this->demoBusinessUnits(),
        ];
    }

    private function resolveAnnouncementData(array $data): array
    {
        if (($data['source'] ?? null) === 'manual' && filled($data['items'] ?? null)) {
            return $data;
        }

        return [
            ...$data,
            'items' => array_slice($this->demoAnnouncements(), 0, (int) ($data['limit'] ?? 3)),
        ];
    }

    private function resolveDownloadData(array $data): array
    {
        if (($data['source'] ?? null) === 'manual' && filled($data['items'] ?? null)) {
            return $data;
        }

        if ($this->canQueryDocuments()) {
            $documents = Document::query()
                ->publiclyVisible()
                ->where('cooperative_id', $this->settingsService->activeCooperative()?->id)
                ->when(
                    filled($data['category'] ?? null),
                    fn ($query) => $query->whereHas('category', fn ($query) => $query->where('slug', $data['category']))
                )
                ->with('category')
                ->orderByDesc('published_at')
                ->orderBy('title')
                ->limit((int) ($data['limit'] ?? 6))
                ->get()
                ->map(fn (Document $document) => [
                    'title' => $document->title,
                    'description' => $document->description,
                    'file_size' => $this->formatBytes($document->file_size),
                    'url' => route('public.downloads.download', $document),
                ])
                ->all();

            if ($documents !== []) {
                return [
                    ...$data,
                    'items' => $documents,
                ];
            }
        }

        return [
            ...$data,
            'items' => array_slice($this->demoDownloads(), 0, (int) ($data['limit'] ?? 6)),
        ];
    }

    private function resolveFaqData(array $data): array
    {
        if (filled($data['items'] ?? null)) {
            return $data;
        }

        return [
            ...$data,
            'items' => $this->demoFaqs(),
        ];
    }

    private function resolveContactData(array $data, array $settings): array
    {
        $contact = $settings['contact'] ?? [];
        $address = collect([
            $contact['address_line_1'] ?? null,
            $contact['address_line_2'] ?? null,
            collect([
                $contact['postcode'] ?? null,
                $contact['city'] ?? null,
                $contact['state'] ?? null,
            ])->filter()->implode(' '),
            $contact['country'] ?? null,
        ])->filter()->implode(', ');

        return [
            ...$data,
            'phone' => $data['phone'] ?? ($contact['phone'] ?? null),
            'email' => $data['email'] ?? ($contact['email'] ?? null),
            'whatsapp' => $data['whatsapp'] ?? ($contact['whatsapp'] ?? null),
            'address' => $data['address'] ?? $address,
        ];
    }

    private function demoServices(): array
    {
        return [
            [
                'title' => 'Keanggotaan',
                'description' => 'Maklumat syarat keahlian, proses permohonan dan kemas kini status anggota.',
                'url' => '/perkhidmatan/keanggotaan',
            ],
            [
                'title' => 'Pembiayaan Anggota',
                'description' => 'Panduan permohonan pembiayaan anggota tertakluk kepada syarat koperasi.',
                'url' => '/perkhidmatan/pembiayaan-anggota',
            ],
            [
                'title' => 'Simpanan & Syer',
                'description' => 'Maklumat berkaitan caruman syer, simpanan anggota dan rekod pegangan.',
                'url' => '/perkhidmatan/simpanan-syer',
            ],
            [
                'title' => 'Takaful Kenderaan',
                'description' => 'Rujukan permohonan dan pembaharuan perlindungan takaful kenderaan.',
                'url' => '/perkhidmatan/takaful-kenderaan',
            ],
            [
                'title' => 'Ar-Rahnu',
                'description' => 'Maklumat umum pajak gadai Islam yang boleh ditawarkan oleh koperasi.',
                'url' => '/perkhidmatan/ar-rahnu',
            ],
            [
                'title' => 'Kebajikan Anggota',
                'description' => 'Sokongan kebajikan anggota berdasarkan inisiatif dan bantuan koperasi.',
                'url' => '/perkhidmatan/kebajikan-anggota',
            ],
        ];
    }

    private function demoBusinessUnits(): array
    {
        return [
            [
                'title' => 'Kedai Koperasi',
                'description' => 'Jualan barangan keperluan harian, produk anggota atau barangan terpilih.',
                'url' => '/perniagaan/kedai-koperasi',
            ],
            [
                'title' => 'Hartanah & Sewaan',
                'description' => 'Maklumat premis, ruang niaga atau aset sewaan koperasi.',
                'url' => '/perniagaan/hartanah-sewaan',
            ],
            [
                'title' => 'Stesen Minyak',
                'description' => 'Paparan unit perniagaan stesen minyak sebagai contoh aktiviti ekonomi koperasi.',
                'url' => '/perniagaan/stesen-minyak',
            ],
            [
                'title' => 'E-Dagang',
                'description' => 'Saluran digital untuk promosi produk dan perkhidmatan koperasi.',
                'url' => '/perniagaan/e-dagang',
            ],
            [
                'title' => 'Bilik Seminar',
                'description' => 'Kemudahan ruang mesyuarat atau latihan yang boleh ditempah mengikut keperluan.',
                'url' => '/perniagaan/bilik-seminar',
            ],
        ];
    }

    private function demoAnnouncements(): array
    {
        return [
            [
                'title' => 'Makluman operasi kaunter minggu ini',
                'excerpt' => 'Semak waktu operasi terkini bagi urusan kaunter dan semakan dokumen fizikal.',
                'published_at' => now()->subDays(2)->toDateString(),
                'url' => '/pengumuman',
            ],
            [
                'title' => 'Hebahan taklimat keanggotaan baharu',
                'excerpt' => 'Sesi taklimat ringkas disediakan untuk pemohon yang ingin memahami proses keahlian.',
                'published_at' => now()->subDays(5)->toDateString(),
                'url' => '/pengumuman',
            ],
            [
                'title' => 'Peringatan kemas kini maklumat ahli',
                'excerpt' => 'Ahli digalakkan menyemak butiran hubungan masing-masing bagi melancarkan urusan koperasi.',
                'published_at' => now()->subDays(9)->toDateString(),
                'url' => '/pengumuman',
            ],
        ];
    }

    private function demoDownloads(): array
    {
        return [
            [
                'title' => 'Borang Permohonan Keanggotaan',
                'description' => 'Borang asas untuk permohonan anggota baharu.',
                'file_size' => 'PDF · 240 KB',
                'url' => '/downloads',
            ],
            [
                'title' => 'Borang Kemas Kini Maklumat Ahli',
                'description' => 'Gunakan borang ini untuk mengemas kini butiran peribadi dan hubungan.',
                'file_size' => 'PDF · 180 KB',
                'url' => '/downloads',
            ],
            [
                'title' => 'Borang Penamaan Waris',
                'description' => 'Rujukan untuk penamaan waris mengikut prosedur koperasi.',
                'file_size' => 'PDF · 210 KB',
                'url' => '/downloads',
            ],
            [
                'title' => 'Panduan Ringkas Portal Ahli',
                'description' => 'Penerangan asas tentang semakan maklumat dan akses dokumen.',
                'file_size' => 'PDF · 320 KB',
                'url' => '/downloads',
            ],
        ];
    }

    private function demoFaqs(): array
    {
        return [
            [
                'question' => 'Siapa yang boleh memohon keahlian?',
                'answer' => 'Permohonan terbuka kepada individu yang memenuhi syarat asas yang ditetapkan oleh koperasi.',
            ],
            [
                'question' => 'Bagaimana saya menyemak status permohonan?',
                'answer' => 'Status permohonan boleh disemak melalui saluran rasmi koperasi atau portal ahli apabila fungsi berkaitan diaktifkan.',
            ],
            [
                'question' => 'Di mana saya boleh mendapatkan borang berkaitan?',
                'answer' => 'Borang umum dan dokumen rujukan disediakan di bahagian muat turun untuk kemudahan pelawat dan anggota.',
            ],
            [
                'question' => 'Bagaimana cara menghubungi koperasi?',
                'answer' => 'Anda boleh menghubungi koperasi melalui telefon, e-mel, WhatsApp atau hadir ke alamat rasmi yang dipaparkan di laman ini.',
            ],
        ];
    }

    private function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}
