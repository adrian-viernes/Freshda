<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                /* One row per item name per vendor: duplicates would leave
                   freshness updates split across copies. */
                Rule::unique('inventory_items', 'name')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id),
                ),
            ],
            'species' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:99999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'freshness' => ['nullable', 'in:fresh,moderate,not_fresh'],
            'freshness_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $result = $this->inventory->store($request->user(), $validated);

        return response()->json([
            'item' => $result['item'],
            'movement' => $result['movement']->summary(),
        ]);
    }

    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:99999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'freshness' => ['required', 'in:fresh,moderate,not_fresh'],
            'freshness_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $result = $this->inventory->update($item, $validated);

        return response()->json([
            'item' => $result['item'],
            'movement' => $result['movement']?->summary(),
        ]);
    }

    public function destroy(Request $request, InventoryItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $name = $item->name;
        $this->inventory->destroy($item);

        return response()->json(['deleted' => true, 'name' => $name]);
    }
}
