<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a batch verification: the batch row, its individual sample scans and
 * the alert the vendor sees on the history page.
 */
class BatchService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Persist a 3–5 photo batch and return the payload the batch screen renders.
     *
     * @param  array{samples: array<int, array{name: string, cls: string, label: string, conf: int|float}>, verdict: string, confidence: int|float}  $validated
     * @return array{id: int, name: string, species_label: string, freshness: string, percentage: int, confidence: float, cls: string, sample_count: int, fresh_count: int, moderate_count: int, not_fresh_count: int, message: string}
     */
    public function store(User $user, array $validated): array
    {
        $counts = ['fresh' => 0, 'moderate' => 0, 'notfresh' => 0];
        foreach ($validated['samples'] as $sample) {
            $counts[$sample['cls']]++;
        }

        /*
         * The verdict is derived from the submitted samples instead of the
         * browser's claim, so a batch cannot be saved as FRESH while every
         * sample reads not fresh. Ties resolve to the worse class.
         */
        $verdict = Batch::verdictFromCounts($counts);
        $confidence = (int) round(array_sum(array_column($validated['samples'], 'conf')) / count($validated['samples']));

        /* Batch row, sample rows and the notification succeed or fail together. */
        [$batch] = DB::transaction(function () use ($user, $validated, $counts, $verdict, $confidence) {
            $batch = $user->batches()->create([
                'sample_count' => count($validated['samples']),
                'fresh_count' => $counts['fresh'],
                'moderate_count' => $counts['moderate'],
                'not_fresh_count' => $counts['notfresh'],
                'verdict' => $verdict,
                'confidence' => $confidence / 100,
            ]);

            foreach ($validated['samples'] as $sample) {
                $user->scans()->create([
                    'batch_id' => $batch->id,
                    'species' => mb_substr($sample['name'], 0, 255),
                    'species_confidence' => round($sample['conf'] / 100, 2),
                    'freshness' => $sample['cls'] === 'notfresh' ? 'not_fresh' : $sample['cls'],
                    'freshness_percentage' => (int) round($sample['conf']),
                    'freshness_confidence' => round($sample['conf'] / 100, 2),
                    'source' => 'batch',
                ]);
            }

            $this->notifications->batchVerified($batch);

            return [$batch];
        });

        return [
            'id' => $batch->id,
            'name' => $batch->displayName(),
            'species_label' => 'Mixed',
            'freshness' => $batch->verdict,
            'percentage' => $confidence,
            'confidence' => round($confidence / 100, 2),
            'cls' => $batch->verdictClass(),
            'sample_count' => $batch->sample_count,
            'fresh_count' => $batch->fresh_count,
            'moderate_count' => $batch->moderate_count,
            'not_fresh_count' => $batch->not_fresh_count,
            'message' => 'Batch Results Saved.',
        ];
    }
}
