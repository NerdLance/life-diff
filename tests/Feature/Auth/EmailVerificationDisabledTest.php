<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_is_disabled_and_unverified_users_can_access_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse(Features::enabled(Features::emailVerification()));
        $this->assertFalse(Route::has('verification.notice'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
