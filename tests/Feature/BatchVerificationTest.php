<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function sample(string $name, string $cls = 'fresh', int $conf = 90): array
    {
        return [
            'name' => $name,
            'cls' => $cls,
            'label' => ucfirst($cls === 'notfresh' ? 'not fresh' : $cls),
            'conf' => $conf,
        ];
    }

    public function test_the_capture_card_asks_for_three_to_five_photos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="batchCamFrame"', false);
        $response->assertSee('id="batchOpenCameraBtn"', false);
        $response->assertSee('id="batchCaptureBtn"', false);
        $response->assertSee('id="batchAnalyzeBtn"', false);
        $response->assertSee('3–5 photos per batch', false);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('BATCH_MIN_SAMPLES = 3', $script);
        $this->assertStringContainsString('BATCH_MAX_SAMPLES = 5', $script);
        $this->assertStringContainsString('createFishCamera', $script);

        /* The in-progress batch must survive a refresh. */
        $this->assertStringContainsString('BATCH_STORAGE_KEY', $script);
        $this->assertStringContainsString('function saveBatchDraft', $script);
        $this->assertStringContainsString('function loadBatchDraft', $script);
    }

    public function test_a_batch_with_the_minimum_three_photos_is_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish'),
                $this->sample('Tilapia', 'moderate', 75),
                $this->sample('Salmon'),
            ],
            'verdict' => 'FRESH',
            'confidence' => 88,
        ]);

        $response->assertOk();
        $response->assertJsonPath('id', fn ($id) => $id > 0);
        $response->assertJsonPath('freshness', 'FRESH');

        $this->assertDatabaseHas('batches', [
            'user_id' => $user->id,
            'sample_count' => 3,
            'fresh_count' => 2,
            'moderate_count' => 1,
            'not_fresh_count' => 0,
            'verdict' => 'FRESH',
        ]);

        $this->assertSame(3, Scan::where('user_id', $user->id)->where('source', 'batch')->count());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'title' => 'Batch Verified — FRESH',
        ]);
    }

    public function test_a_batch_with_five_photos_is_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish'),
                $this->sample('Tilapia'),
                $this->sample('Salmon'),
                $this->sample('Mackerel', 'moderate', 70),
                $this->sample('Tuna', 'notfresh', 55),
            ],
            'verdict' => 'MODERATE',
            'confidence' => 71,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('batches', [
            'user_id' => $user->id,
            'sample_count' => 5,
            'not_fresh_count' => 1,
        ]);
    }

    public function test_a_batch_needs_at_least_three_photos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/batches', [
            'samples' => [$this->sample('Milkfish'), $this->sample('Tilapia')],
            'verdict' => 'FRESH',
            'confidence' => 90,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('samples');
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_a_batch_rejects_more_than_five_photos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish'),
                $this->sample('Tilapia'),
                $this->sample('Salmon'),
                $this->sample('Mackerel'),
                $this->sample('Tuna'),
                $this->sample('Sardine'),
            ],
            'verdict' => 'FRESH',
            'confidence' => 90,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('samples');
        $this->assertDatabaseCount('batches', 0);
    }

    /**
     * The saved verdict is rendered from the database on the next page load,
     * so it survives a refresh.
     */
    public function test_the_saved_batch_appears_in_history_after_a_refresh(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish'),
                $this->sample('Tilapia'),
                $this->sample('Salmon'),
            ],
            'verdict' => 'FRESH',
            'confidence' => 88,
        ])->assertOk();

        $this->assertTrue(Batch::where('user_id', $user->id)->exists());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Batch (3 samples)', false);
        $response->assertDontSee('No scans yet', false);
    }

    /** The browser can claim FRESH, but the samples decide the verdict. */
    public function test_the_saved_verdict_comes_from_the_samples_not_the_browser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish', 'notfresh', 40),
                $this->sample('Tilapia', 'notfresh', 45),
                $this->sample('Salmon', 'notfresh', 50),
            ],
            'verdict' => 'FRESH',
            'confidence' => 99,
        ]);

        $response->assertOk()->assertJsonPath('freshness', 'NOT FRESH');

        $this->assertDatabaseHas('batches', [
            'user_id' => $user->id,
            'verdict' => 'NOT FRESH',
            'not_fresh_count' => 3,
        ]);
    }

    /** A batch shows up once, as its own row — samples are not listed twice. */
    public function test_batch_samples_are_not_listed_again_in_the_history_table(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/batches', [
            'samples' => [
                $this->sample('Milkfish'),
                $this->sample('Tilapia'),
                $this->sample('Salmon'),
            ],
            'verdict' => 'FRESH',
            'confidence' => 88,
        ])->assertOk();

        $html = (string) $this->actingAs($user)->get('/dashboard')->getContent();

        /* History rows carry data-name attributes; the Recent Scans card does not. */
        $this->assertSame(1, substr_count($html, 'data-name="Batch (3 samples)"'));
        $this->assertStringNotContainsString('data-name="Milkfish"', $html);
        /* Batch samples still reach the Recent Scans card and the stats exclude
           them so the same fish is never counted twice. */
        $this->assertStringContainsString('Milkfish', $html);
        $this->assertStringContainsString('"stats":{"total":0', $html);
    }
}
