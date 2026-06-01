<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AnsuranCategory;
use App\Models\AnsuranProduct;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnsuranCatalogController extends Controller
{
    public function index(Request $request)
    {
        $cooperativeId = app(SettingsService::class)->activeCooperative()?->id;

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

        return Inertia::render('Public/Pages/Ansuran/Catalog', [
            'products' => $products->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'category_name' => $p->category->name,
                'primary_image_url' => $p->primaryImage()?->url(),
                'min_price' => $p->variants->min('price'),
                'max_price' => $p->variants->max('price'),
                'variant_count' => $p->variants->count(),
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

        $variants = $product->variants()->active()->ordered()->get();

        return Inertia::render('Public/Pages/Ansuran/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category_name' => $product->category->name,
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
        ]);
    }
}
