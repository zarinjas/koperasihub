<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $services = Service::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->published()
            ->ordered()
            ->get()
            ->map(fn (Service $service) => $this->serializeService($service))
            ->all();

        return Inertia::render('Public/Pages/Services/Index', [
            'services' => $services,
        ])->toResponse($request);
    }

    public function show(Request $request, string $slug): Response
    {
        $service = Service::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->forPublicSlug($slug)
            ->first();

        abort_unless($service, 404);

        return Inertia::render('Public/Pages/Services/Show', [
            'service' => $this->serializeService($service, includeBody: true),
        ])->toResponse($request);
    }

    private function serializeService(Service $service, bool $includeBody = false): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'slug' => $service->slug,
            'category' => $service->category,
            'summary' => $service->summary,
            'description' => $includeBody ? $service->description : null,
            'image_path' => $service->image_path,
            'image_url' => $service->imageUrl(),
            'icon' => $service->icon,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'whatsapp' => $service->whatsapp,
            'button_text' => $service->button_text,
            'button_url' => $service->button_url,
            'detail_url' => '/perkhidmatan/'.$service->slug,
        ];
    }
}