<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $deliveries) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id),
                ),
            ],
            'fish_type' => ['required', 'string', 'max:255'],
            'delivery_date' => ['required', 'date'],
            'expected_quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'inspection_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'delivery' => $this->deliveries->store($request->user(), $validated)->summary(),
        ]);
    }

    /** 9.13 — pending deliveries only; the service enforces it. */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        abort_unless($delivery->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id),
                ),
            ],
            'fish_type' => ['required', 'string', 'max:255'],
            'delivery_date' => ['required', 'date'],
            'expected_quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'inspection_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'delivery' => $this->deliveries->update($delivery, $validated)->summary(),
        ]);
    }

    /** 9.4 / 9.7 — record what arrived, then stock it. */
    public function accept(Request $request, Delivery $delivery): JsonResponse
    {
        abort_unless($delivery->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'actual_quantity' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'inspection_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->deliveries->accept($delivery, $validated);

        return response()->json([
            'delivery' => $result['delivery']->summary(),
            'movement' => $result['movement']?->summary(),
        ]);
    }

    public function reject(Request $request, Delivery $delivery): JsonResponse
    {
        abort_unless($delivery->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        return response()->json([
            'delivery' => $this->deliveries->reject($delivery, $validated)->summary(),
        ]);
    }

    /** The service keeps deliveries that already stocked inventory on record. */
    public function destroy(Request $request, Delivery $delivery): JsonResponse
    {
        abort_unless($delivery->user_id === $request->user()->id, 403);

        $this->deliveries->destroy($delivery);

        return response()->json([
            'deleted' => true,
            'id' => $delivery->id,
        ]);
    }
}
