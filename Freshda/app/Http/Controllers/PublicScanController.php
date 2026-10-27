<?php

namespace App\Http\Controllers;

use App\Services\FishAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicScanController extends Controller
{
    /**
     * Analyze an image for a visitor without an account.
     *
     * Nothing is persisted on purpose: public traffic must not create scan
     * records, notifications, or inventory updates — that belongs to the
     * authenticated scanner. FishAiService answers from the deployed model
     * when AI_SERVICE_URL is configured and from the offline simulator when
     * it is not, exactly like the private scanner.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        /* The upload's temp path lives for this request only, which is long
           enough for the model service to read it — nothing is stored. */
        $image = $request->file('image');
        $imagePath = $image !== null && $image->isValid() ? $image->getRealPath() : null;

        $result = app(FishAiService::class)->predict($imagePath ?: null);

        /* Rejects non-fish images (e.g. persons, backgrounds, or low confidence) */
        if (isset($result['is_fish']) && ! $result['is_fish']) {
            throw ValidationException::withMessages([
                'image' => $result['message'] ?? 'No fish detected. Please position the fish clearly in frame and scan again.',
            ]);
        }

        return response()->json([
            'name' => $result['species'],
            'species_label' => $result['species_label'],
            'species_confidence' => $result['species_confidence'],
            'freshness' => $result['freshness'],
            'freshness_percentage' => $result['freshness_percentage'],
            'freshness_confidence' => $result['freshness_confidence'],
            'cls' => $result['freshness'] === 'fresh'
                ? 'ok'
                : ($result['freshness'] === 'moderate' ? 'warn' : 'bad'),
            'confidence_band' => FishAiService::confidenceBand((float) $result['species_confidence']),
            'source' => $result['source'],
            'message' => $result['source'] === 'ai'
                ? 'Analysis Complete — AI Model Result.'
                : 'Analysis Complete — Simulated Result (AI Service Offline).',
        ]);
    }
}
