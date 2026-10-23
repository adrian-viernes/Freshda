<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Runs the supplier delivery workflow: recording an incoming delivery, editing
 * it while it is still pending, then accepting or rejecting it, plus removing
 * deliveries that never stocked anything. Accepting is the only step that
 * touches the inventory, and it is guarded so the same delivery can never add
 * its quantity twice.
 */
class DeliveryService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * @param  array{supplier_id: int, fish_type: string, delivery_date: string, expected_quantity: int|float, inspection_notes?: ?string}  $validated
     */
    public function store(User $user, array $validated): Delivery
    {
        $this->guardSupplier($user, (int) $validated['supplier_id']);

        return $user->deliveries()->create([
            'supplier_id' => $validated['supplier_id'],
            'fish_type' => $validated['fish_type'],
            'delivery_date' => $validated['delivery_date'],
            'expected_quantity' => $validated['expected_quantity'],
            'inspection_notes' => $validated['inspection_notes'] ?? null,
            'status' => Delivery::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array{supplier_id: int, fish_type: string, delivery_date: string, expected_quantity: int|float, inspection_notes?: ?string}  $validated
     */
    public function update(Delivery $delivery, array $validated): Delivery
    {
        if (! $delivery->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending deliveries can be edited. Accept or reject it instead.',
            ]);
        }

        /* A supplier that has since been deactivated can stay on the delivery
           it already belongs to, but cannot be swapped back in. */
        if ((int) $validated['supplier_id'] !== $delivery->supplier_id) {
            $this->guardSupplier($delivery->user, (int) $validated['supplier_id']);
        }

        $delivery->update([
            'supplier_id' => $validated['supplier_id'],
            'fish_type' => $validated['fish_type'],
            'delivery_date' => $validated['delivery_date'],
            'expected_quantity' => $validated['expected_quantity'],
            /* Same as the supplier form: an emptied notes field clears it. */
            'inspection_notes' => $validated['inspection_notes'] ?? null,
        ]);

        return $delivery->fresh(['supplier', 'inventoryItem']);
    }

    /**
     * Records the quantity that physically arrived, compares it with what was
     * expected and pushes the received amount into the matching inventory
     * item. Runs in one locked transaction so a double tap of "Accept" cannot
     * stock the same delivery twice.
     *
     * @param  array{actual_quantity: ?int|float, inspection_notes: ?string}  $validated
     * @return array{delivery: Delivery, movement: ?StockMovement}
     */
    public function accept(Delivery $delivery, array $validated): array
    {
        $alreadyProcessed = false;
        $movement = null;

        $delivery = DB::transaction(function () use ($delivery, $validated, &$alreadyProcessed, &$movement): Delivery {
            $locked = Delivery::query()->whereKey($delivery->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isAccepted() && $locked->stock_updated_at !== null) {
                $alreadyProcessed = true;

                return $locked->fresh(['supplier', 'inventoryItem']);
            }

            $quantity = $validated['actual_quantity'] ?? $locked->actual_quantity;
            $quantity = $quantity === null ? (float) $locked->expected_quantity : (float) $quantity;

            $locked->fill([
                'actual_quantity' => $quantity,
                'inspection_notes' => $validated['inspection_notes'] ?? $locked->inspection_notes,
                'status' => Delivery::STATUS_ACCEPTED,
                'accepted_at' => $locked->accepted_at ?? now(),
            ])->save();

            [$item, $movement] = $this->applyToStock($locked, $quantity);

            /* The link plus the timestamp is the duplicate guard: the next
               acceptance attempt sees stock_updated_at and stops. */
            $locked->update([
                'inventory_item_id' => $item->id,
                'stock_updated_at' => now(),
            ]);

            return $locked->fresh(['supplier', 'inventoryItem']);
        });

        if (! $alreadyProcessed) {
            $this->notifications->deliveryAccepted($delivery);
        }

        return ['delivery' => $delivery, 'movement' => $movement];
    }

    /**
     * @param  array{rejection_reason: string}  $validated
     */
    public function reject(Delivery $delivery, array $validated): Delivery
    {
        if ($delivery->isAccepted() && $delivery->stock_updated_at !== null) {
            throw ValidationException::withMessages([
                'status' => 'This delivery was already accepted and stocked. It cannot be rejected.',
            ]);
        }

        $delivery->update([
            'status' => Delivery::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $this->notifications->deliveryRejected($delivery->fresh(['supplier']));

        return $delivery->fresh(['supplier', 'inventoryItem']);
    }

    /**
     * Removes a delivery that never touched the stock. An accepted delivery
     * already pushed its quantity into inventory, so it stays on record —
     * deleting it would leave the inventory without the delivery that
     * explains it.
     */
    public function destroy(Delivery $delivery): Delivery
    {
        if ($delivery->stock_updated_at !== null) {
            throw ValidationException::withMessages([
                'delivery' => 'This delivery was accepted and already stocked your inventory. It cannot be deleted.',
            ]);
        }

        $delivery->delete();

        return $delivery;
    }

    /**
     * 9.9 / 9.15 — add the received quantity to the matching stock item and
     * leave the ledger row that explains the jump.
     *
     * @return array{0: InventoryItem, 1: StockMovement}
     */
    private function applyToStock(Delivery $delivery, float $quantity): array
    {
        $item = InventoryItem::query()
            ->where('user_id', $delivery->user_id)
            ->where('name', $delivery->fish_type)
            ->lockForUpdate()
            ->first();

        /* A delivery for a fish the vendor does not stock yet opens the item
           first, so the quantity has somewhere to land. */
        $item ??= $delivery->user->inventoryItems()->create([
            'name' => $delivery->fish_type,
            'species' => $delivery->fish_type,
            'quantity' => 0,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        $item->increment('quantity', $quantity);
        $item = $item->refresh();

        $movement = $delivery->user->stockMovements()->create([
            'inventory_item_id' => $item->id,
            'item_name' => $item->name,
            'change' => $quantity,
            'balance_after' => (float) $item->quantity,
            'type' => StockMovement::TYPE_DELIVERY,
            'reference' => 'Delivery #'.$delivery->id,
        ]);

        return [$item, $movement];
    }

    /** Inactive suppliers must not receive new deliveries. */
    private function guardSupplier(User $user, int $supplierId): void
    {
        $supplier = Supplier::query()
            ->whereKey($supplierId)
            ->where('user_id', $user->id)
            ->first();

        if (! $supplier) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a supplier from your list.',
            ]);
        }

        if (! $supplier->isActive()) {
            throw ValidationException::withMessages([
                'supplier_id' => $supplier->name.' is inactive. Reactivate it before recording a delivery.',
            ]);
        }
    }
}
