<?php

namespace App\Http\Controllers;

use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublicRepositoryController extends Controller
{
    public function show(User $user, Repository $repository): Response
    {
        Gate::authorize('view', $repository);

        return Inertia::render('repositories/public-show', [
            'profile' => [
                'display_name' => $user->display_name ?? $user->name,
                'handle' => $user->handle,
            ],
            'repository' => [
                'name' => $repository->name,
                'slug' => $repository->slug,
                'description' => $repository->description,
                'status' => $repository->status->value,
                'visibility' => $repository->visibility->value,
            ],
            'publishedReleases' => $repository->releases()->published()->where('visibility', RepositoryVisibility::Public)->chronological()->limit(20)->get()->map($this->releaseItem(...))->values(),
        ]);
    }

    /** @return array{version: string, title: string, release_type: string, published_at: string|null} */
    private function releaseItem(Release $release): array
    {
        return [
            'version' => $release->version,
            'title' => $release->title,
            'release_type' => $release->release_type->value,
            'published_at' => $release->published_at?->toDateString(),
        ];
    }
}
