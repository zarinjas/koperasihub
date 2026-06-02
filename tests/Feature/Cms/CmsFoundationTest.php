<?php

namespace Tests\Feature\Cms;

use App\Enums\PageSectionType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Cooperative;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Database\Seeders\CmsDemoSeeder;
use Database\Seeders\CooperativeSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_page_sections_relationship_returns_ordered_sections(): void
    {
        $cooperative = Cooperative::factory()->create();
        $author = User::factory()->admin()->create([
            'cooperative_id' => $cooperative->id,
        ]);

        $page = Page::factory()->create([
            'cooperative_id' => $cooperative->id,
            'created_by' => $author->id,
            'updated_by' => $author->id,
        ]);

        PageSection::factory()->create([
            'cooperative_id' => $cooperative->id,
            'page_id' => $page->id,
            'name' => 'Ketiga',
            'created_by' => $author->id,
            'updated_by' => $author->id,
            'created_at' => now()->subMinutes(3),
        ]);
        PageSection::factory()->inactive()->create([
            'cooperative_id' => $cooperative->id,
            'page_id' => $page->id,
            'name' => 'Kedua',
            'created_by' => $author->id,
            'updated_by' => $author->id,
            'created_at' => now()->subMinutes(2),
        ]);
        PageSection::factory()->create([
            'cooperative_id' => $cooperative->id,
            'page_id' => $page->id,
            'name' => 'Pertama',
            'created_by' => $author->id,
            'updated_by' => $author->id,
            'created_at' => now()->subMinutes(1),
        ]);

        $this->assertSame(
            ['Pertama', 'Kedua', 'Ketiga'],
            $page->fresh()->sections->pluck('name')->all()
        );

        $this->assertSame(
            ['Pertama', 'Ketiga'],
            $page->fresh()->activeSections->pluck('name')->all()
        );
    }

    public function test_published_scope_returns_only_publicly_available_pages(): void
    {
        $cooperative = Cooperative::factory()->create();

        Page::factory()->published()->create([
            'cooperative_id' => $cooperative->id,
            'slug' => 'halaman-terbit',
        ]);
        Page::factory()->create([
            'cooperative_id' => $cooperative->id,
            'slug' => 'halaman-draf',
            'status' => PageStatus::Draft->value,
        ]);
        Page::factory()->create([
            'cooperative_id' => $cooperative->id,
            'slug' => 'halaman-masa-depan',
            'status' => PageStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);
        Page::factory()->create([
            'cooperative_id' => $cooperative->id,
            'slug' => 'halaman-arkib',
            'status' => PageStatus::Archived->value,
        ]);

        $publishedSlugs = Page::query()->published()->pluck('slug')->all();

        $this->assertSame(['halaman-terbit'], $publishedSlugs);
        $this->assertNotNull(Page::query()->forPublicSlug('halaman-terbit')->first());
        $this->assertNull(Page::query()->forPublicSlug('halaman-draf')->first());
        $this->assertNull(Page::query()->forPublicSlug('halaman-masa-depan')->first());
    }

    public function test_cms_demo_seeder_creates_published_homepage_with_expected_sections(): void
    {
        $this->seed(CooperativeSettingsSeeder::class);

        $adminUser = User::factory()->admin()->create([
            'name' => 'Pentadbir CMS Demo',
            'email' => 'admin@koperasihub.test',
            'cooperative_id' => Cooperative::query()->where('slug', 'koperasi-demo-berhad')->value('id'),
        ]);

        $this->seed(CmsDemoSeeder::class);

        $homepage = Page::query()
            ->where('slug', 'home')
            ->where('template', PageTemplate::Homepage->value)
            ->first();

        $this->assertNotNull($homepage);
        $this->assertSame(PageStatus::Published, $homepage->status);
        $this->assertSame(11, $homepage->sections()->count());
        $this->assertSame([
            PageSectionType::Hero->value,
            PageSectionType::Stats->value,
            PageSectionType::FeatureGrid->value,
            PageSectionType::LatestNews->value,
            PageSectionType::ServiceGrid->value,
            PageSectionType::BusinessUnits->value,
            PageSectionType::AnnouncementList->value,
            PageSectionType::DownloadList->value,
            PageSectionType::CtaBanner->value,
            PageSectionType::Faq->value,
            PageSectionType::ContactBlock->value,
        ], $homepage->sections()->orderBy('id')->get()->pluck('type')->map->value->all());

        $heroSection = $homepage->sections()->where('type', PageSectionType::Hero->value)->first();

        $this->assertSame('Koperasi moden untuk keperluan anggota', $heroSection?->data['title']);
        $this->assertSame('image_right', $heroSection?->settings['variant']);
        $this->assertTrue($homepage->sections()->get()->every(fn (PageSection $section) => $section->is_active));
        $this->assertSame($adminUser->id, $homepage->created_by);
    }
}