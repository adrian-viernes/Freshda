<?php

namespace Tests\Feature;

use App\Services\FishAiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The offline recognizer powers labels, bands and scan results, so its
 * helpers must keep the same guarantees the old AI plumbing enforced.
 */
class FishAiServiceTest extends TestCase
{
    public function test_confidence_bands_use_the_shared_thresholds(): void
    {
        $this->assertSame('high', FishAiService::confidenceBand(0.80));
        $this->assertSame('high', FishAiService::confidenceBand(0.93));
        $this->assertSame('moderate', FishAiService::confidenceBand(0.60));
        $this->assertSame('moderate', FishAiService::confidenceBand(0.79));
        $this->assertSame('low', FishAiService::confidenceBand(0.42));
    }

    public function test_percent_scale_confidences_are_clamped_to_the_band_scale(): void
    {
        $this->assertSame('high', FishAiService::confidenceBand(95));
        $this->assertSame('low', FishAiService::confidenceBand(0));
        /* Out-of-range percent values clamp to the top of the scale. */
        $this->assertSame('high', FishAiService::confidenceBand(150));
    }

    public function test_species_names_map_to_their_latin_labels(): void
    {
        $this->assertSame('Chanos chanos', FishAiService::latinFor('Milkfish'));
        $this->assertSame('Oreochromis niloticus', FishAiService::latinFor('Tilapia'));
        /* Case-insensitive partial matches stay supported. */
        $this->assertSame('Thunnus albacares', FishAiService::latinFor('tuna'));
    }

    public function test_an_unknown_species_stays_unchanged(): void
    {
        $this->assertSame('Galunggong', FishAiService::latinFor('Galunggong'));
    }

    public function test_simulated_results_carry_an_honest_simulated_source(): void
    {
        $result = (new FishAiService)->predict();

        $this->assertSame('simulated', $result['source']);
        $this->assertContains($result['freshness'], ['fresh', 'moderate', 'not_fresh']);
        $this->assertGreaterThanOrEqual(0, $result['freshness_percentage']);
        $this->assertLessThanOrEqual(100, $result['freshness_percentage']);
        $this->assertArrayHasKey('species', $result);
        $this->assertNotSame('', $result['species']);
    }

    public function test_a_connected_model_answer_is_mapped_into_the_scan_contract(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake([
            'fish-model.test/predict' => Http::response([
                'species' => 'Tilapia',
                'species_label' => 'Oreochromis niloticus',
                /* Percent-scale confidences are accepted and clamped. */
                'species_confidence' => 94,
                'freshness' => 'fresh',
                'freshness_percentage' => 87,
                'freshness_confidence' => 0.87,
            ]),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Tilapia', $result['species']);
        $this->assertSame('Oreochromis niloticus', $result['species_label']);
        $this->assertSame(0.94, $result['species_confidence']);
        $this->assertSame('fresh', $result['freshness']);
        $this->assertSame(87, $result['freshness_percentage']);
        $this->assertSame(0.87, $result['freshness_confidence']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://fish-model.test/predict'
            && $request->hasFile('image'));
    }

    /** An answer without a label borrows the species' latin name. */
    public function test_a_model_answer_without_a_label_gets_the_latin_name(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake([
            'fish-model.test/predict' => Http::response([
                'species' => 'Tuna',
                'species_confidence' => 0.9,
                'freshness' => 'moderate',
                'freshness_percentage' => 66,
            ]),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Thunnus albacares', $result['species_label']);
        $this->assertSame(0.66, $result['freshness_confidence']);
    }

    /** An error answer degrades to simulation; a scan must never 500. */
    public function test_an_erroring_model_service_falls_back_to_a_simulated_result(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake([
            'fish-model.test/predict' => Http::response(['detail' => 'model warming up'], 503),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('simulated', $result['source']);
    }

    public function test_an_unreachable_model_service_falls_back_to_a_simulated_result(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('simulated', $result['source']);
    }

    /** The contract in config/ai.php is enforced, not trusted. */
    public function test_a_payload_outside_the_contract_falls_back_to_a_simulated_result(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake([
            'fish-model.test/predict' => Http::response([
                'species' => 'Tilapia',
                'freshness' => 'rotten',
                'freshness_percentage' => 87,
            ]),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('simulated', $result['source']);
    }

    /** Nothing to analyse means the model service is never called at all. */
    public function test_predict_without_an_image_skips_the_model_service(): void
    {
        config(['ai.url' => 'http://fish-model.test']);

        Http::fake();

        $result = (new FishAiService)->predict();

        $this->assertSame('simulated', $result['source']);
        Http::assertNothingSent();
    }

    public function test_predict_uses_roboflow_workflow_when_configured(): void
    {
        config([
            'roboflow.api_key' => 'rf_test_key',
            'roboflow.workspace' => 'test-workspace',
            'roboflow.workflow_id' => 'test-workflow',
        ]);

        Http::fake([
            'serverless.roboflow.com/*' => Http::response([
                'outputs' => [
                    [
                        'predictions' => [
                            'predictions' => [
                                'Bangus' => ['confidence' => 0.95, 'class_id' => 0],
                                'Fresh' => ['confidence' => 0.89, 'class_id' => 2],
                            ],
                            'predicted_classes' => ['Bangus', 'Fresh'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Bangus', $result['species']);
        $this->assertSame('Chanos chanos', $result['species_label']);
        $this->assertSame(0.95, $result['species_confidence']);
        $this->assertSame('fresh', $result['freshness']);
        $this->assertSame(89, $result['freshness_percentage']);
    }

    public function test_predict_degrades_to_simulated_when_roboflow_errors(): void
    {
        config([
            'roboflow.api_key' => 'rf_test_key',
            'ai.url' => null,
        ]);

        Http::fake([
            'serverless.roboflow.com/*' => Http::response(['message' => 'Internal server error'], 500),
        ]);

        $result = (new FishAiService)->predict($this->modelSampleImage());

        $this->assertSame('simulated', $result['source']);
    }

    /** A real temp file, so the multipart client has a body to attach. */
    private function modelSampleImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'freshda_model');
        file_put_contents($path, 'sample image bytes');

        return (string) $path;
    }
}
