<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Keeps the vendor's supplier book: storing suppliers, editing their
 * details, switching them between active and inactive, and deleting the ones
 * that never received a delivery. A supplier that already has deliveries is
 * never deleted, because past deliveries must keep the record that explains
 * them — deactivate it instead.
 */
class SupplierService
{
    /**
     * @param  array{name: string, contact_person: ?string, phone: ?string, email: ?string, address: ?string, notes: ?string}  $validated
     */
    public function store(User $user, array $validated): Supplier
    {
        try {
            return $user->suppliers()->create([
                'name' => $validated['name'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => Supplier::STATUS_ACTIVE,
            ]);
        } catch (QueryException $e) {
            /* The controller pre-checks the name, so this only fires when two
               requests race past it — the unique index still says no. */
            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'name' => 'A supplier with this name already exists.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * The edit form posts every field, so an emptied optional field really
     * means "remove it" — blanking a supplier's email must not be a no-op.
     *
     * @param  array{name: string, contact_person: ?string, phone: ?string, email: ?string, address: ?string, notes: ?string}  $validated
     */
    public function update(Supplier $supplier, array $validated): Supplier
    {
        $supplier->update([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return $supplier;
    }

    /**
     * Deactivating only flips the flag: deliveries already recorded against
     * the supplier stay exactly as they were.
     */
    public function updateStatus(Supplier $supplier, string $status): Supplier
    {
        if (! in_array($status, [Supplier::STATUS_ACTIVE, Supplier::STATUS_INACTIVE], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status must be active or inactive.',
            ]);
        }

        $supplier->update(['status' => $status]);

        return $supplier;
    }

    /**
     * A supplier is only removed while it owns no deliveries: deleting one
     * that has history would take the explanation of those deliveries with
     * it, so those are deactivated rather than deleted.
     */
    public function destroy(Supplier $supplier): Supplier
    {
        $deliveries = $supplier->deliveries()->count();

        if ($deliveries > 0) {
            throw ValidationException::withMessages([
                'supplier' => $supplier->name.' has '.$deliveries.' recorded delivery'.($deliveries === 1 ? '' : 'ies')
                    .'. Deactivate it instead so its delivery history stays.',
            ]);
        }

        $supplier->delete();

        return $supplier;
    }
}
