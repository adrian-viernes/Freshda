<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $suppliers) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            /* One row per supplier name per vendor: a duplicate would split
               that supplier's delivery history across two records. */
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id),
                ),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'supplier' => $this->suppliers->store($request->user(), $validated)->summary(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($supplier->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id),
                )->ignore($supplier->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'supplier' => $this->suppliers->update($supplier, $validated)->summary(),
        ]);
    }

    /** 8.8 — switch a supplier off without touching its delivery history. */
    public function updateStatus(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($supplier->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        return response()->json([
            'supplier' => $this->suppliers->updateStatus($supplier, $validated['status'])->summary(),
        ]);
    }

    /** Deleting is refused by the service while deliveries point at the supplier. */
    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($supplier->user_id === $request->user()->id, 403);

        $this->suppliers->destroy($supplier);

        return response()->json([
            'deleted' => true,
            'id' => $supplier->id,
        ]);
    }
}
