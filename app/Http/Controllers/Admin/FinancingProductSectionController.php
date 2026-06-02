<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancingProduct;
use App\Models\FinancingProductSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancingProductSectionController extends Controller
{
    public function store(Request $request, FinancingProduct $product): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'page_break_before' => ['nullable', 'boolean'],
        ]);

        $maxOrder = $product->sections()->max('sort_order') ?? 0;

        $section = $product->sections()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'page_break_before' => $validated['page_break_before'] ?? false,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        $section->load('fields');

        return response()->json([
            'ok' => true,
            'section' => $this->serializeSection($section),
        ]);
    }

    public function update(Request $request, FinancingProduct $product, FinancingProductSection $section): JsonResponse
    {
        abort_unless($section->financing_product_id === $product->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'page_break_before' => ['nullable', 'boolean'],
        ]);

        $section->update($validated);
        $section->load('fields');

        return response()->json([
            'ok' => true,
            'section' => $this->serializeSection($section),
        ]);
    }

    public function destroy(FinancingProduct $product, FinancingProductSection $section): JsonResponse
    {
        abort_unless($section->financing_product_id === $product->id, 404);

        DB::transaction(function () use ($section) {
            $section->fields()->delete();
            $section->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function moveUp(FinancingProduct $product, FinancingProductSection $section): JsonResponse
    {
        abort_unless($section->financing_product_id === $product->id, 404);

        $previous = $product->sections()
            ->where('sort_order', '<', $section->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $currentOrder = $section->sort_order;
            $section->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $currentOrder]);
        }

        return response()->json(['ok' => true]);
    }

    public function moveDown(FinancingProduct $product, FinancingProductSection $section): JsonResponse
    {
        abort_unless($section->financing_product_id === $product->id, 404);

        $next = $product->sections()
            ->where('sort_order', '>', $section->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $currentOrder = $section->sort_order;
            $section->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $currentOrder]);
        }

        return response()->json(['ok' => true]);
    }

    private function serializeSection(FinancingProductSection $section): array
    {
        return [
            'id' => $section->id,
            'financing_product_id' => $section->financing_product_id,
            'title' => $section->title,
            'description' => $section->description,
            'page_break_before' => $section->page_break_before,
            'is_active' => $section->is_active,
            'fields' => $section->fields->map(fn ($field) => [
                'id' => $field->id,
                'financing_product_section_id' => $field->financing_product_section_id,
                'label' => $field->label,
                'field_key' => $field->field_key,
                'type' => $field->type->value,
                'type_label' => $field->type->label(),
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'options_json' => $field->options_json,
                'settings_json' => $field->settings_json,
                'file_url' => $field->file_url,
                'is_active' => $field->is_active,
            ])->all(),
        ];
    }
}