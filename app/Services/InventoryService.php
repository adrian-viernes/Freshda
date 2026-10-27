<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Applies the vendor's stock changes and raises the alerts that follow from
 * them: the confirmation for a new row and the warning when stock runs low.
 * Every quantity change is written to the stock movement ledger, so the
 * history always explains how an item reached its current balance.
 */
class InventoryService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * @param  array{name: string, species: ?string, quantity: int|float, min_stock: ?int|float, freshness: ?string, freshness_percentage: ?int}  $validated
     * @return array{item: InventoryItem, movement: StockMovement}
     */
    public function store(User $user, array $validated): array
    {
        $item = $user->inventoryItems()->create([
            'name' => $validated['name'],
            'species' => ($validated['species'] ?? null) ?: $validated['name'],
            'quantity' => $validated['quantity'],
            'min_stock' => $validated['min_stock'] ?? null,
            'freshness' => $validated['freshness'] ?? 'fresh',
            'freshness_percentage' => $validated['freshness_percentage'] ?? 90,
            'last_scanned_at' => now(),
        ]);

        $movement = $this->recordMovement($user, $item, (float) $item->quantity, StockMovement::TYPE_ADD, 'Item created');

        if ($item->isLowStock()) {
            $this->notifications->lowStock($item);
        }

        return ['item' => $item, 'movement' => $movement];
    }

    /**
     * @param  array{name: string, species: ?string, quantity: int|float, min_stock: ?int|float, freshness: string, freshness_percentage: int}  $validated
     * @return array{item: InventoryItem, movement: ?StockMovement}
     */
    public function update(InventoryItem $item, array $validated): array
    {
        $previous = (float) $item->quantity;
        $wasLowStock = $item->isLowStock();

        $item->fill([
            'name' => $validated['name'],
            'species' => ($validated['species'] ?? null) ?: $item->species,
            'quantity' => $validated['quantity'],
            'min_stock' => $validated['min_stock'] ?? null,
            'freshness' => $validated['freshness'],
            'freshness_percentage' => $validated['freshness_percentage'],
        ])->save();

        /* Only a real quantity change moves the ledger; editing freshness
           alone leaves stock history untouched. */
        $movement = null;
        if ((float) $item->quantity !== $previous) {
            $movement = $this->recordMovement(
                $item->user,
                $item,
                (float) $item->quantity - $previous,
                StockMovement::TYPE_ADJUST,
                'Manual adjustment',
            );
        }

        /* Warn only when the item actually drops below the threshold. */
        if (! $wasLowStock && $item->isLowStock()) {
            $this->notifications->lowStock($item);
        }

        return ['item' => $item, 'movement' => $movement];
    }

    /** A deleted item keeps its final movement row for the audit trail. */
    public function destroy(InventoryItem $item): void
    {
        if ((float) $item->quantity > 0) {
            $this->recordMovement(
                $item->user,
                $item,
                -(float) $item->quantity,
                StockMovement::TYPE_REMOVED,
                'Item removed from inventory',
            );
        }

        $item->delete();
    }

    /** One signed ledger row for a quantity change on one item. */
    private function recordMovement(User $user, InventoryItem $item, float $change, string $type, ?string $reference = null): StockMovement
    {
        return $user->stockMovements()->create([
            'inventory_item_id' => $item->id,
            'item_name' => $item->name,
            'change' => $change,
            'balance_after' => (float) $item->quantity,
            'type' => $type,
            'reference' => $reference,
        ]);
    }
}
