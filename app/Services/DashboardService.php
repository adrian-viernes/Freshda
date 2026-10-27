<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Batch;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Scan;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Assembles everything the dashboard page renders: overview stats, the recent
 * scans and history rows, inventory figures and notifications. The controller
 * only hands the result to the view.
 */
class DashboardService
{
    /**
     * @return array{user: User, stats: array{total: int, fresh: int, moderate: int, not_fresh: int, week: int}, recentScans: Collection<int, Scan>, history: array<int, array<string, mixed>>, inventory: Collection<int, InventoryItem>, inventorySummary: array{items: Collection<int, InventoryItem>, total: int, species: int, low_stock: int, updated_at: ?Carbon, total_kg: float}, suppliers: Collection<int, Supplier>, supplierSummary: array{total: int, active: int, inactive: int, deliveries: int}, deliveries: Collection<int, Delivery>, deliverySummary: array{total: int, pending: int, accepted: int, rejected: int}, movements: Collection<int, StockMovement>, notifications: Collection<int, AppNotification>, unreadCount: int, notificationsData: array<int, array<string, mixed>>, dashboardData: array<string, mixed>}
     */
    public function overview(User $user): array
    {
        /* Batch samples roll up into their batch row in the history table, so
           stats and history only count standalone scans. The overview's
           Recent Scans card still lists every scan, batch samples included. */
        $scans = $user->scans()->latest()->get();
        $historyScans = $scans->filter(fn (Scan $scan): bool => $scan->batch_id === null)->values();
        $notifications = $user->appNotifications()->latest()->get();
        $inventory = $user->isVendor() ? $user->inventoryItems()->latest('updated_at')->get() : collect();
        /* The movement ledger explains every quantity change on the
           inventory page and in the stock movement history modal. */
        $movements = $user->isVendor() ? $user->stockMovements()->latest()->take(40)->get() : collect();
        /* Suppliers and their deliveries are vendor tools as well: the two
           pages read the same collections the tables render. */
        $suppliers = $user->isVendor() ? $user->suppliers()->withCount('deliveries')->latest()->get() : collect();
        $deliveries = $user->isVendor()
            ? $user->deliveries()->with(['supplier', 'inventoryItem'])->orderByDesc('delivery_date')->orderByDesc('id')->get()
            : collect();

        $stats = $this->stats($historyScans);
        $unreadCount = $notifications->where('is_read', false)->count();
        $notificationsData = $notifications->map(fn ($n): array => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'desc' => $n->body,
            'time' => $n->created_at->diffForHumans(),
            'unread' => ! $n->is_read,
            'category' => $n->category,
            'page' => $n->page,
        ])->all();
        $dashboardData = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->roleLabel(),
                'initials' => $user->initials(),
                'is_vendor' => $user->isVendor(),
            ],
            'stats' => $stats,
            'unread_count' => $unreadCount,
            'notifications' => $notificationsData,
        ];

        /* The delivery form picks its supplier from this list, and the supplier
           detail modal reads the delivery history from it, so both pages work
           without extra round trips. */
        if ($user->isVendor()) {
            $dashboardData['suppliers'] = $suppliers->map(fn (Supplier $supplier): array => $supplier->summary())->values()->all();
            $dashboardData['deliveries'] = $deliveries->map(fn (Delivery $delivery): array => $delivery->summary())->values()->all();
            $dashboardData['movements'] = $movements->map(fn (StockMovement $movement): array => $movement->summary())->values()->all();
        }

        return [
            'user' => $user,
            'stats' => $stats,
            'recentScans' => $scans->take(5),
            'history' => $this->history($historyScans, $user->batches()->latest()->get()),
            'inventory' => $inventory,
            'inventorySummary' => $this->inventorySummary($inventory),
            'suppliers' => $suppliers,
            'supplierSummary' => $this->supplierSummary($suppliers),
            'deliveries' => $deliveries,
            'deliverySummary' => $this->deliverySummary($deliveries),
            'movements' => $movements,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'notificationsData' => $notificationsData,
            'dashboardData' => $dashboardData,
        ];
    }

    /**
     * @param  Collection<int, Scan>  $scans
     * @return array{total: int, fresh: int, moderate: int, not_fresh: int, week: int}
     */
    private function stats(Collection $scans): array
    {
        return [
            'total' => $scans->count(),
            'fresh' => $scans->where('freshness', 'fresh')->count(),
            'moderate' => $scans->where('freshness', 'moderate')->count(),
            'not_fresh' => $scans->where('freshness', 'not_fresh')->count(),
            'week' => $scans->where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    /**
     * Standalone scans and batches merged into the rows the history table and
     * the export tools read, newest first.
     *
     * @param  Collection<int, Scan>  $scans
     * @param  Collection<int, Batch>  $batches
     * @return array<int, array{type: string, name: string, species_label: string, freshness: string, label: string, percentage: int, confidence: float, confidence_band: string, source: string, image: ?string, time: Carbon, time_ago: string}>
     */
    private function history(Collection $scans, Collection $batches): array
    {
        $history = [];

        foreach ($scans as $scan) {
            $history[] = [
                'type' => 'scan',
                'name' => $scan->species ?? 'Unknown',
                'species_label' => FishAiService::latinFor((string) ($scan->species ?? '')),
                'freshness' => $scan->freshnessClass(),
                'label' => $scan->freshnessLabel(),
                'percentage' => $scan->freshness_percentage,
                'confidence' => round((float) $scan->species_confidence, 2),
                'confidence_band' => FishAiService::confidenceBand((float) $scan->species_confidence),
                'source' => $scan->source === 'batch' ? 'Batch Sample' : 'Single Scan',
                'image' => $scan->image_path ? asset('storage/'.$scan->image_path) : null,
                'time' => $scan->created_at,
                'time_ago' => $scan->created_at->diffForHumans(),
            ];
        }

        foreach ($batches as $batch) {
            $history[] = [
                'type' => 'batch',
                'name' => $batch->displayName(),
                'species_label' => 'Mixed',
                'freshness' => $batch->verdictClass(),
                'label' => $batch->verdict,
                'percentage' => (int) round((float) $batch->confidence * 100),
                'confidence' => round((float) $batch->confidence, 2),
                'confidence_band' => FishAiService::confidenceBand((float) $batch->confidence),
                'source' => 'Batch Verification',
                'image' => null,
                'time' => $batch->created_at,
                'time_ago' => $batch->created_at->diffForHumans(),
            ];
        }

        usort($history, fn (array $a, array $b): int => $b['time']->getTimestamp() <=> $a['time']->getTimestamp());

        return $history;
    }

    /**
     * @param  Collection<int, InventoryItem>  $inventory
     * @return array{items: Collection<int, InventoryItem>, total: int, species: int, low_stock: int, updated_at: ?Carbon, total_kg: float}
     */
    private function inventorySummary(Collection $inventory): array
    {
        $top = $inventory->sortByDesc('quantity')->take(3)->values();

        return [
            'items' => $top,
            'total' => $inventory->count(),
            'species' => $inventory->pluck('species')->unique()->count(),
            'low_stock' => $inventory->filter(fn (InventoryItem $item): bool => $item->isLowStock())->count(),
            'updated_at' => $inventory->count() ? $inventory->max('updated_at') : null,
            /* The inventory page's stat cards read the column sum. */
            'total_kg' => (float) $inventory->sum('quantity'),
        ];
    }

    /**
     * @param  Collection<int, Supplier>  $suppliers
     * @return array{total: int, active: int, inactive: int, deliveries: int}
     */
    private function supplierSummary(Collection $suppliers): array
    {
        return [
            'total' => $suppliers->count(),
            'active' => $suppliers->filter(fn (Supplier $supplier): bool => $supplier->isActive())->count(),
            'inactive' => $suppliers->filter(fn (Supplier $supplier): bool => ! $supplier->isActive())->count(),
            /* Deliveries booked against every supplier on the list. */
            'deliveries' => (int) $suppliers->sum('deliveries_count'),
        ];
    }

    /**
     * @param  Collection<int, Delivery>  $deliveries
     * @return array{total: int, pending: int, accepted: int, rejected: int}
     */
    private function deliverySummary(Collection $deliveries): array
    {
        return [
            'total' => $deliveries->count(),
            'pending' => $deliveries->where('status', Delivery::STATUS_PENDING)->count(),
            'accepted' => $deliveries->where('status', Delivery::STATUS_ACCEPTED)->count(),
            'rejected' => $deliveries->where('status', Delivery::STATUS_REJECTED)->count(),
        ];
    }
}
