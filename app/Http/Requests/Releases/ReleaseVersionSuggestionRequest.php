<?php

namespace App\Http\Requests\Releases;

use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseVersionSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');
        $release = $this->route('release');

        if ($repository instanceof Repository) {
            return $this->user()?->can('create', [Release::class, $repository]) === true;
        }

        // The controller runs the view policy first so private drafts deny as 404.
        return $release instanceof Release;
    }

    protected function failedAuthorization(): void
    {
        $repository = $this->route('repository');

        if ($repository instanceof Repository
            && $repository->visibility === RepositoryVisibility::Private
            && ! $repository->owner->is($this->user())) {
            throw (new AuthorizationException)->asNotFound();
        }

        parent::failedAuthorization();
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
