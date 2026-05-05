<?php

namespace App\Http\Controllers\Member;

use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Services\FinancingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FinancingController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);

        $categories = FinancingCategory::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->with(['products' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'type' => $category->type->value,
                'type_label' => $category->type->label(),
                'products' => $category->products->map(fn (FinancingProduct $product) => $this->serializeProduct($product))->all(),
            ])
            ->all();

        $myApplications = $member
            ? $member->financingApplications()->with('product')->latest('submitted_at')->get()->map(fn ($application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'product_name' => $application->product?->name,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'show_url' => route('member.financing.applications.show', $application),
            ])->all()
            : [];

        $guarantorRequests = $member
            ? $member->financingGuarantorRequests()->where('status', 'pending')->count()
            : 0;

        return Inertia::render('Member/Pages/Financing/Index', [
            'categories' => $categories,
            'myApplications' => $myApplications,
            'guarantorRequestsCount' => $guarantorRequests,
            'memberLinked' => (bool) $member,
        ]);
    }

    public function showProduct(Request $request, FinancingProduct $product): Response
    {
        $cooperativeId = $this->activeCooperativeId($request);
        abort_unless($product->cooperative_id === $cooperativeId && $product->is_active, 404);

        $product->load('category');

        return Inertia::render('Member/Pages/Financing/ProductShow', [
            'product' => $this->serializeProduct($product, withCategory: true),
        ]);
    }

    public function downloadProductDocument(Request $request, FinancingProduct $product, string $documentKey)
    {
        $cooperativeId = $this->activeCooperativeId($request);
        abort_unless($product->cooperative_id === $cooperativeId && $product->is_active, 404);

        $definition = FinancingProduct::PRODUCT_DOCUMENTS[$documentKey] ?? null;
        abort_unless($definition, 404);

        $path = $product->{$definition['path']};
        $name = $product->{$definition['name']} ?: basename((string) $path);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $name);
    }

    public function guarantorSearch(Request $request): JsonResponse
    {
        $member = $this->currentMember($request);
        $search = trim((string) $request->string('search'));

        return response()->json([
            'results' => $this->financing->guarantorSearchResults($member, $search),
        ]);
    }

    private function serializeProduct(FinancingProduct $product, bool $withCategory = false): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'eligibility_terms' => $product->eligibility_terms,
            'product_terms' => $product->product_terms,
            'application_notes' => $product->application_notes,
            'application_instructions' => $product->application_instructions,
            'required_documents_note' => $product->required_documents_note,
            'officer_contact_name' => $product->officer_contact_name,
            'officer_contact_phone' => $product->officer_contact_phone,
            'officer_contact_email' => $product->officer_contact_email,
            'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
            'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
            'min_tenure_months' => $product->min_tenure_months,
            'max_tenure_months' => $product->max_tenure_months,
            'rate_image_url' => $product->rate_image_path ? Storage::disk('public')->url($product->rate_image_path) : null,
            'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
            'rate_note' => $product->rate_note,
            'requires_guarantor' => $product->requires_guarantor,
            'guarantor_count' => $product->guarantor_count,
            'required_documents' => $product->required_documents_json ?? [],
            'product_documents' => collect(FinancingProduct::PRODUCT_DOCUMENTS)
                ->map(function (array $definition, string $key) use ($product): ?array {
                    $path = $product->{$definition['path']};

                    if (! $path) {
                        return null;
                    }

                    return [
                        'key' => $key,
                        'label' => $definition['label'],
                        'download_label' => $definition['download_label'],
                        'file_name' => $product->{$definition['name']} ?: basename($path),
                        'download_url' => route('member.financing.products.documents.download', [$product, $key]),
                    ];
                })
                ->filter()
                ->values()
                ->all(),
            'category' => $withCategory ? [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
                'type_label' => $product->category?->type?->label(),
            ] : null,
            'apply_url' => route('member.financing.applications.create', ['product' => $product->id]),
        ];
    }
}
