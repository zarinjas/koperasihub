<?php

namespace Tests\Feature\Public;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Enums\NewsStatus;
use App\Enums\ServiceStatus;
use App\Models\Announcement;
use App\Models\Cooperative;
use App\Models\News;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\CmsDemoSeeder;
use Database\Seeders\CooperativeSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementServicePublicTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);
        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => 'admin',
        ]);
    }

    public function test_public_announcements_route_shows_only_published_public_and_non_expired_records_in_pinned_order(): void
    {
        Announcement::factory()->published()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Pengumuman Biasa',
            'summary' => 'Boleh dilihat umum.',
            'published_at' => now()->subDays(2),
        ]);

        Announcement::factory()->published()->public()->pinned()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Pengumuman Dipin',
            'summary' => 'Perlu muncul dahulu.',
            'published_at' => now()->subDay(),
        ]);

        Announcement::factory()->published()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Khas Ahli',
        ]);

        Announcement::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Masih Draf',
            'audience' => AnnouncementAudience::Public->value,
            'status' => AnnouncementStatus::Draft->value,
        ]);

        Announcement::factory()->published()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Sudah Tamat',
            'expires_at' => now()->subHour(),
        ]);

        $this->get('/announcements')
            ->assertOk()
            ->assertSeeInOrder(['Pengumuman Dipin', 'Pengumuman Biasa'])
            ->assertDontSee('Khas Ahli')
            ->assertDontSee('Masih Draf')
            ->assertDontSee('Sudah Tamat');
    }

    public function test_public_announcement_detail_route_returns_404_for_non_public_or_unpublished_records(): void
    {
        $membersOnly = Announcement::factory()->published()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Notis Dalaman',
        ]);

        $draft = Announcement::factory()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Belum Terbit',
            'status' => AnnouncementStatus::Draft->value,
        ]);

        $public = Announcement::factory()->published()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Hebahan Awam',
            'summary' => 'Untuk orang ramai.',
        ]);

        $this->get("/announcements/{$membersOnly->slug}")->assertNotFound();
        $this->get("/announcements/{$draft->slug}")->assertNotFound();
        $this->get("/announcements/{$public->slug}")
            ->assertOk()
            ->assertSee('Hebahan Awam');
    }

    public function test_public_services_routes_only_show_published_records(): void
    {
        $published = Service::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Perkhidmatan Awam',
            'summary' => 'Perkhidmatan untuk paparan awam.',
        ]);

        $draft = Service::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Perkhidmatan Draf',
            'status' => ServiceStatus::Draft->value,
        ]);

        $this->get('/services')
            ->assertOk()
            ->assertSee('Perkhidmatan Awam')
            ->assertDontSee('Perkhidmatan Draf');

        $this->get("/services/{$published->slug}")
            ->assertOk()
            ->assertSee('Perkhidmatan Awam');

        $this->get("/services/{$draft->slug}")
            ->assertNotFound();
    }

    public function test_homepage_sections_use_real_records_and_fall_back_safely_when_module_records_are_missing(): void
    {
        $this->cooperative->update(['status' => 'inactive']);
        $this->seed(CooperativeSettingsSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Pentadbir CMS Demo',
            'email' => 'admin@koperasihub.test',
            'cooperative_id' => Cooperative::query()->where('slug', 'koperasi-demo-berhad')->value('id'),
            'user_type' => 'admin',
        ]);

        $this->seed(CmsDemoSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Keanggotaan')
            ->assertSee('Pembukaan Permohonan Keanggotaan Sesi Demo');

        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->firstOrFail();
        $authorId = User::query()->where('email', 'admin@koperasihub.test')->value('id');

        Service::factory()->published()->create([
            'cooperative_id' => $cooperative->id,
            'created_by' => $authorId,
            'updated_by' => $authorId,
            'title' => 'Khidmat Akaun Demo',
            'summary' => 'Rekod sebenar perkhidmatan dari modul.',
        ]);

        Announcement::factory()->published()->public()->create([
            'cooperative_id' => $cooperative->id,
            'created_by' => $authorId,
            'updated_by' => $authorId,
            'title' => 'Hebahan Rekod Sebenar',
            'summary' => 'Rekod sebenar pengumuman dari modul.',
            'published_at' => now()->subHour(),
        ]);

        News::factory()->create([
            'cooperative_id' => $cooperative->id,
            'created_by' => $authorId,
            'updated_by' => $authorId,
            'title' => 'Berita Dengan Thumbnail',
            'status' => NewsStatus::Published->value,
            'published_at' => now()->subMinutes(30),
            'image_path' => 'news/thumbnail-ujian.jpg',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Khidmat Akaun Demo')
            ->assertSee('Hebahan Rekod Sebenar')
            ->assertSee('Berita Dengan Thumbnail')
            ->assertSee('\/storage\/news\/thumbnail-ujian.jpg', false);
    }
}