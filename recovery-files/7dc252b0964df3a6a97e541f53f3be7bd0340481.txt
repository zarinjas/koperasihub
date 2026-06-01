<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnsuranApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ansuran\StoreApplicationRequest;
use App\Models\AnsuranCategory;
use App\Models\AnsuranProduct;
use App\Models\AnsuranTenureOption;
use App\Models\Member;
use App\Notifications\AnsuranApplicationSubmitted;
use App\Notifications\AnsuranGuarantorRequest;
use App\Services\AnsuranService;
use App\Services\NotificationRoutingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnsuranCatalogController extends Controller
{
    public function __construct(
        private readonly AnsuranService $ansuranService,
        private readonly SettingsService $settings,
        private readonly NotificationRoutingService $notificationRouter,
    ) {}

    public function index(Request $request)
    {
        $cooperativeId = request()->user()->cooperative_id;

        $products = AnsuranProduct::forCooperative($cooperativeId)
            ->with(['category', 'images', 'variants'])
            ->active()
            ->when($request->category, function ($query, $categoryId) {
                $query->where('ansuran_category_id', $categoryId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Member/Pages/Ansuran/Catalog', [
            'products' => $products->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'category_name' => $p->category->name,
                'primary_image_url' => $p->primaryImage()?->url(),
                'min_price' => $p->variants->min('price'),
                'max_price' => $p->variants->max('price'),
                'variant_count' => $p->variants->count(),
                'guarantor_count' => $p->guarantor_count,
            ]),
            'categories' => AnsuranCategory::forCooperative($cooperativeId)
                ->active()
                ->ordered()
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function show(AnsuranProduct $product)
    {
        $product->load(['category', 'images']);

        $cooperativeId = request()->user()->cooperative_id;
        $tenures = AnsuranTenureOption::forCooperative($cooperativeId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('months')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'months' => $t->months,
                'label' => $t->formattedLabel(),
                'interest_rate_percent' => (float) $t->interest_rate_percent,
            ]);

        $variants = $product->variants()->active()->ordered()->get();

        return Inertia::render('Member/Pages/Ansuran/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category_name' => $product->category->name,
                'min_down_payment_percent' => (float) $product->min_down_payment_percent,
                'guarantor_count' => $product->guarantor_count,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url(),
                    'is_primary' => $img->is_primary,
                ])->values(),
                'variants' => $variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price' => (float) $v->price,
                    'formatted_price' => $v->formattedPrice(),
                    'stock' => $v->stock,
                    'attributes' => $v->attributes,
                ])->values(),
            ],
            'tenures' => $tenures,
            'member' => Member::where('cooperative_id', $cooperativeId)
                ->where('user_id', auth()->id())
                ->first(),
        ]);
    }

    public function apply(StoreApplicationRequest $request)
    {
        $cooperativeId = request()->user()->cooperative_id;
        $member = Member::where('cooperative_id', $cooperativeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notificationSettings = $this->settings->group('notification', $cooperativeId);
        $unitId = ($notificationSettings['pembiayaan_unit_id'] ?? null);
        $unitId = $unitId ? (int) $unitId : null;

        $application = $this->ansuranService->submitApplication(
            $request->validated(),
            $member->id,
            $cooperativeId,
            $unitId,
        );

        if ($application->guarantors->isNotEmpty()) {
            foreach ($application->guarantors as $guarantor) {
                $guarantor->guarantorMember->user->notify(new AnsuranGuarantorRequest($application));
            }
        } else {
            $recipients = $this->notificationRouter->recipients($application->unit_id, $cooperativeId);

            foreach ($recipients as $recipient) {
                $recipient->notify(new AnsuranApplicationSubmitted($application));
            }
        }

        return Inertia::render('Member/Pages/Ansuran/ApplyConfirmation', [
            'application' => [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'product_name' => $application->product->name,
                'variant_name' => $application->variant->name,
                'monthly_amount' => (float) $application->monthly_amount,
                'tenure_months' => $application->tenure_months,
            ],
        ]);
    }
}
