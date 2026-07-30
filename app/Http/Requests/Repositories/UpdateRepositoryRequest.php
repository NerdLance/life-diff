<?php

namespace App\Http\Requests\Repositories;

use App\Enums\ProfileStatus;
use App\Enums\RepositoryVisibility;
use App\Models\Repository;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');

        return $repository instanceof Repository && $this->user()?->can('update', $repository) === true;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name');
        $slug = Str::slug((string) ($this->input('slug') ?: $name));

        $this->merge([
            'slug' => $slug,
            'normalized_name' => Str::lower(Str::squish($name)),
        ]);
    }

    /**
     * @return array{description: string|null, name: string, slug: string, status: string, visibility: string}
     */
    public function repositoryAttributes(): array
    {
        $description = $this->input('description');

        return [
            'name' => $this->string('name')->toString(),
            'slug' => $this->string('slug')->toString(),
            'description' => is_string($description) ? $description : null,
            'visibility' => $this->string('visibility')->toString(),
            'status' => $this->string('status')->toString(),
        ];
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
            'name' => ['required', 'string', 'max:80'],
            'normalized_name' => [
                'required',
                Rule::unique(Repository::class, 'normalized_name')
                    ->where('owner_id', $repository->owner_id)
                    ->ignore($repository),
            ],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique(Repository::class, 'slug')
                    ->where('owner_id', $repository->owner_id)
                    ->ignore($repository),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', Rule::enum(RepositoryVisibility::class)],
            'status' => ['required', Rule::enum(ProfileStatus::class)],
        ];
    }
}
