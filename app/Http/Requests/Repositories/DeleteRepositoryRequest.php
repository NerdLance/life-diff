<?php

namespace App\Http\Requests\Repositories;

use App\Models\Repository;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');

        return $repository instanceof Repository && $this->user()?->can('delete', $repository) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $repository = $this->route('repository');

        if (! $repository instanceof Repository) {
            return [];
        }

        return [
            'confirmation' => ['required', 'string', Rule::in([$repository->name])],
        ];
    }
}
