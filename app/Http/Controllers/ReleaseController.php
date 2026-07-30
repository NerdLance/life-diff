<?php

namespace App\Http\Controllers;

use App\Actions\Releases\CreateReleaseDraft;
use App\Actions\Releases\DeleteRelease;
use App\Actions\Releases\SuggestReleaseVersion;
use App\Actions\Releases\UpdateRelease;
use App\Http\Requests\Releases\DeleteReleaseRequest;
use App\Http\Requests\Releases\ReleaseVersionSuggestionRequest;
use App\Http\Requests\Releases\StoreReleaseDraftRequest;
use App\Http\Requests\Releases\UpdateReleaseRequest;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReleaseController extends Controller
{
    public function create(ReleaseVersionSuggestionRequest $request, Repository $repository, SuggestReleaseVersion $suggestReleaseVersion): JsonResponse
    {
        return response()->json([
            'suggested_version' => $suggestReleaseVersion($repository, $request->releaseType()),
        ]);
    }

    public function store(StoreReleaseDraftRequest $request, Repository $repository, CreateReleaseDraft $createReleaseDraft): RedirectResponse
    {
        $createReleaseDraft($repository, $request->draftAttributes());

        return to_route('repositories.show', $repository);
    }

    public function edit(Release $release): JsonResponse
    {
        Gate::authorize('view', $release);
        Gate::authorize('update', $release);

        return response()->json([
            'release' => [
                'public_id' => $release->public_id,
                'version' => $release->version,
                'release_type' => $release->release_type->value,
                'title' => $release->title,
                'body' => $release->body,
                'visibility' => $release->visibility->value,
                'change_entries' => $release->changeEntries()->get()->map(fn ($changeEntry): array => [
                    'id' => $changeEntry->id,
                    'change_type' => $changeEntry->change_type->value,
                    'content' => $changeEntry->content,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateReleaseRequest $request, Release $release, UpdateRelease $updateRelease): RedirectResponse
    {
        $updateRelease($release, $request->releaseAttributes());

        return to_route('repositories.show', $release->repository);
    }

    public function destroy(DeleteReleaseRequest $request, Release $release, DeleteRelease $deleteRelease): RedirectResponse
    {
        $repository = $release->repository;
        $deleteRelease($release);

        return to_route('repositories.show', $repository);
    }
}
