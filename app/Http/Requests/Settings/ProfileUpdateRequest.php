<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('update', $this->user());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'handle' => Str::lower((string) $this->input('handle')),
        ]);
    }

    /**
     * @return array{bio: string|null, display_name: string, email: string, handle: string, status: string, timezone: string}
     */
    public function profileAttributes(): array
    {
        $bio = $this->input('bio');

        return [
            'handle' => $this->string('handle')->toString(),
            'display_name' => $this->string('display_name')->toString(),
            'email' => $this->string('email')->toString(),
            'bio' => is_string($bio) ? $bio : null,
            'status' => $this->string('status')->toString(),
            'timezone' => $this->string('timezone')->toString(),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }
}
