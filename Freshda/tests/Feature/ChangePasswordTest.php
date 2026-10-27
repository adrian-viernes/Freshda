<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password Updated Successfully.');

        $this->assertTrue(Hash::check('brand-new-secret', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_current_password_must_match(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'not-the-password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $mismatch = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'different-secret',
        ]);

        $mismatch->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_new_password_must_differ_from_the_current_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_guests_cannot_change_a_password(): void
    {
        $response = $this->patchJson('/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ]);

        $response->assertUnauthorized();
    }

    /**
     * The login screen keeps things simple: no direct change-password form
     * (that lives in Profile / Settings) and no forgotten-password flow.
     */
    public function test_the_login_screen_offers_no_change_password_or_forgot_password(): void
    {
        $html = (string) $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('Forgot password?', $html);

        foreach ([
            'id="changePasswordView"',
            'id="changePasswordForm"',
            'id="goToChangePasswordLink"',
            'id="changeEmail"',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $html, "Login page should no longer show: {$needle}");
        }
    }

    public function test_the_login_screen_change_password_route_is_gone(): void
    {
        $this->post('/password/from-login', [
            'email' => 'juan@freshda.ph',
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertNotFound();
    }

    public function test_the_profile_and_settings_pages_open_the_change_password_modal(): void
    {
        $user = User::factory()->vendor()->create();

        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="changePasswordBtn"', $html);
        $this->assertStringContainsString('id="settingsChangePasswordBtn"', $html);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('openChangePasswordModal', $script);
        $this->assertStringContainsString('"/password"', $script);
    }
}
