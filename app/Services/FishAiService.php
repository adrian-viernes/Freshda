<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Species + freshness recognition for a scan.
 *
 * With AI_SERVICE_URL configured (see config/ai.php) every scan image is
 * POSTed to the deployed YOLOv8 model service and comes back with source
 * "ai". While no URL is configured — or whenever the service is
 * unreachable, slow, errors, or answers outside the documented contract —
 * predict() returns a deterministic simulated result with source
 * "simulated", so a scan never fails just because the model is offline.
 */
class FishAiService
{
    /**
     * @var array<int, array{0: string, 1: string}>
     */
    public const CANDIDATES = [
        ['Milkfish', 'Chanos chanos'],
        ['Tilapia', 'Oreochromis niloticus'],
        ['Salmon', 'Salmo salar'],
        ['Mackerel', 'Scomber scombrus'],
        ['Bangus', 'Chanos chanos'],
        ['Tuna', 'Thunnus albacares'],
        ['Snapper', 'Lutjanus spp.'],
        ['Catfish', 'Pangasius pangasius'],
    ];

    /**
     * The only verdicts the app understands; a model answer naming anything
     * else is a contract breach and degrades to a simulated result.
     *
     * @var array<int, string>
     */
    private const FRESHNESS_STATES = ['fresh', 'moderate', 'not_fresh'];

    /**
     * Produce the result for a scan: the model's answer when the service is
     * connected, a deterministic simulated one otherwise.
     *
     * @return array{species: string, species_label: string, species_confidence: float, freshness: string, freshness_percentage: int, freshness_confidence: float, source: string}
     */
    public function predict(?string $imagePath = null): array
    {
        $baseUrl = config('ai.url');

        if (! is_string($baseUrl) || $baseUrl === '' || $imagePath === null || ! is_file($imagePath)) {
            return self::simulated();
        }

        return $this->askModel(rtrim($baseUrl, '/'), $imagePath) ?? self::simulated();
    }

    /**
     * Confidence bands the UI displays next to a scan: high ≥ 0.80,
     * moderate ≥ 0.60, low below that.
     */
    public static function confidenceBand(float $confidence): string
    {
        $confidence = self::clamp01($confidence);

        return match (true) {
            $confidence >= 0.80 => 'high',
            $confidence >= 0.60 => 'moderate',
            default => 'low',
        };
    }

    /**
     * Build a deterministic-looking offline result for a scan.
     *
     * @return array{species: string, species_label: string, species_confidence: float, freshness: string, freshness_percentage: int, freshness_confidence: float, source: string}
     */
    public static function simulated(): array
    {
        $classes = [
            ['fresh', 80, 98],
            ['fresh', 80, 98],
            ['moderate', 60, 79],
            ['not_fresh', 20, 55],
        ];

        [$species, $label] = self::CANDIDATES[array_rand(self::CANDIDATES)];
        [$freshness, $min, $max] = $classes[array_rand($classes)];
        $percentage = random_int($min, $max);

        return [
            'species' => $species,
            'species_label' => $label,
            'species_confidence' => round(random_int(8500, 9900) / 10000, 2),
            'freshness' => $freshness,
            'freshness_percentage' => $percentage,
            'freshness_confidence' => round($percentage / 100, 2),
            'source' => 'simulated',
        ];
    }

    public static function latinFor(string $species): string
    {
        foreach (self::CANDIDATES as $candidate) {
            if (str_contains(strtolower($species), strtolower($candidate[0]))) {
                return $candidate[1];
            }
        }

        return $species;
    }

    /**
     * Ask the FastAPI model service for one prediction.
     *
     * @return array{species: string, species_label: string, species_confidence: float, freshness: string, freshness_percentage: int, freshness_confidence: float, source: string}|null
     *                                                                                                                                                                                  The model's answer, or null on any failure so the caller can
     *                                                                                                                                                                                  degrade to a simulated result instead of failing the scan.
     */
    private function askModel(string $baseUrl, string $imagePath): ?array
    {
        try {
            $response = Http::timeout((int) config('ai.timeout', 15))
                ->attach('image', $imagePath, basename($imagePath))
                ->post($baseUrl.'/predict');
        } catch (\Throwable $exception) {
            Log::warning('Fish AI service unreachable: '.$exception->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Fish AI service answered with status '.$response->status().'.');

            return null;
        }

        $payload = $response->json();

        return self::fromPayload(is_array($payload) ? $payload : []);
    }

    /**
     * Validate the model's JSON against the contract in config/ai.php and
     * shape it the way the rest of the app consumes it; anything outside
     * the contract is rejected (null) so the scan degrades to simulated.
     *
     * @param  array<string, mixed>  $payload
     * @return array{species: string, species_label: string, species_confidence: float, freshness: string, freshness_percentage: int, freshness_confidence: float, source: string}|null
     */
    private static function fromPayload(array $payload): ?array
    {
        $species = trim((string) ($payload['species'] ?? ''));
        $freshness = (string) ($payload['freshness'] ?? '');
        $percentage = filter_var($payload['freshness_percentage'] ?? null, FILTER_VALIDATE_INT);
        $speciesConfidence = $payload['species_confidence'] ?? null;

        if ($species === ''
            || ! in_array($freshness, self::FRESHNESS_STATES, true)
            || $percentage === false
            || $percentage < 0
            || $percentage > 100
            || ! is_numeric($speciesConfidence)) {
            return null;
        }

        $label = trim((string) ($payload['species_label'] ?? ''));
        $freshnessConfidence = $payload['freshness_confidence'] ?? null;

        return [
            'species' => $species,
            'species_label' => $label === '' ? self::latinFor($species) : $label,
            'species_confidence' => self::clamp01((float) $speciesConfidence),
            'freshness' => $freshness,
            'freshness_percentage' => $percentage,
            'freshness_confidence' => is_numeric($freshnessConfidence)
                ? self::clamp01((float) $freshnessConfidence)
                : round($percentage / 100, 2),
            'source' => 'ai',
        ];
    }

    /**
     * Confidences are fractions, so a percent-scale payload must not be
     * stored or displayed as e.g. "90.00".
     */
    private static function clamp01(float $value): float
    {
        return round(max(0.0, min(1.0, $value > 1.0 ? $value / 100 : $value)), 2);
    }
}
