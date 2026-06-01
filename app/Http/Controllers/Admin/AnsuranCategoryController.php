<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ansuran\StoreCategoryRequest;
use App\Models\AnsuranCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AnsuranCategoryController extends Controller
{
    use InteractsWithActiveCooperative;

    public function index()
    {
        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Categories/Index', [
            'categories' => AnsuranCategory::forCooperative($cooperativeId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'description' => $cat->description,
                    'image_url' => $cat->imageUrl(),
                    'is_active' => $cat->is_active,
                    'sort_order' => $cat->sort_order,
                    'products_count' => $cat->products()->count(),
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pages/Ansuran/Categories/Form');
    }

    public function store(StoreCategoryRequest $request)
    {
        $cooperativeId = $this->activeCooperative()?->id;

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('ansuran/categories', 'public');
        }

        $data['cooperative_id'] = $cooperativeId;

        AnsuranCategory::create($data);

        return redirect()->route('admin.ansuran.categories.index')
            ->with('success', 'Kategori berjaya ditambah.');
    }

    public function edit(AnsuranCategory $category)
    {
        return Inertia::render('Admin/Pages/Ansuran/Categories/Form', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'image_url' => $category->imageUrl(),
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ],
        ]);
    }

    public function update(StoreCategoryRequest $request, AnsuranCategory $category)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = $request->file('image')->store('ansuran/categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.ansuran.categories.index')
            ->with('success', 'Kategori berjaya dikemaskini.');
    }

    public function destroy(AnsuranCategory $category)
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()->route('admin.ansuran.categories.index')
            ->with('success', 'Kategori berjaya dipadam.');
    }
}
