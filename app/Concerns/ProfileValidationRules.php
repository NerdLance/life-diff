<?php

namespace App\Concerns;

use App\Domain\Profiles\ReservedHandles;
use App\Enums\ProfileStatus;
use App\Models\User;
use App\Rules\UniqueHandle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function registrationRules(): array
    {
        return [
            'handle' => $this->handleRules(),
            'display_name' => $this->displayNameRules(),
            'email' => $this->emailRules(),
        ];
    }

    /**
     * Get the validation rules used to update user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'handle' => $this->handleRules($userId),
            'display_name' => $this->displayNameRules(),
            'email' => $this->emailRules($userId),
            'bio' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(ProfileStatus::class)],
            'timezone' => ['required', 'string', 'max:64', 'timezone'],
        ];
    }

    /**
     * Get the validation rules used to validate user handles.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function handleRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
            Rule::notIn(ReservedHandles::all()),
            new UniqueHandle($userId),
        ];
    }

    /**
     * Get the validation rules used to validate display names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function displayNameRules(): array
    {
        return ['required', 'string', 'max:80'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
