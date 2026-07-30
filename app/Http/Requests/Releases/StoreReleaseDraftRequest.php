<?php

namespace App\Http\Requests\Releases;

use App\Domain\Releases\SemanticVersion;
use App\Domain\Repositories\VisibilityCeiling;
use App\Enums\ChangeType;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class StoreReleaseDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');

        return $repository instanceof Repository && $this->user()?->can('create', [Release::class, $repository]) === true;
    }

    protected function prepareForValidation(): void
    {
        $version = (string) $this->input('version');

        try {
            $version = SemanticVersion::normalize($version);
        } catch (InvalidArgumentException) {
            // The version rule returns the validation message for malformed input.
        }

        $this->merge([
            'version' => $version,
            'change_entries' => $this->nonEmptyChangeEntries(),
        ]);
    }

    /**
     * @return array{body: string|null, change_entries: list<array{change_type: string, content: string}>, release_type: string, title: string, version: string, visibility: string}
     */
    public function draftAttributes(): array
    {
        /** @var array{body: string|null, change_entries: array<array{change_type: string, content: string, client_id?: string}>, release_type: string, title: string, version: string, visibility: string} $validated */
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'version' => $validated['version'],
            'release_type' => $validated['release_type'],
            'body' => $validated['body'],
            'visibility' => $validated['visibility'],
            'change_entries' => array_values(array_map(
                fn (array $entry): array => [
                    'change_type' => $entry['change_type'],
                    'content' => $entry['content'],
                ],
                $validated['change_entries'],
            )),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $repository = $this->route('repository');

        return [
            'title' => ['required', 'string', 'min:1', 'max:160'],
            'version' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        SemanticVersion::fromString((string) $value);
                    } catch (InvalidArgumentException) {
                        $fail('The :attribute must use the major.minor.patch format.');
                    }
                },
                Rule::unique(Release::class, 'version')->where('repository_id', $repository instanceof Repository ? $repository->id : null),
            ],
            'release_type' => ['required', Rule::enum(ReleaseType::class)],
            'body' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['required', Rule::enum(RepositoryVisibility::class)],
            'change_entries' => ['present', 'array', 'max:50'],
            'change_entries.*.id' => ['prohibited'],
            'change_entries.*.client_id' => ['nullable', 'string', 'max:64'],
            'change_entries.*.change_type' => ['required', Rule::enum(ChangeType::class)],
            'change_entries.*.content' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $repository = $this->route('repository');
            $visibility = RepositoryVisibility::tryFrom((string) $this->input('visibility'));

            if ($repository instanceof Repository && $visibility !== null && ! VisibilityCeiling::allows($repository->visibility, $visibility)) {
                $validator->errors()->add('visibility', 'The release visibility cannot exceed the repository visibility.');
            }
        }];
    }

    /** @return array<array-key, array<string, mixed>> */
    private function nonEmptyChangeEntries(): array
    {
        $entries = $this->input('change_entries', []);

        if (! is_array($entries)) {
            return [];
        }

        return array_filter($entries, fn (mixed $entry): bool => is_array($entry) && trim((string) ($entry['content'] ?? '')) !== '');
    }
}
