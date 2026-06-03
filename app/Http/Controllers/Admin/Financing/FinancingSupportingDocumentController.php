<?php

namespace App\Http\Controllers\Admin\Financing;

use App\Http\Controllers\Controller;
use App\Models\FinancingProduct;
use App\Models\FinancingSupportingDocument;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancingSupportingDocumentController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function store(Request $request, FinancingProduct $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'string', 'in:single,multiple,monthly'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'is_required' => ['boolean'],
            'accepted_types' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['nullable', 'integer', 'min:1', 'max:51200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $maxSort = $product->supportingDocuments()->max('sort_order') ?? 0;
        $data['sort_order'] = $maxSort + 1;
        $data['cooperative_id'] = $product->cooperative_id;

        $doc = $product->supportingDocuments()->create($data);

        return response()->json(['document' => $this->serialize($doc)]);
    }

    public function update(Request $request, FinancingProduct $product, FinancingSupportingDocument $document): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($document->financing_product_id === $product->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'string', 'in:single,multiple,monthly'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'is_required' => ['boolean'],
            'accepted_types' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['nullable', 'integer', 'min:1', 'max:51200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $document->update($data);

        return response()->json(['document' => $this->serialize($document->fresh())]);
    }

    public function destroy(Request $request, FinancingProduct $product, FinancingSupportingDocument $document): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($document->financing_product_id === $product->id, 404);

        $document->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeProduct(Request $request, FinancingProduct $product): void
    {
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);
        abort_unless($product->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }

    private function serialize(FinancingSupportingDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'description' => $document->description,
            'mode' => $document->mode,
            'count' => $document->count,
            'is_required' => $document->is_required,
            'accepted_types' => $document->accepted_types,
            'max_size_kb' => $document->max_size_kb,
            'sort_order' => $document->sort_order,
            'is_active' => $document->is_active,
            'slot_labels' => $document->slotLabels(),
        ];
    }
}
