<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ansuran\StoreProductRequest;
use App\Http\Requests\Ansuran\StoreVariantRequest;
use App\Models\AnsuranCategory;
use App\Models\AnsuranProduct;
use App\Models\AnsuranProductImage;
use App\Models\AnsuranProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AnsuranProductController extends Controller
{
    use InteractsWithActiveCooperative;

    public function index()
    {
        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Products/Index', [
            'products' => AnsuranProduct::forCooperative($cooperativeId)
                ->with(['category', 'images', 'variants'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'category_name' => $p->category->name,
                    'status' => $p->status,
                    'guarantor_count' => $p->guarantor_count,
                    'primary_image_url' => $p->primaryImage()?->url(),
                    'variants_count' => $p->variants->count(),
                    'min_variant_price' => $p->variants->min('price'),
                    'max_variant_price' => $p->variants->max('price'),
                ]),
        ]);
    }

    public function create()
    {
        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Products/Form', [
            'categories' => AnsuranCategory::forCooperative($cooperativeId)
                ->active()
                ->ordered()
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $cooperativeId = $this->activeCooperative()?->id;

        $data = $request->validated();
        $data['cooperative_id'] = $cooperativeId;
        $data['created_by'] = auth()->id();

        AnsuranProduct::create($data);

        return redirect()->route('admin.ansuran.products.index')
            ->with('success', 'Produk berjaya ditambah.');
    }

    public function edit(AnsuranProduct $product)
    {
        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Products/Form', [
            'product' => [
                'id' => $product->id,
                'ansuran_category_id' => $product->ansuran_category_id,
                'name' => $product->name,
                'description' => $product->description,
                'min_down_payment_percent' => (float) $product->min_down_payment_percent,
                'guarantor_count' => $product->guarantor_count,
                'status' => $product->status,
                'sort_order' => $product->sort_order,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url(),
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                ])->values(),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => (float) $v->price,
                    'stock' => $v->stock,
                    'attributes' => $v->attributes,
                    'sort_order' => $v->sort_order,
                    'is_active' => $v->is_active,
                ])->values(),
            ],
            'categories' => AnsuranCategory::forCooperative($cooperativeId)
                ->ordered()
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
    }

    public function update(StoreProductRequest $request, AnsuranProduct $product)
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $product->update($data);

        return redirect()->route('admin.ansuran.products.index')
            ->with('success', 'Produk berjaya dikemaskini.');
    }

    public function destroy(AnsuranProduct $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();

        return redirect()->route('admin.ansuran.products.index')
            ->with('success', 'Produk berjaya dipadam.');
    }

    public function uploadImage(Request $request, AnsuranProduct $product)
    {
        $request->validate([
            'images.*' => ['required', 'image', 'max:5120'],
        ]);

        $sortOrder = $product->images()->max('sort_order') ?? 0;

        foreach ($request->file('images', []) as $file) {
            $path = $file->store('ansuran/products/'.$product->id, 'public');
            $sortOrder++;

            AnsuranProductImage::create([
                'ansuran_product_id' => $product->id,
                'path' => $path,
                'sort_order' => $sortOrder,
                'is_primary' => $product->images()->count() === 0,
            ]);
        }

        return back()->with('success', 'Gambar berjaya dimuat naik.');
    }

    public function deleteImage(AnsuranProduct $product, AnsuranProductImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Gambar berjaya dipadam.');
    }

    public function setPrimaryImage(AnsuranProduct $product, AnsuranProductImage $image)
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Gambar utama berjaya ditukar.');
    }

    public function reorderImages(Request $request, AnsuranProduct $product)
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:ansuran_product_images,id'],
        ]);

        foreach ($request->input('order', []) as $index => $imageId) {
            AnsuranProductImage::where('id', $imageId)
                ->where('ansuran_product_id', $product->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Susunan gambar berjaya dikemaskini.');
    }

    public function storeVariant(StoreVariantRequest $request, AnsuranProduct $product)
    {
        $data = $request->validated();
        $data['ansuran_product_id'] = $product->id;
        $data['sort_order'] = $product->variants()->max('sort_order') ?? 0 + 1;

        AnsuranProductVariant::create($data);

        return back()->with('success', 'Varian berjaya ditambah.');
    }

    public function updateVariant(StoreVariantRequest $request, AnsuranProduct $product, AnsuranProductVariant $variant)
    {
        $variant->update($request->validated());

        return back()->with('success', 'Varian berjaya dikemaskini.');
    }

    public function destroyVariant(AnsuranProduct $product, AnsuranProductVariant $variant)
    {
        $variant->delete();

        return back()->with('success', 'Varian berjaya dipadam.');
    }
}
