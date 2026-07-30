<?php

namespace Tests\Feature\Settings;

use App\Enums\ProfileStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'handle' => 'Test-User',
                'display_name' => 'Test User',
                'email' => 'test@example.com',
                'bio' => 'A short profile.',
                'status' => ProfileStatus::Experimental->value,
                'timezone' => 'America/New_York',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('Test User', $user->display_name);
        $this->assertSame('test-user', $user->handle);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('A short profile.', $user->bio);
        $this->assertSame(ProfileStatus::Experimental, $user->status);
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'handle' => $user->handle,
                'display_name' => 'Test User',
                'email' => $user->email,
                'bio' => $user->bio,
                'status' => $user->status->value,
                'timezone' => $user->timezone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_handle_changes_are_normalized_and_preserve_the_display_name_mirror()
    {
        $user = User::factory()->create(['handle' => 'old-handle']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'handle' => 'New-Handle',
                'display_name' => 'New Display Name',
                'email' => $user->email,
                'bio' => null,
                'status' => ProfileStatus::Stable->value,
                'timezone' => 'UTC',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('new-handle', $user->handle);
        $this->assertSame('New Display Name', $user->display_name);
        $this->assertSame('New Display Name', $user->name);
    }

    public function test_invalid_timezones_are_rejected()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'handle' => $user->handle,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'bio' => $user->bio,
                'status' => $user->status->value,
                'timezone' => 'Mars/Olympus_Mons',
            ])
            ->assertSessionHasErrors('timezone')
            ->assertRedirect(route('profile.edit'));
    }

    public function test_bios_over_five_hundred_characters_are_rejected()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'handle' => $user->handle,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'bio' => str_repeat('a', 501),
                'status' => $user->status->value,
                'timezone' => $user->timezone,
            ])
            ->assertSessionHasErrors('bio')
            ->assertRedirect(route('profile.edit'));
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
