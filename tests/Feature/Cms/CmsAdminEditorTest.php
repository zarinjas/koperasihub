<?php

namespace Tests\Feature\Cms;

use App\Enums\PageSectionType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Cooperative;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsAdminEditorTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create();
        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->member = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
        ]);
        $this->member->assignRole(AccessControl::ROLE_MEMBER);
    }

    public function test_admin_can_view_cms_page_index(): void
    {
        Page::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'title' => 'Tentang Kami',
            'slug' => 'tentang-kami',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/cms/pages')
            ->assertOk()
            ->assertSee('Halaman CMS')
            ->assertSee('Tentang Kami');
    }

    public function test_member_is_redirected_away_from_admin_cms(): void
    {
        $this->actingAs($this->member)
            ->get('/admin/cms/pages')
            ->assertRedirect(route('member.dashboard'));
    }

    public function test_admin_can_create_update_and_change_page_status(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/cms/pages', [
                'title' => 'Hubungi Kami',
                'slug' => 'hubungi-kami',
                'template' => PageTemplate::Contact->value,
                'summary' => 'Ringkasan halaman hubungan.',
                'status' => PageStatus::Draft->value,
                'meta_title' => 'Hubungi Kami',
                'meta_description' => 'Maklumat untuk hubungi koperasi.',
                'published_at' => null,
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'hubungi-kami')->firstOrFail();

        $this->assertSame($this->cooperative->id, $page->cooperative_id);
        $this->assertSame(PageStatus::Draft, $page->status);

        $this->actingAs($this->admin)
            ->patch("/admin/cms/pages/{$page->id}", [
                'title' => 'Hubungi Kami Sekarang',
                'slug' => 'hubungi-kami',
                'template' => PageTemplate::Contact->value,
                'summary' => 'Ringkasan terkini.',
                'status' => PageStatus::Draft->value,
                'meta_title' => 'Hubungi Kami Sekarang',
                'meta_description' => 'Maklumat yang dikemas kini.',
                'published_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/publish")
            ->assertRedirect();

        $page->refresh();
        $this->assertSame(PageStatus::Published, $page->status);
        $this->assertNotNull($page->published_at);

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/unpublish")
            ->assertRedirect();

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }

    public function test_admin_can_manage_page_sections_and_reorder_them(): void
    {
        $page = Page::factory()->homepage()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/cms/pages/{$page->id}/sections", [
                'type' => PageSectionType::Hero->value,
                'name' => 'Hero Baharu',
                'data' => [
                    'title' => 'Tajuk Hero',
                    'subtitle' => 'Penerangan hero',
                    'primary_button_text' => 'Daftar',
                    'primary_button_url' => '/member/register',
                ],
                'settings' => [
                    'variant' => 'image_right',
                    'background' => 'default',
                    'spacing' => 'xl',
                    'alignment' => 'left',
                    'container' => 'default',
                ],
                'is_active' => true,
            ])
            ->assertRedirect();

        $hero = $page->sections()->firstOrFail();
        $this->assertSame('Hero Baharu', $hero->name);

        $stats = PageSection::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'page_id' => $page->id,
            'type' => PageSectionType::Stats->value,
            'name' => 'Statistik',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/page-sections/{$hero->id}", [
                'type' => PageSectionType::Hero->value,
                'name' => 'Hero Dikemas Kini',
                'is_active' => false,
                'data' => [
                    'title' => 'Tajuk Hero Dikemas Kini',
                    'subtitle' => 'Penerangan baharu',
                    'primary_button_text' => 'Hubungi Kami',
                    'primary_button_url' => '/hubungi',
                ],
                'settings' => [
                    'variant' => 'split',
                    'background' => 'gradient',
                    'spacing' => 'lg',
                    'alignment' => 'center',
                    'container' => 'wide',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $hero->refresh();
        $this->assertSame('Hero Dikemas Kini', $hero->name);
        $this->assertFalse($hero->is_active);
        $this->assertSame('split', $hero->settings['variant']);

        $this->actingAs($this->admin)
            ->delete("/admin/page-sections/{$hero->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('page_sections', ['id' => $hero->id]);
    }

    public function test_section_validation_rejects_unsafe_keys(): void
    {
        $page = Page::factory()->homepage()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->from("/admin/cms/pages/{$page->id}/sections")
            ->post("/admin/cms/pages/{$page->id}/sections", [
                'type' => PageSectionType::Hero->value,
                'name' => 'Hero Tidak Sah',
                'data' => [
                    'title' => 'Tajuk Hero',
                    'css' => 'body { display:none; }',
                ],
                'settings' => [
                    'variant' => 'image_right',
                ],
            ])
            ->assertRedirect("/admin/cms/pages/{$page->id}/sections")
            ->assertSessionHasErrors('data.css');
    }
}