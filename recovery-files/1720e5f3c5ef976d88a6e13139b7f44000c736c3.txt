<?php

namespace App\Http\Controllers\Member;

use App\Enums\FinancingFieldType;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Models\Member;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FinancingController extends MemberPortalController
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);

        $categories = FinancingCategory::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->ordered()
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'type' => $category->type->value,
                'type_label' => $category->type->label(),
                'products_count' => $category->products()->where('is_active', true)->count(),
            ])
            ->all();

        $products = FinancingProduct::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->ordered()
            ->with('category')
            ->get()
            ->groupBy('financing_category_id')
            ->map(function ($group) {
                $category = $group->first()->category;

                return [
                    'category_id' => $category?->id,
                    'category_name' => $category?->name,
                    'category_type' => $category?->type->value,
                    'category_type_label' => $category?->type->label(),
                    'items' => $group->map(fn (FinancingProduct $product) => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                        'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                        'min_tenure_months' => $product->min_tenure_months,
                        'max_tenure_months' => $product->max_tenure_months,
                'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
                'rate_tiers_json' => $product->rate_tiers_json ?? [],
                'requires_guarantor' => $product->requires_guarantor,
                'guarantor_count' => $product->guarantor_count,
                'show_url' => route('member.financing.products.show', $product),
                'apply_url' => route('member.financing.applications.create', ['product' => $product->id]),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $applications = $member
            ? $member->financingApplications()
                ->with(['product', 'category'])
                ->latest('submitted_at')
                ->take(5)
                ->get()
                ->map(fn ($application) => [
                    'id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'product_name' => $application->product?->name,
                    'category_name' => $application->category?->name,
                    'amount_requested' => $application->amount_requested !== null ? 'RM ' . number_format((float) $application->amount_requested, 0, '.', ',') : '-',
                    'tenure_months' => $application->tenure_months,
                    'status' => $application->status->value,
                    'status_label' => $application->status->label(),
                    'submitted_at' => $application->submitted_at?->format('d/m/Y'),
                    'show_url' => route('member.financing.applications.show', $application),
                ])
                ->all()
            : [];

        $guarantorRequestsCount = $member
            ? $member->financingGuarantorRequests()->where('status', 'pending')->count()
            : 0;

        return Inertia::render('Member/Pages/Financing/Index', [
            'categories' => $categories,
            'products' => $products,
            'applications' => $applications,
            'guarantorRequestsCount' => $guarantorRequestsCount,
            'memberLinked' => (bool) $member,
        ]);
    }

    public function showProduct(Request $request, FinancingProduct $product): Response
    {
        $cooperativeId = $this->activeCooperativeId($request);
        abort_unless($product->cooperative_id === $cooperativeId && $product->is_active, 404);

        $product->load([
            'category',
            'sections' => fn ($query) => $query->where('is_active', true)->latest(),
            'sections.fields' => fn ($query) => $query->where('is_active', true)->latest(),
        ]);

        return Inertia::render('Member/Pages/Financing/ProductShow', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                'min_tenure_months' => $product->min_tenure_months,
                'max_tenure_months' => $product->max_tenure_months,
                'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
                'rate_tiers_json' => $product->rate_tiers_json ?? [],
                'requires_guarantor' => $product->requires_guarantor,
                'guarantor_count' => $product->guarantor_count,
                'requires_stamped_upload' => $product->requires_stamped_upload,
                'stamped_upload_instructions' => $product->stamped_upload_instructions,
                'rate_image_url' => $product->rateImageUrl(),
                'rate_note' => $product->rate_note,
                'sections' => $product->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'fields' => $section->fields->map(fn (FinancingProductField $field) => $this->serializeProductField($field))->values()->all(),
                ])->values()->all(),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'type' => $product->category->type->value,
                    'type_label' => $product->category->type->label(),
                ] : null,
                'apply_url' => route('member.financing.applications.create', ['product' => $product->id]),
            ],
        ]);
    }

    public function guarantorSearch(Request $request): JsonResponse
    {
        $member = $this->currentMember($request);
        $search = trim((string) $request->string('search'));

        $results = Member::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->where('id', '!=', $member->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->limit(20)
            ->get()
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->full_name,
                'member_no' => $m->member_no,
            ])
            ->all();

        return response()->json($results);
    }

    private function serializeProductField(FinancingProductField $field): array
    {
        $data = [
            'id' => $field->id,
            'label' => $field->label,
            'field_key' => $field->field_key,
            'type' => $field->type->value,
            'type_label' => $field->type->label(),
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'is_required' => $field->is_required,
            'options_json' => $field->options_json ?? [],
            'validation_json' => $field->validation_json ?? [],
            'settings_json' => $field->settings_json ?? [],
        ];

        if ($field->type === FinancingFieldType::Image) {
            $filePath = $field->settings_json['file_path'] ?? null;
            $data['image_url'] = $filePath ? asset('storage/' . $filePath) : null;
        }

        if ($field->type === FinancingFieldType::PdfDocument) {
            $filePath = $field->settings_json['file_path'] ?? null;
            $data['pdf_url'] = $filePath ? asset('storage/' . $filePath) : null;
        }

        return $data;
    }

    public function calculator(Request $request): Response
    {
        $cooperativeId = $this->activeCooperativeId($request);

        $products = FinancingProduct::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->ordered()
            ->with('category')
            ->get()
            ->map(fn (FinancingProduct $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category_name' => $product->category?->name,
                'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                'min_tenure_months' => $product->min_tenure_months,
                'max_tenure_months' => $product->max_tenure_months,
                'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
                'rate_tiers_json' => $product->rate_tiers_json ?? [],
                'apply_url' => route('member.financing.applications.create', ['product' => $product->id]),
            ])
            ->values()
            ->all();

        return Inertia::render('Member/Pages/Financing/Calculator', [
            'products' => $products,
        ]);
    }
}
