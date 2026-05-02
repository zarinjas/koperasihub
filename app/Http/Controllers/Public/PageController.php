<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Cms\PublicPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PublicPageService $publicPageService,
    ) {
    }

    public function home(Request $request): Response
    {
        $page = $this->publicPageService->findHomepage();

        if (! $page) {
            return Inertia::render('Public/Pages/Home')->toResponse($request);
        }

        return Inertia::render('Public/Pages/Page', [
            'page' => $this->publicPageService->toPayload($page, $request->url()),
        ])->toResponse($request);
    }

    public function show(Request $request, string $slug): Response
    {
        $page = $this->publicPageService->findPublishedBySlug($slug);

        if (! $page) {
            return $this->notFoundResponse($request);
        }

        return Inertia::render('Public/Pages/Page', [
            'page' => $this->publicPageService->toPayload($page, $request->url()),
        ])->toResponse($request);
    }

    private function notFoundResponse(Request $request): Response
    {
        return Inertia::render('Public/Pages/NotFound', [
            'requestedPath' => $request->path(),
        ])->toResponse($request)->setStatusCode(404);
    }
}
