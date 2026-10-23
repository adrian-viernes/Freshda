<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_the_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $authenticatedSessionId = session()->getId();

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($authenticatedSessionId, session()->getId());

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    /**
     * The login form's "Remember me" checkbox has to reach the auth layer,
     * otherwise the control is decorative and the session dies on browser close.
     */
    public function test_ticking_remember_me_issues_a_persistent_login_cookie(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $rememberCookies = array_filter(
            $response->headers->getCookies(),
            fn ($cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'),
        );

        $this->assertNotEmpty($rememberCookies, 'Remember me should issue a persistent cookie');
    }

    public function test_login_without_remember_me_issues_no_persistent_cookie(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $rememberCookies = array_filter(
            $response->headers->getCookies(),
            fn ($cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'),
        );

        $this->assertEmpty($rememberCookies);
    }

    public function test_a_guest_cannot_reach_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_pages_are_marked_not_cacheable(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * Server-side field errors (bad email format, empty password) have to be
     * visible on the login form instead of the generic message hiding them.
     */
    public function test_login_field_errors_are_shown_on_the_login_form(): void
    {
        $response = $this->followingRedirects()->from(route('login'))->post('/login', [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response->assertOk();
        $response->assertSee('valid email address', false);
    }

    public function test_vendor_and_customer_accounts_both_log_in_to_the_dashboard(): void
    {
        foreach ([User::factory()->vendor()->create(['password' => 'secret-password']), User::factory()->create(['password' => 'secret-password'])] as $user) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ])->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);

            $this->post('/logout')->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_the_role_sent_in_the_request_cannot_override_the_database_role(): void
    {
        $customer = User::factory()->create(['password' => 'secret-password']);
        $vendor = User::factory()->vendor()->create(['password' => 'secret-password']);

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'secret-password',
            'role' => 'vendor',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($customer);
        $this->assertSame('customer', $customer->fresh()->role);

        $this->post('/logout');

        $this->post('/login', [
            'email' => $vendor->email,
            'password' => 'secret-password',
            'role' => 'customer',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($vendor);
        $this->assertSame('vendor', $vendor->fresh()->role);
    }

    /**
     * Even a row that somehow holds an unsupported role (admin, supplier) must
     * not authenticate. Registration already refuses to create one.
     */
    public function test_an_unsupported_role_cannot_log_in(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'secret-password',
        ]);

        $response = $this->from(route('login'))->post('/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['credentials' => 'Invalid Email or Password.']);
        $this->assertGuest();
    }

    public function test_sql_injection_payloads_cannot_authenticate(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->from(route('login'))->post('/login', [
            'email' => "' OR '1'='1",
            'password' => "' OR '1'='1",
        ])->assertSessionHasErrors();
        $this->assertGuest();

        $this->from(route('login'))->post('/login', [
            'email' => $user->email,
            'password' => "' OR 1=1 --",
        ])->assertSessionHasErrors(['credentials' => 'Invalid Email or Password.']);
        $this->assertGuest();
    }

    public function test_malformed_and_oversized_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $payloads = [
            ['email' => '', 'password' => 'secret-password'],
            ['email' => '   ', 'password' => 'secret-password'],
            ['email' => 'test', 'password' => 'secret-password'],
            ['email' => 'test@', 'password' => 'secret-password'],
            ['email' => '@test.com', 'password' => 'secret-password'],
            ['email' => 'test.com', 'password' => 'secret-password'],
            ['email' => str_repeat('a', 246).'@example.com', 'password' => 'secret-password'],
            ['email' => $user->email, 'password' => ''],
        ];

        /* A distinct IP per case keeps the login throttle from masking the
           validation response we are asserting. */
        foreach ($payloads as $index => $payload) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($index + 1)])
                ->from(route('login'))
                ->post('/login', $payload)
                ->assertSessionHasErrors();
            $this->assertGuest();
        }
    }

    public function test_invalid_credentials_use_the_same_generic_message(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->from(route('login'))->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['credentials' => 'Invalid Email or Password.']);

        $this->from(route('login'))->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['credentials' => 'Invalid Email or Password.']);

        $this->assertGuest();
    }

    public function test_the_session_id_is_regenerated_after_login(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->get(route('login'))->assertOk();
        $before = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    public function test_passwords_are_hashed_and_verified_by_the_login_flow(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_script_payload_in_the_email_is_escaped_on_the_login_page(): void
    {
        $response = $this->followingRedirects()->from(route('login'))->post('/login', [
            'email' => '<script>alert(1)</script>',
            'password' => 'secret-password',
        ]);

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_login_page_has_no_password_reset_or_social_login(): void
    {
        $html = (string) $this->get('/login')->assertOk()->getContent();

        $forbidden = [
            'Forgot Password',
            'Reset Password',
            'Send Password Reset',
            'Sign in with Google',
            'Continue with Google',
            'Admin Login',
            'Supplier Login',
        ];

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString($needle, $html, $needle);
        }
    }

    public function test_the_login_button_locks_while_submitting(): void
    {
        $script = (string) file_get_contents(public_path('js/auth.js'));

        $this->assertStringContainsString('loginSubmitting', $script);
        $this->assertStringContainsString('loginBtn.disabled = true', $script);
        $this->assertStringContainsString('Logging in...', $script);
        $this->assertStringContainsString('window.addEventListener("pageshow"', $script);
    }

    /**
     * A different account signing in on the same device must not inherit the
     * previous account's unsaved batch captures.
     */
    public function test_stale_browser_data_is_cleared_for_the_next_account(): void
    {
        $script = (string) file_get_contents(public_path('js/auth.js'));

        $this->assertStringContainsString('freshda-batch-draft', $script);
        $this->assertStringNotContainsString('freshda-chat-history', $script);
        $this->assertStringContainsString('localStorage.removeItem(key)', $script);
    }
}
