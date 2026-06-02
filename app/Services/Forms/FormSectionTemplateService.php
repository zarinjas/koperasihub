<?php

namespace App\Services\Forms;

use App\Enums\FormFieldDisplayMode;
use App\Enums\FormFieldType;
use App\Models\FormSection;
use App\Models\FormSectionTemplate;
use App\Models\OnlineForm;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormSectionTemplateService
{
    public function availableTemplates(?int $cooperativeId): array
    {
        $presets = collect($this->presetTemplates())
            ->map(fn (array $template) => [
                'ref' => 'preset:'.$template['key'],
                'name' => $template['name'],
                'title' => $template['title'],
                'description' => $template['description'],
                'source' => 'preset',
                'fields_count' => count($template['fields']),
            ]);

        $saved = FormSectionTemplate::query()
            ->where('cooperative_id', $cooperativeId)
            ->orderBy('name')
            ->get()
            ->map(fn (FormSectionTemplate $template) => [
                'ref' => 'saved:'.$template->id,
                'name' => $template->name,
                'title' => $template->title,
                'description' => $template->description,
                'source' => 'saved',
                'fields_count' => count($template->fields_json ?? []),
            ]);

        return $presets->concat($saved)->values()->all();
    }

    public function saveSectionAsTemplate(FormSection $section, User $user): FormSectionTemplate
    {
        $section->loadMissing(['fields' => fn ($query) => $query->latest()]);

        return FormSectionTemplate::query()->create([
            'cooperative_id' => $section->form->cooperative_id,
            'created_by' => $user->id,
            'name' => $section->title,
            'slug' => $this->uniqueTemplateSlug($section->form->cooperative_id, $section->title),
            'title' => $section->title,
            'description' => $section->description,
            'page_break_before' => $section->page_break_before,
            'fields_json' => $section->fields->map(fn ($field) => [
                'label' => $field->label,
                'field_key' => $field->field_key,
                'type' => $field->type->value,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'options_json' => $field->options_json ?? [],
                'validation_json' => $field->validation_json ?? [],
                'settings_json' => $field->settings_json ?? [],
                'is_active' => $field->is_active,
            ])->values()->all(),
        ]);
    }

    public function createSectionFromTemplate(OnlineForm $form, string $templateRef): FormSection
    {
        $template = $this->resolveTemplate($templateRef, $form->cooperative_id);

        return DB::transaction(function () use ($form, $template): FormSection {
            $section = $form->sections()->create([
                'title' => $template['title'],
                'description' => $template['description'],
                'page_break_before' => (bool) ($template['page_break_before'] ?? false),
                'is_active' => true,
            ]);

            foreach (collect($template['fields'])->sortByDesc('created_at')->values() as $index => $field) {
                $section->fields()->create([
                    'online_form_id' => $form->id,
                    'label' => $field['label'],
                    'field_key' => $this->uniqueFieldKey($form, $field['field_key'] ?: $field['label']),
                    'type' => $field['type'],
                    'placeholder' => $field['placeholder'] ?? null,
                    'help_text' => $field['help_text'] ?? null,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'options_json' => $field['options_json'] ?? [],
                    'validation_json' => $field['validation_json'] ?? [],
                    'settings_json' => $field['settings_json'] ?? [],
                    'is_active' => (bool) ($field['is_active'] ?? true),
                ]);
            }

            return $section->load('fields');
        });
    }

    private function resolveTemplate(string $templateRef, ?int $cooperativeId): array
    {
        if (str_starts_with($templateRef, 'preset:')) {
            $key = Str::after($templateRef, 'preset:');
            $template = collect($this->presetTemplates())->firstWhere('key', $key);

            abort_unless($template, 404);

            return $template;
        }

        abort_unless(str_starts_with($templateRef, 'saved:'), 404);
        $id = (int) Str::after($templateRef, 'saved:');

        $template = FormSectionTemplate::query()
            ->where('cooperative_id', $cooperativeId)
            ->findOrFail($id);

        return [
            'title' => $template->title,
            'description' => $template->description,
            'page_break_before' => $template->page_break_before,
            'fields' => $template->fields_json ?? [],
        ];
    }

    private function uniqueFieldKey(OnlineForm $form, string $baseKey): string
    {
        $key = Str::snake($baseKey);
        $candidate = $key;
        $counter = 2;

        while ($form->fields()->withTrashed()->where('field_key', $candidate)->exists()) {
            $candidate = $key.'_'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniqueTemplateSlug(?int $cooperativeId, string $name): string
    {
        $slug = Str::slug($name) ?: 'template-seksyen';
        $candidate = $slug;
        $counter = 2;

        while (FormSectionTemplate::query()
            ->where('cooperative_id', $cooperativeId)
            ->withTrashed()
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function presetTemplates(): array
    {
        return [
            [
                'key' => 'maklumat-peribadi',
                'name' => 'Maklumat Peribadi',
                'title' => 'Maklumat Peribadi',
                'description' => 'Butiran asas pemohon atau ahli.',
                'page_break_before' => false,
                'fields' => [
                    $this->field('Nama penuh', 'full_name', FormFieldType::ShortText, true, 1),
                    $this->field('No. Kad Pengenalan', 'identity_no', FormFieldType::IdentityNo, true, 2),
                    $this->field('Tarikh lahir', 'date_of_birth', FormFieldType::Date, false, 3),
                    $this->field('No. Telefon', 'phone', FormFieldType::Phone, true, 4),
                    $this->field('Emel', 'email', FormFieldType::Email, false, 5),
                ],
            ],
            [
                'key' => 'maklumat-pekerjaan',
                'name' => 'Maklumat Pekerjaan',
                'title' => 'Maklumat Pekerjaan',
                'description' => 'Butiran pekerjaan dan pendapatan asas.',
                'page_break_before' => false,
                'fields' => [
                    $this->field('Nama majikan', 'employer_name', FormFieldType::ShortText, false, 1),
                    $this->field('Jawatan', 'job_title', FormFieldType::ShortText, false, 2),
                    $this->field('Jabatan', 'department', FormFieldType::ShortText, false, 3),
                    $this->field('Pendapatan bulanan', 'monthly_income', FormFieldType::Currency, false, 4),
                ],
            ],
            [
                'key' => 'maklumat-waris',
                'name' => 'Maklumat Waris',
                'title' => 'Maklumat Waris',
                'description' => 'Maklumat waris atau orang untuk dihubungi.',
                'page_break_before' => false,
                'fields' => [
                    $this->field('Nama waris', 'nominee_name', FormFieldType::ShortText, true, 1),
                    $this->field('Hubungan', 'nominee_relationship', FormFieldType::ShortText, true, 2),
                    $this->field('No. Telefon waris', 'nominee_phone', FormFieldType::Phone, false, 3),
                    $this->field('Alamat waris', 'nominee_address', FormFieldType::LongText, false, 4),
                ],
            ],
            [
                'key' => 'pengesahan-tandatangan',
                'name' => 'Pengesahan & Tandatangan',
                'title' => 'Pengesahan & Tandatangan',
                'description' => 'Akuan rasmi dan ruang tandatangan pemohon.',
                'page_break_before' => true,
                'fields' => [
                    $this->field('Nota Pengesahan', 'verification_note', FormFieldType::Note, false, 1, 'Saya mengesahkan bahawa semua maklumat yang diberikan adalah benar, tepat, dan lengkap.'),
                    $this->field('Akuan pemohon', 'declaration', FormFieldType::AgreementCheckbox, true, 2, 'Saya mengesahkan semua maklumat adalah benar.'),
                    $this->field('Tandatangan pemohon', 'signature', FormFieldType::Signature, true, 3),
                ],
            ],
            [
                'key' => 'untuk-kegunaan-pejabat',
                'name' => 'Untuk Kegunaan Pejabat',
                'title' => 'Untuk Kegunaan Pejabat',
                'description' => 'Ruang semakan, cop, dan pengesahan pejabat.',
                'page_break_before' => true,
                'fields' => [
                    $this->field('Nota Pejabat', 'office_note', FormFieldType::Note, false, 1, 'Diisi oleh pegawai yang menyemak borang ini.'),
                    [
                        ...$this->field('Ruang kegunaan pejabat', 'office_use_box', FormFieldType::OfficeUseBox, false, 2, 'Ruang untuk cop rasmi, tandatangan, semakan, dan catatan pejabat.'),
                        'settings_json' => ['print_only' => true, 'display_mode' => FormFieldDisplayMode::PrintOnly->value],
                    ],
                ],
            ],
        ];
    }

    private function field(
        string $label,
        string $fieldKey,
        FormFieldType $type,
        bool $required,
        int $sortOrder,
        ?string $helpText = null,
    ): array {
        return [
            'label' => $label,
            'field_key' => $fieldKey,
            'type' => $type->value,
            'placeholder' => null,
            'help_text' => $helpText,
            'is_required' => $required,
            'options_json' => [],
            'validation_json' => [],
            'settings_json' => ['display_mode' => FormFieldDisplayMode::OnlineAndPrint->value],
            'is_active' => true,
        ];
    }
}