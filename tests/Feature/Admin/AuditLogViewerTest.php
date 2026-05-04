<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Enums\PageSectionType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\Page;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);

        app(SettingsService::class)->clearCache();

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_admin_can_view_and_filter_audit_logs_with_detail_panel(): void
    {
        $page = Page::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'title' => 'Halaman Audit Demo',
            'status' => PageStatus::Published->value,
            'template' => PageTemplate::Default->value,
        ]);

        $log = AuditLog::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'actor_id' => $this->admin->id,
            'action' => 'page_published',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'published', 'title' => $page->title],
            'metadata' => ['source' => 'test'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/audit-logs/{$log->id}?action=page_published")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/AuditLogs/Index', false)
                ->where('filters.action', 'page_published')
                ->has('auditLogs.data', 1)
                ->where('auditLogs.data.0.action', 'page_published')
                ->where('selectedLog.id', $log->id)
                ->where('selectedLog.action_label', 'Halaman diterbitkan')
                ->where('selectedLog.subject_label', 'Halaman Audit Demo')
            );
    }

    public function test_audit_log_viewer_formats_legacy_dot_action_labels(): void
    {
        $page = Page::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'title' => 'Halaman Legacy',
            'status' => PageStatus::Draft->value,
            'template' => PageTemplate::Default->value,
        ]);

        $log = AuditLog::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'actor_id' => $this->admin->id,
            'action' => 'announcement.archived',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
            'new_values' => ['title' => $page->title],
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/audit-logs/{$log->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedLog.action', 'announcement.archived')
                ->where('selectedLog.action_label', 'Pengumuman diarkibkan')
            );
    }

    public function test_audit_logs_route_requires_permission(): void
    {
        $restrictedAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $restrictedAdmin->givePermissionTo(AccessControl::PERMISSION_VIEW_SETTINGS);

        $this->actingAs($restrictedAdmin)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_audit_logs_are_read_only_from_normal_ui_routes(): void
    {
        $log = AuditLog::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'actor_id' => $this->admin->id,
            'action' => 'settings_updated',
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/audit-logs/{$log->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'settings_updated',
        ]);
    }

    public function test_settings_page_document_and_section_actions_are_audit_logged(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings', [
                'brand' => [
                    'name' => 'Koperasi Demo Berhad',
                    'short_name' => 'KDB',
                    'registration_no' => 'REG-123',
                    'logo_path' => '',
                    'primary_color' => '#0F766E',
                    'secondary_color' => '#1D4ED8',
                ],
                'contact' => [
                    'address_line_1' => 'Jalan Demo 1',
                    'address_line_2' => '',
                    'city' => 'Kajang',
                    'state' => 'Selangor',
                    'postcode' => '43000',
                    'country' => 'Malaysia',
                    'phone' => '0388889999',
                    'email' => 'admin@demo.test',
                    'whatsapp' => '60128889999',
                    'website_url' => 'https://demo.test',
                ],
                'social' => [
                    'facebook_url' => 'https://facebook.com/demo',
                    'instagram_url' => 'https://instagram.com/demo',
                    'linkedin_url' => 'https://linkedin.com/company/demo',
                ],
                'seo' => [
                    'meta_title' => 'Demo Koperasi',
                    'meta_description' => 'Portal demo koperasi',
                ],
                'system' => [
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'date_format' => 'd/m/Y',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/admin/cms/pages', [
                'title' => 'Halaman Tentang',
                'slug' => 'halaman-tentang',
                'template' => PageTemplate::Default->value,
                'status' => PageStatus::Draft->value,
            ])
            ->assertRedirect();

        $page = Page::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/publish")
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/unpublish")
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/sections", [
                'type' => PageSectionType::Hero->value,
                'name' => 'Hero Utama',
                'data' => ['title' => 'Selamat Datang'],
                'settings' => [],
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/admin/documents', [
                'title' => 'Dokumen Polisi',
                'visibility' => DocumentVisibility::MembersOnly->value,
                'status' => DocumentStatus::Draft->value,
                'file' => UploadedFile::fake()->create('dokumen-polisi.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $document = Document::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->delete("/admin/documents/{$document->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings_updated',
            'subject_type' => Cooperative::class,
            'subject_id' => $this->cooperative->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'page_created',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'page_unpublished',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'section_created',
            'subject_type' => 'App\\Models\\PageSection',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_uploaded',
            'subject_type' => Document::class,
            'subject_id' => $document->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_deleted',
            'subject_type' => Document::class,
            'subject_id' => $document->id,
        ]);
    }
}
