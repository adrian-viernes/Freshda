<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEMPORARY scaffolding for the blade restructure: renders the dashboard for
 * both roles into storage/framework/snapshots so the before/after markup can
 * be diffed. Delete this file once the refactor is verified.
 */
class ZZSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_dashboard_snapshots(): void
    {
        $dir = storage_path('framework/snapshots');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach (['vendor', 'customer'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'name' => 'Snapshot '.ucfirst($role),
                'email' => $role.'@snapshot.test',
            ]);

            $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

            file_put_contents($dir.'/'.$role.'.html', $html);
        }

        $this->assertDirectoryExists($dir);
    }
}
