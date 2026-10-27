<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_an_account_writes_an_audit_entry(): void
    {
        $response = $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertRedirect(route('dashboard'));

        $entry = ActivityLog::query()
            ->where('action', 'account.registered')
            ->where('email', 'juan@example.com')
            ->firstOrFail();

        $this->assertSame('New account registered', $entry->description);
        $this->assertNotNull($entry->user_id);
        $this->assertNotNull($entry->ip);
    }

    public function test_changing_the_password_writes_an_audit_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ]);

        $response->assertOk();

        $entry = ActivityLog::query()
            ->where('action', 'password.changed')
            ->where('email', $user->email)
            ->firstOrFail();

        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame('Password changed via profile or settings', $entry->description);
    }

    public function test_audit_rows_survive_the_user_being_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertOk();

        $user->delete();

        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertNull(ActivityLog::query()->first()->user_id);
        $this->assertNotNull(ActivityLog::query()->first()->email);
    }
}
