<?php

namespace App\Http\Requests\Releases;

use App\Domain\Releases\SemanticVersion;
use App\Domain\Repositories\VisibilityCeiling;
use App\Enums\ChangeType;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\ChangeEntry;
use App\Models\Release;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class UpdateReleaseRequest extends FormRequest
{
    public function getRedirectUrl(): string
    {
        $release = $this->route('release');

        return $release instanceof Release
            ? route('releases.edit', $release)
            : parent::getRedirectUrl();
    }

    public function authorize(): bool
    {
        $release = $this->route('release');

        return $release instanceof Release && $this->user()?->can('update', $release) === true;
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
     * @return array{body: string|null, change_entries: list<array{change_type: string, content: string, id?: int}>, release_type: string, title: string, version: string, visibility: string}
     */
    public function releaseAttributes(): array
    {
        /** @var array{body: string|null, change_entries: array<array{change_type: string, content: string, id?: int, client_id?: string}>, release_type: string, title: string, version: string, visibility: string} $validated */
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'version' => $validated['version'],
            'release_type' => $validated['release_type'],
            'body' => $validated['body'],
            'visibility' => $validated['visibility'],
            'change_entries' => array_values(array_map(
                fn (array $entry): array => array_filter([
                    'id' => $entry['id'] ?? null,
                    'change_type' => $entry['change_type'],
                    'content' => $entry['content'],
                ], fn (mixed $value): bool => $value !== null),
                $validated['change_entries'],
            )),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $release = $this->route('release');

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
                Rule::unique(Release::class, 'version')->where('repository_id', $release instanceof Release ? $release->repository_id : null)->ignore($release),
            ],
            'release_type' => ['required', Rule::enum(ReleaseType::class)],
            'body' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['required', Rule::enum(RepositoryVisibility::class)],
            'change_entries' => ['present', 'array', 'max:50'],
            'change_entries.*.id' => ['nullable', 'integer', 'distinct', Rule::exists(ChangeEntry::class, 'id')],
            'change_entries.*.client_id' => ['nullable', 'string', 'max:64'],
            'change_entries.*.change_type' => ['required', Rule::enum(ChangeType::class)],
            'change_entries.*.content' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $release = $this->route('release');
            $visibility = RepositoryVisibility::tryFrom((string) $this->input('visibility'));

            if (! $release instanceof Release || $visibility === null) {
                return;
            }

            if (! VisibilityCeiling::allows($release->repository->visibility, $visibility)) {
                $validator->errors()->add('visibility', 'The release visibility cannot exceed the repository visibility.');
            }

            foreach ((array) $this->input('change_entries', []) as $key => $entry) {
                if (! is_array($entry) || ! isset($entry['id'])) {
                    continue;
                }

                if (! $release->changeEntries()->whereKey($entry['id'])->exists()) {
                    $validator->errors()->add("change_entries.$key.id", 'The selected change entry does not belong to this release.');
                }
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
