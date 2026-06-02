<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create();
        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_document_upload_validation_rejects_unsupported_file_type(): void
    {
        $category = DocumentCategory::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Borang',
            'slug' => 'forms',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->from('/admin/documents/create')
            ->post('/admin/documents', [
                'title' => 'Dokumen Tidak Sah',
                'document_category_id' => $category->id,
                'visibility' => DocumentVisibility::Public->value,
                'status' => DocumentStatus::Published->value,
                'file' => UploadedFile::fake()->create('script.txt', 20, 'text/plain'),
            ])
            ->assertRedirect('/admin/documents/create')
            ->assertSessionHasErrors('file');
    }

    public function test_public_download_listing_only_shows_public_documents(): void
    {
        $category = DocumentCategory::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Borang',
            'slug' => 'forms',
            'is_active' => true,
        ]);

        Storage::disk('local')->put('documents/borang-awam.pdf', 'public');
        Storage::disk('local')->put('documents/panduan-admin.pdf', 'private');

        Document::factory()->withCategory($category)->create([
            'cooperative_id' => $this->cooperative->id,
            'uploaded_by' => $this->admin->id,
            'title' => 'Borang Awam',
            'file_path' => 'documents/borang-awam.pdf',
            'file_name' => 'borang-awam.pdf',
            'visibility' => DocumentVisibility::Public->value,
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);

        Document::factory()->withCategory($category)->create([
            'cooperative_id' => $this->cooperative->id,
            'uploaded_by' => $this->admin->id,
            'title' => 'Panduan Admin',
            'file_path' => 'documents/panduan-admin.pdf',
            'file_name' => 'panduan-admin.pdf',
            'visibility' => DocumentVisibility::AdminOnly->value,
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/downloads')
            ->assertOk()
            ->assertSee('Borang Awam')
            ->assertDontSee('Panduan Admin');
    }

    public function test_non_public_document_is_protected_from_public_access_but_admin_can_download_it(): void
    {
        Storage::disk('local')->put('documents/garis-panduan-admin.pdf', 'internal');

        $document = Document::factory()->adminOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'uploaded_by' => $this->admin->id,
            'title' => 'Garis Panduan Admin',
            'file_path' => 'documents/garis-panduan-admin.pdf',
            'file_name' => 'garis-panduan-admin.pdf',
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);

        $this->get("/downloads/{$document->id}/download")
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get("/admin/documents/{$document->id}/download")
            ->assertOk()
            ->assertDownload('garis-panduan-admin.pdf');
    }
}