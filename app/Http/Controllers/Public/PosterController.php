<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Poster;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosterController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $posters = Poster::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->published()
            ->ordered()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Poster $poster) => [
                'id' => $poster->id,
                'title' => $poster->title,
                'image_url' => $poster->imageUrl(),
                'alt_text' => $poster->alt_text,
            ]);

        return Inertia::render('Public/Pages/Posters', [
            'posters' => $posters,
        ]);
    }
}