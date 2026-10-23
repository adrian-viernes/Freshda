<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The register form has no vendor/customer picker anymore, and the role it
     * used to post is ignored: every new account is a vendor.
     */
    public function test_a_registration_always_creates_a_vendor_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'maria@freshda.ph')->firstOrFail();
        $this->assertSame('vendor', $user->role);
        $this->assertTrue($user->isVendor());
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_a_vendor_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pedro Reyes',
            'email' => 'pedro@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'vendor',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'pedro@freshda.ph')->firstOrFail();
        $this->assertTrue($user->isVendor());
    }

    public function test_the_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@freshda.ph']);

        $response = $this->from(route('login'))->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'taken@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * The picker is gone, so a role value posted by a crafted request is
     * ignored rather than validated — the account is still a vendor.
     */
    public function test_a_posted_role_is_ignored(): void
    {
        foreach (['admin', 'supplier'] as $role) {
            $response = $this->post('/register', [
                'name' => 'Maria Santos',
                'email' => $role.'@freshda.ph',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => $role,
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticated();
            $this->post('/logout');

            $this->assertSame(
                'vendor',
                User::query()->where('email', $role.'@freshda.ph')->firstOrFail()->role
            );
        }

        $this->assertDatabaseCount('users', 2);
    }

    public function test_the_password_must_be_confirmed_and_at_least_eight_characters(): void
    {
        $short = $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@freshda.ph',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'customer',
        ]);

        $short->assertSessionHasErrors('password');

        $mismatch = $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'other-password',
            'role' => 'customer',
        ]);

        $mismatch->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_name_and_email_are_required(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    /**
     * The register form lives on the login page and no longer asks which kind
     * of account to create — sign-up is vendor-only.
     */
    public function test_the_register_form_does_not_ask_for_a_role(): void
    {
        $html = (string) $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('id="registerForm"', $html);
        $this->assertStringNotContainsString('name="role"', $html);
        $this->assertStringNotContainsString('role-picker', $html);

        foreach (['value="admin"', 'value="supplier"', 'Google'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }

    public function test_a_signed_in_user_is_redirected_away_from_the_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect();
        $this->assertNotSame(route('login'), $response->headers->get('Location'));
    }

    /**
     * Emails are stored lowercase so they always match the normalized lookup
     * used by login and both password flows.
     */
    public function test_the_stored_email_is_normalized_to_lowercase(): void
    {
        $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => '  Maria@FreshDA.ph ',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'maria@freshda.ph']);
    }

    /** A duplicate is rejected even when only the casing differs. */
    public function test_a_duplicate_email_is_rejected_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'taken@freshda.ph']);

        $response = $this->from(route('login'))->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'TAKEN@FreshDA.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * The register form lives on the login page, so a rejected signup must
     * reopen that panel with its error instead of hiding it behind the login
     * view after the redirect back.
     */
    public function test_a_rejected_registration_reopens_the_register_panel(): void
    {
        User::factory()->create(['email' => 'taken@freshda.ph']);

        $response = $this->followingRedirects()->from(route('login'))->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'taken@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ]);

        $response->assertOk();
        $response->assertSee('data-view="register"', false);
        $response->assertSee('The email has already been taken.', false);

        $this->get(route('login'))->assertSee('data-view="login"', false);
    }

    /** A freshly created account must be usable on the next login attempt. */
    public function test_a_newly_registered_account_can_log_in_after_logging_out(): void
    {
        $this->post('/register', [
            'name' => 'Vendor Account',
            'email' => 'vendor@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'vendor@freshda.ph',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
    }

    /** The auth POST endpoints are guest-only, so a signed-in user is bounced. */
    public function test_a_signed_in_user_cannot_post_the_auth_forms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/login', [
            'email' => 'someone@freshda.ph',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($user)->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@freshda.ph',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'customer',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
    }
}
