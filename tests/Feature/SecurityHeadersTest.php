<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** D16 — clickjacking, MIME sniffing and referrer leakage stay off. */
    public function test_every_response_carries_the_baseline_security_headers(): void
    {
        $landing = $this->get('/')->assertOk();

        $landing->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $landing->assertHeader('X-Content-Type-Options', 'nosniff');
        $landing->assertHeader('Referrer-Policy', 'same-origin');

        $user = User::factory()->vendor()->create();

        $dashboard = $this->actingAs($user)->get('/dashboard')->assertOk();

        $dashboard->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $dashboard->assertHeader('X-Content-Type-Options', 'nosniff');

        /* Upload endpoints get them too, including rejected requests. */
        $this->postJson('/scan/analyze', [])
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
