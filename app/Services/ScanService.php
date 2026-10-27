<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Records what an authenticated scan found: the scan row itself, the alert it
 * raises and the inventory freshness it keeps in step for vendors.
 */
class ScanService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Store one analyzed scan and return the payload the scanner renders.
     *
     * @return array{id: int, name: ?string, species_label: string, species_confidence: float, freshness: string, freshness_percentage: int, freshness_confidence: float, cls: string, confidence_band: string, image: ?string, source: string, message: string}
     */
    public function store(User $user, ?UploadedFile $image): array
    {
        $imagePath = null;

        if ($image !== null && $image->isValid()) {
            $imagePath = $image->store('scans', 'public');
        }

        $absolutePath = $imagePath === null ? null : storage_path('app/public/'.$imagePath);

        /* The model service answers when it is connected (source "ai");
           FishAiService degrades to a simulated result when it is not. */
        $result = app(FishAiService::class)->predict($absolutePath);

        $scan = $user->scans()->create([
            'image_path' => $imagePath,
            'species' => $result['species'],
            'species_confidence' => $result['species_confidence'],
            'freshness' => $result['freshness'],
            'freshness_percentage' => $result['freshness_percentage'],
            'freshness_confidence' => $result['freshness_confidence'],
            'source' => 'single',
        ]);

        $this->notifications->scanCompleted($scan, $result['source']);

        /* D10: only a real model result may restamp inventory freshness —
           a simulated scan must never overwrite the vendor's data. */
        if ($user->isVendor() && $result['source'] === 'ai') {
            $this->syncInventory($user, $scan);
        }

        return [
            'id' => $scan->id,
            'name' => $scan->species,
            'species_label' => FishAiService::latinFor((string) $scan->species),
            'species_confidence' => $scan->species_confidence,
            'freshness' => $scan->freshnessLabel(),
            'freshness_percentage' => $scan->freshness_percentage,
            'freshness_confidence' => $scan->freshness_confidence,
            'cls' => $scan->freshnessClass(),
            'confidence_band' => FishAiService::confidenceBand((float) $scan->species_confidence),
            'image' => $imagePath === null ? null : asset('storage/'.$imagePath),
            'source' => $result['source'],
            'message' => $result['source'] === 'ai'
                ? 'Analysis Complete — AI Model Result.'
                : 'Analysis Complete — AI Service Offline, Simulated Result Shown.',
        ];
    }

    /**
     * Push the scan result onto the vendor's inventory: every matching row is
     * updated, because duplicate names can exist and leaving later copies
     * stale would report outdated freshness to the vendor.
     */
    private function syncInventory(User $user, Scan $scan): void
    {
        InventoryItem::query()
            ->where('user_id', $user->id)
            ->where('name', $scan->species)
            ->update([
                'freshness' => $scan->freshness,
                'freshness_percentage' => $scan->freshness_percentage,
                'last_scanned_at' => now(),
            ]);
    }
}
