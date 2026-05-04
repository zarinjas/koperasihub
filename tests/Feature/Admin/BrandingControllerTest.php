<?php

namespace Tests\Feature\Admin;

use App\Models\Cooperative;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $superAdmin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['status' => 'active']);

        app(SettingsService::class)->clearCache();

        $this->superAdmin = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $this->superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $this->member = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->member->assignRole(AccessControl::ROLE_MEMBER);
    }

    // --- Logo upload ---

    public function test_super_admin_can_upload_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 540, 540);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.logo'), ['logo' => $file])
            ->assertRedirect();

        Storage::disk('public')->assertExists('branding/logos/'.$file->hashName());

        $this->cooperative->refresh();
        $this->assertNotNull($this->cooperative->logo_path);
        $this->assertStringStartsWith('branding/logos/', $this->cooperative->logo_path);
    }

    public function test_super_admin_can_upload_favicon(): void
    {
        $file = UploadedFile::fake()->image('favicon.png', 64, 64);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.favicon'), ['favicon' => $file])
            ->assertRedirect();

        Storage::disk('public')->assertExists('branding/favicons/'.$file->hashName());

        $this->cooperative->refresh();
        $this->assertNotNull($this->cooperative->favicon_path);
        $this->assertStringStartsWith('branding/favicons/', $this->cooperative->favicon_path);
    }

    public function test_logo_upload_replaces_old_logo_file(): void
    {
        $oldFile = UploadedFile::fake()->image('old-logo.png');
        $oldPath = $oldFile->store('branding/logos', 'public');
        $this->cooperative->forceFill(['logo_path' => $oldPath])->save();

        $newFile = UploadedFile::fake()->image('new-logo.png', 540, 540);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.logo'), ['logo' => $newFile])
            ->assertRedirect();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists('branding/logos/'.$newFile->hashName());
    }

    public function test_member_cannot_upload_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png');

        // EnsureUserArea middleware redirects non-admin users away from /admin routes
        $this->actingAs($this->member)
            ->post(route('admin.settings.branding.logo'), ['logo' => $file])
            ->assertRedirect();

        $this->cooperative->refresh();
        $this->assertNull($this->cooperative->logo_path);
    }

    public function test_member_cannot_upload_favicon(): void
    {
        $file = UploadedFile::fake()->image('favicon.png');

        // EnsureUserArea middleware redirects non-admin users away from /admin routes
        $this->actingAs($this->member)
            ->post(route('admin.settings.branding.favicon'), ['favicon' => $file])
            ->assertRedirect();

        $this->cooperative->refresh();
        $this->assertNull($this->cooperative->favicon_path);
    }

    public function test_unauthenticated_user_cannot_upload_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png');

        $this->post(route('admin.settings.branding.logo'), ['logo' => $file])
            ->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_user_cannot_upload_favicon(): void
    {
        $file = UploadedFile::fake()->image('favicon.png');

        $this->post(route('admin.settings.branding.favicon'), ['favicon' => $file])
            ->assertRedirect('/admin/login');
    }

    public function test_invalid_file_type_rejected_for_logo(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.logo'), ['logo' => $file])
            ->assertSessionHasErrors('logo');

        $this->cooperative->refresh();
        $this->assertNull($this->cooperative->logo_path);
    }

    public function test_invalid_file_type_rejected_for_favicon(): void
    {
        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.favicon'), ['favicon' => $file])
            ->assertSessionHasErrors('favicon');

        $this->cooperative->refresh();
        $this->assertNull($this->cooperative->favicon_path);
    }

    public function test_logo_exceeding_max_size_is_rejected(): void
    {
        $file = UploadedFile::fake()->image('logo.png')->size(3000);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.logo'), ['logo' => $file])
            ->assertSessionHasErrors('logo');
    }

    public function test_favicon_exceeding_max_size_is_rejected(): void
    {
        $file = UploadedFile::fake()->image('favicon.png')->size(600);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.favicon'), ['favicon' => $file])
            ->assertSessionHasErrors('favicon');
    }

    public function test_app_settings_returns_logo_url_after_upload(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 540, 540);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.logo'), ['logo' => $file]);

        app(SettingsService::class)->clearCache();

        $shared = app(SettingsService::class)->shared();

        $this->assertNotNull($shared['cooperative']['logo_url']);
        $this->assertStringContainsString('branding/logos/', $shared['cooperative']['logo_url']);
    }

    public function test_app_settings_returns_favicon_url_after_upload(): void
    {
        $file = UploadedFile::fake()->image('favicon.png', 64, 64);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.branding.favicon'), ['favicon' => $file]);

        app(SettingsService::class)->clearCache();

        $shared = app(SettingsService::class)->shared();

        $this->assertNotNull($shared['cooperative']['favicon_url']);
        $this->assertStringContainsString('branding/favicons/', $shared['cooperative']['favicon_url']);
    }

    public function test_app_settings_returns_null_logo_url_when_no_logo_set(): void
    {
        $shared = app(SettingsService::class)->shared();

        $this->assertNull($shared['cooperative']['logo_url']);
    }

    public function test_settings_page_is_accessible_to_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.index'))
            ->assertOk();
    }
}
