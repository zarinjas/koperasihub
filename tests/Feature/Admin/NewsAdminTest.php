<?php

namespace Tests\Feature\Admin;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Models\Cooperative;
use App\Models\News;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected User $member;

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

        $this->member = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => 'member',
            'role' => 'member',
        ]);
        $this->member->assignRole(AccessControl::ROLE_MEMBER);
    }

    public function test_unauthenticated_user_cannot_access_news_index(): void
    {
        $this->get('/admin/news')->assertRedirect();
    }

    public function test_member_cannot_access_news_index(): void
    {
        $this->actingAs($this->member)
            ->get('/admin/news')
            ->assertRedirect();
    }

    public function test_admin_can_view_news_index(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/news')
            ->assertOk();
    }

    public function test_admin_can_create_news(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/news', [
                'title' => 'Berita Baharu Koperasi',
                'slug' => 'berita-baharu-koperasi',
                'category' => NewsCategory::General->value,
                'status' => NewsStatus::Draft->value,
                'excerpt' => 'Ringkasan berita baharu.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('news', [
            'cooperative_id' => $this->cooperative->id,
            'slug' => 'berita-baharu-koperasi',
            'status' => NewsStatus::Draft->value,
        ]);
    }

    public function test_member_cannot_create_news(): void
    {
        $this->actingAs($this->member)
            ->post('/admin/news', [
                'title' => 'Berita Haram',
                'slug' => 'berita-haram',
                'category' => NewsCategory::General->value,
                'status' => NewsStatus::Draft->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('news', ['slug' => 'berita-haram']);
    }

    public function test_admin_can_edit_and_update_news(): void
    {
        $news = News::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/news/{$news->id}/edit")
            ->assertOk();

        $this->actingAs($this->admin)
            ->put("/admin/news/{$news->id}", [
                'title' => 'Berita Dikemaskini',
                'slug' => $news->slug,
                'category' => NewsCategory::Event->value,
                'status' => NewsStatus::Draft->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'Berita Dikemaskini',
            'category' => NewsCategory::Event->value,
        ]);
    }

    public function test_admin_can_update_news_with_image_upload(): void
    {
        Storage::fake('public');

        $news = News::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $image = UploadedFile::fake()->image('berita-baharu.jpg');

        $this->actingAs($this->admin)
            ->post("/admin/news/{$news->id}", [
                '_method' => 'patch',
                'title' => 'Berita Dengan Imej',
                'slug' => $news->slug,
                'category' => NewsCategory::General->value,
                'status' => NewsStatus::Draft->value,
                'image' => $image,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $news->refresh();

        $this->assertSame('Berita Dengan Imej', $news->title);
        $this->assertNotNull($news->image_path);
        Storage::disk('public')->assertExists($news->image_path);
    }

    public function test_admin_can_publish_news(): void
    {
        $news = News::factory()->draft()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/news/{$news->id}/publish")
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => NewsStatus::Published->value,
        ]);
    }

    public function test_admin_can_unpublish_news(): void
    {
        $news = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/news/{$news->id}/unpublish")
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => NewsStatus::Draft->value,
        ]);
    }

    public function test_admin_can_archive_news(): void
    {
        $news = News::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/news/{$news->id}/archive")
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => NewsStatus::Archived->value,
        ]);
    }

    public function test_admin_can_delete_news(): void
    {
        $news = News::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/news/{$news->id}")
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSoftDeleted('news', ['id' => $news->id]);
    }

    public function test_admin_cannot_access_news_from_another_cooperative(): void
    {
        $otherCooperative = Cooperative::factory()->create(['status' => 'active']);
        $news = News::factory()->create([
            'cooperative_id' => $otherCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/news/{$news->id}/edit")
            ->assertNotFound();
    }

    public function test_slug_must_be_unique_within_cooperative(): void
    {
        News::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'slug' => 'berita-unik',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/news', [
                'title' => 'Tajuk Lain',
                'slug' => 'berita-unik',
                'category' => NewsCategory::General->value,
                'status' => NewsStatus::Draft->value,
            ])
            ->assertSessionHasErrors('slug');
    }
}