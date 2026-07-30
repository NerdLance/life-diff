<?php

namespace App\Http\Requests\Releases;

use App\Enums\ReleaseType;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseVersionSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');

        return $repository instanceof Repository && $this->user()?->can('create', [Release::class, $repository]) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'release_type' => ['nullable', Rule::enum(ReleaseType::class)],
        ];
    }

    public function releaseType(): ReleaseType
    {
        return ReleaseType::from($this->input('release_type', ReleaseType::Patch->value));
    }
}
