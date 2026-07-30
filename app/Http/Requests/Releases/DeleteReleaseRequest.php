<?php

namespace App\Http\Requests\Releases;

use App\Models\Release;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $release = $this->route('release');

        return $release instanceof Release && $this->user()?->can('delete', $release) === true;
    }

    protected function failedAuthorization(): void
    {
        $release = $this->route('release');

        if ($release instanceof Release
            && $release->isDraft()
            && ! $release->repository->owner->is($this->user())) {
            throw (new AuthorizationException)->asNotFound();
        }

        parent::failedAuthorization();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $release = $this->route('release');

        return $release instanceof Release
            ? ['confirmation' => ['required', 'string', Rule::in([$release->title])]]
            : [];
    }
}
