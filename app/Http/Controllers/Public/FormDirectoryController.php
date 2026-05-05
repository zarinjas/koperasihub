<?php

namespace App\Http\Controllers\Public;

use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormSubmissionStatus;
use App\Enums\FormVisibility;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreOnlineFormSubmissionRequest;
use App\Http\Requests\Public\UploadStampedFormRequest;
use App\Models\FormCategory;
use App\Models\FormSubmission;
use App\Models\OnlineForm;
use App\Services\Forms\FormSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormDirectoryController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly FormSubmissionService $submissions,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $categories = FormCategory::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->active()
            ->withCount(['forms as published_forms_count' => fn ($query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FormCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'published_forms_count' => $category->published_forms_count,
                'url' => route('public.forms.category', $category->slug),
            ])->all();

        $featuredForms = OnlineForm::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->published()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->with('category')
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (OnlineForm $form) => $this->serializeCard($form))
            ->all();

        return Inertia::render('Public/Pages/Forms/Index', [
            'filters' => ['search' => $search],
            'categories' => $categories,
            'featuredForms' => $featuredForms,
        ]);
    }

    public function category(FormCategory $category, Request $request): Response
    {
        $this->ensureSameCooperative($category);
        abort_unless($category->is_active, 404);

        $search = trim((string) $request->string('search'));
        $forms = $category->forms()
            ->published()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (OnlineForm $form) => $this->serializeCard($form))
            ->all();

        return Inertia::render('Public/Pages/Forms/Category', [
            'filters' => ['search' => $search],
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'forms' => $forms,
        ]);
    }

    public function show(OnlineForm $onlineForm): Response|RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_if($onlineForm->status !== FormStatus::Published, 404);

        if ($onlineForm->visibility === FormVisibility::MembersOnly && ! request()->user()?->isMember()) {
            return redirect()->guest(route('member.login'));
        }

        $onlineForm->load([
            'category',
            'sections' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'sections.fields' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
        ]);

        $defaultInstruction = 'Borang ini perlu dicetak dan mendapatkan tandatangan serta cop pengesahan sebelum dimuat naik semula.';

        return Inertia::render('Public/Pages/Forms/Show', [
            'formRecord' => [
                'id' => $onlineForm->id,
                'title' => $onlineForm->title,
                'slug' => $onlineForm->slug,
                'description' => $onlineForm->description,
                'visibility' => $onlineForm->visibility->value,
                'visibility_label' => $onlineForm->visibility === FormVisibility::MembersOnly ? 'Ahli sahaja' : 'Terbuka',
                'status' => $onlineForm->status->value,
                'success_message' => $onlineForm->success_message ?: 'Borang anda berjaya dihantar.',
                'submission_method' => $onlineForm->submission_method->value,
                'stamped_upload_instructions' => $onlineForm->stamped_upload_instructions ?: $defaultInstruction,
                'show_document_header' => $onlineForm->show_document_header,
                'document_code' => $onlineForm->document_code,
                'revision_no' => $onlineForm->revision_no,
                'effective_date' => $onlineForm->effective_date?->format('d/m/Y'),
                'document_title' => $onlineForm->document_title,
                'category_name' => $onlineForm->category?->name,
                'sections' => $onlineForm->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'description' => $section->description,
                        'page_break_before' => $section->page_break_before,
                        'fields' => $section->fields->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'label' => $field->label,
                                'field_key' => $field->field_key,
                                'type' => $field->type->value,
                                'placeholder' => $field->placeholder,
                                'help_text' => $field->help_text,
                                'is_required' => $field->is_required,
                                'options' => $field->options_json ?? [],
                                'display_mode' => $field->displayMode()->value,
                                'settings_json' => $field->settings_json ?? [],
                                'validation_json' => $field->validation_json ?? [],
                            ];
                        })->all(),
                    ];
                })->all(),
            ],
        ]);
    }

    public function store(StoreOnlineFormSubmissionRequest $request, OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_if($onlineForm->status !== FormStatus::Published, 404);

        $member = $request->user()?->member;
        $submission = $this->submissions->submit(
            $onlineForm->load('fields'),
            $request->validated(),
            $request->user(),
            $member,
        );

        if ($onlineForm->submission_method === FormSubmissionMethod::RequiresStampedUpload) {
            return redirect()->route('public.forms.next-step', [$onlineForm->slug, $submission]);
        }

        $message = ($onlineForm->success_message ?: 'Borang anda berjaya dihantar.')
            .' Rujukan: '.$submission->reference_no.'.';

        return redirect()
            ->route('public.forms.show', $onlineForm->slug)
            ->with('status', $message);
    }

    public function nextStep(OnlineForm $onlineForm, FormSubmission $submission): Response|RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_if($onlineForm->status !== FormStatus::Published, 404);
        abort_unless($submission->online_form_id === $onlineForm->id, 404);
        abort_unless($submission->status === FormSubmissionStatus::PendingStampUpload, 404);

        $defaultInstruction = 'Borang ini perlu dicetak dan mendapatkan tandatangan serta cop pengesahan sebelum dimuat naik semula.';

        return Inertia::render('Public/Pages/Forms/NextStep', [
            'formRecord' => [
                'id' => $onlineForm->id,
                'title' => $onlineForm->title,
                'slug' => $onlineForm->slug,
                'stamped_upload_instructions' => $onlineForm->stamped_upload_instructions ?: $defaultInstruction,
                'print_url' => route('admin.forms.submissions.print', [$onlineForm, $submission]),
            ],
            'submission' => [
                'id' => $submission->id,
                'reference_no' => $submission->reference_no,
                'status' => $submission->status->value,
                'upload_url' => route('public.forms.upload-stamped', [$onlineForm->slug, $submission]),
            ],
        ]);
    }

    public function uploadStamped(UploadStampedFormRequest $request, OnlineForm $onlineForm, FormSubmission $submission): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_if($onlineForm->status !== FormStatus::Published, 404);
        abort_unless($submission->online_form_id === $onlineForm->id, 404);
        abort_unless($submission->status === FormSubmissionStatus::PendingStampUpload, 404);

        $this->submissions->uploadStampedFile($submission, $request->file('stamped_file'));

        $message = 'Borang bercop berjaya dimuat naik. Rujukan: '.$submission->reference_no.'. Pihak koperasi akan menyemak permohonan anda.';

        return redirect()
            ->route('public.forms.show', $onlineForm->slug)
            ->with('status', $message);
    }

    private function serializeCard(OnlineForm $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'slug' => $form->slug,
            'description' => $form->description,
            'category_name' => $form->category?->name,
            'visibility' => $form->visibility->value,
            'visibility_label' => $form->visibility === FormVisibility::MembersOnly ? 'Ahli sahaja' : 'Terbuka',
            'url' => route('public.forms.show', $form->slug),
        ];
    }
}
