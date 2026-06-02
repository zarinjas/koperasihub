<?php

namespace App\Http\Controllers\Member;

use App\Models\Poster;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosterController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $posters = Poster::query()
            ->where('cooperative_id', $this->activeCooperativeId($request))
            ->published()
            ->ordered()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%");
            })
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Poster $poster) => $this->serializePoster($poster));

        return Inertia::render('Member/Pages/Posters/Index', [
            'posters' => $posters,
            'filters' => ['search' => $search],
        ]);
    }

    private function serializePoster(Poster $poster): array
    {
        return [
            'id' => $poster->id,
            'title' => $poster->title,
            'image_url' => $poster->imageUrl(),
            'alt_text' => $poster->alt_text,
        ];
    }
}