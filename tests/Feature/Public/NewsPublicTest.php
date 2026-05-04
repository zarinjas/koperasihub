<?php

namespace Tests\Feature\Public;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Models\Cooperative;
use App\Models\News;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPublicTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['status' => 'active']);
        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => 'admin',
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_public_news_listing_shows_only_published_records(): void
    {
        News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Tersiar',
            'published_at' => now()->subDay(),
        ]);

        News::factory()->draft()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Draf',
        ]);

        News::factory()->archived()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Diarkib',
        ]);

        $this->get('/berita')
            ->assertOk()
            ->assertSee('Berita Tersiar')
            ->assertDontSee('Berita Draf')
            ->assertDontSee('Berita Diarkib');
    }

    public function test_public_news_detail_returns_ok_for_published_article(): void
    {
        $news = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Awam Tersiar',
            'excerpt' => 'Ringkasan berita untuk ujian.',
        ]);

        $this->get("/berita/{$news->slug}")
            ->assertOk()
            ->assertSee('Berita Awam Tersiar');
    }

    public function test_public_news_detail_returns_404_for_draft(): void
    {
        $news = News::factory()->draft()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Belum Terbit',
        ]);

        $this->get("/berita/{$news->slug}")->assertNotFound();
    }

    public function test_public_news_detail_returns_404_for_archived(): void
    {
        $news = News::factory()->archived()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Diarkib',
        ]);

        $this->get("/berita/{$news->slug}")->assertNotFound();
    }

    public function test_public_news_detail_returns_404_for_nonexistent_slug(): void
    {
        $this->get('/berita/slug-tidak-wujud')->assertNotFound();
    }

    public function test_suggested_articles_exclude_current_article(): void
    {
        $current = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'category' => NewsCategory::General->value,
            'published_at' => now()->subDays(1),
        ]);

        $other1 = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'category' => NewsCategory::General->value,
            'published_at' => now()->subDays(2),
        ]);

        $other2 = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'category' => NewsCategory::Event->value,
            'published_at' => now()->subDays(3),
        ]);

        $response = $this->get("/berita/{$current->slug}")->assertOk();

        $data = json_decode(
            $response->getContent(),
            true
        );

        // Extract Inertia page props from the embedded JSON
        preg_match('/<script[^>]+data-page[^>]*>(.*?)<\/script>/s', $response->getContent(), $matches);
        $props = json_decode($matches[1] ?? '{}', true)['props'] ?? [];

        $suggestedIds = array_column($props['suggested'] ?? [], 'id');

        $this->assertNotContains($current->id, $suggestedIds, 'Current article should not appear in suggested list.');
        $this->assertContains($other1->id, $suggestedIds);
        $this->assertContains($other2->id, $suggestedIds);
    }

    public function test_news_listing_filters_by_category(): void
    {
        News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Acara',
            'category' => NewsCategory::Event->value,
            'published_at' => now()->subDay(),
        ]);

        News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Umum',
            'category' => NewsCategory::General->value,
            'published_at' => now()->subDays(2),
        ]);

        $this->get('/berita?category='.NewsCategory::Event->value)
            ->assertOk()
            ->assertSee('Berita Acara')
            ->assertDontSee('Berita Umum');
    }

    public function test_news_listing_shows_empty_state_when_no_records(): void
    {
        $this->get('/berita')->assertOk();
    }

    public function test_news_listing_ordered_by_published_date_descending(): void
    {
        News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Lama',
            'published_at' => now()->subDays(10),
        ]);

        News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Berita Terbaharu',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/berita')
            ->assertOk()
            ->assertSeeInOrder(['Berita Terbaharu', 'Berita Lama']);
    }
}
