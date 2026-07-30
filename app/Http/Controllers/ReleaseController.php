<?php

namespace App\Http\Controllers;

use App\Actions\Releases\CreateReleaseDraft;
use App\Actions\Releases\DeleteRelease;
use App\Actions\Releases\PublishRelease;
use App\Actions\Releases\SuggestReleaseVersion;
use App\Actions\Releases\UpdateRelease;
use App\Enums\RepositoryVisibility;
use App\Http\Requests\Releases\DeleteReleaseRequest;
use App\Http\Requests\Releases\PublishReleaseRequest;
use App\Http\Requests\Releases\ReleaseVersionSuggestionRequest;
use App\Http\Requests\Releases\StoreReleaseDraftRequest;
use App\Http\Requests\Releases\UpdateReleaseRequest;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function create(ReleaseVersionSuggestionRequest $request, Repository $repository, SuggestReleaseVersion $suggestReleaseVersion): Response
    {
        return Inertia::render('releases/create', [
            'repository' => $this->repositoryDetail($repository),
            'suggestedVersion' => $suggestReleaseVersion($repository, $request->releaseType()),
            'release' => [
                'release_type' => $request->releaseType()->value,
                'version' => $suggestReleaseVersion($repository, $request->releaseType()),
                'title' => '',
                'body' => '',
                'visibility' => 'private',
                'change_entries' => [$this->emptyChangeEntry()],
            ],
        ]);
    }

    public function store(StoreReleaseDraftRequest $request, Repository $repository, CreateReleaseDraft $createReleaseDraft): RedirectResponse
    {
        $createReleaseDraft($repository, $request->draftAttributes());

        return to_route('repositories.show', $repository);
    }

    public function edit(ReleaseVersionSuggestionRequest $request, Release $release, SuggestReleaseVersion $suggestReleaseVersion): Response
    {
        Gate::authorize('view', $release);
        Gate::authorize('update', $release);

        return Inertia::render('releases/edit', [
            'repository' => $this->repositoryDetail($release->repository),
            'suggestedVersion' => $suggestReleaseVersion($release->repository, $request->releaseType()),
            'release' => [
                'public_id' => $release->public_id,
                'state' => $release->state->value,
                'version' => $release->version,
                'release_type' => $release->release_type->value,
                'title' => $release->title,
                'body' => $release->body,
                'visibility' => $release->visibility->value,
                'change_entries' => $release->changeEntries()->get()->map(fn ($changeEntry): array => [
                    'id' => $changeEntry->id,
                    'client_id' => 'entry-'.$changeEntry->id,
                    'change_type' => $changeEntry->change_type->value,
                    'content' => $changeEntry->content,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateReleaseRequest $request, Release $release, UpdateRelease $updateRelease): RedirectResponse
    {
        $release = $updateRelease($release, $request->releaseAttributes());

        return to_route('releases.show', $release);
    }

    public function publish(PublishReleaseRequest $request, Release $release, PublishRelease $publishRelease): RedirectResponse
    {
        $release = $publishRelease($release, $request->releaseAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Release published.')]);

        return to_route('releases.show', $release);
    }

    public function show(Release $release): Response
    {
        Gate::authorize('view', $release);

        $repository = $release->repository;
        $owner = $repository->owner;
        $isOwner = $owner->is(request()->user());

        return Inertia::render('releases/show', [
            'profile' => [
                'display_name' => $owner->display_name ?? $owner->name,
                'handle' => $owner->handle,
            ],
            'repository' => $this->repositoryDetail($repository),
            'release' => [
                'public_id' => $release->public_id,
                'version' => $release->version,
                'release_type' => $release->release_type->value,
                'state' => $release->state->value,
                'title' => $release->title,
                'body' => $release->body,
                'visibility' => $release->visibility->value,
                'published_at' => $release->published_at?->toIso8601String(),
                'edited_at' => $release->edited_at?->toIso8601String(),
                'change_entries' => $release->changeEntries()->get()->map(fn ($changeEntry): array => [
                    'change_type' => $changeEntry->change_type->value,
                    'content' => $changeEntry->content,
                ])->values(),
            ],
            'actions' => [
                'canUpdate' => Gate::allows('update', $release),
                'canDelete' => Gate::allows('delete', $release),
                'isOwner' => $isOwner,
            ],
            'copyLink' => $release->isPublished() && $release->visibility !== RepositoryVisibility::Private && $repository->visibility !== RepositoryVisibility::Private
                ? route('public.releases.show', $release)
                : null,
        ]);
    }

    public function destroy(DeleteReleaseRequest $request, Release $release, DeleteRelease $deleteRelease): RedirectResponse
    {
        $repository = $release->repository;
        $deleteRelease($release);

        return to_route('repositories.show', $repository);
    }

    /** @return array{public_id: string, name: string, slug: string, status: string, visibility: string} */
    private function repositoryDetail(Repository $repository): array
    {
        return [
            'public_id' => $repository->public_id,
            'name' => $repository->name,
            'slug' => $repository->slug,
            'status' => $repository->status->value,
            'visibility' => $repository->visibility->value,
        ];
    }

    /** @return array{client_id: string, change_type: string, content: string} */
    private function emptyChangeEntry(): array
    {
        return [
            'client_id' => (string) Str::uuid(),
            'change_type' => 'added',
            'content' => '',
        ];
    }
}
