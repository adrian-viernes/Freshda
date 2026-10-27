<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shapes the four vendor reports — fish assessment, inventory, low stock and
 * supplier deliveries — for the JSON endpoint and the CSV download. Rows come
 * back as plain arrays so the controller, the CSV writer and the jsPDF
 * renderer all consume the same data.
 *
 * Alongside the tabular `rows` each report carries `records`: the same facts
 * told per item, with the quantity and confidence the printable PDF quotes in
 * its narrative paragraphs, plus `meta` identifying who generated the report
 * and under which reference number.
 */
class ReportService
{
    public const TYPES = ['assessment', 'inventory', 'lowstock', 'deliveries'];

    public function __construct(private HistoryExportService $history) {}

    /**
     * @param  ?string  $from  YYYY-MM-DD (inclusive), null for no lower bound
     * @param  ?string  $to  YYYY-MM-DD (inclusive), null for no upper bound
     * @return array{title: string, type: string, generated_at: string, from: ?string, to: ?string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totals: array<string, int|float>, records: array<int, array{fish: string, species: string, classification: string, confidence: ?float, quantity: ?float, datetime: ?string, detail: ?string}>, meta: array{reference: string, generated_by: string, role: string}}
     */
    public function data(User $user, string $type, ?string $from, ?string $to): array
    {
        [$from, $to] = $this->bounds($from, $to);

        $report = match ($type) {
            'assessment' => $this->assessment($user, $from, $to),
            'inventory' => $this->inventory($user),
            'lowstock' => $this->lowStock($user),
            'deliveries' => $this->deliveries($user, $from, $to),
            default => throw new \InvalidArgumentException("Unknown report type [{$type}]."),
        };

        $report['meta'] = [
            'reference' => $this->reference(),
            'generated_by' => (string) $user->name,
            'role' => $user->roleLabel(),
        ];

        return $report;
    }

    /**
     * Stream one report as a CSV download, formula-injection safe.
     */
    public function csv(User $user, string $type, ?string $from, ?string $to): callable
    {
        $report = $this->data($user, $type, $from, $to);

        return function () use ($report): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $report['headers']);

            foreach ($report['rows'] as $row) {
                $safe = array_map(
                    fn ($value) => is_string($value) ? $this->history->csvSafe($value) : $value,
                    $row,
                );
                fputcsv($handle, $safe);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Totals']);
            foreach ($report['totals'] as $label => $value) {
                fputcsv($handle, [ucwords(str_replace('_', ' ', $label)), $value]);
            }

            fclose($handle);
        };
    }

    public function filename(string $type): string
    {
        return 'freshda-'.$type.'-report-'.now()->format('Ymd-His').'.csv';
    }

    /**
     * Clamp the requested date window; a reversed pair flips to its swap so
     * the range can never silently return zero rows.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function bounds(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : now()->startOfYear();
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    /**
     * The identifier the printable report wears, so a hard copy can be traced
     * back to the moment Fresh-DA produced it.
     */
    private function reference(): string
    {
        return 'FDA-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    /**
     * The vendor's stock on hand, keyed by every name it can be referred to
     * by — the common name and the latin label — so an assessment record can
     * quote the quantity it belongs to.
     *
     * @return array<string, float>
     */
    private function inventoryQuantities(User $user): array
    {
        $quantities = [];

        foreach ($user->inventoryItems as $item) {
            foreach ([strtolower((string) $item->name), strtolower((string) $item->species)] as $key) {
                if ($key !== '') {
                    $quantities[$key] = round((float) $item->quantity, 2);
                }
            }
        }

        return $quantities;
    }

    /**
     * The inventory key a scanned species points at — the stocked common name
     * first, then the latin label — or null when the fish is not stocked.
     *
     * @param  array<string, float>  $quantities
     */
    private function quantityKey(array $quantities, string $species): ?string
    {
        foreach ([strtolower($species), strtolower(FishAiService::latinFor($species))] as $key) {
            if ($key !== '' && array_key_exists($key, $quantities)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The newest model confidence recorded for each stocked item, keyed by
     * the names that item can be referred to by, so the narrative can quote
     * the assessment the freshness label came from. One query, never one per
     * row.
     *
     * @param  Collection<int, InventoryItem>  $items
     * @return array<string, float>
     */
    private function latestConfidence(User $user, Collection $items): array
    {
        $names = [];

        foreach ($items as $item) {
            foreach ([$item->name, (string) $item->species] as $name) {
                if ($name !== null && $name !== '') {
                    $names[] = $name;
                }
            }
        }

        $confidence = [];

        if ($names === []) {
            return $confidence;
        }

        $scans = Scan::query()
            ->where('user_id', $user->id)
            ->whereIn('species', array_values(array_unique($names)))
            ->latest()
            ->get(['species', 'species_confidence']);

        foreach ($scans as $scan) {
            if ($scan->species_confidence === null) {
                continue;
            }

            /* The query is newest-first, so the first answer for a name wins. */
            foreach ([(string) $scan->species, FishAiService::latinFor((string) $scan->species)] as $name) {
                $key = strtolower($name);

                if ($key !== '' && ! array_key_exists($key, $confidence)) {
                    $confidence[$key] = round((float) $scan->species_confidence, 4);
                }
            }
        }

        return $confidence;
    }

    /**
     * The confidence the newest scan holds for one stocked item, matched on
     * the item's common name first and its latin label second.
     *
     * @param  array<string, float>  $confidence
     */
    private function itemConfidence(InventoryItem $item, array $confidence): ?float
    {
        return $confidence[strtolower((string) $item->name)]
            ?? $confidence[strtolower((string) $item->species)]
            ?? null;
    }

    /**
     * Fish Assessment Report: every standalone scan in the window with its
     * freshness verdict, plus how the window splits across the three bands.
     * Each record also quotes the stock the scanned species sits under, so
     * the PDF can tell the assessment and the quantity it belongs to.
     *
     * @return array{title: string, type: string, generated_at: string, from: ?string, to: ?string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totals: array<string, int|float>, records: array<int, array{fish: string, species: string, classification: string, confidence: ?float, quantity: ?float, datetime: ?string, detail: ?string}>}
     */
    private function assessment(User $user, Carbon $from, Carbon $to): array
    {
        $scans = Scan::query()
            ->where('user_id', $user->id)
            ->whereNull('batch_id')
            ->whereBetween('created_at', [$from, $to])
            ->latest('created_at')
            ->get();

        $quantities = $this->inventoryQuantities($user);
        $records = [];
        $quoted = [];

        foreach ($scans->values() as $scan) {
            $key = $this->quantityKey($quantities, (string) ($scan->species ?? ''));

            if ($key !== null) {
                $quoted[$key] = true;
            }

            $records[] = [
                'fish' => (string) ($scan->species ?? 'Unknown'),
                'species' => FishAiService::latinFor((string) ($scan->species ?? '')),
                'classification' => $scan->freshnessLabel(),
                'confidence' => $scan->species_confidence === null
                    ? null
                    : round((float) $scan->species_confidence, 4),
                'quantity' => $key === null ? null : $quantities[$key],
                'datetime' => $scan->created_at->format('Y-m-d H:i'),
                'detail' => null,
            ];
        }

        $rows = $scans->map(fn (Scan $scan): array => [
            $scan->created_at->format('Y-m-d H:i'),
            (string) ($scan->species ?? 'Unknown'),
            FishAiService::latinFor((string) ($scan->species ?? '')),
            $scan->freshnessLabel(),
            (int) $scan->freshness_percentage,
            number_format((float) $scan->species_confidence, 2),
        ])->all();

        $fresh = $scans->where('freshness', 'fresh')->count();
        $moderate = $scans->where('freshness', 'moderate')->count();
        $notFresh = $scans->where('freshness', 'not_fresh')->count();

        return [
            'title' => 'Fish Assessment Report',
            'type' => 'assessment',
            'generated_at' => now()->format('M j, Y H:i'),
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'headers' => ['Date', 'Fish', 'Species', 'Freshness', 'Freshness %', 'Confidence'],
            'rows' => $rows,
            'records' => $records,
            'totals' => [
                'total_scans' => $scans->count(),
                'fresh' => $fresh,
                'moderate' => $moderate,
                'not_fresh' => $notFresh,
                'average_freshness_percent' => $scans->isEmpty()
                    ? 0
                    : round($scans->avg('freshness_percentage'), 1),
                /* One stocked item can answer for several scans, so the weight
                   is counted once per item rather than once per record. */
                'total_quantity' => round((float) array_sum(array_intersect_key($quantities, $quoted)), 2),
            ],
        ];
    }

    /**
     * Inventory Report: one row per stocked item with its current balance,
     * threshold and last scan time. A point-in-time report, so no dates. Each
     * record carries the newest confidence the vendor's scans hold for that
     * item, which is what the freshness label was decided from.
     *
     * @return array{title: string, type: string, generated_at: string, from: ?string, to: ?string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totals: array<string, int|float>, records: array<int, array{fish: string, species: string, classification: string, confidence: ?float, quantity: ?float, datetime: ?string, detail: ?string}>}
     */
    private function inventory(User $user): array
    {
        $items = $user->inventoryItems()->orderBy('name')->get();
        $confidence = $this->latestConfidence($user, $items);
        $totalKg = round($items->sum(fn (InventoryItem $item): float => (float) $item->quantity), 2);

        $rows = $items->map(fn (InventoryItem $item): array => [
            $item->name,
            (string) $item->species,
            round((float) $item->quantity, 2),
            round($item->lowStockThreshold(), 2),
            $item->isLowStock() ? 'Yes' : 'No',
            $item->freshnessLabel(),
            (int) $item->freshness_percentage,
            $item->last_scanned_at?->format('Y-m-d H:i') ?? '—',
        ])->all();

        $records = $items->map(fn (InventoryItem $item): array => [
            'fish' => (string) $item->name,
            'species' => (string) $item->species,
            'classification' => $item->freshnessLabel(),
            'confidence' => $this->itemConfidence($item, $confidence),
            'quantity' => round((float) $item->quantity, 2),
            'datetime' => $item->last_scanned_at?->format('Y-m-d H:i'),
            'detail' => null,
        ])->all();

        return [
            'title' => 'Inventory Report',
            'type' => 'inventory',
            'generated_at' => now()->format('M j, Y H:i'),
            'from' => null,
            'to' => null,
            'headers' => ['Item', 'Species', 'Quantity (kg)', 'Min Stock (kg)', 'Low Stock', 'Freshness', 'Freshness %', 'Last Scan'],
            'rows' => $rows,
            'records' => $records,
            'totals' => [
                'items' => $items->count(),
                'total_kg' => $totalKg,
                'species' => $items->pluck('species')->unique()->count(),
                'low_stock' => $items->filter(fn (InventoryItem $item): bool => $item->isLowStock())->count(),
                'total_quantity' => $totalKg,
            ],
        ];
    }

    /**
     * Low-Stock Report: only the items under their threshold, with how many
     * kilograms each one is short by.
     *
     * @return array{title: string, type: string, generated_at: string, from: ?string, to: ?string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totals: array<string, int|float>, records: array<int, array{fish: string, species: string, classification: string, confidence: ?float, quantity: ?float, datetime: ?string, detail: ?string}>}
     */
    private function lowStock(User $user): array
    {
        $items = $user->inventoryItems()->orderBy('name')->get()
            ->filter(fn (InventoryItem $item): bool => $item->isLowStock())
            ->values();

        $confidence = $this->latestConfidence($user, $items);

        $rows = $items->map(fn (InventoryItem $item): array => [
            $item->name,
            (string) $item->species,
            round((float) $item->quantity, 2),
            round($item->lowStockThreshold(), 2),
            round($item->lowStockThreshold() - (float) $item->quantity, 2),
            $item->freshnessLabel(),
        ])->all();

        $records = $items->map(fn (InventoryItem $item): array => [
            'fish' => (string) $item->name,
            'species' => (string) $item->species,
            'classification' => $item->freshnessLabel(),
            'confidence' => $this->itemConfidence($item, $confidence),
            'quantity' => round((float) $item->quantity, 2),
            'datetime' => $item->last_scanned_at?->format('Y-m-d H:i'),
            'detail' => $item->isLowStock()
                ? 'short by '.round($item->lowStockThreshold() - (float) $item->quantity, 2).' kg'
                : null,
        ])->all();

        return [
            'title' => 'Low-Stock Report',
            'type' => 'lowstock',
            'generated_at' => now()->format('M j, Y H:i'),
            'from' => null,
            'to' => null,
            'headers' => ['Item', 'Species', 'On Hand (kg)', 'Min Stock (kg)', 'Short By (kg)', 'Freshness'],
            'rows' => $rows,
            'records' => $records,
            'totals' => [
                'low_stock_items' => $items->count(),
                'total_short_kg' => round(
                    $items->sum(fn (InventoryItem $item): float => $item->lowStockThreshold() - (float) $item->quantity),
                    2,
                ),
                'total_quantity' => round($items->sum(fn (InventoryItem $item): float => (float) $item->quantity), 2),
            ],
        ];
    }

    /**
     * Supplier Delivery Report: every delivery in the window with supplier,
     * promised vs received quantities and the acceptance verdict. Its records
     * quote the quantity that physically arrived and who it came from.
     *
     * @return array{title: string, type: string, generated_at: string, from: ?string, to: ?string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totals: array<string, int|float>, records: array<int, array{fish: string, species: string, classification: string, confidence: ?float, quantity: ?float, datetime: ?string, detail: ?string}>}
     */
    private function deliveries(User $user, Carbon $from, Carbon $to): array
    {
        $deliveries = Delivery::query()
            ->where('user_id', $user->id)
            ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString()])
            ->with('supplier')
            ->latest('delivery_date')
            ->latest('id')
            ->get();

        $totalRecorded = round($deliveries->sum(fn (Delivery $delivery): float => $delivery->receivedQuantity()), 2);
        $totalReceived = round($deliveries->sum(fn (Delivery $delivery): float => (float) ($delivery->actual_quantity ?? 0)), 2);

        $rows = $deliveries->map(fn (Delivery $delivery): array => [
            $delivery->delivery_date->format('Y-m-d'),
            (string) ($delivery->supplier?->name ?? '—'),
            (string) $delivery->fish_type,
            round((float) $delivery->expected_quantity, 2),
            $delivery->actual_quantity === null ? '—' : round((float) $delivery->actual_quantity, 2),
            $delivery->variance_label,
            ucfirst((string) $delivery->status),
        ])->all();

        $records = $deliveries->map(fn (Delivery $delivery): array => [
            'fish' => (string) $delivery->fish_type,
            'species' => FishAiService::latinFor((string) $delivery->fish_type),
            'classification' => ucfirst((string) $delivery->status),
            'confidence' => null,
            'quantity' => round($delivery->receivedQuantity(), 2),
            'datetime' => $delivery->delivery_date->format('Y-m-d'),
            'detail' => (string) ($delivery->supplier?->name ?? ''),
        ])->all();

        return [
            'title' => 'Supplier Delivery Report',
            'type' => 'deliveries',
            'generated_at' => now()->format('M j, Y H:i'),
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'headers' => ['Date', 'Supplier', 'Fish', 'Expected (kg)', 'Received (kg)', 'Variance', 'Status'],
            'rows' => $rows,
            'records' => $records,
            'totals' => [
                'deliveries' => $deliveries->count(),
                'accepted' => $deliveries->where('status', Delivery::STATUS_ACCEPTED)->count(),
                'pending' => $deliveries->where('status', Delivery::STATUS_PENDING)->count(),
                'rejected' => $deliveries->where('status', Delivery::STATUS_REJECTED)->count(),
                'total_expected_kg' => round($deliveries->sum(fn (Delivery $delivery): float => (float) $delivery->expected_quantity), 2),
                'total_received_kg' => $totalReceived,
                /* What each record quotes: the quantity that arrived, or the
                   quantity promised while the delivery is still pending. */
                'total_quantity' => $totalRecorded,
            ],
        ];
    }
}
