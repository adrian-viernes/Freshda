<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_details_are_updated(): void
    {
        $user = User::factory()->create([
            'contact_number' => '09170000000',
            'address' => 'Quezon City',
        ]);

        $response = $this->actingAs($user)->patchJson('/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@freshda.ph',
            'contact_number' => '09171234567',
            'address' => 'Manila',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Juan Dela Cruz')
            ->assertJsonPath('email', 'juan@freshda.ph')
            ->assertJsonPath('contact_number', '09171234567')
            ->assertJsonPath('address', 'Manila')
            ->assertJsonPath('initials', 'JD');

        $user->refresh();
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('juan@freshda.ph', $user->email);
        $this->assertSame('09171234567', $user->contact_number);
        $this->assertSame('Manila', $user->address);
    }

    public function test_blank_contact_and_address_are_cleared(): void
    {
        $user = User::factory()->create([
            'contact_number' => '09170000000',
            'address' => 'Quezon City',
        ]);

        $response = $this->actingAs($user)->patchJson('/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@freshda.ph',
            'contact_number' => '',
            'address' => '',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNull($user->contact_number);
        $this->assertNull($user->address);
    }

    public function test_profile_picture_is_stored_and_the_old_one_is_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->update(['profile_picture_path' => 'profile-pictures/old.jpg']);
        Storage::disk('public')->write('profile-pictures/old.jpg', 'old');

        $response = $this->actingAs($user)->call(
            'PATCH',
            '/profile',
            ['name' => 'Juan Dela Cruz', 'email' => 'juan@freshda.ph'],
            [],
            ['profile_picture' => UploadedFile::fake()->image('avatar.jpg')],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertOk()
            ->assertJsonPath('name', 'Juan Dela Cruz');

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        $this->assertStringStartsWith('profile-pictures/', $user->profile_picture_path);
        Storage::disk('public')->assertExists($user->profile_picture_path);
        Storage::disk('public')->assertMissing('profile-pictures/old.jpg');
        $response->assertJsonPath('profile_picture_url', Storage::url($user->profile_picture_path));
    }

    public function test_profile_picture_must_be_an_image_under_2mb(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->call(
            'PATCH',
            '/profile',
            ['name' => 'Juan Dela Cruz', 'email' => 'juan@freshda.ph'],
            [],
            ['profile_picture' => UploadedFile::fake()->create('notes.txt', 10)],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('profile_picture');

        $user->refresh();
        $this->assertNull($user->profile_picture_path);
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@freshda.ph']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => 'taken@freshda.ph',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_name_and_email_are_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/profile', [
            'name' => '',
            'email' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_profile_picture_upload_via_spoofed_post_form(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile', [
            '_method' => 'PATCH',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@freshda.ph',
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('name', 'Juan Dela Cruz');

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        Storage::disk('public')->assertExists($user->profile_picture_path);
    }

    /**
     * "Remove photo" used to only drop the <img> from the DOM, so the stored
     * picture came back on the next refresh and login.
     */
    public function test_a_stored_profile_picture_can_be_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->update(['profile_picture_path' => 'profile-pictures/me.jpg']);
        Storage::disk('public')->write('profile-pictures/me.jpg', 'photo');

        $response = $this->actingAs($user)->patchJson('/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => $user->email,
            'remove_profile_picture' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('profile_picture_url', null)
            ->assertJsonPath('profile_picture_path', null);

        $user->refresh();
        $this->assertNull($user->profile_picture_path);
        Storage::disk('public')->assertMissing('profile-pictures/me.jpg');
    }

    public function test_a_new_photo_wins_over_a_pending_removal(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->update(['profile_picture_path' => 'profile-pictures/old.jpg']);
        Storage::disk('public')->write('profile-pictures/old.jpg', 'old');

        $response = $this->actingAs($user)->call(
            'PATCH',
            '/profile',
            [
                'name' => 'Juan Dela Cruz',
                'email' => $user->email,
                'remove_profile_picture' => '1',
            ],
            [],
            ['profile_picture' => UploadedFile::fake()->image('avatar.jpg')],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        $this->assertStringStartsWith('profile-pictures/', $user->profile_picture_path);
        Storage::disk('public')->assertExists($user->profile_picture_path);
    }

    /**
     * The uploaded photo has to show up in the topbar, sidebar footer, and
     * the profile page itself.
     */
    public function test_the_uploaded_photo_is_rendered_in_every_avatar(): void
    {
        $user = User::factory()->create(['profile_picture_path' => 'profile-pictures/me.jpg']);

        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, Storage::url('profile-pictures/me.jpg')));
    }

    public function test_avatars_fall_back_to_initials_without_a_photo(): void
    {
        $user = User::factory()->create(['profile_picture_path' => null]);

        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('<img src="/storage/profile-pictures', $html);
        $this->assertStringContainsString($user->initials(), $html);
    }

    /**
     * The removal button posts to the profile endpoint and every avatar is
     * repainted from the response, so no avatar keeps a deleted photo.
     */
    public function test_the_profile_script_removes_the_photo_on_the_server(): void
    {
        $script = $this->dashboardScript();

        $this->assertStringContainsString('remove_profile_picture: 1', $script);
        $this->assertStringContainsString('function removeStoredProfilePicture()', $script);
        $this->assertStringContainsString('function applyAvatarState(el, initials, url)', $script);
        $this->assertStringContainsString('document.querySelectorAll(".avatar, .topbar-avatar")', $script);
        $this->assertStringContainsString('photoBtn.style.display = res.profile_picture_url ? "" : "none";', $script);
    }

    public function test_guests_cannot_update_their_profile(): void
    {
        $response = $this->patchJson('/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@freshda.ph',
        ]);

        $response->assertUnauthorized();
    }

    /** A single-word name must not repeat its own first letter. */
    public function test_single_word_names_get_one_initial(): void
    {
        $user = User::factory()->create(['name' => 'Madonna']);

        $this->assertSame('M', $user->initials());
    }

    /** Two-word names keep the classic two-letter avatar. */
    public function test_two_word_names_get_two_initials(): void
    {
        $user = User::factory()->create(['name' => 'Juan Dela Cruz']);

        $this->assertSame('JD', $user->initials());
    }
}
