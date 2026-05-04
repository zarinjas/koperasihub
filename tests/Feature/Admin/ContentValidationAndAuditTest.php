<?php

namespace Tests\Feature\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Enums\ServiceStatus;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\Service;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentValidationAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $inactiveCooperative;

    protected Cooperative $activeCooperative;

    protected User $superAdmin;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->inactiveCooperative = Cooperative::factory()->create([
            'status' => 'inactive',
        ]);
        $this->activeCooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);

        app(SettingsService::class)->clearCache();

        $this->superAdmin = User::factory()->create([
            'cooperative_id' => null,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $this->superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->activeCooperative->id,
            'user_type' => 'admin',
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_service_creation_uses_active_cooperative_scope_for_super_admin_validation(): void
    {
        Service::factory()->create([
            'cooperative_id' => $this->inactiveCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Khidmat Ahli',
            'slug' => 'khidmat-ahli',
        ]);

        $this->actingAs($this->superAdmin)
            ->post('/admin/services', [
                'title' => 'Khidmat Ahli',
                'slug' => 'Khidmat Ahli',
                'status' => ServiceStatus::Draft->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $service = Service::query()
            ->where('cooperative_id', $this->activeCooperative->id)
            ->where('slug', 'khidmat-ahli')
            ->first();

        $this->assertNotNull($service);
    }

    public function test_service_creation_fails_closed_when_no_active_cooperative_exists(): void
    {
        $this->activeCooperative->update(['status' => 'inactive']);
        app(SettingsService::class)->clearCache();

        $this->actingAs($this->superAdmin)
            ->from('/admin/services/create')
            ->post('/admin/services', [
                'title' => 'Khidmat Ahli',
                'slug' => 'Khidmat Ahli',
                'status' => ServiceStatus::Draft->value,
            ])
            ->assertRedirect('/admin/services/create')
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('services', 0);
    }

    public function test_service_creation_rejects_slug_after_normalization_before_hitting_database_constraint(): void
    {
        Service::factory()->create([
            'cooperative_id' => $this->activeCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Perkhidmatan Demo',
            'slug' => 'perkhidmatan-demo',
        ]);

        $this->actingAs($this->superAdmin)
            ->from('/admin/services/create')
            ->post('/admin/services', [
                'title' => 'Perkhidmatan Lain',
                'slug' => 'Perkhidmatan Demo',
                'status' => ServiceStatus::Draft->value,
            ])
            ->assertRedirect('/admin/services/create')
            ->assertSessionHasErrors('slug');
    }

    public function test_announcement_creation_uses_active_cooperative_scope_and_normalized_slug_validation(): void
    {
        Announcement::factory()->create([
            'cooperative_id' => $this->inactiveCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Hebahan Komuniti',
            'slug' => 'hebahan-komuniti',
        ]);

        $this->actingAs($this->superAdmin)
            ->post('/admin/announcements', [
                'title' => 'Hebahan Komuniti',
                'slug' => 'Hebahan Komuniti',
                'audience' => AnnouncementAudience::Public->value,
                'status' => AnnouncementStatus::Draft->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Announcement::factory()->create([
            'cooperative_id' => $this->activeCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'title' => 'Hebahan Demo',
            'slug' => 'hebahan-demo',
        ]);

        $this->actingAs($this->superAdmin)
            ->from('/admin/announcements/create')
            ->post('/admin/announcements', [
                'title' => 'Hebahan Baharu',
                'slug' => 'Hebahan Demo',
                'audience' => AnnouncementAudience::Public->value,
                'status' => AnnouncementStatus::Draft->value,
            ])
            ->assertRedirect('/admin/announcements/create')
            ->assertSessionHasErrors('slug');
    }

    public function test_sensitive_service_and_announcement_actions_are_audit_logged(): void
    {
        $service = Service::factory()->create([
            'cooperative_id' => $this->activeCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => ServiceStatus::Draft->value,
        ]);

        $announcement = Announcement::factory()->create([
            'cooperative_id' => $this->activeCooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => AnnouncementStatus::Draft->value,
            'audience' => AnnouncementAudience::Public->value,
            'is_pinned' => false,
        ]);

        $this->actingAs($this->admin)->post("/admin/services/{$service->id}/publish")->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/services/{$service->id}/archive")->assertRedirect();
        $this->actingAs($this->admin)->delete("/admin/services/{$service->id}")->assertRedirect();

        $this->actingAs($this->admin)->post("/admin/announcements/{$announcement->id}/publish")->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/announcements/{$announcement->id}/pin")->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/announcements/{$announcement->id}/unpin")->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/announcements/{$announcement->id}/archive")->assertRedirect();
        $this->actingAs($this->admin)->delete("/admin/announcements/{$announcement->id}")->assertRedirect();

        $this->assertSame([
            'announcement.archived',
            'announcement.deleted',
            'announcement.pinned',
            'announcement.unpinned',
            'announcement_published',
            'service.archived',
            'service.deleted',
            'service.published',
        ], AuditLog::query()->orderBy('action')->pluck('action')->all());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.deleted',
            'actor_id' => $this->admin->id,
            'cooperative_id' => $this->activeCooperative->id,
            'subject_type' => Service::class,
            'subject_id' => $service->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'announcement.pinned',
            'actor_id' => $this->admin->id,
            'cooperative_id' => $this->activeCooperative->id,
            'subject_type' => Announcement::class,
            'subject_id' => $announcement->id,
        ]);
    }
}
