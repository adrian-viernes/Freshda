<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Scan;
use App\Models\User;

/**
 * Creates the in-app alerts the notifications page and the topbar bell list.
 *
 * The wording, severity and target of every alert live here so no controller
 * and no view has to know how a scan, batch or inventory change turns into a
 * notification. Alerts are written through the owner's relation, never by
 * mass-assigning a user id, so a forged payload cannot redirect them.
 */
class NotificationService
{
    public function scanCompleted(Scan $scan, string $source): void
    {
        $this->push($scan->user, [
            'type' => $scan->severity(),
            'title' => 'Scan Complete — '.$scan->species,
            'body' => $scan->species.' assessed as '.$scan->freshnessLabel()
                .' ('.$scan->freshness_percentage.'%, '.($source === 'ai' ? 'AI' : 'simulated').').',
            'category' => 'scans',
            'page' => 'history',
        ]);
    }

    public function batchVerified(Batch $batch): void
    {
        $this->push($batch->user, [
            'type' => $batch->severity(),
            'title' => 'Batch Verified — '.$batch->verdict,
            'body' => $batch->sample_count.' Samples Processed. '.$batch->fresh_count
                .' Fresh, '.$batch->moderate_count.' Moderately Fresh, '.$batch->not_fresh_count.' Not Fresh.',
            'category' => 'scans',
            'page' => 'history',
        ]);
    }

    public function inventoryAdded(InventoryItem $item): void
    {
        $this->push($item->user, [
            'type' => 'success',
            'title' => 'Inventory Added — '.$item->name,
            'body' => $item->name.' added to your inventory with '.$item->quantity.' kg.',
            'category' => 'inventory',
            'page' => 'inventory',
        ]);
    }

    public function lowStock(InventoryItem $item): void
    {
        $this->push($item->user, [
            'type' => 'warning',
            'title' => 'Low Stock — '.$item->name,
            'body' => $item->name.' is below '.$item->lowStockThreshold().' kg ('.$item->quantity
                .' kg remaining). Consider restocking.',
            'category' => 'inventory',
            'page' => 'inventory',
        ]);
    }

    public function deliveryAccepted(Delivery $delivery): void
    {
        $variance = $delivery->variance();

        $this->push($delivery->user, [
            'type' => $variance !== null && $variance < 0 ? 'warning' : 'success',
            'title' => 'Delivery Accepted — '.$delivery->fish_type,
            'body' => $delivery->receivedQuantity().' kg received from '.($delivery->supplier?->name ?? 'Supplier')
                .' and added to inventory'
                .($variance === null || $variance == 0.0 ? '.' : ' ('.$delivery->varianceLabel().').'),
            'category' => 'inventory',
            'page' => 'deliveries',
        ]);
    }

    public function deliveryRejected(Delivery $delivery): void
    {
        $this->push($delivery->user, [
            'type' => 'warning',
            'title' => 'Delivery Rejected — '.$delivery->fish_type,
            'body' => 'Rejected delivery from '.($delivery->supplier?->name ?? 'Supplier')
                .': '.$delivery->rejection_reason,
            'category' => 'inventory',
            'page' => 'deliveries',
        ]);
    }

    /**
     * @param  array{type: string, title: string, body: string, category: string, page: ?string}  $data
     */
    private function push(?User $user, array $data): void
    {
        /* A deleted owner has no inbox worth writing to. */
        $user?->appNotifications()->create($data);
    }
}
