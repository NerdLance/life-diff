<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function show(User $user): Response
    {
        Gate::authorize('view', $user);

        return Inertia::render('profiles/show', [
            'profile' => [
                'display_name' => $user->display_name ?? $user->name,
                'handle' => $user->handle,
                'bio' => $user->bio,
                'status' => $user->status->value,
            ],
            'repositories' => $user->ownedRepositories()->publiclyListed()->latest()->get()->map($this->repositoryItem(...))->values(),
            'recentPublishedReleases' => Release::query()->publiclyListed()->whereHas('repository', fn (Builder $query) => $query->where('owner_id', $user->id))->with('repository:id,slug,name,owner_id')->chronological()->limit(6)->get()->map($this->releaseItem(...))->values(),
        ]);
    }

    /** @return array{name: string, slug: string, description: string|null, status: string} */
    private function repositoryItem(Repository $repository): array
    {
        return [
            'name' => $repository->name,
            'slug' => $repository->slug,
            'description' => $repository->description,
            'status' => $repository->status->value,
        ];
    }

    /** @return array{public_id: string, version: string, title: string, release_type: string, published_at: string|null, repository: array{name: string, slug: string}} */
    private function releaseItem(Release $release): array
    {
        return [
            'public_id' => $release->public_id,
            'version' => $release->version,
            'title' => $release->title,
            'release_type' => $release->release_type->value,
            'published_at' => $release->published_at?->toDateString(),
            'repository' => [
                'name' => $release->repository->name,
                'slug' => $release->repository->slug,
            ],
        ];
    }
}
