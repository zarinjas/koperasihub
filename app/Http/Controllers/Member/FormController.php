<?php

namespace App\Http\Controllers\Member;

use App\Models\FormCategory;
use App\Models\OnlineForm;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $cooperativeId = $this->activeCooperativeId($request);

        $categories = FormCategory::query()
            ->where('cooperative_id', $cooperativeId)
            ->active()
            ->withCount(['forms as published_forms_count' => fn ($query) => $query->published()])
            ->latest()
            ->get()
            ->map(fn (FormCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'published_forms_count' => $category->published_forms_count,
                'url' => route('public.forms.category', $category->slug),
            ])
            ->all();

        $featuredForms = OnlineForm::query()
            ->where('cooperative_id', $cooperativeId)
            ->published()
            ->where(function ($query) {
                $query->whereDoesntHave('category')
                    ->orWhereHas('category', fn ($q) => $q->where('is_active', true));
            })
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->with('category')
            ->latest('updated_at')
            ->limit(12)
            ->get()
            ->map(fn (OnlineForm $form) => $this->serializeCard($form))
            ->all();

        return Inertia::render('Member/Pages/Forms/Index', [
            'filters' => ['search' => $search],
            'categories' => $categories,
            'featuredForms' => $featuredForms,
        ]);
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
            'visibility_label' => $form->visibility->value === 'members_only' ? 'Ahli sahaja' : 'Terbuka',
            'url' => route('public.forms.show', $form->slug),
        ];
    }
}