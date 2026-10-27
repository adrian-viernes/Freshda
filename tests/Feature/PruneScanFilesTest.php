<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneScanFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphaned_scan_photos_are_deleted_and_referenced_ones_are_kept(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $kept = 'scans/kept.jpg';
        Storage::disk('public')->put($kept, 'kept');
        Storage::disk('public')->put('scans/orphan.jpg', 'orphan');
        Storage::disk('public')->put('scans/another-orphan.jpg', 'orphan');

        $user->scans()->create([
            'image_path' => $kept,
            'species' => 'Tilapia',
            'species_confidence' => 0.9,
            'freshness' => 'fresh',
            'freshness_percentage' => 92,
            'freshness_confidence' => 0.92,
            'source' => 'single',
        ]);

        $this->artisan('scans:prune-files')
            ->expectsOutputToContain('Deleted 2 orphaned scan photo(s).')
            ->assertSuccessful();

        Storage::disk('public')->assertExists($kept);
        Storage::disk('public')->assertMissing('scans/orphan.jpg');
        Storage::disk('public')->assertMissing('scans/another-orphan.jpg');
    }

    public function test_dry_run_only_lists_orphaned_files(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('scans/orphan.jpg', 'orphan');

        $this->artisan('scans:prune-files', ['--dry-run' => true])
            ->expectsOutputToContain('Would delete 1 orphaned scan photo(s).')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('scans/orphan.jpg');
    }

    public function test_the_prune_command_is_scheduled_daily(): void
    {
        $events = collect(Schedule::events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'scans:prune-files'));

        $this->assertNotEmpty($events, 'scans:prune-files should be scheduled');
    }
}
