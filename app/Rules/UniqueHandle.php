<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueHandle implements ValidationRule
{
    public function __construct(private ?int $ignoredUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = User::query()->whereRaw('LOWER(handle) = ?', [Str::lower((string) $value)]);

        if ($this->ignoredUserId !== null) {
            $query->whereKeyNot($this->ignoredUserId);
        }

        if ($query->exists()) {
            $fail('The handle has already been taken.');
        }
    }
}
