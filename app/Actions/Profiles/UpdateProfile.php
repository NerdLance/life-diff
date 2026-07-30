<?php

namespace App\Actions\Profiles;

use App\Models\User;
use Illuminate\Support\Str;

class UpdateProfile
{
    /**
     * @param  array{bio: string|null, display_name: string, email: string, handle: string, status: string, timezone: string}  $attributes
     */
    public function __invoke(User $user, array $attributes): void
    {
        $user->fill([
            ...$attributes,
            'handle' => Str::lower($attributes['handle']),
            'name' => $attributes['display_name'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
