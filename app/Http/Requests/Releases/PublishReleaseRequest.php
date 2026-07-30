<?php

namespace App\Http\Requests\Releases;

use App\Models\Release;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;

class PublishReleaseRequest extends UpdateReleaseRequest
{
    public function authorize(): bool
    {
        $release = $this->route('release');

        return $release instanceof Release && $this->user()?->can('publish', $release) === true;
    }

    protected function failedAuthorization(): void
    {
        $release = $this->route('release');

        if ($release instanceof Release && ! $release->repository->owner->is($this->user())) {
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
            ...parent::rules(),
            'change_entries' => ['present', 'array', 'min:1', 'max:50'],
        ];
    }
}
