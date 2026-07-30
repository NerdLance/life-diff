<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'handle' => 'Test-User',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('test-user', $user->handle);
        $this->assertSame('Test User', $user->display_name);
        $this->assertSame('Test User', $user->name);
    }

    public function test_handles_are_unique_after_normalization()
    {
        User::factory()->create(['handle' => 'Existing-Handle']);

        $this->post(route('register.store'), [
            'handle' => 'EXISTING-HANDLE',
            'display_name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('handle');
    }

    public function test_reserved_handles_are_rejected()
    {
        $this->post(route('register.store'), [
            'handle' => 'admin',
            'display_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('handle');
    }

    public function test_handles_with_invalid_hyphen_placement_are_rejected()
    {
        foreach (['-starts', 'ends-', 'two--hyphens'] as $handle) {
            $this->post(route('register.store'), [
                'handle' => $handle,
                'display_name' => 'Test User',
                'email' => $handle.'@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertSessionHasErrors('handle');
        }
    }
}
